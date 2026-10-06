<?php
/**
 * Classe para gerenciamento de Cupons de Desconto
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cursos_Cupons {
    
    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_boxes'));
        add_action('save_post_cupom', array($this, 'save_coupon_meta'));
        add_action('wp_ajax_validate_coupon', array($this, 'ajax_validate_coupon'));
        add_action('wp_ajax_nopriv_validate_coupon', array($this, 'ajax_validate_coupon'));
        add_filter('manage_cupom_posts_columns', array($this, 'coupon_columns'));
        add_action('manage_cupom_posts_custom_column', array($this, 'coupon_column_content'), 10, 2);
    }
    
    /**
     * Registra o Custom Post Type para Cupons
     */
    public function register_coupon_post_type() {
        $labels = array(
            'name'               => 'Cupons',
            'singular_name'      => 'Cupom',
            'menu_name'          => 'Cupons',
            'add_new'            => 'Adicionar Cupom',
            'add_new_item'       => 'Adicionar Novo Cupom',
            'edit_item'          => 'Editar Cupom',
            'new_item'           => 'Novo Cupom',
            'view_item'          => 'Ver Cupom',
            'search_items'       => 'Buscar Cupons',
            'not_found'          => 'Nenhum cupom encontrado',
            'not_found_in_trash' => 'Nenhum cupom na lixeira'
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false, // Ocultar - será exibido via menu Pagamentos
            'menu_position'       => 27,
            'menu_icon'           => 'dashicons-tickets-alt',
            'supports'            => array('title'),
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'post',
        );
        
        register_post_type('cupom', $args);
    }
    
    /**
     * Adiciona meta boxes para configuração do cupom
     */
    public function add_coupon_meta_boxes() {
        add_meta_box(
            'cupom_settings',
            'Configurações do Cupom',
            array($this, 'render_coupon_settings_metabox'),
            'cupom',
            'normal',
            'high'
        );
        
        add_meta_box(
            'cupom_usage',
            'Estatísticas de Uso',
            array($this, 'render_coupon_usage_metabox'),
            'cupom',
            'side',
            'default'
        );
    }
    
    /**
     * Renderiza o meta box de configurações
     */
    public function render_coupon_settings_metabox($post) {
        wp_nonce_field('cupom_settings_nonce', 'cupom_nonce');
        
        $codigo = get_post_meta($post->ID, '_cupom_codigo', true);
        $tipo_desconto = get_post_meta($post->ID, '_cupom_tipo_desconto', true);
        $valor_desconto = get_post_meta($post->ID, '_cupom_valor_desconto', true);
        $valor_minimo = get_post_meta($post->ID, '_cupom_valor_minimo', true);
        $valor_maximo_desconto = get_post_meta($post->ID, '_cupom_valor_maximo_desconto', true);
        $data_inicio = get_post_meta($post->ID, '_cupom_data_inicio', true);
        $data_fim = get_post_meta($post->ID, '_cupom_data_fim', true);
        $limite_uso = get_post_meta($post->ID, '_cupom_limite_uso', true);
        $limite_por_usuario = get_post_meta($post->ID, '_cupom_limite_por_usuario', true);
        $cursos_permitidos = get_post_meta($post->ID, '_cupom_cursos_permitidos', true);
        $categorias_permitidas = get_post_meta($post->ID, '_cupom_categorias_permitidas', true);
        $ativo = get_post_meta($post->ID, '_cupom_ativo', true);
        $bloquear_outros_descontos = get_post_meta($post->ID, '_cupom_bloquear_outros_descontos', true);
        
        // Define valores padrão
        if (empty($tipo_desconto)) $tipo_desconto = 'percentual';
        if ($ativo === '') $ativo = '1';
        if ($bloquear_outros_descontos === '') $bloquear_outros_descontos = '1'; // Padrão: bloquear
        ?>
        
        <style>
            .cupom-settings-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            .cupom-field {
                margin-bottom: 15px;
            }
            .cupom-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
            }
            .cupom-field input[type="text"],
            .cupom-field input[type="number"],
            .cupom-field input[type="date"],
            .cupom-field select {
                width: 100%;
                padding: 8px;
            }
            .cupom-field-full {
                grid-column: span 2;
            }
            .cupom-field .description {
                color: #666;
                font-size: 12px;
                margin-top: 5px;
            }
            .cupom-status-toggle {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .cupom-status-toggle input[type="checkbox"] {
                width: 20px;
                height: 20px;
            }
        </style>
        
        <div class="cupom-settings-grid">
            <div class="cupom-field">
                <label for="cupom_codigo">Código do Cupom *</label>
                <input type="text" id="cupom_codigo" name="cupom_codigo" 
                       value="<?php echo esc_attr($codigo); ?>" 
                       style="text-transform: uppercase;" required>
                <p class="description">Código que o cliente irá digitar (ex: DESCONTO10)</p>
            </div>
            
            <div class="cupom-field">
                <label>Status</label>
                <div class="cupom-status-toggle">
                    <input type="checkbox" id="cupom_ativo" name="cupom_ativo" 
                           value="1" <?php checked($ativo, '1'); ?>>
                    <label for="cupom_ativo" style="font-weight: normal;">Cupom Ativo</label>
                </div>
                <div class="cupom-status-toggle" style="margin-top: 10px;">
                    <input type="checkbox" id="cupom_bloquear_outros_descontos" name="cupom_bloquear_outros_descontos" 
                           value="1" <?php checked($bloquear_outros_descontos, '1'); ?>>
                    <label for="cupom_bloquear_outros_descontos" style="font-weight: normal;">Bloquear uso com outros descontos (PIX/Estudante)</label>
                </div>
            </div>
            
            <div class="cupom-field">
                <label for="cupom_tipo_desconto">Tipo de Desconto *</label>
                <select id="cupom_tipo_desconto" name="cupom_tipo_desconto">
                    <option value="percentual" <?php selected($tipo_desconto, 'percentual'); ?>>
                        Percentual (%)
                    </option>
                    <option value="fixo" <?php selected($tipo_desconto, 'fixo'); ?>>
                        Valor Fixo (R$)
                    </option>
                </select>
            </div>
            
            <div class="cupom-field">
                <label for="cupom_valor_desconto">Valor do Desconto *</label>
                <input type="number" id="cupom_valor_desconto" name="cupom_valor_desconto" 
                       value="<?php echo esc_attr($valor_desconto); ?>" 
                       step="0.01" min="0" required>
                <p class="description">Valor em % ou R$ conforme tipo selecionado</p>
            </div>
            
            <div class="cupom-field">
                <label for="cupom_valor_minimo">Valor Mínimo do Pedido</label>
                <input type="number" id="cupom_valor_minimo" name="cupom_valor_minimo" 
                       value="<?php echo esc_attr($valor_minimo); ?>" 
                       step="0.01" min="0">
                <p class="description">Valor mínimo em R$ para usar o cupom (opcional)</p>
            </div>
            
            <div class="cupom-field">
                <label for="cupom_valor_maximo_desconto">Desconto Máximo (R$)</label>
                <input type="number" id="cupom_valor_maximo_desconto" name="cupom_valor_maximo_desconto" 
                       value="<?php echo esc_attr($valor_maximo_desconto); ?>" 
                       step="0.01" min="0">
                <p class="description">Limite máximo de desconto em R$ (apenas para %)</p>
            </div>
            
            <div class="cupom-field">
                <label for="cupom_data_inicio">Data de Início</label>
                <input type="date" id="cupom_data_inicio" name="cupom_data_inicio" 
                       value="<?php echo esc_attr($data_inicio); ?>">
            </div>
            
            <div class="cupom-field">
                <label for="cupom_data_fim">Data de Expiração</label>
                <input type="date" id="cupom_data_fim" name="cupom_data_fim" 
                       value="<?php echo esc_attr($data_fim); ?>">
            </div>
            
            <div class="cupom-field">
                <label for="cupom_limite_uso">Limite de Usos Total</label>
                <input type="number" id="cupom_limite_uso" name="cupom_limite_uso" 
                       value="<?php echo esc_attr($limite_uso); ?>" min="0">
                <p class="description">Deixe vazio para uso ilimitado</p>
            </div>
            
            <div class="cupom-field">
                <label for="cupom_limite_por_usuario">Limite por Usuário</label>
                <input type="number" id="cupom_limite_por_usuario" name="cupom_limite_por_usuario" 
                       value="<?php echo esc_attr($limite_por_usuario); ?>" min="1">
                <p class="description">Quantas vezes cada pessoa pode usar</p>
            </div>
            
            <div class="cupom-field cupom-field-full">
                <label>Cursos Permitidos</label>
                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                    <?php
                    $cursos = get_posts(array('post_type' => 'curso', 'numberposts' => -1));
                    $cursos_array = is_array($cursos_permitidos) ? $cursos_permitidos : array();
                    
                    if (empty($cursos)) {
                        echo '<p>Nenhum curso cadastrado.</p>';
                    } else {
                        echo '<label style="display: block; margin-bottom: 10px; font-weight: normal;">';
                        echo '<input type="checkbox" id="todos_cursos" ' . (empty($cursos_array) ? 'checked' : '') . '> ';
                        echo '<strong>Todos os cursos</strong></label>';
                        
                        foreach ($cursos as $curso) {
                            $checked = in_array($curso->ID, $cursos_array) ? 'checked' : '';
                            echo '<label style="display: block; margin-bottom: 5px; font-weight: normal;">';
                            echo '<input type="checkbox" name="cupom_cursos_permitidos[]" class="curso-checkbox" value="' . $curso->ID . '" ' . $checked . '> ';
                            echo esc_html($curso->post_title);
                            echo '</label>';
                        }
                    }
                    ?>
                </div>
                <p class="description">Deixe vazio para aplicar a todos os cursos</p>
            </div>
            
            <div class="cupom-field cupom-field-full">
                <label>Categorias Permitidas</label>
                <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                    <?php
                    $categorias = get_terms(array('taxonomy' => 'categoria_curso', 'hide_empty' => false));
                    $categorias_array = is_array($categorias_permitidas) ? $categorias_permitidas : array();
                    
                    if (empty($categorias) || is_wp_error($categorias)) {
                        echo '<p>Nenhuma categoria cadastrada.</p>';
                    } else {
                        echo '<label style="display: block; margin-bottom: 10px; font-weight: normal;">';
                        echo '<input type="checkbox" id="todas_categorias" ' . (empty($categorias_array) ? 'checked' : '') . '> ';
                        echo '<strong>Todas as categorias</strong></label>';
                        
                        foreach ($categorias as $categoria) {
                            $checked = in_array($categoria->term_id, $categorias_array) ? 'checked' : '';
                            echo '<label style="display: block; margin-bottom: 5px; font-weight: normal;">';
                            echo '<input type="checkbox" name="cupom_categorias_permitidas[]" class="categoria-checkbox" value="' . $categoria->term_id . '" ' . $checked . '> ';
                            echo esc_html($categoria->name);
                            echo '</label>';
                        }
                    }
                    ?>
                </div>
                <p class="description">Deixe vazio para aplicar a todas as categorias</p>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Auto uppercase no código
            $('#cupom_codigo').on('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });
            
            // Toggle todos os cursos
            $('#todos_cursos').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.curso-checkbox').prop('checked', false);
                }
            });
            
            $('.curso-checkbox').on('change', function() {
                if ($('.curso-checkbox:checked').length > 0) {
                    $('#todos_cursos').prop('checked', false);
                } else {
                    $('#todos_cursos').prop('checked', true);
                }
            });
            
            // Toggle todas as categorias
            $('#todas_categorias').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.categoria-checkbox').prop('checked', false);
                }
            });
            
            $('.categoria-checkbox').on('change', function() {
                if ($('.categoria-checkbox:checked').length > 0) {
                    $('#todas_categorias').prop('checked', false);
                } else {
                    $('#todas_categorias').prop('checked', true);
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Renderiza o meta box de estatísticas
     */
    public function render_coupon_usage_metabox($post) {
        $total_usos = get_post_meta($post->ID, '_cupom_total_usos', true);
        $total_usos = $total_usos ? intval($total_usos) : 0;
        $limite = get_post_meta($post->ID, '_cupom_limite_uso', true);
        
        ?>
        <div style="text-align: center; padding: 10px;">
            <div style="font-size: 36px; font-weight: bold; color: #0073aa;">
                <?php echo $total_usos; ?>
            </div>
            <div style="color: #666;">
                <?php 
                if ($limite) {
                    echo 'de ' . $limite . ' usos';
                } else {
                    echo 'usos totais';
                }
                ?>
            </div>
        </div>
        
        <?php if ($total_usos > 0): ?>
        <hr>
        <p><strong>Últimos usos:</strong></p>
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'cursos_cupom_usos';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            $usos = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE cupom_id = %d ORDER BY data_uso DESC LIMIT 5",
                $post->ID
            ));
            
            if ($usos) {
                echo '<ul style="margin: 0; padding-left: 20px;">';
                foreach ($usos as $uso) {
                    echo '<li style="font-size: 12px;">';
                    echo esc_html($uso->email) . '<br>';
                    echo '<small>' . date('d/m/Y H:i', strtotime($uso->data_uso)) . '</small>';
                    echo '</li>';
                }
                echo '</ul>';
            }
        }
        ?>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Salva os meta dados do cupom
     */
    public function save_coupon_meta($post_id) {
        if (!isset($_POST['cupom_nonce']) || !wp_verify_nonce($_POST['cupom_nonce'], 'cupom_settings_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Código do cupom (uppercase)
        if (isset($_POST['cupom_codigo'])) {
            $codigo = strtoupper(sanitize_text_field($_POST['cupom_codigo']));
            $codigo = preg_replace('/[^A-Z0-9]/', '', $codigo);
            update_post_meta($post_id, '_cupom_codigo', $codigo);
        }
        
        // Status ativo
        $ativo = isset($_POST['cupom_ativo']) ? '1' : '0';
        update_post_meta($post_id, '_cupom_ativo', $ativo);
        
        // Bloquear outros descontos
        $bloquear = isset($_POST['cupom_bloquear_outros_descontos']) ? '1' : '0';
        update_post_meta($post_id, '_cupom_bloquear_outros_descontos', $bloquear);
        
        // Tipo de desconto
        if (isset($_POST['cupom_tipo_desconto'])) {
            update_post_meta($post_id, '_cupom_tipo_desconto', sanitize_text_field($_POST['cupom_tipo_desconto']));
        }
        
        // Valor do desconto
        if (isset($_POST['cupom_valor_desconto'])) {
            update_post_meta($post_id, '_cupom_valor_desconto', floatval($_POST['cupom_valor_desconto']));
        }
        
        // Valor mínimo
        if (isset($_POST['cupom_valor_minimo'])) {
            update_post_meta($post_id, '_cupom_valor_minimo', floatval($_POST['cupom_valor_minimo']));
        }
        
        // Valor máximo de desconto
        if (isset($_POST['cupom_valor_maximo_desconto'])) {
            update_post_meta($post_id, '_cupom_valor_maximo_desconto', floatval($_POST['cupom_valor_maximo_desconto']));
        }
        
        // Datas
        if (isset($_POST['cupom_data_inicio'])) {
            update_post_meta($post_id, '_cupom_data_inicio', sanitize_text_field($_POST['cupom_data_inicio']));
        }
        
        if (isset($_POST['cupom_data_fim'])) {
            update_post_meta($post_id, '_cupom_data_fim', sanitize_text_field($_POST['cupom_data_fim']));
        }
        
        // Limites
        if (isset($_POST['cupom_limite_uso'])) {
            update_post_meta($post_id, '_cupom_limite_uso', intval($_POST['cupom_limite_uso']));
        }
        
        if (isset($_POST['cupom_limite_por_usuario'])) {
            update_post_meta($post_id, '_cupom_limite_por_usuario', intval($_POST['cupom_limite_por_usuario']));
        }
        
        // Cursos permitidos
        $cursos = isset($_POST['cupom_cursos_permitidos']) ? array_map('intval', $_POST['cupom_cursos_permitidos']) : array();
        update_post_meta($post_id, '_cupom_cursos_permitidos', $cursos);
        
        // Categorias permitidas
        $categorias = isset($_POST['cupom_categorias_permitidas']) ? array_map('intval', $_POST['cupom_categorias_permitidas']) : array();
        update_post_meta($post_id, '_cupom_categorias_permitidas', $categorias);
    }
    
    /**
     * Colunas personalizadas na listagem
     */
    public function coupon_columns($columns) {
        $new_columns = array(
            'cb' => $columns['cb'],
            'title' => 'Nome',
            'codigo' => 'Código',
            'desconto' => 'Desconto',
            'validade' => 'Validade',
            'usos' => 'Usos',
            'status' => 'Status',
            'date' => $columns['date']
        );
        return $new_columns;
    }
    
    /**
     * Conteúdo das colunas personalizadas
     */
    public function coupon_column_content($column, $post_id) {
        switch ($column) {
            case 'codigo':
                $codigo = get_post_meta($post_id, '_cupom_codigo', true);
                echo '<code style="background: #f0f0f0; padding: 3px 8px; border-radius: 3px;">' . esc_html($codigo) . '</code>';
                break;
                
            case 'desconto':
                $tipo = get_post_meta($post_id, '_cupom_tipo_desconto', true);
                $valor = get_post_meta($post_id, '_cupom_valor_desconto', true);
                if ($tipo === 'percentual') {
                    echo $valor . '%';
                } else {
                    echo 'R$ ' . number_format($valor, 2, ',', '.');
                }
                break;
                
            case 'validade':
                $inicio = get_post_meta($post_id, '_cupom_data_inicio', true);
                $fim = get_post_meta($post_id, '_cupom_data_fim', true);
                
                if ($inicio || $fim) {
                    if ($inicio) echo date('d/m/Y', strtotime($inicio));
                    if ($inicio && $fim) echo ' - ';
                    if ($fim) echo date('d/m/Y', strtotime($fim));
                } else {
                    echo '<span style="color: #999;">Sem limite</span>';
                }
                break;
                
            case 'usos':
                $total = get_post_meta($post_id, '_cupom_total_usos', true) ?: 0;
                $limite = get_post_meta($post_id, '_cupom_limite_uso', true);
                
                echo $total;
                if ($limite) {
                    echo ' / ' . $limite;
                }
                break;
                
            case 'status':
                $ativo = get_post_meta($post_id, '_cupom_ativo', true);
                $valido = $this->is_coupon_valid($post_id);
                
                if (!$ativo || $ativo === '0') {
                    echo '<span style="color: #999; background: #f0f0f0; padding: 3px 8px; border-radius: 3px;">Inativo</span>';
                } elseif (!$valido) {
                    echo '<span style="color: #a00; background: #fee; padding: 3px 8px; border-radius: 3px;">Expirado</span>';
                } else {
                    echo '<span style="color: #0a0; background: #efe; padding: 3px 8px; border-radius: 3px;">Ativo</span>';
                }
                break;
        }
    }
    
    /**
     * Verifica se o cupom está válido (data e uso)
     */
    public function is_coupon_valid($post_id) {
        $hoje = date('Y-m-d');
        $inicio = get_post_meta($post_id, '_cupom_data_inicio', true);
        $fim = get_post_meta($post_id, '_cupom_data_fim', true);
        
        if ($inicio && $hoje < $inicio) {
            return false;
        }
        
        if ($fim && $hoje > $fim) {
            return false;
        }
        
        $limite = get_post_meta($post_id, '_cupom_limite_uso', true);
        $total_usos = get_post_meta($post_id, '_cupom_total_usos', true) ?: 0;
        
        if ($limite && $total_usos >= $limite) {
            return false;
        }
        
        return true;
    }
    
    /**
     * AJAX - Validar cupom
     */
    public function ajax_validate_coupon() {
        $codigo = isset($_POST['codigo']) ? strtoupper(sanitize_text_field($_POST['codigo'])) : '';
        $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        // Cursos pode vir como JSON string ou array
        $cursos_raw = isset($_POST['cursos']) ? $_POST['cursos'] : array();
        if (is_string($cursos_raw)) {
            $cursos = json_decode(stripslashes($cursos_raw), true);
            if (!is_array($cursos)) {
                $cursos = array();
            }
        } else {
            $cursos = $cursos_raw;
        }
        $cursos = array_map('intval', $cursos);
        
        $result = $this->validate_coupon($codigo, $subtotal, $cursos, $email);
        
        wp_send_json($result);
    }
    
    /**
     * Valida um cupom
     */
    public function validate_coupon($codigo, $subtotal = 0, $cursos = array(), $email = '') {
        // Busca o cupom pelo código
        $cupom = get_posts(array(
            'post_type' => 'cupom',
            'meta_query' => array(
                array(
                    'key' => '_cupom_codigo',
                    'value' => $codigo,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (empty($cupom)) {
            return array(
                'valid' => false,
                'message' => 'Cupom não encontrado.'
            );
        }
        
        $cupom = $cupom[0];
        $cupom_id = $cupom->ID;
        
        // Verifica se está ativo
        $ativo = get_post_meta($cupom_id, '_cupom_ativo', true);
        if (!$ativo || $ativo === '0') {
            return array(
                'valid' => false,
                'message' => 'Este cupom está inativo.'
            );
        }
        
        // Verifica validade de datas
        if (!$this->is_coupon_valid($cupom_id)) {
            return array(
                'valid' => false,
                'message' => 'Este cupom está expirado ou ainda não é válido.'
            );
        }
        
        // Verifica valor mínimo
        $valor_minimo = get_post_meta($cupom_id, '_cupom_valor_minimo', true);
        if ($valor_minimo && $subtotal < $valor_minimo) {
            return array(
                'valid' => false,
                'message' => 'Valor mínimo do pedido: R$ ' . number_format($valor_minimo, 2, ',', '.')
            );
        }
        
        // Verifica limite por usuário
        if ($email) {
            $limite_usuario = get_post_meta($cupom_id, '_cupom_limite_por_usuario', true);
            if ($limite_usuario) {
                $usos_usuario = $this->get_user_usage_count($cupom_id, $email);
                if ($usos_usuario >= $limite_usuario) {
                    return array(
                        'valid' => false,
                        'message' => 'Você já utilizou este cupom o número máximo de vezes.'
                    );
                }
            }
        }
        
        // Verifica cursos permitidos
        $cursos_permitidos = get_post_meta($cupom_id, '_cupom_cursos_permitidos', true);
        if (!empty($cursos_permitidos) && !empty($cursos)) {
            $cursos_validos = array_intersect($cursos, $cursos_permitidos);
            if (empty($cursos_validos)) {
                return array(
                    'valid' => false,
                    'message' => 'Este cupom não é válido para os cursos selecionados.'
                );
            }
        }
        
        // Calcula o desconto
        $tipo_desconto = get_post_meta($cupom_id, '_cupom_tipo_desconto', true);
        $valor_desconto = get_post_meta($cupom_id, '_cupom_valor_desconto', true);
        $valor_maximo = get_post_meta($cupom_id, '_cupom_valor_maximo_desconto', true);
        
        $desconto = 0;
        if ($tipo_desconto === 'percentual') {
            $desconto = $subtotal * ($valor_desconto / 100);
            if ($valor_maximo && $desconto > $valor_maximo) {
                $desconto = $valor_maximo;
            }
        } else {
            $desconto = $valor_desconto;
            if ($desconto > $subtotal) {
                $desconto = $subtotal;
            }
        }
        
        // Verificar se bloqueia outros descontos (padrão: bloquear se não definido)
        $bloquear_outros = get_post_meta($cupom_id, '_cupom_bloquear_outros_descontos', true);
        if ($bloquear_outros === '') {
            $bloquear_outros = '1'; // Padrão: bloquear outros descontos
        }
        
        return array(
            'valid' => true,
            'cupom_id' => $cupom_id,
            'codigo' => $codigo,
            'tipo' => $tipo_desconto,
            'valor_desconto' => $valor_desconto,
            'desconto_calculado' => $desconto,
            'desconto_formatado' => 'R$ ' . number_format($desconto, 2, ',', '.'),
            'bloquear_outros_descontos' => $bloquear_outros === '1',
            'message' => 'Cupom aplicado com sucesso!'
        );
    }
    
    /**
     * Conta quantas vezes um email usou um cupom
     */
    public function get_user_usage_count($cupom_id, $email) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cursos_cupom_usos';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return 0;
        }
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE cupom_id = %d AND email = %s",
            $cupom_id,
            $email
        ));
    }
    
    /**
     * Registra o uso de um cupom
     */
    public function register_coupon_usage($cupom_id, $email, $order_id = 0, $valor_desconto = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cursos_cupom_usos';
        
        // Cria a tabela se não existir
        $this->create_usage_table();
        
        // Insere o registro de uso
        $wpdb->insert($table_name, array(
            'cupom_id' => $cupom_id,
            'email' => $email,
            'order_id' => $order_id,
            'valor_desconto' => $valor_desconto,
            'data_uso' => current_time('mysql')
        ));
        
        // Atualiza o contador total
        $total = get_post_meta($cupom_id, '_cupom_total_usos', true) ?: 0;
        update_post_meta($cupom_id, '_cupom_total_usos', $total + 1);
    }
    
    /**
     * Cria a tabela de usos de cupom
     */
    public function create_usage_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cursos_cupom_usos';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cupom_id bigint(20) NOT NULL,
            email varchar(255) NOT NULL,
            order_id bigint(20) DEFAULT 0,
            valor_desconto decimal(10,2) DEFAULT 0,
            data_uso datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY cupom_id (cupom_id),
            KEY email (email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Inicializa a classe
new Cursos_Cupons();
