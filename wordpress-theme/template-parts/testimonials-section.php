<?php
/**
 * Template Part: Testimonials Section
 * Igual ao React/Lovable - Carousel de Depoimentos
 * 
 * @package CursosTheme
 */

if (!defined('ABSPATH')) {
    exit;
}

// Buscar depoimentos do post type ou usar dados estáticos
$testimonials_query = new WP_Query(array(
    'post_type' => 'depoimento',
    'posts_per_page' => 5,
    'post_status' => 'publish',
));

// Dados estáticos de fallback (iguais ao React)
$static_testimonials = array(
    array(
        'name' => 'Dra. Patrícia Lima',
        'course' => 'Odontologia Veterinária em Cães e Gatos',
        'image' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&h=200&fit=crop&crop=face',
        'text' => 'O curso transformou minha prática clínica. Agora ofereço serviços odontológicos completos na minha clínica e meus clientes notam a diferença.',
        'rating' => 5,
    ),
    array(
        'name' => 'Dr. Fernando Alves',
        'course' => 'Emergências e Cuidados Intensivos',
        'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop&crop=face',
        'text' => 'Salvei vidas graças ao que aprendi neste curso. Os protocolos são claros e práticos. Recomendo a todos os colegas.',
        'rating' => 5,
    ),
    array(
        'name' => 'Dra. Mariana Costa',
        'course' => 'Medicina Felina Avançada',
        'image' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=200&h=200&fit=crop&crop=face',
        'text' => 'Finalmente entendi as particularidades dos felinos. Meu atendimento ficou muito mais cat-friendly e os tutores percebem isso.',
        'rating' => 5,
    ),
    array(
        'name' => 'Dr. Roberto Nunes',
        'course' => 'Pós-Graduação em Clínica Médica de Pequenos Animais',
        'image' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=200&h=200&fit=crop&crop=face',
        'text' => 'A pós-graduação elevou meu nível profissional. O networking com colegas e o suporte dos professores foram excepcionais.',
        'rating' => 5,
    ),
    array(
        'name' => 'Dra. Carla Souza',
        'course' => 'Anestesiologia Veterinária Segura',
        'image' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=200&h=200&fit=crop&crop=face',
        'text' => 'Tinha muito medo de anestesia. Hoje me sinto segura e confiante para anestesiar até pacientes de risco. Curso incrível!',
        'rating' => 5,
    ),
);

// Usar dados do banco se disponíveis, senão usar estáticos
$testimonials = array();
if ($testimonials_query->have_posts()) {
    while ($testimonials_query->have_posts()) {
        $testimonials_query->the_post();
        $testimonials[] = array(
            'name' => get_the_title(),
            'course' => get_post_meta(get_the_ID(), '_depoimento_curso', true),
            'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&h=200&fit=crop&crop=face',
            'text' => get_the_content(),
            'rating' => intval(get_post_meta(get_the_ID(), '_depoimento_rating', true)) ?: 5,
        );
    }
    wp_reset_postdata();
}

if (empty($testimonials)) {
    $testimonials = $static_testimonials;
}
?>

<section class="testimonials-section py-20 bg-secondary-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <span class="inline-block px-4 py-2 bg-primary/10 text-primary rounded-full text-sm font-medium mb-4">
                Depoimentos
            </span>
            <h2 class="font-display text-3xl md:text-4xl font-bold text-foreground mb-4">
                O que nossos alunos dizem
            </h2>
            <p class="text-muted-foreground text-lg max-w-2xl mx-auto">
                Histórias reais de profissionais que transformaram suas carreiras com nossos cursos.
            </p>
        </div>

        <div class="testimonials-carousel">
            <div class="testimonials-track">
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-card">
                        <div class="quote-icon">
                            <svg class="h-8 w-8 text-primary/20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                            </svg>
                        </div>
                        <p class="testimonial-text"><?php echo esc_html($testimonial['text']); ?></p>
                        <div class="testimonial-author">
                            <img src="<?php echo esc_url($testimonial['image']); ?>" alt="<?php echo esc_attr($testimonial['name']); ?>" class="author-image">
                            <div class="author-info">
                                <h4 class="author-name"><?php echo esc_html($testimonial['name']); ?></h4>
                                <p class="author-course"><?php echo esc_html($testimonial['course']); ?></p>
                            </div>
                        </div>
                        <div class="testimonial-rating">
                            <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                                <svg class="h-5 w-5 text-yellow-500 fill-yellow-500" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="carousel-nav">
                <button class="carousel-btn carousel-prev" aria-label="Anterior">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <button class="carousel-btn carousel-next" aria-label="Próximo">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</section>

<style>
.testimonials-section {
    padding: 5rem 0;
    background-color: var(--background-secondary, #f9fafb);
}

.testimonials-section .container {
    max-width: var(--container-width, 1200px);
}

.testimonials-section .inline-block {
    display: inline-block;
    padding: 0.5rem 1rem;
    background-color: rgba(127, 11, 13, 0.1);
    color: var(--primary, #7f0b0d);
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 1rem;
}

.testimonials-section h2 {
    font-family: var(--heading-font, 'Noto Sans', sans-serif);
    font-size: 2rem;
    font-weight: 700;
    color: var(--text, #333333);
    margin-bottom: 1rem;
}

.testimonials-section .text-center > p {
    color: var(--text-secondary, #6b7280);
    font-size: 1.125rem;
    max-width: 42rem;
    margin: 0 auto;
}

.testimonials-carousel {
    position: relative;
    overflow: hidden;
}

.testimonials-track {
    display: flex;
    gap: 1.5rem;
    overflow-x: auto;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding: 1rem 0;
}

.testimonials-track::-webkit-scrollbar {
    display: none;
}

.testimonial-card {
    flex: 0 0 350px;
    background-color: var(--background, #ffffff);
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.testimonial-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
}

.quote-icon {
    margin-bottom: 1rem;
}

.quote-icon svg {
    color: rgba(127, 11, 13, 0.2);
}

.testimonial-text {
    font-size: 1rem;
    color: var(--text, #333333);
    line-height: 1.7;
    margin-bottom: 1.5rem;
    font-style: italic;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.author-image {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.author-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text, #333333);
    margin: 0;
}

.author-course {
    font-size: 0.875rem;
    color: var(--text-secondary, #6b7280);
    margin: 0;
}

.testimonial-rating {
    display: flex;
    gap: 0.25rem;
}

.testimonial-rating svg {
    color: #eab308;
    fill: #eab308;
}

.carousel-nav {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 2rem;
}

.carousel-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background-color: var(--background, #ffffff);
    border: 1px solid var(--border, #e5e7eb);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.carousel-btn:hover {
    background-color: var(--primary, #7f0b0d);
    border-color: var(--primary, #7f0b0d);
    color: #ffffff;
}

.carousel-btn:hover svg {
    stroke: #ffffff;
}

@media (max-width: 768px) {
    .testimonial-card {
        flex: 0 0 300px;
    }
    
    .testimonials-section h2 {
        font-size: 1.75rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const track = document.querySelector('.testimonials-track');
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    
    if (track && prevBtn && nextBtn) {
        const cardWidth = 366; // 350px + 16px gap
        
        prevBtn.addEventListener('click', function() {
            track.scrollBy({ left: -cardWidth, behavior: 'smooth' });
        });
        
        nextBtn.addEventListener('click', function() {
            track.scrollBy({ left: cardWidth, behavior: 'smooth' });
        });
    }
});
</script>
