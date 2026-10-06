<?php
/**
 * Gerenciador de Descontos Automáticos
 * 
 * - Desconto PIX: 10% para pagamento via PIX
 * - Desconto Estudante: 10% para estudantes (não acumula com PIX)
 * 
 * @package CursosTheme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cursos_Descontos {
    
    /**
     * Percentual de desconto PIX (padrão 10%)
     */
    private $pix_discount_percent;
    
    /**
     * Percentual de desconto estudante (padrão 10%)
     */
    private $student_discount_percent;
    
    /**
     * Se o desconto PIX está ativo
     */
    private $pix_discount_active;
    
    /**
     * Se o desconto estudante está ativo
     */
    private $student_discount_active;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->pix_discount_percent = floatval(get_option('cursos_desconto_pix_percent', 10));
        $this->student_discount_percent = floatval(get_option('cursos_desconto_estudante_percent', 10));
        $this->pix_discount_active = get_option('cursos_desconto_pix_ativo', '1') === '1';
        $this->student_discount_active = get_option('cursos_desconto_estudante_ativo', '1') === '1';
    }
    
    /**
     * Verifica se o desconto PIX está ativo
     */
    public function is_pix_discount_active() {
        return $this->pix_discount_active;
    }
    
    /**
     * Verifica se o desconto estudante está ativo
     */
    public function is_student_discount_active() {
        return $this->student_discount_active;
    }
    
    /**
     * Retorna o percentual de desconto PIX
     */
    public function get_pix_discount_percent() {
        return $this->pix_discount_percent;
    }
    
    /**
     * Retorna o percentual de desconto estudante
     */
    public function get_student_discount_percent() {
        return $this->student_discount_percent;
    }
    
    /**
     * Calcula o desconto baseado na forma de pagamento e tipo de cliente
     * 
     * Regras:
     * - Estudante recebe desconto de estudante (10%)
     * - Estudante NÃO recebe desconto adicional do PIX
     * - Não-estudante pagando com PIX recebe desconto PIX (10%)
     * 
     * @param float $subtotal Valor original
     * @param string $payment_method Método de pagamento (pix, boleto, credit_card)
     * @param bool $is_student Se o cliente é estudante
     * @return array Array com detalhes do desconto
     */
    public function calculate_discount($subtotal, $payment_method = '', $is_student = false) {
        $result = array(
            'subtotal' => $subtotal,
            'pix_discount' => 0,
            'pix_discount_percent' => 0,
            'student_discount' => 0,
            'student_discount_percent' => 0,
            'total_discount' => 0,
            'final_total' => $subtotal,
            'discount_type' => 'none', // none, pix, student
            'discount_message' => '',
        );
        
        // Se é estudante, aplica desconto de estudante (prioridade)
        if ($is_student && $this->student_discount_active) {
            $student_discount = $subtotal * ($this->student_discount_percent / 100);
            $result['student_discount'] = $student_discount;
            $result['student_discount_percent'] = $this->student_discount_percent;
            $result['total_discount'] = $student_discount;
            $result['final_total'] = $subtotal - $student_discount;
            $result['discount_type'] = 'student';
            $result['discount_message'] = sprintf(
                'Desconto estudante de %d%% aplicado!',
                $this->student_discount_percent
            );
            
            // Estudante NÃO recebe desconto PIX adicional
            return $result;
        }
        
        // Se não é estudante e paga com PIX, aplica desconto PIX
        if ($payment_method === 'pix' && $this->pix_discount_active) {
            $pix_discount = $subtotal * ($this->pix_discount_percent / 100);
            $result['pix_discount'] = $pix_discount;
            $result['pix_discount_percent'] = $this->pix_discount_percent;
            $result['total_discount'] = $pix_discount;
            $result['final_total'] = $subtotal - $pix_discount;
            $result['discount_type'] = 'pix';
            $result['discount_message'] = sprintf(
                'Desconto de %d%% no PIX aplicado!',
                $this->pix_discount_percent
            );
        }
        
        // Garantir que o total não seja negativo
        if ($result['final_total'] < 0) {
            $result['final_total'] = 0;
        }
        
        return $result;
    }
    
    /**
     * Retorna informações de desconto para exibição no frontend
     */
    public function get_discount_info() {
        return array(
            'pix_active' => $this->pix_discount_active,
            'pix_percent' => $this->pix_discount_percent,
            'student_active' => $this->student_discount_active,
            'student_percent' => $this->student_discount_percent,
        );
    }
    
    /**
     * Formata valor como preço em Real
     */
    public static function format_price($value) {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}

/**
 * Retorna instância da classe de descontos
 */
function cursos_descontos() {
    static $instance = null;
    if ($instance === null) {
        $instance = new Cursos_Descontos();
    }
    return $instance;
}

/**
 * AJAX handler para calcular desconto em tempo real
 */
add_action('wp_ajax_calculate_discount', 'cursos_ajax_calculate_discount');
add_action('wp_ajax_nopriv_calculate_discount', 'cursos_ajax_calculate_discount');

function cursos_ajax_calculate_discount() {
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');
    $is_student = ($_POST['is_student'] ?? '0') === '1';
    $coupon_discount = floatval($_POST['coupon_discount'] ?? 0);
    
    // Calcular subtotal após cupom
    $subtotal_after_coupon = $subtotal - $coupon_discount;
    if ($subtotal_after_coupon < 0) $subtotal_after_coupon = 0;
    
    $descontos = cursos_descontos();
    $result = $descontos->calculate_discount($subtotal_after_coupon, $payment_method, $is_student);
    
    // Adicionar valores formatados para frontend
    $result['pix_discount_formatted'] = Cursos_Descontos::format_price($result['pix_discount']);
    $result['student_discount_formatted'] = Cursos_Descontos::format_price($result['student_discount']);
    $result['total_discount_formatted'] = Cursos_Descontos::format_price($result['total_discount']);
    $result['final_total_formatted'] = Cursos_Descontos::format_price($result['final_total']);
    
    wp_send_json($result);
}
