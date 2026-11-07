# PRD — Sistema de Gestão Financeira Pessoal

**Nome provisório:** Meu Financeiro  
**Versão:** 1.0  
**Status:** Especificação para desenvolvimento  
**Tecnologia:** PHP + SQLite + HTML + CSS + JavaScript  
**Tipo:** Aplicação web responsiva, com possibilidade de instalação como PWA.

---

# 1. Visão geral do projeto

## 1.1. Objetivo

Desenvolver uma plataforma web de controle financeiro pessoal, inspirada nas funcionalidades do Meu Dinheiro, permitindo que usuários administrem suas finanças em um único ambiente.

O sistema deverá permitir o cadastro de receitas, despesas, contas bancárias, cartões de crédito, transferências, orçamentos, metas financeiras e investimentos.

A aplicação deverá apresentar informações financeiras por meio de dashboards, gráficos, relatórios e indicadores que facilitem o acompanhamento da saúde financeira do usuário.

## 1.2. Público-alvo

- Pessoas que desejam organizar suas finanças pessoais.
- Usuários que precisam controlar receitas e despesas.
- Pessoas que possuem múltiplas contas bancárias e cartões.
- Pessoas que desejam controlar dívidas, parcelamentos e investimentos.
- Usuários que desejam planejar objetivos financeiros.

## 1.3. Princípios do sistema

1. Interface simples, moderna e intuitiva.
2. Responsividade para computadores, tablets e smartphones.
3. Separação completa dos dados financeiros por usuário.
4. Cadastro rápido de movimentações financeiras.
5. Cálculos financeiros consistentes.
6. Gráficos e relatórios atualizados automaticamente.
7. Código organizado e modular.
8. Não depender de APIs pagas.
9. Não depender de conexão com instituições bancárias.
10. Possibilidade de expansão futura.

---

# 2. Tecnologias e arquitetura

## 2.1. Stack tecnológica

| Tecnologia | Utilização |
|---|---|
| PHP 8.3+ | Backend e regras de negócio |
| SQLite 3 | Banco de dados |
| PDO | Comunicação com o banco |
| HTML5 | Estrutura das páginas |
| CSS3 | Estilização |
| JavaScript | Interatividade e operações dinâmicas |
| Chart.js | Gráficos financeiros |
| Bootstrap 5 | Componentes e responsividade |
| Font Awesome ou Lucide | Ícones |

Não utilizar frameworks PHP pesados inicialmente. O objetivo é manter a aplicação leve, simples de instalar e fácil de manter.

## 2.2. Arquitetura

Utilizar uma arquitetura MVC simplificada, separando responsabilidades:

- Models: acesso e manipulação dos dados.
- Controllers: processamento das requisições.
- Services: regras de negócio e cálculos financeiros.
- Views: interface apresentada ao usuário.
- Middleware: autenticação e autorização.
- Config: configurações da aplicação e conexão com banco.

O código deverá ser organizado por módulos funcionais.

## 2.3. Banco de dados

Utilizar SQLite com PDO.

O banco deverá ficar em diretório privado, fora da pasta pública do servidor.

Arquivo principal:

`/storage/database.sqlite`

Requisitos:

- Utilizar chaves estrangeiras.
- Criar índices para consultas frequentes.
- Utilizar transações SQL em operações financeiras que envolvam múltiplas alterações.
- Utilizar prepared statements.
- Implementar migrations para criação e atualização das tabelas.
- Configurar o SQLite com WAL quando adequado.
- Criar mecanismos de backup e restauração.

**Regra fundamental:** todas as tabelas que armazenam dados pertencentes aos usuários deverão possuir uma referência ao `user_id`, direta ou por meio de uma relação validada.

O sistema jamais poderá permitir que um usuário consulte, edite ou exclua informações de outro usuário.

---

# 3. Módulo de autenticação e usuários

## 3.1. Cadastro

Permitir que o usuário crie uma conta informando:

- Nome completo.
- E-mail.
- Senha.
- Confirmação de senha.

Validações:

- E-mail válido e único.
- Senha com requisitos mínimos de segurança.
- Confirmação de senha.
- Impedir cadastros duplicados.

## 3.2. Login

Funcionalidades:

- Login por e-mail e senha.
- Opção para visualizar senha.
- Manter sessão autenticada.
- Encerrar sessão.
- Redirecionamento para dashboard após login.

## 3.3. Segurança da autenticação

