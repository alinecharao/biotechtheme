<?php
/**
 * Cursos Online Theme - Functions
 * 
 * @package CursosTheme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Includes - Classes de pagamento, cupons e descontos
require_once get_template_directory() . '/includes/class-payment-asaas.php';
require_once get_template_directory() . '/includes/class-payment-pagseguro.php';
require_once get_template_directory() . '/includes/class-cupons.php';
require_once get_template_directory() . '/includes/class-descontos.php';

// Customizer e LGPD
require_once get_template_directory() . '/includes/class-customizer.php';
require_once get_template_directory() . '/includes/class-lgpd.php';

// Sincronização com Portal Biotech movida para plugin standalone: portal-biotech-sync

// Integração com Elementor movida para plugin: biotech-elements
// A classe Cursos_Elementor_Integration foi removida deste tema para evitar duplicação
// O plugin Biotech Elements agora gerencia todos os widgets Elementor (Hero Slider, Grade de Cursos, etc.)

// Classes de Cursos Particulares e Pós-Graduação REMOVIDAS
// Essas páginas serão criadas via Elementor

// Definir constantes do tema
define('CURSOS_THEME_VERSION', '1.0.0');
define('CURSOS_THEME_DIR', get_template_directory());
define('CURSOS_THEME_URI', get_template_directory_uri());

/**
 * ========================================
 * FORÇAR CORES BORDÔ (Igual ao React/Lovable)
 * ========================================
 */
add_filter('theme_mod_cursos_primary_color', function() { return '#7f0b0d'; });
add_filter('theme_mod_cursos_primary_dark_color', function() { return '#5f0809'; });
add_filter('theme_mod_cursos_primary_light_color', function() { return '#9a1214'; });
add_filter('theme_mod_cursos_secondary_color', function() { return '#c9a227'; });
add_filter('theme_mod_cursos_text_color', function() { return '#333333'; });
add_filter('theme_mod_cursos_body_font', function() { return 'Noto Sans'; });
add_filter('theme_mod_cursos_heading_font', function() { return 'Noto Sans'; });

/**
 * ========================================
 * AUTO-CRIAR PÁGINAS ESSENCIAIS DO CHECKOUT
 * Garante que /pedido-confirmado/, /carrinho/ e /checkout/ existam
 * usando os Page Templates corretos. Idempotente.
 * ========================================
 */
function cursos_ensure_checkout_pages() {
    if (!is_admin() && !wp_doing_cron()) {
        if (get_transient('cursos_pages_checked')) return;
        set_transient('cursos_pages_checked', 1, DAY_IN_SECONDS);
    }

    $pages = array(
        'pedido-confirmado' => array('title' => 'Pedido Confirmado', 'template' => 'page-pedido-confirmado.php'),
        'carrinho'          => array('title' => 'Carrinho',          'template' => 'page-cart.php'),
        'checkout'          => array('title' => 'Checkout',          'template' => 'page-checkout.php'),
        'pagamento-pix'     => array('title' => 'Pagamento PIX',     'template' => 'page-pagamento-pix.php'),
    );

    foreach ($pages as $slug => $info) {
        $existing = get_page_by_path($slug);
        if ($existing && $existing->post_status === 'publish') {
            $current_tpl = get_post_meta($existing->ID, '_wp_page_template', true);
            if ($current_tpl !== $info['template']) {
                update_post_meta($existing->ID, '_wp_page_template', $info['template']);
            }
            continue;
        }

        $page_id = wp_insert_post(array(
            'post_title'   => $info['title'],
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        ));

        if ($page_id && !is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', $info['template']);
        }
    }
}
add_action('after_switch_theme', 'cursos_ensure_checkout_pages');
add_action('admin_init', 'cursos_ensure_checkout_pages');

/**
 * ========================================
 * CONFIGURAÇÃO DO TEMA
 * ========================================
 */
function cursos_theme_setup() {
    // Suporte a recursos do tema
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('custom-logo', array(
        'height' => 100,
        'width' => 300,
        'flex-height' => true,
        'flex-width' => true,
    ));

    // Tamanhos de imagem personalizados
    add_image_size('curso-thumbnail', 400, 225, true);
    add_image_size('curso-featured', 800, 450, true);
    add_image_size('curso-large', 1200, 675, true);

    // Registrar menus de navegação
    register_nav_menus(array(
        'primary' => __('Menu Principal', 'cursos-theme'),
        'footer' => __('Menu do Rodapé', 'cursos-theme'),
    ));
    
    // ========================================
    // SUPORTE A GUTENBERG E PAGE BUILDERS
    // ========================================
    
    // Blocos wide e full-width
    add_theme_support('align-wide');
    
    // Estilos do editor para preview correto
    add_theme_support('editor-styles');
    add_editor_style('assets/css/editor-style.css');
    
    // Embeds responsivos
    add_theme_support('responsive-embeds');
    
    // Cores do editor (paleta personalizada - Bordô igual ao React)
    add_theme_support('editor-color-palette', array(
        array(
            'name'  => __('Primária', 'cursos-theme'),
            'slug'  => 'primary',
            'color' => '#7f0b0d',
        ),
        array(
            'name'  => __('Primária Escura', 'cursos-theme'),
            'slug'  => 'primary-dark',
            'color' => '#5f0809',
        ),
        array(
            'name'  => __('Gold', 'cursos-theme'),
            'slug'  => 'gold',
            'color' => '#c9a227',
        ),
        array(
            'name'  => __('Sucesso', 'cursos-theme'),
            'slug'  => 'success',
            'color' => '#10b981',
        ),
        array(
            'name'  => __('Perigo', 'cursos-theme'),
            'slug'  => 'danger',
            'color' => '#ef4444',
        ),
        array(
            'name'  => __('Escuro', 'cursos-theme'),
            'slug'  => 'dark',
            'color' => '#333333',
        ),
        array(
            'name'  => __('Claro', 'cursos-theme'),
            'slug'  => 'light',
            'color' => '#f5f5f5',
        ),
        array(
            'name'  => __('Branco', 'cursos-theme'),
            'slug'  => 'white',
            'color' => '#ffffff',
        ),
    ));
    
    // Tamanhos de fonte do editor
    add_theme_support('editor-font-sizes', array(
        array(
            'name' => __('Pequeno', 'cursos-theme'),
            'size' => 14,
            'slug' => 'small',
        ),
        array(
            'name' => __('Normal', 'cursos-theme'),
            'size' => 16,
            'slug' => 'normal',
        ),
        array(
            'name' => __('Médio', 'cursos-theme'),
            'size' => 20,
            'slug' => 'medium',
        ),
        array(
            'name' => __('Grande', 'cursos-theme'),
            'size' => 28,
            'slug' => 'large',
        ),
        array(
            'name' => __('Extra Grande', 'cursos-theme'),
            'size' => 42,
            'slug' => 'x-large',
        ),
    ));
    
    // Espaçamentos do editor
    add_theme_support('custom-spacing');
    
    // Unidades de espaçamento
    add_theme_support('custom-units', array('px', 'em', 'rem', '%', 'vh', 'vw'));
    
    // Suporte a padrões de blocos
    add_theme_support('core-block-patterns');
}
add_action('after_setup_theme', 'cursos_theme_setup');

/**
 * Adicionar classe ao body para páginas com template full-width
 */
function cursos_body_classes($classes) {
    if (is_page_template('template-fullwidth.php')) {
        $classes[] = 'template-fullwidth';
    }
    if (is_front_page()) {
        $classes[] = 'front-page';
    }
    return $classes;
}
add_filter('body_class', 'cursos_body_classes');

/**
 * ========================================
 * ENQUEUE SCRIPTS E STYLES
 * ========================================
 */
function cursos_enqueue_assets() {
    // Styles - Noto Sans igual ao React/Lovable
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&display=swap', array(), null);
    wp_enqueue_style('cursos-style', get_stylesheet_uri(), array(), CURSOS_THEME_VERSION);
    
    // Scripts
    wp_enqueue_script('cursos-main', CURSOS_THEME_URI . '/assets/js/main.js', array('jquery'), CURSOS_THEME_VERSION, true);
    
    // Localizar script para AJAX
    wp_localize_script('cursos-main', 'cursosAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cursos_ajax_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'cursos_enqueue_assets');
/**
 * ========================================
 * ORDENAÇÃO: CURSOS COM VAGAS PRIMEIRO
 * ========================================
 * Filtro reutilizável para WP_Query que ordena cursos disponíveis antes dos sem turma/esgotados.
 */
function cursos_orderby_disponibilidade($clauses, $query) {
    global $wpdb;
    // LEFT JOIN no meta _curso_sem_turma_aberta: valor '1' = sem turma, NULL/outro = com turma
    $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS mt_sem_turma ON ({$wpdb->posts}.ID = mt_sem_turma.post_id AND mt_sem_turma.meta_key = '_curso_sem_turma_aberta') ";
    // LEFT JOIN no meta _curso_proxima_turma_data para ordenação por próxima turma
    $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS mt_proxima ON ({$wpdb->posts}.ID = mt_proxima.post_id AND mt_proxima.meta_key = '_curso_proxima_turma_data') ";
    // Cursos com vagas primeiro (0), sem turma depois (1), e dentro de cada grupo ordenar por data da próxima turma
    $clauses['orderby'] = "CASE WHEN mt_sem_turma.meta_value = '1' THEN 1 ELSE 0 END ASC, COALESCE(mt_proxima.meta_value, '9999-12-31') ASC, " . $clauses['orderby'];
    return $clauses;
}

/**
 * ========================================
 * AJAX ENDPOINT - FILTRO DE CURSOS
 * ========================================
 */
function cursos_ajax_filter_courses() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cursos_ajax_nonce')) {
        wp_send_json_error('Nonce inválido');
        return;
    }
    
    // Parâmetros
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 9;
    $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : 'all';
    $busca = isset($_POST['busca']) ? sanitize_text_field($_POST['busca']) : '';
    $ordenar = isset($_POST['ordenar']) ? sanitize_text_field($_POST['ordenar']) : 'recent';
    
    // Argumentos da query
    $args = array(
        'post_type' => 'curso',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'post_status' => 'publish',
        'suppress_filters' => false,
        'no_found_rows' => false,
    );
    
    // Busca
    if (!empty($busca)) {
        $args['s'] = $busca;
    }
    
    // Categoria
    if ($categoria !== 'all') {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'categoria_curso',
                'field' => 'slug',
                'terms' => $categoria,
            ),
        );
    }
    
    // Ordenação
    switch ($ordenar) {
        case 'popular':
            $args['meta_key'] = '_curso_alunos';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'rating':
            $args['meta_key'] = '_curso_avaliacao';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'price_asc':
            $args['meta_key'] = '_curso_preco';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
            break;
        case 'price_desc':
            $args['meta_key'] = '_curso_preco';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'recent':
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
    }
    
    // Ordenar cursos com vagas abertas primeiro
    add_filter('posts_clauses', 'cursos_orderby_disponibilidade', 10, 2);
    $query = new WP_Query($args);
    remove_filter('posts_clauses', 'cursos_orderby_disponibilidade', 10, 2);
    
    // Gerar HTML dos cursos
    ob_start();
    
    if ($query->have_posts()) {
        echo '<div class="cursos-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            $curso_id = get_the_ID();
            $curso_image = get_the_post_thumbnail_url($curso_id, 'large');
            $has_image = !empty($curso_image);
            $curso_preco = get_post_meta($curso_id, '_curso_preco', true);
            $curso_preco_original = get_post_meta($curso_id, '_curso_preco_original', true);
            $curso_duracao = get_post_meta($curso_id, '_curso_duracao', true) ?: 'A definir';
            $curso_alunos = get_post_meta($curso_id, '_curso_alunos', true) ?: 0;
            $curso_categorias = get_the_terms($curso_id, 'categoria_curso');
            $curso_categoria = ($curso_categorias && !is_wp_error($curso_categorias)) ? $curso_categorias[0]->name : '';
            $curso_tipo = get_post_meta($curso_id, '_curso_tipo', true) ?: 'online';
            // Buscar múltiplos professores (com fallback para campo antigo)
            $professores = array();
            if (function_exists('cursos_get_curso_professores')) {
                $professores = cursos_get_curso_professores($curso_id);
            } else {
                // Fallback para campo antigo
                $professor_id = get_post_meta($curso_id, '_curso_professor', true);
                if ($professor_id) {
                    $data = cursos_get_professor_full_data($professor_id);
                    if ($data) $professores[] = $data;
                }
            }
            
            // Gerar iniciais do nome para fallback
            $get_initials = function($name) {
                $parts = explode(' ', $name);
                $initials = '';
                foreach (array_slice($parts, 0, 2) as $part) {
                    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                }
                return $initials;
            };
            
            // Type labels - priorizar taxonomia tipo_curso, fallback para meta field
            $type_labels = array('online' => 'Online', 'particular' => 'Particular', 'pos-graduacao' => 'Pós-Graduação');
            $tipo_terms = get_the_terms($curso_id, 'tipo_curso');
            if (!is_wp_error($tipo_terms) && !empty($tipo_terms)) {
                $type_label = $tipo_terms[0]->name;
            } elseif (!empty($curso_tipo) && isset($type_labels[$curso_tipo])) {
                $type_label = $type_labels[$curso_tipo];
            } else {
                $type_label = 'Online';
            }
            
            $savings = ($curso_preco_original && $curso_preco_original > $curso_preco) ? ($curso_preco_original - $curso_preco) : 0;
            $has_discount = $savings > 0;
            $is_popular = intval($curso_alunos) >= 1000;
            $curso_link = get_permalink($curso_id);
            
            $vagas_curso = cursos_check_curso_vagas($curso_id);
            $curso_esgotado = $vagas_curso['tem_limite'] && !$vagas_curso['disponivel'];
            $menor_vagas = cursos_get_curso_menor_vagas($curso_id);
            
            if ($vagas_curso['tem_limite'] && $vagas_curso['vagas_restantes'] !== null) {
                $vagas_display = $vagas_curso['vagas_restantes'];
            } else {
                $vagas_display = $menor_vagas;
            }
            $ultimas_vagas = ($vagas_display !== null && $vagas_display > 0 && $vagas_display <= 5);
            $sem_turma_aberta = get_post_meta($curso_id, '_curso_sem_turma_aberta', true);
            $tem_vagas_abertas = cursos_curso_tem_vagas_abertas($curso_id);
            ?>
            <article class="curso-card">
                <a href="<?php echo esc_url($curso_link); ?>" class="curso-card-image-link">
                    <div class="curso-card-image-wrapper curso-card-image-fullwidth">
                        <?php if ($has_image): ?>
                        <img src="<?php echo esc_url($curso_image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="curso-card-image">
                        <?php else: ?>
                        <div class="curso-card-image-placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                                <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                            </svg>
                        </div>
                        <?php endif; ?>
                        
                        <span class="curso-card-badge curso-card-badge-type"><?php echo esc_html($type_label); ?></span>
                        
                        <?php if ($tem_vagas_abertas): ?>
                        <span class="curso-card-badge curso-card-badge-vagas-abertas">
                            VAGAS ABERTAS
                        </span>
                        <?php endif; ?>

                        <?php if ($has_discount): ?>
                        <span class="curso-card-badge curso-card-badge-savings">
                            ECONOMIZE <?php echo cursos_format_price($savings); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </a>
                
                <div class="curso-card-meta">
                    <div class="curso-card-meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span><?php echo esc_html($curso_duracao); ?></span>
                    </div>
                    <?php if ($curso_categoria): ?>
                    <div class="curso-card-meta-item curso-card-meta-category">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
                        <span><?php echo esc_html($curso_categoria); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php 
                if (function_exists('cursos_get_proximas_turmas')) {
                    $proximas = cursos_get_proximas_turmas($curso_id);
                    if ($proximas['proxima']):
                        $data_formatada = cursos_format_turma_date($proximas['proxima']);
                ?>
                <div class="curso-card-turmas-wrapper" style="display:block;width:100%;border-bottom:1px solid rgba(0,0,0,0.05);">
                    <div class="curso-card-proxima-turma" style="display:flex;align-items:center;gap:0.5rem;padding:0.5rem 1.25rem;font-size:0.8rem;color:var(--primary,#7f0b0d);background-color:rgba(127,11,13,0.05);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>Próxima turma: <strong><?php echo esc_html($data_formatada); ?></strong></span>
                        <?php if ($proximas['total'] > 1): ?>
                        <span class="curso-card-mais-turmas" style="font-size:0.7rem;background-color:var(--primary,#7f0b0d);color:#fff;padding:0.1rem 0.4rem;border-radius:9999px;font-weight:600;cursor:pointer;" onclick="var e=this.closest('.curso-card-turmas-wrapper').querySelector('.curso-card-turmas-extras');if(e.classList.contains('aberto')){e.classList.remove('aberto');this.textContent='<?php echo '+' . ($proximas['total'] - 1) . ' turma' . (($proximas['total'] - 1) > 1 ? 's' : ''); ?>';}else{e.classList.add('aberto');this.textContent='fechar';}">+<?php echo ($proximas['total'] - 1); ?> turma<?php echo ($proximas['total'] - 1) > 1 ? 's' : ''; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($proximas['total'] > 1): ?>
                    <div class="curso-card-turmas-extras" style="display:none;padding:0.25rem 1.25rem 0.5rem;background-color:rgba(127,11,13,0.03);">
                        <?php foreach (array_slice($proximas['todas'], 1) as $turma_extra): ?>
                        <div class="curso-card-turma-extra-item" style="display:flex;align-items:center;gap:0.4rem;font-size:0.75rem;color:var(--primary,#7f0b0d);padding:0.2rem 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;opacity:0.5;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <strong><?php echo esc_html(cursos_format_turma_date($turma_extra)); ?></strong>
                            <?php if (!empty($turma_extra['nome'])): ?>
                            <span style="color:#6b7280;">— <?php echo esc_html($turma_extra['nome']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; } ?>

                <div class="curso-card-content">
                    <a href="<?php echo esc_url($curso_link); ?>">
                        <h3 class="curso-card-title"><?php the_title(); ?></h3>
                    </a>
                    
                    <p class="curso-card-description">
                        <?php echo esc_html(wp_trim_words(get_the_excerpt(), 15)); ?>
                    </p>
                    
                    <?php if (!empty($professores)): ?>
                        <?php if (count($professores) > 1): ?>
                        <!-- Avatares Empilhados para múltiplos professores -->
                        <div class="curso-card-instructors-stacked">
                            <div class="stacked-avatars">
                                <?php 
                                $max_show = 3;
                                $total = count($professores);
                                $to_show = array_slice($professores, 0, $max_show);
                                $remaining = $total - $max_show;
                                
                                foreach ($to_show as $prof): 
                                ?>
                                <div class="avatar-wrapper">
                                    <?php if ($prof['foto']): ?>
                                    <img src="<?php echo esc_url($prof['foto']); ?>" alt="<?php echo esc_attr($prof['nome']); ?>" class="stacked-avatar">
                                    <?php else: ?>
                                    <div class="stacked-avatar-placeholder"><?php echo $get_initials($prof['nome']); ?></div>
                                    <?php endif; ?>
                                    <div class="avatar-tooltip">
                                        <span class="tooltip-name"><?php echo esc_html($prof['nome']); ?></span>
                                        <?php if (!empty($prof['titulo'])): ?>
                                        <span class="tooltip-title"><?php echo esc_html($prof['titulo']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <?php if ($remaining > 0): ?>
                                <div class="stacked-more">
                                    +<?php echo $remaining; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <span class="stacked-names">
                            <?php 
                            $nomes = array_map(function($p) { 
                                $nome = !empty($p['nome']) ? $p['nome'] : 'Professor';
                                return explode(' ', $nome)[0]; 
                            }, array_slice($professores, 0, 2));
                            echo esc_html(implode(', ', $nomes));
                            if ($total > 2) echo ' +' . ($total - 2);
                            ?>
                        </span>
                        </div>
                        <?php else: ?>
                        <!-- Professor único -->
                        <div class="curso-card-instructor">
                            <?php if ($professores[0]['foto']): ?>
                            <img src="<?php echo esc_url($professores[0]['foto']); ?>" alt="<?php echo esc_attr($professores[0]['nome']); ?>" class="instructor-image">
                            <?php else: ?>
                            <div class="instructor-image-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <?php endif; ?>
                            <div class="instructor-info">
                                <span class="instructor-name"><?php echo esc_html($professores[0]['nome']); ?></span>
                                <span class="instructor-title"><?php echo esc_html($professores[0]['titulo']); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="curso-card-price-section">
                        <div class="curso-card-price-info">
                            <?php if ($has_discount): ?>
                            <span class="price-original"><?php echo cursos_format_price($curso_preco_original); ?></span>
                            <?php endif; ?>
                            <div class="price-row">
                                <span class="price-current"><?php echo cursos_format_price($curso_preco); ?></span>
                                <span class="price-installments">ou <?php echo cursos_get_installment_display_text(floatval($curso_preco)); ?></span>
                            </div>
                        </div>

                        <a href="<?php echo esc_url($curso_link); ?>" class="btn btn-primary btn-block">
                            Matricule-se
                        </a>
                    </div>
                </div>
            </article>
            <?php
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<div class="cursos-no-results"><p>Nenhum curso encontrado com os filtros selecionados.</p></div>';
    }
    
    $html = ob_get_clean();
    
    // Retornar resposta
    wp_send_json_success(array(
        'html' => $html,
        'total' => $query->found_posts,
        'pages' => $query->max_num_pages,
        'current' => $page
    ));
}
add_action('wp_ajax_cursos_filter', 'cursos_ajax_filter_courses');
add_action('wp_ajax_nopriv_cursos_filter', 'cursos_ajax_filter_courses');

/**
 * Enqueue admin scripts for course editing
 */
function cursos_enqueue_admin_assets($hook) {
    global $post_type;
    
    // Carrega apenas na edição de cursos
    if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'curso') {
        wp_enqueue_script('cursos-admin', CURSOS_THEME_URI . '/assets/js/admin.js', array('jquery'), CURSOS_THEME_VERSION, true);
    }

    // Biblioteca de Mídia para a página Cursos → Banners.
    if ($hook === 'curso_page_cursos-banners') {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'cursos_enqueue_admin_assets');

/**
 * ========================================
 * CUSTOM POST TYPE - CURSOS
 * ========================================
 */
function cursos_register_curso_post_type() {
    $labels = array(
        'name' => 'Cursos',
        'singular_name' => 'Curso',
        'menu_name' => 'Cursos',
        'add_new' => 'Adicionar Novo',
        'add_new_item' => 'Adicionar Novo Curso',
        'edit_item' => 'Editar Curso',
        'new_item' => 'Novo Curso',
        'view_item' => 'Ver Curso',
        'search_items' => 'Buscar Cursos',
        'not_found' => 'Nenhum curso encontrado',
        'not_found_in_trash' => 'Nenhum curso na lixeira',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'cursos'),
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
    );

    register_post_type('curso', $args);
}
add_action('init', 'cursos_register_curso_post_type');

/**
 * Taxonomias para Cursos
 */
function cursos_register_taxonomies() {
    // Categoria do Curso
    register_taxonomy('categoria_curso', 'curso', array(
        'labels' => array(
            'name' => 'Categorias',
            'singular_name' => 'Categoria',
            'search_items' => 'Buscar Categorias',
            'all_items' => 'Todas as Categorias',
            'edit_item' => 'Editar Categoria',
            'update_item' => 'Atualizar Categoria',
            'add_new_item' => 'Adicionar Nova Categoria',
            'new_item_name' => 'Nome da Nova Categoria',
            'menu_name' => 'Categorias',
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'categoria-curso'),
    ));

    // Tipo do Curso (online, particular, pós-graduação)
    register_taxonomy('tipo_curso', 'curso', array(
        'labels' => array(
            'name' => 'Tipos',
            'singular_name' => 'Tipo',
            'search_items' => 'Buscar Tipos',
            'all_items' => 'Todos os Tipos',
            'edit_item' => 'Editar Tipo',
            'update_item' => 'Atualizar Tipo',
            'add_new_item' => 'Adicionar Novo Tipo',
            'new_item_name' => 'Nome do Novo Tipo',
            'menu_name' => 'Tipos',
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'tipo-curso'),
    ));
}
add_action('init', 'cursos_register_taxonomies');

/**
 * Meta Boxes para Cursos
 */
function cursos_curso_meta_boxes() {
    add_meta_box(
        'curso_detalhes',
        'Detalhes do Curso',
        'cursos_curso_detalhes_callback',
        'curso',
        'normal',
        'high'
    );
    
    add_meta_box(
        'curso_preco',
        'Preço e Pagamento',
        'cursos_curso_preco_callback',
        'curso',
        'side',
        'high'
    );

    add_meta_box(
        'curso_presencial',
        'Curso Presencial',
        'cursos_curso_presencial_callback',
        'curso',
        'side',
        'high'
    );
    
    add_meta_box(
        'curso_what_youll_learn',
        'O que você vai aprender',
        'cursos_curso_what_youll_learn_callback',
        'curso',
        'normal',
        'default'
    );
    
    add_meta_box(
        'curso_curriculum',
        'Currículo / Módulos',
        'cursos_curso_curriculum_callback',
        'curso',
        'normal',
        'default'
    );
    
    add_meta_box(
        'curso_includes',
        'O que está incluso',
        'cursos_curso_includes_callback',
        'curso',
        'normal',
        'default'
    );
    
    add_meta_box(
        'curso_faqs',
        'Perguntas Frequentes (FAQ)',
        'cursos_curso_faqs_callback',
        'curso',
        'normal',
        'default'
    );
    
    add_meta_box(
        'curso_turmas',
        'Turmas',
        'cursos_curso_turmas_callback',
        'curso',
        'normal',
        'default'
    );

    add_meta_box(
        'curso_pagto_alternativo',
        'Pagamento Alternativo (Link Asaas)',
        'cursos_curso_pagto_alternativo_callback',
        'curso',
        'normal',
        'default'
    );

    if (current_user_can('manage_options')) {
        add_meta_box(
            'curso_pagto_alternativo_diag',
            'Pagamento Alternativo — Diagnóstico',
            'cursos_curso_pagto_alternativo_diag_callback',
            'curso',
            'normal',
            'low'
        );
    }

}
add_action('add_meta_boxes', 'cursos_curso_meta_boxes');

function cursos_curso_presencial_callback($post) {
    $ativo = get_post_meta($post->ID, '_curso_presencial', true) === '1';
    ?>
    <p>
        <label style="display:flex;align-items:flex-start;gap:8px;">
            <input type="checkbox" name="curso_presencial" value="1" <?php checked($ativo); ?> style="margin-top:2px;">
            <span><strong>Ativar Curso Presencial</strong><br><span class="description">Exibe o banner global abaixo de “Este curso inclui”.</span></span>
        </label>
    </p>
    <p><a href="<?php echo esc_url(admin_url('edit.php?post_type=curso&page=cursos-banners')); ?>">Editar banner global</a></p>
    <?php
}

