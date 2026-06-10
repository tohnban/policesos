# Documentação Técnica — Operação e Deploy

**Última revisão:** 2026-06-08

## 1. Variáveis de ambiente (`.env`)

Copiar `.env.example` → `.env`.

| Variável | Descrição | Default |
|----------|-----------|---------|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | MySQL | localhost / imobil_db |
| `APP_ENV` | `development` ou `production` | development |
| `APP_URL` | URL base (links, mail) | http://localhost |
| `APP_DEBUG` | Erros detalhados | true |
| `SESSION_LIFETIME` | Segundos de sessão | 1800 |
| `DB_PERSISTENT` | Conexão PDO persistente | false |
| `LOG_CHANNEL` | `file` para `storage/logs/app.log` | file |
| `LOG_LEVEL` | Nível mínimo de log | info |
| `EMAIL_ENABLED` | Envio real de email | false |
| `MAIL_*`, `SMTP_*` | Configuração SMTP | ver .env.example |

Settings adicionais em runtime na tabela `settings` (editáveis no painel super_admin).

## 2. Tarefas agendadas (cron)

### Instalação Windows
```powershell
# Executar como Administrador
.\scripts\cron_setup.ps1 -PhpPath C:\xampp\php\php.exe -RootDir C:\xampp\htdocs
```

### Tarefas registadas

| Tarefa | Script | Intervalo | Função |
|--------|--------|-----------|--------|
| Imobil_SLA_Scheduler | `requests_sla_scheduler.php` | 1h | Lembretes SLA + expiração a 30 dias |
| Imobil_Commission_Scheduler | `commission_scheduler.php` | 6h | Comissões vencidas + backfill transacções |
| Imobil_Boost_Expiration_Scheduler | `boost_expiration_scheduler.php` | 1h | Expira destaques |
| Imobil_Subscription_Scheduler | `subscription_scheduler.php` | 1h | Renovação/downgrade de planos |
| Imobil_Mail_Queue_Worker | `mail_queue_worker.php` | 5min | Fila de emails |

### Workers opcionais (registar manualmente)

| Script | Função |
|--------|--------|
| `image_queue_worker.php` | Redimensionamento/processamento de imagens |
| `report_queue_worker.php` | Relatórios pesados em background |
| `notify_new_property_worker.php` | Alertas de novos imóveis |

Todos os scripts CLI recusam execução via HTTP (`PHP_SAPI !== 'cli'`).

## 3. Migrações de base de dados

Ficheiros em `scripts/migration_*.sql` — aplicar em ordem cronológica no nome do ficheiro.

Convenção: `migration_YYYYMMDD_descricao.sql`

Exemplos relevantes:
- `migration_20260416_request_lifecycle_and_compliance.sql` — ciclo de solicitações
- `migration_20260514_request_payment_confirmation_flow.sql` — trilha de pagamento
- `migration_20260515_background_jobs_queue.sql` — fila de jobs
- `migration_20260516_api_token_auth.sql` — tokens API

Não há runner automático de migrações; aplicar via cliente MySQL ou script dedicado quando existir.

## 4. Logging e monitorização

### Log de aplicação
- Ficheiro: `storage/logs/app.log`
- Formato: JSON por linha
- Correlacionar pedidos: header `X-Request-Id`

### Log de acções (auditoria)
- Tabela `logs` — acções de utilizadores admin e eventos críticos
- Modelo: `App\model\Log`

### Saída dos schedulers
Scripts CLI imprimem JSON no stdout com contadores (`expired`, `alerts_sent`, etc.) — útil para monitorização externa.

## 5. Qualidade e regressão

```powershell
# Lint + auditorias de rotas/imports
pwsh -File scripts/regression-smoke.ps1

# Com cenários HTTP (servidor local activo)
pwsh -File scripts/regression-smoke.ps1 -RunHttp -BaseUrl http://localhost

# Pré-release completo
pwsh -File scripts/pre-release.ps1 -BaseUrl http://localhost

# Estilo + análise estática
pwsh -File scripts/quality-check.ps1
pwsh -File scripts/quality-check.ps1 -FixCs
```

### O que o smoke valida
- Lint PHP em `app/`, `src/`, `public/`, `config/`
- 6 auditorias PHP (rotas, imports, classes, sombras, duplicados)
- Presença de CSRF em formulários críticos
- CSP sem `unsafe-inline`
- Sem `<script>` inline nas views
- Cenários HTTP opcionais (login, API health, páginas públicas)

## 6. Seed de dados de teste

```powershell
pwsh -File scripts/seed-full-test-data.ps1
```

Cria utilizadores por perfil, imóveis, solicitações, comissões, documentos e notificações. Senha universal: `Teste@123`.

## 7. Cache e deploy

Após deploy ou alteração de política CSRF:
```php
// Via tinker ou script one-off
\Src\classes\PageCache::flush();
```

Limpar `storage/cache/cache_*.php` se necessário.

## 8. Fila de emails

Quando `mail_queue_enabled = 1` (default), emails passam pela tabela `background_jobs` tipo `send_mail`.

Worker: `mail_queue_worker.php` (5 min via cron_setup).

Settings: `mail_queue_batch_size`, `mail_queue_max_attempts`, `mail_queue_lock_timeout_seconds`.

## 9. CI/CD

GitHub Actions: `.github/workflows/pre-release-smoke.yml`
- Trigger: push/PR para `main`, `workflow_dispatch`
- Comando: `regression-smoke.ps1`

## 10. Estrutura de storage

```
storage/
├── cache/          # PageCache (ficheiros PHP)
├── logs/           # app.log
└── uploads/        # documentos, comprovativos, imagens
```

Garantir permissões de escrita para o utilizador do servidor web.

## 11. Checklist de deploy

Ver guia completo: [DOCUMENTACAO_PRODUCAO.md](DOCUMENTACAO_PRODUCAO.md)

Resumo:
1. `pwsh -File scripts/deploy-production.ps1` (ou `deploy-production.sh`)
2. `.env` a partir de `.env.production.example`
3. Migrações SQL aplicadas
4. Tarefas agendadas (`cron_setup.ps1` ou crontab Linux)
5. `php scripts/production-check.php` sem erros