- Utilizar `password_hash()` e `password_verify()`.
- Regenerar o ID da sessão após autenticação.
- Utilizar cookies HttpOnly, Secure em HTTPS e SameSite.
- Implementar proteção CSRF.
- Limitar tentativas consecutivas de login.
- Implementar recuperação de senha em etapa posterior.
- Não armazenar senhas em texto puro.

## 3.4. Perfil do usuário

Permitir:

- Editar nome.
- Alterar e-mail.
- Alterar senha.
- Definir moeda padrão.
- Definir formato de data.
- Definir preferências de visualização.
- Excluir conta.

A exclusão da conta deverá solicitar confirmação e remover os dados associados conforme a política de retenção definida.

---

# 4. Dashboard — Painel financeiro

O dashboard será a página inicial após o login.

Deverá apresentar uma visão consolidada da situação financeira do usuário.

## 4.1. Indicadores principais

Exibir cards com:

1. Saldo total das contas.
2. Receitas do mês.
3. Despesas do mês.
4. Resultado financeiro do mês.
5. Contas a pagar.
6. Contas a receber.
7. Total das faturas abertas.
8. Patrimônio líquido, quando o módulo patrimonial estiver disponível.

## 4.2. Gráficos

Implementar gráficos utilizando Chart.js:

- Receitas versus despesas.
- Evolução mensal do saldo.
- Distribuição de despesas por categoria.
- Distribuição de receitas por categoria.
- Despesas por semana.
- Evolução patrimonial.
- Comparação entre meses.

## 4.3. Movimentações recentes

Apresentar uma lista com:

- Descrição.
- Categoria.
- Conta.
- Data.
- Tipo de movimentação.
- Valor.

Diferenciar visualmente receitas, despesas e transferências.

## 4.4. Filtros

Permitir selecionar:

- Mês atual.
- Mês anterior.
- Últimos três meses.
- Últimos seis meses.
- Ano atual.
- Período personalizado.

Todos os indicadores e gráficos deverão respeitar o período selecionado.

---

# 5. Módulo de movimentações financeiras

Este será o principal módulo do sistema.

## 5.1. Cadastro de receitas

Permitir registrar:

- Descrição.
- Valor.
- Data de recebimento.
- Categoria.
- Conta de destino.
- Observações.
- Status: recebida ou pendente.
- Recorrência.
- Tags.

Exemplos:

- Salário.
- Freelances.
- Vendas.
- Rendimentos.
- Aluguéis.
- Outras receitas.

## 5.2. Cadastro de despesas

Permitir registrar:

- Descrição.
- Valor.
- Data da despesa.
- Categoria.
- Conta utilizada.
- Forma de pagamento.
- Observações.
- Status: paga ou pendente.
- Recorrência.
- Tags.

Exemplos:

- Alimentação.
- Transporte.
- Moradia.
- Educação.
- Saúde.
- Lazer.
- Assinaturas.
- Compras.
- Serviços.

## 5.3. Gerenciamento de movimentações

Funcionalidades:

- Listar todas as movimentações.
- Pesquisar por descrição.
- Filtrar por período.
- Filtrar por categoria.
- Filtrar por conta.
- Filtrar por tipo.
- Filtrar por status.
- Editar movimentação.
- Excluir movimentação.
- Duplicar lançamento.
- Marcar como pago ou recebido.
- Visualizar detalhes.

## 5.4. Recorrência

Permitir criar lançamentos recorrentes:

- Diário.
- Semanal.
- Mensal.
- Anual.
- Personalizado.

Exemplos:

- Salário mensal.
- Aluguel.
- Internet.
- Energia elétrica.
- Mensalidades.

O sistema deverá gerar os lançamentos futuros sem duplicá-los.

O usuário deverá poder cancelar a recorrência sem apagar o histórico anterior.

## 5.5. Parcelamentos

Permitir dividir despesas em parcelas.

Exemplo:

Compra de R$ 1.200,00 em 6 parcelas de R$ 200,00.

O sistema deverá:

- Criar as seis parcelas.
- Identificar o número da parcela.
- Registrar o total de parcelas.
- Definir datas de vencimento.
- Permitir visualizar o parcelamento completo.
- Permitir editar ou cancelar parcelas futuras.
- Preservar o histórico das parcelas já pagas.

## 5.6. Transferências

Permitir transferir dinheiro entre contas cadastradas.

Exemplo:

