# Requests: Fluxo de Fecho com Confirmacao de Pagamento

## Objetivo
Evoluir o ciclo de solicitacoes para separar:
- fecho comercial (acordo entre as partes)
- liquidacao financeira (pagamento efetivamente recebido)

A comissao deve nascer apenas apos confirmacao de recebimento pelo proprietario.

## Problema Atual
No fluxo atual, o proprietario marca `fechado_ganho` e o solicitante confirma/contesta o fecho.
Risco: o fecho pode ser consolidado antes da confirmacao de recebimento financeiro.

## Modelo Proposto
Manter `status` para ciclo comercial principal e adicionar uma trilha de pagamento com estado proprio.

### 1. Ciclo comercial (status)
- `em_contacto`
- `fechado_ganho`
- `cancelado`
- `expirado`
- `em_disputa`

### 2. Ciclo de pagamento (novo campo)
`payment_confirmation_status`:
- `pendente`
- `declarado_comprador`
- `confirmado_proprietario`
- `contestado`

## Matriz de Transicoes

### A. Transicoes comerciais
1. `em_contacto -> fechado_ganho`
- Ator: proprietario
- Efeito: abre ciclo de pagamento (`payment_confirmation_status = pendente`)

2. `em_contacto -> cancelado`
- Ator: proprietario ou solicitante (conforme fluxo atual)

3. `fechado_ganho -> em_disputa`
- Ator: solicitante, proprietario ou moderacao (conforme regras de disputa)

4. `em_disputa -> fechado_ganho|cancelado`
- Ator: moderacao/compliance

### B. Transicoes de pagamento
1. `pendente -> declarado_comprador`
- Ator: solicitante
- Pre-condicao: `status = fechado_ganho`
- Dados opcionais: comprovativo

2. `declarado_comprador -> confirmado_proprietario`
- Ator: proprietario
- Pre-condicao: `status = fechado_ganho`
- Efeito: consolidar obrigacao financeira/comissao

3. `pendente|declarado_comprador -> contestado`
- Ator: proprietario ou solicitante
- Efeito: encaminhar para disputa quando aplicavel

## Permissoes por Perfil

1. Solicitante (comprador)
- Pode declarar pagamento
- Pode contestar declaracao de fecho/pagamento
- Nao pode confirmar recebimento

2. Proprietario (vendedor)
- Pode declarar fecho comercial (`fechado_ganho`)
- Pode confirmar recebimento
- Pode contestar declaracao de pagamento

3. Moderacao/Compliance
- Nao deve declarar fecho inicial fora da disputa
- Pode decidir casos em disputa e forcar resolucao

## Regra de Comissao
Criacao de comissao deve ocorrer apenas quando:
- `status = fechado_ganho`
- `payment_confirmation_status = confirmado_proprietario`
- ainda nao existe comissao para `request_id`

## Eventos e Auditoria
Registrar em log e chat de sistema:
1. Fecho comercial declarado
2. Pagamento declarado pelo comprador
3. Recebimento confirmado pelo proprietario
4. Contestacao de pagamento
5. Encaminhamento para disputa

## Notificacoes Novas
1. `request_payment_declared`
- Destino: proprietario
- Mensagem: comprador declarou pagamento

2. `request_payment_receipt_confirmed`
- Destino: solicitante
- Mensagem: proprietario confirmou recebimento

3. `request_payment_contested`
- Destino: contraparte + moderacao
- Mensagem: pagamento contestado, possivel disputa

## Checklist Tecnico de Implementacao

1. Banco de dados
- Adicionar colunas de trilha de pagamento em `requests`
- Expandir ENUM de `status` apenas se novos estados comerciais forem introduzidos

2. Modelo
- Atualizar `app/model/Request.php` com:
  - constantes de pagamento
  - validadores de transicao de pagamento
  - helpers para acoes por perfil

3. Controller
- Evoluir `app/controller/ControllerRequest.php`:
  - separar acao de fecho comercial da acao de confirmacao financeira
  - mover criacao de comissao para gatilho de recebimento confirmado

4. Views
- Atualizar:
  - `app/view/dashboard/requests/Main.php`
  - `app/view/dashboard/request_chat/Main.php`
  - `app/view/dashboard/dispute_detail/Main.php`
- Exibir chip de status de pagamento e botoes por perfil

5. Notificacoes
- Expandir catalogo e dispatcher para novos eventos de pagamento

6. Jobs/schedulers
- Revisar rotinas de expiracao e SLA para considerar pagamentos pendentes

7. Testes
- Cobrir:
  - declaracao de pagamento pelo comprador
  - confirmacao de recebimento pelo proprietario
  - tentativa indevida por perfil incorreto
  - comissao criada apenas no momento correto

## Estrategia de Rollout
1. Fase 1 (compatibilidade)
- Criar colunas novas
- Popular defaults para requests antigas
- Nao remover fluxo antigo imediatamente

2. Fase 2 (regra efetiva)
- Criacao de comissao passa para confirmacao de recebimento
- Interface mostra acoes separadas de fecho e pagamento

3. Fase 3 (higienizacao)
- Remover caminhos legados de confirmacao que misturam fecho com pagamento
- Atualizar documentacao operacional e scripts de smoke test