function cursos_curso_detalhes_callback($post) {
    wp_nonce_field('cursos_curso_save', 'cursos_curso_nonce');
    
    $duracao = get_post_meta($post->ID, '_curso_duracao', true);
    $aulas = get_post_meta($post->ID, '_curso_aulas', true);
    $alunos = get_post_meta($post->ID, '_curso_alunos', true);
    $avaliacao = get_post_meta($post->ID, '_curso_avaliacao', true);
    $professor_id = get_post_meta($post->ID, '_curso_professor', true);
    $tipo = get_post_meta($post->ID, '_curso_tipo', true);
    $destaque = get_post_meta($post->ID, '_curso_destaque', true);
    ?>
    <style>
        .curso-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .curso-field { margin-bottom: 15px; }
        .curso-field label { display: block; font-weight: 600; margin-bottom: 5px; }
        .curso-field input, .curso-field select { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        @media (max-width: 768px) { .curso-grid { grid-template-columns: 1fr; } }
    </style>
    
    <?php $limite_vagas = get_post_meta($post->ID, '_curso_limite_vagas', true); ?>
    <div class="curso-grid">
        <div class="curso-field">
            <label for="curso_duracao">Duração</label>
            <input type="text" id="curso_duracao" name="curso_duracao" value="<?php echo esc_attr($duracao); ?>" placeholder="Ex: 6 meses, 40 horas">
        </div>
        
        <div class="curso-field">
            <label for="curso_aulas">Número de Aulas</label>
            <input type="number" id="curso_aulas" name="curso_aulas" value="<?php echo esc_attr($aulas); ?>" min="0">
        </div>
        
        <div class="curso-field">
            <label for="curso_alunos">Número de Alunos (estatística)</label>
            <input type="number" id="curso_alunos" name="curso_alunos" value="<?php echo esc_attr($alunos); ?>" min="0">
            <p class="description" style="margin-top: 5px; color: #666; font-size: 12px;">
                Apenas para exibição. Ex: "500 alunos já fizeram este curso".
            </p>
        </div>
    </div>
    
    <div class="curso-field" style="margin-top: 15px; padding: 15px; background: #f0f7ff; border: 1px solid #0073aa; border-radius: 6px;">
        <label for="curso_limite_vagas" style="color: #0073aa; font-weight: 600;">
            🎓 Limite Máximo de Vagas do Curso
        </label>
        <input type="number" id="curso_limite_vagas" name="curso_limite_vagas" 
               value="<?php echo esc_attr($limite_vagas); ?>" min="0" 
               placeholder="Deixe vazio ou 0 para ilimitado"
               style="margin-top: 8px;">
        <p class="description" style="margin-top: 8px; color: #555; font-size: 12px;">
            <strong>Controle real de vagas.</strong> Este limite é verificado na hora da compra.<br>
            Deixe em branco ou 0 para permitir matrículas ilimitadas.
        </p>
        <?php 
        // Mostrar preview de vagas se houver limite definido
        if (!empty($limite_vagas) && intval($limite_vagas) > 0) {
            $vagas_info = cursos_check_curso_vagas($post->ID);
            if ($vagas_info['tem_limite']) {
                $cor = $vagas_info['disponivel'] ? '#46b450' : '#dc3232';
                echo '<p style="margin-top: 10px; padding: 8px; background: #fff; border-radius: 4px; font-size: 13px;">';
                echo '<strong style="color: ' . $cor . ';">📊 Status atual:</strong> ';
                echo $vagas_info['vendidos'] . ' vendidos / ' . $vagas_info['vagas_totais'] . ' vagas → ';
                echo '<strong>' . $vagas_info['vagas_restantes'] . ' disponíveis</strong>';
                if (isset($vagas_info['reservas']) && $vagas_info['reservas'] > 0) {
                    echo ' (' . $vagas_info['reservas'] . ' reservas ativas)';
                }
                echo '</p>';
            }
        }
        ?>
    </div>
    
    <div class="curso-grid">
        <div class="curso-field">
            <label for="curso_categoria">Categoria</label>
            <select id="curso_categoria" name="curso_categoria">
                <option value="">Selecione...</option>
                <?php
                $categorias = get_terms(array(
                    'taxonomy' => 'categoria_curso',
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC'
                ));
                $categoria_atual = get_post_meta($post->ID, '_curso_categoria', true);
                if (!is_wp_error($categorias) && !empty($categorias)) {
                    foreach ($categorias as $cat) {
                        $selected = ($categoria_atual == $cat->slug) ? 'selected' : '';
                        echo '<option value="' . esc_attr($cat->slug) . '" ' . $selected . '>' . esc_html($cat->name) . '</option>';
                    }
                }
                ?>
            </select>
            <p class="description" style="margin-top: 5px; color: #666; font-size: 12px;">
                <a href="<?php echo admin_url('edit-tags.php?taxonomy=categoria_curso&post_type=curso'); ?>">Gerenciar categorias</a>
            </p>
        </div>
        
<div class="curso-field curso-field-full" style="grid-column: 1 / -1;">
    <label>Professores</label>
    <?php
    $professores = get_posts(array(
        'post_type' => 'professor', 
        'posts_per_page' => -1, 
        'orderby' => 'title', 
        'order' => 'ASC'
    ));
    
    // Obter IDs selecionados (suporta array ou valor único antigo)
    $professores_ids = get_post_meta($post->ID, '_curso_professores', true);
    if (!is_array($professores_ids) || empty($professores_ids)) {
        $old_professor = get_post_meta($post->ID, '_curso_professor', true);
        $professores_ids = !empty($old_professor) ? array($old_professor) : array();
    }
    
    if (!empty($professores)):
        $count = count($professores_ids);
        $label_text = $count > 0 
            ? sprintf('%d professor%s selecionado%s', $count, $count > 1 ? 'es' : '', $count > 1 ? 's' : '')
            : 'Selecionar professores...';
    ?>
    <style>
        #professores-dropdown-list.open { display: block !important; }
        .prof-checkbox-label:hover { background: #f6f7f7 !important; }
    </style>
    <div class="curso-professores-dropdown" style="position: relative;">
        <button type="button" id="professores-dropdown-btn" 
                style="width: 100%; padding: 10px 12px; background: #fff; border: 1px solid #8c8f94; 
                       border-radius: 4px; cursor: pointer; text-align: left; display: flex; 
                       justify-content: space-between; align-items: center;">
            <span id="professores-dropdown-label"><?php echo esc_html($label_text); ?></span>
            <span style="border: solid #50575e; border-width: 0 2px 2px 0; padding: 3px; 
                         transform: rotate(45deg); margin-top: -3px;"></span>
        </button>
        
        <div id="professores-dropdown-list" 
             style="display: none; position: absolute; top: 100%; left: 0; right: 0; 
                    background: #fff; border: 1px solid #8c8f94; border-top: none; 
                    border-radius: 0 0 4px 4px; max-height: 250px; overflow-y: auto; 
                    z-index: 1000; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
            <?php foreach ($professores as $prof): 
                $checked = in_array($prof->ID, $professores_ids) ? 'checked' : '';
            ?>
            <label class="prof-checkbox-label" 
                   style="display: flex; align-items: center; padding: 10px 12px; cursor: pointer; 
                          border-bottom: 1px solid #f0f0f0; background: #fff;">
                <input type="checkbox" name="curso_professores[]" 
                       value="<?php echo $prof->ID; ?>" <?php echo $checked; ?>
                       style="margin-right: 10px; width: 18px; height: 18px; cursor: pointer;">
                <?php echo esc_html($prof->post_title); ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
    (function() {
        var btn = document.getElementById('professores-dropdown-btn');
        var list = document.getElementById('professores-dropdown-list');
        var label = document.getElementById('professores-dropdown-label');
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            list.classList.toggle('open');
        });
        
        list.addEventListener('change', function() {
            var checked = list.querySelectorAll('input[type="checkbox"]:checked').length;
            if (checked === 0) {
                label.textContent = 'Selecionar professores...';
            } else if (checked === 1) {
                label.textContent = '1 professor selecionado';
            } else {
                label.textContent = checked + ' professores selecionados';
            }
        });
        
        document.addEventListener('click', function(e) {
            if (!btn.contains(e.target) && !list.contains(e.target)) {
                list.classList.remove('open');
            }
        });
    })();
    </script>
    
    <p class="description" style="margin-top: 8px; color: #666; font-size: 12px;">
        Clique para abrir e selecione os professores.
        <a href="<?php echo admin_url('edit.php?post_type=professor'); ?>">Gerenciar professores</a>
    </p>
    <?php else: ?>
    <p style="color: #666; font-style: italic;">
        Nenhum professor cadastrado. 
        <a href="<?php echo admin_url('post-new.php?post_type=professor'); ?>">Adicionar professor</a>
    </p>
    <?php endif; ?>
</div>
        
        <div class="curso-field">
            <label for="curso_tipo">Tipo do Curso</label>
            <select id="curso_tipo" name="curso_tipo">
                <option value="">Selecione...</option>
                <?php
                $tipos = get_terms(array(
                    'taxonomy' => 'tipo_curso',
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC'
                ));
                if (!is_wp_error($tipos) && !empty($tipos)) {
                    foreach ($tipos as $tipo_term) {
                        $selected = ($tipo == $tipo_term->slug) ? 'selected' : '';
                        echo '<option value="' . esc_attr($tipo_term->slug) . '" ' . $selected . '>' . esc_html($tipo_term->name) . '</option>';
                    }
                } else {
                    // Fallback caso não tenha tipos cadastrados
                    ?>
                    <option value="online" <?php selected($tipo, 'online'); ?>>Online</option>
                    <option value="particular" <?php selected($tipo, 'particular'); ?>>Particular</option>
                    <option value="pos-graduacao" <?php selected($tipo, 'pos-graduacao'); ?>>Pós-Graduação</option>
                    <?php
                }
                ?>
            </select>
            <p class="description" style="margin-top: 5px; color: #666; font-size: 12px;">
                <a href="<?php echo admin_url('edit-tags.php?taxonomy=tipo_curso&post_type=curso'); ?>">Gerenciar tipos de curso</a>
            </p>
        </div>
    </div>
    
    <?php $publico_alvo = get_post_meta($post->ID, '_curso_publico_alvo', true); ?>
    <div class="curso-field" style="margin-top: 15px; grid-column: 1 / -1;">
        <label for="curso_publico_alvo">Público-alvo</label>
        <input type="text" id="curso_publico_alvo" name="curso_publico_alvo" 
               value="<?php echo esc_attr($publico_alvo); ?>" 
               placeholder="Ex: Médicos Veterinários, Estudantes de Veterinária" style="width: 100%;">
        <p class="description" style="margin-top: 5px; color: #666; font-size: 12px;">
            Informe o público-alvo do curso. Este texto aparecerá na página do curso.
        </p>
    </div>
    
    <div class="curso-field" style="margin-top: 15px;">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" name="curso_destaque" value="1" <?php checked($destaque, '1'); ?> style="width: 18px; height: 18px; margin: 0;">
            <span>Destacar este curso (aparece na home)</span>
        </label>
    </div>
    
    <?php $waitlist_url = get_post_meta($post->ID, '_curso_waitlist_url', true); ?>
    <div class="curso-field" style="margin-top: 20px; grid-column: 1 / -1;">
        <label for="curso_waitlist_url">Link do Formulário de Lista de Espera</label>
        <input type="url" id="curso_waitlist_url" name="curso_waitlist_url" 
               value="<?php echo esc_attr($waitlist_url); ?>" 
               placeholder="https://forms.google.com/..." style="width: 100%;">
        <p class="description" style="margin-top: 5px; color: #666; font-size: 12px;">
            Cole aqui o link do seu formulário externo (Google Forms, Typeform, etc.). O botão aparecerá quando não houver turmas disponíveis.
        </p>
    </div>
    <?php
}

function cursos_curso_preco_callback($post) {
    $preco = get_post_meta($post->ID, '_curso_preco', true);
    $preco_original = get_post_meta($post->ID, '_curso_preco_original', true);

    // Compat: campo antigo migra para estudante
    $legacy_pct = get_post_meta($post->ID, '_curso_desconto_percent', true);
    $legacy_acc = get_post_meta($post->ID, '_curso_desconto_acumula', true);

    $est_pct = get_post_meta($post->ID, '_curso_desconto_estudante_percent', true);
    if ($est_pct === '' && $legacy_pct !== '') $est_pct = $legacy_pct;
    $est_acc_raw = get_post_meta($post->ID, '_curso_desconto_estudante_acumula', true);
    if ($est_acc_raw === '' && $legacy_acc !== '') $est_acc_raw = $legacy_acc;
    $est_acc = $est_acc_raw === '1';

    $prof_pct = get_post_meta($post->ID, '_curso_desconto_profissional_percent', true);
    $prof_acc = get_post_meta($post->ID, '_curso_desconto_profissional_acumula', true) === '1';

    // Formata os valores para exibição
    $preco_formatted = $preco ? number_format((float)$preco, 2, ',', '.') : '';
    $preco_original_formatted = $preco_original ? number_format((float)$preco_original, 2, ',', '.') : '';
    ?>
    <div class="curso-field">
        <label for="curso_preco">Preço Atual</label>
        <input type="text" id="curso_preco" name="curso_preco" 
               class="currency-mask" 
               data-value="<?php echo esc_attr($preco); ?>"
               value="<?php echo $preco_formatted ? 'R$ ' . $preco_formatted : ''; ?>" 
               placeholder="R$ 0,00"
               style="width: 100%;">
    </div>
    
    <div class="curso-field" style="margin-top: 15px;">
        <label for="curso_preco_original">Preço Original</label>
        <input type="text" id="curso_preco_original" name="curso_preco_original" 
               class="currency-mask"
               data-value="<?php echo esc_attr($preco_original); ?>"
               value="<?php echo $preco_original_formatted ? 'R$ ' . $preco_original_formatted : ''; ?>" 
               placeholder="R$ 0,00"
               style="width: 100%;">
        <p class="description">Deixe em branco ou igual ao preço atual se não houver desconto.</p>
    </div>

    <hr style="margin: 18px 0;">

    <div class="curso-field">
        <label for="curso_desconto_estudante_percent"><strong>Desconto para estudante (%)</strong></label>
        <input type="number" id="curso_desconto_estudante_percent" name="curso_desconto_estudante_percent"
               value="<?php echo esc_attr($est_pct); ?>"
               min="0" max="100" step="0.01" placeholder="0"
               style="width: 100%;">
        <p class="description">Aplicado quando o cliente seleciona "Estudante" no checkout. Deixe em branco para usar o desconto global de estudante.</p>
    </div>

    <div class="curso-field" style="margin-top: 12px;">
        <label style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" id="curso_desconto_estudante_acumula" name="curso_desconto_estudante_acumula" value="1" <?php checked($est_acc); ?>>
            <span>Acumular com o desconto global de Estudante</span>
        </label>
        <p class="description">Se desmarcado, o desconto deste curso <strong>substitui</strong> o desconto global de Estudante neste item.</p>
    </div>

    <hr style="margin: 18px 0;">

    <div class="curso-field">
        <label for="curso_desconto_profissional_percent"><strong>Desconto para profissional via PIX (%)</strong></label>
        <input type="number" id="curso_desconto_profissional_percent" name="curso_desconto_profissional_percent"
               value="<?php echo esc_attr($prof_pct); ?>"
               min="0" max="100" step="0.01" placeholder="0"
               style="width: 100%;">
        <p class="description">Aplicado quando o cliente é profissional (não-estudante) e seleciona PIX. Deixe em branco para usar o desconto global de PIX.</p>
    </div>

    <div class="curso-field" style="margin-top: 12px;">
        <label style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" id="curso_desconto_profissional_acumula" name="curso_desconto_profissional_acumula" value="1" <?php checked($prof_acc); ?>>
            <span>Acumular com o desconto global de PIX</span>
        </label>
        <p class="description">Se desmarcado, o desconto deste curso <strong>substitui</strong> o desconto global de PIX neste item.</p>
    </div>

    <hr style="margin: 18px 0;">

    <?php
    $sem_juros_curso = get_post_meta($post->ID, '_curso_parcelamento_sem_juros', true);
    $sem_juros_global = intval(get_option('cursos_parcelamento_sem_juros', 12));
    ?>
    <div class="curso-field">
        <label for="curso_parcelamento_sem_juros"><strong>Parcelas sem juros no cartão</strong></label>
        <input type="number" id="curso_parcelamento_sem_juros" name="curso_parcelamento_sem_juros"
               value="<?php echo esc_attr($sem_juros_curso); ?>"
               min="0" max="24" step="1"
               placeholder="Global: <?php echo esc_attr($sem_juros_global); ?>x"
               style="width: 100%;">
        <p class="description">Quantidade de parcelas sem juros para este curso (sobrescreve o global). Deixe em branco para usar a configuração global (<?php echo esc_html($sem_juros_global); ?>x). Use <code>0</code> para desativar parcelas sem juros neste curso.</p>
    </div>
    <?php
}


/**
 * Callback: O que você vai aprender
 */