Transferência de R$ 500,00 da conta corrente para a poupança.

Regras:

- Registrar conta de origem e destino.
- Debitar da origem.
- Creditar no destino.
- Não contabilizar como receita ou despesa.
- Impedir transferências para a mesma conta.
- Garantir consistência da operação utilizando transação SQL.

---

# 6. Módulo de contas financeiras

Permitir cadastrar várias contas.

## 6.1. Tipos de contas

- Conta corrente.
- Conta poupança.
- Carteira em dinheiro.
- Conta digital.
- Conta de pagamento.
- Conta de investimentos.
- Outras.

## 6.2. Cadastro

Campos:

- Nome da conta.
- Tipo.
- Instituição financeira.
- Saldo inicial.
- Data do saldo inicial.
- Cor de identificação.
- Descrição.
- Status ativo/inativo.

## 6.3. Funcionalidades

- Listar contas.
- Consultar saldo individual.
- Consultar saldo consolidado.
- Visualizar extrato.
- Filtrar movimentações.
- Editar informações.
- Desativar conta.
- Consultar histórico de movimentações.

## 6.4. Cálculo do saldo

O saldo deverá considerar:

Saldo inicial + receitas efetivadas - despesas efetivadas + transferências recebidas - transferências enviadas.

Não considerar lançamentos pendentes como movimentações efetivadas.

---

# 7. Módulo de cartões de crédito

## 7.1. Cadastro de cartões

Campos:

- Nome do cartão.
- Instituição financeira.
- Bandeira.
- Limite total.
- Dia de fechamento.
- Dia de vencimento.
- Conta utilizada para pagamento.
- Cor de identificação.
- Status.

## 7.2. Lançamentos no cartão

Permitir registrar despesas utilizando um cartão de crédito.

Campos:

- Descrição.
- Valor total.
- Categoria.
- Cartão.
- Data da compra.
- Quantidade de parcelas.
- Observações.

## 7.3. Faturas

Implementar controle de faturas mensais.

Funcionalidades:

- Fatura atual.
- Próximas faturas.
- Faturas anteriores.
- Valor total da fatura.
- Compras parceladas.
- Data de fechamento.
- Data de vencimento.
- Status aberta, fechada ou paga.
- Histórico de pagamentos.

## 7.4. Pagamento da fatura

Ao pagar uma fatura:

- Registrar a saída na conta bancária selecionada.
- Registrar o pagamento da fatura.
- Atualizar o saldo da conta.
- Não contabilizar novamente as compras como despesas.
- Manter o histórico completo.

**Regra importante:** compras no cartão devem ser contabilizadas como despesas no controle de gastos, enquanto o pagamento da fatura deve representar a liquidação da obrigação, sem duplicar despesas.

---

# 8. Módulo de categorias e subcategorias

Permitir organizar receitas e despesas.

## 8.1. Categorias padrão

Despesas:

- Alimentação.
- Moradia.
- Transporte.
- Saúde.
- Educação.
- Lazer.
- Compras.
- Serviços.
- Assinaturas.
- Impostos.
- Outros.

Receitas:

- Salário.
- Freelance.
- Vendas.
- Investimentos.
- Aluguéis.
- Outros.

## 8.2. Funcionalidades

- Criar categorias personalizadas.
- Editar categorias.
- Excluir categorias sem movimentações vinculadas.
- Criar subcategorias.
- Definir cores.
- Definir ícones.
- Separar categorias de receitas e despesas.
- Consultar valores movimentados por categoria.

Não permitir que categorias de receitas sejam utilizadas em despesas e vice-versa.

---

# 9. Módulo de orçamento e limites de gastos

Permitir definir limites mensais de despesas.

## 9.1. Cadastro de orçamento

Campos:

- Nome.
- Categoria.
- Valor limite.
- Período.
- Mês de referência.

## 9.2. Acompanhamento

Exibir:

- Valor planejado.
- Valor gasto.
- Valor disponível.
- Percentual utilizado.
- Saldo restante.

## 9.3. Alertas

Exibir avisos quando:

- O usuário atingir 50% do limite.
- O usuário atingir 80% do limite.
- O usuário atingir 100% do limite.
- O usuário ultrapassar o orçamento.

Permitir configurar os percentuais de alerta.

---

# 10. Módulo de metas financeiras

Permitir que o usuário estabeleça objetivos financeiros.

## 10.1. Cadastro

Campos:

