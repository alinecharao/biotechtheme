<?php
/**
 * Integração com Elementor
 * Adiciona widgets personalizados para exibir cursos no Elementor
 *
 * @package Starter_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principal de integração com Elementor
 */
class Cursos_Elementor_Integration {

    /**
     * Versão dos assets
     */
    private $version = '1.0.0';

    /**
     * Instância única da classe
     */
    private static $instance = null;

    /**
     * Retorna instância única da classe
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Construtor
     */
    public function __construct() {
        // Registrar categoria ANTES dos widgets (prioridade 5)
        add_action('elementor/elements/categories_registered', [$this, 'register_category'], 5);
        
        // Registrar widgets DEPOIS da categoria (prioridade 10)
        add_action('elementor/widgets/register', [$this, 'register_widgets'], 10);
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'enqueue_frontend_styles']);
        add_action('elementor/frontend/after_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('elementor/editor/after_enqueue_styles', [$this, 'enqueue_editor_styles']);
    }

    /**
     * Registra a categoria "Cursos" no painel do Elementor
     */
    public function register_category($elements_manager) {
        $elements_manager->add_category(
            'cursos-theme',
            [
                'title' => __('Cursos', 'starter-theme'),
                'icon'  => 'eicon-products',
            ]
        );
    }

    /**
     * Registra todos os widgets
     */
    public function register_widgets($widgets_manager) {
        $widgets_path = get_template_directory() . '/includes/elementor-widgets/';

        // Lista de widgets
        $widgets = [
            'widget-course-grid.php' => 'Cursos_Widget_Course_Grid',
            'widget-menu-cart.php'   => 'Cursos_Widget_Menu_Cart',
            'widget-accordion.php'   => 'Cursos_Widget_Accordion',
        ];

        foreach ($widgets as $file => $class) {
            $file_path = $widgets_path . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
                if (class_exists($class)) {
                    $widgets_manager->register(new $class());
                }
            }
        }
    }

    /**
     * Estilos do frontend
     */
    public function enqueue_frontend_styles() {
        // Estilos dos widgets serão carregados aqui
    }

    /**
     * Scripts do frontend
     */
    public function enqueue_frontend_scripts() {
        // Swiper para carrossel
        wp_enqueue_style(
            'swiper',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
            [],
            '11.0.0'
        );

        wp_enqueue_script(
            'swiper',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
            [],
            '11.0.0',
            true
        );
    }

    /**
     * Estilos do editor
     */
    public function enqueue_editor_styles() {
        // Estilos do editor serão carregados aqui
    }
}
