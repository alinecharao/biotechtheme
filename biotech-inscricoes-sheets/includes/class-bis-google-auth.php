<?php
if (!defined('ABSPATH')) exit;

class BIS_Google_Auth {
    const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const USERINFO_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';
    const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';
    const SCOPES = 'https://www.googleapis.com/auth/spreadsheets https://www.googleapis.com/auth/userinfo.email';

    public function __construct() {
        add_action('admin_init', array($this, 'handle_callback'));
    }

    public function callback_url() {
        return admin_url('admin.php?page=biotech-inscricoes-sheets&bis_google_callback=1');
    }

    public function is_configured() {
        return (bool) get_option('bis_google_client_id') && (bool) $this->get_client_secret();
    }

    public function is_connected() {
        $tokens = $this->tokens();
        return !empty($tokens['refresh_token']);
    }

    public function email() {
        $tokens = $this->tokens();
        return isset($tokens['email']) ? $tokens['email'] : '';
    }

    public function auth_url() {
        if (!$this->is_configured()) return '';
        $state = wp_generate_password(32, false, false);
        set_transient('bis_oauth_state_' . get_current_user_id(), $state, 10 * MINUTE_IN_SECONDS);
        return self::AUTH_URL . '?' . http_build_query(array(
            'client_id' => get_option('bis_google_client_id'),
            'redirect_uri' => $this->callback_url(),
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ));
    }

    public function handle_callback() {
        if (!is_admin() || !current_user_can('manage_options')) return;
        if (empty($_GET['page']) || $_GET['page'] !== 'biotech-inscricoes-sheets' || empty($_GET['bis_google_callback'])) return;
        $redirect = admin_url('admin.php?page=biotech-inscricoes-sheets');
        if (!empty($_GET['error'])) {
            wp_safe_redirect(add_query_arg('bis_notice', 'google_denied', $redirect));
            exit;
        }
        $expected = get_transient('bis_oauth_state_' . get_current_user_id());
        $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
        $code = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
        if (!$expected || !hash_equals($expected, $state) || !$code) {
            wp_safe_redirect(add_query_arg('bis_notice', 'google_security', $redirect));
            exit;
        }
        delete_transient('bis_oauth_state_' . get_current_user_id());
        $response = wp_remote_post(self::TOKEN_URL, array('timeout' => 20, 'body' => array(
            'client_id' => get_option('bis_google_client_id'),
            'client_secret' => $this->get_client_secret(),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->callback_url(),
        )));
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            wp_safe_redirect(add_query_arg('bis_notice', 'google_error', $redirect));
            exit;
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['access_token'])) {
            wp_safe_redirect(add_query_arg('bis_notice', 'google_error', $redirect));
            exit;
        }
        $tokens = array(
            'access_token' => $body['access_token'],
            'refresh_token' => isset($body['refresh_token']) ? $body['refresh_token'] : '',
            'expires_at' => time() + intval(isset($body['expires_in']) ? $body['expires_in'] : 3600),
        );
        $info = wp_remote_get(self::USERINFO_URL, array('headers' => array('Authorization' => 'Bearer ' . $body['access_token'])));
        if (!is_wp_error($info)) {
            $user = json_decode(wp_remote_retrieve_body($info), true);
            $tokens['email'] = isset($user['email']) ? sanitize_email($user['email']) : '';
        }
        update_option('bis_google_tokens', $this->encrypt($tokens), false);
        wp_safe_redirect(add_query_arg('bis_notice', 'google_connected', $redirect));
        exit;
    }

    public function access_token() {
        $tokens = $this->tokens();
        if (empty($tokens['access_token'])) return new WP_Error('bis_not_connected', 'A conta Google não está conectada.');
        if (!empty($tokens['expires_at']) && intval($tokens['expires_at']) > time() + 300) return $tokens['access_token'];
        if (empty($tokens['refresh_token'])) return new WP_Error('bis_refresh_missing', 'Reconecte a conta Google.');
        $response = wp_remote_post(self::TOKEN_URL, array('timeout' => 20, 'body' => array(
            'client_id' => get_option('bis_google_client_id'),
            'client_secret' => $this->get_client_secret(),
            'refresh_token' => $tokens['refresh_token'],
            'grant_type' => 'refresh_token',
        )));
        if (is_wp_error($response)) return $response;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (wp_remote_retrieve_response_code($response) !== 200 || empty($body['access_token'])) {
            return new WP_Error('bis_refresh_failed', 'Não foi possível renovar o acesso ao Google. Reconecte a conta.');
        }
        $tokens['access_token'] = $body['access_token'];
        $tokens['expires_at'] = time() + intval(isset($body['expires_in']) ? $body['expires_in'] : 3600);
        update_option('bis_google_tokens', $this->encrypt($tokens), false);
        return $tokens['access_token'];
    }

    public function save_credentials($client_id, $client_secret) {
        update_option('bis_google_client_id', sanitize_text_field($client_id), false);
        if ($client_secret !== '') update_option('bis_google_client_secret', $this->encrypt($client_secret), false);
    }

    public function disconnect() {
        $token = $this->access_token();
        if (!is_wp_error($token)) wp_remote_post(self::REVOKE_URL, array('timeout' => 15, 'body' => array('token' => $token)));
        delete_option('bis_google_tokens');
    }

    private function get_client_secret() {
        $stored = get_option('bis_google_client_secret', '');
        return $stored ? $this->decrypt($stored) : '';
    }

    private function tokens() {
        $stored = get_option('bis_google_tokens', '');
        $tokens = $stored ? $this->decrypt($stored) : array();
        return is_array($tokens) ? $tokens : array();
    }

    private function key() {
        $salt = defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : wp_salt('secure_auth');
        return hash('sha256', $salt . '|biotech-inscricoes-sheets', true);
    }

    private function encrypt($value) {
        $iv = openssl_random_pseudo_bytes(16);
        $json = wp_json_encode($value);
        $cipher = openssl_encrypt($json, 'AES-256-CBC', $this->key(), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    private function decrypt($value) {
        $raw = base64_decode($value, true);
        if ($raw === false || strlen($raw) < 17) return '';
        $plain = openssl_decrypt(substr($raw, 16), 'AES-256-CBC', $this->key(), OPENSSL_RAW_DATA, substr($raw, 0, 16));
        if ($plain === false) return '';
        $decoded = json_decode($plain, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $plain;
    }
}