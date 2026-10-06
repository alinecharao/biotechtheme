<?php
/**
 * Template para listagem de cursos (archive)
 * Replica exatamente o layout do React/Lovable
 * COM FILTROS AJAX (sem recarregar página)
 * 
 * @package CursosTheme
 */

get_header();

// Parâmetros de filtro via GET (para carregamento inicial e compartilhamento de URL)
$search_term = isset($_GET['busca']) ? sanitize_text_field($_GET['busca']) : '';
$selected_category = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : 'all';
$order_by = isset($_GET['ordenar']) ? sanitize_text_field($_GET['ordenar']) : 'recent';
$current_page = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$per_page = 9;

// Argumentos da query
$args = array(
    'post_type' => 'curso',
    'posts_per_page' => $per_page,
    'paged' => $current_page,
    'post_status' => 'publish',
    'suppress_filters' => false,
    'no_found_rows' => false,
);

// Busca por texto
if (!empty($search_term)) {
    $args['s'] = $search_term;
}

// Filtro por categoria
if ($selected_category !== 'all') {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'categoria_curso',
            'field' => 'slug',
            'terms' => $selected_category,
        ),
    );
}

// Ordenação
switch ($order_by) {
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
$cursos_query = new WP_Query($args);
remove_filter('posts_clauses', 'cursos_orderby_disponibilidade', 10, 2);
$total_pages = $cursos_query->max_num_pages;
$total_courses = $cursos_query->found_posts;

// Obter todas as categorias para o filtro
$categorias = get_terms(array(
    'taxonomy' => 'categoria_curso',
    'hide_empty' => true,
));

// Helper para formatar preço (usa função do functions.php)
if (!function_exists('cursos_format_price_brl')) {
    function cursos_format_price_brl($price) {
        return 'R$ ' . number_format(floatval($price), 2, ',', '.');
    }
}
?>

<main id="main-content">
    <!-- Hero Section - Igual ao Lovable -->
    <div class="cursos-hero">
        <div class="container">
            <h1 class="cursos-hero-title animate-fade-in">Nossos Cursos</h1>
            <p class="cursos-hero-description">
                Explore nossa variedade de cursos de medicina veterinária e encontre 
                o programa ideal para sua especialização.
            </p>
        </div>
    </div>

<!-- Content Section - AJAX Container -->
    <section class="cursos-content py-12">
        <div class="container">
            <!-- AJAX Container com data attributes para estado -->
            <div id="cursos-ajax-container" 
                 data-page="<?php echo esc_attr($current_page); ?>" 
                 data-categoria="<?php echo esc_attr($selected_category); ?>"
                 data-busca="<?php echo esc_attr($search_term); ?>"
                 data-ordenar="<?php echo esc_attr($order_by); ?>"
                 data-per-page="<?php echo esc_attr($per_page); ?>">
                
            <!-- Filters -->
            <div class="cursos-filters">
                <!-- Category Tags - Agora são botões AJAX -->
                <div class="cursos-tags">
                    <button type="button" class="cursos-tag <?php echo $selected_category === 'all' ? 'active' : ''; ?>" data-categoria="all">
                        Todas as Categorias
                    </button>
                    <?php foreach ($categorias as $cat): ?>
                    <button type="button" class="cursos-tag <?php echo $selected_category === $cat->slug ? 'active' : ''; ?>" data-categoria="<?php echo esc_attr($cat->slug); ?>">
                        <?php echo esc_html($cat->name); ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Search + Order Row -->
                <div class="cursos-filters-row">
                    <div class="cursos-search-form">
                        <div class="cursos-search-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input type="text" id="cursos-search-input" placeholder="Buscar cursos..." value="<?php echo esc_attr($search_term); ?>" class="cursos-search-input">
                        </div>
                    </div>

                    <select id="cursos-order-select" class="cursos-order-select">
                        <option value="recent" <?php selected($order_by, 'recent'); ?>>Mais Recentes</option>
                        <option value="popular" <?php selected($order_by, 'popular'); ?>>Mais Populares</option>
                        <option value="rating" <?php selected($order_by, 'rating'); ?>>Melhor Avaliados</option>
                        <option value="price_asc" <?php selected($order_by, 'price_asc'); ?>>Menor Preço</option>
                        <option value="price_desc" <?php selected($order_by, 'price_desc'); ?>>Maior Preço</option>
                    </select>
                </div>
            </div>

            <!-- Results -->
            <div class="cursos-results">
                <p class="cursos-count" id="cursos-count">
                    <?php echo $total_courses; ?> curso<?php echo $total_courses !== 1 ? 's' : ''; ?> encontrado<?php echo $total_courses !== 1 ? 's' : ''; ?>
                </p>

                <!-- Grid Container - será atualizado via AJAX -->
                <div id="cursos-grid-container" class="cursos-grid-wrapper">
                    <?php if ($cursos_query->have_posts()): ?>
                    <div class="cursos-grid">
                        <?php while ($cursos_query->have_posts()): $cursos_query->the_post(); 
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
                            
                            // Debug: log para verificar professores encontrados
                            if (defined('WP_DEBUG') && WP_DEBUG && WP_DEBUG_LOG) {
                                $prof_ids_raw = get_post_meta($curso_id, '_curso_professores', true);
                                $prof_id_old = get_post_meta($curso_id, '_curso_professor', true);
                                error_log("[Archive Debug] Curso: " . get_the_title() . " (ID: $curso_id)");
                                error_log("[Archive Debug] _curso_professores raw: " . print_r($prof_ids_raw, true));
                                error_log("[Archive Debug] _curso_professor old: " . print_r($prof_id_old, true));
                                error_log("[Archive Debug] Professores válidos: " . count($professores));
                                foreach ($professores as $idx => $p) {
                                    error_log("[Archive Debug] Prof[$idx]: ID={$p['id']}, Nome={$p['nome']}, Titulo={$p['titulo']}");
                                }
                            }
                            
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
                            
                            // Calcula economia
                            $savings = ($curso_preco_original && $curso_preco_original > $curso_preco) ? ($curso_preco_original - $curso_preco) : 0;
                            $has_discount = $savings > 0;
                            
                            // Popular (mais de 1000 alunos)
                            $is_popular = intval($curso_alunos) >= 1000;
                            
                            // Link
                            $curso_link = get_permalink($curso_id);
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
                                    
                                    <!-- Badge de Tipo (esquerda) -->
                                    <span class="curso-card-badge curso-card-badge-type"><?php echo esc_html($type_label); ?></span>
                                    
                                    <!-- Badge Esgotado, Últimas Vagas OU Popular (direita) -->
                                    <?php 
                                    $vagas_curso = cursos_check_curso_vagas($curso_id);
                                    $curso_esgotado = $vagas_curso['tem_limite'] && !$vagas_curso['disponivel'];
                                    $menor_vagas = cursos_get_curso_menor_vagas($curso_id);
                                    
                                    // Prioridade: Esgotado > Últimas Vagas Curso > Últimas Vagas Turma > Popular
                                    if ($vagas_curso['tem_limite'] && $vagas_curso['vagas_restantes'] !== null) {
                                        $vagas_display = $vagas_curso['vagas_restantes'];
                                    } else {
                                        $vagas_display = $menor_vagas;
                                    }
                                    
                                    $ultimas_vagas = ($vagas_display !== null && $vagas_display > 0 && $vagas_display <= 5);
                                    $sem_turma_aberta = get_post_meta($curso_id, '_curso_sem_turma_aberta', true);
                                    $tem_vagas_abertas = cursos_curso_tem_vagas_abertas($curso_id);
                                    ?>
                                    
                                    <?php if ($tem_vagas_abertas): ?>
                                    <span class="curso-card-badge curso-card-badge-vagas-abertas">
                                        VAGAS ABERTAS
                                    </span>
                                    <?php endif; ?>

                                    <!-- Badge de Economia (inferior) -->
                                    <?php if ($has_discount): ?>
                                    <span class="curso-card-badge curso-card-badge-savings">
                                        ECONOMIZE <?php echo cursos_format_price_brl($savings); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                            
                            <!-- Meta Info Row -->
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
                            // Próximas turmas
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
                                
                                <!-- Instrutor(es) -->
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
                                                $prof_nome = !empty($prof['nome']) ? $prof['nome'] : 'Professor';
                                                $prof_titulo = !empty($prof['titulo']) ? $prof['titulo'] : '';
                                            ?>
                                                <div class="avatar-wrapper">
                                                    <?php if (!empty($prof['foto'])): ?>
                                                    <img src="<?php echo esc_url($prof['foto']); ?>" 
                                                         alt="<?php echo esc_attr($prof_nome); ?>" 
                                                         class="stacked-avatar">
                                                    <?php else: ?>
                                                    <div class="stacked-avatar-placeholder">
                                                        <?php echo mb_strtoupper(mb_substr($prof_nome, 0, 1)); ?>
                                                    </div>
                                                    <?php endif; ?>
                                                    <div class="avatar-tooltip">
                                                        <span class="tooltip-name"><?php echo esc_html($prof_nome); ?></span>
                                                        <?php if ($prof_titulo): ?>
                                                        <span class="tooltip-title"><?php echo esc_html($prof_titulo); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            
                                            <?php if ($remaining > 0): ?>
                                            <div class="stacked-more">+<?php echo $remaining; ?></div>
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
                                    <?php else: 
                                        // Professor único
                                        $prof = $professores[0];
                                        $prof_nome = !empty($prof['nome']) ? $prof['nome'] : 'Professor';
                                        $prof_titulo = !empty($prof['titulo']) ? $prof['titulo'] : '';
                                    ?>
                                    <div class="curso-card-instructor">
                                        <?php if (!empty($prof['foto'])): ?>
                                        <img src="<?php echo esc_url($prof['foto']); ?>" alt="<?php echo esc_attr($prof_nome); ?>" class="instructor-image">
                                        <?php else: ?>
                                        <div class="instructor-image-placeholder">
                                            <?php echo mb_strtoupper(mb_substr($prof_nome, 0, 1)); ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="instructor-info">
                                            <span class="instructor-name"><?php echo esc_html($prof_nome); ?></span>
                                            <?php if ($prof_titulo): ?>
                                            <span class="instructor-title"><?php echo esc_html($prof_titulo); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Fallback: nenhum professor válido -->
                                    <div class="curso-card-instructor curso-card-instructor-undefined">
                                        <div class="instructor-image-placeholder">?</div>
                                        <div class="instructor-info">
                                            <span class="instructor-name">Professor não definido</span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Preço e Parcelas -->
                                <div class="curso-card-price-section">
                                    <div class="curso-card-price-info">
                                        <?php if ($has_discount): ?>
                                        <span class="price-original"><?php echo cursos_format_price_brl($curso_preco_original); ?></span>
                                        <?php endif; ?>
                                        <div class="price-row">
                                            <span class="price-current"><?php echo cursos_format_price_brl($curso_preco); ?></span>
                                            <span class="price-installments">ou <?php echo cursos_get_installment_display_text(floatval($curso_preco)); ?></span>
                                        </div>
                                    </div>

                                    <a href="<?php echo esc_url($curso_link); ?>" class="btn btn-primary btn-block">
                                        Matricule-se
                                    </a>
                                </div>
                            </div>
                        </article>
                        <?php endwhile; ?>
                    </div>
                    <?php wp_reset_postdata(); ?>
                    <?php else: ?>
                    <div class="cursos-no-results">
                        <p>Nenhum curso encontrado com os filtros selecionados.</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Paginação AJAX -->
                <?php if ($total_pages > 1): ?>
                <div id="cursos-pagination" class="cursos-pagination" data-total-pages="<?php echo esc_attr($total_pages); ?>">
                    <?php if ($current_page > 1): ?>
                    <button type="button" class="pagination-btn pagination-prev" data-page="<?php echo $current_page - 1; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                        Anterior
                    </button>
                    <?php endif; ?>
                    
                    <div class="pagination-numbers">
                        <?php
                        // Mostrar até 5 páginas
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if ($start_page > 1) {
                            echo '<button type="button" class="pagination-btn pagination-number" data-page="1">1</button>';
                            if ($start_page > 2) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active_class = ($i === $current_page) ? 'active' : '';
                            echo '<button type="button" class="pagination-btn pagination-number ' . $active_class . '" data-page="' . $i . '">' . $i . '</button>';
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                            echo '<button type="button" class="pagination-btn pagination-number" data-page="' . $total_pages . '">' . $total_pages . '</button>';
                        }
                        ?>
                    </div>
                    
                    <?php if ($current_page < $total_pages): ?>
                    <button type="button" class="pagination-btn pagination-next" data-page="<?php echo $current_page + 1; ?>">
                        Próxima
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            </div><!-- /#cursos-ajax-container -->
        </div>
    </section>
</main>

<?php get_footer(); ?>
