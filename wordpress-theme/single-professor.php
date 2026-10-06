<?php
/**
 * Template: Página Individual do Professor
 * 
 * @package CursosTheme
 */

get_header();

$cor_primaria = get_theme_mod('cursos_cor_primaria', '#7f0b0d');
$cor_secundaria = get_theme_mod('cursos_cor_secundaria', '#10b981');

// Meta fields do professor
$titulo = get_post_meta(get_the_ID(), '_professor_titulo', true);
$email = get_post_meta(get_the_ID(), '_professor_email', true);
$linkedin = get_post_meta(get_the_ID(), '_professor_linkedin', true);
$instagram = get_post_meta(get_the_ID(), '_professor_instagram', true);
$website = get_post_meta(get_the_ID(), '_professor_website', true);
$crmv = get_post_meta(get_the_ID(), '_professor_crmv', true);
$experiencia = get_post_meta(get_the_ID(), '_professor_experiencia', true);
$curriculo = get_post_meta(get_the_ID(), '_professor_curriculo_completo', true);
$especialidades = get_post_meta(get_the_ID(), '_professor_especialidades', true);
$foto = get_the_post_thumbnail_url(get_the_ID(), 'large');

// Fallback para foto
if (!$foto) {
    $foto = get_post_meta(get_the_ID(), '_professor_foto', true);
}

// Buscar cursos onde este professor está vinculado
$cursos_do_professor = get_posts(array(
    'post_type' => 'curso',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => '_curso_professores',
            'value' => get_the_ID(),
            'compare' => 'LIKE'
        )
    )
));

// Preparar descrição para meta tag
$meta_description = $experiencia ? wp_trim_words(strip_tags($experiencia), 25) : 'Conheça ' . get_the_title() . ', professor especializado em cursos de medicina veterinária.';
?>

<!-- Schema.org Person markup -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Person",
    "name": "<?php echo esc_js(get_the_title()); ?>",
    <?php if ($titulo): ?>"jobTitle": "<?php echo esc_js($titulo); ?>",<?php endif; ?>
    <?php if ($foto): ?>"image": "<?php echo esc_url($foto); ?>",<?php endif; ?>
    <?php if ($experiencia): ?>"description": "<?php echo esc_js(wp_trim_words(strip_tags($experiencia), 50)); ?>",<?php endif; ?>
    <?php if ($email): ?>"email": "<?php echo esc_js($email); ?>",<?php endif; ?>
    "url": "<?php echo esc_url(get_permalink()); ?>",
    "sameAs": [
        <?php 
        $social_links = array_filter([$linkedin, $instagram, $website]);
        echo '"' . implode('", "', array_map('esc_url', $social_links)) . '"';
        ?>
    ]
    <?php if (!empty($cursos_do_professor)): ?>
    ,"worksFor": {
        "@type": "EducationalOrganization",
        "name": "<?php echo esc_js(get_bloginfo('name')); ?>"
    }
    <?php endif; ?>
}
</script>

