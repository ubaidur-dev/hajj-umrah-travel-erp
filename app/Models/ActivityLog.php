<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $guarded = [];

    public function user() { return $this->belongsTo(User::class); }

    public static function record(string $action, string $description, ?Model $subject = null): void
    {
        static::create([
            'user_id' => auth()->id(), 'action' => $action, 'description' => $description,
            'subject_type' => $subject ? class_basename($subject) : null, 'subject_id' => $subject?->getKey(),
        ]);
    }
}
