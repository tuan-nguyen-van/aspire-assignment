<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Indicates whether the default seeder should run before each test.
     */
    protected bool $seed = true;

    /**
     * @return void
     */
    public function testUserIsAdmin()
    {
        $this->assertTrue(User::find(1)->isAdmin());
        $this->assertFalse(User::find(2)->isAdmin());
    }
}
