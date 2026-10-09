<?php
if (!defined('ABSPATH')) exit;

class BIS_Sync {
    private $api;
    private $excluded_statuses = array('cancelled', 'canceled', 'failed', 'refunded', 'expired');

    public function __construct(BIS_Sheets_API $api) {
        $this->api = $api;

        add_action('cursos_payment_completed', array($this, 'on_order_event'), 30, 2);
        add_action('cursos_payment_cancelled', array($this, 'on_order_event'), 30, 2);
        add_action('cursos_payment_status_changed', array($this, 'on_status_event'), 30, 3);
        add_action('cursos_order_created', array($this, 'on_order_event'), 30, 2);

        add_action('save_post_curso', array($this, 'on_course_saved'), 60, 3);
        add_action('bis_reconcile_event', array($this, 'reconcile'));
        add_action('bis_reconcile_queue_tick', array($this, 'process_queue'));
    }

    public function on_order_event($order_id) {
        return $this->sync_order(absint($order_id), true);
    }

    public function on_status_event($order_id) {
        return $this->sync_order(absint($order_id), true);
    }

    public function on_course_saved($post_id, $post, $update) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
        if (!$post || $post->post_type !== 'curso' || $post->post_status === 'auto-draft') return;
        if (!current_user_can('edit_post', $post_id)) return;

