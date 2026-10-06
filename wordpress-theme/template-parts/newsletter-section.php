<?php
/**
 * Template Part: Newsletter Section
 * Igual ao React/Lovable
 * 
 * @package CursosTheme
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<section class="newsletter-section py-16" style="background-color: var(--primary, #7f0b0d);">
    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto text-center">
            <h2 class="font-display text-2xl md:text-3xl font-bold text-white mb-4">
                Receba conteúdos exclusivos
            </h2>
            <p class="text-white/80 text-lg mb-8">
                Cadastre-se e receba dicas, novidades e ofertas especiais diretamente no seu e-mail.
            </p>
            
            <form class="newsletter-form" id="newsletter-form">
                <div class="form-group">
                    <input 
                        type="email" 
                        name="email" 
                        placeholder="Digite seu melhor e-mail" 
                        required 
                        class="newsletter-input"
                    >
                    <button type="submit" class="newsletter-btn">
                        <span class="btn-text">Inscrever-se</span>
                        <span class="btn-loading" style="display: none;">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
                <p class="form-note">
                    Não enviamos spam. Você pode cancelar a qualquer momento.
                </p>
            </form>
        </div>
    </div>
</section>

<style>
.newsletter-section {
    padding: 4rem 0;
}

.newsletter-section .container {
    max-width: var(--container-width, 1200px);
}

.newsletter-section h2 {
    font-family: var(--heading-font, 'Noto Sans', sans-serif);
    font-size: 1.75rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 1rem;
}

.newsletter-section > .container > .max-w-2xl > p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.125rem;
    margin-bottom: 2rem;
}

.newsletter-form .form-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-width: 500px;
    margin: 0 auto;
}

.newsletter-input {
    width: 100%;
    height: 3.5rem;
    padding: 0 1.5rem;
    font-size: 1rem;
    border: 2px solid transparent;
    border-radius: 0.5rem;
    background-color: #ffffff;
    color: var(--text, #333333);
    transition: all 0.2s ease;
}

.newsletter-input:focus {
    outline: none;
    border-color: var(--secondary, #c9a227);
    box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.3);
}

.newsletter-input::placeholder {
    color: var(--text-secondary, #6b7280);
}

.newsletter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 3.5rem;
    padding: 0 2rem;
    font-size: 1rem;
    font-weight: 600;
    background-color: var(--secondary, #c9a227);
    color: #1a1a1a;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.newsletter-btn:hover {
    background-color: #b8921f;
    transform: translateY(-2px);
}

.newsletter-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.form-note {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.6);
    margin-top: 1rem;
}

.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@media (min-width: 640px) {
    .newsletter-form .form-group {
        flex-direction: row;
    }
    
    .newsletter-input {
        flex: 1;
    }
    
    .newsletter-section h2 {
        font-size: 2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('newsletter-form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = form.querySelector('.newsletter-btn');
            const btnText = btn.querySelector('.btn-text');
            const btnLoading = btn.querySelector('.btn-loading');
            const email = form.querySelector('input[name="email"]').value;
            
            // Show loading
            btnText.style.display = 'none';
            btnLoading.style.display = 'block';
            btn.disabled = true;
            
            // Simulate API call
            setTimeout(function() {
                btnText.style.display = 'block';
                btnLoading.style.display = 'none';
                btn.disabled = false;
                
                // Show success message
                const formGroup = form.querySelector('.form-group');
                formGroup.innerHTML = '<p style="color: #ffffff; font-size: 1.125rem;">✓ Inscrição realizada com sucesso!</p>';
            }, 1500);
        });
    }
});
</script>
