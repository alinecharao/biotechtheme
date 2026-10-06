<?php
/**
 * Classe de Integração com PagSeguro
 * 
 * @package CursosTheme
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cursos_Payment_PagSeguro {
    
    private $email;
    private $token;
    private $ambiente;
    private $base_url;
    
    public function __construct() {
        $this->email = trim((string) get_option('cursos_pagseguro_email'));
        $raw_token = (string) get_option('cursos_pagseguro_token');
        // Sanitiza: remove espaços, quebras de linha e prefixo "Bearer " caso o usuário tenha colado junto
        $raw_token = trim($raw_token);
        $raw_token = preg_replace('/^Bearer\s+/i', '', $raw_token);
        $raw_token = preg_replace('/\s+/', '', $raw_token);
        $this->token = $raw_token;
        $this->ambiente = get_option('cursos_pagseguro_ambiente', 'sandbox');
        
        $this->base_url = $this->ambiente === 'producao' 
            ? 'https://api.pagseguro.com'
            : 'https://sandbox.api.pagseguro.com';
    }
    
    /**
     * Verificar se o PagSeguro está ativo e configurado
     */
    public function is_active() {
        return get_option('cursos_pagseguro_ativo') === '1' && !empty($this->email) && !empty($this->token);
    }
    
    /**
     * Obter ambiente configurado
     */
    public function get_ambiente() {
        return $this->ambiente;
    }

    /**
     * Obter chave pública usada para criptografar cartões no navegador.
     * Consulta primeiro uma chave existente e cria uma nova somente se necessário.
     */
    public function get_public_key() {
        if (!$this->is_active()) {
            return array(
                'success' => false,
                'error' => 'PagSeguro não está ativo ou configurado.',
            );
        }

        $cache_key = 'cursos_pagseguro_public_key_' . $this->ambiente;
        $cached = get_transient($cache_key);
        if (!empty($cached)) {
            return array(
                'success' => true,
                'public_key' => $cached,
                'source' => 'cache',
            );
        }

        // A API oficial permite consultar a chave de cartão em /public-keys/card.
        $result = $this->request('/public-keys/card', 'GET');

        // Se ainda não existir uma chave para cartão, criar uma.
        if (!$result['success'] || empty($result['data']['public_key'])) {
            $result = $this->request('/public-keys', 'POST', array('type' => 'card'));
        }

        if ($result['success'] && !empty($result['data']['public_key'])) {
            $public_key = trim((string) $result['data']['public_key']);
            set_transient($cache_key, $public_key, 6 * HOUR_IN_SECONDS);

            return array(
                'success' => true,
                'public_key' => $public_key,
                'source' => 'api',
            );
        }

        return array(
            'success' => false,
            'error' => $result['error'] ?? 'Não foi possível obter a chave pública do PagBank.',
            'data' => $result['data'] ?? null,
        );
    }
    
    /**
     * Testar conexão com a API do PagSeguro
     * Verifica se as credenciais são válidas para o ambiente selecionado
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function test_connection() {
        if (empty($this->token)) {
            return array(
                'success' => false,
                'message' => 'Token não configurado',
                'code' => 'no_token'
            );
        }
        
        if (empty($this->email)) {
            return array(
                'success' => false,
                'message' => 'E-mail não configurado',
                'code' => 'no_email'
            );
        }
        
        // Testar via endpoint /public-keys que aceita o Token de Integração
        // (GET /orders exige OAuth Connect e retorna "Invalid credential" mesmo com token válido)
        $result = $this->request('/public-keys', 'POST', array('type' => 'card'));
        
        if ($result['success']) {
            return array(
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso!',
                'ambiente' => $this->ambiente,
                'code' => 'connected'
            );
        }
        
        // Analisar o erro
        $error = $result['error'] ?? '';
        $error_lower = strtolower($error);
        
        // Erro de autenticação pode indicar ambiente incorreto
        if (strpos($error_lower, 'unauthorized') !== false || 
            strpos($error_lower, '401') !== false ||
            strpos($error_lower, 'forbidden') !== false ||
            strpos($error_lower, '403') !== false) {
            
            $ambiente_sugerido = $this->ambiente === 'producao' ? 'sandbox' : 'producao';
            
            return array(
                'success' => false,
                'message' => 'Credenciais inválidas ou ambiente incorreto.',
                'ambiente_atual' => $this->ambiente,
                'ambiente_sugerido' => $ambiente_sugerido,
                'code' => 'auth_error',
                'fix_suggestion' => 'Verifique se o Token corresponde ao ambiente selecionado. Tokens de Sandbox não funcionam em Produção e vice-versa.'
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Erro ao conectar: ' . $error,
            'code' => 'connection_error'
        );
    }
    
    /**
     * Fazer requisição à API do PagSeguro
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        $url = $this->base_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'x-api-version' => '4.0',
            ),
            'timeout' => 30,
        );
        
        if ($data !== null) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message(),
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        $result = json_decode($body, true);
        
        if ($code >= 200 && $code < 300) {
            return array(
                'success' => true,
                'data' => $result,
            );
        }
        
        $error_message = 'Erro desconhecido';
        if (isset($result['error_messages'])) {
            $error_message = implode(', ', array_column($result['error_messages'], 'description'));
        }
        
        return array(
            'success' => false,
            'error' => $error_message,
            'data' => $result,
        );
    }
    
    /**
     * Criar pedido/cobrança
     */
    public function create_order($order_data) {
        $data = array(
            'reference_id' => $order_data['reference_id'],
            'customer' => array(
                'name' => $order_data['customer']['name'],
                'email' => $order_data['customer']['email'],
                'tax_id' => preg_replace('/[^0-9]/', '', $order_data['customer']['cpf']),
                'phones' => array(
                    array(
                        'country' => '55',
                        'area' => substr(preg_replace('/[^0-9]/', '', $order_data['customer']['phone']), 0, 2),
                        'number' => substr(preg_replace('/[^0-9]/', '', $order_data['customer']['phone']), 2),
                        'type' => 'MOBILE',
                    ),
                ),
            ),
            'items' => array(
                array(
                    'reference_id' => $order_data['item']['id'],
                    'name' => $order_data['item']['name'],
                    'quantity' => 1,
                    'unit_amount' => intval($order_data['item']['amount'] * 100), // Valor em centavos
                ),
            ),
            'notification_urls' => array(
                home_url('/wp-json/cursos/v1/pagseguro-webhook'),
            ),
        );
        
        // Adicionar método de pagamento conforme solicitado
        if ($order_data['payment_method'] === 'pix') {
            $data['charges'] = array(
                array(
                    'reference_id' => $order_data['reference_id'],
                    'description' => $order_data['item']['name'],
                    'amount' => array(
                        'value' => intval($order_data['item']['amount'] * 100),
                        'currency' => 'BRL',
                    ),
                    'payment_method' => array(
                        'type' => 'PIX',
                        'pix' => array(
                            'expiration_date' => date('c', strtotime('+24 hours')),
                        ),
                    ),
                ),
            );
        } elseif ($order_data['payment_method'] === 'credit_card') {
            $encrypted_card = isset($order_data['card']['encrypted'])
                ? trim((string) $order_data['card']['encrypted'])
                : '';

            if ($encrypted_card === '') {
                return array(
                    'success' => false,
                    'error' => 'Cartão não criptografado. Atualize a página e tente novamente.',
                );
            }

            $holder_name = sanitize_text_field($order_data['card']['holder_name'] ?? '');
            $holder_tax_id = preg_replace('/[^0-9]/', '', (string) ($order_data['card']['holder_tax_id'] ?? $order_data['customer']['cpf'] ?? ''));

            $data['charges'] = array(
                array(
                    'reference_id' => $order_data['reference_id'],
                    'description' => $order_data['item']['name'],
                    'amount' => array(
                        'value' => intval($order_data['item']['amount'] * 100),
                        'currency' => 'BRL',
                    ),
                    'payment_method' => array(
                        'type' => 'CREDIT_CARD',
                        'installments' => $order_data['installments'] ?? 1,
                        'capture' => true,
                        'card' => array(
                            'encrypted' => $encrypted_card,
                            'store' => false,
                        ),
                        'holder' => array(
                            'name' => $holder_name,
                            'tax_id' => $holder_tax_id,
                        ),
                    ),
                ),
            );
        }
        
        $result = $this->request('/orders', 'POST', $data);
        
        if ($result['success']) {
            $response = array(
                'success' => true,
                'order_id' => $result['data']['id'],
                'reference_id' => $result['data']['reference_id'],
            );
            
            // Adicionar dados específicos do método de pagamento
            if (isset($result['data']['charges']) && !empty($result['data']['charges'])) {
                $charge = $result['data']['charges'][0];
                $response['charge_id'] = $charge['id'] ?? '';
                $response['status'] = $charge['status'] ?? 'UNKNOWN';

                if ($order_data['payment_method'] === 'pix') {
                    $response['pix_code'] = $charge['qr_code']['text'] ?? '';
                    $response['pix_qrcode'] = '';

                    // No PIX v2, a imagem fica nos links da cobrança com rel QRCODE.PNG.
                    if (!empty($charge['links']) && is_array($charge['links'])) {
                        foreach ($charge['links'] as $link) {
                            if (isset($link['rel'], $link['href']) && strtoupper((string) $link['rel']) === 'QRCODE.PNG') {
                                $response['pix_qrcode'] = $link['href'];
                                break;
                            }
                        }
                    }
                }
            }
            
            return $response;
        }
        
        return $result;
    }
    
    /**
     * Criar pagamento PIX
     */
    public function create_pix_payment($customer_data, $amount, $description, $reference_id) {
        return $this->create_order(array(
            'reference_id' => $reference_id,
            'customer' => $customer_data,
            'item' => array(
                'id' => $reference_id,
                'name' => $description,
                'amount' => $amount,
            ),
            'payment_method' => 'pix',
        ));
    }
    
    /**
     * Criar pagamento via Cartão de Crédito
     */
    public function create_credit_card_payment($customer_data, $amount, $description, $reference_id, $card_data, $installments = 1) {
        return $this->create_order(array(
            'reference_id' => $reference_id,
            'customer' => $customer_data,
            'item' => array(
                'id' => $reference_id,
                'name' => $description,
                'amount' => $amount,
            ),
            'payment_method' => 'credit_card',
            'card' => $card_data,
            'installments' => $installments,
        ));
    }
    
    /**
     * Consultar pedido
     */
    public function get_order($order_id) {
        $result = $this->request('/orders/' . $order_id);
        
        if ($result['success']) {
            return array(
                'success' => true,
                'order' => $result['data'],
                'status' => $result['data']['charges'][0]['status'] ?? 'UNKNOWN',
            );
        }
        
        return $result;
    }
    
    /**
     * Buscar configuração de parcelamento
     * 
     * PagSeguro/PagBank oferece endpoint para cálculo de taxas:
     * GET /charges/fees/calculate
     * 
     * @param float $value Valor total da compra
     * @return array Configuração de parcelas disponíveis
     */
    public function get_installment_config($value) {
        // Tentar buscar taxas via API do PagBank
        $fees_result = $this->get_installment_fees($value);
        
        if ($fees_result['success']) {
            return $fees_result;
        }
        
        // Fallback: retornar erro para usar configuração manual
        return array(
            'success' => false,
            'error' => $fees_result['error'] ?? 'PagSeguro não retornou dados de parcelamento.',
        );
    }
    
    /**
     * Consultar taxas de parcelamento via API do PagBank
     * 
     * Endpoint: GET /charges/fees/calculate
     * 
     * @param float $value Valor total da compra (em reais)
     * @param int $max_installments Máximo de parcelas (opcional, usa config do admin)
     * @return array Opções de parcelamento com taxas
     */
    public function get_installment_fees($value, $max_installments = null) {
        if (!$this->is_active()) {
            return array(
                'success' => false,
                'error' => 'PagSeguro não está ativo',
            );
        }
        
        // Usar configuração do admin se não informado
        if ($max_installments === null) {
            $max_installments = intval(get_option('cursos_parcelamento_max', 12));
        }
        $sem_juros = intval(get_option('cursos_parcelamento_sem_juros', 12));
        
        // Valor em centavos para a API
        $value_cents = intval($value * 100);
        
        // Endpoint do PagBank para cálculo de taxas
        $endpoint = '/charges/fees/calculate?' . http_build_query(array(
            'payment_methods' => 'CREDIT_CARD',
            'value' => $value_cents,
            'max_installments' => $max_installments,
            'max_installments_no_interest' => $sem_juros,
        ));
        
        $result = $this->request($endpoint);
        
        if ($result['success'] && isset($result['data'])) {
            $data = $result['data'];
            $installments = array();
            
            // Processar resposta do PagBank
            if (isset($data['payment_methods']['credit_card'])) {
                $credit_card_data = $data['payment_methods']['credit_card'];
                
                // Iterar pelas bandeiras disponíveis (usar a primeira como referência)
                $brands = $credit_card_data['brands'] ?? array();
                $first_brand = reset($brands);
                
                if ($first_brand && isset($first_brand['installment_plans'])) {
                    foreach ($first_brand['installment_plans'] as $plan) {
                        $installment_count = $plan['installments'] ?? 1;
                        $installment_value = ($plan['installment_value'] ?? 0) / 100; // Converter de centavos
                        $total_value = ($plan['amount']['value'] ?? 0) / 100;
                        $interest_free = ($plan['interest_free'] ?? false);
                        
                        $installments[] = array(
                            'number' => $installment_count,
                            'value' => round($installment_value, 2),
                            'total' => round($total_value, 2),
                            'interest_free' => $interest_free,
                            'fees' => array(
                                'buyer_interest' => round(($plan['amount']['fees']['buyer']['interest']['total'] ?? 0) / 100, 2),
                            ),
                        );
                    }
                }
            }
            
            // Fallback: se não encontrou estrutura esperada, tentar formato alternativo
            if (empty($installments) && isset($data['installment_options'])) {
                foreach ($data['installment_options'] as $option) {
                    $installments[] = array(
                        'number' => $option['installments'] ?? 1,
                        'value' => ($option['installment_value'] ?? 0) / 100,
                        'total' => ($option['total'] ?? 0) / 100,
                        'interest_free' => ($option['interest'] ?? 0) == 0,
                        'fees' => array(
                            'buyer_interest' => ($option['interest'] ?? 0) / 100,
                        ),
                    );
                }
            }
            
            if (!empty($installments)) {
                return array(
                    'success' => true,
                    'installments' => $installments,
                    'source' => 'api',
                );
            }
        }
        
        // API não retornou dados esperados
        return array(
            'success' => false,
            'error' => $result['error'] ?? 'Não foi possível obter parcelamento da API do PagBank',
        );
    }
    
    /**
     * Criar pedido com repasse de juros ao comprador
     * 
     * Quando há juros de parcelamento, inclui o campo fees.buyer.interest.total
     * para explicitar que os juros são pagos pelo comprador.
     * 
     * @param array $order_data Dados do pedido
     * @param float $buyer_interest Valor dos juros do comprador (em reais)
     * @return array Resultado da criação do pedido
     */
    public function create_order_with_buyer_fees($order_data, $buyer_interest = 0) {
        $data = array(
            'reference_id' => $order_data['reference_id'],
            'customer' => array(
                'name' => $order_data['customer']['name'],
                'email' => $order_data['customer']['email'],
                'tax_id' => preg_replace('/[^0-9]/', '', $order_data['customer']['cpf']),
                'phones' => array(
                    array(
                        'country' => '55',
                        'area' => substr(preg_replace('/[^0-9]/', '', $order_data['customer']['phone']), 0, 2),
                        'number' => substr(preg_replace('/[^0-9]/', '', $order_data['customer']['phone']), 2),
                        'type' => 'MOBILE',
                    ),
                ),
            ),
            'items' => array(
                array(
                    'reference_id' => $order_data['item']['id'],
                    'name' => $order_data['item']['name'],
                    'quantity' => 1,
                    'unit_amount' => intval($order_data['item']['amount'] * 100),
                ),
            ),
            'notification_urls' => array(
                home_url('/wp-json/cursos/v1/pagseguro-webhook'),
            ),
        );
        
        // Montar charge com juros do comprador se houver
        if ($order_data['payment_method'] === 'credit_card') {
            $encrypted_card = isset($order_data['card']['encrypted'])
                ? trim((string) $order_data['card']['encrypted'])
                : '';

            if ($encrypted_card === '') {
                return array(
                    'success' => false,
                    'error' => 'Cartão não criptografado. Atualize a página e tente novamente.',
                );
            }

            $charge_amount = array(
                'value' => intval($order_data['item']['amount'] * 100),
                'currency' => 'BRL',
            );
            
            // Adicionar juros do comprador quando aplicável
            if ($buyer_interest > 0) {
                $charge_amount['fees'] = array(
                    'buyer' => array(
                        'interest' => array(
                            'total' => intval($buyer_interest * 100),
                        ),
                    ),
                );
            }

            $holder_name = sanitize_text_field($order_data['card']['holder_name'] ?? '');
            $holder_tax_id = preg_replace('/[^0-9]/', '', (string) ($order_data['card']['holder_tax_id'] ?? $order_data['customer']['cpf'] ?? ''));
            
            $data['charges'] = array(
                array(
                    'reference_id' => $order_data['reference_id'],
                    'description' => $order_data['item']['name'],
                    'amount' => $charge_amount,
                    'payment_method' => array(
                        'type' => 'CREDIT_CARD',
                        'installments' => $order_data['installments'] ?? 1,
                        'capture' => true,
                        'card' => array(
                            'encrypted' => $encrypted_card,
                            'store' => false,
                        ),
                        'holder' => array(
                            'name' => $holder_name,
                            'tax_id' => $holder_tax_id,
                        ),
                    ),
                ),
            );
        }
        
        return $this->request('/orders', 'POST', $data);
    }
    
    /**
     * Processar notificação/webhook do PagSeguro
     */
    public function process_webhook() {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        if (!$data) {
            return false;
        }
        
        // Verificar se é uma notificação de cobrança
        if (isset($data['charges']) && !empty($data['charges'])) {
            $charge = $data['charges'][0];
            $reference_id = $data['reference_id'];
            
            if ($charge['status'] === 'PAID') {
                global $wpdb;
                $table_name = $wpdb->prefix . 'cursos_orders';
                
                $wpdb->update(
                    $table_name,
                    array(
                        'status' => 'completed',
                        'transaction_id' => $charge['id'],
                    ),
                    array('id' => intval($reference_id)),
                    array('%s', '%s'),
                    array('%d')
                );
                
                // Buscar dados completos do pedido para o trigger
                $order = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE id = %d",
                    intval($reference_id)
                ), ARRAY_A);
                
                $order_data = array();
                if ($order) {
                    // Buscar nome do curso
                    $curso_nome = '';
                    if (!empty($order['curso_id'])) {
                        $curso_nome = get_the_title($order['curso_id']);
                    }
                    
                    $order_data = array(
                        'nome' => $order['customer_name'] ?? '',
                        'email' => $order['customer_email'] ?? '',
                        'total' => $order['amount'] ?? 0,
                        'payment_method' => $order['payment_method'] ?? '',
                        'curso_id' => $order['curso_id'] ?? 0,
                        'curso_nome' => $curso_nome,
                        'cpf' => $order['customer_cpf'] ?? '',
                        'telefone' => $order['customer_phone'] ?? '',
                        'transaction_id' => $charge['id'],
                    );
                }
                
                // Disparar ação para outras integrações (sistema de e-mails)
                do_action('cursos_payment_completed', $reference_id, $order_data);
            }
        }
        
        return true;
    }
}

// Instância global
function cursos_pagseguro() {
    static $instance = null;
    if ($instance === null) {
        $instance = new Cursos_Payment_PagSeguro();
    }
    return $instance;
}

// Endpoint para webhook
add_action('rest_api_init', function() {
    register_rest_route('cursos/v1', '/pagseguro-webhook', array(
        'methods' => 'POST',
        'callback' => function() {
            $pagseguro = cursos_pagseguro();
            $result = $pagseguro->process_webhook();
            return new WP_REST_Response(array('received' => $result), 200);
        },
        'permission_callback' => '__return_true',
    ));
});
