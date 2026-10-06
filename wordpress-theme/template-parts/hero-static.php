<?php
/**
 * Template Part: Hero Estático
 * 
 * Exibe um hero EXATAMENTE igual ao React/Lovable HeroSection
 * Background: gradiente escuro para bordô
 * Inclui: badges, trust elements, social proof, stats bar
 * 
 * @package CursosTheme
 */

// Configurações do Customizer (com fallbacks do React)
$hero_title = get_theme_mod('cursos_hero_title', 'Invista em você e dobre seu salário em 12 meses');
$hero_subtitle = get_theme_mod('cursos_hero_subtitle', 'Cursos práticos com professores que atuam no mercado. Comece hoje e veja resultados reais na sua carreira.');
$btn_primary_text = get_theme_mod('cursos_hero_btn_primary_text', 'Quero Começar Agora');
$btn_primary_link = get_theme_mod('cursos_hero_btn_primary_link', '/cursos');
$btn_secondary_text = get_theme_mod('cursos_hero_btn_secondary_text', 'Ver Cursos Gratuitos');
$btn_secondary_link = get_theme_mod('cursos_hero_btn_secondary_link', '/cursos');

// Stats customizáveis
$stat_alunos = get_theme_mod('cursos_stat_alunos', '50.000+');
$stat_cursos = get_theme_mod('cursos_stat_cursos', '200+');
$stat_aprovacao = get_theme_mod('cursos_stat_aprovacao', '98%');
$stat_avaliacao = get_theme_mod('cursos_stat_avaliacao', '4.9★');
?>

