<?php
/**
 * Template Name: Checkout
 * Template para página de checkout - Layout idêntico ao React/Lovable
 * 
 * @package CursosTheme
 */

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se há itens no carrinho ou curso direto
$curso_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : 0;
$turma_id_get = isset($_GET['turma_id']) ? sanitize_text_field($_GET['turma_id']) : '';
$cart_items = cursos_get_cart_items();

// Se veio curso_id direto, usar apenas esse curso
if ($curso_id) {
    $curso = get_post($curso_id);
    if (!$curso || $curso->post_type !== 'curso') {
        wp_redirect(home_url('/cursos'));
        exit;
    }
    $meta = cursos_get_curso_meta($curso_id);
    
    // Buscar informações da turma se foi passada via URL
    $turma_info = null;
    if (!empty($turma_id_get)) {
        $turmas = get_post_meta($curso_id, '_curso_turmas', true);
        if (!empty($turmas) && is_array($turmas)) {
            foreach ($turmas as $turma) {
                if ($turma['id'] === $turma_id_get) {
                    $turma_info = $turma;
                    break;
                }
            }
        }
    }
    
    // Descontos por curso (estudante + profissional PIX)
    $est_pct = get_post_meta($curso_id, '_curso_desconto_estudante_percent', true);
    if ($est_pct === '') $est_pct = get_post_meta($curso_id, '_curso_desconto_percent', true); // compat
    $est_acc = get_post_meta($curso_id, '_curso_desconto_estudante_acumula', true);
    if ($est_acc === '') $est_acc = get_post_meta($curso_id, '_curso_desconto_acumula', true); // compat
    $checkout_items = array(array(
        'id' => $curso_id,
        'title' => $curso->post_title,
        'meta' => $meta,
        'preco' => floatval($meta['preco']),
        'turma_id' => !empty($turma_id_get) ? $turma_id_get : null,
        'turma_info' => $turma_info,
        'desconto_estudante_pct' => floatval($est_pct),
        'desconto_estudante_acumula' => ($est_acc === '1'),
        'desconto_profissional_pct' => floatval(get_post_meta($curso_id, '_curso_desconto_profissional_percent', true)),
        'desconto_profissional_acumula' => get_post_meta($curso_id, '_curso_desconto_profissional_acumula', true) === '1',
    ));
    $subtotal = floatval($meta['preco']);
} else {
    // Usar carrinho
    if (empty($cart_items)) {
        wp_redirect(home_url('/carrinho'));
        exit;
    }
    $checkout_items = array();
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $preco = floatval($item['meta']['preco']);
        $i_est_pct = get_post_meta($item['id'], '_curso_desconto_estudante_percent', true);
        if ($i_est_pct === '') $i_est_pct = get_post_meta($item['id'], '_curso_desconto_percent', true);
        $i_est_acc = get_post_meta($item['id'], '_curso_desconto_estudante_acumula', true);
        if ($i_est_acc === '') $i_est_acc = get_post_meta($item['id'], '_curso_desconto_acumula', true);
        $checkout_items[] = array(
            'id' => $item['id'],
            'title' => $item['title'],
            'meta' => $item['meta'],
            'preco' => $preco,
            'turma_id' => $item['turma_id'] ?? null,
            'turma_info' => $item['turma_info'] ?? null,
            'desconto_estudante_pct' => floatval($i_est_pct),
            'desconto_estudante_acumula' => ($i_est_acc === '1'),
            'desconto_profissional_pct' => floatval(get_post_meta($item['id'], '_curso_desconto_profissional_percent', true)),
            'desconto_profissional_acumula' => get_post_meta($item['id'], '_curso_desconto_profissional_acumula', true) === '1',
        );
        $subtotal += $preco;
    }
}

/**
 * Resolve qual desconto-por-curso aplica ao item dado o contexto (is_student / payment_method).
 * Retorna array('pct'=>float, 'acumula'=>bool). Se não houver desconto contextual, pct=0.
 */
function cursos_resolve_item_discount($item, $is_student, $payment_method) {
    // Estudante: aplica em qualquer método de pagamento
    if ($is_student) {
        return array(
            'pct' => floatval($item['desconto_estudante_pct'] ?? 0),
            'acumula' => !empty($item['desconto_estudante_acumula']),
        );
    }
    // Profissional: desconto só via PIX
    if ($payment_method === 'pix') {
        return array(
            'pct' => floatval($item['desconto_profissional_pct'] ?? 0),
            'acumula' => !empty($item['desconto_profissional_acumula']),
        );
    }
    return array('pct' => 0, 'acumula' => false);
}

/**
 * Calcula totais agregados de course discount e base elegível para descontos globais
 * dado o contexto (is_student / payment_method).
 */
function cursos_calc_course_totals($items, $is_student, $payment_method) {
    $course_discount_total = 0;
    $eligible = 0;
    foreach ($items as $ci) {
        $p = floatval($ci['preco']);
        $r = cursos_resolve_item_discount($ci, $is_student, $payment_method);
        if ($r['pct'] > 0) {
            $course_discount_total += $p * ($r['pct'] / 100);
            if ($r['acumula']) {
                $eligible += $p;
            }
        } else {
            $eligible += $p;
        }
    }
    return array('course_discount_total' => $course_discount_total, 'eligible' => $eligible);
}

/**
 * Redireciona para link externo do Pagamento Alternativo com fallback caso headers já tenham sido enviados.
 */
function cursos_checkout_redirect_to_alt_link($url) {
    $url = esc_url_raw($url);
    if (!$url) return;

    if (!headers_sent()) {
        wp_redirect($url, 302);
        exit;
    }

    $safe_url = esc_url($url);
    echo '<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="0;url=' . $safe_url . '"></head><body>';
    echo '<script>window.location.href=' . wp_json_encode($url) . ';</script>';
    echo '<p>Redirecionando para o pagamento... <a href="' . $safe_url . '">Clique aqui se não abrir automaticamente</a>.</p>';
    echo '</body></html>';
    exit;
}

// Totais iniciais (sem contexto): course_discount=0, eligible=subtotal — JS recalcula on-the-fly.
$course_discount_total = 0;
$subtotal_eligible_for_global = $subtotal;

// Criar reservas para itens do carrinho
$reservas_info = array();
$has_reservations = false;
$reservation_expiry = null;

// Configurações do Timer (do painel administrativo)
$timer_ativo = get_option('cursos_checkout_timer_ativo', '1') === '1';
$timer_minutos = intval(get_option('cursos_checkout_timer_minutos', 15));
$timer_aviso = intval(get_option('cursos_checkout_timer_aviso', 2));
$timer_renovar = get_option('cursos_checkout_timer_renovar', '1') === '1';

// Tempo de reserva em segundos
$checkout_time_limit = $timer_minutos * 60;
$checkout_started = time();

// Verificar se há itens com turma para criar reservas no banco
foreach ($checkout_items as $item) {
    if (!empty($item['turma_info'])) {
        $reserva = cursos_create_reserva($item['id'], $item['turma_info']['id'], $timer_minutos);
        if (!is_wp_error($reserva)) {
            $reservas_info[$item['id']] = array(
                'reserva_id' => $reserva,
                'turma_id' => $item['turma_info']['id'],
                'expires_at' => date('Y-m-d H:i:s', strtotime('+' . $timer_minutos . ' minutes'))
            );
            $has_reservations = true;
        }
    }
}

// Obter tempo restante da reserva do banco
if ($has_reservations) {
    $reservation_expiry = cursos_get_reserva_expiry_time();
}

// Se não há reservas no banco mas há itens no carrinho e timer está ativo, mostrar timer genérico
if (!$has_reservations && !empty($checkout_items) && $timer_ativo) {
    $has_reservations = true;
    $reservation_expiry = $checkout_time_limit;
}

$asaas_active = cursos_asaas()->is_active();
$pagseguro_active = cursos_pagseguro()->is_active();

// Chave pública do PagBank usada exclusivamente no navegador para criptografar o cartão.
$pagseguro_public_key = '';
$pagseguro_public_key_error = '';
if ($pagseguro_active) {
    $pagseguro_key_result = cursos_pagseguro()->get_public_key();
    if (!empty($pagseguro_key_result['success']) && !empty($pagseguro_key_result['public_key'])) {
        $pagseguro_public_key = $pagseguro_key_result['public_key'];
    } else {
        $pagseguro_public_key_error = $pagseguro_key_result['error'] ?? 'Não foi possível obter a chave pública do PagBank.';
    }
}

// Configuração de descontos
$descontos = cursos_descontos();
$discount_info = $descontos->get_discount_info();

// Configuração do formulário
$checkout_show_gender = get_option('cursos_checkout_show_gender', '1') === '1';
$checkout_phone_mode = get_option('cursos_checkout_phone_mode', 'required');
$checkout_title = get_option('cursos_checkout_title', 'Finalizar Compra');
$checkout_button_text = get_option('cursos_checkout_button_text', 'Pagar');
$checkout_secure_message = get_option('cursos_checkout_secure_message', 'Pagamento 100% seguro');

// Configuração de termos (do submenu LGPD)
$terms_text = get_option('cursos_checkout_terms_text', 'Li e aceito os');
$terms_link1_label = get_option('cursos_checkout_terms_link1_label', 'Termos de Uso');
$terms_link1_url = get_option('cursos_checkout_terms_link1_url', '/termos');
$terms_link2_label = get_option('cursos_checkout_terms_link2_label', 'Política de Privacidade');
$terms_link2_url = get_option('cursos_checkout_terms_link2_url', '/privacidade');
$terms_link3_label = get_option('cursos_checkout_terms_link3_label', 'Política de Reembolso');
$terms_link3_url = get_option('cursos_checkout_terms_link3_url', '/reembolso');
$terms_final_text = get_option('cursos_checkout_terms_final_text', '.');
$terms_required = get_option('cursos_checkout_terms_required', '1') === '1';

// Gateway preferido (automático - escondido do cliente)
$gateway_preferido = get_option('cursos_gateway_preferido', 'auto');
if ($gateway_preferido === 'auto') {
    $payment_gateway = $asaas_active ? 'asaas' : ($pagseguro_active ? 'pagseguro' : '');
} else {
    $payment_gateway = $gateway_preferido;
}

