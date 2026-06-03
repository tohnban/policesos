# Documentação SEO - Imobil Fácil

**Data:** 15 de Maio de 2026  
**Versão:** 1.0  
**Escopo:** Guia de implementação e manutenção de SEO para a plataforma

---

## 1. Visão Geral

A implementação de SEO (Search Engine Optimization) na Imobil Fácil segue as melhores práticas da indústria:

- **Meta Tags Avançadas**: Open Graph, Twitter Cards, Canonical URLs
- **Structured Data**: JSON-LD schema.org para propriedades e páginas
- **Robots.txt & Sitemap**: Controle de indexação e descoberta de conteúdo
- **Heading Hierarchy**: Estrutura semântica de cabeçalhos
- **Performance**: Otimização de imagens e cache

---

## 2. Arquitetura de SEO

### 2.1 ClassSEO.php

Localização: [src/classes/ClassSEO.php](../src/classes/ClassSEO.php)

Classe centralizadora de configurações e utilidades de SEO:

```php
// Site configuration
ClassSEO::SITE_NAME = 'Imobil Fácil'
ClassSEO::SITE_DESCRIPTION = 'Encontre, anuncie e negocie imóveis de forma simples e segura'
ClassSEO::SITE_LANGUAGE = 'pt-br'

// Structured data methods
ClassSEO::getOrganizationSchema()      // Organization (RealEstateAgent)
ClassSEO::getPropertySchema($prop)     // Individual property (RealEstateProperty)
ClassSEO::getCollectionPageSchema()    // Listing pages (CollectionPage)
ClassSEO::getBreadcrumbSchema($items)  // Navigation breadcrumbs

// Sanitization methods
ClassSEO::sanitizeTitle($text, $maxLength)       // Max 60 chars (Google SERP limit)
ClassSEO::sanitizeDescription($text, $maxLength) // Max 160 chars (Google SERP limit)
```

### 2.2 ClassRender.php Extensões

Localização: [src/classes/ClassRender.php](../src/classes/ClassRender.php)

Novos métodos adicionados para suportar SEO avançado:

```php
// Open Graph support
setOgTitle($title)       // Social media title
setOgDescription($desc)  // Social media description
setOgImage($imageUrl)    // Social media image (1200x630px)
setOgType($type)         // website, article, product

// Canonical URL
setCanonical($url)       // Canonical URL para evitar conteúdo duplicado

// Structured Data (JSON-LD)
setStructuredData($data) // Set structured data
addStructuredData($data) // Add to existing structured data
```

### 2.3 Layout.php Melhorado

Localização: [app/view/Layout.php](../app/view/Layout.php)

Head section agora inclui:

```html
<!-- Basic Meta Tags -->
<meta name="description" content="...">
<meta name="keywords" content="...">
<meta name="robots" content="index, follow, max-image-preview:large...">

<!-- Canonical URL -->
<link rel="canonical" href="...">

<!-- Open Graph Tags -->
<meta property="og:type" content="website">
<meta property="og:title" content="...">
<meta property="og:image" content="...">

<!-- Twitter Card Tags -->
<meta name="twitter:card" content="summary_large_image">

<!-- Structured Data (JSON-LD) -->
<script type="application/ld+json">
{...JSON-LD schema...}
</script>
```

---

## 3. Implementação por Página

### 3.1 Homepage (ControllerHome)

**Ficheiro:** [app/controller/ControllerHome.php](../app/controller/ControllerHome.php)

```php
$Render->setTitle("Imobil Fácil - Encontre e Negocie Imóveis com Segurança");
$Render->setDescription("Plataforma de negociação de imóveis verificados...");
$Render->setOgImage(DIRIMG . 'og-home.jpg');  // 1200x630px minimum

// Structured Data
$Render->addStructuredData(ClassSEO::getOrganizationSchema());
$Render->addStructuredData(ClassSEO::getBreadcrumbSchema([...]));
```

**SEO Score:**
- ✓ Unique title tag
- ✓ Compelling meta description
- ✓ Organization schema
- ✓ Breadcrumb navigation
- ✓ Open Graph tags

### 3.2 Propriedade Individual (ControllerProperty::show)

**Ficheiro:** [app/controller/ControllerProperty.php](../app/controller/ControllerProperty.php)

```php
// Dynamic meta tags based on property data
$render->setTitle($title . ' - ' . $location);
$render->setOgImage($property['primary_image_url']);

// RealEstateProperty schema
$render->addStructuredData(ClassSEO::getPropertySchema($property));
```

**Schema Incluído:**
```json
{
  "@type": "RealEstateProperty",
  "name": "Property Title",
  "price": 250000,
  "priceCurrency": "EUR",
  "address": { "@type": "PostalAddress", ... },
  "offers": { "@type": "Offer", ... },
  "numberOfRooms": 3,
  "floorSize": { "value": 150, "unitCode": "MTK" }
}
```

