<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledRepayment extends Model
{
    use HasFactory;

    /**
     * The attributes that are guarded.
     *
     * @var array<int,string>
     */
    protected $guarded = [];
}
