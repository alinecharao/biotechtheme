<?php
/**
 * Plugin Name: Biotech Inscrições no Sheets
 * Plugin URI: https://fazendaescolabiotech.com.br
 * Description: Sincroniza inscrições dos cursos com uma aba por turma no Google Sheets, em ordem alfabética.
 * Version: 2.0.1
 * Author: Fazenda Escola Biotech
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */
if (!defined('ABSPATH')) exit;

define('BIS_VERSION', '2.0.1');
define('BIS_PATH', plugin_dir_path(__FILE__));

require_once BIS_PATH . 'includes/class-bis-google-auth.php';
require_once BIS_PATH . 'includes/class-bis-sheets-api.php';
require_once BIS_PATH . 'includes/class-bis-sync.php';
require_once BIS_PATH . 'includes/class-bis-admin.php';

function bis_document_view_url($document_id, $file_path) {
    $signature = hash_hmac('sha256', absint($document_id) . '|' . ltrim((string) $file_path, '/'), wp_salt('auth'));
    return add_query_arg(array('action' => 'bis_view_document', 'doc_id' => absint($document_id), 'signature' => $signature), admin_url('admin-post.php'));
}

function bis_serve_student_document() {
    $document_id = absint($_GET['doc_id'] ?? 0);
    $signature = sanitize_text_field(wp_unslash($_GET['signature'] ?? ''));
    global $wpdb;
    $document = $document_id ? $wpdb->get_row($wpdb->prepare(
        "SELECT id, file_name, file_path FROM {$wpdb->prefix}cursos_student_documents WHERE id = %d",
        $document_id
    )) : null;
    if (!$document) wp_die('Documento não encontrado.', '', array('response' => 404));
    $expected = hash_hmac('sha256', $document_id . '|' . ltrim($document->file_path, '/'), wp_salt('auth'));
    if (!$signature || !hash_equals($expected, $signature)) wp_die('Link inválido.', '', array('response' => 403));

    $uploads = wp_upload_dir();
    $base_dir = realpath($uploads['basedir']);
    $absolute_path = realpath($uploads['basedir'] . '/' . ltrim($document->file_path, '/'));
    if (!$base_dir || !$absolute_path || strpos($absolute_path, $base_dir . DIRECTORY_SEPARATOR) !== 0 || !is_file($absolute_path)) {
        wp_die('Arquivo não encontrado.', '', array('response' => 404));
    }
    $mime = function_exists('mime_content_type') ? mime_content_type($absolute_path) : 'application/pdf';
    nocache_headers();
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($absolute_path));
    header('Content-Disposition: inline; filename="' . sanitize_file_name($document->file_name ?: basename($absolute_path)) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($absolute_path);
    exit;
}

add_action('admin_post_bis_view_document', 'bis_serve_student_document');
add_action('admin_post_nopriv_bis_view_document', 'bis_serve_student_document');

final class BIS_Plugin {
    private static $instance;
    public $auth;
    public $api;
    public $sync;

    public static function instance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        // A v2 usa uma fila incompatível com a arquitetura antiga.
        // Mantemos credenciais, planilhas e mapeamentos de abas, mas descartamos
        // somente a fila pendente da versão anterior.
        if (get_option('bis_plugin_version') !== BIS_VERSION) {
            wp_clear_scheduled_hook('bis_reconcile_queue_tick');
            delete_option('bis_reconcile_queue');
            update_option('bis_plugin_version', BIS_VERSION, false);
        }

        $this->auth = new BIS_Google_Auth();
        $this->api = new BIS_Sheets_API($this->auth);
        $this->sync = new BIS_Sync($this->api);
        $pending = get_option('bis_reconcile_queue', array());
        if (is_array($pending) && ($pending['status'] ?? '') === 'running'
            && !wp_next_scheduled('bis_reconcile_queue_tick')) {
            wp_schedule_single_event(time() + 30, 'bis_reconcile_queue_tick');
        }
        if (is_admin()) new BIS_Admin($this->auth, $this->sync);
        add_filter('cron_schedules', array(__CLASS__, 'cron_schedules'));
    }

    public static function activate() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$wpdb->prefix}bis_sheet_tabs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            course_id bigint(20) unsigned NOT NULL,
            class_key varchar(191) NOT NULL,
            profile_key varchar(191) NOT NULL,
            spreadsheet_id varchar(255) NOT NULL,
            sheet_id bigint(20) unsigned NOT NULL,
            sheet_title varchar(100) NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY course_class_profile (course_id,class_key,profile_key)
        ) {$charset};");
        dbDelta("CREATE TABLE {$wpdb->prefix}bis_sync_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned DEFAULT NULL,
            course_id bigint(20) unsigned DEFAULT NULL,
            status varchar(30) NOT NULL,
            message text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id), KEY order_id (order_id), KEY created_at (created_at)
        ) {$charset};");
        if (get_option('bis_auto_sync', null) === null) add_option('bis_auto_sync', '1', '', false);
        self::schedule();
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('bis_reconcile_event');
        wp_clear_scheduled_hook('bis_reconcile_queue_tick');
    }
    public static function cron_schedules($schedules) { return $schedules; }
    public static function schedule() {
        wp_clear_scheduled_hook('bis_reconcile_event');
        if (get_option('bis_auto_sync', '1') === '1') wp_schedule_event(time() + 300, 'hourly', 'bis_reconcile_event');
    }
}

register_activation_hook(__FILE__, array('BIS_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('BIS_Plugin', 'deactivate'));
add_action('plugins_loaded', array('BIS_Plugin', 'instance'));