**SEO Benefits:**
- Rich snippets em resultados de busca
- Estrela de classificações (se implementado)
- Preço, localização e características visíveis
- Melhoria de CTR (Click-Through Rate)

### 3.3 Listagens (ControllerProperty::index)

**Ficheiro:** [app/controller/ControllerProperty.php](../app/controller/ControllerProperty.php)

```php
// Pagination-aware titles
$render->setTitle("Imóveis Disponíveis - Página " . $page);

// CollectionPage schema
$render->addStructuredData(ClassSEO::getCollectionPageSchema(
    $title, $description, $items, $page, $totalPages
));
```

**Schema Features:**
- Pagination support (previousPage, nextPage)
- ItemList com posição de cada item
- Facilita navegação do crawler

---

## 4. Robots.txt & Sitemap

### 4.1 Robots.txt

Localização: [public/robots.txt](../public/robots.txt)

```
User-agent: *
Allow: /

Disallow: /dashboard
Disallow: /admin
Disallow: /api
Disallow: /auth

Crawl-delay: 10 (para bots agressivos)

Sitemap: http://localhost/sitemap.xml
```

**Propósito:**
- Controlar acesso de bots
- Apontar para sitemaps
- Proteger áreas privadas

### 4.2 Sitemap (ControllerSitemap)

Localização: [app/controller/ControllerSitemap.php](../app/controller/ControllerSitemap.php)

**Rutas disponíveis:**

| URL | Propósito | Atualização |
|-----|----------|------------|
| `/sitemap` ou `/sitemap.xml` | Índice de sitemaps | Estática |
| `/sitemap/pages` | Páginas estáticas | Semanal |
| `/sitemap/properties` | Propriedades dinâmicas | Diária |

**Exemplo de sitemap/properties:**
```xml
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>http://localhost/property/123</loc>
    <lastmod>2026-05-15T10:30:00+00:00</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
</urlset>
```

**Limites & Boas Práticas:**
- Máximo 50.000 URLs por sitemap
- Máximo 50MB por ficheiro
- Images incluídos com `<image:image>`
- Priority: 1.0 (homepage) → 0.6 (conteúdo antigo)

---

## 5. Open Graph & Twitter Cards

### 5.1 Open Graph (Facebook, LinkedIn, etc.)

```html
<meta property="og:type" content="website|article|product">
<meta property="og:title" content="...">
<meta property="og:description" content="...">
<meta property="og:image" content="...">  <!-- 1200x630px -->
<meta property="og:url" content="...">
<meta property="og:site_name" content="Imobil Fácil">
```

**Recomendações de Imagem:**
- Tamanho mínimo: 1200x630px (16:9)
- Formato: JPG ou PNG
- Tamanho de ficheiro: < 5MB
- Sem texto muito pequeno

### 5.2 Twitter Cards

```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="...">
<meta name="twitter:description" content="...">
<meta name="twitter:image" content="...">
```

**Validação:**
- Twitter Card Validator: https://cards-dev.twitter.com/validator
- Facebook Debugger: https://developers.facebook.com/tools/debug/

---

## 6. Structured Data (JSON-LD)

### 6.1 Organization Schema

```json
{
  "@context": "https://schema.org",
  "@type": "RealEstateAgent",
  "name": "Imobil Fácil",
  "description": "...",
  "url": "http://localhost",
  "logo": "http://localhost/public/img/logo.png",
  "contactPoint": {
    "@type": "ContactPoint",
    "contactType": "Customer Service",
    "areaServed": "PT"
  }
}
```

### 6.2 RealEstateProperty Schema

```json
{
  "@context": "https://schema.org",
  "@type": "RealEstateProperty",
  "name": "Luxury Apartment",
  "description": "...",
  "price": 250000,
  "priceCurrency": "EUR",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Rua X",
    "addressLocality": "Lisboa",
    "postalCode": "1000-000"
  },
  "numberOfRooms": 3,
  "numberOfBathrooms": 2,
  "floorSize": { "value": 150, "unitCode": "MTK" }
}
```

### 6.3 Validação de Structured Data

Ferramentas recomendadas:
- Google Rich Result Tester: https://search.google.com/test/rich-results
- Schema.org Validator: https://validator.schema.org/
- JSON-LD Playground: https://json-ld.org/playground/

---

## 7. Boas Práticas de SEO

### 7.1 Title Tags

**Recomendações:**
- Comprimento: 50-60 caracteres
- Incluir keyword primária no início
- Unique para cada página
- Branding no final (opcional)

**Exemplos:**
```
✓ Apartamento T3 em Lisboa, Alcântara - Imobil Fácil
✗ Propriedade à venda
```

### 7.2 Meta Descriptions

**Recomendações:**
- Comprimento: 120-160 caracteres
- Call-to-action claro
- Include keywords naturally
- Unique por página

**Exemplos:**
```
✓ Encontre este apartamento T3 luxuoso em Lisboa com garagem. 
  Negocie com segurança na plataforma Imobil Fácil.
✗ Esta é uma propriedade
```

