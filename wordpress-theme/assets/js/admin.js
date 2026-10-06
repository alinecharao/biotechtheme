/**
 * Cursos Theme - Admin JavaScript
 * Máscaras e funcionalidades para o painel administrativo
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initCurrencyMasks();
    });

    /**
     * Inicializa máscaras de moeda nos campos de preço
     */
    function initCurrencyMasks() {
        // Seleciona campos de preço - inclui campos de meta do WordPress
        var $currencyFields = $('input.currency-mask, input[data-mask="currency"], input[id*="preco"], input[id*="price"], input[name*="preco"], input[name*="price"]');
        
        // Aplica máscara ao digitar
        $currencyFields.on('input', function() {
            var cursorPos = this.selectionStart;
            var oldLength = this.value.length;
            
            // Remove tudo exceto números e vírgula
            var value = this.value.replace(/[^\d,]/g, '');
            
            if (value === '') {
                this.value = '';
                return;
            }
            
            // Se tiver vírgula, separa parte inteira e decimal
            var parts = value.split(',');
            var integerPart = parts[0].replace(/\D/g, '');
            var decimalPart = parts[1] ? parts[1].replace(/\D/g, '').substring(0, 2) : '';
            
            // Remove zeros à esquerda (exceto se for só zero)
            if (integerPart.length > 1) {
                integerPart = integerPart.replace(/^0+/, '') || '0';
            }
            
            // Formata com pontos de milhar
            var formattedInteger = '';
            for (var i = integerPart.length - 1, j = 0; i >= 0; i--, j++) {
                if (j > 0 && j % 3 === 0) {
                    formattedInteger = '.' + formattedInteger;
                }
                formattedInteger = integerPart[i] + formattedInteger;
            }
            
            // Monta valor final
            if (parts.length > 1) {
                this.value = 'R$ ' + formattedInteger + ',' + decimalPart;
            } else {
                this.value = 'R$ ' + formattedInteger;
            }
            
            // Ajusta posição do cursor
            var newLength = this.value.length;
            var newPos = cursorPos + (newLength - oldLength);
            if (newPos < 3) newPos = 3;
            this.setSelectionRange(newPos, newPos);
        });

        // Formata valores existentes ao carregar
        $currencyFields.each(function() {
            var value = $(this).data('value') || $(this).val();
            if (value) {
                var numValue = parseFloat(String(value).replace(/[R$\s.]/g, '').replace(',', '.'));
                if (!isNaN(numValue) && numValue > 0) {
                    this.value = formatCurrency(numValue);
                }
            }
        });

        // Ao submeter o formulário, converte valores formatados para numéricos
        $('#post, form.metabox-form').on('submit', function() {
            $currencyFields.each(function() {
                var $input = $(this);
                var formattedValue = $input.val();
                var numericValue = parseCurrency(formattedValue);
                $input.val(numericValue);
            });
        });
    }

    /**
     * Converte string formatada para float
     * Ex: "R$ 1.234,56" -> 1234.56
     */
    function parseCurrency(value) {
        if (!value) return 0;
        // Remove R$, pontos de milhar e substitui vírgula por ponto
        return parseFloat(value.replace(/[R$\s.]/g, '').replace(',', '.')) || 0;
    }

    /**
     * Formata número para moeda brasileira
     * Ex: 1234.56 -> "R$ 1.234,56"
     */
    function formatCurrency(value) {
        var numericValue = parseFloat(value) || 0;
        var formatted = numericValue.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        return 'R$ ' + formatted;
    }

    // Expõe funções globalmente para uso em outros scripts
    window.cursosParseCurrency = parseCurrency;
    window.cursosFormatCurrency = formatCurrency;

})(jQuery);
