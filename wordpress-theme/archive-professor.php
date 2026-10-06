<?php
/**
 * Template: Lista de Professores
 */

get_header();

$cor_primaria = get_theme_mod('cursos_cor_primaria', '#7f0b0d');
$cor_secundaria = get_theme_mod('cursos_cor_secundaria', '#10b981');

// Query de professores
$professores = get_posts(array(
    'post_type' => 'professor',
    'posts_per_page' => -1,
    'orderby' => 'meta_value_num',
    'meta_key' => '_professor_ordem',
    'order' => 'ASC',
));

// Fallback para ordenação por título se não houver ordem definida
if (empty($professores)) {
    $professores = get_posts(array(
        'post_type' => 'professor',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));
}
?>

<style>
    .profs-section {
        padding: 80px 0;
        background: #f8fafc;
    }
    .profs-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .profs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 30px;
    }
    
    .prof-card {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    .prof-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 40px rgba(0,0,0,0.12);
    }
    
    .prof-card-image {
        position: relative;
        padding-top: 100%;
        background: linear-gradient(135deg, <?php echo esc_attr($cor_primaria); ?>22, <?php echo esc_attr($cor_secundaria); ?>22);
    }
    .prof-card-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .prof-card-image .no-photo {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 60px;
        color: <?php echo esc_attr($cor_primaria); ?>44;
    }
    
    .prof-card-content {
        padding: 25px;
    }
    .prof-card-content h3 {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 5px;
    }
    .prof-card-titulo {
        font-size: 0.9rem;
        color: <?php echo esc_attr($cor_primaria); ?>;
        font-weight: 500;
        margin-bottom: 10px;
    }
    .prof-card-crmv {
        font-size: 0.85rem;
        color: #9ca3af;
        margin-bottom: 15px;
    }
    .prof-card-bio {
        font-size: 0.95rem;
        color: #6b7280;
        line-height: 1.6;
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .prof-card-social {
        display: flex;
        gap: 10px;
    }
    .prof-card-social a {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f3f4f6;
        color: #6b7280;
        transition: all 0.2s;
    }
    .prof-card-social a:hover {
        background: <?php echo esc_attr($cor_primaria); ?>;
        color: #fff;
    }
    .prof-card-social svg {
        width: 18px;
        height: 18px;
    }
    
    .prof-card-btn {
        display: block;
        width: 100%;
        padding: 12px;
        text-align: center;
        background: #f8fafc;
        color: <?php echo esc_attr($cor_primaria); ?>;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        border-top: 1px solid #e5e7eb;
    }
    .prof-card-btn:hover {
        background: <?php echo esc_attr($cor_primaria); ?>;
        color: #fff;
    }
    
    /* Modal */
    .prof-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.7);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .prof-modal.active {
        display: flex;
    }
    .prof-modal-content {
        background: #fff;
        border-radius: 16px;
        max-width: 700px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
    }
    .prof-modal-close {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 44px;
        height: 44px;
        border: none;
        background: #fff;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        font-weight: bold;
        color: #374151;
        z-index: 100;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        transition: all 0.2s;
    }
    .prof-modal-close:hover {
        background: #f3f4f6;
        color: #111827;
        transform: scale(1.1);
    }
    .prof-modal-header {
        display: flex;
        gap: 25px;
        padding: 30px;
        background: #f8fafc;
        border-radius: 16px 16px 0 0;
    }
    .prof-modal-avatar {
        width: 120px;
        height: 120px;
        border-radius: 12px;
        object-fit: cover;
        flex-shrink: 0;
    }
    .prof-modal-info h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 5px;
    }
    .prof-modal-info .titulo {
        color: <?php echo esc_attr($cor_primaria); ?>;
        font-weight: 500;
        margin-bottom: 10px;
    }
    .prof-modal-info .meta {
        font-size: 0.9rem;
        color: #6b7280;
    }
    .prof-modal-body {
        padding: 30px;
    }
    .prof-modal-body h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 15px;
    }
    .prof-modal-body .curriculo {
        color: #4b5563;
        line-height: 1.8;
    }
    .prof-modal-body .curriculo p {
        margin-bottom: 15px;
    }
    
    @media (max-width: 768px) {
        .prof-modal-header { flex-direction: column; align-items: center; text-align: center; }
    }
</style>

<main id="main-content" class="site-main">

<!-- Hero -->
<div class="cursos-hero">
    <div class="container">
        <h1 class="cursos-hero-title">Nossos Professores</h1>
        <p class="cursos-hero-description">
            Conheça o time de especialistas que vai guiar você em sua jornada de aprendizado. 
            Profissionais com experiência real de mercado.
        </p>
    </div>
</div>

