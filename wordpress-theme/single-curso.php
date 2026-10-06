<?php
/**
 * Template para página individual do curso
 * Layout replicado EXATAMENTE do Lovable React
 * 
 * @package CursosTheme
 */

get_header();

$meta = cursos_get_curso_meta(get_the_ID());
$categorias = get_the_terms(get_the_ID(), 'categoria_curso');
$tipos = get_the_terms(get_the_ID(), 'tipo_curso');
?>

<!-- Hero Section - Gradient igual ao React -->
<section class="curso-hero">
    <div class="container">
        <!-- Badges -->
        <div class="curso-hero-badges">
            <?php if ($categorias && !is_wp_error($categorias)): ?>
                <span class="badge badge-secondary">
                    <?php echo esc_html($categorias[0]->name); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($tipos && !is_wp_error($tipos)): ?>
                <span class="badge badge-outline">
                    <?php echo esc_html($tipos[0]->name); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <!-- Title -->
        <h1 class="curso-hero-title"><?php the_title(); ?></h1>
        
        <!-- Description -->
        <p class="curso-hero-description"><?php echo get_the_excerpt(); ?></p>
        
        <!-- Meta Info -->
        <div class="curso-hero-meta">
            <?php if (!empty($meta['duracao'])): ?>
            <div class="curso-hero-meta-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span><?php echo esc_html($meta['duracao']); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($meta['aulas'])): ?>
            <div class="curso-hero-meta-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polygon points="10 8 16 12 10 16 10 8"></polygon>
                </svg>
                <span><?php echo $meta['aulas']; ?> aulas</span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($meta['alunos'])): ?>
            <div class="curso-hero-meta-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span><?php echo number_format($meta['alunos'], 0, ',', '.'); ?> alunos</span>
            </div>
            <?php endif; ?>
            
            
            <?php 
            $publico_alvo = get_post_meta(get_the_ID(), '_curso_publico_alvo', true);
            if (!empty($publico_alvo)): 
            ?>
            <div class="curso-hero-meta-item curso-publico-alvo">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <circle cx="12" cy="12" r="6"></circle>
                    <circle cx="12" cy="12" r="2"></circle>
                </svg>
                <span><?php echo esc_html($publico_alvo); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Main Content Section -->
