<?php
/**
 * Template padrão para páginas
 * 
 * Este é o template genérico que exibe o conteúdo de qualquer página
 * criada no WordPress. Compatível com Gutenberg, Elementor e outros builders.
 * 
 * @package CursosTheme
 */

get_header();
?>

<main id="main-content" class="site-main page-main">
    <?php if (have_posts()): while (have_posts()): the_post(); ?>
        
        <article id="post-<?php the_ID(); ?>" <?php post_class('page-article'); ?>>
            
            <?php 
            // Header da página (apenas se não for template fullwidth e tiver título)
            $hide_title = get_post_meta(get_the_ID(), '_cursos_hide_title', true);
            if (!$hide_title):
            ?>
            <header class="page-header" style="background: #1C1C1C; padding: 80px 0; text-align: center;">
                <div class="container">
                    <h1 class="page-title" style="font-size: 3rem; font-weight: 700; font-style: italic; color: #fff; margin: 0;"><?php the_title(); ?></h1>
                </div>
            </header>
            <?php endif; ?>
            
            <div class="page-content">
                <?php 
                // Exibir conteúdo da página
                the_content(); 
                
                // Links de paginação para páginas divididas
                wp_link_pages(array(
                    'before' => '<div class="page-links">' . __('Páginas:', 'cursos-theme'),
                    'after'  => '</div>',
                ));
                ?>
            </div>
            
        </article>
        
    <?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>