function cursos_curso_what_youll_learn_callback($post) {
    $items = get_post_meta($post->ID, '_curso_what_youll_learn', true);
    if (!is_array($items)) $items = array();
    ?>
    <style>
        .repeater-list { margin-bottom: 10px; }
        .repeater-item { display: flex; gap: 10px; margin-bottom: 8px; align-items: center; }
        .repeater-item input { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .repeater-item .remove-item { background: #dc3545; color: #fff; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
        .add-item-btn { background: #0073aa; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
    </style>
    <div id="what_youll_learn_repeater">
        <div class="repeater-list">
            <?php if (!empty($items)): foreach ($items as $i => $item): ?>
            <div class="repeater-item">
                <input type="text" name="curso_what_youll_learn[]" value="<?php echo esc_attr($item); ?>" placeholder="Ex: Fundamentos teóricos e práticos">
                <button type="button" class="remove-item" onclick="this.parentElement.remove()">✕</button>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <button type="button" class="add-item-btn" onclick="addWhatYoullLearnItem()">+ Adicionar Item</button>
    </div>
    <script>
    function addWhatYoullLearnItem() {
        var html = '<div class="repeater-item"><input type="text" name="curso_what_youll_learn[]" value="" placeholder="Ex: Fundamentos teóricos e práticos"><button type="button" class="remove-item" onclick="this.parentElement.remove()">✕</button></div>';
        document.querySelector('#what_youll_learn_repeater .repeater-list').insertAdjacentHTML('beforeend', html);
    }
    </script>
    <?php
}

/**
 * Callback: Currículo / Módulos
 */
function cursos_curso_curriculum_callback($post) {
    $curriculum = get_post_meta($post->ID, '_curso_curriculum', true);
    if (!is_array($curriculum)) $curriculum = array();
    ?>
    <style>
        .module-item { background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .module-header { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .module-header input { flex: 1; padding: 10px 12px; border: 1px solid #ccc; border-radius: 4px; font-weight: 600; }
        .lessons-list { padding-left: 20px; margin-top: 10px; }
        .lesson-item { display: flex; gap: 10px; margin-bottom: 8px; align-items: center; }
        .lesson-item input { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .add-lesson-btn { background: #28a745; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .remove-module-btn { background: #dc3545; color: #fff; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
    </style>
    <div id="curriculum_repeater">
        <div class="modules-list">
            <?php if (!empty($curriculum)): foreach ($curriculum as $mi => $module): ?>
            <div class="module-item" data-module="<?php echo $mi; ?>">
                <div class="module-header">
                    <input type="text" name="curso_curriculum[<?php echo $mi; ?>][title]" value="<?php echo esc_attr($module['title'] ?? ''); ?>" placeholder="Título do Módulo">
                    <button type="button" class="remove-module-btn" onclick="this.closest('.module-item').remove()">✕ Remover Módulo</button>
                </div>
                <div class="lessons-list">
                    <?php if (!empty($module['lessons'])): foreach ($module['lessons'] as $li => $lesson): ?>
                    <div class="lesson-item">
                        <input type="text" name="curso_curriculum[<?php echo $mi; ?>][lessons][]" value="<?php echo esc_attr($lesson); ?>" placeholder="Nome da aula">
                        <button type="button" class="remove-item" onclick="this.parentElement.remove()">✕</button>
                    </div>
                    <?php endforeach; endif; ?>
                    <button type="button" class="add-lesson-btn" onclick="addLesson(this, <?php echo $mi; ?>)">+ Adicionar Aula</button>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <button type="button" class="add-item-btn" onclick="addModule()">+ Adicionar Módulo</button>
    </div>
    <script>
    var moduleIndex = <?php echo !empty($curriculum) ? max(array_keys($curriculum)) + 1 : 0; ?>;
    function addModule() {
        var html = '<div class="module-item" data-module="' + moduleIndex + '"><div class="module-header"><input type="text" name="curso_curriculum[' + moduleIndex + '][title]" value="" placeholder="Título do Módulo"><button type="button" class="remove-module-btn" onclick="this.closest(\'.module-item\').remove()">✕ Remover Módulo</button></div><div class="lessons-list"><button type="button" class="add-lesson-btn" onclick="addLesson(this, ' + moduleIndex + ')">+ Adicionar Aula</button></div></div>';
        document.querySelector('#curriculum_repeater .modules-list').insertAdjacentHTML('beforeend', html);
        moduleIndex++;
    }
    function addLesson(btn, mi) {
        var html = '<div class="lesson-item"><input type="text" name="curso_curriculum[' + mi + '][lessons][]" value="" placeholder="Nome da aula"><button type="button" class="remove-item" onclick="this.parentElement.remove()">✕</button></div>';
        btn.insertAdjacentHTML('beforebegin', html);
    }
    </script>
    <?php
}

/**
 * Callback: O que está incluso
 */
function cursos_curso_includes_callback($post) {
    $includes = get_post_meta($post->ID, '_curso_includes', true);
    if (!is_array($includes)) $includes = array();
    $icon_options = array(
        'play-circle' => '▶ Play Circle',
        'clock' => '⏰ Clock',
        'award' => '🏆 Award',
        'message-circle' => '💬 Message',
        'smartphone' => '📱 Smartphone',
        'book' => '📖 Book',
        'file-text' => '📄 File',
        'download' => '⬇ Download',
        'check-circle' => '✓ Check',
    );
    ?>
    <div id="includes_repeater">
        <div class="repeater-list">
            <?php if (!empty($includes)): foreach ($includes as $i => $item): ?>
            <div class="repeater-item">
                <select name="curso_includes[<?php echo $i; ?>][icon]" style="width: 150px;">
                    <?php foreach ($icon_options as $val => $label): ?>
                    <option value="<?php echo $val; ?>" <?php selected($item['icon'] ?? '', $val); ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="curso_includes[<?php echo $i; ?>][text]" value="<?php echo esc_attr($item['text'] ?? ''); ?>" placeholder="Descrição">
                <button type="button" class="remove-item" onclick="this.parentElement.remove()">✕</button>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <button type="button" class="add-item-btn" onclick="addIncludeItem()">+ Adicionar Item</button>
    </div>
    <script>
    var includeIndex = <?php echo !empty($includes) ? count($includes) : 0; ?>;
    function addIncludeItem() {
        var options = '<?php foreach ($icon_options as $val => $label): ?><option value="<?php echo $val; ?>"><?php echo $label; ?></option><?php endforeach; ?>';
        var html = '<div class="repeater-item"><select name="curso_includes[' + includeIndex + '][icon]" style="width: 150px;">' + options + '</select><input type="text" name="curso_includes[' + includeIndex + '][text]" value="" placeholder="Descrição"><button type="button" class="remove-item" onclick="this.parentElement.remove()">✕</button></div>';
        document.querySelector('#includes_repeater .repeater-list').insertAdjacentHTML('beforeend', html);
        includeIndex++;
    }
    </script>
    <?php
}

/**
 * Callback: FAQs
 */
function cursos_curso_faqs_callback($post) {
    $faqs = get_post_meta($post->ID, '_curso_faqs', true);
    if (!is_array($faqs)) $faqs = array();
    ?>
    <style>
        .faq-item { background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .faq-field { margin-bottom: 10px; }
        .faq-field label { display: block; font-weight: 600; margin-bottom: 5px; }
        .faq-field input, .faq-field textarea { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .faq-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    </style>
    <div id="faqs_repeater">
        <div class="faqs-list">
            <?php if (!empty($faqs)): foreach ($faqs as $i => $faq): ?>
            <div class="faq-item">
                <div class="faq-header">
                    <strong>Pergunta #<?php echo $i + 1; ?></strong>
                    <button type="button" class="remove-item" onclick="this.closest('.faq-item').remove()">✕ Remover</button>
                </div>
                <div class="faq-field">
                    <label>Pergunta</label>
                    <input type="text" name="curso_faqs[<?php echo $i; ?>][question]" value="<?php echo esc_attr($faq['question'] ?? ''); ?>">
                </div>
                <div class="faq-field">
                    <label>Resposta</label>
                    <textarea name="curso_faqs[<?php echo $i; ?>][answer]" rows="3"><?php echo esc_textarea($faq['answer'] ?? ''); ?></textarea>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <button type="button" class="add-item-btn" onclick="addFaqItem()">+ Adicionar FAQ</button>
    </div>
    <script>
    var faqIndex = <?php echo !empty($faqs) ? count($faqs) : 0; ?>;
    function addFaqItem() {
        var html = '<div class="faq-item"><div class="faq-header"><strong>Pergunta #' + (faqIndex + 1) + '</strong><button type="button" class="remove-item" onclick="this.closest(\'.faq-item\').remove()">✕ Remover</button></div><div class="faq-field"><label>Pergunta</label><input type="text" name="curso_faqs[' + faqIndex + '][question]" value=""></div><div class="faq-field"><label>Resposta</label><textarea name="curso_faqs[' + faqIndex + '][answer]" rows="3"></textarea></div></div>';
        document.querySelector('#faqs_repeater .faqs-list').insertAdjacentHTML('beforeend', html);
        faqIndex++;
    }
    </script>
    <?php
}

/**
 * Callback: Turmas
 */
function cursos_curso_turmas_callback($post) {
    $turmas = get_post_meta($post->ID, '_curso_turmas', true);
    if (!is_array($turmas)) $turmas = array();
    $sem_turma = get_post_meta($post->ID, '_curso_sem_turma_aberta', true);
    ?>
    <style>
        .turma-item { background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .turma-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; }
        .turma-field label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 12px; }
        .turma-field input { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; }
        .turma-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        @media (max-width: 768px) { .turma-grid { grid-template-columns: 1fr 1fr; } }
    </style>
    
    <div style="margin-bottom: 20px; padding: 15px; background: #fff3cd; border-radius: 8px;">
        <label>
            <input type="checkbox" name="curso_sem_turma_aberta" value="1" <?php checked($sem_turma, '1'); ?>>
            <strong>Sem turma aberta no momento</strong> (exibe mensagem de "aguarde novas turmas")
        </label>
    </div>
    
    <div id="turmas_repeater">
        <div class="turmas-list">
            <?php if (!empty($turmas)): foreach ($turmas as $i => $turma): ?>
            <div class="turma-item">
                <div class="turma-header">
                    <strong>Turma #<?php echo $i + 1; ?></strong>
                    <button type="button" class="remove-item" onclick="this.closest('.turma-item').remove()">✕ Remover</button>
                </div>
                <div class="turma-grid">
                    <div class="turma-field">
                        <label>ID da Turma</label>
                        <input type="text" name="curso_turmas[<?php echo $i; ?>][id]" value="<?php echo esc_attr($turma['id'] ?? ''); ?>" placeholder="turma-2024-01">
                    </div>
                    <div class="turma-field">
                        <label>Nome da Turma</label>
                        <input type="text" name="curso_turmas[<?php echo $i; ?>][nome]" value="<?php echo esc_attr($turma['nome'] ?? ''); ?>" placeholder="Turma Janeiro 2024">
                    </div>
                    <div class="turma-field">
                        <label>Data de Início</label>
                        <input type="date" name="curso_turmas[<?php echo $i; ?>][data_inicio]" value="<?php echo esc_attr($turma['data_inicio'] ?? ''); ?>">
                    </div>
                    <div class="turma-field">
                        <label>Data de Fim</label>
                        <input type="date" name="curso_turmas[<?php echo $i; ?>][data_fim]" value="<?php echo esc_attr($turma['data_fim'] ?? ''); ?>">
                    </div>
                    <div class="turma-field">
                        <label>Vagas Disponíveis</label>
                        <input type="number" name="curso_turmas[<?php echo $i; ?>][vagas]" value="<?php echo esc_attr($turma['vagas'] ?? ''); ?>" min="0">
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <button type="button" class="add-item-btn" onclick="addTurmaItem()">+ Adicionar Turma</button>
    </div>
    <script>
    var turmaIndex = <?php echo !empty($turmas) ? count($turmas) : 0; ?>;
    function addTurmaItem() {
        var html = '<div class="turma-item"><div class="turma-header"><strong>Turma #' + (turmaIndex + 1) + '</strong><button type="button" class="remove-item" onclick="this.closest(\'.turma-item\').remove()">✕ Remover</button></div><div class="turma-grid"><div class="turma-field"><label>ID da Turma</label><input type="text" name="curso_turmas[' + turmaIndex + '][id]" value="" placeholder="turma-2024-01"></div><div class="turma-field"><label>Nome da Turma</label><input type="text" name="curso_turmas[' + turmaIndex + '][nome]" value="" placeholder="Turma Janeiro 2024"></div><div class="turma-field"><label>Data de Início</label><input type="date" name="curso_turmas[' + turmaIndex + '][data_inicio]" value=""></div><div class="turma-field"><label>Data de Fim</label><input type="date" name="curso_turmas[' + turmaIndex + '][data_fim]" value=""></div><div class="turma-field"><label>Vagas Disponíveis</label><input type="number" name="curso_turmas[' + turmaIndex + '][vagas]" value="" min="0"></div></div></div>';
        document.querySelector('#turmas_repeater .turmas-list').insertAdjacentHTML('beforeend', html);
        turmaIndex++;
    }
    </script>
    <?php
}

/**
 * Pagamento Alternativo — metabox (Link de Pagamento Asaas)
 */
function cursos_curso_pagto_alternativo_callback($post) {
    // Legado (fallback aplicado a ambas modalidades quando as novas metas não existirem)
    $legacy_ativo = get_post_meta($post->ID, '_curso_pagto_alt_ativo', true);
    $legacy_cartao = get_post_meta($post->ID, '_curso_pagto_alt_link_cartao', true);
    $legacy_pix = get_post_meta($post->ID, '_curso_pagto_alt_link_pix', true);

    $modalidades = array(
        'prof' => array(
            'titulo' => 'Profissional',
            'ativo'  => get_post_meta($post->ID, '_curso_pagto_alt_prof_ativo', true),
            'cartao' => get_post_meta($post->ID, '_curso_pagto_alt_prof_link_cartao', true),
            'pix'    => get_post_meta($post->ID, '_curso_pagto_alt_prof_link_pix', true),
        ),
        'aluno' => array(
            'titulo' => 'Aluno',
            'ativo'  => get_post_meta($post->ID, '_curso_pagto_alt_aluno_ativo', true),
            'cartao' => get_post_meta($post->ID, '_curso_pagto_alt_aluno_link_cartao', true),
            'pix'    => get_post_meta($post->ID, '_curso_pagto_alt_aluno_link_pix', true),
        ),
    );

    // Fallback do legado quando as novas metas nunca foram salvas
    foreach ($modalidades as $slug => &$m) {
        if ($m['ativo'] === '' && $m['cartao'] === '' && $m['pix'] === '') {
            $m['ativo']  = $legacy_ativo;
            $m['cartao'] = $legacy_cartao;
            $m['pix']    = $legacy_pix;
        }
        $m['ativo'] = ($m['ativo'] === '1');
    }
    unset($m);
    ?>
    <p class="description" style="margin:0 0 10px;">Quando ativo para a modalidade escolhida no checkout, o pedido é criado normalmente com status <em>Pagamento Pendente</em> e o aluno é redirecionado para o Link de Pagamento do Asaas em vez de cobrar pela API.</p>
    <?php foreach ($modalidades as $slug => $m):
        $field_ativo  = 'curso_pagto_alt_' . $slug . '_ativo';
        $field_cartao = 'curso_pagto_alt_' . $slug . '_link_cartao';
        $field_pix    = 'curso_pagto_alt_' . $slug . '_link_pix';
        $box_id       = 'curso_pagto_alt_' . $slug . '_fields';
    ?>
    <div style="border:1px solid #dcdcde; border-radius:4px; padding:10px 12px; margin-bottom:12px;">
        <p style="margin:0 0 8px;">
            <label>
                <input type="checkbox" id="<?php echo esc_attr($field_ativo); ?>" name="<?php echo esc_attr($field_ativo); ?>" value="1" <?php checked($m['ativo']); ?>>
                <strong>Ativar Pagamento Alternativo (<?php echo esc_html($m['titulo']); ?>)</strong>
            </label>
        </p>
        <div id="<?php echo esc_attr($box_id); ?>" style="<?php echo $m['ativo'] ? '' : 'display:none;'; ?> padding:12px; background:#f6f7f7; border-left:3px solid #2271b1;">
            <p>
                <label for="<?php echo esc_attr($field_cartao); ?>"><strong>Link de Pagamento - Cartão de Crédito</strong></label><br>
                <input type="url" id="<?php echo esc_attr($field_cartao); ?>" name="<?php echo esc_attr($field_cartao); ?>"
                       value="<?php echo esc_attr($m['cartao']); ?>" class="widefat"
                       placeholder="https://www.asaas.com/c/..." />
            </p>
            <p>
                <label for="<?php echo esc_attr($field_pix); ?>"><strong>Link de Pagamento - PIX</strong></label><br>
                <input type="url" id="<?php echo esc_attr($field_pix); ?>" name="<?php echo esc_attr($field_pix); ?>"
                       value="<?php echo esc_attr($m['pix']); ?>" class="widefat"
                       placeholder="https://www.asaas.com/c/..." />
            </p>
            <p class="description" style="margin:0;">Se o aluno escolher uma forma de pagamento sem link cadastrado, será exibida uma mensagem informando que a opção está indisponível.</p>
        </div>
        <script>
        (function(){
            var cb = document.getElementById(<?php echo wp_json_encode($field_ativo); ?>);
            var box = document.getElementById(<?php echo wp_json_encode($box_id); ?>);
            if (!cb || !box) return;
            cb.addEventListener('change', function(){ box.style.display = cb.checked ? '' : 'none'; });
        })();
        </script>
    </div>
    <?php endforeach; ?>
    <?php
}

/**
 * Admin notice para URLs inválidas do Pagamento Alternativo
 */
add_action('admin_notices', function() {
    $key = 'cursos_pagto_alt_invalid_' . get_current_user_id();
    $invalid = get_transient($key);
    if (!$invalid) return;
    delete_transient($key);
    echo '<div class="notice notice-warning is-dismissible"><p><strong>Pagamento Alternativo:</strong> URL inválida ignorada para: ' . esc_html(implode(', ', (array) $invalid)) . '. Informe uma URL começando com http(s)://.</p></div>';
});

/**
 * Admin notice de diagnóstico do checkout Pagto Alternativo (visível para admin).
 * Consome transiente gravado em wordpress-theme/page-checkout.php.
 */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    $key = 'cursos_pagto_alt_diag_' . get_current_user_id();
    $diag = get_transient($key);
    if (!$diag) return;
    delete_transient($key);

    $res = $diag['resultado'] ?? array();
    $ativo = !empty($res['ativo']);
    $classe = $ativo ? (empty($res['url']) ? 'notice-error' : 'notice-success') : 'notice-info';

    echo '<div class="notice ' . esc_attr($classe) . '"><p><strong>Diagnóstico Pagto Alternativo (' . esc_html($diag['when'] ?? '') . '):</strong></p>';
    echo '<p>payment_method=<code>' . esc_html($diag['payment_method'] ?? '') . '</code> · is_student=<code>' . intval($diag['is_student'] ?? 0) . '</code></p>';
    echo '<p>Resultado do helper: ativo=<code>' . ($ativo ? 'true' : 'false') . '</code> · url=<code>' . esc_html($res['url'] ?? '') . '</code> · motivo=<code>' . esc_html($res['motivo'] ?? '') . '</code></p>';
    if (!empty($diag['items'])) {
        echo '<details><summary>Metas por curso no checkout</summary><pre style="white-space:pre-wrap">' . esc_html(wp_json_encode($diag['items'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</pre></details>';
    }
    echo '</div>';
});

/**
 * Página de admin dedicada ao diagnóstico do Pagamento Alternativo.
 * Necessária porque o Custom Course Builder oculta os metaboxes padrão.
 * Acesse em: Ferramentas → Pagto Alt. Diag
 */
add_action('admin_menu', function() {
    add_management_page(
        'Pagto Alternativo — Diagnóstico',
        'Pagto Alt. Diag',
        'manage_options',
        'cursos-pagto-alt-diag',
        'cursos_pagto_alt_diag_page'
    );
});

function cursos_pagto_alt_diag_page() {
    if (!current_user_can('manage_options')) wp_die('Sem permissão.');

    $post_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : 0;
    $cursos = get_posts(array('post_type' => 'curso', 'posts_per_page' => -1, 'post_status' => 'any', 'orderby' => 'title', 'order' => 'ASC'));

    echo '<div class="wrap"><h1>Pagamento Alternativo — Diagnóstico</h1>';
    echo '<form method="get" style="margin:16px 0"><input type="hidden" name="page" value="cursos-pagto-alt-diag"/>';
    echo '<label>Curso: <select name="curso_id" onchange="this.form.submit()"><option value="0">— selecione —</option>';
    foreach ($cursos as $c) {
        printf('<option value="%d" %s>#%d — %s</option>', $c->ID, selected($post_id, $c->ID, false), $c->ID, esc_html($c->post_title));
    }
    echo '</select></label></form>';

    if ($post_id) {
        $metas = array(
            '_curso_pagto_alt_prof_ativo', '_curso_pagto_alt_prof_link_cartao', '_curso_pagto_alt_prof_link_pix',
            '_curso_pagto_alt_aluno_ativo', '_curso_pagto_alt_aluno_link_cartao', '_curso_pagto_alt_aluno_link_pix',
            '_curso_pagto_alt_ativo', '_curso_pagto_alt_link_cartao', '_curso_pagto_alt_link_pix',
        );
        echo '<h2>Metas gravadas (#' . intval($post_id) . ' — ' . esc_html(get_the_title($post_id)) . ')</h2>';
        echo '<table class="widefat striped" style="max-width:820px"><thead><tr><th>Meta key</th><th>Valor</th></tr></thead><tbody>';
        foreach ($metas as $mk) {
            $val = get_post_meta($post_id, $mk, true);
            $display = ($val === '' || $val === null) ? '<em style="color:#999">(vazio)</em>' : '<code>' . esc_html((string) $val) . '</code>';
            echo '<tr><td><code>' . esc_html($mk) . '</code></td><td>' . $display . '</td></tr>';
        }
        echo '</tbody></table>';

        if (function_exists('cursos_get_pagto_alternativo_redirect')) {
            $items = array(array('id' => $post_id));
            $combos = array(
                array('label' => 'Profissional + Cartão', 'is_student' => false, 'method' => 'credit_card'),
                array('label' => 'Profissional + PIX',    'is_student' => false, 'method' => 'pix'),
                array('label' => 'Aluno + Cartão',        'is_student' => true,  'method' => 'credit_card'),
                array('label' => 'Aluno + PIX',           'is_student' => true,  'method' => 'pix'),
            );
            echo '<h2>Resultado do helper</h2>';
            echo '<table class="widefat striped" style="max-width:820px"><thead><tr><th>Combinação</th><th>ativo</th><th>url</th><th>motivo</th></tr></thead><tbody>';
            foreach ($combos as $c) {
                $r = cursos_get_pagto_alternativo_redirect($items, $c['method'], $c['is_student']);
                $ativo_txt = !empty($r['ativo']) ? '<strong style="color:#2e7d32">true</strong>' : '<span style="color:#999">false</span>';
                $url_txt = !empty($r['url']) ? '<code>' . esc_html($r['url']) . '</code>' : '<em style="color:#999">(vazio)</em>';
                $motivo_txt = !empty($r['motivo']) ? esc_html($r['motivo']) : '—';
                echo '<tr><td>' . esc_html($c['label']) . '</td><td>' . $ativo_txt . '</td><td>' . $url_txt . '</td><td>' . $motivo_txt . '</td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<div class="notice notice-error inline"><p>Função <code>cursos_get_pagto_alternativo_redirect()</code> não existe.</p></div>';
        }
    }

    $last = get_option('cursos_pagto_alt_last_save');
    echo '<h2>Último save registrado</h2>';
    if (is_array($last)) {
        echo '<table class="widefat" style="max-width:820px"><tbody>';
        echo '<tr><th style="width:180px">Curso</th><td>#' . intval($last['post_id'] ?? 0) . ' — ' . esc_html(get_the_title($last['post_id'] ?? 0)) . '</td></tr>';
        echo '<tr><th>Quando</th><td>' . esc_html($last['when'] ?? '') . '</td></tr>';
        echo '<tr><th>Usuário</th><td>' . esc_html($last['user'] ?? '') . '</td></tr>';
        echo '<tr><th>POST keys pagto_alt</th><td><code>' . esc_html(implode(', ', (array)($last['post_keys'] ?? array()))) . '</code></td></tr>';
        echo '<tr><th>Payload</th><td><pre style="margin:0;white-space:pre-wrap">' . esc_html(wp_json_encode($last['payload'] ?? array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</pre></td></tr>';
        echo '</tbody></table>';
    } else {
        echo '<p><em>Nenhum save registrado ainda. Salve um curso com o Pagamento Alternativo configurado.</em></p>';
    }

    $chk = get_transient('cursos_pagto_alt_diag_' . get_current_user_id());
    echo '<h2>Último checkout registrado (seu usuário)</h2>';
    if (is_array($chk)) {
        echo '<pre style="background:#fff;padding:12px;border:1px solid #ccd0d4;max-width:820px;white-space:pre-wrap">' . esc_html(wp_json_encode($chk, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</pre>';
    } else {
        echo '<p><em>Nenhum checkout registrado nos últimos 5 minutos.</em></p>';
    }

    echo '</div>';
}


/**
 * Metabox de diagnóstico do Pagamento Alternativo (somente admins).
 * Mostra as metas gravadas e o resultado do helper para as 4 combinações.
 */
function cursos_curso_pagto_alternativo_diag_callback($post) {
    if (!current_user_can('manage_options')) {
        echo '<p>Sem permissão.</p>';
        return;
    }

    $metas = array(
        '_curso_pagto_alt_prof_ativo',
        '_curso_pagto_alt_prof_link_cartao',
        '_curso_pagto_alt_prof_link_pix',
        '_curso_pagto_alt_aluno_ativo',
        '_curso_pagto_alt_aluno_link_cartao',
        '_curso_pagto_alt_aluno_link_pix',
        '_curso_pagto_alt_ativo',
        '_curso_pagto_alt_link_cartao',
        '_curso_pagto_alt_link_pix',
    );

    echo '<p style="margin-top:0"><em>Visível apenas para administradores. Não altera o comportamento do checkout.</em></p>';

    echo '<h4 style="margin-bottom:4px">Metas gravadas neste curso (#' . intval($post->ID) . ')</h4>';
    echo '<table class="widefat striped" style="max-width:820px"><thead><tr><th>Meta key</th><th>Valor</th></tr></thead><tbody>';
    foreach ($metas as $mk) {
        $val = get_post_meta($post->ID, $mk, true);
        $display = ($val === '' || $val === null) ? '<em style="color:#999">(vazio)</em>' : '<code>' . esc_html((string) $val) . '</code>';
        echo '<tr><td><code>' . esc_html($mk) . '</code></td><td>' . $display . '</td></tr>';
    }
    echo '</tbody></table>';

    if (function_exists('cursos_get_pagto_alternativo_redirect')) {
        $items = array(array('id' => $post->ID));
        $combos = array(
            array('label' => 'Profissional + Cartão', 'is_student' => false, 'method' => 'credit_card'),
            array('label' => 'Profissional + PIX',    'is_student' => false, 'method' => 'pix'),
            array('label' => 'Aluno + Cartão',        'is_student' => true,  'method' => 'credit_card'),
            array('label' => 'Aluno + PIX',           'is_student' => true,  'method' => 'pix'),
        );
        echo '<h4 style="margin-bottom:4px">Resultado do helper para este curso</h4>';
        echo '<table class="widefat striped" style="max-width:820px"><thead><tr><th>Combinação</th><th>ativo</th><th>url</th><th>motivo</th></tr></thead><tbody>';
        foreach ($combos as $c) {
            $r = cursos_get_pagto_alternativo_redirect($items, $c['method'], $c['is_student']);
            $ativo_txt = !empty($r['ativo']) ? '<strong style="color:#2e7d32">true</strong>' : '<span style="color:#999">false</span>';
            $url_txt = !empty($r['url']) ? '<code>' . esc_html($r['url']) . '</code>' : '<em style="color:#999">(vazio)</em>';
            $motivo_txt = !empty($r['motivo']) ? esc_html($r['motivo']) : '<em style="color:#999">—</em>';
            echo '<tr><td>' . esc_html($c['label']) . '</td><td>' . $ativo_txt . '</td><td>' . $url_txt . '</td><td>' . $motivo_txt . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    $last = get_option('cursos_pagto_alt_last_save');
    if (is_array($last)) {
        echo '<h4 style="margin-bottom:4px">Último save registrado (qualquer curso)</h4>';
        echo '<table class="widefat" style="max-width:820px"><tbody>';
        echo '<tr><th style="width:180px">Curso</th><td>#' . intval($last['post_id'] ?? 0) . ' — ' . esc_html(get_the_title($last['post_id'] ?? 0)) . '</td></tr>';
        echo '<tr><th>Quando</th><td>' . esc_html($last['when'] ?? '') . '</td></tr>';
        echo '<tr><th>Usuário</th><td>' . esc_html($last['user'] ?? '') . '</td></tr>';
        echo '<tr><th>Payload</th><td><pre style="margin:0;white-space:pre-wrap">' . esc_html(wp_json_encode($last['payload'] ?? array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</pre></td></tr>';
        echo '</tbody></table>';
    } else {
        echo '<p><em>Nenhum save do Pagamento Alternativo registrado ainda. Salve este curso para gerar o registro.</em></p>';
    }
}


/**
 * Resolve o redirecionamento de Pagamento Alternativo para os itens do checkout.
 *
 * Regra: se qualquer item tiver o modo ativo, o pedido inteiro é alternativo.
 * Todos os itens ativos precisam apontar para o MESMO link no método escolhido.
 *
 * @param array  $checkout_items Itens do checkout (usa $item['id'] como curso_id).
 * @param string $payment_method 'credit_card' | 'pix'
 * @return array{ativo:bool,url:string,motivo:string}
 */
function cursos_get_pagto_alternativo_redirect($checkout_items, $payment_method, $is_student = false) {
    $ativo = false;
    $urls = array();
    $prefix = $is_student ? '_curso_pagto_alt_aluno_' : '_curso_pagto_alt_prof_';
    $meta_ativo  = $prefix . 'ativo';
    $meta_link   = $prefix . 'link_' . (($payment_method === 'pix') ? 'pix' : 'cartao');
    $legacy_link = ($payment_method === 'pix') ? '_curso_pagto_alt_link_pix' : '_curso_pagto_alt_link_cartao';

    error_log('[PagtoAlt] payment_method=' . $payment_method . ' is_student=' . ($is_student ? '1' : '0') . ' prefix=' . $prefix);

    foreach ((array) $checkout_items as $item) {
        $curso_id = isset($item['id']) ? intval($item['id']) : (isset($item['curso_id']) ? intval($item['curso_id']) : 0);
        if (!$curso_id) continue;

        $v_prof_ativo  = get_post_meta($curso_id, '_curso_pagto_alt_prof_ativo', true);
        $v_aluno_ativo = get_post_meta($curso_id, '_curso_pagto_alt_aluno_ativo', true);
        $v_legacy_ativo= get_post_meta($curso_id, '_curso_pagto_alt_ativo', true);
        $v_meta_ativo  = get_post_meta($curso_id, $meta_ativo, true);
        $v_meta_link   = get_post_meta($curso_id, $meta_link, true);
        error_log("[PagtoAlt] curso=$curso_id prof_ativo=$v_prof_ativo aluno_ativo=$v_aluno_ativo legacy_ativo=$v_legacy_ativo meta_ativo($meta_ativo)=$v_meta_ativo meta_link($meta_link)=$v_meta_link");

        // Fallback legado: se as três metas da modalidade nunca foram salvas, usar as antigas
        $has_new = (get_post_meta($curso_id, $prefix . 'ativo', true) !== '')
            || (get_post_meta($curso_id, $prefix . 'link_cartao', true) !== '')
            || (get_post_meta($curso_id, $prefix . 'link_pix', true) !== '');

        if ($has_new) {
            if (get_post_meta($curso_id, $meta_ativo, true) !== '1') continue;
            $url = trim((string) get_post_meta($curso_id, $meta_link, true));
        } else {
            if (get_post_meta($curso_id, '_curso_pagto_alt_ativo', true) !== '1') continue;
            $url = trim((string) get_post_meta($curso_id, $legacy_link, true));
        }

        $ativo = true;
        if ($url !== '') $urls[$url] = true;
    }

    error_log('[PagtoAlt] resultado ativo=' . ($ativo ? '1' : '0') . ' urls=' . wp_json_encode(array_keys($urls)));

    if (!$ativo) return array('ativo' => false, 'url' => '', 'motivo' => '');

    if (empty($urls)) {
        return array('ativo' => true, 'url' => '', 'motivo' => 'Esta forma de pagamento não está disponível para este curso no momento. Escolha outra opção.');
    }
    if (count($urls) > 1) {
        return array('ativo' => true, 'url' => '', 'motivo' => 'Os cursos no carrinho utilizam links de pagamento diferentes. Finalize a inscrição de um curso por vez.');
    }
    return array('ativo' => true, 'url' => array_key_first($urls), 'motivo' => '');
}



/**
 * Salvar meta fields do Curso
 */
function cursos_save_curso_meta($post_id) {
    if (!isset($_POST['cursos_curso_nonce']) || !wp_verify_nonce($_POST['cursos_curso_nonce'], 'cursos_curso_save')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Identifica os cursos que devem exibir o banner global presencial.
    update_post_meta($post_id, '_curso_presencial', isset($_POST['curso_presencial']) ? '1' : '0');
    
    // Campos simples (exceto preços que precisam de tratamento especial)
    $fields = array(
        'curso_duracao' => '_curso_duracao',
        'curso_aulas' => '_curso_aulas',
        'curso_alunos' => '_curso_alunos',
        'curso_categoria' => '_curso_categoria',
        'curso_tipo' => '_curso_tipo',
        'curso_publico_alvo' => '_curso_publico_alvo',
        'curso_limite_vagas' => '_curso_limite_vagas',
    );
    
    foreach ($fields as $field => $meta_key) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
        }
    }
    
    // Professores (array de IDs - suporte a múltiplos professores)
    if (isset($_POST['curso_professores']) && is_array($_POST['curso_professores'])) {
        $professores_ids = array_map('absint', $_POST['curso_professores']);
        $professores_ids = array_filter($professores_ids); // Remove zeros
        $professores_ids = array_values($professores_ids); // Reindexa array
        update_post_meta($post_id, '_curso_professores', $professores_ids);
        
        // Manter compatibilidade: salvar primeiro professor no campo antigo
        if (!empty($professores_ids)) {
            update_post_meta($post_id, '_curso_professor', $professores_ids[0]);
        } else {
            delete_post_meta($post_id, '_curso_professor');
        }
    } else {
        // Se nenhum selecionado, limpar ambos os campos
        update_post_meta($post_id, '_curso_professores', array());
        delete_post_meta($post_id, '_curso_professor');
    }
    
    // Campos de preço (converte de formato brasileiro para float)
    $price_fields = array(
        'curso_preco' => '_curso_preco',
        'curso_preco_original' => '_curso_preco_original',
    );
    
    foreach ($price_fields as $field => $meta_key) {
        if (isset($_POST[$field])) {
            $value = $_POST[$field];
            // Remove R$, pontos de milhar e converte vírgula para ponto
            $value = preg_replace('/[R$\s.]/', '', $value);
            $value = str_replace(',', '.', $value);
            $value = floatval($value);
            update_post_meta($post_id, $meta_key, $value);
        }
    }
    
    // Desconto por curso — estudante
    if (isset($_POST['curso_desconto_estudante_percent'])) {
        $pct = floatval(str_replace(',', '.', $_POST['curso_desconto_estudante_percent']));
        if ($pct < 0) $pct = 0;
        if ($pct > 100) $pct = 100;
        update_post_meta($post_id, '_curso_desconto_estudante_percent', $pct);
        // Compat: manter o meta legado em sincronia com o desconto de estudante
        update_post_meta($post_id, '_curso_desconto_percent', $pct);
    }
    $est_acc = isset($_POST['curso_desconto_estudante_acumula']) ? '1' : '0';
    update_post_meta($post_id, '_curso_desconto_estudante_acumula', $est_acc);
    update_post_meta($post_id, '_curso_desconto_acumula', $est_acc); // compat

    // Desconto por curso — profissional via PIX
    if (isset($_POST['curso_desconto_profissional_percent'])) {
        $pct = floatval(str_replace(',', '.', $_POST['curso_desconto_profissional_percent']));
        if ($pct < 0) $pct = 0;
        if ($pct > 100) $pct = 100;
        update_post_meta($post_id, '_curso_desconto_profissional_percent', $pct);
    }

    // Pagamento Alternativo (Link Asaas) — por modalidade
    $alt_invalid = array();
    $alt_modalidades = array(
        'prof'  => 'Profissional',
        'aluno' => 'Aluno',
    );
    foreach ($alt_modalidades as $slug => $titulo) {
        $field_ativo = 'curso_pagto_alt_' . $slug . '_ativo';
        update_post_meta($post_id, '_curso_pagto_alt_' . $slug . '_ativo', isset($_POST[$field_ativo]) ? '1' : '0');

        $url_fields = array(
            'curso_pagto_alt_' . $slug . '_link_cartao' => '_curso_pagto_alt_' . $slug . '_link_cartao',
            'curso_pagto_alt_' . $slug . '_link_pix'    => '_curso_pagto_alt_' . $slug . '_link_pix',
        );
        foreach ($url_fields as $field => $meta_key) {
            if (!isset($_POST[$field])) continue;
            $raw = trim((string) $_POST[$field]);
            if ($raw === '') {
                delete_post_meta($post_id, $meta_key);
                continue;
            }
            $sanitized = esc_url_raw($raw);
            $is_valid = $sanitized && filter_var($sanitized, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $sanitized);
            if ($is_valid) {
                update_post_meta($post_id, $meta_key, $sanitized);
            } else {
                $tipo = (strpos($field, 'cartao') !== false) ? 'Cartão de Crédito' : 'PIX';
                $alt_invalid[] = $titulo . ' - ' . $tipo;
            }
        }
    }
    if (!empty($alt_invalid)) {
        set_transient('cursos_pagto_alt_invalid_' . get_current_user_id(), $alt_invalid, 60);
    }

    // Registra o payload do último save de Pagamento Alternativo (diagnóstico admin).
    $alt_payload = array();
    foreach ($alt_modalidades as $slug => $titulo) {
        $alt_payload[$slug] = array(
            'ativo'        => get_post_meta($post_id, '_curso_pagto_alt_' . $slug . '_ativo', true),
            'link_cartao'  => get_post_meta($post_id, '_curso_pagto_alt_' . $slug . '_link_cartao', true),
            'link_pix'     => get_post_meta($post_id, '_curso_pagto_alt_' . $slug . '_link_pix', true),
        );
    }
    $current_user = wp_get_current_user();
    update_option('cursos_pagto_alt_last_save', array(
        'post_id' => $post_id,
        'when'    => current_time('mysql'),
        'user'    => $current_user ? ($current_user->user_login . ' (#' . $current_user->ID . ')') : 'desconhecido',
        'payload' => $alt_payload,
        'post_keys' => array_values(array_filter(array_keys($_POST), function($k) { return strpos($k, 'curso_pagto_alt_') === 0; })),
    ), false);



    update_post_meta($post_id, '_curso_desconto_profissional_acumula', isset($_POST['curso_desconto_profissional_acumula']) ? '1' : '0');

    // Parcelas sem juros por curso (sobrescreve o global)
    if (isset($_POST['curso_parcelamento_sem_juros'])) {
        $raw = trim((string) $_POST['curso_parcelamento_sem_juros']);
        if ($raw === '') {
            delete_post_meta($post_id, '_curso_parcelamento_sem_juros');
        } else {
            $sj = max(0, min(24, intval($raw)));
            update_post_meta($post_id, '_curso_parcelamento_sem_juros', $sj);
        }
    }

    
    // Checkbox destaque
    update_post_meta($post_id, '_curso_destaque', isset($_POST['curso_destaque']) ? '1' : '0');
    
    // Checkbox sem turma aberta
    update_post_meta($post_id, '_curso_sem_turma_aberta', isset($_POST['curso_sem_turma_aberta']) ? '1' : '0');
    
    // Link da Lista de Espera
    if (isset($_POST['curso_waitlist_url'])) {
        update_post_meta($post_id, '_curso_waitlist_url', esc_url_raw($_POST['curso_waitlist_url']));
    }
    
    // O que você vai aprender (array simples)
    if (isset($_POST['curso_what_youll_learn']) && is_array($_POST['curso_what_youll_learn'])) {
        $items = array_filter(array_map('sanitize_text_field', $_POST['curso_what_youll_learn']));
        update_post_meta($post_id, '_curso_what_youll_learn', array_values($items));
    } else {
        delete_post_meta($post_id, '_curso_what_youll_learn');
    }
    
    // Currículo (array de módulos com aulas)
    if (isset($_POST['curso_curriculum']) && is_array($_POST['curso_curriculum'])) {
        $curriculum = array();
        foreach ($_POST['curso_curriculum'] as $module) {
            if (!empty($module['title'])) {
                $lessons = array();
                if (!empty($module['lessons']) && is_array($module['lessons'])) {
                    $lessons = array_filter(array_map('sanitize_text_field', $module['lessons']));
                }
                $curriculum[] = array(
                    'title' => sanitize_text_field($module['title']),
                    'lessons' => array_values($lessons),
                );
            }
        }
        update_post_meta($post_id, '_curso_curriculum', $curriculum);
    } else {
        delete_post_meta($post_id, '_curso_curriculum');
    }
    
    // O que está incluso (array de icon + text)
    if (isset($_POST['curso_includes']) && is_array($_POST['curso_includes'])) {
        $includes = array();
        foreach ($_POST['curso_includes'] as $item) {
            if (!empty($item['text'])) {
                $includes[] = array(
                    'icon' => sanitize_text_field($item['icon'] ?? 'check-circle'),
                    'text' => sanitize_text_field($item['text']),
                );
            }
        }
        update_post_meta($post_id, '_curso_includes', $includes);
    } else {
        delete_post_meta($post_id, '_curso_includes');
    }
    
    // FAQs (array de question + answer)
    if (isset($_POST['curso_faqs']) && is_array($_POST['curso_faqs'])) {
        $faqs = array();
        foreach ($_POST['curso_faqs'] as $faq) {
            if (!empty($faq['question'])) {
                $faqs[] = array(
                    'question' => sanitize_text_field($faq['question']),
                    'answer' => sanitize_textarea_field($faq['answer'] ?? ''),
                );
            }
        }
        update_post_meta($post_id, '_curso_faqs', $faqs);
    } else {
        delete_post_meta($post_id, '_curso_faqs');
    }
    
    // Turmas (array de turma data)
    if (isset($_POST['curso_turmas']) && is_array($_POST['curso_turmas'])) {
        $turmas = array();
        foreach ($_POST['curso_turmas'] as $turma) {
            if (!empty($turma['nome']) || !empty($turma['id'])) {
                $turmas[] = array(
                    'id' => sanitize_text_field($turma['id'] ?? ''),
                    'nome' => sanitize_text_field($turma['nome'] ?? ''),
                    'data_inicio' => sanitize_text_field($turma['data_inicio'] ?? ''),
                    'data_fim' => sanitize_text_field($turma['data_fim'] ?? ''),
                    'vagas' => intval($turma['vagas'] ?? 0),
                );
            }
        }
        update_post_meta($post_id, '_curso_turmas', $turmas);
    } else {
        delete_post_meta($post_id, '_curso_turmas');
    }
}
add_action('save_post_curso', 'cursos_save_curso_meta');

/**
 * Banner global exibido nos cursos marcados como presenciais.
 */
function cursos_register_banners_menu() {
    add_submenu_page(
        'edit.php?post_type=curso',
        'Banners dos Cursos',
        'Banners',
        'manage_options',
        'cursos-banners',
        'cursos_banners_page'
    );
}
add_action('admin_menu', 'cursos_register_banners_menu', 30);

function cursos_banners_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para acessar esta página.', 'cursos-theme'));
    }

    if (isset($_POST['cursos_banner_submit'])) {
        check_admin_referer('cursos_banner_save', 'cursos_banner_nonce');

        $tipo = isset($_POST['cursos_presencial_banner_tipo']) && $_POST['cursos_presencial_banner_tipo'] === 'html' ? 'html' : 'imagem';
        update_option('cursos_presencial_banner_ativo', isset($_POST['cursos_presencial_banner_ativo']) ? '1' : '0');
        update_option('cursos_presencial_banner_tipo', $tipo);
        update_option('cursos_presencial_banner_imagem_id', isset($_POST['cursos_presencial_banner_imagem_id']) ? absint($_POST['cursos_presencial_banner_imagem_id']) : 0);
        update_option('cursos_presencial_banner_link', isset($_POST['cursos_presencial_banner_link']) ? esc_url_raw($_POST['cursos_presencial_banner_link']) : '');
        update_option('cursos_presencial_banner_html', isset($_POST['cursos_presencial_banner_html']) ? cursos_sanitize_banner_html(wp_unslash($_POST['cursos_presencial_banner_html'])) : '');

        echo '<div class="notice notice-success is-dismissible"><p>Banner salvo com sucesso.</p></div>';
    }

    $ativo = get_option('cursos_presencial_banner_ativo', '0') === '1';
    $tipo = get_option('cursos_presencial_banner_tipo', 'imagem');
    $imagem_id = absint(get_option('cursos_presencial_banner_imagem_id', 0));
    $imagem_url = $imagem_id ? wp_get_attachment_image_url($imagem_id, 'large') : '';
    $link = get_option('cursos_presencial_banner_link', '');
    $html = get_option('cursos_presencial_banner_html', '');
    ?>
    <div class="wrap">
        <h1>Banner dos Cursos Presenciais</h1>
        <p>Este banner será usado em todos os cursos com a opção <strong>Curso Presencial</strong> ativada.</p>

        <form method="post">
            <?php wp_nonce_field('cursos_banner_save', 'cursos_banner_nonce'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Exibição</th>
                    <td>
                        <label><input type="checkbox" name="cursos_presencial_banner_ativo" value="1" <?php checked($ativo); ?>> Ativar banner global</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Formato</th>
                    <td>
                        <fieldset>
                            <label><input type="radio" name="cursos_presencial_banner_tipo" value="imagem" <?php checked($tipo, 'imagem'); ?>> Imagem</label><br>
                            <label><input type="radio" name="cursos_presencial_banner_tipo" value="html" <?php checked($tipo, 'html'); ?>> HTML / conteúdo personalizado</label>
                        </fieldset>
                    </td>
                </tr>
            </table>

            <div id="cursos-banner-imagem-fields" style="<?php echo $tipo === 'imagem' ? '' : 'display:none;'; ?>">
                <h2>Banner em imagem</h2>
                <input type="hidden" id="cursos_presencial_banner_imagem_id" name="cursos_presencial_banner_imagem_id" value="<?php echo esc_attr($imagem_id); ?>">
                <div id="cursos-banner-image-preview" style="margin:12px 0;max-width:720px;">
                    <?php if ($imagem_url): ?>
                        <img src="<?php echo esc_url($imagem_url); ?>" alt="Prévia do banner" style="display:block;max-width:100%;height:auto;">
                    <?php endif; ?>
                </div>
                <p>
                    <button type="button" class="button button-secondary" id="cursos-banner-select-image"><?php echo $imagem_url ? 'Trocar imagem' : 'Selecionar imagem'; ?></button>
                    <button type="button" class="button" id="cursos-banner-remove-image" style="<?php echo $imagem_url ? '' : 'display:none;'; ?>">Remover imagem</button>
                </p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="cursos_presencial_banner_link">Link opcional</label></th>
                        <td>
                            <input type="url" id="cursos_presencial_banner_link" name="cursos_presencial_banner_link" value="<?php echo esc_attr($link); ?>" class="regular-text" placeholder="https://">
                            <p class="description">Quando preenchido, o clique na imagem abrirá este endereço.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="cursos-banner-html-fields" style="<?php echo $tipo === 'html' ? '' : 'display:none;'; ?>max-width:900px;">
                <h2>Conteúdo HTML</h2>
                <p class="description">Cole o código HTML e CSS diretamente no campo abaixo.</p>
                <?php wp_editor($html, 'cursos_presencial_banner_html', array('textarea_name' => 'cursos_presencial_banner_html', 'textarea_rows' => 16, 'media_buttons' => true, 'tinymce' => false, 'quicktags' => true)); ?>
            </div>

            <?php submit_button('Salvar banner', 'primary', 'cursos_banner_submit'); ?>
        </form>

        <?php if ($ativo && (($tipo === 'imagem' && $imagem_url) || ($tipo === 'html' && trim($html) !== ''))): ?>
            <hr>
            <h2>Prévia</h2>
            <div style="max-width:720px;background:#fff;border:1px solid #dcdcde;padding:16px;">
                <?php echo cursos_get_banner_presencial_html(); ?>
            </div>
        <?php endif; ?>
    </div>
    <script>
    jQuery(function($) {
        function toggleBannerFields() {
            var type = $('input[name="cursos_presencial_banner_tipo"]:checked').val();
            $('#cursos-banner-imagem-fields').toggle(type === 'imagem');
            $('#cursos-banner-html-fields').toggle(type === 'html');
        }
        $('input[name="cursos_presencial_banner_tipo"]').on('change', toggleBannerFields);

        var mediaFrame;
        $('#cursos-banner-select-image').on('click', function(event) {
            event.preventDefault();
            if (mediaFrame) {
                mediaFrame.open();
                return;
            }
            mediaFrame = wp.media({ title: 'Selecionar banner', button: { text: 'Usar esta imagem' }, multiple: false });
            mediaFrame.on('select', function() {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                var url = attachment.sizes && attachment.sizes.large ? attachment.sizes.large.url : attachment.url;
                $('#cursos_presencial_banner_imagem_id').val(attachment.id);
                $('#cursos-banner-image-preview').html($('<img>', { src: url, alt: 'Prévia do banner' }).css({ display: 'block', maxWidth: '100%', height: 'auto' }));
                $('#cursos-banner-select-image').text('Trocar imagem');
                $('#cursos-banner-remove-image').show();
            });
            mediaFrame.open();
        });
        $('#cursos-banner-remove-image').on('click', function() {
            $('#cursos_presencial_banner_imagem_id').val('');
            $('#cursos-banner-image-preview').empty();
            $('#cursos-banner-select-image').text('Selecionar imagem');
            $(this).hide();
        });
    });
    </script>
    <?php
}

/**
 * Normaliza e protege o código personalizado do banner.
 * Também recupera códigos que foram convertidos em texto pelo editor visual.
 */
function cursos_sanitize_banner_html($conteudo) {
    $conteudo = (string) $conteudo;

    if (preg_match('/&lt;\/?(?:style|div|section|a|span)\b/i', $conteudo)) {
        $conteudo = html_entity_decode($conteudo, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    $permitidos = wp_kses_allowed_html('post');
    $permitidos['style'] = array(
        'type' => true,
        'media' => true,
    );

    return wp_kses($conteudo, $permitidos);
}

function cursos_get_banner_presencial_html() {
    if (get_option('cursos_presencial_banner_ativo', '0') !== '1') return '';

    $tipo = get_option('cursos_presencial_banner_tipo', 'imagem');
    if ($tipo === 'html') {
        $conteudo = trim(cursos_sanitize_banner_html(get_option('cursos_presencial_banner_html', '')));
        return $conteudo === '' ? '' : '<div class="curso-presencial-banner-html">' . $conteudo . '</div>';
    }

    $imagem_id = absint(get_option('cursos_presencial_banner_imagem_id', 0));
    if (!$imagem_id) return '';

    $imagem = wp_get_attachment_image($imagem_id, 'large', false, array('class' => 'curso-presencial-banner-image', 'loading' => 'lazy'));
    if (!$imagem) return '';

    $link = get_option('cursos_presencial_banner_link', '');
    if ($link) {
        $imagem = '<a href="' . esc_url($link) . '" class="curso-presencial-banner-link">' . $imagem . '</a>';
    }

    return $imagem;
}

/**
 * Atualiza o meta _curso_proxima_turma_data com a data da próxima turma futura com vagas
 * Isso permite ordenação via SQL sem precisar deserializar arrays
 */
function cursos_update_proxima_turma_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (get_post_type($post_id) !== 'curso') return;
    
    $turmas = get_post_meta($post_id, '_curso_turmas', true);
    $sem_turma = get_post_meta($post_id, '_curso_sem_turma_aberta', true);
    
    if (empty($turmas) || !is_array($turmas) || $sem_turma === '1') {
        update_post_meta($post_id, '_curso_proxima_turma_data', '9999-12-31');
        return;
    }
    
    $hoje = date('Y-m-d');
    $proxima_data = '9999-12-31';
    
    foreach ($turmas as $turma) {
        if (empty($turma['data_inicio'])) continue;
        if ($turma['data_inicio'] >= $hoje && $turma['data_inicio'] < $proxima_data) {
            $proxima_data = $turma['data_inicio'];
        }
    }
    
    update_post_meta($post_id, '_curso_proxima_turma_data', $proxima_data);
}
add_action('save_post_curso', 'cursos_update_proxima_turma_meta', 25);

/**
 * Cron diário: atualiza _curso_proxima_turma_data de todos os cursos
 * para que turmas que passaram sejam removidas da ordenação automaticamente
 */
function cursos_cron_update_proximas_turmas() {
    $cursos = get_posts(array(
        'post_type' => 'curso',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'fields' => 'ids',
    ));
    
    foreach ($cursos as $curso_id) {
        cursos_update_proxima_turma_meta($curso_id);
    }
}
add_action('cursos_daily_update_proximas_turmas', 'cursos_cron_update_proximas_turmas');

// Agendar cron se não existir
if (!wp_next_scheduled('cursos_daily_update_proximas_turmas')) {
    wp_schedule_event(time(), 'daily', 'cursos_daily_update_proximas_turmas');
}

// Inicializar meta _curso_proxima_turma_data para cursos existentes (roda uma vez)
if (!get_option('cursos_proxima_turma_meta_initialized')) {
    add_action('init', function() {
        cursos_cron_update_proximas_turmas();
        update_option('cursos_proxima_turma_meta_initialized', true);
    }, 999);
}

/**
 * ========================================
 * NOTIFICAÇÃO LISTA DE ESPERA - NOVA TURMA
 * ========================================
 */

/**
 * Detectar quando novas turmas são adicionadas e notificar lista de espera
 */
function cursos_check_new_turmas_and_notify($post_id) {
    // Evitar auto-save e revisões
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if (get_post_type($post_id) !== 'curso') return;
    
    // Verificar se notificações estão habilitadas
    $notify_enabled = get_option('cursos_waitlist_notify_enabled', '1');
    if ($notify_enabled !== '1') return;
    
    // Obter turmas antigas (antes do save)
    $old_turmas = get_transient('curso_turmas_before_save_' . $post_id);
    
    // Obter turmas novas (após o save)
    $new_turmas = get_post_meta($post_id, '_curso_turmas', true);
    if (!is_array($new_turmas)) $new_turmas = array();
    
    // Limpar transient
    delete_transient('curso_turmas_before_save_' . $post_id);
    
    // Se não tínhamos turmas antes, todas são novas
    if (!is_array($old_turmas)) $old_turmas = array();
    
    // Extrair IDs das turmas antigas
    $old_turma_ids = array_column($old_turmas, 'id');
    
    // Encontrar turmas que são realmente novas (ID não existia antes)
    $truly_new_turmas = array();
    foreach ($new_turmas as $turma) {
        if (!empty($turma['id']) && !in_array($turma['id'], $old_turma_ids)) {
            // Verificar se tem vagas disponíveis
            if (!empty($turma['vagas']) && intval($turma['vagas']) > 0) {
                $truly_new_turmas[] = $turma;
            }
        }
    }
    
    // Se há novas turmas, notificar lista de espera
    if (!empty($truly_new_turmas)) {
        cursos_notify_waitlist_new_turma($post_id, $truly_new_turmas);
    }
}
add_action('save_post_curso', 'cursos_check_new_turmas_and_notify', 20);

/**
 * Salvar turmas atuais antes do save (para comparação)
 */
function cursos_store_turmas_before_save($post_id) {
    if (get_post_type($post_id) !== 'curso') return;
    
    $current_turmas = get_post_meta($post_id, '_curso_turmas', true);
    if (!is_array($current_turmas)) $current_turmas = array();
    
    // Armazenar em transient (expira em 1 minuto)
    set_transient('curso_turmas_before_save_' . $post_id, $current_turmas, 60);
}
add_action('pre_post_update', 'cursos_store_turmas_before_save');

/**
 * Notificar todos da lista de espera sobre nova turma
 */
function cursos_notify_waitlist_new_turma($curso_id, $new_turmas) {
    // Buscar registros da lista de espera para este curso
    $waitlist_entries = get_posts(array(
        'post_type' => 'waitlist',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_waitlist_curso_id',
                'value' => $curso_id,
            ),
        ),
    ));
    
    if (empty($waitlist_entries)) return;
    
    // Dados do curso
    $curso_title = get_the_title($curso_id);
    $curso_url = get_permalink($curso_id);
    $curso_preco = get_post_meta($curso_id, '_curso_preco', true);
    $curso_preco_formatado = $curso_preco ? 'R$ ' . number_format(floatval($curso_preco), 2, ',', '.') : '';
    
    // Formatar informações das novas turmas
    $turmas_info = '';
    foreach ($new_turmas as $turma) {
        $turmas_info .= '<li><strong>' . esc_html($turma['nome']) . '</strong>';
        $data_formatada = cursos_format_turma_date($turma);
        if ($data_formatada) {
            $turmas_info .= ' - ' . $data_formatada;
        }
        if (!empty($turma['vagas'])) {
            $turmas_info .= ' (' . intval($turma['vagas']) . ' vagas)';
        }
        $turmas_info .= '</li>';
    }
    
    // Configurações de e-mail
    $sender_name = get_bloginfo('name');
    $sender_email = get_option('admin_email');
    $primary_color = '#7f0b0d';
    $logo_url = '';
    
    // Enviar e-mail para cada pessoa da lista
    foreach ($waitlist_entries as $entry) {
        $nome = get_post_meta($entry->ID, '_waitlist_nome', true);
        $email = get_post_meta($entry->ID, '_waitlist_email', true);
        
        if (!is_email($email)) continue;
        
        // Montar e-mail
        $subject = '🎉 Nova turma disponível: ' . $curso_title;
        
        $html_content = cursos_get_waitlist_email_html(array(
            'nome' => $nome,
            'curso_title' => $curso_title,
            'curso_url' => $curso_url,
            'curso_preco' => $curso_preco_formatado,
            'turmas_info' => $turmas_info,
            'primary_color' => $primary_color,
            'logo_url' => $logo_url,
            'site_nome' => get_bloginfo('name'),
            'ano' => date('Y'),
        ));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $sender_name . ' <' . $sender_email . '>',
        );
        
        // Enviar e-mail via wp_mail
        wp_mail($email, $subject, $html_content, $headers);
        
        // Registrar no meta do registro da lista de espera
        $notificacoes = get_post_meta($entry->ID, '_waitlist_notificacoes', true);
        if (!is_array($notificacoes)) $notificacoes = array();
        $notificacoes[] = array(
            'data' => current_time('timestamp'),
            'turmas' => array_column($new_turmas, 'nome'),
        );
        update_post_meta($entry->ID, '_waitlist_notificacoes', $notificacoes);
    }
    
    // Registrar log
    $log_message = sprintf(
        'Notificação de nova turma enviada para %d pessoas da lista de espera do curso "%s"',
        count($waitlist_entries),
        $curso_title
    );
    error_log('[Cursos] ' . $log_message);
}

/**
 * Template HTML do e-mail de nova turma
 */
function cursos_get_waitlist_email_html($data) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; }
        .email-wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .email-header { background-color: ' . esc_attr($data['primary_color']) . '; padding: 30px; text-align: center; }
        .email-header img { max-height: 50px; }
        .email-header h1 { color: #ffffff; margin: 0; font-size: 20px; }
        .email-body { padding: 40px 30px; }
        .email-body h2 { color: #1f2937; margin: 0 0 20px; font-size: 24px; }
        .email-body p { color: #4b5563; margin: 0 0 15px; }
        .curso-card { background: #f8f9fa; border-radius: 12px; padding: 20px; margin: 20px 0; border-left: 4px solid ' . esc_attr($data['primary_color']) . '; }
        .curso-card h3 { margin: 0 0 10px; color: ' . esc_attr($data['primary_color']) . '; }
        .curso-card .preco { font-size: 24px; font-weight: bold; color: #10b981; margin: 15px 0; }
        .turmas-list { margin: 15px 0; padding-left: 20px; }
        .turmas-list li { margin-bottom: 8px; color: #374151; }
        .btn { display: inline-block; padding: 14px 28px; background-color: ' . esc_attr($data['primary_color']) . '; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; margin: 20px 0; }
        .btn:hover { opacity: 0.9; }
        .email-footer { background: #f8f9fa; padding: 20px 30px; text-align: center; font-size: 12px; color: #6b7280; }
        .urgency-badge { display: inline-block; background: #fef3c7; color: #92400e; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 14px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">';
    
    if (!empty($data['logo_url'])) {
        $html .= '<img src="' . esc_url($data['logo_url']) . '" alt="' . esc_attr($data['site_nome']) . '">';
    } else {
        $html .= '<h1>' . esc_html($data['site_nome']) . '</h1>';
    }
    
    $html .= '</div>
        <div class="email-body">
            <span class="urgency-badge">🔔 Vagas Abertas!</span>
            <h2>Olá, ' . esc_html($data['nome']) . '!</h2>
            <p>Temos uma ótima notícia para você! O curso que você estava aguardando acabou de abrir novas turmas:</p>
            
            <div class="curso-card">
                <h3>' . esc_html($data['curso_title']) . '</h3>';
    
    if (!empty($data['curso_preco'])) {
        $html .= '<div class="preco">' . esc_html($data['curso_preco']) . '</div>';
    }
    
    $html .= '<p><strong>Novas turmas disponíveis:</strong></p>
                <ul class="turmas-list">' . $data['turmas_info'] . '</ul>
            </div>
            
            <p><strong>⚡ Corra para garantir sua vaga!</strong> As turmas costumam preencher rapidamente.</p>
            
            <p style="text-align: center;">
                <a href="' . esc_url($data['curso_url']) . '" class="btn">Ver Curso e Matricular-se</a>
            </p>
            
            <p style="font-size: 14px; color: #6b7280;">Se você não deseja mais receber avisos sobre este curso, basta ignorar este e-mail.</p>
        </div>
        <div class="email-footer">
            <p>&copy; ' . esc_html($data['ano']) . ' ' . esc_html($data['site_nome']) . '. Todos os direitos reservados.</p>
            <p>Você recebeu este e-mail porque se cadastrou na lista de espera.</p>
        </div>
    </div>
</body>
</html>';
    
    return $html;
}


/**
 * Adicionar opção de notificação nas configurações
 */
function cursos_register_waitlist_settings() {
    register_setting('cursos_settings', 'cursos_waitlist_notify_enabled');
}
add_action('admin_init', 'cursos_register_waitlist_settings');

/**
 * Colunas personalizadas na lista de cursos
 */
function cursos_curso_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['thumbnail'] = 'Imagem';
    $new_columns['title'] = $columns['title'];
    $new_columns['curso_tipo'] = 'Tipo';
    $new_columns['curso_preco'] = 'Preço';
    $new_columns['curso_alunos'] = 'Alunos';
    $new_columns['curso_destaque'] = 'Destaque';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}
add_filter('manage_curso_posts_columns', 'cursos_curso_columns');

function cursos_curso_column_content($column, $post_id) {
    switch ($column) {
        case 'thumbnail':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, array(60, 60), array('style' => 'border-radius: 4px;'));
            } else {
                echo '<span style="color: #999;">—</span>';
            }
            break;
        case 'curso_tipo':
            $tipo = get_post_meta($post_id, '_curso_tipo', true);
            $labels = array('online' => 'Online', 'particular' => 'Particular', 'pos-graduacao' => 'Pós-Graduação');
            echo isset($labels[$tipo]) ? $labels[$tipo] : '—';
            break;
        case 'curso_preco':
            $preco = get_post_meta($post_id, '_curso_preco', true);
            echo $preco ? 'R$ ' . number_format(floatval($preco), 2, ',', '.') : '—';
            break;
        case 'curso_alunos':
            echo get_post_meta($post_id, '_curso_alunos', true) ?: '0';
            break;
        case 'curso_destaque':
            $destaque = get_post_meta($post_id, '_curso_destaque', true);
            echo $destaque ? '⭐' : '—';
            break;
    }
}
add_action('manage_curso_posts_custom_column', 'cursos_curso_column_content', 10, 2);

/**
 * ========================================
 * CUSTOM POST TYPE - PROFESSORES
 * ========================================
 */
function cursos_register_professor_post_type() {
    $labels = array(
        'name' => 'Professores',
        'singular_name' => 'Professor',
        'menu_name' => 'Professores',
        'add_new' => 'Adicionar Novo',
        'add_new_item' => 'Adicionar Novo Professor',
        'edit_item' => 'Editar Professor',
        'new_item' => 'Novo Professor',
        'view_item' => 'Ver Professor',
        'search_items' => 'Buscar Professores',
        'not_found' => 'Nenhum professor encontrado',
        'not_found_in_trash' => 'Nenhum professor na lixeira',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'professores'),
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 6,
        'menu_icon' => 'dashicons-businessperson',
        'supports' => array('title', 'editor', 'thumbnail'),
    );

    register_post_type('professor', $args);
}
add_action('init', 'cursos_register_professor_post_type');

/**
 * Meta Box para Professores
 */
function cursos_professor_meta_boxes() {
    add_meta_box(
        'professor_detalhes',
        'Detalhes do Professor',
        'cursos_professor_detalhes_callback',
        'professor',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'cursos_professor_meta_boxes');

function cursos_professor_detalhes_callback($post) {
    wp_nonce_field('cursos_professor_save', 'cursos_professor_nonce');
    
    $titulo = get_post_meta($post->ID, '_professor_titulo', true);
    $email = get_post_meta($post->ID, '_professor_email', true);
    $linkedin = get_post_meta($post->ID, '_professor_linkedin', true);
    $website = get_post_meta($post->ID, '_professor_website', true);
    $experiencia = get_post_meta($post->ID, '_professor_experiencia', true);
    $especialidades = get_post_meta($post->ID, '_professor_especialidades', true);
    if (!is_array($especialidades)) $especialidades = array();
    ?>
    <style>
        .prof-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .prof-field { margin-bottom: 15px; }
        .prof-field label { display: block; font-weight: 600; margin-bottom: 5px; }
        .prof-field input[type="text"],
        .prof-field input[type="email"],
        .prof-field input[type="url"],
        .prof-field textarea { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .prof-field textarea { min-height: 100px; }
        @media (max-width: 768px) { .prof-grid { grid-template-columns: 1fr; } }
    </style>
    
    <div class="prof-grid">
        <div class="prof-field">
            <label for="professor_titulo">Título / Cargo</label>
            <input type="text" id="professor_titulo" name="professor_titulo" value="<?php echo esc_attr($titulo); ?>" placeholder="Ex: Doutor em Administração, MBA em Finanças">
        </div>
        
        <div class="prof-field">
            <label for="professor_email">E-mail</label>
            <input type="email" id="professor_email" name="professor_email" value="<?php echo esc_attr($email); ?>">
        </div>
        
        <div class="prof-field">
            <label for="professor_linkedin">LinkedIn</label>
            <input type="url" id="professor_linkedin" name="professor_linkedin" value="<?php echo esc_attr($linkedin); ?>" placeholder="https://linkedin.com/in/...">
        </div>
        
        <div class="prof-field">
            <label for="professor_website">Website</label>
            <input type="url" id="professor_website" name="professor_website" value="<?php echo esc_attr($website); ?>">
        </div>
    </div>
    
    <div class="prof-field">
        <label for="professor_experiencia">Anos de Experiência</label>
        <input type="text" id="professor_experiencia" name="professor_experiencia" value="<?php echo esc_attr($experiencia); ?>" placeholder="Ex: 15 anos" style="max-width: 200px;">
    </div>
    
    <div class="prof-field">
        <label>Especialidades</label>
        <div id="especialidades-container">
            <?php foreach ($especialidades as $i => $esp): ?>
            <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                <input type="text" name="professor_especialidades[]" value="<?php echo esc_attr($esp); ?>" style="flex: 1;">
                <button type="button" class="button remove-especialidade">Remover</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="add-especialidade">+ Adicionar Especialidade</button>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#add-especialidade').on('click', function() {
            var html = '<div style="display: flex; gap: 10px; margin-bottom: 8px;">' +
                '<input type="text" name="professor_especialidades[]" style="flex: 1;">' +
                '<button type="button" class="button remove-especialidade">Remover</button>' +
                '</div>';
            $('#especialidades-container').append(html);
        });
        
        $(document).on('click', '.remove-especialidade', function() {
            $(this).closest('div').remove();
        });
    });
    </script>
    <?php
}

function cursos_save_professor_meta($post_id) {
    if (!isset($_POST['cursos_professor_nonce']) || !wp_verify_nonce($_POST['cursos_professor_nonce'], 'cursos_professor_save')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $fields = array(
        'professor_titulo' => '_professor_titulo',
        'professor_email' => '_professor_email',
        'professor_linkedin' => '_professor_linkedin',
        'professor_website' => '_professor_website',
        'professor_experiencia' => '_professor_experiencia',
    );
    
    foreach ($fields as $field => $meta_key) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
        }
    }
    
    // Especialidades
    if (isset($_POST['professor_especialidades']) && is_array($_POST['professor_especialidades'])) {
        $especialidades = array_filter(array_map('sanitize_text_field', $_POST['professor_especialidades']));
        update_post_meta($post_id, '_professor_especialidades', $especialidades);
    }
}
add_action('save_post_professor', 'cursos_save_professor_meta');

/**
 * Helper: Obter dados do professor
 */
function cursos_get_professor_data($professor_id) {
    if (!$professor_id) return null;
    
    $professor = get_post($professor_id);
    if (!$professor || $professor->post_type !== 'professor') return null;
    
    return array(
        'id' => $professor_id,
        'nome' => $professor->post_title,
        'bio' => $professor->post_content,
        'foto' => get_the_post_thumbnail_url($professor_id, 'thumbnail'),
        'titulo' => get_post_meta($professor_id, '_professor_titulo', true),
        'email' => get_post_meta($professor_id, '_professor_email', true),
        'linkedin' => get_post_meta($professor_id, '_professor_linkedin', true),
        'website' => get_post_meta($professor_id, '_professor_website', true),
        'experiencia' => get_post_meta($professor_id, '_professor_experiencia', true),
        'especialidades' => get_post_meta($professor_id, '_professor_especialidades', true) ?: array(),
    );
}

/**
 * AJAX: Obter dados do professor
 */
function cursos_ajax_get_professor_data() {
    check_ajax_referer('cursos_builder_nonce', 'nonce');
    
    $professor_id = intval($_POST['professor_id']);
    $data = cursos_get_professor_data($professor_id);
    
    if ($data) {
        $data['edit_link'] = get_edit_post_link($professor_id, 'raw');
        wp_send_json_success($data);
    } else {
        wp_send_json_error('Professor não encontrado');
    }
}
add_action('wp_ajax_get_professor_data', 'cursos_ajax_get_professor_data');

/**
 * Adicionar coluna de foto na lista de professores
 */
function cursos_professor_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['professor_photo'] = 'Foto';
    
    foreach ($columns as $key => $value) {
        if ($key !== 'cb') {
            $new_columns[$key] = $value;
        }
    }
    
    return $new_columns;
}
add_filter('manage_professor_posts_columns', 'cursos_professor_columns');

function cursos_professor_column_content($column, $post_id) {
    switch ($column) {
        case 'professor_photo':
            $thumb = get_the_post_thumbnail_url($post_id, 'thumbnail');
            if ($thumb) {
                echo '<img src="' . esc_url($thumb) . '" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">';
            } else {
                echo '<span class="dashicons dashicons-admin-users" style="font-size: 40px; color: #ccc;"></span>';
            }
            break;
    }
}
add_action('manage_professor_posts_custom_column', 'cursos_professor_column_content', 10, 2);

// Taxonomias de cursos removidas - serão recriadas via Elementor/ACF

// Meta boxes de cursos removidas - serão recriadas via Elementor/ACF

// Funções de callback e save de cursos removidas - serão recriadas via Elementor/ACF

/**
 * ========================================
 * PÁGINA DE CONFIGURAÇÕES DO TEMA
 * ========================================
 */
function cursos_theme_menu() {
    add_menu_page(
        'Configurações do Tema',
        'Cursos Config',
        'manage_options',
        'cursos-config',
        'cursos_config_page',
        'dashicons-admin-generic',
        60
    );
    
    add_submenu_page(
        'cursos-config',
        'Configurações de Pagamento',
        'Pagamentos',
        'manage_options',
        'cursos-pagamentos',
        'cursos_pagamentos_page'
    );
}
add_action('admin_menu', 'cursos_theme_menu');

function cursos_config_page() {
    if (isset($_POST['cursos_config_submit'])) {
        check_admin_referer('cursos_config_save');
        
        update_option('cursos_whatsapp', sanitize_text_field($_POST['cursos_whatsapp']));
        update_option('cursos_email', sanitize_email($_POST['cursos_email']));
        update_option('cursos_telefone', sanitize_text_field($_POST['cursos_telefone']));
        update_option('cursos_endereco', sanitize_textarea_field($_POST['cursos_endereco']));
        update_option('cursos_facebook', esc_url($_POST['cursos_facebook']));
        update_option('cursos_instagram', esc_url($_POST['cursos_instagram']));
        update_option('cursos_youtube', esc_url($_POST['cursos_youtube']));
        update_option('cursos_linkedin', esc_url($_POST['cursos_linkedin']));
        
        echo '<div class="notice notice-success"><p>Configurações salvas com sucesso!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Configurações do Tema - Cursos Online</h1>
        
        <form method="post">
            <?php wp_nonce_field('cursos_config_save'); ?>
            
            <h2>Informações de Contato</h2>
            <table class="form-table">
                <tr>
                    <th><label for="cursos_whatsapp">WhatsApp</label></th>
                    <td>
                        <input type="text" id="cursos_whatsapp" name="cursos_whatsapp" value="<?php echo esc_attr(get_option('cursos_whatsapp')); ?>" class="regular-text" placeholder="5511999999999">
                        <p class="description">Número com código do país, sem espaços ou símbolos</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cursos_email">E-mail</label></th>
                    <td><input type="email" id="cursos_email" name="cursos_email" value="<?php echo esc_attr(get_option('cursos_email')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="cursos_telefone">Telefone</label></th>
                    <td><input type="text" id="cursos_telefone" name="cursos_telefone" value="<?php echo esc_attr(get_option('cursos_telefone')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="cursos_endereco">Endereço</label></th>
                    <td><textarea id="cursos_endereco" name="cursos_endereco" rows="3" class="large-text"><?php echo esc_textarea(get_option('cursos_endereco')); ?></textarea></td>
                </tr>
            </table>
            
            <h2>Redes Sociais</h2>
            <table class="form-table">
                <tr>
                    <th><label for="cursos_facebook">Facebook</label></th>
                    <td><input type="url" id="cursos_facebook" name="cursos_facebook" value="<?php echo esc_attr(get_option('cursos_facebook')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="cursos_instagram">Instagram</label></th>
                    <td><input type="url" id="cursos_instagram" name="cursos_instagram" value="<?php echo esc_attr(get_option('cursos_instagram')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="cursos_youtube">YouTube</label></th>
                    <td><input type="url" id="cursos_youtube" name="cursos_youtube" value="<?php echo esc_attr(get_option('cursos_youtube')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="cursos_linkedin">LinkedIn</label></th>
                    <td><input type="url" id="cursos_linkedin" name="cursos_linkedin" value="<?php echo esc_attr(get_option('cursos_linkedin')); ?>" class="regular-text"></td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="cursos_config_submit" class="button button-primary" value="Salvar Configurações">
            </p>
        </form>
    </div>
    <?php
}


function cursos_pagamentos_page() {
    if (isset($_POST['cursos_pagamentos_submit'])) {
        check_admin_referer('cursos_pagamentos_save');
        
        // Asaas
        update_option('cursos_asaas_api_key', sanitize_text_field($_POST['cursos_asaas_api_key']));
        update_option('cursos_asaas_ambiente', sanitize_text_field($_POST['cursos_asaas_ambiente']));
        update_option('cursos_asaas_ativo', isset($_POST['cursos_asaas_ativo']) ? '1' : '0');
        update_option('cursos_asaas_debug', isset($_POST['cursos_asaas_debug']) ? '1' : '0');
        
        // PagSeguro
        update_option('cursos_pagseguro_email', sanitize_email($_POST['cursos_pagseguro_email']));
        update_option('cursos_pagseguro_token', sanitize_text_field($_POST['cursos_pagseguro_token']));
        update_option('cursos_pagseguro_ambiente', sanitize_text_field($_POST['cursos_pagseguro_ambiente']));
        update_option('cursos_pagseguro_ativo', isset($_POST['cursos_pagseguro_ativo']) ? '1' : '0');
        
        // Descontos Automáticos
        update_option('cursos_desconto_pix_ativo', isset($_POST['cursos_desconto_pix_ativo']) ? '1' : '0');
        update_option('cursos_desconto_pix_percent', floatval($_POST['cursos_desconto_pix_percent']));
        update_option('cursos_desconto_estudante_ativo', isset($_POST['cursos_desconto_estudante_ativo']) ? '1' : '0');
        update_option('cursos_desconto_estudante_percent', floatval($_POST['cursos_desconto_estudante_percent']));
        
        echo '<div class="notice notice-success"><p>Configurações de pagamento salvas!</p></div>';
    }
    
    // Verificar detecção automática de ambiente para Asaas
    $asaas_api_key = get_option('cursos_asaas_api_key');
    $asaas_ambiente = get_option('cursos_asaas_ambiente', 'sandbox');
    $asaas_ambiente_detectado = Cursos_Payment_Asaas::detect_environment($asaas_api_key);
    $asaas_ambiente_mismatch = $asaas_ambiente_detectado && $asaas_ambiente_detectado !== $asaas_ambiente;
    ?>
    <style>
        .gateway-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
        }
        .gateway-status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .gateway-status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .gateway-status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .gateway-status.loading {
            background: #e9ecef;
            color: #495057;
            border: 1px solid #dee2e6;
        }
        .test-connection-btn {
            margin-left: 10px !important;
        }
        .ambiente-alert {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 4px;
            padding: 12px 16px;
            margin: 10px 0;
            color: #856404;
        }
        .ambiente-alert strong {
            display: block;
            margin-bottom: 5px;
        }
        .fix-suggestion {
            margin-top: 8px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
    
    <div class="wrap">
        <h1>Configurações de Pagamento</h1>
        
        <form method="post">
            <?php wp_nonce_field('cursos_pagamentos_save'); ?>
            
            <h2>Descontos Automáticos</h2>
            <p class="description" style="margin-bottom: 15px;">
                <strong>Regra:</strong> Estudantes recebem desconto de estudante e <strong>NÃO</strong> acumulam com desconto PIX. 
                Não-estudantes pagando com PIX recebem o desconto PIX.
            </p>
            <table class="form-table">
                <tr>
                    <th><label for="cursos_desconto_pix_ativo">Desconto PIX</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="cursos_desconto_pix_ativo" name="cursos_desconto_pix_ativo" value="1" <?php checked(get_option('cursos_desconto_pix_ativo', '1'), '1'); ?>>
                            Ativar desconto para pagamentos via PIX
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="cursos_desconto_pix_percent">Percentual PIX (%)</label></th>
                    <td>
                        <input type="number" id="cursos_desconto_pix_percent" name="cursos_desconto_pix_percent" 
                               value="<?php echo esc_attr(get_option('cursos_desconto_pix_percent', '10')); ?>" 
                               class="small-text" min="0" max="100" step="0.5">
                        <p class="description">Desconto aplicado quando o cliente paga via PIX</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cursos_desconto_estudante_ativo">Desconto Estudante</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="cursos_desconto_estudante_ativo" name="cursos_desconto_estudante_ativo" value="1" <?php checked(get_option('cursos_desconto_estudante_ativo', '1'), '1'); ?>>
                            Ativar desconto para estudantes
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="cursos_desconto_estudante_percent">Percentual Estudante (%)</label></th>
                    <td>
                        <input type="number" id="cursos_desconto_estudante_percent" name="cursos_desconto_estudante_percent" 
                               value="<?php echo esc_attr(get_option('cursos_desconto_estudante_percent', '10')); ?>" 
                               class="small-text" min="0" max="100" step="0.5">
                        <p class="description">Desconto aplicado para estudantes (não acumula com PIX)</p>
                    </td>
                </tr>
            </table>
            
            <hr style="margin: 30px 0;">
            
            <h2>Asaas</h2>
            <?php if ($asaas_ambiente_mismatch): ?>
            <div class="ambiente-alert">
                <strong>⚠️ Possível incompatibilidade de ambiente detectada!</strong>
                A chave de API parece ser de <strong><?php echo $asaas_ambiente_detectado === 'producao' ? 'Produção' : 'Sandbox'; ?></strong>, 
                mas o ambiente está configurado como <strong><?php echo $asaas_ambiente === 'producao' ? 'Produção' : 'Sandbox'; ?></strong>.
                <div class="fix-suggestion">
                    💡 <strong>Solução:</strong> Altere o ambiente para "<?php echo $asaas_ambiente_detectado === 'producao' ? 'Produção' : 'Sandbox (Testes)'; ?>" 
                    ou utilize uma chave de API correspondente ao ambiente selecionado.
                </div>
            </div>
            <?php endif; ?>
            <table class="form-table">
                <tr>
                    <th><label for="cursos_asaas_ativo">Ativar Asaas</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="cursos_asaas_ativo" name="cursos_asaas_ativo" value="1" <?php checked(get_option('cursos_asaas_ativo'), '1'); ?>>
                            Habilitar pagamentos via Asaas
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="cursos_asaas_api_key">API Key</label></th>
                    <td>
                        <input type="text" id="cursos_asaas_api_key" name="cursos_asaas_api_key" value="<?php echo esc_attr(get_option('cursos_asaas_api_key')); ?>" class="regular-text">
                        <button type="button" class="button test-connection-btn" id="test-asaas-connection">Testar Conexão</button>
                        <span id="asaas-connection-status"></span>
                        <p class="description">Sua chave de API do Asaas</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cursos_asaas_ambiente">Ambiente</label></th>
                    <td>
                        <select id="cursos_asaas_ambiente" name="cursos_asaas_ambiente">
                            <option value="sandbox" <?php selected(get_option('cursos_asaas_ambiente'), 'sandbox'); ?>>Sandbox (Testes)</option>
                            <option value="producao" <?php selected(get_option('cursos_asaas_ambiente'), 'producao'); ?>>Produção</option>
                        </select>
                        <p class="description" style="margin-top: 5px;">
                            <strong>Importante:</strong> Use chaves de Sandbox apenas com ambiente Sandbox, e chaves de Produção apenas com ambiente Produção.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cursos_asaas_debug">Modo Debug</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="cursos_asaas_debug" name="cursos_asaas_debug" value="1" <?php checked(get_option('cursos_asaas_debug'), '1'); ?>>
                            Ativar logs de debug (ver em <code>wp-content/debug.log</code>)
                        </label>
                        <p class="description">Ative para diagnosticar problemas de integração. Lembre de desativar após resolver.</p>
                    </td>
                </tr>
            </table>
            
            <h2>PagSeguro</h2>
            <table class="form-table">
                <tr>
                    <th><label for="cursos_pagseguro_ativo">Ativar PagSeguro</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="cursos_pagseguro_ativo" name="cursos_pagseguro_ativo" value="1" <?php checked(get_option('cursos_pagseguro_ativo'), '1'); ?>>
                            Habilitar pagamentos via PagSeguro
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="cursos_pagseguro_email">E-mail da Conta</label></th>
                    <td><input type="email" id="cursos_pagseguro_email" name="cursos_pagseguro_email" value="<?php echo esc_attr(get_option('cursos_pagseguro_email')); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="cursos_pagseguro_token">Token</label></th>
                    <td>
                        <input type="text" id="cursos_pagseguro_token" name="cursos_pagseguro_token" value="<?php echo esc_attr(get_option('cursos_pagseguro_token')); ?>" class="regular-text">
                        <button type="button" class="button test-connection-btn" id="test-pagseguro-connection">Testar Conexão</button>
                        <span id="pagseguro-connection-status"></span>
                        <p class="description">Token de integração do PagSeguro</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cursos_pagseguro_ambiente">Ambiente</label></th>
                    <td>
                        <select id="cursos_pagseguro_ambiente" name="cursos_pagseguro_ambiente">
                            <option value="sandbox" <?php selected(get_option('cursos_pagseguro_ambiente'), 'sandbox'); ?>>Sandbox (Testes)</option>
                            <option value="producao" <?php selected(get_option('cursos_pagseguro_ambiente'), 'producao'); ?>>Produção</option>
                        </select>
                        <p class="description" style="margin-top: 5px;">
                            <strong>Importante:</strong> Use tokens de Sandbox apenas com ambiente Sandbox, e tokens de Produção apenas com ambiente Produção.
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="cursos_pagamentos_submit" class="button button-primary" value="Salvar Configurações de Pagamento">
            </p>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Testar conexão Asaas
        $('#test-asaas-connection').on('click', function() {
            var $btn = $(this);
            var $status = $('#asaas-connection-status');
            
            $btn.prop('disabled', true);
            $status.html('<span class="gateway-status loading">🔄 Testando...</span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cursos_test_gateway_connection',
                    gateway: 'asaas',
                    nonce: '<?php echo wp_create_nonce('cursos_test_gateway'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<span class="gateway-status success">✅ ' + response.data.message + '</span>');
                    } else {
                        var html = '<span class="gateway-status error">❌ ' + response.data.message + '</span>';
                        if (response.data.fix_suggestion) {
                            html += '<div class="fix-suggestion" style="margin-top: 8px;">' + response.data.fix_suggestion + '</div>';
                        }
                        $status.html(html);
                    }
                },
                error: function() {
                    $status.html('<span class="gateway-status error">❌ Erro ao testar conexão</span>');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // Testar conexão PagSeguro
        $('#test-pagseguro-connection').on('click', function() {
            var $btn = $(this);
            var $status = $('#pagseguro-connection-status');
            
            $btn.prop('disabled', true);
            $status.html('<span class="gateway-status loading">🔄 Testando...</span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cursos_test_gateway_connection',
                    gateway: 'pagseguro',
                    nonce: '<?php echo wp_create_nonce('cursos_test_gateway'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<span class="gateway-status success">✅ ' + response.data.message + '</span>');
                    } else {
                        var html = '<span class="gateway-status error">❌ ' + response.data.message + '</span>';
                        if (response.data.fix_suggestion) {
                            html += '<div class="fix-suggestion" style="margin-top: 8px;">' + response.data.fix_suggestion + '</div>';
                        }
                        $status.html(html);
                    }
                },
                error: function() {
                    $status.html('<span class="gateway-status error">❌ Erro ao testar conexão</span>');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // Alerta ao mudar ambiente com chave preenchida
        $('#cursos_asaas_ambiente').on('change', function() {
            var apiKey = $('#cursos_asaas_api_key').val();
            if (apiKey) {
                $('#asaas-connection-status').html('<span class="gateway-status warning">⚠️ Ambiente alterado. Clique em "Testar Conexão" para verificar.</span>');
            }
        });
        
        $('#cursos_pagseguro_ambiente').on('change', function() {
            var token = $('#cursos_pagseguro_token').val();
            if (token) {
                $('#pagseguro-connection-status').html('<span class="gateway-status warning">⚠️ Ambiente alterado. Clique em "Testar Conexão" para verificar.</span>');
            }
        });
    });
    </script>
    <?php
}

/**
 * AJAX: Testar conexão com gateway de pagamento
 */
function cursos_ajax_test_gateway_connection() {
    check_ajax_referer('cursos_test_gateway', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permissão negada'));
        return;
    }
    
    $gateway = sanitize_text_field($_POST['gateway']);
    
    if ($gateway === 'asaas') {
        $asaas = new Cursos_Payment_Asaas();
        $result = $asaas->test_connection();
    } elseif ($gateway === 'pagseguro') {
        $pagseguro = new Cursos_Payment_PagSeguro();
        $result = $pagseguro->test_connection();
    } else {
        wp_send_json_error(array('message' => 'Gateway inválido'));
        return;
    }
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_cursos_test_gateway_connection', 'cursos_ajax_test_gateway_connection');

/**
 * ========================================
 * HELPER FUNCTIONS
 * ========================================
 */
function cursos_format_price($price) {
    return 'R$ ' . number_format($price, 2, ',', '.');
}

/**
 * Obter contagem de itens do carrinho (para o header)
 * Versão simplificada usando sessão
 */
function cursos_get_cart_count() {
    if (!session_id()) {
        @session_start();
    }
    
    if (!isset($_SESSION['cursos_cart']) || !is_array($_SESSION['cursos_cart'])) {
        return 0;
    }
    
    return count($_SESSION['cursos_cart']);
}

/**
 * Obter itens do carrinho com dados completos
 * Nova estrutura: carrinho é array associativo [curso_id => ['curso_id' => X, 'turma_id' => Y]]
 */
function cursos_get_cart_items() {
    if (!session_id()) {
        @session_start();
    }
    
    if (!isset($_SESSION['cursos_cart']) || !is_array($_SESSION['cursos_cart'])) {
        return array();
    }
    
    $items = array();
    foreach ($_SESSION['cursos_cart'] as $curso_id => $cart_item) {
        // Compatibilidade com estrutura antiga (array simples de IDs)
        if (is_numeric($cart_item)) {
            $curso_id = $cart_item;
            $turma_id = '';
        } else {
            $curso_id = $cart_item['curso_id'];
            $turma_id = $cart_item['turma_id'] ?? '';
        }
        
        $post = get_post($curso_id);
        if ($post && $post->post_type === 'curso') {
            $turma_info = null;
            if (!empty($turma_id)) {
                $turmas = get_post_meta($curso_id, '_curso_turmas', true);
                if (!empty($turmas) && is_array($turmas)) {
                    foreach ($turmas as $turma) {
                        if ($turma['id'] == $turma_id) {
                            $turma_info = $turma;
                            break;
                        }
                    }
                }
            }
            
            $items[] = array(
                'id' => $curso_id,
                'title' => $post->post_title,
                'permalink' => get_permalink($curso_id),
                'thumbnail' => get_the_post_thumbnail_url($curso_id, 'medium'),
                'meta' => cursos_get_curso_meta($curso_id),
                'turma_id' => $turma_id,
                'turma_info' => $turma_info
            );
        }
    }
    
    return $items;
}

/**
 * Obter subtotal do carrinho
 */
function cursos_get_cart_subtotal() {
    $items = cursos_get_cart_items();
    $subtotal = 0;
    
    foreach ($items as $item) {
        $preco = floatval($item['meta']['preco']);
        $subtotal += $preco;
    }
    
    return $subtotal;
}

/**
 * Limpar carrinho completamente
 */
function cursos_clear_cart() {
    if (!session_id()) {
        @session_start();
    }
    $_SESSION['cursos_cart'] = array();
}

/**
 * Helper: Verificar vagas disponíveis em uma turma
 * Retorna array com 'disponivel' (bool) e 'vagas_restantes' (int)
 */
/**
 * Verifica vagas globais do curso (limite máximo de alunos)
 * 
 * @param int $curso_id ID do curso
 * @return array Informações sobre vagas do curso
 */
function cursos_check_curso_vagas($curso_id) {
    global $wpdb;
    
    $limite_vagas = get_post_meta($curso_id, '_curso_limite_vagas', true);
    $limite_vagas = intval($limite_vagas);
    
    // Se não tem limite definido, sempre disponível
    if ($limite_vagas <= 0) {
        return array(
            'tem_limite' => false,
            'disponivel' => true,
            'vagas_restantes' => null,
            'vagas_totais' => null,
            'vendidos' => 0,
            'reservas' => 0
        );
    }
    
    // Contar todas as vendas deste curso (todas as turmas)
    $table_name = $wpdb->prefix . 'cursos_orders';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    $vendidos = 0;
    if ($table_exists) {
        $vendidos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE curso_id = %d 
             AND status IN ('completed', 'processing', 'paid', 'pending')",
            $curso_id
        ));
        $vendidos = intval($vendidos);
    }
    
    // Considerar reservas ativas
    $reservas = cursos_count_reservas_curso($curso_id);
    
    $vagas_restantes = $limite_vagas - $vendidos - $reservas;
    
    return array(
        'tem_limite' => true,
        'disponivel' => $vagas_restantes > 0,
        'vagas_restantes' => max(0, $vagas_restantes),
        'vagas_totais' => $limite_vagas,
        'vendidos' => $vendidos,
        'reservas' => $reservas
    );
}

/**
 * Conta reservas ativas para todo o curso (todas as turmas)
 * 
 * @param int $curso_id ID do curso
 * @param string|null $exclude_session Session ID a excluir da contagem
 * @return int Número de reservas ativas
 */
function cursos_count_reservas_curso($curso_id, $exclude_session = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'cursos_reservas';
    
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    if (!$table_exists) return 0;
    
    $query = "SELECT COUNT(*) FROM $table WHERE curso_id = %d AND expires_at > NOW()";
    $params = array($curso_id);
    
    if ($exclude_session) {
        $query .= " AND session_id != %s";
        $params[] = $exclude_session;
    }
    
    return intval($wpdb->get_var($wpdb->prepare($query, ...$params)));
}

/**
 * Regra única para exibir o badge "VAGAS ABERTAS" nos cards de curso.
 * Mostra sempre que houver qualquer vaga disponível (>0) ou vagas ilimitadas,
 * ou quando o curso estiver em uma categoria/taxonomia "Vagas Abertas".
 *
 * @param int $curso_id
 * @return bool
 */
function cursos_curso_tem_vagas_abertas($curso_id) {
    $curso_id = intval($curso_id);
    if (!$curso_id) return false;

    // Marcação manual tem prioridade: sem turma aberta => nunca mostra
    if (get_post_meta($curso_id, '_curso_sem_turma_aberta', true) === '1') {
        return false;
    }

    // Categoria/termo manual "Vagas Abertas" força a exibição
    $taxonomies = get_object_taxonomies(get_post_type($curso_id) ?: 'curso');
    if (!empty($taxonomies)) {
        $terms = wp_get_object_terms($curso_id, $taxonomies, array('fields' => 'all'));
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $slug = sanitize_title($term->slug);
                $name = sanitize_title($term->name);
                if ($slug === 'vagas-abertas' || $name === 'vagas-abertas') {
                    return true;
                }
            }
        }
    }

    // Limite global do curso
    if (function_exists('cursos_check_curso_vagas')) {
        $vagas_curso = cursos_check_curso_vagas($curso_id);
        if (!empty($vagas_curso['tem_limite'])) {
            return intval($vagas_curso['vagas_restantes']) > 0;
        }
    }

    // Limite por turma (menor vaga disponível)
    if (function_exists('cursos_get_curso_menor_vagas')) {
        $menor = cursos_get_curso_menor_vagas($curso_id);
        if ($menor !== null) {
            return intval($menor) > 0;
        }
    }

    // Sem limites configurados => vagas abertas
    return true;
}


/**
 * Retorna a menor quantidade de vagas disponíveis entre todas as turmas abertas de um curso
 * Útil para exibir badge "Últimas vagas!" no card do curso
 * 
 * @param int $curso_id ID do curso
 * @return int|null Número de vagas restantes (menor entre todas as turmas) ou null se ilimitado
 */
function cursos_get_curso_menor_vagas($curso_id) {
    $turmas = get_post_meta($curso_id, '_curso_turmas', true);
    if (empty($turmas) || !is_array($turmas)) {
        return null;
    }
    
    $menor_vagas = null;
    foreach ($turmas as $turma) {
        // Pular turmas sem ID
        if (empty($turma['id'])) continue;
        
        $check = cursos_check_turma_vagas($curso_id, $turma['id']);
        
        // Se a turma está disponível e tem limite de vagas
        if ($check['disponivel'] && $check['vagas_restantes'] !== null) {
            if ($menor_vagas === null || $check['vagas_restantes'] < $menor_vagas) {
                $menor_vagas = $check['vagas_restantes'];
            }
        }
    }
    
    return $menor_vagas;
}

/**
 * Retorna as próximas turmas com vagas abertas de um curso
 * @param int $curso_id
 * @return array Array com 'proxima' (próxima turma) e 'total' (total de turmas futuras)
 */
function cursos_get_proximas_turmas($curso_id) {
    $turmas = get_post_meta($curso_id, '_curso_turmas', true);
    $sem_turma = get_post_meta($curso_id, '_curso_sem_turma_aberta', true);
    
    if (empty($turmas) || !is_array($turmas) || $sem_turma === '1') {
        return array('proxima' => null, 'total' => 0);
    }
    
    $hoje = date('Y-m-d');
    $futuras = array();
    
    foreach ($turmas as $turma) {
        if (empty($turma['id']) || empty($turma['data_inicio'])) continue;
        
        if ($turma['data_inicio'] >= $hoje) {
            // Verificar vagas apenas se a função existir
            if (function_exists('cursos_check_turma_vagas')) {
                $check = cursos_check_turma_vagas($curso_id, $turma['id']);
                if ($check['disponivel'] && ($check['vagas_restantes'] === null || $check['vagas_restantes'] > 0)) {
                    $futuras[] = $turma;
                }
            } else {
                // Sem verificação de vagas, incluir todas as futuras
                $futuras[] = $turma;
            }
        }
    }
    
    usort($futuras, function($a, $b) {
        return strcmp($a['data_inicio'], $b['data_inicio']);
    });
    
    return array(
        'proxima' => !empty($futuras) ? $futuras[0] : null,
        'total' => count($futuras),
        'todas' => $futuras,
    );
}

/**
 * Formata a data de uma turma para exibição.
 * Multi-dia (mesmo mês): "de 15 a 20 de agosto de 2026"
 * Multi-dia (meses diferentes): "de 15 de agosto a 20 de setembro de 2026"
 * Dia único: "15 de agosto de 2026"
 */
function cursos_format_turma_date($turma) {
    if (empty($turma['data_inicio'])) return '';

    $meses = array(
        '01' => 'janeiro', '02' => 'fevereiro', '03' => 'março',
        '04' => 'abril', '05' => 'maio', '06' => 'junho',
        '07' => 'julho', '08' => 'agosto', '09' => 'setembro',
        '10' => 'outubro', '11' => 'novembro', '12' => 'dezembro',
    );

    $inicio = $turma['data_inicio'];
    $dia_inicio = ltrim(date('d', strtotime($inicio)), '0');
    $mes_inicio = $meses[date('m', strtotime($inicio))];
    $ano_inicio = date('Y', strtotime($inicio));

    if (!empty($turma['data_fim']) && $turma['data_fim'] !== $inicio) {
        $fim = $turma['data_fim'];
        $dia_fim = ltrim(date('d', strtotime($fim)), '0');
        $mes_fim = $meses[date('m', strtotime($fim))];
        $ano_fim = date('Y', strtotime($fim));

        if ($mes_inicio === $mes_fim && $ano_inicio === $ano_fim) {
            return 'de ' . $dia_inicio . ' a ' . $dia_fim . ' de ' . $mes_inicio . ' de ' . $ano_inicio;
        } else {
            $txt_inicio = $dia_inicio . ' de ' . $mes_inicio . ($ano_inicio !== $ano_fim ? ' de ' . $ano_inicio : '');
            return 'de ' . $txt_inicio . ' a ' . $dia_fim . ' de ' . $mes_fim . ' de ' . $ano_fim;
        }
    }

    return $dia_inicio . ' de ' . $mes_inicio . ' de ' . $ano_inicio;
}

function cursos_check_turma_vagas($curso_id, $turma_id) {
    global $wpdb;
    
    $turmas = get_post_meta($curso_id, '_curso_turmas', true);
    if (empty($turmas) || !is_array($turmas)) {
        return array('disponivel' => true, 'vagas_restantes' => null, 'turma_info' => null);
    }
    
    $turma_info = null;
    foreach ($turmas as $turma) {
        if ($turma['id'] == $turma_id) {
            $turma_info = $turma;
            break;
        }
    }
    
    if (!$turma_info) {
        return array('disponivel' => false, 'vagas_restantes' => 0, 'turma_info' => null, 'error' => 'Turma não encontrada');
    }
    
    // Se não tem limite de vagas definido, sempre disponível
    $vagas_totais = isset($turma_info['vagas']) ? intval($turma_info['vagas']) : 0;
    if ($vagas_totais <= 0) {
        return array('disponivel' => true, 'vagas_restantes' => null, 'turma_info' => $turma_info);
    }
    
    // Contar pedidos confirmados para esta turma
    $table_name = $wpdb->prefix . 'cursos_orders';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    $vendidos = 0;
    if ($table_exists) {
        $vendidos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE curso_id = %d 
             AND turma_id = %s 
             AND status IN ('completed', 'processing', 'paid', 'pending')",
            $curso_id,
            $turma_id
        ));
        $vendidos = intval($vendidos);
    }
    
    $vagas_restantes = $vagas_totais - $vendidos;
    
    return array(
        'disponivel' => $vagas_restantes > 0,
        'vagas_restantes' => max(0, $vagas_restantes),
        'vagas_totais' => $vagas_totais,
        'vendidos' => $vendidos,
        'turma_info' => $turma_info
    );
}

/**
 * AJAX: Adicionar ao carrinho
 */
function cursos_ajax_add_to_cart() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cursos_ajax_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
        return;
    }
    
    $curso_id = intval($_POST['curso_id']);
    $turma_id = isset($_POST['turma_id']) ? sanitize_text_field($_POST['turma_id']) : '';
    
    if (!$curso_id || get_post_type($curso_id) !== 'curso') {
        wp_send_json_error(array('message' => 'Curso não encontrado'));
        return;
    }
    
    // Verificar se curso tem turmas e se turma foi selecionada
    $turmas = get_post_meta($curso_id, '_curso_turmas', true);
    $sem_turma = get_post_meta($curso_id, '_curso_sem_turma_aberta', true);
    $tem_turmas = !empty($turmas) && is_array($turmas) && !$sem_turma;
    
    if ($tem_turmas && empty($turma_id)) {
        wp_send_json_error(array('message' => 'Por favor, selecione uma turma antes de continuar'));
        return;
    }
    
    // 1. Verificar limite global do curso primeiro
    $vagas_curso = cursos_check_curso_vagas($curso_id);
    if ($vagas_curso['tem_limite'] && !$vagas_curso['disponivel']) {
        wp_send_json_error(array(
            'message' => 'Este curso atingiu o limite máximo de alunos. Entre na lista de espera para ser avisado sobre novas turmas.',
            'esgotada' => true,
            'vagas_restantes' => 0
        ));
        return;
    }
    
    // 2. Verificar vagas disponíveis na turma selecionada (considerando reservas)
    if ($tem_turmas && !empty($turma_id)) {
        $vagas_check = cursos_check_turma_vagas_com_reservas($curso_id, $turma_id);
        
        if (isset($vagas_check['error'])) {
            wp_send_json_error(array('message' => $vagas_check['error']));
            return;
        }
        
        if (!$vagas_check['disponivel']) {
            wp_send_json_error(array(
                'message' => 'Desculpe, esta turma está esgotada. Por favor, selecione outra turma.',
                'esgotada' => true
            ));
            return;
        }
    }
    
    if (!session_id()) {
        @session_start();
    }
    
    if (!isset($_SESSION['cursos_cart'])) {
        $_SESSION['cursos_cart'] = array();
    }
    
    // Verificar se curso já está no carrinho
    if (isset($_SESSION['cursos_cart'][$curso_id])) {
        $turma_atual = $_SESSION['cursos_cart'][$curso_id]['turma_id'] ?? '';
        
        // Se a turma for diferente, verificar vagas (com reservas) e atualizar
        if ($turma_id !== $turma_atual && !empty($turma_id)) {
            // Verificar vagas da nova turma considerando reservas
            $vagas_check = cursos_check_turma_vagas_com_reservas($curso_id, $turma_id);
            if (!$vagas_check['disponivel']) {
                wp_send_json_error(array(
                    'message' => 'Desculpe, esta turma está esgotada. Por favor, selecione outra turma.',
                    'esgotada' => true
                ));
                return;
            }
            
            $_SESSION['cursos_cart'][$curso_id]['turma_id'] = $turma_id;
            
            // Buscar nome da nova turma
            $turma_nome = $vagas_check['turma_info']['nome'] ?? '';
            
            wp_send_json_success(array(
                'message' => 'Turma atualizada para: ' . $turma_nome,
                'cart_count' => count($_SESSION['cursos_cart']),
                'updated' => true
            ));
            return;
        }
        
        wp_send_json_error(array('message' => 'Este curso já está no seu carrinho'));
        return;
    }
    
    // Adicionar ao carrinho com nova estrutura
    $_SESSION['cursos_cart'][$curso_id] = array(
        'curso_id' => $curso_id,
        'turma_id' => $turma_id
    );
    
    wp_send_json_success(array(
        'message' => 'Curso adicionado ao carrinho!',
        'cart_count' => count($_SESSION['cursos_cart']),
        'subtotal' => cursos_get_cart_subtotal(),
        'subtotal_formatted' => cursos_format_price(cursos_get_cart_subtotal())
    ));
}
add_action('wp_ajax_add_to_cart', 'cursos_ajax_add_to_cart');
add_action('wp_ajax_nopriv_add_to_cart', 'cursos_ajax_add_to_cart');

/**
 * AJAX: Remover do carrinho
 */
function cursos_ajax_remove_from_cart() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cursos_ajax_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
        return;
    }
    
    $curso_id = intval($_POST['curso_id']);
    
    if (!session_id()) {
        @session_start();
    }
    
    if (isset($_SESSION['cursos_cart'][$curso_id])) {
        unset($_SESSION['cursos_cart'][$curso_id]);
    }
    
    wp_send_json_success(array(
        'message' => 'Curso removido do carrinho',
        'cart_count' => cursos_get_cart_count(),
        'subtotal' => cursos_get_cart_subtotal(),
        'subtotal_formatted' => cursos_format_price(cursos_get_cart_subtotal())
    ));
}
add_action('wp_ajax_remove_from_cart', 'cursos_ajax_remove_from_cart');
add_action('wp_ajax_nopriv_remove_from_cart', 'cursos_ajax_remove_from_cart');

/**
 * AJAX: Obter contagem real do carrinho da sessão (fonte de verdade)
 */
function cursos_ajax_get_cart_count() {
    if (!session_id()) {
        @session_start();
    }
    wp_send_json_success(array(
        'cart_count' => cursos_get_cart_count(),
        'subtotal' => cursos_get_cart_subtotal(),
        'subtotal_formatted' => function_exists('cursos_format_price') ? cursos_format_price(cursos_get_cart_subtotal()) : '',
    ));
}
add_action('wp_ajax_get_cart_count', 'cursos_ajax_get_cart_count');
add_action('wp_ajax_nopriv_get_cart_count', 'cursos_ajax_get_cart_count');

/**
 * AJAX: Atualizar turma de um curso no carrinho
 */
function cursos_ajax_update_cart_turma() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cursos_ajax_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
        return;
    }
    
    $curso_id = intval($_POST['curso_id']);
    $turma_id = sanitize_text_field($_POST['turma_id']);
    
    if (!session_id()) {
        @session_start();
    }
    
    if (!isset($_SESSION['cursos_cart'][$curso_id])) {
        wp_send_json_error(array('message' => 'Curso não encontrado no carrinho'));
        return;
    }
    
    // Verificar vagas disponíveis na nova turma (considerando reservas)
    $vagas_check = cursos_check_turma_vagas_com_reservas($curso_id, $turma_id);
    
    if (isset($vagas_check['error'])) {
        wp_send_json_error(array('message' => $vagas_check['error']));
        return;
    }
    
    if (!$vagas_check['disponivel']) {
        wp_send_json_error(array(
            'message' => 'Desculpe, esta turma está esgotada (' . $vagas_check['vagas_restantes'] . ' vagas restantes). Por favor, selecione outra turma.',
            'esgotada' => true
        ));
        return;
    }
    
    $turma_info = $vagas_check['turma_info'];
    
    $_SESSION['cursos_cart'][$curso_id]['turma_id'] = $turma_id;
    
    wp_send_json_success(array(
        'message' => 'Turma atualizada com sucesso!',
        'turma_nome' => $turma_info['nome'],
        'turma_data' => cursos_format_turma_date($turma_info),
        'vagas_restantes' => $vagas_check['vagas_restantes']
    ));
}
add_action('wp_ajax_update_cart_turma', 'cursos_ajax_update_cart_turma');
add_action('wp_ajax_nopriv_update_cart_turma', 'cursos_ajax_update_cart_turma');

/**
 * AJAX: Verificar status do pagamento PIX
 */
function cursos_ajax_check_pix_status() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'check_pix_status')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
        return;
    }
    
    $order_id = intval($_POST['order_id'] ?? 0);
    $payment_id = sanitize_text_field($_POST['payment_id'] ?? '');
    
    if (!$order_id) {
        wp_send_json_error(array('message' => 'ID do pedido inválido'));
        return;
    }
    
    // Verificar status no banco de dados primeiro
    global $wpdb;
    $table_name = $wpdb->prefix . 'cursos_orders';
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT status FROM $table_name WHERE id = %d",
        $order_id
    ));
    
    if ($order && $order->status === 'completed') {
        wp_send_json_success(array(
            'status' => 'completed',
            'message' => 'Pagamento confirmado!'
        ));
        return;
    }
    
    // Se tiver payment_id, consultar Asaas
    if ($payment_id && cursos_asaas()->is_active()) {
        $asaas = cursos_asaas();
        $status_result = $asaas->get_payment_status($payment_id);
        
        if ($status_result['success']) {
            $status = $status_result['status'];
            
            // Se confirmado, atualizar banco
            if (in_array($status, array('CONFIRMED', 'RECEIVED'))) {
                $wpdb->update(
                    $table_name,
                    array('status' => 'completed'),
                    array('id' => $order_id),
                    array('%s'),
                    array('%d')
                );
                
                // Disparar action de pagamento completado
                $order_full = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE id = %d",
                    $order_id
                ), ARRAY_A);
                
                if ($order_full) {
                    $curso_nome = get_the_title($order_full['curso_id']);
                    do_action('cursos_payment_completed', $order_id, array(
                        'nome' => $order_full['customer_name'],
                        'email' => $order_full['customer_email'],
                        'total' => $order_full['amount'],
                        'payment_method' => $order_full['payment_method'],
                        'curso_id' => $order_full['curso_id'],
                        'curso_nome' => $curso_nome,
                    ));
                }
            }
            
            wp_send_json_success(array(
                'status' => $status,
                'message' => $status_result['status']
            ));
            return;
        }
    }
    
    wp_send_json_success(array(
        'status' => 'PENDING',
        'message' => 'Aguardando pagamento'
    ));
}
add_action('wp_ajax_check_pix_status', 'cursos_ajax_check_pix_status');
add_action('wp_ajax_nopriv_check_pix_status', 'cursos_ajax_check_pix_status');

