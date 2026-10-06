<?php
/**
 * Widget Elementor: Grade de Cursos
 * Exibe cursos em grid com layout idêntico ao Lovable
 *
 * @package Starter_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cursos_Widget_Course_Grid extends \Elementor\Widget_Base {

    public function get_name() {
        return 'cursos_course_grid';
    }

    public function get_title() {
        return __('Grade de Cursos', 'starter-theme');
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    public function get_categories() {
        return ['cursos-theme'];
    }

    public function get_keywords() {
        return ['cursos', 'grid', 'cards', 'curso'];
    }

    protected function register_controls() {
        // Seção de Conteúdo
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Conteúdo', 'starter-theme'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label'   => __('Quantidade de Cursos', 'starter-theme'),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'default' => 6,
                'min'     => 1,
                'max'     => 50,
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label'   => __('Colunas', 'starter-theme'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'selectors' => [
                    '{{WRAPPER}} .cg-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label'   => __('Ordenar por', 'starter-theme'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date'       => __('Data', 'starter-theme'),
                    'title'      => __('Título', 'starter-theme'),
                    'menu_order' => __('Ordem do Menu', 'starter-theme'),
                    'rand'       => __('Aleatório', 'starter-theme'),
                ],
            ]
        );

        $this->add_control(
            'order',
            [
                'label'   => __('Ordem', 'starter-theme'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'DESC' => __('Decrescente', 'starter-theme'),
                    'ASC'  => __('Crescente', 'starter-theme'),
                ],
            ]
        );

        // Filtro por Categoria
        $categorias = get_terms([
            'taxonomy'   => 'categoria_curso',
            'hide_empty' => false,
        ]);
        
        $cat_options = ['' => __('Todas as Categorias', 'starter-theme')];
        if (!is_wp_error($categorias) && !empty($categorias)) {
            foreach ($categorias as $cat) {
                $cat_options[$cat->slug] = $cat->name;
            }
        }

        $this->add_control(
            'categoria',
            [
                'label'   => __('Categoria', 'starter-theme'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '',
                'options' => $cat_options,
            ]
        );

        $this->add_control(
            'tipo_curso',
            [
                'label'   => __('Tipo de Curso', 'starter-theme'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    ''              => __('Todos os Tipos', 'starter-theme'),
                    'online'        => __('Online', 'starter-theme'),
                    'particular'    => __('Particular', 'starter-theme'),
                    'pos-graduacao' => __('Pós-Graduação', 'starter-theme'),
                ],
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'   => __('Texto do Botão', 'starter-theme'),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __('Matricule-se', 'starter-theme'),
            ]
        );

        $this->end_controls_section();

        // Seção de Exibição
        $this->start_controls_section(
            'section_display',
            [
                'label' => __('Exibição', 'starter-theme'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_instructor',
            [
                'label'        => __('Mostrar Instrutor', 'starter-theme'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Sim', 'starter-theme'),
                'label_off'    => __('Não', 'starter-theme'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'instructor_style',
            [
                'label'   => __('Estilo do Instrutor', 'starter-theme'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'single',
                'options' => [
                    'single'  => __('Único (nome + foto)', 'starter-theme'),
                    'stacked' => __('Avatares Empilhados', 'starter-theme'),
                ],
                'condition' => [
                    'show_instructor' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'stacked_max_avatars',
            [
                'label'   => __('Máximo de Avatares', 'starter-theme'),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'default' => 4,
                'min'     => 2,
                'max'     => 8,
                'condition' => [
                    'show_instructor'  => 'yes',
                    'instructor_style' => 'stacked',
                ],
            ]
        );

        $this->add_control(
            'show_duration',
            [
                'label'        => __('Mostrar Duração', 'starter-theme'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Sim', 'starter-theme'),
                'label_off'    => __('Não', 'starter-theme'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_students',
            [
                'label'        => __('Mostrar Alunos', 'starter-theme'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Sim', 'starter-theme'),
                'label_off'    => __('Não', 'starter-theme'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_rating',
            [
                'label'        => __('Mostrar Avaliação', 'starter-theme'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Sim', 'starter-theme'),
                'label_off'    => __('Não', 'starter-theme'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_description',
            [
                'label'        => __('Mostrar Descrição', 'starter-theme'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Sim', 'starter-theme'),
                'label_off'    => __('Não', 'starter-theme'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_price',
            [
                'label'        => __('Mostrar Preço', 'starter-theme'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Sim', 'starter-theme'),
                'label_off'    => __('Não', 'starter-theme'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();

        // Seção de Estilo - Card
        $this->start_controls_section(
            'section_style_card',
            [
                'label' => __('Card', 'starter-theme'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'gap',
            [
                'label'      => __('Espaçamento', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem'],
                'range'      => [
                    'px'  => ['min' => 0, 'max' => 100],
                    'rem' => ['min' => 0, 'max' => 6],
                ],
                'default'    => ['unit' => 'rem', 'size' => 1.5],
                'selectors'  => [
                    '{{WRAPPER}} .cg-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'card_background',
            [
                'label'     => __('Cor de Fundo', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .cg-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_border_radius',
            [
                'label'      => __('Arredondamento', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem'],
                'range'      => [
                    'px'  => ['min' => 0, 'max' => 50],
                    'rem' => ['min' => 0, 'max' => 3],
                ],
                'default'    => ['unit' => 'rem', 'size' => 0.75],
                'selectors'  => [
                    '{{WRAPPER}} .cg-card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .cg-card',
            ]
        );

        // Controle de Proporção da Imagem
        $this->add_control(
            'image_aspect_ratio',
            [
                'label'     => __('Proporção da Imagem', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'default'   => '16-10',
                'options'   => [
                    '16-9'   => '16:9 (Widescreen)',
                    '16-10'  => '16:10 (Padrão)',
                    '4-3'    => '4:3 (Clássico)',
                    '1-1'    => '1:1 (Quadrado)',
                    'custom' => __('Altura Personalizada', 'starter-theme'),
                ],
                'separator' => 'before',
            ]
        );

        // Controle de Altura Personalizada da Imagem
        $this->add_responsive_control(
            'image_height',
            [
                'label'      => __('Altura da Imagem', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => ['min' => 100, 'max' => 400, 'step' => 10],
                ],
                'default'    => ['unit' => 'px', 'size' => 200],
                'selectors'  => [
                    '{{WRAPPER}} .cg-image-wrapper' => 'height: {{SIZE}}{{UNIT}}; aspect-ratio: unset;',
                ],
                'condition'  => [
                    'image_aspect_ratio' => 'custom',
                ],
            ]
        );

        $this->end_controls_section();

        // Seção de Estilo - Tipografia
        $this->start_controls_section(
            'section_style_typography',
            [
                'label' => __('Tipografia', 'starter-theme'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => __('Cor do Título', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#1f2937',
                'selectors' => [
                    '{{WRAPPER}} .cg-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'title_hover_color',
            [
                'label'     => __('Cor do Título (Hover)', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#7F0B0D',
                'selectors' => [
                    '{{WRAPPER}} .cg-card:hover .cg-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label'     => __('Cor do Preço', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#7F0B0D',
                'selectors' => [
                    '{{WRAPPER}} .cg-price-current' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Seção de Estilo - Botão
        $this->start_controls_section(
            'section_style_button',
            [
                'label' => __('Botão', 'starter-theme'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label'     => __('Cor de Fundo', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#7F0B0D',
                'selectors' => [
                    '{{WRAPPER}} .cg-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label'     => __('Cor do Texto', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .cg-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_bg_color',
            [
                'label'     => __('Cor de Fundo (Hover)', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#5c0809',
                'selectors' => [
                    '{{WRAPPER}} .cg-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label'      => __('Arredondamento', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem'],
                'range'      => [
                    'px'  => ['min' => 0, 'max' => 50],
                    'rem' => ['min' => 0, 'max' => 3],
                ],
                'default'    => ['unit' => 'rem', 'size' => 0.5],
                'selectors'  => [
                    '{{WRAPPER}} .cg-button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        // Query args
        $args = [
            'post_type'      => 'curso',
            'posts_per_page' => $settings['posts_per_page'],
            'post_status'    => 'publish',
            'orderby'        => $settings['orderby'],
            'order'          => $settings['order'],
        ];

        // Filtro por categoria
        if (!empty($settings['categoria'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'categoria_curso',
                    'field'    => 'slug',
                    'terms'    => $settings['categoria'],
                ],
            ];
        }

        // Filtro por tipo
        if (!empty($settings['tipo_curso'])) {
            $args['meta_query'] = [
                [
                    'key'     => '_curso_tipo',
                    'value'   => $settings['tipo_curso'],
                    'compare' => '=',
                ],
            ];
        }

        $query = new \WP_Query($args);

        if (!$query->have_posts()) {
            echo '<p style="text-align: center; padding: 2rem; color: #6b7280;">' . __('Nenhum curso encontrado.', 'starter-theme') . '</p>';
            return;
        }

        // Type labels
        $type_labels = [
            'online'        => 'Online',
            'particular'    => 'Particular',
            'pos-graduacao' => 'Pós-Graduação',
        ];
        ?>
        <style>
            .cg-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 1.5rem;
            }
            .cg-card {
                background: #ffffff;
                border-radius: 0.75rem;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                border: 1px solid #e5e7eb;
                transition: box-shadow 0.3s ease;
                display: flex;
                flex-direction: column;
            }
            .cg-card:hover {
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            }
            .cg-card {
                overflow: visible; /* Permitir tooltips visíveis */
            }
            .cg-image-link {
                display: block;
                text-decoration: none;
            }
            .cg-image-wrapper {
                position: relative;
                width: 100%;
                height: 200px; /* Fallback fixo */
                overflow: hidden;
            }
            .cg-image-wrapper[data-aspect="16-9"] { aspect-ratio: 16 / 9; height: auto; }
            .cg-image-wrapper[data-aspect="16-10"] { aspect-ratio: 16 / 10; height: auto; }
            .cg-image-wrapper[data-aspect="4-3"] { aspect-ratio: 4 / 3; height: auto; }
            .cg-image-wrapper[data-aspect="1-1"] { aspect-ratio: 1 / 1; height: auto; }
            .cg-image-wrapper img,
            .cg-image {
                position: absolute;
                inset: 0;
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
                transition: transform 0.3s ease;
            }
            .cg-card:hover .cg-image {
                transform: scale(1.05);
            }
            .cg-image-placeholder {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #7F0B0D;
                color: #ffffff;
            }
            .cg-badge {
                position: absolute;
                padding: 0.25rem 0.75rem;
                border-radius: 0.25rem;
                font-size: 0.75rem;
                font-weight: 600;
                z-index: 2;
            }
            .cg-badge-type {
                top: 0.75rem;
                left: 0.75rem;
                background: #7F0B0D;
                color: #ffffff;
            }
            .cg-badge-popular {
                top: 0.75rem;
                right: 0.75rem;
                background: #f59e0b;
                color: #ffffff;
                display: flex;
                align-items: center;
                gap: 0.25rem;
            }
            .cg-badge-savings {
                bottom: 0.75rem;
                left: 0.75rem;
                background: #16a34a;
                color: #ffffff;
            }
            .cg-badge-ultimas-vagas {
                top: 0.75rem;
                right: 0.75rem;
                background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
                color: #ffffff;
                display: flex;
                align-items: center;
                gap: 0.25rem;
                animation: pulse-urgencia 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
                font-weight: 700;
                letter-spacing: 0.025em;
            }
            @keyframes pulse-urgencia {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.9; transform: scale(1.02); }
            }
            .cg-meta {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1rem 1.25rem 0.5rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                font-size: 0.875rem;
                color: #6b7280;
                flex-wrap: wrap;
            }
            .cg-meta-item {
                display: flex;
                align-items: center;
                gap: 0.25rem;
            }
            .cg-meta-item svg {
                flex-shrink: 0;
            }
            .cg-meta-rating {
                color: #f59e0b;
            }
            .cg-meta-rating span {
                font-weight: 500;
            }
            .cg-content {
                padding: 1.25rem;
                flex: 1;
                display: flex;
                flex-direction: column;
                overflow: visible; /* Permitir tooltips visíveis */
                position: relative;
            }
            .cg-title {
                font-family: 'Noto Sans', system-ui, sans-serif;
                font-size: 1.125rem;
                font-weight: 600;
                color: #1f2937;
                margin: 0 0 0.5rem;
                line-height: 1.4;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                transition: color 0.2s ease;
                text-decoration: none;
            }
            .cg-title:hover {
                color: #7F0B0D;
            }
            .cg-description {
                font-size: 0.875rem;
                color: #6b7280;
                margin: 0 0 1rem;
                line-height: 1.5;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .cg-instructor {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                margin-bottom: 1rem;
            }
            .cg-instructor-image,
            img.cg-instructor-image {
                width: 2.5rem !important;
                height: 2.5rem !important;
                min-width: 2.5rem !important;
                min-height: 2.5rem !important;
                max-width: 2.5rem !important;
                max-height: 2.5rem !important;
                border-radius: 50% !important;
                object-fit: cover !important;
                border: 2px solid #e5e7eb !important;
                aspect-ratio: 1 / 1 !important;
                display: block !important;
                overflow: hidden;
                -webkit-clip-path: circle(50%);
                clip-path: circle(50%);
            }
            .cg-instructor-placeholder {
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 50%;
                background: #f3f4f6;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 2px solid #e5e7eb;
                color: #9ca3af;
            }
            .cg-instructor-info {
                display: flex;
                flex-direction: column;
            }
            .cg-instructor-name {
                font-size: 0.875rem;
                font-weight: 500;
                color: #1f2937;
            }
            .cg-instructor-title {
                font-size: 0.75rem;
                color: #6b7280;
            }
            /* Avatares Empilhados */
            .cg-instructors-stacked {
                display: flex;
                align-items: center;
                margin-bottom: 1rem;
                overflow: visible; /* Permitir tooltips visíveis */
                position: relative;
                z-index: 10;
            }
            .cg-stacked-avatars {
                display: flex;
                align-items: center;
                overflow: visible; /* Permitir tooltips visíveis */
            }
            /* Avatar Wrapper para Tooltip */
            .cg-avatar-wrapper {
                position: relative;
                display: inline-block;
                margin-left: -0.75rem;
                z-index: 1;
            }
            .cg-avatar-wrapper:first-child {
                margin-left: 0;
            }
            .cg-avatar-wrapper:hover {
                z-index: 50; /* Elevar ao passar o mouse */
            }
            .cg-stacked-avatar,
            img.cg-stacked-avatar {
                width: 2.25rem !important;
                height: 2.25rem !important;
                min-width: 2.25rem !important;
                min-height: 2.25rem !important;
                max-width: 2.25rem !important;
                max-height: 2.25rem !important;
                border-radius: 50% !important;
                border: 2px solid #ffffff !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
                object-fit: cover !important;
                aspect-ratio: 1 / 1 !important;
                background: #f3f4f6;
                display: block !important;
                overflow: hidden;
                -webkit-clip-path: circle(50%);
                clip-path: circle(50%);
            }
            .cg-stacked-avatar-placeholder {
                width: 2.25rem;
                height: 2.25rem;
                min-width: 2.25rem;
                min-height: 2.25rem;
                border-radius: 50%;
                border: 2px solid #ffffff;
                background: #e5e7eb;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #6b7280;
                font-size: 0.75rem;
                font-weight: 600;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
                aspect-ratio: 1 / 1;
            }
            .cg-stacked-more {
                width: 2.25rem;
                height: 2.25rem;
                min-width: 2.25rem;
                min-height: 2.25rem;
                border-radius: 50%;
                border: 2px solid #ffffff;
                background: #7F0B0D;
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-left: -0.75rem;
                font-size: 0.75rem;
                font-weight: 600;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
            }
            .cg-stacked-names {
                margin-left: 0.75rem;
                font-size: 0.8rem;
                color: #6b7280;
                max-width: 150px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            /* Tooltips Estilizados */
            .cg-avatar-tooltip {
                position: absolute;
                bottom: calc(100% + 8px);
                left: 50%;
                transform: translateX(-50%);
                background: #1f2937;
                color: #ffffff;
                padding: 0.5rem 0.75rem;
                border-radius: 0.375rem;
                font-size: 0.75rem;
                white-space: nowrap;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.2s ease, visibility 0.2s ease;
                z-index: 9999; /* Aumentado para garantir visibilidade */
                pointer-events: none;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                text-align: center;
            }
            .cg-avatar-tooltip::after {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                transform: translateX(-50%);
                border: 6px solid transparent;
                border-top-color: #1f2937;
            }
            .cg-avatar-wrapper:hover .cg-avatar-tooltip {
                opacity: 1;
                visibility: visible;
            }
            .cg-tooltip-name {
                font-weight: 600;
                display: block;
                line-height: 1.3;
            }
            .cg-tooltip-title {
                color: #9ca3af;
                font-size: 0.7rem;
                display: block;
                margin-top: 2px;
                line-height: 1.3;
            }
            .cg-price-section {
                padding-top: 1rem;
                border-top: 1px solid #e5e7eb;
                margin-top: auto;
            }
            .cg-price-info {
                display: flex;
                flex-direction: column;
                margin-bottom: 1rem;
            }
            .cg-price-original {
                font-size: 0.875rem;
                color: #6b7280;
                text-decoration: line-through;
            }
            .cg-price-row {
                display: flex;
                align-items: baseline;
                gap: 0.5rem;
            }
            .cg-price-current {
                font-size: 1.5rem;
                font-weight: 700;
                color: #7F0B0D;
            }
            .cg-price-installments {
                font-size: 0.75rem;
                color: #6b7280;
            }
            .cg-button {
                display: block;
                width: 100%;
                padding: 0.75rem 1.5rem;
                background: #7F0B0D;
                color: #ffffff;
                text-align: center;
                text-decoration: none;
                font-weight: 600;
                font-size: 0.875rem;
                border-radius: 0.5rem;
                transition: background-color 0.2s ease;
                border: none;
                cursor: pointer;
            }
            .cg-button:hover {
                background: #5c0809;
                color: #ffffff;
            }
            @media (max-width: 1024px) {
                .cg-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            @media (max-width: 768px) {
                .cg-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div class="cg-grid">
            <?php while ($query->have_posts()): $query->the_post();
                $curso_id = get_the_ID();
                $curso_image = get_the_post_thumbnail_url($curso_id, 'large');
                $has_image = !empty($curso_image);
                $curso_preco = floatval(get_post_meta($curso_id, '_curso_preco', true));
                $curso_preco_original = floatval(get_post_meta($curso_id, '_curso_preco_original', true));
                $curso_duracao = get_post_meta($curso_id, '_curso_duracao', true) ?: 'A definir';
                $curso_alunos = intval(get_post_meta($curso_id, '_curso_alunos', true));
                $curso_avaliacao = floatval(get_post_meta($curso_id, '_curso_avaliacao', true));
                $curso_tipo = get_post_meta($curso_id, '_curso_tipo', true);
                
                // Professor data - buscar todos para suporte a avatares empilhados
                $professores = array();
                if (function_exists('cursos_get_curso_professores')) {
                    $professores = cursos_get_curso_professores($curso_id);
                } elseif (function_exists('cursos_get_professor_full_data')) {
                    // Fallback para método antigo
                    $professor_id = get_post_meta($curso_id, '_curso_professor', true);
                    if ($professor_id) {
                        $data = cursos_get_professor_full_data($professor_id);
                        if ($data) $professores[] = $data;
                    }
                }
                $professor_data = !empty($professores) ? $professores[0] : null;
                
                // Type label - buscar da taxonomy tipo_curso primeiro, depois do meta field
                $type_label = '';
                $tipo_terms = get_the_terms($curso_id, 'tipo_curso');
                if (!is_wp_error($tipo_terms) && !empty($tipo_terms)) {
                    $type_label = $tipo_terms[0]->name;
                } elseif (!empty($curso_tipo) && isset($type_labels[$curso_tipo])) {
                    $type_label = $type_labels[$curso_tipo];
                } else {
                    // Fallback - não mostrar badge se não tiver tipo definido
                    $type_label = '';
                }
                
                // Calculations
                $savings = ($curso_preco_original > $curso_preco) ? ($curso_preco_original - $curso_preco) : 0;
                $has_discount = $savings > 0;
                $is_popular = $curso_alunos >= 1000;
                $installment_value = $curso_preco / 12;
                $curso_link = get_permalink($curso_id);
            ?>
            <article class="cg-card">
                <a href="<?php echo esc_url($curso_link); ?>" class="cg-image-link">
                    <div class="cg-image-wrapper" data-aspect="<?php echo esc_attr($settings['image_aspect_ratio'] ?? '16-10'); ?>">
                        <?php if ($has_image): ?>
                        <img src="<?php echo esc_url($curso_image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="cg-image">
                        <?php else: ?>
                        <div class="cg-image-placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                                <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                            </svg>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($type_label)): ?>
                        <span class="cg-badge cg-badge-type"><?php echo esc_html($type_label); ?></span>
                        <?php endif; ?>
                        
                        <?php 
                        // Verificar últimas vagas
                        $menor_vagas = cursos_get_curso_menor_vagas($curso_id);
                        $ultimas_vagas = ($menor_vagas !== null && $menor_vagas > 0 && $menor_vagas <= 5);
                        ?>
                        
                        <?php if ($ultimas_vagas): ?>
                        <span class="cg-badge cg-badge-ultimas-vagas">
                            🔥 Últimas <?php echo $menor_vagas; ?> vaga<?php echo $menor_vagas > 1 ? 's' : ''; ?>!
                        </span>
                        <?php elseif ($is_popular): ?>
                        <span class="cg-badge cg-badge-popular">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                            POPULAR
                        </span>
                        <?php endif; ?>

                        <?php if ($has_discount): ?>
                        <span class="cg-badge cg-badge-savings">
                            ECONOMIZE <?php echo 'R$ ' . number_format($savings, 2, ',', '.'); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </a>
                
                <div class="cg-meta">
                    <?php if ($settings['show_duration'] === 'yes'): ?>
                    <div class="cg-meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span><?php echo esc_html($curso_duracao); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_students'] === 'yes'): ?>
                    <div class="cg-meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span><?php echo number_format($curso_alunos, 0, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_rating'] === 'yes' && $curso_avaliacao > 0): ?>
                    <div class="cg-meta-item cg-meta-rating">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <span><?php echo number_format($curso_avaliacao, 1, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="cg-content">
                    <a href="<?php echo esc_url($curso_link); ?>" class="cg-title">
                        <?php the_title(); ?>
                    </a>
                    
                    <?php if ($settings['show_description'] === 'yes'): ?>
                    <p class="cg-description">
                        <?php echo esc_html(wp_trim_words(get_the_excerpt(), 15)); ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($settings['show_instructor'] === 'yes' && !empty($professores)): ?>
                        <?php if (($settings['instructor_style'] ?? 'single') === 'stacked' && count($professores) > 1): ?>
                        <!-- Avatares Empilhados -->
                        <div class="cg-instructors-stacked">
                            <div class="cg-stacked-avatars">
                                <?php 
                                $max_show = intval($settings['stacked_max_avatars'] ?? 4);
                                $total = count($professores);
                                $to_show = array_slice($professores, 0, $max_show);
                                $remaining = $total - $max_show;
                                
                                foreach ($to_show as $prof): ?>
                                    <div class="cg-avatar-wrapper">
                                        <?php if (!empty($prof['foto'])): ?>
                                        <img src="<?php echo esc_url($prof['foto']); ?>" 
                                             alt="<?php echo esc_attr($prof['nome']); ?>" 
                                             class="cg-stacked-avatar">
                                        <?php else: ?>
                                        <div class="cg-stacked-avatar-placeholder">
                                            <?php echo mb_strtoupper(mb_substr($prof['nome'], 0, 1)); ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="cg-avatar-tooltip">
                                            <span class="cg-tooltip-name"><?php echo esc_html($prof['nome']); ?></span>
                                            <?php if (!empty($prof['titulo'])): ?>
                                            <span class="cg-tooltip-title"><?php echo esc_html($prof['titulo']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if ($remaining > 0): ?>
                                <div class="cg-stacked-more">+<?php echo $remaining; ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="cg-stacked-names">
                                <?php 
                                $nomes = array_map(function($p) { 
                                    return explode(' ', $p['nome'])[0]; 
                                }, array_slice($professores, 0, 2));
                                echo esc_html(implode(', ', $nomes));
                                if ($total > 2) echo ' +' . ($total - 2);
                                ?>
                            </span>
                        </div>
                        <?php else: ?>
                        <!-- Layout Original (1 professor) -->
                        <div class="cg-instructor">
                            <?php if (!empty($professor_data['foto'])): ?>
                            <img src="<?php echo esc_url($professor_data['foto']); ?>" alt="<?php echo esc_attr($professor_data['nome']); ?>" class="cg-instructor-image">
                            <?php else: ?>
                            <div class="cg-instructor-placeholder">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <?php endif; ?>
                            <div class="cg-instructor-info">
                                <span class="cg-instructor-name"><?php echo esc_html($professor_data['nome']); ?></span>
                                <?php if (!empty($professor_data['titulo'])): ?>
                                <span class="cg-instructor-title"><?php echo esc_html($professor_data['titulo']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="cg-price-section">
                        <?php if ($settings['show_price'] === 'yes'): ?>
                        <div class="cg-price-info">
                            <?php if ($has_discount): ?>
                            <span class="cg-price-original">
                                <?php echo 'R$ ' . number_format($curso_preco_original, 2, ',', '.'); ?>
                            </span>
                            <?php endif; ?>
                            <div class="cg-price-row">
                                <span class="cg-price-current">
                                    <?php echo 'R$ ' . number_format($curso_preco, 2, ',', '.'); ?>
                                </span>
                                <span class="cg-price-installments">
                                    ou 12x <?php echo 'R$ ' . number_format($installment_value, 2, ',', '.'); ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <a href="<?php echo esc_url($curso_link); ?>" class="cg-button">
                            <?php echo esc_html($settings['button_text']); ?>
                        </a>
                    </div>
                </div>
            </article>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
    }
}
