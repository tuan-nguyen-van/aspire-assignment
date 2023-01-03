<?php

namespace Database\Factories;

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
        $periods = ['weekly', 'monthly'];
        $states = ['approved', 'pending', 'paid'];

        return [
            'amount' => fake()->numberBetween(1000, 50000),
            'term' => fake()->numberBetween(1, 12),
            'payment_period' => $periods[array_rand($periods)],
            'start_date' => Carbon::now()->subDays(fake()->numberBetween(-10, 10))->format('Y-m-d'),
            'state' => $states[array_rand($states)],
        ];
    }
}
