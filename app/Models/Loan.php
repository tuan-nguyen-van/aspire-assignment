<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    /**
     * The attributes that are guarded.
     *
     * @var array<int,string>
     */
    protected $guarded = [];

    /**
     * @var string[]
     */
    public const PAYMENT_PERIOD = ['weekly', 'monthly'];

    /**
     * @var string[]
     */
    public const STATE = ['approved', 'pending', 'paid'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function scheduledRepayments()
    {
        return $this->hasMany(ScheduledRepayment::class);
    }
}
