# Sistema de Notificações - Implementação Completa

## Visão Geral

Sistema de notificações sem preferências de usuário, implementado com 3 camadas de apresentação:

1. **Toast**: Notificações auto-dismissíveis (5-8s) para eventos críticos
2. **Bell Icon** com drawer: Últimas 10 notificações da inbox
3. **Páginas dedidas**: Inbox completa + Arquivo com filtros

## Arquitetura

### Banco de Dados

Tabela `notifications` com campos:
- `id`: Identificador único
- `user_id`: Receptor da notificação
- `type`: Categoria (request_status, payment, property_update, etc)
- `title`: Títo curto
- `message`: Descrição detalhada
- `title`: Ação/meta (URL, ID, etc)
- `notification_group`: SHA1 hash para agregação
- `grouped_count`: Contagem de notificações agregadas
- `is_read`: Flag de leitura
- `is_archived`: Flag de arquivo
- `created_at`: Timestamp

### Modelos PHP

**app/model/Notification.php**:
```php
// Inbox (não arquivadas)
Notification::getInboxByUser($userId, $limit, $offset)
Notification::countInboxByUser($userId)
Notification::countUnreadByUser($userId)

// Arquivo (arquivadas)
Notification::getArchiveByUser($userId, $limit, $offset, $typeFilter = null)
Notification::countArchiveByUser($userId, $typeFilter = null)

// Ações
Notification::markAsReadByUser($notificationId, $userId): bool
Notification::markAllAsReadByUser($userId): bool
Notification::archiveByUser($notificationId, $userId): bool
Notification::archiveAllByUser($userId): bool
Notification::deleteArchivedOlderThan($daysAgo = 90): int

// Utilitários
Notification::buildNotificationGroup(type, metadata): string
```

### Controladores

**app/controller/ControllerNotification.php**:
- `GET /notification/inbox` - Página principal com paginação
- `GET /notification/archive` - Arquivo com filtros por tipo
- `POST /notification/mark-as-read` - Marcar uma como lida
- `POST /notification/mark-all-as-read` - Marcar todas como lidas
- `POST /notification/archive` - Arquivar uma
- `POST /notification/archive-all` - Arquivar todas

### API REST

**Endpoints em /api/v1/notifications**:

#### GET /api/v1/notifications
Fetch inbox with pagination
```json
Request:
GET /api/v1/notifications?page=1&per_page=10
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "page": 1,
    "per_page": 10,
    "total": 42,
    "unread": 5,
    "notifications": [
      {
        "id": 1,
        "type": "request_status",
        "title": "Solicitação atualizada",
        "message": "A solicitação #123 foi aceita",
        "data": "...",
        "is_read": false,
        "created_at": "2025-01-23T10:30:00"
      },
      ...
    ]
  }
}
```

#### GET /api/v1/notifications/archive
Fetch archived notifications with optional type filter
```json
Request:
GET /api/v1/notifications/archive?page=1&per_page=20&type=request_status
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "page": 1,
    "per_page": 20,
    "total": 156,
    "notifications": [...]
  }
}
```

#### POST /api/v1/notifications
Bulk actions: mark_as_read, mark_all_as_read, archive, archive_all
```json
Request:
POST /api/v1/notifications
Authorization: Bearer {token}
Content-Type: application/json

{
  "action": "mark_as_read",
  "notification_id": 123
}

Response:
{
  "success": true,
  "data": {
    "marked": true
  }
}
```

### Frontend JavaScript

**public/js/notification-system.js**:

```javascript
// Inicialização automática (se autenticado)
window.notificationSystem = new NotificationSystem('/api/v1');

// Mostrar toast
notificationSystem.showToast(
  'Sucesso',
  'Solicitação aceita',
  'success',
  5000
);

// Gerenciar drawer manualmente
notificationSystem.toggleDrawer();
notificationSystem.fetchUnreadCount();
notificationSystem.markAsRead(notificationId);
notificationSystem.archiveNotification(notificationId);
```

## Integração em Templates

### Incluir JavaScript

