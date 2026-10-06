# Configuração da Página Inicial - Starter Theme

## Requisitos

Antes de configurar a página inicial, certifique-se de que:

1. O tema "Starter Theme" está ativo
2. Você tem acesso ao painel administrativo do WordPress

---

## Opção 1: Página Inicial Automática (Recomendado)

O tema já está configurado para exibir automaticamente a página inicial completa quando você acessa a URL principal do site.

**Nenhuma configuração adicional é necessária!**

Apenas certifique-se de que em **Configurações → Leitura**:
- A opção "Sua página inicial exibe" está definida como **"Suas últimas publicações"**

---

## Opção 2: Página Estática como Inicial

Se preferir usar uma página estática como página inicial:

### Passo 1: Criar a Página

1. Vá para **Páginas → Adicionar nova**
2. Título: `Home` (ou qualquer nome)
3. No painel direito, em **Template de página**, selecione: **Template Home**
4. Publique a página

### Passo 2: Definir como Página Inicial

1. Vá para **Configurações → Leitura**
2. Em "Sua página inicial exibe", selecione **"Uma página estática"**
3. Em "Página inicial", escolha a página que você criou (ex: "Home")
4. Salve as alterações

---

## Seções da Página Inicial

A página inicial inclui automaticamente as seguintes seções:

| Seção | Descrição | Arquivo |
|-------|-----------|---------|
| Hero | Banner principal com título e CTAs | `template-parts/hero-static.php` |
| Social Proof | Estatísticas (alunos, avaliações, etc.) | `template-parts/social-proof-bar.php` |
| Cursos em Destaque | Grid de 4 cursos destacados | `template-parts/home-featured-courses.php` |
| Mid CTA | Chamada para curso gratuito | `template-parts/mid-cta.php` |
| Features | 6 cards de benefícios | `template-parts/features-section.php` |
| Depoimentos | Carousel de testemunhos | `template-parts/testimonials-section.php` |
| CTA Final | Chamada para ação final | `template-parts/final-cta.php` |
| Newsletter | Formulário de inscrição | `template-parts/newsletter-section.php` |

---

## Personalizando as Seções

### Via Customizer

Acesse **Aparência → Personalizar** para ajustar:

- **Cores**: Primária, secundária, texto
- **Tipografia**: Fonte do corpo e títulos
- **Hero**: Tipo (estático, slider, vídeo), título, subtítulo
- **WhatsApp**: Número e mensagem padrão
- **Redes Sociais**: Links do Facebook, Instagram, etc.

### Via Código

Cada seção está em um arquivo separado em `template-parts/`. Você pode editar diretamente esses arquivos para customizações avançadas.

---

## Adicionando Cursos

Para que os cursos apareçam na página inicial:

1. Vá para **Cursos → Adicionar novo**
2. Preencha título, descrição, preço, duração, etc.
3. Marque a opção **"Curso em Destaque"** para aparecer na home
4. Selecione uma categoria
5. Adicione uma imagem destacada
6. Publique

**Nota:** O tema inclui dados de demonstração estáticos que aparecem mesmo sem cursos cadastrados.

---

## Adicionando Depoimentos

Para exibir depoimentos reais:

1. Vá para **Depoimentos → Adicionar novo**
2. Título: Nome do cliente
3. Conteúdo: Texto do depoimento
4. Campos personalizados:
   - `cargo`: Profissão/cargo do cliente
   - `avaliacao`: Número de 1 a 5
5. Adicione uma foto como imagem destacada
6. Publique

---

## Troubleshooting

### As seções não aparecem?

1. Limpe o cache do navegador (Ctrl+F5)
2. Se usar plugin de cache, limpe o cache do plugin
3. Verifique se o tema está ativo em **Aparência → Temas**

### Os cursos não aparecem?

1. Verifique se existem cursos publicados em **Cursos**
2. Certifique-se de que pelo menos um curso está marcado como "Destaque"
3. O tema mostra dados de demonstração se não houver cursos

### As cores estão diferentes?

O tema força cores bordô por padrão. Para alterar:
1. Vá para **Aparência → Personalizar → Cores**
2. Ajuste as cores conforme desejado
3. Publique as alterações

---

## Suporte

Para dúvidas ou problemas, entre em contato com o desenvolvedor do tema.
