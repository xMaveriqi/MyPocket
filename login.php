<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');

    $stmt = $pdo->prepare('SELECT id, nome, senha FROM usuarios WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $pdo->prepare('UPDATE transacoes SET user_id = :user_id WHERE user_id IS NULL')
            ->execute(['user_id' => $usuario['id']]);
        header('Location: index.php');
        exit;
    }

    $erro = 'E-mail ou senha incorretos.';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar - MyPocket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2d6a62;
            --primary-dark: #214f4a;
            --bg: #f4f6f3;
            --surface: #ffffff;
            --border: #e5e7e6;
            --text: #1f2d2b;
            --muted: #64706d;
            --shadow: 0 10px 24px rgba(17, 24, 39, 0.06);
        }

        body {
            background: linear-gradient(180deg, #eef3f1 0%, var(--bg) 100%);
            color: var(--text);
            font-family: "Segoe UI", system-ui, sans-serif;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow);
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

        .form-control {
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #fff;
            box-shadow: none;
        }
    </style>
</head>
<body>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm"><div class="card-body p-4">
                <h1 class="h3 mb-4">Entrar no MyPocket</h1>
                <?php if ($erro !== null): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
                <form method="post">
                    <div class="mb-3"><label class="form-label" for="email">E-mail</label><input class="form-control" id="email" name="email" type="email" required></div>
                    <div class="mb-3"><label class="form-label" for="senha">Senha</label><input class="form-control" id="senha" name="senha" type="password" required></div>
                    <button class="btn btn-primary w-100" type="submit">Entrar</button>
                </form>
                <a class="d-block text-center mt-3" href="cadastro.php">Criar uma conta</a>
            </div></div>
        </div>
    </div>
</main>
</body>
</html>
