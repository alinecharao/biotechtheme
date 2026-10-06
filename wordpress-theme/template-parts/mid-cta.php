<?php
/**
 * Template Part: Mid-page CTA
 * Igual ao React/Lovable
 * 
 * @package CursosTheme
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<section class="mid-cta py-16" style="background-color: var(--primary, #7f0b0d);">
    <div class="container mx-auto px-4 text-center">
        <h2 class="font-display text-2xl md:text-4xl font-bold text-white mb-4">
            Ainda em dúvida? Comece pelo nosso curso gratuito!
        </h2>
        <p class="text-white/80 text-lg mb-8 max-w-2xl mx-auto">
            Experimente a qualidade EduPro sem compromisso. 
            Milhares de alunos começaram assim e hoje têm carreiras de sucesso.
        </p>
        <div class="cta-buttons flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?php echo esc_url(home_url('/cursos')); ?>" class="btn btn-secondary h-14 px-8 text-lg gap-2">
                Acessar Curso Gratuito
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
            <a href="<?php echo esc_url(home_url('/cursos')); ?>" class="btn btn-outline-light h-14 px-8 text-lg">
                Ver Todos os Cursos
            </a>
        </div>
    </div>
</section>

<style>
.mid-cta {
    padding: 4rem 0;
}

.mid-cta .container {
    max-width: var(--container-width, 1200px);
}

.mid-cta h2 {
    font-family: var(--heading-font, 'Noto Sans', sans-serif);
    font-size: 2.25rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 1rem;
}

.mid-cta p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.125rem;
    margin-bottom: 2rem;
    max-width: 42rem;
    margin-left: auto;
    margin-right: auto;
}

.mid-cta .cta-buttons {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    justify-content: center;
    align-items: center;
}

.mid-cta .btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    height: 3.5rem;
    padding: 0 2rem;
    font-size: 1.125rem;
    font-weight: 600;
    background-color: var(--secondary, #c9a227);
    color: #1a1a1a;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.mid-cta .btn-secondary:hover {
    background-color: #b8921f;
    transform: translateY(-2px);
}

.mid-cta .btn-outline-light {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 3.5rem;
    padding: 0 2rem;
    font-size: 1.125rem;
    font-weight: 600;
    background-color: transparent;
    color: #ffffff;
    border: 2px solid #ffffff;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.mid-cta .btn-outline-light:hover {
    background-color: #ffffff;
    color: var(--primary, #7f0b0d);
}

@media (min-width: 640px) {
    .mid-cta .cta-buttons {
        flex-direction: row;
    }
}

@media (min-width: 768px) {
    .mid-cta h2 {
        font-size: 2.5rem;
    }
}
</style>
