<?php

namespace App\Models;

use App\Support\Num;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['paid_at' => 'date'];

    public const METHODS = ['cash' => 'Cash', 'bank' => 'Bank Transfer', 'raast' => 'Raast / IBFT', 'jazzcash' => 'JazzCash', 'easypaisa' => 'Easypaisa', 'cheque' => 'Cheque', 'other' => 'Other'];

    public function getIsRefundAttribute(): bool { return $this->kind === 'refund'; }

    public function booking() { return $this->belongsTo(Booking::class)->withTrashed(); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by'); }

    public static function newNumber(): string { return Num::next('RC', 'payments', 'receipt_no'); }

    protected static function booted(): void
    {
        static::saved(fn ($p) => $p->booking?->recalc());
        static::deleted(fn ($p) => $p->booking?->recalc());
    }
}
