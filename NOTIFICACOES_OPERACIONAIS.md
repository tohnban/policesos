# Catalogo de Notificacoes Operacionais

**Ultima revisao:** 2026-06-08  
**Codigo de referencia:** `app/model/Notification.php`, `app/model/RequestChatMessage.php`

## 1. Solicitacao atualizada
- Tipo: `request_status_updated`
- Titulo padrao: Atualizacao de solicitacao
- Mensagem padrao: A sua solicitacao no imovel "{property_title}" foi atualizada para: {status_label}.
- Quando: transicoes gerais de `status` (ex.: `cancelado`, `expirado`, `em_disputa`, resolucao de disputa)

## 2. Fecho ganho — visita da plataforma
- Tipo: `request_closing_won_platform_visit`
- Titulo: Fecho ganho — visita e avaliacao final
- Mensagem (interessado): A plataforma entrara em contacto consigo atraves dos dados cadastrados no sistema para agendar a visita ao imovel "{property_title}" e efectuar a avaliacao final do negocio. Apos visitar o imovel e confirmar que tudo esta conforme acordado, podera declarar o pagamento na plataforma.
- Mensagem (proprietario): A plataforma entrara em contacto consigo atraves dos dados cadastrados no sistema para agendar a visita ao imovel "{property_title}" e efectuar a avaliacao final do negocio.
- Quando: proprietario marca `fechado_ganho` a partir de `em_contacto` (substitui notificacao generica de fecho nesse momento)
- Chat: mensagem de sistema paralela com o mesmo conteudo operacional

## 2.1 Pagamento declarado pelo interessado
- Tipo: `request_payment_declared`
- Titulo: Pagamento declarado
- Mensagem: O interessado declarou pagamento no imovel "{property_title}". Confirme o recebimento para consolidar o fecho.

## 2.2 Recebimento confirmado pelo proprietario
- Tipo: `request_payment_receipt_confirmed`
- Titulo: Recebimento confirmado
- Mensagem: O proprietario confirmou o recebimento do pagamento no imovel "{property_title}".

## 2.3 Pagamento contestado
- Tipo: `request_payment_contested`
- Titulo: Pagamento contestado
- Mensagem: Houve contestacao sobre pagamento no imovel "{property_title}". O caso pode seguir para disputa.

## 3. Negociacao cancelada
- Tipo: `request_status_updated`
- Titulo: Cancelado (via `status_label`)
- Mensagem: A negociacao no imovel "{property_title}" foi encerrada como cancelado.
- Nota: o estado comercial e `cancelado` (nao existe `fechado_perdido` no modelo actual)

## 4. Solicitacao em disputa
- Tipo: `request_status_updated`
- Titulo: Solicitacao em disputa
- Mensagem: A solicitacao no imovel "{property_title}" entrou em disputa e sera analisada pela equipa.

## 5. Solicitacao expirada
- Tipo: `request_status_updated`
- Titulo: Solicitacao expirada
- Mensagem: A solicitacao no imovel "{property_title}" expirou por falta de atualizacao.

## 6. Lembrete de SLA
- Tipo: `request_sla_reminder`
- Titulo: Acompanhamento de solicitacao
- Mensagem: A solicitacao do imovel "{property_title}" esta em "{status_label}" ha {days_without_update} dia(s) sem atualizacao. Atualize o desfecho para evitar expiracao.
- Quando: `em_contacto` com `next_followup_at` vencido; expiracao automatica aos 30 dias

## Mensagens de sistema no chat (sem notificacao dedicada)
- Politica de contacto na primeira abertura do chat: proibido partilhar telefone, e-mail ou redes sociais (`[[policy:negotiation_contact]]`)
- Visita da plataforma apos fecho ganho (`[[policy:closing_won_platform_visit]]`)

## Variaveis dinamicas
- {property_title}
- {status_label}
- {days_without_update}
- {request_id}

## Publico
- Interessado (requester)
- Proprietario (owner)
- Moderacao/operacao (quando aplicavel por fluxo adicional)
