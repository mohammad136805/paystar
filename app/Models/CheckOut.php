<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckOut extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','ref_num','order_id' , 'payment_amount'];
}
