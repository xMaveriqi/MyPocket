<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/servicos/autenticacao.php';
require_once __DIR__ . '/servicos/auxilares.php';

$mensagem = $_SESSION['mensagem'] ?? null;
$mensagemTipo = $_SESSION['mensagem_tipo'] ?? 'info';
unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo']);

// R - READ: Buscar todas as transações
$stmt = $pdo->prepare('SELECT * FROM transacoes WHERE user_id = :user_id ORDER BY data_transacao DESC, id DESC');
$stmt->execute(['user_id' => $_SESSION['usuario_id']]);
$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todas as Transações - MyPocket</title>
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

        .table thead th {
            color: var(--muted);
            font-size: 0.72rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
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

        .btn-secondary {
            background: #edf2ef;
            border-color: #edf2ef;
            color: var(--text);
        }

        .receita { color: var(--success); font-weight: 600; }
        .despesa { color: var(--danger); font-weight: 600; }
        .diario { color: var(--warning); font-weight: 600; }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #fff;
            box-shadow: none;
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
                    <a class="nav-link" href="index.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="listar.php">Todas as Transações</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-4">
    <?php if ($mensagem): ?>
        <div class="alert alert-<?= $mensagemTipo ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensagem) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-3">Todas as Transações</h1>
            <a href="criar.php" class="btn btn-success">+ Nova Transação</a>
            <a href="index.php" class="btn btn-secondary">Voltar ao Dashboard</a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transacoes)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Nenhuma transação registrada.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transacoes as $transacao): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)$transacao['id']) ?></td>
                                        <td>
                                            <span class="badge <?= $transacao['tipo'] === 'receita' ? 'bg-success' : ($transacao['tipo'] === 'diario' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                                <?= $transacao['tipo'] === 'receita' ? 'Entrada' : ($transacao['tipo'] === 'diario' ? 'Diário' : 'Saída') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($transacao['descricao']) ?></td>
                                        <td><?= formatarData($transacao['data_transacao']) ?></td>
                                        <td class="<?= $transacao['tipo'] === 'receita' ? 'receita' : ($transacao['tipo'] === 'diario' ? 'diario' : 'despesa') ?>">
                                            <?= ($transacao['tipo'] === 'receita' ? '+ ' : '- ') . formatarValor((float)$transacao['valor']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($transacao['criado_em']) ?></td>
                                        <td>
                                            <a href="editar.php?id=<?= $transacao['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
                                            <a href="delete.php?id=<?= $transacao['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja deletar?')">Deletar</a>
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
