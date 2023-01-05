<?php

namespace Database\Factories;

use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $period = Loan::PAYMENT_PERIOD[array_rand(Loan::PAYMENT_PERIOD)];
        $state = Loan::STATE[array_rand(Loan::STATE)];
        $amount = fake()->numberBetween(1000, 100000);

        return [
            'amount' => $amount,
            'remained_principle' => $amount,
            'term' => fake()->numberBetween(1, 12),
            'payment_period' => $period,
            'start_date' => Carbon::now()->addDays(fake()->numberBetween(-100, 100))->format('Y-m-d'),
            'state' => $state,
        ];
    }
}
