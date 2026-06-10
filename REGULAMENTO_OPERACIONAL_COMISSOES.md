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

### 5.1 Estado comercial (`status`)
Estados activos no sistema (`app/model/Request.php`):
1. `em_contacto` — negociacao em curso (estado inicial na criacao)
2. `fechado_ganho` — acordo comercial declarado pelo proprietario
3. `cancelado` — negociacao encerrada sem acordo
4. `expirado` — encerramento automatico por inactividade
5. `em_disputa` — divergencia em analise pela moderacao

Estados legados (`pendente`, `proposta`, `analise`) existem apenas em etiquetas de compatibilidade; nao sao valores activos do ENUM.

### 5.2 Trilhas auxiliares
Alem do `status`, cada solicitacao regista:
- `closing_confirmation_status`: `pendente` | `confirmado` | `contestada`
- `payment_confirmation_status`: `pendente` | `declarado_comprador` | `confirmado_proprietario` | `contestado`
- `dispute_status`: `nenhuma` | `aberta` | `em_analise` | `julgada_procedente` | `julgada_improcedente`
- `commercial_status` — ultimo desfecho comercial consolidado (espelho para auditoria)
- `attribution_expires_at` — fim da janela de atribuicao (90 dias apos criacao)
- `dispute_open_until` — prazo para abrir disputa apos fecho (30 dias)

### 5.3 Transicoes permitidas
| De | Para | Actor |
|----|------|-------|
| `em_contacto` | `fechado_ganho` | proprietario |
| `em_contacto` | `cancelado` | proprietario ou interessado |
| `fechado_ganho` | `em_disputa` | qualquer parte (contestacao de pagamento) |
| `em_disputa` | `fechado_ganho` ou `cancelado` | moderacao |
| `em_contacto` (inactivo) | `expirado` | scheduler automatico |

`cancelado` e `expirado` sao terminais no ciclo comercial; disputa pode ser aberta dentro da janela de 30 dias quando elegivel.

### 5.4 Regras de negocio
1. Toda solicitacao inicia em `em_contacto` com `next_followup_at` a 7 dias.
2. Desfechos comerciais validos: `fechado_ganho`, `cancelado` ou `expirado`.
3. `fechado_ganho` abre ciclo de confirmacao de fecho e pagamento (`closing_confirmation_status = pendente`, `payment_confirmation_status = pendente`).
4. Divergencia sobre pagamento ou recebimento encaminha para `em_disputa`.

## 6. SLA de acompanhamento
Implementado em `scripts/requests_sla_scheduler.php` e `Request::AUTO_EXPIRE_DAYS` (30):
1. Solicitacoes em `em_contacto` com `next_followup_at` vencido recebem lembrete (`request_sla_reminder`) e o prazo e renovado (+7 dias).
2. Solicitacoes em `em_contacto` sem `last_interaction_at` ha 30 dias migram para `expirado`.
3. A prioridade urgente manual aos 14 dias e recomendacao operacional para suporte; nao ha escalonamento automatico no codigo.
4. Proprietario com recorrencia de expirados entra em monitorizacao ativa (processo manual de compliance).

## 7. Janela de atribuicao comercial
1. Janela padrao: 90 dias apos criacao da solicitacao.
2. Qualquer fecho entre as mesmas partes e mesmo imovel dentro da janela gera comissao devida.
3. Se houver afiliado no request_id original, sua comissao permanece devida na janela de atribuicao.
4. Excecoes so podem ocorrer com decisao formal de compliance e registro em log.

## 8. Dupla confirmacao de fecho e visita da plataforma
1. Quando o proprietario marca `fechado_ganho` a partir de `em_contacto`, o sistema publica mensagem de sistema no chat e notifica ambas as partes (`request_closing_won_platform_visit`): a plataforma agenda visita ao imovel e avaliacao final atraves dos dados cadastrados.
2. O interessado so deve declarar pagamento apos visitar o imovel e confirmar conformidade com o acordado.
3. Quando o interessado declara pagamento (`payment_confirmation_status = declarado_comprador`), o proprietario recebe pedido para confirmar recebimento.
4. O fecho financeiro e a comissao so se consolidam quando o proprietario confirma recebimento (`confirmado_proprietario`).
5. Se qualquer parte contestar declaracao de pagamento ou recebimento, o caso passa a `em_disputa` com `payment_confirmation_status = contestado`.
6. Resolucao de disputa a favor de `fechado_ganho` pode consolidar o fecho financeiro via moderacao (`Request::consolidateFinancialClosingByModerator`).
7. Casos sem resposta dentro do SLA operacional seguem para revisao manual.

