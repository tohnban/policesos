# Requests: Fluxo de Fecho com Confirmacao de Pagamento

**Status:** implementado (referencia tecnica)  
**Ultima revisao:** 2026-06-08  
**Codigo de referencia:** `app/model/Request.php`, `app/controller/ControllerRequestWorkflow.php`

## Objetivo
Separar no ciclo de solicitacoes:
- fecho comercial (acordo entre as partes)
- liquidacao financeira (pagamento efetivamente recebido)

A comissao nasce apenas apos confirmacao de recebimento pelo proprietario.

## Resumo do modelo em producao
O fluxo anterior (fecho consolidado na marcacao de `fechado_ganho`) foi substituido por trilhas independentes de fecho e pagamento. O proprietario declara o acordo comercial; o interessado declara pagamento apos visita da plataforma; o proprietario confirma recebimento para gerar comissao.

## Modelo de estados

### 1. Ciclo comercial (`status`)
- `em_contacto` — estado inicial na criacao
- `fechado_ganho`
- `cancelado`
- `expirado`
- `em_disputa`

### 2. Confirmacao de fecho (`closing_confirmation_status`)
- `pendente` — aguarda declaracao de pagamento ou resolucao
- `confirmado` — fecho financeiro consolidado
- `contestada` — divergencia registada

### 3. Ciclo de pagamento (`payment_confirmation_status`)
- `pendente`
- `declarado_comprador`
- `confirmado_proprietario`
- `contestado`

### 4. Disputa (`dispute_status`)
- `nenhuma` | `aberta` | `em_analise` | `julgada_procedente` | `julgada_improcedente`

## Matriz de transicoes

### A. Transicoes comerciais
1. `em_contacto -> fechado_ganho`
- Ator: proprietario
- Efeito: `closing_confirmation_status = pendente`, `payment_confirmation_status = pendente`, mensagem de visita da plataforma no chat, notificacao `request_closing_won_platform_visit`

2. `em_contacto -> cancelado`
- Ator: proprietario ou interessado

3. `em_contacto -> expirado`
- Ator: scheduler (`AUTO_EXPIRE_DAYS = 30`, sem interacao)

4. `fechado_ganho -> em_disputa`
- Ator: interessado ou proprietario (contestacao de pagamento)

5. `em_disputa -> fechado_ganho|cancelado`
- Ator: moderacao (`Request::resolveDispute`)

### B. Transicoes de pagamento
1. `pendente -> declarado_comprador`
- Ator: interessado
- Pre-condicao: `status = fechado_ganho`, `closing_confirmation_status = pendente`

2. `declarado_comprador -> confirmado_proprietario`
- Ator: proprietario
- Pre-condicao: `status = fechado_ganho`
- Efeito: `closing_confirmation_status = confirmado`, criacao de comissao

3. `pendente|declarado_comprador -> contestado`
- Ator: proprietario ou interessado
- Efeito: `status = em_disputa`, encaminhamento para moderacao

## Permissoes por perfil

1. Interessado (comprador)
- Pode cancelar em `em_contacto`
- Pode declarar pagamento apos fecho ganho
- Pode contestar fecho ou pagamento
- Nao pode confirmar recebimento

2. Proprietario (vendedor)
- Pode marcar `fechado_ganho` ou `cancelado` em `em_contacto`
- Pode confirmar recebimento
- Pode contestar declaracao de pagamento

3. Moderacao/Compliance
- Resolve disputas (`fechado_ganho` ou `cancelado`)
- Pode consolidar fecho financeiro apos disputa (`consolidateFinancialClosingByModerator`)

## Regra de comissao
Criacao de comissao ocorre quando:
- `status = fechado_ganho`
- `payment_confirmation_status = confirmado_proprietario`
- `closing_confirmation_status = confirmado`
- ainda nao existe comissao para `request_id`

Alternativa: resolucao de disputa a favor de fecho ganho com consolidacao financeira pela moderacao.

## Eventos e auditoria
Registados em log, chat de sistema e notificacoes:
1. Fecho comercial declarado (+ visita da plataforma)
2. Pagamento declarado pelo interessado
3. Recebimento confirmado pelo proprietario
4. Contestacao de pagamento
5. Encaminhamento e resolucao de disputa

## Notificacoes
Catalogo completo em `NOTIFICACOES_OPERACIONAIS.md`. Tipos principais:
- `request_closing_won_platform_visit` — fecho ganho com visita da plataforma
- `request_payment_declared`
- `request_payment_receipt_confirmed`
- `request_payment_contested`
- `request_status_updated` — demais transicoes de `status`
- `request_sla_reminder`

## Checklist tecnico (estado actual)

- [x] Colunas de trilha de pagamento e disputa em `requests`
- [x] Constantes e validadores em `Request.php`
- [x] Acoes separadas em `ControllerRequestWorkflow.php`
- [x] Comissao no gatilho de recebimento confirmado
- [x] Views com chips de pagamento e acoes por perfil
- [x] Notificacoes de pagamento e visita da plataforma
- [x] Scheduler SLA (`requests_sla_scheduler.php`, 30 dias)
- [x] Politica de contacto no chat na primeira abertura
- [ ] Cobertura de testes automatizados do fluxo completo

## Documentacao relacionada
- `REGULAMENTO_OPERACIONAL_COMISSOES.md` — regras operacionais e SLA
- `NOTIFICACOES_OPERACIONAIS.md` — textos padrao de notificacoes
