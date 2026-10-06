<?php
if (!defined('ABSPATH')) exit;

class BIS_Admin {
    private $auth;
    private $sync;

    public function __construct(BIS_Google_Auth $auth, BIS_Sync $sync) {
        $this->auth = $auth;
        $this->sync = $sync;
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_init', array($this, 'actions'));
        add_action('admin_notices', array($this, 'notices'));
    }

    public function menu() {
        add_menu_page(
            'Inscrições no Google Sheets',
            'Inscrições Sheets',
            'manage_options',
            'biotech-inscricoes-sheets',
            array($this, 'page'),
            'dashicons-media-spreadsheet',
            30
        );

        add_submenu_page(
            'biotech-inscricoes-sheets',
            'Configuração',
            'Configuração',
            'manage_options',
            'biotech-inscricoes-sheets',
            array($this, 'page')
        );
    }

    public function actions() {
        if (!current_user_can('manage_options')) return;
        if (empty($_POST['bis_action'])) return;
        check_admin_referer('bis_admin_action');
        $action = sanitize_key(wp_unslash($_POST['bis_action']));
        $notice = 'saved';
        if ($action === 'save_google') {
            $this->auth->save_credentials(wp_unslash($_POST['client_id'] ?? ''), wp_unslash($_POST['client_secret'] ?? ''));
        } elseif ($action === 'disconnect_google') {
            $this->auth->disconnect();
            $notice = 'disconnected';
        } elseif ($action === 'save_profile') {
            $name = sanitize_text_field(wp_unslash($_POST['profile_name'] ?? ''));
            $id = BIS_Sheets_API::spreadsheet_id(wp_unslash($_POST['spreadsheet'] ?? ''));
            if (!$name || !$id) $notice = 'invalid_profile';
            else {
                $profiles = (array) get_option('bis_sheet_profiles', array());
                $existing_keys = array_keys($profiles);
                $key = sanitize_key($name . '-' . substr(md5($id), 0, 8));
                $profiles[$key] = array('name' => $name, 'spreadsheet_id' => $id);
                update_option('bis_sheet_profiles', $profiles, false);
                if (!get_option('bis_active_sheet_profile')) update_option('bis_active_sheet_profile', $existing_keys ? reset($existing_keys) : $key, false);
            }
        } elseif ($action === 'delete_profile') {
            $key = sanitize_key(wp_unslash($_POST['profile_key'] ?? ''));
            $profiles = (array) get_option('bis_sheet_profiles', array());
            unset($profiles[$key]);
            update_option('bis_sheet_profiles', $profiles, false);
            if (get_option('bis_active_sheet_profile') === $key) {
                $remaining = array_keys($profiles);
                update_option('bis_active_sheet_profile', $remaining ? reset($remaining) : '', false);
            }
        } elseif ($action === 'save_active_profile') {
            $key = sanitize_key(wp_unslash($_POST['active_profile'] ?? ''));
            $profiles = (array) get_option('bis_sheet_profiles', array());
            if (!$key || empty($profiles[$key])) $notice = 'invalid_active_profile';
            else update_option('bis_active_sheet_profile', $key, false);
        } elseif ($action === 'save_automation') {
            update_option('bis_auto_sync', !empty($_POST['auto_sync']) ? '1' : '0', false);
            BIS_Plugin::schedule();
        } elseif ($action === 'manual_sync') {
            $result = $this->sync->reconcile(true);
            if (is_wp_error($result)) {
                set_transient('bis_admin_error_' . get_current_user_id(), $result->get_error_message(), MINUTE_IN_SECONDS);
                $notice = 'sync_error';
            } else {
                set_transient('bis_sync_result_' . get_current_user_id(), $result, MINUTE_IN_SECONDS);
                $notice = 'sync_result';
            }
        }
        wp_safe_redirect(add_query_arg('bis_notice', $notice, admin_url('admin.php?page=biotech-inscricoes-sheets')));
        exit;
    }

