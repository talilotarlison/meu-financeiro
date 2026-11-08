# Changelog

## 2026-11-07 — Perfil e autenticação

- Adicionados status da conta e registro do último login, com bloqueio de autenticação para contas desativadas.
- Adicionada página de perfil com e-mail, status, último acesso e data de criação.
- Barra superior agora mostra o primeiro nome, abre o perfil e inclui botão de logout com proteção CSRF.
- Corrigido o carregamento da preferência de tema em todas as páginas autenticadas.

## 2026-11-07 — Fase 8

- Criado runner de testes sem dependências com SQLite em memória e verificações gerais/de segurança.
- Otimizada a série mensal de saldo, substituindo uma consulta por mês por agregações por período.
- Adicionada PWA com ícones, modo offline informativo e cache restrito a arquivos estáticos públicos.
- Reforçados cookies de sessão, política de conteúdo e cabeçalhos de segurança.
- Criada configuração Apache para publicar somente `public/` e documentação de implantação.
- Melhorados foco de teclado, navegação por salto, movimento reduzido e ajustes responsivos.

## 2026-11-07 — Fase 7

- Adicionada carteira manual de investimentos com histórico de aplicações, resgates, rendimentos e avaliações.
- Adicionados bens, dívidas, baixa integrada a despesas e evolução mensal do patrimônio líquido.
- Criada importação OFX/CSV com mapeamento de colunas, prévia, seleção e bloqueio de duplicidades.
- Implementados backup/restauração JSON por usuário com validação de integridade e remapeamento de IDs.
- Adicionada migration 005 para carteira, patrimônio, dívidas e identificadores de importação.

## 2026-11-07 — Fase 6

- Criados relatórios financeiros por período com resumo, comparativo mensal e análise de despesas por categoria.
- Adicionadas exportações CSV e PDF, incluindo proteção contra fórmulas em células textuais do CSV.
- Atualizados histórico detalhado de fases, checklist, README e arquitetura.

## 2026-11-07 — Fase 5

- Adicionados orçamentos mensais por categoria com alertas e cálculo de gastos de cartão por vencimento.
- Adicionadas metas com progresso e histórico de aportes/resgates; metas vinculadas movimentam saldos por transferência.
- Registrado histórico das Fases 1 a 4 em `PROJECT_STATUS.md`.

## 2026-11-07 — Fase 4

- Adicionados cartões, compras parceladas, faturas e pagamento com baixa direta na conta.
- Compras de cartão integradas ao dashboard sem duplicar despesa no pagamento.
- Adicionados compromissos a pagar/receber com adiamento e liquidação.
- Criadas regras recorrentes com geração idempotente de ocorrências futuras.

## 2026-11-06 — Fase 3

- Adicionados filtros para mês atual/anterior, últimos 3/6 meses, ano atual e período personalizado.
- Dashboard passou a recalcular totais, pendências, movimentações e séries conforme o intervalo selecionado.
- Incluídos gráficos de fluxo, saldo mensal, despesas por categoria e despesas por semana com Chart.js.

## 2026-11-06 — Fase 2

- Criadas tabelas isoladas por usuário para contas, categorias, movimentações e transferências.
- Implementados saldos em centavos, extrato, filtros, pendências, cancelamento lógico e transferências transacionais.
- Ligado o dashboard aos dados reais de cada usuário.
- Criadas telas responsivas e navegação para os módulos financeiros.

## 2026-11-06 — Fase 1

- Criada fundação PHP/SQLite e migration inicial.
- Implementados cadastro, login, logout, sessão segura, CSRF e tela inicial responsiva.
- Adicionadas notas de instalação, arquitetura e schema.
