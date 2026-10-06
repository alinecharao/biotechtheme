<?php
/**
 * Widget Elementor: Carrinho do Menu
 * Ícone de carrinho com contador de itens igual ao Lovable
 *
 * @package Starter_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cursos_Widget_Menu_Cart extends \Elementor\Widget_Base {

    public function get_name() {
        return 'cursos_menu_cart';
    }

    public function get_title() {
        return __('Carrinho', 'starter-theme');
    }

    public function get_icon() {
        return 'eicon-cart';
    }

    public function get_categories() {
        return ['cursos-theme'];
    }

    public function get_keywords() {
        return ['carrinho', 'cart', 'shop', 'menu'];
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
            'cart_url',
            [
                'label'       => __('URL do Carrinho', 'starter-theme'),
                'type'        => \Elementor\Controls_Manager::URL,
                'placeholder' => home_url('/carrinho'),
                'default'     => [
                    'url' => home_url('/carrinho'),
                ],
            ]
        );

        $this->add_control(
            'show_counter',
            [
                'label'        => __('Mostrar Contador', 'starter-theme'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Sim', 'starter-theme'),
                'label_off'    => __('Não', 'starter-theme'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'hide_empty',
            [
                'label'        => __('Esconder Contador Vazio', 'starter-theme'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Sim', 'starter-theme'),
                'label_off'    => __('Não', 'starter-theme'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'show_counter' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Seção de Estilo - Ícone
        $this->start_controls_section(
            'section_style_icon',
            [
                'label' => __('Ícone', 'starter-theme'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'icon_size',
            [
                'label'      => __('Tamanho', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem'],
                'range'      => [
                    'px'  => ['min' => 12, 'max' => 48],
                    'rem' => ['min' => 0.75, 'max' => 3],
                ],
                'default'    => ['unit' => 'px', 'size' => 20],
                'selectors'  => [
                    '{{WRAPPER}} .mc-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label'     => __('Cor', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#374151',
                'selectors' => [
                    '{{WRAPPER}} .mc-icon svg' => 'color: {{VALUE}}; stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'icon_hover_color',
            [
                'label'     => __('Cor (Hover)', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#7F0B0D',
                'selectors' => [
                    '{{WRAPPER}} .mc-button:hover .mc-icon svg' => 'color: {{VALUE}}; stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_padding',
            [
                'label'      => __('Padding', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem'],
                'default'    => [
                    'top'    => '0.5',
                    'right'  => '0.5',
                    'bottom' => '0.5',
                    'left'   => '0.5',
                    'unit'   => 'rem',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .mc-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label'      => __('Arredondamento', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', '%'],
                'range'      => [
                    'px'  => ['min' => 0, 'max' => 50],
                    'rem' => ['min' => 0, 'max' => 3],
                    '%'   => ['min' => 0, 'max' => 50],
                ],
                'default'    => ['unit' => 'rem', 'size' => 0.375],
                'selectors'  => [
                    '{{WRAPPER}} .mc-button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label'     => __('Cor de Fundo', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => 'transparent',
                'selectors' => [
                    '{{WRAPPER}} .mc-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_bg_color',
            [
                'label'     => __('Cor de Fundo (Hover)', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => 'rgba(0, 0, 0, 0.05)',
                'selectors' => [
                    '{{WRAPPER}} .mc-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Seção de Estilo - Contador
        $this->start_controls_section(
            'section_style_counter',
            [
                'label'     => __('Contador', 'starter-theme'),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_counter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'counter_size',
            [
                'label'      => __('Tamanho', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem'],
                'range'      => [
                    'px'  => ['min' => 14, 'max' => 32],
                    'rem' => ['min' => 0.875, 'max' => 2],
                ],
                'default'    => ['unit' => 'rem', 'size' => 1.25],
                'selectors'  => [
                    '{{WRAPPER}} .mc-counter' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'counter_font_size',
            [
                'label'      => __('Tamanho da Fonte', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem'],
                'range'      => [
                    'px'  => ['min' => 8, 'max' => 20],
                    'rem' => ['min' => 0.5, 'max' => 1.25],
                ],
                'default'    => ['unit' => 'rem', 'size' => 0.75],
                'selectors'  => [
                    '{{WRAPPER}} .mc-counter' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'counter_bg_color',
            [
                'label'     => __('Cor de Fundo', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#7F0B0D',
                'selectors' => [
                    '{{WRAPPER}} .mc-counter' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'counter_text_color',
            [
                'label'     => __('Cor do Texto', 'starter-theme'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .mc-counter' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'counter_position_top',
            [
                'label'      => __('Posição Vertical', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem'],
                'range'      => [
                    'px'  => ['min' => -20, 'max' => 20],
                    'rem' => ['min' => -1.25, 'max' => 1.25],
                ],
                'default'    => ['unit' => 'rem', 'size' => -0.25],
                'selectors'  => [
                    '{{WRAPPER}} .mc-counter' => 'top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'counter_position_right',
            [
                'label'      => __('Posição Horizontal', 'starter-theme'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem'],
                'range'      => [
                    'px'  => ['min' => -20, 'max' => 20],
                    'rem' => ['min' => -1.25, 'max' => 1.25],
                ],
                'default'    => ['unit' => 'rem', 'size' => -0.25],
                'selectors'  => [
                    '{{WRAPPER}} .mc-counter' => 'right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        // Obter contagem do carrinho (via sessão/cookie do WordPress)
        $cart_count = 0;
        
        // Tenta obter da sessão PHP se disponível
        if (isset($_SESSION['cursos_cart']) && is_array($_SESSION['cursos_cart'])) {
            $cart_count = count($_SESSION['cursos_cart']);
        }
        
        // Fallback: tenta obter de cookie
        if ($cart_count === 0 && isset($_COOKIE['cursos_cart_count'])) {
            $cart_count = intval($_COOKIE['cursos_cart_count']);
        }

        // URL do carrinho
        $cart_url = !empty($settings['cart_url']['url']) ? $settings['cart_url']['url'] : home_url('/carrinho');
        $target = !empty($settings['cart_url']['is_external']) ? '_blank' : '_self';
        $nofollow = !empty($settings['cart_url']['nofollow']) ? 'nofollow' : '';

        // Verificar se deve esconder contador vazio
        $show_counter = $settings['show_counter'] === 'yes';
        $hide_empty = $settings['hide_empty'] === 'yes';
        $display_counter = $show_counter && (!$hide_empty || $cart_count > 0);
        ?>
        <style>
            .mc-wrapper {
                display: inline-flex;
                align-items: center;
            }
            .mc-button {
                position: relative;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.5rem;
                background: transparent;
                border: none;
                border-radius: 0.375rem;
                cursor: pointer;
                transition: all 0.2s ease;
                text-decoration: none;
            }
            .mc-button:hover {
                background: rgba(0, 0, 0, 0.05);
            }
            .mc-icon {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .mc-icon svg {
                width: 20px;
                height: 20px;
                color: #374151;
                stroke: currentColor;
                transition: color 0.2s ease;
            }
            .mc-button:hover .mc-icon svg {
                color: #7F0B0D;
            }
            .mc-counter {
                position: absolute;
                top: -0.25rem;
                right: -0.25rem;
                width: 1.25rem;
                height: 1.25rem;
                border-radius: 50%;
                background: #7F0B0D;
                color: #ffffff;
                font-size: 0.75rem;
                font-weight: 500;
                display: flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
            }
            .mc-counter.mc-hidden {
                display: none;
            }
        </style>

        <div class="mc-wrapper">
            <a href="<?php echo esc_url($cart_url); ?>" 
               class="mc-button" 
               target="<?php echo esc_attr($target); ?>"
               <?php echo $nofollow ? 'rel="nofollow"' : ''; ?>
               aria-label="<?php esc_attr_e('Carrinho de compras', 'starter-theme'); ?>">
                <span class="mc-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="21" r="1"/>
                        <circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                </span>
                <?php if ($show_counter): ?>
                <span class="mc-counter <?php echo (!$display_counter) ? 'mc-hidden' : ''; ?>" data-cart-counter>
                    <?php echo esc_html($cart_count); ?>
                </span>
                <?php endif; ?>
            </a>
        </div>

        <script>
        (function() {
            var PHP_COUNT = <?php echo intval($cart_count); ?>;
            var HIDE_EMPTY = <?php echo $hide_empty ? 'true' : 'false'; ?>;
            var AJAX_URL = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
            var currentCount = PHP_COUNT;

            function render(count) {
                var counter = document.querySelector('[data-cart-counter]');
                if (!counter) return;
                currentCount = count;
                counter.textContent = count;
                if (HIDE_EMPTY && count === 0) counter.classList.add('mc-hidden');
                else counter.classList.remove('mc-hidden');
            }

            function syncLocalStorage(count) {
                try {
                    localStorage.setItem('cursos_cart_updated', JSON.stringify({count: count, timestamp: Date.now()}));
                } catch(e) {}
            }

            function init() {
                // PHP é a fonte de verdade no page load — sobrescreve localStorage obsoleto
                render(PHP_COUNT);
                syncLocalStorage(PHP_COUNT);
            }

            function refreshFromServer() {
                try {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', AJAX_URL, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        if (xhr.status !== 200) return;
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res && res.success && res.data && typeof res.data.cart_count === 'number') {
                                if (res.data.cart_count !== currentCount) {
                                    render(res.data.cart_count);
                                    syncLocalStorage(res.data.cart_count);
                                }
                            }
                        } catch(e) {}
                    };
                    xhr.send('action=get_cart_count');
                } catch(e) {}
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }

            // Sincronização entre abas
            window.addEventListener('storage', function(e) {
                if (e.key === 'cursos_cart_updated') {
                    try {
                        var data = JSON.parse(e.newValue || '{}');
                        if (data && typeof data.count === 'number') render(data.count);
                    } catch(err) {}
                }
            });

            // Eventos locais (add/remove)
            function onCartUpdate(e) {
                if (e && e.detail && typeof e.detail.count === 'number') {
                    render(e.detail.count);
                    syncLocalStorage(e.detail.count);
                } else {
                    refreshFromServer();
                }
            }
            window.addEventListener('cartUpdated', onCartUpdate);
            document.addEventListener('cartUpdated', onCartUpdate);

            // Revalidar contra o servidor periodicamente e ao focar a aba (detecta sessão expirada)
            setInterval(refreshFromServer, 30000);
            window.addEventListener('focus', refreshFromServer);
        })();
        </script>
        <?php
    }
}
