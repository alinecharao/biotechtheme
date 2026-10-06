<?php
/**
 * Template Name: Carrinho
 * 
 * Página do carrinho de compras
 */

get_header();

$cart_items = cursos_get_cart_items();
$subtotal = cursos_get_cart_subtotal();
?>

<?php if (empty($cart_items)): ?>
<script>
// Sessão PHP não tem itens — sincronizar localStorage e header imediatamente
(function() {
    try { localStorage.removeItem('cursos_cart_updated'); } catch(e) {}
    try { localStorage.setItem('cursos_cart_updated', JSON.stringify({count: 0, timestamp: Date.now()})); } catch(e) {}
    try {
        document.dispatchEvent(new CustomEvent('cartUpdated', {detail: {count: 0}}));
        window.dispatchEvent(new CustomEvent('cartUpdated', {detail: {count: 0}}));
    } catch(e) {}
})();
</script>
<?php endif; ?>

<section class="cart-page" style="padding: 60px 0; min-height: 60vh;">
    <div class="container">
        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 30px;">Meu Carrinho</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="cart-empty" style="text-align: center; padding: 60px 20px; background: var(--card-bg, #f9fafb); border-radius: 12px;">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted, #9ca3af)" stroke-width="1.5" style="margin-bottom: 20px;">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <h2 style="font-size: 1.5rem; margin-bottom: 10px; color: var(--text-dark, #111827);">Seu carrinho está vazio</h2>
                <p style="color: var(--text-muted, #6b7280); margin-bottom: 30px;">Explore nossos cursos e adicione ao carrinho!</p>
                <a href="<?php echo home_url('/cursos'); ?>" class="btn btn-primary" style="padding: 12px 30px; font-size: 1rem;">
                    Ver Cursos
                </a>
            </div>
        <?php else: ?>
            <div class="cart-layout" style="display: grid; grid-template-columns: 1fr 380px; gap: 30px;">
                
                <!-- Lista de itens -->
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): 
                        $preco = floatval($item['meta']['preco']);
                        $turmas = get_post_meta($item['id'], '_curso_turmas', true);
                        $sem_turma = get_post_meta($item['id'], '_curso_sem_turma_aberta', true);
                        $tem_turmas = !empty($turmas) && is_array($turmas) && !$sem_turma;
                    ?>
                    <div class="cart-item" data-curso-id="<?php echo $item['id']; ?>" style="display: flex; gap: 20px; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 15px;">
                        <!-- Thumbnail -->
                        <div style="flex-shrink: 0;">
                            <?php if ($item['thumbnail']): ?>
                                <img src="<?php echo esc_url($item['thumbnail']); ?>" alt="<?php echo esc_attr($item['title']); ?>" 
                                     style="width: 140px; height: 90px; object-fit: cover; border-radius: 8px;">
                            <?php else: ?>
                                <div class="cart-item-placeholder">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Info -->
                        <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 5px;">
                                    <a href="<?php echo esc_url($item['permalink']); ?>" style="color: var(--text-dark, #111827); text-decoration: none;">
                                        <?php echo esc_html($item['title']); ?>
                                    </a>
                                </h3>
                                <?php if ($item['meta']['duracao']): ?>
                                <p style="font-size: 0.875rem; color: var(--text-muted, #6b7280); margin-bottom: 5px;">
                                    Duração: <?php echo esc_html($item['meta']['duracao']); ?>
                                </p>
                                <?php endif; ?>
                                
                                <?php if ($tem_turmas): ?>
                                <div class="cart-item-turma" style="margin-top: 8px;">
                                    <?php if ($item['turma_info']): ?>
                                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                            <span style="font-size: 0.875rem; color: var(--primary, #7f0b0d); font-weight: 500;">
                                                Turma: <?php echo esc_html($item['turma_info']['nome']); ?>
                                            </span>
                                            <span style="font-size: 0.75rem; color: var(--text-muted, #6b7280);">
                                                (<?php echo esc_html(cursos_format_turma_date($item['turma_info'])); ?>)
                                            </span>
                                            <button type="button" class="change-turma-btn" data-curso-id="<?php echo $item['id']; ?>" 
                                                    style="background: none; border: none; color: var(--primary, #7f0b0d); font-size: 0.75rem; cursor: pointer; text-decoration: underline;">
                                                Alterar
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span style="font-size: 0.875rem; color: var(--danger, #ef4444);">
                                            ⚠️ Nenhuma turma selecionada
                                        </span>
                                        <button type="button" class="change-turma-btn" data-curso-id="<?php echo $item['id']; ?>" 
                                                style="background: none; border: none; color: var(--primary, #7f0b0d); font-size: 0.75rem; cursor: pointer; text-decoration: underline; margin-left: 8px;">
                                            Selecionar turma
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- Modal de seleção de turma (hidden) -->
                                    <div class="turma-selector" id="turma-selector-<?php echo $item['id']; ?>" style="display: none; margin-top: 10px; padding: 12px; background: var(--card-bg, #f9fafb); border-radius: 8px;">
                                        <p style="font-size: 0.875rem; font-weight: 600; margin-bottom: 8px;">Selecione a turma:</p>
                                        <?php foreach ($turmas as $turma): ?>
                                        <label style="display: flex; align-items: flex-start; gap: 8px; padding: 8px; cursor: pointer; border-radius: 6px; margin-bottom: 4px; transition: background 0.2s;" 
                                               onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='transparent'">
                                            <input type="radio" name="turma_<?php echo $item['id']; ?>" value="<?php echo esc_attr($turma['id']); ?>" 
                                                   <?php echo ($item['turma_id'] == $turma['id']) ? 'checked' : ''; ?>
                                                   style="margin-top: 3px;">
                                            <div>
                                                <span style="font-size: 0.875rem; font-weight: 500;"><?php echo esc_html($turma['nome']); ?></span><br>
                                                <span style="font-size: 0.75rem; color: var(--text-muted, #6b7280);">
                                                    <?php echo esc_html(cursos_format_turma_date($turma)); ?>
                                                    <?php if (!empty($turma['vagas_restantes'])): ?>
                                                        · <?php echo $turma['vagas_restantes']; ?> vagas
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </label>
                                        <?php endforeach; ?>
                                        <div style="display: flex; gap: 10px; margin-top: 10px;">
                                            <button type="button" class="save-turma-btn btn btn-primary" data-curso-id="<?php echo $item['id']; ?>" 
                                                    style="padding: 6px 16px; font-size: 0.875rem;">
                                                Salvar
                                            </button>
                                            <button type="button" class="cancel-turma-btn" data-curso-id="<?php echo $item['id']; ?>" 
                                                    style="background: none; border: 1px solid var(--border, #e5e7eb); padding: 6px 16px; font-size: 0.875rem; cursor: pointer; border-radius: 6px;">
                                                Cancelar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <button class="remove-from-cart" data-curso-id="<?php echo $item['id']; ?>" 
                                    style="background: none; border: none; color: var(--danger, #ef4444); font-size: 0.875rem; cursor: pointer; padding: 5px 0; text-align: left; width: fit-content; margin-top: 8px;">
                                Remover
                            </button>
                        </div>
                        
                        <!-- Preço -->
                        <div style="flex-shrink: 0; text-align: right;">
                            <span style="font-size: 1.25rem; font-weight: 700; color: var(--primary, #6366f1);">
                                <?php echo cursos_format_price($preco); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Resumo -->
                <div class="cart-summary" style="position: sticky; top: 100px; height: fit-content;">
                    <div style="background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 25px;">
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 20px;">Resumo do Pedido</h3>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: var(--text-muted, #6b7280);">
                            <span id="summaryCount"><?php echo count($cart_items); ?> curso(s)</span>
                            <span id="summarySubtotal"><?php echo cursos_format_price($subtotal); ?></span>
                        </div>
                        
                        <hr style="margin: 15px 0; border: none; border-top: 1px solid var(--border, #e5e7eb);">
                        
                        <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700; margin-bottom: 20px;">
                            <span>Total</span>
                            <span id="summaryTotal" style="color: var(--primary, #6366f1);"><?php echo cursos_format_price($subtotal); ?></span>
                        </div>
                        
                        <a href="<?php echo home_url('/checkout'); ?>" class="btn btn-primary" 
                           style="display: block; width: 100%; text-align: center; padding: 14px; font-size: 1rem; font-weight: 600;">
                            Finalizar Compra
                        </a>
                        
                        <a href="<?php echo home_url('/cursos'); ?>" 
                           style="display: block; text-align: center; margin-top: 15px; color: var(--text-muted, #6b7280); font-size: 0.875rem; text-decoration: none;">
                            Continuar Comprando
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
@media (max-width: 768px) {
    .cart-layout {
        grid-template-columns: 1fr !important;
    }
    .cart-item {
        flex-direction: column !important;
    }
    .cart-item img {
        width: 100% !important;
        height: 160px !important;
    }
    .cart-summary {
        position: static !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Função para atualizar o resumo do carrinho
    function updateCartSummary(cartCount, subtotalFormatted) {
        const summaryCount = document.getElementById('summaryCount');
        const summarySubtotal = document.getElementById('summarySubtotal');
        const summaryTotal = document.getElementById('summaryTotal');
        
        if (summaryCount) {
            summaryCount.textContent = cartCount + ' curso(s)';
        }
        if (summarySubtotal) {
            summarySubtotal.textContent = subtotalFormatted;
        }
        if (summaryTotal) {
            summaryTotal.textContent = subtotalFormatted;
        }
        
        // Disparar evento para sincronizar header e outras abas
        document.dispatchEvent(new CustomEvent('cartUpdated', {
            detail: { count: cartCount }
        }));
        
        // Sincronizar via localStorage para outras abas
        localStorage.setItem('cursos_cart_updated', JSON.stringify({
            count: cartCount,
            timestamp: Date.now()
        }));
    }
    
    // Função para mostrar estado vazio do carrinho
    function showEmptyCart() {
        const cartLayout = document.querySelector('.cart-layout');
        if (cartLayout) {
            cartLayout.innerHTML = `
                <div class="cart-empty" style="text-align: center; padding: 60px 20px; background: var(--card-bg, #f9fafb); border-radius: 12px; grid-column: 1 / -1;">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted, #9ca3af)" stroke-width="1.5" style="margin-bottom: 20px;">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <h2 style="font-size: 1.5rem; margin-bottom: 10px; color: var(--text-dark, #111827);">Seu carrinho está vazio</h2>
                    <p style="color: var(--text-muted, #6b7280); margin-bottom: 30px;">Explore nossos cursos e adicione ao carrinho!</p>
                    <a href="<?php echo home_url('/cursos'); ?>" class="btn btn-primary" style="padding: 12px 30px; font-size: 1rem;">
                        Ver Cursos
                    </a>
                </div>
            `;
        }
    }
    
    // Remover do carrinho
    document.querySelectorAll('.remove-from-cart').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const cursoId = this.dataset.cursoId;
            const item = this.closest('.cart-item');
            
            // Desabilitar botão durante a requisição
            this.disabled = true;
            this.textContent = 'Removendo...';
            
            fetch(cursosAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=remove_from_cart&curso_id=' + cursoId + '&nonce=' + cursosAjax.nonce
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animar remoção do item
                    item.style.transition = 'opacity 0.3s, transform 0.3s';
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-20px)';
                    
                    setTimeout(() => {
                        item.remove();
                        
                        // Atualizar resumo com novos valores
                        updateCartSummary(data.data.cart_count, data.data.subtotal_formatted);
                        
                        // Mostrar estado vazio se não há mais itens
                        if (data.data.cart_count === 0) {
                            showEmptyCart();
                        }
                    }, 300);
                } else {
                    this.disabled = false;
                    this.textContent = 'Remover';
                    alert(data.data?.message || 'Erro ao remover item');
                }
            })
            .catch(error => {
                this.disabled = false;
                this.textContent = 'Remover';
                alert('Erro de conexão');
            });
        });
    });
    
    // Abrir seletor de turma
    document.querySelectorAll('.change-turma-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const cursoId = this.dataset.cursoId;
            const selector = document.getElementById('turma-selector-' + cursoId);
            if (selector) {
                selector.style.display = selector.style.display === 'none' ? 'block' : 'none';
            }
        });
    });
    
    // Cancelar seleção de turma
    document.querySelectorAll('.cancel-turma-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const cursoId = this.dataset.cursoId;
            const selector = document.getElementById('turma-selector-' + cursoId);
            if (selector) {
                selector.style.display = 'none';
            }
        });
    });
    
    // Salvar turma selecionada
    document.querySelectorAll('.save-turma-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const cursoId = this.dataset.cursoId;
            const selector = document.getElementById('turma-selector-' + cursoId);
            const selectedTurma = selector.querySelector('input[name="turma_' + cursoId + '"]:checked');
            const item = this.closest('.cart-item');
            
            if (!selectedTurma) {
                alert('Por favor, selecione uma turma');
                return;
            }
            
            const saveBtn = this;
            saveBtn.disabled = true;
            saveBtn.textContent = 'Salvando...';
            
            fetch(cursosAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=update_cart_turma&curso_id=' + cursoId + '&turma_id=' + selectedTurma.value + '&nonce=' + cursosAjax.nonce
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fechar modal
                    selector.style.display = 'none';
                    
                    // Atualizar a exibição da turma no item
                    const turmaDisplay = item.querySelector('.cart-item-turma');
                    if (turmaDisplay) {
                        // Encontrar o container de exibição da turma (não o modal)
                        const displayContainer = turmaDisplay.querySelector('div:first-child') || turmaDisplay;
                        
                        // Se é o container com a turma atual
                        if (displayContainer && !displayContainer.classList.contains('turma-selector')) {
                            displayContainer.innerHTML = `
                                <span style="font-size: 0.875rem; color: var(--primary, #7f0b0d); font-weight: 500;">
                                    Turma: ${data.data.turma_nome}
                                </span>
                                <span style="font-size: 0.75rem; color: var(--text-muted, #6b7280);">
                                    (${data.data.turma_data})
                                </span>
                                <button type="button" class="change-turma-btn" data-curso-id="${cursoId}" 
                                        style="background: none; border: none; color: var(--primary, #7f0b0d); font-size: 0.75rem; cursor: pointer; text-decoration: underline;">
                                    Alterar
                                </button>
                            `;
                            
                            // Re-anexar evento ao novo botão
                            const newBtn = displayContainer.querySelector('.change-turma-btn');
                            if (newBtn) {
                                newBtn.addEventListener('click', function() {
                                    selector.style.display = selector.style.display === 'none' ? 'block' : 'none';
                                });
                            }
                        }
                    }
                    
                    // Mostrar feedback visual
                    item.style.transition = 'box-shadow 0.3s';
                    item.style.boxShadow = '0 0 0 2px var(--primary, #7f0b0d)';
                    setTimeout(() => {
                        item.style.boxShadow = '0 2px 8px rgba(0,0,0,0.06)';
                    }, 1000);
                    
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Salvar';
                } else {
                    alert(data.data?.message || 'Erro ao atualizar turma');
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Salvar';
                }
            })
            .catch(error => {
                alert('Erro de conexão');
                saveBtn.disabled = false;
                saveBtn.textContent = 'Salvar';
            });
        });
    });
    
    // Escutar mudanças de outras abas via localStorage
    window.addEventListener('storage', function(e) {
        if (e.key === 'cursos_cart_updated') {
            try {
                const data = JSON.parse(e.newValue);
                if (data && typeof data.count !== 'undefined') {
                    // Recarregar página para obter dados atualizados
                    location.reload();
                }
            } catch (err) {}
        }
    });
});
</script>

<?php get_footer(); ?>
