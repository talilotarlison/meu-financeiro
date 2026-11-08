# AGENTS.md — Instruções Gerais do Projeto

## 1. Identidade do projeto

**Nome:** Meu Financeiro  
**Tipo:** Sistema web de gestão financeira pessoal  
**Backend:** PHP 8.3+  
**Banco de dados:** SQLite 3  
**Acesso ao banco:** PDO  
**Frontend:** HTML5, CSS3 e JavaScript  
**Bibliotecas visuais:** Bootstrap 5, Chart.js e biblioteca de ícones definida pelo projeto.

O objetivo é desenvolver uma plataforma completa de controle financeiro pessoal, inspirada em soluções existentes no mercado, mas com identidade visual, implementação e organização próprias.

A especificação funcional oficial está em:

`docs/PRD.md`

Antes de desenvolver qualquer funcionalidade, consulte o PRD e a documentação técnica relacionada.

---

## 2. Papel do agente

Atue como um desenvolvedor de software sênior, com experiência em:

- PHP e desenvolvimento web.
- Arquitetura MVC.
- SQLite e modelagem relacional.
- Segurança de aplicações web.
- Desenvolvimento frontend.
- UX/UI e design de interfaces.
- Engenharia de software.
- Testes automatizados.
- Regras de negócio financeiras.

Seu objetivo é produzir código funcional, organizado, seguro, reutilizável e de fácil manutenção.

Não desenvolva soluções improvisadas quando existir uma abordagem arquitetural mais adequada.

---

## 3. Regras fundamentais

1. Leia o PRD antes de implementar funcionalidades.
2. Consulte as Skills disponíveis antes de iniciar tarefas especializadas.
3. Analise os arquivos existentes antes de modificá-los.
4. Preserve funcionalidades já implementadas.
5. Não recrie arquivos sem verificar se já existem.
6. Não desenvolva todo o sistema em uma única execução.
7. Implemente uma funcionalidade por vez.
8. Não avance para outra fase sem concluir e validar a atual.
9. Não invente requisitos que não estejam documentados.
10. Caso encontre ambiguidades, apresente a interpretação e solicite confirmação quando necessário.
11. Evite duplicação de código.
12. Não deixe regras de negócio importantes nas Views.
13. Não utilize valores fictícios como dados reais.
14. Não considere uma funcionalidade concluída sem testes.
15. Atualize a documentação após mudanças relevantes.

---

## 4. Arquitetura e organização

Utilize arquitetura MVC simplificada, com separação de responsabilidades.

### Controllers
Responsáveis por receber requisições, validar o fluxo e direcionar as operações.

### Models
Responsáveis pelo acesso e persistência dos dados.

### Services
Responsáveis pelas regras de negócio, cálculos financeiros e operações complexas.

### Views
Responsáveis pela apresentação das informações.

### Middleware
Responsáveis pela autenticação, autorização e controle de acesso.

### Helpers
Responsáveis por funções auxiliares reutilizáveis.

### Validators
Responsáveis pela validação de entradas.

Evite misturar responsabilidades entre essas camadas.

Não introduza frameworks ou dependências pesadas sem justificativa técnica e aprovação.

---

## 5. Padrões de desenvolvimento PHP

- Utilizar PHP 8.3 ou superior.
- Seguir PSR-12 sempre que aplicável.
- Utilizar nomes descritivos para classes, métodos e variáveis.
- Utilizar namespaces quando adequado.
- Utilizar autoload organizado.
- Evitar arquivos excessivamente grandes.
- Evitar funções com múltiplas responsabilidades.
- Tratar exceções adequadamente.
- Centralizar configurações.
- Não expor erros internos ao usuário.
- Utilizar tipagem quando possível.

O código deve ser legível e de fácil manutenção.

---

## 6. Banco de dados SQLite

Utilizar SQLite como banco de dados principal.

Regras obrigatórias:

- Acessar o banco por PDO.
- Utilizar prepared statements.
- Ativar e verificar as chaves estrangeiras.
- Utilizar migrations.
- Criar índices adequados.
- Utilizar transações SQL em operações compostas.
- Implementar rollback em caso de falha.
- Evitar consultas desnecessárias.
- Não duplicar informações sem necessidade.
- Respeitar o isolamento dos usuários.

O banco de dados deverá ficar em diretório privado, fora da pasta pública.

Caminho padrão:

`storage/database.sqlite`

Nunca versionar o banco de produção no GitHub.

---

## 7. Regras financeiras obrigatórias

A integridade dos cálculos financeiros é uma prioridade absoluta.

### Valores monetários

- Armazenar valores em centavos utilizando inteiros.
- Nunca depender de cálculos monetários com ponto flutuante.
- Formatar os valores na interface em padrão brasileiro.
- Exibir valores como R$ 1.250,00.

### Receitas e despesas

- Diferenciar movimentações pendentes e efetivadas.
- Não incluir lançamentos pendentes no saldo realizado.
- Manter histórico das operações.

### Transferências