// Processar formulário
$errors = array();
$success = false;
$applied_coupon = null;
$coupon_discount = 0;
$pix_success = false;
$pix_data = null;
$card_success = false;
$card_success_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_submit'])) {
    // Verificar nonce
    if (!wp_verify_nonce($_POST['checkout_nonce'], 'process_checkout')) {
        $errors[] = 'Sessão expirada. Por favor, tente novamente.';
    } else {
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
        $payment_gateway = isset($_POST['payment_gateway']) ? sanitize_text_field($_POST['payment_gateway']) : '';
        $professional_type = isset($_POST['professional_type']) ? sanitize_text_field($_POST['professional_type']) : '';
        if (!in_array($professional_type, array('veterinarian', 'student', 'general'), true)) $professional_type = '';
        $is_student = ($professional_type === 'student') || (isset($_POST['is_student']) && $_POST['is_student'] === '1');

        // Idempotência: token único por sessão de checkout
        // IMPORTANTE: só considerar pedidos que REALMENTE foram enviados ao gateway
        // (têm transaction_id) ou já confirmados. Pedidos pending sem transaction_id
        // são tentativas que falharam antes de criar cobrança - devem ser marcados
        // como failed e o retry deve prosseguir normalmente.
        $checkout_token = isset($_POST['checkout_token']) ? sanitize_text_field($_POST['checkout_token']) : '';
        if ($checkout_token) {
            global $wpdb;
            $existing_orders = $wpdb->get_results($wpdb->prepare(
                "SELECT id, payment_method, status, transaction_id FROM {$wpdb->prefix}cursos_orders WHERE checkout_token = %s",
                $checkout_token
            ));

            $valid_existing = null;
            $orphan_ids = array();
            foreach ($existing_orders as $eo) {
                $has_tx = !empty($eo->transaction_id);
                $is_confirmed = in_array($eo->status, array('completed', 'confirmed'), true);
                if ($has_tx || $is_confirmed) {
                    if (!$valid_existing) $valid_existing = $eo;
                } else {
                    $orphan_ids[] = intval($eo->id);
                }
            }

            // Marcar como failed pedidos órfãos (sem transaction_id) de tentativas anteriores
            if (!empty($orphan_ids)) {
                $orders_table = $wpdb->prefix . 'cursos_orders';
                foreach ($orphan_ids as $oid) {
                    $wpdb->update($orders_table,
                        array('status' => 'failed', 'checkout_token' => null, 'updated_at' => current_time('mysql')),
                        array('id' => $oid)
                    );
                    error_log("[VetCursos Checkout] Pedido órfão #$oid marcado como failed (retry).");
                }
            }

            if ($valid_existing) {
                $existing_order_id = intval($valid_existing->id);
                $existing_method = $valid_existing->payment_method;

                // Se o curso agora usa Pagamento Alternativo, não reaproveitar a rota nativa
                // de confirmação/PIX; reenviar para o link externo configurado.
                if (function_exists('cursos_get_pagto_alternativo_redirect') && !empty($payment_method)) {
                    $alt_existing = cursos_get_pagto_alternativo_redirect($checkout_items, $payment_method, $is_student);
                    if (!empty($alt_existing['ativo']) && !empty($alt_existing['url'])) {
                        error_log('[VetCursos Checkout] Pedido existente #' . $existing_order_id . ' reenviado para Pagamento Alternativo: ' . $alt_existing['url']);
                        cursos_checkout_redirect_to_alt_link($alt_existing['url']);
                    }
                }

                // PIX duplicado: redirecionar para a tela do QR Code (usa transient cursos_pix_{order_id})
                if ($existing_method === 'pix' && get_transient('cursos_pix_' . $existing_order_id)) {
                    wp_safe_redirect(home_url('/pagamento-pix/?order=' . $existing_order_id));
                    exit;
                }

                // Cartão (ou PIX sem transient): redirecionar para confirmação
                wp_safe_redirect(home_url('/pedido-confirmado/?order_id=' . $existing_order_id));
                exit;
            }
        }
        
        // Validar campos
        $nome = sanitize_text_field($_POST['nome']);
        $email = sanitize_email($_POST['email']);
        $cpf = sanitize_text_field($_POST['cpf']);
        $telefone = sanitize_text_field($_POST['telefone']);
        $genero = isset($_POST['genero']) ? sanitize_text_field($_POST['genero']) : '';
        $referral_source = isset($_POST['referral_source']) ? sanitize_text_field($_POST['referral_source']) : '';
        $referral_detail = isset($_POST['referral_detail']) ? sanitize_text_field($_POST['referral_detail']) : '';
        $allowed_referral = array('google','instagram','facebook','indicacao','outro');
        if (!in_array($referral_source, $allowed_referral, true)) $referral_source = '';
        if (!in_array($referral_source, array('indicacao','outro'), true)) $referral_detail = '';
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : $payment_method;
        $payment_gateway = isset($_POST['payment_gateway']) ? sanitize_text_field($_POST['payment_gateway']) : $payment_gateway;
        $professional_type = isset($_POST['professional_type']) ? sanitize_text_field($_POST['professional_type']) : $professional_type;
        if (!in_array($professional_type, array('veterinarian', 'student', 'general'), true)) $professional_type = '';
        $crmv_raw = isset($_POST['crmv']) ? sanitize_text_field($_POST['crmv']) : '';
        $crmv = '';
        if ($crmv_raw !== '') {
            // Validar formato CRMV: CRMV-UF NNNNN (UF brasileira válida, 1-6 dígitos)
            $ufs = 'AC|AL|AP|AM|BA|CE|DF|ES|GO|MA|MT|MS|MG|PA|PB|PR|PE|PI|RJ|RN|RS|RO|RR|SC|SP|SE|TO';
            $normalized = preg_replace('/\s+/', ' ', trim($crmv_raw));
            if (preg_match('/^CRMV[\-\/\s]?(' . $ufs . ')[\-\/\s]?(\d{1,6})$/i', $normalized, $m)) {
                // Normalizar para formato canônico: CRMV-SP 12345
                $crmv = 'CRMV-' . strtoupper($m[1]) . ' ' . $m[2];
            } else {
                $errors[] = 'CRMV inválido. Use o formato CRMV-UF seguido do número (ex: CRMV-SP 12345).';
            }
        }
        $diploma_document_id = isset($_POST['diploma_document_id']) ? intval($_POST['diploma_document_id']) : 0;
        // is_student deriva de professional_type (compat com lógica existente)
        $is_student = ($professional_type === 'student') || (isset($_POST['is_student']) && $_POST['is_student'] === '1');
        $cupom_codigo = isset($_POST['cupom_codigo']) ? strtoupper(sanitize_text_field($_POST['cupom_codigo'])) : '';
        $cupom_id = isset($_POST['cupom_id']) ? intval($_POST['cupom_id']) : 0;
        $cupom_desconto = isset($_POST['cupom_desconto']) ? floatval($_POST['cupom_desconto']) : 0;
        
        // Verificar se algum item exige comprovação
        $requires_proof = false;
        foreach ($checkout_items as $ci) {
            if (get_post_meta($ci['id'], '_curso_sem_comprovacao', true) !== '1') {
                $requires_proof = true;
                break;
            }
        }
        
        if (empty($nome)) $errors[] = 'Nome é obrigatório';
        if (empty($email) || !is_email($email)) $errors[] = 'E-mail válido é obrigatório';
        if (empty($cpf)) $errors[] = 'CPF é obrigatório';
        if (empty($payment_method)) $errors[] = 'Selecione uma forma de pagamento';
        if ($checkout_phone_mode === 'required' && empty($telefone)) $errors[] = 'Telefone é obrigatório';
        if ($checkout_show_gender && empty($genero)) $errors[] = 'Selecione o gênero';
        if (empty($referral_source)) $errors[] = 'Informe por onde você nos encontrou';
        if (in_array($referral_source, array('indicacao','outro'), true) && empty($referral_detail)) {
            $errors[] = $referral_source === 'indicacao' ? 'Informe o nome de quem indicou' : 'Conte para nós como nos encontrou';
        }
        
        // Validações de comprovação profissional
        if ($requires_proof) {
            if (empty($professional_type) || $professional_type === 'general') {
                $errors[] = 'Selecione se você é Médico Veterinário ou Estudante';
            } elseif ($professional_type === 'veterinarian') {
                if (empty($crmv) && empty($diploma_document_id)) {
                    $errors[] = 'Informe o CRMV ou anexe o diploma para continuar';
                }
            } elseif ($professional_type === 'student') {
                $student_doc = isset($_POST['student_document_id']) ? intval($_POST['student_document_id']) : 0;
                if (empty($student_doc)) {
                    $errors[] = 'Anexe o comprovante de matrícula para continuar';
                }
            }
        } else {
            if (empty($professional_type)) $professional_type = 'general';
        }
        
        // Validar termos obrigatórios
        if ($terms_required && empty($_POST['accept_terms'])) {
            $errors[] = 'Você precisa aceitar os termos para continuar.';
        }

        // PagBank: o backend aceita somente o cartão criptografado pelo SDK oficial.
        if ($payment_method === 'credit_card' && $payment_gateway === 'pagseguro') {
            $encrypted_card_post = isset($_POST['pagseguro_encrypted_card'])
                ? sanitize_text_field(wp_unslash($_POST['pagseguro_encrypted_card']))
                : '';

            if ($encrypted_card_post === '') {
                $errors[] = 'Não foi possível criptografar os dados do cartão. Atualize a página e tente novamente.';
            }
        }
        
        // Revalidar cupom se foi aplicado
        $curso_ids = array_map(function($item) { return $item['id']; }, $checkout_items);
        if ($cupom_codigo && $cupom_id) {
            $cupons = new Cursos_Cupons();
            $cupom_result = $cupons->validate_coupon($cupom_codigo, $subtotal, $curso_ids, $email);
            if (!$cupom_result['valid']) {
                $errors[] = 'Cupom inválido: ' . $cupom_result['message'];
                $cupom_id = 0;
                $cupom_desconto = 0;
            } else {
                $cupom_desconto = $cupom_result['desconto_calculado'];
            }
        }
        
        if (empty($errors)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'cursos_orders';

            // Recalcular course discount + base elegível com base no contexto real (is_student / payment_method)
            $ctx_totals = cursos_calc_course_totals($checkout_items, $is_student, $payment_method);
            $course_discount_total = $ctx_totals['course_discount_total'];
            $subtotal_eligible_for_global = $ctx_totals['eligible'];

            // Calcular valores com descontos automáticos
            $subtotal_after_coupon = $subtotal - $cupom_desconto;
            if ($subtotal_after_coupon < 0) $subtotal_after_coupon = 0;

            // Verificar se cupom bloqueia outros descontos
            $cupom_bloqueia_outros = false;
            if ($cupom_id) {
                $cupom_bloqueia_outros = get_post_meta($cupom_id, '_cupom_bloquear_outros_descontos', true) === '1';
            }

            // Base elegível para global (PIX/Estudante) ajustada pelo cupom proporcionalmente
            $eligible_after_coupon = 0;
            if ($subtotal > 0) {
                $eligible_after_coupon = $subtotal_eligible_for_global - ($cupom_desconto * ($subtotal_eligible_for_global / $subtotal));
                if ($eligible_after_coupon < 0) $eligible_after_coupon = 0;
            }

            // Calcular desconto automático (PIX ou estudante) - apenas se cupom não bloquear
            $pix_discount = 0;
            $student_discount = 0;
            $global_discount_amount = 0;

            if (!$cupom_bloqueia_outros || $cupom_desconto == 0) {
                $discount_result = $descontos->calculate_discount($eligible_after_coupon, $payment_method, $is_student);
                $pix_discount = $discount_result['pix_discount'];
                $student_discount = $discount_result['student_discount'];
                $global_discount_amount = $discount_result['total_discount'];
            } else {
                // Cupom bloqueia outros descontos: zerar também o desconto por curso
                $course_discount_total = 0;
            }

            $total = $subtotal_after_coupon - $course_discount_total - $global_discount_amount;
            if ($total < 0) $total = 0;


            // Criar pedido para cada curso
            $first_order_id = null;
            $order_ids = array();
            $cursos_nomes = array();

            foreach ($checkout_items as $checkout_item) {
                $item_curso_id = $checkout_item['id'];
                $item_preco = $checkout_item['preco'];
                $item_titulo = $checkout_item['title'];
                // Incluir turma na descrição se disponível
                if (!empty($checkout_item['turma_info']['nome'])) {
                    $cursos_nomes[] = $item_titulo . ' - Turma: ' . $checkout_item['turma_info']['nome'];
                } else {
                    $cursos_nomes[] = $item_titulo;
                }

                // Desconto exclusivo deste curso (contextual)
                $item_resolved = cursos_resolve_item_discount($checkout_item, $is_student, $payment_method);
                $item_pct = $item_resolved['pct'];
                $item_acumula = $item_resolved['acumula'];
                $item_course_discount = ($item_pct > 0 && !($cupom_bloqueia_outros && $cupom_desconto > 0)) ? $item_preco * ($item_pct / 100) : 0;

                // Proporcionalizar cupom pelo subtotal bruto
                $item_proportion = $subtotal > 0 ? ($item_preco / $subtotal) : 0;
                $item_cupom_desconto = $cupom_desconto * $item_proportion;

                // Proporcionalizar global (PIX/Estudante) pela base elegível desse item
                $item_eligible_base = ($item_pct > 0 && !$item_acumula) ? 0 : $item_preco;
                $item_global_proportion = $subtotal_eligible_for_global > 0 ? ($item_eligible_base / $subtotal_eligible_for_global) : 0;
                $item_pix_discount = $pix_discount * $item_global_proportion;
                $item_student_discount = $student_discount * $item_global_proportion;

                $item_total = $item_preco - $item_cupom_desconto - $item_course_discount - $item_pix_discount - $item_student_discount;
                if ($item_total < 0) $item_total = 0;
                
                $order_data = array(
                    'user_id' => get_current_user_id() ?: null,
                    'curso_id' => $item_curso_id,
                    'turma_id' => $checkout_item['turma_id'] ?? null,
                    'customer_name' => $nome,
                    'customer_email' => $email,
                    'customer_cpf' => $cpf,
                    'customer_phone' => $telefone,
                    'customer_gender' => $genero,
                    'referral_source' => $referral_source ?: null,
                    'referral_detail' => $referral_detail ?: null,
                    'subtotal' => $item_preco,
                    'discount' => $item_cupom_desconto,
                    'coupon_code' => $cupom_codigo ?: null,
                    'coupon_id' => $cupom_id ?: null,
                    'pix_discount' => $item_pix_discount,
                    'student_discount' => $item_student_discount,
                    'course_discount' => $item_course_discount,
                    'is_student' => $is_student ? 1 : 0,
                    'amount' => $item_total,
                    'payment_method' => $payment_method,
                    'payment_gateway' => $payment_gateway,
                    'status' => 'pending',
                    'professional_type' => $professional_type ?: null,
                    'crmv' => $crmv ?: null,
                    'diploma_document_id' => $diploma_document_id ?: null,
                    'checkout_token' => $checkout_token ?: null,
                );
                
            $insert_result = $wpdb->insert($table_name, $order_data);
            $order_id = $wpdb->insert_id;
            
            // DEBUG - Log de criação de pedido
            if ($insert_result === false || !$order_id) {
                error_log("[VetCursos Checkout] ERRO ao criar pedido para: $email");
                error_log("[VetCursos Checkout] Erro: " . $wpdb->last_error);
                error_log("[VetCursos Checkout] Query: " . $wpdb->last_query);
            } else {
                error_log("[VetCursos Checkout] Pedido criado com sucesso: ID #$order_id para $email - R$ " . number_format($item_total, 2, ',', '.'));
            }
            
            $order_ids[] = $order_id;
                
                if (!$first_order_id) {
                    $first_order_id = $order_id;
                }
            }
            
            $order_id = $first_order_id;
            
            // Vincular documento de estudante ao pedido (se houver)
            $student_document_id = isset($_POST['student_document_id']) ? intval($_POST['student_document_id']) : 0;
            if ($student_document_id > 0 && function_exists('cursos_link_document_to_order')) {
                cursos_link_document_to_order($order_id, $student_document_id);
            }
            // Vincular diploma ao pedido (se houver)
            if ($diploma_document_id > 0 && function_exists('cursos_link_document_to_order')) {
                cursos_link_document_to_order($order_id, $diploma_document_id);
            }
            
            // Registrar uso do cupom se aplicado
            if ($cupom_id && $cupom_desconto > 0) {
                $cupons = new Cursos_Cupons();
                $cupons->register_coupon_usage($cupom_id, $email, $order_id, $cupom_desconto);
            }
            
            // Disparar trigger: pedido criado
            $cursos_nomes_str = implode(', ', $cursos_nomes);
            $curso_ids_str = implode(',', array_map(function($item) { return $item['id']; }, $checkout_items));
            do_action('cursos_order_created', $order_id, array(
                'nome' => $nome,
                'email' => $email,
                'total' => $total,
                'payment_method' => $payment_method,
                'curso_id' => $curso_ids_str,
                'curso_nome' => $cursos_nomes_str,
                'cpf' => $cpf,
                'telefone' => $telefone,
            ));
            
            // Processar pagamento
            $customer_data = array(
                'nome' => $nome,
                'name' => $nome,
                'email' => $email,
                'cpf' => $cpf,
                'telefone' => $telefone,
                'phone' => $telefone,
            );
            
            $amount = $total;
            $description = count($checkout_items) > 1 
                ? 'Cursos: ' . $cursos_nomes_str 
                : 'Curso: ' . $cursos_nomes_str;

            // Se cartão de crédito com repasse de taxas ativo,
            // recalcular $amount incluindo juros (inclusive 1x) para bater com o valor exibido ao cliente
            if ($payment_method === 'credit_card' && get_option('cursos_repasse_taxas_ativo', '0') === '1' && function_exists('cursos_get_installment_options')) {
                $installments_qty = isset($_POST['installments']) ? max(1, intval($_POST['installments'])) : 1;
                $sj_override_be = function_exists('cursos_get_effective_sem_juros') ? cursos_get_effective_sem_juros($checkout_items) : null;
                $inst_options = cursos_get_installment_options($total, $payment_gateway, $sj_override_be);
                foreach ($inst_options as $opt) {
                    if (intval($opt['number']) === $installments_qty && !empty($opt['total'])) {
                        $amount = floatval($opt['total']);
                        break;
                    }
                }
                // Atualizar amount dos pedidos criados para refletir o valor com juros
                if ($amount != $total && !empty($order_ids)) {
                    global $wpdb;
                    $orders_table = $wpdb->prefix . 'cursos_orders';
                    foreach ($order_ids as $oid) {
                        $wpdb->update($orders_table, array('amount' => $amount, 'updated_at' => current_time('mysql')), array('id' => $oid), array('%f', '%s'), array('%d'));
                    }
                }
            }
            
            $payment_result = null;

            // === Pagamento Alternativo (Link de Pagamento Asaas) ===
            // Se algum curso do checkout tiver o modo ativo, pula a API do gateway
            // e redireciona para o link configurado. Matrícula/pedido já foram criados.
            if (function_exists('cursos_get_pagto_alternativo_redirect')) {
                $alt_pag = cursos_get_pagto_alternativo_redirect($checkout_items, $payment_method, $is_student);

                // Diagnóstico visível para admins: registra em transient e será
                // renderizado via admin_notices no próximo carregamento do admin.
                if (current_user_can('manage_options')) {
                    $diag_items = array();
                    foreach ((array) $checkout_items as $it) {
                        $cid = isset($it['id']) ? intval($it['id']) : 0;
                        if (!$cid) continue;
                        $diag_items[] = array(
                            'curso_id'          => $cid,
                            'titulo'            => get_the_title($cid),
                            'prof_ativo'        => get_post_meta($cid, '_curso_pagto_alt_prof_ativo', true),
                            'prof_link_cartao'  => get_post_meta($cid, '_curso_pagto_alt_prof_link_cartao', true),
                            'prof_link_pix'     => get_post_meta($cid, '_curso_pagto_alt_prof_link_pix', true),
                            'aluno_ativo'       => get_post_meta($cid, '_curso_pagto_alt_aluno_ativo', true),
                            'aluno_link_cartao' => get_post_meta($cid, '_curso_pagto_alt_aluno_link_cartao', true),
                            'aluno_link_pix'    => get_post_meta($cid, '_curso_pagto_alt_aluno_link_pix', true),
                            'legacy_ativo'      => get_post_meta($cid, '_curso_pagto_alt_ativo', true),
                            'legacy_link_cartao'=> get_post_meta($cid, '_curso_pagto_alt_link_cartao', true),
                            'legacy_link_pix'   => get_post_meta($cid, '_curso_pagto_alt_link_pix', true),
                        );
                    }
                    set_transient('cursos_pagto_alt_diag_' . get_current_user_id(), array(
                        'when'           => current_time('mysql'),
                        'professional_type' => $professional_type,
                        'payment_method' => $payment_method,
                        'is_student'     => $is_student ? 1 : 0,
                        'items'          => $diag_items,
                        'resultado'      => $alt_pag,
                    ), 300);
                }

                if (!empty($alt_pag['ativo'])) {

                    if (empty($alt_pag['url'])) {
                        $errors[] = $alt_pag['motivo'] ?: 'Esta forma de pagamento não está disponível no momento.';
                    } else {
                        // Marcador em transaction_id: preserva o pedido da limpeza de "órfãos"
                        // (linha ~252) e sinaliza a origem para o admin/relatórios.
                        if (!empty($order_ids)) {
                            foreach ($order_ids as $oid) {
                                $wpdb->update(
                                    $table_name,
                                    array(
                                        'transaction_id' => 'ALT_LINK:' . $oid,
                                        'updated_at'     => current_time('mysql'),
                                    ),
                                    array('id' => $oid),
                                    array('%s', '%s'),
                                    array('%d')
                                );
                            }
                        }

                        // E-mail de "pedido criado, aguardando pagamento" (mesmo hook do PIX)
                        $alt_email_data = array(
                            'nome' => $nome,
                            'email' => $email,
                            'total' => $amount,
                            'payment_method' => $payment_method,
                            'curso_id' => $curso_ids_str,
                            'curso_nome' => $cursos_nomes_str,
                            'cpf' => $cpf,
                            'telefone' => $telefone,
                            'pagto_alternativo' => true,
                            'pagto_alternativo_url' => $alt_pag['url'],
                        );
                        do_action('cursos_payment_pending', $order_id, $alt_email_data);

                        // Liberar reservas e limpar carrinho (mesmo padrão do PIX)
                        foreach ($checkout_items as $it_alt) {
                            if (!empty($it_alt['turma_info'])) {
                                cursos_release_reserva($it_alt['id'], $it_alt['turma_info']['id']);
                            }
                        }
                        cursos_clear_cart();

                        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['checkout_token'])) {
                            unset($_SESSION['checkout_token']);
                        }

                        error_log('[VetCursos Checkout] Pagamento Alternativo (Asaas Payment Link) — pedidos ' . implode(',', $order_ids) . ' redirecionados para ' . $alt_pag['url']);

                        cursos_checkout_redirect_to_alt_link($alt_pag['url']);
                    }
                }
            }


            
            if ($payment_gateway === 'asaas' && $asaas_active) {
                $asaas = cursos_asaas();
                
                $customer_result = $asaas->get_or_create_customer($customer_data);
                
                if ($customer_result['success']) {
                    $customer_id = $customer_result['customer_id'];
                    
                    switch ($payment_method) {
                        case 'pix':
                            $payment_result = $asaas->create_pix_charge($customer_id, $amount, $description, $order_id);
                            break;
                        case 'credit_card':
                            // Separar validade MM/AA ou MM/AAAA com normalização defensiva
                            $card_expiry = sanitize_text_field($_POST['card_expiry']);
                            $expiry_parts = explode('/', $card_expiry);
                            $card_month = isset($expiry_parts[0]) ? str_pad(preg_replace('/\D/', '', $expiry_parts[0]), 2, '0', STR_PAD_LEFT) : '';
                            $card_year_raw = isset($expiry_parts[1]) ? preg_replace('/\D/', '', $expiry_parts[1]) : '';
                            $card_year = strlen($card_year_raw) === 2 ? '20' . $card_year_raw : $card_year_raw;
                            
                            $card_data = array(
                                'holder_name' => sanitize_text_field($_POST['card_holder']),
                                'number' => preg_replace('/\D/', '', sanitize_text_field($_POST['card_number'])),
                                'expiry_month' => $card_month,
                                'expiry_year' => $card_year,
                                'cvv' => sanitize_text_field($_POST['card_cvv']),
                                'email' => $email,
                                'cpf' => $cpf,
                                'telefone' => $telefone,
                                'cep' => preg_replace('/\D/', '', sanitize_text_field($_POST['card_cep'])),
                                'street' => sanitize_text_field($_POST['card_street'] ?? ''),
                                'number_address' => sanitize_text_field($_POST['card_number_address'] ?? '1'),
                                'complement' => sanitize_text_field($_POST['card_complement'] ?? ''),
                                'neighborhood' => sanitize_text_field($_POST['card_neighborhood'] ?? ''),
                                'city' => sanitize_text_field($_POST['card_city'] ?? ''),
                                'state' => sanitize_text_field($_POST['card_state'] ?? ''),
                            );
                            $installments = intval($_POST['installments']) ?: 1;
                            $payment_result = $asaas->create_credit_card_charge($customer_id, $amount, $description, $card_data, $installments, $order_id);
                            break;
                    }
                } else {
                    $errors[] = 'Erro ao processar dados do cliente: ' . $customer_result['error'];
                }
            } elseif ($payment_gateway === 'pagseguro' && $pagseguro_active) {
                $pagseguro = cursos_pagseguro();
                
                switch ($payment_method) {
                    case 'pix':
                        $payment_result = $pagseguro->create_pix_payment($customer_data, $amount, $description, $order_id);
                        break;
                    case 'credit_card':
                        $card_data = array(
                            'holder_name' => sanitize_text_field($_POST['card_holder'] ?? ''),
                            'holder_tax_id' => preg_replace('/\D/', '', $cpf),
                            'encrypted' => isset($_POST['pagseguro_encrypted_card'])
                                ? sanitize_text_field(wp_unslash($_POST['pagseguro_encrypted_card']))
                                : '',
                        );
                        $installments = intval($_POST['installments']) ?: 1;
                        $payment_result = $pagseguro->create_credit_card_payment($customer_data, $amount, $description, $order_id, $card_data, $installments);
                        break;
                }
            }
            
            if ($payment_result && $payment_result['success']) {
                $transaction_id = $payment_result['payment_id'] ?? $payment_result['order_id'] ?? '';
                $wpdb->update(
                    $table_name,
                    array('transaction_id' => $transaction_id),
                    array('id' => $order_id)
                );

                if (class_exists('PB_Gateway_Sync')) {
                    $pb_gateway_sync = new PB_Gateway_Sync();
                    $pb_gateway_sync->maybe_sync_order_after_transaction_update($order_id);
                }
                
                $email_data = array(
                    'nome' => $nome,
                    'email' => $email,
                    'total' => $total,
                    'payment_method' => $payment_method,
                    'curso_id' => $curso_ids_str,
                    'curso_nome' => $cursos_nomes_str,
                    'cpf' => $cpf,
                    'telefone' => $telefone,
                    'pix_codigo' => $payment_result['pix_code'] ?? '',
                    'pix_qrcode' => $payment_result['pix_qrcode'] ?? '',
                    'prazo_pagamento' => $payment_result['expiration'] ?? '',
                );

                if ($payment_method === 'credit_card') {
                    $email_data['total'] = $amount;
                }
                
                if ($payment_method === 'pix') {
                    do_action('cursos_payment_pending', $order_id, $email_data);
                    do_action('cursos_pix_generated', $order_id, $email_data);
                    
                    // Liberar reservas e limpar carrinho
                    foreach ($checkout_items as $item) {
                        if (!empty($item['turma_info'])) {
                            cursos_release_reserva($item['id'], $item['turma_info']['id']);
                        }
                    }
                    cursos_clear_cart();
                    
                    // Guardar dados do PIX para exibir na mesma página
                    $pix_success = true;
                    $pix_data = array(
                        'pix_code' => $payment_result['pix_code'] ?? '',
                        'pix_qrcode' => $payment_result['pix_qrcode'] ?? '',
                        'expiration' => $payment_result['expiration'] ?? '',
                        'total' => $total,
                        'order_id' => $order_id,
                        // Dados do cliente para exibir no resumo
                        'customer_name' => $nome,
                        'customer_email' => $email,
                        'customer_phone' => $telefone,
                        'customer_cpf' => $cpf,
                        'customer_gender' => $genero,
                        'is_student' => $is_student,
                        'student_document_id' => isset($_POST['student_document_id']) ? intval($_POST['student_document_id']) : 0,
                        'items' => $checkout_items,
                        'subtotal' => $subtotal,
                        'coupon_discount' => $cupom_desconto,
                        'coupon_code' => $cupom_codigo,
                        'pix_discount' => $pix_discount,
                        'student_discount' => $student_discount,
                    );
                } elseif ($payment_method === 'credit_card') {
                    // Para cartão, verificar status de confirmação
                    $status = $payment_result['status'] ?? '';
                    if (in_array($status, array('CONFIRMED', 'RECEIVED'))) {
                        // Atualizar status do pedido para completed
                        foreach ($order_ids as $oid) {
                            $wpdb->update(
                                $table_name,
                                array(
                                    'status' => 'completed',
                                    'updated_at' => current_time('mysql'),
                                ),
                                array('id' => $oid),
                                array('%s', '%s'),
                                array('%d')
                            );
                        }
                        do_action('cursos_payment_completed', $order_id, $email_data);
                        error_log("[VetCursos Checkout] Cartão confirmado - Pedido #$order_id atualizado para 'completed'");
                    } else {
                        // Status PENDING ou PROCESSING - pagamento será confirmado via webhook
                        do_action('cursos_payment_pending', $order_id, $email_data);
                        error_log("[VetCursos Checkout] Cartão pendente - Pedido #$order_id aguardando webhook. Status: $status");
                    }
                    
                    // Liberar reservas e limpar carrinho
                    foreach ($checkout_items as $item) {
                        if (!empty($item['turma_info'])) {
                            cursos_release_reserva($item['id'], $item['turma_info']['id']);
                        }
                    }
                    cursos_clear_cart();
                    
                    // Guardar dados do cartão para exibir na mesma página
                    $card_success = true;
                    $card_success_data = array(
                        'order_id' => $order_id,
                        'total' => $amount,
                        'payment_status' => $status,
                        'invoice_url' => $payment_result['invoice_url'] ?? '',
                        'requires_redirect' => false,
                        'customer_name' => $nome,
                        'customer_email' => $email,
                        'customer_phone' => $telefone,
                        'customer_cpf' => $cpf,
                        'customer_gender' => $genero,
                        'is_student' => $is_student,
                        'student_document_id' => isset($_POST['student_document_id']) ? intval($_POST['student_document_id']) : 0,
                        'items' => $checkout_items,
                        'subtotal' => $subtotal,
                        'coupon_discount' => $cupom_desconto,
                        'coupon_code' => $cupom_codigo,
                        'pix_discount' => 0,
                        'student_discount' => $student_discount,
                        'installments' => intval($_POST['installments']) ?: 1,
                    );
                }
                
                $success = true;

                // Finalizou esta sessão de checkout com sucesso:
                // gerar novo token no próximo acesso para não reutilizar
                // o mesmo pedido em compras futuras da mesma sessão.
                if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['checkout_token'])) {
                    unset($_SESSION['checkout_token']);
                }
            } elseif ($payment_result) {
                $errors[] = 'Erro no pagamento: ' . ($payment_result['error'] ?? 'Erro desconhecido');
                // Marcar pedidos criados como failed para permitir retry limpo
                if (!empty($order_ids)) {
                    foreach ($order_ids as $oid) {
                        $wpdb->update($table_name,
                            array('status' => 'failed', 'checkout_token' => null, 'updated_at' => current_time('mysql')),
                            array('id' => $oid)
                        );
                    }
                    error_log("[VetCursos Checkout] Falha no gateway: " . ($payment_result['error'] ?? 'sem detalhe') . ". Pedidos " . implode(',', $order_ids) . " marcados como failed.");
                }
                if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['checkout_token'])) {
                    unset($_SESSION['checkout_token']);
                }
            } elseif (!empty($errors) && !empty($order_ids)) {
                // Erro antes mesmo de chamar o gateway (ex: falha em get_or_create_customer)
                foreach ($order_ids as $oid) {
                    $wpdb->update($table_name,
                        array('status' => 'failed', 'checkout_token' => null, 'updated_at' => current_time('mysql')),
                        array('id' => $oid)
                    );
                }
                if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['checkout_token'])) {
                    unset($_SESSION['checkout_token']);
                }
            }
        }
    }
}



