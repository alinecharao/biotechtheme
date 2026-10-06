<?php
/**
 * Template Part: Hero com Vídeo de Fundo
 * 
 * Exibe um hero com vídeo de fundo (MP4 ou YouTube)
 * 
 * @package CursosTheme
 */

$video_url = get_theme_mod('cursos_hero_video_url');
$hero_height = get_theme_mod('cursos_hero_height', '500');
$hero_overlay = get_theme_mod('cursos_hero_overlay', 40);
$hero_title = get_theme_mod('cursos_hero_title', 'Transforme sua Carreira');
$hero_subtitle = get_theme_mod('cursos_hero_subtitle', 'Cursos online de qualidade para impulsionar sua carreira profissional');
$btn_primary_text = get_theme_mod('cursos_hero_btn_primary_text', 'Ver Cursos');
$btn_primary_link = get_theme_mod('cursos_hero_btn_primary_link', '/cursos');
$btn_secondary_text = get_theme_mod('cursos_hero_btn_secondary_text', 'Saiba Mais');
$btn_secondary_link = get_theme_mod('cursos_hero_btn_secondary_link', '/sobre');

// Verificar se é YouTube
$is_youtube = strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false;

// Extrair ID do YouTube
$youtube_id = '';
if ($is_youtube) {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
        $youtube_id = $matches[1];
    }
}

$fallback_image = get_theme_mod('cursos_hero_image');
?>

<section class="hero-section hero-video" style="min-height: <?php echo esc_attr($hero_height); ?>px; position: relative; overflow: hidden;">
    <!-- Video Background -->
    <div class="hero-video-container" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; overflow: hidden;">
        <?php if ($is_youtube && $youtube_id): ?>
            <!-- YouTube Background -->
            <div id="youtubePlayer" style="position: absolute; top: 50%; left: 50%; min-width: 100%; min-height: 100%; width: auto; height: auto; transform: translate(-50%, -50%);"></div>
        <?php elseif ($video_url): ?>
            <!-- MP4 Video -->
            <video autoplay muted loop playsinline style="position: absolute; top: 50%; left: 50%; min-width: 100%; min-height: 100%; width: auto; height: auto; transform: translate(-50%, -50%); object-fit: cover;">
                <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
            </video>
        <?php else: ?>
            <!-- Fallback Image/Gradient -->
            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; <?php echo $fallback_image ? 'background-image: url(' . esc_url($fallback_image) . ');' : 'background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);'; ?> background-size: cover; background-position: center;"></div>
        <?php endif; ?>
    </div>
    
    <!-- Overlay -->
    <div class="hero-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,<?php echo esc_attr($hero_overlay / 100); ?>); z-index: 1;"></div>
    
    <!-- Content -->
    <div class="container" style="position: relative; z-index: 2; height: 100%; display: flex; align-items: center; min-height: <?php echo esc_attr($hero_height); ?>px;">
        <div class="hero-content" style="max-width: 700px; color: #fff;">
            <?php if ($hero_title): ?>
                <h1 class="hero-title" style="font-size: 3rem; font-weight: 700; margin-bottom: 20px; line-height: 1.2;">
                    <?php echo esc_html($hero_title); ?>
                </h1>
            <?php endif; ?>
            
            <?php if ($hero_subtitle): ?>
                <p class="hero-subtitle" style="font-size: 1.25rem; margin-bottom: 30px; opacity: 0.9; line-height: 1.6;">
                    <?php echo esc_html($hero_subtitle); ?>
                </p>
            <?php endif; ?>
            
            <div class="hero-buttons" style="display: flex; gap: 15px; flex-wrap: wrap;">
                <?php if ($btn_primary_text): ?>
                    <a href="<?php echo esc_url(home_url($btn_primary_link)); ?>" class="btn btn-primary" style="padding: 15px 30px; font-size: 1rem; font-weight: 600; background: var(--primary); color: #fff; text-decoration: none; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
                        <?php echo esc_html($btn_primary_text); ?>
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </a>
                <?php endif; ?>
                
                <?php if ($btn_secondary_text): ?>
                    <a href="<?php echo esc_url(home_url($btn_secondary_link)); ?>" class="btn btn-outline" style="padding: 15px 30px; font-size: 1rem; font-weight: 600; background: transparent; color: #fff; text-decoration: none; border-radius: 8px; border: 2px solid rgba(255,255,255,0.5); transition: all 0.3s ease;">
                        <?php echo esc_html($btn_secondary_text); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Scroll Indicator -->
    <div class="hero-scroll-indicator" style="position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); z-index: 2; text-align: center; color: #fff; opacity: 0.7; animation: bounce 2s infinite;">
        <svg width="30" height="30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
        </svg>
    </div>
</section>

<style>
@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateX(-50%) translateY(0);
    }
    40% {
        transform: translateX(-50%) translateY(-10px);
    }
    60% {
        transform: translateX(-50%) translateY(-5px);
    }
}

.hero-video .btn-primary:hover {
    background: var(--primary-dark) !important;
    transform: translateY(-2px);
}

.hero-video .btn-outline:hover {
    background: rgba(255,255,255,0.1) !important;
    border-color: #fff !important;
}

@media (max-width: 768px) {
    .hero-video .hero-title {
        font-size: 2rem !important;
    }
    
    .hero-video .hero-subtitle {
        font-size: 1rem !important;
    }
    
    .hero-video .hero-buttons {
        flex-direction: column;
    }
    
    .hero-video .btn {
        width: 100%;
        justify-content: center;
    }
    
    .hero-scroll-indicator {
        display: none !important;
    }
}
</style>

<?php if ($is_youtube && $youtube_id): ?>
<script>
// YouTube API
var tag = document.createElement('script');
tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

var player;
function onYouTubeIframeAPIReady() {
    player = new YT.Player('youtubePlayer', {
        height: '100%',
        width: '100%',
        videoId: '<?php echo esc_js($youtube_id); ?>',
        playerVars: {
            'autoplay': 1,
            'mute': 1,
            'loop': 1,
            'controls': 0,
            'showinfo': 0,
            'modestbranding': 1,
            'playsinline': 1,
            'rel': 0,
            'playlist': '<?php echo esc_js($youtube_id); ?>'
        },
        events: {
            'onReady': function(event) {
                event.target.playVideo();
            }
        }
    });
}
</script>
<?php endif; ?>
