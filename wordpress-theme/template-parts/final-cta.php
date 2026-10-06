<?php
/**
 * Template Part: Final CTA Section
 * Igual ao React/Lovable
 * 
 * @package CursosTheme
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<section class="final-cta py-20" style="background-color: #1a1a1a;">
    <div class="container mx-auto px-4 text-center">
        <h2 class="font-display text-3xl md:text-5xl font-bold text-white mb-6">
            Sua nova carreira começa aqui
        </h2>
        <p class="text-white/70 text-xl mb-8 max-w-2xl mx-auto">
            Não deixe para depois. Cada dia que passa é uma oportunidade perdida 
            de transformar sua vida profissional.
        </p>
        <a href="<?php echo esc_url(home_url('/cursos')); ?>" class="btn btn-primary h-16 px-12 text-xl gap-2">
            Quero Transformar Minha Carreira
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </a>
        <div class="trust-elements flex flex-wrap items-center justify-center gap-6 mt-8">
            <span>✓ Acesso imediato</span>
            <span>✓ Garantia de 7 dias</span>
            <span>✓ Suporte premium</span>
            <span>✓ Certificado incluso</span>
        </div>
    </div>
</section>

<style>
.final-cta {
    padding: 5rem 0;
    background-color: #1a1a1a;
}

.final-cta .container {
    max-width: var(--container-width, 1200px);
}

.final-cta h2 {
    font-family: var(--heading-font, 'Noto Sans', sans-serif);
    font-size: 2.5rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 1.5rem;
}

.final-cta > .container > p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.25rem;
    margin-bottom: 2rem;
    max-width: 42rem;
    margin-left: auto;
    margin-right: auto;
}

.final-cta .btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    height: 4rem;
    padding: 0 3rem;
    font-size: 1.25rem;
    font-weight: 600;
    background-color: var(--primary, #7f0b0d);
    color: #ffffff;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.final-cta .btn-primary:hover {
    background-color: var(--primary-dark, #5f0809);
    transform: translateY(-2px);
    box-shadow: 0 10px 40px rgba(127, 11, 13, 0.4);
}

.trust-elements {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    margin-top: 2rem;
}

.trust-elements span {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.875rem;
}

@media (min-width: 768px) {
    .final-cta h2 {
        font-size: 3rem;
    }
}

@media (max-width: 640px) {
    .final-cta .btn-primary {
        height: 3.5rem;
        padding: 0 2rem;
        font-size: 1rem;
    }
    
    .trust-elements {
        gap: 1rem;
    }
    
    .trust-elements span {
        flex: 0 0 45%;
        text-align: center;
    }
}
</style>
