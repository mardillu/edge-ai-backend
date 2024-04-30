<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UssdStep extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ussd_steps';

    protected $fillable = ['msisdn', 'step_zero', 'step_one', 'step_two', 'page', 'completed', 'source'];
}