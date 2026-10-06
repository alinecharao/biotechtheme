<?php
/**
 * Template Name: Pedido Confirmado
 * 
 * Página de sucesso após pagamento
 * 
 * @package CursosTheme
 */

get_header();

// Verificar dados do pedido
// Aceita ?order_id= (checkout) e ?pedido= (legado)
$order_id = 0;
if (isset($_GET['order_id'])) {
    $order_id = absint($_GET['order_id']);
} elseif (isset($_GET['pedido'])) {
    $order_id = absint($_GET['pedido']);
} elseif (isset($_GET['order'])) {
    $order_id = absint($_GET['order']);
}

// Buscar dados do pedido
global $wpdb;
$order = null;
$order_items = array();

if ($order_id) {
    $table_name = $wpdb->prefix . 'cursos_orders';
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $order_id
    ));
    
    if ($order && $order->items) {
        $order_items = json_decode($order->items, true) ?: array();
    }
}

// Método de pagamento formatado
$payment_methods = array(
    'pix' => 'PIX',
    'credit_card' => 'Cartão de Crédito',
    'boleto' => 'Boleto Bancário',
);
$payment_method = $order ? ($payment_methods[$order->payment_method] ?? $order->payment_method) : '';

// Status do pedido
$status_labels = array(
    'pending' => array('label' => 'Pendente', 'color' => '#f59e0b', 'bg' => '#fef3c7'),
    'processing' => array('label' => 'Processando', 'color' => '#3b82f6', 'bg' => '#dbeafe'),
    'completed' => array('label' => 'Confirmado', 'color' => '#10b981', 'bg' => '#d1fae5'),
    'failed' => array('label' => 'Falhou', 'color' => '#ef4444', 'bg' => '#fee2e2'),
);
$status_info = $order ? ($status_labels[$order->status] ?? $status_labels['pending']) : $status_labels['pending'];
?>