<section class="curso-content-section">
    <div class="container">
        <div class="curso-grid">
            <!-- Main Content Column -->
            <div class="curso-main">
                <!-- Course Image -->
                <div class="curso-image-wrapper">
                    <?php if (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('curso-featured', ['class' => 'curso-image']); ?>
                    <?php else: ?>
                        <div class="curso-image-placeholder">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                                <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <?php 
                $content = get_the_content();
                if (!empty(trim(strip_tags($content)))): 
                ?>
                <div class="curso-section">
                    <h2 class="curso-section-title">Sobre o Curso</h2>
                    <div class="curso-description">
                        <?php the_content(); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- What you'll learn -->
                <?php 
                $aprenda = get_post_meta(get_the_ID(), '_curso_what_youll_learn', true);
                if (!empty($aprenda) && is_array($aprenda)): 
                ?>
                <div class="curso-section">
                    <h2 class="curso-section-title">O que você vai aprender</h2>
                    <div class="curso-learn-grid">
                        <?php foreach ($aprenda as $item): ?>
                        <div class="curso-learn-item">
                            <svg class="icon-primary" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span><?php echo esc_html(is_array($item) ? $item['texto'] : $item); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Curriculum -->
                <?php 
                $curriculum = $meta['curriculum'];
                // Filtra módulos válidos (com título preenchido)
                $valid_modules = array();
                if (!empty($curriculum) && is_array($curriculum)) {
                    foreach ($curriculum as $mod) {
                        $mod_title = isset($mod['title']) ? $mod['title'] : (isset($mod['titulo']) ? $mod['titulo'] : '');
                        if (!empty(trim($mod_title))) {
                            $valid_modules[] = $mod;
                        }
                    }
                }
                
                if (!empty($valid_modules)): 
                ?>
                <div class="curso-section">
                    <h2 class="curso-section-title">Conteúdo Programático</h2>
                    <div class="curso-accordion">
                        <?php 
                        $module_number = 1;
                        foreach ($valid_modules as $modulo): 
                            // Suporta ambos os formatos: title/lessons (do admin) ou titulo/aulas (legado)
                            $titulo = isset($modulo['title']) ? $modulo['title'] : (isset($modulo['titulo']) ? $modulo['titulo'] : '');
                            $aulas = isset($modulo['lessons']) ? $modulo['lessons'] : (isset($modulo['aulas']) ? $modulo['aulas'] : array());
                            // Filtra aulas vazias
                            if (is_array($aulas)) {
                                $aulas = array_filter($aulas, function($a) {
                                    return !empty(trim($a));
                                });
                            }
                        ?>
                        <div class="accordion-item">
                            <button type="button" class="accordion-trigger" onclick="toggleAccordion(this)">
                                <div class="accordion-header">
                                    <span class="accordion-number"><?php echo $module_number; ?></span>
                                    <span class="accordion-title"><?php echo esc_html($titulo); ?></span>
                                </div>
                                <svg class="accordion-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                            <div class="accordion-content">
                                <?php if (!empty($aulas)): ?>
                                <ul class="accordion-lessons">
                                    <?php foreach ($aulas as $aula): ?>
                                    <li class="accordion-lesson">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polygon points="10 8 16 12 10 16 10 8"></polygon>
                                        </svg>
                                        <span><?php echo esc_html($aula); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php else: ?>
                                <p class="accordion-empty">Conteúdo em breve</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php 
                        $module_number++;
                        endforeach; 
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Instructors (Suporte a múltiplos professores) -->
                <?php 
                $professores = cursos_get_curso_professores(get_the_ID());
                if (!empty($professores)): 
                ?>
                <div class="curso-section">
                    <h2 class="curso-section-title">
                        <?php echo count($professores) > 1 ? 'Professores' : 'Professor'; ?>
                    </h2>
                    
                    <div class="curso-instructors" style="display: flex; flex-direction: column; gap: 24px;">
                        <?php foreach ($professores as $professor): ?>
                        <div class="curso-instructor">
                            <div class="instructor-avatar">
                                <?php if (!empty($professor['foto'])): ?>
                                    <img src="<?php echo esc_url($professor['foto']); ?>" 
                                         alt="<?php echo esc_attr($professor['nome']); ?>" 
                                         class="instructor-photo">
                                <?php else: ?>
                                    <div class="instructor-photo-placeholder">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="instructor-info">
                                <h3 class="instructor-name"><?php echo esc_html($professor['nome']); ?></h3>
                                <?php if (!empty($professor['titulo'])): ?>
                                    <p class="instructor-title"><?php echo esc_html($professor['titulo']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($professor['bio'])): ?>
                                <p class="instructor-bio">
                                    <?php echo wp_kses_post($professor['bio']); ?>
                                </p>
                                <?php endif; ?>
                                <?php 
                                $especialidades = !empty($professor['especialidades']) && is_array($professor['especialidades']) ? $professor['especialidades'] : array();
                                if (!empty($especialidades)): 
                                ?>
                                <div class="instructor-specialties">
                                    <?php foreach ($especialidades as $especialidade): ?>
                                        <span class="badge badge-secondary"><?php echo esc_html($especialidade); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($professor['id'])): ?>
                                <a href="<?php echo get_permalink($professor['id']); ?>" class="instructor-curriculo-btn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                    Ver Currículo Completo
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- FAQs -->
                <?php 
                $faqs = get_post_meta(get_the_ID(), '_curso_faqs', true);
                if (!empty($faqs) && is_array($faqs)): 
                ?>
                <div class="curso-section">
                    <h2 class="curso-section-title">Perguntas Frequentes</h2>
                    <div class="curso-accordion">
                        <?php foreach ($faqs as $index => $faq): 
                            // Suporta ambos os formatos: question/answer (do admin) ou pergunta/resposta (legado)
                            $pergunta = isset($faq['question']) ? $faq['question'] : (isset($faq['pergunta']) ? $faq['pergunta'] : '');
                            $resposta = isset($faq['answer']) ? $faq['answer'] : (isset($faq['resposta']) ? $faq['resposta'] : '');
                        ?>
                        <div class="accordion-faq">
                            <button type="button" class="accordion-trigger" onclick="toggleAccordion(this)">
                                <span class="accordion-title"><?php echo esc_html($pergunta); ?></span>
                                <svg class="accordion-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                            <div class="accordion-content">
                                <p class="faq-answer"><?php echo nl2br(esc_html($resposta)); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="curso-sidebar">
                <div class="curso-card">
                    <?php 
                    // Verificar vagas globais do curso
                    $vagas_curso = cursos_check_curso_vagas(get_the_ID());
                    $curso_esgotado = $vagas_curso['tem_limite'] && !$vagas_curso['disponivel'];
                    ?>
                    
                    <!-- Badge de Vagas Globais do Curso -->
                    <?php if ($vagas_curso['tem_limite']): ?>
                    <div class="curso-vagas-info <?php echo $curso_esgotado ? 'vagas-esgotadas' : ($vagas_curso['vagas_restantes'] <= 5 ? 'vagas-ultimas' : 'vagas-disponiveis'); ?>">
                        <?php if ($curso_esgotado): ?>
                            <span class="vagas-icon">🚫</span>
                            <span class="vagas-texto">Vagas esgotadas</span>
                        <?php elseif ($vagas_curso['vagas_restantes'] <= 5): ?>
                            <span class="vagas-icon">🔥</span>
                            <span class="vagas-numero"><?php echo $vagas_curso['vagas_restantes']; ?></span>
                            <span class="vagas-texto">vaga<?php echo $vagas_curso['vagas_restantes'] > 1 ? 's' : ''; ?> restante<?php echo $vagas_curso['vagas_restantes'] > 1 ? 's' : ''; ?>!</span>
                        <?php else: ?>
                            <span class="vagas-icon">✅</span>
                            <span class="vagas-numero"><?php echo $vagas_curso['vagas_restantes']; ?></span>
                            <span class="vagas-texto">vagas disponíveis</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Carregar turmas ANTES de usar no preço e nos botões
                    $turmas = get_post_meta(get_the_ID(), '_curso_turmas', true);
                    $sem_turma = get_post_meta(get_the_ID(), '_curso_sem_turma_aberta', true);
                    ?>

                    <!-- Pricing (oculto se sem turma aberta ou esgotado) -->
                    <?php if (!$sem_turma && !$curso_esgotado): ?>
                    <div class="curso-card-pricing">
                        <?php if (!empty($meta['preco_original']) && $meta['preco_original'] > $meta['preco']): ?>
                            <span class="price-original"><?php echo cursos_format_price($meta['preco_original']); ?></span>
                        <?php endif; ?>
                        <div class="price-row">
                            <span class="price-current"><?php echo cursos_format_price($meta['preco']); ?></span>
                            <?php if (!empty($meta['preco_original']) && $meta['preco_original'] > $meta['preco']): 
                                $desconto = round((($meta['preco_original'] - $meta['preco']) / $meta['preco_original']) * 100);
                            ?>
                            <span class="price-discount-badge"><?php echo $desconto; ?>% OFF</span>
                            <?php endif; ?>
                        </div>
                        <p class="price-installments">ou <?php echo cursos_get_installment_display_text(floatval($meta['preco'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php
                    $tem_turmas = !empty($turmas) && is_array($turmas) && !$sem_turma;
                    
                    // Desabilitar botões se: sem turma aberta OU se tem turmas (precisa selecionar uma) OU curso esgotado
                    $deve_desabilitar = $sem_turma || $tem_turmas || $curso_esgotado;
                    $disabled_attr = $deve_desabilitar ? 'disabled' : '';
                    $disabled_class = $deve_desabilitar ? 'btn-disabled' : '';
                    ?>

                    <!-- Aviso de Curso Esgotado -->
                    <?php if ($curso_esgotado): 
                        $waitlist_url = get_post_meta(get_the_ID(), '_curso_waitlist_url', true);
                    ?>
                    <div class="curso-card-esgotado">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                        <div>
                            <strong>Vagas esgotadas</strong>
                            <?php if ($waitlist_url): ?>
                            <p>Cadastre-se para ser avisado quando abrirem novas turmas.</p>
                            <a href="<?php echo esc_url($waitlist_url); ?>" target="_blank" rel="noopener" class="btn-waitlist-external btn-waitlist-small">
                                Entrar na Lista de Espera
                            </a>
                            <?php else: ?>
                            <p>Entre em contato para mais informações.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Aviso de Sem Turma (aparece ANTES dos botões) -->
                    <?php if ($sem_turma && !$curso_esgotado): 
                        $waitlist_url = get_post_meta(get_the_ID(), '_curso_waitlist_url', true);
                    ?>
                    <div class="curso-card-no-turma">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <div>
                            <strong>Sem turma aberta no momento</strong>
                            <?php if ($waitlist_url): ?>
                            <p>Cadastre-se para ser avisado sobre novas turmas.</p>
                            <a href="<?php echo esc_url($waitlist_url); ?>" target="_blank" rel="noopener" class="btn-waitlist-external btn-waitlist-small">
                                Entrar na Lista de Espera
                            </a>
                            <?php else: ?>
                            <p>Entre em contato para mais informações.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Buttons (ocultos quando sem turma ou curso esgotado) -->
                    <?php if (!$sem_turma && !$curso_esgotado): ?>
                    <div class="curso-card-actions">
                        <button type="button" class="btn btn-primary btn-lg btn-block curso-add-cart <?php echo $disabled_class; ?>" onclick="addToCart(<?php echo get_the_ID(); ?>)" <?php echo $disabled_attr; ?>>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            Adicionar ao Carrinho
                        </button>

                        <form action="<?php echo home_url('/checkout'); ?>" method="get" id="checkout-form">
                            <input type="hidden" name="curso_id" value="<?php echo get_the_ID(); ?>">
                            <input type="hidden" name="turma_id" id="turma_id_checkout" value="">
                            <button type="submit" class="btn btn-buy-now btn-lg btn-block <?php echo $disabled_class; ?>" <?php echo $disabled_attr; ?>>
                                Comprar Agora
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- Turmas Selection -->
                    <?php
                    if (!empty($turmas) && is_array($turmas) && !$sem_turma):
                        // Verificar se há pelo menos uma turma disponível
                        $tem_turma_disponivel = false;
                        foreach ($turmas as $turma) {
                            $vagas_check = cursos_check_turma_vagas(get_the_ID(), $turma['id']);
                            if ($vagas_check['disponivel']) {
                                $tem_turma_disponivel = true;
                                break;
                            }
                        }
                    ?>
                    <div class="curso-card-turmas">
                        <label class="turmas-label">Selecione a turma:</label>
                        <div class="turmas-list">
                            <?php foreach ($turmas as $turma): 
                                $vagas_check = cursos_check_turma_vagas(get_the_ID(), $turma['id']);
                                $esgotada = !$vagas_check['disponivel'];
                                $vagas_restantes = $vagas_check['vagas_restantes'];
                            ?>
                            <label class="turma-option <?php echo $esgotada ? 'turma-esgotada' : ''; ?>">
                                <input type="radio" name="turma_id" value="<?php echo esc_attr($turma['id']); ?>" <?php echo $esgotada ? 'disabled' : ''; ?>>
                                <span class="turma-radio"></span>
                                <div class="turma-info">
                                    <div class="turma-nome-row">
                                        <span class="turma-nome"><?php echo esc_html($turma['nome']); ?></span>
                                        <?php if ($esgotada): ?>
                                            <span class="badge-esgotada">Esgotado</span>
                                        <?php elseif ($vagas_restantes !== null && $vagas_restantes <= 5): ?>
                                            <span class="badge-ultimas-vagas">Últimas <?php echo $vagas_restantes; ?> vagas!</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="turma-data">
                                        <?php echo esc_html(cursos_format_turma_date($turma)); ?>
                                        <?php if ($vagas_restantes !== null && !$esgotada): ?>
                                            · <?php echo $vagas_restantes; ?> vagas
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($tem_turma_disponivel): ?>
                        <p class="turmas-notice">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            Selecione uma turma para continuar
                        </p>
                        <?php else: 
                            $waitlist_url = get_post_meta(get_the_ID(), '_curso_waitlist_url', true);
                            if ($waitlist_url): ?>
                            <a href="<?php echo esc_url($waitlist_url); ?>" target="_blank" rel="noopener" class="btn-waitlist-external">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <line x1="20" y1="8" x2="20" y2="14"></line>
                                    <line x1="23" y1="11" x2="17" y2="11"></line>
                                </svg>
                                Entrar na Lista de Espera
                            </a>
                            <?php else: ?>
                            <p class="turmas-notice turmas-notice-esgotado">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                Todas as turmas estão esgotadas.
                            </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Course Includes -->
                    <div class="curso-card-includes">
                        <h4>Este curso inclui:</h4>
                        <ul class="includes-list">
                            <?php 
                            $curso_inclui = get_post_meta(get_the_ID(), '_curso_includes', true);
                            if (empty($curso_inclui)) {
                                $curso_inclui = get_post_meta(get_the_ID(), '_curso_inclui', true);
                            }
                            
                            $icon_svgs = array(
                                'PlayCircle' => '<circle cx="12" cy="12" r="10"></circle><polygon points="10 8 16 12 10 16 10 8"></polygon>',
                                'Clock' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
                                'Award' => '<circle cx="12" cy="8" r="6"></circle><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>',
                                'CheckCircle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
                                'Download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>',
                                'MessageCircle' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>',
                                'Users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
                                'Smartphone' => '<rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line>',
                            );
                            
                            if (!empty($curso_inclui) && is_array($curso_inclui)):
                                foreach ($curso_inclui as $item):
                                    $icone = $item['icone'] ?? $item['icon'] ?? 'CheckCircle';
                                    $texto = $item['texto'] ?? $item['text'] ?? '';
                                    $svg_content = $icon_svgs[$icone] ?? $icon_svgs['CheckCircle'];
                            ?>
                            <li>
                                <svg class="icon-primary" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <?php echo $svg_content; ?>
                                </svg>
                                <span><?php echo esc_html($texto); ?></span>
                            </li>
                            <?php 
                                endforeach;
                            else:
                            ?>
                            <li>
                                <svg class="icon-primary" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                </svg>
                                <span>Hospedagem</span>
                            </li>
                            <li>
                                <svg class="icon-primary" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                                    <line x1="6" y1="1" x2="6" y2="4"></line>
                                    <line x1="10" y1="1" x2="10" y2="4"></line>
                                    <line x1="14" y1="1" x2="14" y2="4"></line>
                                </svg>
                                <span>Alimentação*</span>
                            </li>
                            <li>
                                <svg class="icon-primary" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                </svg>
                                <span>Material didático</span>
                            </li>
                            <li>
                                <svg class="icon-primary" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="8" r="6"></circle>
                                    <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>
                                </svg>
                                <span>Certificado</span>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php
                    $curso_presencial = get_post_meta(get_the_ID(), '_curso_presencial', true) === '1';
                    $banner_presencial = $curso_presencial && function_exists('cursos_get_banner_presencial_html')
                        ? cursos_get_banner_presencial_html()
                        : '';
                    if ($banner_presencial):
                    ?>
                    <div class="curso-presencial-banner">
                        <?php echo $banner_presencial; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>


<script>
function toggleAccordion(btn) {
    const item = btn.closest('.accordion-item, .accordion-faq');
    const content = item.querySelector('.accordion-content');
    const chevron = btn.querySelector('.accordion-chevron');
    const isOpen = item.classList.contains('active');
    
    // Close all others
    document.querySelectorAll('.accordion-item.active, .accordion-faq.active').forEach(function(openItem) {
        if (openItem !== item) {
            openItem.classList.remove('active');
        }
    });
    
    // Toggle current
    if (isOpen) {
        item.classList.remove('active');
    } else {
        item.classList.add('active');
    }
}

// Inicializar seleção de turma
document.addEventListener('DOMContentLoaded', function() {
    const turmaRadios = document.querySelectorAll('input[name="turma_id"]');
    const addCartBtn = document.querySelector('.curso-add-cart');
    const buyNowBtn = document.querySelector('.btn-buy-now');
    const turmaIdCheckout = document.getElementById('turma_id_checkout');
    
    turmaRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            // Habilitar botões
            if (addCartBtn) {
                addCartBtn.disabled = false;
                addCartBtn.classList.remove('btn-disabled');
            }
            if (buyNowBtn) {
                buyNowBtn.disabled = false;
                buyNowBtn.classList.remove('btn-disabled');
            }
            // Atualizar turma_id no checkout
            if (turmaIdCheckout) {
                turmaIdCheckout.value = this.value;
            }
        });
    });
});

