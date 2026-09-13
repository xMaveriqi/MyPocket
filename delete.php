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

try {
    // D - DELETE: Remover transação do banco
    $stmt = $pdo->prepare('DELETE FROM transacoes WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $id, 'user_id' => $_SESSION['usuario_id']]);

    $_SESSION['mensagem'] = 'Transação deletada com sucesso!';
    $_SESSION['mensagem_tipo'] = 'success';
} catch (Exception $e) {
    $_SESSION['mensagem'] = 'Erro ao deletar transação: ' . $e->getMessage();
    $_SESSION['mensagem_tipo'] = 'danger';
}

header('Location: index.php');
exit;
