<?php

/**
 * Abre a conexão PDO e garante a estrutura mínima do banco.
 * A conexão fica centralizada aqui para que as páginas não repitam SQL de configuração.
 */

declare(strict_types=1);

$host = 'localhost';
$db = 'sistema_crud';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Usuários são donos dos próprios dados financeiros.
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Uma recorrência é um modelo que pode gerar lançamentos mensais.
    $pdo->exec("CREATE TABLE IF NOT EXISTS recorrencias (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        tipo ENUM('receita', 'despesa', 'diario') NOT NULL,
        valor DECIMAL(10, 2) NOT NULL,
        descricao VARCHAR(255) NOT NULL,
        data_inicio DATE NOT NULL,
        data_fim DATE NULL,
        ativa TINYINT(1) NOT NULL DEFAULT 1,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_recorrencias_usuario (user_id, ativa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS transacoes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        tipo ENUM('receita', 'despesa', 'diario') NOT NULL,
        valor DECIMAL(10, 2) NOT NULL,
        descricao VARCHAR(255) NOT NULL,
        data_transacao DATE NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_transacoes_usuario_data (user_id, data_transacao)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Migração simples para instalações antigas que ainda não tinham user_id.
    try {
        $pdo->exec('ALTER TABLE transacoes ADD COLUMN user_id INT UNSIGNED NULL AFTER id');
    } catch (PDOException $exception) {
        if (stripos($exception->getMessage(), 'duplicate column') === false) {
            throw $exception;
        }
    }

    try {
        $pdo->exec('ALTER TABLE transacoes ADD COLUMN recorrencia_id INT UNSIGNED NULL AFTER user_id');
    } catch (PDOException $exception) {
        if (stripos($exception->getMessage(), 'duplicate column') === false) {
            throw $exception;
        }
    }
} catch (PDOException $exception) {
    die('Erro na conexão com o banco de dados: ' . $exception->getMessage());
}
