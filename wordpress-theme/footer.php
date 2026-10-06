<!-- Footer igual ao React: bg escuro (foreground), logo VetCursos, grid responsivo -->
<footer class="site-footer" style="background: var(--dark); color: #fff;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 4rem 1rem;">
        <div class="footer-grid" style="display: grid; grid-template-columns: 1fr; gap: 3rem;">
            
            <!-- Brand Column - igual ao React (lg:col-span-2) -->
            <div class="footer-about" style="grid-column: span 1;">
                <?php if (has_custom_logo()): ?>
                    <?php the_custom_logo(); ?>
                <?php else: ?>
                    <!-- Logo igual ao React -->
                    <a href="<?php echo home_url('/'); ?>" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; margin-bottom: 1.5rem;">
                        <div style="width: 2.5rem; height: 2.5rem; background: var(--primary); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                            <span style="color: #fff; font-family: 'Noto Sans', sans-serif; font-weight: 700; font-size: 1.25rem;">F</span>
                        </div>
                        <span style="font-family: 'Noto Sans', sans-serif; font-size: 1.5rem; font-weight: 700; color: #fff;">Fazenda Escola Biotech</span>
                    </a>
                <?php endif; ?>
                <p style="color: rgba(255,255,255,0.7); max-width: 24rem; margin-bottom: 1.5rem; line-height: 1.6;">
                    Transformando vidas através da educação de qualidade. Cursos online e presenciais para impulsionar sua carreira.
                </p>
                
                <!-- Contact Info igual ao React -->
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php if ($email = get_option('cursos_email')): ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>" style="display: flex; align-items: center; gap: 0.75rem; color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <?php echo esc_html($email); ?>
                    </a>
                    <?php else: ?>
                    <a href="mailto:contato@fazendaescolabiotech.com.br" style="display: flex; align-items: center; gap: 0.75rem; color: rgba(255,255,255,0.7); text-decoration: none;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        contato@fazendaescolabiotech.com.br
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($telefone = get_option('cursos_telefone')): ?>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $telefone)); ?>" style="display: flex; align-items: center; gap: 0.75rem; color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <?php echo esc_html($telefone); ?>
                    </a>
                    <?php else: ?>
                    <a href="tel:+5511999999999" style="display: flex; align-items: center; gap: 0.75rem; color: rgba(255,255,255,0.7); text-decoration: none;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        (11) 99999-9999
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($endereco = get_option('cursos_endereco')): ?>
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem; color: rgba(255,255,255,0.7);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span><?php echo nl2br(esc_html($endereco)); ?></span>
                    </div>
                    <?php else: ?>
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem; color: rgba(255,255,255,0.7);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span>Av. Paulista, 1000 - São Paulo, SP</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Cursos Column - igual ao React -->
            <div class="footer-widget">
                <h4 style="font-family: 'Noto Sans', sans-serif; font-size: 1.125rem; font-weight: 600; margin-bottom: 1.5rem; color: #fff;">Cursos</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li><a href="<?php echo home_url('/cursos'); ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Todos os Cursos</a></li>
                    <li><a href="<?php echo home_url('/cursos-particulares'); ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Cursos Particulares</a></li>
                    <li><a href="<?php echo home_url('/pos-graduacao'); ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Pós-Graduação</a></li>
                    <li><a href="<?php echo home_url('/cursos'); ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Certificações</a></li>
                </ul>
            </div>

            <!-- Institucional Column - igual ao React -->
            <div class="footer-widget">
                <h4 style="font-family: 'Noto Sans', sans-serif; font-size: 1.125rem; font-weight: 600; margin-bottom: 1.5rem; color: #fff;">Institucional</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li><a href="<?php echo home_url('/sobre'); ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Sobre Nós</a></li>
                    <li><a href="<?php echo home_url('/professores'); ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Professores</a></li>
                    <li><a href="<?php echo home_url('/blog'); ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Blog</a></li>
                    <li><a href="<?php echo home_url('/trabalhe-conosco'); ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Trabalhe Conosco</a></li>
                </ul>
            </div>
            
            <!-- Suporte Column - igual ao React -->
            <div class="footer-widget">
                <h4 style="font-family: 'Noto Sans', sans-serif; font-size: 1.125rem; font-weight: 600; margin-bottom: 1.5rem; color: #fff;">Suporte</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
                    <li><a href="<?php echo home_url('/contato'); ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Contato</a></li>
                    <li><a href="<?php echo home_url('/faq'); ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">FAQ</a></li>
                    <li><a href="<?php echo home_url('/termos-de-uso'); ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Termos de Uso</a></li>
                    <li><a href="<?php echo home_url('/politica-de-privacidade'); ?>" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-light)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">Política de Privacidade</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Footer Bottom - igual ao React -->
        <div class="footer-bottom" style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.2); display: flex; flex-direction: column; gap: 1rem; align-items: center;">
            <p style="color: rgba(255,255,255,0.5); font-size: 0.875rem;">
                © <?php echo date('Y'); ?> Fazenda Escola Biotech. Todos os direitos reservados.
            </p>
            
            <!-- Social Links - igual ao React (círculos) -->
            <div style="display: flex; gap: 1rem;">
                <?php if ($facebook = get_option('cursos_facebook')): ?>
                <a href="<?php echo esc_url($facebook); ?>" target="_blank" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: #fff; transition: background 0.3s;" onmouseover="this.style.background='var(--primary)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'" aria-label="Facebook">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </a>
                <?php else: ?>
                <a href="#" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: #fff; transition: background 0.3s;" onmouseover="this.style.background='var(--primary)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'" aria-label="Facebook">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if ($instagram = get_option('cursos_instagram')): ?>
                <a href="<?php echo esc_url($instagram); ?>" target="_blank" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: #fff; transition: background 0.3s;" onmouseover="this.style.background='var(--primary)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'" aria-label="Instagram">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                </a>
                <?php else: ?>
                <a href="#" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: #fff; transition: background 0.3s;" onmouseover="this.style.background='var(--primary)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'" aria-label="Instagram">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if ($linkedin = get_option('cursos_linkedin')): ?>
                <a href="<?php echo esc_url($linkedin); ?>" target="_blank" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: #fff; transition: background 0.3s;" onmouseover="this.style.background='var(--primary)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'" aria-label="LinkedIn">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                </a>
                <?php else: ?>
                <a href="#" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: #fff; transition: background 0.3s;" onmouseover="this.style.background='var(--primary)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'" aria-label="LinkedIn">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if ($youtube = get_option('cursos_youtube')): ?>
                <a href="<?php echo esc_url($youtube); ?>" target="_blank" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: #fff; transition: background 0.3s;" onmouseover="this.style.background='var(--primary)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'" aria-label="YouTube">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </a>
                <?php else: ?>
                <a href="#" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: #fff; transition: background 0.3s;" onmouseover="this.style.background='var(--primary)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'" aria-label="YouTube">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</footer>

