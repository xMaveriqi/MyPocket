<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/servicos/autenticacao.php';
require_once __DIR__ . '/servicos/auxilares.php';
require_once __DIR__ . '/servicos/recorrenciaservico.php';

$servico = new RecorrenciaServico($pdo, (int) $_SESSION['usuario_id']);
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['acao'] ?? '') === 'criar') {
            $servico->criar(
                (string) ($_POST['tipo'] ?? ''),
                (float) ($_POST['valor'] ?? 0),
                (string) ($_POST['descricao'] ?? ''),
                (string) ($_POST['data_inicio'] ?? ''),
                (string) ($_POST['data_fim'] ?? '')
            );
        } elseif (($_POST['acao'] ?? '') === 'alternar') {
            $servico->alternar((int) ($_POST['id'] ?? 0));
        }
        header('Location: recorrencias.php');
        exit;
    } catch (Throwable $exception) {
        $erro = $exception->getMessage();
    }
}

$recorrencias = $servico->listar();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recorrências - MyPocket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary"><div class="container-fluid"><a class="navbar-brand" href="index.php">MyPocket</a><a class="btn btn-outline-light" href="logout.php">Sair</a></div></nav>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 mb-0">Lançamentos recorrentes</h1><a class="btn btn-secondary" href="index.php">Dashboard</a></div>
    <?php if ($erro !== null): ?><div class="alert alert-danger"><?= escape($erro) ?></div><?php endif; ?>
    <div class="card shadow-sm mb-4"><div class="card-body">
        <h2 class="h5">Nova recorrência mensal</h2>
        <form class="row g-3" method="post">
            <input type="hidden" name="acao" value="criar">
            <div class="col-md-3"><label class="form-label" for="tipo">Tipo</label><select class="form-select" id="tipo" name="tipo"><option value="receita">Receita</option><option value="despesa">Despesa</option><option value="diario">Diário</option></select></div>
            <div class="col-md-3"><label class="form-label" for="valor">Valor</label><input class="form-control" id="valor" name="valor" type="number" min="0.01" step="0.01" required></div>
            <div class="col-md-6"><label class="form-label" for="descricao">Descrição</label><input class="form-control" id="descricao" name="descricao" required></div>
            <div class="col-md-3"><label class="form-label" for="data_inicio">Começa em</label><input class="form-control" id="data_inicio" name="data_inicio" type="date" required></div>
            <div class="col-md-3"><label class="form-label" for="data_fim">Termina em</label><input class="form-control" id="data_fim" name="data_fim" type="date"></div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Salvar recorrência</button></div>
        </form>
    </div></div>
    <div class="card shadow-sm"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Tipo</th><th>Descrição</th><th>Valor</th><th>Período</th><th>Status</th><th>Ação</th></tr></thead><tbody>
    <?php foreach ($recorrencias as $recorrencia): ?>
        <tr><td><?= escape($recorrencia['tipo']) ?></td><td><?= escape($recorrencia['descricao']) ?></td><td><?= formatarValor((float) $recorrencia['valor']) ?></td><td><?= formatarData($recorrencia['data_inicio']) ?> até <?= $recorrencia['data_fim'] ? formatarData($recorrencia['data_fim']) : 'indefinido' ?></td><td><?= $recorrencia['ativa'] ? 'Ativa' : 'Pausada' ?></td><td><form method="post"><input type="hidden" name="acao" value="alternar"><input type="hidden" name="id" value="<?= (int) $recorrencia['id'] ?>"><button class="btn btn-sm btn-outline-secondary" type="submit"><?= $recorrencia['ativa'] ? 'Pausar' : 'Ativar' ?></button></form></td></tr>
    <?php endforeach; ?>
    <?php if (!$recorrencias): ?><tr><td colspan="6" class="text-center text-muted">Nenhuma recorrência cadastrada.</td></tr><?php endif; ?>
    </tbody></table></div></div>
</main>
</body>
</html>
