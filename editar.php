<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/servicos/autenticacao.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['mensagem'] = 'ID de transação inválido.';
    $_SESSION['mensagem_tipo'] = 'danger';
    header('Location: index.php');
    exit;
}

// Buscar dados atuais da transação
$stmt = $pdo->prepare('SELECT * FROM transacoes WHERE id = :id AND user_id = :user_id');
$stmt->execute(['id' => $id, 'user_id' => $_SESSION['usuario_id']]);
$transacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transacao) {
    $_SESSION['mensagem'] = 'Transação não encontrada.';
    $_SESSION['mensagem_tipo'] = 'danger';
    header('Location: index.php');
    exit;
}

$mensagem = '';
$mensagemTipo = 'info';

// U - UPDATE: Salvar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING) ?? '';
    $valorBruto = filter_input(INPUT_POST, 'valor', FILTER_UNSAFE_RAW) ?? '';
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_UNSAFE_RAW) ?? '';
    $data = filter_input(INPUT_POST, 'data', FILTER_SANITIZE_STRING) ?? '';

    if (empty($tipo) || empty($valorBruto) || empty($descricao) || empty($data)) {
        $mensagem = 'Todos os campos são obrigatórios.';
        $mensagemTipo = 'danger';
    } else {
        try {
            $valor = (float) str_replace(',', '.', trim($valorBruto));

            if ($valor <= 0) {
                throw new Exception('O valor deve ser maior que zero.');
            }

            if (!in_array($tipo, ['receita', 'despesa', 'diario'], true)) {
                throw new Exception('Tipo de transação inválido.');
            }

            // Validar data
            $dateObj = DateTime::createFromFormat('Y-m-d', $data);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $data) {
                throw new Exception('Data inválida.');
            }

            $stmt = $pdo->prepare(
                "UPDATE transacoes SET tipo = :tipo, valor = :valor, descricao = :descricao, data_transacao = :data WHERE id = :id AND user_id = :user_id"
            );
            $result = $stmt->execute([
                'tipo' => $tipo,
                'valor' => $valor,
                'descricao' => $descricao,
                'data' => $data,
                'id' => $id,
                'user_id' => $_SESSION['usuario_id']
            ]);

            if ($result) {
                $_SESSION['mensagem'] = 'Transação atualizada com sucesso!';
                $_SESSION['mensagem_tipo'] = 'success';
                header('Location: index.php');
                exit;
            } else {
                throw new Exception('Erro ao atualizar transação.');
            }
        } catch (Exception $e) {
            $mensagem = $e->getMessage();
            $mensagemTipo = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Transação - MyPocket</title>
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

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-secondary {
            background: #edf2ef;
            border-color: #edf2ef;
            color: var(--text);
        }

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
                    <a class="nav-link" href="listar.php">Todas as Transações</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if ($mensagem): ?>
                <div class="alert alert-<?= $mensagemTipo ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($mensagem) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Editar Transação #<?= htmlspecialchars((string)$transacao['id']) ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Transação *</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Selecione...</option>
                                <option value="receita" <?= $transacao['tipo'] === 'receita' ? 'selected' : '' ?>>Entrada (Receita)</option>
                                <option value="despesa" <?= $transacao['tipo'] === 'despesa' ? 'selected' : '' ?>>Saída (Despesa)</option>
                                <option value="diario" <?= $transacao['tipo'] === 'diario' ? 'selected' : '' ?>>Diário</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição *</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" 
                                   value="<?= htmlspecialchars($transacao['descricao']) ?>" required>
                            <small class="text-muted">Descreva a origem/destino do dinheiro</small>
                        </div>

                        <div class="mb-3">
                            <label for="valor" class="form-label">Valor *</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control" id="valor" name="valor" 
                                       value="<?= htmlspecialchars((string)$transacao['valor']) ?>" 
                                       step="0.01" min="0.01" required>
                            </div>
                            <small class="text-muted">Digite o valor em reais</small>
                        </div>

                        <div class="mb-3">
                            <label for="data" class="form-label">Data da Transação *</label>
                            <input type="date" class="form-control" id="data" name="data" 
                                   value="<?= htmlspecialchars($transacao['data_transacao']) ?>" required>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Atualizar Transação</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
