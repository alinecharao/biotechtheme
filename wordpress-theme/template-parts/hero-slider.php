<?php
/**
 * Template Part: Hero Slider
 * 
 * Exibe um slider de imagens no hero
 * 
 * @package CursosTheme
 */

$hero_type = get_theme_mod('cursos_hero_type', 'slider');
$is_fullscreen = ($hero_type === 'slider_full');
$hero_height = $is_fullscreen ? '100vh' : get_theme_mod('cursos_hero_height', '500') . 'px';
$hero_overlay = get_theme_mod('cursos_hero_overlay', 40);

// Coletar slides
$slides = array();
for ($i = 1; $i <= 5; $i++) {
    $image = get_theme_mod('cursos_hero_slide_' . $i);
    if ($image) {
        $slides[] = array(
            'image' => $image,
            'title' => get_theme_mod('cursos_hero_slide_title_' . $i),
            'subtitle' => get_theme_mod('cursos_hero_slide_subtitle_' . $i),
        );
    }
}

// Fallback para slides vazios
if (empty($slides)) {
    $slides[] = array(
        'image' => '',
        'title' => get_theme_mod('cursos_hero_title', 'Transforme sua Carreira'),
        'subtitle' => get_theme_mod('cursos_hero_subtitle', 'Cursos online de qualidade'),
    );
}

$btn_primary_text = get_theme_mod('cursos_hero_btn_primary_text', 'Ver Cursos');
$btn_primary_link = get_theme_mod('cursos_hero_btn_primary_link', '/cursos');
$btn_secondary_text = get_theme_mod('cursos_hero_btn_secondary_text', 'Saiba Mais');
$btn_secondary_link = get_theme_mod('cursos_hero_btn_secondary_link', '/sobre');
?>

<section class="hero-section hero-slider" style="min-height: <?php echo esc_attr($hero_height); ?>; position: relative; overflow: hidden;">
    <!-- Slides Container -->
    <div class="hero-slides" id="heroSlides">
        <?php foreach ($slides as $index => $slide): ?>
            <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
                 style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: <?php echo $index === 0 ? '1' : '0'; ?>; transition: opacity 1s ease-in-out; <?php echo $slide['image'] ? 'background-image: url(' . esc_url($slide['image']) . ');' : 'background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);'; ?> background-size: cover; background-position: center;">
                
                <!-- Overlay -->
                <div class="hero-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,<?php echo esc_attr($hero_overlay / 100); ?>);"></div>
                
                <!-- Content -->
                <div class="container" style="position: relative; z-index: 1; height: 100%; display: flex; align-items: center; min-height: <?php echo esc_attr($hero_height); ?>;">
                    <div class="hero-content" style="max-width: 700px; color: #fff;">
                        <?php if ($slide['title']): ?>
                            <h1 class="hero-title" style="font-size: 3rem; font-weight: 700; margin-bottom: 20px; line-height: 1.2; transform: translateY(20px); opacity: 0; animation: slideIn 0.8s ease forwards 0.3s;">
                                <?php echo esc_html($slide['title']); ?>
                            </h1>
                        <?php endif; ?>
                        
                        <?php if ($slide['subtitle']): ?>
                            <p class="hero-subtitle" style="font-size: 1.25rem; margin-bottom: 30px; opacity: 0; line-height: 1.6; transform: translateY(20px); animation: slideIn 0.8s ease forwards 0.5s;">
                                <?php echo esc_html($slide['subtitle']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="hero-buttons" style="display: flex; gap: 15px; flex-wrap: wrap; transform: translateY(20px); opacity: 0; animation: slideIn 0.8s ease forwards 0.7s;">
                            <?php if ($btn_primary_text): ?>
                                <a href="<?php echo esc_url(home_url($btn_primary_link)); ?>" class="btn btn-primary" style="padding: 15px 30px; font-size: 1rem; font-weight: 600; background: var(--primary); color: #fff; text-decoration: none; border-radius: 8px;">
                                    <?php echo esc_html($btn_primary_text); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($btn_secondary_text): ?>
                                <a href="<?php echo esc_url(home_url($btn_secondary_link)); ?>" class="btn btn-outline" style="padding: 15px 30px; font-size: 1rem; font-weight: 600; background: transparent; color: #fff; text-decoration: none; border-radius: 8px; border: 2px solid rgba(255,255,255,0.5);">
                                    <?php echo esc_html($btn_secondary_text); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (count($slides) > 1): ?>
    <!-- Navigation Dots -->
    <div class="hero-dots" style="position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); z-index: 10; display: flex; gap: 10px;">
        <?php foreach ($slides as $index => $slide): ?>
            <button class="hero-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                    data-slide="<?php echo $index; ?>"
                    style="width: 12px; height: 12px; border-radius: 50%; border: 2px solid #fff; background: <?php echo $index === 0 ? '#fff' : 'transparent'; ?>; cursor: pointer; transition: all 0.3s ease;">
            </button>
        <?php endforeach; ?>
    </div>
    
    <!-- Navigation Arrows -->
    <button class="hero-arrow hero-arrow-prev" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); z-index: 10; background: rgba(255,255,255,0.2); border: none; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
        <svg width="24" height="24" fill="none" stroke="#fff" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
    </button>
    <button class="hero-arrow hero-arrow-next" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); z-index: 10; background: rgba(255,255,255,0.2); border: none; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
        <svg width="24" height="24" fill="none" stroke="#fff" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
    </button>
    <?php endif; ?>
</section>

<style>
@keyframes slideIn {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hero-arrow:hover {
    background: rgba(255,255,255,0.4) !important;
}

.hero-dot:hover {
    background: rgba(255,255,255,0.5) !important;
}

@media (max-width: 768px) {
    .hero-slider .hero-title {
        font-size: 2rem !important;
    }
    
    .hero-slider .hero-subtitle {
        font-size: 1rem !important;
    }
    
    .hero-arrow {
        display: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var slides = document.querySelectorAll('.hero-slide');
    var dots = document.querySelectorAll('.hero-dot');
    var prevBtn = document.querySelector('.hero-arrow-prev');
    var nextBtn = document.querySelector('.hero-arrow-next');
    var currentSlide = 0;
    var slideCount = slides.length;
    var autoplayInterval;
    
    function goToSlide(index) {
        slides[currentSlide].style.opacity = '0';
        slides[currentSlide].classList.remove('active');
        if (dots.length) dots[currentSlide].style.background = 'transparent';
        
        currentSlide = (index + slideCount) % slideCount;
        
        slides[currentSlide].style.opacity = '1';
        slides[currentSlide].classList.add('active');
        if (dots.length) dots[currentSlide].style.background = '#fff';
    }
    
    function nextSlide() {
        goToSlide(currentSlide + 1);
    }
    
    function prevSlide() {
        goToSlide(currentSlide - 1);
    }
    
    function startAutoplay() {
        autoplayInterval = setInterval(nextSlide, 5000);
    }
    
    function stopAutoplay() {
        clearInterval(autoplayInterval);
    }
    
    if (slideCount > 1) {
        if (nextBtn) nextBtn.addEventListener('click', function() {
            stopAutoplay();
            nextSlide();
            startAutoplay();
        });
        
        if (prevBtn) prevBtn.addEventListener('click', function() {
            stopAutoplay();
            prevSlide();
            startAutoplay();
        });
        
        dots.forEach(function(dot, index) {
            dot.addEventListener('click', function() {
                stopAutoplay();
                goToSlide(index);
                startAutoplay();
            });
        });
        
        startAutoplay();
    }
});
</script>
