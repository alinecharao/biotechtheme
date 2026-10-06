<?php
if (!defined('ABSPATH')) exit;

class BIS_Sync {
    const CONFIRMED = array('confirmed', 'completed');
    private $api;

    public function __construct(BIS_Sheets_API $api) {
        $this->api = $api;
        add_action('cursos_payment_completed', array($this, 'on_order_event'), 30, 2);
        add_action('cursos_payment_cancelled', array($this, 'on_order_event'), 30, 2);
        add_action('cursos_payment_status_changed', array($this, 'on_status_event'), 30, 3);
        add_action('cursos_order_created', array($this, 'on_order_event'), 30, 2);
        add_action('bis_reconcile_event', array($this, 'reconcile'));
    }

    public function on_order_event($order_id) { $this->sync_related_orders(absint($order_id)); }
    public function on_status_event($order_id) { $this->sync_related_orders(absint($order_id)); }

    private function sync_related_orders($order_id) {
        if (!$order_id) return;
        global $wpdb;
        $orders = $wpdb->prefix . 'cursos_orders';
        $token = $wpdb->get_var($wpdb->prepare("SELECT checkout_token FROM {$orders} WHERE id = %d", $order_id));
        $ids = $token ? $wpdb->get_col($wpdb->prepare("SELECT id FROM {$orders} WHERE checkout_token = %s ORDER BY id ASC LIMIT 20", $token)) : array($order_id);
        foreach ($ids as $id) $this->sync_order(absint($id));
    }
    public function on_course_saved($post_id, $post, $update) {
        if (wp_is_post_revision($post_id) || $post->post_status === 'auto-draft') return;
        if (!current_user_can('edit_post', $post_id)) return;
        $this->ensure_course_tabs($post_id);
    }

    public function ensure_course_tabs($course_id) {
        $profile = $this->active_profile();
        if (!$profile) return new WP_Error('bis_no_profile', 'Defina a planilha ativa nas configurações do plugin.');
        $classes = get_post_meta($course_id, '_curso_turmas', true);
        if (!is_array($classes) || !$classes) $classes = get_post_meta($course_id, '_turmas', true);
        if (!is_array($classes) || !$classes) $classes = array(array('id' => 'sem-turma', 'nome' => 'Inscrições', 'data_inicio' => '', 'data_fim' => ''));
        $created = 0;
        foreach ($classes as $class) {
            if ($this->class_is_past($class)) continue;
            $result = $this->ensure_tab($course_id, $class, $profile);
            if (!is_wp_error($result)) $created++;
            else $this->log('error', $result->get_error_message(), 0, $course_id);
        }
        return $created;
    }

    public function connection_check() {
        $profile = $this->active_profile();
        if (!$profile) return new WP_Error('bis_no_profile', 'Defina a planilha ativa nas configurações do plugin.');
        $metadata = $this->api->metadata($profile['spreadsheet_id']);
        if (is_wp_error($metadata)) return $metadata;
        return array(
            'profile' => $profile,
            'title' => !empty($metadata['properties']['title']) ? $metadata['properties']['title'] : $profile['name'],
        );
    }

