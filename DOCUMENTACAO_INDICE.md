# Índice da Documentação — Imobil

Mapa central de toda a documentação técnica e operacional do projeto.

**Última revisão:** 2026-06-08 (gestão de equipa admin, correcção registo)

---

## Arquitectura e infraestrutura

| Documento | Conteúdo |
|-----------|----------|
| [DOCUMENTACAO_ARQUITETURA.md](DOCUMENTACAO_ARQUITETURA.md) | MVC, rotas declarativas vs legacy, middleware, guards, cache, segurança |
| [DOCUMENTACAO_OPERACAO.md](DOCUMENTACAO_OPERACAO.md) | Cron/workers, migrações, logging, smoke tests, variáveis de ambiente |
| [DOCUMENTACAO_PRODUCAO.md](DOCUMENTACAO_PRODUCAO.md) | Deploy, checklist go-live, hardening, cron em produção |
| [DOCUMENTACAO_API_V1.md](DOCUMENTACAO_API_V1.md) | API REST v1, tokens, scopes, endpoints |

## Domínio de negócio

| Documento | Conteúdo |
|-----------|----------|
| [DOCUMENTACAO_CONTAS_UTILIZADOR.md](DOCUMENTACAO_CONTAS_UTILIZADOR.md) | Estados de conta, acesso limitado, documentos, suspensão |
| [DOCUMENTACAO_IMOVEIS.md](DOCUMENTACAO_IMOVEIS.md) | Ciclo de vida do imóvel, moderação, destaque, selo de confiança, afiliação |
| [DOCUMENTACAO_COMISSOES_PAGAMENTOS.md](DOCUMENTACAO_COMISSOES_PAGAMENTOS.md) | Comissões, hub de pagamentos, subscrições, transacções |
| [DOCUMENTACAO_SOLICITACOES.md](DOCUMENTACAO_SOLICITACOES.md) | Solicitações, chat, disputas, controllers e rotas |
| [REQUESTS_FECHO_PAGAMENTO_ROADMAP.md](REQUESTS_FECHO_PAGAMENTO_ROADMAP.md) | Matriz de estados e transições de fecho/pagamento |
| [REGULAMENTO_OPERACIONAL_COMISSOES.md](REGULAMENTO_OPERACIONAL_COMISSOES.md) | Regras operacionais, SLA, disputas, penalidades |

## Experiência e descoberta

| Documento | Conteúdo |
|-----------|----------|
| [UX_MELHORIAS_SPRINTS.md](UX_MELHORIAS_SPRINTS.md) | Plano de sprints UX — feed inbox, mobile, modais, tab bar |
| [DOCUMENTACAO_RANKING_COMPORTAMENTAL.md](DOCUMENTACAO_RANKING_COMPORTAMENTAL.md) | Ranking por comportamento (autenticado e anónimo) |
| [DOCUMENTACAO_SEO.md](DOCUMENTACAO_SEO.md) | SEO, schema.org, sitemap, meta tags |

## Administração e notificações

| Documento | Conteúdo |
|-----------|----------|
| [DOCUMENTACAO_ACESSOS_ADMIN.md](DOCUMENTACAO_ACESSOS_ADMIN.md) | Perfis admin, permissões, gestão da equipa (criar/suspender/revogar) |
| [NOTIFICACOES_OPERACIONAIS.md](NOTIFICACOES_OPERACIONAIS.md) | Textos e tipos de notificações de solicitações |
| [NOTIFICACOES_SISTEMA_COMPLETO.md](NOTIFICACOES_SISTEMA_COMPLETO.md) | API de notificações, arquivo, agrupamento |

## Início rápido

| Recurso | Conteúdo |
|---------|----------|
| [README.md](README.md) | Instalação, estrutura, seed de teste, CI |
| `database_schema.sql` | Schema base |
| `scripts/seed-full-test-data.ps1` | Dados de teste completos |
| `scripts/regression-smoke.ps1` | Validação pré-commit |

---

## Convenções do código

- **Controllers:** `app/controller/` — fluxo HTTP, autorização, redirecionamentos
- **Models:** `app/model/` — acesso a dados e regras de domínio
- **Views:** `app/view/` — templates PHP (CSRF em todos os POST)
- **Infraestrutura:** `src/classes/` — rotas, sessão, auth, cache, mail
- **Serviços:** `app/services/` — lógica transversal (ex.: liquidação de comissões)
- **Rotas:** `config/routes.php` — rotas declarativas; restantes via `ClassRoutes` legacy
- **Front controller:** `public/index.php`
- **Legado histórico:** pasta `antigo/` — não usar em produção

## Onde procurar no código

| Tema | Ficheiros principais |
|------|---------------------|
| Dispatch e rotas | `app/Dispatch.php`, `config/routes.php`, `src/classes/RouteRegistry.php` |
| Permissões | `src/classes/ClassAccess.php` |
| Equipa administrativa | `app/controller/ControllerDashboardModeration.php`, aba `tab=equipa`, `User::createAdministrativeUser()` |
| Conta limitada | `src/classes/ClassLimitedAccountGuard.php`, `src/classes/UserAccountState.php` |
| Solicitações | `app/model/Request.php`, `app/controller/ControllerRequest*.php` |
| Comissões | `app/model/Commission.php`, `app/services/CommissionSettlementService.php` |
| Imóveis | `app/model/Property.php`, `app/controller/ControllerProperty*.php` |
| Descoberta | `src/classes/DiscoveryEngine.php` |
| Notificações | `app/model/Notification.php` |
| Settings runtime | `src/classes/ClassSettings.php`, tabela `settings` |
