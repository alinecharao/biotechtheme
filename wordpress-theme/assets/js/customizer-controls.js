/**
 * Customizer Controls - Cursos Theme
 * Scripts para os controles personalizados do Customizer
 */
(function($) {
    'use strict';
    
    wp.customize.bind('ready', function() {
        
        // ========================================
        // Mostrar/ocultar campos baseado no tipo de Hero
        // ========================================
        wp.customize('cursos_hero_type', function(setting) {
            var toggleHeroControls = function(value) {
                // Controles específicos do slider
                var sliderControls = [
                    'cursos_hero_slide_1_image',
                    'cursos_hero_slide_1_title',
                    'cursos_hero_slide_1_subtitle',
                    'cursos_hero_slide_2_image',
                    'cursos_hero_slide_2_title',
                    'cursos_hero_slide_2_subtitle',
                    'cursos_hero_slide_3_image',
                    'cursos_hero_slide_3_title',
                    'cursos_hero_slide_3_subtitle'
                ];
                
                // Controles específicos do vídeo
                var videoControls = [
                    'cursos_hero_video_url',
                    'cursos_hero_video_poster'
                ];
                
                // Controles do gradiente
                var gradientControls = [
                    'cursos_hero_gradient_start',
                    'cursos_hero_gradient_end'
                ];
                
                // Controles de imagem estática
                var staticControls = [
                    'cursos_hero_image'
                ];
                
                // Função para toggle de controles
                var toggleControls = function(controls, show) {
                    controls.forEach(function(controlId) {
                        var control = wp.customize.control(controlId);
                        if (control) {
                            if (show) {
                                control.container.slideDown(200);
                            } else {
                                control.container.slideUp(200);
                            }
                        }
                    });
                };
                
                // Aplicar visibilidade baseado no tipo selecionado
                toggleControls(sliderControls, value === 'slider');
                toggleControls(videoControls, value === 'video');
                toggleControls(gradientControls, value === 'gradient');
                toggleControls(staticControls, value === 'static');
            };
            
            // Aplicar ao carregar
            setting.bind(toggleHeroControls);
            toggleHeroControls(setting.get());
        });
        
        // ========================================
        // Mostrar/ocultar campos do Header
        // ========================================
        wp.customize('cursos_header_type', function(setting) {
            var toggleHeaderControls = function(value) {
                var stickyControls = ['cursos_header_sticky'];
                var transparentControls = ['cursos_header_transparent_home'];
                
                stickyControls.forEach(function(controlId) {
                    var control = wp.customize.control(controlId);
                    if (control) {
                        control.container.toggle(value === 'sticky' || value === 'default');
                    }
                });
            };
            
            setting.bind(toggleHeaderControls);
            toggleHeaderControls(setting.get());
        });
        
        // ========================================
        // Mostrar/ocultar campos do WhatsApp
        // ========================================
        wp.customize('cursos_whatsapp_show', function(setting) {
            var toggleWhatsAppControls = function(value) {
                var whatsappControls = [
                    'cursos_whatsapp_number',
                    'cursos_whatsapp_message',
                    'cursos_whatsapp_position'
                ];
                
                whatsappControls.forEach(function(controlId) {
                    var control = wp.customize.control(controlId);
                    if (control) {
                        if (value) {
                            control.container.slideDown(200);
                        } else {
                            control.container.slideUp(200);
                        }
                    }
                });
            };
            
            setting.bind(toggleWhatsAppControls);
            toggleWhatsAppControls(setting.get());
        });
        
        // ========================================
        // Atualização em tempo real de cores
        // ========================================
        var colorSettings = [
            'cursos_primary_color',
            'cursos_primary_dark_color', 
            'cursos_secondary_color',
            'cursos_text_color',
            'cursos_background_color',
            'cursos_header_bg_color',
            'cursos_header_text_color',
            'cursos_footer_bg_color',
            'cursos_footer_text_color'
        ];
        
        colorSettings.forEach(function(settingId) {
            wp.customize(settingId, function(setting) {
                setting.bind(function(value) {
                    // Trigger change event para atualizar preview
                    wp.customize.previewer.refresh();
                });
            });
        });
        
        // ========================================
        // Validação de campos
        // ========================================
        
        // Validar número do WhatsApp (apenas números)
        wp.customize('cursos_whatsapp_number', function(setting) {
            setting.bind(function(value) {
                var cleaned = value.replace(/\D/g, '');
                if (cleaned !== value) {
                    setting.set(cleaned);
                }
            });
        });
        
        // ========================================
        // Helpers para preview instantâneo de imagens
        // ========================================
        var imageSettings = [
            'cursos_logo',
            'cursos_logo_white',
            'cursos_favicon',
            'cursos_hero_image'
        ];
        
        imageSettings.forEach(function(settingId) {
            wp.customize(settingId, function(setting) {
                setting.bind(function(value) {
                    // Atualizar preview quando imagem mudar
                    wp.customize.previewer.refresh();
                });
            });
        });
        
        // ========================================
        // Controles de layout responsivo
        // ========================================
        wp.customize('cursos_layout_width', function(setting) {
            setting.bind(function(value) {
                // Feedback visual para largura do layout
                var validWidths = ['1200', '1400', '1600', 'full'];
                if (validWidths.indexOf(value) === -1) {
                    setting.set('1400');
                }
            });
        });
        
    });
    
})(jQuery);