<!-- Lista de Professores -->
<section class="profs-section">
    <div class="profs-container">
        <?php if (!empty($professores)): ?>
        <div class="profs-grid">
            <?php foreach ($professores as $prof): 
                $foto = get_the_post_thumbnail_url($prof->ID, 'medium_large');
                $titulo = get_post_meta($prof->ID, '_professor_titulo', true);
                $email = get_post_meta($prof->ID, '_professor_email', true);
                $linkedin = get_post_meta($prof->ID, '_professor_linkedin', true);
                $instagram = get_post_meta($prof->ID, '_professor_instagram', true);
                $crmv = get_post_meta($prof->ID, '_professor_crmv', true);
                $experiencia = get_post_meta($prof->ID, '_professor_experiencia', true);
                $curriculo = get_post_meta($prof->ID, '_professor_curriculo_completo', true);
            ?>
            <div class="prof-card">
                <div class="prof-card-image">
                    <?php if ($foto): ?>
                        <img src="<?php echo esc_url($foto); ?>" alt="<?php echo esc_attr($prof->post_title); ?>">
                    <?php else: ?>
                        <span class="no-photo">👤</span>
                    <?php endif; ?>
                </div>
                
                <div class="prof-card-content">
                    <h3><?php echo esc_html($prof->post_title); ?></h3>
                    <?php if ($titulo): ?>
                        <div class="prof-card-titulo"><?php echo esc_html($titulo); ?></div>
                    <?php endif; ?>
                    <?php if ($crmv): ?>
                        <div class="prof-card-crmv">CRMV: <?php echo esc_html($crmv); ?></div>
                    <?php endif; ?>
                    <?php if ($experiencia): ?>
                        <p class="prof-card-bio"><?php echo esc_html($experiencia); ?></p>
                    <?php endif; ?>
                    
                    <div class="prof-card-social">
                        <?php if ($linkedin): ?>
                        <a href="<?php echo esc_url($linkedin); ?>" target="_blank" title="LinkedIn">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($instagram): ?>
                        <a href="<?php echo esc_url($instagram); ?>" target="_blank" title="Instagram">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($email): ?>
                        <a href="mailto:<?php echo esc_attr($email); ?>" title="E-mail">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($curriculo): ?>
                <a href="#" class="prof-card-btn" onclick="openProfModal(<?php echo $prof->ID; ?>); return false;">
                    Ver Currículo Completo
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; color: #6b7280;">
            <p style="font-size: 1.2rem;">Nenhum professor cadastrado ainda.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<section style="padding: 80px 0; background: <?php echo esc_attr($cor_primaria); ?>; text-align: center;">
    <div style="max-width: 800px; margin: 0 auto; padding: 0 20px;">
        <h2 style="font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 15px;">
            Pronto para aprender com os melhores?
        </h2>
        <p style="font-size: 1.1rem; color: rgba(255,255,255,0.9); margin-bottom: 30px;">
            Explore nosso catálogo de cursos e encontre o programa ideal para sua carreira.
        </p>
        <a href="<?php echo home_url('/cursos'); ?>" 
           style="display: inline-flex; align-items: center; gap: 8px; padding: 14px 32px; background: #fff; color: <?php echo esc_attr($cor_primaria); ?>; font-size: 1.1rem; font-weight: 600; text-decoration: none; border-radius: 8px; transition: all 0.2s;"
           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)';"
           onmouseout="this.style.transform=''; this.style.boxShadow='';">
            Ver Todos os Cursos
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
    </div>
</section>

<!-- Modal para currículo completo -->
<div id="profModal" class="prof-modal">
    <div class="prof-modal-content">
        <button class="prof-modal-close" onclick="closeProfModal()">×</button>
        <div class="prof-modal-header">
            <img id="modalAvatar" src="" alt="" class="prof-modal-avatar">
            <div class="prof-modal-info">
                <h2 id="modalNome"></h2>
                <div class="titulo" id="modalTitulo"></div>
                <div class="meta" id="modalMeta"></div>
            </div>
        </div>
        <div class="prof-modal-body">
            <h3>Currículo</h3>
            <div class="curriculo" id="modalCurriculo"></div>
        </div>
    </div>
</div>

<script>
// Dados dos professores para o modal
var professoresData = {
    <?php foreach ($professores as $prof): 
        $foto = get_the_post_thumbnail_url($prof->ID, 'medium_large');
        $titulo = get_post_meta($prof->ID, '_professor_titulo', true);
        $crmv = get_post_meta($prof->ID, '_professor_crmv', true);
        $curriculo = get_post_meta($prof->ID, '_professor_curriculo_completo', true);
    ?>
    <?php echo $prof->ID; ?>: {
        nome: <?php echo json_encode($prof->post_title); ?>,
        foto: <?php echo json_encode($foto ?: ''); ?>,
        titulo: <?php echo json_encode($titulo ?: ''); ?>,
        crmv: <?php echo json_encode($crmv ?: ''); ?>,
        curriculo: <?php echo json_encode($curriculo ?: ''); ?>
    },
    <?php endforeach; ?>
};

function openProfModal(id) {
    var prof = professoresData[id];
    if (!prof) return;
    
    document.getElementById('modalNome').textContent = prof.nome;
    document.getElementById('modalTitulo').textContent = prof.titulo;
    document.getElementById('modalMeta').textContent = prof.crmv ? 'CRMV: ' + prof.crmv : '';
    document.getElementById('modalAvatar').src = prof.foto || 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="%23f3f4f6" width="100" height="100"/><text x="50" y="60" font-size="40" text-anchor="middle" fill="%239ca3af">👤</text></svg>';
    document.getElementById('modalCurriculo').innerHTML = prof.curriculo.replace(/\n/g, '<br>');
    
    document.getElementById('profModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeProfModal() {
    document.getElementById('profModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Fechar ao clicar fora
document.getElementById('profModal').addEventListener('click', function(e) {
    if (e.target === this) closeProfModal();
});

// Fechar com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeProfModal();
});
</script>

</main>

<?php get_footer(); ?>
