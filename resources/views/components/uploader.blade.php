{{--
  Industry-style uploader (drag & drop, previews, progress, delete). Files are stored with Spatie Media Library.
  Instant mode  : give model + id  -> files upload as soon as they are chosen.
  Deferred mode : no id            -> files travel with the surrounding form (input name = $name).
--}}
@props(['model' => null, 'id' => null, 'name' => 'files', 'media' => collect(), 'accept' => 'image/*', 'multiple' => true, 'label' => 'Drag & drop files here, or click to browse', 'hint' => null, 'reload' => false, 'shape' => 'square'])
@php
  $instant = (bool) $id || $model === 'agency';
  $items = collect($media)->map(fn ($m) => \App\Http\Controllers\MediaController::present($m))->values();
@endphp
<div data-uploader data-mode="{{ $instant ? 'instant' : 'deferred' }}" data-model="{{ $model }}" data-id="{{ $id }}"
     data-url="{{ route('media.store') }}" data-multiple="{{ $multiple ? 1 : 0 }}" data-reload="{{ $reload ? 1 : 0 }}" data-shape="{{ $shape }}" class="space-y-3">
  <div data-tiles class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3 empty:hidden"></div>
  <label data-drop class="flex flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 hover:bg-brand-50 hover:border-brand-500 transition cursor-pointer px-4 py-7 text-center">
    <svg class="w-9 h-9 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
    <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
    <span class="text-xs text-slate-500">{{ $hint ?? ($accept === 'image/*' ? 'JPG, PNG or WebP — up to 10 MB each' : 'Images, PDF or Word — up to 10 MB each') }}</span>
    <input type="file" class="sr-only" accept="{{ $accept }}" @if($multiple) multiple @endif @unless($instant) name="{{ $name }}{{ $multiple ? '[]' : '' }}" @endunless>
  </label>
  <script type="application/json" data-initial>@json($items)</script>
</div>
