# Documentacao Tecnica - Ranking Comportamental (Autenticado e Anonimo)

## 1. Objetivo

Este modulo adiciona personalizacao de descoberta de imoveis usando sinais comportamentais de:

- utilizadores autenticados (user_id)
- visitantes anonimos (visitor_key em sessao)

O desenho foi feito em safe mode: a prioridade comercial continua em primeiro lugar, e o score comportamental so reordena itens dentro dessa hierarquia.

## 2. Escopo funcional

### 2.1 Eventos coletados

- view: visualizacao de detalhe do imovel
- favorite: marcacao de favorito
- request: envio de solicitacao

### 2.2 Publicos cobertos

- autenticado: eventos gravados com user_id e visitor_key
- anonimo: eventos gravados com visitor_key (user_id nulo)

### 2.3 Superficies que consomem ranking

- listagem publica de imoveis
- pagina de imoveis em destaque
- vitrine da home (featured)

## 3. Arquitetura da solucao

### 3.1 Identidade de visitante

Classe responsavel: src/classes/ClassSession.php

- metodo getOrCreateVisitorKey() cria e persiste visitor_key em sessao
- formato: 32 hex chars via random_bytes(16)
- fallback criptografico: sha1(uniqid + microtime)

### 3.2 Captura de eventos

Classe responsavel: app/model/PropertyBehaviorEvent.php

Metodo principal: track(userId, propertyId, eventType, visitorKey)

Regras:

- rejeita propertyId invalido ou eventType fora de [view, favorite, request]
- exige ao menos 1 identificador: user_id ou visitor_key
- dedupe para view: ignora repeticao dentro de 30 minutos por (identificador, imovel)
  - autenticado: user_id + property_id
  - anonimo: visitor_key + property_id

Persistencia:

- tabela property_behavior_events
- colunas: user_id (NULL), visitor_key (NULL), property_id, event_type, created_at

### 3.3 Coleta no fluxo HTTP

ControllerProperty:

- index(): injeta viewer_visitor_key nos filtros
- show(id): registra evento view
- featured(): passa user_id e visitor_key para ranking
- favorite(id): registra evento favorite

ControllerRequest:

- store(): apos criar solicitacao, registra evento request

ControllerHome:

- construtor: carrega featured com user_id e visitor_key

## 4. Modelo de ranking

Classe responsavel: app/model/Property.php

### 4.1 Chaves de configuracao

- behavior_ranking_enabled (0/1)
- behavior_ranking_lookback_days
- behavior_weight_view
- behavior_weight_favorite
- behavior_weight_request
- behavior_max_score_per_property

### 4.2 Formula de score

Para cada imovel candidato:

score_bruto = soma dos pesos de eventos dentro da janela lookback

- view -> behavior_weight_view
- favorite -> behavior_weight_favorite
- request -> behavior_weight_request

Cap por imovel:

viewer_behavior_score = min(score_bruto, behavior_max_score_per_property)

### 4.3 Identificador usado na consulta

Se existir user_id e visitor_key:

- usa OR (pbe.user_id = ? OR pbe.visitor_key = ?)

Se existir apenas um:

- usa o identificador disponivel

### 4.4 Ordem final de listagem (safe mode)

Listagem geral:

1. featured pago do imovel
2. peso de plano/subscricao do proprietario
3. visibilidade premium
4. viewer_behavior_score
5. sort escolhido pelo utilizador (preco/data)

Featured:

1. peso de plano/subscricao
2. visibilidade premium
3. viewer_behavior_score
4. created_at DESC

Conclusao: monetizacao e prioridade comercial nao foram substituidas pelo comportamento.

## 5. Banco de dados e migracoes

Arquivos:

- database_schema.sql
- scripts/migration_20260514_behavior_ranking_safe_mode.sql

### 5.1 Estrutura property_behavior_events

- user_id INT NULL
- visitor_key VARCHAR(64) NULL
- property_id INT NOT NULL
- event_type ENUM('view','favorite','request')
- created_at TIMESTAMP

Indices recomendados implementados:

- idx_pbe_property_time (property_id, created_at)
- idx_pbe_user_time (user_id, created_at)
- idx_pbe_visitor_time (visitor_key, created_at)
- idx_pbe_event_time (event_type, created_at)

### 5.2 Configuracoes seedadas pela migracao

A migracao grava/atualiza no settings:

- behavior_ranking_enabled = 0
- behavior_ranking_lookback_days = 90
- behavior_weight_view = 1
- behavior_weight_favorite = 4
- behavior_weight_request = 8
- behavior_max_score_per_property = 50

## 6. Painel administrativo

Controller:

- app/controller/ControllerDashboard.php

View:

- app/view/dashboard/settings/Main.php

Comportamento:

- todas as chaves acima aparecem no formulario de settings
- validacao de booleano para behavior_ranking_enabled
- validacao numerica inteira >= 1 para janela, pesos e teto

## 7. Fluxo operacional recomendado

### 7.1 Deploy seguro

1. executar migracao SQL
2. validar se coluna visitor_key existe e user_id permite NULL
3. publicar codigo PHP
4. ativar behavior_ranking_enabled gradualmente (0 -> 1)

### 7.2 Por que essa ordem e importante

Se o codigo novo subir antes da migracao, inserts em property_behavior_events podem falhar em ambientes antigos.

## 8. Observabilidade e verificacao

### 8.1 Queries de verificacao rapida

Eventos por tipo:

SELECT event_type, COUNT(*)
FROM property_behavior_events
GROUP BY event_type;

Eventos anonimos recentes:

SELECT COUNT(*)
FROM property_behavior_events
WHERE user_id IS NULL AND visitor_key IS NOT NULL
  AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY);

Top imoveis por score de um visitor_key:

SELECT property_id,
       SUM(CASE event_type
           WHEN 'view' THEN 1
           WHEN 'favorite' THEN 4
           WHEN 'request' THEN 8
           ELSE 0 END) AS score
FROM property_behavior_events
WHERE visitor_key = 'CHAVE_AQUI'
  AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
GROUP BY property_id
ORDER BY score DESC;

## 9. Privacidade e seguranca

- visitor_key e identificador pseudonimo de sessao, nao e PII direta
- cookie de sessao usa httponly e samesite lax
- em producao, cookie_secure segue APP_ENV
- dedupe reduz ruido e volume de telemetria passiva

## 10. Limites atuais e melhorias futuras

Limites atuais:

- dedupe de view e por janela fixa de 30 minutos
- score soma linear simples de eventos

Melhorias sugeridas:

- decaimento temporal por evento (recencia)
- cap global por visitante por janela
- jobs de agregacao para reduzir custo de subconsulta
- limpeza periodica de eventos antigos (retencao)
- score separado por contexto geografico (pais/regiao)

## 11. Checklist de troubleshooting

Se o ranking nao estiver mudando:

1. confirmar behavior_ranking_enabled = 1
2. confirmar existencia de eventos na tabela
3. validar se visitor_key esta sendo criado em sessao
4. validar se filtros carregam viewer_visitor_key e/ou viewer_user_id
5. revisar lookback e pesos (podem estar muito baixos)
6. verificar se cap por imovel nao esta limitando demais

Se houver erro de SQL apos deploy:

1. verificar se migracao foi executada
2. confirmar coluna visitor_key
3. confirmar user_id nullable
4. confirmar indices pbe
