<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    protected $guarded = [];

    public function booking() { return $this->belongsTo(Booking::class); }

    public function getTotalAttribute(): float { return $this->quantity * $this->unit_price; }
    public function getCostTotalAttribute(): float { return $this->quantity * $this->unit_cost; }
}
