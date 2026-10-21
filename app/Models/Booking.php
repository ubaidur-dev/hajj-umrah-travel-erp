<?php

namespace App\Models;

use App\Support\Num;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Booking extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
    }


    protected $guarded = [];

    protected $casts = [
        'booking_date' => 'date', 'travel_date' => 'date', 'return_date' => 'date', 'payment_due_date' => 'date', 'confirmed_at' => 'datetime',
    ];

    public const STATUSES = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

    public function customer() { return $this->belongsTo(Customer::class)->withTrashed(); }
    public function package() { return $this->belongsTo(Package::class)->withTrashed(); }
    public function items() { return $this->hasMany(BookingItem::class); }
    public function travelers() { return $this->hasMany(Traveler::class); }
    public function payments() { return $this->hasMany(Payment::class)->orderBy('paid_at'); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function expenses() { return $this->hasMany(Expense::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function getIsOverdueAttribute(): bool
    {
        return $this->payment_due_date && $this->payment_due_date->lt(today()) && $this->due > 0 && in_array($this->status, ['pending', 'confirmed']);
    }

    public function getProfitAttribute(): float { return (float) $this->net_total - (float) $this->cost_total; }
    public function getDueAttribute(): float { return max(0, (float) $this->net_total - (float) $this->paid_total); }
    public function getPaxAttribute(): int { return $this->adults + $this->children + $this->infants; }
    public function getIsCountedAttribute(): bool { return in_array($this->status, ['confirmed', 'completed']); }

    /** Recompute all money totals from items + payments. Call after any change. */
    public function recalc(): static
    {
        $items = $this->items()->get();
        $sale = $items->sum(fn ($i) => $i->quantity * $i->unit_price);
        $cost = $items->sum(fn ($i) => $i->quantity * $i->unit_cost);
        $this->sale_total = $sale;
        $this->cost_total = $cost;
        $this->net_total = max(0, $sale - $this->discount);
        $this->paid_total = $this->payments()->sum('amount');
        $this->save();

        foreach ($this->invoices()->where('status', '!=', 'cancelled')->get() as $inv) {
            $inv->syncPaid();
        }

        return $this;
    }

    /** Replace items with lines generated from the package prices. */
    public function fillItemsFromPackage(): void
    {
        $p = $this->package;
        if (! $p) {
            return;
        }
        $this->items()->delete();
        $unitCost = (float) $p->cost_price;
        $rows = [
            ['Adult', $this->adults, $p->sale_price, $unitCost],
            ['Child', $this->children, $p->child_price, $unitCost],
            ['Infant', $this->infants, $p->infant_price, 0],
        ];
        foreach ($rows as [$label, $qty, $price, $cost]) {
            if ($qty > 0) {
                $this->items()->create([
                    'description' => "{$p->name} – $label",
                    'quantity' => $qty, 'unit_price' => $price, 'unit_cost' => $cost,
                ]);
            }
        }
    }

    public static function newNumber(): string { return Num::next('BK', 'bookings', 'booking_no'); }
}
