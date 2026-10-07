<?php
if (!defined('ABSPATH')) exit;

class BIS_Sync {
    const CONFIRMED = array('confirmed', 'completed');
    private $api;
    private $batch_mode = false;
    private $order_index_cache = array();
    private $deferred_sorts = array();

    public function __construct(BIS_Sheets_API $api) {
        $this->api = $api;
        add_action('cursos_payment_completed', array($this, 'on_order_event'), 30, 2);
        add_action('cursos_payment_cancelled', array($this, 'on_order_event'), 30, 2);
        add_action('cursos_payment_status_changed', array($this, 'on_status_event'), 30, 3);
        add_action('cursos_order_created', array($this, 'on_order_event'), 30, 2);
        add_action('bis_reconcile_event', array($this, 'reconcile'));
        add_action('bis_reconcile_queue_tick', array($this, 'process_reconcile_queue'));
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
        $index = $this->get_order_index($profile['spreadsheet_id'], $tab->sheet_title);
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

        if ($this->batch_mode) {
            $cache_key = $this->index_cache_key($profile['spreadsheet_id'], $tab->sheet_title);
            if (!$row) {
                $rows = array_values($this->order_index_cache[$cache_key] ?? array());
                $next_row = $rows ? (max(array_map('intval', $rows)) + 1) : 6;
                $this->order_index_cache[$cache_key][(string) $order_id] = $next_row;
            }
            $sort_key = $profile['spreadsheet_id'] . '|' . intval($tab->sheet_id);
            $this->deferred_sorts[$sort_key] = array(
                'spreadsheet_id' => $profile['spreadsheet_id'],
                'sheet_id' => intval($tab->sheet_id),
                'course_id' => absint($order->curso_id),
            );
        } else {
            // Em sincronizações isoladas, ordenar imediatamente.
            $sorted = $this->api->sort_tab($profile['spreadsheet_id'], $tab->sheet_id);
            if (is_wp_error($sorted)) return $this->record_error($sorted, $order_id, $order->curso_id);
        }

        $this->log($row ? 'updated' : 'added', $row ? 'Inscrição atualizada.' : 'Inscrição adicionada.', $order_id, $order->curso_id);
        return true;
    }

