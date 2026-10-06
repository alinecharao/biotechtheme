<?php
/**
 * Widget Elementor - Acordeão FAQ
 * Replica o estilo do acordeão do Lovable
 *
 * @package Starter_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget de Acordeão FAQ
 */
class Cursos_Widget_Accordion extends \Elementor\Widget_Base {

    /**
     * Nome do widget
     */
    public function get_name() {
        return 'cursos_accordion';
    }

    /**
     * Título do widget
     */
    public function get_title() {
        return __('Acordeão FAQ', 'starter-theme');
    }

    /**
     * Ícone do widget
     */
    public function get_icon() {
        return 'eicon-accordion';
    }

    /**
     * Categorias do widget
     */
    public function get_categories() {
        return ['cursos-theme'];
    }

    /**
     * Palavras-chave para busca
     */
    public function get_keywords() {
        return ['accordion', 'faq', 'perguntas', 'toggle', 'collapse'];
    }

    /**
     * Registra os controles do widget
     */
    protected function register_controls() {
        // =====================================================================
        // SEÇÃO: CONTEÚDO
        // =====================================================================
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Conteúdo', 'starter-theme'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'title',
            [
                'label'       => __('Título/Pergunta', 'starter-theme'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => __('Pergunta frequente aqui', 'starter-theme'),
                'label_block' => true,
                'dynamic'     => ['active' => true],
            ]
        );

        $repeater->add_control(
            'content',
            [
                'label'   => __('Conteúdo/Resposta', 'starter-theme'),
                'type'    => \Elementor\Controls_Manager::WYSIWYG,
                'default' => __('Resposta detalhada para a pergunta frequente.', 'starter-theme'),
                'dynamic' => ['active' => true],
            ]
        );

        $this->add_control(
            'accordion_items',
            [
                'label'       => __('Itens do Acordeão', 'starter-theme'),
                'type'        => \Elementor\Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [
                    [
                        'title'   => __('Como funciona o curso particular?', 'starter-theme'),
                        'content' => __('O curso particular é uma mentoria personalizada onde você aprende diretamente com um especialista, no seu ritmo e com foco nas suas necessidades específicas.', 'starter-theme'),
                    ],
                    [
                        'title'   => __('Qual a duração das sessões?', 'starter-theme'),
                        'content' => __('As sessões têm duração de 1 a 2 horas, dependendo do pacote escolhido e da complexidade do tema abordado.', 'starter-theme'),
                    ],
                    [
                        'title'   => __('Posso agendar em qualquer horário?', 'starter-theme'),
                        'content' => __('Sim! Oferecemos flexibilidade de horários. Você agenda diretamente com seu mentor, de acordo com a disponibilidade de ambos.', 'starter-theme'),
                    ],
                ],
                'title_field' => '{{{ title }}}',
            ]
        );

        $this->add_control(
            'first_open',
            [
                'label'        => __('Abrir Primeiro Item', 'starter-theme'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Sim', 'starter-theme'),
                'label_off'    => __('Não', 'starter-theme'),
                'return_value' => 'yes',
                'default'      => '',
                'separator'    => 'before',
            ]
        );

        $this->add_control(
            'collapse_mode',
            [
                'label'   => __('Modo de Abertura', 'starter-theme'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'single',
                'options' => [
                    'single'   => __('Um por vez', 'starter-theme'),
                    'multiple' => __('Múltiplos abertos', 'starter-theme'),
                ],
            ]
        );

        $this->end_controls_section();

        // =====================================================================
        // SEÇÃO: ESTILO - CONTAINER/ITEM
        // =====================================================================
        $this->start_controls_section(
            'section_style_item',
            [
                'label' => __('Container do Item', 'starter-theme'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'item_gap',
            [
                'label'      => __('Espaçamento entre Itens', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 30],
                ],
                'default'    => ['size' => 8, 'unit' => 'px'],
                'selectors'  => [
                    '{{WRAPPER}} .vetcursos-accordion-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .vetcursos-accordion-item:last-child' => 'margin-bottom: 0;',
                ],
            ]
        );

        $this->add_control(
            'item_background',
            [
                'label'     => __('Cor de Fundo', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .vetcursos-accordion-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'item_border_color',
            [
                'label'     => __('Cor da Borda', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#e5e7eb',
                'selectors' => [
                    '{{WRAPPER}} .vetcursos-accordion-item' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_border_width',
            [
                'label'      => __('Espessura da Borda', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 5],
                ],
                'default'    => ['size' => 1, 'unit' => 'px'],
                'selectors'  => [
                    '{{WRAPPER}} .vetcursos-accordion-item' => 'border-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_border_radius',
            [
                'label'      => __('Arredondamento', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 30],
                ],
                'default'    => ['size' => 8, 'unit' => 'px'],
                'selectors'  => [
                    '{{WRAPPER}} .vetcursos-accordion-item' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'item_box_shadow',
                'label'    => __('Sombra', 'starter-theme'),
                'selector' => '{{WRAPPER}} .vetcursos-accordion-item',
            ]
        );

        $this->end_controls_section();

        // =====================================================================
        // SEÇÃO: ESTILO - TÍTULO/TRIGGER
        // =====================================================================
        $this->start_controls_section(
            'section_style_title',
            [
                'label' => __('Título/Pergunta', 'starter-theme'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => __('Cor do Texto', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#1f2937',
                'selectors' => [
                    '{{WRAPPER}} .vetcursos-accordion-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'title_hover_color',
            [
                'label'     => __('Cor no Hover', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .vetcursos-accordion-trigger:hover .vetcursos-accordion-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'label'    => __('Tipografia', 'starter-theme'),
                'selector' => '{{WRAPPER}} .vetcursos-accordion-title',
            ]
        );

        $this->add_responsive_control(
            'title_padding',
            [
                'label'      => __('Padding', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'    => '16',
                    'right'  => '16',
                    'bottom' => '16',
                    'left'   => '16',
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vetcursos-accordion-trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'icon_heading',
            [
                'label'     => __('Ícone', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label'     => __('Cor do Ícone', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#6b7280',
                'selectors' => [
                    '{{WRAPPER}} .vetcursos-accordion-icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label'      => __('Tamanho do Ícone', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => ['min' => 10, 'max' => 30],
                ],
                'default'    => ['size' => 16, 'unit' => 'px'],
                'selectors'  => [
                    '{{WRAPPER}} .vetcursos-accordion-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // =====================================================================
        // SEÇÃO: ESTILO - CONTEÚDO
        // =====================================================================
        $this->start_controls_section(
            'section_style_content',
            [
                'label' => __('Conteúdo/Resposta', 'starter-theme'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'content_color',
            [
                'label'     => __('Cor do Texto', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#6b7280',
                'selectors' => [
                    '{{WRAPPER}} .vetcursos-accordion-content-inner' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .vetcursos-accordion-content-inner p' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'content_typography',
                'label'    => __('Tipografia', 'starter-theme'),
                'selector' => '{{WRAPPER}} .vetcursos-accordion-content-inner, {{WRAPPER}} .vetcursos-accordion-content-inner p',
            ]
        );

        $this->add_responsive_control(
            'content_padding',
            [
                'label'      => __('Padding', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'    => '0',
                    'right'  => '16',
                    'bottom' => '16',
                    'left'   => '16',
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vetcursos-accordion-content-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Renderiza o widget
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        $items = $settings['accordion_items'];
        $first_open = $settings['first_open'] === 'yes';
        $collapse_mode = $settings['collapse_mode'];
        $widget_id = $this->get_id();

        if (empty($items)) {
            return;
        }
        ?>
        <style>
            /* Reset e Container */
            .vetcursos-accordion {
                width: 100%;
            }

            .vetcursos-accordion-item {
                background-color: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                margin-bottom: 8px;
                overflow: hidden;
            }

            .vetcursos-accordion-item:last-child {
                margin-bottom: 0;
            }

            /* Trigger/Header */
            .vetcursos-accordion-trigger {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                padding: 16px;
                background: transparent;
                border: none;
                cursor: pointer;
                text-align: left;
                font-weight: 500;
                color: #1f2937;
                transition: background-color 0.15s ease;
                gap: 12px;
            }

            .vetcursos-accordion-trigger:hover {
                background-color: rgba(0, 0, 0, 0.02);
            }

            .vetcursos-accordion-trigger:focus {
                outline: none;
            }

            .vetcursos-accordion-title {
                flex: 1;
                margin: 0;
                font-size: inherit;
                font-weight: inherit;
                line-height: 1.5;
            }

            /* Ícone Chevron */
            .vetcursos-accordion-icon {
                width: 16px;
                height: 16px;
                flex-shrink: 0;
                transition: transform 0.2s ease-out;
                color: #6b7280;
            }

            .vetcursos-accordion-item.active .vetcursos-accordion-icon {
                transform: rotate(180deg);
            }

            /* Conteúdo Colapsável */
            .vetcursos-accordion-content {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.2s ease-out;
            }

            .vetcursos-accordion-item.active .vetcursos-accordion-content {
                max-height: 2000px;
            }

            .vetcursos-accordion-content-inner {
                padding: 0 16px 16px 16px;
                color: #6b7280;
                font-size: 14px;
                line-height: 1.6;
            }

            .vetcursos-accordion-content-inner p {
                margin: 0 0 1em 0;
            }

            .vetcursos-accordion-content-inner p:last-child {
                margin-bottom: 0;
            }

            .vetcursos-accordion-content-inner ul,
            .vetcursos-accordion-content-inner ol {
                margin: 0 0 1em 1.5em;
                padding: 0;
            }

            .vetcursos-accordion-content-inner li {
                margin-bottom: 0.5em;
            }
        </style>

        <div class="vetcursos-accordion" data-collapse-mode="<?php echo esc_attr($collapse_mode); ?>">
            <?php foreach ($items as $index => $item) : 
                $is_active = $first_open && $index === 0;
            ?>
                <div class="vetcursos-accordion-item<?php echo $is_active ? ' active' : ''; ?>">
                    <button type="button" class="vetcursos-accordion-trigger" aria-expanded="<?php echo $is_active ? 'true' : 'false'; ?>">
                        <span class="vetcursos-accordion-title"><?php echo esc_html($item['title']); ?></span>
                        <svg class="vetcursos-accordion-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="vetcursos-accordion-content">
                        <div class="vetcursos-accordion-content-inner">
                            <?php echo wp_kses_post($item['content']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <script>
        (function() {
            const accordion = document.querySelector('.elementor-element-<?php echo esc_js($widget_id); ?> .vetcursos-accordion');
            if (!accordion) return;

            const collapseMode = accordion.dataset.collapseMode || 'single';
            const triggers = accordion.querySelectorAll('.vetcursos-accordion-trigger');

            triggers.forEach(trigger => {
                trigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    const item = this.closest('.vetcursos-accordion-item');
                    const wasActive = item.classList.contains('active');

                    if (collapseMode === 'single') {
                        // Fecha todos os outros
                        accordion.querySelectorAll('.vetcursos-accordion-item').forEach(i => {
                            i.classList.remove('active');
                            i.querySelector('.vetcursos-accordion-trigger').setAttribute('aria-expanded', 'false');
                        });
                    }

                    // Toggle do item atual
                    if (!wasActive) {
                        item.classList.add('active');
                        this.setAttribute('aria-expanded', 'true');
                    } else if (collapseMode === 'multiple') {
                        item.classList.remove('active');
                        this.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Template do editor
     */
    protected function content_template() {
        ?>
        <#
        var items = settings.accordion_items;
        var firstOpen = settings.first_open === 'yes';
        var collapseMode = settings.collapse_mode;
        #>
        <div class="vetcursos-accordion" data-collapse-mode="{{ collapseMode }}">
            <# _.each(items, function(item, index) { 
                var isActive = firstOpen && index === 0;
            #>
                <div class="vetcursos-accordion-item<# if (isActive) { #> active<# } #>">
                    <button type="button" class="vetcursos-accordion-trigger" aria-expanded="<# if (isActive) { #>true<# } else { #>false<# } #>">
                        <span class="vetcursos-accordion-title">{{{ item.title }}}</span>
                        <svg class="vetcursos-accordion-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="vetcursos-accordion-content">
                        <div class="vetcursos-accordion-content-inner">
                            {{{ item.content }}}
                        </div>
                    </div>
                </div>
            <# }); #>
        </div>

        <style>
            .vetcursos-accordion-item {
                background-color: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                margin-bottom: 8px;
                overflow: hidden;
            }
            .vetcursos-accordion-trigger {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                padding: 16px;
                background: transparent;
                border: none;
                cursor: pointer;
                text-align: left;
                font-weight: 500;
                color: #1f2937;
                gap: 12px;
            }
            .vetcursos-accordion-title {
                flex: 1;
                margin: 0;
            }
            .vetcursos-accordion-icon {
                width: 16px;
                height: 16px;
                flex-shrink: 0;
                transition: transform 0.2s ease-out;
                color: #6b7280;
            }
            .vetcursos-accordion-item.active .vetcursos-accordion-icon {
                transform: rotate(180deg);
            }
            .vetcursos-accordion-content {
                max-height: 0;
                overflow: hidden;
            }
            .vetcursos-accordion-item.active .vetcursos-accordion-content {
                max-height: 2000px;
            }
            .vetcursos-accordion-content-inner {
                padding: 0 16px 16px 16px;
                color: #6b7280;
                font-size: 14px;
                line-height: 1.6;
            }
        </style>
        <?php
    }
}