- Nome da meta.
- Descrição.
- Valor desejado.
- Valor acumulado.
- Data de início.
- Data prevista de conclusão.
- Conta vinculada opcional.
- Categoria.
- Cor.

Exemplos:

- Reserva de emergência.
- Comprar um computador.
- Comprar um veículo.
- Fazer uma viagem.
- Comprar uma casa.

## 10.2. Acompanhamento

Exibir:

- Valor acumulado.
- Valor restante.
- Percentual concluído.
- Prazo restante.
- Evolução da meta.

Permitir registrar aportes e retiradas, mantendo histórico.

---

# 11. Módulo de contas a pagar e receber

Criar uma área específica para compromissos financeiros.

## 11.1. Contas a pagar

Campos:

- Descrição.
- Valor.
- Categoria.
- Data de vencimento.
- Conta de pagamento.
- Status.
- Observações.

## 11.2. Contas a receber

Campos:

- Descrição.
- Valor.
- Categoria.
- Data prevista.
- Conta de recebimento.
- Status.

## 11.3. Funcionalidades

- Listar compromissos.
- Identificar contas vencidas.
- Identificar contas próximas do vencimento.
- Marcar como pago.
- Marcar como recebido.
- Adiar vencimento.
- Criar lançamentos recorrentes.
- Visualizar calendário financeiro.

---

# 12. Módulo de relatórios e análises

Disponibilizar relatórios que ajudem o usuário a compreender seu comportamento financeiro.

## 12.1. Relatórios disponíveis

1. Receitas por período.
2. Despesas por período.
3. Receitas por categoria.
4. Despesas por categoria.
5. Fluxo de caixa.
6. Saldo por conta.
7. Despesas por cartão.
8. Evolução mensal.
9. Contas pendentes.
10. Orçamentos utilizados.
11. Evolução das metas.
12. Patrimônio líquido (quando implementado).

## 12.2. Filtros

- Período.
- Categoria.
- Conta.
- Cartão.
- Tipo de movimentação.

## 12.3. Exportação

Permitir:

- Exportar CSV.
- Exportar Excel, futuramente.
- Gerar relatórios em PDF.
- Imprimir relatórios.

---

# 13. Módulo de investimentos — expansão

Desenvolver uma área para controle manual dos investimentos.

Não será necessário consultar cotações externas inicialmente.

## 13.1. Tipos de investimentos

- Tesouro Direto.
- CDB.
- LCI e LCA.
- Poupança.
- Ações.
- Fundos imobiliários.
- ETFs.
- Fundos de investimento.
- Criptomoedas.
- Previdência privada.
- Outros.

## 13.2. Cadastro de ativos

Campos:

- Nome do ativo.
- Tipo.
- Instituição.
- Quantidade.
- Preço médio.
- Valor investido.
- Data da aplicação.
- Observações.

## 13.3. Funcionalidades

- Registrar aplicações.
- Registrar resgates.
- Registrar rendimentos.
- Consultar posição.
- Consultar histórico.
- Acompanhar evolução da carteira.
- Visualizar distribuição por tipo de ativo.

Os valores de mercado deverão ser informados manualmente inicialmente.

---

# 14. Módulo de patrimônio e dívidas — expansão

Permitir acompanhar a evolução patrimonial do usuário.

## 14.1. Ativos

- Dinheiro.
- Contas bancárias.
- Investimentos.
- Imóveis.
- Veículos.
- Outros bens.

## 14.2. Passivos

- Empréstimos.
- Financiamentos.
- Dívidas.
- Parcelamentos.
- Outras obrigações.

## 14.3. Indicadores

Patrimônio líquido = total de ativos - total de passivos.

Exibir evolução patrimonial mensal.

---

# 15. Módulo de importação e backup

## 15.1. Importação financeira

Implementar futuramente importação de arquivos:

- OFX.
- CSV.

O sistema deverá:

- Ler os dados do arquivo.
- Exibir uma prévia.
- Permitir mapear colunas.
- Identificar possíveis duplicidades.
- Permitir selecionar os lançamentos.
- Confirmar importação.
- Registrar os dados na conta selecionada.

Não realizar importações bancárias automáticas nesta primeira versão.

## 15.2. Backup

Permitir:

- Criar backup do banco de dados.
- Baixar arquivo de backup.
- Restaurar backup.
- Exibir data do último backup.
- Solicitar confirmação antes de restaurar.

