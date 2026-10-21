<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Traveler extends Model
{
    protected $guarded = [];

    protected $casts = ['passport_expiry' => 'date', 'dob' => 'date'];

    public const VISA = ['not_applied' => 'Not applied', 'applied' => 'Applied', 'approved' => 'Approved', 'issued' => 'Issued', 'rejected' => 'Rejected'];

    public function booking() { return $this->belongsTo(Booking::class); }
}
