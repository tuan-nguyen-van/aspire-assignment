<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Bin\ApiToken;
use Tests\TestCase;

class ScheduledRepaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = true;

    private const AMOUNT = 10000;

    /**
     * @return void
     */
    public function testPayForARepaymentWithExactRepaymentAmount()
    {
        /**
         * @var Loan               $loan
         * @var ScheduledRepayment $firstRepayment
         */
        [$loan, $firstRepayment] = $this->createLoanWith3ScheduledRepayments();
        // Arrange user pay for the first scheduled_repayments
        $response = $this->patchJson(
            "/api/scheduled-repayments/pay/$firstRepayment->id",
            [
                'user_id' => 2,
                'amount' => round($loan->amount / $loan->term, 2),
            ],
            ApiToken::bearerHeader(2)
        );
        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('loan')->has('scheduledRepayment')
        );

        // Assert the result inside database.
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $firstRepayment->id,
            'state' => 'paid',
        ]);
        // The remained_principle is decremented by repayment amount
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'remained_principle' => round($loan->amount - round($loan->amount / $loan->term, 2), 2),
        ]);
    }

    /**
     * And less than remained_principle amount.
     * 
     * @return void
     */
    public function testPayForARepaymentWithMoreThanRepaymentAmount()
    {
        /**
         * @var Loan               $loan
         * @var ScheduledRepayment $firstRepayment
         * @var ScheduledRepayment $secondRepayment
         * @var ScheduledRepayment $thirdRepayment
         */
        [$loan, $firstRepayment, $secondRepayment, $thirdRepayment] = $this->createLoanWith3ScheduledRepayments();
        // Arrange user pay for the first scheduled_repayments with haft the loan amount
        $this->patchJson(
            "/api/scheduled-repayments/pay/$firstRepayment->id",
            [
                'user_id' => 2,
                'amount' => self::AMOUNT / 2,
            ],
            ApiToken::bearerHeader(2)
        )->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('loan')->has('scheduledRepayment')
        );
        // The amount of the first repayment is amount/2 and state is 'paid'
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $firstRepayment->id,
            'state' => 'paid',
            'amount' => self::AMOUNT / 2,
        ]);
        // Other two have amount change from AMOUNT/3 to AMOUNT/4
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $secondRepayment->id,
            'state' => 'active',
            'amount' => self::AMOUNT / 4,
        ]);
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $thirdRepayment->id,
            'state' => 'active',
            'amount' => self::AMOUNT / 4,
        ]);
        // The remained_principle of the loan change to AMOUNT/2
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'remained_principle' => self::AMOUNT / 2,
            'state' => 'approved',
        ]);
    }

    /**
     * @return void
     */
    public function testPayForARepaymentWithRemainedPrincipleAmount()
    {
        /**
         * @var Loan               $loan
         * @var ScheduledRepayment $firstRepayment
         * @var ScheduledRepayment $secondRepayment
         * @var ScheduledRepayment $thirdRepayment
         */
        [$loan, $firstRepayment, $secondRepayment, $thirdRepayment] =
            $this->createLoanWith3ScheduledRepayments();
        // Arrange user pays for the first scheduled_repayments with haft the loan amount
        $this->patchJson(
            "/api/scheduled-repayments/pay/$firstRepayment->id",
            [
                'user_id' => 2,
                'amount' => self::AMOUNT / 2,
            ],
            ApiToken::bearerHeader(2)
        )->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('loan')->has('scheduledRepayment')
        );

        // The loan has haft of the amount left for remained_principle.
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'remained_principle' => self::AMOUNT / 2,
            'state' => 'approved',
        ]);

        // Pay the $secondRepayment with exact remained_principle
        $this->patchJson(
            "/api/scheduled-repayments/pay/$secondRepayment->id",
            [
                'user_id' => 2,
                'amount' => self::AMOUNT / 2,
            ],
            ApiToken::bearerHeader(2)
        )->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('loan')->has('scheduledRepayment')
        );

        // The loan has remained_principle is 0 and the state is 'paid'
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'remained_principle' => 0.00,
            'state' => 'paid',
        ]);
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $firstRepayment->id,
            'amount' => self::AMOUNT / 2,
            'state' => 'paid',
        ]);
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $secondRepayment->id,
            'amount' => self::AMOUNT / 4,
            'state' => 'paid',
        ]);
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $thirdRepayment->id,
            'amount' => self::AMOUNT / 4,
            'state' => 'paid',
        ]);
    }

    /**
     * @return void
     */
    public function testPayForARepaymentWithInvalidInput()
    {
        /**
         * @var Loan               $loan
         * @var ScheduledRepayment $firstRepayment
         */
        [$loan, $firstRepayment] = $this->createLoanWith3ScheduledRepayments();
        // lack user_id input
        $this->patchJson(
            "/api/scheduled-repayments/pay/$firstRepayment->id",
            [
                'amount' => self::AMOUNT / 2,
            ],
            ApiToken::bearerHeader(2)
        )->assertStatus(422)
            ->assertJsonPath('errors.user_id', ['The user id field is required.']);

        // Lack amount input
        $this->patchJson(
            "/api/scheduled-repayments/pay/$firstRepayment->id",
            [
                'user_id' => 2,
            ],
            ApiToken::bearerHeader(2)
        )->assertStatus(422)
            ->assertJsonPath('errors.amount', ['The amount field is required.']);

        // The amount is less than the repayment amount.
        $this->patchJson(
            "/api/scheduled-repayments/pay/$firstRepayment->id",
            [
                'user_id' => 2,
                'amount' => self::AMOUNT / 10,
            ],
            ApiToken::bearerHeader(2)
        )->assertStatus(422)
            ->assertJsonPath('amount', 'The amount must be at least: '
                .  round(self::AMOUNT / 3, 2) . '.');

        // The amount is more than the remained_principle.
        $this->patchJson(
            "/api/scheduled-repayments/pay/$firstRepayment->id",
            [
                'user_id' => 2,
                'amount' => self::AMOUNT + 1,
            ],
            ApiToken::bearerHeader(2)
        )->assertStatus(422)
            ->assertJsonPath(
                'amount',
                'The max amount of money you can pay for this loan is: '
                    .  round(self::AMOUNT, 2) . '.'
            );

        // Test the state of the repayment is not active (state: paid).
        $this->patchJson(
            "/api/scheduled-repayments/pay/$firstRepayment->id",
            [
                'user_id' => 2,
                'amount' => round(self::AMOUNT / $loan->term, 2),
            ],
            ApiToken::bearerHeader(2)
        );
        // state is paid
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $firstRepayment->id,
            'state' => 'paid',
        ]);

        // Pay the first repayment again.
        $this->patchJson(
            "/api/scheduled-repayments/pay/$firstRepayment->id",
            [
                'user_id' => 2,
                'amount' => round(self::AMOUNT / $loan->term, 2),
            ],
            ApiToken::bearerHeader(2)
        )->assertStatus(422)
            ->assertJsonPath('state', 'The scheduled payment is not active.');
    }

    /**
     * @return void
     */
    public function testPayForARepaymentWithTokenOwnerIsNotTheSameAsLoaner()
    {
        /**
         * @var Loan               $loan
         * @var ScheduledRepayment $firstRepayment
         */
        [$loan, $firstRepayment] = $this->createLoanWith3ScheduledRepayments();
        // The third user pay for the second user repayment
        $this->patchJson(
            "/api/scheduled-repayments/pay/$firstRepayment->id",
            [
                'user_id' => 2,
                'amount' => round(self::AMOUNT / $loan->term, 2),
            ],
            ApiToken::bearerHeader(3)
        )->assertStatus(401)
            ->assertJsonPath('error', 'Unauthorized');
    }

    /**
     * @return array<Loan|ScheduledRepayment>
     */
    public function createLoanWith3ScheduledRepayments()
    {
        /** @var Loan */
        $loan = Loan::factory()->create([
            'user_id' => 2,
            'term' => 3,
            'amount' => self::AMOUNT,
            'remained_principle' => self::AMOUNT,
            'payment_period' => 'weekly',
            'state' => 'approved',
        ]);
        /** @var ScheduledRepayment */
        $firstRepayment = ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => round($loan->amount / $loan->term, 2),
            'due_date' => Carbon::createFromFormat('Y-m-d', $loan->start_date)
                ->addWeek()
                ->format('Y-m-d'),
            'state' => 'active',
        ]);
        /** @var ScheduledRepayment */
        $secondRepayment = ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => round($loan->amount / $loan->term, 2),
            'due_date' => Carbon::createFromFormat('Y-m-d', $loan->start_date)
                ->addWeeks(2)
                ->format('Y-m-d'),
            'state' => 'active',
        ]);
        /** @var ScheduledRepayment */
        $thirdRepayment = ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => round($loan->amount / $loan->term, 2),
            'due_date' => Carbon::createFromFormat('Y-m-d', $loan->start_date)
                ->addWeeks(3)
                ->format('Y-m-d'),
            'state' => 'active',
        ]);

        return [
            $loan, $firstRepayment, $secondRepayment, $thirdRepayment,
        ];
    }
}
