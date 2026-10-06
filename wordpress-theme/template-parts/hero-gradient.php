<?php
/**
 * Template Part: Hero com Gradiente
 * 
 * Exibe um hero com gradiente animado de fundo
 * 
 * @package CursosTheme
 */

$hero_height = get_theme_mod('cursos_hero_height', '500');
$hero_title = get_theme_mod('cursos_hero_title', 'Transforme sua Carreira');
$hero_subtitle = get_theme_mod('cursos_hero_subtitle', 'Cursos online de qualidade para impulsionar sua carreira profissional');
$btn_primary_text = get_theme_mod('cursos_hero_btn_primary_text', 'Ver Cursos');
$btn_primary_link = get_theme_mod('cursos_hero_btn_primary_link', '/cursos');
$btn_secondary_text = get_theme_mod('cursos_hero_btn_secondary_text', 'Saiba Mais');
$btn_secondary_link = get_theme_mod('cursos_hero_btn_secondary_link', '/sobre');

$primary_color = get_theme_mod('cursos_primary_color', '#6366f1');
$secondary_color = get_theme_mod('cursos_secondary_color', '#f59e0b');
?>

<section class="hero-section hero-gradient" style="min-height: <?php echo esc_attr($hero_height); ?>px; position: relative; overflow: hidden; background: linear-gradient(-45deg, <?php echo esc_attr($primary_color); ?>, #1e3a8a, #7c3aed, <?php echo esc_attr($secondary_color); ?>); background-size: 400% 400%; animation: gradientAnimation 15s ease infinite;">
    
    <!-- Decorative Elements -->
    <div class="hero-decoration" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; overflow: hidden; pointer-events: none;">
        <div style="position: absolute; top: -50%; left: -20%; width: 60%; height: 100%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: float 8s ease-in-out infinite;"></div>
        <div style="position: absolute; bottom: -50%; right: -20%; width: 60%; height: 100%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: float 10s ease-in-out infinite reverse;"></div>
    </div>
    
    <!-- Grid Pattern -->
    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px); background-size: 50px 50px; pointer-events: none;"></div>
    
    <!-- Content -->
    <div class="container" style="position: relative; z-index: 1; height: 100%; display: flex; align-items: center; justify-content: center; min-height: <?php echo esc_attr($hero_height); ?>px; text-align: center;">
        <div class="hero-content" style="max-width: 800px; color: #fff;">
            <?php if ($hero_title): ?>
                <h1 class="hero-title" style="font-size: 3.5rem; font-weight: 800; margin-bottom: 25px; line-height: 1.1; text-shadow: 0 2px 30px rgba(0,0,0,0.3);">
                    <?php echo esc_html($hero_title); ?>
                </h1>
            <?php endif; ?>
            
            <?php if ($hero_subtitle): ?>
                <p class="hero-subtitle" style="font-size: 1.35rem; margin-bottom: 40px; opacity: 0.95; line-height: 1.7; max-width: 600px; margin-left: auto; margin-right: auto;">
                    <?php echo esc_html($hero_subtitle); ?>
                </p>
            <?php endif; ?>
            
            <div class="hero-buttons" style="display: flex; gap: 20px; flex-wrap: wrap; justify-content: center;">
                <?php if ($btn_primary_text): ?>
                    <a href="<?php echo esc_url(home_url($btn_primary_link)); ?>" class="btn btn-primary" style="padding: 18px 40px; font-size: 1.1rem; font-weight: 600; background: #fff; color: <?php echo esc_attr($primary_color); ?>; text-decoration: none; border-radius: 50px; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                        <?php echo esc_html($btn_primary_text); ?>
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                <?php endif; ?>
                
                <?php if ($btn_secondary_text): ?>
                    <a href="<?php echo esc_url(home_url($btn_secondary_link)); ?>" class="btn btn-outline" style="padding: 18px 40px; font-size: 1.1rem; font-weight: 600; background: transparent; color: #fff; text-decoration: none; border-radius: 50px; border: 2px solid rgba(255,255,255,0.6); transition: all 0.3s ease;">
                        <?php echo esc_html($btn_secondary_text); ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Stats -->
            <div class="hero-stats" style="display: flex; justify-content: center; gap: 60px; margin-top: 60px; padding-top: 40px; border-top: 1px solid rgba(255,255,255,0.2);">
                <div class="stat-item">
                    <div style="font-size: 2.5rem; font-weight: 700;">500+</div>
                    <div style="opacity: 0.8; font-size: 0.9rem;">Cursos</div>
                </div>
                <div class="stat-item">
                    <div style="font-size: 2.5rem; font-weight: 700;">50k+</div>
                    <div style="opacity: 0.8; font-size: 0.9rem;">Alunos</div>
                </div>
                <div class="stat-item">
                    <div style="font-size: 2.5rem; font-weight: 700;">98%</div>
                    <div style="opacity: 0.8; font-size: 0.9rem;">Satisfação</div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
@keyframes gradientAnimation {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-30px) rotate(5deg); }
}

.hero-gradient .btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.3) !important;
}

.hero-gradient .btn-outline:hover {
    background: rgba(255,255,255,0.15) !important;
    border-color: #fff !important;
}

@media (max-width: 768px) {
    .hero-gradient .hero-title {
        font-size: 2.2rem !important;
    }
    
    .hero-gradient .hero-subtitle {
        font-size: 1rem !important;
    }
    
    .hero-gradient .hero-buttons {
        flex-direction: column;
    }
    
    .hero-gradient .btn {
        width: 100%;
        justify-content: center;
    }
    
    .hero-gradient .hero-stats {
        flex-direction: column;
        gap: 25px;
    }
    
    .hero-gradient .stat-item {
        padding: 0;
    }
}
</style>