// Nota: A localização do cursosAjax já é feita em cursos_enqueue_assets()
// com o nonce 'cursos_nonce' - não duplicar aqui para evitar conflitos

// Função para obter meta dados do curso
function cursos_get_curso_meta($post_id) {
    // Buscar dados do professor via ID do post de professor (prioridade)
    $professor_id = get_post_meta($post_id, '_curso_professor', true);
    $instrutor_nome = '';
    $instrutor_titulo = '';
    $instrutor_foto = '';
    $instrutor_bio = '';
    $instrutor_especialidades = array();
    
    if (!empty($professor_id)) {
        // Buscar dados do CPT professor
        $professor_data = cursos_get_professor_full_data($professor_id);
        if ($professor_data) {
            $instrutor_nome = $professor_data['nome'];
            $instrutor_titulo = $professor_data['titulo'];
            $instrutor_foto = $professor_data['foto'];
            $instrutor_bio = $professor_data['bio'];
            $instrutor_especialidades = $professor_data['especialidades'];
        }
    }
    
    // Fallback para campos manuais (caso não tenha professor selecionado)
    if (empty($instrutor_nome)) {
        $instrutor_nome = get_post_meta($post_id, '_curso_instrutor_nome', true);
    }
    if (empty($instrutor_titulo)) {
        $instrutor_titulo = get_post_meta($post_id, '_curso_instrutor_titulo', true);
    }
    if (empty($instrutor_foto)) {
        $instrutor_foto = get_post_meta($post_id, '_curso_instrutor_foto', true);
    }
    if (empty($instrutor_bio)) {
        $instrutor_bio = get_post_meta($post_id, '_curso_instrutor_bio', true);
    }
    if (empty($instrutor_especialidades)) {
        $instrutor_especialidades = get_post_meta($post_id, '_curso_instrutor_especialidades', true);
    }
    
    return array(
        'preco' => get_post_meta($post_id, '_curso_preco', true),
        'preco_original' => get_post_meta($post_id, '_curso_preco_original', true),
        'duracao' => get_post_meta($post_id, '_curso_duracao', true),
        'aulas' => get_post_meta($post_id, '_curso_aulas', true),
        'alunos' => get_post_meta($post_id, '_curso_alunos', true),
        'avaliacao' => get_post_meta($post_id, '_curso_avaliacao', true),
        'destaque' => get_post_meta($post_id, '_curso_destaque', true),
        'instrutor_nome' => $instrutor_nome,
        'instrutor_titulo' => $instrutor_titulo,
        'instrutor_foto' => $instrutor_foto,
        'instrutor_bio' => $instrutor_bio,
        'instrutor_especialidades' => $instrutor_especialidades,
        'curriculum' => get_post_meta($post_id, '_curso_curriculum', true),
    );
}

