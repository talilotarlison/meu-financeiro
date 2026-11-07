# Andamento do projeto

**Atualizado em:** 07/11/2026  
**Fase atual:** 8 — Finalização (concluída)

## Fase 8 — concluída

- Runner automatizado sem dependências externas, executado com SQLite em memória e bloqueado fora do ambiente de teste.
- 30 verificações passaram cobrindo cálculos, migrations, filtros/agregações, orçamento, metas, investimentos, patrimônio, imports OFX/CSV, relatórios, CSV formula injection, isolamento/restore de backups, PWA, acessibilidade responsiva, otimização da série mensal, persistência de movimentações e dados de perfil/login.
- Série mensal de saldo otimizada para uma agregação por período, em vez de executar uma consulta para cada mês.
- PWA instalável com ícones PNG, manifest e service worker; cache limitado a arquivos estáticos públicos e fallback offline sem páginas ou dados financeiros.
- Cabeçalhos CSP, nosniff, frame denial, referrer policy, permissions policy e cache privado sem armazenamento para respostas dinâmicas.
- Sessões com strict mode, cookies somente HTTP, SameSite=Lax e Secure sob HTTPS.
- Ajustes responsivos, foco de teclado visível, link para pular ao conteúdo e respeito à preferência de movimento reduzido.
- Configuração Apache para encaminhar rotas e bloquear acesso a arquivos/diretórios internos.
- Criado guia de implantação com document root, ambiente, TLS, permissões e checklist pós-publicação.

## Fase 7 — concluída

- Carteira manual de investimentos para Tesouro Direto, CDB, LCI/LCA, poupança, ações, FIIs, ETFs, fundos, cripto, previdência e outros.
- Cadastro de ativo com instituição, quantidade, preço médio, custo, valor de mercado, data de aplicação e observações.
- Histórico de aplicações, resgates, rendimentos e atualizações de valor de mercado.
- Cadastro de bens e passivos, saldo de dívidas, baixa parcial/total integrada a despesa efetivada.
- Patrimônio líquido calculado como ativos menos passivos e snapshots mensais.
- Importação de OFX e CSV com limite de tamanho/linhas, mapeamento de colunas CSV, prévia, seleção e detecção de possíveis duplicatas.
- Identificador externo único por conta impede a importação repetida de lançamentos OFX/CSV.
- Backup e restauração JSON por usuário; checksum, validação de propriedade e integridade referencial, confirmação explícita e remapeamento dos IDs para preservar as demais contas.
- Migration SQLite 005, telas, navegação, rotas protegidas por autenticação/CSRF e documentação atualizada.

## Fase 6 — concluída

- Módulo de relatórios com filtros de período reaproveitados do dashboard (mês atual/anterior, 3/6 meses, ano e intervalo personalizado).
- Indicadores de receitas, despesas, resultado e saldo no encerramento do período.
- Comparativo mensal de receitas e despesas com gráfico e tabela de evolução.
- Análise de despesas por categoria com valor e participação percentual.
- Exportação CSV com BOM para compatibilidade com planilhas e proteção contra fórmulas em células textuais.
- Exportação PDF com resumo, evolução mensal e despesas por categoria, sem dependência de pacote externo.
- Rotas e consultas vinculadas ao usuário autenticado; downloads com cabeçalhos de anexo e sem cache.
- Layout da nova área adaptável a desktop e celular, com atualização da navegação e documentação.

## Histórico de fases

### Fase 1 — Fundação

- Configuração PHP, front controller, rotas GET/POST e renderização de views.
- Conexão PDO/SQLite com WAL, foreign keys, timeout e migrations SQL versionadas.
- Cadastro, login e logout com hash de senha, validação e token CSRF.
- Regeneração de sessão após autenticação e limitação de tentativas por sessão.
- Preferências iniciais por usuário, alternância de tema persistida e sessões protegidas.
- Layout adaptável para desktop e celular, navegação lateral e telas de autenticação.
- Documentação inicial de instalação, arquitetura e schema.

### Fase 2 — Núcleo financeiro

- Contas com tipo, instituição, saldo/data inicial, cor, extrato e arquivamento sem apagar histórico.
- Categorias de receita e despesa isoladas por usuário, incluindo padrão e personalizadas.
- Movimentações em centavos com conta, categoria, datas de competência/vencimento, status e observações.
- Criação/edição, filtros, pendências e cancelamento lógico de receitas e despesas.
- Transferências entre contas em transação SQL, sem duplicar receitas ou despesas consolidadas.
- Saldos calculados a partir do saldo inicial e movimentos efetivados.
- Dashboard inicial alimentado por dados reais e navegação para as áreas financeiras.

### Fase 3 — Dashboard

- Filtros por mês atual/anterior, últimos 3/6 meses, ano atual e período personalizado (até três anos).
- Indicadores de receitas, despesas, resultado, saldo, compromissos pendentes e faturas abertas.
- Séries mensais de receitas/despesas e saldo de encerramento.
- Gráficos de fluxo de caixa, evolução do saldo, despesas por categoria e despesas por semana.
- Atividade recente e saldos por conta, com dados restritos ao usuário autenticado.

### Fase 4 — Cartões e compromissos

- Cartões com limite, dia de fechamento/vencimento e arquivamento preservando histórico.
- Compras à vista e parceladas alocadas nas faturas conforme o ciclo do cartão.
- Detalhamento de parcelas/faturas, fechamento e pagamento a partir de conta bancária.
- Pagamento de fatura reduz o saldo da conta sem criar uma segunda despesa.
- Contas a pagar/receber com vencimento, baixa transacional, adiamento e cancelamento.
- Regras de recorrência com geração idempotente e até 12 meses de ocorrências futuras.
- Pendências e despesas de cartão integradas aos indicadores por data de vencimento.

### Fase 5 — Orçamentos e metas

- Orçamento mensal único por categoria, com nome, limite e mês de referência.
- Acompanhamento de valor gasto/disponível, percentual usado e excesso do limite.
- Alertas por percentuais configuráveis; por padrão 50%, 80% e 100%.
- Despesas efetivadas e parcelas de cartão contabilizadas no mês, sem duplicar pagamento de fatura.
- Metas com valor desejado, descrição, início, prazo, conta/categoria e cor.
- Progresso, valor restante, prazo restante, aportes e resgates com histórico.
- Aportes e resgates em metas vinculadas refletidos como transferências entre contas.
- Migration 004 com constraints e índices para orçamentos, alertas, metas e movimentos.

## Histórico e operação

- O carregamento dos gráficos usa Chart.js via jsDelivr; os indicadores em texto continuam disponíveis sem a biblioteca.
- O carregamento de cotações externas de investimentos permanece fora do escopo; valores de mercado são informados manualmente.
- O banco e os backups devem permanecer fora do document root.
- As oito fases do PRD estão concluídas; publicar o sistema requer configurar domínio, TLS e permissões no ambiente de hospedagem escolhido.