A restauração deverá validar a integridade do arquivo e evitar sobrescrever dados sem confirmação.

---

# 16. Interface e experiência do usuário

## 16.1. Layout principal

Criar uma interface moderna, inspirada em dashboards de aplicações SaaS financeiras.

Estrutura:

- Sidebar lateral.
- Cabeçalho superior.
- Área principal de conteúdo.
- Cards de indicadores.
- Gráficos.
- Tabelas.
- Modais para cadastros.

## 16.2. Menu lateral

Organizar as funcionalidades:

**Visão geral**
- Dashboard.

**Movimentações**
- Receitas e despesas.
- Transferências.
- Contas a pagar.
- Contas a receber.

**Organização**
- Contas.
- Cartões.
- Categorias.
- Orçamentos.
- Metas.

**Análises**
- Relatórios.
- Fluxo de caixa.

**Patrimônio**
- Investimentos.
- Ativos e passivos.

**Configurações**
- Perfil.
- Preferências.
- Backup.
- Segurança.

## 16.3. Design

Características desejadas:

- Visual limpo.
- Design responsivo.
- Boa hierarquia visual.
- Cards com bordas suaves.
- Gráficos interativos.
- Ícones intuitivos.
- Feedback visual nas operações.
- Modais de cadastro.
- Tabelas com paginação.
- Estados vazios informativos.
- Mensagens de sucesso e erro.

## 16.4. Tema

Implementar:

- Tema claro.
- Tema escuro.
- Persistência da preferência do usuário.

## 16.5. Responsividade

No celular:

- Sidebar transformada em menu recolhível.
- Cards adaptados.
- Tabelas com rolagem ou apresentação em lista.
- Formulários adaptados.
- Botões acessíveis.
- Navegação otimizada para toque.

---

# 17. Estrutura sugerida de pastas

```text
meu-financeiro/
│
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── TransactionController.php
│   │   ├── AccountController.php
│   │   ├── CardController.php
│   │   ├── CategoryController.php
│   │   ├── BudgetController.php
│   │   ├── GoalController.php
│   │   └── ReportController.php
│   │
│   ├── Models/
│   ├── Services/
│   ├── Middleware/
│   ├── Helpers/
│   └── Validators/
│
├── config/
│   ├── app.php
│   └── database.php
│
├── database/
│   ├── migrations/
│   └── seeds/
│
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── .htaccess
│
├── resources/
│   └── views/
│       ├── layouts/
│       ├── auth/
│       ├── dashboard/
│       ├── transactions/
│       ├── accounts/
│       ├── cards/
│       ├── categories/
│       ├── budgets/
│       ├── goals/
│       └── reports/
│
├── routes/
│   └── web.php
│
├── storage/
│   ├── database.sqlite
│   ├── backups/
│   └── logs/
│
├── tests/
│
├── .env
├── .gitignore
└── README.md
```

A pasta `public` deverá ser o único diretório acessível diretamente pelo navegador. O banco, backups, configurações e arquivos internos devem permanecer protegidos.

---

# 18. Estrutura inicial do banco de dados

Criar as seguintes tabelas:

| Tabela | Finalidade |
|---|---|
| users | Usuários |
| user_settings | Preferências |
| accounts | Contas financeiras |
| categories | Categorias |
| subcategories | Subcategorias |
| transactions | Receitas e despesas |
| transfers | Transferências |
| recurring_transactions | Regras de recorrência |
| installments | Parcelamentos |
| credit_cards | Cartões |
| invoices | Faturas |
| invoice_items | Compras vinculadas às faturas |
| budgets | Orçamentos |
| goals | Metas |
| goal_contributions | Aportes e retiradas |
| payables | Contas a pagar |
| receivables | Contas a receber |
| tags | Tags |
| transaction_tags | Relação entre movimentações e tags |
| investments | Ativos de investimento |
| investment_transactions | Aplicações, resgates e rendimentos |
| assets | Bens e ativos patrimoniais |
| liabilities | Dívidas e obrigações |

## 18.1. Regras para modelagem

- Utilizar `INTEGER PRIMARY KEY` como identificador.
- Utilizar `user_id` para isolamento dos dados.
- Utilizar `created_at` e `updated_at`.
- Utilizar chaves estrangeiras.
- Definir exclusões em cascata somente quando forem apropriadas.
- Criar índices para campos utilizados em filtros.
- Utilizar valores monetários em centavos, armazenados como inteiros, evitando erros de precisão com números decimais.
- Armazenar datas em formato ISO 8601.
- Não excluir definitivamente movimentações que já tenham sido liquidadas quando isso comprometer o histórico ou a integridade financeira.

