<?php
/**
 * Template Part: Featured Courses Section
 * Exibe cursos em destaque na home - Igual ao React/Lovable
 * 
 * @package CursosTheme
 */

if (!defined('ABSPATH')) {
    exit;
}

// Buscar cursos em destaque
$args = array(
    'post_type' => 'curso',
    'posts_per_page' => 6,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => '_curso_destaque',
            'value' => '1',
            'compare' => '='
        )
    )
);

// Ordenar cursos com vagas abertas primeiro
add_filter('posts_clauses', 'cursos_orderby_disponibilidade', 10, 2);
$featured_query = new WP_Query($args);

// Se não houver cursos em destaque, buscar os mais recentes
if (!$featured_query->have_posts()) {
    $args = array(
        'post_type' => 'curso',
        'posts_per_page' => 6,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    );
    $featured_query = new WP_Query($args);
}
remove_filter('posts_clauses', 'cursos_orderby_disponibilidade', 10, 2);

// Se ainda não houver cursos, não exibe a seção
if (!$featured_query->have_posts()) {
    return;
}
?>

<section class="featured-courses-section py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <span class="inline-block px-4 py-2 bg-primary/10 text-primary rounded-full text-sm font-medium mb-4">
                Cursos em Destaque
            </span>
            <h2 class="font-display text-3xl md:text-4xl font-bold text-foreground mb-4">
                Escolha o curso ideal para você
            </h2>
            <p class="text-muted-foreground text-lg max-w-2xl mx-auto">
                Cursos desenvolvidos por especialistas para impulsionar sua carreira
            </p>
        </div>

        <div class="courses-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php while ($featured_query->have_posts()): $featured_query->the_post(); 
                $meta = cursos_get_curso_meta(get_the_ID());
                $preco = floatval($meta['preco']);
                $preco_original = floatval($meta['preco_original']);
                $desconto = ($preco_original > $preco && $preco_original > 0) 
                    ? round((($preco_original - $preco) / $preco_original) * 100) 
                    : 0;
            ?>
                <article class="course-card bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-300">
                    <!-- Imagem -->
                    <div class="course-image relative">
                        <?php if (has_post_thumbnail()): ?>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('curso-featured', array('class' => 'w-full h-48 object-cover')); ?>
                            </a>
                        <?php else: ?>
                            <a href="<?php the_permalink(); ?>" class="block h-48 bg-gray-100 flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M6 12v5c3 3 9 3 12 0v-5"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                        
                        <?php 
                        // Verificar vagas
                        $menor_vagas = cursos_get_curso_menor_vagas(get_the_ID());
                        $sem_turma = get_post_meta(get_the_ID(), '_curso_sem_turma_aberta', true);
                        $esgotado = ($menor_vagas !== null && $menor_vagas <= 0);
                        $ultimas_vagas = ($menor_vagas !== null && $menor_vagas > 0 && $menor_vagas <= 5);
                        $vagas_abertas = function_exists('cursos_curso_tem_vagas_abertas') ? cursos_curso_tem_vagas_abertas(get_the_ID()) : ($menor_vagas !== null && $menor_vagas > 5);
                        ?>
                        
                        <?php if ($vagas_abertas || $ultimas_vagas): ?>
                        <span class="absolute bottom-3 right-3 text-white px-3 py-1 rounded text-sm font-bold uppercase" style="background: linear-gradient(135deg, #22c55e, #16a34a); padding: 0.35rem 0.75rem;">
                            VAGAS ABERTAS
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($desconto > 0 && $sem_turma !== '1'): ?>
                        <span class="absolute top-3 right-3 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold">
                            <?php echo $desconto; ?>% OFF
                        </span>
                        <?php endif; ?>
                        
                        <?php 
                        $tipos = get_the_terms(get_the_ID(), 'tipo_curso');
                        if ($tipos && !is_wp_error($tipos)):
                        ?>
                        <span class="absolute top-3 left-3 bg-primary text-white px-2 py-1 rounded text-xs font-semibold">
                            <?php echo esc_html($tipos[0]->name); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Conteúdo -->
                    <div class="course-content p-5">
                        <!-- Meta -->
                        <div class="course-meta flex items-center gap-4 text-sm text-muted-foreground mb-3">
                            <?php if (!empty($meta['duracao'])): ?>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?php echo esc_html($meta['duracao']); ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($meta['alunos'])): ?>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                </svg>
                                <?php echo number_format($meta['alunos'], 0, ',', '.'); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Título -->
                        <h3 class="course-title text-lg font-bold mb-2 line-clamp-2">
                            <a href="<?php the_permalink(); ?>" class="hover:text-primary transition-colors">
                                <?php the_title(); ?>
                            </a>
                        </h3>
                        
                        <!-- Descrição curta -->
                        <?php if (has_excerpt()): ?>
                        <p class="course-excerpt text-sm text-muted-foreground mb-4 line-clamp-2">
                            <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                        </p>
                        <?php endif; ?>
                        
                        <!-- Instrutor -->
                        <?php if (!empty($meta['instrutor_nome'])): ?>
                        <div class="course-instructor flex items-center gap-2 mb-4">
                            <?php if (!empty($meta['instrutor_foto'])): ?>
                                <img src="<?php echo esc_url($meta['instrutor_foto']); ?>" alt="" class="w-8 h-8 rounded-full object-cover">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <span class="text-sm text-muted-foreground"><?php echo esc_html($meta['instrutor_nome']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Preço e CTA -->
                        <div class="course-footer flex items-center justify-between pt-4 border-t">
                            <div class="course-price">
                                <?php if ($preco_original > $preco): ?>
                                <span class="text-sm text-muted-foreground line-through">
                                    <?php echo cursos_format_price($preco_original); ?>
                                </span>
                                <?php endif; ?>
                                <span class="text-xl font-bold text-primary">
                                    <?php echo cursos_format_price($preco); ?>
                                </span>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="btn btn-primary py-2 px-4 text-sm">
                                Ver Curso
                            </a>
                        </div>
                    </div>
                </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <div class="text-center mt-12">
            <a href="<?php echo esc_url(home_url('/cursos')); ?>" class="btn btn-outline h-12 px-8 text-base gap-2">
                Ver Todos os Cursos
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<style>
.featured-courses-section {
    padding: 5rem 0;
    background-color: var(--background, #ffffff);
}

.featured-courses-section .container {
    max-width: var(--container-width, 1200px);
}

.featured-courses-section .inline-block {
    display: inline-block;
    padding: 0.5rem 1rem;
    background-color: rgba(127, 11, 13, 0.1);
    color: var(--primary, #7f0b0d);
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 1rem;
}

.featured-courses-section h2 {
    font-family: var(--heading-font, 'Noto Sans', sans-serif);
    font-size: 2rem;
    font-weight: 700;
    color: var(--text, #333333);
    margin-bottom: 1rem;
}

.featured-courses-section .text-center > p {
    color: var(--text-secondary, #6b7280);
    font-size: 1.125rem;
    max-width: 42rem;
    margin: 0 auto;
}

.courses-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
}

.course-card {
    background-color: var(--background, #ffffff);
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.course-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
}

.course-image {
    position: relative;
}

.course-image img {
    width: 100%;
    height: 12rem;
    object-fit: cover;
}

.course-content {
    padding: 1.25rem;
}

.course-title a {
    color: var(--text, #333333);
    text-decoration: none;
}

.course-title a:hover {
    color: var(--primary, #7f0b0d);
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.course-footer {
    border-top: 1px solid var(--border, #e5e7eb);
    padding-top: 1rem;
}

.course-price {
    display: flex;
    flex-direction: column;
}

.featured-courses-section .btn-outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    height: 3rem;
    padding: 0 2rem;
    font-size: 1rem;
    font-weight: 600;
    background-color: transparent;
    color: var(--primary, #7f0b0d);
    border: 2px solid var(--primary, #7f0b0d);
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.featured-courses-section .btn-outline:hover {
    background-color: var(--primary, #7f0b0d);
    color: #ffffff;
}

@media (min-width: 768px) {
    .courses-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .featured-courses-section h2 {
        font-size: 2.5rem;
    }
}

@media (min-width: 1024px) {
    .courses-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>
