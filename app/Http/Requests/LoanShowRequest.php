<?php

namespace App\Http\Requests;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;

class LoanShowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
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
        ];
    }

    /**
     * @return \Illuminate\Http\JsonResponse|true
     */
    public function validateUser(Loan $loan)
    {
        // Check if the user owns the api token is the owner of the loan
        // We can allow admin to view user's loan as well
        if ($this->user()->id !== $loan->user_id && !$this->user()->isAdmin()) {
            return response()->json([
                'message' => 'This action is unauthorized.',
            ], 403);
        }

        return true;
    }
}