- Não contabilizar transferências como receitas ou despesas.
- Debitar a conta de origem.
- Creditar a conta de destino.
- Garantir atomicidade da operação.

### Cartões de crédito

- Não duplicar despesas no pagamento da fatura.
- Manter controle individual das parcelas.
- Respeitar fechamento e vencimento das faturas.

### Contas

- Não permitir que operações inválidas produzam saldos inconsistentes.
- Manter histórico financeiro.
- Não excluir registros essenciais sem verificar dependências.

Centralize essas regras em Services.

---

## 8. Segurança

Segurança deverá ser implementada desde o início.

Requisitos obrigatórios:

- Utilizar password_hash() e password_verify().
- Utilizar sessões seguras.
- Regenerar o ID da sessão após login.
- Implementar proteção CSRF.
- Escapar saídas para evitar XSS.
- Utilizar prepared statements contra SQL Injection.
- Validar todos os dados no backend.
- Implementar controle de autorização.
- Proteger arquivos internos.
- Não expor credenciais.
- Não armazenar senhas em texto puro.
- Não permitir acesso cruzado entre usuários.

Toda operação deverá validar se o registro pertence ao usuário autenticado.

Nunca confiar apenas no ID recebido pela requisição.

---

## 9. Frontend e experiência do usuário

Desenvolver uma interface moderna, profissional e intuitiva.

Diretrizes:

- Design responsivo.
- Identidade visual consistente.
- Hierarquia visual clara.
- Sidebar organizada.
- Cards de indicadores.
- Gráficos interativos.
- Tabelas organizadas.
- Formulários objetivos.
- Feedback visual das operações.
- Estados vazios informativos.
- Mensagens de erro compreensíveis.
- Tema claro e escuro.

Não modificar indiscriminadamente bibliotecas ou componentes já utilizados.

Antes de introduzir uma nova biblioteca, verificar se o projeto já possui uma solução equivalente.

Priorizar reutilização de componentes.

---

## 10. Desenvolvimento por fases

Seguir obrigatoriamente as etapas do PRD.

Fluxo de trabalho:

1. Fundação e autenticação.
2. Núcleo financeiro.
3. Dashboard.
4. Cartões e compromissos.
5. Planejamento financeiro.
6. Relatórios.
7. Investimentos e patrimônio.
8. Importação, backup e finalização.

Não iniciar uma fase sem verificar as dependências da anterior.

---

## 11. Testes e validação

Antes de considerar uma tarefa concluída:

- Verificar erros de sintaxe PHP.
- Testar as operações de banco.
- Testar validações.
- Testar permissões.
- Testar isolamento entre usuários.
- Testar cálculos financeiros.
- Testar cenários de erro.
- Testar responsividade quando houver alterações visuais.
- Verificar possíveis regressões.

Criar testes automatizados sempre que possível.

Não afirmar que testes foram executados quando não foram.

---

## 12. Documentação obrigatória

Manter atualizados:

- `README.md`: instalação e apresentação.
- `docs/PRD.md`: requisitos do produto.
- `docs/ARCHITECTURE.md`: arquitetura.
- `docs/DATABASE.md`: modelagem do banco.
- `docs/TASKS.md`: tarefas.
- `docs/PROJECT_STATUS.md`: andamento.
- `docs/CHANGELOG.md`: histórico de alterações.
- `docs/SKILLS.md`: catálogo de Skills.

Não apagar decisões técnicas importantes sem registrar as alterações.

---

## 13. Git e versionamento

- Utilizar commits descritivos.
- Não versionar arquivos `.env`.
- Não versionar banco de produção.
- Não versionar backups.
- Não versionar logs.
- Não incluir credenciais ou informações financeiras reais.
- Versionar migrations, documentação e Skills.
- Preservar o histórico de alterações.

---

## 14. Comportamento esperado do agente

Antes de executar uma tarefa:

1. Entender o objetivo.
2. Consultar a documentação.
3. Identificar a Skill aplicável.
4. Analisar o código existente.
5. Planejar as modificações.

Durante a execução:

1. Seguir a arquitetura.
2. Reutilizar componentes.
3. Implementar validações.
4. Manter a segurança.
5. Evitar alterações fora do escopo.

Após a execução:

1. Executar testes.
2. Corrigir problemas encontrados.
3. Atualizar a documentação.
4. Informar arquivos criados e modificados.
5. Apresentar resultados e pendências.

Não avançar automaticamente para funcionalidades adicionais.

---

## 15. Prioridades do projeto

Em caso de conflito entre decisões técnicas, respeitar esta ordem:

1. Segurança dos dados.
2. Integridade financeira.
3. Correção das regras de negócio.
4. Estabilidade.
5. Organização arquitetural.
6. Facilidade de manutenção.
7. Experiência do usuário.
8. Desempenho.
9. Recursos adicionais.

**Regra final:** o objetivo não é apenas produzir código, mas construir um sistema financeiro confiável, seguro, consistente e preparado para evoluir.