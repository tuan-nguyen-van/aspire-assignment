<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoanApproveRequest;
use App\Http\Requests\LoanShowRequest;
use App\Http\Requests\LoanStoreRequest;
use App\Http\Transactions\Loan\LoanApproveTransaction;
use App\Http\Transactions\Loan\LoanStoreTransaction;
use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(LoanStoreRequest $request)
    {
        /**
         * @var array<string,int|string> $validatedData
         */
        $validatedData = $request->validated();
        $validatePeriod = $request->validatePaymentPeriod();
        if ($validatePeriod !== true) {
            return $validatePeriod;
        }

        /**
         * @var Loan|null;
         */
        $loan = null;
        // Make a transation here to save the new loan and create
        // a bunch of scheduled_repayments with the state 'pending'.
        $loanStoreTransaction = new LoanStoreTransaction($validatedData, $loan);
        $loanStoreTransaction->commit();

        return response()->json([
            'loan' => $loan,
            'repayments' => ScheduledRepayment::where('loan_id', $loan->id)->get(),
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(LoanApproveRequest $request, Loan $loan)
    {
        /** 
         * Check if the user who owns the api token is admin or not.
         */
        $request->validated();

        if ($loan->state === 'pending') {
            $loanApproveTransaction = new LoanApproveTransaction($loan);
            $loanApproveTransaction->commit();
        } else {
            return response()->json([
                'loan' => 'Already processed this loan',
            ], 422);
        }

        return response()->json([
            'loan' => $loan,
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json([
            'loans' => Loan::where('user_id', $request->user()->id)
                ->with(['scheduledRepayments'])->get(),
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(LoanShowRequest $request, Loan $loan)
    {
        if ($request->validateUser($loan) !== true) {
            return $request->validateUser($loan);
        }

        return response()->json([
            'loan' => $loan,
        ]);
    }
}