// Criar tabela de pedidos na ativação do tema
function cursos_create_orders_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cursos_orders';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) DEFAULT NULL,
        curso_id bigint(20) NOT NULL,
        turma_id varchar(100) DEFAULT NULL,
        customer_name varchar(255) NOT NULL,
        customer_email varchar(255) NOT NULL,
        customer_cpf varchar(14) NOT NULL,
        customer_phone varchar(20) DEFAULT NULL,
        customer_gender varchar(20) DEFAULT NULL,
        subtotal decimal(10,2) NOT NULL,
        discount decimal(10,2) DEFAULT 0,
        coupon_code varchar(50) DEFAULT NULL,
        coupon_id bigint(20) DEFAULT NULL,
        pix_discount decimal(10,2) DEFAULT 0,
        student_discount decimal(10,2) DEFAULT 0,
        is_student tinyint(1) DEFAULT 0,
        amount decimal(10,2) NOT NULL,
        payment_method varchar(50) NOT NULL,
        payment_gateway varchar(50) NOT NULL,
        transaction_id varchar(255) DEFAULT NULL,
        status varchar(50) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY curso_id (curso_id),
        KEY turma_id (turma_id),
        KEY status (status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'cursos_create_orders_table');

/**
 * Migração: Adicionar colunas faltantes na tabela de pedidos existente
 */
