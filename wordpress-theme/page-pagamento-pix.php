<?php
/**
 * Template Name: Pagamento PIX
 * Template para exibir QR Code do PIX após checkout
 * 
 * @package CursosTheme
 */

// Obter order_id do parâmetro GET
$order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;

// Tentar buscar dados do PIX via transient primeiro
$payment_result = null;
if ($order_id) {
    $payment_result = get_transient('cursos_pix_' . $order_id);
}

// Fallback para sessão (compatibilidade)
if (!$payment_result) {
    cursos_ensure_session();
    $payment_result = isset($_SESSION['payment_result']) ? $_SESSION['payment_result'] : null;
    $order_id = $order_id ?: (isset($_SESSION['order_id']) ? intval($_SESSION['order_id']) : 0);
}

// Se não houver dados, redirecionar para home
if (!$payment_result || !$order_id) {
    wp_redirect(home_url('/'));
    exit;
}

// Buscar dados do pedido
global $wpdb;
$table_name = $wpdb->prefix . 'cursos_orders';
$order = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE id = %d",
    $order_id
));

// Dados do PIX
$pix_code = $payment_result['pix_code'] ?? '';
$pix_qrcode = $payment_result['pix_qrcode'] ?? '';
$expiration = $payment_result['expiration'] ?? '';
$payment_id = $payment_result['payment_id'] ?? '';
$amount = $order ? floatval($order->amount) : 0;

// Configurações visuais
$page_title = get_option('cursos_pix_page_title', 'Pagamento via PIX');
$instructions = get_option('cursos_pix_instructions', 'Escaneie o QR Code abaixo ou copie o código PIX para realizar o pagamento.');


get_header();
?>

<style>
.pix-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 40px 0 80px;
}

.pix-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0 20px;
}

.pix-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    overflow: hidden;
}

