# Documentação Técnica — Imóveis

**Última revisão:** 2026-06-08  
**Código de referência:** `app/model/Property.php`, `app/controller/ControllerProperty*.php`

## 1. Estados do imóvel (`properties.status`)

| Estado | Descrição |
|--------|-----------|
| `pendente` | Submetido pelo proprietário, aguarda moderação |
| `em_analise` | Em revisão pela equipa |
| `disponivel` | Aprovado e visível no catálogo público |
| `rejeitado` | Rejeitado na moderação |
| `vendido` | Transacção concluída (venda) |
| `alugado` | Transacção concluída (aluguer) |

Constantes em `Property::MODERATION_STATUS_*`.

### Transições de moderação
```
pendente → em_analise   (startAnalysis — moderador)
em_analise → disponivel (approve)
em_analise → rejeitado  (reject)
```

Rota de moderação: `GET /property/moderate` (permissão `properties.moderate`)  
Controller: `ControllerPropertyModeration`

Acções POST (todas com CSRF):
- `property/startAnalysis/{id}`
- `property/approve/{id}`
- `property/reject/{id}`

## 2. Catálogo público

Controllers: `ControllerPropertyCatalog`

| Rota | Função |
|------|--------|
| `/properties` | Listagem com filtros e paginação server-side |
| `/property/{id}` | Detalhe do imóvel |
| `/featured` | Imóveis em destaque (paginação) |
| `/agency/{id}` | Página da agência/afiliado |

Filtros validados em `normalizePropertyListFilters()` (whitelist anti-injection).

Listagens públicas usam `DiscoveryEngine` quando ranking comportamental está activo. Ver [DOCUMENTACAO_RANKING_COMPORTAMENTAL.md](DOCUMENTACAO_RANKING_COMPORTAMENTAL.md).

Cache de página (`PageCache`) para anónimos em listagem, detalhe e featured.

## 3. Gestão pelo proprietário

Controller: `ControllerPropertyOwner`

- Criar e editar imóveis (conta activa + permissões)
- Definir `purpose`: `venda`, `aluguer_curto`, `aluguer_longo`
- Modos de afiliação (`affiliate_approval_mode`):
  - `manual` — aprova cada afiliado
  - `auto` — aprovação automática
  - `disabled` — sem afiliados

Novo imóvel entra em `pendente` até moderação.

## 4. Afiliação a imóveis

Tabela: `property_affiliates`

Um utilizador pode ser afiliado de um imóvel com status `ativo`. O afiliado associado à solicitação (`requests.affiliate_id`) determina comissão de afiliação no fecho.

## 5. Destaque (boost)

Tabela: `property_boost_requests`

Fluxo:
1. Proprietário solicita destaque (`featured = 1` após aprovação)
2. Moderação aprova/rejeita em `property/moderate` (secção boosts)
3. Expiração automática: `scripts/boost_expiration_scheduler.php` (cada 1h)

## 6. Imóvel vendido/alugado

Marcado via fluxo de solicitação (`fechado_ganho`) ou acção manual do proprietário. Imóveis `vendido`/`alugado` saem do catálogo activo mas podem permanecer no sitemap conforme configuração SEO.

## 7. Sitemap e SEO

Controller: `ControllerSitemap`

- `/sitemap`, `/sitemap/pages`, `/sitemap/properties`
- `/robots.txt`
- Imóveis no sitemap: `disponivel`, `vendido`, `alugado`

Detalhe SEO: [DOCUMENTACAO_SEO.md](DOCUMENTACAO_SEO.md).

## 8. Eventos comportamentais

Registados pelo `DiscoveryEngine` na interacção:
- `view` — abrir detalhe
- `favorite` — favoritar
- `request` — criar solicitação

Alimentam reordenação em home, listagem e featured.

## 9. Workers relacionados

| Worker | Função |
|--------|--------|
| `notify_new_property_worker.php` | Notifica subscritores/pesquisas guardadas de novo imóvel |
| `image_queue_worker.php` | Processamento assíncrono de imagens |

Registar no Task Scheduler se usar notificações de novos imóveis ou fila de imagens (não incluídos no `cron_setup.ps1` base).

## 10. Permissões

| Acção | Permissão |
|-------|-----------|
| Moderar imóveis | `properties.moderate` |
| Aprovar/rejeitar boost | `properties.moderate` |
| Publicar imóvel | conta activa + não admin-only |