### 7.3 Heading Hierarchy

**Estrutura recomendada:**
```html
<h1>Página Principal</h1>  <!-- Uma por página -->
<h2>Secção Principal</h2>
<h3>Subsecção</h3>
<h4>Detalhe</h4>
```

**NÃO fazer:**
```html
❌ Múltiplos H1
❌ H1 → H3 (pular níveis)
❌ H2 → H4 → H2 (desordenado)
```

### 7.4 Alt Text em Imagens

**Padrão:**
```html
<!-- ✓ Bom -->
<img src="...jpg" alt="Apartamento T3 em Lisboa - Sala de estar com varanda">

<!-- ✗ Ruim -->
<img src="...jpg" alt="Imagem">
<img src="...jpg" alt="">
```

### 7.5 Internal Linking

**Estratégia:**
- Link de propriedade → listagens relevantes
- Link de listagem → homepage
- Breadcrumbs em todo lado
- Anchor text descritivo

---

## 8. Performance & SEO

### 8.1 Core Web Vitals

**Fatores importantes:**
- **LCP** (Largest Contentful Paint): < 2.5s
- **FID** (First Input Delay): < 100ms
- **CLS** (Cumulative Layout Shift): < 0.1

**Otimizações implementadas:**
- Canonical URLs para evitar duplicação
- Cache headers nos sitemas estáticos
- Lazy loading de imagens (recomendado adicionar)

### 8.2 Imagem Optimization

**Recomendações:**
```php
// Lazy loading (HTML5)
<img src="..." loading="lazy">

// Responsive images
<img srcset="small.jpg 480w, medium.jpg 800w, large.jpg 1200w"
     sizes="(max-width: 480px) 100vw, 80vw">

// Next-Gen formats
<picture>
  <source srcset="image.webp" type="image/webp">
  <img src="image.jpg" alt="...">
</picture>
```

---

## 9. Monitoramento & Analytics

### 9.1 Google Search Console

**Configuração:**
1. Adicionar propriedade: https://search.google.com/search-console
2. Submeter sitemap: `/sitemap.xml`
3. Verificar robots.txt: `/robots.txt`
4. Monitorar indexação

**Métricas a acompanhar:**
- Posição média nas buscas
- CTR (Click-Through Rate)
- Impressões
- Erros de crawl

### 9.2 Google Analytics

**Eventos SEO a rastrear:**
- Page views
- Bounce rate
- Session duration
- Conversões (contacto, aluguel, venda)

### 9.3 Ferramentas Recomendadas

| Ferramenta | Propósito |
|-----------|----------|
| Screaming Frog | Audit técnico de SEO |
| Ahrefs | Análise de backlinks |
| SEMrush | Análise competitiva |
| Moz | Keyword research |
| Google PageSpeed | Performance |

---

## 10. Roadmap SEO Futuro

### Fase 2 (Próximas 4 semanas)

- [ ] Implementar lazy loading de imagens
- [ ] Adicionar FAQ schema para perguntas frequentes
- [ ] Criar landing pages por localidade
- [ ] Adicionar hreflang para versões multiidiomas

### Fase 3 (Próximas 8 semanas)

- [ ] Implementar AMP (Accelerated Mobile Pages)
- [ ] Video schema para tours de propriedades
- [ ] Blog com conteúdo SEO-optimizado
- [ ] Backlink strategy & guest posting

### Fase 4 (Longo prazo)

- [ ] Local SEO (Google My Business)
- [ ] Voice search optimization
- [ ] Featured snippets strategy
- [ ] Continuous A/B testing

---

## 11. Troubleshooting

### Propriedade não aparece em resultados

1. Verificar `robots.txt`: Não está bloqueada?
2. Verificar `<meta name="robots">`: Não tem `noindex`?
3. Validar structured data no Google Rich Result Tester
4. Verificar Google Search Console para erros
5. Aguardar re-indexação (até 2 semanas)

### Meta tags não aparecem em redes sociais

1. Validar Open Graph tags: https://www.opengraphcheck.com/
2. Validar Twitter Cards: https://cards-dev.twitter.com/validator
3. Verificar tamanho de imagem (mínimo 1200x630px)
4. Fazer refresh do cache social (por vezes necessário esperar)

### Structured Data não valida

1. Usar Google Rich Result Tester
2. Verificar JSON syntax (vírgulas, aspas)
3. Validar contra schema.org
4. Testar em navegador (DevTools → Network → ver JSON-LD)

---

## 12. Referências

- [Google SEO Starter Guide](https://developers.google.com/search/docs)
- [Schema.org Documentation](https://schema.org/)
- [Open Graph Protocol](https://ogp.me/)
- [Twitter Card Documentation](https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards)
- [Web.dev Core Web Vitals](https://web.dev/vitals/)

---

**Última atualização:** 15 de Maio de 2026  
**Próxima revisão:** 30 de Junho de 2026
