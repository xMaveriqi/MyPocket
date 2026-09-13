<?php

/** Centraliza a criação e a geração de lançamentos recorrentes. */

declare(strict_types=1);

class RecorrenciaServico
{
    public function __construct(private PDO $pdo, private int $userId)
    {
    }

    public function criar(string $tipo, float $valor, string $descricao, string $inicio, ?string $fim): void
    {
        if ($valor <= 0 || trim($descricao) === '') {
            throw new InvalidArgumentException('Informe um valor e uma descrição válidos.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO recorrencias (user_id, tipo, valor, descricao, data_inicio, data_fim)
             VALUES (:user_id, :tipo, :valor, :descricao, :inicio, :fim)'
        );
        $stmt->execute([
            'user_id' => $this->userId,
            'tipo' => $tipo,
            'valor' => $valor,
            'descricao' => trim($descricao),
            'inicio' => $inicio,
            'fim' => $fim !== '' ? $fim : null,
        ]);
    }

    /** Gera uma ocorrência mensal sem duplicar o mesmo lançamento. */
    public function gerarMes(int $ano, int $mes): void
    {
        $competencia = sprintf('%04d-%02d-01', $ano, $mes);
        $stmt = $this->pdo->prepare('SELECT * FROM recorrencias WHERE user_id = :user_id AND ativa = 1');
        $stmt->execute(['user_id' => $this->userId]);

        foreach ($stmt as $recorrencia) {
            $inicio = new DateTimeImmutable($recorrencia['data_inicio']);
            $fim = $recorrencia['data_fim'] ? new DateTimeImmutable($recorrencia['data_fim']) : null;
            $data = new DateTimeImmutable($competencia);
            if ($data < new DateTimeImmutable($inicio->format('Y-m-01')) || ($fim !== null && $data > $fim)) {
                continue;
            }

            $dataLancamento = $data->format('Y-m-d');
            $check = $this->pdo->prepare(
                'SELECT id FROM transacoes WHERE user_id = :user_id AND recorrencia_id = :recorrencia_id AND data_transacao = :data'
            );
            $check->execute([
                'user_id' => $this->userId,
                'recorrencia_id' => $recorrencia['id'],
                'data' => $dataLancamento,
            ]);
            if ($check->fetchColumn()) {
                continue;
            }

            $insert = $this->pdo->prepare(
                'INSERT INTO transacoes (user_id, recorrencia_id, tipo, valor, descricao, data_transacao)
                 VALUES (:user_id, :recorrencia_id, :tipo, :valor, :descricao, :data)'
            );
            $insert->execute([
                'user_id' => $this->userId,
                'recorrencia_id' => $recorrencia['id'],
                'tipo' => $recorrencia['tipo'],
                'valor' => $recorrencia['valor'],
                'descricao' => $recorrencia['descricao'],
                'data' => $dataLancamento,
            ]);
        }
    }

    public function listar(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM recorrencias WHERE user_id = :user_id ORDER BY ativa DESC, data_inicio DESC');
        $stmt->execute(['user_id' => $this->userId]);
        return $stmt->fetchAll();
    }

    public function alternar(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE recorrencias SET ativa = NOT ativa WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $this->userId]);
    }
}