    public function notices() {
        if (empty($_GET['bis_notice'])) return;
        $notice = sanitize_text_field(wp_unslash($_GET['bis_notice']));
        $messages = array(
            'saved' => 'Configuração salva.', 'disconnected' => 'Conta Google desconectada.',
            'invalid_profile' => 'Informe um nome e um link ou ID válido da planilha.',
            'invalid_active_profile' => 'Selecione uma planilha válida para as novas turmas.',
            'google_connected' => 'Conta Google conectada.', 'google_denied' => 'A conexão com o Google foi cancelada.',
            'google_security' => 'A conexão expirou ou não passou na verificação de segurança.',
            'google_error' => 'Não foi possível concluir a conexão com o Google.',
        );
        if ($notice === 'sync_error') {
            $messages[$notice] = get_transient('bis_admin_error_' . get_current_user_id()) ?: 'A sincronização não pôde ser concluída.';
            delete_transient('bis_admin_error_' . get_current_user_id());
        }
        if ($notice === 'sync_result') {
            $result = get_transient('bis_sync_result_' . get_current_user_id());
            delete_transient('bis_sync_result_' . get_current_user_id());
            $result = is_array($result) ? $result : array();
            $messages[$notice] = sprintf(
                '%d pedidos verificados: %d sincronizados, %d ignorados, %d guias renomeadas e %d com erro.',
                absint($result['checked'] ?? 0),
                absint($result['synced'] ?? 0),
                absint($result['ignored'] ?? 0),
                absint($result['renamed'] ?? 0),
                absint($result['errors'] ?? 0)
            );
        }
        if (!isset($messages[$notice])) return;
        $error = in_array($notice, array('invalid_profile', 'invalid_active_profile', 'google_denied', 'google_security', 'google_error', 'sync_error'), true);
        echo '<div class="notice ' . ($error ? 'notice-error' : 'notice-success') . ' is-dismissible"><p>' . esc_html($messages[$notice]) . '</p></div>';
    }