    public function sync_order($order_id) {
        if (!$order_id) return new WP_Error('bis_order_id', 'Pedido inválido.');
        global $wpdb;
        $orders = $wpdb->prefix . 'cursos_orders';
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$orders} WHERE id = %d", $order_id));
        if (!$order) return new WP_Error('bis_order_missing', 'Pedido não encontrado.');
        $class = $this->find_class($order->curso_id, $order->turma_id);
        $tab = $this->find_tab($order->curso_id, $order->turma_id);
        $profile = $tab ? $this->profile_for_tab($tab) : $this->active_profile();
        if (!$profile) return new WP_Error('bis_no_profile', 'Defina a planilha ativa nas configurações do plugin.');
        if ($tab) {
            $renamed = $this->rename_tab_if_needed($tab, $class, $profile);
            if (is_wp_error($renamed)) return $this->record_error($renamed, $order_id, $order->curso_id);
            $tab = $renamed;
        }
        if (!$tab && in_array($order->status, self::CONFIRMED, true)) {
            // Se existe inscrição confirmada, a turma precisa existir na planilha,
            // mesmo que a data da turma já tenha passado.
            $tab = $this->ensure_tab($order->curso_id, $class, $profile);
        }
        if (is_wp_error($tab)) return $tab;
        if (!$tab) return 'ignored';
        $index = $this->api->order_index($profile['spreadsheet_id'], $tab->sheet_title);
        if (is_wp_error($index)) return $this->record_error($index, $order_id, $order->curso_id);
        $row = isset($index[(string) $order_id]) ? intval($index[(string) $order_id]) : 0;
        if (!in_array($order->status, self::CONFIRMED, true)) {
            if ($row) {
                $result = $this->api->delete_row($profile['spreadsheet_id'], $tab->sheet_id, $row);
                if (is_wp_error($result)) return $this->record_error($result, $order_id, $order->curso_id);
                $this->log('removed', 'Inscrição removida após mudança de status.', $order_id, $order->curso_id);
            }
            return true;
        }
        $result = $this->api->write_order($profile['spreadsheet_id'], $tab->sheet_title, $row, $this->order_values($order));
        if (is_wp_error($result)) return $this->record_error($result, $order_id, $order->curso_id);

        // Manter a guia em ordem cronológica estável pelo ID do pedido (coluna M, oculta).
        $sorted = $this->api->sort_tab($profile['spreadsheet_id'], $tab->sheet_id);
        if (is_wp_error($sorted)) return $this->record_error($sorted, $order_id, $order->curso_id);

