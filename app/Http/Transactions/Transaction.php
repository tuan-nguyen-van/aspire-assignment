<?php

namespace App\Http\Transactions;

interface Transaction
{
    /**
     * @return void
     */
    public function commit();
}
