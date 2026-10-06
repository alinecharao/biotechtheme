<?php
/**
 * Template Part: Features Section
 * Igual ao React/Lovable
 * 
 * @package CursosTheme
 */

if (!defined('ABSPATH')) {
    exit;
}

$features = array(
    array(
        'icon' => 'shield',
        'title' => 'Garantia de 7 Dias',
        'description' => 'Não gostou? Devolvemos 100% do seu dinheiro nos primeiros 7 dias. Sem perguntas.',
        'highlight' => true,
    ),
    array(
        'icon' => 'trophy',
        'title' => 'Certificado Reconhecido',
        'description' => 'Certificado válido em todo o território nacional para comprovar suas habilidades.',
        'highlight' => false,
    ),
    array(
        'icon' => 'clock',
        'title' => 'Acesso Vitalício',
        'description' => 'Comprou uma vez, acessa para sempre. Incluindo todas as atualizações futuras.',
        'highlight' => true,
    ),
    array(
        'icon' => 'users',
        'title' => 'Comunidade VIP',
        'description' => 'Acesso exclusivo ao grupo de alunos para networking e troca de experiências.',
        'highlight' => false,
    ),
    array(
        'icon' => 'book',
        'title' => 'Projetos Práticos',
        'description' => 'Aprenda fazendo projetos reais que você pode usar no seu portfólio profissional.',
        'highlight' => false,
    ),
    array(
        'icon' => 'headphones',
        'title' => 'Suporte Premium',
        'description' => 'Tire suas dúvidas diretamente com os professores. Resposta garantida em 24h.',
        'highlight' => false,
    ),
);
?>

<section class="features-section py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <span class="inline-block px-4 py-2 bg-primary/10 text-primary rounded-full text-sm font-medium mb-4">
                Por que escolher a EduPro?
            </span>
            <h2 class="font-display text-3xl md:text-4xl font-bold text-foreground mb-4">
                Tudo que você precisa para ter sucesso
            </h2>
            <p class="text-muted-foreground text-lg max-w-2xl mx-auto">
                Não é só um curso online. É uma experiência completa de aprendizado 
                com suporte real e resultados garantidos.
            </p>
        </div>

        <div class="features-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($features as $feature): ?>
                <div class="feature-card p-6 rounded-xl border <?php echo $feature['highlight'] ? 'feature-highlight' : 'bg-white border-border'; ?>">
                    <div class="feature-icon mb-4">
                        <?php if ($feature['icon'] === 'shield'): ?>
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        <?php elseif ($feature['icon'] === 'trophy'): ?>
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        <?php elseif ($feature['icon'] === 'clock'): ?>
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        <?php elseif ($feature['icon'] === 'users'): ?>
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        <?php elseif ($feature['icon'] === 'book'): ?>
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        <?php elseif ($feature['icon'] === 'headphones'): ?>
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <h3 class="feature-title text-lg font-bold mb-2"><?php echo esc_html($feature['title']); ?></h3>
                    <p class="feature-description text-sm"><?php echo esc_html($feature['description']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <a href="<?php echo esc_url(home_url('/cursos')); ?>" class="btn btn-primary h-14 px-8 text-lg gap-2">
                Começar Sem Risco
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
            <p class="text-muted-foreground text-sm mt-4">
                7 dias de garantia incondicional
            </p>
        </div>
    </div>
</section>

<style>
.features-section {
    padding: 5rem 0;
    background-color: var(--background, #ffffff);
}

.features-section .container {
    max-width: var(--container-width, 1200px);
}

.features-section .inline-block {
    display: inline-block;
    padding: 0.5rem 1rem;
    background-color: rgba(127, 11, 13, 0.1);
    color: var(--primary, #7f0b0d);
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 1rem;
}

.features-section h2 {
    font-family: var(--heading-font, 'Noto Sans', sans-serif);
    font-size: 2rem;
    font-weight: 700;
    color: var(--text, #333333);
    margin-bottom: 1rem;
}

.features-section > .container > .text-center > p {
    color: var(--text-secondary, #6b7280);
    font-size: 1.125rem;
    max-width: 42rem;
    margin: 0 auto 4rem;
}

.features-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
}

.feature-card {
    padding: 1.5rem;
    border-radius: 0.75rem;
    border: 1px solid var(--border, #e5e7eb);
    background-color: var(--background, #ffffff);
    transition: all 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.feature-highlight {
    background: linear-gradient(135deg, var(--primary, #7f0b0d), var(--primary-dark, #5f0809));
    border-color: transparent;
}

.feature-highlight .feature-icon svg {
    color: var(--secondary, #c9a227);
}

.feature-highlight .feature-title {
    color: #ffffff;
}

.feature-highlight .feature-description {
    color: rgba(255, 255, 255, 0.8);
}

.feature-icon svg {
    width: 2rem;
    height: 2rem;
    color: var(--primary, #7f0b0d);
}

.feature-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text, #333333);
    margin-bottom: 0.5rem;
}

.feature-description {
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
    line-height: 1.6;
}

.features-section .btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    height: 3.5rem;
    padding: 0 2rem;
    font-size: 1.125rem;
    font-weight: 600;
    background-color: var(--primary, #7f0b0d);
    color: #ffffff;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.features-section .btn-primary:hover {
    background-color: var(--primary-dark, #5f0809);
    transform: translateY(-2px);
}

@media (min-width: 768px) {
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .features-section h2 {
        font-size: 2.5rem;
    }
}

@media (min-width: 1024px) {
    .features-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>
