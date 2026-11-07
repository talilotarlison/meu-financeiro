# Arquitetura

- `public/index.php`: entrada web, inicialização da sessão, migrations e despacho de rotas.
- `public/sw.js` e `public/manifest.webmanifest`: PWA, cache seletivo de arquivos públicos e instalação.
- `routes/web.php`: mapa de rotas por método HTTP.
- `app/Controllers/`: validação da requisição e coordenação dos fluxos.
- `app/Services/FinancialService.php`: conversão de moeda e cálculo de saldos em centavos.
- `app/Services/DashboardService.php`: validação de intervalos e agregação de indicadores e séries do painel.
- `app/Services/RecurrenceService.php`: geração idempotente de ocorrências e cálculo da próxima data.
- `app/Services/BudgetService.php`: consolidação de gastos mensais e alertas por categoria.
- `app/Services/GoalService.php`: progresso das metas a partir do histórico de aportes e resgates.
- `app/Services/ReportService.php`: agregação de relatórios, exportação CSV e geração de PDF.
- `app/Services/InvestmentService.php`: posições manuais de investimentos e variação da carteira.
- `app/Services/PatrimonyService.php`: totalização de ativos, passivos e snapshots patrimoniais mensais.
- `app/Services/ImportService.php`: leitura OFX/CSV, normalização e detecção de possíveis duplicidades.
- `app/Services/BackupService.php`: backup e restauração isolados por usuário com checksum e remapeamento de IDs.
- `app/Database.php`: conexão PDO única por processo e configuração SQLite.
- `app/Migrator.php`: executa migrations SQL pendentes dentro de transação.
- `app/Helpers/functions.php`: escape HTML, sessão, CSRF e renderização.
- `resources/views/`: templates PHP e layout.
- `public/assets/`: CSS e JavaScript públicos.
- `database/migrations/`: schema versionado.
- `storage/`: arquivos privados, banco, backups e logs.

O document root deve apontar exclusivamente para `public/`. As operações que alteram dados usam POST e token CSRF; toda consulta por usuário aplica `user_id` no SQL. Transferências são gravadas em transação SQL; movimentações canceladas permanecem no histórico.

O runner `tests/run.php` exige `APP_ENV=test` e `DATABASE_PATH=:memory:` para não acessar o arquivo real. O caminho do banco pode ser definido por `DATABASE_PATH` para manter os dados fora do código.

Compras no cartão são agregadas a partir das parcelas. Pagamentos de fatura são saídas em `invoice_payments`, sem criar uma transação de despesa. Baixas de compromissos e geração de parcelas são atômicas.

Orçamentos somam despesas efetivadas e parcelas de cartão pelo vencimento da fatura, sem somar novamente o pagamento da fatura. Metas mantêm histórico próprio; se vinculadas a uma conta, aportes e resgates também são registrados como transferências para manter os saldos consistentes.

Relatórios reutilizam os períodos e agregações do dashboard, acrescentam a distribuição completa de despesas por categoria e oferecem downloads CSV/PDF. Os relatórios são somente leitura e não exigem alteração no schema.

Investimentos e bens são informados manualmente, sem cotações externas. Pagamentos de passivos atualizam o saldo da dívida e criam uma despesa efetivada dentro da mesma transação. Importações ficam em sessão durante a prévia; a confirmação grava apenas linhas selecionadas e não duplicadas. Backups contêm somente os dados do usuário e ficam fora do document root.