## 18.2. Modelagem das movimentações

A tabela `transactions` deverá possuir, no mínimo:

- id
- user_id
- account_id
- category_id
- type
- description
- amount
- transaction_date
- due_date
- status
- payment_method
- notes
- created_at
- updated_at

Tipos:

- income
- expense

Status:

- pending
- paid
- received
- cancelled

Transferências e operações de cartão deverão possuir modelagem própria e regras específicas para evitar duplicidade de lançamentos.

---

# 19. Segurança e integridade dos dados

Por se tratar de um sistema financeiro, segurança deverá ser uma prioridade desde o início.

Requisitos obrigatórios:

1. Prepared statements via PDO.
2. Proteção contra SQL Injection.
3. Proteção contra XSS com escape de saída.
4. Proteção CSRF em operações de alteração.
5. Autenticação por sessão segura.
6. Autorização por usuário em todas as consultas.
7. Validação de dados no backend.
8. Senhas armazenadas em hash.
9. Proteção de arquivos internos.
10. Controle de acesso às rotas.
11. Logs de erros sem expor dados financeiros.
12. Backup do banco.
13. Uso de HTTPS em produção.
14. Controle de transações no banco de dados.

Não confiar apenas em filtros feitos no JavaScript. Todas as validações deverão ser repetidas no backend.

---

# 20. Regras de negócio fundamentais

Estas regras deverão ser implementadas antes de considerar o sistema financeiramente confiável.

### Regra 1 — Receitas e despesas

Somente movimentações efetivadas entram nos cálculos de saldo realizado.

### Regra 2 — Transferências

Transferências não representam receitas nem despesas consolidadas.

### Regra 3 — Cartões

Compras no cartão são despesas. O pagamento da fatura não pode gerar uma segunda despesa.

### Regra 4 — Parcelamentos

Cada parcela deve possuir vencimento, valor e situação próprios.

### Regra 5 — Contas pendentes

Contas pendentes devem aparecer nas previsões financeiras, mas não alterar o saldo realizado.

### Regra 6 — Isolamento de usuários

Toda consulta deverá verificar a propriedade dos registros. Não basta conhecer o ID de uma movimentação para acessá-la.

### Regra 7 — Valores monetários

Todos os cálculos deverão utilizar centavos inteiros e formatação brasileira na interface.

### Regra 8 — Exclusões

Excluir uma conta, categoria ou cartão não poderá comprometer o histórico financeiro. Quando houver movimentações vinculadas, utilizar desativação ou impedir a exclusão.

### Regra 9 — Consistência

Operações financeiras compostas devem ser executadas dentro de transações SQL, com rollback em caso de falha.

---

# 21. Etapas de desenvolvimento

O projeto deverá ser desenvolvido por etapas independentes, com testes e validações em cada uma.

## Fase 1 — Fundação

- Criar estrutura de pastas.
- Configurar PHP e SQLite.
- Implementar conexão PDO.
- Criar sistema de migrations.
- Criar layout principal.
- Implementar cadastro e login.
- Implementar sessões e segurança.
- Implementar isolamento de usuários.

**Resultado esperado:** aplicação funcional com autenticação e banco de dados.

## Fase 2 — Núcleo financeiro

- CRUD de contas.
- CRUD de categorias.
- Cadastro de receitas.
- Cadastro de despesas.
- Edição e exclusão.
- Transferências.
- Extrato financeiro.
- Cálculo de saldos.

**Resultado esperado:** usuário consegue registrar e acompanhar todas as movimentações básicas.

## Fase 3 — Dashboard

- Indicadores financeiros.
- Gráficos.
- Movimentações recentes.
- Filtros por período.
- Fluxo de caixa.
- Saldo consolidado.

**Resultado esperado:** visão geral das finanças atualizada automaticamente.

## Fase 4 — Cartões e compromissos

- Cadastro de cartões.
- Lançamentos no cartão.
- Faturas.
- Parcelamentos.
- Contas a pagar.
- Contas a receber.
- Recorrências.

**Resultado esperado:** controle completo das obrigações financeiras.

## Fase 5 — Planejamento

- Orçamentos.
- Limites de gastos.
- Alertas.
- Metas financeiras.
- Acompanhamento de objetivos.

