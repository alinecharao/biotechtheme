<?php
/**
 * Template principal - Index
 * 
 * Template principal da página inicial com todas as seções
 * idêntico ao layout React/Lovable
 * 
 * @package CursosTheme
 */

get_header();
?>

<main id="main-content" class="site-main">
    <div class="container mx-auto px-4 py-12">
        <?php
        if (have_posts()) :
            while (have_posts()) :
                the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('mb-8'); ?>>
                    <header class="entry-header mb-4">
                        <?php the_title('<h1 class="text-3xl font-bold">', '</h1>'); ?>
                    </header>
                    
                    <div class="entry-content prose max-w-none">
                        <?php the_content(); ?>
                    </div>
                </article>
                <?php
            endwhile;
            
            the_posts_pagination(array(
                'prev_text' => '&laquo; Anterior',
                'next_text' => 'Próximo &raquo;',
            ));
        else :
            ?>
            <p class="text-center text-gray-600">Nenhum conteúdo encontrado.</p>
            <?php
        endif;
        ?>
    </div>
</main>

<?php get_footer(); ?>
