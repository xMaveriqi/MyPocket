<?php

declare(strict_types=1);

require_once __DIR__ . '/Receita.php';
require_once __DIR__ . '/Despesa.php';
require_once __DIR__ . '/Diario.php';

class Carteira
{
    private float $saldo = 0.0;
    /** @var Transacao[] */
    private array $historico = [];

    public function __construct(private PDO $pdo, private ?int $userId = null)
    {
        $sql = 'SELECT tipo, valor, descricao, data_transacao FROM transacoes';
        $params = [];
        if ($this->userId !== null) {
            $sql .= ' WHERE user_id = :user_id';
            $params['user_id'] = $this->userId;
        }
        $sql .= ' ORDER BY data_transacao ASC, id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        foreach ($stmt as $transacao) {
            $objeto = match ($transacao['tipo']) {
                'receita' => new Receita((float) $transacao['valor'], $transacao['descricao'], $transacao['data_transacao']),
                'diario' => new Diario((float) $transacao['valor'], $transacao['descricao'], $transacao['data_transacao']),
                default => new Despesa((float) $transacao['valor'], $transacao['descricao'], $transacao['data_transacao']),
            };

            $this->adicionarAoHistorico($objeto);
        }
    }

    public function getSaldo(): float
    {
        return $this->saldo;
    }

    /**
     * @return Transacao[]
     */
    public function getHistorico(): array
    {
        return $this->historico;
    }

    public function adicionarReceita(Receita $receita): void
    {
        $this->salvarTransacao($receita, 'receita');
    }

    public function adicionarDespesa(Despesa $despesa): void
    {
        if ($despesa->getValor() > $this->saldo) {
            throw new Exception('Saldo insuficiente para essa despesa.');
        }

        $this->salvarTransacao($despesa, 'despesa');
    }

    public function adicionarDiario(Diario $diario): void
    {
        $this->salvarTransacao($diario, 'diario');
    }

    private function salvarTransacao(Transacao $transacao, string $tipo): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO transacoes (user_id, tipo, valor, descricao, data_transacao)
             VALUES (:user_id, :tipo, :valor, :descricao, :data_transacao)'
        );
        $stmt->execute([
            'user_id' => $this->userId,
            'tipo' => $tipo,
            'valor' => $transacao->getValor(),
            'descricao' => $transacao->getDescricao(),
            'data_transacao' => $transacao->getDataFormatada('Y-m-d'),
        ]);

        $this->adicionarAoHistorico($transacao);
    }

    private function adicionarAoHistorico(Transacao $transacao): void
    {
        $this->saldo += $transacao instanceof Receita ? $transacao->getValor() : -$transacao->getValor();
        $this->historico[] = $transacao;
    }

}
