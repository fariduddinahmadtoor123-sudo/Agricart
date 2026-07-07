<?php

namespace App\Services\Sales;

class SalesBankAccountService
{
    /**
     * @return list<array{id: int, label: string}>
     */
    public function options(): array
    {
        // Finance → Bank Accounts module will feed this list later.
        return [];
    }
}