get_header();
?>

<style>
/* Turma Section in Checkout */
.checkout-turma-section {
    margin-top: 6px;
    position: relative;
}

.checkout-order-turma {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: var(--primary, #7f0b0d);
}

.checkout-order-turma.checkout-order-turma-warning {
    color: #d97706;
}

.checkout-order-turma-date {
    color: var(--text-muted, #6b7280);
}

.checkout-change-turma-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
    padding: 4px 10px;
    font-size: 0.75rem;
    color: var(--primary, #7f0b0d);
    background: rgba(127, 11, 13, 0.08);
    border: 1px solid rgba(127, 11, 13, 0.2);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.checkout-change-turma-btn:hover {
    background: rgba(127, 11, 13, 0.15);
    border-color: rgba(127, 11, 13, 0.3);
}

.checkout-change-turma-btn svg {
    transition: transform 0.2s ease;
}

.checkout-change-turma-btn.active svg {
    transform: rotate(180deg);
}

/* Turma Dropdown */
.checkout-turma-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    margin-top: 8px;
    background: #fff;
    border: 1px solid var(--gray-200, #e5e7eb);
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    z-index: 100;
    overflow: hidden;
    min-width: 280px;
}

.checkout-turma-dropdown.open {
    display: block;
    animation: dropdownFadeIn 0.2s ease;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.checkout-turma-dropdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: var(--gray-50, #f9fafb);
    border-bottom: 1px solid var(--gray-200, #e5e7eb);
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--gray-700, #374151);
}

.checkout-turma-dropdown-close {
    padding: 4px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--gray-500, #6b7280);
    border-radius: 4px;
    transition: background 0.2s;
}

.checkout-turma-dropdown-close:hover {
    background: var(--gray-200, #e5e7eb);
}

.checkout-turma-dropdown-list {
    padding: 8px;
    max-height: 250px;
    overflow-y: auto;
}

.checkout-turma-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
    border: 2px solid transparent;
}

.checkout-turma-option:hover {
    background: var(--gray-50, #f9fafb);
}

.checkout-turma-option.selected {
    background: rgba(127, 11, 13, 0.05);
    border-color: var(--primary, #7f0b0d);
}

.checkout-turma-option.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.checkout-turma-option.disabled:hover {
    background: none;
}

.checkout-turma-option input[type="radio"] {
    display: none;
}

.checkout-turma-option-content {
    flex: 1;
}

.checkout-turma-option-name {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--gray-800, #1f2937);
    margin-bottom: 2px;
}

.checkout-turma-option-info {
    display: block;
    font-size: 0.75rem;
    color: var(--gray-500, #6b7280);
}

.checkout-turma-option-check {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary, #7f0b0d);
    color: #fff;
    border-radius: 50%;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.2s ease;
}

.checkout-turma-option.selected .checkout-turma-option-check {
    opacity: 1;
    transform: scale(1);
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .checkout-turma-dropdown {
        position: fixed;
        top: auto;
        bottom: 0;
        left: 0;
        right: 0;
        margin: 0;
        border-radius: 20px 20px 0 0;
        max-height: 70vh;
    }
    
    .checkout-turma-dropdown-list {
        max-height: calc(70vh - 60px);
    }
}

/* PIX Success Card */
.checkout-pix-success-card {
    text-align: center;
    padding: 30px !important;
}

.pix-success-header {
    margin-bottom: 24px;
}

.pix-success-icon {
    color: #10b981;
    margin-bottom: 12px;
}

.pix-success-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-800, #1f2937);
    margin: 0 0 4px 0;
}

.pix-order-id {
    font-size: 0.9rem;
    color: var(--gray-500, #6b7280);
    margin: 0;
}

.pix-total-display {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: var(--gray-50, #f9fafb);
    border-radius: 12px;
    margin-bottom: 24px;
}

.pix-total-label {
    font-size: 1rem;
    color: var(--gray-600, #4b5563);
}

.pix-total-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary, #7f0b0d);
}

.pix-qrcode-section {
    margin-bottom: 24px;
}

.pix-qrcode-label {
    font-size: 0.9rem;
    color: var(--gray-600, #4b5563);
    margin-bottom: 16px;
}

.pix-qrcode-container {
    display: flex;
    justify-content: center;
    padding: 20px;
    background: #fff;
    border: 2px solid var(--gray-200, #e5e7eb);
    border-radius: 16px;
}

.pix-qrcode-image {
    max-width: 200px;
    height: auto;
}

.pix-code-section {
    margin-bottom: 24px;
}

.pix-code-label {
    font-size: 0.9rem;
    color: var(--gray-600, #4b5563);
    margin-bottom: 12px;
}

.pix-code-container {
    display: flex;
    gap: 8px;
}

.pix-code-input {
    flex: 1;
    padding: 12px 16px;
    font-size: 0.85rem;
    font-family: monospace;
    background: var(--gray-50, #f9fafb);
    border: 1px solid var(--gray-200, #e5e7eb);
    border-radius: 8px;
    color: var(--gray-700, #374151);
    text-overflow: ellipsis;
}

.pix-copy-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 12px 20px;
    background: var(--primary, #7f0b0d);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.pix-copy-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.pix-copy-btn.copied {
    background: #10b981;
}

.pix-expiration-notice {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    background: #fef3c7;
    color: #92400e;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 24px;
}

/* PIX Countdown Container */
.pix-countdown-container {
    flex-direction: column;
    padding: 20px;
    background: #fee2e2;
    border-radius: 12px;
}

.pix-countdown-text {
    font-size: 0.95rem;
    font-weight: 600;
    color: #991b1b;
    margin-bottom: 10px;
}

.pix-countdown-timer {
    font-size: 2.5rem;
    font-weight: 700;
    color: #dc2626;
    font-family: 'Courier New', monospace;
    letter-spacing: 2px;
}

.pix-countdown-timer.urgent {
    color: #ef4444;
    animation: pixPulse 1s infinite;
}

.pix-countdown-timer.expired {
    color: #991b1b;
    font-size: 1.5rem;
}

@keyframes pixPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.02); }
}

.pix-instructions {
    text-align: left;
    padding: 20px;
    background: var(--gray-50, #f9fafb);
    border-radius: 12px;
    margin-bottom: 20px;
}

.pix-instructions h4 {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--gray-800, #1f2937);
    margin: 0 0 12px 0;
}

.pix-instructions ol {
    margin: 0;
    padding-left: 20px;
}

.pix-instructions li {
    font-size: 0.9rem;
    color: var(--gray-600, #4b5563);
    margin-bottom: 6px;
}

.pix-instructions li:last-child {
    margin-bottom: 0;
}

.pix-confirmation-notice {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: #d1fae5;
    color: #065f46;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 20px;
}

.pix-back-home-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 14px 24px;
    background: var(--gray-100, #f3f4f6);
    color: var(--gray-700, #374151);
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.pix-back-home-btn:hover {
    background: var(--gray-200, #e5e7eb);
}

@media (max-width: 768px) {
    .pix-qrcode-image {
        max-width: 160px;
    }
    
    .pix-code-container {
        flex-direction: column;
    }
    
    .pix-copy-btn {
        justify-content: center;
    }
}

/* Card Success Card */
.checkout-card-success-card {
    text-align: center;
    padding: 30px !important;
    animation: cardSuccessSlideIn 0.5s ease;
}

@keyframes cardSuccessSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card-success-header {
    margin-bottom: 24px;
}

.card-success-icon {
    width: 72px;
    height: 72px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px auto;
    animation: cardSuccessPulse 0.6s ease;
}

@keyframes cardSuccessPulse {
    0% { transform: scale(0); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.card-success-icon svg {
    color: #fff;
}

.card-success-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-800, #1f2937);
    margin: 0 0 4px 0;
}

.card-success-order-id {
    font-size: 0.9rem;
    color: var(--gray-500, #6b7280);
    margin: 0;
}

.card-success-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-top: 8px;
}

.card-success-status.confirmed {
    background: #d1fae5;
    color: #065f46;
}

.card-success-status.pending {
    background: #fef3c7;
    color: #92400e;
}

.card-success-total-display {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: var(--gray-50, #f9fafb);
    border-radius: 12px;
    margin-bottom: 24px;
}

.card-success-total-label {
    font-size: 1rem;
    color: var(--gray-600, #4b5563);
}

.card-success-total-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary, #7f0b0d);
}

.card-success-installments {
    font-size: 0.875rem;
    color: var(--gray-500, #6b7280);
    margin-left: 4px;
}

.card-success-items-section {
    text-align: left;
    background: #fff;
    border: 1px solid var(--gray-200, #e5e7eb);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
}

.card-success-items-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-700, #374151);
    margin: 0 0 12px 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.card-success-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--gray-100, #f3f4f6);
}

.card-success-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.card-success-item-name {
    font-size: 0.9rem;
    color: var(--gray-800, #1f2937);
    font-weight: 500;
}

.card-success-item-turma {
    font-size: 0.75rem;
    color: var(--gray-500, #6b7280);
    margin-top: 2px;
}

.card-success-item-price {
    font-size: 0.9rem;
    color: var(--primary, #7f0b0d);
    font-weight: 600;
    white-space: nowrap;
}

.card-success-customer-section {
    text-align: left;
    background: var(--gray-50, #f9fafb);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
}

.card-success-customer-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-700, #374151);
    margin: 0 0 12px 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.card-success-customer-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

@media (max-width: 480px) {
    .card-success-customer-grid {
        grid-template-columns: 1fr;
    }
}

.card-success-customer-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.card-success-customer-label {
    font-size: 0.7rem;
    color: var(--gray-500, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.card-success-customer-value {
    font-size: 0.875rem;
    color: var(--gray-800, #1f2937);
    font-weight: 500;
}

.card-success-next-steps {
    text-align: left;
    padding: 20px;
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-radius: 12px;
    margin-bottom: 24px;
}

.card-success-next-steps h4 {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    color: #065f46;
    margin: 0 0 12px 0;
}

.card-success-next-steps p {
    font-size: 0.875rem;
    color: #047857;
    margin: 0 0 8px 0;
}

.card-success-next-steps p:last-child {
    margin-bottom: 0;
}

.card-success-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.card-success-primary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 14px 24px;
    background: var(--primary, #7f0b0d);
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.card-success-primary-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.card-success-secondary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 14px 24px;
    background: var(--gray-100, #f3f4f6);
    color: var(--gray-700, #374151);
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.card-success-secondary-btn:hover {
    background: var(--gray-200, #e5e7eb);
}

/* Coupon Section */
.checkout-coupon-section {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--gray-50, #f9fafb);
    border-radius: 0.5rem;
    border: 1px solid var(--gray-200, #e5e7eb);
}

.checkout-coupon-label {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    color: var(--gray-700, #374151);
}

.checkout-coupon-input-group {
    display: flex;
    gap: 0.5rem;
}

.checkout-coupon-input {
    flex: 1;
    padding: 0.625rem 0.75rem;
    border: 1px solid var(--gray-300, #d1d5db);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    text-transform: uppercase;
    background: #fff;
}

.checkout-coupon-input:focus {
    outline: none;
    border-color: var(--primary, #7f0b0d);
    box-shadow: 0 0 0 2px rgba(127, 11, 13, 0.1);
}

.checkout-coupon-btn {
    padding: 0.625rem 1rem;
    background: var(--primary, #7f0b0d);
    color: #fff;
    border: none;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.2s ease;
}

.checkout-coupon-btn:hover {
    background: var(--primary-dark, #6b0a0c);
}

.checkout-coupon-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.checkout-coupon-message {
    margin-top: 0.5rem;
    font-size: 0.8125rem;
}

.checkout-coupon-message .success {
    color: #15803d;
}

.checkout-coupon-message .error {
    color: #dc2626;
}

/* CEP Validation Styles */
.cep-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.cep-input-wrapper input {
    flex: 1;
    padding-right: 40px;
}

.cep-status {
    position: absolute;
    right: 12px;
    font-size: 18px;
    display: none;
}

.cep-status.loading::after {
    content: '';
    width: 16px;
    height: 16px;
    border: 2px solid #d1d5db;
    border-top-color: var(--primary, #7f0b0d);
    border-radius: 50%;
    display: inline-block;
    animation: cep-spin 0.8s linear infinite;
}

@keyframes cep-spin {
    to { transform: rotate(360deg); }
}

.cep-status.valid {
    display: block;
    color: #10b981;
}

.cep-status.valid::after {
    content: '✓';
}

.cep-status.invalid {
    display: block;
    color: #dc2626;
}

.cep-status.invalid::after {
    content: '✕';
}

.cep-status.loading {
    display: block;
}

.form-field-hint.cep-error {
    color: #dc2626;
}

.form-field-hint.cep-success {
    color: #10b981;
}

/* Address Fields */
.checkout-address-fields {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--gray-50, #f9fafb);
    border-radius: 0.5rem;
    border: 1px solid var(--gray-200, #e5e7eb);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.checkout-address-fields .checkout-form-grid {
    gap: 0.75rem;
}

.checkout-address-fields input[readonly] {
    background: var(--gray-100, #f3f4f6);
    color: var(--gray-600, #4b5563);
}

/* Reservation Timer */
.reserva-timer-container {
    background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
    border: 1px solid #F59E0B;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.reserva-timer-container.warning {
    background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
    border-color: #DC2626;
}

.reserva-timer-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.reserva-timer-text {
    flex: 1;
}

.reserva-timer-text strong {
    display: block;
    color: #92400E;
    font-size: 0.9375rem;
}

.reserva-timer-container.warning .reserva-timer-text strong {
    color: #991B1B;
}

.reserva-timer-text span {
    font-size: 0.875rem;
    color: #B45309;
}

.reserva-timer-container.warning .reserva-timer-text span {
    color: #DC2626;
}

#timerCountdown {
    font-weight: 700;
    font-size: 1rem;
    color: #DC2626;
}

.reserva-timer-renew {
    background: #F59E0B;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    white-space: nowrap;
}

.reserva-timer-renew:hover {
    background: #D97706;
}

.reserva-timer-renew:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.checkout-applied-coupon {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: #d1fae5;
    border-radius: 0.375rem;
    color: #065f46;
}

.checkout-applied-coupon-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    font-size: 0.875rem;
    flex-wrap: wrap;
}

.checkout-applied-coupon-badge {
    background: #10b981;
    color: #fff;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.checkout-applied-coupon-info strong {
    font-weight: 600;
    color: #065f46;
}

.checkout-applied-coupon-info span {
    color: #047857;
    font-size: 0.8125rem;
}

.remove-coupon-btn {
    background: transparent;
    border: 1px solid #dc2626;
    color: #dc2626;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.remove-coupon-btn:hover {
    background: #dc2626;
    color: #fff;
}

/* PIX Summary Card - Resumo dos Dados */
.checkout-data-summary {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #86efac !important;
}

.checkout-summary-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    color: #166534;
}

.checkout-summary-header svg {
    color: #22c55e;
    flex-shrink: 0;
}

.checkout-summary-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: #166534;
}

.checkout-data-grid {
    display: grid;
    gap: 1rem;
}

.checkout-data-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.checkout-data-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 500;
}

.checkout-data-value {
    font-size: 0.9375rem;
    color: #1f2937;
    font-weight: 500;
}

.checkout-data-attachment {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #fff;
    border-radius: 0.5rem;
    margin-top: 1rem;
    color: #166534;
    font-size: 0.875rem;
}

.checkout-data-attachment svg {
    flex-shrink: 0;
}

.checkout-data-courses {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #86efac;
}

.checkout-data-courses h3 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #166534;
    margin: 0 0 0.75rem 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.checkout-data-course-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.75rem;
    background: #fff;
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
}

.checkout-data-course-item:last-child {
    margin-bottom: 0;
}

.checkout-data-course-name {
    font-size: 0.875rem;
    color: #1f2937;
    font-weight: 500;
}

.checkout-data-course-turma {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.checkout-data-course-price {
    font-size: 0.875rem;
    color: #166534;
    font-weight: 600;
    white-space: nowrap;
}
</style>

<div class="checkout-page">
    <!-- Header igual React -->
    <section class="checkout-header">
        <div class="container">
            <a href="<?php echo home_url('/carrinho'); ?>" class="checkout-back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Voltar ao Carrinho
            </a>
            <h1 class="checkout-title"><?php echo esc_html($checkout_title); ?></h1>
        </div>
    </section>
    
    <section class="checkout-content">
        <div class="container">
            <?php if ($timer_ativo && $has_reservations && $reservation_expiry !== null && !$pix_success && !$card_success): ?>
            <!-- Timer de Reserva -->
            <div class="reserva-timer-container" id="reservaTimer">
                <div class="reserva-timer-icon">⏱️</div>
                <div class="reserva-timer-text">
                    <strong>Sua vaga está reservada!</strong>
                    <span>Tempo restante: <span id="timerCountdown">--:--</span></span>
                </div>
            </div>
            <script>
            (function() {
                var tempoRestante = <?php echo intval($reservation_expiry); ?>;
                var tempoAviso = <?php echo intval($timer_aviso) * 60; ?>; // em segundos
                var timerContainer = document.getElementById('reservaTimer');
                var timerCountdown = document.getElementById('timerCountdown');
                var cursosUrl = '<?php echo home_url("/cursos"); ?>';
                
                function formatTime(seconds) {
                    var mins = Math.floor(seconds / 60);
                    var secs = seconds % 60;
                    return mins.toString().padStart(2, '0') + ':' + secs.toString().padStart(2, '0');
                }
                
                function atualizarTimer() {
                    timerCountdown.textContent = formatTime(tempoRestante);
                    
                    // Avisar quando restar menos que o tempo configurado
                    if (tempoRestante <= tempoAviso) {
                        timerContainer.classList.add('warning');
                    }
                    
                    // Quando expirar
                    if (tempoRestante <= 0) {
                        alert('Sua reserva expirou! Você será redirecionado para a página de cursos.');
                        window.location.href = cursosUrl;
                        return;
                    }
                    
                    tempoRestante--;
                    setTimeout(atualizarTimer, 1000);
                }
                
                // Iniciar timer
                if (tempoRestante > 0) {
                    atualizarTimer();
                }
            })();
            </script>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="checkout-alert checkout-alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="checkout-alert checkout-alert-success">
                    Pagamento processado com sucesso! Você receberá um e-mail com os detalhes.
                </div>
            <?php endif; ?>
            
            <?php
            // Token de idempotência (transient por 30 min)
            if (empty($_SESSION['checkout_token'])) {
                $_SESSION['checkout_token'] = wp_generate_password(32, false);
            }
            $checkout_token_value = $_SESSION['checkout_token'];

            // Verificar se algum item exige comprovação profissional
            $requires_proof_ui = false;
            foreach ($checkout_items as $ci_ui) {
                if (get_post_meta($ci_ui['id'], '_curso_sem_comprovacao', true) !== '1') {
                    $requires_proof_ui = true; break;
                }
            }
            $posted_prof_type = isset($_POST['professional_type']) ? sanitize_text_field($_POST['professional_type']) : '';
            ?>
            <form method="post" id="checkoutForm">
                <?php wp_nonce_field('process_checkout', 'checkout_nonce'); ?>
                <input type="hidden" name="checkout_token" value="<?php echo esc_attr($checkout_token_value); ?>">
                <input type="hidden" name="professional_type" id="professional_type_input" value="<?php echo esc_attr($posted_prof_type); ?>">
                <input type="hidden" name="diploma_document_id" id="diploma_document_id" value="">

                <?php if ($requires_proof_ui): ?>
                <!-- Etapa 1: Tipo de Profissional -->
                <div id="checkout_step_professional" class="checkout-card" style="margin-bottom:24px;<?php echo $posted_prof_type ? 'display:none;' : ''; ?>">
                    <h2 class="checkout-card-title">Antes de continuar, identifique-se</h2>
                    <p style="color:#6b7280;margin-bottom:16px;">Este curso é destinado a profissionais e estudantes da área. Selecione abaixo:</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <button type="button" class="prof-type-btn" data-type="veterinarian" style="padding:20px;border:2px solid #e5e7eb;border-radius:12px;background:#fff;cursor:pointer;text-align:left;transition:all .2s;">
                            <div style="margin-bottom:10px;color:#7f0b0d;">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/><path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"/><circle cx="20" cy="10" r="2"/></svg>
                            </div>
                            <strong style="display:block;font-size:1rem;color:#111;">Sou Médico Veterinário</strong>
                            <small style="color:#6b7280;">Você precisará informar CRMV ou diploma</small>
                        </button>
                        <button type="button" class="prof-type-btn" data-type="student" style="padding:20px;border:2px solid #e5e7eb;border-radius:12px;background:#fff;cursor:pointer;text-align:left;transition:all .2s;">
                            <div style="margin-bottom:10px;color:#7f0b0d;">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                            </div>
                            <strong style="display:block;font-size:1rem;color:#111;">Sou Estudante de Veterinária</strong>
                            <?php
                            $btn_est_pct = intval($discount_info['student_percent']);
                            $btn_max_est = 0;
                            foreach ($checkout_items as $ci_btn) {
                                $p_btn = floatval($ci_btn['desconto_estudante_pct']);
                                if ($p_btn > $btn_max_est) $btn_max_est = $p_btn;
                            }
                            if ($btn_max_est > 0) $btn_est_pct = intval($btn_max_est);
                            ?>
                            <small style="color:#0d7a5f;font-weight:600;"><?php if ($discount_info['student_active']): ?>Comprovante de matrícula • <span id="student_btn_discount_pct"><?php echo $btn_est_pct; ?></span>% OFF<?php else: ?>Comprovante de matrícula • desconto especial<?php endif; ?></small>
                        </button>
                    </div>
                </div>

                <?php else: ?>
                <input type="hidden" name="professional_type" value="general">
                <input type="hidden" name="is_student" value="0">
                <?php endif; ?>

                <!-- Gateway escondido -->
                <input type="hidden" name="payment_gateway" value="<?php echo esc_attr($payment_gateway); ?>">
                <input type="hidden" name="pagseguro_encrypted_card" id="pagseguro_encrypted_card" value="">
                
                <div class="checkout-grid">
                    <!-- Coluna 1: Formulário ou Resumo -->
                    <div class="checkout-form-column">
                        <?php if ($card_success && $card_success_data): ?>
                        <!-- Resumo dos Dados Enviados (quando cartão confirmado) -->
                        <div class="checkout-card checkout-data-summary">
                            <div class="checkout-summary-header">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <h2>Dados do Pedido</h2>
                            </div>
                            
                            <div class="checkout-data-grid">
                                <div class="checkout-data-item">
                                    <span class="checkout-data-label">Nome</span>
                                    <span class="checkout-data-value"><?php echo esc_html($card_success_data['customer_name'] ?? ''); ?></span>
                                </div>
                                <div class="checkout-data-item">
                                    <span class="checkout-data-label">E-mail</span>
                                    <span class="checkout-data-value"><?php echo esc_html($card_success_data['customer_email'] ?? ''); ?></span>
                                </div>
                                <?php if (!empty($card_success_data['customer_phone'])): ?>
                                <div class="checkout-data-item">
                                    <span class="checkout-data-label">Telefone</span>
                                    <span class="checkout-data-value"><?php echo esc_html($card_success_data['customer_phone']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="checkout-data-item">
                                    <span class="checkout-data-label">CPF</span>
                                    <span class="checkout-data-value"><?php echo esc_html($card_success_data['customer_cpf'] ?? ''); ?></span>
                                </div>
                            </div>
                            
                            <!-- Comprovante de Estudante (se houver) -->
                            <?php if (!empty($card_success_data['is_student']) && !empty($card_success_data['student_document_id'])): ?>
                            <div class="checkout-data-attachment">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                <span>Comprovante de estudante anexado</span>
                            </div>
                            <?php elseif (!empty($card_success_data['is_student'])): ?>
                            <div class="checkout-data-attachment">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                                </svg>
                                <span>Desconto de estudante aplicado</span>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Curso(s) comprado(s) -->
                            <div class="checkout-data-courses">
                                <h3>Curso(s)</h3>
                                <?php foreach ($card_success_data['items'] as $item): ?>
                                <div class="checkout-data-course-item">
                                    <div>
                                        <div class="checkout-data-course-name"><?php echo esc_html($item['title']); ?></div>
                                        <?php if (!empty($item['turma_info'])): ?>
                                        <div class="checkout-data-course-turma"><?php echo esc_html($item['turma_info']['nome']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="checkout-data-course-price"><?php echo cursos_format_price($item['preco']); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php elseif ($pix_success && $pix_data): ?>
                        <!-- Resumo dos Dados Enviados (quando PIX gerado) -->
                        <div class="checkout-card checkout-data-summary">
                            <div class="checkout-summary-header">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <h2>Dados do Pedido</h2>
                            </div>
                            
                            <div class="checkout-data-grid">
                                <div class="checkout-data-item">
                                    <span class="checkout-data-label">Nome</span>
                                    <span class="checkout-data-value"><?php echo esc_html($pix_data['customer_name'] ?? ''); ?></span>
                                </div>
                                <div class="checkout-data-item">
                                    <span class="checkout-data-label">E-mail</span>
                                    <span class="checkout-data-value"><?php echo esc_html($pix_data['customer_email'] ?? ''); ?></span>
                                </div>
                                <?php if (!empty($pix_data['customer_phone'])): ?>
                                <div class="checkout-data-item">
                                    <span class="checkout-data-label">Telefone</span>
                                    <span class="checkout-data-value"><?php echo esc_html($pix_data['customer_phone']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="checkout-data-item">
                                    <span class="checkout-data-label">CPF</span>
                                    <span class="checkout-data-value"><?php echo esc_html($pix_data['customer_cpf'] ?? ''); ?></span>
                                </div>
                            </div>
                            
                            <!-- Comprovante de Estudante (se houver) -->
                            <?php if (!empty($pix_data['is_student']) && !empty($pix_data['student_document_id'])): ?>
                            <div class="checkout-data-attachment">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                <span>Comprovante de estudante anexado</span>
                            </div>
                            <?php elseif (!empty($pix_data['is_student'])): ?>
                            <div class="checkout-data-attachment">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                                </svg>
                                <span>Desconto de estudante aplicado</span>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Curso(s) comprado(s) -->
                            <div class="checkout-data-courses">
                                <h3>Curso(s)</h3>
                                <?php foreach ($pix_data['items'] as $item): ?>
                                <div class="checkout-data-course-item">
                                    <div>
                                        <div class="checkout-data-course-name"><?php echo esc_html($item['title']); ?></div>
                                        <?php if (!empty($item['turma_info'])): ?>
                                        <div class="checkout-data-course-turma"><?php echo esc_html($item['turma_info']['nome']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="checkout-data-course-price"><?php echo cursos_format_price($item['preco']); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else: 
                        // Esconder Dados Pessoais + Forma de Pagamento até o usuário escolher Veterinário/Estudante
                        $hide_personal = ($requires_proof_ui && empty($posted_prof_type));
                        ?>
                        <div id="checkout_post_selection"<?php echo $hide_personal ? ' style="display:none;"' : ''; ?>>
                        <!-- Card Dados Pessoais (formulário editável) -->
                        <div class="checkout-card">
                            <h2 class="checkout-card-title">Dados Pessoais</h2>
                            
                            <div class="checkout-form-grid">
                                <div class="form-field">
                                    <label for="nome">Nome completo *</label>
                                    <input type="text" id="nome" name="nome" placeholder="Seu nome completo"
                                           value="<?php echo isset($_POST['nome']) ? esc_attr($_POST['nome']) : ''; ?>" 
                                           required maxlength="100">
                                </div>
                                
                                <div class="form-field">
                                    <label for="email">E-mail *</label>
                                    <input type="email" id="email" name="email" placeholder="seu@email.com"
                                           value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>" 
                                           required maxlength="255">
                                    <div id="email_suggestion" class="email-suggestion" style="display: none;"></div>
                                </div>
                                
                                <?php if ($checkout_phone_mode !== 'hidden'): ?>
                                <div class="form-field">
                                    <label for="telefone">Telefone <?php echo $checkout_phone_mode === 'required' ? '*' : ''; ?></label>
                                    <input type="tel" id="telefone" name="telefone" placeholder="(00) 00000-0000"
                                           value="<?php echo isset($_POST['telefone']) ? esc_attr($_POST['telefone']) : ''; ?>" 
                                           <?php echo $checkout_phone_mode === 'required' ? 'required' : ''; ?> maxlength="15">
                                </div>
                                <?php endif; ?>
                                
                                <div class="form-field">
                                    <label for="cpf">CPF *</label>
                                    <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00"
                                           value="<?php echo isset($_POST['cpf']) ? esc_attr($_POST['cpf']) : ''; ?>" 
                                           required maxlength="14">
                                </div>
                                
                                <?php if ($checkout_show_gender): ?>
                                <div class="form-field">
                                    <label for="genero">Gênero *</label>
                                    <select id="genero" name="genero" required>
                                        <option value="">Selecione o gênero</option>
                                        <option value="masculino" <?php selected(isset($_POST['genero']) ? $_POST['genero'] : '', 'masculino'); ?>>Masculino</option>
                                        <option value="feminino" <?php selected(isset($_POST['genero']) ? $_POST['genero'] : '', 'feminino'); ?>>Feminino</option>
                                        <option value="nao-binario" <?php selected(isset($_POST['genero']) ? $_POST['genero'] : '', 'nao-binario'); ?>>Não-binário</option>
                                        <option value="prefiro-nao-informar" <?php selected(isset($_POST['genero']) ? $_POST['genero'] : '', 'prefiro-nao-informar'); ?>>Prefiro não informar</option>
                                    </select>
                                </div>
                                <?php endif; ?>

                                <div class="form-field" style="grid-column: 1 / -1;">
                                    <label for="referral_source">Por onde você nos encontrou? *</label>
                                    <select id="referral_source" name="referral_source" required onchange="cursosToggleReferralDetail()">
                                        <option value="">Selecione uma opção</option>
                                        <?php $rs = isset($_POST['referral_source']) ? $_POST['referral_source'] : ''; ?>
                                        <option value="google" <?php selected($rs, 'google'); ?>>Google</option>
                                        <option value="instagram" <?php selected($rs, 'instagram'); ?>>Instagram</option>
                                        <option value="facebook" <?php selected($rs, 'facebook'); ?>>Facebook</option>
                                        <option value="indicacao" <?php selected($rs, 'indicacao'); ?>>Indicação</option>
                                        <option value="outro" <?php selected($rs, 'outro'); ?>>Outro</option>
                                    </select>
                                </div>
                                <div class="form-field" id="referral_detail_field" style="grid-column: 1 / -1; display: <?php echo in_array($rs, array('indicacao','outro'), true) ? 'block' : 'none'; ?>;">
                                    <label for="referral_detail" id="referral_detail_label">
                                        <?php echo $rs === 'indicacao' ? 'Nome de quem indicou *' : ($rs === 'outro' ? 'Conte para nós *' : 'Detalhe *'); ?>
                                    </label>
                                    <input type="text" id="referral_detail" name="referral_detail" maxlength="255"
                                           value="<?php echo isset($_POST['referral_detail']) ? esc_attr($_POST['referral_detail']) : ''; ?>">
                                </div>
                                <script>
                                function cursosToggleReferralDetail(){
                                    var sel = document.getElementById('referral_source');
                                    var field = document.getElementById('referral_detail_field');
                                    var label = document.getElementById('referral_detail_label');
                                    var input = document.getElementById('referral_detail');
                                    if (!sel || !field || !input) return;
                                    var v = sel.value;
                                    if (v === 'indicacao') {
                                        field.style.display = 'block';
                                        label.textContent = 'Nome de quem indicou *';
                                        input.required = true;
                                    } else if (v === 'outro') {
                                        field.style.display = 'block';
                                        label.textContent = 'Conte para nós *';
                                        input.required = true;
                                    } else {
                                        field.style.display = 'none';
                                        input.required = false;
                                        input.value = '';
                                    }
                                }
                                document.addEventListener('DOMContentLoaded', cursosToggleReferralDetail);
                                </script>
                            </div>
                        </div>

                        <?php if ($requires_proof_ui): ?>
                        <!-- Bloco "Comprovação Veterinário" (visível apenas se prof_type=veterinarian) -->
                        <div id="vet_proof_block" class="checkout-card" style="margin-bottom:24px;display:none;">
                            <h2 class="checkout-card-title">Comprovação Profissional <span style="font-size:.8rem;color:#6b7280;font-weight:400;">(CRMV ou Diploma)</span></h2>
                            <div class="form-field" style="margin-bottom:16px;">
                                <label for="crmv_input">CRMV</label>
                                <input type="text" id="crmv_input" name="crmv" maxlength="20" placeholder="Ex: CRMV-SP 12345" autocomplete="off" value="<?php echo isset($_POST['crmv']) ? esc_attr($_POST['crmv']) : ''; ?>">
                                <small id="crmv_help" style="color:#6b7280;">Formato: CRMV-UF seguido do número (ex: CRMV-SP 12345). Informe o CRMV <strong>OU</strong> anexe o diploma abaixo.</small>
                                <small id="crmv_error" style="color:#dc2626;display:none;font-weight:600;">CRMV inválido. Use o formato CRMV-UF seguido do número (ex: CRMV-SP 12345).</small>
                            </div>
                            <div class="form-field">
                                <label>Diploma (PDF, máx 5MB)</label>
                                <input type="file" id="diploma_file_input" accept=".pdf" style="margin-top:6px;">
                                <div id="diploma_status" style="margin-top:8px;font-size:.85rem;"></div>
                            </div>
                        </div>

                        <!-- Bloco "Comprovação Estudante" (visível apenas se prof_type=student) -->
                        <div id="student_proof_block" class="checkout-card" style="margin-bottom:24px;display:none;">
                            <h2 class="checkout-card-title">Comprovante de Estudante <span style="font-size:.8rem;color:#6b7280;font-weight:400;">(matrícula em PDF, máx 5MB)</span></h2>
                            <?php
                            // Determinar % de desconto a exibir: prioriza desconto de estudante exclusivo do curso (maior),
                            // senão usa o desconto global de estudante
                            $student_msg_pct = intval($discount_info['student_percent']);
                            $max_est_pct = 0;
                            foreach ($checkout_items as $ci_msg) {
                                $p_msg = floatval($ci_msg['desconto_estudante_pct']);
                                if ($p_msg > $max_est_pct) $max_est_pct = $p_msg;
                            }
                            if ($max_est_pct > 0) $student_msg_pct = intval($max_est_pct);
                            ?>
                            <p style="color:#0d7a5f;font-weight:600;margin:0 0 12px 0;display:flex;align-items:center;gap:6px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> <span id="student_discount_message_pct"><?php echo $student_msg_pct; ?></span>% de desconto aplicado automaticamente</p>
                            <input type="file" id="student_document_file" accept=".pdf" style="display:none;">
                            <div id="student_upload_area" class="checkout-upload-area">
                                <label for="student_document_file" class="checkout-upload-label">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <span>Clique para anexar o comprovante (PDF)</span>
                                </label>
                            </div>
                            <div id="student_upload_preview" class="checkout-upload-preview" style="display:none;">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                <div class="checkout-upload-info">
                                    <p id="student_file_name" class="checkout-upload-filename"></p>
                                    <p id="student_file_size" class="checkout-upload-filesize"></p>
                                </div>
                                <button type="button" id="remove_student_file" class="checkout-upload-remove">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                            </div>
                            <div id="student_upload_progress" class="checkout-upload-progress" style="display:none;">
                                <div class="checkout-progress-bar">
                                    <div id="upload_progress_bar" class="checkout-progress-fill"></div>
                                </div>
                                <p id="upload_status" class="checkout-progress-status">Enviando...</p>
                            </div>
                            <div id="student_upload_success" class="checkout-upload-success" style="display:none;">
                                ✓ Comprovante enviado! Será analisado em até 24h.
                            </div>
                            <div id="student_upload_error" class="checkout-upload-error" style="display:none;"></div>
                            <input type="hidden" name="student_document_id" id="student_document_id" value="">
                            <input type="hidden" name="is_student" id="is_student_hidden" value="0">
                        </div>
                        <?php endif; ?>

                        <!-- Card Forma de Pagamento -->
                        <div class="checkout-card">
                            <h2 class="checkout-card-title">Forma de Pagamento</h2>
                            
                            <div class="checkout-payment-methods">
                                <label class="checkout-payment-option" data-method="credit_card">
                                    <input type="radio" name="payment_method" value="credit_card">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="20" height="14" x="2" y="5" rx="2"/>
                                        <line x1="2" x2="22" y1="10" y2="10"/>
                                    </svg>
                                    <div class="checkout-payment-info">
                                        <span class="checkout-payment-label">Cartão de Crédito</span>
                                        <span class="checkout-payment-desc">Até 12x</span>
                                    </div>
                                </label>
                                
                                <label class="checkout-payment-option" data-method="pix">
                                    <input type="radio" name="payment_method" value="pix">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="14" height="20" x="5" y="2" rx="2" ry="2"/>
                                        <path d="M12 18h.01"/>
                                    </svg>
                                    <div class="checkout-payment-info">
                                        <span class="checkout-payment-label">PIX</span>
                                        <?php
                                        $pix_badge_pct = intval($discount_info['pix_percent']);
                                        $max_prof_pct = 0;
                                        foreach ($checkout_items as $ci_pix) {
                                            $p_pix = floatval($ci_pix['desconto_profissional_pct']);
                                            if ($p_pix > $max_prof_pct) $max_prof_pct = $p_pix;
                                        }
                                        if ($max_prof_pct > 0) $pix_badge_pct = intval($max_prof_pct);
                                        $pix_badge_show = ($discount_info['pix_active'] || $max_prof_pct > 0);
                                        if ($pix_badge_show):
                                        ?>
                                        <span class="checkout-payment-badge" id="pix_discount_badge"><span id="pix_discount_badge_pct"><?php echo $pix_badge_pct; ?></span>% OFF</span>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            </div>

                            <?php
                            // ============= Pagamento Alternativo: mapa por modalidade x método =============
                            $alt_ui_config = array('student' => array('credit_card' => false, 'pix' => false), 'prof' => array('credit_card' => false, 'pix' => false));
                            if (function_exists('cursos_get_pagto_alternativo_redirect')) {
                                foreach (array('student' => true, 'prof' => false) as $mod_key => $is_stu_ctx) {
                                    foreach (array('credit_card', 'pix') as $mtd) {
                                        $r_alt = cursos_get_pagto_alternativo_redirect($checkout_items, $mtd, $is_stu_ctx);
                                        $alt_ui_config[$mod_key][$mtd] = !empty($r_alt['ativo']);
                                    }
                                }
                            }
                            ?>
                            <script>window.CURSOS_ALT_CONFIG = <?php echo wp_json_encode($alt_ui_config); ?>;</script>

                            <!-- Info exibida quando o método selecionado usa Pagamento Alternativo (link externo) -->
                            <div id="altPaymentInfo" class="checkout-alt-info" style="display:none; margin-top:16px; padding:16px; border:1px solid #bbf7d0; background:#f0fdf4; border-radius:10px; color:#166534;">
                                <strong style="display:block;margin-bottom:6px;">Pagamento em ambiente externo</strong>
                                <span style="font-size:.9rem;">Você será redirecionado para uma página segura do nosso parceiro para concluir o pagamento. Não é necessário preencher os dados do cartão aqui.</span>
                            </div>

                            <!-- Dados do Cartão -->
                            <div id="creditCardFields" class="checkout-card-fields" style="display: none;">
                                <h3 class="checkout-card-subtitle">Dados do Cartão</h3>
                                
                                <div class="form-field form-field-full">
                                    <label for="card_number">Número do Cartão *</label>
                                    <div class="input-with-badge">
                                        <input type="text" id="card_number" name="card_number" placeholder="0000 0000 0000 0000" maxlength="23">
                                        <span id="card_brand_badge" class="card-brand-badge"></span>
                                    </div>
                                </div>
                                
                                <div class="form-field form-field-full">
                                    <label for="card_holder">Nome no Cartão *</label>
                                    <input type="text" id="card_holder" name="card_holder" placeholder="Como está impresso no cartão" maxlength="50">
                                </div>
                                
                                <div class="checkout-form-grid checkout-form-grid-2">
                                    <div class="form-field">
                                        <label for="card_expiry">Validade *</label>
                                        <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/AA" maxlength="5">
                                    </div>
                                    
                                    <div class="form-field">
                                        <label for="card_cvv">CVV *</label>
                                        <input type="text" id="card_cvv" name="card_cvv" placeholder="000" maxlength="4">
                                    </div>
                                </div>
                                
                                <div class="form-field form-field-full">
                                    <label for="card_cep">CEP do Titular *</label>
                                    <div class="cep-input-wrapper">
                                        <input type="text" id="card_cep" name="card_cep" placeholder="00000-000" maxlength="9">
                                        <span id="cep_status" class="cep-status"></span>
                                    </div>
                                    <small id="cep_hint" class="form-field-hint">CEP do endereço de cobrança do cartão</small>
                                </div>
                                
                                <div id="address_fields" class="checkout-address-fields" style="display: none;">
                                    <div class="checkout-form-grid">
                                        <div class="form-field form-field-full">
                                            <label for="card_street">Rua/Logradouro *</label>
                                            <input type="text" id="card_street" name="card_street" placeholder="Rua, Avenida, etc." maxlength="200">
                                        </div>
                                        
                                        <div class="form-field">
                                            <label for="card_number_address">Número *</label>
                                            <input type="text" id="card_number_address" name="card_number_address" placeholder="Nº" maxlength="10">
                                        </div>
                                        
                                        <div class="form-field">
                                            <label for="card_complement">Complemento</label>
                                            <input type="text" id="card_complement" name="card_complement" placeholder="Apto, Sala, etc." maxlength="50">
                                        </div>
                                        
                                        <div class="form-field">
                                            <label for="card_neighborhood">Bairro *</label>
                                            <input type="text" id="card_neighborhood" name="card_neighborhood" placeholder="Bairro" maxlength="100">
                                        </div>
                                        
                                        <div class="form-field">
                                            <label for="card_city">Cidade *</label>
                                            <input type="text" id="card_city" name="card_city" placeholder="Cidade" maxlength="100" readonly>
                                        </div>
                                        
                                        <div class="form-field" style="max-width: 100px;">
                                            <label for="card_state">UF *</label>
                                            <input type="text" id="card_state" name="card_state" placeholder="UF" maxlength="2" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-field form-field-full">
                                    <label for="installments">Parcelas *</label>
                                    <select name="installments" id="installments">
                                <?php 
                                $sj_override_render = function_exists('cursos_get_effective_sem_juros') ? cursos_get_effective_sem_juros($checkout_items) : null;
                                $installment_options = cursos_get_installment_options($subtotal, '', $sj_override_render);
                                $repasse_taxas_ativo = get_option('cursos_repasse_taxas_ativo', '0') === '1';
                                $sem_juros_config = ($sj_override_render !== null) ? intval($sj_override_render) : intval(get_option('cursos_parcelamento_sem_juros', 12));
                                
                                foreach ($installment_options as $option): 
                                    $label = $option['number'] . 'x de ' . cursos_format_price($option['value']);
                                    
                                    // Se há juros, mostrar informações detalhadas (CDC compliance)
                                    if (!$option['interest_free']) {
                                        $juros_valor = $option['total'] - $subtotal;
                                        $label .= ' (total: ' . cursos_format_price($option['total']) . ')';
                                    } else {
                                        $label .= ' sem juros';
                                    }
                                ?>
                                <option value="<?php echo $option['number']; ?>" 
                                        data-value="<?php echo esc_attr($option['value']); ?>" 
                                        data-total="<?php echo esc_attr($option['total']); ?>" 
                                        data-interest-free="<?php echo $option['interest_free'] ? '1' : '0'; ?>"
                                        data-original-value="<?php echo esc_attr($subtotal); ?>">
                                    <?php echo esc_html($label); ?>
                                </option>
                                <?php endforeach; ?>
                                    </select>
                                    
                                </div>
                            </div>
                        </div>
                        
                        <!-- Checkbox de Termos -->
                        <div class="checkout-terms">
                            <label class="checkout-terms-label">
                                <input type="checkbox" id="accept_terms" name="accept_terms" value="1" <?php echo $terms_required ? 'required' : ''; ?>>
                                <span class="checkout-terms-text">
                                    <?php echo esc_html($terms_text); ?>
                                    <a href="<?php echo esc_url($terms_link1_url); ?>" target="_blank"><?php echo esc_html($terms_link1_label); ?></a>,
                                    <a href="<?php echo esc_url($terms_link2_url); ?>" target="_blank"><?php echo esc_html($terms_link2_label); ?></a>
                                    e a
                                    <a href="<?php echo esc_url($terms_link3_url); ?>" target="_blank"><?php echo esc_html($terms_link3_label); ?></a><?php echo esc_html($terms_final_text); ?>
                                </span>
                            </label>
                        </div>
                        </div><!-- /#checkout_post_selection -->
                        <?php endif; // End of !$pix_success && !$card_success block ?>
                    </div>
                    
                    <!-- Coluna 2: Resumo do Pedido ou PIX -->
                    <div class="checkout-summary-column" id="checkout_summary_column"<?php echo (!$card_success && !$pix_success && $requires_proof_ui && empty($posted_prof_type)) ? ' style="display:none;"' : ''; ?>>
                        <?php if ($card_success && $card_success_data): ?>
                        <!-- Card Payment Success -->
                        <div class="checkout-card checkout-card-success-card">
                            <div class="card-success-header">
                                <?php $requires_asaas_redirect = !empty($card_success_data['requires_redirect']) && !empty($card_success_data['invoice_url']); ?>
                                <div class="card-success-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                                <h2><?php echo $requires_asaas_redirect ? 'Finalize o pagamento no Asaas' : 'Pagamento Confirmado!'; ?></h2>
                                <p class="card-success-order-id">Pedido #<?php echo esc_html($card_success_data['order_id']); ?></p>
                                <?php 
                                $payment_status = $card_success_data['payment_status'] ?? 'PENDING';
                                $status_class = in_array($payment_status, array('CONFIRMED', 'RECEIVED')) ? 'confirmed' : 'pending';
                                $status_text = in_array($payment_status, array('CONFIRMED', 'RECEIVED')) ? 'Aprovado' : ($requires_asaas_redirect ? 'Aguardando pagamento' : 'Processando');
                                ?>
                                <span class="card-success-status <?php echo esc_attr($status_class); ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <?php if ($status_class === 'confirmed'): ?>
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                        <?php else: ?>
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                        <?php endif; ?>
                                    </svg>
                                    <?php echo esc_html($status_text); ?>
                                </span>
                            </div>
                            
                            <div class="card-success-total-display">
                                <span class="card-success-total-label">Total pago:</span>
                                <span class="card-success-total-value"><?php echo cursos_format_price($card_success_data['total']); ?></span>
                                <?php if ($card_success_data['installments'] > 1): ?>
                                <span class="card-success-installments">(<?php echo intval($card_success_data['installments']); ?>x)</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Itens comprados -->
                            <div class="card-success-items-section">
                                <h3 class="card-success-items-title">Curso(s) adquirido(s)</h3>
                                <?php foreach ($card_success_data['items'] as $item): ?>
                                <div class="card-success-item">
                                    <div>
                                        <div class="card-success-item-name"><?php echo esc_html($item['title']); ?></div>
                                        <?php if (!empty($item['turma_info'])): ?>
                                        <div class="card-success-item-turma"><?php echo esc_html($item['turma_info']['nome']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-success-item-price"><?php echo cursos_format_price($item['preco']); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Dados do cliente -->
                            <div class="card-success-customer-section">
                                <h3 class="card-success-customer-title">Dados do comprador</h3>
                                <div class="card-success-customer-grid">
                                    <div class="card-success-customer-item">
                                        <span class="card-success-customer-label">Nome</span>
                                        <span class="card-success-customer-value"><?php echo esc_html($card_success_data['customer_name']); ?></span>
                                    </div>
                                    <div class="card-success-customer-item">
                                        <span class="card-success-customer-label">E-mail</span>
                                        <span class="card-success-customer-value"><?php echo esc_html($card_success_data['customer_email']); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Próximos passos -->
                            <div class="card-success-next-steps">
                                <h4>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    Próximos passos
                                </h4>
                                <p>✓ Você receberá um e-mail de confirmação em breve</p>
                                <?php if ($requires_asaas_redirect): ?>
                                <p>✓ Conclua o cartão na página segura do Asaas</p>
                                <?php else: ?>
                                <p>✓ O acesso ao curso será liberado automaticamente</p>
                                <?php endif; ?>
                                <?php if (!in_array($payment_status, array('CONFIRMED', 'RECEIVED')) && !$requires_asaas_redirect): ?>
                                <p>⏳ Aguardando confirmação do pagamento pela operadora</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Botões de ação -->
                            <div class="card-success-actions">
                                <?php if ($requires_asaas_redirect): ?>
                                <a href="<?php echo esc_url($card_success_data['invoice_url']); ?>" class="card-success-primary-btn">
                                    Pagar no Asaas
                                </a>
                                <?php else: ?>
                                <a href="<?php echo home_url('/cursos'); ?>" class="card-success-primary-btn">
                                    Continuar Comprando
                                </a>
                                <?php endif; ?>
                                <a href="<?php echo home_url('/'); ?>" class="card-success-secondary-btn">
                                    Voltar para Página Inicial
                                </a>
                            </div>
                        </div>
                        <?php if ($requires_asaas_redirect): ?>
                        <script>
                        setTimeout(function() {
                            window.location.href = <?php echo json_encode(esc_url_raw($card_success_data['invoice_url'])); ?>;
                        }, 1200);
                        </script>
                        <?php endif; ?>
                        
                        <?php elseif ($pix_success && $pix_data): ?>
                        <!-- PIX Payment Success -->
                        <div class="checkout-card checkout-pix-success-card">
                            <div class="pix-success-header">
                                <svg class="pix-success-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <h2>PIX Gerado com Sucesso!</h2>
                                <p class="pix-order-id">Pedido #<?php echo esc_html($pix_data['order_id']); ?></p>
                            </div>
                            
                            <div class="pix-total-display">
                                <span class="pix-total-label">Valor a pagar:</span>
                                <span class="pix-total-value"><?php echo cursos_format_price($pix_data['total']); ?></span>
                            </div>
                            
                            <?php if (!empty($pix_data['pix_qrcode'])): 
                                // Detecta se é base64 ou URL
                                $pix_qrcode_value = $pix_data['pix_qrcode'];
                                $is_base64 = (strpos($pix_qrcode_value, 'http') !== 0 && strpos($pix_qrcode_value, '//') !== 0);
                                $qr_src = $is_base64 ? 'data:image/png;base64,' . $pix_qrcode_value : $pix_qrcode_value;
                            ?>
                            <div class="pix-qrcode-section">
                                <p class="pix-qrcode-label">Escaneie o QR Code com seu app do banco:</p>
                                <div class="pix-qrcode-container">
                                    <img src="<?php echo esc_attr($qr_src); ?>" alt="QR Code PIX" class="pix-qrcode-image" 
                                         onerror="this.style.display='none'; this.parentElement.innerHTML='<p style=\'color:#dc2626;text-align:center;\'>Erro ao carregar QR Code. Use o código abaixo.</p>';">
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($pix_data['pix_code'])): ?>
                            <div class="pix-code-section">
                                <p class="pix-code-label">Ou copie o código PIX:</p>
                                <div class="pix-code-container">
                                    <input type="text" readonly id="pix_code_input" class="pix-code-input" value="<?php echo esc_attr($pix_data['pix_code']); ?>">
                                    <button type="button" onclick="copyPixCode()" class="pix-copy-btn" id="pix_copy_btn">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                        </svg>
                                        <span id="pix_copy_text">Copiar</span>
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="pix-info-notice" style="background: #f0f9ff; padding: 16px; border-radius: 8px; border-left: 4px solid #0284c7; margin: 16px 0;">
                                <p style="margin: 0; color: #0369a1;">
                                    <strong>📱 Pagamento via PIX:</strong> O código será válido por 24 horas. 
                                    Realize o pagamento para garantir sua vaga.
                                </p>
                            </div>
                            
                            <div class="pix-instructions">
                                <h4>Como pagar:</h4>
                                <ol>
                                    <li>Abra o app do seu banco</li>
                                    <li>Escolha pagar com PIX</li>
                                    <li>Escaneie o QR Code ou cole o código</li>
                                    <li>Confirme o pagamento</li>
                                </ol>
                            </div>
                            
                            <div class="pix-confirmation-notice">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <span>Após o pagamento, você receberá um e-mail de confirmação automaticamente.</span>
                            </div>
                            
                            <a href="<?php echo home_url('/'); ?>" class="pix-back-home-btn">
                                Voltar para a página inicial
                            </a>
                        </div>
                        
                        <?php else: ?>
                        <!-- Normal Order Summary -->
                        <div class="checkout-card checkout-summary-card">
                            <h2 class="checkout-card-title">Resumo do Pedido</h2>
                            
                            <!-- Itens do carrinho -->
                            <?php foreach ($checkout_items as $checkout_item): 
                                // Buscar turmas disponíveis para este curso
                                $item_turmas = get_post_meta($checkout_item['id'], '_curso_turmas', true);
                                $item_sem_turma = get_post_meta($checkout_item['id'], '_curso_sem_turma_aberta', true);
                                $item_tem_turmas = !empty($item_turmas) && is_array($item_turmas) && !$item_sem_turma;
                            ?>
                            <div class="checkout-order-item" data-curso-id="<?php echo esc_attr($checkout_item['id']); ?>">
                                <div class="checkout-order-image">
                                    <?php 
                                    $thumbnail = get_the_post_thumbnail($checkout_item['id'], 'thumbnail');
                                    if ($thumbnail) {
                                        echo $thumbnail;
                                    } else {
                                        echo '<div class="checkout-order-image-placeholder"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg></div>';
                                    }
                                    ?>
                                </div>
                                <div class="checkout-order-details">
                                    <div class="checkout-order-info">
                                        <p class="checkout-order-name"><?php echo esc_html($checkout_item['title']); ?></p>
                                        
                                        <?php if ($item_tem_turmas): ?>
                                        <div class="checkout-turma-section">
                                            <?php if (!empty($checkout_item['turma_info'])): ?>
                                            <div class="checkout-order-turma" id="turma-display-<?php echo esc_attr($checkout_item['id']); ?>">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                                </svg>
                                                <span class="turma-nome"><?php echo esc_html($checkout_item['turma_info']['nome']); ?></span>
                                                <?php if (!empty($checkout_item['turma_info']['data_inicio'])): ?>
                                                <span class="checkout-order-turma-date">• 
                                                    <?php echo esc_html(cursos_format_turma_date($checkout_item['turma_info'])); ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php else: ?>
                                            <div class="checkout-order-turma checkout-order-turma-warning" id="turma-display-<?php echo esc_attr($checkout_item['id']); ?>">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                                </svg>
                                                <span class="turma-nome">Selecione uma turma</span>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <button type="button" class="checkout-change-turma-btn" onclick="toggleTurmaDropdown(<?php echo esc_attr($checkout_item['id']); ?>)">
                                                Alterar Turma
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="6 9 12 15 18 9"></polyline>
                                                </svg>
                                            </button>
                                            
                                            <!-- Dropdown de turmas -->
                                            <div class="checkout-turma-dropdown" id="turma-dropdown-<?php echo esc_attr($checkout_item['id']); ?>">
                                                <div class="checkout-turma-dropdown-header">
                                                    <span>Selecione uma turma</span>
                                                    <button type="button" onclick="closeTurmaDropdown(<?php echo esc_attr($checkout_item['id']); ?>)" class="checkout-turma-close-btn">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <div class="checkout-turma-dropdown-list">
                                                    <?php foreach ($item_turmas as $turma): 
                                                        $is_selected = !empty($checkout_item['turma_info']) && $checkout_item['turma_info']['id'] === $turma['id'];
                                                        // Verificar vagas
                                                        $vagas_check = cursos_check_turma_vagas($checkout_item['id'], $turma['id']);
                                                        $tem_vagas = $vagas_check['disponivel'];
                                                        $vagas_restantes = $vagas_check['vagas_restantes'];
                                                    ?>
                                                    <label class="checkout-turma-option <?php echo $is_selected ? 'selected' : ''; ?> <?php echo !$tem_vagas ? 'disabled' : ''; ?>">
                                                        <input type="radio" 
                                                               name="turma_curso_<?php echo esc_attr($checkout_item['id']); ?>" 
                                                               value="<?php echo esc_attr($turma['id']); ?>"
                                                               data-curso-id="<?php echo esc_attr($checkout_item['id']); ?>"
                                                               data-turma-nome="<?php echo esc_attr($turma['nome']); ?>"
                                                               data-turma-data="<?php echo !empty($turma['data_inicio']) ? esc_attr(cursos_format_turma_date($turma)) : ''; ?>"
                                                               <?php echo $is_selected ? 'checked' : ''; ?>
                                                               <?php echo !$tem_vagas ? 'disabled' : ''; ?>
                                                               onchange="selectTurma(this)">
                                                        <div class="checkout-turma-option-content">
                                                            <span class="checkout-turma-option-name"><?php echo esc_html($turma['nome']); ?></span>
                                                            <span class="checkout-turma-option-info">
                                                                <?php if (!empty($turma['data_inicio'])): ?>
                                                                    <?php echo esc_html(cursos_format_turma_date($turma)); ?>
                                                                <?php endif; ?>
                                                                <?php if ($tem_vagas && $vagas_restantes !== null && $vagas_restantes > 0): ?>
                                                                • <?php echo $vagas_restantes; ?> vaga<?php echo $vagas_restantes > 1 ? 's' : ''; ?>
                                                                <?php elseif (!$tem_vagas): ?>
                                                                • Esgotado
                                                                <?php endif; ?>
                                                            </span>
                                                        </div>
                                                        <div class="checkout-turma-option-check">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                                <polyline points="20 6 9 17 4 12"></polyline>
                                                            </svg>
                                                        </div>
                                                    </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Hidden input for selected turma -->
                                            <input type="hidden" name="turma_id[<?php echo esc_attr($checkout_item['id']); ?>]" 
                                                   id="turma-input-<?php echo esc_attr($checkout_item['id']); ?>"
                                                   value="<?php echo esc_attr($checkout_item['turma_id'] ?? ''); ?>">
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="checkout-order-price">
                                        <?php echo cursos_format_price($checkout_item['preco']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- Cupom Section -->
                            <div class="checkout-coupon-section">
                                <label for="coupon_code" class="checkout-coupon-label">Cupom de Desconto</label>
                                <div class="checkout-coupon-input-group">
                                    <input type="text" id="coupon_code" name="coupon_code_input" placeholder="Digite seu cupom" class="checkout-coupon-input">
                                    <button type="button" id="apply_coupon" class="checkout-coupon-btn">Aplicar</button>
                                </div>
                                <div id="coupon_message" class="checkout-coupon-message"></div>
                                
                                <!-- Hidden fields para cupom aplicado -->
                                <input type="hidden" name="cupom_codigo" id="cupom_codigo" value="">
                                <input type="hidden" name="cupom_id" id="cupom_id" value="">
                                <input type="hidden" name="cupom_desconto" id="cupom_desconto" value="0">
                                
                                <!-- Applied coupon display -->
                                <div id="applied_coupon" class="checkout-applied-coupon" style="display: none;">
                                    <div class="checkout-applied-coupon-info">
                                        <span class="checkout-applied-coupon-badge">Cupom aplicado</span>
                                        <strong id="applied_coupon_code"></strong>
                                        <span id="applied_coupon_discount"></span>
                                    </div>
                                    <button type="button" id="remove_coupon" class="remove-coupon-btn">Remover</button>
                                </div>
                            </div>
                            
                            <!-- Totais -->
                            <div class="checkout-totals">
                                <div class="checkout-total-row">
                                    <span>Subtotal</span>
                                    <span id="order_subtotal"><?php echo cursos_format_price($subtotal); ?></span>
                                </div>
                                
                                <div class="checkout-total-row checkout-discount-row coupon-discount-row" style="display: none;">
                                    <span>Cupom</span>
                                    <span id="coupon_discount_display">-R$ 0,00</span>
                                </div>

                                <div class="checkout-total-row checkout-discount-row course-discount-row" style="display: <?php echo $course_discount_total > 0 ? 'flex' : 'none'; ?>;">
                                    <span>Desconto do curso</span>
                                    <span id="course_discount_display">-<?php echo cursos_format_price($course_discount_total); ?></span>
                                </div>

                                
                                <div class="checkout-total-row checkout-discount-row pix-discount-row" style="display: none;">
                                    <span>PIX (<?php echo intval($discount_info['pix_percent']); ?>%)</span>
                                    <span id="pix_discount_display">-R$ 0,00</span>
                                </div>
                                
                                <div class="checkout-total-row checkout-discount-row student-discount-row" style="display: none;">
                                    <span>Estudante (<?php echo intval($discount_info['student_percent']); ?>%)</span>
                                    <span id="student_discount_display">-R$ 0,00</span>
                                </div>
                                
                                <div id="discounts_blocked_notice" class="checkout-discounts-blocked-notice" style="display: none;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <span>O cupom aplicado não acumula com outros descontos</span>
                                </div>
                                
                                <div class="checkout-total-row checkout-total-final">
                                    <span>Total</span>
                                    <span class="checkout-total-amount" id="order_total"><?php echo cursos_format_price($subtotal); ?></span>
                                </div>
                                
                                <div id="installment_display" class="checkout-installment-display" style="display: none;"></div>
                            </div>
                            
                            <!-- Botão de Submit -->
                            <button type="submit" name="checkout_submit" value="1" class="checkout-submit-btn" id="submit_btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                <span id="submit_btn_text"><?php echo esc_html($checkout_button_text); ?> <?php echo cursos_format_price($subtotal); ?></span>
                            </button>
                            
                            <div class="checkout-secure-message">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                <?php echo esc_html($checkout_secure_message); ?>
                            </div>
                            <div class="checkout-discount-notice">
                                Descontos não cumulativos
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<?php if ($pagseguro_active): ?>
<script src="https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js"></script>
<?php endif; ?>

<script>
var originalPrice = <?php echo floatval($subtotal); ?>;
var checkoutPaymentGateway = <?php echo wp_json_encode($payment_gateway); ?>;
var pagseguroPublicKey = <?php echo wp_json_encode($pagseguro_public_key); ?>;
var pagseguroPublicKeyError = <?php echo wp_json_encode($pagseguro_public_key_error); ?>;
var courseDiscountTotal = 0;
var eligibleSubtotal = <?php echo floatval($subtotal); ?>;
var couponDiscount = 0;
var couponBlocksOtherDiscounts = false;
var currentPaymentMethod = '';
var isStudent = false;
var currentInstallments = 1;

// Itens do checkout (com ambos descontos por curso)
var checkoutItems = <?php echo json_encode(array_map(function($ci){
    return array(
        'preco' => floatval($ci['preco']),
        'est_pct' => floatval($ci['desconto_estudante_pct']),
        'est_acc' => !empty($ci['desconto_estudante_acumula']),
        'prof_pct' => floatval($ci['desconto_profissional_pct']),
        'prof_acc' => !empty($ci['desconto_profissional_acumula']),
    );
}, $checkout_items)); ?>;

var discountConfig = {
    pixActive: <?php echo $discount_info['pix_active'] ? 'true' : 'false'; ?>,
    pixPercent: <?php echo floatval($discount_info['pix_percent']); ?>,
    studentActive: <?php echo $discount_info['student_active'] ? 'true' : 'false'; ?>,
    studentPercent: <?php echo floatval($discount_info['student_percent']); ?>
};

// Recalcula courseDiscountTotal e eligibleSubtotal com base no contexto atual
function recomputeCourseTotals() {
    var cd = 0, eligible = 0;
    for (var i = 0; i < checkoutItems.length; i++) {
        var it = checkoutItems[i];
        var pct = 0, acc = false;
        if (isStudent) { pct = it.est_pct; acc = it.est_acc; }
        else if (currentPaymentMethod === 'pix') { pct = it.prof_pct; acc = it.prof_acc; }
        if (pct > 0) {
            cd += it.preco * (pct / 100);
            if (acc) eligible += it.preco;
        } else {
            eligible += it.preco;
        }
    }
    courseDiscountTotal = cd;
    eligibleSubtotal = eligible;
}


// Validação real de CPF
function validateCPF(cpf) {
    var numbers = cpf.replace(/\D/g, '');
    if (numbers.length !== 11) return false;
    if (/^(\d)\1+$/.test(numbers)) return false;
    
    var sum = 0;
    for (var i = 0; i < 9; i++) {
        sum += parseInt(numbers[i]) * (10 - i);
    }
    var remainder = (sum * 10) % 11;
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(numbers[9])) return false;
    
    sum = 0;
    for (var i = 0; i < 10; i++) {
        sum += parseInt(numbers[i]) * (11 - i);
    }
    remainder = (sum * 10) % 11;
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(numbers[10])) return false;
    
    return true;
}

// DDDs válidos
var validDDDs = [11,12,13,14,15,16,17,18,19,21,22,24,27,28,31,32,33,34,35,37,38,41,42,43,44,45,46,47,48,49,51,53,54,55,61,62,64,63,65,66,67,68,69,71,73,74,75,77,79,81,87,82,83,84,85,88,86,89,91,93,94,92,97,95,96,98,99];

function validatePhone(phone) {
    var numbers = phone.replace(/\D/g, '');
    if (numbers.length < 10 || numbers.length > 11) return false;
    var ddd = parseInt(numbers.substring(0, 2));
    if (validDDDs.indexOf(ddd) === -1) return false;
    if (numbers.length === 11 && numbers[2] !== '9') return false;
    return true;
}

// Sugestão de e-mail
var domainTypos = {
    'gmail.com.br': 'gmail.com',
    'gmal.com': 'gmail.com',
    'gmial.com': 'gmail.com',
    'hotmal.com': 'hotmail.com',
    'outloo.com': 'outlook.com',
    'outlook.con': 'outlook.com'
};

function suggestEmailCorrection(email) {
    var parts = email.toLowerCase().split('@');
    if (parts.length !== 2) return null;
    var domain = parts[1];
    if (domainTypos[domain]) {
        return parts[0] + '@' + domainTypos[domain];
    }
    return null;
}

// Validação de cartão (Luhn)
function validateCardNumber(cardNumber) {
    var numbers = cardNumber.replace(/\D/g, '');
    if (numbers.length < 13 || numbers.length > 19) return false;
    var sum = 0, isEven = false;
    for (var i = numbers.length - 1; i >= 0; i--) {
        var digit = parseInt(numbers[i]);
        if (isEven) {
            digit *= 2;
            if (digit > 9) digit -= 9;
        }
        sum += digit;
        isEven = !isEven;
    }
    return sum % 10 === 0;
}

// Detectar bandeira
function getCardBrand(cardNumber) {
    var numbers = cardNumber.replace(/\D/g, '');
    if (/^4/.test(numbers)) return 'Visa';
    if (/^5[1-5]/.test(numbers) || /^2[2-7]/.test(numbers)) return 'Mastercard';
    if (/^3[47]/.test(numbers)) return 'Amex';
    if (/^(606282|3841)/.test(numbers)) return 'Hipercard';
    if (/^(4011|4312|4389|4514|4576|5041|5066|5067|509)/.test(numbers)) return 'Elo';
    return '';
}

// Validar validade
function validateExpiry(expiry) {
    var match = expiry.match(/^(\d{2})\/(\d{2})$/);
    if (!match) return false;
    var month = parseInt(match[1]);
    var year = parseInt('20' + match[2]);
    if (month < 1 || month > 12) return false;
    var now = new Date();
    if (year < now.getFullYear()) return false;
    if (year === now.getFullYear() && month < now.getMonth() + 1) return false;
    return true;
}

// Formatação
function formatCPF(value) {
    var numbers = value.replace(/\D/g, '').slice(0, 11);
    return numbers.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
}

function formatPhone(value) {
    var numbers = value.replace(/\D/g, '').slice(0, 11);
    if (numbers.length <= 10) {
        return numbers.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2');
    }
    return numbers.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2');
}

function formatCardNumber(value) {
    var numbers = value.replace(/\D/g, '').slice(0, 19);
    var brand = getCardBrand(numbers);
    if (brand === 'Amex') {
        return numbers.replace(/(\d{4})(\d)/, '$1 $2').replace(/(\d{4}) (\d{6})(\d)/, '$1 $2 $3');
    }
    return numbers.replace(/(\d{4})(?=\d)/g, '$1 ');
}

function formatExpiry(value) {
    var numbers = value.replace(/\D/g, '').slice(0, 4);
    if (numbers.length >= 2) {
        return numbers.slice(0, 2) + '/' + numbers.slice(2);
    }
    return numbers;
}

function formatPrice(value) {
    return 'R$ ' + value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function calculateDiscounts() {
    recomputeCourseTotals();
    var subtotalAfterCoupon = originalPrice - couponDiscount;
    if (subtotalAfterCoupon < 0) subtotalAfterCoupon = 0;

    // Base elegível para PIX/Estudante (exclui cursos com desconto exclusivo não-acumulável)
    var eligibleAfterCoupon = eligibleSubtotal;
    if (originalPrice > 0) {
        eligibleAfterCoupon = eligibleSubtotal - (couponDiscount * (eligibleSubtotal / originalPrice));
    }
    if (eligibleAfterCoupon < 0) eligibleAfterCoupon = 0;

    var pixDiscount = 0;
    var studentDiscount = 0;
    var effectiveCourseDiscount = courseDiscountTotal;

    // Se cupom bloqueia outros descontos, não aplicar PIX/Estudante nem desconto por curso
    if (couponBlocksOtherDiscounts && couponDiscount > 0) {
        effectiveCourseDiscount = 0;
    } else {
        if (isStudent && discountConfig.studentActive) {
            studentDiscount = eligibleAfterCoupon * (discountConfig.studentPercent / 100);
        } else if (!isStudent && currentPaymentMethod === 'pix' && discountConfig.pixActive) {
            pixDiscount = eligibleAfterCoupon * (discountConfig.pixPercent / 100);
        }
    }

    var finalTotal = subtotalAfterCoupon - effectiveCourseDiscount - pixDiscount - studentDiscount;
    if (finalTotal < 0) finalTotal = 0;


    return {
        subtotalAfterCoupon: subtotalAfterCoupon,
        courseDiscount: effectiveCourseDiscount,
        pixDiscount: pixDiscount,
        studentDiscount: studentDiscount,
        finalTotal: finalTotal,
        couponBlocksOtherDiscounts: couponBlocksOtherDiscounts && couponDiscount > 0
    };

}

function applyAltPaymentUI() {
    try {
        var cfg = window.CURSOS_ALT_CONFIG;
        if (!cfg) return;
        var mod = isStudent ? 'student' : 'prof';
        var altActive = !!(cfg[mod] && cfg[mod][currentPaymentMethod]);
        var cardFields = document.getElementById('creditCardFields');
        var altInfo = document.getElementById('altPaymentInfo');
        if (altActive) {
            if (cardFields) cardFields.style.display = 'none';
            if (altInfo) altInfo.style.display = 'block';
        } else {
            if (altInfo) altInfo.style.display = 'none';
            if (cardFields) cardFields.style.display = (currentPaymentMethod === 'credit_card') ? 'block' : 'none';
        }
    } catch (e) { /* noop */ }
}

function updateOrderTotal() {
    applyAltPaymentUI();
    var result = calculateDiscounts();

    
    document.getElementById('order_total').textContent = formatPrice(result.finalTotal);
    
    // Atualizar texto do botão
    document.getElementById('submit_btn_text').textContent = '<?php echo esc_js($checkout_button_text); ?> ' + formatPrice(result.finalTotal);
    
    // PIX discount row
    var pixRow = document.querySelector('.pix-discount-row');
    if (result.pixDiscount > 0) {
        pixRow.style.display = 'flex';
        document.getElementById('pix_discount_display').textContent = '-' + formatPrice(result.pixDiscount);
    } else {
        pixRow.style.display = 'none';
    }
    
    // Student discount row
    var studentRow = document.querySelector('.student-discount-row');
    if (result.studentDiscount > 0) {
        studentRow.style.display = 'flex';
        document.getElementById('student_discount_display').textContent = '-' + formatPrice(result.studentDiscount);
    } else {
        studentRow.style.display = 'none';
    }

    // Course discount row (desconto por curso, contextual)
    var courseRow = document.querySelector('.course-discount-row');
    if (courseRow) {
        if (result.courseDiscount > 0) {
            courseRow.style.display = 'flex';
            var cdDisp = document.getElementById('course_discount_display');
            if (cdDisp) cdDisp.textContent = '-' + formatPrice(result.courseDiscount);
        } else {
            courseRow.style.display = 'none';
        }
    }

    // Atualizar tags de % dinamicamente (estudante e PIX) — usa maior pct por curso ou cai no global
    var maxEstPct = 0, maxProfPct = 0;
    for (var i = 0; i < checkoutItems.length; i++) {
        if (checkoutItems[i].est_pct > maxEstPct) maxEstPct = checkoutItems[i].est_pct;
        if (checkoutItems[i].prof_pct > maxProfPct) maxProfPct = checkoutItems[i].prof_pct;
    }
    var studentMsg = document.getElementById('student_discount_message_pct');
    var studentBtn = document.getElementById('student_btn_discount_pct');
    var sp = maxEstPct > 0 ? maxEstPct : discountConfig.studentPercent;
    if (studentMsg) studentMsg.textContent = Math.round(sp);
    if (studentBtn) studentBtn.textContent = Math.round(sp);
    var pixBadgePct = document.getElementById('pix_discount_badge_pct');
    if (pixBadgePct) {
        var pp = maxProfPct > 0 ? maxProfPct : discountConfig.pixPercent;
        pixBadgePct.textContent = Math.round(pp);
    }
    
    // Mostrar aviso quando cupom bloqueia outros descontos
    var blockedNotice = document.getElementById('discounts_blocked_notice');
    if (blockedNotice) {
        if (result.couponBlocksOtherDiscounts) {
            blockedNotice.style.display = 'block';
        } else {
            blockedNotice.style.display = 'none';
        }
    }
    
    // Atualizar parcelas
    updateInstallments(result.finalTotal);
}

function updateInstallments(total) {
    var installmentsSelect = document.getElementById('installments');
    var installmentDisplay = document.getElementById('installment_display');
    
    // Configurações de parcelamento do PHP
    var repasseTaxasAtivo = <?php echo get_option('cursos_repasse_taxas_ativo', '0') === '1' ? 'true' : 'false'; ?>;
    var semJuros = <?php echo intval(function_exists('cursos_get_effective_sem_juros') ? cursos_get_effective_sem_juros($checkout_items) : get_option('cursos_parcelamento_sem_juros', 12)); ?>;
    
    // Taxas detalhadas por parcela (taxa aplicada uma vez sobre o valor total)
    var taxasPorParcela = <?php echo json_encode(cursos_get_taxas_detalhadas_js()); ?>;
    
    if (installmentsSelect) {
        var options = installmentsSelect.querySelectorAll('option');
        options.forEach(function(option, index) {
            var i = index + 1;
            var value, totalValue, interestFree;
            
            if (repasseTaxasAtivo && i > semJuros) {
                // Obter taxas específicas para esta quantidade de parcelas
                var taxas = taxasPorParcela[i] || {percentual: 0, fixa: 0};
                var taxaPercentual = parseFloat(taxas.percentual); // Taxa configurada para esta quantidade de parcelas
                var taxaFixa = parseFloat(taxas.fixa) || 0;
                var iDec = taxaPercentual / 100;

                // Taxa da quantidade de parcelas aplicada uma vez sobre o total; depois divide pelas parcelas
                totalValue = total * (1 + iDec) + taxaFixa;
                value = totalValue / i;
                interestFree = false;
            } else {
                value = total / i;
                totalValue = total;
                interestFree = true;
            }
            
            var label = i + 'x de ' + formatPrice(value);
            if (!interestFree) {
                label += ' (total: ' + formatPrice(totalValue) + ')';
            } else {
                label += ' sem juros';
            }
            
            option.textContent = label;
            option.dataset.value = value.toFixed(2);
            option.dataset.total = totalValue.toFixed(2);
            option.dataset.interestFree = interestFree ? '1' : '0';
        });
    }
    
    // Atualizar total, subtotal e botão baseado na parcela selecionada (cartão de crédito)
    if (currentPaymentMethod === 'credit_card' && installmentsSelect) {
        var selectedOption = installmentsSelect.querySelector('option[value="' + currentInstallments + '"]');
        var installmentValue = selectedOption ? parseFloat(selectedOption.dataset.value) : (total / currentInstallments);
        var finalTotalWithInterest = selectedOption ? parseFloat(selectedOption.dataset.total) : total;
        
        // Atualizar exibição do total e botão com valor final (com juros se aplicável)
        document.getElementById('order_total').textContent = formatPrice(finalTotalWithInterest);
        document.getElementById('submit_btn_text').textContent = '<?php echo esc_js($checkout_button_text); ?> ' + formatPrice(finalTotalWithInterest);
        
        if (currentInstallments > 1) {
            var isInterestFree = selectedOption ? (selectedOption.dataset.interestFree === '1') : true;
            var displayText = 'em ' + currentInstallments + 'x de ' + formatPrice(installmentValue);
            if (isInterestFree) {
                displayText += ' sem juros';
            }
            installmentDisplay.textContent = displayText;
            installmentDisplay.style.display = 'block';
        } else {
            installmentDisplay.style.display = 'none';
        }

    } else {
        installmentDisplay.style.display = 'none';
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Payment method selection
    document.querySelectorAll('.checkout-payment-option').forEach(function(option) {
        option.addEventListener('click', function() {
            document.querySelectorAll('.checkout-payment-option').forEach(function(o) {
                o.classList.remove('selected');
            });
            this.classList.add('selected');
            this.querySelector('input').checked = true;
            
            currentPaymentMethod = this.dataset.method;
            document.getElementById('creditCardFields').style.display = currentPaymentMethod === 'credit_card' ? 'block' : 'none';
            applyAltPaymentUI();
            updateOrderTotal();
        });
    });
    
    // is_student é controlado pelo wizard de comprovação profissional (hidden input #is_student_hidden)
    var isStudentHidden = document.getElementById('is_student_hidden');
    if (isStudentHidden) {
        isStudent = isStudentHidden.value === '1';
    }

    // Installments change
    var installmentsSelect = document.getElementById('installments');
    if (installmentsSelect) {
        installmentsSelect.addEventListener('change', function() {
            currentInstallments = parseInt(this.value);
            var result = calculateDiscounts();
            updateInstallments(result.finalTotal);
        });
    }
    
    // CPF formatting
    document.getElementById('cpf').addEventListener('input', function(e) {
        e.target.value = formatCPF(e.target.value);
    });
    
    // Phone formatting
    var telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            e.target.value = formatPhone(e.target.value);
        });
    }
    
    // Email suggestion
    document.getElementById('email').addEventListener('blur', function(e) {
        var suggestion = suggestEmailCorrection(e.target.value);
        var suggestionDiv = document.getElementById('email_suggestion');
        if (suggestion) {
            suggestionDiv.innerHTML = 'Você quis dizer <button type="button" onclick="applyEmailSuggestion(\'' + suggestion + '\')">' + suggestion + '</button>?';
            suggestionDiv.style.display = 'block';
        } else {
            suggestionDiv.style.display = 'none';
        }
    });
    
    // Card number formatting and brand detection
    document.getElementById('card_number').addEventListener('input', function(e) {
        e.target.value = formatCardNumber(e.target.value);
        var brand = getCardBrand(e.target.value);
        document.getElementById('card_brand_badge').textContent = brand;
    });
    
    // Card holder uppercase
    document.getElementById('card_holder').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });
    
    // Card expiry formatting
    document.getElementById('card_expiry').addEventListener('input', function(e) {
        e.target.value = formatExpiry(e.target.value);
    });
    
    // CVV
    document.getElementById('card_cvv').addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
    });
    
    // CEP do titular com validação via ViaCEP
    var cepInput = document.getElementById('card_cep');
    var cepStatus = document.getElementById('cep_status');
    var cepHint = document.getElementById('cep_hint');
    var cepValidated = false;
    var cepTimeout = null;
    
    window.isCepValid = function() {
        return cepValidated || currentPaymentMethod !== 'credit_card';
    };
    
    cepInput.addEventListener('input', function(e) {
        var value = e.target.value.replace(/\D/g, '').slice(0, 8);
        if (value.length > 5) {
            value = value.substring(0, 5) + '-' + value.substring(5);
        }
        e.target.value = value;
        
        // Reset validation status
        cepValidated = false;
        cepStatus.className = 'cep-status';
        cepHint.textContent = 'CEP do endereço de cobrança do cartão';
        cepHint.className = 'form-field-hint';
        
        // Clear previous timeout
        if (cepTimeout) {
            clearTimeout(cepTimeout);
        }
        
        // Validate when CEP is complete (8 digits)
        var cleanCep = value.replace(/\D/g, '');
        if (cleanCep.length === 8) {
            cepTimeout = setTimeout(function() {
                validateCep(cleanCep);
            }, 300);
        }
    });
    
    function validateCep(cep) {
        cepStatus.className = 'cep-status loading';
        cepHint.textContent = 'Validando CEP...';
        cepHint.className = 'form-field-hint';
        
        fetch('https://viacep.com.br/ws/' + cep + '/json/')
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.erro) {
                    cepValidated = false;
                    cepStatus.className = 'cep-status invalid';
                    cepHint.textContent = 'CEP não encontrado. Verifique o número.';
                    cepHint.className = 'form-field-hint cep-error';
                    hideAddressFields();
                } else {
                    cepValidated = true;
                    cepStatus.className = 'cep-status valid';
                    cepHint.textContent = data.localidade + ' - ' + data.uf;
                    cepHint.className = 'form-field-hint cep-success';
                    fillAddressFields(data);
                }
            })
            .catch(function(error) {
                // Em caso de erro de rede, permitir continuar
                cepValidated = true;
                cepStatus.className = 'cep-status';
                cepHint.textContent = 'CEP do endereço de cobrança do cartão';
                cepHint.className = 'form-field-hint';
                console.log('Erro ao validar CEP:', error);
            });
    }
    
    function fillAddressFields(data) {
        var addressFields = document.getElementById('address_fields');
        addressFields.style.display = 'block';
        
        // Preencher campos
        document.getElementById('card_street').value = data.logradouro || '';
        document.getElementById('card_neighborhood').value = data.bairro || '';
        document.getElementById('card_city').value = data.localidade || '';
        document.getElementById('card_state').value = data.uf || '';
        
        // Focar no campo número se rua foi preenchida
        if (data.logradouro) {
            document.getElementById('card_number_address').focus();
        } else {
            // Se não tem logradouro (CEP genérico), permitir edição
            document.getElementById('card_street').removeAttribute('readonly');
            document.getElementById('card_street').focus();
        }
    }
    
    function hideAddressFields() {
        var addressFields = document.getElementById('address_fields');
        addressFields.style.display = 'none';
        
        // Limpar campos
        document.getElementById('card_street').value = '';
        document.getElementById('card_number_address').value = '';
        document.getElementById('card_complement').value = '';
        document.getElementById('card_neighborhood').value = '';
        document.getElementById('card_city').value = '';
        document.getElementById('card_state').value = '';
    }
    
    // Student file upload
    var studentFileInput = document.getElementById('student_document_file');
    var removeFileBtn = document.getElementById('remove_student_file');

    if (studentFileInput) {
        studentFileInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;

            if (!file.name.toLowerCase().endsWith('.pdf')) {
                showUploadError('Apenas arquivos PDF são aceitos');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                showUploadError('O arquivo deve ter no máximo 5MB');
                return;
            }

            document.getElementById('student_upload_area').style.display = 'none';
            document.getElementById('student_upload_preview').style.display = 'flex';
            document.getElementById('student_file_name').textContent = file.name;
            document.getElementById('student_file_size').textContent = (file.size / 1024).toFixed(1) + ' KB';

            uploadStudentDocument(file);
        });
    }

    if (removeFileBtn) {
        removeFileBtn.addEventListener('click', function() {
            resetStudentUpload();
        });
    }

    // ============= Wizard de Comprovação Profissional =============
    (function() {
        var profInput = document.getElementById('professional_type_input');
        if (!profInput) return; // Curso sem comprovação - não há wizard

        var stepProf = document.getElementById('checkout_step_professional');
        var vetBlock = document.getElementById('vet_proof_block');
        var studentBlock = document.getElementById('student_proof_block');
        var isStudentHidden = document.getElementById('is_student_hidden');
        var submitBtn = document.getElementById('submit_btn');
        var diplomaIdField = document.getElementById('diploma_document_id');

        // Badge "Selecionado: ... [Trocar]"
        var badge = document.createElement('div');
        badge.id = 'prof_type_badge';
        badge.style.cssText = 'display:none;align-items:center;justify-content:space-between;gap:12px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-weight:600;';
        if (stepProf && stepProf.parentNode) {
            stepProf.parentNode.insertBefore(badge, stepProf);
        }

        var postSelection = document.getElementById('checkout_post_selection');
        var summaryColumn = document.getElementById('checkout_summary_column');

        var ICON_VET = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px;"><path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/><path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"/><circle cx="20" cy="10" r="2"/></svg>';
        var ICON_STU = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px;"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>';
        var ICON_CHECK = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px;"><polyline points="20 6 9 17 4 12"/></svg>';

        function showBadge(type) {
            var icon = type === 'veterinarian' ? ICON_VET : ICON_STU;
            var label = type === 'veterinarian' ? 'Médico Veterinário' : 'Estudante de Veterinária';
            badge.innerHTML = '<span>' + ICON_CHECK + 'Selecionado: ' + icon + label + '</span>' +
                '<button type="button" id="change_prof_type" style="background:transparent;border:1px solid #166534;color:#166534;padding:4px 12px;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;">Trocar</button>';
            badge.style.display = 'flex';
            var changeBtn = document.getElementById('change_prof_type');
            if (changeBtn) changeBtn.addEventListener('click', resetWizard);
        }

        function hideBadge() { badge.style.display = 'none'; badge.innerHTML = ''; }

        function togglePixBadge(hide) {
            var pixBadge = document.getElementById('pix_discount_badge');
            if (pixBadge) pixBadge.style.display = hide ? 'none' : '';
        }

        function resetWizard() {
            profInput.value = '';
            if (vetBlock) vetBlock.style.display = 'none';
            if (studentBlock) studentBlock.style.display = 'none';
            if (stepProf) stepProf.style.display = 'block';
            if (isStudentHidden) isStudentHidden.value = '0';
            if (postSelection) postSelection.style.display = 'none';
            if (summaryColumn) summaryColumn.style.display = 'none';
            isStudent = false;
            togglePixBadge(false);
            hideBadge();
            if (typeof updateOrderTotal === 'function') updateOrderTotal();
        }

        function applyType(type) {
            profInput.value = type;
            if (stepProf) stepProf.style.display = 'none';
            if (type === 'veterinarian') {
                if (vetBlock) vetBlock.style.display = 'block';
                if (studentBlock) studentBlock.style.display = 'none';
                if (isStudentHidden) isStudentHidden.value = '0';
                isStudent = false;
                togglePixBadge(false);
            } else if (type === 'student') {
                if (vetBlock) vetBlock.style.display = 'none';
                if (studentBlock) studentBlock.style.display = 'block';
                if (isStudentHidden) isStudentHidden.value = '1';
                isStudent = true;
                togglePixBadge(true);
            }
            if (postSelection) postSelection.style.display = '';
            if (summaryColumn) summaryColumn.style.display = '';
            showBadge(type);
            if (typeof updateOrderTotal === 'function') updateOrderTotal();
        }

        document.querySelectorAll('.prof-type-btn').forEach(function(btn) {
            btn.addEventListener('click', function() { applyType(btn.dataset.type); });
            btn.addEventListener('mouseenter', function() { btn.style.borderColor = '#7f0b0d'; });
            btn.addEventListener('mouseleave', function() { btn.style.borderColor = '#e5e7eb'; });
        });

        // Restaurar estado se POST falhou
        if (profInput.value) applyType(profInput.value);

        // Upload de diploma para veterinário
        var diplomaInput = document.getElementById('diploma_file_input');
        var diplomaStatus = document.getElementById('diploma_status');
        if (diplomaInput) {
            diplomaInput.addEventListener('change', function(e) {
                var file = e.target.files[0]; if (!file) return;
                if (!file.name.toLowerCase().endsWith('.pdf')) { diplomaStatus.innerHTML = '<span style="color:#dc2626;">Apenas PDF</span>'; return; }
                if (file.size > 5 * 1024 * 1024) { diplomaStatus.innerHTML = '<span style="color:#dc2626;">Máx 5MB</span>'; return; }
                diplomaStatus.innerHTML = 'Enviando...';
                var fd = new FormData();
                fd.append('action', 'upload_student_document');
                fd.append('nonce', '<?php echo wp_create_nonce("student_document_upload"); ?>');
                fd.append('document', file);
                fd.append('document_type', 'diploma');
                fd.append('name', document.getElementById('nome').value || 'Veterinario');
                fd.append('email', document.getElementById('email').value || 'pending@checkout.local');
                fd.append('cpf', document.getElementById('cpf').value || '');
                fetch('<?php echo admin_url("admin-ajax.php"); ?>', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(resp) {
                        if (resp.success) {
                            diplomaIdField.value = resp.data.document_id;
                            diplomaStatus.innerHTML = '<span style="color:#0d7a5f;">✓ Diploma enviado</span>';
                        } else {
                            diplomaStatus.innerHTML = '<span style="color:#dc2626;">' + (resp.data.message || 'Erro') + '</span>';
                        }
                    })
                    .catch(function() { diplomaStatus.innerHTML = '<span style="color:#dc2626;">Erro de conexão</span>'; });
            });
        }

        // Validação no submit + desabilitar botão (anti duplo-clique)
        var form = document.getElementById('checkoutForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                var type = profInput.value;
                if (!type) {
                    e.preventDefault();
                    alert('Selecione se você é Médico Veterinário ou Estudante.');
                    if (stepProf) { stepProf.style.display = 'block'; stepProf.scrollIntoView({behavior:'smooth'}); }
                    return false;
                }
                if (type === 'veterinarian') {
                    var crmvField = document.getElementById('crmv_input');
                    var crmv = crmvField ? crmvField.value.trim() : '';
                    var hasDiploma = !!diplomaIdField.value;
                    if (!crmv && !hasDiploma) {
                        e.preventDefault();
                        alert('Informe o CRMV ou anexe o diploma para continuar.');
                        return false;
                    }
                    // Validação de formato CRMV (apenas se preencheu)
                    if (crmv) {
                        // UFs válidas brasileiras
                        var ufs = 'AC|AL|AP|AM|BA|CE|DF|ES|GO|MA|MT|MS|MG|PA|PB|PR|PE|PI|RJ|RN|RS|RO|RR|SC|SP|SE|TO';
                        var crmvRegex = new RegExp('^CRMV[\\-/\\s]?(' + ufs + ')[\\-/\\s]?(\\d{1,6})$', 'i');
                        if (!crmvRegex.test(crmv.replace(/\s+/g, ' '))) {
                            e.preventDefault();
                            var errEl = document.getElementById('crmv_error');
                            if (errEl) errEl.style.display = 'block';
                            if (crmvField) { crmvField.focus(); crmvField.scrollIntoView({behavior:'smooth', block:'center'}); }
                            return false;
                        }
                    }
                }
                if (type === 'student') {
                    var sd = document.getElementById('student_document_id');
                    if (!sd || !sd.value) {
                        e.preventDefault();
                        alert('Anexe o comprovante de matrícula para continuar.');
                        return false;
                    }
                }
                // Anti duplo-clique
                if (submitBtn) {
                    setTimeout(function() {
                        submitBtn.disabled = true;
                        submitBtn.style.opacity = '0.6';
                        submitBtn.style.cursor = 'not-allowed';
                    }, 50);
                }
            });
        }
    })();

    // Form submit validation - verificar CEP antes de enviar
    var checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            // Se pagamento alternativo (link externo) está ativo, pular todas as validações de cartão
            var _cfg = window.CURSOS_ALT_CONFIG;
            var _mod = isStudent ? 'student' : 'prof';
            var _altActive = !!(_cfg && _cfg[_mod] && _cfg[_mod][currentPaymentMethod]);
            if (_altActive) return true;

            // Verificar se é pagamento com cartão e CEP não foi validado
            if (currentPaymentMethod === 'credit_card') {

                var cepInput = document.getElementById('card_cep');
                var cepValue = cepInput ? cepInput.value.replace(/\D/g, '') : '';
                
                // Verificar se CEP está preenchido
                if (cepValue.length !== 8) {
                    e.preventDefault();
                    alert('Por favor, informe o CEP do titular do cartão.');
                    cepInput.focus();
                    return false;
                }
                
                // Verificar se CEP foi validado
                if (typeof window.isCepValid === 'function' && !window.isCepValid()) {
                    e.preventDefault();
                    alert('Por favor, aguarde a validação do CEP ou verifique se o CEP está correto.');
                    cepInput.focus();
                    return false;
                }
            }
        });

        // PagBank: criptografar localmente antes do navegador serializar o formulário.
        // Número, validade e CVV são desabilitados após a criptografia para não serem enviados ao PHP.
        checkoutForm.addEventListener('submit', function(e) {
            if (e.defaultPrevented) return;

            var selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            var method = currentPaymentMethod || (selectedMethod ? selectedMethod.value : '');

            var _cfg = window.CURSOS_ALT_CONFIG;
            var _mod = isStudent ? 'student' : 'prof';
            var _altActive = !!(_cfg && _cfg[_mod] && _cfg[_mod][method]);

            if (_altActive || checkoutPaymentGateway !== 'pagseguro' || method !== 'credit_card') {
                return;
            }

            if (!pagseguroPublicKey) {
                e.preventDefault();
                alert(pagseguroPublicKeyError || 'Não foi possível obter a chave pública do PagBank. Atualize a página e tente novamente.');
                return false;
            }

            if (typeof window.PagSeguro === 'undefined' || typeof window.PagSeguro.encryptCard !== 'function') {
                e.preventDefault();
                alert('Não foi possível carregar a criptografia segura do PagBank. Atualize a página e tente novamente.');
                return false;
            }

            var numberField = document.getElementById('card_number');
            var holderField = document.getElementById('card_holder');
            var expiryField = document.getElementById('card_expiry');
            var cvvField = document.getElementById('card_cvv');
            var encryptedField = document.getElementById('pagseguro_encrypted_card');

            var expiry = expiryField ? expiryField.value.trim().split('/') : [];
            var expMonth = expiry[0] || '';
            var expYear = expiry[1] || '';
            if (expYear.length === 2) expYear = '20' + expYear;

            try {
                var encryptedResult = window.PagSeguro.encryptCard({
                    publicKey: pagseguroPublicKey,
                    holder: holderField ? holderField.value.trim() : '',
                    number: numberField ? numberField.value.replace(/\D/g, '') : '',
                    expMonth: expMonth,
                    expYear: expYear,
                    securityCode: cvvField ? cvvField.value.replace(/\D/g, '') : ''
                });

                if (!encryptedResult || encryptedResult.hasErrors || !encryptedResult.encryptedCard) {
                    e.preventDefault();
                    var detail = '';
                    if (encryptedResult && Array.isArray(encryptedResult.errors) && encryptedResult.errors.length) {
                        detail = encryptedResult.errors.map(function(err) {
                            return err.message || err.code || '';
                        }).filter(Boolean).join('\n');
                    }
                    alert('Não foi possível criptografar os dados do cartão.' + (detail ? '\n' + detail : ' Verifique os dados e tente novamente.'));
                    return false;
                }

                encryptedField.value = encryptedResult.encryptedCard;

                // Esses campos já estão encapsulados em encryptedCard e não devem chegar ao servidor.
                if (numberField) numberField.disabled = true;
                if (expiryField) expiryField.disabled = true;
                if (cvvField) cvvField.disabled = true;
            } catch (err) {
                e.preventDefault();
                console.error('PagBank card encryption error:', err);
                alert('Não foi possível criptografar os dados do cartão. Atualize a página e tente novamente.');
                return false;
            }
        });
    }
});

function applyEmailSuggestion(email) {
    document.getElementById('email').value = email;
    document.getElementById('email_suggestion').style.display = 'none';
}

function uploadStudentDocument(file) {
    var formData = new FormData();
    formData.append('action', 'upload_student_document');
    formData.append('nonce', '<?php echo wp_create_nonce("student_document_upload"); ?>');
    formData.append('document', file);
    formData.append('name', document.getElementById('nome').value);
    formData.append('email', document.getElementById('email').value);
    formData.append('cpf', document.getElementById('cpf').value);
    
    document.getElementById('student_upload_progress').style.display = 'block';
    document.getElementById('upload_status').textContent = 'Enviando comprovante...';
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo admin_url("admin-ajax.php"); ?>', true);
    
    xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
            var percent = (e.loaded / e.total) * 100;
            document.getElementById('upload_progress_bar').style.width = percent + '%';
        }
    };
    
    xhr.onload = function() {
        document.getElementById('student_upload_progress').style.display = 'none';
        
        try {
            var response = JSON.parse(xhr.responseText);
            if (response.success) {
                document.getElementById('student_upload_success').style.display = 'block';
                document.getElementById('student_document_id').value = response.data.document_id;
            } else {
                showUploadError(response.data.message || 'Erro ao enviar comprovante');
            }
        } catch (e) {
            showUploadError('Erro ao processar resposta do servidor');
        }
    };
    
    xhr.onerror = function() {
        document.getElementById('student_upload_progress').style.display = 'none';
        showUploadError('Erro de conexão. Tente novamente.');
    };
    
    xhr.send(formData);
}

function showUploadError(message) {
    var errorDiv = document.getElementById('student_upload_error');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    setTimeout(function() {
        errorDiv.style.display = 'none';
    }, 5000);
}

function resetStudentUpload() {
    document.getElementById('student_document_file').value = '';
    document.getElementById('student_upload_area').style.display = 'block';
    document.getElementById('student_upload_preview').style.display = 'none';
    document.getElementById('student_upload_success').style.display = 'none';
    document.getElementById('student_upload_error').style.display = 'none';
    document.getElementById('student_document_id').value = '';
}

// Coupon functionality
document.getElementById('apply_coupon').addEventListener('click', function() {
    var code = document.getElementById('coupon_code').value.trim().toUpperCase();
    var email = document.getElementById('email').value;
    var messageDiv = document.getElementById('coupon_message');
    var button = this;
    
    if (!code) {
        messageDiv.innerHTML = '<span class="error">Digite um código de cupom.</span>';
        return;
    }
    
    button.disabled = true;
    button.textContent = 'Verificando...';
    
    var formData = new FormData();
    formData.append('action', 'validate_coupon');
    formData.append('codigo', code);
    formData.append('subtotal', originalPrice);
    formData.append('cursos', JSON.stringify([<?php echo implode(',', array_map(function($item) { return $item['id']; }, $checkout_items)); ?>]));
    formData.append('email', email);
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        button.disabled = false;
        button.textContent = 'Aplicar';
        
        if (data.valid) {
            document.getElementById('coupon_code').style.display = 'none';
            document.getElementById('apply_coupon').style.display = 'none';
            document.getElementById('applied_coupon').style.display = 'flex';
            document.getElementById('applied_coupon_code').textContent = data.codigo;
            document.getElementById('applied_coupon_discount').textContent = ' (-' + data.desconto_formatado + ')';
            
            document.getElementById('cupom_codigo').value = data.codigo;
            document.getElementById('cupom_id').value = data.cupom_id;
            document.getElementById('cupom_desconto').value = data.desconto_calculado;
            
            couponDiscount = data.desconto_calculado;
            couponBlocksOtherDiscounts = data.bloquear_outros_descontos || false;
            document.getElementById('coupon_discount_display').textContent = '-' + data.desconto_formatado;
            document.querySelector('.coupon-discount-row').style.display = 'flex';
            updateOrderTotal();
            
            var msg = data.message;
            if (couponBlocksOtherDiscounts) {
                msg += ' <small style="display:block;color:#666;margin-top:4px;">(Este cupom não acumula com outros descontos)</small>';
            }
            messageDiv.innerHTML = '<span class="success">' + msg + '</span>';
        } else {
            messageDiv.innerHTML = '<span class="error">' + data.message + '</span>';
        }
    })
    .catch(error => {
        button.disabled = false;
        button.textContent = 'Aplicar';
        messageDiv.innerHTML = '<span class="error">Erro ao verificar cupom. Tente novamente.</span>';
    });
});

document.getElementById('remove_coupon').addEventListener('click', function() {
    document.getElementById('coupon_code').value = '';
    document.getElementById('coupon_code').style.display = 'block';
    document.getElementById('apply_coupon').style.display = 'inline-block';
    document.getElementById('applied_coupon').style.display = 'none';
    document.getElementById('cupom_codigo').value = '';
    document.getElementById('cupom_id').value = '';
    document.getElementById('cupom_desconto').value = '0';
    document.getElementById('coupon_message').innerHTML = '';
    
    couponDiscount = 0;
    couponBlocksOtherDiscounts = false;
    document.querySelector('.coupon-discount-row').style.display = 'none';
    updateOrderTotal();
});

document.getElementById('coupon_code').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('apply_coupon').click();
    }
});

// ========================================
// TURMA DROPDOWN FUNCTIONS
// ========================================

function toggleTurmaDropdown(cursoId) {
    var dropdown = document.getElementById('turma-dropdown-' + cursoId);
    var btn = dropdown.previousElementSibling.previousElementSibling;
    
    // Close all other dropdowns first
    document.querySelectorAll('.checkout-turma-dropdown.open').forEach(function(el) {
        if (el.id !== 'turma-dropdown-' + cursoId) {
            el.classList.remove('open');
        }
    });
    document.querySelectorAll('.checkout-change-turma-btn.active').forEach(function(el) {
        el.classList.remove('active');
    });
    
    // Toggle this dropdown
    if (dropdown.classList.contains('open')) {
        dropdown.classList.remove('open');
        btn.classList.remove('active');
    } else {
        dropdown.classList.add('open');
        btn.classList.add('active');
    }
}

function closeTurmaDropdown(cursoId) {
    var dropdown = document.getElementById('turma-dropdown-' + cursoId);
    var btn = dropdown.previousElementSibling.previousElementSibling;
    dropdown.classList.remove('open');
    btn.classList.remove('active');
}

function selectTurma(radio) {
    var cursoId = radio.dataset.cursoId;
    var turmaNome = radio.dataset.turmaNome;
    var turmaData = radio.dataset.turmaData;
    var turmaId = radio.value;
    
    // Update display
    var display = document.getElementById('turma-display-' + cursoId);
    display.classList.remove('checkout-order-turma-warning');
    display.querySelector('.turma-nome').textContent = turmaNome;
    
    // Update date if exists
    var dateSpan = display.querySelector('.checkout-order-turma-date');
    if (turmaData) {
        if (dateSpan) {
            dateSpan.textContent = '• ' + turmaData;
        } else {
            var newDateSpan = document.createElement('span');
            newDateSpan.className = 'checkout-order-turma-date';
            newDateSpan.textContent = '• ' + turmaData;
            display.appendChild(newDateSpan);
        }
    } else if (dateSpan) {
        dateSpan.remove();
    }
    
    // Update hidden input
    var hiddenInput = document.getElementById('turma-input-' + cursoId);
    if (hiddenInput) {
        hiddenInput.value = turmaId;
    }
    
    // Update option styles
    var dropdown = document.getElementById('turma-dropdown-' + cursoId);
    dropdown.querySelectorAll('.checkout-turma-option').forEach(function(option) {
        option.classList.remove('selected');
    });
    radio.closest('.checkout-turma-option').classList.add('selected');
    
    // Update cart session via AJAX
    updateCartTurma(cursoId, turmaId);
    
    // Close dropdown
    closeTurmaDropdown(cursoId);
}

function updateCartTurma(cursoId, turmaId) {
    var formData = new FormData();
    formData.append('action', 'update_cart_turma');
    formData.append('curso_id', cursoId);
    formData.append('turma_id', turmaId);
    formData.append('nonce', '<?php echo wp_create_nonce("cursos_ajax_nonce"); ?>');
    
    fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Erro ao atualizar turma:', data.data?.message);
        }
    })
    .catch(error => {
        console.error('Erro ao atualizar turma:', error);
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.checkout-turma-section')) {
        document.querySelectorAll('.checkout-turma-dropdown.open').forEach(function(el) {
            el.classList.remove('open');
        });
        document.querySelectorAll('.checkout-change-turma-btn.active').forEach(function(el) {
            el.classList.remove('active');
        });
    }
});

// PIX Copy Function
function copyPixCode() {
    var codeInput = document.getElementById('pix_code_input');
    var copyBtn = document.getElementById('pix_copy_btn');
    var copyText = document.getElementById('pix_copy_text');
    
    if (codeInput) {
        codeInput.select();
        codeInput.setSelectionRange(0, 99999);
        
        navigator.clipboard.writeText(codeInput.value).then(function() {
            copyBtn.classList.add('copied');
            copyText.textContent = 'Copiado!';
            
            setTimeout(function() {
                copyBtn.classList.remove('copied');
                copyText.textContent = 'Copiar';
            }, 2000);
        }).catch(function() {
            // Fallback for older browsers
            document.execCommand('copy');
            copyBtn.classList.add('copied');
            copyText.textContent = 'Copiado!';
            
            setTimeout(function() {
                copyBtn.classList.remove('copied');
                copyText.textContent = 'Copiar';
            }, 2000);
        });
    }
}

</script>

<?php get_footer(); ?>