**Resultado esperado:** usuário consegue planejar e acompanhar suas finanças futuras.

## Fase 6 — Relatórios

- Relatórios financeiros.
- Gráficos comparativos.
- Exportação CSV.
- Exportação PDF.
- Análises por categoria.
- Evolução mensal.

**Resultado esperado:** relatórios completos para análise financeira.

## Fase 7 — Recursos avançados

- Investimentos.
- Patrimônio.
- Dívidas.
- Importação OFX.
- Importação CSV.
- Backup e restauração.

**Resultado esperado:** plataforma financeira avançada.

## Fase 8 — Finalização

- Testes gerais.
- Testes de segurança.
- Otimização de consultas.
- Ajustes responsivos.
- PWA.
- Documentação.
- Preparação para hospedagem.

---

# 22. Critérios de aceitação

O projeto somente poderá ser considerado funcional quando:

- Um usuário conseguir criar uma conta e realizar login.
- Cada usuário acessar exclusivamente seus próprios dados.
- O usuário conseguir cadastrar, editar e excluir movimentações.
- Os saldos forem calculados corretamente.
- Transferências não duplicarem receitas ou despesas.
- Cartões e faturas funcionarem corretamente.
- Parcelamentos respeitarem os vencimentos.
- O dashboard apresentar dados reais do banco.
- Os gráficos respeitarem os filtros selecionados.
- Os orçamentos calcularem corretamente os gastos.
- As metas apresentarem evolução correta.
- O sistema funcionar em computadores e smartphones.
- Os dados persistirem após sair e entrar novamente.
- Os mecanismos de backup e restauração forem testados.
- Não existirem erros críticos de segurança ou integridade financeira.

---

# 23. Diretrizes para desenvolvimento com Codex

O Codex deverá atuar como desenvolvedor responsável pela implementação do projeto.

As seguintes regras deverão ser respeitadas durante o desenvolvimento:

1. Não tentar desenvolver todos os módulos em uma única execução.
2. Implementar uma fase de cada vez.
3. Analisar a estrutura existente antes de criar ou modificar arquivos.
4. Não sobrescrever funcionalidades já implementadas sem necessidade.
5. Manter a arquitetura organizada e modular.
6. Não duplicar regras de negócio entre controllers e views.
7. Centralizar cálculos financeiros em Services.
8. Utilizar PDO para todas as consultas.
9. Criar migrations para alterações no banco.
10. Criar dados de demonstração apenas em ambiente de desenvolvimento.
11. Testar cada funcionalidade antes de avançar.
12. Manter o README atualizado.
13. Não utilizar APIs pagas ou serviços externos obrigatórios.
14. Não inserir dados financeiros fictícios no ambiente de produção.
15. Não considerar uma funcionalidade concluída sem testar suas regras de negócio.

## 23.1. Fluxo de trabalho do Codex

Para cada fase:

1. Analisar os requisitos.
2. Identificar arquivos necessários.
3. Planejar a implementação.
4. Desenvolver o código.
5. Executar testes.
6. Corrigir erros.
7. Verificar possíveis impactos em módulos existentes.
8. Documentar o que foi implementado.
9. Apresentar um resumo da conclusão.
10. Aguardar autorização para avançar à próxima fase.

## 23.2. Documentação técnica

Manter os seguintes arquivos:

- `README.md`: apresentação e instalação.
- `PROJECT_STATUS.md`: andamento do projeto.
- `DATABASE.md`: estrutura do banco.
- `ARCHITECTURE.md`: arquitetura.
- `CHANGELOG.md`: alterações realizadas.
- `TASKS.md`: tarefas pendentes.

---

# 24. Resultado esperado

O resultado será uma plataforma web de gestão financeira pessoal, com autenticação individual, armazenamento local em SQLite, interface responsiva e módulos independentes.

O sistema deverá começar como um gerenciador financeiro simples, mas possuir uma arquitetura preparada para evoluir para uma solução completa, incluindo controle de investimentos, patrimônio, importação de extratos e planejamento financeiro.

**Prioridade máxima:** confiabilidade dos cálculos financeiros, segurança dos dados, facilidade de utilização e qualidade da arquitetura.

**Fora do escopo inicial:** integração automática com bancos, Open Finance, pagamentos, movimentações bancárias reais e recomendações automatizadas de investimentos.

Esses recursos poderão ser avaliados em versões futuras.