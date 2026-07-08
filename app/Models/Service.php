<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['service_name', 'price', 'unit'];

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
}
