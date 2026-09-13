<?php

declare(strict_types=1);

require_once __DIR__ . '/classes/Carteira.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/servicos/autenticacao.php';

$carteira = new Carteira($pdo, (int) $_SESSION['usuario_id']);
$message = '';
$messageType = 'suçeso';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING) ?? '';
    $valorBruto = filter_input(INPUT_POST, 'valor', FILTER_UNSAFE_RAW) ?? '';
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_UNSAFE_RAW) ?? '';
    $data = filter_input(INPUT_POST, 'data', FILTER_SANITIZE_STRING) ?? date('Y-m-d');

    $valor = (float) str_replace(',', '.', trim($valorBruto));

    try {
        if ($tipo === 'receita') {
            $receita = new Receita($valor, $descricao, $data);
            $carteira->adicionarReceita($receita);
        } elseif ($tipo === 'despesa') {
            $despesa = new Despesa($valor, $descricao, $data);
            $carteira->adicionarDespesa($despesa);
        } elseif ($tipo === 'diario') {
            $diario = new Diario($valor, $descricao, $data);
            $carteira->adicionarDiario($diario);
        } else {
            throw new Exception('Tipo de transação inválido.');
        }

        $_SESSION['mensagem'] = 'Lançamento registrado com sucesso.';
        $_SESSION['mensagem_tipo'] = 'success';
    } catch (Exception $exception) {
        $_SESSION['mensagem'] = $exception->getMessage();
        $_SESSION['mensagem_tipo'] = 'danger';
    }

    header('Location: index.php');
    exit;
}
