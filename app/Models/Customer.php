<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Customer extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
    }


    protected $guarded = [];

    public function bookings() { return $this->hasMany(Booking::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }

    /** Total still owed by this customer across live bookings. */
    public function getBalanceAttribute(): float
    {
        return (float) $this->bookings()->where('status', '!=', 'cancelled')->get()->sum(fn ($b) => $b->due);
    }
}
