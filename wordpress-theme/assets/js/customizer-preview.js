/**
 * Cursos Theme Customizer Live Preview
 * 
 * @package CursosTheme
 */

(function($) {
    'use strict';

    // Cores
    wp.customize('cursos_primary_color', function(value) {
        value.bind(function(newval) {
            document.documentElement.style.setProperty('--primary', newval);
        });
    });

    wp.customize('cursos_primary_dark_color', function(value) {
        value.bind(function(newval) {
            document.documentElement.style.setProperty('--primary-dark', newval);
        });
    });

    wp.customize('cursos_secondary_color', function(value) {
        value.bind(function(newval) {
            document.documentElement.style.setProperty('--secondary', newval);
        });
    });

    wp.customize('cursos_text_color', function(value) {
        value.bind(function(newval) {
            document.documentElement.style.setProperty('--text', newval);
        });
    });

    wp.customize('cursos_background_color', function(value) {
        value.bind(function(newval) {
            document.documentElement.style.setProperty('--background', newval);
        });
    });

    // Header
    wp.customize('cursos_header_bg_color', function(value) {
        value.bind(function(newval) {
            document.documentElement.style.setProperty('--header-bg', newval);
            $('.site-header').css('background-color', newval);
        });
    });

    wp.customize('cursos_header_text_color', function(value) {
        value.bind(function(newval) {
            document.documentElement.style.setProperty('--header-text', newval);
            $('.site-header, .site-header a').css('color', newval);
        });
    });

    // Footer
    wp.customize('cursos_footer_bg_color', function(value) {
        value.bind(function(newval) {
            document.documentElement.style.setProperty('--footer-bg', newval);
            $('.site-footer').css('background-color', newval);
        });
    });

    wp.customize('cursos_footer_text_color', function(value) {
        value.bind(function(newval) {
            document.documentElement.style.setProperty('--footer-text', newval);
            $('.site-footer').css('color', newval);
        });
    });

    // Hero
    wp.customize('cursos_hero_title', function(value) {
        value.bind(function(newval) {
            $('.hero-title').text(newval);
        });
    });

    wp.customize('cursos_hero_subtitle', function(value) {
        value.bind(function(newval) {
            $('.hero-subtitle').text(newval);
        });
    });

})(jQuery);