<section class="hero-section hero-static" style="position: relative; background: linear-gradient(to bottom right, var(--dark), var(--dark), var(--primary)); overflow: hidden;">
    
    <!-- Background Pattern (igual ao React) -->
    <div style="position: absolute; inset: 0; opacity: 0.1;">
        <div style="position: absolute; top: 0; left: 0; width: 24rem; height: 24rem; background: var(--primary); border-radius: 50%; filter: blur(48px); transform: translate(-50%, -50%);"></div>
        <div style="position: absolute; bottom: 0; right: 0; width: 24rem; height: 24rem; background: var(--primary); border-radius: 50%; filter: blur(48px); transform: translate(50%, 50%);"></div>
    </div>

    <div class="container" style="position: relative; padding: 4rem 1rem; max-width: 1200px; margin: 0 auto;">
        <div style="max-width: 48rem; margin: 0 auto; text-align: center;">
            
            <!-- Badges (igual ao React) -->
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center; margin-bottom: 1.5rem;">
                <span style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border-radius: 9999px; font-size: 0.875rem; font-weight: 500;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Certificado Reconhecido
                </span>
                <span style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: rgba(127, 11, 13, 0.2); color: #fff; border-radius: 9999px; font-size: 0.875rem; font-weight: 500;">
                    🎓 +50.000 alunos formados
                </span>
            </div>
            
            <!-- Título Principal -->
            <h1 style="font-family: 'Noto Sans', sans-serif; font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 700; color: #fff; line-height: 1.1; margin-bottom: 1.5rem;">
                <?php 
                // Destacar parte do título em bordô
                $title_parts = explode('dobre seu salário', $hero_title);
                if (count($title_parts) > 1) {
                    echo esc_html($title_parts[0]);
                    echo '<span style="color: var(--primary-light);">dobre seu salário</span>';
                    echo esc_html($title_parts[1]);
                } else {
                    echo esc_html($hero_title);
                }
                ?>
            </h1>
            
            <!-- Subtítulo -->
            <p style="font-size: 1.25rem; color: rgba(255,255,255,0.8); margin-bottom: 1.5rem; max-width: 36rem; margin-left: auto; margin-right: auto; line-height: 1.6;">
                <?php echo esc_html($hero_subtitle); ?>
            </p>

            <!-- Trust Elements (igual ao React) -->
            <div style="display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; margin-bottom: 2rem; font-size: 0.875rem; color: rgba(255,255,255,0.7);">
                <span style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" fill="none" stroke="#4ade80" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                    Garantia de 7 dias
                </span>
                <span style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" fill="none" stroke="var(--primary-light)" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Acesso vitalício
                </span>
                <span style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" fill="none" stroke="#facc15" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                    </svg>
                    Acesso imediato
                </span>
            </div>
            
            <!-- Botões CTA (igual ao React) -->
            <div style="display: flex; flex-direction: column; gap: 1rem; justify-content: center; margin-bottom: 2rem;">
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center;">
                    <?php if ($btn_primary_text): ?>
                        <a href="<?php echo esc_url(home_url($btn_primary_link)); ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; font-size: 1.125rem; font-weight: 600; background: var(--primary); color: #fff; text-decoration: none; border-radius: 0.5rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3); transition: all 0.3s ease;">
                            <?php echo esc_html($btn_primary_text); ?>
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($btn_secondary_text): ?>
                        <a href="<?php echo esc_url(home_url($btn_secondary_link)); ?>" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; font-size: 1.125rem; font-weight: 600; background: transparent; color: #fff; text-decoration: none; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.3); transition: all 0.3s ease;">
                            <?php echo esc_html($btn_secondary_text); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Social Proof (igual ao React) -->
            <div style="display: flex; align-items: center; gap: 1rem; justify-content: center; margin-bottom: 2rem;">
                <div style="display: flex; margin-left: -0.75rem;">
                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=40&h=40&fit=crop&crop=face" alt="" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; border: 2px solid var(--primary); object-fit: cover; margin-left: -0.5rem;">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=40&h=40&fit=crop&crop=face" alt="" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; border: 2px solid var(--primary); object-fit: cover; margin-left: -0.5rem;">
                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=40&h=40&fit=crop&crop=face" alt="" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; border: 2px solid var(--primary); object-fit: cover; margin-left: -0.5rem;">
                    <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=40&h=40&fit=crop&crop=face" alt="" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; border: 2px solid var(--primary); object-fit: cover; margin-left: -0.5rem;">
                    <div style="width: 2.5rem; height: 2.5rem; border-radius: 50%; border: 2px solid var(--primary); background: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: #fff; margin-left: -0.5rem;">
                        +5k
                    </div>
                </div>
                <div style="text-align: left;">
                    <div style="display: flex; align-items: center; gap: 0.25rem;">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <svg width="16" height="16" fill="#facc15" viewBox="0 0 20 20">
                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                        </svg>
                        <?php endfor; ?>
                    </div>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.875rem;">4.9/5 de 2.847 avaliações</p>
                </div>
            </div>
        </div>

        <!-- Bottom Stats Bar (igual ao React) -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-top: 4rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.2);">
            <div style="text-align: center;">
                <p style="font-family: 'Noto Sans', sans-serif; font-size: 1.875rem; font-weight: 700; color: var(--primary-light);"><?php echo esc_html($stat_alunos); ?></p>
                <p style="color: rgba(255,255,255,0.6); font-size: 0.875rem; margin-top: 0.25rem;">Alunos Formados</p>
            </div>
            <div style="text-align: center;">
                <p style="font-family: 'Noto Sans', sans-serif; font-size: 1.875rem; font-weight: 700; color: var(--primary-light);"><?php echo esc_html($stat_cursos); ?></p>
                <p style="color: rgba(255,255,255,0.6); font-size: 0.875rem; margin-top: 0.25rem;">Cursos Disponíveis</p>
            </div>
            <div style="text-align: center;">
                <p style="font-family: 'Noto Sans', sans-serif; font-size: 1.875rem; font-weight: 700; color: var(--primary-light);"><?php echo esc_html($stat_aprovacao); ?></p>
                <p style="color: rgba(255,255,255,0.6); font-size: 0.875rem; margin-top: 0.25rem;">Taxa de Aprovação</p>
            </div>
            <div style="text-align: center;">
                <p style="font-family: 'Noto Sans', sans-serif; font-size: 1.875rem; font-weight: 700; color: var(--primary-light);"><?php echo esc_html($stat_avaliacao); ?></p>
                <p style="color: rgba(255,255,255,0.6); font-size: 0.875rem; margin-top: 0.25rem;">Avaliação Média</p>
            </div>
        </div>
    </div>
</section>

<style>
/* Responsivo - 4 colunas no desktop */
@media (min-width: 768px) {
    .hero-section .container > div:last-child {
        grid-template-columns: repeat(4, 1fr) !important;
    }
}

/* Hover effects */
.hero-section .btn-primary:hover {
    background: var(--primary-dark) !important;
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.4) !important;
}

.hero-section .btn-outline:hover {
    background: rgba(255,255,255,0.1) !important;
    border-color: #fff !important;
}

/* Mobile adjustments */
@media (max-width: 640px) {
    .hero-section h1 {
        font-size: 1.875rem !important;
    }
    
    .hero-section p {
        font-size: 1rem !important;
    }
    
    .hero-section .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
