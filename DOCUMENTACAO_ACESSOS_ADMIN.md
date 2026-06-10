# Documentacao de Acessos Administrativos

Este documento descreve como os acessos administrativos estao organizados no projeto, qual perfil pode abrir cada area e como adicionar/remover permissoes de forma segura.

## 1. Matriz de perfis e paginas

Paginas comuns para perfis administrativos:

- dashboard
- profile

Matriz atual:

| Perfil | Papel tecnico | Paginas principais |
|---|---|---|
| Admin Total | super_admin | Todas as areas administrativas (inclui KPIs, auditoria, metodos/canais, subscricoes, settings) |
| Admin Moderacao | moderador | property/moderate, dashboard/moderate_users (sem tabs Acessos/Equipa), dashboard/reviewDocuments |
| Admin Suporte | suporte | requests, dashboard/requestChats, dashboard/disputes |
| Admin Financeiro | financeiro | dashboard/payments, payment_transactions |

Regras adicionais importantes:

- A aba **Acessos** em `dashboard/moderateUsers?tab=acessos` e exclusiva de `super_admin` (Admin Total): suspende/bloqueia **utilizadores normais**.
- A aba **Equipa Admin** em `dashboard/moderateUsers?tab=equipa` e exclusiva de `super_admin` (Admin Total): cria, altera papéis e restringe **contas administrativas**.
- Os endpoints de gestao de acessos, criacao de admin e troca de papel exigem `ClassAccess::requireSuperAdmin()`.
- Existe protecao anti-lockout: nao e permitido rebaixar, suspender ou revogar o ultimo `super_admin` ativo.
- O Admin Total **nao pode** alterar o proprio acesso administrativo pelo ecran da equipa (evita auto-bloqueio acidental).

## 2. Onde as permissoes vivem

As permissoes por papel estao em:

- src/classes/ClassAccess.php

Mapa principal (resumo):

- super_admin: `*`
- moderador: `dashboard.view`, `users.review`, `documents.review`, `properties.moderate`
- suporte: `dashboard.view`, `requests.manage`
- financeiro: `dashboard.view`, `payments.manage`
- KPIs (`dashboard/kpi`): apenas `super_admin` via `requireSuperAdmin()`

Nota: sem permissao explicita, o acesso e negado.

## 3. Como ADICIONAR acesso de pagina para um perfil admin

Exemplo: permitir que `suporte` veja uma nova pagina `dashboard/foo`.

1. Definir/usar uma chave de permissao

- Exemplo de chave: `foo.view`

2. Conceder a permissao no papel correto

- Editar `ROLE_PERMISSIONS` em `src/classes/ClassAccess.php`
- Adicionar `foo.view` ao array do papel `suporte`

3. Proteger a action no controller

- No metodo do controller, usar:

```php
ClassAccess::requirePermission('foo.view', 'dashboard', 'Sem permissao para esta area');
```

4. Exibir item de menu apenas para quem pode

- Em `app/view/Layout.php`:

```php
if (Src\classes\ClassAccess::can('foo.view', $dashboardShellUser)) {
    $dashboardMenuItems[] = [
        'key' => 'foo',
        'label' => 'Foo',
        'icon' => 'fa-cube',
        'href' => DIRPAGE . 'dashboard/foo',
    ];
}
```

5. Se houver tabs internas, proteger tambem a tab

- Exemplo: validar `$_GET['tab']` e forcar fallback para tab permitida quando o perfil nao tiver permissao.

## 4. Como REMOVER acesso de pagina para um perfil admin

1. Remover a chave da permissao no papel em `ClassAccess::ROLE_PERMISSIONS`.
2. Manter (ou adicionar) `requirePermission(...)` no controller da pagina.
3. Remover/ocultar item de menu condicional em `Layout.php`.
4. Revisar links diretos e tabs para garantir fallback sem permissao.

## 5. Boas praticas obrigatorias

- Toda action sensivel deve usar `POST` + CSRF.
- Toda pagina administrativa deve ter guard de permissao no controller.
- Menu nunca deve ser unica barreira; a barreira real e sempre server-side.
- Registar acao em log quando alterar acesso/role.
- Notificar o utilizador impactado quando o acesso/role for alterado.

