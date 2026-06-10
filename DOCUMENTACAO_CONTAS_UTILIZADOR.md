# Documentação Técnica — Contas de Utilizador

**Última revisão:** 2026-06-08  
**Código de referência:** `src/classes/UserAccountState.php`, `src/classes/ClassLimitedAccountGuard.php`, `src/classes/ClassAccess.php`

## 1. Camadas de estado

A conta tem três camadas independentes:

### Camada 1 — Moderação (`users.status`)
| Valor | Significado |
|-------|-------------|
| `pendente` | Registo submetido, aguarda aprovação da equipa |
| `ativo` | Conta aprovada |
| `rejeitado` | Dados ou documento precisam de correcção |

Transições (moderação): `pendente` → `ativo` | `rejeitado` (`User::approve`, `User::reject`).

### Camada 2 — Acesso (`UserAccountState::resolveAccessTier`)
Derivado do status + `suspended_until` (não altera `users.status`):

| Tier | Condição | Acesso |
|------|----------|--------|
| `full` | `ativo` e não suspenso | Plataforma completa |
| `onboarding` | `pendente` | Acesso limitado |
| `correction` | `rejeitado` | Acesso limitado + pode corrigir |
| `suspended` | `suspended_until` no futuro | Logout forçado |
| `unknown` | Estado inválido | Bloqueado |

### Camada 3 — Documentos (`documents`)
Compliance de identificação independente do status. A página `dashboard/accountStatus` combina estado da conta com estado dos documentos via `UserAccountState::resolveWithDocument()`.

## 2. Acesso limitado

Implementado em `ClassLimitedAccountGuard::enforce()` (chamado em cada request no `Dispatch`).

### Permitido com conta limitada
- `/`, `/home`, páginas legais (`/cookies`, `/privacidade`, `/termos`)
- Leitura pública: `/properties`, `/featured`, `/property/{id}`
- Auth: login, registo, recuperação, verificação, logout
- `dashboard/accountStatus` — estado da conta e documentos
- `dashboard/update`, `dashboard/resubmitDocument`, `dashboard/submitAccountDocument`
- `profile/update`

### Bloqueado até conta activa
- Criar/editar imóveis, solicitações, chat, comissões, afiliação, pagamentos, etc.

Redirect padrão: `dashboard/accountStatus?error=...`

Mensagens contextuais via `ClassLimitedAccountGuard::redirectLimited($message)` (ex.: ao tentar solicitar visita).

## 3. Quem pode solicitar negócios

`ClassAccess::canSubmitPropertyRequest()`:
- Utilizador autenticado com acesso completo (`hasFullPlatformAccess`)
- **Não** admin/moderação/financeiro/suporte
- **Não** conta pendente ou em correcção

## 4. Perfis administrativos

Papel em `users.role` (não confundir com `users.status`):

| Role | Permissões principais |
|------|----------------------|
| `super_admin` | `*` (todas) |
| `moderador` | moderação de imóveis, utilizadores, documentos |
| `suporte` | solicitações, chats, disputas |
| `financeiro` | pagamentos, transacções |

**Gestão da equipa:** o Admin Total (`super_admin`) cria e restringe contas administrativas em `dashboard/moderateUsers?tab=equipa` (criar, alterar papel, suspender, revogar). Ver secção 7 de [DOCUMENTACAO_ACESSOS_ADMIN.md](DOCUMENTACAO_ACESSOS_ADMIN.md).

Detalhe completo: [DOCUMENTACAO_ACESSOS_ADMIN.md](DOCUMENTACAO_ACESSOS_ADMIN.md).

## 5. Selo de confiança (trust badge)

Estado em `users.trust_badge_status`: `pendente`, `aprovado`, `rejeitado`.

Elegibilidade automática: `ClassTrustBadgeEligibility` (settings `trust_badge_min_won_deals`, `trust_badge_min_account_days`, etc.).

Fluxo: pedido pelo utilizador → moderação aprova/rejeita → impacto na visibilidade e confiança nas listagens.

## 6. Subscrições

Planos geridos via `UserSubscription` e `SubscriptionPlan`. Renovação automática: `scripts/subscription_scheduler.php`. Ver [DOCUMENTACAO_COMISSOES_PAGAMENTOS.md](DOCUMENTACAO_COMISSOES_PAGAMENTOS.md).

## 7. Página de estado da conta

Rota: `dashboard/accountStatus`  
View: `app/view/dashboard/account_status/`

Mostra:
- Label e descrição do estado (`status_label`, `status_description`)
- Tier de acesso (`access_label`)
- Checklist de documentos e acções disponíveis (submeter, corrigir, editar identificação)

## 8. Seed de teste

Contas em `scripts/seed_full_test_data.sql` (senha `Teste@123`):
- `pendente.seed@imobil.local` — conta pendente (testar guard limitado)
- `cliente.seed@imobil.local` — cliente activo
- Ver lista completa no [README.md](README.md)
