# Documentação SEO — Imobil Fácil

**Última atualização:** 3 de Junho de 2026  
**Versão:** 2.0  
**Mercado:** Angola (pt / pt-AO, moeda AOA)

---

## 1. Visão geral

A plataforma expõe SEO técnico e semântico através de:

| Camada | Ficheiro | Função |
|--------|----------|--------|
| Configuração central | `src/classes/ClassSEO.php` | Constantes, schemas JSON-LD, robots.txt, sanitização |
| Renderização | `src/classes/ClassRender.php` | Title, description, OG, canonical, robots, structured data |
| Layout | `app/view/Layout.php` | Meta tags no `<head>` |
| Defaults automáticos | `src/classes/LayoutPresenter.php` | `ClassSEO::primeLayoutSeo()` em cada página |
| Sitemap / robots | `app/controller/ControllerSitemap.php` | XML e `robots.txt` dinâmicos |
| Dados | `app/model/Property.php` | `getPublicSitemapEntries()` |

### Constantes principais (`ClassSEO`)

```php
ClassSEO::SITE_NAME           // Imobil Fácil
ClassSEO::SITE_DESCRIPTION    // Plataforma angolana...
ClassSEO::SITE_LANGUAGE       // pt
ClassSEO::SITE_LOCALE         // pt_AO
ClassSEO::SITE_COUNTRY        // AO
ClassSEO::DEFAULT_CURRENCY    // AOA
ClassSEO::DEFAULT_KEYWORDS    // imóveis angola, ...
ClassSEO::defaultOgImage()    // public/img/logo-imobilfacil.png
```

**Produção:** defina `APP_URL` no `.env` (ex.: `https://www.imobilfacil.ao/`) para canonical, OG, sitemap e robots usarem o domínio correcto.

---

## 2. Meta tags e Open Graph

Cada página pública deve configurar no controller:

```php
$render->setTitle('Título — Imobil Fácil');
$render->setDescription('Descrição até ~160 caracteres.');
$render->setKeywords(ClassSEO::DEFAULT_KEYWORDS); // ou keywords específicas
$render->setCanonical(rtrim(DIRPAGE, '/') . '/caminho');
$render->setOgTitle('Título social');
$render->setOgDescription('Texto para redes sociais');
$render->setOgImage(ClassSEO::propertyImageUrl($property)); // ou defaultOgImage()
$render->setOgType('website'); // ou product na ficha do imóvel
```

O `Layout.php` inclui:

- `meta robots` (index ou noindex conforme área)
- `link rel="canonical"`
- Open Graph (`og:title`, `og:description`, `og:url`, `og:image`, `og:locale`)
- Twitter Card (`summary_large_image`)

### Áreas privadas (noindex automático)

Prefixos em `ClassSEO::PRIVATE_VIEW_PREFIXES`:

- `auth/`, `dashboard/`, `notification/`, `property/moderate`, `errors/`

Robots: `noindex, nofollow`. Não definir canonical em páginas privadas.

---

## 3. Structured Data (JSON-LD)

| Método | Tipo schema.org | Uso |
|--------|-----------------|-----|
| `getWebSiteSchema()` | WebSite + SearchAction | Homepage |
| `getOrganizationSchema()` | RealEstateAgent | Homepage / marca |
| `getPropertySchema($property)` | Accommodation + Offer | Ficha `/property/{id}` |
| `getCollectionPageSchema(...)` | CollectionPage + ItemList | Listagem `/properties` |
| `getBreadcrumbSchema($items)` | BreadcrumbList | Navegação hierárquica |

### Imóvel — campos reais da BD

- Localização: `location` (não `city`/`state`)
- Imagem: JSON `images` via `ClassSEO::propertyImageUrl()`
- Moeda: `currency` ou fallback `AOA`
- Vendedor: `owner_name` quando disponível no `Property::find()`

### Adicionar ao render

