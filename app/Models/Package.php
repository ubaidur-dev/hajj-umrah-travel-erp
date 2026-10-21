<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Package extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $guarded = [];

    protected $casts = ['departure_date' => 'date', 'return_date' => 'date', 'is_active' => 'boolean'];

    public function costs() { return $this->hasMany(PackageCost::class); }
    public function bookings() { return $this->hasMany(Booking::class); }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(300)->nonQueued();
    }

    public function getMarginAttribute(): float
    {
        return (float) $this->sale_price - (float) $this->cost_price;
    }

    public function getSeatsBookedAttribute(): int
    {
        return (int) $this->bookings()->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->selectRaw('COALESCE(SUM(adults + children),0) as s')->value('s');
    }

    public function recalcCost(): void
    {
        $this->update(['cost_price' => $this->costs()->sum('amount')]);
    }
}
