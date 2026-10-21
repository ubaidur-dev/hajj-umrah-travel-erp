@extends('layouts.app')
@section('title', 'Packages')
@section('content')
<div class="card card-b flex flex-wrap items-center justify-between gap-3">
  <form data-auto class="flex flex-wrap gap-2"><input name="q" value="{{ request('q') }}" placeholder="Search package, code, hotel, airline…" class="inp w-72" data-suggest="packages" autocomplete="off" spellcheck="false">
    <select name="type" class="inp w-40"><option value="">All types</option>@foreach($types as $k=>$v)<option value="{{ $k }}" @selected(request('type')==$k)>{{ $v }}</option>@endforeach</select>
    <button class="btn btn-light">Filter</button></form>
  <a href="{{ route('packages.create') }}" class="btn btn-primary">+ Add Package</a>
</div>
<div data-live>
<div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
@forelse($packages as $p)
  <a href="{{ route('packages.show', $p) }}" class="card overflow-hidden hover:shadow-md transition block">
    <div class="h-40 bg-brand-100 flex items-center justify-center">
      @if($img = $p->getFirstMediaUrl('images', 'thumb'))<img src="{{ $img }}" class="w-full h-full object-cover" alt="">@else<span class="text-brand-700 text-4xl">🕋</span>@endif
    </div>
    <div class="card-b space-y-1">
      <div class="flex justify-between items-start"><h3 class="font-semibold">{{ $p->name }}</h3>@unless($p->is_active)<span class="badge bg-slate-200">Inactive</span>@endunless</div>
      <div class="text-xs text-slate-500">{{ $types[$p->type] ?? $p->type }} · {{ $p->code }}@if($p->duration_days) · {{ $p->duration_days }} days @endif @if($p->departure_date) · {{ dt($p->departure_date) }} @endif</div>
      <div class="flex justify-between pt-2 text-sm"><span>Sale <b>{{ money($p->sale_price) }}</b></span><span class="text-brand-700">Margin <b>{{ money($p->margin) }}</b></span></div>
      <div class="text-xs text-slate-500">{{ $p->sold }} bookings @if($p->total_seats)· {{ $p->seats_booked }}/{{ $p->total_seats }} seats @endif</div>
    </div>
  </a>
@empty <div class="card card-b col-span-full text-center text-slate-500">No packages found. Use "+ Add Package" to create one.</div> @endforelse
</div>
<div class="mt-4">{{ $packages->links() }}</div>
</div>
@endsection
