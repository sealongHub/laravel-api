<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class userRole extends Model
{
    protected $table="roles";
    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'testing',
    ];
}