No layout base (app/view/layout.php ou similar):
```html
<script src="<?php echo DIRPAGE; ?>js/notification-system.js"></script>
```

### Usar Toast em eventos

Após criar propriedade:
```javascript
if (window.notificationSystem) {
  notificationSystem.showToast(
    'Propriedade Criada',
    'Sua propriedade foi listada com sucesso',
    'success'
  );
}
```

Após erro de pagamento:
```javascript
if (window.notificationSystem) {
  notificationSystem.showToast(
    'Erro no Pagamento',
    'Falha ao processar pagamento. Tente novamente.',
    'error'
  );
}
```

## Fluxo de Uso

### Para o Usuário (Frontend)

1. **Bell Icon** no header mostra badge com contagem de não lidas
2. **Clica no bell** → Drawer abre mostrando últimas 10 notificações
   - Clica em notificação → marca como lida
   - Clica 📦 → arquiva
   - Botão "Marcar como lidas" → marca todas
   - Link "Ver tudo" → vai para /notification/inbox
3. **Página /notification/inbox**
   - Lista completa com paginação (15/página)
   - Filtros por tipo (todo, request, payment, etc)
   - Ações: marcar como lido, arquivar, marcar todas como lidas
4. **Página /notification/archive**
   - Notificações arquivadas com paginação (20/página)
   - Filtros por tipo
   - Ações: restaurar, apagar definitivamente

### Para o Sistema (Backend)

Quando criar/atualizar propriedade:
```php
use App\model\Notification;

// Notificar proprietário
Notification::create([
    'user_id' => $ownerId,
    'type' => 'property_update',
    'title' => 'Propriedade Atualizada',
    'message' => "Sua propriedade '{$name}' foi atualizada",
    'data' => json_encode(['property_id' => $propertyId]),
    'notification_group' => Notification::buildNotificationGroup(
        'property_update',
        ['property_id' => $propertyId]
    )
]);

// Notificar admin sobre pagamento
Notification::create([
    'user_id' => 1, // Admin
    'type' => 'payment',
    'title' => 'Novo Pagamento',
    'message' => "Usuário $userName pagou R$ 150,00",
    'data' => json_encode(['transaction_id' => $txnId]),
]);
```

## Configuração

### Variáveis de Ambiente

Não há preferências de usuário - sistema é padronizado:
- Toast: 5 segundos (hardcoded em notification-system.js)
- Drawer: mostra 10 últimas (parametrizado em API)
- Inbox: 15/página (em ControllerNotification)
- Archive: 20/página (em ControllerNotification)
- Limpeza: 90 dias (em deleteArchivedOlderThan)

### Migrations

Execute em sequência:
```bash
# 1. Estrutura base
scripts/migration_20260517_notifications_grouping_archive.sql

# 2. Verificação/correção de schema
scripts/migration_20250123_notification_schema_finalize.sql
```

## Testes

### Teste Manual - Toast
```javascript
// No console do navegador
notificationSystem.showToast('Teste', 'Este é um teste', 'info', 3000);
```

### Teste Manual - API
```bash
# Get inbox
curl -X GET "http://localhost/api/v1/notifications?page=1&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Mark as read
curl -X POST "http://localhost/api/v1/notifications" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"action":"mark_as_read","notification_id":1}'

# Archive
curl -X POST "http://localhost/api/v1/notifications" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"action":"archive","notification_id":1}'

# Get archive
curl -X GET "http://localhost/api/v1/notifications/archive?page=1&type=request_status" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Teste Manual - Páginas
- Visite `/notification/inbox` logado
- Visite `/notification/archive` logado
- Clique no bell icon no header
- Marque como lido, arquive, etc

## Próximos Passos (Opcional)

1. **Preferências** (se needed later): adicionar tabla user_notification_preferences
2. **Notificações em tempo real**: WebSocket para push instant
3. **Email notifications**: integrar com queue para enviar por email
4. **Digests**: agregar múltiplas notificações em 1 email
5. **Templates**: sistema de templates para títulos/mensagens dinâmicas
6. **Analytics**: rastrear abertura, cliques, ações
