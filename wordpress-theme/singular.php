<?php
/**
 * Template fallback para qualquer conteúdo singular
 * 
 * Este arquivo serve como fallback para posts e páginas
 * que não têm um template específico
 * 
 * @package CursosTheme
 */

get_header();
?>

<main id="main-content" class="site-main singular-main">
    <div class="container">
        <?php if (have_posts()): while (have_posts()): the_post(); ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class('singular-article'); ?>>
                
                <header class="entry-header" style="background: #1C1C1C; padding: 80px 0; text-align: center; margin: 0 -20px;">
                    <div class="container">
                        <h1 class="entry-title" style="font-size: 3rem; font-weight: 700; font-style: italic; color: #fff; margin: 0;"><?php the_title(); ?></h1>
                        
                        <?php if (get_post_type() === 'post'): ?>
                        <div class="entry-meta" style="color: rgba(255,255,255,0.8); margin-top: 15px;">
                            <span class="posted-on">
                                <time datetime="<?php echo get_the_date('c'); ?>">
                                    <?php echo get_the_date(); ?>
                                </time>
                            </span>
                            <span class="byline">
                                por <?php the_author(); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </header>
                
                <?php if (has_post_thumbnail() && get_post_type() === 'post'): ?>
                <div class="entry-thumbnail">
                    <?php the_post_thumbnail('large'); ?>
                </div>
                <?php endif; ?>
                
                <div class="entry-content">
                    <?php 
                    the_content();
                    
                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . __('Páginas:', 'cursos-theme'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>
                
                <?php if (get_post_type() === 'post'): ?>
                <footer class="entry-footer">
                    <?php
                    $categories = get_the_category();
                    if ($categories):
                    ?>
                    <div class="entry-categories">
                        <strong>Categorias:</strong>
                        <?php foreach ($categories as $cat): ?>
                            <a href="<?php echo get_category_link($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php
                    $tags = get_the_tags();
                    if ($tags):
                    ?>
                    <div class="entry-tags">
                        <strong>Tags:</strong>
                        <?php foreach ($tags as $tag): ?>
                            <a href="<?php echo get_tag_link($tag->term_id); ?>"><?php echo esc_html($tag->name); ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </footer>
                <?php endif; ?>
                
            </article>
            
            <?php 
            // Navegação entre posts
            if (get_post_type() === 'post'):
                the_post_navigation(array(
                    'prev_text' => '<span class="nav-subtitle">' . __('Anterior:', 'cursos-theme') . '</span> <span class="nav-title">%title</span>',
                    'next_text' => '<span class="nav-subtitle">' . __('Próximo:', 'cursos-theme') . '</span> <span class="nav-title">%title</span>',
                ));
            endif;
            ?>
            
        <?php endwhile; endif; ?>
    </div>
</main>

<?php get_footer(); ?>