function cursos_migrate_orders_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cursos_orders';
    
    // Verificar se a tabela existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    if (!$table_exists) {
        cursos_create_orders_table();
        return;
    }
    
    // Verificar se coluna turma_id existe
    $turma_col = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'turma_id'");
    if (empty($turma_col)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN turma_id varchar(100) DEFAULT NULL AFTER curso_id");
        $wpdb->query("ALTER TABLE $table_name ADD KEY turma_id (turma_id)");
    }
    
    // Verificar se coluna customer_gender existe
    $gender_col = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'customer_gender'");
    if (empty($gender_col)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN customer_gender varchar(20) DEFAULT NULL AFTER customer_phone");
    }
}
add_action('admin_init', 'cursos_migrate_orders_table');

/**
 * ========================================
 * SISTEMA DE RESERVA TEMPORÁRIA DE VAGAS
 * ========================================
 */

/**
 * Criar tabela de reservas temporárias
 */
function cursos_create_reservas_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cursos_reservas';
    
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    if ($table_exists) {
        return;
    }
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id varchar(255) NOT NULL,
        curso_id bigint(20) NOT NULL,
        turma_id varchar(100) NOT NULL,
        quantidade int(11) DEFAULT 1,
        expires_at datetime NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_curso_turma (session_id, curso_id, turma_id),
        KEY expires_at (expires_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'cursos_create_reservas_table');

/**
 * Criar ou renovar reserva de vaga
 * 
 * @param int $curso_id ID do curso
 * @param string $turma_id ID da turma
 * @param int $minutos Tempo de reserva em minutos (padrão: 15)
 * @return int|WP_Error ID da reserva ou erro
 */
function cursos_create_reserva($curso_id, $turma_id, $minutos = 15) {
    global $wpdb;
    $table = $wpdb->prefix . 'cursos_reservas';
    
    if (!session_id()) {
        @session_start();
    }
    $session_id = session_id() ?: md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    
    // Limpar reservas expiradas primeiro
    cursos_cleanup_reservas();
    
    // Verificar se já tem reserva ativa
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE session_id = %s AND curso_id = %d AND turma_id = %s AND expires_at > NOW()",
        $session_id, $curso_id, $turma_id
    ));
    
    if ($existing) {
        // Renovar reserva existente
        $wpdb->update($table, 
            array('expires_at' => date('Y-m-d H:i:s', strtotime("+{$minutos} minutes"))),
            array('id' => $existing->id)
        );
        return $existing->id;
    }
    
    // Verificar se há vagas considerando reservas ativas
    $vagas_check = cursos_check_turma_vagas_com_reservas($curso_id, $turma_id);
    if (!$vagas_check['disponivel']) {
        return new WP_Error('sem_vagas', 'Não há vagas disponíveis para esta turma.');
    }
    
    // Criar nova reserva
    $wpdb->insert($table, array(
        'session_id' => $session_id,
        'curso_id' => $curso_id,
        'turma_id' => $turma_id,
        'expires_at' => date('Y-m-d H:i:s', strtotime("+{$minutos} minutes"))
    ));
    
    return $wpdb->insert_id;
}

/**
 * Contar reservas ativas para uma turma (excluindo sessão atual opcionalmente)
 */
function cursos_count_reservas_ativas($curso_id, $turma_id, $exclude_session = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'cursos_reservas';
    
    // Verificar se tabela existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    if (!$table_exists) {
        return 0;
    }
    
    $query = "SELECT COUNT(*) FROM $table WHERE curso_id = %d AND turma_id = %s AND expires_at > NOW()";
    $params = array($curso_id, $turma_id);
    
    if ($exclude_session) {
        $query .= " AND session_id != %s";
        $params[] = $exclude_session;
    }
    
    return intval($wpdb->get_var($wpdb->prepare($query, ...$params)));
}

/**
 * Verificar vagas considerando reservas ativas de outros usuários
 */
function cursos_check_turma_vagas_com_reservas($curso_id, $turma_id) {
    $vagas_check = cursos_check_turma_vagas($curso_id, $turma_id);
    
    // Se não tem limite de vagas, retornar original
    if ($vagas_check['vagas_restantes'] === null) {
        return $vagas_check;
    }
    
    if (!session_id()) {
        @session_start();
    }
    $session_id = session_id() ?: md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    
    // Contar reservas de OUTROS usuários (excluindo a sessão atual)
    $reservas_outros = cursos_count_reservas_ativas($curso_id, $turma_id, $session_id);
    
    $vagas_reais = $vagas_check['vagas_restantes'] - $reservas_outros;
    
    return array_merge($vagas_check, array(
        'vagas_restantes' => max(0, $vagas_reais),
        'disponivel' => $vagas_reais > 0,
        'reservas_ativas' => $reservas_outros
    ));
}

/**
 * Limpar reservas expiradas
 */
function cursos_cleanup_reservas() {
    global $wpdb;
    $table = $wpdb->prefix . 'cursos_reservas';
    
    // Verificar se tabela existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    if (!$table_exists) {
        return;
    }
    
    $wpdb->query("DELETE FROM $table WHERE expires_at < NOW()");
}

/**
 * Liberar reserva específica (quando compra é finalizada)
 */
function cursos_release_reserva($curso_id, $turma_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'cursos_reservas';
    
    // Verificar se tabela existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    if (!$table_exists) {
        return;
    }
    
    if (!session_id()) {
        @session_start();
    }
    $session_id = session_id() ?: md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    
    $wpdb->delete($table, array(
        'session_id' => $session_id,
        'curso_id' => $curso_id,
        'turma_id' => $turma_id
    ));
}

/**
 * Liberar todas as reservas de uma sessão
 */
function cursos_release_all_reservas() {
    global $wpdb;
    $table = $wpdb->prefix . 'cursos_reservas';
    
    // Verificar se tabela existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    if (!$table_exists) {
        return;
    }
    
    if (!session_id()) {
        @session_start();
    }
    $session_id = session_id() ?: md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    
    $wpdb->delete($table, array('session_id' => $session_id));
}

/**
 * Obter tempo restante da reserva mais próxima de expirar
 */
function cursos_get_reserva_expiry_time() {
    global $wpdb;
    $table = $wpdb->prefix . 'cursos_reservas';
    
    // Verificar se tabela existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    if (!$table_exists) {
        return null;
    }
    
    if (!session_id()) {
        @session_start();
    }
    $session_id = session_id() ?: md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    
    $earliest_expiry = $wpdb->get_var($wpdb->prepare(
        "SELECT MIN(expires_at) FROM $table WHERE session_id = %s AND expires_at > NOW()",
        $session_id
    ));
    
    if (!$earliest_expiry) {
        return null;
    }
    
    $expiry_time = strtotime($earliest_expiry);
    $now = current_time('timestamp');
    $seconds_remaining = $expiry_time - $now;
    
    return max(0, $seconds_remaining);
}

/**
 * AJAX: Renovar reserva
 */
add_action('wp_ajax_renovar_reserva', 'cursos_ajax_renovar_reserva');
add_action('wp_ajax_nopriv_renovar_reserva', 'cursos_ajax_renovar_reserva');

function cursos_ajax_renovar_reserva() {
    $curso_id = intval($_POST['curso_id'] ?? 0);
    $turma_id = sanitize_text_field($_POST['turma_id'] ?? '');
    
    if (!$curso_id || empty($turma_id)) {
        wp_send_json_error(array('message' => 'Dados inválidos'));
        return;
    }
    
    $result = cursos_create_reserva($curso_id, $turma_id, 15);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
        return;
    }
    
    wp_send_json_success(array(
        'message' => 'Reserva renovada',
        'expires_in' => 15 * 60
    ));
}

/**
 * ========================================
 * SISTEMA DE COMPROVANTES DE ESTUDANTE
 * ========================================
 */

/**
 * Criar tabela de comprovantes de estudante
 */
function cursos_create_student_documents_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cursos_student_documents';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        order_id bigint(20) unsigned DEFAULT NULL,
        customer_email varchar(255) NOT NULL,
        customer_name varchar(255) NOT NULL,
        customer_cpf varchar(20) DEFAULT NULL,
        file_name varchar(255) NOT NULL,
        file_path varchar(500) NOT NULL,
        file_url varchar(500) NOT NULL,
        file_size int(11) DEFAULT 0,
        status varchar(20) DEFAULT 'pending',
        admin_notes text DEFAULT NULL,
        reviewed_by bigint(20) unsigned DEFAULT NULL,
        reviewed_at datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        curso_id bigint(20) unsigned DEFAULT NULL,
        amount decimal(10,2) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY order_id (order_id),
        KEY customer_email (customer_email),
        KEY status (status),
        KEY curso_id (curso_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Adicionar colunas se não existirem (para instalações existentes)
    $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM $table_name");
    if (!in_array('curso_id', $existing_columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN curso_id bigint(20) unsigned DEFAULT NULL");
        $wpdb->query("ALTER TABLE $table_name ADD INDEX curso_id (curso_id)");
    }
    if (!in_array('amount', $existing_columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN amount decimal(10,2) DEFAULT NULL");
    }
}
add_action('after_switch_theme', 'cursos_create_student_documents_table');

/**
 * Criar tabela de histórico de alterações de pedidos
 */
function cursos_create_order_history_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cursos_order_history';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        document_id bigint(20) unsigned NOT NULL,
        old_status varchar(20) DEFAULT NULL,
        new_status varchar(20) NOT NULL,
        notes text DEFAULT NULL,
        changed_by bigint(20) unsigned DEFAULT NULL,
        changed_by_name varchar(255) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY document_id (document_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'cursos_create_order_history_table');

// Criar tabelas ao iniciar (para desenvolvimento)
add_action('init', function() {
    global $wpdb;
    
    // Tabela de documentos
    $table_name = $wpdb->prefix . 'cursos_student_documents';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        cursos_create_student_documents_table();
    }
    
    // Tabela de histórico
    $history_table = $wpdb->prefix . 'cursos_order_history';
    if ($wpdb->get_var("SHOW TABLES LIKE '$history_table'") != $history_table) {
        cursos_create_order_history_table();
    }
});

/**
 * REST API para upload de comprovantes
 */
function cursos_register_student_document_routes() {
    register_rest_route('cursos/v1', '/student-document', array(
        'methods' => 'POST',
        'callback' => 'cursos_handle_student_document_upload',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'cursos_register_student_document_routes');

/**
 * Handler para upload de comprovante via REST API
 */
function cursos_handle_student_document_upload($request) {
    // Verificar se há arquivo
    $files = $request->get_file_params();
    
    if (empty($files['document'])) {
        return new WP_Error('no_file', 'Nenhum arquivo enviado', array('status' => 400));
    }
    
    $file = $files['document'];
    
    // Validar tipo de arquivo
    $allowed_types = array('application/pdf');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return new WP_Error('invalid_type', 'Apenas arquivos PDF são permitidos', array('status' => 400));
    }
    
    // Validar tamanho (5MB)
    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        return new WP_Error('file_too_large', 'O arquivo deve ter no máximo 5MB', array('status' => 400));
    }
    
    // Validar extensão
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        return new WP_Error('invalid_extension', 'Apenas arquivos PDF são permitidos', array('status' => 400));
    }
    
    // Obter dados do formulário
    $params = $request->get_params();
    $customer_email = sanitize_email($params['email'] ?? '');
    $customer_name = sanitize_text_field($params['name'] ?? '');
    $customer_cpf = sanitize_text_field($params['cpf'] ?? '');
    
    if (empty($customer_email) || empty($customer_name)) {
        return new WP_Error('missing_data', 'E-mail e nome são obrigatórios', array('status' => 400));
    }
    
    // Criar diretório para upload
    $upload_dir = wp_upload_dir();
    $student_docs_dir = $upload_dir['basedir'] . '/comprovantes-estudante/' . date('Y/m');
    
    if (!file_exists($student_docs_dir)) {
        wp_mkdir_p($student_docs_dir);
        
        // Criar .htaccess para proteger arquivos
        $htaccess_content = "Order Deny,Allow\nDeny from all\n";
        file_put_contents($upload_dir['basedir'] . '/comprovantes-estudante/.htaccess', $htaccess_content);
    }
    
    // Gerar nome único para o arquivo
    $unique_name = sprintf(
        '%s_%s_%s.pdf',
        date('YmdHis'),
        sanitize_file_name(substr(preg_replace('/[^a-z0-9]/', '', strtolower($customer_name)), 0, 20)),
        wp_generate_password(8, false)
    );
    
    $file_path = $student_docs_dir . '/' . $unique_name;
    $relative_path = 'comprovantes-estudante/' . date('Y/m') . '/' . $unique_name;
    
    // Mover arquivo
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        return new WP_Error('upload_failed', 'Falha ao salvar o arquivo', array('status' => 500));
    }
    
    // Salvar no banco de dados
    global $wpdb;
    $table_name = $wpdb->prefix . 'cursos_student_documents';
    
    $inserted = $wpdb->insert($table_name, array(
        'customer_email' => $customer_email,
        'customer_name' => $customer_name,
        'customer_cpf' => $customer_cpf,
        'file_name' => sanitize_file_name($file['name']),
        'file_path' => $relative_path,
        'file_url' => $upload_dir['baseurl'] . '/' . $relative_path,
        'file_size' => $file['size'],
        'status' => 'pending',
    ));
    
    if (!$inserted) {
        // Remover arquivo se falhou no banco
        @unlink($file_path);
        return new WP_Error('db_error', 'Falha ao registrar comprovante', array('status' => 500));
    }
    
    $document_id = $wpdb->insert_id;
    
    // Notificar administradores
    do_action('cursos_student_document_uploaded', $document_id, array(
        'name' => $customer_name,
        'email' => $customer_email,
        'file_name' => $file['name'],
    ));
    
    return array(
        'success' => true,
        'document_id' => $document_id,
        'message' => 'Comprovante enviado com sucesso! Será analisado em até 24 horas.',
    );
}

/**
 * AJAX para upload de comprovante (WordPress tradicional)
 */
function cursos_ajax_upload_student_document() {
    // Verificar nonce
    if (!check_ajax_referer('student_document_upload', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Sessão expirada. Recarregue a página.'));
    }
    
    if (empty($_FILES['document'])) {
        wp_send_json_error(array('message' => 'Nenhum arquivo enviado'));
    }
    
    $file = $_FILES['document'];
    
    // Validar tipo
    $allowed_types = array('application/pdf');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        wp_send_json_error(array('message' => 'Apenas arquivos PDF são permitidos'));
    }
    
    // Validar tamanho
    if ($file['size'] > 5 * 1024 * 1024) {
        wp_send_json_error(array('message' => 'O arquivo deve ter no máximo 5MB'));
    }
    
    $customer_email = sanitize_email($_POST['email'] ?? '');
    $customer_name = sanitize_text_field($_POST['name'] ?? '');
    $customer_cpf = sanitize_text_field($_POST['cpf'] ?? '');
    $document_type = sanitize_text_field($_POST['document_type'] ?? 'enrollment');
    if (!in_array($document_type, array('enrollment', 'diploma'), true)) $document_type = 'enrollment';
    
    if (empty($customer_email) || empty($customer_name)) {
        wp_send_json_error(array('message' => 'E-mail e nome são obrigatórios'));
    }
    
    // Criar diretório
    $upload_dir = wp_upload_dir();
    $student_docs_dir = $upload_dir['basedir'] . '/comprovantes-estudante/' . date('Y/m');
    
    if (!file_exists($student_docs_dir)) {
        wp_mkdir_p($student_docs_dir);
        file_put_contents($upload_dir['basedir'] . '/comprovantes-estudante/.htaccess', "Order Deny,Allow\nDeny from all\n");
    }
    
    // Gerar nome único (inclui document_type para distinguir diploma/matricula)
    $unique_name = sprintf('%s_%s_%s_%s.pdf',
        date('YmdHis'),
        $document_type,
        sanitize_file_name(substr(preg_replace('/[^a-z0-9]/', '', strtolower($customer_name)), 0, 20)),
        wp_generate_password(8, false)
    );
    
    $file_path = $student_docs_dir . '/' . $unique_name;
    $relative_path = 'comprovantes-estudante/' . date('Y/m') . '/' . $unique_name;
    
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        wp_send_json_error(array('message' => 'Falha ao salvar o arquivo'));
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'cursos_student_documents';
    
    $insert_data = array(
        'customer_email' => $customer_email,
        'customer_name' => $customer_name,
        'customer_cpf' => $customer_cpf,
        'file_name' => sanitize_file_name($file['name']),
        'file_path' => $relative_path,
        'file_url' => $upload_dir['baseurl'] . '/' . $relative_path,
        'file_size' => $file['size'],
        'status' => 'pending',
    );
    // Só inclui document_type se a coluna existir (compat com migration ainda não rodada)
    $cols = $wpdb->get_col("DESCRIBE $table_name", 0);
    if (in_array('document_type', $cols)) {
        $insert_data['document_type'] = $document_type;
    }
    $wpdb->insert($table_name, $insert_data);
    
    if (!$wpdb->insert_id) {
        @unlink($file_path);
        wp_send_json_error(array('message' => 'Falha ao registrar comprovante'));
    }
    
    wp_send_json_success(array(
        'document_id' => $wpdb->insert_id,
        'document_type' => $document_type,
        'message' => 'Comprovante enviado com sucesso!',
    ));
}
add_action('wp_ajax_upload_student_document', 'cursos_ajax_upload_student_document');
add_action('wp_ajax_nopriv_upload_student_document', 'cursos_ajax_upload_student_document');


/**
 * Vincular comprovante ao pedido (com curso_id e amount automático)
 */
function cursos_link_document_to_order($order_id, $document_id) {
    global $wpdb;
    $docs_table = $wpdb->prefix . 'cursos_student_documents';
    $orders_table = $wpdb->prefix . 'cursos_orders';
    
    // Buscar curso_id e amount do pedido
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT curso_id, amount FROM $orders_table WHERE id = %d",
        $order_id
    ));
    
    // Atualizar documento com order_id, curso_id e amount
    return $wpdb->update(
        $docs_table,
        array(
            'order_id' => $order_id,
            'curso_id' => $order ? $order->curso_id : null,
            'amount' => $order ? $order->amount : null
        ),
        array('id' => $document_id),
        array('%d', '%d', '%f'),
        array('%d')
    );
}

/**
 * Atualizar status do comprovante
 */
function cursos_update_document_status($document_id, $status, $notes = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cursos_student_documents';
    $history_table = $wpdb->prefix . 'cursos_order_history';
    
    // Buscar status anterior
    $doc = $wpdb->get_row($wpdb->prepare("SELECT status FROM $table_name WHERE id = %d", $document_id));
    $old_status = $doc ? $doc->status : null;
    
    // Atualizar documento
    $data = array(
        'status' => $status,
        'reviewed_by' => get_current_user_id(),
        'reviewed_at' => current_time('mysql'),
    );
    
    if (!empty($notes)) {
        $data['admin_notes'] = sanitize_textarea_field($notes);
    }
    
    $result = $wpdb->update($table_name, $data, array('id' => $document_id));
    
    // Registrar no histórico (se status mudou)
    if ($result !== false && $old_status !== $status) {
        $current_user = wp_get_current_user();
        $wpdb->insert($history_table, array(
            'document_id' => $document_id,
            'old_status' => $old_status,
            'new_status' => $status,
            'notes' => $notes ? sanitize_textarea_field($notes) : null,
            'changed_by' => get_current_user_id(),
            'changed_by_name' => $current_user->display_name,
            'created_at' => current_time('mysql'),
        ));
    }
    
    return $result;
}



/**
 * ========================================
 * CAMPOS EXTRAS PARA PROFESSORES
 * ========================================
 */

/**
 * Adicionar campos extras no meta box de professores
 */
function cursos_professor_campos_extras() {
    add_meta_box(
        'professor_campos_extras',
        'Informações Adicionais',
        'cursos_professor_campos_extras_callback',
        'professor',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'cursos_professor_campos_extras');

/**
 * Callback para renderizar campos extras do professor
 */
function cursos_professor_campos_extras_callback($post) {
    wp_nonce_field('professor_campos_extras_nonce', 'professor_campos_extras_nonce_field');
    
    $instagram = get_post_meta($post->ID, '_professor_instagram', true);
    $crmv = get_post_meta($post->ID, '_professor_crmv', true);
    $ordem = get_post_meta($post->ID, '_professor_ordem', true);
    $curriculo_completo = get_post_meta($post->ID, '_professor_curriculo_completo', true);
    ?>
    <style>
        .professor-extras-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        .professor-extras-grid .field-group {
            display: flex;
            flex-direction: column;
        }
        .professor-extras-grid label {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .professor-extras-grid input {
            padding: 8px 12px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
        }
        .professor-extras-grid input:focus {
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
            outline: none;
        }
        .curriculo-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .curriculo-section label {
            font-weight: 600;
            display: block;
            margin-bottom: 10px;
        }
    </style>
    
    <div class="professor-extras-grid">
        <div class="field-group">
            <label for="professor_instagram">Instagram</label>
            <input type="text" id="professor_instagram" name="professor_instagram" 
                   value="<?php echo esc_attr($instagram); ?>" 
                   placeholder="@usuario ou URL completa">
        </div>
        
        <div class="field-group">
            <label for="professor_crmv">CRMV (Registro Profissional)</label>
            <input type="text" id="professor_crmv" name="professor_crmv" 
                   value="<?php echo esc_attr($crmv); ?>" 
                   placeholder="Ex: CRMV-SP 12345">
        </div>
        
        <div class="field-group">
            <label for="professor_ordem">Ordem de Exibição</label>
            <input type="number" id="professor_ordem" name="professor_ordem" 
                   value="<?php echo esc_attr($ordem); ?>" 
                   placeholder="0" min="0" step="1">
            <small style="color: #666; margin-top: 4px;">Menor número = aparece primeiro</small>
        </div>
    </div>
    
    <div class="curriculo-section">
        <label for="professor_curriculo_completo">Currículo Completo</label>
        <p class="description" style="margin-bottom: 10px;">
            Formação acadêmica detalhada, experiência profissional, publicações, etc.
        </p>
        <?php
        wp_editor($curriculo_completo, 'professor_curriculo_completo', array(
            'textarea_name' => 'professor_curriculo_completo',
            'textarea_rows' => 10,
            'media_buttons' => true,
            'teeny'         => false,
            'quicktags'     => true,
        ));
        ?>
    </div>
    <?php
}

/**
 * Salvar campos extras do professor
 */
function cursos_save_professor_campos_extras($post_id) {
    // Verificar nonce
    if (!isset($_POST['professor_campos_extras_nonce_field']) || 
        !wp_verify_nonce($_POST['professor_campos_extras_nonce_field'], 'professor_campos_extras_nonce')) {
        return;
    }
    
    // Verificar autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Verificar permissões
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Salvar campos
    if (isset($_POST['professor_instagram'])) {
        update_post_meta($post_id, '_professor_instagram', sanitize_text_field($_POST['professor_instagram']));
    }
    
    if (isset($_POST['professor_crmv'])) {
        update_post_meta($post_id, '_professor_crmv', sanitize_text_field($_POST['professor_crmv']));
    }
    
    if (isset($_POST['professor_ordem'])) {
        update_post_meta($post_id, '_professor_ordem', absint($_POST['professor_ordem']));
    }
    
    if (isset($_POST['professor_curriculo_completo'])) {
        update_post_meta($post_id, '_professor_curriculo_completo', wp_kses_post($_POST['professor_curriculo_completo']));
    }
}
add_action('save_post_professor', 'cursos_save_professor_campos_extras');

/**
 * Adicionar coluna de ordem na lista de professores
 */
function cursos_professor_columns_extras($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['crmv'] = 'CRMV';
            $new_columns['ordem'] = 'Ordem';
        }
    }
    return $new_columns;
}
add_filter('manage_professor_posts_columns', 'cursos_professor_columns_extras');

/**
 * Conteúdo das colunas extras de professores
 */
function cursos_professor_column_content_extras($column, $post_id) {
    switch ($column) {
        case 'crmv':
            $crmv = get_post_meta($post_id, '_professor_crmv', true);
            echo $crmv ? esc_html($crmv) : '<span style="color: #999;">—</span>';
            break;
        case 'ordem':
            $ordem = get_post_meta($post_id, '_professor_ordem', true);
            echo $ordem !== '' ? esc_html($ordem) : '0';
            break;
    }
}
add_action('manage_professor_posts_custom_column', 'cursos_professor_column_content_extras', 10, 2);

/**
 * Tornar coluna de ordem ordenável
 */
function cursos_professor_sortable_columns($columns) {
    $columns['ordem'] = 'ordem';
    return $columns;
}
add_filter('manage_edit-professor_sortable_columns', 'cursos_professor_sortable_columns');

/**
 * Ordenar por ordem no query
 */
function cursos_professor_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if ($query->get('post_type') === 'professor' && $query->get('orderby') === 'ordem') {
        $query->set('meta_key', '_professor_ordem');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'cursos_professor_orderby');

/**
 * Helper para obter dados completos do professor
 */
function cursos_get_professor_full_data($professor_id) {
    $professor = get_post($professor_id);
    
    // Validar que o post existe, é do tipo correto E está publicado
    if (!$professor || $professor->post_type !== 'professor' || $professor->post_status !== 'publish') {
        return null;
    }
    
    // Validar nome tem tamanho mínimo (evita nomes como "M" ou "")
    $nome = trim($professor->post_title);
    if (empty($nome) || mb_strlen($nome) < 2) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[VetCursos] Professor ID {$professor_id} ignorado: nome inválido '{$nome}'");
        }
        return null;
    }
    
    return array(
        'id'                 => $professor_id,
        'nome'               => $nome,
        'bio'                => $professor->post_content,
        'foto'               => get_the_post_thumbnail_url($professor_id, 'medium'),
        'titulo'             => get_post_meta($professor_id, '_professor_titulo', true),
        'email'              => get_post_meta($professor_id, '_professor_email', true),
        'linkedin'           => get_post_meta($professor_id, '_professor_linkedin', true),
        'website'            => get_post_meta($professor_id, '_professor_website', true),
        'experiencia'        => get_post_meta($professor_id, '_professor_experiencia', true),
        'especialidades'     => get_post_meta($professor_id, '_professor_especialidades', true),
        'instagram'          => get_post_meta($professor_id, '_professor_instagram', true),
        'crmv'               => get_post_meta($professor_id, '_professor_crmv', true),
        'ordem'              => get_post_meta($professor_id, '_professor_ordem', true),
        'curriculo_completo' => get_post_meta($professor_id, '_professor_curriculo_completo', true),
    );
}

/**
 * Obter dados de todos os professores de um curso
 * Suporta múltiplos professores e mantém compatibilidade com campo único
 * 
 * @param int $post_id ID do curso
 * @return array Array de professores com dados completos
 */
function cursos_get_curso_professores($post_id) {
    // Primeiro tenta buscar do novo campo (array)
    $professores_ids = get_post_meta($post_id, '_curso_professores', true);
    
    // Compatibilidade com campo antigo (single ID)
    if (!is_array($professores_ids) || empty($professores_ids)) {
        $old_professor = get_post_meta($post_id, '_curso_professor', true);
        if (!empty($old_professor)) {
            $professores_ids = array($old_professor);
        } else {
            return array();
        }
    }
    
    $professores = array();
    foreach ($professores_ids as $prof_id) {
        $data = cursos_get_professor_full_data($prof_id);
        if ($data) {
            $professores[] = $data;
        }
    }
    
    return $professores;
}

/**
 * Classes de Cursos Particulares e Pós-Graduação REMOVIDAS
 * Essas páginas serão criadas via Elementor
 */

/**
 * ========================================
 * REORGANIZAÇÃO DO MENU ADMIN - 5 MENUS DISTINTOS
 * ========================================
 * Pagamentos, Cursos, Professores, CRM, LGPD
 */

/**
 * Remover menus duplicados e ocultar CPTs originais
 */
function cursos_hide_duplicate_menus() {
    // Ocultar menu original do CPT Cupom (será movido para Pagamentos)
    remove_menu_page('edit.php?post_type=cupom');
    
    // Remover menu antigo Cursos Config se existir
    remove_menu_page('cursos-config');
}
add_action('admin_menu', 'cursos_hide_duplicate_menus', 998);

/**
 * Criar os 5 menus distintos reorganizados
 */
function cursos_admin_menu_reorganize() {
    global $menu, $submenu;
    
    // ========================================
    // 1. PAGAMENTOS (menu_position: 26)
    // ========================================
add_menu_page(
    'Pagamentos',
    'Pagamentos',
    'manage_options',
    'cursos-pagamentos-config',
    'cursos_pagamentos_page',
    'dashicons-money-alt',
    26
);

// Submenu: Configurações (substitui o submenu automático "Pagamentos")
add_submenu_page(
    'cursos-pagamentos-config',
    'Configurações de Pagamentos',
    'Configurações',
    'manage_options',
    'cursos-pagamentos-config',
    'cursos_pagamentos_page'
);

// Submenu: Cupons
add_submenu_page(
    'cursos-pagamentos-config',
    'Cupons',
    'Cupons',
    'manage_options',
    'edit.php?post_type=cupom'
);

// Submenu: Formulário de Checkout
add_submenu_page(
    'cursos-pagamentos-config',
    'Formulário de Checkout',
    'Formulário',
    'manage_options',
    'cursos-checkout-form',
    'cursos_checkout_form_settings_page'
);

// Submenu: Parcelamento
add_submenu_page(
    'cursos-pagamentos-config',
    'Parcelamento',
    'Parcelamento',
    'manage_options',
    'cursos-parcelamento',
    'cursos_parcelamento_settings_page'
);

// Submenu: Timer Checkout
add_submenu_page(
    'cursos-pagamentos-config',
    'Timer Checkout',
    'Timer Checkout',
    'manage_options',
    'cursos-timer-checkout',
    'cursos_timer_checkout_settings_page'
);
    
}
add_action('admin_menu', 'cursos_admin_menu_reorganize', 999);


/**
 * Página de Pedidos
 */

/**
 * Render Dashboard Principal
 * Nota: CPT 'curso' foi removido - cursos serão gerenciados via Elementor/ACF
 */
