<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/** One-row model whose only job is to own the agency logo in Spatie Media Library. */
class AgencyProfile extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];

    public static function current(): static
    {
        return static::query()->first() ?? static::create();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }
}
