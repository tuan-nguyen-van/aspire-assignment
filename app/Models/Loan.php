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

    public const WEEKLY = 'weekly';

    public const MONTHLY = 'monthly';

    /**
     * Could be something more like biweekly, triweekly.
     *
     * @var string[]
     */
    public const PAYMENT_PERIOD = [self::WEEKLY, self::MONTHLY];

    public const APPROVED = 'approved';

    public const PENDING = 'pending';

    public const PAID = 'paid';

    /**
     * @var string[]
     */
    public const STATE = [self::APPROVED, self::PENDING, self::PAID];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function scheduledRepayments()
    {
        return $this->hasMany(ScheduledRepayment::class);
    }
}
