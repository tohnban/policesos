# Catalogo de Notificacoes Operacionais

## 1. Solicitação atualizada
- Tipo: request_status_updated
- Titulo padrao: Atualizacao de solicitacao
- Mensagem padrao: A sua solicitacao no imovel "{property_title}" foi atualizada para: {status_label}.

## 2. Fecho ganho
- Tipo: request_status_updated
- Titulo: Fecho ganho
- Mensagem: O negocio no imovel "{property_title}" foi marcado como fecho ganho e aguarda declaracao de pagamento.

## 2.1 Pagamento declarado pelo interessado
- Tipo: request_payment_declared
- Titulo: Pagamento declarado
- Mensagem: O interessado declarou pagamento no imovel "{property_title}". Confirme o recebimento para consolidar o fecho.

## 2.2 Recebimento confirmado pelo proprietario
- Tipo: request_payment_receipt_confirmed
- Titulo: Recebimento confirmado
- Mensagem: O proprietario confirmou o recebimento do pagamento no imovel "{property_title}".

## 2.3 Pagamento contestado
- Tipo: request_payment_contested
- Titulo: Pagamento contestado
- Mensagem: Houve contestacao sobre pagamento no imovel "{property_title}". O caso pode seguir para disputa.

## 3. Fecho perdido
- Tipo: request_status_updated
- Titulo: Fecho perdido
- Mensagem: A negociacao no imovel "{property_title}" foi encerrada como fecho perdido.

## 4. Solicitação em disputa
- Tipo: request_status_updated
- Titulo: Solicitacao em disputa
- Mensagem: A solicitacao no imovel "{property_title}" entrou em disputa e sera analisada pela equipa.

## 5. Solicitação expirada
- Tipo: request_status_updated
- Titulo: Solicitacao expirada
- Mensagem: A solicitacao no imovel "{property_title}" expirou por falta de atualizacao.

## 6. Lembrete de SLA
- Tipo: request_sla_reminder
- Titulo: Acompanhamento de solicitacao
- Mensagem: A solicitacao do imovel "{property_title}" esta em "{status_label}" ha {days_without_update} dia(s) sem atualizacao. Atualize o desfecho para evitar expiracao.

## Variaveis dinamicas
- {property_title}
- {status_label}
- {days_without_update}
- {request_id}

## Publico
- Interessado (requester)
- Proprietario (owner)
- Moderacao/operacao (quando aplicavel por fluxo adicional)
