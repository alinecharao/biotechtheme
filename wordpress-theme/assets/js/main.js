/**
 * Cursos Theme - Main JavaScript
 * 
 * @package CursosTheme
 */

(function($) {
    'use strict';

    // Document Ready
    $(document).ready(function() {
        initMobileMenu();
        initSmoothScroll();
        initFormMasks();
        initAccordion();
        initCursosAjaxFilters();
    });

    /**
     * Accordion para Single Curso
     */
    function initAccordion() {
        $('.accordion-trigger').on('click', function() {
            const $item = $(this).closest('.accordion-item');
            const isActive = $item.hasClass('active');
            
            // Fechar todos os outros
            $item.siblings('.accordion-item').removeClass('active');
            
            // Toggle o atual
            $item.toggleClass('active', !isActive);
        });
    }

    /**
     * Mobile Menu Toggle
     */
    function initMobileMenu() {
        const $toggle = $('#mobileMenuToggle');
        const $menu = $('#mobileMenu');

        $toggle.on('click', function() {
            $menu.slideToggle(300);
        });

        // Close menu on window resize
        $(window).on('resize', function() {
            if ($(window).width() > 768) {
                $menu.hide();
            }
        });

        // Close menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#mobileMenu, #mobileMenuToggle').length) {
                $menu.slideUp(300);
            }
        });
    }

    /**
     * Smooth Scroll
     */
    function initSmoothScroll() {
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 800);
            }
        });
    }

    /**
     * Form Masks
     */
    function initFormMasks() {
        // CPF Mask
        $('input[name="cpf"], #cpf').on('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
            this.value = value;
        });

        // Phone Mask
        $('input[name="telefone"], input[type="tel"]').on('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            this.value = value;
        });

        // Credit Card Number Mask
        $('input[name="card_number"], #card_number').on('input', function() {
            let value = this.value.replace(/\D/g, '');
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            this.value = value;
        });

        // CEP Mask
        $('input[name="cep"]').on('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length <= 8) {
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            this.value = value;
        });

        // Currency/Money Mask (R$ 1.234,56)
        $('input[data-mask="currency"], input.currency-mask, input[name="preco"], input[name="valor"]').on('input', function() {
            let value = this.value.replace(/\D/g, '');
            
            if (value === '') {
                this.value = '';
                return;
            }
            
            // Converter para centavos e depois formatar
            let numericValue = parseInt(value, 10);
            let formattedValue = (numericValue / 100).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            
            this.value = 'R$ ' + formattedValue;
        });

        // Currency without prefix (1.234,56)
        $('input[data-mask="currency-no-prefix"]').on('input', function() {
            let value = this.value.replace(/\D/g, '');
            
            if (value === '') {
                this.value = '';
                return;
            }
            
            let numericValue = parseInt(value, 10);
            let formattedValue = (numericValue / 100).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            
            this.value = formattedValue;
        });
    }

    /**
     * Parse currency string to float
     */
    window.parseCurrency = function(value) {
        if (!value) return 0;
        // Remove R$, pontos de milhar e substitui vírgula por ponto
        return parseFloat(value.replace(/[R$\s.]/g, '').replace(',', '.')) || 0;
    };

    /**
     * Format number to Brazilian currency
     */
    window.formatCurrency = function(value, withPrefix = true) {
        let numericValue = parseFloat(value) || 0;
        let formatted = numericValue.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        return withPrefix ? 'R$ ' + formatted : formatted;
    };


    /**
     * Show loading overlay
     */
    function showLoading() {
        if (!$('#loadingOverlay').length) {
            $('body').append('<div id="loadingOverlay" class="loading-overlay"><div class="loading-spinner"></div></div>');
        }
        $('#loadingOverlay').fadeIn(200);
    }

    /**
     * Hide loading overlay
     */
    function hideLoading() {
        $('#loadingOverlay').fadeOut(200);
    }

    /**
     * Show notification
     */
    function showNotification(message, type) {
        const $notification = $('<div class="notification notification-' + type + '">' + message + '</div>');
        
        $notification.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 25px',
            borderRadius: '8px',
            color: '#fff',
            fontWeight: '600',
            zIndex: 10000,
            opacity: 0,
            transform: 'translateX(100px)',
            transition: 'all 0.3s ease'
        });

        if (type === 'success') {
            $notification.css('background', '#10b981');
        } else if (type === 'error') {
            $notification.css('background', '#ef4444');
        } else {
            $notification.css('background', '#6366f1');
        }

        $('body').append($notification);

        setTimeout(function() {
            $notification.css({
                opacity: 1,
                transform: 'translateX(0)'
            });
        }, 10);

        setTimeout(function() {
            $notification.css({
                opacity: 0,
                transform: 'translateX(100px)'
            });
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Accordion functionality for curriculum
     */
    window.toggleModule = function(btn) {
        const $btn = $(btn);
        const $content = $btn.next('.curriculum-content');
        const $chevron = $btn.find('.chevron');

        $content.slideToggle(300);
        $chevron.toggleClass('rotated');
    };

    /**
     * Accordion functionality for single-curso page (Pure JS fallback)
     */
    window.toggleAccordion = function(btn) {
        const item = btn.closest('.accordion-item, .accordion-faq');
        const content = btn.nextElementSibling;
        const chevron = btn.querySelector('.accordion-chevron');
        const isOpen = item.classList.contains('active');
        
        // Fechar outros acordeões do mesmo grupo (siblings)
        const parent = item.parentElement;
        const siblings = parent.querySelectorAll('.accordion-item, .accordion-faq');
        siblings.forEach(function(sibling) {
            if (sibling !== item) {
                sibling.classList.remove('active');
                const sibContent = sibling.querySelector('.accordion-content');
                const sibChevron = sibling.querySelector('.accordion-chevron');
                if (sibContent) {
                    sibContent.style.maxHeight = null;
                    sibContent.style.display = 'none';
                }
                if (sibChevron) {
                    sibChevron.classList.remove('rotated');
                }
            }
        });

        // Toggle o atual
        if (!isOpen) {
            item.classList.add('active');
            content.style.display = 'block';
            content.style.maxHeight = content.scrollHeight + 'px';
            if (chevron) chevron.classList.add('rotated');
        } else {
            item.classList.remove('active');
            content.style.maxHeight = null;
            content.style.display = 'none';
            if (chevron) chevron.classList.remove('rotated');
        }
    };

    /**
     * Payment gateway selection
     */
    window.selectGateway = function(element, gateway) {
        $(element).closest('.payment-methods').find('.payment-option').removeClass('active');
        $(element).addClass('active');
        $(element).find('input').prop('checked', true);
        $('#paymentMethods').slideDown(300);
    };

    /**
     * Payment method selection
     */
    window.selectPayment = function(element, method) {
        $(element).siblings('.payment-option').removeClass('active');
        $(element).addClass('active');
        $(element).find('input').prop('checked', true);

        if (method === 'credit_card') {
            $('#creditCardFields').slideDown(300);
        } else {
            $('#creditCardFields').slideUp(300);
        }
    };

    /**
     * Copy to clipboard
     */
    window.copyToClipboard = function(text) {
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
        showNotification('Código copiado!', 'success');
    };

    /**
     * ========================================
     * AJAX FILTERS PARA CURSOS
     * Filtra sem recarregar página
     * ========================================
     */
    function initCursosAjaxFilters() {
        const $container = $('#cursos-ajax-container');
        if (!$container.length) return;

        let debounceTimer;
        const state = {
            page: parseInt($container.data('page')) || 1,
            categoria: $container.data('categoria') || 'all',
            busca: $container.data('busca') || '',
            ordenar: $container.data('ordenar') || 'recent',
            perPage: parseInt($container.data('per-page')) || 9
        };

        // Referencias aos elementos
        const $gridContainer = $('#cursos-grid-container');
        const $countDisplay = $('#cursos-count');
        const $paginationContainer = $('#cursos-pagination');
        const $searchInput = $('#cursos-search-input');
        const $orderSelect = $('#cursos-order-select');

        // ========================================
        // EVENT: Click nas tags de categoria
        // ========================================
        $container.on('click', '.cursos-tag[data-categoria]', function() {
            const categoria = $(this).attr('data-categoria');
            state.categoria = categoria;
            state.page = 1;
            
            // Atualizar visual das tags
            $container.find('.cursos-tag').removeClass('active');
            $(this).addClass('active');
            
            fetchCursos();
        });

        // ========================================
        // EVENT: Input de busca (com debounce)
        // ========================================
        $searchInput.on('input', function() {
            clearTimeout(debounceTimer);
            const searchValue = $(this).val();
            
            debounceTimer = setTimeout(function() {
                state.busca = searchValue;
                state.page = 1;
                fetchCursos();
            }, 400);
        });

        // Permitir Enter para buscar imediatamente
        $searchInput.on('keypress', function(e) {
            if (e.which === 13) {
                clearTimeout(debounceTimer);
                state.busca = $(this).val();
                state.page = 1;
                fetchCursos();
            }
        });

        // ========================================
        // EVENT: Select de ordenação
        // ========================================
        $orderSelect.on('change', function() {
            state.ordenar = $(this).val();
            state.page = 1;
            fetchCursos();
        });

        // ========================================
        // EVENT: Botões de paginação (delegado)
        // ========================================
        $container.on('click', '.pagination-btn[data-page]', function() {
            const page = parseInt($(this).data('page'));
            if (page !== state.page) {
                state.page = page;
                fetchCursos();
                
                // Scroll suave para o topo da lista
                $('html, body').animate({
                    scrollTop: $container.offset().top - 120
                }, 400);
            }
        });

        // ========================================
        // FUNÇÃO: Fetch cursos via AJAX
        // ========================================
        function fetchCursos() {
            // Loading state
            $gridContainer.addClass('loading');

            // Atualizar URL sem recarregar
            updateURL();

            // Fazer requisição AJAX
            $.ajax({
                url: cursosAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cursos_filter',
                    nonce: cursosAjax.nonce,
                    page: state.page,
                    categoria: state.categoria,
                    busca: state.busca,
                    ordenar: state.ordenar,
                    per_page: state.perPage
                },
                success: function(response) {
                    if (response.success) {
                        // Atualizar grid de cursos
                        $gridContainer.html(response.data.html);
                        
                        // Atualizar contagem
                        updateCount(response.data.total);
                        
                        // Atualizar paginação
                        updatePagination(response.data);
                        
                        // Atualizar data attributes do container
                        $container.data('page', state.page);
                    } else {
                        console.error('Erro ao filtrar cursos:', response.data);
                    }
                    
                    $gridContainer.removeClass('loading');
                },
                error: function(xhr, status, error) {
                    console.error('Erro AJAX:', error);
                    $gridContainer.removeClass('loading');
                    showNotification('Erro ao carregar cursos. Tente novamente.', 'error');
                }
            });
        }

        // ========================================
        // FUNÇÃO: Atualizar URL (para compartilhar/histórico)
        // ========================================
        function updateURL() {
            const params = new URLSearchParams();
            
            if (state.categoria !== 'all') {
                params.set('categoria', state.categoria);
            }
            if (state.busca) {
                params.set('busca', state.busca);
            }
            if (state.ordenar !== 'recent') {
                params.set('ordenar', state.ordenar);
            }
            if (state.page > 1) {
                params.set('pagina', state.page);
            }
            
            const queryString = params.toString();
            const newURL = window.location.pathname + (queryString ? '?' + queryString : '');
            
            // Usar pushState para atualizar URL sem recarregar
            history.pushState(state, '', newURL);
        }

        // ========================================
        // FUNÇÃO: Atualizar contagem de cursos
        // ========================================
        function updateCount(total) {
            const plural = total !== 1 ? 's' : '';
            $countDisplay.text(total + ' curso' + plural + ' encontrado' + plural);
        }

        // ========================================
        // FUNÇÃO: Atualizar paginação
        // ========================================
        function updatePagination(data) {
            const totalPages = data.pages;
            const currentPage = data.current;
            
            if (totalPages <= 1) {
                $paginationContainer.hide();
                return;
            }
            
            $paginationContainer.show();
            
            let html = '';
            
            // Botão Anterior
            if (currentPage > 1) {
                html += '<button type="button" class="pagination-btn pagination-prev" data-page="' + (currentPage - 1) + '">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>' +
                        'Anterior</button>';
            }
            
            // Números de página
            html += '<div class="pagination-numbers">';
            
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) {
                html += '<button type="button" class="pagination-btn pagination-number" data-page="1">1</button>';
                if (startPage > 2) {
                    html += '<span class="pagination-ellipsis">...</span>';
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const activeClass = (i === currentPage) ? 'active' : '';
                html += '<button type="button" class="pagination-btn pagination-number ' + activeClass + '" data-page="' + i + '">' + i + '</button>';
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += '<span class="pagination-ellipsis">...</span>';
                }
                html += '<button type="button" class="pagination-btn pagination-number" data-page="' + totalPages + '">' + totalPages + '</button>';
            }
            
            html += '</div>';
            
            // Botão Próxima
            if (currentPage < totalPages) {
                html += '<button type="button" class="pagination-btn pagination-next" data-page="' + (currentPage + 1) + '">' +
                        'Próxima' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>' +
                        '</button>';
            }
            
            $paginationContainer.html(html).attr('data-total-pages', totalPages);
        }

        // ========================================
        // EVENT: Navegação do navegador (Back/Forward)
        // ========================================
        $(window).on('popstate', function(e) {
            if (e.originalEvent.state) {
                // Restaurar estado
                Object.assign(state, e.originalEvent.state);
                
                // Atualizar visual dos controles
                $container.find('.cursos-tag').removeClass('active');
                $container.find('.cursos-tag[data-categoria="' + state.categoria + '"]').addClass('active');
                $searchInput.val(state.busca);
                $orderSelect.val(state.ordenar);
                
                // Buscar cursos sem atualizar URL (já está correta)
                $gridContainer.addClass('loading');
                $.ajax({
                    url: cursosAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cursos_filter',
                        nonce: cursosAjax.nonce,
                        page: state.page,
                        categoria: state.categoria,
                        busca: state.busca,
                        ordenar: state.ordenar,
                        per_page: state.perPage
                    },
                    success: function(response) {
                        if (response.success) {
                            $gridContainer.html(response.data.html);
                            updateCount(response.data.total);
                            updatePagination(response.data);
                        }
                        $gridContainer.removeClass('loading');
                    }
                });
            }
        });
    }

})(jQuery);