<div class="order-confirmed-page">
    <!-- Hero Section -->
    <section class="confirmed-hero" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 80px 0; color: white; text-align: center;">
        <div class="container">
            <!-- Animated Check Icon -->
            <div class="success-icon" style="display: inline-flex; align-items: center; justify-content: center; width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; margin-bottom: 25px; animation: scaleIn 0.5s ease-out;">
                <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="animation: drawCheck 0.5s ease-out 0.3s both;">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            
            <h1 style="font-size: 2.5rem; margin-bottom: 15px;">Pedido Confirmado!</h1>
            <p style="opacity: 0.9; font-size: 1.2rem; max-width: 500px; margin: 0 auto;">
                Obrigado pela sua compra. Você receberá um e-mail com os detalhes do seu pedido.
            </p>
            
            <?php if ($order_id): ?>
            <div style="margin-top: 25px; padding: 15px 30px; background: rgba(255,255,255,0.15); border-radius: 50px; display: inline-block;">
                <span style="opacity: 0.8;">Número do Pedido:</span>
                <strong style="font-size: 1.2rem; margin-left: 8px;">#<?php echo $order_id; ?></strong>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <section style="padding: 60px 0; background: var(--gray-50);">
        <div class="container">
            <?php if ($order): ?>
                <div style="max-width: 800px; margin: 0 auto;">
                    
                    <!-- Detalhes do Pedido -->
                    <div class="order-details" style="background: white; border-radius: 16px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 30px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
                            <h2 style="font-size: 1.3rem; margin: 0; color: var(--gray-800);">Detalhes do Pedido</h2>
                            <span style="padding: 8px 16px; background: <?php echo $status_info['bg']; ?>; color: <?php echo $status_info['color']; ?>; border-radius: 20px; font-weight: 600; font-size: 0.9rem;">
                                <?php echo $status_info['label']; ?>
                            </span>
                        </div>
                        
                        <!-- Grid de informações -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; margin-bottom: 30px;">
                            <div>
                                <p style="color: var(--gray-500); font-size: 0.85rem; margin-bottom: 5px;">Data do Pedido</p>
                                <p style="font-weight: 600; color: var(--gray-800); margin: 0;">
                                    <?php echo date('d/m/Y H:i', strtotime($order->created_at)); ?>
                                </p>
                            </div>
                            <div>
                                <p style="color: var(--gray-500); font-size: 0.85rem; margin-bottom: 5px;">Forma de Pagamento</p>
                                <p style="font-weight: 600; color: var(--gray-800); margin: 0;">
                                    <?php echo esc_html($payment_method); ?>
                                </p>
                            </div>
                            <div>
                                <p style="color: var(--gray-500); font-size: 0.85rem; margin-bottom: 5px;">E-mail</p>
                                <p style="font-weight: 600; color: var(--gray-800); margin: 0;">
                                    <?php echo esc_html($order->customer_email); ?>
                                </p>
                            </div>
                            <div>
                                <p style="color: var(--gray-500); font-size: 0.85rem; margin-bottom: 5px;">Total Pago</p>
                                <p style="font-weight: 700; color: var(--primary); font-size: 1.3rem; margin: 0;">
                                    R$ <?php echo number_format($order->amount, 2, ',', '.'); ?>
                                </p>
                                <?php
                                $installments = isset($order->installments) ? intval($order->installments) : 1;
                                $pay_detail = '';
                                if ($order->payment_method === 'credit_card') {
                                    if ($installments > 1) {
                                        $parcela = $order->amount / $installments;
                                        $pay_detail = sprintf('Cartão em %dx de R$ %s', $installments, number_format($parcela, 2, ',', '.'));
                                    } else {
                                        $pay_detail = 'Cartão à vista (1x)';
                                    }
                                } elseif ($order->payment_method === 'pix') {
                                    $pay_detail = 'Pago via PIX';
                                } elseif ($order->payment_method === 'boleto') {
                                    $pay_detail = 'Pago via Boleto';
                                }
                                if ($pay_detail): ?>
                                <p style="color: var(--gray-500); font-size: 0.8rem; margin: 4px 0 0;">
                                    <?php echo esc_html($pay_detail); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Itens do Pedido -->
                        <?php if (!empty($order_items)): ?>
                        <div style="border-top: 1px solid var(--gray-200); padding-top: 25px;">
                            <h3 style="font-size: 1.1rem; margin-bottom: 20px; color: var(--gray-800);">Cursos Adquiridos</h3>
                            
                            <div class="order-items">
                                <?php foreach ($order_items as $item): 
                                    $curso = get_post($item['curso_id'] ?? $item['course_id'] ?? 0);
                                ?>
                                <div style="display: flex; gap: 20px; padding: 15px; background: var(--gray-50); border-radius: 12px; margin-bottom: 15px;">
                                    <?php if ($curso && has_post_thumbnail($curso->ID)): ?>
                                    <img src="<?php echo get_the_post_thumbnail_url($curso->ID, 'thumbnail'); ?>" 
                                         alt="<?php echo esc_attr($item['name'] ?? ''); ?>" 
                                         style="width: 80px; height: 60px; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                    <div style="width: 80px; height: 60px; background: var(--gray-200); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--gray-400)" stroke-width="2">
                                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                        </svg>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 5px; font-size: 1rem; color: var(--gray-800);">
                                            <?php echo esc_html($item['name'] ?? 'Curso'); ?>
                                        </h4>
                                        <p style="margin: 0; color: var(--gray-500); font-size: 0.9rem;">
                                            Qtd: <?php echo $item['quantity'] ?? 1; ?>
                                        </p>
                                    </div>
                                    
                                    <div style="text-align: right;">
                                        <p style="margin: 0; font-weight: 600; color: var(--gray-800);">
                                            R$ <?php echo number_format($item['price'] ?? 0, 2, ',', '.'); ?>
                                        </p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Próximos Passos -->
                    <div class="next-steps" style="background: white; border-radius: 16px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 30px;">
                        <h2 style="font-size: 1.3rem; margin-bottom: 25px; color: var(--gray-800);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" style="display: inline; vertical-align: middle; margin-right: 10px;">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            Próximos Passos
                        </h2>
                        
                        <div class="steps-grid" style="display: grid; gap: 20px;">
                            <div style="display: flex; gap: 15px; padding: 20px; background: var(--gray-50); border-radius: 12px;">
                                <div style="flex-shrink: 0; width: 40px; height: 40px; background: #dbeafe; color: #3b82f6; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                </div>
                                <div>
                                    <h4 style="margin: 0 0 5px; font-weight: 600; color: var(--gray-800);">Verifique seu e-mail</h4>
                                    <p style="margin: 0; color: var(--gray-500); font-size: 0.9rem;">Enviamos os detalhes do pedido e instruções de acesso para <?php echo esc_html($order->customer_email); ?></p>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 15px; padding: 20px; background: var(--gray-50); border-radius: 12px;">
                                <div style="flex-shrink: 0; width: 40px; height: 40px; background: #d1fae5; color: #10b981; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 style="margin: 0 0 5px; font-weight: 600; color: var(--gray-800);">Acesse seus cursos</h4>
                                    <p style="margin: 0; color: var(--gray-500); font-size: 0.9rem;">Após a confirmação do pagamento, acesse a área "Meus Cursos" para começar a estudar</p>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 15px; padding: 20px; background: var(--gray-50); border-radius: 12px;">
                                <div style="flex-shrink: 0; width: 40px; height: 40px; background: #fef3c7; color: #f59e0b; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                    </svg>
                                </div>
                                <div>
                                    <h4 style="margin: 0 0 5px; font-weight: 600; color: var(--gray-800);">Precisa de ajuda?</h4>
                                    <p style="margin: 0; color: var(--gray-500); font-size: 0.9rem;">Entre em contato conosco pelo WhatsApp ou e-mail. Estamos prontos para ajudar!</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div style="text-align: center; display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                        <a href="<?php echo home_url('/meus-cursos'); ?>" class="btn btn-primary" style="padding: 15px 30px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle; margin-right: 8px;">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                            Acessar Meus Cursos
                        </a>
                        <a href="<?php echo home_url('/cursos'); ?>" class="btn btn-outline" style="padding: 15px 30px;">
                            Continuar Comprando
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Pedido não encontrado -->
                <div style="max-width: 500px; margin: 0 auto; text-align: center; background: white; border-radius: 16px; padding: 60px 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                    <div style="width: 80px; height: 80px; background: #fef3c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <h2 style="font-size: 1.5rem; margin-bottom: 15px; color: var(--gray-800);">Pedido não encontrado</h2>
                    <p style="color: var(--gray-500); margin-bottom: 30px;">Não conseguimos localizar os dados do seu pedido. Por favor, verifique seu e-mail ou entre em contato conosco.</p>
                    <a href="<?php echo home_url('/contato'); ?>" class="btn btn-primary">Falar Conosco</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<style>
@keyframes scaleIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes drawCheck {
    from {
        stroke-dashoffset: 50;
        stroke-dasharray: 50;
    }
    to {
        stroke-dashoffset: 0;
        stroke-dasharray: 50;
    }
}

.order-confirmed-page {
    min-height: 100vh;
}

@media (max-width: 768px) {
    .confirmed-hero h1 {
        font-size: 1.8rem !important;
    }
    
    .order-details,
    .next-steps {
        padding: 25px !important;
    }
}
</style>

<?php get_footer(); ?>
