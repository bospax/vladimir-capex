<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approvers extends Model
{
    protected $connection = 'remote_mysql';

    protected $table = 'approvers';

    protected $guarded = [];

	public function user()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
