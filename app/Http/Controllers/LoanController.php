<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validatedData = $this->validateLoanInputs($request);

        // Check the payment_period is weekly or monthly.
        if ($this->validatePaymentPeriod($request) !== 'pass') {
            return $this->validatePaymentPeriod($request);
        }

        // Check the user who owns the api token is the same as $request->user_id
        if ($request->user()->id !== $request->user_id) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 401);
        }
        // Make a transation here to save the new loan and create
        // a bunch of scheduled_repayments with the state 'pending'.
        /**
         * @var Loan|null;
         */
        $loan = null;
        DB::transaction(function () use ($validatedData, &$loan) {
            // The remained_principle equals the amount in the beginning.
            $validatedData['remained_principle'] = $validatedData['amount'];
            $loan = Loan::create($validatedData);
            $totalAmountOfRepayments = 0;
            /**
             * @var array<string,mixed>
             */
            $scheduledRepayments = [];
            for ($i = 1; $i <= $validatedData['term']; ++$i) {
                /**
                 * @var Carbon
                 *
                 * @phpstan-ignore-next-line
                 */
                $carbonStartDate = Carbon::createFromFormat('Y-m-d', $validatedData['start_date']);
                $dueDate = $validatedData['payment_period'] === Loan::PAYMENT_PERIOD[0] ?
                    $carbonStartDate->addWeeks($i)->format('Y-m-d') :
                    $carbonStartDate->addMonths($i)->format('Y-m-d');

                // Calculate the amount for the last ScheduledRepayment to ensure the total
                // amount of all ScheduledRepayments equal $validatedData['amount']
                // to prevent round(..., 2) summing up not equal $validatedData['amount'].
                $amount = ($i > 1 && $i === $validatedData['term']) ?
                    $validatedData['amount'] - $totalAmountOfRepayments :
                    round($validatedData['amount'] / $validatedData['term'], 2);

                $scheduledRepayments[] = [
                    'loan_id' => $loan->id,
                    'amount' => $amount,
                    'due_date' => $dueDate,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $totalAmountOfRepayments += $amount;
            }
            // Use bulk insert here instead of single ScheduledRepayment::create()
            // one at a time because that causes many queries to database
            // and makes application runs slower.
            DB::table('scheduled_repayments')->insert($scheduledRepayments);
        });

        return response()->json([
            'loan' => $loan,
            'repayments' => ScheduledRepayment::where('loan_id', $loan->id)->get(),
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request, Loan $loan)
    {
        /** 
         * Check if the user who owns the api token is admin or not.
         *
         * @phpstan-ignore-next-line 
         */
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 401);
        }

        if ($loan->state === 'pending') {
            DB::transaction(function () use (&$loan) {
                $loan->update([
                    'state' => 'approved',
                ]);

                DB::table('scheduled_repayments')
                    ->where('loan_id', $loan->id)
                    ->update(['state' => 'active']);
            });
        } else {
            return response()->json([
                'loan' => 'Already processed this loan',
            ], 422);
        }

        return response()->json([
            'status' => 'Approved',
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
     * @return array<string,mixed>
     */
    private function validateLoanInputs(Request &$request)
    {
        return $request->validate([
            'user_id' => 'required|integer|min:1',
            'amount' => 'required|integer|min:1',
            'term' => 'required|integer|min:1|max:100',
            'payment_period' => 'required|string|min:6',
            'start_date' => 'required|date_format:Y-m-d|after_or_equal:today',
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse|'pass'
     */
    public function validatePaymentPeriod(Request &$request)
    {
        if (!in_array($request->payment_period, Loan::PAYMENT_PERIOD)) {
            return response()->json([
                'payment_period' => sprintf(
                    'Payment period must be either %s or %s',
                    Loan::PAYMENT_PERIOD[0],
                    Loan::PAYMENT_PERIOD[1]
                ),
            ], 422);
        }

        return 'pass';
    }
}
