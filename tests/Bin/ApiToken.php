<?php

namespace Tests\Bin;

use App\Models\User;

class ApiToken
{
    /**
     * @return array<"Authorization",string>
     */
    public static function bearerHeader(int $userId)
    {
        $token = User::find($userId)->createToken('authenticate')->plainTextToken;

        return ['Authorization' => "Bearer $token"];
    }
}
