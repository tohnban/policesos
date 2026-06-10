# Documentação Técnica — Arquitectura

**Última revisão:** 2026-06-08

## 1. Visão geral

Aplicação PHP MVC com front controller em `public/index.php`. Cada pedido passa por `App\Dispatch`, que tenta primeiro uma rota **declarativa** e, se não houver correspondência, cai no sistema **legacy** baseado em convenção de URL.

```
Browser → public/index.php → App\Dispatch
         ├─ RouteRegistry::match()  → controller + action + middleware
         └─ ClassRoutes (legacy)    → ControllerX + método dinâmico
```

## 2. Rotas declarativas

Ficheiro: `config/routes.php`

Cada entrada define:
- `path` — segmentos URL (suporta `{id}` e path raiz vazio `''`)
- `controller` — nome curto (ex.: `ControllerHome`)
- `action` — método PHP a invocar
- `methods` — verbos HTTP permitidos
- `middleware` — opcional: `auth`, `csrf`, `can:permission.name`, `admin`, `super_admin`

Resolução: `src/classes/RouteRegistry.php` → `ResolvedRoute`.

**Ordem importa:** rotas literais devem preceder rotas com parâmetros dinâmicos (ex.: `property/moderate` antes de `property/{id}`).

Listar rotas registadas:
```powershell
php scripts/list-routes.php
```

Verificar correspondência de um path:
```powershell
php scripts/check-route-match.php GET property/moderate
```

## 3. Dispatch legacy

Quando não há match declarativo, `ClassRoutes` resolve:
- `url[0]` → nome do controller (`property` → `ControllerProperty`)
- `url[1]` → método ou ID numérico
- Parâmetros adicionais passados ao método

Facades legacy mantêm compatibilidade:
- `ControllerProperty` → delega para `ControllerPropertyCatalog`, `ControllerPropertyOwner`, etc.
- `ControllerRequest` → `ControllerRequestIntake`, `ControllerRequestWorkflow`, `ControllerRequestChat`
- `ControllerDashboard` → módulos `ControllerDashboard*`
- `ControllerAuth` → `ControllerAuthSession`, `ControllerAuthRegistration`, etc.

## 4. Middleware e guards

Executados em `app/Dispatch.php` antes da action:

| Camada | Classe | Função |
|--------|--------|--------|
| Conta limitada | `ClassLimitedAccountGuard` | Restringe utilizadores `pendente`/`rejeitado` |
| Comissão vencida | `ClassCommissionGuard` | Bloqueia acções comerciais se comissão em atraso |
| Selo de confiança | `ClassTrustBadgeGuard` | Regras de elegibilidade do badge |
| Rate limit | `ClassRateLimiter` | Limite global POST e por rota |
| CSRF | `ClassCsrf` | Token obrigatório em POST autenticado (legacy + rotas) |
| Permissões | `ClassAccess` | `can:foo.bar` no middleware declarativo |

### Conta limitada (resumo)

Utilizadores em onboarding ou correcção (`pendente`, `rejeitado`) podem:
- Home, páginas legais, leitura pública de imóveis (`/properties`, `/featured`, `/property/{id}`)
- `dashboard/accountStatus` e submissão de documentos

Restantes rotas redireccionam para `dashboard/accountStatus`. Detalhe em [DOCUMENTACAO_CONTAS_UTILIZADOR.md](DOCUMENTACAO_CONTAS_UTILIZADOR.md).

## 5. Camadas da aplicação

```
app/controller/   → HTTP, redirects, autorização
app/model/        → SQL, validação de domínio, transições de estado
app/view/         → HTML/PHP, formulários com ClassCsrf::field()
app/services/     → Orquestração multi-modelo (ex.: CommissionSettlementService)
src/classes/      → Infra partilhada (Auth, Mail, Cache, SEO, Discovery)
config/           → routes.php, config.php, bootstrap
storage/          → logs, cache de página, uploads
scripts/          → schedulers, workers, migrações, auditorias
```

## 6. Cache de página

Classe: `src/classes/PageCache.php`

- Activado via setting `page_cache_enabled` (default: 1)
- Apenas GET, utilizadores **não** autenticados
- Namespaces usados: `home`, `property_list:*`, `property_show:*`, `featured_list:*`
- TTL configurável por controller (via `ClassSettings`)
- Invalidação: `PageCache::flush()` ou `PageCache::invalidate($namespace)`

Motivo de não cachear autenticados: HTML inclui CSRF, favoritos e UI personalizada.

## 7. Segurança

### CSRF
- `ClassCsrf::field()` nas views
- `ClassCsrf::enforcePostToken()` nos controllers POST
- `Dispatch::enforceLegacyAuthenticatedCsrf()` para rotas legacy

### CSP
Definida em `public/index.php`. O smoke test rejeita `unsafe-inline` em `script-src` e blocos `<script>` inline nas views (excepto JSON-LD).

### Sessão e auth
- `ClassAuth` — login, logout, `user()` na sessão
- `ClassSession` — `visitor_key` para ranking comportamental anónimo
- Sessão expira conforme `SESSION_LIFETIME` no `.env`

### Ficheiros protegidos
Rota `file/serve` (middleware `auth`) serve uploads com autorização.

### Logging
- Erros não tratados → `storage/logs/app.log` (JSON)
- `request_id` via header `X-Request-Id`
- Acções admin em tabela `logs` via `App\model\Log`

## 8. Settings em runtime

Chaves importantes na tabela `settings` (lidas por `ClassSettings`):

| Chave | Uso |
|-------|-----|
| `page_cache_enabled` | Cache HTML público |
| `behavior_ranking_enabled` | Ranking comportamental |
| `behavior_*` | Pesos e limites do DiscoveryEngine |
| `mail_queue_*` | Fila de email |
| `rate_limit_*` | Rate limiting |
| `trust_badge_*` | Elegibilidade do selo |

Ver também [DOCUMENTACAO_RANKING_COMPORTAMENTAL.md](DOCUMENTACAO_RANKING_COMPORTAMENTAL.md).

## 9. Auditorias automáticas

Integradas em `scripts/regression-smoke.ps1`:

| Script | Verifica |
|--------|----------|
| `audit-legacy-routes.php` | Rotas legacy vs declarativas |
| `audit-view-routes.php` | Links em views apontam para rotas válidas |
| `audit-controller-imports.php` | `use` statements em controllers |
| `audit-controller-classes.php` | Classes referenciadas existem |
| `audit-route-shadows.php` | Rotas literais obscurecidas por dinâmicas |
| `audit-duplicate-routes.php` | Entradas duplicadas em routes.php |

## 10. Testes e CI

```powershell
pwsh -File scripts/regression-smoke.ps1              # estático
pwsh -File scripts/regression-smoke.ps1 -RunHttp     # + HTTP local
pwsh -File scripts/pre-release.ps1 -BaseUrl http://localhost
pwsh -File scripts/quality-check.ps1                 # PHP-CS-Fixer + PHPStan
```

Workflow GitHub: `.github/workflows/pre-release-smoke.yml`