<!-- WhatsApp Float Button -->
<?php 
// Pegar número: prioridade Customizer, fallback para config do admin
$whatsapp_number = get_theme_mod('cursos_whatsapp_number', '');
if (empty($whatsapp_number)) {
    $whatsapp_number = get_option('cursos_whatsapp', '');
}

// Só verificar toggle do Customizer se foi explicitamente desativado
// get_theme_mod retorna o default (true) se nunca foi salvo
$whatsapp_show = get_theme_mod('cursos_whatsapp_show', true);

$whatsapp_position = get_theme_mod('cursos_whatsapp_position', 'right');
$whatsapp_message = get_theme_mod('cursos_whatsapp_message', 'Olá! Gostaria de mais informações sobre os cursos.');
$whatsapp_pos_style = $whatsapp_position === 'left' ? 'left: 20px;' : 'right: 20px;';
$whatsapp_href = 'https://wa.me/' . esc_attr($whatsapp_number);
if (!empty($whatsapp_message)) {
    $whatsapp_href .= '?text=' . rawurlencode($whatsapp_message);
}

// Debug temporário - remover após confirmar funcionamento
// error_log('[WhatsApp Debug] show=' . var_export($whatsapp_show, true) . ' number=' . $whatsapp_number . ' theme_mod=' . get_theme_mod('cursos_whatsapp_number', 'VAZIO') . ' option=' . get_option('cursos_whatsapp', 'VAZIO'));
?>
<?php if ($whatsapp_show && !empty($whatsapp_number)): ?>
<a href="<?php echo esc_url($whatsapp_href); ?>" 
   target="_blank" 
   rel="noopener noreferrer"
   class="whatsapp-float"
   aria-label="WhatsApp"
   style="position: fixed; bottom: 20px; <?php echo $whatsapp_pos_style; ?> background: #25D366; color: white; width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 20px rgba(0,0,0,0.2); z-index: 9999; transition: transform 0.3s ease; text-decoration: none;"
   onmouseover="this.style.transform='scale(1.1)'"
   onmouseout="this.style.transform='scale(1)'">
    <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
    </svg>
</a>
<?php endif; ?>

<style>
/* Footer Grid Responsivo - igual ao React */
@media (min-width: 768px) {
    .footer-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    .footer-about {
        grid-column: span 2 !important;
    }
}

@media (min-width: 1024px) {
    .footer-grid {
        grid-template-columns: 2fr 1fr 1fr 1fr !important;
    }
    .footer-about {
        grid-column: span 1 !important;
    }
}

@media (min-width: 768px) {
    .footer-bottom {
        flex-direction: row !important;
        justify-content: space-between !important;
    }
}
</style>

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('mobileMenuToggle');
    const menu = document.getElementById('mobileMenu');
    
    if (toggle && menu) {
        toggle.addEventListener('click', function() {
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        });
    }
    
    // Responsive nav
    function checkWidth() {
        const nav = document.querySelector('.main-nav');
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        
        if (window.innerWidth <= 768) {
            if (nav) nav.style.display = 'none';
            if (mobileToggle) mobileToggle.style.display = 'block';
        } else {
            if (nav) nav.style.display = 'block';
            if (mobileToggle) mobileToggle.style.display = 'none';
            if (menu) menu.style.display = 'none';
        }
    }
    
    checkWidth();
    window.addEventListener('resize', checkWidth);
});
</script>

<?php wp_footer(); ?>
</body>
</html>