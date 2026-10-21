<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['expected_travel' => 'date', 'next_follow_up' => 'date'];

    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'follow_up' => 'Follow up', 'won' => 'Won', 'lost' => 'Lost'];

    public function customer() { return $this->belongsTo(Customer::class); }

    public function getIsDueAttribute(): bool
    {
        return $this->next_follow_up && $this->next_follow_up->lte(today()) && ! in_array($this->status, ['won', 'lost']);
    }
}
