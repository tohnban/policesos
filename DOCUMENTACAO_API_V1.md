# Documentação Técnica — API v1

**Última revisão:** 2026-06-08  
**Código de referência:** `app/controller/ControllerApiV1*.php`, `app/model/ApiToken.php`, `app/controller/ApiControllerSupport.php`

## 1. Visão geral

API REST JSON sob `/api/v1/*`. Autenticação via **Bearer token** no header `Authorization`.

Formato de resposta padrão:
```json
{
  "success": true,
  "data": { ... },
  "error": null,
  "timestamp": "2026-06-08T12:00:00+01:00",
  "version": "v1"
}
```

## 2. Endpoints públicos (sem token)

| Método | Path | Descrição |
|--------|------|-----------|
| GET | `/api` | Página de documentação da API |
| GET | `/api/health` | Health check |

## 3. Autenticação

### Obter token
Tokens criados via painel ou endpoint `POST /api/v1/tokens` (requer token existente com scope `manage:tokens`).

Modelo: `ApiToken::createToken($userId, $name, $scopes, $expiresAt)`

### Usar token
```
Authorization: Bearer <token_plaintext>
```

Validação: `ApiToken::validateToken()` — marca `last_used_at` em cada request.

### Scopes disponíveis
| Scope | Permite |
|-------|---------|
| `read:properties` | Listar e ver imóveis |
| `read:notifications` | Listar notificações |
| `manage:tokens` | Criar/listar/revogar tokens |
| `manage:saved_searches` | CRUD de pesquisas guardadas |

Verificação: `ApiToken::hasScope($token, $requiredScope)`.

## 4. Recursos

### 4.1 Root
```
GET /api/v1
```
Requer token. Retorna versão e lista de recursos.

### 4.2 Imóveis

**Listagem**
```
GET /api/v1/properties
```

Query params:
- `page`, `per_page` (max 50) — paginação offset
- `cursor`, `next_cursor` — paginação por cursor (alternativa)
- Filtros: `type`, `purpose`, `min_price`, `max_price`, `location`, `country_id`, `region_id`, `keyword`

Scope: `read:properties`  
Apenas imóveis `disponivel` no detalhe; listagem segue filtros do modelo.

**Detalhe**
```
GET /api/v1/property/{id}
```
404 se não existir ou não estiver `disponivel`.

### 4.3 Tokens

```
GET    /api/v1/tokens          # listar tokens do utilizador
POST   /api/v1/tokens          # criar (body JSON: name, scopes, expires_at)
DELETE /api/v1/tokens/{id}     # revogar
```

Scope: `manage:tokens`

### 4.4 Pesquisas guardadas

```
GET    /api/v1/saved_searches
POST   /api/v1/saved_searches
DELETE /api/v1/saved_searches/{id}
```

Scope: `manage:saved_searches`

### 4.5 Notificações

Controller: `ControllerApiV1Notifications`

```
GET  /api/v1/notifications
GET  /api/v1/notifications/archive
POST /api/v1/notifications/{id}/read
POST /api/v1/notifications/read-all
```

Scope: `read:notifications`  
Detalhe completo: [NOTIFICACOES_SISTEMA_COMPLETO.md](NOTIFICACOES_SISTEMA_COMPLETO.md).

## 5. Rate limiting

API sujeita a `ClassRateLimiter` nos controllers via `ApiControllerSupport::beginV1Request()`.

Limites configuráveis em `settings` (`rate_limit_*`).

## 6. Logging de API

Cada request autenticado regista em `logs`:
- `action` — ex.: `api.properties.list`
- `status_code`
- `user_id` do token
- Detalhes opcionais

## 7. Erros comuns

| HTTP | Causa |
|------|-------|
| 401 | Token ausente, inválido ou expirado |
| 403 | Scope insuficiente |
| 404 | Recurso não encontrado |
| 429 | Rate limit excedido |
| 500 | Erro interno (ver `storage/logs/app.log`) |

## 8. Teste rápido

```bash
# Sem token — deve retornar 401
curl -s http://localhost/api/v1

# Health
curl -s http://localhost/api/health

# Com token
curl -s -H "Authorization: Bearer SEU_TOKEN" \
  "http://localhost/api/v1/properties?per_page=5"
```

## 9. Legacy dispatcher

URLs `/api/v1/*` não declaradas em `routes.php` podem ser resolvidas pelo método `ControllerApiV1::v1()` para compatibilidade com routing legacy.

Rotas preferenciais estão em `config/routes.php` com controllers dedicados (`ControllerApiV1Properties`, etc.).
