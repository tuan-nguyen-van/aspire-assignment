<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduledRepaymentController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function pay(Request $request, ScheduledRepayment $repayment)
    {
        $request->validate([
            'user_id' => 'required|integer|min:1',
            'amount' => 'required|decimal:0,2|min:0.01',
        ]);
        // Validate the user_id must be the same as the user who own the token.
        if ($request->user()->id !== $request->user_id) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 401);
        }

        /**
         * @var Loan
         */
        $loan = Loan::where('id', $repayment->loan_id)->first();
        /**
         * @var float
         */
        $remainedPrinciple = $loan->remained_principle;

        // The amount must be greater than or equal then repayment amount
        if ($request->amount < $repayment->amount) {
            return response()->json([
                'amount' => "The amount must be at least: $repayment->amount.",
            ], 422);
            // The repayment amount must be less than or equal the remained_principle.
        }
        if ($request->amount > $remainedPrinciple) {
            return response()->json([
                'amount' => 'The max amount of money you can pay for this loan is: '
                    . (float) $remainedPrinciple . '.',
            ], 422);
            // The state of the repayment must be 'active'.
        }
        if ($repayment->state !== 'active') {
            return response()->json([
                'amount' => 'The scheduled payment is not active yet.',
            ], 422);
        }

        DB::transaction(function () use ($remainedPrinciple, $loan, $request, $repayment) {
            // Reduce the remained_principle of the loan
            $newRemainedPrinciple = $remainedPrinciple - $request->amount;
            $loan->update([
                'remained_principle' => $newRemainedPrinciple,
            ]);

            // If the $newRemainedPrinciple is 0 then we just need
            // to change the rest of repayments and the loan to state "paid"
            if (round($newRemainedPrinciple, 2) === 0.00) {
                $loan->update([
                    'state' => 'paid',
                ]);
                ScheduledRepayment::where('state', 'active')
                    ->where('loan_id', $loan->id)->update([
                        'state' => 'paid',
                    ]);
            } else {
                $repaymentCurAmount = $repayment->amount;
                $repayment->update([
                    'amount' => $request->amount,
                    'state' => 'paid',
                ]);
                // Distribute the $newRemainedPrinciple equally for the rest of the
                // active scheduledRepayments.
                if ($request->amount > $repaymentCurAmount) {
                    $otherRepaymentsQuery = ScheduledRepayment::where('state', 'active')
                        ->where('id', '<>', $repayment->id)
                        ->where('loan_id', $loan->id);

                    $numberOfRepayments = $otherRepaymentsQuery->get()->count();
                    $otherRepaymentsQuery->update([
                        'amount' => round($newRemainedPrinciple / $numberOfRepayments, 2),
                        'state' => round($newRemainedPrinciple / $numberOfRepayments, 2) === 0.00
                            ? 'paid' : 'active',
                    ]);
                }
            }
        });

        return response()->json([
            'scheduledRepayment' => ScheduledRepayment::find($repayment->id),
            'loan' => Loan::where('id', $repayment->loan_id)
                ->with(['scheduledRepayments'])->first(),
        ]);
    }
}
