<?php
/**
 * Template Part: Social Proof Bar
 * Igual ao React/Lovable
 * 
 * @package CursosTheme
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<section class="social-proof-bar py-6 bg-muted border-y border-border">
    <div class="container mx-auto px-4">
        <div class="flex flex-wrap items-center justify-center gap-8 text-sm text-muted-foreground">
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <strong class="text-foreground">50.000+</strong> alunos formados
            </span>
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-yellow-500 fill-yellow-500" viewBox="0 0 24 24">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                <strong class="text-foreground">4.9/5</strong> avaliação média
            </span>
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Certificado <strong class="text-foreground">reconhecido pelo MEC</strong>
            </span>
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <strong class="text-foreground">Garantia</strong> de 7 dias
            </span>
        </div>
    </div>
</section>

<style>
.social-proof-bar {
    background-color: var(--background-secondary, #f3f4f6);
    border-top: 1px solid var(--border, #e5e7eb);
    border-bottom: 1px solid var(--border, #e5e7eb);
}

.social-proof-bar .container {
    max-width: var(--container-width, 1200px);
}

.social-proof-bar .flex {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 2rem;
}

.social-proof-bar span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
}

.social-proof-bar strong {
    color: var(--text, #333333);
    font-weight: 600;
}

.social-proof-bar .text-green-500 {
    color: #10b981;
}

.social-proof-bar .text-yellow-500 {
    color: #eab308;
    fill: #eab308;
}

@media (max-width: 768px) {
    .social-proof-bar .flex {
        gap: 1rem;
    }
    
    .social-proof-bar span {
        flex: 0 0 45%;
        justify-content: center;
        text-align: center;
    }
}
</style>
