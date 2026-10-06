<?php
/**
 * Cursos Theme Customizer
 * 
 * Configurações completas do tema via Customizer do WordPress
 * 
 * @package CursosTheme
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cursos_Customizer {
    
    /**
     * Instância única da classe
     */
    private static $instance = null;
    
    /**
     * Obter instância
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
    private function __construct() {
        add_action('customize_register', array($this, 'register_customizer'));
        add_action('wp_head', array($this, 'customizer_css'), 100);
        add_action('customize_preview_init', array($this, 'customize_preview_js'));
        add_action('customize_controls_enqueue_scripts', array($this, 'customize_controls_js'));
    }
    
    /**
     * Registrar seções e configurações do Customizer
     */
    public function register_customizer($wp_customize) {
        
        // ========================================
        // PAINEL: CONFIGURAÇÕES DO TEMA
        // ========================================
        $wp_customize->add_panel('cursos_theme_panel', array(
            'title' => __('Configurações do Tema', 'cursos-theme'),
            'priority' => 30,
        ));
        
        // ========================================
        // SEÇÃO: CORES DO TEMA
        // ========================================
        $wp_customize->add_section('cursos_colors', array(
            'title' => __('Cores do Tema', 'cursos-theme'),
            'panel' => 'cursos_theme_panel',
            'priority' => 10,
        ));
        
        // Cor Primária - Bordô igual ao React
        $wp_customize->add_setting('cursos_primary_color', array(
            'default' => '#7f0b0d',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cursos_primary_color', array(
            'label' => __('Cor Primária', 'cursos-theme'),
            'section' => 'cursos_colors',
            'description' => __('Cor principal do tema - Bordô (botões, links, destaques)', 'cursos-theme'),
        )));
        
        // Cor Primária Escura (Hover) - Bordô mais escuro
        $wp_customize->add_setting('cursos_primary_dark_color', array(
            'default' => '#5f0809',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cursos_primary_dark_color', array(
            'label' => __('Cor Primária (Hover)', 'cursos-theme'),
            'section' => 'cursos_colors',
        )));
        
        // Cor Secundária - Gold
        $wp_customize->add_setting('cursos_secondary_color', array(
            'default' => '#c9a227',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cursos_secondary_color', array(
            'label' => __('Cor Secundária (Gold)', 'cursos-theme'),
            'section' => 'cursos_colors',
        )));
        
        // Cor de Sucesso
        $wp_customize->add_setting('cursos_success_color', array(
            'default' => '#10b981',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cursos_success_color', array(
            'label' => __('Cor de Sucesso', 'cursos-theme'),
            'section' => 'cursos_colors',
        )));
        
        // Cor de Perigo
        $wp_customize->add_setting('cursos_danger_color', array(
            'default' => '#ef4444',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cursos_danger_color', array(
            'label' => __('Cor de Perigo/Erro', 'cursos-theme'),
            'section' => 'cursos_colors',
        )));
        
        // Cor do Texto - Cinza escuro igual ao React
        $wp_customize->add_setting('cursos_text_color', array(
            'default' => '#333333',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cursos_text_color', array(
            'label' => __('Cor do Texto', 'cursos-theme'),
            'section' => 'cursos_colors',
        )));
        
        // Cor do Texto Secundário
        $wp_customize->add_setting('cursos_text_secondary_color', array(
            'default' => '#6b7280',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cursos_text_secondary_color', array(
            'label' => __('Cor do Texto Secundário', 'cursos-theme'),
            'section' => 'cursos_colors',
        )));
        
        // Cor de Fundo
        $wp_customize->add_setting('cursos_background_color', array(
            'default' => '#ffffff',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cursos_background_color', array(
            'label' => __('Cor de Fundo', 'cursos-theme'),
            'section' => 'cursos_colors',
        )));
        
        // Cor de Fundo Secundária
        $wp_customize->add_setting('cursos_background_secondary_color', array(
            'default' => '#f3f4f6',
            'sanitize_callback' => 'sanitize_hex_color',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cursos_background_secondary_color', array(
            'label' => __('Cor de Fundo Secundária', 'cursos-theme'),
            'section' => 'cursos_colors',
        )));
        
        // ========================================
        // SEÇÃO: HEADER
        // ========================================
        $wp_customize->add_section('cursos_header', array(
            'title' => __('Header', 'cursos-theme'),
            'panel' => 'cursos_theme_panel',
            'priority' => 20,
        ));
        
        // Tipo de Header
        $wp_customize->add_setting('cursos_header_type', array(
            'default' => 'default',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_header_type', array(
            'label' => __('Tipo de Header', 'cursos-theme'),
            'section' => 'cursos_header',
            'type' => 'select',
            'choices' => array(
                'default' => __('Padrão', 'cursos-theme'),
                'fullwidth' => __('Largura Total', 'cursos-theme'),
                'transparent' => __('Transparente', 'cursos-theme'),
                'centered' => __('Centralizado', 'cursos-theme'),
            ),
        ));
        
        // Cor do Header
        $wp_customize->add_setting('cursos_header_bg_color', array(
            'default' => '#ffffff',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cursos_header_bg_color', array(
            'label' => __('Cor de Fundo do Header', 'cursos-theme'),
            'section' => 'cursos_header',
        )));
        
        // Cor do Texto do Header
        $wp_customize->add_setting('cursos_header_text_color', array(
            'default' => '#1f2937',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cursos_header_text_color', array(
            'label' => __('Cor do Texto/Links do Header', 'cursos-theme'),
            'section' => 'cursos_header',
        )));
        
        // Header Sticky
        $wp_customize->add_setting('cursos_header_sticky', array(
            'default' => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        $wp_customize->add_control('cursos_header_sticky', array(
            'label' => __('Header Fixo (Sticky)', 'cursos-theme'),
            'section' => 'cursos_header',
            'type' => 'checkbox',
        ));
        
        // Mostrar Telefone
        $wp_customize->add_setting('cursos_header_show_phone', array(
            'default' => false,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        $wp_customize->add_control('cursos_header_show_phone', array(
            'label' => __('Mostrar Telefone no Header', 'cursos-theme'),
            'section' => 'cursos_header',
            'type' => 'checkbox',
        ));
        
        // Mostrar Botão CTA
        $wp_customize->add_setting('cursos_header_show_cta', array(
            'default' => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        $wp_customize->add_control('cursos_header_show_cta', array(
            'label' => __('Mostrar Botão CTA no Header', 'cursos-theme'),
            'section' => 'cursos_header',
            'type' => 'checkbox',
        ));
        
        // Texto do Botão CTA
        $wp_customize->add_setting('cursos_header_cta_text', array(
            'default' => 'Começar Agora',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_header_cta_text', array(
            'label' => __('Texto do Botão CTA', 'cursos-theme'),
            'section' => 'cursos_header',
            'type' => 'text',
        ));
        
        // Link do Botão CTA
        $wp_customize->add_setting('cursos_header_cta_link', array(
            'default' => '/cursos',
            'sanitize_callback' => 'esc_url_raw',
        ));
        $wp_customize->add_control('cursos_header_cta_link', array(
            'label' => __('Link do Botão CTA', 'cursos-theme'),
            'section' => 'cursos_header',
            'type' => 'url',
        ));
        
        // ========================================
        // SEÇÃO: HERO / BANNER
        // ========================================
        $wp_customize->add_section('cursos_hero', array(
            'title' => __('Hero / Banner', 'cursos-theme'),
            'panel' => 'cursos_theme_panel',
            'priority' => 25,
        ));
        
        // Tipo de Hero
        $wp_customize->add_setting('cursos_hero_type', array(
            'default' => 'static',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_hero_type', array(
            'label' => __('Tipo de Hero/Banner', 'cursos-theme'),
            'section' => 'cursos_hero',
            'type' => 'select',
            'choices' => array(
                'none' => __('Sem Hero (só conteúdo)', 'cursos-theme'),
                'static' => __('Imagem Estática', 'cursos-theme'),
                'slider' => __('Slider de Imagens', 'cursos-theme'),
                'slider_full' => __('Slider Full Screen (100vh)', 'cursos-theme'),
                'video' => __('Vídeo de Fundo', 'cursos-theme'),
                'gradient' => __('Gradiente com Texto', 'cursos-theme'),
            ),
        ));
        
        // Altura do Hero
        $wp_customize->add_setting('cursos_hero_height', array(
            'default' => '500',
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control('cursos_hero_height', array(
            'label' => __('Altura do Hero (px)', 'cursos-theme'),
            'section' => 'cursos_hero',
            'type' => 'number',
            'input_attrs' => array('min' => 200, 'max' => 1000, 'step' => 10),
            'description' => __('Não se aplica ao modo Full Screen', 'cursos-theme'),
        ));
        
        // Overlay (escurecimento)
        $wp_customize->add_setting('cursos_hero_overlay', array(
            'default' => 40,
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control('cursos_hero_overlay', array(
            'label' => __('Intensidade do Overlay (%)', 'cursos-theme'),
            'section' => 'cursos_hero',
            'type' => 'range',
            'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 5),
        ));
        
        // Imagem Estática
        $wp_customize->add_setting('cursos_hero_image', array(
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ));
        $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'cursos_hero_image', array(
            'label' => __('Imagem do Hero', 'cursos-theme'),
            'section' => 'cursos_hero',
            'description' => __('Imagem principal para hero estático', 'cursos-theme'),
        )));
        
        // Imagens do Slider (até 5)
        for ($i = 1; $i <= 5; $i++) {
            $wp_customize->add_setting('cursos_hero_slide_' . $i, array(
                'default' => '',
                'sanitize_callback' => 'esc_url_raw',
            ));
            $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'cursos_hero_slide_' . $i, array(
                'label' => sprintf(__('Slide %d', 'cursos-theme'), $i),
                'section' => 'cursos_hero',
            )));
            
            // Título do Slide
            $wp_customize->add_setting('cursos_hero_slide_title_' . $i, array(
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ));
            $wp_customize->add_control('cursos_hero_slide_title_' . $i, array(
                'label' => sprintf(__('Título do Slide %d', 'cursos-theme'), $i),
                'section' => 'cursos_hero',
                'type' => 'text',
            ));
            
            // Subtítulo do Slide
            $wp_customize->add_setting('cursos_hero_slide_subtitle_' . $i, array(
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ));
            $wp_customize->add_control('cursos_hero_slide_subtitle_' . $i, array(
                'label' => sprintf(__('Subtítulo do Slide %d', 'cursos-theme'), $i),
                'section' => 'cursos_hero',
                'type' => 'text',
            ));
        }
        
        // URL do Vídeo
        $wp_customize->add_setting('cursos_hero_video_url', array(
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ));
        $wp_customize->add_control('cursos_hero_video_url', array(
            'label' => __('URL do Vídeo', 'cursos-theme'),
            'section' => 'cursos_hero',
            'type' => 'url',
            'description' => __('URL do vídeo MP4 ou YouTube', 'cursos-theme'),
        ));
        
        // Título Principal do Hero (igual ao React)
        $wp_customize->add_setting('cursos_hero_title', array(
            'default' => 'Invista em você e dobre seu salário em 12 meses',
            'sanitize_callback' => 'sanitize_text_field',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control('cursos_hero_title', array(
            'label' => __('Título do Hero', 'cursos-theme'),
            'section' => 'cursos_hero',
            'type' => 'text',
        ));
        
        // Subtítulo do Hero (igual ao React)
        $wp_customize->add_setting('cursos_hero_subtitle', array(
            'default' => 'Cursos práticos com professores que atuam no mercado. Comece hoje e veja resultados reais na sua carreira.',
            'sanitize_callback' => 'sanitize_textarea_field',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control('cursos_hero_subtitle', array(
            'label' => __('Subtítulo do Hero', 'cursos-theme'),
            'section' => 'cursos_hero',
            'type' => 'textarea',
        ));
        
        // Texto do Botão Primário (igual ao React)
        $wp_customize->add_setting('cursos_hero_btn_primary_text', array(
            'default' => 'Quero Começar Agora',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_hero_btn_primary_text', array(
            'label' => __('Texto do Botão Primário', 'cursos-theme'),
            'section' => 'cursos_hero',
            'type' => 'text',
        ));
        
        // Link do Botão Primário
        $wp_customize->add_setting('cursos_hero_btn_primary_link', array(
            'default' => '/cursos',
            'sanitize_callback' => 'esc_url_raw',
        ));
        $wp_customize->add_control('cursos_hero_btn_primary_link', array(
            'label' => __('Link do Botão Primário', 'cursos-theme'),
            'section' => 'cursos_hero',
            'type' => 'url',
        ));
        
        // Texto do Botão Secundário (igual ao React)
        $wp_customize->add_setting('cursos_hero_btn_secondary_text', array(
            'default' => 'Ver Cursos Gratuitos',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_hero_btn_secondary_text', array(
            'label' => __('Texto do Botão Secundário', 'cursos-theme'),
            'section' => 'cursos_hero',
            'type' => 'text',
        ));
        
        // Link do Botão Secundário
        $wp_customize->add_setting('cursos_hero_btn_secondary_link', array(
            'default' => '/sobre',
            'sanitize_callback' => 'esc_url_raw',
        ));
        $wp_customize->add_control('cursos_hero_btn_secondary_link', array(
            'label' => __('Link do Botão Secundário', 'cursos-theme'),
            'section' => 'cursos_hero',
            'type' => 'url',
        ));
        
        // ========================================
        // SEÇÃO: FOOTER
        // ========================================
        $wp_customize->add_section('cursos_footer', array(
            'title' => __('Footer', 'cursos-theme'),
            'panel' => 'cursos_theme_panel',
            'priority' => 30,
        ));
        
        // Layout do Footer
        $wp_customize->add_setting('cursos_footer_layout', array(
            'default' => '4',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_footer_layout', array(
            'label' => __('Número de Colunas', 'cursos-theme'),
            'section' => 'cursos_footer',
            'type' => 'select',
            'choices' => array(
                '2' => __('2 Colunas', 'cursos-theme'),
                '3' => __('3 Colunas', 'cursos-theme'),
                '4' => __('4 Colunas', 'cursos-theme'),
            ),
        ));
        
        // Cor de Fundo do Footer
        $wp_customize->add_setting('cursos_footer_bg_color', array(
            'default' => '#1f2937',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cursos_footer_bg_color', array(
            'label' => __('Cor de Fundo do Footer', 'cursos-theme'),
            'section' => 'cursos_footer',
        )));
        
        // Cor do Texto do Footer
        $wp_customize->add_setting('cursos_footer_text_color', array(
            'default' => '#d1d5db',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'cursos_footer_text_color', array(
            'label' => __('Cor do Texto do Footer', 'cursos-theme'),
            'section' => 'cursos_footer',
        )));
        
        // Mostrar Logo no Footer
        $wp_customize->add_setting('cursos_footer_show_logo', array(
            'default' => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        $wp_customize->add_control('cursos_footer_show_logo', array(
            'label' => __('Mostrar Logo no Footer', 'cursos-theme'),
            'section' => 'cursos_footer',
            'type' => 'checkbox',
        ));
        
        // Mostrar Redes Sociais
        $wp_customize->add_setting('cursos_footer_show_social', array(
            'default' => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        $wp_customize->add_control('cursos_footer_show_social', array(
            'label' => __('Mostrar Redes Sociais', 'cursos-theme'),
            'section' => 'cursos_footer',
            'type' => 'checkbox',
        ));
        
        // Mostrar Contato
        $wp_customize->add_setting('cursos_footer_show_contact', array(
            'default' => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        $wp_customize->add_control('cursos_footer_show_contact', array(
            'label' => __('Mostrar Informações de Contato', 'cursos-theme'),
            'section' => 'cursos_footer',
            'type' => 'checkbox',
        ));
        
        // Texto do Copyright
        $wp_customize->add_setting('cursos_footer_copyright', array(
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_footer_copyright', array(
            'label' => __('Texto do Copyright', 'cursos-theme'),
            'section' => 'cursos_footer',
            'type' => 'text',
            'description' => __('Deixe vazio para usar o padrão', 'cursos-theme'),
        ));
        
        // ========================================
        // SEÇÃO: LAYOUT GERAL
        // ========================================
        $wp_customize->add_section('cursos_layout', array(
            'title' => __('Layout Geral', 'cursos-theme'),
            'panel' => 'cursos_theme_panel',
            'priority' => 40,
        ));
        
        // Largura do Container
        $wp_customize->add_setting('cursos_container_width', array(
            'default' => '1200',
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control('cursos_container_width', array(
            'label' => __('Largura do Container (px)', 'cursos-theme'),
            'section' => 'cursos_layout',
            'type' => 'select',
            'choices' => array(
                '1000' => '1000px',
                '1200' => '1200px',
                '1400' => '1400px',
                '1600' => '1600px',
                '100' => 'Full Width (100%)',
            ),
        ));
        
        // Fonte do Corpo
        $wp_customize->add_setting('cursos_body_font', array(
            'default' => 'Inter',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_body_font', array(
            'label' => __('Fonte do Corpo', 'cursos-theme'),
            'section' => 'cursos_layout',
            'type' => 'select',
            'choices' => $this->get_google_fonts(),
        ));
        
        // Fonte dos Títulos
        $wp_customize->add_setting('cursos_heading_font', array(
            'default' => 'Inter',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_heading_font', array(
            'label' => __('Fonte dos Títulos', 'cursos-theme'),
            'section' => 'cursos_layout',
            'type' => 'select',
            'choices' => $this->get_google_fonts(),
        ));
        
        // Mostrar Breadcrumbs
        $wp_customize->add_setting('cursos_show_breadcrumbs', array(
            'default' => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        $wp_customize->add_control('cursos_show_breadcrumbs', array(
            'label' => __('Mostrar Breadcrumbs', 'cursos-theme'),
            'section' => 'cursos_layout',
            'type' => 'checkbox',
        ));
        
        // Colunas da Grid de Cursos
        $wp_customize->add_setting('cursos_grid_columns', array(
            'default' => '3',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_grid_columns', array(
            'label' => __('Colunas da Grid de Cursos', 'cursos-theme'),
            'section' => 'cursos_layout',
            'type' => 'select',
            'choices' => array(
                '2' => __('2 Colunas', 'cursos-theme'),
                '3' => __('3 Colunas', 'cursos-theme'),
                '4' => __('4 Colunas', 'cursos-theme'),
            ),
        ));
        
        // Mostrar Sidebar no Curso
        $wp_customize->add_setting('cursos_single_sidebar', array(
            'default' => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        $wp_customize->add_control('cursos_single_sidebar', array(
            'label' => __('Mostrar Sidebar na Página do Curso', 'cursos-theme'),
            'section' => 'cursos_layout',
            'type' => 'checkbox',
        ));
        
        // ========================================
        // SEÇÃO: BOTÃO WHATSAPP
        // ========================================
        $wp_customize->add_section('cursos_whatsapp', array(
            'title' => __('Botão WhatsApp', 'cursos-theme'),
            'panel' => 'cursos_theme_panel',
            'priority' => 50,
        ));
        
        // Mostrar Botão WhatsApp
        $wp_customize->add_setting('cursos_whatsapp_show', array(
            'default' => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        $wp_customize->add_control('cursos_whatsapp_show', array(
            'label' => __('Mostrar Botão Flutuante', 'cursos-theme'),
            'section' => 'cursos_whatsapp',
            'type' => 'checkbox',
        ));
        
        // Posição do Botão
        $wp_customize->add_setting('cursos_whatsapp_position', array(
            'default' => 'right',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_whatsapp_position', array(
            'label' => __('Posição do Botão', 'cursos-theme'),
            'section' => 'cursos_whatsapp',
            'type' => 'select',
            'choices' => array(
                'left' => __('Esquerda', 'cursos-theme'),
                'right' => __('Direita', 'cursos-theme'),
            ),
        ));
        
        // Número do WhatsApp
        $wp_customize->add_setting('cursos_whatsapp_number', array(
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_whatsapp_number', array(
            'label' => __('Número do WhatsApp', 'cursos-theme'),
            'description' => __('Apenas números com DDD e código do país. Ex: 5511999999999', 'cursos-theme'),
            'section' => 'cursos_whatsapp',
            'type' => 'text',
        ));

        // Mensagem Padrão
        $wp_customize->add_setting('cursos_whatsapp_message', array(
            'default' => 'Olá! Gostaria de mais informações sobre os cursos.',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_whatsapp_message', array(
            'label' => __('Mensagem Padrão', 'cursos-theme'),
            'section' => 'cursos_whatsapp',
            'type' => 'text',
        ));
        
        // ========================================
        // SEÇÃO: PORTAL DO ALUNO (SYNC)
        // ========================================
        $wp_customize->add_section('cursos_portal_sync', array(
            'title' => __('Portal do Aluno (Sync)', 'cursos-theme'),
            'panel' => 'cursos_theme_panel',
            'priority' => 54,
            'description' => __('Sincronize cursos e alunos automaticamente com o Portal Biotech.', 'cursos-theme'),
        ));
        
        // Ativar sincronização
        $wp_customize->add_setting('cursos_portal_sync_enabled', array(
            'default' => false,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        $wp_customize->add_control('cursos_portal_sync_enabled', array(
            'label' => __('Ativar Sincronização com Portal', 'cursos-theme'),
            'description' => __('Sincroniza cursos e alunos automaticamente com o Portal do Aluno.', 'cursos-theme'),
            'section' => 'cursos_portal_sync',
            'type' => 'checkbox',
        ));
        
        // Secret alternativo (caso não use wp-config.php)
        $wp_customize->add_setting('cursos_portal_sync_secret', array(
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control('cursos_portal_sync_secret', array(
            'label' => __('Secret de Autenticação', 'cursos-theme'),
            'description' => __('Deixe em branco se definiu WP_SYNC_SECRET no wp-config.php (recomendado).', 'cursos-theme'),
            'section' => 'cursos_portal_sync',
            'type' => 'password',
        ));
        
        // ========================================
        // SEÇÃO: AUTOMAÇÃO DE CURSOS
        // ========================================
        $wp_customize->add_section('cursos_automation', array(
            'title' => __('Automação de Cursos', 'cursos-theme'),
            'panel' => 'cursos_theme_panel',
            'priority' => 55,
            'description' => __('Configure a automação para categorizar cursos automaticamente baseado na disponibilidade de vagas.', 'cursos-theme'),
        ));
        
        // Obter categorias existentes
        $categorias = get_terms(array(
            'taxonomy'   => 'categoria_curso',
            'hide_empty' => false,
        ));
        
        $category_choices = array('' => __('-- Desativado --', 'cursos-theme'));
        if (!is_wp_error($categorias) && !empty($categorias)) {
            foreach ($categorias as $cat) {
                $category_choices[$cat->term_id] = $cat->name;
            }
        }
        
        // Categoria para Cursos com Vagas
        $wp_customize->add_setting('cursos_availability_category', array(
            'default'           => '',
            'sanitize_callback' => 'absint',
        ));
        $wp_customize->add_control('cursos_availability_category', array(
            'label'       => __('Categoria de Vagas Disponíveis', 'cursos-theme'),
            'description' => __('Cursos com vagas serão automaticamente adicionados a esta categoria. Cursos sem vagas serão removidos.', 'cursos-theme'),
            'section'     => 'cursos_automation',
            'type'        => 'select',
            'choices'     => $category_choices,
        ));
        
        // Ativar sincronização diária
        $wp_customize->add_setting('cursos_automation_daily_sync', array(
            'default'           => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        $wp_customize->add_control('cursos_automation_daily_sync', array(
            'label'       => __('Sincronização Diária Automática', 'cursos-theme'),
            'description' => __('Verifica todos os cursos diariamente e atualiza a categoria.', 'cursos-theme'),
            'section'     => 'cursos_automation',
            'type'        => 'checkbox',
        ));
        
        // Sincronizar após pagamento
        $wp_customize->add_setting('cursos_automation_on_payment', array(
            'default'           => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));
        $wp_customize->add_control('cursos_automation_on_payment', array(
            'label'       => __('Sincronizar Após Pagamentos', 'cursos-theme'),
            'description' => __('Atualiza a categoria do curso imediatamente após cada pagamento confirmado ou cancelado.', 'cursos-theme'),
            'section'     => 'cursos_automation',
            'type'        => 'checkbox',
        ));
    }
    
    /**
     * Lista de Google Fonts disponíveis
     */
    private function get_google_fonts() {
        return array(
            'Inter' => 'Inter',
            'Roboto' => 'Roboto',
            'Open Sans' => 'Open Sans',
            'Lato' => 'Lato',
            'Montserrat' => 'Montserrat',
            'Poppins' => 'Poppins',
            'Raleway' => 'Raleway',
            'Nunito' => 'Nunito',
            'Playfair Display' => 'Playfair Display',
            'Merriweather' => 'Merriweather',
            'Source Sans Pro' => 'Source Sans Pro',
            'PT Sans' => 'PT Sans',
            'Ubuntu' => 'Ubuntu',
            'Oswald' => 'Oswald',
            'Quicksand' => 'Quicksand',
            'Work Sans' => 'Work Sans',
            'Rubik' => 'Rubik',
            'Fira Sans' => 'Fira Sans',
            'Barlow' => 'Barlow',
            'DM Sans' => 'DM Sans',
        );
    }
    
    /**
     * Sanitize checkbox
     */
    public function sanitize_checkbox($checked) {
        return ((isset($checked) && true == $checked) ? true : false);
    }
    
    /**
     * Gerar CSS dinâmico baseado nas opções do Customizer
     */
    public function customizer_css() {
        // Cores Bordô - Iguais ao React/Lovable
        $primary = get_theme_mod('cursos_primary_color', '#7f0b0d');
        $primary_dark = get_theme_mod('cursos_primary_dark_color', '#5f0809');
        $primary_light = get_theme_mod('cursos_primary_light_color', '#9a1214');
        $secondary = get_theme_mod('cursos_secondary_color', '#c9a227');
        $success = get_theme_mod('cursos_success_color', '#10b981');
        $danger = get_theme_mod('cursos_danger_color', '#ef4444');
        $text = get_theme_mod('cursos_text_color', '#333333');
        $text_secondary = get_theme_mod('cursos_text_secondary_color', '#6b7280');
        $background = get_theme_mod('cursos_background_color', '#ffffff');
        $background_secondary = get_theme_mod('cursos_background_secondary_color', '#f3f4f6');
        
        $header_bg = get_theme_mod('cursos_header_bg_color', '#ffffff');
        $header_text = get_theme_mod('cursos_header_text_color', '#1f2937');
        
        $footer_bg = get_theme_mod('cursos_footer_bg_color', '#1f2937');
        $footer_text = get_theme_mod('cursos_footer_text_color', '#d1d5db');
        
        $container_width = get_theme_mod('cursos_container_width', '1200');
        $body_font = get_theme_mod('cursos_body_font', 'Noto Sans');
        $heading_font = get_theme_mod('cursos_heading_font', 'Noto Sans');
        
        $hero_height = get_theme_mod('cursos_hero_height', '500');
        $grid_columns = get_theme_mod('cursos_grid_columns', '3');
        ?>
        <style id="cursos-customizer-css">
            :root {
                --primary: <?php echo esc_attr($primary); ?>;
                --primary-dark: <?php echo esc_attr($primary_dark); ?>;
                --primary-light: <?php echo esc_attr($primary_light); ?>;
                --secondary: <?php echo esc_attr($secondary); ?>;
                --success: <?php echo esc_attr($success); ?>;
                --danger: <?php echo esc_attr($danger); ?>;
                --text: <?php echo esc_attr($text); ?>;
                --text-secondary: <?php echo esc_attr($text_secondary); ?>;
                --background: <?php echo esc_attr($background); ?>;
                --background-secondary: <?php echo esc_attr($background_secondary); ?>;
                
                --header-bg: <?php echo esc_attr($header_bg); ?>;
                --header-text: <?php echo esc_attr($header_text); ?>;
                
                --footer-bg: <?php echo esc_attr($footer_bg); ?>;
                --footer-text: <?php echo esc_attr($footer_text); ?>;
                
                --container-width: <?php echo $container_width === '100' ? '100%' : esc_attr($container_width) . 'px'; ?>;
                --body-font: '<?php echo esc_attr($body_font); ?>', sans-serif;
                --heading-font: '<?php echo esc_attr($heading_font); ?>', sans-serif;
                
                --hero-height: <?php echo esc_attr($hero_height); ?>px;
                --grid-columns: <?php echo esc_attr($grid_columns); ?>;
            }
            
            body {
                font-family: var(--body-font);
                color: var(--text);
                background-color: var(--background);
            }
            
            h1, h2, h3, h4, h5, h6 {
                font-family: var(--heading-font);
            }
            
            .container {
                max-width: var(--container-width);
            }
            
            .site-header {
                background-color: var(--header-bg);
                color: var(--header-text);
            }
            
            .site-header a {
                color: var(--header-text);
            }
            
            .site-footer {
                background-color: var(--footer-bg);
                color: var(--footer-text);
            }
            
            .btn-primary,
            .btn {
                background-color: var(--primary);
            }
            
            .btn-primary:hover,
            .btn:hover {
                background-color: var(--primary-dark);
            }
            
            a {
                color: var(--primary);
            }
            
            a:hover {
                color: var(--primary-dark);
            }
            
            .hero-section {
                min-height: var(--hero-height);
            }
            
            .cursos-grid {
                grid-template-columns: repeat(var(--grid-columns), 1fr);
            }
            
            @media (max-width: 992px) {
                .cursos-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            
            @media (max-width: 576px) {
                .cursos-grid {
                    grid-template-columns: 1fr;
                }
            }
            
            <?php if (get_theme_mod('cursos_header_sticky', true)): ?>
            .site-header {
                position: sticky;
                top: 0;
                z-index: 1000;
            }
            <?php endif; ?>
            
            <?php if (get_theme_mod('cursos_header_type') === 'transparent'): ?>
            .front-page .site-header {
                position: absolute;
                width: 100%;
                background-color: transparent;
            }
            .front-page .site-header a {
                color: #ffffff;
            }
            <?php endif; ?>
            
            <?php if (get_theme_mod('cursos_header_type') === 'centered'): ?>
            .site-header .header-inner {
                flex-direction: column;
                text-align: center;
            }
            .site-header .main-nav {
                margin-top: 15px;
            }
            <?php endif; ?>
        </style>
        <?php
    }
    
    /**
     * Enqueue script para preview ao vivo
     */
    public function customize_preview_js() {
        wp_enqueue_script(
            'cursos-customizer-preview',
            CURSOS_THEME_URI . '/assets/js/customizer-preview.js',
            array('customize-preview', 'jquery'),
            CURSOS_THEME_VERSION,
            true
        );
    }
    
    /**
     * Enqueue scripts para controles do Customizer
     */
    public function customize_controls_js() {
        wp_enqueue_script(
            'cursos-customizer-controls',
            CURSOS_THEME_URI . '/assets/js/customizer-controls.js',
            array('customize-controls', 'jquery'),
            CURSOS_THEME_VERSION,
            true
        );
    }
}

// Inicializar Customizer
Cursos_Customizer::get_instance();

/**
 * Helper functions para obter valores do Customizer
 */
function cursos_get_customizer($key, $default = '') {
    return get_theme_mod($key, $default);
}

function cursos_get_hero_type() {
    return get_theme_mod('cursos_hero_type', 'static');
}

function cursos_get_header_type() {
    return get_theme_mod('cursos_header_type', 'default');
}

function cursos_show_breadcrumbs() {
    return get_theme_mod('cursos_show_breadcrumbs', true);
}
