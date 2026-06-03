# Imobil - Plataforma Imobiliária

Uma aplicação web moderna e responsiva para o setor imobiliário, desenvolvida com PHP MVC.

## Escopo Ativo do Projeto

- Código ativo (produção/desenvolvimento): raiz do projeto.
- Front controller ativo: `public/index.php`.
- Código legado/histórico: pasta `antigo/` (não é ponto de entrada web nesta estrutura).

### Política de manutenção

- Novas funcionalidades e correções devem ser aplicadas apenas na raiz (`app/`, `src/`, `public/`, `config/`).
- A pasta `antigo/` deve ser tratada como referência histórica.
- Antes de qualquer merge/release, validar que as alterações não foram feitas apenas em `antigo/`.

## Documentacao Tecnica

- Ranking comportamental (autenticado e anonimo): [DOCUMENTACAO_RANKING_COMPORTAMENTAL.md](DOCUMENTACAO_RANKING_COMPORTAMENTAL.md)
- Acessos administrativos e perfis: [DOCUMENTACAO_ACESSOS_ADMIN.md](DOCUMENTACAO_ACESSOS_ADMIN.md)
- SEO (Search Engine Optimization): [DOCUMENTACAO_SEO.md](DOCUMENTACAO_SEO.md)

## Funcionalidades

- **Área Pública**: Listagem de imóveis, filtros avançados, favoritos (sem conta)
- **Autenticação**: Cadastro e login de usuários
- **Sistema de Solicitações**: Pedidos de compra/aluguer com status tracking
- **Sistema de Filiais**: Usuários podem atuar como afiliados e ganhar comissões
- **Painel do Usuário**: Gerenciamento de solicitações e comissões

## Instalação

1. Clone o repositório
2. Configure o ambiente: copie `.env.example` para `.env` e ajuste as configurações
3. Execute o schema SQL em `database_schema.sql` para criar as tabelas
4. Instale dependências PHP (a partir de `src/`):

```bash
cd src
composer install
```

A pasta `src/vendor/` **não** é versionada — o CI executa `composer install` automaticamente.

5. Acesse a aplicação

### Qualidade de código (local)

```powershell
pwsh -File scripts/quality-check.ps1
```

- **php-cs-fixer** (PSR-12) e **PHPStan** (nível 5) em `src/classes`, `app/model`, `app/controller`, `app/services`
- Corrigir estilo automaticamente: `pwsh -File scripts/quality-check.ps1 -FixCs`

### Logging técnico

Erros não tratados são registados em `storage/logs/app.log` (JSON) com `request_id` (header `X-Request-Id`). Configure `LOG_CHANNEL=file` e `LOG_LEVEL=info` no `.env`.

## Operação Rápida

### Rotas principais

- `/` e `/home`: página inicial pública.
- `/properties`: listagem de imóveis.
- `/property/{id}`: detalhe do imóvel.
- `/dashboard`: painel autenticado.
- `/requests`: solicitações do utilizador/proprietário/admin (conforme perfil).
- `/dashboard/payments`: gestão financeira de comissões (permissão específica).
- `/dashboard/moderate_users`: moderação de utilizadores (rota snake_case suportada).

### Regras de segurança e integridade

- Todas as ações de mudança de estado devem usar `POST` + `CSRF`.
- Endpoints críticos validados com `POST` + `CSRF`:
	- `request/updateStatus/{id}`
	- `property/approve/{id}`
	- `property/reject/{id}`
	- `dashboard/confirmPayment/{id}`
	- `dashboard/cancelPayment/{id}`
- O status de solicitação é recebido por `POST` (`status`) no fluxo de moderação.

### Permissões (resumo)

- Visitante: navegação pública, sem ações protegidas.
- Utilizador autenticado: painel próprio, pedidos, favoritos e afiliação.
- Moderação: revisão de utilizadores/imóveis/documentos.
- Financeiro: confirmação/cancelamento de pagamentos de comissões.

## Estrutura recomendada para mudanças

- `app/controller/`: regras de fluxo HTTP, autorização e redirecionamentos.
- `app/model/`: regras de acesso a dados e validações de domínio.
- `app/view/`: formulários e páginas (com tokens CSRF nos POSTs).
- `src/classes/`: infraestrutura (rotas, sessão, auth, csrf, render, etc.).

## Estrutura do Banco

- `users`: Usuários e afiliados
- `properties`: Imóveis disponíveis
- `requests`: Solicitações de compra/aluguer
- `commissions`: Comissões dos afiliados
- `favorites`: Favoritos dos usuários

## Tecnologias

- PHP 7.4+
- MySQL
- HTML5/CSS3 (responsivo)
- JavaScript (localStorage para favoritos)
- PHPMailer para emails

## Desenvolvimento

A aplicação segue arquitetura MVC com foco em segurança, escalabilidade e UX moderna.

### Melhorias base de escalabilidade já aplicadas

- Reutilização de uma única conexão PDO por request para reduzir overhead de bootstrap.
- Suporte opcional a conexões persistentes via `DB_PERSISTENT=true` no `.env`.
- Paginação server-side na listagem `/properties`, evitando carregar o catálogo completo em memória por request.

## Monitoramento de Regressão (Rápido)

Para validação rápida antes de commit/deploy, execute:

```powershell
pwsh -File scripts/regression-smoke.ps1
```

Para pre-release local (estatico + HTTP real):

```powershell
pwsh -File scripts/pre-release.ps1 -BaseUrl http://localhost
```

Se o servidor local nao estiver ativo, execute apenas checks estaticos:

```powershell
pwsh -File scripts/pre-release.ps1 -SkipHttp
```

O smoke check valida:

- lint PHP da base ativa (`app/`, `src/`, `public/`, `config/`), ignorando `src/vendor/`
- consistência de regras críticas em controllers (POST + CSRF)
- consistência de formulários críticos em views (POST + token)
- presença da rota `moderate_users` no roteador
- cenários HTTP locais opcionais (`-RunHttp`) para:
	- `GET /login` (página e token CSRF)
	- `POST /authenticate` com credencial inválida (redirect esperado)
	- `POST /request/updateStatus/1` sem autenticação (redirect para login)
	- `POST /property/approve/1` e `POST /property/reject/1` sem autenticação (redirect para login)

Se qualquer verificação falhar, o script retorna código de saída `1`.

## Seed de Dados para Teste Completo

Para popular o banco com dados de teste cobrindo permissões, moderação, solicitações,
comissões, documentos, favoritos e notificações:

```powershell
pwsh -File scripts/seed-full-test-data.ps1
```

Arquivos envolvidos:

- `scripts/seed-full-test-data.ps1` (executor)
- `scripts/seed_full_test_data.sql` (dados idempotentes)

Logins de teste criados (senha para todos: `Teste@123`):

- `admin.seed@imobil.local` (super_admin)
- `moderador.seed@imobil.local` (moderador)
- `financeiro.seed@imobil.local` (financeiro)
- `suporte.seed@imobil.local` (suporte)
- `afiliado.seed@imobil.local` (utilizador afiliado)
- `proprietario.seed@imobil.local` (utilizador proprietário)
- `cliente.seed@imobil.local` (utilizador cliente)
- `pendente.seed@imobil.local` (conta pendente)

## Pipeline de Pre-release

Workflow configurado em `.github/workflows/pre-release-smoke.yml`:

- executa em `push`/`pull_request` para `main` e `workflow_dispatch`
- roda `pwsh -File scripts/regression-smoke.ps1`
- cobre checks estáticos de regressão em CI