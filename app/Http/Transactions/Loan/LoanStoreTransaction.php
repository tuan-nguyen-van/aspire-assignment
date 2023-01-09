<?php

namespace App\Http\Transactions\Loan;

use App\Http\Transactions\Transaction;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoanStoreTransaction implements Transaction
{
    /**
     * @param array<string,int|string> $validatedData
     * @param Loan|null                $loan
     *
     * @return void
     */
    public function __construct(private $validatedData, private &$loan)
    {
    }

    /**
     * @return void
     */
    public function commit()
    {
        $validatedData = $this->validatedData;
        DB::transaction(function () use ($validatedData) {
            // The remained_principle equals the amount in the beginning.
            $validatedData['remained_principle'] = $validatedData['amount'];
            $this->loan = Loan::create($validatedData);
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
                $dueDate = $validatedData['payment_period'] === Loan::WEEKLY ?
                    $carbonStartDate->addWeeks($i)->format('Y-m-d') :
                    $carbonStartDate->addMonths($i)->format('Y-m-d');

                // Calculate the amount for the last ScheduledRepayment to ensure the total
                // amount of all ScheduledRepayments equal $validatedData['amount']
                // to prevent round(..., 2) summing up not equal $validatedData['amount'].
                /** @var int */
                $inputAmount = $validatedData['amount'];
                /** @var int */
                $inputTerm = $validatedData['term'];
                $amountOfRepayment = ($i > 1 && $i === $validatedData['term']) ?
                    $inputAmount - $totalAmountOfRepayments :
                    round($inputAmount / $inputTerm, 2);

                $scheduledRepayments[] = [
                    'loan_id' => $this->loan->id,
                    'amount' => $amountOfRepayment,
                    'due_date' => $dueDate,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $totalAmountOfRepayments += $amountOfRepayment;
            }
            // Use bulk insert here instead of single ScheduledRepayment::create()
            // one at a time because that causes many queries to database (N+1 problem)
            // and makes application runs slower.
            DB::table('scheduled_repayments')->insert($scheduledRepayments);
        });
    }
}