```php
$render->addStructuredData(ClassSEO::getPropertySchema($property));
```

Vários blocos são agrupados automaticamente em `@graph` pelo `ClassRender::addStructuredData()`.

---

## 4. robots.txt

- **URL:** `/robots.txt` (gerado por `ControllerSitemap::robots()`)
- **Ficheiro estático removido** de `public/robots.txt` para evitar `localhost` fixo.

Bloqueia: dashboard, auth, API, ficheiros, parâmetros `sort`, `filter`, `cursor`, `ref`.

**Permite** paginação: `/properties?page=2` (regra antiga `Disallow: /*?*page=` foi removida).

Sitemap referenciado: `{APP_URL}sitemap`

---

## 5. Sitemap XML

| URL | Conteúdo |
|-----|----------|
| `/sitemap` | Índice (pages + properties) |
| `/sitemap/pages` | Home, `/properties`, `/featured`, `/cookies` |
| `/sitemap/properties` | Imóveis com status `disponivel`, `vendido`, `alugado` |

Implementação: `Property::getPublicSitemapEntries()` (máx. 50 000 URLs).

Submeter no Google Search Console: `https://seu-dominio/sitemap`

---

## 6. Páginas já configuradas

| Página | Controller | Indexável |
|--------|------------|-----------|
| Home | `ControllerHome` | Sim |
| Listagem | `ControllerProperty::index` | Sim |
| Ficha imóvel | `ControllerProperty::show` | Sim (público) |
| Destaques | `ControllerProperty::featured` | Sim |
| Agência | `ControllerProperty::agency` | Sim |
| Cookies | `ControllerLegal::cookies` | Sim |
| 404 | `Controller404` | Não (`noindex`) |
| Login / registo | `ControllerAuth` | Não (prefixo `auth/`) |
| Dashboard | `ControllerDashboard` | Não |

---

## 7. Checklist para novas páginas públicas

1. `setTitle` único (50–60 caracteres úteis)
2. `setDescription` com `ClassSEO::excerptFromText()` se o texto vier da BD
3. `setCanonical` sem parâmetros de tracking (`utm_*`, `ref`, etc.)
4. OG image absoluta (mín. 200×200; ideal 1200×630)
5. Breadcrumb JSON-LD quando houver hierarquia
6. Incluir URL em `ControllerSitemap::pages()` se for página estática importante
7. Confirmar `APP_URL` em produção

---

## 8. Sanitização e canonical global

- `sanitizeTitle()` / `sanitizeDescription()` usam `mb_strlen` / `mb_substr` (UTF-8)
- `getCanonicalUrl($explicit)` — se o render tiver `setCanonical()`, o Layout usa esse valor para `og:url`
- Sem canonical explícito: mantém só `?page=` quando > 1

---

## 9. Cache de página

Páginas em cache (`PageCache`) guardam HTML com meta tags antigas até expirar o TTL. Após alterações SEO:

- Aguardar TTL (`page_cache_home_ttl_seconds`, `page_cache_property_list_ttl_seconds`), ou
- Limpar ficheiros em `storage/cache/`

---

## 10. Ferramentas de validação

- [Google Rich Results Test](https://search.google.com/test/rich-results)
- [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/)
- Google Search Console (propriedade com domínio de produção)
- `curl -s https://seu-dominio/robots.txt`
- `curl -s https://seu-dominio/sitemap`

---

## 11. Histórico de alterações (v2.0)

- Alinhamento Angola: locale `pt_AO`, país `AO`, moeda `AOA`
- Sitemap corrigido (`Property::getPublicSitemapEntries`)
- `robots.txt` dinâmico com `APP_URL`
- Schema de imóvel: `Accommodation` + campos `location` / `images`
- `noindex` automático em áreas privadas
- Remoção de referências Portugal / EUR / `og-home.jpg` inexistente
- Homepage: schema `WebSite` + imagem OG oficial