## 9. Mensagens internas e contato externo
1. O sistema prioriza mensagens internas como canal oficial de trilho.
2. Na primeira abertura do chat de negociacao, e exibida politica de contacto: e proibido partilhar telefone, e-mail ou redes sociais no chat.
3. Dados de contacto pessoal fora do chat nao substituem a obrigacao de declarar desfecho na plataforma.
4. Eventos criticos (`fechado_ganho`, declaracao de pagamento, confirmacao de recebimento, `cancelado`, disputa) geram mensagem de sistema e/ou notificacao.

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
1. Janela para abrir disputa: 30 dias apos `fechado_ganho` ou `cancelado` (`DISPUTE_WINDOW_DAYS`), desde que `dispute_status = nenhuma`.
2. Sub-estados de disputa: `aberta` → `em_analise` → `julgada_procedente` ou `julgada_improcedente`.
3. Evidencias aceites: mensagens internas, contrato, comprovativo de sinal, declaracoes das partes.
4. Prazo de analise: ate 7 dias uteis (meta operacional).
5. Decisao final: moderacao (+ financeiro quando aplicavel), com registro obrigatorio.
6. Decisao deve indicar: fundamento, impacto financeiro e medidas aplicadas.

## 16. Clausulas contratuais minimas (Termos de Uso)
Texto base recomendado:
1. "Toda negociacao derivada de solicitacao registrada na plataforma e comissionavel durante a janela de atribuicao definida na politica comercial vigente."
2. "A conclusao do negocio deve ser declarada no sistema, independentemente do canal de negociacao entre as partes."
3. "A omissao de desfecho ou ocultacao de negocio para evitar pagamento de comissao configura violacao contratual e autoriza aplicacao de penalidades operacionais e comerciais."
4. "Quando houver afiliado associado a solicitacao, a comissao de afiliacao permanece devida em caso de fecho dentro da janela de atribuicao."

## 17. RACI operacional
1. Proprietario
- Marcar `fechado_ganho` ou `cancelado` em `em_contacto`.
- Confirmar recebimento de pagamento apos declaracao do interessado.
- Contestar pagamento quando aplicavel.

2. Interessado
- Cancelar negociacao em `em_contacto`.
- Declarar pagamento apos visita ao imovel (quando `fechado_ganho`).
- Contestar fecho ou pagamento.

3. Moderacao
- Resolver `em_disputa` para `fechado_ganho` ou `cancelado`.
- Consolidar fecho financeiro quando a disputa confirma acordo.

4. Financeiro
- Confirmar pagamento manual de comissao e gerir inadimplencia.

5. Suporte
- Acompanhar SLA, lembretes, visitas da plataforma e triagem inicial de conflitos.

## 18. Indicadores obrigatorios (KPI)
1. Taxa de desfecho declarado por proprietario.
2. Tempo medio ate desfecho.
3. Percentual de solicitacoes expiradas.
4. Valor de comissao emitida vs recebida.
5. Taxa de disputa por 100 solicitacoes.
6. Taxa de perda de comissao por evasao suspeita.

## 19. Estado de implementacao (referencia tecnica)
Funcionalidades activas no codigo:
1. Estados comerciais e trilhas de fecho/pagamento/disputa (`Request.php`).
2. SLA automatico: lembretes a 7 dias e expiracao a 30 dias (`requests_sla_scheduler.php`).
3. Dupla confirmacao de pagamento com comissao apenas apos `confirmado_proprietario`.
4. Chat com politica de contacto e mensagem de visita da plataforma no fecho ganho.
5. Notificacoes operacionais documentadas em `NOTIFICACOES_OPERACIONAIS.md`.
6. Detalhe tecnico do fluxo de pagamento em `REQUESTS_FECHO_PAGAMENTO_ROADMAP.md`.

Pendente de evolucao operacional (fora do nucleo de estados):
1. Bloqueios automaticos por comissao vencida.
2. Score de risco e fila de revisao automatizada.
3. Escalonamento automatico de prioridade aos 14 dias.

## 20. Checklist de execucao diaria (operacao)
1. Verificar fila de solicitacoes urgentes/atrasadas.
2. Cobrar declaracao de desfecho em casos sem atualizacao.
3. Confirmar pagamentos manuais pendentes no financeiro.
4. Aplicar bloqueios previstos para comissao vencida.
5. Revisar alertas de risco e abrir disputas quando necessario.
6. Fechar o dia com relatorio de compliance comercial.

---

Versao: 1.1
Data: 2026-06-08
Status: alinhado com modelo de estados em producao (`Request.php`)