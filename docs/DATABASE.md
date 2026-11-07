# Banco de dados

SQLite é acessado exclusivamente via PDO. O caminho padrão é `storage/database.sqlite`, configurado em `config/database.php`. A conexão habilita foreign keys, WAL e timeout de espera.

## Schema da Fase 1

### `users`

`id` (INTEGER PK), `name`, `email` (único, sem distinção de caixa), `password_hash`, `created_at`, `updated_at`.

### `user_settings`

`id` (INTEGER PK), `user_id` (único, FK para users com cascade), `currency`, `date_format`, `theme` (`light` ou `dark`), `created_at`, `updated_at`.

Migrations são arquivos SQL ordenados em `database/migrations/` e registradas na tabela `migrations` após execução bem-sucedida.

A migration `006_add_account_status_and_last_login.sql` acrescenta `users.status` (`active`/`inactive`, padrão `active`) e `users.last_login_at`, atualizado a cada autenticação bem-sucedida.

## Fase 2

- `accounts`: saldo inicial em centavos e data-base; contas podem ser desativadas sem apagar histórico.
- `categories`: separa income/expense por usuário; nomes não se repetem por tipo.
- `transactions`: receita/despesa em centavos, status pendente/efetivada/cancelada e FKs compostas que validam a propriedade de conta e categoria pelo mesmo usuário.
- `transfers`: operação única com origem/destino, valor e data; FKs compostas garantem propriedade das duas contas.

Os saldos consideram o saldo inicial e eventos efetivados entre a data-base e hoje. Transferências alteram saldos individuais e ficam fora dos totais consolidados de receitas/despesas.

## Fase 4

- `credit_cards`, `card_purchases`, `invoices`, `invoice_items`: compras e parcelas separadas das saídas bancárias; cada item é uma parcela em uma fatura.
- `invoice_payments`: liquidação da fatura e conta debitada. Reduz o saldo sem criar uma segunda despesa.
- `payables`, `receivables`: compromissos previstos; ao liquidar, uma transação SQL cria a movimentação efetivada.
- `recurring_transactions`, `recurrence_occurrences`: regras e chave única por regra/data para impedir ocorrências duplicadas.

## Fase 5

- `budgets`: limite mensal por categoria e usuário, com unicidade por categoria/mês; valores monetários em centavos.
- `budget_alert_levels`: percentuais de alerta configuráveis por orçamento.
- `goals`: objetivos com valor, prazo opcional, conta/categoria opcionais, cor e status.
- `goal_contributions`: histórico de aportes e resgates; pode apontar para uma transferência que movimentou saldo entre contas.

## Fase 7

- `investments`, `investment_transactions`: carteira manual com quantidade, preço médio, custo, valor de mercado e histórico de aplicações/resgates/rendimentos/avaliações.
- `assets`: bens manuais com valor e data da última avaliação.
- `liabilities`: obrigações com valor original e saldo restante; pagamentos efetivados também criam despesa na conta selecionada.
- `net_worth_snapshots`: um fechamento por usuário/mês para acompanhar a evolução patrimonial.
- `transactions.external_id`: identificador de origem com índice único parcial por usuário/conta para impedir reimportação de OFX/CSV.

Backups são arquivos JSON versionados e assinados por checksum que incluem somente registros vinculados ao usuário autenticado. A restauração valida o proprietário e as referências e recria as chaves internas, preservando dados de outros usuários.
