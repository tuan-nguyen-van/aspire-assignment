<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Bin\ApiToken;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = true;

    private const ROUTE = '/api/loan';

    /**
     * @return void
     */
    public function testUserCreatesNewMonthlyTermLoan()
    {
        // Create monthly term loan for the second user.
        $this->postJson(
            self::ROUTE,
            $this->getInputs(),
            ApiToken::bearerHeader(2)
        )->assertJson(
            fn (AssertableJson $json) => $json->has('loan')
                ->has('repayments')
        );

        $this->assertDatabaseHas('loans', [
            'id' => 1,
            'state' => 'pending',
            'amount' => 10000,
        ]);

        // Assert database has 3 new scheduled repayments.
        $this->assertDatabaseHas('scheduled_repayments', [
            'amount' => 3333.33,
            'due_date' => Carbon::now()->addDays(1)->addMonths(1)->format('Y-m-d'),
            'state' => 'pending',
        ]);
        $this->assertDatabaseHas('scheduled_repayments', [
            'amount' => 3333.33,
            'due_date' => Carbon::now()->addDays(1)->addMonths(2)->format('Y-m-d'),
            'state' => 'pending',
        ]);
        $this->assertDatabaseHas('scheduled_repayments', [
            'amount' => 3333.34,
            'due_date' => Carbon::now()->addDays(1)->addMonths(3)->format('Y-m-d'),
            'state' => 'pending',
        ]);
    }

    /**
     * @return void
     */
    public function testUserCreatesNewWeeklyTermLoan()
    {
        // Create weekly term loan for the second user.
        $inputs = $this->getInputs([
            'amount' => 1500,
            'term' => 5,
            'payment_period' => 'weekly',
        ]);
        $this->postJson(
            self::ROUTE,
            $inputs,
            ApiToken::bearerHeader(2)
        )->assertJson(
            fn (AssertableJson $json) => $json->has('loan')
                ->has('repayments')
        );

        $inputs['state'] = 'pending';
        $inputs['remained_principle'] = 1500;
        $this->assertDatabaseHas('loans', $inputs);

        // Assert database has 5 new scheduled repayments from 1 to 5 with due_date
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => 1,
            'amount' => 300.00,
            'due_date' => Carbon::now()->addDays(1)->addWeeks(1)->format('Y-m-d'),
            'state' => 'pending',
        ]);
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => 5,
            'amount' => 300.00,
            'due_date' => Carbon::now()->addDays(1)->addWeeks(5)->format('Y-m-d'),
            'state' => 'pending',
        ]);
    }

    /**
     * @return void
     */
    public function testUserCreatesNewLoanWithInvalidInputs()
    {
        // The token belongs to the first user but use
        // the second user for user_id
        $this->postJson(
            self::ROUTE,
            $this->getInputs(),
            ApiToken::bearerHeader(1)
        )->assertStatus(401)
            ->assertJsonPath('error', 'Unauthorized');

        // The payment_period is neither 'weekly' or 'monthly'
        $this->postJson(
            self::ROUTE,
            $this->getInputs(['payment_period' => 'yearly']),
            ApiToken::bearerHeader(2)
        )->assertStatus(422)
            ->assertJsonPath(
                'payment_period',
                'Payment period must be either weekly or monthly'
            );

        // The start_date format is wrong
        $this->postJson(self::ROUTE, $this->getInputs([
            'start_date' => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
        ]), ApiToken::bearerHeader(2))
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.start_date',
                ['The start date does not match the format Y-m-d.']
            );

        // The start_date is in the past
        $this->postJson(self::ROUTE, $this->getInputs([
            'start_date' => Carbon::now()->addDays(-1)->format('Y-m-d'),
        ]), ApiToken::bearerHeader(2))
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.start_date',
                ['The start date must be a date after or equal to today.']
            );

        // The user who owns the api token is not the same as input user_id
        $this->postJson(self::ROUTE, $this->getInputs([
            'user_id' => 3,
        ]), ApiToken::bearerHeader(2))
            ->assertStatus(401)
            ->assertJsonPath(
                'error',
                'Unauthorized'
            );

        // Admin users cannot create new loan for themself
        $this->postJson(self::ROUTE, $this->getInputs([
            'user_id' => 1,
        ]), ApiToken::bearerHeader(1))
            ->assertStatus(401)
            ->assertJsonPath(
                'forbidden',
                'Admins cannot create loan for themself.'
            );
    }

    /**
     * @return void
     */
    public function testAdminCanApproveLoan()
    {
        // Strange Laravel bug happened here when I tried to send requests: postJson('/api/loan')
        // to create new loan then patchJson('/api/loan/approve/$id') to approve loan.
        // The $request->user()->isAdmin() did not update to admin when I switched to admin user
        // to approve loan. That's why I had to use factory here.
        /**
         * @var Loan               $loan
         * @var ScheduledRepayment $firstRepayment
         * @var ScheduledRepayment $secondRepayment
         */
        [$loan, $firstRepayment, $secondRepayment] = $this->createLoanWithScheduledRepayments();

        $this->patchJson(
            '/api/loan/approve/' . $loan->id,
            [],
            ApiToken::bearerHeader(1)
        )->assertStatus(200)
            ->assertJsonPath('status', 'Approved');

        // The loan changed state to 'approved' and the repayments changed state to 'active'
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'state' => 'approved',
        ]);
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $firstRepayment->id,
            'state' => 'active',
        ]);
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $secondRepayment->id,
            'state' => 'active',
        ]);
    }

    /**
     * @return void
     */
    public function testUserCannotApproveLoan()
    {
        /** @var Loan */
        $loan = $this->createLoanWithScheduledRepayments()[0];

        // The second user with token bearerHeader(2) is not an admin so cannot approve
        $this->patchJson(
            '/api/loan/approve/' . $loan->id,
            [],
            ApiToken::bearerHeader(2)
        )->assertStatus(401)
            ->assertJsonPath('error', 'Unauthorized');
    }

    /**
     * @return void
     */
    public function testAdminCannotApproveLoanWithStateApproved()
    {
        /** @var Loan */
        $loan = $this->createLoanWithScheduledRepayments()[0];
        $loan->update([
            'state' => 'approved',
        ]);
        $this->patchJson(
            '/api/loan/approve/' . $loan->id,
            [],
            ApiToken::bearerHeader(1)
        )->assertStatus(422)
            ->assertJsonPath('loan', 'Already processed this loan');
    }

    /**
     * @return void
     */
    public function testUserCanViewAllTheirLoans()
    {
        // Create 3 loans for the 2nd user.
        $this->createLoanWithScheduledRepayments()[0];
        $this->createLoanWithScheduledRepayments()[0];
        $this->createLoanWithScheduledRepayments()[0];

        $this->getJson(self::ROUTE, ApiToken::bearerHeader(2))
            ->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has('loans', 3)
            );
    }

    /**
     * @return void
     */
    public function testUserCanViewTheirSingleLoan()
    {
        /** @var Loan */
        $loan = $this->createLoanWithScheduledRepayments()[0];
        $this->getJson("/api/loan/$loan->id", ApiToken::bearerHeader(2))
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('loan'));
    }

    /**
     * @return void
     */
    public function testAdminCanViewUserLoan()
    {
        /** @var Loan */
        $loan = $this->createLoanWithScheduledRepayments()[0];
        $this->getJson("/api/loan/$loan->id", ApiToken::bearerHeader(1))
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('loan'));
    }

    /**
     * @return void
     */
    public function testUserCannotViewOtherUserLoan()
    {
        /** @var Loan */
        $loan = $this->createLoanWithScheduledRepayments()[0];
        $this->getJson("/api/loan/$loan->id", ApiToken::bearerHeader(3))
            ->assertStatus(401)
            ->assertJsonPath('error', 'Unauthorized');
    }

    /**
     * @param array<string,int|string> $overRideInputs
     *
     * @return array<string,int|string>
     */
    public function getInputs($overRideInputs = [])
    {
        $inputs = [
            'user_id' => 2,
            'amount' => 10000,
            'term' => 3,
            'payment_period' => 'monthly',
            'start_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
        ];
        foreach ($overRideInputs as $key => $value) {
            $inputs[$key] = $value;
        }

        return $inputs;
    }

    /**
     * @return array{0:Loan,1:ScheduledRepayment,2:ScheduledRepayment}
     */
    public function createLoanWithScheduledRepayments()
    {
        /** @var Loan */
        $loan = Loan::factory()->create([
            'user_id' => 2,
            'term' => 2,
            'payment_period' => 'weekly',
            'state' => 'pending',
        ]);
        /** @var ScheduledRepayment */
        $firstRepayment = ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => round($loan->amount / $loan->term, 2),
            'due_date' => Carbon::createFromFormat('Y-m-d', $loan->start_date)
                ->addWeek()
                ->format('Y-m-d'),
        ]);
        /** @var ScheduledRepayment */
        $secondRepayment = ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => round($loan->amount / $loan->term, 2),
            'due_date' => Carbon::createFromFormat('Y-m-d', $loan->start_date)
                ->addWeeks(2)
                ->format('Y-m-d'),
        ]);

        return [
            $loan, $firstRepayment, $secondRepayment,
        ];
    }
}