## 6. Checklist rapido antes de publicar alteracoes de acesso

- Permissao nova adicionada em `ClassAccess`.
- Controller protegido com `requirePermission`.
- Menu condicionado com `ClassAccess::can`.
- Tabs internas protegidas.
- Fluxo sensivel com CSRF.
- Logs e notificacoes para alteracoes de acesso/role.
- Teste com pelo menos 1 utilizador de cada papel admin.

## 7. Gestao da equipa administrativa (Admin Total)

Ecran: `dashboard/moderateUsers?tab=equipa`  
Controller: `app/controller/ControllerDashboardModeration.php`  
Modelo: `app/model/User.php`

### 7.1 Criar conta administrativa

O Admin Total pode criar contas para outros administradores **sem passar pelo registo publico**:

| Campo | Notas |
|-------|-------|
| Nome, email, telefone | Validacao de unicidade (email e telefone) |
| Papel | `super_admin`, `moderador`, `suporte`, `financeiro` |
| Palavra-passe | Minimo 6 caracteres; comunicar ao novo membro por canal seguro |

Comportamento ao criar:

- `status = ativo` e `email_verified_at` preenchido (login imediato)
- `is_admin = 1` e `role` conforme seleccionado
- `document_number` interno gerado (`ADM…`) — nao exige upload documental
- Log: `create_admin_user` | Notificacao: `admin_account_created`

**Endpoint:** `POST dashboard/createAdministrativeUser` (middleware `super_admin` + CSRF)

### 7.2 Alterar papel de um administrador

Na mesma aba, selector por membro da equipa.

| Papel tecnico | Label na UI |
|---------------|-------------|
| `super_admin` | Admin Total |
| `moderador` | Admin Moderacao |
| `suporte` | Admin Suporte |
| `financeiro` | Admin Financeiro |

**Endpoint:** `POST dashboard/setAdminRole/{id}`

Proteccoes: nao rebaixar o proprio papel; nao rebaixar o ultimo `super_admin` ativo.

### 7.3 Restringir acesso administrativo

Accoes disponiveis por membro (excepto a conta actual):

| Accao | Efeito | Endpoint |
|-------|--------|----------|
| Suspender | `suspended_until` por N dias (1–365); bloqueia login | `POST dashboard/suspendAdministrativeAccess/{id}` |
| Levantar suspensao | Limpa `suspended_until` | `POST dashboard/unsuspendAdministrativeAccess/{id}` |
| Revogar admin | `role = utilizador`, `is_admin = 0`; conta passa a normal | `POST dashboard/revokeAdministrativeAccess/{id}` |

Logs: `suspend_admin_access`, `unsuspend_admin_access`, `revoke_admin_access`  
Notificacoes: `admin_access_suspended`, `admin_access_unsuspended`, `admin_access_revoked`

Proteccoes: nao suspender/revogar o ultimo `super_admin` ativo; nao actuar sobre a propria conta.

### 7.4 Metodos de modelo relevantes

```text
User::createAdministrativeUser(array $data): array
User::setAdministrativeRole(int $userId, string $role): bool
User::suspendAdministrativeAccess(int $userId, int $days): bool
User::unsuspendAdministrativeAccess(int $userId): bool
User::revokeAdministrativeAccess(int $userId): bool
User::isAdministrativeUser(?array $user): bool
User::countActiveSuperAdmins(): int
User::getAdministrativeUsers(): array
```

### 7.5 Diferenca entre abas Acessos e Equipa

| Aba | Alvo | Accoes tipicas |
|-----|------|----------------|
| `tab=acessos` | Utilizadores normais (`role = utilizador`) | Suspender, bloquear, levantar suspensao |
| `tab=equipa` | Contas administrativas | Criar admin, alterar papel, suspender/revogar admin |

Nunca misturar os dois fluxos: metodos como `suspendByAdmin()` excluem contas admin; metodos `*AdministrativeAccess()` aplicam-se apenas a administradores.
