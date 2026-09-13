<?php

declare(strict_types=1);

require_once __DIR__ . '/Transacao.php';

class Diario extends Transacao
{
    public function getTipo(): string
    {
        return 'Diário';
    }
}