<style>
    .prof-single-hero {
        background: linear-gradient(135deg, <?php echo esc_attr($cor_primaria); ?> 0%, <?php echo esc_attr($cor_primaria); ?>dd 100%);
        padding: 80px 0;
        color: #fff;
    }
    .prof-single-hero .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        gap: 40px;
        align-items: center;
    }
    .prof-hero-avatar {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255,255,255,0.3);
        flex-shrink: 0;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    .prof-hero-avatar-placeholder {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 80px;
        flex-shrink: 0;
        border: 4px solid rgba(255,255,255,0.2);
    }
    .prof-hero-info h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    .prof-hero-titulo {
        font-size: 1.2rem;
        opacity: 0.9;
        margin-bottom: 10px;
    }
    .prof-hero-crmv {
        font-size: 0.95rem;
        opacity: 0.8;
        margin-bottom: 20px;
    }
    .prof-hero-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .prof-hero-badge {
        background: rgba(255,255,255,0.2);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .prof-single-content {
        padding: 60px 0;
        background: #f8fafc;
    }
    .prof-single-content .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 40px;
    }
    
    .prof-main-content section {
        background: #fff;
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .prof-main-content h2 {
        font-size: 1.4rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid <?php echo esc_attr($cor_primaria); ?>22;
    }
    .prof-bio-content {
        color: #4b5563;
        line-height: 1.8;
        font-size: 1rem;
    }
    .prof-bio-content p {
        margin-bottom: 15px;
    }
    .prof-curriculo-content {
        color: #4b5563;
        line-height: 1.8;
        white-space: pre-line;
    }
    
    /* Cursos Grid */
    .prof-cursos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    .prof-curso-card {
        background: #f8fafc;
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.2s;
        border: 1px solid #e5e7eb;
    }
    .prof-curso-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .prof-curso-card img {
        width: 100%;
        height: 140px;
        object-fit: cover;
    }
    .prof-curso-card-content {
        padding: 15px;
    }
    .prof-curso-card h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 8px;
        line-height: 1.4;
    }
    .prof-curso-card .tipo {
        display: inline-block;
        background: <?php echo esc_attr($cor_primaria); ?>15;
        color: <?php echo esc_attr($cor_primaria); ?>;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 10px;
    }
    .prof-curso-card .preco {
        font-size: 1.1rem;
        font-weight: 700;
        color: <?php echo esc_attr($cor_primaria); ?>;
    }
    .prof-curso-card a {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    
    /* Sidebar */
    .prof-sidebar {
        position: sticky;
        top: 100px;
        height: fit-content;
    }
    .prof-sidebar-card {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }
    .prof-sidebar-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto 15px;
        display: block;
        border: 3px solid <?php echo esc_attr($cor_primaria); ?>22;
    }
    .prof-sidebar-name {
        text-align: center;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 5px;
    }
    .prof-sidebar-titulo {
        text-align: center;
        font-size: 0.9rem;
        color: #6b7280;
        margin-bottom: 20px;
    }
    
    .prof-social-links {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    .prof-social-links a {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f3f4f6;
        color: #6b7280;
        transition: all 0.2s;
    }
    .prof-social-links a:hover {
        background: <?php echo esc_attr($cor_primaria); ?>;
        color: #fff;
        transform: translateY(-2px);
    }
    .prof-social-links svg {
        width: 20px;
        height: 20px;
    }
    
    .prof-contact-btn {
        display: block;
        width: 100%;
        padding: 12px;
        background: <?php echo esc_attr($cor_primaria); ?>;
        color: #fff;
        text-align: center;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.2s;
    }
    .prof-contact-btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }
    
    /* CTA Section */
    .prof-cta-section {
        padding: 80px 0;
        background: <?php echo esc_attr($cor_primaria); ?>;
        text-align: center;
    }
    .prof-cta-section h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 15px;
    }
    .prof-cta-section p {
        font-size: 1.1rem;
        color: rgba(255,255,255,0.9);
        margin-bottom: 30px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    .prof-cta-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 14px 32px;
        background: #fff;
        color: <?php echo esc_attr($cor_primaria); ?>;
        font-size: 1.1rem;
        font-weight: 600;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .prof-cta-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    /* Breadcrumbs */
    .prof-breadcrumbs {
        background: #1f2937;
        padding: 15px 0;
    }
    .prof-breadcrumbs .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    .prof-breadcrumbs ul {
        display: flex;
        align-items: center;
        gap: 10px;
        list-style: none;
        margin: 0;
        padding: 0;
        font-size: 0.9rem;
    }
    .prof-breadcrumbs a {
        color: rgba(255,255,255,0.7);
        text-decoration: none;
    }
    .prof-breadcrumbs a:hover {
        color: #fff;
    }
    .prof-breadcrumbs .sep {
        color: rgba(255,255,255,0.4);
    }
    .prof-breadcrumbs .current {
        color: #fff;
    }
    
    @media (max-width: 900px) {
        .prof-single-hero .container {
            flex-direction: column;
            text-align: center;
        }
        .prof-single-content .container {
            grid-template-columns: 1fr;
        }
        .prof-sidebar {
            position: static;
        }
    }
    @media (max-width: 600px) {
        .prof-hero-avatar,
        .prof-hero-avatar-placeholder {
            width: 150px;
            height: 150px;
        }
        .prof-hero-info h1 {
            font-size: 1.8rem;
        }
        .prof-hero-badges {
            justify-content: center;
        }
    }
</style>

<main id="main-content" class="site-main">

<?php if (have_posts()): while (have_posts()): the_post(); ?>

<!-- Breadcrumbs -->
<nav class="prof-breadcrumbs" aria-label="Breadcrumb">
    <div class="container">
        <ul>
            <li><a href="<?php echo home_url(); ?>">Home</a></li>
            <li class="sep">›</li>
            <li><a href="<?php echo get_post_type_archive_link('professor'); ?>">Professores</a></li>
            <li class="sep">›</li>
            <li class="current"><?php the_title(); ?></li>
        </ul>
    </div>
</nav>

<!-- Hero Section -->
<section class="prof-single-hero">
    <div class="container">
        <?php if ($foto): ?>
            <img src="<?php echo esc_url($foto); ?>" alt="<?php the_title_attribute(); ?>" class="prof-hero-avatar">
        <?php else: ?>
            <div class="prof-hero-avatar-placeholder">👤</div>
        <?php endif; ?>
        
        <div class="prof-hero-info">
            <h1><?php the_title(); ?></h1>
            
            <?php if ($titulo): ?>
                <div class="prof-hero-titulo"><?php echo esc_html($titulo); ?></div>
            <?php endif; ?>
            
            <?php if ($crmv): ?>
                <div class="prof-hero-crmv">CRMV: <?php echo esc_html($crmv); ?></div>
            <?php endif; ?>
            
            <?php if ($especialidades && is_array($especialidades)): ?>
                <div class="prof-hero-badges">
                    <?php foreach ($especialidades as $esp): ?>
                        <span class="prof-hero-badge"><?php echo esc_html($esp); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="prof-single-content">
    <div class="container">
        <div class="prof-main-content">
            
            <?php 
            $conteudo_principal = get_the_content();
            if (!$conteudo_principal && $experiencia) {
                $conteudo_principal = $experiencia;
            }
            if ($conteudo_principal): 
            ?>
            <section>
                <h2>Sobre o Professor</h2>
                <div class="prof-bio-content">
                    <?php echo wpautop($conteudo_principal); ?>
                </div>
            </section>
            <?php endif; ?>
            
            <?php if ($curriculo): ?>
            <section>
                <h2>Currículo</h2>
                <div class="prof-curriculo-content">
                    <?php echo wp_kses_post(wpautop($curriculo)); ?>
                </div>
            </section>
            <?php endif; ?>
            
            <?php if (!empty($cursos_do_professor)): ?>
            <section>
                <h2>Cursos que Leciona</h2>
                <div class="prof-cursos-grid">
                    <?php foreach ($cursos_do_professor as $curso): 
                        $curso_img = get_the_post_thumbnail_url($curso->ID, 'medium');
                        $curso_preco = get_post_meta($curso->ID, '_curso_preco', true);
                        $curso_tipo = '';
                        $tipos = wp_get_post_terms($curso->ID, 'tipo_curso');
                        if (!empty($tipos) && !is_wp_error($tipos)) {
                            $curso_tipo = $tipos[0]->name;
                        }
                    ?>
                    <div class="prof-curso-card">
                        <a href="<?php echo get_permalink($curso->ID); ?>">
                            <?php if ($curso_img): ?>
                                <img src="<?php echo esc_url($curso_img); ?>" alt="<?php echo esc_attr($curso->post_title); ?>">
                            <?php endif; ?>
                            <div class="prof-curso-card-content">
                                <?php if ($curso_tipo): ?>
                                    <span class="tipo"><?php echo esc_html($curso_tipo); ?></span>
                                <?php endif; ?>
                                <h3><?php echo esc_html($curso->post_title); ?></h3>
                                <?php if ($curso_preco): ?>
                                    <div class="preco">R$ <?php echo number_format((float)$curso_preco, 2, ',', '.'); ?></div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            
        </div>
        
        <!-- Sidebar -->
        <aside class="prof-sidebar">
            <div class="prof-sidebar-card">
                <?php if ($foto): ?>
                    <img src="<?php echo esc_url($foto); ?>" alt="<?php the_title_attribute(); ?>" class="prof-sidebar-avatar">
                <?php endif; ?>
                
                <div class="prof-sidebar-name"><?php the_title(); ?></div>
                <?php if ($titulo): ?>
                    <div class="prof-sidebar-titulo"><?php echo esc_html($titulo); ?></div>
                <?php endif; ?>
                
                <?php if ($linkedin || $instagram || $email || $website): ?>
                <div class="prof-social-links">
                    <?php if ($linkedin): ?>
                    <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener" title="LinkedIn">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($instagram): ?>
                    <a href="<?php echo esc_url($instagram); ?>" target="_blank" rel="noopener" title="Instagram">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($website): ?>
                    <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener" title="Website">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($email): ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>" title="E-mail">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($email): ?>
                <a href="mailto:<?php echo esc_attr($email); ?>" class="prof-contact-btn">
                    Entrar em Contato
                </a>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<!-- CTA Section -->
<section class="prof-cta-section">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 20px;">
        <h2>Explore Nossos Cursos</h2>
        <p>Descubra outros cursos ministrados por nossos professores especialistas e avance na sua carreira.</p>
        <a href="<?php echo home_url('/cursos'); ?>" class="prof-cta-btn">
            Ver Todos os Cursos
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
    </div>
</section>

<?php endwhile; endif; ?>

</main>

<?php get_footer(); ?>
