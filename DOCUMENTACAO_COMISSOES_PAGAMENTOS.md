# Documentação Técnica — Comissões e Pagamentos

**Última revisão:** 2026-06-08  
**Código de referência:** `app/model/Commission.php`, `app/services/CommissionSettlementService.php`, `app/model/PaymentTransaction.php`

## 1. Origem da comissão

A comissão nasce **apenas** quando:
1. Solicitação em `fechado_ganho`
2. Proprietário confirma recebimento (`payment_confirmation_status = confirmado_proprietario`)
3. Não existe comissão prévia para o `request_id`

Alternativa: resolução de disputa pela moderação (`Request::consolidateFinancialClosingByModerator`).

Criação: `Commission::createFromRequest()` — chamada em `RequestControllerSupport` após confirmação.

Regras operacionais: [REGULAMENTO_OPERACIONAL_COMISSOES.md](REGULAMENTO_OPERACIONAL_COMISSOES.md).

## 2. Estados da comissão (`commissions.status`)

| Estado | Descrição |
|--------|-----------|
| `pendente` | Obrigação emitida, aguarda pagamento do proprietário |
| `pago` | Validada pelo financeiro |
| `cancelado_autorizado` | Cancelamento formal com auditoria |

## 3. Pagamento pelo proprietário (`owner_payment_status`)

| Estado | Descrição |
|--------|-----------|
| `nenhum` | Sem comprovativo submetido |
| `enviado` | Comprovativo enviado, aguarda validação |
| `aprovado` | Financeiro validou → `commissions.status = pago` |
| `rejeitado` | Comprovativo rejeitado, proprietário pode reenviar |

Fluxo:
1. Proprietário submete comprovativo (método + canal + referência)
2. Financeiro aprova via `CommissionSettlementService::approveOwnerPayment()`
3. Ou rejeita via `rejectOwnerPayment()`

Painel: `dashboard/payments` (permissão `payments.manage`).

## 4. Repasse ao afiliado (`affiliate_payout_status`)

| Estado | Descrição |
|--------|-----------|
| `nenhum` | Sem afiliado válido na solicitação |
| `pendente` | Aguarda pagamento ao afiliado |
| `pago` | Repasse concluído |

Conta de destino: `UserPaymentAccount::getDefaultActiveForUser($affiliateId)`.

## 5. Bloqueios por inadimplência

`ClassCommissionGuard` bloqueia acções comerciais (ex.: nova solicitação) quando o proprietário tem comissões vencidas.

Tipos de bloqueio (`Commission::OVERDUE_BLOCK_*`):
- `pagamento_pendente` — comissão vencida sem comprovativo
- `aguardando_validacao` — comprovativo em análise

## 6. Hub de pagamentos

### Métodos e canais
- `payment_methods` — tipos de pagamento (transferência, depósito, etc.)
- `payment_channels` — canais/contas da plataforma
- Gestão: controllers `ControllerPaymentMethods`, `ControllerPaymentChannels` (super_admin)

### Transacções (`payment_transactions`)
Registo contabilístico de movimentos:
- `commission_owner_payment` — pagamento do proprietário
- `system_commission` — receita da plataforma
- Outros tipos conforme integração

Scheduler: `scripts/commission_scheduler.php` — backfill de transacções em falta e marcação de vencidas (cada 6h).

## 7. Subscrições

Modelo: `UserSubscription`, `SubscriptionPlan`

Ciclo automático (`subscription_scheduler.php`, cada 1h):
- Emissão de facturas de renovação
- Downgrade por expiração
- Estatísticas: `invoiced`, `skipped`, `failed`, `downgraded`

Painel: `dashboard/subscriptions` (super_admin).

## 8. Comissão do sistema vs afiliado

Ao criar comissão, o valor total divide-se:
- `amount` — total devido pelo proprietário
- `system_amount` — parte da plataforma
- `affiliate_amount` — parte do afiliado (se aplicável)

Política comercial activa via settings e planos.

## 9. Auditoria

Todas as confirmações/rejeições geram:
- Entrada em `logs` (`Log::create`)
- Notificação às partes (`Notification::*`)
- Transacção em `payment_transactions` quando aplicável

## 10. Rotas financeiras principais

| Rota | Perfil |
|------|--------|
| `dashboard/payments` | financeiro |
| `dashboard/confirmPayment/{id}` | financeiro |
| `dashboard/cancelPayment/{id}` | financeiro |
| `payment_transactions` | financeiro |
| `dashboard/paymentMethods` | super_admin |

Todas as mutações: **POST + CSRF**.