    public function page() {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $profiles = (array) get_option('bis_sheet_profiles', array());
        $active_profile = get_option('bis_active_sheet_profile', '');
        if ((!$active_profile || empty($profiles[$active_profile])) && $profiles) {
            $profile_keys = array_keys($profiles);
            $active_profile = reset($profile_keys);
        }
        $logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bis_sync_log ORDER BY id DESC LIMIT 30");
        $callback = $this->auth->callback_url();
        $connection = $this->sync->connection_check();
        ?>
        <div class="wrap"><h1>Inscrições no Google Sheets</h1>
        <p>Envie inscrições confirmadas automaticamente para a planilha ativa, com uma aba para cada turma.</p>

        <div class="card" style="max-width:900px"><h2>1. Conexão Google</h2>
        <?php if ($this->auth->is_connected()): ?>
            <p><strong>Conectado<?php echo $this->auth->email() ? ' como ' . esc_html($this->auth->email()) : ''; ?>.</strong></p>
            <form method="post"><?php wp_nonce_field('bis_admin_action'); ?><input type="hidden" name="bis_action" value="disconnect_google"><button class="button">Desconectar</button></form>
        <?php else: ?>
            <p>Crie uma credencial OAuth do tipo “Aplicativo da Web” no Google Cloud, ative a API Google Sheets e use este endereço de redirecionamento:</p>
            <code style="display:block;padding:10px;word-break:break-all"><?php echo esc_html($callback); ?></code>
            <form method="post" style="margin-top:15px"><?php wp_nonce_field('bis_admin_action'); ?><input type="hidden" name="bis_action" value="save_google">
                <table class="form-table"><tr><th><label for="client_id">ID do cliente</label></th><td><input class="regular-text" id="client_id" name="client_id" value="<?php echo esc_attr(get_option('bis_google_client_id')); ?>" required></td></tr>
                <tr><th><label for="client_secret">Chave secreta</label></th><td><input class="regular-text" type="password" id="client_secret" name="client_secret" placeholder="<?php echo get_option('bis_google_client_secret') ? 'Deixe em branco para manter' : ''; ?>"></td></tr></table>
                <button class="button button-primary">Salvar credenciais</button>
                <?php if ($this->auth->is_configured()): ?><a class="button" href="<?php echo esc_url($this->auth->auth_url()); ?>">Conectar conta Google</a><?php endif; ?>
            </form>
        <?php endif; ?></div>

        <div class="card" style="max-width:900px"><h2>2. Planilhas disponíveis</h2>
            <?php if ($profiles): ?>
                <form method="post"><?php wp_nonce_field('bis_admin_action'); ?><input type="hidden" name="bis_action" value="save_active_profile">
                    <p><label for="active_profile"><strong>Planilha ativa para novas turmas</strong></label></p>
                    <select name="active_profile" id="active_profile" required><?php foreach ($profiles as $key => $profile): ?><option value="<?php echo esc_attr($key); ?>" <?php selected($active_profile, $key); ?>><?php echo esc_html($profile['name']); ?></option><?php endforeach; ?></select>
                    <button class="button button-primary">Salvar planilha ativa</button>
                    <p class="description">A troca vale somente para novas turmas. Turmas já criadas continuam na planilha anterior.</p>
                </form>
                <table class="widefat striped" style="margin-top:15px"><thead><tr><th>Nome</th><th>ID da planilha</th><th></th></tr></thead><tbody><?php foreach ($profiles as $key => $profile): ?><tr><td><?php echo esc_html($profile['name']); ?><?php if ($active_profile === $key): ?> <strong>(ativa)</strong><?php endif; ?></td><td><code><?php echo esc_html($profile['spreadsheet_id']); ?></code></td><td><form method="post"><?php wp_nonce_field('bis_admin_action'); ?><input type="hidden" name="bis_action" value="delete_profile"><input type="hidden" name="profile_key" value="<?php echo esc_attr($key); ?>"><button class="button-link-delete">Remover</button></form></td></tr><?php endforeach; ?></tbody></table>
            <?php else: ?><p>Cadastre a primeira planilha. Ela será definida automaticamente como ativa.</p><?php endif; ?>
            <form method="post" style="margin-top:15px"><?php wp_nonce_field('bis_admin_action'); ?><input type="hidden" name="bis_action" value="save_profile"><input name="profile_name" placeholder="Nome da planilha" required> <input class="regular-text" name="spreadsheet" placeholder="Cole o link da planilha" required> <button class="button button-primary">Adicionar planilha</button></form>
        </div>

        <div class="card" style="max-width:900px"><h2>3. Sincronização</h2>
            <?php if (is_wp_error($connection)): ?>
                <div class="notice notice-error inline"><p><strong>A planilha não está pronta:</strong> <?php echo esc_html($connection->get_error_message()); ?></p></div>
            <?php elseif (!empty($connection['title'])): ?>
                <div class="notice notice-success inline"><p><strong>Planilha acessível:</strong> <?php echo esc_html($connection['title']); ?></p></div>
            <?php endif; ?>
            <form method="post"><?php wp_nonce_field('bis_admin_action'); ?><input type="hidden" name="bis_action" value="save_automation"><label><input type="checkbox" name="auto_sync" value="1" <?php checked(get_option('bis_auto_sync', '1'), '1'); ?>> Reconciliação automática a cada hora</label> <button class="button">Salvar</button></form>
            <form method="post" style="margin-top:12px"><?php wp_nonce_field('bis_admin_action'); ?><input type="hidden" name="bis_action" value="manual_sync"><button class="button button-primary">Reconciliação completa das inscrições</button><p class="description">Revisa todos os pedidos confirmados para recuperar inscrições ausentes e corrigir a ordem nas guias.</p></form>
        </div>

        <div class="card" style="max-width:900px"><h2>Atividade recente</h2><table class="widefat striped"><thead><tr><th>Data</th><th>Pedido</th><th>Resultado</th><th>Detalhe</th></tr></thead><tbody><?php if (!$logs): ?><tr><td colspan="4">Nenhuma sincronização registrada.</td></tr><?php else: foreach ($logs as $log): ?><tr><td><?php echo esc_html($log->created_at); ?></td><td><?php echo $log->order_id ? '#' . absint($log->order_id) : '—'; ?></td><td><?php echo esc_html($log->status); ?></td><td><?php echo esc_html($log->message); ?></td></tr><?php endforeach; endif; ?></tbody></table></div>
        </div>
        <?php
    }
}