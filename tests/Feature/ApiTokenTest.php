<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = true;

    private const ROUTE = '/api/token/create';

    /**
     * @return void
     */
    public function testGetApiToken()
    {
        $response = $this->postJson(self::ROUTE, [
            'email' => User::first()->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('token')
        );

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    /**
     * @return void
     */
    public function testInputValidationFails()
    {
        // Body does not contain email.
        $this->postJson(self::ROUTE, [
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonPath('errors.email', [
                'The email field is required.',
            ]);

        // Email is invalid
        $this->postJson(self::ROUTE, [
            'email' => 'john.doe',
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonPath('errors.email', [
                'The email must be a valid email address.',
            ]);

        // Password is missing
        $this->postJson(self::ROUTE, [
            'email' => 'john.doe@gmail.com',
        ])->assertStatus(422)
            ->assertJsonPath('errors.password', [
                'The password field is required.',
            ]);

        // Password is less than 8 characters.
        $this->postJson(self::ROUTE, [
            'email' => 'john.doe@gmail.com',
            'password' => 'passwor',
        ])->assertStatus(422)
            ->assertJsonPath('errors.password', [
                'The password must be at least 8 characters.',
            ]);

        // Email is incorrect.
        $this->postJson(self::ROUTE, [
            'email' => 'john.doe111@gmail.com',
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonPath(
                'authentication',
                'The provided credentials are incorrect.',
            );

        // Password is incorrect
        $this->postJson(self::ROUTE, [
            'email' => User::first()->email,
            'password' => 'password1',
        ])->assertStatus(422)
            ->assertJsonPath(
                'authentication',
                'The provided credentials are incorrect.',
            );
    }
}
