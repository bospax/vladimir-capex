<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApproverSet extends Model
{
    use HasFactory;

    protected $table = 'approver_set';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
