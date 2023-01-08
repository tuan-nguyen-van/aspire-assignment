<?php

namespace App\Http\Transactions\ScheduledRepayment;

use App\Http\Requests\ScheduledRepaymentPayRequest;
use App\Http\Transactions\Transaction;
use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Illuminate\Support\Facades\DB;

class RepaymentPayTransaction implements Transaction
{
    /**
     * @param float                        $remainedPrinciple
     * @param Loan                         $loan
     * @param ScheduledRepaymentPayRequest $request
     * @param ScheduledRepayment           $repayment
     *
     * @return void
     */
    public function __construct(
        private $remainedPrinciple,
        private $loan,
        private $request,
        private $repayment
    ) {
    }

    /**
     * @return void
     */
    public function commit()
    {
        DB::transaction(function () {
            // Reduce the remained_principle of the loan
            $newRemainedPrinciple = $this->remainedPrinciple - $this->request->amount;
            $this->loan->update([
                'remained_principle' => $newRemainedPrinciple,
            ]);

            // If the $newRemainedPrinciple is 0 then we just need
            // to change the rest of repayments and the loan to state "paid"
            if (round($newRemainedPrinciple, 2) === 0.00) {
                $this->loan->update([
                    'state' => 'paid',
                ]);
                ScheduledRepayment::where('state', 'active')
                    ->where('loan_id', $this->loan->id)->update([
                        'state' => 'paid',
                    ]);
            } else {
                $repaymentCurAmount = $this->repayment->amount;
                $this->repayment->update([
                    'amount' => $this->request->amount,
                    'state' => 'paid',
                ]);
                // Distribute the $newRemainedPrinciple equally for the rest of the
                // active scheduledRepayments in case the amount is greater than the
                // chosen repayment amount
                if ($this->request->amount > $repaymentCurAmount) {
                    $otherRepaymentsQuery = ScheduledRepayment::where('state', 'active')
                        ->where('id', '<>', $this->repayment->id)
                        ->where('loan_id', $this->loan->id);

                    $numberOfRepayments = $otherRepaymentsQuery->get()->count();
                    $otherRepaymentsQuery->update([
                        'amount' => round($newRemainedPrinciple / $numberOfRepayments, 2),
                        'state' => round($newRemainedPrinciple / $numberOfRepayments, 2) === 0.00
                            ? 'paid' : 'active',
                    ]);
                }
            }
        });
    }
}
