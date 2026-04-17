<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OneCharging extends Model
{
    protected $connection = 'remote_mysql';
    protected $table = 'one_chargings';

    protected $fillable = [
        'name',
        'code',
    ];
}
