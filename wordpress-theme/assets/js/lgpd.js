/**
 * Cursos LGPD Consent System
 * Sistema de gerenciamento de consentimento de cookies
 */

(function() {
    'use strict';
    
    const COOKIE_NAME = 'cursos_lgpd_consent';
    const COOKIE_VERSION = '1.0';
    
    window.CursosLGPD = {
        
        /**
         * Inicializar
         */
        init: function() {
            this.banner = document.getElementById('lgpd-banner');
            this.modal = document.getElementById('lgpd-modal');
            this.floatBtn = document.getElementById('lgpd-float-btn');
            
            if (!this.banner) return;
            
            this.bindEvents();
            
            // Verificar se já tem consentimento
            if (!this.hasConsent()) {
                this.showBanner();
            } else {
                // Carregar scripts permitidos
                this.loadConsentedScripts();
                this.showFloatButton();
            }
        },
        
        /**
         * Bind de eventos
         */
        bindEvents: function() {
            var self = this;
            
            // Botão Aceitar Todos
            document.querySelectorAll('[data-lgpd-accept-all]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    self.acceptAll();
                });
            });
            
            // Botão Rejeitar
            document.querySelectorAll('[data-lgpd-reject-all]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    self.rejectAll();
                });
            });
            
            // Botão Configurar
            document.querySelectorAll('[data-lgpd-settings]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    self.openSettings();
                });
            });
            
            // Fechar modal
            document.querySelectorAll('[data-lgpd-close-modal]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    self.closeSettings();
                });
            });
            
            // Salvar preferências do modal
            document.querySelectorAll('[data-lgpd-save-preferences]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    self.saveFromModal();
                });
            });
            
            // Botão flutuante
            if (this.floatBtn) {
                this.floatBtn.addEventListener('click', function() {
                    self.openSettings();
                });
            }
            
            // Fechar modal ao clicar fora
            if (this.modal) {
                this.modal.addEventListener('click', function(e) {
                    if (e.target === self.modal) {
                        self.closeSettings();
                    }
                });
            }
            
            // ESC para fechar modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && self.modal && self.modal.classList.contains('active')) {
                    self.closeSettings();
                }
            });
        },
        
        /**
         * Verificar se tem consentimento válido
         */
        hasConsent: function() {
            var consent = this.getConsent();
            if (!consent) return false;
            
            // Verificar expiração
            if (consent.expires && new Date(consent.expires) < new Date()) {
                this.clearConsent();
                return false;
            }
            
            return true;
        },
        
        /**
         * Obter consentimento salvo
         */
        getConsent: function() {
            try {
                var cookie = this.getCookie(COOKIE_NAME);
                if (cookie) {
                    return JSON.parse(cookie);
                }
            } catch (e) {
                console.error('LGPD: Error parsing consent cookie', e);
            }
            return null;
        },
        
        /**
         * Obter preferências (categorias aceitas)
         */
        getPreferences: function() {
            var consent = this.getConsent();
            var prefs = {};
            
            if (consent && consent.categories) {
                consent.categories.forEach(function(cat) {
                    prefs[cat] = true;
                });
            }
            
            return prefs;
        },
        
        /**
         * Verificar consentimento para categoria específica
         */
        hasConsentFor: function(category) {
            var consent = this.getConsent();
            if (!consent || !consent.categories) return false;
            return consent.categories.indexOf(category) !== -1;
        },
        
        /**
         * Aceitar todos
         */
        acceptAll: function() {
            var categories = this.getAllCategories();
            this.saveConsent(categories, 'accept_all');
            this.hideBanner();
            this.closeSettings();
            this.loadConsentedScripts();
            this.showFloatButton();
            this.dispatchEvent('consent_given', { action: 'accept_all', categories: categories });
        },
        
        /**
         * Rejeitar todos (apenas necessários)
         */
        rejectAll: function() {
            var categories = this.getMandatoryCategories();
            this.saveConsent(categories, 'reject_all');
            this.hideBanner();
            this.closeSettings();
            this.showFloatButton();
            this.dispatchEvent('consent_given', { action: 'reject_all', categories: categories });
        },
        
        /**
         * Salvar preferências do modal
         */
        saveFromModal: function() {
            var categories = [];
            
            document.querySelectorAll('.lgpd-modal-category input[type="checkbox"]:checked').forEach(function(cb) {
                categories.push(cb.value);
            });
            
            // Adicionar obrigatórios
            this.getMandatoryCategories().forEach(function(cat) {
                if (categories.indexOf(cat) === -1) {
                    categories.push(cat);
                }
            });
            
            this.saveConsent(categories, 'custom');
            this.hideBanner();
            this.closeSettings();
            this.loadConsentedScripts();
            this.showFloatButton();
            this.dispatchEvent('consent_given', { action: 'custom', categories: categories });
        },
        
        /**
         * Salvar preferências diretamente
         */
        savePreferences: function(categories) {
            // Adicionar obrigatórios
            var self = this;
            this.getMandatoryCategories().forEach(function(cat) {
                if (categories.indexOf(cat) === -1) {
                    categories.push(cat);
                }
            });
            
            this.saveConsent(categories, 'custom');
            this.loadConsentedScripts();
            this.dispatchEvent('preferences_updated', { categories: categories });
        },
        
        /**
         * Salvar consentimento
         */
        saveConsent: function(categories, action) {
            var config = window.CursosLGPDConfig || {};
            var expirationDays = config.expiration || 365;
            
            var expires = new Date();
            expires.setDate(expires.getDate() + expirationDays);
            
            var consent = {
                version: COOKIE_VERSION,
                categories: categories,
                action: action,
                timestamp: new Date().toISOString(),
                expires: expires.toISOString()
            };
            
            this.setCookie(COOKIE_NAME, JSON.stringify(consent), expirationDays);
            
            // Enviar para o servidor se log estiver habilitado
            if (config.logEnabled) {
                this.sendToServer(action, categories);
            }
        },
        
        /**
         * Enviar consentimento para o servidor
         */
        sendToServer: function(action, categories) {
            var config = window.CursosLGPDConfig || {};
            
            var formData = new FormData();
            formData.append('action', 'cursos_lgpd_save_consent');
            formData.append('nonce', config.nonce);
            formData.append('consent_action', action);
            categories.forEach(function(cat) {
                formData.append('categories[]', cat);
            });
            
            fetch(config.ajaxUrl, {
                method: 'POST',
                body: formData
            }).catch(function(error) {
                console.error('LGPD: Error saving consent', error);
            });
        },
        
        /**
         * Limpar consentimento
         */
        clearConsent: function() {
            this.deleteCookie(COOKIE_NAME);
        },
        
        /**
         * Obter todas as categorias
         */
        getAllCategories: function() {
            var config = window.CursosLGPDConfig || {};
            return Object.keys(config.categories || {});
        },
        
        /**
         * Obter categorias obrigatórias
         */
        getMandatoryCategories: function() {
            var config = window.CursosLGPDConfig || {};
            var mandatory = [];
            
            for (var key in config.categories) {
                if (config.categories[key].mandatory) {
                    mandatory.push(key);
                }
            }
            
            return mandatory;
        },
        
        /**
         * Carregar scripts permitidos
         */
        loadConsentedScripts: function() {
            var self = this;
            
            // Ativar scripts bloqueados
            document.querySelectorAll('script[type="text/plain"][data-lgpd-category]').forEach(function(script) {
                var category = script.getAttribute('data-lgpd-category');
                
                if (self.hasConsentFor(category)) {
                    var newScript = document.createElement('script');
                    
                    // Copiar atributos
                    Array.from(script.attributes).forEach(function(attr) {
                        if (attr.name !== 'type' && attr.name !== 'data-lgpd-category') {
                            newScript.setAttribute(attr.name, attr.value);
                        }
                    });
                    
                    // Copiar conteúdo
                    if (script.innerHTML) {
                        newScript.innerHTML = script.innerHTML;
                    }
                    
                    script.parentNode.replaceChild(newScript, script);
                }
            });
            
            // Ativar iframes bloqueados
            document.querySelectorAll('[data-lgpd-src]').forEach(function(el) {
                var category = el.getAttribute('data-lgpd-category');
                
                if (self.hasConsentFor(category)) {
                    el.src = el.getAttribute('data-lgpd-src');
                    el.removeAttribute('data-lgpd-src');
                }
            });
            
            // Disparar evento para Google Analytics
            if (this.hasConsentFor('analytics')) {
                this.dispatchEvent('analytics_consent', { granted: true });
                
                // Atualizar consent do Google
                if (typeof gtag === 'function') {
                    gtag('consent', 'update', {
                        'analytics_storage': 'granted'
                    });
                }
            }
            
            // Disparar evento para Marketing
            if (this.hasConsentFor('marketing')) {
                this.dispatchEvent('marketing_consent', { granted: true });
                
                // Atualizar consent do Google
                if (typeof gtag === 'function') {
                    gtag('consent', 'update', {
                        'ad_storage': 'granted',
                        'ad_user_data': 'granted',
                        'ad_personalization': 'granted'
                    });
                }
            }
        },
        
        /**
         * Mostrar banner
         */
        showBanner: function() {
            if (this.banner) {
                this.banner.classList.add('active');
                this.banner.setAttribute('aria-hidden', 'false');
                document.body.classList.add('lgpd-banner-visible');
            }
        },
        
        /**
         * Esconder banner
         */
        hideBanner: function() {
            if (this.banner) {
                this.banner.classList.remove('active');
                this.banner.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('lgpd-banner-visible');
            }
        },
        
        /**
         * Abrir configurações
         */
        openSettings: function() {
            if (this.modal) {
                // Marcar checkboxes baseado nas preferências atuais
                var prefs = this.getPreferences();
                
                this.modal.querySelectorAll('.lgpd-modal-category input[type="checkbox"]').forEach(function(cb) {
                    if (!cb.disabled) {
                        cb.checked = prefs[cb.value] || false;
                    }
                });
                
                this.modal.classList.add('active');
                this.modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('lgpd-modal-open');
                
                // Focus no modal
                this.modal.querySelector('.lgpd-modal-content').focus();
            }
        },
        
        /**
         * Fechar configurações
         */
        closeSettings: function() {
            if (this.modal) {
                this.modal.classList.remove('active');
                this.modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('lgpd-modal-open');
            }
        },
        
        /**
         * Mostrar botão flutuante
         */
        showFloatButton: function() {
            if (this.floatBtn) {
                this.floatBtn.classList.add('active');
            }
        },
        
        /**
         * Disparar evento customizado
         */
        dispatchEvent: function(name, detail) {
            var event = new CustomEvent('cursos_lgpd_' + name, { detail: detail });
            document.dispatchEvent(event);
        },
        
        // === Cookie Helpers ===
        
        setCookie: function(name, value, days) {
            var expires = '';
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
        },
        
        getCookie: function(name) {
            var nameEQ = name + '=';
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
            }
            return null;
        },
        
        deleteCookie: function(name) {
            document.cookie = name + '=; Max-Age=-99999999; path=/';
        }
    };
    
    // Inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            CursosLGPD.init();
        });
    } else {
        CursosLGPD.init();
    }
    
})();
