<?php

namespace App\Http\Controllers;

use App\Models\AgencyProfile;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** One place for every upload in the system (all files go through Spatie Media Library). */
class MediaController extends Controller
{
    /** model key => [class, collection, allow pdf] */
    protected function map(): array
    {
        return [
            'package' => [Package::class, 'images', false],
            'customer' => [Customer::class, 'documents', true],
            'booking' => [Booking::class, 'documents', true],
            'user' => [User::class, 'avatar', false],
            'agency' => [AgencyProfile::class, 'logo', false],
        ];
    }

    public function store(Request $r): JsonResponse
    {
        $key = $r->validate(['model' => 'required|string'])['model'];
        abort_unless(isset($this->map()[$key]), 422);
        [$class, $collection, $pdf] = $this->map()[$key];

        $r->validate(['files' => 'required|array|max:20', 'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,webp,gif'.($pdf ? ',pdf,doc,docx' : '')]);

        $model = $key === 'agency' ? AgencyProfile::current() : $class::findOrFail($r->input('id'));
        $this->authorizeModel($r, $key, $model);

        $out = [];
        foreach ($r->file('files') as $file) {
            $m = $model->addMedia($file)->toMediaCollection($collection);
            $out[] = $this->present($m);
        }

        return response()->json(['ok' => true, 'items' => $out]);
    }

    public function destroy(Request $r, Media $media): JsonResponse
    {
        $model = $media->model;
        $key = collect($this->map())->search(fn ($v) => $v[0] === $media->model_type);
        abort_unless($key && $model instanceof HasMedia, 404);
        $this->authorizeModel($r, $key, $model);
        $media->delete();

        return response()->json(['ok' => true]);
    }

    protected function authorizeModel(Request $r, string $key, $model): void
    {
        $user = $r->user();
        if ($key === 'user') {
            abort_unless($model->id === $user->id, 403);
        }
        if ($key === 'agency') {
            abort_unless($user->isAdmin(), 403);
        }
    }

    public static function present(Media $m): array
    {
        return [
            'id' => $m->id, 'name' => $m->file_name, 'size' => $m->human_readable_size, 'url' => $m->getUrl(),
            'thumb' => str_starts_with($m->mime_type, 'image/') ? $m->getUrl() : null,
            'is_image' => str_starts_with($m->mime_type, 'image/'),
            'delete' => route('media.destroy', $m->id),
        ];
    }
}
