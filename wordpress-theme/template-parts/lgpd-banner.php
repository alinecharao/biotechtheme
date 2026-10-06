<?php
/**
 * Template: Banner LGPD
 * 
 * @package CursosTheme
 */

if (!defined('ABSPATH')) {
    exit;
}

// Configurações
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

$float_button = get_option('cursos_lgpd_float_button', true);
$float_position = get_option('cursos_lgpd_float_position', 'left');

$categories = Cursos_LGPD::get_instance()->get_categories();
?>

<!-- Banner Principal -->
<div id="lgpd-banner" 
     class="lgpd-banner position-<?php echo esc_attr($position); ?> style-<?php echo esc_attr($style); ?>" 
     role="dialog" 
     aria-labelledby="lgpd-title" 
     aria-describedby="lgpd-message"
     aria-hidden="true"
     style="--lgpd-bg: <?php echo esc_attr($bg_color); ?>; 
            --lgpd-text: <?php echo esc_attr($text_color); ?>; 
            --lgpd-btn-accept: <?php echo esc_attr($btn_accept_color); ?>; 
            --lgpd-btn-reject: <?php echo esc_attr($btn_reject_color); ?>;">
    
    <div class="lgpd-banner-content" style="background-color: var(--lgpd-bg); color: var(--lgpd-text);">
        <div class="lgpd-banner-text">
            <?php if ($title): ?>
            <h2 id="lgpd-title" class="lgpd-banner-title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            
            <p id="lgpd-message" class="lgpd-banner-message">
                <?php echo wp_kses_post($message); ?>
                <?php if ($privacy_page): ?>
                <a href="<?php echo get_permalink($privacy_page); ?>" target="_blank">
                    Política de Privacidade
                </a>
                <?php endif; ?>
            </p>
        </div>
        
        <div class="lgpd-banner-buttons">
            <button type="button" 
                    class="lgpd-btn lgpd-btn-accept" 
                    data-lgpd-accept-all>
                <?php echo esc_html($btn_accept); ?>
            </button>
            
            <button type="button" 
                    class="lgpd-btn lgpd-btn-reject" 
                    data-lgpd-reject-all>
                <?php echo esc_html($btn_reject); ?>
            </button>
            
            <button type="button" 
                    class="lgpd-btn lgpd-btn-settings" 
                    data-lgpd-settings>
                <?php echo esc_html($btn_settings); ?>
            </button>
        </div>
    </div>
</div>

<!-- Modal de Configurações -->
<div id="lgpd-modal" class="lgpd-modal" role="dialog" aria-labelledby="lgpd-modal-title" aria-hidden="true">
    <div class="lgpd-modal-content" tabindex="-1">
        <header class="lgpd-modal-header">
            <h3 id="lgpd-modal-title" class="lgpd-modal-title">Configurações de Cookies</h3>
            <button type="button" class="lgpd-modal-close" data-lgpd-close-modal aria-label="Fechar">×</button>
        </header>
        
        <div class="lgpd-modal-body">
            <p class="lgpd-modal-description">
                Escolha quais tipos de cookies você deseja aceitar. Cookies necessários são essenciais para o funcionamento do site e não podem ser desativados.
            </p>
            
            <div class="lgpd-modal-categories">
                <?php foreach ($categories as $key => $category): ?>
                <div class="lgpd-modal-category">
                    <div class="lgpd-modal-category-header">
                        <div class="lgpd-modal-category-info">
                            <div class="lgpd-modal-category-name"><?php echo esc_html($category['name']); ?></div>
                            <p class="lgpd-modal-category-desc"><?php echo esc_html($category['description']); ?></p>
                        </div>
                        
                        <?php if (!empty($category['mandatory'])): ?>
                        <span class="lgpd-always-active">Sempre ativo</span>
                        <?php else: ?>
                        <label class="lgpd-toggle">
                            <input type="checkbox" 
                                   value="<?php echo esc_attr($key); ?>" 
                                   <?php echo !empty($category['active_by_default']) ? 'checked' : ''; ?>>
                            <span class="lgpd-toggle-slider"></span>
                        </label>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <footer class="lgpd-modal-footer">
            <button type="button" class="lgpd-btn lgpd-btn-reject" data-lgpd-reject-all>
                Rejeitar Opcionais
            </button>
            <button type="button" class="lgpd-btn lgpd-btn-accept" data-lgpd-save-preferences>
                Salvar Preferências
            </button>
            <button type="button" class="lgpd-btn lgpd-btn-accept" data-lgpd-accept-all>
                Aceitar Todos
            </button>
        </footer>
    </div>
</div>

<!-- Botão Flutuante -->
<?php if ($float_button): ?>
<button type="button" 
        id="lgpd-float-btn" 
        class="lgpd-float-btn position-<?php echo esc_attr($float_position); ?>" 
        aria-label="Configurações de Cookies"
        title="Configurações de Cookies">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <circle cx="12" cy="12" r="4"/>
        <line x1="4.93" y1="4.93" x2="9.17" y2="9.17"/>
        <line x1="14.83" y1="14.83" x2="19.07" y2="19.07"/>
        <line x1="14.83" y1="9.17" x2="19.07" y2="4.93"/>
        <line x1="4.93" y1="19.07" x2="9.17" y2="14.83"/>
    </svg>
</button>
<?php endif; ?>
