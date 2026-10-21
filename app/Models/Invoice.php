<?php

namespace App\Models;

use App\Support\Num;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['issue_date' => 'date', 'due_date' => 'date'];

    public function customer() { return $this->belongsTo(Customer::class)->withTrashed(); }
    public function booking() { return $this->belongsTo(Booking::class)->withTrashed(); }
    public function items() { return $this->hasMany(InvoiceItem::class); }

    public function getBalanceAttribute(): float { return max(0, (float) $this->total - (float) $this->paid); }

    public static function newNumber(): string { return Num::next('INV', 'invoices', 'invoice_no'); }

    public function recalc(): static
    {
        $sub = $this->items()->get()->sum(fn ($i) => $i->quantity * $i->unit_price);
        $this->subtotal = $sub;
        $this->tax = round(max(0, $sub - $this->discount) * $this->tax_rate / 100, 2);
        $this->total = max(0, $sub - $this->discount) + $this->tax;
        $this->save();

        return $this->syncPaid();
    }

    /** Paid amount follows the booking's payments (capped at invoice total). */
    public function syncPaid(): static
    {
        if ($this->status === 'cancelled') {
            return $this;
        }
        $paid = $this->booking ? min((float) $this->booking->paid_total, (float) $this->total) : (float) $this->paid;
        $this->paid = $paid;
        $this->status = $paid <= 0 ? 'unpaid' : ($paid >= $this->total ? 'paid' : 'partial');
        $this->save();

        return $this;
    }
}