.pix-header {
    background: linear-gradient(135deg, var(--primary, #7f0b0d) 0%, var(--primary-dark, #5f0809) 100%);
    color: #fff;
    padding: 30px;
    text-align: center;
}

.pix-header-icon {
    width: 70px;
    height: 70px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
}

.pix-header-icon svg {
    width: 36px;
    height: 36px;
}

.pix-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 5px;
}

.pix-header p {
    opacity: 0.9;
    margin: 0;
    font-size: 0.95rem;
}

.pix-body {
    padding: 30px;
}

.pix-amount {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 12px;
}

.pix-amount-label {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.pix-amount-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary, #7f0b0d);
}

.pix-qrcode {
    text-align: center;
    margin-bottom: 25px;
}

.pix-qrcode img {
    max-width: 250px;
    width: 100%;
    height: auto;
    border: 3px solid #e9ecef;
    border-radius: 12px;
    padding: 10px;
    background: #fff;
}

.pix-code-section {
    margin-bottom: 25px;
}

.pix-code-label {
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
    font-size: 0.95rem;
}

.pix-code-container {
    position: relative;
    display: flex;
    gap: 10px;
}

.pix-code-input {
    flex: 1;
    padding: 14px 16px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 0.85rem;
    font-family: monospace;
    background: #f8f9fa;
    color: #333;
    word-break: break-all;
    resize: none;
    min-height: 80px;
}

.pix-copy-btn {
    padding: 14px 20px;
    background: var(--primary, #7f0b0d);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.pix-copy-btn:hover {
    background: var(--primary-dark, #5f0809);
    transform: translateY(-2px);
}

.pix-copy-btn.copied {
    background: #10b981;
}

.pix-copy-btn svg {
    width: 18px;
    height: 18px;
}

.pix-instructions {
    background: #fff8e6;
    border: 1px solid #ffd666;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}

.pix-instructions h3 {
    color: #b88a00;
    font-size: 1rem;
    margin: 0 0 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.pix-instructions ol {
    margin: 0;
    padding-left: 20px;
    color: #666;
    line-height: 1.7;
}

.pix-instructions li {
    margin-bottom: 5px;
}

.pix-expiration {
    text-align: center;
    padding: 20px;
    background: #fee2e2;
    border-radius: 10px;
    margin-bottom: 25px;
}

.pix-expiration-label {
    color: #991b1b;
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 10px;
}

.pix-expiration-date {
    font-size: 0.85rem;
    color: #666;
    margin-top: 8px;
}

.pix-order-info {
    text-align: center;
    padding: 15px;
    background: #f0fdf4;
    border-radius: 10px;
    color: #166534;
    font-size: 0.9rem;
}

.pix-order-info strong {
    color: #15803d;
}

.pix-footer {
    padding: 20px 30px 30px;
    text-align: center;
    border-top: 1px solid #e9ecef;
}

.pix-home-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--primary, #7f0b0d);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.pix-home-link:hover {
    text-decoration: underline;
}

.pix-status-check {
    margin-top: 20px;
    padding: 15px;
    background: #f0f9ff;
    border-radius: 10px;
    text-align: center;
}

.pix-status-check p {
    color: #0369a1;
    margin: 0 0 10px;
    font-size: 0.9rem;
}

.pix-check-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    background: #0284c7;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.pix-check-btn:hover {
    background: #0369a1;
}

.pix-check-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.pix-status-result {
    margin-top: 10px;
    padding: 10px;
    border-radius: 8px;
    display: none;
}

.pix-status-result.pending {
    background: #fef3c7;
    color: #92400e;
    display: block;
}

.pix-status-result.confirmed {
    background: #d1fae5;
    color: #065f46;
    display: block;
}

@media (max-width: 480px) {
    .pix-code-container {
        flex-direction: column;
    }
    
    .pix-copy-btn {
        width: 100%;
        justify-content: center;
    }
    
    .pix-amount-value {
        font-size: 1.6rem;
    }
}
</style>

<div class="pix-page">
    <div class="pix-container">
        <div class="pix-card">
            <!-- Header -->
            <div class="pix-header">
                <div class="pix-header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <h1><?php echo esc_html($page_title); ?></h1>
                <p><?php echo esc_html($instructions); ?></p>
            </div>
            
            <!-- Body -->
            <div class="pix-body">
                <!-- Valor -->
                <div class="pix-amount">
                    <div class="pix-amount-label">Valor a pagar</div>
                    <div class="pix-amount-value">R$ <?php echo number_format($amount, 2, ',', '.'); ?></div>
                </div>
                
                <!-- QR Code -->
                <?php if ($pix_qrcode): 
                    // Detecta se é base64 ou URL
                    $is_base64 = (strpos($pix_qrcode, 'http') !== 0 && strpos($pix_qrcode, '//') !== 0);
                    $qr_src = $is_base64 ? 'data:image/png;base64,' . $pix_qrcode : $pix_qrcode;
                ?>
                <div class="pix-qrcode">
                    <img src="<?php echo esc_attr($qr_src); ?>" alt="QR Code PIX" onerror="this.style.display='none'; this.parentElement.innerHTML='<p style=&quot;color:#dc2626;text-align:center;&quot;>Erro ao carregar QR Code. Use o código abaixo.</p>';">
                </div>
                <?php endif; ?>
                
                <!-- Código Copia e Cola -->
                <?php if ($pix_code): ?>
                <div class="pix-code-section">
                    <div class="pix-code-label">Código PIX Copia e Cola:</div>
                    <div class="pix-code-container">
                        <textarea class="pix-code-input" id="pix-code" readonly><?php echo esc_textarea($pix_code); ?></textarea>
                        <button type="button" class="pix-copy-btn" onclick="copyPixCode()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                            </svg>
                            <span id="copy-btn-text">Copiar</span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Instruções -->
                <div class="pix-instructions">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4"/>
                            <path d="M12 8h.01"/>
                        </svg>
                        Como pagar
                    </h3>
                    <ol>
                        <li>Abra o app do seu banco ou carteira digital</li>
                        <li>Escolha a opção PIX Copia e Cola</li>
                        <li>Cole o código copiado ou escaneie o QR Code</li>
                        <li>Confirme o pagamento</li>
                    </ol>
                </div>
                
                <!-- Validade do PIX (informação do Asaas) -->
                <?php if ($expiration): 
                    $exp_date = new DateTime($expiration);
                    $exp_formatted = $exp_date->format('d/m/Y \à\s H:i');
                ?>
                <div class="pix-expiration" style="background: #f0f9ff; border-left: 4px solid #0284c7;">
                    <div class="pix-expiration-label" style="color: #0369a1;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle;">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Validade do código PIX
                    </div>
                    <div style="font-size: 1.1rem; font-weight: 600; color: #0c4a6e; margin-top: 8px;">
                        Válido até: <?php echo esc_html($exp_formatted); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Info do pedido -->
                <div class="pix-order-info">
                    <strong>Pedido #<?php echo esc_html($order_id); ?></strong><br>
                    Você receberá um e-mail de confirmação assim que o pagamento for identificado.
                </div>
                
                <!-- Verificar status -->
                <div class="pix-status-check">
                    <p>Já realizou o pagamento?</p>
                    <button type="button" class="pix-check-btn" id="check-status-btn" onclick="checkPaymentStatus()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"/>
                            <polyline points="1 20 1 14 7 14"/>
                            <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                        </svg>
                        Verificar pagamento
                    </button>
                    <div class="pix-status-result" id="status-result"></div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="pix-footer">
                <a href="<?php echo home_url('/'); ?>" class="pix-home-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Voltar para o site
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Função para copiar código PIX
function copyPixCode() {
    var pixCode = document.getElementById('pix-code');
    var copyBtn = document.querySelector('.pix-copy-btn');
    var copyBtnText = document.getElementById('copy-btn-text');
    
    // Selecionar texto
    pixCode.select();
    pixCode.setSelectionRange(0, 99999);
    
    // Copiar
    try {
        navigator.clipboard.writeText(pixCode.value).then(function() {
            copyBtn.classList.add('copied');
            copyBtnText.textContent = 'Copiado!';
            
            setTimeout(function() {
                copyBtn.classList.remove('copied');
                copyBtnText.textContent = 'Copiar';
            }, 3000);
        });
    } catch (err) {
        // Fallback para navegadores antigos
        document.execCommand('copy');
        copyBtn.classList.add('copied');
        copyBtnText.textContent = 'Copiado!';
        
        setTimeout(function() {
            copyBtn.classList.remove('copied');
            copyBtnText.textContent = 'Copiar';
        }, 3000);
    }
}

function checkPaymentStatus() {
    var btn = document.getElementById('check-status-btn');
    var resultDiv = document.getElementById('status-result');
    var orderId = <?php echo intval($order_id); ?>;
    var paymentId = '<?php echo esc_js($payment_id); ?>';
    
    btn.disabled = true;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="animate-spin"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Verificando...';
    
    // AJAX para verificar status
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=check_pix_status&order_id=' + orderId + '&payment_id=' + paymentId + '&nonce=<?php echo wp_create_nonce('check_pix_status'); ?>'
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg> Verificar pagamento';
        
        if (data.success) {
            if (data.data.status === 'CONFIRMED' || data.data.status === 'RECEIVED' || data.data.status === 'completed') {
                resultDiv.className = 'pix-status-result confirmed';
                resultDiv.innerHTML = '✅ Pagamento confirmado! Redirecionando...';
                setTimeout(function() {
                    window.location.href = '<?php echo home_url('/pedido-confirmado?order=' . $order_id); ?>';
                }, 2000);
            } else {
                resultDiv.className = 'pix-status-result pending';
                resultDiv.innerHTML = '⏳ Pagamento ainda não identificado. Aguarde alguns instantes após realizar o pagamento.';
            }
        } else {
            resultDiv.className = 'pix-status-result pending';
            resultDiv.innerHTML = '⏳ Aguardando confirmação do pagamento...';
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg> Verificar pagamento';
        resultDiv.className = 'pix-status-result pending';
        resultDiv.innerHTML = 'Erro ao verificar. Tente novamente.';
    });
}
</script>

<?php
// Limpar dados da sessão após exibir (mantém por um tempo para refresh)
// unset($_SESSION['payment_result']);
// unset($_SESSION['order_id']);

get_footer();
