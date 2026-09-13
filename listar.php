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
        .receita { color: #28a745; font-weight: bold; }
        .despesa { color: #dc3545; font-weight: bold; }
        .diario { color: #fd7e14; font-weight: bold; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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
