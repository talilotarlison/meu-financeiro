# Meu Financeiro

Aplicação web financeira pessoal em PHP 8.2+ e SQLite, sem frameworks ou serviços pagos. O desenvolvimento segue as fases descritas em [docs/PDR.md](docs/PDR.md); as Fases 1 a 8 estão implementadas.

## Requisitos

- PHP 8.2 ou superior com extensões PDO e PDO_SQLite habilitadas.
- Apache com `mod_rewrite` ou servidor equivalente, apontando o document root para `public/`.

## Executar localmente

Na raiz do projeto:

```powershell
php -S 127.0.0.1:8000 -t public public/index.php
```

Abra `http://127.0.0.1:8000`. O banco e as tabelas são criados automaticamente em `storage/database.sqlite` na primeira requisição. O usuário precisa ter permissão de escrita em `storage/`.

## Segurança e configuração

- Configure `APP_ENV=production` e `APP_DEBUG=0` em produção. O debug vem desligado por padrão.
- Sirva somente a pasta `public/`; `storage/`, `config/`, `database/` e `app/` não devem ser acessíveis via web.
- Use HTTPS em produção; cookies de sessão são `HttpOnly`, `SameSite=Lax` e `Secure` quando HTTPS está ativo.
- A aplicação cria as migrations automaticamente, usa PDO com prepared statements, proteção CSRF e hash de senha.
- SQLite WAL e chaves estrangeiras são habilitados na conexão.

## Fases implementadas

**Fase 1:** cadastro, login/logout, limitação de tentativas na sessão, preferências de tema persistentes e layout responsivo.

**Fase 2:** contas, categorias, receitas, despesas, transferências, extrato, filtros e dashboard alimentado por dados reais. Valores são armazenados em centavos. Pendências não alteram saldo; transferências não entram como receita ou despesa; cancelamentos preservam o histórico.

**Fase 3:** indicadores por período, pendências por vencimento e gráficos mensais, por categoria, semanais e de evolução do saldo. Chart.js é carregado do jsDelivr no dashboard; os dados numéricos do painel funcionam sem a biblioteca.

**Fase 4:** cartões, parcelamento por ciclo de fatura, pagamento sem duplicidade, contas a pagar/receber com baixa e recorrências que geram ocorrências sem duplicar.

**Fase 5:** orçamentos mensais por categoria, alertas de limite e metas com progresso e histórico de aportes/resgates. Aportes e resgates em metas vinculadas a contas são refletidos como transferências.

**Fase 6:** relatórios por período, evolução mensal, análise de despesas por categoria e exportação para CSV e PDF.

**Fase 7:** investimentos manuais, patrimônio e dívidas, importação de extratos OFX/CSV com prévia e deduplicação, e backup/restauração por usuário.

**Fase 8:** PWA instalável, modo offline sem cache de dados financeiros, controles de segurança para hospedagem, otimização de séries e testes automatizados sem dependências externas.

## Testes automatizados

O conjunto usa SQLite em memória e recusa rodar sem a configuração isolada de teste. No PowerShell:

```powershell
$env:APP_ENV = 'test'
$env:DATABASE_PATH = ':memory:'
php tests/run.php
Remove-Item Env:APP_ENV, Env:DATABASE_PATH
```

Consulte [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) para publicação Apache/HTTPS, armazenamento e preparação de produção.

Consulte [docs/PROJECT_STATUS.md](docs/PROJECT_STATUS.md), [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md), [docs/DATABASE.md](docs/DATABASE.md), [docs/TASKS.md](docs/TASKS.md), [docs/CHANGELOG.md](docs/CHANGELOG.md) e [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).