        $this->log($row ? 'updated' : 'added', $row ? 'Inscrição atualizada.' : 'Inscrição adicionada.', $order_id, $order->curso_id);
        return true;
    }

    public function reconcile($manual = false) {
        if (!$this->acquire_lock()) return new WP_Error('bis_sync_locked', 'Já existe uma sincronização em andamento. Aguarde alguns minutos e tente novamente.');
        global $wpdb;
        $orders = $wpdb->prefix . 'cursos_orders';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $orders)) !== $orders) {
            $this->release_lock();
            return new WP_Error('bis_orders_table_missing', 'A tabela de pedidos não foi encontrada.');
        }
        $connection = $this->connection_check();
        if (is_wp_error($connection)) {
            $this->log('error', $connection->get_error_message());
            $this->release_lock();
            return $connection;
        }
        $renamed = 0;
        $rename_errors = 0;
        if ($manual) {
            $rename_result = $this->rename_existing_tabs();
            $renamed = $rename_result['renamed'];
            $rename_errors = $rename_result['errors'];
        }
        $cursor = absint(get_option('bis_reconcile_cursor', 0));
        $limit = 50;
        if ($manual) {
            // Reconciliação manual é completa: revisa todos os pedidos confirmados,
            // do mais antigo ao mais recente, para recuperar inscrições ausentes.
            $placeholders = implode(',', array_fill(0, count(self::CONFIRMED), '%s'));
            $ids = $wpdb->get_col($wpdb->prepare(
                "SELECT id FROM {$orders} WHERE status IN ({$placeholders}) ORDER BY id ASC",
                self::CONFIRMED
            ));
        } else {
            $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$orders} WHERE id > %d ORDER BY id ASC LIMIT %d", $cursor, $limit));
        }
        if (!$manual && !$ids && $cursor) {
            update_option('bis_reconcile_cursor', 0, false);
            $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$orders} ORDER BY id ASC LIMIT %d", $limit));
        }
        $result = array('checked' => count($ids), 'synced' => 0, 'ignored' => 0, 'errors' => $rename_errors, 'renamed' => $renamed);
        foreach ($ids as $id) {
            $order_id = absint($id);
            $sync = $this->sync_order($order_id);
            if (is_wp_error($sync)) {
                $result['errors']++;
            } elseif ($sync === 'ignored') {
                $result['ignored']++;
            } else {
                $result['synced']++;
            }
        }
        if (!$manual && $ids) update_option('bis_reconcile_cursor', max(array_map('intval', $ids)), false);
        $this->release_lock();
        return $result;
    }

    private function ensure_tab($course_id, $class, $profile) {
        $class_id = $this->class_id($class);
        $existing = $this->find_tab($course_id, $class_id);
        if ($existing) return $existing;
        $metadata = $this->api->metadata($profile['spreadsheet_id']);
        if (is_wp_error($metadata)) return $metadata;
        $title = $this->tab_title($course_id, $class);
        $sheet = null;
        foreach ((array) (isset($metadata['sheets']) ? $metadata['sheets'] : array()) as $candidate) {
            if (!isset($candidate['properties']['title']) || $candidate['properties']['title'] !== $title) continue;
            global $wpdb;
            $mapped = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}bis_sheet_tabs WHERE spreadsheet_id = %s AND sheet_id = %d LIMIT 1",
                $profile['spreadsheet_id'],
                intval($candidate['properties']['sheetId'])
            ));
            if (!$mapped) $sheet = $candidate['properties'];
        }
        if (!$sheet) {
            $occupied = array();
            foreach ((array) (isset($metadata['sheets']) ? $metadata['sheets'] : array()) as $candidate) {
                if (!empty($candidate['properties']['title'])) $occupied[$candidate['properties']['title']] = true;
            }
            if (isset($occupied[$title])) {
                $suffix = $this->safe_title_part($class_id);
                $title = mb_substr($title . ' - ' . ($suffix ?: $course_id), 0, 100);
                $counter = 2;
                while (isset($occupied[$title])) {
                    $title = mb_substr($this->tab_title($course_id, $class), 0, 94) . ' - ' . $counter;
                    $counter++;
                }
            }
        }
        $created_now = false;
        if (!$sheet) {
            $sheet = $this->api->create_tab($profile['spreadsheet_id'], $title);
            $created_now = true;
        }
        if (is_wp_error($sheet)) return $sheet;

        $setup = $this->api->setup_tab(
            $profile['spreadsheet_id'],
            $sheet['sheetId'],
            $sheet['title'],
            get_the_title($course_id),
            isset($class['nome']) ? $class['nome'] : $class_id,
            $this->class_date($class)
        );

        if (is_wp_error($setup)) {
            // Evita deixar guias vazias quando a estrutura inicial falha.
            if ($created_now) $this->api->delete_tab($profile['spreadsheet_id'], $sheet['sheetId']);
            return $setup;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'bis_sheet_tabs';
        $wpdb->replace(
            $table,
            array(
                'course_id' => $course_id,
                'class_key' => $class_id,
                'profile_key' => $profile['key'],
                'spreadsheet_id' => $profile['spreadsheet_id'],
                'sheet_id' => intval($sheet['sheetId']),
                'sheet_title' => $sheet['title'],
                'updated_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );

        return $this->find_tab($course_id, $class_id);
    }

    private function rename_existing_tabs() {
        global $wpdb;
        $tabs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bis_sheet_tabs ORDER BY spreadsheet_id ASC, id ASC");
        $result = array('renamed' => 0, 'errors' => 0);
        foreach ((array) $tabs as $tab) {
            $class = $this->find_class(absint($tab->course_id), $tab->class_key);
            $profile = $this->profile_for_tab($tab);
            $previous_title = $tab->sheet_title;
            $renamed = $this->rename_tab_if_needed($tab, $class, $profile);
            if (is_wp_error($renamed)) {
                $result['errors']++;
                $this->log('error', 'Não foi possível renomear a guia: ' . $renamed->get_error_message(), 0, absint($tab->course_id));
                continue;
            }
            if ($renamed->sheet_title !== $previous_title) {
                    $result['renamed']++;
                    $this->log('renamed', 'Guia renomeada para ' . $renamed->sheet_title . '.', 0, absint($tab->course_id));
            }
            $formatted = $this->api->format_tab($profile['spreadsheet_id'], $renamed->sheet_id);
            if (is_wp_error($formatted)) {
                $result['errors']++;
                $this->log('error', 'Não foi possível formatar a guia: ' . $formatted->get_error_message(), 0, absint($tab->course_id));
            }
        }
        return $result;
    }

    private function rename_tab_if_needed($tab, $class, $profile) {
        $preferred = $this->tab_title(absint($tab->course_id), $class);
        if ($tab->sheet_title === $preferred) return $tab;
        $metadata = $this->api->metadata($profile['spreadsheet_id']);
        if (is_wp_error($metadata)) return $metadata;
        $occupied = array();
        foreach ((array) ($metadata['sheets'] ?? array()) as $sheet) {
            if (empty($sheet['properties']['title'])) continue;
            $sheet_id = intval($sheet['properties']['sheetId'] ?? 0);
            if ($sheet_id !== intval($tab->sheet_id)) $occupied[$sheet['properties']['title']] = true;
        }
        $target = $preferred;
        if (isset($occupied[$target])) {
            $suffix = $this->safe_title_part($this->class_id($class));
            $target = mb_substr($preferred . ' - ' . ($suffix ?: absint($tab->course_id)), 0, 100);
            $counter = 2;
            while (isset($occupied[$target])) {
                $target = mb_substr($preferred, 0, 94) . ' - ' . $counter;
                $counter++;
            }
        }
        if ($tab->sheet_title === $target) return $tab;
        $renamed = $this->api->rename_tab($profile['spreadsheet_id'], $tab->sheet_id, $target);
        if (is_wp_error($renamed)) return $renamed;
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'bis_sheet_tabs',
            array('sheet_title' => $target, 'updated_at' => current_time('mysql')),
            array('id' => absint($tab->id)),
            array('%s', '%s'),
            array('%d')
        );
        $tab->sheet_title = $target;
        return $tab;
    }

    private function active_profile() {
        $profiles = (array) get_option('bis_sheet_profiles', array());
        $key = get_option('bis_active_sheet_profile', '');
        if ((!$key || empty($profiles[$key])) && $profiles) {
            $keys = array_keys($profiles);
            $key = reset($keys);
        }
        if (!$key || empty($profiles[$key]['spreadsheet_id'])) return null;
        return array('key' => $key, 'name' => $profiles[$key]['name'], 'spreadsheet_id' => $profiles[$key]['spreadsheet_id']);
    }

    private function profile_for_tab($tab) {
        $profiles = (array) get_option('bis_sheet_profiles', array());
        $name = !empty($profiles[$tab->profile_key]['name']) ? $profiles[$tab->profile_key]['name'] : 'Planilha anterior';
        return array('key' => $tab->profile_key, 'name' => $name, 'spreadsheet_id' => $tab->spreadsheet_id);
    }

    private function find_tab($course_id, $class_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bis_sheet_tabs WHERE course_id = %d AND class_key = %s ORDER BY id ASC LIMIT 1", $course_id, $class_id ?: 'sem-turma'));
    }

    private function find_class($course_id, $class_id) {
        $classes = get_post_meta($course_id, '_curso_turmas', true);
        if (!is_array($classes) || !$classes) $classes = get_post_meta($course_id, '_turmas', true);
        foreach ((array) $classes as $class) if ($this->class_id($class) === ($class_id ?: 'sem-turma')) return $class;
        return array('id' => $class_id ?: 'sem-turma', 'nome' => $class_id ?: 'Inscrições', 'data_inicio' => '', 'data_fim' => '');
    }

    private function class_id($class) {
        if (!empty($class['id'])) return sanitize_text_field($class['id']);

        // Turmas antigas podem não ter ID preenchido. Use nome/data como chave estável
        // para impedir que várias turmas diferentes colidam em "sem-turma".
        $seed = trim((string) ($class['nome'] ?? '')) . '|' .
                trim((string) ($class['data_inicio'] ?? '')) . '|' .
                trim((string) ($class['data_fim'] ?? ''));
        if (trim(str_replace('|', '', $seed)) !== '') return 'turma-' . substr(md5($seed), 0, 12);

        return 'sem-turma';
    }
    private function class_is_past($class) {
        $date = !empty($class['data_fim']) ? $class['data_fim'] : (!empty($class['data_inicio']) ? $class['data_inicio'] : '');
        return $date && strtotime($date . ' 23:59:59') < current_time('timestamp');
    }
    private function class_date($class) {
        $start = !empty($class['data_inicio']) ? date_i18n('d/m/Y', strtotime($class['data_inicio'])) : '';
        $end = !empty($class['data_fim']) ? date_i18n('d/m/Y', strtotime($class['data_fim'])) : '';
        return $start && $end && $start !== $end ? $start . ' a ' . $end : ($start ?: $end);
    }
    private function tab_title($course_id, $class) {
        $date = !empty($class['data_inicio']) ? $this->date_timestamp($class['data_inicio']) : 0;
        if ($date) return 'Curso ' . wp_date('d/m', $date);
        return mb_substr('Curso ' . $this->safe_title_part($this->class_id($class) ?: $course_id), 0, 100);
    }

    private function date_timestamp($date) {
        $date = trim((string) $date);
        if (!$date) return 0;
        $timezone = wp_timezone();
        foreach (array('!Y-m-d', '!d/m/Y') as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $date, $timezone);
            $errors = DateTimeImmutable::getLastErrors();
            if ($parsed && ($errors === false || (!$errors['warning_count'] && !$errors['error_count']))) return $parsed->getTimestamp();
        }
        $timestamp = strtotime($date);
        return $timestamp ? $timestamp : 0;
    }
    private function safe_title_part($value) {
        return trim(preg_replace('/[\\\/\?\*\[\]:]/', '-', sanitize_text_field((string) $value)));
    }

    private function order_values($order) {
        global $wpdb;
        $proof = $order->crmv;
        $document = null;
        if (!$proof) {
            $doc_id = !empty($order->diploma_document_id) ? absint($order->diploma_document_id) : 0;
            if ($doc_id) $document = $wpdb->get_row($wpdb->prepare("SELECT id, file_path, file_url FROM {$wpdb->prefix}cursos_student_documents WHERE id = %d", $doc_id));
            if (!$document) $document = $wpdb->get_row($wpdb->prepare("SELECT id, file_path, file_url FROM {$wpdb->prefix}cursos_student_documents WHERE order_id = %d ORDER BY id DESC LIMIT 1", $order->id));
            if (!$document && !empty($order->customer_email)) $document = $wpdb->get_row($wpdb->prepare("SELECT id, file_path, file_url FROM {$wpdb->prefix}cursos_student_documents WHERE customer_email = %s ORDER BY id DESC LIMIT 1", $order->customer_email));
        } elseif (filter_var($proof, FILTER_VALIDATE_URL)) {
            $document = $wpdb->get_row($wpdb->prepare("SELECT id, file_path, file_url FROM {$wpdb->prefix}cursos_student_documents WHERE file_url = %s ORDER BY id DESC LIMIT 1", $proof));
        }
        if ($document) $proof = bis_document_view_url($document->id, $document->file_path);
        $referral = trim($order->referral_source . (!empty($order->referral_detail) ? ' - ' . $order->referral_detail : ''));
        $methods = array('pix' => 'PIX', 'credit_card' => 'Cartão de crédito', 'boleto' => 'Boleto');
        $professional = $order->professional_type === 'student' ? 'Estudante' : ($order->professional_type ?: 'Profissional');
        return array(
            date_i18n('d/m/Y H:i', strtotime($order->created_at)), $order->customer_name, $order->customer_email, $order->customer_cpf,
            $order->customer_phone, $order->customer_gender, $referral, $professional, $proof ?: '',
            isset($methods[$order->payment_method]) ? $methods[$order->payment_method] : $order->payment_method,
            round((float) $order->amount, 2), $order->coupon_code ?: '', (string) $order->id,
        );
    }

    private function acquire_lock() {
        $expires = absint(get_option('bis_sync_lock', 0));
        if ($expires && $expires > time()) return false;
        delete_option('bis_sync_lock');
        return add_option('bis_sync_lock', time() + 5 * MINUTE_IN_SECONDS, '', false);
    }
    private function release_lock() { delete_option('bis_sync_lock'); }
    private function record_error($error, $order_id, $course_id) { $this->log('error', $error->get_error_message(), $order_id, $course_id); return $error; }
    private function log($status, $message, $order_id = 0, $course_id = 0) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'bis_sync_log', array('order_id' => $order_id ?: null, 'course_id' => $course_id ?: null, 'status' => $status, 'message' => $message, 'created_at' => current_time('mysql')), array('%d', '%d', '%s', '%s', '%s'));
    }
}