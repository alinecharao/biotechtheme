<?php
/**
 * Template para página inicial estática
 * Igual ao React/Lovable - Layout completo
 * 
 * @package CursosTheme
 */

get_header();
?>

<main id="main-content" class="site-main front-page-content">

<?php
// Verificar tipo de hero do Customizer
$hero_type = get_theme_mod('cursos_hero_type', 'static');

// Exibir hero baseado nas configurações
if ($hero_type !== 'none') {
    switch ($hero_type) {
        case 'slider':
        case 'slider_full':
            get_template_part('template-parts/hero', 'slider');
            break;
        case 'video':
            get_template_part('template-parts/hero', 'video');
            break;
        case 'gradient':
            get_template_part('template-parts/hero', 'gradient');
            break;
        case 'static':
        default:
            get_template_part('template-parts/hero', 'static');
            break;
    }
}

// Social Proof Bar (igual ao React)
get_template_part('template-parts/social-proof', 'bar');

// Cursos em Destaque
get_template_part('template-parts/home', 'featured-courses');

// Mid-page CTA
get_template_part('template-parts/mid', 'cta');

// Features Section
get_template_part('template-parts/features', 'section');

// Testimonials Section
get_template_part('template-parts/testimonials', 'section');

// Final CTA
get_template_part('template-parts/final', 'cta');

// Newsletter Section
get_template_part('template-parts/newsletter', 'section');
?>

</main>

<?php get_footer(); ?>
