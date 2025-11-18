<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provice extends Model
{
    protected $table = 'provices';

    protected $fillable = [
        'name',
        'code',
        'description',
        'distand_from_city',
        'status',
    ];
}
