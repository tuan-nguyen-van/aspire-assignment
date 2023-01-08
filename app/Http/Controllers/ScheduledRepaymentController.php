<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduledRepaymentPayRequest;
use App\Http\Transactions\ScheduledRepayment\RepaymentPayTransaction;
use App\Models\Loan;
use App\Models\ScheduledRepayment;

class ScheduledRepaymentController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function pay(
        ScheduledRepaymentPayRequest $request,
        ScheduledRepayment $repayment
    ) {
        $request->validated();
        /**
         * @var Loan
         */
        $loan = Loan::where('id', $repayment->loan_id)->first();
        /**
         * @var float
         */
        $remainedPrinciple = $loan->remained_principle;

        $validateAmountAndState = $request->validateAmountAndState(
            $repayment,
            $remainedPrinciple
        );
        if ($validateAmountAndState !== true) {
            return $validateAmountAndState;
        }

        $repaymentPayTransaction = new RepaymentPayTransaction(
            $remainedPrinciple,
            $loan,
            $request,
            $repayment
        );
        $repaymentPayTransaction->commit();

        return response()->json([
            'scheduledRepayment' => ScheduledRepayment::find($repayment->id),
            'loan' => Loan::where('id', $repayment->loan_id)
                ->with(['scheduledRepayments'])->first(),
        ]);
    }
}