        $this->ensure_course_tabs(absint($post_id));
    }

    public function connection_check() {
        $profile = $this->active_profile();
        if (!$profile) return new WP_Error('bis_no_profile', 'Defina a planilha ativa nas configurações do plugin.');

        $metadata = $this->api->metadata($profile['spreadsheet_id']);
        if (is_wp_error($metadata)) return $metadata;

        return array(
            'profile' => $profile,
            'title' => $metadata['properties']['title'] ?? $profile['name'],
        );
    }

    public function ensure_course_tabs($course_id) {
        $classes = $this->course_classes($course_id);
        if (!$classes) return 0;

        $count = 0;
        foreach ($classes as $class) {
            $key = $this->class_key($class);
            $result = $this->sync_class($course_id, $key, true);
            if (is_wp_error($result)) {
                $this->record_error($result, 0, $course_id);
                continue;
            }
            $count++;
        }
        return $count;
    }

    public function sync_order($order_id, $force = true) {
        if (!$order_id) return new WP_Error('bis_order_id', 'Pedido inválido.');

        global $wpdb;
        $table = $wpdb->prefix . 'cursos_orders';
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $order_id));
        if (!$order) return new WP_Error('bis_order_missing', 'Pedido não encontrado.');

        $class_key = $this->normalize_class_key($order->turma_id ?? '');
        return $this->sync_class(absint($order->curso_id), $class_key, $force);
    }

    public function reconcile($manual = false) {
        $queue = get_option('bis_reconcile_queue', array());
        if (is_array($queue) && ($queue['status'] ?? '') === 'running') {
            return array(
                'queued' => true,
                'checked' => count($queue['tasks'] ?? array()),
                'already_running' => true,
            );
        }

        return $this->start_queue((bool) $manual);
    }

    private function start_queue($force) {
        $tasks = $this->collect_tasks();

        $state = array(
            'status' => 'running',
            'force' => $force ? 1 : 0,
            'tasks' => $tasks,
            'position' => 0,
            'legacy_tabs' => $force ? $this->legacy_blank_candidates() : array(),
            'legacy_position' => 0,
            'legacy_removed' => 0,
            'synced' => 0,
            'skipped' => 0,
            'errors' => 0,
            'started_at' => time(),
        );

        update_option('bis_reconcile_queue', $state, false);
        $this->schedule_queue_tick(5);

        return array(
            'queued' => true,
            'checked' => count($tasks),
        );
    }

    public function process_queue() {
        if (!$this->acquire_lock()) {
            $this->schedule_queue_tick(75);
            return;
        }

        $state = get_option('bis_reconcile_queue', array());
        if (!is_array($state) || ($state['status'] ?? '') !== 'running') {
            $this->release_lock();
            return;
        }

        $processed = 0;
        while ($processed < 2 && $state['position'] < count($state['tasks'])) {
            $task = $state['tasks'][$state['position']];
            $result = $this->sync_class(
                absint($task['course_id']),
                (string) $task['class_key'],
                !empty($state['force'])
            );

            if (is_wp_error($result) && $result->get_error_code() === 'bis_rate_limited') {
                break;
            }

            $state['position']++;
            $processed++;

            if (is_wp_error($result)) {
                $state['errors']++;
                $this->record_error($result, 0, absint($task['course_id']));
            } elseif ($result === 'skipped') {
                $state['skipped']++;
            } else {
                $state['synced']++;
            }
        }

        if ($state['position'] >= count($state['tasks'])
            && $state['legacy_position'] < count($state['legacy_tabs'])) {
            $legacy = $state['legacy_tabs'][$state['legacy_position']];
            $values = $this->api->read_values($legacy['spreadsheet_id'], $legacy['title'], 'A1:N');

            if (is_wp_error($values) && $values->get_error_code() === 'bis_rate_limited') {
                // Aguarda o próximo ciclo sem avançar.
            } else {
                if (is_wp_error($values)) {
                    $state['errors']++;
                    $this->record_error($values, 0, 0);
                } elseif (empty($values['values'])) {
                    $deleted = $this->api->delete_tab($legacy['spreadsheet_id'], $legacy['sheet_id']);
                    if (is_wp_error($deleted) && $deleted->get_error_code() === 'bis_rate_limited') {
                        update_option('bis_reconcile_queue', $state, false);
                        $this->release_lock();
                        $this->schedule_queue_tick(75);
                        return;
                    }
                    if (is_wp_error($deleted)) {
                        $state['errors']++;
                        $this->record_error($deleted, 0, 0);
                    } else {
                        $state['legacy_removed']++;
                        $this->log('removed_blank_tab', 'Guia antiga e vazia removida: ' . $legacy['title'] . '.');
                    }
                }
                $state['legacy_position']++;
            }
        }

        if ($state['position'] >= count($state['tasks'])
            && $state['legacy_position'] >= count($state['legacy_tabs'])) {
            $state['status'] = 'completed';
            $state['completed_at'] = time();
            $this->log(
                'completed',
                sprintf(
                    'Reconciliação concluída: %d abas sincronizadas, %d sem alterações, %d guias antigas vazias removidas e %d erros.',
                    absint($state['synced']),
                    absint($state['skipped']),
                    absint($state['legacy_removed']),
                    absint($state['errors'])
                )
            );
        }

        update_option('bis_reconcile_queue', $state, false);
        $this->release_lock();

        if ($state['status'] === 'running') $this->schedule_queue_tick(75);
    }

    private function schedule_queue_tick($delay = 75) {
        if (!wp_next_scheduled('bis_reconcile_queue_tick')) {
            wp_schedule_single_event(time() + max(5, absint($delay)), 'bis_reconcile_queue_tick');
        }
    }

    private function collect_tasks() {
        $tasks = array();

        $course_ids = get_posts(array(
            'post_type' => 'curso',
            'post_status' => array('publish', 'draft', 'private', 'future'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
        ));

        foreach ($course_ids as $course_id) {
            foreach ($this->course_classes($course_id) as $class) {
                $key = $this->class_key($class);
                $tasks[$course_id . '|' . $key] = array(
                    'course_id' => absint($course_id),
                    'class_key' => $key,
                );
            }
        }

        global $wpdb;
        $orders = $wpdb->prefix . 'cursos_orders';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $orders)) === $orders) {
            $rows = $wpdb->get_results("SELECT DISTINCT curso_id, turma_id FROM {$orders} ORDER BY curso_id ASC, turma_id ASC");
            foreach ((array) $rows as $row) {
                $course_id = absint($row->curso_id);
                if (!$course_id) continue;
                $key = $this->normalize_class_key($row->turma_id ?? '');
                $tasks[$course_id . '|' . $key] = array(
                    'course_id' => $course_id,
                    'class_key' => $key,
                );
            }
        }

        return array_values($tasks);
    }

    private function legacy_blank_candidates() {
        global $wpdb;
        $profiles = (array) get_option('bis_sheet_profiles', array());
        $spreadsheet_ids = array();

        foreach ($profiles as $profile) {
            if (!empty($profile['spreadsheet_id'])) $spreadsheet_ids[] = $profile['spreadsheet_id'];
        }
        $spreadsheet_ids = array_unique($spreadsheet_ids);

        $candidates = array();
        foreach ($spreadsheet_ids as $spreadsheet_id) {
            $metadata = $this->api->metadata($spreadsheet_id, true);
            if (is_wp_error($metadata)) continue;

            foreach ((array) ($metadata['sheets'] ?? array()) as $sheet) {
                $title = (string) ($sheet['properties']['title'] ?? '');
                $sheet_id = intval($sheet['properties']['sheetId'] ?? 0);
                if (!$sheet_id || preg_match('/^curso_\d+$/i', $title)) continue;

                // Somente nomes gerados pelas versões antigas do plugin.
                if (!preg_match('/^Curso(?:\s+-)?\s*(?:\d{1,2}\/\d{1,2}|\d+)?$/u', $title)) continue;

                $mapped = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}bis_sheet_tabs WHERE spreadsheet_id = %s AND sheet_id = %d LIMIT 1",
                    $spreadsheet_id,
                    $sheet_id
                ));
                if ($mapped) continue;

                $candidates[] = array(
                    'spreadsheet_id' => $spreadsheet_id,
                    'sheet_id' => $sheet_id,
                    'title' => $title,
                );
            }
        }

        return $candidates;
    }

    private function sync_class($course_id, $class_key, $force = false) {
        $course = get_post($course_id);
        if (!$course || $course->post_type !== 'curso') {
            return new WP_Error('bis_course_missing', 'Curso não encontrado.');
        }

        $class = $this->find_class($course_id, $class_key);
        $profile = $this->profile_for_course_class($course_id, $class_key);
        if (!$profile) return new WP_Error('bis_no_profile', 'Defina a planilha ativa nas configurações do plugin.');

        $tab_info = $this->ensure_tab($course_id, $class, $profile);
        if (is_wp_error($tab_info)) return $tab_info;

        $tab = $tab_info['tab'];
        $rows = $this->registration_rows($course_id, $class_key);
        if (is_wp_error($rows)) return $rows;

        $hash = md5(wp_json_encode(array(
            'course' => $course->post_title,
            'class' => $class,
            'rows' => $rows,
        )));

        $hash_key = $this->hash_option_key($course_id, $class_key, $profile['spreadsheet_id']);
        if (!$force && empty($tab_info['created']) && get_option($hash_key) === $hash) {
            return 'skipped';
        }

        $result = $this->api->write_tab(
            $profile['spreadsheet_id'],
            intval($tab->sheet_id),
            $tab->sheet_title,
            $course->post_title,
            $this->class_name($class, $class_key),
            $this->class_date($class),
            $rows,
            $force || !empty($tab_info['created']) || !empty($tab_info['renamed'])
        );

        if (is_wp_error($result)) {
            if (!empty($tab_info['created'])) {
                // Não deixar guia vazia/orfã se a primeira gravação falhar.
                // Se a própria exclusão for bloqueada pela cota, preservamos o
                // mapeamento para que o próximo ciclo tente preencher a mesma aba.
                $deleted = $this->api->delete_tab($profile['spreadsheet_id'], intval($tab->sheet_id));
                if (!is_wp_error($deleted)) {
                    global $wpdb;
                    $wpdb->delete(
                        $wpdb->prefix . 'bis_sheet_tabs',
                        array('id' => absint($tab->id)),
                        array('%d')
                    );
                }
            }
            return $result;
        }

        update_option($hash_key, $hash, false);
        $this->log('synced', 'Aba ' . $tab->sheet_title . ' sincronizada.', 0, $course_id);
        return true;
    }

    private function ensure_tab($course_id, $class, $profile) {
        $class_key = $this->class_key($class);
        $tab = $this->find_tab($course_id, $class_key);
        $created = false;
        $renamed = false;

        if ($tab) {
            if (!preg_match('/^curso_\d+$/i', (string) $tab->sheet_title)) {
                $new_title = $this->next_tab_title($profile['spreadsheet_id']);
                if (is_wp_error($new_title)) return $new_title;
                $result = $this->api->rename_tab($profile['spreadsheet_id'], intval($tab->sheet_id), $new_title);
                if (is_wp_error($result)) return $result;

                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'bis_sheet_tabs',
                    array('sheet_title' => $new_title, 'updated_at' => current_time('mysql')),
                    array('id' => absint($tab->id)),
                    array('%s', '%s'),
                    array('%d')
                );
                $tab->sheet_title = $new_title;
                $renamed = true;
            }

            return array('tab' => $tab, 'created' => false, 'renamed' => $renamed);
        }

        $title = $this->next_tab_title($profile['spreadsheet_id']);
        if (is_wp_error($title)) return $title;
        $sheet = $this->api->create_tab($profile['spreadsheet_id'], $title);
        if (is_wp_error($sheet)) return $sheet;

        global $wpdb;
        $table = $wpdb->prefix . 'bis_sheet_tabs';
        $inserted = $wpdb->replace(
            $table,
            array(
                'course_id' => $course_id,
                'class_key' => $class_key,
                'profile_key' => $profile['key'],
                'spreadsheet_id' => $profile['spreadsheet_id'],
                'sheet_id' => intval($sheet['sheetId']),
                'sheet_title' => $title,
                'updated_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );

        if ($inserted === false) {
            $this->api->delete_tab($profile['spreadsheet_id'], intval($sheet['sheetId']));
            return new WP_Error('bis_mapping_failed', 'Não foi possível registrar a nova aba no WordPress.');
        }

        $tab = $this->find_tab($course_id, $class_key);
        if (!$tab) return new WP_Error('bis_mapping_missing', 'A aba foi criada, mas o mapeamento não pôde ser recuperado.');

        $created = true;
        return array('tab' => $tab, 'created' => $created, 'renamed' => false);
    }

    private function next_tab_title($spreadsheet_id) {
        $metadata = $this->api->metadata($spreadsheet_id, true);
        if (is_wp_error($metadata)) return $metadata;

        $max = 0;
        foreach ((array) ($metadata['sheets'] ?? array()) as $sheet) {
            $title = (string) ($sheet['properties']['title'] ?? '');
            if (preg_match('/^curso_(\d+)$/i', $title, $match)) {
                $max = max($max, intval($match[1]));
            }
        }
        return 'curso_' . ($max + 1);
    }

    private function registration_rows($course_id, $class_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'cursos_orders';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) return array();

        $class = $this->find_class($course_id, $class_key);
        $raw_id = trim((string) ($class['id'] ?? ''));

        if ($class_key === 'sem-turma' || $raw_id === '' || $raw_id === 'sem-turma') {
            $orders = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE curso_id = %d AND (turma_id IS NULL OR turma_id = '' OR turma_id = 'sem-turma') ORDER BY id ASC",
                $course_id
            ));
        } elseif ($raw_id !== '') {
            $orders = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE curso_id = %d AND turma_id = %s ORDER BY id ASC",
                $course_id,
                $raw_id
            ));
        } else {
            $orders = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE curso_id = %d AND turma_id = %s ORDER BY id ASC",
                $course_id,
                $class_key
            ));
        }

        $rows = array();
        foreach ((array) $orders as $order) {
            $status = strtolower(trim((string) ($order->status ?? '')));
            if (in_array($status, $this->excluded_statuses, true)) continue;
            $rows[] = $this->order_values($order);
        }

        usort($rows, function($a, $b) {
            $name_a = function_exists('remove_accents') ? remove_accents((string) ($a[1] ?? '')) : (string) ($a[1] ?? '');
            $name_b = function_exists('remove_accents') ? remove_accents((string) ($b[1] ?? '')) : (string) ($b[1] ?? '');
            return strnatcasecmp($name_a, $name_b);
        });

        return $rows;
    }

    private function order_values($order) {
        $proof = $this->proof_value($order);
        $referral_source = $this->prop($order, 'referral_source');
        $referral_detail = $this->prop($order, 'referral_detail');
        $referral = trim($referral_source . ($referral_detail ? ' - ' . $referral_detail : ''));

        $professional_type = $this->prop($order, 'professional_type');
        $is_student = intval($this->prop($order, 'is_student')) === 1;
        $type_labels = array(
            'veterinarian' => 'Médico Veterinário',
            'student' => 'Estudante',
            'general' => 'Geral',
        );
        $type = $type_labels[$professional_type] ?? ($is_student ? 'Estudante' : ($professional_type ?: 'Profissional'));

        $referral_labels = array(
            'google' => 'Google',
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'indicacao' => 'Indicação',
            'outro' => 'Outro',
        );
        if ($referral_source && isset($referral_labels[$referral_source])) {
            $referral = $referral_labels[$referral_source] . ($referral_detail ? ' - ' . $referral_detail : '');
        }

        $methods = array(
            'pix' => 'PIX',
            'credit_card' => 'Cartão de crédito',
            'boleto' => 'Boleto',
        );
        $method = $this->prop($order, 'payment_method');
        $method_label = $methods[$method] ?? $method;

        return array(
            $this->format_datetime($this->prop($order, 'created_at')),
            $this->prop($order, 'customer_name'),
            $this->prop($order, 'customer_email'),
            $this->prop($order, 'customer_cpf'),
            $this->prop($order, 'customer_phone'),
            $this->prop($order, 'customer_gender'),
            $referral,
            $type,
            $proof,
            $method_label,
            round((float) $this->prop($order, 'amount'), 2),
            $this->prop($order, 'coupon_code'),
            $this->status_label($this->prop($order, 'status')),
            (string) absint($order->id ?? 0),
        );
    }

    private function proof_value($order) {
        global $wpdb;

        $proof = $this->prop($order, 'crmv');
        $doc_table = $wpdb->prefix . 'cursos_student_documents';
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $doc_table)) === $doc_table;

        if (!$exists) return $proof;

        $document = null;
        $doc_id = absint($this->prop($order, 'diploma_document_id'));

        if ($doc_id) {
            $document = $wpdb->get_row($wpdb->prepare(
                "SELECT id, file_path, file_url FROM {$doc_table} WHERE id = %d",
                $doc_id
            ));
        }

        if (!$document && !empty($order->id)) {
            $document = $wpdb->get_row($wpdb->prepare(
                "SELECT id, file_path, file_url FROM {$doc_table} WHERE order_id = %d ORDER BY id DESC LIMIT 1",
                absint($order->id)
            ));
        }

        if (!$document && $this->prop($order, 'customer_email')) {
            $document = $wpdb->get_row($wpdb->prepare(
                "SELECT id, file_path, file_url FROM {$doc_table} WHERE customer_email = %s ORDER BY id DESC LIMIT 1",
                $this->prop($order, 'customer_email')
            ));
        }

        if ($document && !empty($document->file_path) && function_exists('bis_document_view_url')) {
            return bis_document_view_url($document->id, $document->file_path);
        }

        return $proof;
    }

    private function course_classes($course_id) {
        $classes = get_post_meta($course_id, '_curso_turmas', true);
        if (!is_array($classes) || !$classes) $classes = get_post_meta($course_id, '_turmas', true);
        if (!is_array($classes)) $classes = array();

        if (!$classes) {
            return array(array(
                'id' => 'sem-turma',
                'nome' => 'Inscrições',
                'data_inicio' => '',
                'data_fim' => '',
            ));
        }

        return array_values($classes);
    }

    private function find_class($course_id, $class_key) {
        foreach ($this->course_classes($course_id) as $class) {
            if ($this->class_key($class) === $class_key) return $class;
        }

        return array(
            'id' => $class_key === 'sem-turma' ? '' : $class_key,
            'nome' => $class_key === 'sem-turma' ? 'Inscrições' : $class_key,
            'data_inicio' => '',
            'data_fim' => '',
        );
    }

    private function class_key($class) {
        $id = trim((string) ($class['id'] ?? ''));
        if ($id !== '') return sanitize_text_field($id);

        $seed = trim((string) ($class['nome'] ?? '')) . '|' .
                trim((string) ($class['data_inicio'] ?? '')) . '|' .
                trim((string) ($class['data_fim'] ?? ''));

        if (trim(str_replace('|', '', $seed)) !== '') {
            return 'turma-' . substr(md5($seed), 0, 12);
        }
        return 'sem-turma';
    }

    private function normalize_class_key($value) {
        $value = trim((string) $value);
        return $value !== '' ? sanitize_text_field($value) : 'sem-turma';
    }

    private function class_name($class, $fallback) {
        $name = trim((string) ($class['nome'] ?? ''));
        return $name !== '' ? $name : ($fallback === 'sem-turma' ? 'Inscrições' : $fallback);
    }

    private function class_date($class) {
        $start = $this->format_date($class['data_inicio'] ?? '');
        $end = $this->format_date($class['data_fim'] ?? '');

        if ($start && $end && $start !== $end) return $start . ' a ' . $end;
        return $start ?: $end;
    }

    private function format_date($value) {
        $value = trim((string) $value);
        if ($value === '') return '';

        $timestamp = strtotime($value);
        return $timestamp ? date_i18n('d/m/Y', $timestamp) : $value;
    }

    private function format_datetime($value) {
        $timestamp = strtotime((string) $value);
        return $timestamp ? date_i18n('d/m/Y H:i', $timestamp) : (string) $value;
    }

    private function status_label($status) {
        $labels = array(
            'pending' => 'Pendente',
            'processing' => 'Processando',
            'paid' => 'Pago',
            'completed' => 'Concluído',
            'confirmed' => 'Confirmado',
        );
        $key = strtolower(trim((string) $status));
        return $labels[$key] ?? (string) $status;
    }

    private function prop($object, $property) {
        return (is_object($object) && isset($object->{$property})) ? (string) $object->{$property} : '';
    }

    private function active_profile() {
        $profiles = (array) get_option('bis_sheet_profiles', array());
        $key = get_option('bis_active_sheet_profile', '');

        if ((!$key || empty($profiles[$key])) && $profiles) {
            $keys = array_keys($profiles);
            $key = reset($keys);
        }

        if (!$key || empty($profiles[$key]['spreadsheet_id'])) return null;

        return array(
            'key' => $key,
            'name' => $profiles[$key]['name'] ?? 'Planilha',
            'spreadsheet_id' => $profiles[$key]['spreadsheet_id'],
        );
    }

    private function profile_for_course_class($course_id, $class_key) {
        $tab = $this->find_tab($course_id, $class_key);
        if (!$tab) return $this->active_profile();

        $profiles = (array) get_option('bis_sheet_profiles', array());
        return array(
            'key' => $tab->profile_key,
            'name' => $profiles[$tab->profile_key]['name'] ?? 'Planilha',
            'spreadsheet_id' => $tab->spreadsheet_id,
        );
    }

    private function find_tab($course_id, $class_key) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bis_sheet_tabs WHERE course_id = %d AND class_key = %s ORDER BY id ASC LIMIT 1",
            $course_id,
            $class_key
        ));
    }

    private function hash_option_key($course_id, $class_key, $spreadsheet_id) {
        return 'bis_hash_' . md5($course_id . '|' . $class_key . '|' . $spreadsheet_id);
    }

    private function acquire_lock() {
        $expires = absint(get_option('bis_sync_lock', 0));
        if ($expires && $expires > time()) return false;
        delete_option('bis_sync_lock');
        return add_option('bis_sync_lock', time() + 5 * MINUTE_IN_SECONDS, '', false);
    }

    private function release_lock() {
        delete_option('bis_sync_lock');
    }

    private function record_error($error, $order_id, $course_id) {
        $this->log('error', $error->get_error_message(), $order_id, $course_id);
        return $error;
    }

    private function log($status, $message, $order_id = 0, $course_id = 0) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'bis_sync_log',
            array(
                'order_id' => $order_id ?: null,
                'course_id' => $course_id ?: null,
                'status' => $status,
                'message' => $message,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
}
