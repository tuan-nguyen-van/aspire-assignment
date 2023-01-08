<?php

namespace App\Http\Requests;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;

class LoanStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Check the user who owns the api token is the same as $this->request->user_id
        if ($this->user()->id !== $this->request->get('user_id')) {
            return false;
        }
        // Admin users cannot create loan for themself.
        if ($this->user()->isAdmin()) {
            return false;
        }

        return true;
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
            'amount' => 'required|integer|min:1',
            'term' => 'required|integer|min:1|max:100',
            'payment_period' => 'required|string|min:6',
            'start_date' => 'required|date_format:Y-m-d|after_or_equal:today',
        ];
    }

    /**
     * Check enum type validation only support from PHP 8.1 on Laravel
     * So I validate this payment_period manually.
     *
     * @return \Illuminate\Http\JsonResponse|true
     */
    public function validatePaymentPeriod()
    {
        // Check the payment_period is weekly or monthly.
        if (!in_array($this->request->get('payment_period'), Loan::PAYMENT_PERIOD)) {
            return response()->json([
                'payment_period' => sprintf(
                    'Payment period must be either %s or %s',
                    Loan::PAYMENT_PERIOD[0],
                    Loan::PAYMENT_PERIOD[1]
                ),
            ], 422);
        }

        return true;
    }
}