function addToCart(cursoId) {
    const btn = document.querySelector('.curso-add-cart');
    const selectedTurma = document.querySelector('input[name="turma_id"]:checked');
    const turmaId = selectedTurma ? selectedTurma.value : '';
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Adicionando...';
    }
    
    fetch(cursosAjax.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add_to_cart&curso_id=' + cursoId + '&turma_id=' + turmaId + '&nonce=' + cursosAjax.nonce
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.data.message, 'success');
            // Atualizar contador do carrinho no header
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = data.data.cart_count;
                cartCount.style.display = 'flex';
            }
            
            // Disparar evento para sincronizar com outras partes da página
            window.dispatchEvent(new CustomEvent('cartUpdated', {
                detail: { count: data.data.cart_count }
            }));
            
            // Sincronizar via localStorage para outras abas (ex: página do carrinho)
            localStorage.setItem('cursos_cart_updated', JSON.stringify({
                count: data.data.cart_count,
                timestamp: Date.now()
            }));
            
            if (btn) {
                btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Adicionado!';
                btn.classList.add('btn-success');
            }
        } else {
            showNotification(data.data.message || 'Erro ao adicionar ao carrinho', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg> Adicionar ao Carrinho';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erro de conexão. Tente novamente.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg> Adicionar ao Carrinho';
        }
    });
}

function showNotification(message, type) {
    // Remover notificações anteriores
    const existing = document.querySelector('.curso-notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = 'curso-notification curso-notification-' + type;
    notification.innerHTML = `
        <div class="notification-content">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${type === 'success' 
                    ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'
                    : '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>'
                }
            </svg>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Remover após 4 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}
</script>

<?php get_footer(); ?>
