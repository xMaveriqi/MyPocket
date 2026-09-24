<?php

declare(strict_types=1);

require_once __DIR__ . '/classes/Carteira.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/servicos/autenticacao.php';
require_once __DIR__ . '/servicos/auxilares.php';
require_once __DIR__ . '/servicos/recorrenciaservico.php';

$carteira = new Carteira($pdo, (int) $_SESSION['usuario_id']);

$ano = filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT) ?: (int) date('Y');
$filtro = $_GET['filtro'] ?? 'todos';
$filtrosPermitidos = ['todos', 'receita', 'despesa', 'diario'];
if (!in_array($filtro, $filtrosPermitidos, true)) {
    $filtro = 'todos';
}

// Gera apenas a competência atual; o método é idempotente e não duplica registros.
$recorrenciaServico = new RecorrenciaServico($pdo, (int) $_SESSION['usuario_id']);
if ($ano === (int) date('Y')) {
    $recorrenciaServico->gerarMes($ano, (int) date('n'));
}

$mensagem = $_SESSION['mensagem'] ?? null;
$mensagemTipo = $_SESSION['mensagem_tipo'] ?? 'info';
unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo']);

// R - READ: Buscar as transações do ano e filtro selecionados.
$sqlTransacoes = 'SELECT * FROM transacoes WHERE user_id = :user_id AND data_transacao >= :inicio AND data_transacao < :fim';
$paramsTransacoes = [
    'user_id' => $_SESSION['usuario_id'],
    'inicio' => sprintf('%04d-01-01', $ano),
    'fim' => sprintf('%04d-01-01', $ano + 1),
];
if ($filtro !== 'todos') {
    $sqlTransacoes .= ' AND tipo = :tipo';
    $paramsTransacoes['tipo'] = $filtro;
}
$sqlTransacoes .= ' ORDER BY data_transacao DESC, id DESC';
$stmt = $pdo->prepare($sqlTransacoes);
$stmt->execute($paramsTransacoes);
$todasTransacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totais = ['receita' => 0.0, 'despesa' => 0.0, 'diario' => 0.0];
foreach ($todasTransacoes as $transacao) {
    $totais[$transacao['tipo']] += (float) $transacao['valor'];
}
$saldoAno = $totais['receita'] - $totais['despesa'] - $totais['diario'];

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mypocket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2d6a62;
            --primary-dark: #214f4a;
            --bg: #f4f6f3;
            --surface: #ffffff;
            --surface-muted: #f8faf8;
            --border: #e5e7e6;
            --text: #1f2d2b;
            --muted: #64706d;
            --success: #2d6a62;
            --danger: #b65151;
            --warning: #c98d2b;
            --shadow: 0 10px 24px rgba(17, 24, 39, 0.06);
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", system-ui, sans-serif;
        }

        .navbar {
            background: var(--surface) !important;
            border-bottom: 1px solid var(--border);
            box-shadow: 0 2px 10px rgba(17, 24, 39, 0.02);
        }

        .navbar-brand,
        .nav-link,
        .navbar-text {
            color: var(--text) !important;
        }

        .nav-link.active {
            color: var(--primary) !important;
            font-weight: 600;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
        }

        .card-header {
            background: var(--surface-muted) !important;
            color: var(--text) !important;
            border-bottom: 1px solid var(--border);
            border-radius: 14px 14px 0 0 !important;
        }

        .card-body {
            padding: 1.25rem 1.35rem;
        }

        .card-title,
        h1, h2, h3, h4, h5, h6 {
            color: var(--text);
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-success {
            background: var(--success);
            border-color: var(--success);
        }

        .receita { color: var(--success); font-weight: 600; }
        .despesa { color: var(--danger); font-weight: 600; }
        .text-success { color: var(--success) !important; }
        .text-danger { color: var(--danger) !important; }
        .text-warning { color: var(--warning) !important; }

        .table {
            --bs-table-bg: transparent;
            color: var(--text);
        }

        .table thead th {
            border-bottom: 1px solid var(--border);
            color: var(--muted);
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            font-size: 0.72rem;
        }

        .table td,
        .table th {
            padding-top: 0.9rem;
            padding-bottom: 0.9rem;
        }

        .alert {
            border-radius: 12px;
            border: 1px solid transparent;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #fff;
            box-shadow: none;
        }

        .display-6 {
            color: var(--primary);
            font-weight: 700;
            letter-spacing: -0.03em;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">MyPocket</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="listar.php">Todas as Transações</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="recorrencias.php">Recorrências</a>
                </li>
                <li class="nav-item">
                    <span class="nav-link">Olá, <?= escape((string) $_SESSION['usuario_nome']) ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Sair</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container my-4">
    <div class="row gy-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="card-title">Saldo Atual</h1>
                    <p class="display-6 mb-0 text-success">R$ <?= number_format($carteira->getSaldo(), 2, ',', '.') ?></p>
                </div>
            </div>
        </div>

        <?php if ($mensagem !== null): ?>
            <div class="col-12">
                <div class="alert alert-<?= escape($mensagemTipo) ?> alert-dismissible fade show" role="alert">
                    <?= escape($mensagem) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            </div>
        <?php endif; ?>

        <div class="col-12">
            <?php
            $nomesMeses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
            $resumoMensal = array_fill(1, 12, ['receita' => 0.0, 'despesa' => 0.0, 'diario' => 0.0]);
            foreach ($todasTransacoes as $transacao) {
                $mesTransacao = (int) (new DateTime($transacao['data_transacao']))->format('n');
                $resumoMensal[$mesTransacao][$transacao['tipo']] += (float) $transacao['valor'];
            }
            ?>
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white"><h2 class="h5 mb-0">Resumo mensal de <?= $ano ?></h2></div>
                <div class="table-responsive"><table class="table table-hover mb-0">
                    <thead><tr><th>Mês</th><th>Receitas</th><th>Diário</th><th>Despesas</th><th>Saldo</th></tr></thead>
                    <tbody>
                    <?php foreach ($resumoMensal as $numeroMes => $resumo): ?>
                        <?php $saldoMes = $resumo['receita'] - $resumo['despesa'] - $resumo['diario']; ?>
                        <tr><td><?= $nomesMeses[$numeroMes] ?></td><td class="text-success"><?= formatarValor($resumo['receita']) ?></td><td class="text-warning"><?= formatarValor($resumo['diario']) ?></td><td class="text-danger"><?= formatarValor($resumo['despesa']) ?></td><td class="<?= $saldoMes >= 0 ? 'text-success' : 'text-danger' ?>"><?= formatarValor($saldoMes) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title">Registrar Transação</h2>
                    <form action="processa.php" method="post" novalidate>
                        <div class="mb-3">
                            <label class="form-label" for="tipo">Tipo</label>
                            <select id="tipo" name="tipo" class="form-select" required>
                                <option value="receita">Receita</option>
                                <option value="despesa">Saída</option>
                                <option value="diario">Diário</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="valor">Valor (R$)</label>
                            <input id="valor" name="valor" type="number" step="0.01" min="0.01" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="descricao">Descrição</label>
                            <input id="descricao" name="descricao" type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="data">Data</label>
                            <input id="data" name="data" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                        <a href="criar.php" class="btn btn-success">+ Nova Transação</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title">Extrato</h2>
                    <?php $historico = $carteira->getHistorico(); ?>
                    <?php if (empty($historico)): ?>
                        <p class="text-muted">Nenhuma transação registrada ainda.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Descrição</th>
                                    <th>Data</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($historico as $transacao): ?>
                                    <?php $isEntrada = $transacao->getTipo() === 'Entrada'; ?>
                                    <tr>
                                        <td>
                                            <span class="badge <?= $isEntrada ? 'bg-success' : 'bg-danger' ?>">
                                                <?= escape($transacao->getTipo()) ?>
                                            </span>
                                        </td>
                                        <td><?= escape($transacao->getDescricao()) ?></td>
                                        <td><?= escape($transacao->getDataFormatada()) ?></td>
                                        <td class="text-end <?= $isEntrada ? 'text-success' : 'text-danger' ?>">
                                            <?= ($isEntrada ? '+ ' : '- ') . number_format($transacao->getValor(), 2, ',', '.') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h3 class="mb-0">Todas as Transações</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($todasTransacoes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Nenhuma transação registrada.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($todasTransacoes as $trans): ?>
                                    <tr>
                                        <td><?= escape((string)$trans['id']) ?></td>
                                        <td>
                                            <span class="badge <?= $trans['tipo'] === 'receita' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $trans['tipo'] === 'receita' ? 'Entrada' : 'Saída' ?>
                                            </span>
                                        </td>
                                        <td><?= escape($trans['descricao']) ?></td>
                                        <td><?= formatarData($trans['data_transacao']) ?></td>
                                        <td class="<?= $trans['tipo'] === 'receita' ? 'receita' : 'despesa' ?>">
                                            <?= formatarValor((float)$trans['valor']) ?>
                                        </td>
                                        <td>
                                            <a href="editar.php?id=<?= $trans['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
                                            <a href="delete.php?id=<?= $trans['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja deletar?')">Deletar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