function cursos_render_dashboard() {
    // Estatísticas - apenas professores (cursos via Elementor)
    $total_professores = wp_count_posts('professor')->publish;
    
    // Professores recentes
    $professores_recentes = get_posts(array(
        'post_type' => 'professor',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC',
    ));
    ?>
    <div class="wrap">
        <h1 style="display: flex; align-items: center; gap: 12px; margin-bottom: 30px;">
            <span class="dashicons dashicons-welcome-learn-more" style="font-size: 36px; width: 36px; height: 36px;"></span>
            VetCursos - Dashboard
        </h1>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <!-- Card: Total de Professores -->
            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Professores</div>
                <div style="font-size: 36px; font-weight: 700;"><?php echo esc_html($total_professores); ?></div>
                <a href="<?php echo admin_url('edit.php?post_type=professor'); ?>" style="color: #fff; opacity: 0.8; font-size: 13px;">Ver todos →</a>
            </div>
            
            <!-- Card: Pagamentos -->
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Pagamentos</div>
                <div style="font-size: 36px; font-weight: 700;">💳</div>
                <a href="<?php echo admin_url('admin.php?page=cursos-pagamentos'); ?>" style="color: #fff; opacity: 0.8; font-size: 13px;">Configurações →</a>
            </div>
            
            <!-- Card: Ações Rápidas -->
            <div style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; color: #64748b; margin-bottom: 12px;">Ações Rápidas</div>
                <a href="<?php echo admin_url('post-new.php?post_type=professor'); ?>" class="button button-primary" style="margin-right: 8px; margin-bottom: 8px;">+ Novo Professor</a>
                <a href="<?php echo admin_url('admin.php?page=cursos-pix-config'); ?>" class="button" style="margin-bottom: 8px;">Configurar PIX</a>
            </div>
            
            <!-- Card: Elementor -->
            <div style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Cursos</div>
                <div style="font-size: 14px; opacity: 0.9;">Gerenciados via Elementor</div>
                <a href="<?php echo admin_url('edit.php?post_type=page'); ?>" style="color: #fff; opacity: 0.8; font-size: 13px;">Editar páginas →</a>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
            <!-- Professores Recentes -->
            <div style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                <h2 style="margin: 0 0 20px; font-size: 18px;">Professores Recentes</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th style="width: 200px;">Título</th>
                            <th style="width: 120px;">Data</th>
                            <th style="width: 100px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($professores_recentes)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: #666;">
                                Nenhum professor cadastrado ainda.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($professores_recentes as $professor) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($professor->post_title); ?></strong></td>
                            <td><?php echo esc_html(get_post_meta($professor->ID, '_professor_titulo', true)); ?></td>
                            <td><?php echo get_the_date('d/m/Y', $professor); ?></td>
                            <td>
                                <a href="<?php echo get_edit_post_link($professor->ID); ?>">Editar</a> |
                                <a href="<?php echo get_permalink($professor->ID); ?>" target="_blank">Ver</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Páginas de configurações auxiliares
 */


function cursos_lgpd_settings_page() {
    if (class_exists('Cursos_LGPD')) {
        Cursos_LGPD::get_instance()->render_admin_page();
    } else {
        echo '<div class="wrap"><h1>LGPD / Privacidade</h1><p>Módulo LGPD não está ativo.</p></div>';
    }
}


/**
 * ========================================
 * CONFIGURAÇÕES DE PARCELAMENTO
 * ========================================
 */

/**
 * Página de configurações de Parcelamento
 * 
 * IMPORTANTE: a taxa configurada para cada quantidade de parcelas é aplicada UMA VEZ
 * sobre o valor total do pedido, e só depois o total final é dividido pelas parcelas.
 * Fórmula: Total = Valor × (1 + taxa_configurada%) + taxa_fixa
 */
function cursos_parcelamento_settings_page() {
    // Valores padrão de taxas por parcela (Asaas 2024)
    // NOTA: Estas são taxas TOTAIS sobre o valor, NÃO mensais!
    // Baseado em: R$1.800 → 2x-6x = R$1.846,44 (2.58%), 7x-12x = R$1.855,92 (3.11%)
    $taxas_padrao = array(
        1 => array('percentual' => 0.00, 'fixa' => 0.00),
        2 => array('percentual' => 2.58, 'fixa' => 0.00),
        3 => array('percentual' => 2.58, 'fixa' => 0.00),
        4 => array('percentual' => 2.58, 'fixa' => 0.00),
        5 => array('percentual' => 2.58, 'fixa' => 0.00),
        6 => array('percentual' => 2.58, 'fixa' => 0.00),
        7 => array('percentual' => 3.11, 'fixa' => 0.00),
        8 => array('percentual' => 3.11, 'fixa' => 0.00),
        9 => array('percentual' => 3.11, 'fixa' => 0.00),
        10 => array('percentual' => 3.11, 'fixa' => 0.00),
        11 => array('percentual' => 3.11, 'fixa' => 0.00),
        12 => array('percentual' => 3.11, 'fixa' => 0.00),
    );
    
    // Salvar configurações
    if (isset($_POST['cursos_parcelamento_save']) && check_admin_referer('cursos_parcelamento_nonce')) {
        update_option('cursos_parcelamento_auto_api', isset($_POST['auto_api']) ? '1' : '0');
        update_option('cursos_repasse_taxas_ativo', isset($_POST['repasse_taxas']) ? '1' : '0');
        update_option('cursos_parcelamento_max', intval($_POST['max_parcelas']));
        update_option('cursos_parcelamento_min_valor', floatval($_POST['min_valor']));
        update_option('cursos_parcelamento_sem_juros', intval($_POST['sem_juros']));
        
        // Salvar taxas detalhadas por parcela
        $taxas_detalhadas = array();
        for ($i = 1; $i <= 12; $i++) {
            $percentual = isset($_POST['taxa_percentual_' . $i]) ? floatval($_POST['taxa_percentual_' . $i]) : 0;
            $fixa = isset($_POST['taxa_fixa_' . $i]) ? floatval($_POST['taxa_fixa_' . $i]) : 0;
            $taxas_detalhadas[$i] = array(
                'percentual' => $percentual,
                'fixa' => $fixa,
            );
        }
        update_option('cursos_parcelamento_taxas_detalhadas', $taxas_detalhadas);
        
        // Limpar cache de parcelamento
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cursos_installment_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cursos_installment_%'");
        
        echo '<div class="notice notice-success"><p>Configurações de parcelamento salvas!</p></div>';
    }
    
    $auto_api = get_option('cursos_parcelamento_auto_api', '1') === '1';
    $repasse_taxas = get_option('cursos_repasse_taxas_ativo', '0') === '1';
    $max_parcelas = intval(get_option('cursos_parcelamento_max', 12));
    $min_valor = floatval(get_option('cursos_parcelamento_min_valor', 50));
    $sem_juros = intval(get_option('cursos_parcelamento_sem_juros', 12));
    $taxas_detalhadas = get_option('cursos_parcelamento_taxas_detalhadas', $taxas_padrao);
    
    // Mesclar com padrões para garantir que todas as parcelas existam
    $taxas_detalhadas = array_replace($taxas_padrao, $taxas_detalhadas);
    ?>
    <div class="wrap">
        <h1 style="display: flex; align-items: center; gap: 12px;">
            <span class="dashicons dashicons-money-alt" style="font-size: 30px; width: 30px; height: 30px;"></span>
            Configurações de Parcelamento
        </h1>
        
        <form method="post">
            <?php wp_nonce_field('cursos_parcelamento_nonce'); ?>
            
            <div style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px; max-width: 900px;">
                
                <!-- Opção de buscar da API -->
                <div style="background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 16px; margin-bottom: 24px; border-radius: 4px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="auto_api" value="1" <?php checked($auto_api); ?> style="margin-top: 3px;">
                        <div>
                            <strong>Buscar configuração automaticamente do gateway de pagamento</strong>
                            <p style="margin: 4px 0 0; color: #64748b; font-size: 13px;">
                                Se ativo, o sistema tentará obter as opções de parcelamento diretamente da API do Asaas/PagBank.<br>
                                Se a API falhar ou não estiver configurada, as taxas configuradas abaixo serão utilizadas.
                            </p>
                        </div>
                    </label>
                </div>
                
                <!-- NOVA OPÇÃO: Repasse de Taxas -->
                <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin-bottom: 24px; border-radius: 4px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="repasse_taxas" value="1" <?php checked($repasse_taxas); ?> style="margin-top: 3px;">
                        <div>
                            <strong>💰 Repassar taxas de parcelamento para o cliente</strong>
                            <p style="margin: 4px 0 0; color: #92400e; font-size: 13px;">
                                Quando ativo, as taxas de juros do cartão de crédito serão repassadas ao cliente.<br>
                                O cliente verá o valor total com juros incluídos antes de confirmar o pagamento.
                            </p>
                        </div>
                    </label>
                </div>
                
                <h2 style="margin: 0 0 20px; font-size: 16px; color: #374151;">⚙️ Configuração Geral</h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div>
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">
                            Máximo de Parcelas
                        </label>
                        <input type="number" name="max_parcelas" value="<?php echo esc_attr($max_parcelas); ?>" 
                               min="1" max="12" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <p style="margin: 4px 0 0; font-size: 12px; color: #6b7280;">De 1 a 12 parcelas</p>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">
                            Valor Mínimo por Parcela
                        </label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #6b7280;">R$</span>
                            <input type="number" name="min_valor" value="<?php echo esc_attr($min_valor); ?>" 
                                   min="0" step="0.01" style="width: 100%; padding: 8px 12px 8px 32px; border: 1px solid #d1d5db; border-radius: 6px;">
                        </div>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #6b7280;">Valor mínimo de cada parcela</p>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">
                            Parcelas sem Juros
                        </label>
                        <input type="number" name="sem_juros" value="<?php echo esc_attr($sem_juros); ?>" 
                               min="0" max="12" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <p style="margin: 4px 0 0; font-size: 12px; color: #6b7280;">Parcelas absorvidas por você</p>
                    </div>
                </div>
                
                <!-- SEÇÃO: Taxas Detalhadas por Parcela -->
                <div style="border-top: 2px solid #e5e7eb; padding-top: 24px; margin-top: 24px;">
                    <h2 style="margin: 0 0 16px; font-size: 16px; color: #374151; display: flex; align-items: center; gap: 8px;">
                        💳 Taxas por Quantidade de Parcelas
                        <span style="font-size: 12px; font-weight: normal; color: #6b7280;">(usado quando API não retorna dados)</span>
                    </h2>
                    
                    <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; margin-bottom: 16px; border-radius: 4px;">
                        <p style="margin: 0; font-size: 13px; color: #92400e;">
                            <strong>⚠️ IMPORTANTE:</strong> A taxa percentual é aplicada sobre o <strong>valor TOTAL</strong>, não como juros mensais.<br>
                            Exemplo: Produto de R$1.800 com taxa 2.58% → Total = R$1.800 × 1.0258 = R$1.846,44
                        </p>
                    </div>
                    
                    <p style="margin: 0 0 16px; font-size: 13px; color: #64748b;">
                        Para encontrar a taxa correta: Crie um link de pagamento no Asaas com "repassar taxas" ativo e compare os valores.
                    </p>
                    
                    <table style="width: 100%; border-collapse: collapse; background: #f9fafb; border-radius: 8px; overflow: hidden;">
                        <thead>
                            <tr style="background: #e5e7eb;">
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; width: 80px;">Parcelas</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">Taxa % (sobre total)</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">Taxa Fixa (R$)</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151;">Exemplo R$ 1.000</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 1; $i <= 12; $i++): 
                                $taxa = isset($taxas_detalhadas[$i]) ? $taxas_detalhadas[$i] : $taxas_padrao[$i];
                                $percentual = floatval($taxa['percentual']);
                                $fixa = floatval($taxa['fixa']);
                                
                                  // Exemplo para R$ 1.000 — taxa da linha aplicada uma vez sobre o total
                                 $exemplo_total = 1000;
                                 if ($i <= $sem_juros) {
                                     $valor_parcela = $exemplo_total / $i;
                                     $total_final = $exemplo_total;
                                     $is_sem_juros = true;
                                 } else {
                                      // Taxa da quantidade de parcelas aplicada uma vez sobre o total
                                     $i_dec = $percentual / 100;
                                      $total_final = $exemplo_total * (1 + $i_dec) + $fixa;
                                     $valor_parcela = $total_final / $i;
                                     $is_sem_juros = false;
                                 }
                                
                                $row_bg = $i % 2 === 0 ? '#ffffff' : '#f9fafb';
                                $is_disabled = $i > $max_parcelas;
                            ?>
                            <tr style="background: <?php echo $row_bg; ?>; <?php echo $is_disabled ? 'opacity: 0.5;' : ''; ?>">
                                <td style="padding: 12px; text-align: center; font-weight: 600;"><?php echo $i; ?>x</td>
                                <td style="padding: 12px; text-align: center;">
                                    <input type="number" name="taxa_percentual_<?php echo $i; ?>" 
                                           value="<?php echo esc_attr($percentual); ?>" 
                                           step="0.01" min="0" max="20" 
                                           style="width: 80px; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; text-align: center;"
                                           <?php echo ($i <= $sem_juros) ? 'disabled title="Sem juros - não aplicável"' : ''; ?>>
                                    <span style="color: #6b7280;">%</span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span style="color: #6b7280;">R$</span>
                                    <input type="number" name="taxa_fixa_<?php echo $i; ?>" 
                                           value="<?php echo esc_attr($fixa); ?>" 
                                           step="0.01" min="0" max="50" 
                                           style="width: 70px; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; text-align: center;"
                                           <?php echo ($i <= $sem_juros) ? 'disabled title="Sem juros - não aplicável"' : ''; ?>>
                                </td>
                                <td style="padding: 12px; text-align: center; font-family: monospace;">
                                    <?php if ($is_sem_juros): ?>
                                        <span style="color: #10b981;"><?php echo $i; ?>x R$ <?php echo number_format($valor_parcela, 2, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span><?php echo $i; ?>x R$ <?php echo number_format($valor_parcela, 2, ',', '.'); ?></span>
                                        <br><small style="color: #6b7280;">(total: R$ <?php echo number_format($total_final, 2, ',', '.'); ?>)</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                    
                    <p style="margin: 12px 0 0; font-size: 12px; color: #6b7280;">
                        💡 <strong>Dica:</strong> Valores típicos do Asaas: 2x-6x ≈ 2.58% total, 7x-12x ≈ 3.11% total. Confirme com um link de teste no Asaas.
                    </p>
                </div>
                
                <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                    <button type="submit" name="cursos_parcelamento_save" class="button button-primary button-large">
                        💾 Salvar Configurações
                    </button>
                </div>
            </div>
            
            <!-- Informações sobre CDC -->
            <div style="background: #ecfdf5; border-left: 4px solid #10b981; padding: 16px; margin-top: 20px; border-radius: 4px; max-width: 900px;">
                <strong>✅ Conformidade com CDC (Código de Defesa do Consumidor):</strong>
                <ul style="margin: 8px 0 0; padding-left: 20px; color: #047857;">
                    <li>O sistema sempre exibe o valor da parcela E o total final quando há juros.</li>
                    <li>Exemplo: "6x de R$ 307,74 (total: R$ 1.846,44)"</li>
                    <li>O cliente vê todas as informações antes de confirmar o pagamento.</li>
                </ul>
            </div>
            
            <div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 16px; margin-top: 20px; border-radius: 4px; max-width: 900px;">
                <strong>📐 Fórmula de cálculo (igual ao Asaas):</strong>
                <ul style="margin: 8px 0 0; padding-left: 20px; color: #1e40af;">
                    <li><strong>Total Final</strong> = Valor Original × (1 + Taxa configurada para a parcela%) + Taxa Fixa</li>
                    <li><strong>Valor Parcela</strong> = Total Final ÷ Nº Parcelas</li>
                    <li><strong>Exemplo:</strong> R$1.800 × 1.0258 = R$1.846,44 (6x de R$307,74)</li>
                </ul>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Página de configuração do Timer de Checkout
 */
function cursos_timer_checkout_settings_page() {
    // Salvar configurações
    if (isset($_POST['cursos_timer_checkout_save']) && check_admin_referer('cursos_timer_checkout_nonce')) {
        update_option('cursos_checkout_timer_minutos', intval($_POST['timer_minutos']));
        update_option('cursos_checkout_timer_ativo', isset($_POST['timer_ativo']) ? '1' : '0');
        update_option('cursos_checkout_timer_aviso', intval($_POST['timer_aviso']));
        update_option('cursos_checkout_timer_renovar', isset($_POST['timer_renovar']) ? '1' : '0');
        
        echo '<div class="notice notice-success"><p>Configurações do Timer salvas com sucesso!</p></div>';
    }
    
    $timer_minutos = intval(get_option('cursos_checkout_timer_minutos', 15));
    $timer_ativo = get_option('cursos_checkout_timer_ativo', '1') === '1';
    $timer_aviso = intval(get_option('cursos_checkout_timer_aviso', 2));
    $timer_renovar = get_option('cursos_checkout_timer_renovar', '1') === '1';
    ?>
    <div class="wrap">
        <h1 style="display: flex; align-items: center; gap: 12px;">
            <span class="dashicons dashicons-clock" style="font-size: 30px; width: 30px; height: 30px;"></span>
            Timer de Checkout
        </h1>
        
        <p style="color: #64748b; margin-bottom: 24px;">
            Configure o tempo de reserva de vaga durante o checkout. Quando ativo, o cliente terá um tempo limitado para finalizar a compra.
        </p>
        
        <form method="post">
            <?php wp_nonce_field('cursos_timer_checkout_nonce'); ?>
            
            <div style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px; max-width: 600px;">
                
                <!-- Ativar Timer -->
                <div style="background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 16px; margin-bottom: 24px; border-radius: 4px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="timer_ativo" value="1" <?php checked($timer_ativo); ?> style="margin-top: 3px;">
                        <div>
                            <strong>Ativar Timer de Checkout</strong>
                            <p style="margin: 4px 0 0; color: #64748b; font-size: 13px;">
                                Quando ativo, mostra um contador regressivo durante o checkout para criar senso de urgência.
                            </p>
                        </div>
                    </label>
                </div>
                
                <h2 style="margin: 0 0 20px; font-size: 16px; color: #374151;">⏱️ Configurações do Timer</h2>
                
                <div style="display: grid; gap: 20px;">
                    
                    <!-- Tempo em minutos -->
                    <div>
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">
                            Tempo de Reserva (minutos)
                        </label>
                        <input type="number" name="timer_minutos" value="<?php echo esc_attr($timer_minutos); ?>" 
                               min="1" max="60" style="width: 120px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <p style="margin: 4px 0 0; font-size: 12px; color: #6b7280;">
                            Tempo que o cliente tem para finalizar a compra (1 a 60 minutos). Padrão: 15 minutos.
                        </p>
                    </div>
                    
                    <!-- Tempo de aviso -->
                    <div>
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">
                            Aviso de Urgência (minutos restantes)
                        </label>
                        <input type="number" name="timer_aviso" value="<?php echo esc_attr($timer_aviso); ?>" 
                               min="1" max="10" style="width: 120px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <p style="margin: 4px 0 0; font-size: 12px; color: #6b7280;">
                            Quando restar esse tempo, o timer ficará em destaque (vermelho). Padrão: 2 minutos.
                        </p>
                    </div>
                    
                    <!-- Permitir renovar -->
                    <div style="background: #f9fafb; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
                        <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="timer_renovar" value="1" <?php checked($timer_renovar); ?> style="margin-top: 3px;">
                            <div>
                                <strong>Permitir Renovar Reserva</strong>
                                <p style="margin: 4px 0 0; color: #64748b; font-size: 13px;">
                                    Quando ativo, mostra um botão para o cliente renovar o tempo quando estiver acabando.
                                </p>
                            </div>
                        </label>
                    </div>
                    
                </div>
                
                <!-- Preview do Timer -->
                <div style="margin-top: 24px; padding: 16px; background: #fef3c7; border-radius: 8px; border: 1px solid #fcd34d;">
                    <p style="margin: 0; font-weight: 600; color: #92400e;">
                        ⏱️ Prévia: O timer mostrará <?php echo $timer_minutos; ?>:00 e ficará vermelho quando restar <?php echo $timer_aviso; ?> minuto(s).
                    </p>
                </div>
                
            </div>
            
            <p style="margin-top: 20px;">
                <button type="submit" name="cursos_timer_checkout_save" class="button button-primary button-large">
                    💾 Salvar Configurações
                </button>
            </p>
        </form>
    </div>
    <?php
}

// Submenu de Parcelamento movido para cursos_admin_menu_reorganize()


/**
 * Obter opções de parcelamento
 * 
 * Primeiro tenta buscar da API do gateway (se configurado),
 * depois usa fallback da configuração manual.
 * Cacheia o resultado por 1 hora para performance.
 * 
 * @param float $total Valor total da compra
 * @param string $gateway Gateway de pagamento (asaas, pagseguro, ou vazio para detectar)
 * @return array Array com opções de parcelamento
 */
/**
 * Retorna a quantidade efetiva de parcelas sem juros considerando o carrinho.
 * Se qualquer item do carrinho tem override configurado, usa o MAIOR valor
 * entre eles; caso contrário, cai no global. Passe null para usar o carrinho
 * atual (cursos_get_cart_items).
 */
function cursos_get_effective_sem_juros($cart_items = null) {
    $global = intval(get_option('cursos_parcelamento_sem_juros', 12));
    if ($cart_items === null && function_exists('cursos_get_cart_items')) {
        $cart_items = cursos_get_cart_items();
    }
    if (empty($cart_items) || !is_array($cart_items)) {
        return $global;
    }
    $override = null;
    foreach ($cart_items as $item) {
        $cid = 0;
        if (is_array($item)) {
            $cid = intval($item['curso_id'] ?? ($item['id'] ?? 0));
        } elseif (is_numeric($item)) {
            $cid = intval($item);
        }
        if (!$cid) continue;
        $meta = get_post_meta($cid, '_curso_parcelamento_sem_juros', true);
        if ($meta === '' || $meta === null) continue;
        $val = intval($meta);
        $override = ($override === null) ? $val : max($override, $val);
    }
    return ($override === null) ? $global : $override;
}

function cursos_get_installment_options($total, $gateway = '', $sem_juros_override = null) {
    // Detectar gateway se não informado
    if (empty($gateway)) {
        $gateway_preferido = get_option('cursos_gateway_preferido', 'auto');
        if ($gateway_preferido === 'auto') {
            $asaas = cursos_asaas();
            $pagseguro = cursos_pagseguro();
            $gateway = $asaas->is_active() ? 'asaas' : ($pagseguro->is_active() ? 'pagseguro' : '');
        } else {
            $gateway = $gateway_preferido;
        }
    }
    
    $auto_api = get_option('cursos_parcelamento_auto_api', '1') === '1';
    $repasse_taxas = get_option('cursos_repasse_taxas_ativo', '0') === '1';
    
    // Verificar cache (inclui override no key para não cruzar cursos)
    $override_key = ($sem_juros_override === null) ? 'g' : intval($sem_juros_override);
    $cache_key = 'cursos_installment_v4_' . md5($total . '_' . $gateway . '_' . ($repasse_taxas ? '1' : '0') . '_' . $override_key);
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }
    
    $installments = array();
    
    // Se há override por curso, SEMPRE usa cálculo manual (para respeitar o override
    // tanto reduzindo quanto ampliando as parcelas sem juros em relação ao global/API).
    if ($sem_juros_override !== null) {
        $installments = cursos_get_manual_installment_options($total, $sem_juros_override);
    } else {
        // Tentar buscar da API se configurado
        if ($auto_api) {
            if ($gateway === 'asaas') {
                $asaas = cursos_asaas();
                $api_result = $asaas->get_installment_config($total);
                if ($api_result['success'] && !empty($api_result['installments'])) {
                    $installments = $api_result['installments'];
                }
            } elseif ($gateway === 'pagseguro') {
                $pagseguro = cursos_pagseguro();
                $api_result = $pagseguro->get_installment_config($total);
                if ($api_result['success'] && !empty($api_result['installments'])) {
                    $installments = $api_result['installments'];
                }
            }
        }
        // Fallback: configuração manual
        if (empty($installments)) {
            $installments = cursos_get_manual_installment_options($total);
        }
    }
    
    // Se repasse de taxas NÃO está ativo, forçar todas as parcelas como "sem juros" (valor original)
    if (!$repasse_taxas) {
        foreach ($installments as &$option) {
            $option['value'] = round($total / $option['number'], 2);
            $option['total'] = $total;
            $option['interest_free'] = true;
        }
    }
    
    // Cache por 1 hora
    set_transient($cache_key, $installments, HOUR_IN_SECONDS);
    
    return $installments;
}

/**
 * Obter opções de parcelamento da configuração manual
 * 
 * @param float $total Valor total
 * @return array Array com opções de parcelamento
 */
function cursos_get_manual_installment_options($total, $sem_juros_override = null) {
    $max_parcelas = intval(get_option('cursos_parcelamento_max', 12));
    $min_valor = floatval(get_option('cursos_parcelamento_min_valor', 50));
    $sem_juros = ($sem_juros_override !== null) ? intval($sem_juros_override) : intval(get_option('cursos_parcelamento_sem_juros', 12));
    
    // Obter taxas detalhadas por parcela
    // NOTA: Estas são taxas TOTAIS sobre o valor, NÃO mensais (igual ao Asaas)
    $taxas_padrao = array(
        1 => array('percentual' => 0.00, 'fixa' => 0.00),
        2 => array('percentual' => 2.58, 'fixa' => 0.00),
        3 => array('percentual' => 2.58, 'fixa' => 0.00),
        4 => array('percentual' => 2.58, 'fixa' => 0.00),
        5 => array('percentual' => 2.58, 'fixa' => 0.00),
        6 => array('percentual' => 2.58, 'fixa' => 0.00),
        7 => array('percentual' => 3.11, 'fixa' => 0.00),
        8 => array('percentual' => 3.11, 'fixa' => 0.00),
        9 => array('percentual' => 3.11, 'fixa' => 0.00),
        10 => array('percentual' => 3.11, 'fixa' => 0.00),
        11 => array('percentual' => 3.11, 'fixa' => 0.00),
        12 => array('percentual' => 3.11, 'fixa' => 0.00),
    );
    $taxas_detalhadas = get_option('cursos_parcelamento_taxas_detalhadas', $taxas_padrao);
    $taxas_detalhadas = array_replace($taxas_padrao, $taxas_detalhadas);
    
    $options = array();
    
    for ($i = 1; $i <= $max_parcelas; $i++) {
        $valor_parcela = $total / $i;
        
        // Verificar valor mínimo por parcela
        if ($valor_parcela < $min_valor && $i > 1) {
            break;
        }
        
        $interest_free = ($i <= $sem_juros);
        
        // Calcular com juros se necessário (inclui 1x quando configurado)
        if (!$interest_free) {
            $taxa = isset($taxas_detalhadas[$i]) ? $taxas_detalhadas[$i] : array('percentual' => 0, 'fixa' => 0);
            $taxa_percentual = floatval($taxa['percentual']); // Taxa configurada para esta quantidade de parcelas
            $taxa_fixa = floatval($taxa['fixa']);
            $i_dec = $taxa_percentual / 100;

            // Taxa da quantidade de parcelas aplicada uma vez sobre o total do curso
            $valor_total = $total * (1 + $i_dec) + $taxa_fixa;
            $valor_parcela = $valor_total / $i;
        } else {
            $valor_total = $total;
        }
        
        $options[] = array(
            'number' => $i,
            'value' => round($valor_parcela, 2),
            'total' => round($valor_total, 2),
            'interest_free' => $interest_free,
        );
    }
    
    return $options;
}

/**
 * Obter taxas detalhadas para uso no JavaScript
 * 
 * @return array Array com taxas por parcela (taxa TOTAL, não mensal)
 */
function cursos_get_taxas_detalhadas_js() {
    // Taxas padrão baseadas no Asaas (taxa TOTAL sobre o valor)
    $taxas_padrao = array(
        1 => array('percentual' => 0.00, 'fixa' => 0.00),
        2 => array('percentual' => 2.58, 'fixa' => 0.00),
        3 => array('percentual' => 2.58, 'fixa' => 0.00),
        4 => array('percentual' => 2.58, 'fixa' => 0.00),
        5 => array('percentual' => 2.58, 'fixa' => 0.00),
        6 => array('percentual' => 2.58, 'fixa' => 0.00),
        7 => array('percentual' => 3.11, 'fixa' => 0.00),
        8 => array('percentual' => 3.11, 'fixa' => 0.00),
        9 => array('percentual' => 3.11, 'fixa' => 0.00),
        10 => array('percentual' => 3.11, 'fixa' => 0.00),
        11 => array('percentual' => 3.11, 'fixa' => 0.00),
        12 => array('percentual' => 3.11, 'fixa' => 0.00),
    );
    $taxas_detalhadas = get_option('cursos_parcelamento_taxas_detalhadas', $taxas_padrao);
    return array_replace($taxas_padrao, $taxas_detalhadas);
}

/**
 * Obter texto de parcelamento para exibição nos cards
 * 
 * @param float $total Valor total
 * @return string Texto formatado (ex: "12x de R$ 99,00 sem juros")
 */
function cursos_get_installment_display_text($total) {
    $options = cursos_get_installment_options($total);
    
    if (empty($options)) {
        return '';
    }
    
    // Pegar a maior parcela sem juros
    $best_option = null;
    foreach ($options as $option) {
        if ($option['interest_free']) {
            $best_option = $option;
        }
    }
    
    // Se não há sem juros, pegar a maior parcela
    if (!$best_option) {
        $best_option = end($options);
    }
    
    if (!$best_option) {
        return '';
    }
    
    $formatted_value = cursos_format_price($best_option['value']);
    $text = $best_option['number'] . 'x de ' . $formatted_value;
    if (!empty($best_option['interest_free'])) {
        $text .= ' sem juros';
    }

    return $text;

}

/**
 * Redirecionar cursos de pós-graduação para template específico
 */
function cursos_pos_graduacao_template($template) {
    if (is_singular('curso')) {
        $tipos = get_the_terms(get_the_ID(), 'tipo_curso');
        if ($tipos && !is_wp_error($tipos)) {
            foreach ($tipos as $tipo) {
                if (in_array($tipo->slug, array('pos-graduacao', 'posgraduacao', 'pos', 'especializacao'))) {
                    $pos_template = locate_template('single-pos-graduacao.php');
                    if ($pos_template) {
                        return $pos_template;
                    }
                }
            }
        }
    }
    return $template;
}
add_filter('template_include', 'cursos_pos_graduacao_template');

/**
 * ========================================
 * HOOK DE ATIVAÇÃO DO TEMA
 * ========================================
 * Consolida todas as criações de tabelas e configurações iniciais
 */
function cursos_theme_activation() {
    // Criar tabela de pedidos
    cursos_create_orders_table();
    
    // Criar tabela de documentos de estudante
    cursos_create_student_documents_table();
    
    
    // Criar tabelas do sistema de cupons
    if (class_exists('Cursos_Cupons') && method_exists('Cursos_Cupons', 'create_tables')) {
        Cursos_Cupons::create_tables();
    }
    
    // Flush rewrite rules para CPTs
    flush_rewrite_rules();
    
    // Definir opções padrão
    if (get_option('cursos_theme_activated') !== 'yes') {
        // Configurações padrão de descontos
        add_option('cursos_desconto_pix_ativo', '1');
        add_option('cursos_desconto_pix_percent', 10);
        add_option('cursos_desconto_estudante_ativo', '1');
        add_option('cursos_desconto_estudante_percent', 10);
        
        // Marcar como ativado
        update_option('cursos_theme_activated', 'yes');
    }
}
add_action('after_switch_theme', 'cursos_theme_activation');

/**
 * Hook de desativação do tema
 */
function cursos_theme_deactivation() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
add_action('switch_theme', 'cursos_theme_deactivation');

/**
 * Página de configurações do Formulário de Checkout
 */
function cursos_checkout_form_settings_page() {
    // Salvar configurações
    if (isset($_POST['save_checkout_form_settings']) && isset($_POST['checkout_form_nonce']) && wp_verify_nonce($_POST['checkout_form_nonce'], 'save_checkout_form')) {
        update_option('cursos_checkout_show_gender', isset($_POST['show_gender']) ? '1' : '0');
        update_option('cursos_checkout_phone_mode', sanitize_text_field($_POST['phone_mode']));
        update_option('cursos_checkout_title', sanitize_text_field($_POST['checkout_title']));
        update_option('cursos_checkout_button_text', sanitize_text_field($_POST['button_text']));
        update_option('cursos_checkout_secure_message', sanitize_text_field($_POST['secure_message']));
        
        echo '<div class="notice notice-success is-dismissible"><p>Configurações salvas com sucesso!</p></div>';
    }
    
    // Carregar valores atuais
    $show_gender = get_option('cursos_checkout_show_gender', '1') === '1';
    $phone_mode = get_option('cursos_checkout_phone_mode', 'required');
    $checkout_title = get_option('cursos_checkout_title', 'Finalizar Compra');
    $button_text = get_option('cursos_checkout_button_text', 'Pagar');
    $secure_message = get_option('cursos_checkout_secure_message', 'Pagamento 100% seguro');
    ?>
    <div class="wrap">
        <h1>Formulário de Checkout</h1>
        <p>Configure os campos e mensagens do formulário de checkout.</p>
        
        <form method="post">
            <?php wp_nonce_field('save_checkout_form', 'checkout_form_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Campos do Formulário</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="show_gender" value="1" <?php checked($show_gender); ?>>
                                Mostrar campo Gênero
                            </label>
                            <p class="description">Se marcado, exibe o campo de seleção de gênero no checkout.</p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Campo Telefone</th>
                    <td>
                        <select name="phone_mode">
                            <option value="required" <?php selected($phone_mode, 'required'); ?>>Obrigatório</option>
                            <option value="optional" <?php selected($phone_mode, 'optional'); ?>>Opcional</option>
                            <option value="hidden" <?php selected($phone_mode, 'hidden'); ?>>Oculto</option>
                        </select>
                        <p class="description">Define se o campo de telefone é obrigatório, opcional ou oculto.</p>
                    </td>
                </tr>
                
                <tr>
                    <th colspan="2">
                        <h2 style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ccd0d4;">Mensagens Personalizáveis</h2>
                    </th>
                </tr>
                
                <tr>
                    <th scope="row">Título da Página</th>
                    <td>
                        <input type="text" name="checkout_title" value="<?php echo esc_attr($checkout_title); ?>" class="regular-text">
                        <p class="description">Título principal exibido no topo da página de checkout.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Texto do Botão</th>
                    <td>
                        <input type="text" name="button_text" value="<?php echo esc_attr($button_text); ?>" class="regular-text">
                        <p class="description">Texto do botão de finalizar compra (o valor será adicionado automaticamente).</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Mensagem de Segurança</th>
                    <td>
                        <input type="text" name="secure_message" value="<?php echo esc_attr($secure_message); ?>" class="regular-text">
                        <p class="description">Mensagem exibida abaixo do botão de pagamento.</p>
                    </td>
                </tr>
            </table>
            
            <h2 style="margin-top: 30px;">Prévia</h2>
            <div style="background: #f0f0f1; padding: 20px; border-radius: 8px; max-width: 400px;">
                <div style="background: var(--primary, #059669); color: white; padding: 15px 30px; border-radius: 6px; text-align: center; font-weight: 600;">
                    🔒 <?php echo esc_html($button_text); ?> R$ XXX,XX
                </div>
                <p style="text-align: center; color: #666; font-size: 14px; margin-top: 10px;">
                    <?php echo esc_html($secure_message); ?>
                </p>
            </div>
            
            <p style="margin-top: 20px;">
                <button type="submit" name="save_checkout_form_settings" class="button button-primary">Salvar Configurações</button>
            </p>
        </form>
    </div>
    <?php
}

/**
 * ========================================
 * HELPER: ÍCONES SVG PARA SINGLE CURSO
 * ========================================
 */

/**
 * Retorna o SVG de um ícone pelo nome
 * Ícones baseados no Lucide React (usado no Lovable)
 */
function cursos_get_icon_svg($icon_name, $size = 16) {
    $icons = [
        'play-circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>',
        'PlayCircle' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>',
        'clock' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'Clock' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'award' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>',
        'Award' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>',
        'check-circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        'CheckCircle' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        'message-circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
        'MessageCircle' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
        'smartphone' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>',
        'Smartphone' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>',
        'download' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
        'Download' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
        'file-text' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'FileText' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'video' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>',
        'Video' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>',
        'headphones' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>',
        'Headphones' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>',
        'book' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        'Book' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'Calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'users' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'Users' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'star' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'Star' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'shield' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'Shield' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'trophy' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>',
        'Trophy' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>',
        'gift' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>',
        'Gift' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>',
        'target' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
        'Target' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
        'zap' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        'Zap' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        'heart' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'Heart' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'briefcase' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
        'Briefcase' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    ];
    
    // Fallback para check-circle se o ícone não existir
    if (!isset($icons[$icon_name])) {
        return $icons['check-circle'];
    }
    
    return $icons[$icon_name];
}

// ========================================
// SEGURANÇA - HEADERS HTTP
// ========================================

/**
 * Adiciona headers de segurança HTTP para proteção contra ataques
 * X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, CSP, HSTS
 */
function cursos_security_headers() {
    // Não aplicar no admin do WordPress
    if (is_admin()) {
        return;
    }
    
    // Impedir que o site seja carregado em iframes externos (Clickjacking)
    header('X-Frame-Options: SAMEORIGIN');
    
    // Impedir sniffing de MIME type
    header('X-Content-Type-Options: nosniff');
    
    // Ativar proteção XSS do navegador
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer Policy - não enviar referrer para outros domínios
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Permissions Policy - restringir acesso a recursos sensíveis
    header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()");
    
    // Content Security Policy - proteção contra XSS e injeção de scripts
    // Permite scripts inline (necessários para WordPress/Elementor) mas restringe origens externas
    $csp = "default-src 'self' https:; ";
    $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.googleapis.com https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net https://www.youtube.com https://player.vimeo.com https://translate.google.com https://translate.googleapis.com https://translate-pa.googleapis.com https://www.gstatic.com; ";
    $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://translate.googleapis.com https://www.gstatic.com; ";
    $csp .= "font-src 'self' https://fonts.gstatic.com data:; ";
    $csp .= "img-src 'self' data: https: blob:; ";
    $csp .= "frame-src 'self' https://www.youtube.com https://player.vimeo.com https://www.google.com https://translate.google.com; ";
    $csp .= "frame-ancestors 'self'; ";
    $csp .= "form-action 'self' https:; ";
    $csp .= "base-uri 'self'; ";
    $csp .= "connect-src 'self' https: wss:; ";
    $csp .= "worker-src 'self' blob:; ";
    $csp .= "object-src 'none';";
    
    header("Content-Security-Policy: " . $csp);
    
    // HSTS - Forçar HTTPS por 1 ano (apenas se já estiver em HTTPS)
    if (is_ssl()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}
add_action('send_headers', 'cursos_security_headers');

// ========================================
// SEGURANÇA - FORÇAR HTTPS
// ========================================

/**
 * Redireciona todo tráfego HTTP para HTTPS
 */
function cursos_force_https() {
    // Verificar se não está em HTTPS e não é localhost
    if (!is_ssl() && !is_admin()) {
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        
        // Não redirecionar em localhost/ambiente de desenvolvimento
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            return;
        }
        
        $redirect_url = 'https://' . $host . $_SERVER['REQUEST_URI'];
        wp_redirect($redirect_url, 301);
        exit;
    }
}
add_action('template_redirect', 'cursos_force_https');

/**
 * Força URLs internas a usarem HTTPS
 */
function cursos_force_https_urls($url) {
    if (is_ssl()) {
        $url = str_replace('http://', 'https://', $url);
    }
    return $url;
}
add_filter('site_url', 'cursos_force_https_urls');
add_filter('home_url', 'cursos_force_https_urls');
add_filter('wp_get_attachment_url', 'cursos_force_https_urls');
add_filter('script_loader_src', 'cursos_force_https_urls');
add_filter('style_loader_src', 'cursos_force_https_urls');

// ========================================
// SEGURANÇA - COOKIES SEGUROS
// ========================================

/**
 * Configura sessões PHP com cookies seguros
 */
function cursos_secure_session() {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        $secure = is_ssl();
        $httponly = true;
        
        // Configurar parâmetros de cookie seguros
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => COOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => 'Lax'
            ]);
        } else {
            session_set_cookie_params(
                0,
                COOKIEPATH . '; SameSite=Lax',
                COOKIE_DOMAIN,
                $secure,
                $httponly
            );
        }
    }
}
add_action('init', 'cursos_secure_session', 1);

