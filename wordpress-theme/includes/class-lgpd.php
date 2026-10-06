<?php
/**
 * Cursos Theme LGPD Consent System
 * 
 * Sistema completo de consentimento LGPD - Banner de cookies e gerenciamento
 * 
 * @package CursosTheme
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cursos_LGPD {
    
    /**
     * Instância única da classe
     */
    private static $instance = null;
    
    /**
     * Categorias padrão de cookies
     */
    private $default_categories = array(
        'necessary' => array(
            'name' => 'Necessários',
            'description' => 'Cookies essenciais para o funcionamento do site. Não podem ser desativados.',
            'mandatory' => true,
            'active_by_default' => true,
            'scripts' => '',
        ),
        'analytics' => array(
            'name' => 'Analytics',
            'description' => 'Cookies para análise de tráfego e comportamento dos usuários.',
            'mandatory' => false,
            'active_by_default' => false,
            'scripts' => '',
        ),
        'marketing' => array(
            'name' => 'Marketing',
            'description' => 'Cookies para publicidade e remarketing.',
            'mandatory' => false,
            'active_by_default' => false,
            'scripts' => '',
        ),
        'functional' => array(
            'name' => 'Funcionais',
            'description' => 'Cookies para recursos extras como chat, vídeos e mapas.',
            'mandatory' => false,
            'active_by_default' => false,
            'scripts' => '',
        ),
    );
    
    /**
     * Obter instância
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor
     */
    private function __construct() {
        // Admin
        add_action('admin_menu', array($this, 'add_lgpd_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_footer', array($this, 'render_banner'), 5);
        
        // AJAX
        add_action('wp_ajax_cursos_lgpd_save_consent', array($this, 'ajax_save_consent'));
        add_action('wp_ajax_nopriv_cursos_lgpd_save_consent', array($this, 'ajax_save_consent'));
        
        // Shortcode
        add_shortcode('lgpd_preferences', array($this, 'preferences_shortcode'));
        
        // Criar tabela de logs
        register_activation_hook(__FILE__, array($this, 'create_consent_table'));
    }
    
    /**
     * Adicionar menu LGPD com submenus
     */
    public function add_lgpd_menu() {
        // Menu principal LGPD
        add_menu_page(
            'LGPD',
            'LGPD',
            'manage_options',
            'cursos-lgpd',
            array($this, 'render_admin_page'),
            'dashicons-shield',
            30
        );
        
        // Submenu: LGPD / Cookies (mesmo conteúdo do menu principal)
        add_submenu_page(
            'cursos-lgpd',
            'LGPD / Cookies',
            'LGPD / Cookies',
            'manage_options',
            'cursos-lgpd',
            array($this, 'render_admin_page')
        );
        
        // Submenu: Consentimento de Compra (NOVO)
        add_submenu_page(
            'cursos-lgpd',
            'Consentimento de Compra',
            'Consentimento de Compra',
            'manage_options',
            'cursos-lgpd-checkout-consent',
            array($this, 'render_checkout_consent_page')
        );
    }
    
    /**
     * Página de Consentimento de Compra (checkbox do checkout)
     */
    public function render_checkout_consent_page() {
        // Salvar configurações
        if (isset($_POST['save_checkout_consent']) && check_admin_referer('cursos_checkout_consent_save')) {
            update_option('cursos_checkout_terms_text', sanitize_text_field($_POST['terms_text']));
            update_option('cursos_checkout_terms_link1_label', sanitize_text_field($_POST['link1_label']));
            update_option('cursos_checkout_terms_link1_url', esc_url_raw($_POST['link1_url']));
            update_option('cursos_checkout_terms_link2_label', sanitize_text_field($_POST['link2_label']));
            update_option('cursos_checkout_terms_link2_url', esc_url_raw($_POST['link2_url']));
            update_option('cursos_checkout_terms_link3_label', sanitize_text_field($_POST['link3_label']));
            update_option('cursos_checkout_terms_link3_url', esc_url_raw($_POST['link3_url']));
            update_option('cursos_checkout_terms_final_text', sanitize_text_field($_POST['final_text']));
            update_option('cursos_checkout_terms_required', isset($_POST['terms_required']) ? '1' : '0');
            
            echo '<div class="notice notice-success"><p>Configurações de consentimento salvas com sucesso!</p></div>';
        }
        
        // Buscar valores atuais
        $terms_text = get_option('cursos_checkout_terms_text', 'Li e aceito os');
        $link1_label = get_option('cursos_checkout_terms_link1_label', 'Termos de Uso');
        $link1_url = get_option('cursos_checkout_terms_link1_url', '/termos');
        $link2_label = get_option('cursos_checkout_terms_link2_label', 'Política de Privacidade');
        $link2_url = get_option('cursos_checkout_terms_link2_url', '/privacidade');
        $link3_label = get_option('cursos_checkout_terms_link3_label', 'Política de Reembolso');
        $link3_url = get_option('cursos_checkout_terms_link3_url', '/reembolso');
        $final_text = get_option('cursos_checkout_terms_final_text', '.');
        $terms_required = get_option('cursos_checkout_terms_required', '1') === '1';
        ?>
        <div class="wrap">
            <h1 style="display: flex; align-items: center; gap: 12px;">
                <span class="dashicons dashicons-yes-alt" style="font-size: 30px; width: 30px; height: 30px;"></span>
                Consentimento de Compra
            </h1>
            <p style="color: #64748b; margin-bottom: 30px;">Configure o checkbox de termos que aparece na página de checkout.</p>
            
            <form method="post">
                <?php wp_nonce_field('cursos_checkout_consent_save'); ?>
                
                <div style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 800px;">
                    <h2 style="margin: 0 0 20px; font-size: 18px;">Texto do Checkbox</h2>
                    
                    <table class="form-table" style="margin: 0;">
                        <tr>
                            <th style="width: 180px;"><label for="terms_text">Texto inicial</label></th>
                            <td>
                                <input type="text" name="terms_text" id="terms_text" class="regular-text" 
                                       value="<?php echo esc_attr($terms_text); ?>" placeholder="Li e aceito os">
                                <p class="description">Texto antes dos links</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="link1_label">Link 1 - Label</label></th>
                            <td>
                                <input type="text" name="link1_label" id="link1_label" class="regular-text" 
                                       value="<?php echo esc_attr($link1_label); ?>" placeholder="Termos de Uso">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="link1_url">Link 1 - URL</label></th>
                            <td>
                                <input type="url" name="link1_url" id="link1_url" class="regular-text" 
                                       value="<?php echo esc_url($link1_url); ?>" placeholder="/termos">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="link2_label">Link 2 - Label</label></th>
                            <td>
                                <input type="text" name="link2_label" id="link2_label" class="regular-text" 
                                       value="<?php echo esc_attr($link2_label); ?>" placeholder="Política de Privacidade">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="link2_url">Link 2 - URL</label></th>
                            <td>
                                <input type="url" name="link2_url" id="link2_url" class="regular-text" 
                                       value="<?php echo esc_url($link2_url); ?>" placeholder="/privacidade">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="link3_label">Link 3 - Label</label></th>
                            <td>
                                <input type="text" name="link3_label" id="link3_label" class="regular-text" 
                                       value="<?php echo esc_attr($link3_label); ?>" placeholder="Política de Reembolso">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="link3_url">Link 3 - URL</label></th>
                            <td>
                                <input type="url" name="link3_url" id="link3_url" class="regular-text" 
                                       value="<?php echo esc_url($link3_url); ?>" placeholder="/reembolso">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="final_text">Texto final</label></th>
                            <td>
                                <input type="text" name="final_text" id="final_text" class="small-text" 
                                       value="<?php echo esc_attr($final_text); ?>" placeholder=".">
                                <p class="description">Texto após os links (geralmente ".")</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Obrigatório</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="terms_required" value="1" <?php checked($terms_required); ?>>
                                    Tornar aceitação obrigatória para finalizar compra
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <hr style="margin: 24px 0;">
                    
                    <h3 style="margin: 0 0 12px; font-size: 15px;">Prévia</h3>
                    <div style="padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                            <input type="checkbox" disabled style="margin-top: 3px;">
                            <span style="color: #64748b; font-size: 14px;">
                                <?php echo esc_html($terms_text); ?>
                                <a href="<?php echo esc_url($link1_url); ?>" target="_blank" style="color: #7f0b0d;"><?php echo esc_html($link1_label); ?></a>,
                                <a href="<?php echo esc_url($link2_url); ?>" target="_blank" style="color: #7f0b0d;"><?php echo esc_html($link2_label); ?></a>
                                e a
                                <a href="<?php echo esc_url($link3_url); ?>" target="_blank" style="color: #7f0b0d;"><?php echo esc_html($link3_label); ?></a><?php echo esc_html($final_text); ?>
                            </span>
                        </label>
                    </div>
                </div>
                
                <p style="margin-top: 20px;">
                    <button type="submit" name="save_checkout_consent" class="button button-primary button-large">
                        Salvar Configurações
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Registrar configurações
     */
    public function register_settings() {
        // Geral
        register_setting('cursos_lgpd_options', 'cursos_lgpd_enabled');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_position');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_style');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_bg_color');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_text_color');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_btn_accept_color');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_btn_reject_color');
        
        // Textos
        register_setting('cursos_lgpd_options', 'cursos_lgpd_title');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_message');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_btn_accept_text');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_btn_reject_text');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_btn_settings_text');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_privacy_page');
        
        // Categorias
        register_setting('cursos_lgpd_options', 'cursos_lgpd_categories');
        
        // Avançado
        register_setting('cursos_lgpd_options', 'cursos_lgpd_expiration');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_strict_mode');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_log_enabled');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_float_button');
        register_setting('cursos_lgpd_options', 'cursos_lgpd_float_position');
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'cursos-lgpd') === false) {
            return;
        }
        
        wp_enqueue_style(
            'cursos-lgpd-admin',
            CURSOS_THEME_URI . '/assets/css/lgpd-admin.css',
            array(),
            CURSOS_THEME_VERSION
        );
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->is_enabled()) {
            return;
        }
        
        wp_enqueue_style(
            'cursos-lgpd',
            CURSOS_THEME_URI . '/assets/css/lgpd.css',
            array(),
            CURSOS_THEME_VERSION
        );
        
        wp_enqueue_script(
            'cursos-lgpd',
            CURSOS_THEME_URI . '/assets/js/lgpd.js',
            array(),
            CURSOS_THEME_VERSION,
            true
        );
        
        // Passar configurações para JS
        wp_localize_script('cursos-lgpd', 'CursosLGPDConfig', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cursos_lgpd_nonce'),
            'expiration' => intval(get_option('cursos_lgpd_expiration', 365)),
            'strictMode' => get_option('cursos_lgpd_strict_mode', false),
            'categories' => $this->get_categories(),
            'logEnabled' => get_option('cursos_lgpd_log_enabled', false),
        ));
    }
    
    /**
     * Verificar se está ativado
     */
    public function is_enabled() {
        return get_option('cursos_lgpd_enabled', false);
    }
    
    /**
     * Obter categorias
     */
    public function get_categories() {
        $saved = get_option('cursos_lgpd_categories');
        if (!empty($saved) && is_array($saved)) {
            return $saved;
        }
        return $this->default_categories;
    }
    
    /**
     * Renderizar página admin
     */
    public function render_admin_page() {
        if (isset($_POST['cursos_lgpd_submit']) && check_admin_referer('cursos_lgpd_save')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>Configurações LGPD salvas com sucesso!</p></div>';
        }
        
        $enabled = get_option('cursos_lgpd_enabled', false);
        $position = get_option('cursos_lgpd_position', 'bottom');
        $style = get_option('cursos_lgpd_style', 'bar');
        $bg_color = get_option('cursos_lgpd_bg_color', '#1f2937');
        $text_color = get_option('cursos_lgpd_text_color', '#ffffff');
        $btn_accept_color = get_option('cursos_lgpd_btn_accept_color', '#10b981');
        $btn_reject_color = get_option('cursos_lgpd_btn_reject_color', '#6b7280');
        
        $title = get_option('cursos_lgpd_title', 'Usamos cookies 🍪');
        $message = get_option('cursos_lgpd_message', 'Este site utiliza cookies para melhorar sua experiência. Ao continuar navegando, você concorda com nossa Política de Privacidade.');
        $btn_accept = get_option('cursos_lgpd_btn_accept_text', 'Aceitar todos');
        $btn_reject = get_option('cursos_lgpd_btn_reject_text', 'Rejeitar');
        $btn_settings = get_option('cursos_lgpd_btn_settings_text', 'Configurar');
        $privacy_page = get_option('cursos_lgpd_privacy_page', 0);
        
        $expiration = get_option('cursos_lgpd_expiration', 365);
        $strict_mode = get_option('cursos_lgpd_strict_mode', false);
        $log_enabled = get_option('cursos_lgpd_log_enabled', false);
        $float_button = get_option('cursos_lgpd_float_button', true);
        $float_position = get_option('cursos_lgpd_float_position', 'left');
        
        $categories = $this->get_categories();
        ?>
        <div class="wrap cursos-lgpd-wrap">
            <h1>
                <span class="dashicons dashicons-shield" style="font-size: 30px; margin-right: 10px;"></span>
                LGPD / Cookies
            </h1>
            
            <form method="post">
                <?php wp_nonce_field('cursos_lgpd_save'); ?>
                
                <!-- Switch de Ativação Principal -->
                <div class="lgpd-main-toggle">
                    <label class="lgpd-switch-large">
                        <input type="checkbox" name="cursos_lgpd_enabled" value="1" <?php checked($enabled, true); ?>>
                        <span class="lgpd-slider"></span>
                    </label>
                    <div class="lgpd-toggle-label">
                        <strong>Ativar Banner LGPD</strong>
                        <p>Quando ativado, o banner de consentimento será exibido para todos os visitantes</p>
                    </div>
                </div>
                
                <div class="cursos-lgpd-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#tab-geral" class="nav-tab nav-tab-active">Aparência</a>
                        <a href="#tab-textos" class="nav-tab">Textos</a>
                        <a href="#tab-categorias" class="nav-tab">Categorias</a>
                        <a href="#tab-avancado" class="nav-tab">Avançado</a>
                        <a href="#tab-relatorios" class="nav-tab">Relatórios</a>
                    </nav>
                    
                    <!-- Tab: Aparência -->
                    <div id="tab-geral" class="tab-content" style="display: block;">
                        <h2>Aparência do Banner</h2>
                        <table class="form-table">
                            <tr>
                                <th><label for="cursos_lgpd_position">Posição</label></th>
                                <td>
                                    <select name="cursos_lgpd_position" id="cursos_lgpd_position">
                                        <option value="bottom" <?php selected($position, 'bottom'); ?>>Barra Inferior</option>
                                        <option value="top" <?php selected($position, 'top'); ?>>Barra Superior</option>
                                        <option value="bottom-left" <?php selected($position, 'bottom-left'); ?>>Canto Inferior Esquerdo</option>
                                        <option value="bottom-right" <?php selected($position, 'bottom-right'); ?>>Canto Inferior Direito</option>
                                        <option value="center" <?php selected($position, 'center'); ?>>Modal Central</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="cursos_lgpd_style">Estilo</label></th>
                                <td>
                                    <select name="cursos_lgpd_style" id="cursos_lgpd_style">
                                        <option value="bar" <?php selected($style, 'bar'); ?>>Barra</option>
                                        <option value="box" <?php selected($style, 'box'); ?>>Caixa</option>
                                        <option value="modal" <?php selected($style, 'modal'); ?>>Modal</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label>Cores</label></th>
                                <td>
                                    <div class="lgpd-color-grid">
                                        <div class="lgpd-color-item">
                                            <label for="cursos_lgpd_bg_color">Fundo</label>
                                            <input type="color" name="cursos_lgpd_bg_color" id="cursos_lgpd_bg_color" value="<?php echo esc_attr($bg_color); ?>">
                                        </div>
                                        <div class="lgpd-color-item">
                                            <label for="cursos_lgpd_text_color">Texto</label>
                                            <input type="color" name="cursos_lgpd_text_color" id="cursos_lgpd_text_color" value="<?php echo esc_attr($text_color); ?>">
                                        </div>
                                        <div class="lgpd-color-item">
                                            <label for="cursos_lgpd_btn_accept_color">Botão Aceitar</label>
                                            <input type="color" name="cursos_lgpd_btn_accept_color" id="cursos_lgpd_btn_accept_color" value="<?php echo esc_attr($btn_accept_color); ?>">
                                        </div>
                                        <div class="lgpd-color-item">
                                            <label for="cursos_lgpd_btn_reject_color">Botão Rejeitar</label>
                                            <input type="color" name="cursos_lgpd_btn_reject_color" id="cursos_lgpd_btn_reject_color" value="<?php echo esc_attr($btn_reject_color); ?>">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><label>Botão Flutuante</label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cursos_lgpd_float_button" value="1" <?php checked($float_button, true); ?>>
                                        Mostrar botão flutuante para reabrir configurações
                                    </label>
                                    <br><br>
                                    <label for="cursos_lgpd_float_position">Posição:</label>
                                    <select name="cursos_lgpd_float_position" id="cursos_lgpd_float_position">
                                        <option value="left" <?php selected($float_position, 'left'); ?>>Esquerda</option>
                                        <option value="right" <?php selected($float_position, 'right'); ?>>Direita</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Tab: Textos -->
                    <div id="tab-textos" class="tab-content" style="display: none;">
                        <h2>Textos do Banner</h2>
                        <table class="form-table">
                            <tr>
                                <th><label for="cursos_lgpd_title">Título</label></th>
                                <td>
                                    <input type="text" name="cursos_lgpd_title" id="cursos_lgpd_title" class="large-text" value="<?php echo esc_attr($title); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="cursos_lgpd_message">Mensagem</label></th>
                                <td>
                                    <textarea name="cursos_lgpd_message" id="cursos_lgpd_message" rows="4" class="large-text"><?php echo esc_textarea($message); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th>Botões</th>
                                <td>
                                    <div class="lgpd-buttons-grid">
                                        <div>
                                            <label for="cursos_lgpd_btn_accept_text">Aceitar</label>
                                            <input type="text" name="cursos_lgpd_btn_accept_text" id="cursos_lgpd_btn_accept_text" value="<?php echo esc_attr($btn_accept); ?>">
                                        </div>
                                        <div>
                                            <label for="cursos_lgpd_btn_reject_text">Rejeitar</label>
                                            <input type="text" name="cursos_lgpd_btn_reject_text" id="cursos_lgpd_btn_reject_text" value="<?php echo esc_attr($btn_reject); ?>">
                                        </div>
                                        <div>
                                            <label for="cursos_lgpd_btn_settings_text">Configurar</label>
                                            <input type="text" name="cursos_lgpd_btn_settings_text" id="cursos_lgpd_btn_settings_text" value="<?php echo esc_attr($btn_settings); ?>">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="cursos_lgpd_privacy_page">Política de Privacidade</label></th>
                                <td>
                                    <?php wp_dropdown_pages(array(
                                        'name' => 'cursos_lgpd_privacy_page',
                                        'id' => 'cursos_lgpd_privacy_page',
                                        'selected' => $privacy_page,
                                        'show_option_none' => '-- Selecionar --',
                                        'option_none_value' => 0,
                                    )); ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Tab: Categorias -->
                    <div id="tab-categorias" class="tab-content" style="display: none;">
                        <h2>Categorias de Cookies</h2>
                        <p class="description">Configure as categorias de cookies que serão exibidas aos usuários.</p>
                        
                        <div id="lgpd-categories-list">
                            <?php foreach ($categories as $key => $category): ?>
                            <div class="lgpd-category-item" data-key="<?php echo esc_attr($key); ?>">
                                <div class="lgpd-category-header">
                                    <span class="lgpd-category-drag">≡</span>
                                    <strong><?php echo esc_html($category['name']); ?></strong>
                                    <?php if (!empty($category['mandatory'])): ?>
                                    <span class="lgpd-badge">Obrigatório</span>
                                    <?php endif; ?>
                                    <button type="button" class="lgpd-category-toggle">▼</button>
                                </div>
                                <div class="lgpd-category-content" style="display: none;">
                                    <table class="form-table">
                                        <tr>
                                            <th><label>Nome</label></th>
                                            <td>
                                                <input type="text" name="categories[<?php echo esc_attr($key); ?>][name]" value="<?php echo esc_attr($category['name']); ?>" class="regular-text">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label>Descrição</label></th>
                                            <td>
                                                <textarea name="categories[<?php echo esc_attr($key); ?>][description]" rows="2" class="large-text"><?php echo esc_textarea($category['description']); ?></textarea>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Opções</th>
                                            <td>
                                                <label>
                                                    <input type="checkbox" name="categories[<?php echo esc_attr($key); ?>][mandatory]" value="1" <?php checked(!empty($category['mandatory'])); ?> <?php echo $key === 'necessary' ? 'disabled checked' : ''; ?>>
                                                    Obrigatório (não pode ser desativado)
                                                </label>
                                                <br>
                                                <label>
                                                    <input type="checkbox" name="categories[<?php echo esc_attr($key); ?>][active_by_default]" value="1" <?php checked(!empty($category['active_by_default'])); ?>>
                                                    Ativo por padrão
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label>Scripts</label></th>
                                            <td>
                                                <textarea name="categories[<?php echo esc_attr($key); ?>][scripts]" rows="4" class="large-text code" placeholder="Scripts que só carregam com consentimento..."><?php echo esc_textarea($category['scripts'] ?? ''); ?></textarea>
                                                <p class="description">Cole aqui os scripts que devem carregar apenas quando esta categoria for aceita.</p>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <p>
                            <button type="button" class="button" id="add-category">+ Adicionar Categoria</button>
                        </p>
                    </div>
                    
                    <!-- Tab: Avançado -->
                    <div id="tab-avancado" class="tab-content" style="display: none;">
                        <h2>Configurações Avançadas</h2>
                        <table class="form-table">
                            <tr>
                                <th><label for="cursos_lgpd_expiration">Expiração do Consentimento</label></th>
                                <td>
                                    <input type="number" name="cursos_lgpd_expiration" id="cursos_lgpd_expiration" value="<?php echo esc_attr($expiration); ?>" min="1" max="730" class="small-text">
                                    dias
                                    <p class="description">Após quantos dias o consentimento expira e o banner aparece novamente.</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label>Modo Estrito</label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cursos_lgpd_strict_mode" value="1" <?php checked($strict_mode, true); ?>>
                                        Bloquear scripts até o usuário consentir
                                    </label>
                                    <p class="description">Em modo estrito, scripts de terceiros são bloqueados até o consentimento.</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label>Log de Consentimentos</label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cursos_lgpd_log_enabled" value="1" <?php checked($log_enabled, true); ?>>
                                        Registrar consentimentos no banco de dados
                                    </label>
                                    <p class="description">Necessário para compliance LGPD. Armazena: ID único, data, preferências, IP anonimizado.</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Tab: Relatórios -->
                    <div id="tab-relatorios" class="tab-content" style="display: none;">
                        <h2>Relatórios de Consentimento</h2>
                        <?php $this->render_reports(); ?>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="cursos_lgpd_submit" class="button-primary" value="Salvar Configurações">
                </p>
            </form>
        </div>
        
        <style>
            .cursos-lgpd-wrap { max-width: 1200px; }
            
            .lgpd-main-toggle {
                background: #fff;
                border: 2px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                display: flex;
                align-items: center;
                gap: 20px;
            }
            .lgpd-main-toggle.active {
                border-color: #10b981;
                background: linear-gradient(135deg, #ecfdf5 0%, #fff 100%);
            }
            
            .lgpd-switch-large {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 34px;
                flex-shrink: 0;
            }
            .lgpd-switch-large input { opacity: 0; width: 0; height: 0; }
            .lgpd-slider {
                position: absolute;
                cursor: pointer;
                top: 0; left: 0; right: 0; bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 34px;
            }
            .lgpd-slider:before {
                position: absolute;
                content: "";
                height: 26px;
                width: 26px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            input:checked + .lgpd-slider { background-color: #10b981; }
            input:checked + .lgpd-slider:before { transform: translateX(26px); }
            
            .lgpd-toggle-label strong { font-size: 18px; }
            .lgpd-toggle-label p { margin: 5px 0 0; color: #666; }
            
            .cursos-lgpd-tabs { margin-top: 20px; }
            .cursos-lgpd-tabs .tab-content {
                background: #fff;
                border: 1px solid #ccc;
                border-top: 0;
                padding: 20px;
            }
            
            .lgpd-color-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
            }
            .lgpd-color-item { text-align: center; }
            .lgpd-color-item label { display: block; margin-bottom: 5px; font-size: 12px; }
            .lgpd-color-item input[type="color"] {
                width: 50px;
                height: 50px;
                cursor: pointer;
                border: 2px solid #ddd;
                border-radius: 8px;
            }
            
            .lgpd-buttons-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }
            .lgpd-buttons-grid label { display: block; margin-bottom: 5px; }
            .lgpd-buttons-grid input { width: 100%; }
            
            .lgpd-category-item {
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 5px;
                margin-bottom: 10px;
            }
            .lgpd-category-header {
                padding: 12px 15px;
                display: flex;
                align-items: center;
                gap: 10px;
                cursor: pointer;
            }
            .lgpd-category-drag { color: #999; cursor: grab; }
            .lgpd-category-toggle { margin-left: auto; background: none; border: none; cursor: pointer; }
            .lgpd-category-content { padding: 0 15px 15px; border-top: 1px solid #ddd; background: #fff; }
            .lgpd-badge {
                background: #10b981;
                color: #fff;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
            }
            
            .lgpd-reports-stats {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
                margin-bottom: 30px;
            }
            .lgpd-stat-card {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
            }
            .lgpd-stat-card h3 { font-size: 32px; margin: 0 0 10px; color: #6366f1; }
            .lgpd-stat-card p { margin: 0; color: #666; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle para ativar/desativar visual
            $('input[name="cursos_lgpd_enabled"]').on('change', function() {
                $(this).closest('.lgpd-main-toggle').toggleClass('active', this.checked);
            }).trigger('change');
            
            // Tabs
            $('.nav-tab-wrapper .nav-tab').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').hide();
                $(target).show();
            });
            
            // Category toggle
            $(document).on('click', '.lgpd-category-header', function() {
                var $content = $(this).next('.lgpd-category-content');
                var $toggle = $(this).find('.lgpd-category-toggle');
                
                $content.slideToggle();
                $toggle.text($content.is(':visible') ? '▲' : '▼');
            });
            
            // Add category
            var categoryIndex = <?php echo count($categories); ?>;
            $('#add-category').on('click', function() {
                var key = 'custom_' + categoryIndex;
                var html = `
                    <div class="lgpd-category-item" data-key="${key}">
                        <div class="lgpd-category-header">
                            <span class="lgpd-category-drag">≡</span>
                            <strong>Nova Categoria</strong>
                            <button type="button" class="lgpd-category-toggle">▼</button>
                            <button type="button" class="button button-small lgpd-remove-category">Remover</button>
                        </div>
                        <div class="lgpd-category-content" style="display: none;">
                            <table class="form-table">
                                <tr>
                                    <th><label>Nome</label></th>
                                    <td><input type="text" name="categories[${key}][name]" value="Nova Categoria" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th><label>Descrição</label></th>
                                    <td><textarea name="categories[${key}][description]" rows="2" class="large-text"></textarea></td>
                                </tr>
                                <tr>
                                    <th>Opções</th>
                                    <td>
                                        <label><input type="checkbox" name="categories[${key}][mandatory]" value="1"> Obrigatório</label><br>
                                        <label><input type="checkbox" name="categories[${key}][active_by_default]" value="1"> Ativo por padrão</label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>Scripts</label></th>
                                    <td><textarea name="categories[${key}][scripts]" rows="4" class="large-text code"></textarea></td>
                                </tr>
                            </table>
                        </div>
                    </div>`;
                
                $('#lgpd-categories-list').append(html);
                categoryIndex++;
            });
            
            // Remove category
            $(document).on('click', '.lgpd-remove-category', function(e) {
                e.stopPropagation();
                if (confirm('Remover esta categoria?')) {
                    $(this).closest('.lgpd-category-item').remove();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Salvar configurações
     */
    private function save_settings() {
        // Geral
        update_option('cursos_lgpd_enabled', isset($_POST['cursos_lgpd_enabled']));
        update_option('cursos_lgpd_position', sanitize_text_field($_POST['cursos_lgpd_position'] ?? 'bottom'));
        update_option('cursos_lgpd_style', sanitize_text_field($_POST['cursos_lgpd_style'] ?? 'bar'));
        update_option('cursos_lgpd_bg_color', sanitize_hex_color($_POST['cursos_lgpd_bg_color'] ?? '#1f2937'));
        update_option('cursos_lgpd_text_color', sanitize_hex_color($_POST['cursos_lgpd_text_color'] ?? '#ffffff'));
        update_option('cursos_lgpd_btn_accept_color', sanitize_hex_color($_POST['cursos_lgpd_btn_accept_color'] ?? '#10b981'));
        update_option('cursos_lgpd_btn_reject_color', sanitize_hex_color($_POST['cursos_lgpd_btn_reject_color'] ?? '#6b7280'));
        update_option('cursos_lgpd_float_button', isset($_POST['cursos_lgpd_float_button']));
        update_option('cursos_lgpd_float_position', sanitize_text_field($_POST['cursos_lgpd_float_position'] ?? 'left'));
        
        // Textos
        update_option('cursos_lgpd_title', sanitize_text_field($_POST['cursos_lgpd_title'] ?? ''));
        update_option('cursos_lgpd_message', wp_kses_post($_POST['cursos_lgpd_message'] ?? ''));
        update_option('cursos_lgpd_btn_accept_text', sanitize_text_field($_POST['cursos_lgpd_btn_accept_text'] ?? ''));
        update_option('cursos_lgpd_btn_reject_text', sanitize_text_field($_POST['cursos_lgpd_btn_reject_text'] ?? ''));
        update_option('cursos_lgpd_btn_settings_text', sanitize_text_field($_POST['cursos_lgpd_btn_settings_text'] ?? ''));
        update_option('cursos_lgpd_privacy_page', intval($_POST['cursos_lgpd_privacy_page'] ?? 0));
        
        // Categorias
        if (isset($_POST['categories']) && is_array($_POST['categories'])) {
            $categories = array();
            foreach ($_POST['categories'] as $key => $cat) {
                $categories[sanitize_key($key)] = array(
                    'name' => sanitize_text_field($cat['name'] ?? ''),
                    'description' => sanitize_textarea_field($cat['description'] ?? ''),
                    'mandatory' => !empty($cat['mandatory']) || $key === 'necessary',
                    'active_by_default' => !empty($cat['active_by_default']),
                    'scripts' => wp_kses($cat['scripts'] ?? '', array(
                        'script' => array('type' => true, 'src' => true, 'async' => true, 'defer' => true),
                    )),
                );
            }
            update_option('cursos_lgpd_categories', $categories);
        }
        
        // Avançado
        update_option('cursos_lgpd_expiration', intval($_POST['cursos_lgpd_expiration'] ?? 365));
        update_option('cursos_lgpd_strict_mode', isset($_POST['cursos_lgpd_strict_mode']));
        update_option('cursos_lgpd_log_enabled', isset($_POST['cursos_lgpd_log_enabled']));
    }
    
    /**
     * Renderizar banner
     */
    public function render_banner() {
        if (!$this->is_enabled()) {
            return;
        }
        
        include get_template_directory() . '/template-parts/lgpd-banner.php';
    }
    
    /**
     * Renderizar relatórios
     */
    private function render_reports() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cursos_lgpd_consents';
        
        // Verificar se a tabela existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            echo '<p>A tabela de logs ainda não foi criada. Ative o log de consentimentos para começar a coletar dados.</p>';
            return;
        }
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $accept_all = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE action = 'accept_all'");
        $reject_all = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE action = 'reject_all'");
        $custom = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE action = 'custom'");
        
        $recent = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 10");
        ?>
        <div class="lgpd-reports-stats">
            <div class="lgpd-stat-card">
                <h3><?php echo intval($total); ?></h3>
                <p>Total de Consentimentos</p>
            </div>
            <div class="lgpd-stat-card">
                <h3><?php echo $total > 0 ? round(($accept_all / $total) * 100) : 0; ?>%</h3>
                <p>Aceitaram Todos</p>
            </div>
            <div class="lgpd-stat-card">
                <h3><?php echo $total > 0 ? round(($reject_all / $total) * 100) : 0; ?>%</h3>
                <p>Rejeitaram</p>
            </div>
            <div class="lgpd-stat-card">
                <h3><?php echo $total > 0 ? round(($custom / $total) * 100) : 0; ?>%</h3>
                <p>Personalizaram</p>
            </div>
        </div>
        
        <h3>Consentimentos Recentes</h3>
        <?php if (!empty($recent)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Ação</th>
                    <th>Categorias</th>
                    <th>IP (Anonimizado)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $row): ?>
                <tr>
                    <td><?php echo esc_html($row->consent_id); ?></td>
                    <td><?php echo esc_html($row->created_at); ?></td>
                    <td>
                        <?php
                        $actions = array(
                            'accept_all' => '<span style="color: green;">✓ Aceitou Todos</span>',
                            'reject_all' => '<span style="color: red;">✗ Rejeitou</span>',
                            'custom' => '<span style="color: blue;">⚙ Personalizou</span>',
                        );
                        echo $actions[$row->action] ?? $row->action;
                        ?>
                    </td>
                    <td><?php echo esc_html($row->categories); ?></td>
                    <td><?php echo esc_html($row->ip_address); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>Nenhum consentimento registrado ainda.</p>
        <?php endif; ?>
        
        <p style="margin-top: 20px;">
            <a href="<?php echo admin_url('admin.php?page=cursos-lgpd&export=csv'); ?>" class="button">Exportar CSV</a>
        </p>
        <?php
    }
    
    /**
     * AJAX: Salvar consentimento
     */
    public function ajax_save_consent() {
        check_ajax_referer('cursos_lgpd_nonce', 'nonce');
        
        $action = sanitize_text_field($_POST['consent_action'] ?? 'custom');
        $categories = isset($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : array();
        
        // Salvar no log se habilitado
        if (get_option('cursos_lgpd_log_enabled', false)) {
            $this->log_consent($action, $categories);
        }
        
        wp_send_json_success(array(
            'message' => 'Consent saved',
            'action' => $action,
            'categories' => $categories,
        ));
    }
    
    /**
     * Registrar consentimento no banco
     */
    private function log_consent($action, $categories) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cursos_lgpd_consents';
        
        // Criar tabela se não existir
        $this->create_consent_table();
        
        // Anonimizar IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ip_parts = explode('.', $ip);
        if (count($ip_parts) === 4) {
            $ip_parts[3] = 'xxx';
            $ip = implode('.', $ip_parts);
        }
        
        $wpdb->insert($table_name, array(
            'consent_id' => wp_generate_uuid4(),
            'action' => $action,
            'categories' => implode(',', $categories),
            'ip_address' => $ip,
            'user_agent' => substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'created_at' => current_time('mysql'),
        ));
    }
    
    /**
     * Criar tabela de consentimentos
     */
    public function create_consent_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cursos_lgpd_consents';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            consent_id varchar(36) NOT NULL,
            action varchar(20) NOT NULL,
            categories text,
            ip_address varchar(45),
            user_agent varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY consent_id (consent_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Shortcode para preferências
     */
    public function preferences_shortcode($atts) {
        ob_start();
        ?>
        <div class="lgpd-preferences-widget">
            <h3>Suas Preferências de Cookies</h3>
            <p>Gerencie suas preferências de cookies abaixo:</p>
            
            <div id="lgpd-preferences-form">
                <?php foreach ($this->get_categories() as $key => $category): ?>
                <div class="lgpd-pref-item">
                    <label>
                        <input type="checkbox" 
                               name="lgpd_pref_<?php echo esc_attr($key); ?>" 
                               data-category="<?php echo esc_attr($key); ?>"
                               <?php echo !empty($category['mandatory']) ? 'checked disabled' : ''; ?>>
                        <strong><?php echo esc_html($category['name']); ?></strong>
                        <?php if (!empty($category['mandatory'])): ?>
                        <em>(Obrigatório)</em>
                        <?php endif; ?>
                    </label>
                    <p><?php echo esc_html($category['description']); ?></p>
                </div>
                <?php endforeach; ?>
                
                <button type="button" id="lgpd-save-prefs" class="button">Salvar Preferências</button>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Carregar preferências atuais
            if (typeof CursosLGPD !== 'undefined') {
                var prefs = CursosLGPD.getPreferences();
                document.querySelectorAll('#lgpd-preferences-form input[type="checkbox"]').forEach(function(cb) {
                    var cat = cb.dataset.category;
                    if (prefs[cat]) cb.checked = true;
                });
            }
            
            // Salvar preferências
            document.getElementById('lgpd-save-prefs').addEventListener('click', function() {
                var categories = [];
                document.querySelectorAll('#lgpd-preferences-form input[type="checkbox"]:checked').forEach(function(cb) {
                    categories.push(cb.dataset.category);
                });
                
                if (typeof CursosLGPD !== 'undefined') {
                    CursosLGPD.savePreferences(categories);
                    alert('Preferências salvas com sucesso!');
                }
            });
        });
        </script>
        
        <style>
        .lgpd-preferences-widget {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .lgpd-pref-item {
            margin-bottom: 15px;
            padding: 10px;
            background: #fff;
            border-radius: 5px;
        }
        .lgpd-pref-item p {
            margin: 5px 0 0 25px;
            font-size: 14px;
            color: #666;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Verificar consentimento para categoria
     */
    public static function has_consent_for($category) {
        // Isso é verificado no JS, mas podemos ter um fallback PHP
        if (isset($_COOKIE['cursos_lgpd_consent'])) {
            $consent = json_decode(stripslashes($_COOKIE['cursos_lgpd_consent']), true);
            return in_array($category, $consent['categories'] ?? array());
        }
        return false;
    }
}

// Inicializar LGPD
Cursos_LGPD::get_instance();

/**
 * Helper function para verificar consentimento
 */
function cursos_has_consent_for($category) {
    return Cursos_LGPD::has_consent_for($category);
}
