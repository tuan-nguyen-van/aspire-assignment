<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Role;
use App\Models\User;
use App\Models\UserHasRoles;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        /** @phpstan-ignore-next-line */
        $count = (int) env('DB_SEED_COUNT');

        /** 
         * @var \Illuminate\Database\Eloquent\Collection<int,User>
         */
        $users = User::factory()->count($count)
            ->create();

        /** 
         * @var Role
         */
        $userRole = Role::factory()->create([
            'name' => 'user',
        ]);

        /** 
         * @var Role
         */
        $adminRole = Role::factory()->create([
            'name' => 'admin',
        ]);

        // The first user has role 'admin',
        UserHasRoles::factory()->create([
            'user_id' => $users[0]->id,
            'role_id' => $adminRole->id,
        ]);

        for ($i = 1; $i < $count; ++$i) {
            // The rest of the users have role 'user'
            UserHasRoles::factory()->create([
                'user_id' => $users[$i]->id,
                'role_id' => $userRole->id,
            ]);
        }
    }
}
