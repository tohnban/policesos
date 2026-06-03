# Regulamento Operacional de Solicitacoes, Fecho e Comissoes

## 1. Objetivo
Estabelecer regras obrigatorias para reduzir evasao de comissoes quando a negociacao evolui fora da plataforma, garantindo sustentabilidade do modelo e protecao de comissoes de afiliados.

## 2. Escopo
Este regulamento aplica-se a:
- Proprietarios de imoveis (ou representantes)
- Interessados (compradores/inquilinos)
- Afiliados
- Equipa de moderacao, suporte e financeiro

Aplica-se a toda solicitacao criada no sistema e associada a um imovel publicado na plataforma.

## 3. Principios de negocio
1. Toda solicitacao criada no sistema gera trilho comercial auditavel.
2. Toda transacao derivada de solicitacao registrada e comissionavel dentro da janela de atribuicao.
3. O fecho do negocio deve ser declarado no sistema, mesmo que o pagamento entre as partes ocorra fora da plataforma.
4. Comissao de afiliado vinculada ao request_id nao pode ser removida manualmente sem justificativa formal e registro de auditoria.

## 4. Definicoes
- Solicitacao: intencao formal de compra/aluguer registrada no sistema.
- Fecho: acordo final entre proprietario e interessado (compra ou contrato de aluguer).
- Janela de atribuicao: periodo em que um fecho e considerado derivado da solicitacao original.
- Comissao devida: valor devido a plataforma e, quando aplicavel, ao afiliado.
- Evasao de comissao: fecho realizado sem declaracao no sistema para evitar pagamento devido.

## 5. Fluxo obrigatorio de solicitacoes
Estados oficiais:
1. pendente
2. em_contacto
3. proposta
4. fechado_ganho
5. fechado_perdido
6. expirado
7. em_disputa

Regras:
1. Toda solicitacao inicia em pendente.
2. Ao primeiro contato efetivo entre as partes, deve migrar para em_contacto.
3. Quando houver proposta concreta, migrar para proposta.
4. Todo desfecho obrigatoriamente termina em fechado_ganho, fechado_perdido ou expirado.
5. Divergencia entre partes gera estado em_disputa.

## 6. SLA de acompanhamento
1. Solicitacoes sem atualizacao por 7 dias recebem lembrete automatico.
2. Solicitacoes sem desfecho por 14 dias ficam com prioridade urgente para acompanhamento.
3. Solicitacoes sem desfecho por 30 dias migram para expirado com flag de compliance comercial.
4. Proprietario com recorrencia de expirados entra em monitorizacao ativa.

## 7. Janela de atribuicao comercial
1. Janela padrao: 90 dias apos criacao da solicitacao.
2. Qualquer fecho entre as mesmas partes e mesmo imovel dentro da janela gera comissao devida.
3. Se houver afiliado no request_id original, sua comissao permanece devida na janela de atribuicao.
4. Excecoes so podem ocorrer com decisao formal de compliance e registro em log.

## 8. Dupla confirmacao de fecho
1. Quando o proprietario marca fechado_ganho, o interessado recebe pedido para declarar pagamento.
2. Se o interessado declarar pagamento, o proprietario recebe pedido para confirmar recebimento.
3. O fecho financeiro so e consolidado quando o proprietario confirmar recebimento.
4. Se qualquer parte contestar declaracao de pagamento ou recebimento, o caso muda para em_disputa.
5. Se nao houver resposta dentro do SLA operacional, o caso vai para revisao manual.

## 9. Mensagens internas e contato externo
1. O sistema deve priorizar mensagens internas como canal oficial de trilho.
2. Dados de contato pessoal podem ser exibidos, mas nao substituem obrigacao de declaracao de desfecho.
3. Eventos criticos (proposta, fechado_ganho, fecho, cancelamento) devem ter registro interno obrigatorio.

## 10. Regras de comissao
1. A comissao nasce no sistema apenas quando houver fechado_ganho e confirmacao de recebimento pelo proprietario.
2. O calculo segue politica comercial ativa (plataforma + afiliado quando aplicavel).
3. A obrigacao financeira e emitida com:
- valor
- data de vencimento
- referencia de pagamento manual
- status: pendente, pago, cancelado_autorizado
4. Cancelamento de comissao so e permitido por perfis autorizados e com motivo registrado.

