# Documentação Técnica — Solicitações e Negociação

**Última revisão:** 2026-06-08  
**Código de referência:** `app/model/Request.php`, `app/controller/ControllerRequest*.php`, `app/model/RequestChatMessage.php`

## 1. Arquitectura de controllers

| Controller | Responsabilidade |
|------------|------------------|
| `ControllerRequest` | Facade legacy — delega para os módulos abaixo |
| `ControllerRequestIntake` | Criar solicitação a partir de imóvel |
| `ControllerRequestWorkflow` | Transições de estado, pagamento, disputa |
| `ControllerRequestChat` | Mensagens e threads de chat |
| `ControllerDashboardRequests` | Listagens no painel |

Trait partilhado: `RequestControllerSupport` (comissão, mensagens de sistema, helpers).

## 2. Criação de solicitação

Rota: `POST /request/store` (autenticado, CSRF)

Pré-condições:
- Conta activa (`canSubmitPropertyRequest`)
- Imóvel `disponivel`
- Sem bloqueio de comissão (`ClassCommissionGuard`)
- Utilizador não é o proprietário do imóvel

Efeitos na criação (`Request::create`):
- `status = em_contacto`
- `next_followup_at` = +7 dias
- `attribution_expires_at` = +90 dias
- Thread de chat criada
- Notificação ao proprietário/afiliado

## 3. Estados e transições

Ver modelo completo em [REQUESTS_FECHO_PAGAMENTO_ROADMAP.md](REQUESTS_FECHO_PAGAMENTO_ROADMAP.md) e regras operacionais em [REGULAMENTO_OPERACIONAL_COMISSOES.md](REGULAMENTO_OPERACIONAL_COMISSOES.md).

Resumo:
```
em_contacto → fechado_ganho | cancelado | expirado (auto)
fechado_ganho → em_disputa (contestação)
em_disputa → fechado_ganho | cancelado (moderação)
```

Constantes: `AUTO_EXPIRE_DAYS = 30`, `DISPUTE_WINDOW_DAYS = 30`.

## 4. Fluxo de fecho e pagamento

1. **Proprietário** marca `fechado_ganho`
2. Sistema publica mensagem de visita da plataforma no chat + notificação `request_closing_won_platform_visit`
3. **Interessado** visita imóvel (processo operacional) e declara pagamento
4. **Proprietário** confirma recebimento → comissão criada
5. Qualquer parte pode **contestar** → `em_disputa`

Rotas POST principais:
- `request/updateStatus/{id}` — mudança de estado comercial
- `request/confirmClosing/{id}` — interessado declara pagamento
- `request/confirmPaymentReceipt/{id}` — proprietário confirma recebimento
- `request/contestClosing/{id}` / `request/contestPayment/{id}` — contestação

## 5. Chat de negociação

Modelos: `RequestChatThread`, `RequestChatMessage`

### Política de contacto
Na primeira abertura do chat, mensagem de sistema automática:
> É proibido partilhar informações de contacto (telefone, e-mail, redes sociais...) através deste chat.

Marker interno: `[[policy:negotiation_contact]]`

### Mensagens de sistema
Eventos de workflow geram mensagens automáticas (mudança de estado, visita da plataforma, etc.).

### SLA de interacção
`Request::touchLastInteraction()` e `RequestChatThread::touch()` actualizam `last_interaction_at` — usado na expiração automática.

Painel: `dashboard/requestChat/{id}` (suporte e partes envolvidas).

## 6. Disputas

Painel: `dashboard/disputes`, `dashboard/dispute_detail/{id}`

Estados `dispute_status`: `nenhuma` → `aberta` → `em_analise` → `julgada_procedente` | `julgada_improcedente`

Resolução pela moderação pode:
- Restaurar `fechado_ganho` ou `cancelado`
- Consolidar fecho financeiro (`consolidateFinancialClosingByModerator`)

## 7. SLA automático

Script: `scripts/requests_sla_scheduler.php` (cada 1h)

1. `Request::autoExpireOpenRequests()` — `em_contacto` inactivo há 30 dias → `expirado`
2. `Request::getDueSlaAlerts()` — lembretes `request_sla_reminder` + renovação `next_followup_at`

## 8. Permissões por perfil

| Acção | Proprietário | Interessado | Moderação |
|-------|-------------|-------------|-----------|
| Marcar fechado_ganho | ✓ | — | — (excepto resolver disputa) |
| Cancelar em_contacto | ✓ | ✓ | — |
| Declarar pagamento | — | ✓ | — |
| Confirmar recebimento | ✓ | — | — |
| Contestar | ✓ | ✓ | — |
| Resolver disputa | — | — | ✓ |

Gestores de solicitação (`requests.manage`): equipa suporte vê todas as solicitações.

## 9. Notificações

Catálogo: [NOTIFICACOES_OPERACIONAIS.md](NOTIFICACOES_OPERACIONAIS.md)

## 10. Views principais

- `app/view/dashboard/requests/Main.php` — lista e acções por perfil
- `app/view/dashboard/request_chat/Main.php` — interface de chat
- `app/view/dashboard/dispute_detail/Main.php` — resolução de disputa
