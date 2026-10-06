<?php
/**
 * Classe de Integração com Asaas
 * 
 * @package CursosTheme
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cursos_Payment_Asaas {
    
    private $api_key;
    private $ambiente;
    private $base_url;
    private $debug_mode;
    
    public function __construct() {
        $this->reload_config();
    }
    
    /**
     * Recarregar configurações do banco de dados
     * Útil quando as configurações podem ter mudado
     */
    public function reload_config() {
        // Forçar leitura fresca do banco
        wp_cache_delete('cursos_asaas_api_key', 'options');
        wp_cache_delete('cursos_asaas_ambiente', 'options');
        wp_cache_delete('cursos_asaas_debug', 'options');
        
        $this->api_key = get_option('cursos_asaas_api_key');
        $this->ambiente = get_option('cursos_asaas_ambiente', 'sandbox');
        $this->debug_mode = get_option('cursos_asaas_debug', '0') === '1';
        
        $this->base_url = $this->ambiente === 'producao' 
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';
            
        if ($this->debug_mode) {
            $this->log('Config carregada - Ambiente: ' . $this->ambiente . ' | URL: ' . $this->base_url . ' | API Key: ' . substr($this->api_key, 0, 15) . '...');
        }
    }
    
    /**
     * Log de debug
     */
    private function log($message) {
        if ($this->debug_mode) {
            error_log('[Asaas Debug] ' . $message);
        }
    }

    /**
     * Obter IP público real do comprador para antifraude do Asaas.
     * Evita enviar IP privado/reservado ou IP interno do servidor/proxy.
     */
    private function get_client_public_ip() {
        $headers = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        );

        foreach ($headers as $header) {
            if (empty($_SERVER[$header])) {
                continue;
            }

            $ips = explode(',', (string) $_SERVER[$header]);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return '';
    }
    
    /**
     * Verificar se o Asaas está ativo e configurado
     */
    public function is_active() {
        return get_option('cursos_asaas_ativo') === '1' && !empty($this->api_key);
    }
    
    /**
     * Obter ambiente configurado
     */
    public function get_ambiente() {
        return $this->ambiente;
    }
    
    /**
     * Testar conexão com a API do Asaas
     * Verifica se a chave de API é válida para o ambiente selecionado
     * 
     * @return array ['success' => bool, 'message' => string, 'ambiente_detectado' => string|null]
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'Chave de API não configurada',
                'code' => 'no_api_key'
            );
        }
        
        // Fazer uma chamada simples para testar a conexão
        $result = $this->request('/customers?limit=1');
        
        if ($result['success']) {
            return array(
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso!',
                'ambiente' => $this->ambiente,
                'code' => 'connected'
            );
        }
        
        // Analisar o erro para dar feedback específico
        $error = $result['error'] ?? '';
        $error_lower = strtolower($error);
        
        // Erro específico de ambiente incorreto
        if (strpos($error_lower, 'não pertence a este ambiente') !== false || 
            strpos($error_lower, 'does not belong to this environment') !== false ||
            strpos($error_lower, 'ambiente') !== false) {
            
            $ambiente_sugerido = $this->ambiente === 'producao' ? 'sandbox' : 'producao';
            
            return array(
                'success' => false,
                'message' => 'A chave de API não corresponde ao ambiente selecionado.',
                'ambiente_atual' => $this->ambiente,
                'ambiente_sugerido' => $ambiente_sugerido,
                'code' => 'wrong_environment',
                'fix_suggestion' => $this->ambiente === 'producao' 
                    ? 'Esta parece ser uma chave de Sandbox. Altere o ambiente para "Sandbox (Testes)" ou use uma chave de Produção.'
                    : 'Esta parece ser uma chave de Produção. Altere o ambiente para "Produção" ou use uma chave de Sandbox.'
            );
        }
        
        // Chave inválida
        if (strpos($error_lower, 'unauthorized') !== false || 
            strpos($error_lower, 'invalid') !== false ||
            strpos($error_lower, 'inválid') !== false) {
            return array(
                'success' => false,
                'message' => 'Chave de API inválida',
                'code' => 'invalid_api_key'
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Erro ao conectar: ' . $error,
            'code' => 'connection_error'
        );
    }
    
    /**
     * Detectar automaticamente o ambiente baseado no formato da chave
     * Chaves de produção Asaas: $aact_prod_... (contém "prod")
     * Chaves de sandbox/homologação Asaas: $aact_hmlg_... (contém "hmlg" = homologação)
     * 
     * @param string $api_key
     * @return string|null 'producao', 'sandbox' ou null se não detectável
     */
    public static function detect_environment($api_key) {
        if (empty($api_key)) {
            return null;
        }
        
        // Normalizar a chave
        $api_key = trim($api_key);
        
        // Chaves de produção do Asaas contêm "prod"
        // Formato: $aact_prod_...
        if (stripos($api_key, '_prod_') !== false || stripos($api_key, 'prod') !== false) {
            return 'producao';
        }
        
        // Chaves de sandbox/homologação do Asaas contêm "hmlg" (homologação)
        // Formato: $aact_hmlg_...
        if (stripos($api_key, '_hmlg_') !== false || stripos($api_key, 'hmlg') !== false) {
            return 'sandbox';
        }
        
        // Também verificar padrões antigos de sandbox
        if (stripos($api_key, 'sandbox') !== false || stripos($api_key, 'test') !== false) {
            return 'sandbox';
        }
        
        // Chaves que começam com $aact_ sem identificador claro - assumir produção
        if (strpos($api_key, '$aact_') === 0) {
            return 'producao';
        }
        
        return null;
    }
    
    /**
     * Fazer requisição à API do Asaas
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        $url = $this->base_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'access_token' => $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 60,
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
        
        return array(
            'success' => false,
            'error' => isset($result['errors']) ? $result['errors'][0]['description'] : 'Erro desconhecido',
            'data' => $result,
        );
    }

    /**
     * Buscar cobrança existente por externalReference para evitar cobranças duplicadas.
     */
    public function find_payment_by_external_reference($external_reference) {
        if (empty($external_reference)) {
            return null;
        }

        $result = $this->request('/payments?externalReference=' . urlencode($external_reference) . '&limit=1');
        if ($result['success'] && !empty($result['data']['data'][0])) {
            return $result['data']['data'][0];
        }

        return null;
    }

    private function normalize_money($value) {
        return round(floatval($value), 2);
    }

    private function build_credit_card_holder_info($card_data) {
        $phone = isset($card_data['telefone']) ? preg_replace('/[^0-9]/', '', $card_data['telefone']) : '';

        return array_filter(array(
            'name' => $card_data['holder_name'],
            'email' => $card_data['email'],
            'cpfCnpj' => preg_replace('/[^0-9]/', '', $card_data['cpf']),
            'postalCode' => isset($card_data['cep']) ? preg_replace('/[^0-9]/', '', $card_data['cep']) : null,
            'address' => isset($card_data['street']) && !empty($card_data['street']) ? $card_data['street'] : null,
            'addressNumber' => isset($card_data['number_address']) && !empty($card_data['number_address']) ? $card_data['number_address'] : '1',
            'addressComplement' => isset($card_data['complement']) && !empty($card_data['complement']) ? $card_data['complement'] : null,
            'province' => isset($card_data['neighborhood']) && !empty($card_data['neighborhood']) ? $card_data['neighborhood'] : null,
            'phone' => $phone ?: null,
            'mobilePhone' => $phone ?: null,
        ), function($value) {
            return $value !== null && $value !== '';
        });
    }

    private function build_credit_card_data($card_data) {
        return array(
            'holderName' => $card_data['holder_name'],
            'number' => preg_replace('/[^0-9]/', '', $card_data['number']),
            'expiryMonth' => $card_data['expiry_month'],
            'expiryYear' => $card_data['expiry_year'],
            'ccv' => $card_data['cvv'],
        );
    }

    /**
     * Criar cobrança de cartão sem capturar dados no site, redirecionando para a fatura Asaas.
     * Usado como fallback para contas com antifraude/checkout transparente mais restritivo.
     */
    private function create_hosted_credit_card_charge($customer_id, $value, $description, $installments = 1, $external_reference = null, $fallback_reason = '') {
        $installments = max(1, intval($installments));
        $total_value = $this->normalize_money($value);
        $installment_value = $installments > 1 ? $this->normalize_money($total_value / $installments) : $total_value;

        $data = array(
            'customer' => $customer_id,
            'billingType' => 'CREDIT_CARD',
            'value' => $installment_value,
            'dueDate' => date('Y-m-d'),
            'description' => $description,
            'externalReference' => $external_reference,
        );

        if ($installments > 1) {
            $data['installmentCount'] = $installments;
            $data['totalValue'] = $total_value;
        }

        $this->log('Criando cobrança cartão hospedada no Asaas - Total: ' . $total_value . ' | Parcelas: ' . $installments . ' | Motivo fallback: ' . $fallback_reason);

        $result = $this->request('/payments', 'POST', $data);
        if ($result['success']) {
            return array(
                'success' => true,
                'payment_id' => $result['data']['id'],
                'status' => $result['data']['status'] ?? 'PENDING',
                'invoice_url' => $result['data']['invoiceUrl'] ?? '',
                'confirmed' => in_array($result['data']['status'] ?? '', array('CONFIRMED', 'RECEIVED'), true),
                'requires_redirect' => true,
                'fallback' => true,
                'fallback_reason' => $fallback_reason,
            );
        }

        return $result;
    }

    private function get_first_installment_payment($installment_id) {
        if (empty($installment_id)) {
            return null;
        }

        $payments = $this->request('/installments/' . rawurlencode($installment_id) . '/payments?limit=100');
        if ($payments['success'] && !empty($payments['data']['data'][0])) {
            return $payments['data']['data'][0];
        }

        return null;
    }
    
    /**
     * Criar ou buscar cliente no Asaas
     * Se encontrar cliente existente, atualiza os dados
     */
    public function get_or_create_customer($data) {
        // Recarregar config para garantir dados atualizados
        $this->reload_config();
        
        $this->log('get_or_create_customer - Iniciando para CPF: ' . substr($data['cpf'], 0, 3) . '...');
        $this->log('Usando URL base: ' . $this->base_url);
        
        // Primeiro, tentar buscar por CPF/CNPJ
        $cpf_limpo = preg_replace('/[^0-9]/', '', $data['cpf']);
        $search = $this->request('/customers?cpfCnpj=' . $cpf_limpo);
        
        $this->log('Busca por CPF - Sucesso: ' . ($search['success'] ? 'sim' : 'não') . ' | Erro: ' . ($search['error'] ?? 'nenhum'));
        
        if ($search['success'] && !empty($search['data']['data'])) {
            $existing_customer = $search['data']['data'][0];
            $customer_id = $existing_customer['id'];
            $this->log('Cliente encontrado: ' . $customer_id);
            
            // Atualizar dados do cliente existente
            $update_data = array(
                'name' => $data['nome'] ?? $data['name'] ?? $existing_customer['name'],
                'email' => $data['email'] ?? $existing_customer['email'],
                'phone' => isset($data['telefone']) ? preg_replace('/[^0-9]/', '', $data['telefone']) : (isset($data['phone']) ? preg_replace('/[^0-9]/', '', $data['phone']) : null),
            );
            
            $this->log('Atualizando cliente existente: ' . json_encode($update_data));
            
            $update_result = $this->request('/customers/' . $customer_id, 'PUT', $update_data);
            
            if ($update_result['success']) {
                $this->log('Cliente atualizado com sucesso');
            } else {
                $this->log('Erro ao atualizar cliente (continuando...): ' . ($update_result['error'] ?? 'desconhecido'));
            }
            
            return array(
                'success' => true,
                'customer_id' => $customer_id,
            );
        }
        
        // Criar novo cliente
        $customer_data = array(
            'name' => $data['nome'] ?? $data['name'] ?? '',
            'email' => $data['email'],
            'cpfCnpj' => $cpf_limpo,
            'phone' => isset($data['telefone']) ? preg_replace('/[^0-9]/', '', $data['telefone']) : (isset($data['phone']) ? preg_replace('/[^0-9]/', '', $data['phone']) : null),
            'notificationDisabled' => false,
        );
        
        $this->log('Criando novo cliente: ' . json_encode($customer_data));
        
        $result = $this->request('/customers', 'POST', $customer_data);
        
        $this->log('Criar cliente - Sucesso: ' . ($result['success'] ? 'sim' : 'não') . ' | Erro: ' . ($result['error'] ?? 'nenhum'));
        
        if ($result['success']) {
            return array(
                'success' => true,
                'customer_id' => $result['data']['id'],
            );
        }
        
        return $result;
    }
    
    /**
     * Criar cobrança via PIX
     * 
     * O Asaas usa dueDate para definir o vencimento. QR Codes PIX dinâmicos
     * permanecem válidos por até 12 meses após a dueDate.
     */
    public function create_pix_charge($customer_id, $value, $description, $external_reference = null) {
        // Usar dueDate de 1 dia (padrão Asaas)
        $due_date = date('Y-m-d', strtotime('+1 day'));
        
        $this->log("PIX charge - dueDate: {$due_date}");
        
        $data = array(
            'customer' => $customer_id,
            'billingType' => 'PIX',
            'value' => $value,
            'dueDate' => $due_date,
            'description' => $description,
            'externalReference' => $external_reference,
        );
        
        $result = $this->request('/payments', 'POST', $data);
        
        if ($result['success']) {
            // Buscar QR Code do PIX
            $pix_result = $this->request('/payments/' . $result['data']['id'] . '/pixQrCode');
            
            if ($pix_result['success']) {
                return array(
                    'success' => true,
                    'payment_id' => $result['data']['id'],
                    'invoice_url' => $result['data']['invoiceUrl'],
                    'pix_code' => $pix_result['data']['payload'],
                    'pix_qrcode' => $pix_result['data']['encodedImage'],
                    'expiration' => $pix_result['data']['expirationDate'],
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Criar cobrança via Cartão de Crédito
     */
    public function create_credit_card_charge($customer_id, $value, $description, $card_data, $installments = 1, $external_reference = null) {
        $installments = max(1, intval($installments));
        $total_value = $this->normalize_money($value);

        // Anti-duplicação: se já existir cobrança válida para este pedido, reutilizar.
        if ($external_reference) {
            $existing = $this->find_payment_by_external_reference($external_reference);
            if ($existing && ($existing['billingType'] ?? '') === 'CREDIT_CARD' && in_array($existing['status'] ?? '', array('CONFIRMED', 'RECEIVED'), true)) {
                $this->log('Reutilizando cobrança cartão Asaas confirmada ' . $existing['id'] . ' para pedido ' . $external_reference);
                return array(
                    'success' => true,
                    'payment_id' => $existing['id'],
                    'status' => $existing['status'],
                    'invoice_url' => $existing['invoiceUrl'] ?? '',
                    'confirmed' => true,
                    'reused' => true,
                    'requires_redirect' => false,
                );
            }
        }

        // remoteIp ajuda o antifraude, mas não é obrigatório para todos os casos.
        $remote_ip = $this->get_client_public_ip();

        if ($installments > 1) {
            // Para 2x ou mais, usar o endpoint próprio de parcelamentos do Asaas.
            // Nele, `value` é o valor da parcela e `totalValue` é o total com juros já calculado pelo tema.
            $installment_value = $this->normalize_money($total_value / $installments);
            $data = array(
                'customer' => $customer_id,
                'billingType' => 'CREDIT_CARD',
                'value' => $installment_value,
                'totalValue' => $total_value,
                'installmentCount' => $installments,
                'dueDate' => date('Y-m-d'),
                'description' => $description,
                'paymentExternalReference' => $external_reference,
                'creditCard' => $this->build_credit_card_data($card_data),
                'creditCardHolderInfo' => $this->build_credit_card_holder_info($card_data),
            );
            if (!empty($remote_ip)) $data['remoteIp'] = $remote_ip;

            $this->log('Criando parcelamento cartão Asaas - Total: ' . $total_value . ' | Parcelas: ' . $installments . 'x de ' . $installment_value);
            $result = $this->request('/installments/', 'POST', $data);

            if ($result['success']) {
                $installment_id = $result['data']['id'];
                $first_payment = $this->get_first_installment_payment($installment_id);
                $status = $first_payment['status'] ?? ($result['data']['status'] ?? 'PENDING');

                return array(
                    'success' => true,
                    'payment_id' => $first_payment['id'] ?? $installment_id,
                    'installment_id' => $installment_id,
                    'status' => $status,
                    'invoice_url' => $first_payment['invoiceUrl'] ?? ($result['data']['invoiceUrl'] ?? ''),
                    'confirmed' => in_array($status, array('CONFIRMED', 'RECEIVED'), true),
                );
            }

            return $result;
        }

        $data = array(
            'customer' => $customer_id,
            'billingType' => 'CREDIT_CARD',
            'value' => $total_value,
            'dueDate' => date('Y-m-d'),
            'description' => $description,
            'externalReference' => $external_reference,
            'creditCard' => $this->build_credit_card_data($card_data),
            'creditCardHolderInfo' => $this->build_credit_card_holder_info($card_data),
        );
        if (!empty($remote_ip)) $data['remoteIp'] = $remote_ip;

        $this->log('Criando cobrança cartão Asaas - Total: ' . $total_value . ' | Parcelas: 1');
        
        $result = $this->request('/payments', 'POST', $data);
        
        if ($result['success']) {
            return array(
                'success' => true,
                'payment_id' => $result['data']['id'],
                'status' => $result['data']['status'],
                'invoice_url' => $result['data']['invoiceUrl'],
                'confirmed' => in_array($result['data']['status'], array('CONFIRMED', 'RECEIVED'), true),
            );
        }

        return $result;
    }
    
    /**
     * Consultar status de pagamento
     */
    public function get_payment_status($payment_id) {
        $result = $this->request('/payments/' . $payment_id);
        
        if ($result['success']) {
            return array(
                'success' => true,
                'status' => $result['data']['status'],
                'confirmed_date' => $result['data']['confirmedDate'] ?? null,
            );
        }

        // Compatibilidade: em parcelamentos antigos, pode ter sido salvo o ID do parcelamento.
        $installment_result = $this->request('/installments/' . rawurlencode($payment_id));
        if ($installment_result['success']) {
            $first_payment = $this->get_first_installment_payment($payment_id);
            $status = $first_payment['status'] ?? ($installment_result['data']['status'] ?? 'PENDING');

            return array(
                'success' => true,
                'status' => $status,
                'confirmed_date' => $first_payment['confirmedDate'] ?? $installment_result['data']['paymentDate'] ?? null,
                'value' => $installment_result['data']['value'] ?? null,
                'netValue' => $installment_result['data']['netValue'] ?? null,
                'paymentDate' => $first_payment['paymentDate'] ?? $installment_result['data']['paymentDate'] ?? null,
                'dueDate' => $first_payment['dueDate'] ?? null,
                'billingType' => $installment_result['data']['billingType'] ?? 'CREDIT_CARD',
                'installmentCount' => $installment_result['data']['installmentCount'] ?? null,
                'description' => $installment_result['data']['description'] ?? '',
                'invoiceUrl' => $first_payment['invoiceUrl'] ?? '',
            );
        }
        
        return $result;
    }
    
    /**
     * Listar pagamentos do Asaas (para backfill / reconciliação)
     *
     * @param array $filters Filtros aceitos: dateCreated[ge], dateCreated[le], limit, offset, status
     * @return array { success: bool, data: array, hasMore: bool, totalCount: int, error?: string }
     */
    public function list_payments($filters = array()) {
        if (!$this->is_active()) {
            return array(
                'success' => false,
                'error' => 'Asaas não está ativo ou configurado',
                'data' => array(),
            );
        }
        
        $defaults = array(
            'limit'  => 100,
            'offset' => 0,
        );
        $filters = array_merge($defaults, $filters);
        
        $query = http_build_query($filters);
        $result = $this->request('/payments?' . $query);
        
        if (!$result['success']) {
            return array(
                'success' => false,
                'error'   => isset($result['error']) ? $result['error'] : 'Erro ao listar pagamentos',
                'data'    => array(),
            );
        }
        
        $payload = isset($result['data']) ? $result['data'] : array();
        
        return array(
            'success'    => true,
            'data'       => isset($payload['data']) ? $payload['data'] : array(),
            'hasMore'    => isset($payload['hasMore']) ? (bool) $payload['hasMore'] : false,
            'totalCount' => isset($payload['totalCount']) ? intval($payload['totalCount']) : 0,
            'limit'      => isset($payload['limit']) ? intval($payload['limit']) : intval($filters['limit']),
            'offset'     => isset($payload['offset']) ? intval($payload['offset']) : intval($filters['offset']),
        );
    }
    
    
    /**
     * Buscar configuração de parcelamento da API
     * 
     * @param float $value Valor total da compra
     * @return array Configuração de parcelas disponíveis
     */
    public function get_installment_config($value) {
        if (!$this->is_active()) {
            return array(
                'success' => false,
                'error' => 'Asaas não está ativo',
            );
        }
        
        // Buscar configuração de parcelamento do Asaas
        $endpoint = '/installments?value=' . number_format($value, 2, '.', '') . '&billingType=CREDIT_CARD';
        $result = $this->request($endpoint);
        
        if ($result['success'] && isset($result['data'])) {
            $installments = array();
            $data = $result['data'];
            
            // A API do Asaas retorna um array com as opções de parcelamento
            if (isset($data['installments']) && is_array($data['installments'])) {
                foreach ($data['installments'] as $option) {
                    $installments[] = array(
                        'number' => $option['installmentCount'],
                        'value' => $option['installmentValue'],
                        'total' => $option['totalValue'],
                        'interest_free' => ($option['totalValue'] == $value),
                    );
                }
            } elseif (is_array($data)) {
                // Fallback: a API pode retornar diretamente o array
                foreach ($data as $option) {
                    if (isset($option['installmentCount'])) {
                        $installments[] = array(
                            'number' => $option['installmentCount'],
                            'value' => $option['installmentValue'],
                            'total' => $option['totalValue'],
                            'interest_free' => ($option['totalValue'] == $value),
                        );
                    }
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
        
        // Fallback: retornar erro para usar configuração manual
        return array(
            'success' => false,
            'error' => $result['error'] ?? 'Não foi possível obter parcelamento da API',
        );
    }
    
    /**
     * Mapear status do Asaas para status interno
     */
    private function map_asaas_status_to_internal($asaas_status) {
        $status_map = array(
            // Pagamento confirmado
            'CONFIRMED' => 'confirmed',
            'RECEIVED' => 'confirmed',
            'RECEIVED_IN_CASH' => 'confirmed',
            
            // Pagamento pendente
            'PENDING' => 'pending',
            'AWAITING_RISK_ANALYSIS' => 'pending',
            
            // Pagamento com problema
            'OVERDUE' => 'error',
            'REFUND_REQUESTED' => 'error',
            'CHARGEBACK_REQUESTED' => 'error',
            'CHARGEBACK_DISPUTE' => 'error',
            'AWAITING_CHARGEBACK_REVERSAL' => 'error',
            'DUNNING_REQUESTED' => 'error',
            'DUNNING_RECEIVED' => 'error',
            
            // Pagamento cancelado/estornado
            'REFUNDED' => 'cancelled',
            'DELETED' => 'cancelled',
        );
        
        return isset($status_map[$asaas_status]) ? $status_map[$asaas_status] : null;
    }
    
    /**
     * Mapear evento do Asaas para status interno
     */
    private function map_asaas_event_to_internal($event) {
        $event_map = array(
            // Confirmados
            'PAYMENT_CONFIRMED' => 'confirmed',
            'PAYMENT_RECEIVED' => 'confirmed',
            'PAYMENT_RECEIVED_IN_CASH_UNDONE' => 'confirmed',
            
            // Pendentes
            'PAYMENT_CREATED' => 'pending',
            'PAYMENT_AWAITING_RISK_ANALYSIS' => 'pending',
            'PAYMENT_APPROVED_BY_RISK_ANALYSIS' => 'pending',
            'PAYMENT_UPDATED' => null, // Manter status atual
            
            // Com erro
            'PAYMENT_OVERDUE' => 'error',
            'PAYMENT_REPROVED_BY_RISK_ANALYSIS' => 'error',
            'PAYMENT_CHARGEBACK_REQUESTED' => 'error',
            'PAYMENT_CHARGEBACK_DISPUTE' => 'error',
            'PAYMENT_AWAITING_CHARGEBACK_REVERSAL' => 'error',
            'PAYMENT_DUNNING_REQUESTED' => 'error',
            'PAYMENT_DUNNING_RECEIVED' => 'error',
            'PAYMENT_BANK_SLIP_VIEWED' => null, // Não altera status
            'PAYMENT_CHECKOUT_VIEWED' => null, // Não altera status
            
            // Cancelados/Estornados
            'PAYMENT_REFUNDED' => 'cancelled',
            'PAYMENT_REFUND_IN_PROGRESS' => 'cancelled',
            'PAYMENT_DELETED' => 'cancelled',
            'PAYMENT_RESTORED' => 'pending',
        );
        
        return isset($event_map[$event]) ? $event_map[$event] : null;
    }
    
    /**
     * Processar webhook do Asaas
     */
    public function process_webhook($raw_body = null) {
        $body = $raw_body !== null ? $raw_body : file_get_contents('php://input');
        $data = json_decode($body, true);
        
        // Log detalhado
        error_log("[Asaas Webhook] ===== INÍCIO DO PROCESSAMENTO =====");
        error_log("[Asaas Webhook] Body raw: " . $body);
        error_log("[Asaas Webhook] Data parsed: " . print_r($data, true));
        
        if (!$data || !isset($data['event'])) {
            error_log("[Asaas Webhook] ERRO: Webhook inválido - evento não encontrado");
            return false;
        }
        
        $event = $data['event'];
        $payment = isset($data['payment']) ? $data['payment'] : null;
        
        if (!$payment) {
            error_log("[Asaas Webhook] ERRO: Webhook sem dados de pagamento");
            return false;
        }
        
        $external_reference = isset($payment['externalReference']) ? $payment['externalReference'] : null;
        $asaas_status = isset($payment['status']) ? $payment['status'] : null;
        $payment_id = isset($payment['id']) ? $payment['id'] : null;
        
        error_log("[Asaas Webhook] Evento: {$event}");
        error_log("[Asaas Webhook] Status Asaas: {$asaas_status}");
        error_log("[Asaas Webhook] Payment ID: {$payment_id}");
        error_log("[Asaas Webhook] External Reference (Order ID): {$external_reference}");
        
        // Determinar status interno baseado no evento
        $internal_status = $this->map_asaas_event_to_internal($event);
        
        // Se o evento não mapeia para um status, tentar usar o status do pagamento
        if ($internal_status === null && $asaas_status) {
            $internal_status = $this->map_asaas_status_to_internal($asaas_status);
        }
        
        error_log("[Asaas Webhook] Status interno mapeado: " . ($internal_status ?? 'null'));
        
        // Se ainda não temos status, não fazer nada
        if ($internal_status === null) {
            error_log("[Asaas Webhook] Evento {$event} não requer atualização de status - ignorando");
            return true;
        }
        
        if ($external_reference) {
            global $wpdb;
            $orders_table = $wpdb->prefix . 'cursos_orders';
            $history_table = $wpdb->prefix . 'cursos_order_history';
            $documents_table = $wpdb->prefix . 'cursos_student_documents';
            
            // Buscar pedido atual para verificar status
            $current_order = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $orders_table WHERE id = %d",
                $external_reference
            ));
            
            if (!$current_order) {
                error_log("[Asaas Webhook] ERRO: Pedido não encontrado - Order ID: {$external_reference}");
                return false;
            }
            
            $old_status = $current_order->status;
            error_log("[Asaas Webhook] Status atual do pedido: {$old_status}");
            
            // Mapear status interno para status da tabela orders
            $order_status = ($internal_status === 'confirmed') ? 'completed' : $internal_status;
            
            error_log("[Asaas Webhook] Novo status para salvar: {$order_status}");
            
            // Só atualizar se o status for diferente
            if ($old_status !== $order_status) {
                // Atualizar tabela cursos_orders
                $update_result = $wpdb->update(
                    $orders_table,
                    array(
                        'status' => $order_status,
                        'transaction_id' => $payment_id,
                        'updated_at' => current_time('mysql'),
                    ),
                    array('id' => $external_reference),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
                
                if ($update_result === false) {
                    error_log("[Asaas Webhook] ERRO ao atualizar pedido: " . $wpdb->last_error);
                } else {
                    error_log("[Asaas Webhook] Pedido atualizado com sucesso - Linhas afetadas: {$update_result}");
                }
                
                // Registrar no histórico
                $history_result = $wpdb->insert($history_table, array(
                    'order_id' => $external_reference,
                    'old_status' => $old_status,
                    'new_status' => $order_status,
                    'notes' => sprintf(
                        'Atualização automática via webhook Asaas. Evento: %s | Status Gateway: %s',
                        $event,
                        $asaas_status
                    ),
                    'changed_by' => 0,
                    'changed_by_name' => 'Sistema (Webhook Asaas)',
                    'created_at' => current_time('mysql'),
                ));
                
                if ($history_result === false) {
                    error_log("[Asaas Webhook] ERRO ao registrar histórico: " . $wpdb->last_error);
                } else {
                    error_log("[Asaas Webhook] Histórico registrado com sucesso");
                }
            } else {
                error_log("[Asaas Webhook] Status já está correto, sem necessidade de atualização");
            }
            
            // Atualizar tabela cursos_student_documents (se existir documento vinculado)
            $document = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $documents_table WHERE order_id = %d",
                $external_reference
            ));
            
            if ($document) {
                $wpdb->update(
                    $documents_table,
                    array(
                        'status' => $internal_status,
                        'reviewed_at' => current_time('mysql'),
                    ),
                    array('order_id' => $external_reference),
                    array('%s', '%s'),
                    array('%d')
                );
                
                error_log("[Asaas Webhook] Documento do estudante atualizado para order_id {$external_reference}");
            }
            
            // Buscar dados completos do pedido para o trigger
            $order = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $orders_table WHERE id = %d",
                $external_reference
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
                    'transaction_id' => $payment_id,
                    'status' => $internal_status,
                    'asaas_status' => $asaas_status,
                    'asaas_event' => $event,
                );
            }
            
            // Disparar ação para outras integrações
            if ($internal_status === 'confirmed') {
                error_log("[Asaas Webhook] Disparando ação cursos_payment_completed");
                do_action('cursos_payment_completed', $external_reference, $order_data);
            } elseif ($internal_status === 'cancelled') {
                error_log("[Asaas Webhook] Disparando ação cursos_payment_cancelled");
                do_action('cursos_payment_cancelled', $external_reference, $order_data);
            } elseif ($internal_status === 'error') {
                error_log("[Asaas Webhook] Disparando ação cursos_payment_error");
                do_action('cursos_payment_error', $external_reference, $order_data);
            }
            
            // Ação genérica para qualquer mudança de status
            do_action('cursos_payment_status_changed', $external_reference, $internal_status, $order_data);
            
            error_log("[Asaas Webhook] ===== FIM DO PROCESSAMENTO - SUCESSO =====");
        } else {
            error_log("[Asaas Webhook] AVISO: Webhook sem external_reference - não é possível identificar o pedido");
        }
        
        return true;
    }
}

// Instância global - Sempre cria nova instância para garantir config atualizada
function cursos_asaas($force_new = false) {
    static $instance = null;
    if ($instance === null || $force_new) {
        $instance = new Cursos_Payment_Asaas();
    }
    return $instance;
}

// Endpoint para webhook - Registrado com prioridade alta
add_action('rest_api_init', 'cursos_register_asaas_webhook_endpoint');
function cursos_register_asaas_webhook_endpoint() {
    // Webhook principal
    register_rest_route('cursos/v1', '/asaas-webhook', array(
        'methods' => array('POST', 'GET'),
        'callback' => 'cursos_handle_asaas_webhook',
        'permission_callback' => '__return_true',
    ));
    
    // Endpoint de teste para verificar se webhook está funcionando
    register_rest_route('cursos/v1', '/asaas-webhook-test', array(
        'methods' => 'GET',
        'callback' => function() {
            return new WP_REST_Response(array(
                'status' => 'ok',
                'message' => 'Webhook endpoint is working',
                'url' => rest_url('cursos/v1/asaas-webhook'),
                'time' => current_time('mysql'),
            ), 200);
        },
        'permission_callback' => '__return_true',
    ));
}

/**
 * Testes curl:
 * # Sem token (401):
 * curl -i -X POST ".../wp-json/cursos/v1/asaas-webhook" \
 *   -H "Content-Type: application/json" \
 *   -d '{"id":"evt_test","event":"PAYMENT_CREATED","payment":{"id":"pay_test"}}'
 *
 * # Com token (200):
 * curl -i -X POST ".../wp-json/cursos/v1/asaas-webhook" \
 *   -H "Content-Type: application/json" \
 *   -H "asaas-access-token: SEU_SEGREDO" \
 *   -d '{"id":"evt_test","event":"PAYMENT_CREATED","payment":{"id":"pay_test"}}'
 */
function cursos_handle_asaas_webhook($request) {
    error_log('[Asaas Webhook] Recebido - Metodo: ' . $request->get_method());

    if ($request->get_method() === 'GET') {
        return new WP_REST_Response([
            'status' => 'ok',
            'message' => 'Webhook active. POST to send events.',
        ], 200);
    }

    // Validar token secreto
    // Asaas envia o token de autenticacao do webhook via header "asaas-access-token"
    // (NAO confundir com a API Key usada para criar cobrancas)
    $token = $request->get_header('asaas-access-token');
    if (empty($token)) {
        $token = $request->get_header('X-Webhook-Token'); // fallback legado
    }
    $secret = defined('ASAAS_WEBHOOK_SECRET')
        ? ASAAS_WEBHOOK_SECRET
        : get_option('cursos_asaas_webhook_secret', '');

    if (empty($secret) || !hash_equals($secret, (string) $token)) {
        error_log('[Asaas Webhook] Token invalido ou ausente');
        return new WP_REST_Response(['error' => 'Unauthorized'], 401);
    }

    // Ler e validar body
    $body = $request->get_body();
    $data = json_decode($body, true);

    if (!is_array($data)) {
        error_log('[Asaas Webhook] JSON invalido ou body vazio');
        return new WP_REST_Response(['ok' => true], 200);
    }

    // Idempotencia
    $event_id = isset($data['id']) ? $data['id'] : null;
    if ($event_id) {
        $cache_key = 'asaas_evt_' . md5($event_id);
        if (get_transient($cache_key)) {
            error_log("[Asaas Webhook] Evento duplicado ignorado: {$event_id}");
            return new WP_REST_Response(['ok' => true], 200);
        }
    }

    // Processar passando o body ja lido
    $asaas  = cursos_asaas();
    $result = $asaas->process_webhook($body);

    // Marcar como processado somente apos sucesso
    if ($result && $event_id) {
        set_transient($cache_key, 1, 48 * HOUR_IN_SECONDS);
    }

    return new WP_REST_Response(['ok' => true], 200);
}

// Fallback: Também registrar via query var para compatibilidade
add_action('init', 'cursos_asaas_webhook_query_var');
function cursos_asaas_webhook_query_var() {
    add_rewrite_rule('^asaas-webhook/?$', 'index.php?asaas_webhook=1', 'top');
    
    // Verificar se a regra já foi aplicada
    $rules = get_option('rewrite_rules');
    if (!isset($rules['^asaas-webhook/?$'])) {
        flush_rewrite_rules(false);
    }
}

add_filter('query_vars', 'cursos_asaas_add_query_vars');
function cursos_asaas_add_query_vars($vars) {
    $vars[] = 'asaas_webhook';
    return $vars;
}

add_action('template_redirect', 'cursos_asaas_webhook_handler');
function cursos_asaas_webhook_handler() {
    if (get_query_var('asaas_webhook')) {
        error_log('[Asaas Webhook Fallback] Recebido via query var');

        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        // Validar token (Asaas envia via "asaas-access-token")
        $token = isset($_SERVER['HTTP_ASAAS_ACCESS_TOKEN']) ? $_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] : '';
        if (empty($token)) {
            $token = isset($_SERVER['HTTP_X_WEBHOOK_TOKEN']) ? $_SERVER['HTTP_X_WEBHOOK_TOKEN'] : '';
        }
        $secret = defined('ASAAS_WEBHOOK_SECRET') ? ASAAS_WEBHOOK_SECRET : get_option('cursos_asaas_webhook_secret', '');
        if (empty($secret) || !hash_equals($secret, (string) $token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $asaas = cursos_asaas();
        $result = $asaas->process_webhook();
        echo json_encode(['ok' => true]);
        exit;
    }
}

// Alternativa: Handler direto via parse_request (mais robusto)
add_action('parse_request', 'cursos_asaas_webhook_direct_handler');
function cursos_asaas_webhook_direct_handler($wp) {
    $request_uri = trim($_SERVER['REQUEST_URI'], '/');
    $request_uri = strtok($request_uri, '?');

    if ($request_uri === 'asaas-webhook') {
        error_log('[Asaas Webhook Direct] Recebido via parse_request');

        if (!headers_sent()) {
            header('Content-Type: application/json');
            header('HTTP/1.1 200 OK');
        }

        // Validar token (Asaas envia via "asaas-access-token")
        $token = isset($_SERVER['HTTP_ASAAS_ACCESS_TOKEN']) ? $_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] : '';
        if (empty($token)) {
            $token = isset($_SERVER['HTTP_X_WEBHOOK_TOKEN']) ? $_SERVER['HTTP_X_WEBHOOK_TOKEN'] : '';
        }
        $secret = defined('ASAAS_WEBHOOK_SECRET') ? ASAAS_WEBHOOK_SECRET : get_option('cursos_asaas_webhook_secret', '');
        if (empty($secret) || !hash_equals($secret, (string) $token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $asaas = cursos_asaas();
        $result = $asaas->process_webhook();
        echo json_encode(['ok' => true]);
        exit;
    }
}
