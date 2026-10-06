<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo is_single() || is_page() ? get_the_excerpt() : get_bloginfo('description'); ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo is_single() || is_page() ? get_the_title() : get_bloginfo('name'); ?>">
    <meta property="og:description" content="<?php echo is_single() || is_page() ? get_the_excerpt() : get_bloginfo('description'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>">
    <?php if (has_post_thumbnail()): ?>
    <meta property="og:image" content="<?php echo get_the_post_thumbnail_url(null, 'large'); ?>">
    <?php endif; ?>
    
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <!-- Logo -->
            <a href="<?php echo home_url('/'); ?>" class="site-logo" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                <?php if (has_custom_logo()): ?>
                    <?php the_custom_logo(); ?>
                <?php else: ?>
                    <!-- Logo igual ao React: quadrado bordô com inicial + nome -->
                    <div style="width: 2.5rem; height: 2.5rem; background: var(--primary); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                        <span style="color: #fff; font-family: 'Noto Sans', sans-serif; font-weight: 700; font-size: 1.25rem;">V</span>
                    </div>
                    <span style="font-family: 'Noto Sans', sans-serif; font-size: 1.5rem; font-weight: 700; color: var(--primary);">VetCursos</span>
                <?php endif; ?>
            </a>
            
            <!-- Navigation -->
            <nav class="main-nav">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container' => false,
                    'menu_class' => '',
                    'fallback_cb' => function() {
                        echo '<ul>';
                        echo '<li><a href="' . home_url('/') . '">Início</a></li>';
                        echo '<li><a href="' . home_url('/cursos') . '">Cursos</a></li>';
                        echo '<li><a href="' . home_url('/cursos-particulares') . '">Cursos Particulares</a></li>';
                        echo '<li><a href="' . home_url('/pos-graduacao') . '">Pós-Graduação</a></li>';
                        echo '<li><a href="' . home_url('/professores') . '">Professores</a></li>';
                        echo '<li><a href="' . home_url('/sobre') . '">Sobre</a></li>';
                        echo '<li><a href="' . home_url('/contato') . '">Contato</a></li>';
                        echo '</ul>';
                    },
                ));
                ?>
            </nav>
            
            <!-- Header Actions (igual ao React) -->
            <div class="header-actions" style="display: flex; align-items: center; gap: 15px;">
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>" class="btn btn-outline" style="padding: 8px 16px; border: 1px solid var(--primary); color: var(--primary); background: transparent;">
                        Área do Aluno
                    </a>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(); ?>" class="btn btn-outline" style="padding: 8px 16px; border: 1px solid var(--primary); color: var(--primary); background: transparent;">
                        Área do Aluno
                    </a>
                <?php endif; ?>
                
                <!-- Cart (sempre visível) -->
                <?php $cart_count = cursos_get_cart_count(); ?>
                <a href="<?php echo home_url('/carrinho'); ?>" class="header-cart" id="headerCart" style="position: relative; padding: 8px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <span id="cartCount" style="position: absolute; top: 0; right: 0; background: var(--primary); color: white; font-size: 11px; width: 18px; height: 18px; border-radius: 50%; display: <?php echo $cart_count > 0 ? 'flex' : 'none'; ?>; align-items: center; justify-content: center;">
                        <?php echo $cart_count; ?>
                    </span>
                </a>
                
                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" id="mobileMenuToggle" style="display: none; background: none; border: none; cursor: pointer; padding: 8px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu" style="display: none; position: fixed; top: 80px; left: 0; right: 0; background: white; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); z-index: 999;">
    <?php
    wp_nav_menu(array(
        'theme_location' => 'primary',
        'container' => false,
        'menu_class' => 'mobile-nav-list',
    ));
    ?>
</div>
