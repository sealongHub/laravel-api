<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class memberships extends Model
{
    protected $table = "memberships";
    protected $fillable = [
        "membership_name",
        "description",
        "discount_rate",
        "monthly_fee",
        "min_spent",
        "max_spent",
        "reward_points",
        "status",
    ];
}