    /**
     * A reconciliação completa é processada em segundo plano, em pequenos
     * lotes. Nunca enviar centenas de gravações na mesma requisição PHP.
     */
    private function start_reconcile_queue() {
        $current = get_option('bis_reconcile_queue', array());
        if (is_array($current) && ($current['status'] ?? '') === 'running') {
            return new WP_Error('bis_queue_running', 'Já existe uma reconciliação completa em andamento. Consulte o progresso na página.');
        }
        global $wpdb;
        $table = $wpdb->prefix . 'cursos_orders';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return new WP_Error('bis_orders_table_missing', 'A tabela de pedidos não foi encontrada.');
        }
        $placeholders = implode(',', array_fill(0, count(self::CONFIRMED), '%s'));
        $ids = array_map('intval', $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$table} WHERE status IN ({$placeholders}) ORDER BY id ASC",
            self::CONFIRMED
        )));
        $tabs = array_map('intval', $wpdb->get_col(
            "SELECT id FROM {$wpdb->prefix}bis_sheet_tabs ORDER BY id ASC"
        ));
        $state = array(
            'status' => 'running', 'ids' => $ids, 'position' => 0,
            'tabs' => $tabs, 'tab_position' => 0, 'sorts' => array(),
            'synced' => 0, 'ignored' => 0, 'errors' => 0,
            'blank_repaired' => 0, 'started_at' => time(),
        );
        update_option('bis_reconcile_queue', $state, false);
        if (!wp_next_scheduled('bis_reconcile_queue_tick')) {
            wp_schedule_single_event(time() + 10, 'bis_reconcile_queue_tick');
        }
        return array('queued' => true, 'checked' => count($ids));
    }

    public function process_reconcile_queue() {
        if (!$this->acquire_lock()) {
            $this->schedule_queue_tick();
            return;
        }
        $state = get_option('bis_reconcile_queue', array());
        if (!is_array($state) || ($state['status'] ?? '') !== 'running') {
            $this->release_lock();
            return;
        }

        // No máximo 5 pedidos por execução. As gravações são adicionalmente
        // protegidas pelo orçamento global na classe BIS_Sheets_API.
        $this->batch_mode = true;
        $this->order_index_cache = array();
        $this->deferred_sorts = array();
        $processed = 0;
        while ($processed < 5 && $state['position'] < count($state['ids'])) {
            $id = absint($state['ids'][$state['position']]);
            $result = $this->sync_order($id);
            if (is_wp_error($result) && $result->get_error_code() === 'bis_rate_limited') break;
            $state['position']++;
            $processed++;
            if (is_wp_error($result)) $state['errors']++;
            elseif ($result === 'ignored') $state['ignored']++;
            else $state['synced']++;
        }
        foreach ($this->deferred_sorts as $key => $sort) $state['sorts'][$key] = $sort;
        $this->batch_mode = false;
        $this->order_index_cache = array();
        $this->deferred_sorts = array();

        // Uma vez concluídos os pedidos, reparar até duas guias sem cabeçalho por ciclo.
        if ($state['position'] >= count($state['ids'])) {
            $repaired = 0;
            while ($repaired < 2 && $state['tab_position'] < count($state['tabs'])) {
                $tab_id = absint($state['tabs'][$state['tab_position']]);
                $repair = $this->repair_blank_tabs($tab_id);
                if (!empty($repair['paused'])) break;
                $state['tab_position']++;
                $repaired++;
                $state['blank_repaired'] += $repair['repaired'];
                $state['errors'] += $repair['errors'];
            }
        }

        // As ordenações são agendadas por guia e distribuídas entre execuções.
        if ($state['position'] >= count($state['ids'])
            && $state['tab_position'] >= count($state['tabs'])) {
            $sorted = 0;
            foreach ($state['sorts'] as $key => $sort) {
                if ($sorted >= 3) break;
                $result = $this->api->sort_tab($sort['spreadsheet_id'], $sort['sheet_id']);
                if (is_wp_error($result) && $result->get_error_code() === 'bis_rate_limited') break;
                if (is_wp_error($result)) {
                    $state['errors']++;
                    $this->record_error($result, 0, $sort['course_id']);
                }
                unset($state['sorts'][$key]);
                $sorted++;
            }
        }
        if ($state['position'] >= count($state['ids'])
            && $state['tab_position'] >= count($state['tabs'])
            && empty($state['sorts'])) {
            $state['status'] = 'completed';
            $state['completed_at'] = time();
            $state['ids'] = array();
            $state['tabs'] = array();
            $this->log('completed', 'Reconciliação completa concluída: ' . $state['synced']
                . ' inscrições sincronizadas, ' . $state['blank_repaired']
                . ' cabeçalhos recuperados e ' . $state['errors'] . ' erros.');
        }

        update_option('bis_reconcile_queue', $state, false);
        $this->release_lock();
        if ($state['status'] === 'running') $this->schedule_queue_tick();
    }

    private function schedule_queue_tick() {
        if (!wp_next_scheduled('bis_reconcile_queue_tick')) {
            wp_schedule_single_event(time() + 75, 'bis_reconcile_queue_tick');
        }
    }

    public function reconcile($manual = false) {
        if ($manual) return $this->start_reconcile_queue();
        $queue = get_option('bis_reconcile_queue', array());
        if (is_array($queue) && ($queue['status'] ?? '') === 'running') {
            return array('checked' => 0, 'synced' => 0, 'ignored' => 0, 'errors' => 0, 'renamed' => 0, 'blank_removed' => 0, 'blank_repaired' => 0);
        }
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
        $blank_removed = 0;
        $blank_repaired = 0;
        if ($manual) {
            // Reconstruir cabeçalhos ausentes sem remover guias ou inscrições existentes.
            $repaired = $this->repair_blank_tabs();
            $blank_repaired = $repaired['repaired'];
            $rename_errors += $repaired['errors'];

            $rename_result = $this->rename_existing_tabs();
            $renamed = $rename_result['renamed'];
            $rename_errors += $rename_result['errors'];
        }
        $cursor = absint(get_option('bis_reconcile_cursor', 0));
        // Mantém a reconciliação automática bem abaixo da cota de leituras/minuto do Sheets.
        $limit = 20;
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
        $result = array(
            'checked' => count($ids),
            'synced' => 0,
            'ignored' => 0,
            'errors' => $rename_errors,
            'renamed' => $renamed,
            'blank_removed' => $blank_removed,
            'blank_repaired' => $blank_repaired,
        );
        if ($manual) {
            // Na reconciliação completa, cada guia é lida uma única vez e ordenada
            // apenas ao final, em vez de fazer uma leitura + ordenação por pedido.
            $this->batch_mode = true;
            $this->order_index_cache = array();
            $this->deferred_sorts = array();
        }

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
        if ($manual && $this->deferred_sorts) {
            foreach ($this->deferred_sorts as $sort) {
                $sorted = $this->api->sort_tab($sort['spreadsheet_id'], $sort['sheet_id']);
                if (is_wp_error($sorted)) {
                    $result['errors']++;
                    $this->log('error', 'Não foi possível ordenar a guia ao final da reconciliação: ' . $sorted->get_error_message(), 0, $sort['course_id']);
                }
            }
        }

        $this->batch_mode = false;
        $this->order_index_cache = array();
        $this->deferred_sorts = array();

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
            if ($created_now) {
                $deleted = $this->api->delete_tab($profile['spreadsheet_id'], $sheet['sheetId']);
                if (is_wp_error($deleted)) {
                    $this->log('error', 'A guia ' . $sheet['title'] . ' ficou vazia após falha de inicialização. Não foi possível removê-la: ' . $deleted->get_error_message(), 0, $course_id);
                }
            }
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

    private function repair_blank_tabs($only_tab_id = 0) {
        global $wpdb;
        $tabs = $only_tab_id
            ? $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bis_sheet_tabs WHERE id = %d", $only_tab_id))
            : $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bis_sheet_tabs ORDER BY spreadsheet_id ASC, id ASC");
        $result = array('repaired' => 0, 'errors' => 0, 'paused' => false);

        foreach ((array) $tabs as $tab) {
            $has_values = $this->api->tab_has_values($tab->spreadsheet_id, $tab->sheet_title);
            if (is_wp_error($has_values)) {
                $result['errors']++;
                $this->record_error($has_values, 0, absint($tab->course_id));
                continue;
            }
            if ($has_values) continue;

            $class = $this->find_class(absint($tab->course_id), $tab->class_key);
            $setup = $this->api->setup_tab(
                $tab->spreadsheet_id,
                intval($tab->sheet_id),
                $tab->sheet_title,
                get_the_title(absint($tab->course_id)),
                isset($class['nome']) ? $class['nome'] : $tab->class_key,
                $this->class_date($class)
            );
            if (is_wp_error($setup)) {
                if ($setup->get_error_code() === 'bis_rate_limited') {
                    $result['paused'] = true;
                    break;
                }
                $result['errors']++;
                $this->record_error($setup, 0, absint($tab->course_id));
                continue;
            }

            $result['repaired']++;
            $this->log('repaired', 'Cabeçalho recuperado na guia ' . $tab->sheet_title . '.', 0, absint($tab->course_id));
        }

        return $result;
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

    private function index_cache_key($spreadsheet_id, $title) {
        return (string) $spreadsheet_id . '|' . (string) $title;
    }

    private function get_order_index($spreadsheet_id, $title) {
        if (!$this->batch_mode) return $this->api->order_index($spreadsheet_id, $title);

        $key = $this->index_cache_key($spreadsheet_id, $title);
        if (!array_key_exists($key, $this->order_index_cache)) {
            $index = $this->api->order_index($spreadsheet_id, $title);
            if (is_wp_error($index)) return $index;
            $this->order_index_cache[$key] = $index;
        }
        return $this->order_index_cache[$key];
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