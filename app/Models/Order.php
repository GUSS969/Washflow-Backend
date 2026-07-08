<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'invoice',
        'customer_id',
        'user_id',
        'status',
        'payment_status',
        'service_type',
        'perfume_type',
        'cloth_notes',
        'estimated_ready',
        'delivery_type',
        'notes',
        'total_price',
        'total_paid',
    ];

    protected $casts = [
        'estimated_ready' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}