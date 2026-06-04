<?php

namespace App\Erp\Services\EFatura;

class EFaturaResult
{
    public function __construct(
        public readonly bool   $success,
        public readonly string $uuid    = '',
        public readonly string $ettn    = '',
        public readonly string $status  = 'pending',
        public readonly string $message = '',
    ) {}

    public static function success(string $uuid, string $ettn = '', string $status = 'pending'): self
    {
        return new self(true, $uuid, $ettn, $status);
    }

    public static function failure(string $message): self
    {
        return new self(false, '', '', 'rejected', $message);
    }
}
