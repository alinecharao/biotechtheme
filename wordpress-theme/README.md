# Tema WordPress - Cursos Online

Tema WordPress completo para venda de cursos online com integração de pagamentos Asaas e PagSeguro.

## Instalação

1. Faça upload da pasta `wordpress-theme` para `/wp-content/themes/` do seu WordPress
2. Renomeie a pasta para `cursos-theme` (ou o nome que preferir)
3. Ative o tema em **Aparência > Temas**
4. Acesse **Cursos Config** no menu admin para configurar

## Estrutura de Arquivos

```
wordpress-theme/
├── style.css                 # Estilos principais do tema
├── functions.php             # Funções do tema, CPT, meta boxes
├── header.php                # Cabeçalho do site
├── footer.php                # Rodapé do site
├── index.php                 # Página inicial
├── single-curso.php          # Página individual do curso
├── archive-curso.php         # Lista de cursos
├── page-checkout.php         # Página de checkout
├── includes/
│   ├── class-payment-asaas.php      # Integração Asaas
│   └── class-payment-pagseguro.php  # Integração PagSeguro
├── assets/
│   └── js/
│       └── main.js           # JavaScript principal
└── README.md                 # Este arquivo
```

## Configuração

### 1. Configurações Gerais

Acesse **Cursos Config** no menu do WordPress para configurar:

- Informações de contato (WhatsApp, e-mail, telefone, endereço)
- Links de redes sociais

### 2. Configurações de Pagamento

Acesse **Cursos Config > Pagamentos**:

#### Asaas
1. Crie uma conta em [asaas.com](https://www.asaas.com/)
2. Obtenha sua API Key em **Configurações > Integrações > API**
3. Cole a API Key no campo correspondente
4. Selecione o ambiente (Sandbox para testes, Produção para vendas reais)
5. Marque "Ativar Asaas"

#### PagSeguro
1. Crie uma conta em [pagseguro.uol.com.br](https://pagseguro.uol.com.br/)
2. Obtenha seu token em **Preferências > Integrações**
3. Informe o e-mail da conta e o token
4. Selecione o ambiente
5. Marque "Ativar PagSeguro"

### 3. Webhooks

Configure os webhooks nos gateways de pagamento:

- **Asaas**: `https://seusite.com/wp-json/cursos/v1/asaas-webhook`
- **PagSeguro**: `https://seusite.com/wp-json/cursos/v1/pagseguro-webhook`

## Adicionando Cursos

1. Vá em **Cursos > Adicionar Novo**
2. Preencha o título e descrição do curso
3. Adicione a imagem destacada
4. Preencha os campos de detalhes:
   - Preço
   - Preço original (para mostrar desconto)
   - Duração
   - Número de aulas
   - Avaliação
5. Adicione informações do instrutor
6. Configure o currículo/módulos
7. Selecione as categorias e tipo do curso
8. Publique!

## Taxonomias

### Categorias de Curso
- Tecnologia
- Negócios
- Design
- Marketing
- Saúde
- Idiomas
- etc.

### Tipos de Curso
- Online
- Particular
- Pós-Graduação

## Menus

Configure os menus em **Aparência > Menus**:
- **Menu Principal**: Navegação do topo
- **Menu do Rodapé**: Links do rodapé

## Páginas Necessárias

Crie as seguintes páginas:

1. **Checkout** - Use o template "Checkout"
2. **Carrinho** - Página do carrinho
3. **Pedido Confirmado** - Página de sucesso
4. **Pagamento PIX** - Exibição do QR Code PIX
5. **Pagamento Boleto** - Exibição do boleto

## Customização

### Cores

Edite as variáveis CSS em `style.css`:

```css
:root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --secondary: #f59e0b;
    /* ... */
}
```

### Logo

Vá em **Aparência > Personalizar > Identidade do site** e adicione seu logo.

## Métodos de Pagamento Disponíveis

- PIX (aprovação instantânea)
- Boleto Bancário (3 dias para vencimento)
- Cartão de Crédito (até 12x sem juros)

## Suporte

Para dúvidas ou suporte, entre em contato.

## Licença

GPL v2 ou posterior.