## 11. Mecanismo de cobranca sem pagamento automatico
1. O sistema emite instrucao de pagamento manual (transferencia/deposito/referencia).
2. A equipa financeira valida comprovativo e confirma no painel.
3. Toda confirmacao/cancelamento gera log de auditoria e notificacao das partes.
4. Comissao vencida sem regularizacao aciona penalidades operacionais.

## 12. Matriz de penalizacoes por incumprimento
Classificacao por gravidade:
- Nivel 1 (leve): atraso de declaracao sem indicio de fraude.
- Nivel 2 (medio): reincidencia de nao declaracao.
- Nivel 3 (grave): ocultacao comprovada de fecho para evasao.

Medidas:
1. Nivel 1
- aviso formal
- prazo de regularizacao de 5 dias

2. Nivel 2
- bloqueio de novos anuncios
- perda temporaria de destaque
- prioridade reduzida no ranking

3. Nivel 3
- ocultacao de imoveis da busca
- suspensao da conta
- bloqueio de saque/repasse de comissoes
- encerramento contratual conforme termos

## 13. Incentivos de conformidade
1. Declaracao no prazo melhora score de confianca do proprietario.
2. Proprietarios conformes recebem melhor visibilidade na listagem.
3. Afiliados e proprietarios com historico limpo podem ter beneficios comerciais (taxa reduzida ou destaque).

## 14. Deteccao de evasao e monitorizacao
Sinais de risco:
1. Alto volume de solicitacoes com baixa taxa de desfecho declarado.
2. Muitos casos em expirado para o mesmo proprietario.
3. Divergencia frequente entre declaracao de proprietario e interessado.
4. Queixas recorrentes sobre negociacao fechada fora do sistema.

Acoes automaticas:
1. gerar alerta de risco
2. abrir checklist de revisao manual
3. escalar para moderacao/compliance quando limiar for atingido

## 15. Governanca de disputa
1. Evidencias aceites: mensagens internas, proposta, contrato, comprovativo de sinal, declaracoes das partes.
2. Prazo de analise: ate 7 dias uteis.
3. Decisao final: moderacao + financeiro, com registro obrigatorio.
4. Decisao deve indicar: fundamento, impacto financeiro e medidas aplicadas.

## 16. Clausulas contratuais minimas (Termos de Uso)
Texto base recomendado:
1. "Toda negociacao derivada de solicitacao registrada na plataforma e comissionavel durante a janela de atribuicao definida na politica comercial vigente."
2. "A conclusao do negocio deve ser declarada no sistema, independentemente do canal de negociacao entre as partes."
3. "A omissao de desfecho ou ocultacao de negocio para evitar pagamento de comissao configura violacao contratual e autoriza aplicacao de penalidades operacionais e comerciais."
4. "Quando houver afiliado associado a solicitacao, a comissao de afiliacao permanece devida em caso de fecho dentro da janela de atribuicao."

## 17. RACI operacional
1. Proprietario
- Atualizar estado da solicitacao e declarar desfecho.

2. Interessado
- Confirmar desfecho quando solicitado.

3. Moderacao
- Revisar disputas e aplicar medidas operacionais.

4. Financeiro
- Confirmar pagamento manual e gerir inadimplencia de comissao.

5. Suporte
- Acompanhar SLA, lembretes e triagem inicial de conflitos.

## 18. Indicadores obrigatorios (KPI)
1. Taxa de desfecho declarado por proprietario.
2. Tempo medio ate desfecho.
3. Percentual de solicitacoes expiradas.
4. Valor de comissao emitida vs recebida.
5. Taxa de disputa por 100 solicitacoes.
6. Taxa de perda de comissao por evasao suspeita.

## 19. Plano de implementacao em 30 dias
Semana 1:
1. publicar termos atualizados
2. ativar estados de funil e SLA

Semana 2:
1. ativar dupla confirmacao de fecho
2. emitir obrigacao financeira manual com vencimento

Semana 3:
1. ativar bloqueios por comissao vencida
2. ativar score de risco e fila de revisao

Semana 4:
1. calibrar penalidades e incentivos
2. validar KPI e ajustar thresholds

## 20. Checklist de execucao diaria (operacao)
1. Verificar fila de solicitacoes urgentes/atrasadas.
2. Cobrar declaracao de desfecho em casos sem atualizacao.
3. Confirmar pagamentos manuais pendentes no financeiro.
4. Aplicar bloqueios previstos para comissao vencida.
5. Revisar alertas de risco e abrir disputas quando necessario.
6. Fechar o dia com relatorio de compliance comercial.

---

Versao: 1.0
Data: 2026-04-16
Status: pronto para adocao operacional