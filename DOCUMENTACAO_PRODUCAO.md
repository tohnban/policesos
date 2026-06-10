# Documentação — Preparação para Produção

**Última revisão:** 2026-06-08

## 1. Checklist rápido

1. Copiar `.env.production.example` → `.env` e preencher valores reais
2. Executar deploy: `pwsh -File scripts/deploy-production.ps1`
3. Aplicar migrações SQL em `scripts/migration_*.sql`
4. Registar cron/workers (ver secção 4)
5. Validar HTTPS, email e login admin
6. Smoke HTTP contra o domínio real: `regression-smoke.ps1 -RunHttp -BaseUrl https://seudominio.ao`

## 2. Variáveis obrigatórias

| Variável | Produção |
|----------|----------|
| `APP_ENV` | `production` |
| `APP_URL` | URL HTTPS completa (ex.: `https://www.imobil.ao`) |
| `APP_DEBUG` | `false` |
| `DB_*` | Credenciais dedicadas (não `root` sem senha) |
| `EMAIL_ENABLED` | `true` |
| `SMTP_*` | Servidor real com TLS |

Se `APP_ENV=production` sem ficheiro `.env`, a aplicação responde 500.  
Se `APP_DEBUG` não estiver definido e `APP_ENV=production`, o debug fica **desactivado** automaticamente.

## 3. Pacote limpo para o servidor

Gera a pasta `deploy/` apenas com o essencial (sem documentação, testes, migrações nem ferramentas de dev):

```powershell
pwsh -File scripts/build-deploy-package.ps1
```

Conteúdo do pacote:
- `app/`, `config/`, `public/`, `src/` (com `vendor` de produção)
- `storage/` (estrutura vazia + `.htaccess`)
- `scripts/` — apenas schedulers e workers
- `index.php`, `.htaccess`, `.env.production.example`

**Não incluído** (fica só no ambiente de desenvolvimento): `*.md`, `.github/`, `phpstan*`, migrações SQL, seeds, auditorias.

Envie o conteúdo de `deploy/` para a Hostinger via FTP ou Gestor de Ficheiros.

## 4. Script de deploy (ambiente local)

### Windows (XAMPP)
```powershell
# 1. Configurar .env
Copy-Item .env.production.example .env
# Editar .env com valores reais

# 2. Deploy
pwsh -File scripts/deploy-production.ps1

# 3. Cron (Administrador)
.\scripts\cron_setup.ps1 -PhpPath C:\xampp\php\php.exe -RootDir C:\xampp\htdocs
```

### Linux
```bash
cp .env.production.example .env
# editar .env
chmod +x scripts/deploy-production.sh
./scripts/deploy-production.sh
```

O script executa:
1. `ensure-storage-dirs.php` — cria pastas de cache, logs, uploads
2. `composer install --no-dev --optimize-autoloader`
3. `production-check.php` — valida `.env`, permissões, vendor
4. `PageCache::flush()`
5. `regression-smoke.ps1` (opcional: `-SkipSmoke`)

Validação isolada:
```powershell
php scripts/production-check.php
```

## 5. Tarefas agendadas (obrigatórias)

| Script | Intervalo | Função |
|--------|-----------|--------|
| `requests_sla_scheduler.php` | 1h | SLA e expiração de solicitações |
| `commission_scheduler.php` | 6h | Comissões vencidas |
| `boost_expiration_scheduler.php` | 1h | Expiração de destaques |
| `subscription_scheduler.php` | 1h | Renovação de planos |
| `mail_queue_worker.php` | 5min | Fila de emails |

Workers opcionais: `image_queue_worker.php`, `notify_new_property_worker.php`, `report_queue_worker.php`.

### Crontab Linux (exemplo)
```
0 * * * *   php /var/www/imobil/scripts/requests_sla_scheduler.php
0 */6 * * * php /var/www/imobil/scripts/commission_scheduler.php
0 * * * *   php /var/www/imobil/scripts/boost_expiration_scheduler.php
0 * * * *   php /var/www/imobil/scripts/subscription_scheduler.php
*/5 * * * * php /var/www/imobil/scripts/mail_queue_worker.php
```

## 6. Segurança aplicada

### Headers HTTP (`public/index.php`)
- `X-Content-Type-Options`, `X-Frame-Options`, CSP
- `Strict-Transport-Security` quando HTTPS + `APP_ENV=production`

### Apache (`.htaccess`)
- Bloqueio de `.env`
- Bloqueio directo de `config/`, `app/`, `src/`, `storage/logs`, `storage/cache`, `storage/documents`
- `public/storage/uploads/` — acesso negado; ficheiros servidos via `/file/serve` (autenticado)
- Documentos de identificação — apenas via `/file/serve?path=storage/documents/...`

### Sessão
- `httponly`, `samesite=Lax`, `secure` em HTTPS

### Erros
- `display_errors=0` sempre
- Respostas genéricas em produção com `request_id` para suporte

## 7. Estrutura de directorios (runtime)

```
storage/cache/          # escrita — cache de página
storage/logs/           # escrita — app.log
storage/documents/      # escrita — documentos de registo
storage/uploads/profiles/
public/storage/uploads/ # comprovativos (bloqueado via .htaccess)
```

Permissões recomendadas: `755` directorios, utilizador do Apache/IIS com escrita em `storage/` e `public/storage/`.

## 8. Composer em produção

```bash
cd src
composer install --no-dev --optimize-autoloader --no-interaction
```

Ou: `composer run production-deps` (dentro de `src/`).

**Não** instalar dependências de desenvolvimento (`php-cs-fixer`, `phpstan`) no servidor.

## 9. Pós-deploy

- [ ] Login admin e verificação de permissões
- [ ] Envio de email de teste
- [ ] Criar solicitação de teste end-to-end
- [ ] Confirmar schedulers a correr (verificar logs JSON no stdout)
- [ ] Backup automático da base de dados configurado
- [ ] Monitorizar `storage/logs/app.log`

## 10. Rollback

1. Restaurar código anterior (git tag / release)
2. `composer install --no-dev` na versão anterior
3. `PageCache::flush()`
4. Reverter migração SQL se necessário (backup prévio obrigatório)

## 11. Documentação relacionada

- [DOCUMENTACAO_OPERACAO.md](DOCUMENTACAO_OPERACAO.md) — operação diária
- [DOCUMENTACAO_ARQUITETURA.md](DOCUMENTACAO_ARQUITETURA.md) — arquitectura e guards
- [DOCUMENTACAO_INDICE.md](DOCUMENTACAO_INDICE.md) — índice geral