/**
 * Adiciona SameSite aos cookies do WordPress
 */
function cursos_secure_auth_cookies($secure, $secure_logged_in_cookie) {
    return is_ssl();
}
add_filter('secure_auth_cookie', 'cursos_secure_auth_cookies', 10, 2);
add_filter('secure_logged_in_cookie', 'cursos_secure_auth_cookies', 10, 2);

// ========================================
// SEGURANÇA - RATE LIMITING
// ========================================

/**
 * Verifica rate limit para ações sensíveis
 * 
 * @param string $action Identificador da ação (checkout, login, etc)
 * @param int $limit Número máximo de tentativas
 * @param int $window Janela de tempo em segundos
 * @return bool True se permitido, False se bloqueado
 */
function cursos_rate_limit_check($action = 'checkout', $limit = 10, $window = 300) {
    $ip = cursos_get_client_ip();
    $transient_key = 'rate_limit_' . md5($action . $ip);
    $attempts = get_transient($transient_key);
    
    if ($attempts === false) {
        $attempts = 0;
    }
    
    if ($attempts >= $limit) {
        return false; // Bloqueado
    }
    
    set_transient($transient_key, $attempts + 1, $window);
    return true;
}

/**
 * Retorna mensagem de erro de rate limit
 */
function cursos_rate_limit_error() {
    return __('Muitas tentativas. Por favor, aguarde alguns minutos antes de tentar novamente.', 'cursos-theme');
}

/**
 * Obtém IP do cliente de forma segura
 */
function cursos_get_client_ip() {
    $ip = '';
    
    // Verificar headers em ordem de confiabilidade
    $headers = array(
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    );
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // Se for lista de IPs, pegar o primeiro
            if (strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }
            break;
        }
    }
    
    // Validar IP
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return '0.0.0.0';
}

// ========================================
// SEGURANÇA - VALIDAÇÃO DE CPF
// ========================================

/**
 * Valida CPF com dígitos verificadores
 * 
 * @param string $cpf CPF a validar (com ou sem formatação)
 * @return bool True se válido, False se inválido
 */
function cursos_validate_cpf($cpf) {
    // Remover caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verificar se tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verificar se todos os dígitos são iguais (CPFs inválidos conhecidos)
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    
    // Calcular primeiro dígito verificador
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += intval($cpf[$i]) * (10 - $i);
    }
    $remainder = $sum % 11;
    $digit1 = ($remainder < 2) ? 0 : (11 - $remainder);
    
    if (intval($cpf[9]) !== $digit1) {
        return false;
    }
    
    // Calcular segundo dígito verificador
    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
        $sum += intval($cpf[$i]) * (11 - $i);
    }
    $remainder = $sum % 11;
    $digit2 = ($remainder < 2) ? 0 : (11 - $remainder);
    
    if (intval($cpf[10]) !== $digit2) {
        return false;
    }
    
    return true;
}

/**
 * Formata CPF para exibição (xxx.xxx.xxx-xx)
 */
function cursos_format_cpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) == 11) {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
    return $cpf;
}

// ========================================
// SEGURANÇA - PROTEÇÃO CONTRA ATAQUES
// ========================================

/**
 * Remove informações de versão do WordPress (fingerprinting)
 */
function cursos_remove_version_info() {
    return '';
}
add_filter('the_generator', 'cursos_remove_version_info');

/**
 * Remove versão dos scripts e estilos
 */
function cursos_remove_script_version($src) {
    if (strpos($src, 'ver=') !== false) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}
add_filter('script_loader_src', 'cursos_remove_script_version', 15);
add_filter('style_loader_src', 'cursos_remove_script_version', 15);

/**
 * Desabilita XML-RPC para prevenir ataques de brute force
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Desabilita REST API para usuários não autenticados (endpoints sensíveis)
 */
function cursos_restrict_rest_api($result) {
    // Permitir acesso público a endpoints específicos
    $allowed_routes = array(
        '/wp/v2/posts',
        '/wp/v2/pages',
        '/wp/v2/curso',
        '/cursos/v1/',
    );
    
    $current_route = isset($GLOBALS['wp']->query_vars['rest_route']) 
        ? $GLOBALS['wp']->query_vars['rest_route'] 
        : '';
    
    foreach ($allowed_routes as $route) {
        if (strpos($current_route, $route) === 0) {
            return $result;
        }
    }
    
    // Bloquear acesso a /wp/v2/users para não logados
    if (strpos($current_route, '/wp/v2/users') !== false && !is_user_logged_in()) {
        return new WP_Error(
            'rest_forbidden',
            __('Acesso não autorizado.', 'cursos-theme'),
            array('status' => 401)
        );
    }
    
    return $result;
}
add_filter('rest_authentication_errors', 'cursos_restrict_rest_api');

/**
 * Adiciona atributos de segurança a links externos
 */
function cursos_secure_external_links($content) {
    // Adiciona rel="noopener noreferrer" a links externos
    $content = preg_replace_callback(
        '/<a\s+([^>]*href=["\']https?:\/\/(?!' . preg_quote(parse_url(home_url(), PHP_URL_HOST), '/') . ')[^"\']+["\'][^>]*)>/i',
        function($matches) {
            $tag = $matches[0];
            if (strpos($tag, 'rel=') === false) {
                return str_replace('>', ' rel="noopener noreferrer" target="_blank">', $tag);
            }
            return $tag;
        },
        $content
    );
    return $content;
}
add_filter('the_content', 'cursos_secure_external_links');

/**
 * Previne enumeração de usuários via autor
 */
function cursos_prevent_user_enumeration() {
    if (!is_admin() && isset($_REQUEST['author']) && is_numeric($_REQUEST['author'])) {
        wp_redirect(home_url(), 301);
        exit;
    }
}
add_action('init', 'cursos_prevent_user_enumeration');

// ========================================
// SEGURANÇA - SANITIZAÇÃO ADICIONAL
// ========================================

/**
 * Sanitiza dados de checkout para segurança extra
 */
function cursos_sanitize_checkout_data($data) {
    $sanitized = array();
    
    // Lista de campos permitidos e seus sanitizadores
    $allowed_fields = array(
        'name' => 'sanitize_text_field',
        'email' => 'sanitize_email',
        'phone' => 'sanitize_text_field',
        'cpf' => 'sanitize_text_field',
        'address' => 'sanitize_text_field',
        'city' => 'sanitize_text_field',
        'state' => 'sanitize_text_field',
        'cep' => 'sanitize_text_field',
        'curso_id' => 'absint',
        'turma_id' => 'absint',
        'payment_method' => 'sanitize_key',
        'gateway' => 'sanitize_key',
        'installments' => 'absint',
        'cupom' => 'sanitize_text_field',
    );
    
    foreach ($allowed_fields as $field => $sanitizer) {
        if (isset($data[$field])) {
            $sanitized[$field] = call_user_func($sanitizer, $data[$field]);
        }
    }
    
    // Validação específica de CPF
    if (isset($sanitized['cpf']) && !cursos_validate_cpf($sanitized['cpf'])) {
        $sanitized['cpf_valid'] = false;
    } else {
        $sanitized['cpf_valid'] = true;
    }
    
    return $sanitized;
}

/**
 * Remove dados sensíveis dos logs de erro
 */
function cursos_clean_error_log($message) {
    // Padrões de dados sensíveis para remover
    $patterns = array(
        '/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/', // CPF
        '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/', // Cartão
        '/\b\d{3}\b(?=\s*$)/', // CVV (3 dígitos no final)
        '/password["\']?\s*[:=]\s*["\'][^"\']+["\']/i', // Senhas
    );
    
    $replacements = array(
        '[CPF_REMOVIDO]',
        '[CARTAO_REMOVIDO]',
        '[CVV_REMOVIDO]',
        'password: [SENHA_REMOVIDA]',
    );
    
    return preg_replace($patterns, $replacements, $message);
}

// ========================================
// SEGURANÇA - INDICADOR VISUAL HTTPS
// ========================================

/**
 * Adiciona badge de segurança no checkout
 */
function cursos_security_badge() {
    if (!is_ssl()) {
        return '';
    }
    
    return '
    <div class="security-badge" style="display: flex; align-items: center; gap: 8px; padding: 12px 16px; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 1px solid #28a745; border-radius: 8px; margin-bottom: 20px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <span style="color: #155724; font-weight: 600; font-size: 14px;">
            Conexão Segura - Seus dados estão protegidos com criptografia SSL/TLS
        </span>
    </div>';
}

/**
 * Exibe informação de segurança no rodapé do checkout
 */
function cursos_security_footer_info() {
    return '
    <div class="security-info" style="text-align: center; padding: 20px; margin-top: 30px; border-top: 1px solid #e9ecef; color: #6c757d; font-size: 12px;">
        <div style="display: flex; justify-content: center; align-items: center; gap: 20px; flex-wrap: wrap; margin-bottom: 10px;">
            <span style="display: flex; align-items: center; gap: 5px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Dados Criptografados
            </span>
            <span style="display: flex; align-items: center; gap: 5px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Pagamento Seguro
            </span>
            <span style="display: flex; align-items: center; gap: 5px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Ambiente Protegido
            </span>
        </div>
        <p style="margin: 0;">Seus dados de cartão são enviados diretamente ao gateway de pagamento e nunca são armazenados em nossos servidores.</p>
    </div>';
}

// ========================================
// AUTOMAÇÃO DE CATEGORIA POR DISPONIBILIDADE
// ========================================

/**
 * Sincronizar disponibilidade de um curso com a categoria configurada
 * 
 * @param int $curso_id ID do curso
 * @return bool True se houve alteração
 */
function cursos_sync_curso_availability($curso_id) {
    $category_id = get_theme_mod('cursos_availability_category', '');
    
    // Se não configurado, não fazer nada
    if (empty($category_id)) {
        return false;
    }
    
    // Verificar se o curso existe e está publicado
    $curso = get_post($curso_id);
    if (!$curso || $curso->post_status !== 'publish' || $curso->post_type !== 'curso') {
        return false;
    }
    
    // Verificar se está marcado como "Sem turma aberta"
    $sem_turma_aberta = get_post_meta($curso_id, '_curso_sem_turma_aberta', true);
    if ($sem_turma_aberta === '1') {
        // Curso marcado manualmente como sem turma = sem vagas
        $current_terms = wp_get_object_terms($curso_id, 'categoria_curso', array('fields' => 'ids'));
        if (!is_wp_error($current_terms) && in_array((int) $category_id, $current_terms)) {
            wp_remove_object_terms($curso_id, (int) $category_id, 'categoria_curso');
            error_log("[Cursos Automation] Curso #{$curso_id} '" . get_the_title($curso_id) . "' removido (sem turma aberta)");
            return true;
        }
        return false;
    }
    
    // Verificar disponibilidade de vagas
    $vagas_curso = cursos_check_curso_vagas($curso_id);
    $menor_vagas_turma = cursos_get_curso_menor_vagas($curso_id);
    
    // Curso tem vagas se:
    // 1. Não tem limite global OU tem vagas restantes no limite global
    // 2. E tem pelo menos uma turma com vagas (se tiver turmas)
    $tem_vagas = true;
    
    // Verificar limite global
    if ($vagas_curso['tem_limite'] && !$vagas_curso['disponivel']) {
        $tem_vagas = false;
    }
    
    // Se passou no global, verificar turmas
    if ($tem_vagas) {
        $turmas = get_post_meta($curso_id, '_curso_turmas', true);
        if (!empty($turmas) && is_array($turmas)) {
            $alguma_turma_disponivel = false;
            foreach ($turmas as $turma) {
                if (empty($turma['id'])) continue;
                $check = cursos_check_turma_vagas($curso_id, $turma['id']);
                if ($check['disponivel']) {
                    $alguma_turma_disponivel = true;
                    break;
                }
            }
            // Se todas as turmas estão esgotadas, curso não tem vagas
            if (!$alguma_turma_disponivel && count(array_filter($turmas, function($t) { return !empty($t['id']); })) > 0) {
                $tem_vagas = false;
            }
        }
    }
    
    // Obter categorias atuais do curso
    $current_terms = wp_get_object_terms($curso_id, 'categoria_curso', array('fields' => 'ids'));
    if (is_wp_error($current_terms)) {
        $current_terms = array();
    }
    $has_category = in_array((int) $category_id, $current_terms);
    
    $changed = false;
    
    if ($tem_vagas && !$has_category) {
        // Adicionar à categoria
        wp_set_object_terms($curso_id, (int) $category_id, 'categoria_curso', true);
        $changed = true;
        error_log("[Cursos Automation] Curso #{$curso_id} '" . get_the_title($curso_id) . "' adicionado à categoria de disponíveis");
    } elseif (!$tem_vagas && $has_category) {
        // Remover da categoria
        wp_remove_object_terms($curso_id, (int) $category_id, 'categoria_curso');
        $changed = true;
        error_log("[Cursos Automation] Curso #{$curso_id} '" . get_the_title($curso_id) . "' removido da categoria de disponíveis");
    }
    
    return $changed;
}

/**
 * Sincronizar todos os cursos publicados
 * 
 * @return int Número de cursos atualizados
 */
function cursos_sync_all_courses_availability() {
    $category_id = get_theme_mod('cursos_availability_category', '');
    
    if (empty($category_id)) {
        return 0;
    }
    
    $cursos = get_posts(array(
        'post_type'      => 'curso',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ));
    
    $updated = 0;
    foreach ($cursos as $curso_id) {
        if (cursos_sync_curso_availability($curso_id)) {
            $updated++;
        }
    }
    
    error_log("[Cursos Automation] Sincronização completa: {$updated} cursos atualizados de " . count($cursos) . " total");
    
    return $updated;
}

/**
 * Sincronizar após pagamento confirmado
 */
add_action('cursos_payment_completed', function($order_id, $order_data) {
    if (!get_theme_mod('cursos_automation_on_payment', true)) {
        return;
    }
    
    $curso_id = null;
    
    // Tentar obter o curso_id dos dados do pedido
    if (!empty($order_data['curso_id'])) {
        $curso_id = intval($order_data['curso_id']);
    } elseif (is_numeric($order_id)) {
        // Tentar buscar na tabela de pedidos
        global $wpdb;
        $table_name = $wpdb->prefix . 'cursos_orders';
        $curso_id = $wpdb->get_var($wpdb->prepare(
            "SELECT curso_id FROM $table_name WHERE id = %d",
            $order_id
        ));
    }
    
    if ($curso_id) {
        cursos_sync_curso_availability($curso_id);
    }
}, 20, 2);

/**
 * Sincronizar após pagamento cancelado/reembolsado (libera vaga)
 */
add_action('cursos_payment_cancelled', function($order_id, $order_data) {
    if (!get_theme_mod('cursos_automation_on_payment', true)) {
        return;
    }
    
    $curso_id = null;
    
    if (!empty($order_data['curso_id'])) {
        $curso_id = intval($order_data['curso_id']);
    } elseif (is_numeric($order_id)) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cursos_orders';
        $curso_id = $wpdb->get_var($wpdb->prepare(
            "SELECT curso_id FROM $table_name WHERE id = %d",
            $order_id
        ));
    }
    
    if ($curso_id) {
        cursos_sync_curso_availability($curso_id);
    }
}, 20, 2);

/**
 * Agendar cron job para sincronização diária
 */
add_action('wp', function() {
    if (get_theme_mod('cursos_automation_daily_sync', true) && !wp_next_scheduled('cursos_daily_availability_sync')) {
        wp_schedule_event(time(), 'daily', 'cursos_daily_availability_sync');
    }
    
    // Remover agendamento se desativado
    if (!get_theme_mod('cursos_automation_daily_sync', true) && wp_next_scheduled('cursos_daily_availability_sync')) {
        wp_clear_scheduled_hook('cursos_daily_availability_sync');
    }
});

add_action('cursos_daily_availability_sync', 'cursos_sync_all_courses_availability');

/**
 * Limpar agendamento ao desativar tema
 */
add_action('switch_theme', function() {
    wp_clear_scheduled_hook('cursos_daily_availability_sync');
});

/**
 * Adicionar botão de sincronização manual na lista de cursos
 */
add_action('admin_notices', function() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'curso' || $screen->base !== 'edit') {
        return;
    }
    
    $category_id = get_theme_mod('cursos_availability_category', '');
    if (empty($category_id)) {
        return;
    }
    
    $category = get_term($category_id, 'categoria_curso');
    $category_name = $category && !is_wp_error($category) ? $category->name : 'Configurada';
    
    // Processar sincronização manual
    if (isset($_GET['sync_availability']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'sync_availability')) {
        $updated = cursos_sync_all_courses_availability();
        echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Sincronização concluída!</strong> ' . $updated . ' curso(s) atualizado(s).</p></div>';
    } else {
        $sync_url = wp_nonce_url(admin_url('edit.php?post_type=curso&sync_availability=1'), 'sync_availability');
        echo '<div class="notice notice-info">';
        echo '<p><strong>🔄 Automação de Vagas Ativa:</strong> Cursos com vagas são adicionados à categoria "' . esc_html($category_name) . '". ';
        echo '<a href="' . esc_url($sync_url) . '" class="button button-small" style="margin-left: 10px;">Sincronizar Agora</a></p>';
        echo '</div>';
    }
});

/**
 * Sincronizar ao salvar/atualizar curso
 */
add_action('save_post_curso', function($post_id, $post, $update) {
    // Ignorar salvamentos automáticos
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Ignorar revisões
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    // Sincronizar apenas se estiver publicado
    if ($post->post_status === 'publish') {
        cursos_sync_curso_availability($post_id);
    }
}, 20, 3);

// ========================================
// Migração automática de documentos órfãos
// ========================================
add_action('admin_init', 'cursos_migrate_orphan_documents');
function cursos_migrate_orphan_documents() {
    // Rodar apenas uma vez
    if (get_option('cursos_orphan_docs_migrated_v2')) {
        return;
    }
    
    global $wpdb;
    $docs_table = $wpdb->prefix . 'cursos_student_documents';
    $orders_table = $wpdb->prefix . 'cursos_orders';
    
    // Buscar documentos órfãos (sem curso_id e sem order_id)
    $orphan_docs = $wpdb->get_results("
        SELECT id, customer_email, customer_cpf 
        FROM $docs_table 
        WHERE (curso_id IS NULL OR curso_id = 0)
        AND (order_id IS NULL OR order_id = 0)
    ");
    
    $migrated = 0;
    
    foreach ($orphan_docs as $doc) {
        // Tentar encontrar pedido pelo email ou CPF
        $order = null;
        
        // Primeiro tentar pelo email
        if (!empty($doc->customer_email)) {
            $order = $wpdb->get_row($wpdb->prepare("
                SELECT id, curso_id, amount 
                FROM $orders_table 
                WHERE customer_email = %s
                ORDER BY created_at DESC 
                LIMIT 1
            ", $doc->customer_email));
        }
        
        // Se não encontrou, tentar pelo CPF
        if (!$order && !empty($doc->customer_cpf)) {
            $order = $wpdb->get_row($wpdb->prepare("
                SELECT id, curso_id, amount 
                FROM $orders_table 
                WHERE customer_cpf = %s
                ORDER BY created_at DESC 
                LIMIT 1
            ", $doc->customer_cpf));
        }
        
        if ($order) {
            $wpdb->update(
                $docs_table, 
                array(
                    'order_id' => $order->id,
                    'curso_id' => $order->curso_id,
                    'amount' => $order->amount
                ),
                array('id' => $doc->id),
                array('%d', '%d', '%f'),
                array('%d')
            );
            $migrated++;
        }
    }
    
    // Marcar como migrado
    update_option('cursos_orphan_docs_migrated_v2', true);
    
    // Log da migração
    if ($migrated > 0) {
        error_log("Cursos: Migração automática - $migrated documentos órfãos vinculados a pedidos");
    }
}

/**
 * ========================================
 * HARDENING DE SEGURANÇA WORDPRESS
 * Para sites com checkout / pagamentos
 * ========================================
 */

/* 1. Ocultar barra administrativa */
add_filter('show_admin_bar', '__return_false');

/* 2. Remover versão do WordPress */
remove_action('wp_head', 'wp_generator');

/* 3. Bloquear enumeração de usuários */
if (!is_admin()) {
    if (isset($_GET['author'])) {
        wp_redirect(home_url());
        exit;
    }
}

/* 4. Bloquear XML-RPC */
add_filter('xmlrpc_enabled', '__return_false');

/* 5. Restringir REST API a usuários logados (com exceções para webhooks) */
add_filter('rest_authentication_errors', function($result) {
    if (!empty($result)) {
        return $result;
    }

    // Identificar rota REST
    $rest_route = '';
    if (isset($_GET['rest_route'])) {
        $rest_route = $_GET['rest_route'];
    } elseif (isset($GLOBALS['wp']->query_vars['rest_route'])) {
        $rest_route = $GLOBALS['wp']->query_vars['rest_route'];
    }

    // Fallback: extrair de REQUEST_URI
    if (empty($rest_route)) {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $needle = '/' . rest_get_url_prefix(); // "/wp-json"
        $pos = strpos($request_uri, $needle);
        if ($pos !== false) {
            $rest_route = substr($request_uri, $pos + strlen($needle));
            $rest_route = strtok($rest_route, '?');
        }
    }

    // Rotas públicas permitidas
    $public_routes = array(
        '/cursos/v1/asaas-webhook',
        '/cursos/v1/pagseguro-webhook',
        '/cursos/v1/orders',
    );

    foreach ($public_routes as $route) {
        if (strpos($rest_route, $route) === 0) {
            return null;
        }
    }

    if (!is_user_logged_in()) {
        return new WP_Error('rest_forbidden', 'REST API bloqueada.', ['status' => 401]);
    }

    return $result;
});

/* 6. Bloquear execução de PHP em uploads */
add_action('init', function() {
    $upload_dir = wp_upload_dir();
    $file = $upload_dir['basedir'] . '/.htaccess';
    if (!file_exists($file)) {
        file_put_contents($file, "php_flag engine off\n");
    }
});

/* 7. Proteger login contra brute force */
add_filter('authenticate', function($user, $username, $password) {
    if (empty($username) || empty($password)) return null;
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'login_fail_' . md5($ip);
    $fails = (int) get_transient($key);
    if ($fails >= 5) {
        return new WP_Error('blocked', 'Muitas tentativas. Tente novamente em 15 minutos.');
    }
    if (is_wp_error($user)) {
        set_transient($key, $fails + 1, 15 * MINUTE_IN_SECONDS);
    } else {
        delete_transient($key);
    }
    return $user;
}, 30, 3);

/* 8. Bloquear acesso direto a wp-config */
add_filter('rest_pre_dispatch', function($result, $server, $request) {
    if (strpos($request->get_route(), 'wp-config') !== false) {
        return new WP_Error('forbidden', 'Acesso bloqueado', ['status' => 403]);
    }
    return $result;
}, 10, 3);

/* 9. Headers de segurança */
add_action('send_headers', function() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: no-referrer-when-downgrade');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
});

/* 10. Desativar editor de arquivos */
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

/**
 * ========================================
 * BOTÃO FLUTUANTE DO WHATSAPP (via wp_footer)
 * Funciona mesmo com footer do Elementor
 * ========================================
 */
add_action('wp_footer', function() {
    // Verificar se está habilitado
    $whatsapp_show = get_theme_mod('cursos_whatsapp_show', true);
    if (!$whatsapp_show) {
        return;
    }

    // Buscar número: Customizer primeiro, fallback para opções legadas
    $whatsapp_number = get_theme_mod('cursos_whatsapp_number', '');
    if (empty($whatsapp_number)) {
        $whatsapp_number = get_option('cursos_whatsapp', '');
    }
    if (empty($whatsapp_number)) {
        return; // Sem número configurado, não exibe
    }

    // Limpar número (só dígitos)
    $whatsapp_clean = preg_replace('/[^0-9]/', '', $whatsapp_number);

    // Mensagem e posição
    $whatsapp_message = get_theme_mod('cursos_whatsapp_message', 'Olá! Gostaria de mais informações sobre os cursos.');
    $whatsapp_position = get_theme_mod('cursos_whatsapp_position', 'right');
    $position_css = ($whatsapp_position === 'left') ? 'left: 1.5rem;' : 'right: 1.5rem;';

    // URL do WhatsApp
    $whatsapp_url = 'https://wa.me/' . $whatsapp_clean;
    if (!empty($whatsapp_message)) {
        $whatsapp_url .= '?text=' . rawurlencode($whatsapp_message);
    }
    ?>
    <a href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer"
       id="whatsapp-float-btn"
       style="
           position: fixed;
           bottom: 1.5rem;
           <?php echo $position_css; ?>
           z-index: 99999;
           width: 56px;
           height: 56px;
           border-radius: 50%;
           background: #25D366;
           display: flex;
           align-items: center;
           justify-content: center;
           box-shadow: 0 4px 12px rgba(0,0,0,0.3);
           transition: transform 0.3s ease, box-shadow 0.3s ease;
           text-decoration: none;
       "
       onmouseover="this.style.transform='scale(1.1)';this.style.boxShadow='0 6px 20px rgba(0,0,0,0.4)'"
       onmouseout="this.style.transform='scale(1)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.3)'"
       aria-label="Fale conosco pelo WhatsApp">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="white">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </a>
    <?php
}, 999); // Prioridade alta para garantir que aparece por último
