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

- A aba de acessos em `dashboard/moderateUsers?tab=acessos` e exclusiva de `super_admin` (Admin Total).
- A aba de equipa administrativa em `dashboard/moderateUsers?tab=equipa` e exclusiva de `super_admin` (Admin Total).
- Os endpoints de gestao de acessos e de troca de papel admin exigem `ClassAccess::requireSuperAdmin()`.
- Existe protecao anti-lockout: nao e permitido rebaixar o ultimo `super_admin` ativo.

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
