<?php

/** Funções pequenas de apresentação compartilhadas pelas páginas. */

declare(strict_types=1);

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatarValor(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function formatarData(string $data): string
{
    return (new DateTime($data))->format('d/m/Y');
}