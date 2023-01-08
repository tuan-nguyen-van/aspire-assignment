<?php

namespace App\Http\Requests;

use App\Models\ScheduledRepayment;
use Illuminate\Foundation\Http\FormRequest;

class ScheduledRepaymentPayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->id === $this->request->get('user_id');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'user_id' => 'required|integer|min:1',
            'amount' => 'required|decimal:0,2|min:0.01',
        ];
    }

    /**
     * @param ScheduledRepayment $repayment
     * @param float              $remainedPrinciple
     *
     * @return true|\Illuminate\Http\JsonResponse
     */
    public function validateAmountAndState($repayment, $remainedPrinciple)
    {
        // The amount must be greater than or equal the repayment amount
        if ($this->request->get('amount') < $repayment->amount) {
            return response()->json([
                'amount' => "The amount must be at least: $repayment->amount.",
            ], 422);
        }
        // The repayment amount must be less than or equal the remained_principle.
        if ($this->request->get('amount') > $remainedPrinciple) {
            return response()->json([
                'amount' => 'The max amount of money you can pay for this loan is: '
                    . (float) $remainedPrinciple . '.',
            ], 422);
        }
        // The state of the repayment must be 'active'.
        if ($repayment->state !== 'active') {
            return response()->json([
                'state' => 'The scheduled payment is not active.',
            ], 422);
        }

        return true;
    }
}
