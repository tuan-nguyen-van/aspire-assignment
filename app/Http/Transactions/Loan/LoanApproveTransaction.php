<?php

namespace App\Http\Transactions\Loan;

use App\Http\Transactions\Transaction;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;

class LoanApproveTransaction implements Transaction
{
    /**
     * @param Loan $loan
     *
     * @return void
     */
    public function __construct(private &$loan)
    {
    }

    /**
     * @return void
     */
    public function commit()
    {
        DB::transaction(function () {
            $this->loan->update([
                'state' => 'approved',
            ]);
            DB::table('scheduled_repayments')
                ->where('loan_id', $this->loan->id)
                ->update(['state' => 'active']);
        });
    }
}
