<?php

declare(strict_types=1);

abstract class Transacao
{
    private float $valor;
    private DateTimeImmutable $data;
    private string $descricao;

    public function __construct(float $valor, string $descricao, string $data)
    {
        if ($valor <= 0) {
            throw new InvalidArgumentException('O valor deve ser maior que zero.');
        }

        $descricao = trim($descricao);
        if ($descricao === '') {
            throw new InvalidArgumentException('A descrição é obrigatória.');
        }

        try {
            $this->data = new DateTimeImmutable($data);
        } catch (Exception $exception) {
            throw new InvalidArgumentException('Data inválida.');
        }

        $this->valor = $valor;
        $this->descricao = $descricao;
    }

    public function getValor(): float
    {
        return $this->valor;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function getData(): DateTimeImmutable
    {
        return $this->data;
    }

    public function getDataFormatada(string $formato = 'd/m/Y'): string
    {
        return $this->data->format($formato);
    }

    abstract public function getTipo(): string;
}
