# SKILLS.md — Catálogo de Skills do Projeto

## 1. Objetivo

Este documento apresenta as Skills utilizadas no desenvolvimento do Meu Financeiro.

As Skills fornecem conhecimentos especializados e instruções específicas para auxiliar o Codex durante a implementação, manutenção, testes e evolução do sistema.

Cada Skill deverá possuir seu próprio diretório e arquivo `SKILL.md`.

Diretório padrão:

`.agents/skills/`

---

## 2. Como utilizar as Skills

Antes de executar uma tarefa:

1. Identificar quais conhecimentos especializados são necessários.
2. Consultar as Skills relacionadas.
3. Aplicar suas instruções durante o desenvolvimento.
4. Respeitar as regras gerais do arquivo `AGENTS.md`.
5. Evitar utilizar Skills que não tenham relação com a tarefa.

O agente poderá utilizar mais de uma Skill quando a tarefa envolver diferentes áreas.

---

## 3. Skills do projeto

### 3.1. PHP Development

**Diretório:** `.agents/skills/php-development/`

**Arquivo:** `SKILL.md`

Responsabilidades:

- Desenvolvimento PHP.
- Arquitetura MVC.
- Organização de classes.
- Controllers e Models.
- Services.
- Autoload.
- Boas práticas.
- Tratamento de erros.
- PSR-12.
- Manutenção e refatoração.

Utilizar em tarefas relacionadas ao backend.

### 3.2. SQLite Database

**Diretório:** `.agents/skills/sqlite-database/`

**Arquivo:** `SKILL.md`

Responsabilidades:

- Modelagem relacional.
- SQLite.
- PDO.
- Migrations.
- Relacionamentos.
- Chaves estrangeiras.
- Índices.
- Transações.
- Otimização de consultas.
- Backup e restauração.

Utilizar em tarefas relacionadas ao banco de dados.

### 3.3. Frontend Design

**Diretório:** `.agents/skills/frontend-design/`

**Arquivo:** `SKILL.md`

Responsabilidades:

- Design de interfaces.
- HTML5.
- CSS3.
- JavaScript.
- Bootstrap.
- Responsividade.
- Dashboards.
- Componentes reutilizáveis.
- Experiência do usuário.
- Acessibilidade.
- Temas claro e escuro.

Utilizar em tarefas relacionadas à interface.

### 3.4. Security

**Diretório:** `.agents/skills/security/`

**Arquivo:** `SKILL.md`

Responsabilidades:

- Autenticação.
- Autorização.
- Gerenciamento de sessões.
- Proteção CSRF.
- Proteção XSS.
- Prevenção de SQL Injection.
- Controle de permissões.
- Proteção de informações sensíveis.
- Segurança de aplicações PHP.

Utilizar em tarefas relacionadas à segurança.

### 3.5. Financial Logic

**Diretório:** `.agents/skills/financial-logic/`

**Arquivo:** `SKILL.md`

Responsabilidades:

- Cálculos financeiros.
- Controle de receitas e despesas.
- Saldos.
- Transferências.
- Cartões de crédito.
- Faturas.
- Parcelamentos.
- Fluxo de caixa.
- Orçamentos.
- Metas financeiras.
- Patrimônio.
- Precisão monetária.

Utilizar sempre que houver alterações nas regras financeiras.

### 3.6. Testing

**Diretório:** `.agents/skills/testing/`

**Arquivo:** `SKILL.md`

Responsabilidades:

- Testes unitários.
- Testes de integração.
- Validação de funcionalidades.
- Testes de segurança.
- Testes de regressão.
- Testes de regras financeiras.
- Identificação de erros.
- Validação de resultados.

Utilizar na implementação e validação das funcionalidades.

---

## 4. Regras de utilização

1. Consultar as Skills antes de iniciar tarefas especializadas.
2. Não presumir que uma Skill existe sem verificar seu diretório.
3. Não criar Skills duplicadas.
4. Manter as instruções atualizadas.
5. Não colocar todas as instruções dentro de uma única Skill.
6. Separar conhecimentos por responsabilidade.
7. Priorizar Skills específicas em tarefas especializadas.
8. Seguir também o arquivo AGENTS.md.
9. Não utilizar instruções de Skills que contrariem os requisitos de segurança do projeto.
10. Documentar novas Skills neste catálogo.

---

## 5. Criação de novas Skills

Novas Skills poderão ser adicionadas conforme o projeto evoluir.

Exemplos de futuras Skills:

| Skill | Responsabilidade |
|---|---|
| api-development | Desenvolvimento de APIs |
| chart-analytics | Gráficos e indicadores |
| performance-optimization | Otimização de desempenho |
| accessibility | Acessibilidade |
| pwa-development | Aplicação instalável |
| financial-reports | Relatórios financeiros |
| data-import | Importação OFX e CSV |

Toda nova Skill deverá possuir:

- Nome descritivo.
- Descrição objetiva.
- Escopo definido.
- Instruções específicas.
- Exemplos quando necessário.
- Critérios de utilização.

---

## 6. Estrutura padrão de uma Skill

Exemplo:

```text
.agents/
└── skills/
    └── php-development/
        └── SKILL.md
```

O arquivo `SKILL.md` deverá começar com metadados:

---
name: php-development
description: Desenvolvimento backend em PHP, arquitetura MVC e boas práticas. Utilizar ao criar, modificar ou revisar funcionalidades PHP.
---
## Estrutura final recomendada
```text
meu-financeiro/
│
├── .agents/
│   └── skills/
│       ├── php-development/
│       │   └── SKILL.md
│       ├── sqlite-database/
│       │   └── SKILL.md
│       ├── frontend-design/
│       │   └── SKILL.md
│       ├── security/
│       │   └── SKILL.md
│       ├── financial-logic/
│       │   └── SKILL.md
│       └── testing/
│           └── SKILL.md
│
├── docs/
│   ├── PRD.md
│   ├── ARCHITECTURE.md
│   ├── DATABASE.md
│   ├── TASKS.md
│   ├── PROJECT_STATUS.md
│   ├── CHANGELOG.md
│   └── SKILLS.md
│
├── app/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
│
├── AGENTS.md
├── README.md
└── .gitignore
```
Uma observação: os seis diretórios de Skills são uma estrutura planejada. Você ainda precisará criar os respectivos arquivos SKILL.md para que sejam Skills efetivamente utilizáveis pelo Codex.

# PHP Development

## Objetivo

Descrever o conhecimento especializado da Skill.

## Diretrizes

Apresentar as regras técnicas.

## Boas práticas

Definir os padrões de desenvolvimento.

## Exemplos

Apresentar exemplos de implementação.

## Validação

Definir como verificar o resultado.

---

## 7. Diretriz final

As Skills são instrumentos de especialização dos agentes, não substitutos da documentação do projeto.

O PRD define o que deve ser desenvolvido.

O AGENTS.md define as regras gerais de desenvolvimento.

Este SKILLS.md organiza as especializações disponíveis.

Cada SKILL.md define como executar tarefas específicas.

Todos esses documentos deverão permanecer versionados junto ao código-fonte no GitHub.
