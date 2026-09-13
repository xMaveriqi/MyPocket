# MyPocket

Sistema web de controle financeiro pessoal desenvolvido em PHP, com MySQL e PDO.
O projeto permite registrar movimentações financeiras, acompanhar o saldo, consultar
resumos por período e administrar lançamentos recorrentes.

## Funcionalidades

- Cadastro de usuários com senha protegida por `password_hash`.
- Login, logout e proteção das páginas privadas.
- Cadastro de receitas, despesas e lançamentos do tipo diário.
- Validação de valor, descrição, data e saldo disponível.
- Edição e exclusão de transações.
- Isolamento dos dados por usuário.
- Filtro do extrato por ano e tipo de movimentação.
- Resumo anual com receitas, despesas, diário e saldo.
- Resumo mensal de janeiro a dezembro.
- Cadastro de recorrências mensais.
- Pausa e ativação de recorrências.
- Geração automática de lançamentos recorrentes sem duplicação.
- Interface responsiva usando Bootstrap 5 via CDN.

## Tecnologias

- PHP 8 ou superior.
- MySQL 5.7+ ou MariaDB compatível.
- PDO para acesso ao banco de dados.
- HTML5 e Bootstrap 5.
- Programação orientada a objetos nas classes de domínio.

## Estrutura

```text
projetopw-main/
├── README.md
└── Projeto-Final-PW2/
    ├── database.sql
    └── mypocket/
	  ├── index.php
	  ├── login.php
	  ├── cadastro.php
	  ├── logout.php
	  ├── criar.php
	  ├── processa.php
	  ├── listar.php
	  ├── editar.php
	  ├── delete.php
	  ├── recorrencias.php
	  ├── classes/
	  │   ├── Transacao.php
	  │   ├── Receita.php
	  │   ├── Despesa.php
	  │   ├── Diario.php
	  │   └── Carteira.php
	  ├── config/
	  │   └── database.php
	  ├── includes/
	  │   ├── auth.php
	  │   └── helpers.php
	  └── services/
		└── RecorrenciaService.php
```

## Responsabilidade dos arquivos

### Páginas

- `index.php`: dashboard, saldo, filtros, cartões de totais e resumo mensal.
- `login.php`: autenticação do usuário.
- `cadastro.php`: criação de contas.
- `logout.php`: encerramento da sessão.
- `criar.php`: formulário completo para uma nova transação.
- `processa.php`: recebe e valida o formulário rápido do dashboard.
- `listar.php`: tabela com todas as transações do usuário.
- `editar.php`: alteração de uma transação existente.
- `delete.php`: exclusão de uma transação.
- `recorrencias.php`: cadastro, listagem, pausa e ativação de recorrências.

### Classes

- `Transacao`: classe abstrata com valor, descrição e data.
- `Receita`: representa uma entrada financeira.
- `Despesa`: representa uma saída financeira.
- `Diario`: representa um lançamento diário.
- `Carteira`: carrega o histórico, calcula o saldo e persiste transações.

### Módulos compartilhados

- `config/database.php`: abre a conexão PDO e garante as tabelas básicas.
- `includes/auth.php`: inicia a sessão e bloqueia páginas sem login.
- `includes/helpers.php`: concentra escape HTML e formatação de valores e datas.
- `services/RecorrenciaService.php`: contém a regra de negócio das recorrências.

## Banco de dados

O banco padrão utilizado é `sistema_crud`. O schema completo está em
[`Projeto-Final-PW2/database.sql`](Projeto-Final-PW2/database.sql).

### Tabelas

#### `usuarios`

Armazena nome, e-mail e senha com hash. O e-mail é único.

#### `transacoes`

Armazena as movimentações financeiras:

| Campo | Descrição |
| --- | --- |
| `id` | Identificador da transação |
| `user_id` | Usuário dono do registro |
| `recorrencia_id` | Recorrência que originou o registro, quando aplicável |
| `tipo` | `receita`, `despesa` ou `diario` |
| `valor` | Valor positivo da movimentação |
| `descricao` | Texto explicativo |
| `data_transacao` | Data financeira |
| `criado_em` | Data de criação do registro |

#### `recorrencias`

Armazena modelos mensais com tipo, valor, descrição, período e status ativo.

## Fluxo principal

```text
Cadastro/Login
	|
	v
Dashboard (index.php)
	|
	+--> Nova transação --> validação --> banco
	|
	+--> Filtros e resumos
	|
	+--> Recorrências --> geração mensal --> transações
	|
	+--> Listagem --> edição ou exclusão
```

## Regras de negócio

- Valores devem ser maiores que zero.
- A descrição não pode ficar vazia.
- Despesas não podem ultrapassar o saldo disponível.
- Cada consulta de transação é filtrada pelo usuário autenticado.
- Uma recorrência não cria duas transações para a mesma competência mensal.
- O saldo é calculado a partir do histórico, não armazenado como um valor separado.
- Receitas aumentam o saldo; despesas e lançamentos diários reduzem o saldo.

## Organização do código

As páginas PHP funcionam como entradas da aplicação. Regras reutilizáveis ficam
nas classes e serviços, enquanto conexão, autenticação e helpers ficam em módulos
separados. Isso evita repetir consultas, validações e funções de apresentação em
vários arquivos.

Os comentários do código destacam decisões de negócio, migrações e pontos em que
uma regra pode não ser óbvia. A implementação mantém os nomes e a estrutura
próprios do projeto, sem depender de arquivos externos.

## Segurança

- Senhas são armazenadas com `password_hash`.
- Login usa `password_verify`.
- Consultas que recebem dados do usuário usam prepared statements.
- Saídas HTML são escapadas antes de serem exibidas.
- Páginas privadas exigem uma sessão autenticada.
- Edição e exclusão verificam o `user_id` do registro.

Em produção, configure credenciais por variáveis de ambiente, desative mensagens
detalhadas de erro e use HTTPS.
