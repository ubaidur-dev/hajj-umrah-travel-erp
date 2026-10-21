@extends('layouts.app')
@section('title', $package->name)
@section('content')
<div class="flex flex-wrap gap-2">
  <a href="{{ route('bookings.create', ['package_id' => $package->id]) }}" class="btn btn-primary">+ Book this package</a>
  <a href="{{ route('packages.edit', $package) }}" class="btn btn-light">Edit</a>
  <a href="{{ route('packages.manifest', $package) }}" class="btn btn-light">Passenger list</a>
  <form method="POST" action="{{ route('packages.destroy', $package) }}" data-confirm="Delete this package?">@csrf @method('DELETE')<button class="btn btn-light text-red-600">Delete</button></form>
</div>
<div class="grid lg:grid-cols-3 gap-5">
  <div class="lg:col-span-2 space-y-5">
    @if($package->getMedia('images')->count())
    <div class="card card-b grid grid-cols-2 sm:grid-cols-3 gap-3">
      @foreach($package->getMedia('images') as $m)<a href="{{ $m->getUrl() }}" target="_blank"><img src="{{ $m->getUrl('thumb') }}" class="rounded-lg w-full h-32 object-cover" alt=""></a>@endforeach
    </div>@endif
    <div class="card"><div class="card-h">Details</div><div class="card-b text-sm grid sm:grid-cols-2 gap-3">
      <div><span class="text-slate-500">Type:</span> {{ $types[$package->type] ?? $package->type }}</div>
      <div><span class="text-slate-500">Code:</span> {{ $package->code }}</div>
      <div><span class="text-slate-500">Duration:</span> {{ $package->duration_days ? $package->duration_days.' days' : '—' }}</div>
      <div><span class="text-slate-500">Airline:</span> {{ $package->airline ?: '—' }}</div>
      <div><span class="text-slate-500">Makkah / Madinah nights:</span> {{ $package->makkah_nights ?? '—' }} / {{ $package->madinah_nights ?? '—' }}</div>
      <div><span class="text-slate-500">Flight:</span> {{ trim($package->departure_city.' '.$package->flight_no) ?: '—' }}</div>
      <div><span class="text-slate-500">Transport:</span> {{ $package->transport ?: '—' }}</div>
      <div><span class="text-slate-500">Meals:</span> {{ $package->meals ?: '—' }}</div>
      <div><span class="text-slate-500">Departure:</span> {{ dt($package->departure_date) }}</div>
      <div><span class="text-slate-500">Return:</span> {{ dt($package->return_date) }}</div>
      <div><span class="text-slate-500">Makkah Hotel:</span> {{ $package->makkah_hotel ?: '—' }}</div>
      <div><span class="text-slate-500">Madinah Hotel:</span> {{ $package->madinah_hotel ?: '—' }}</div>
      @if($package->description)<p class="sm:col-span-2 whitespace-pre-line">{{ $package->description }}</p>@endif
      @if($package->inclusions)<div class="sm:col-span-2"><b>Inclusions</b><p class="whitespace-pre-line">{{ $package->inclusions }}</p></div>@endif
      @if($package->exclusions)<div class="sm:col-span-2"><b>Exclusions</b><p class="whitespace-pre-line">{{ $package->exclusions }}</p></div>@endif
    </div></div>
    <div class="card"><div class="card-h">Bookings ({{ $package->bookings->count() }})</div><table class="tbl"><thead><tr><th>No</th><th>Customer</th><th>Pax</th><th>Total</th><th>Status</th></tr></thead><tbody>
      @forelse($package->bookings as $b)<tr><td><a class="text-brand-700" href="{{ route('bookings.show', $b) }}">{{ $b->booking_no }}</a></td><td>{{ $b->customer->name }}</td><td>{{ $b->pax }}</td><td>{{ money($b->net_total) }}</td><td><x-badge :status="$b->status" /></td></tr>
      @empty<tr><td colspan="5" class="text-center text-slate-500 py-6">No bookings.</td></tr>@endforelse</tbody></table></div>
  </div>
  <div class="space-y-5">
    <div class="card"><div class="card-h">Pricing (per person)</div><dl class="card-b text-sm space-y-2">
      <div class="flex justify-between"><dt>Adult</dt><dd><b>{{ money($package->sale_price) }}</b></dd></div>
      <div class="flex justify-between"><dt>Child</dt><dd>{{ money($package->child_price) }}</dd></div>
      <div class="flex justify-between"><dt>Infant</dt><dd>{{ money($package->infant_price) }}</dd></div>
      <div class="flex justify-between border-t pt-2"><dt>Cost</dt><dd class="text-red-600"><b>{{ money($package->cost_price) }}</b></dd></div>
      <div class="flex justify-between"><dt>Profit / adult</dt><dd class="text-brand-700"><b>{{ money($package->margin) }}</b></dd></div>
    </dl></div>
    <div class="card"><div class="card-h">Cost Breakdown</div><table class="tbl"><tbody>
      @forelse($package->costs as $c)<tr><td>{{ $c->title }}</td><td class="text-right">{{ money($c->amount) }}</td></tr>
      @empty<tr><td class="text-slate-500 text-center py-4">No costs added.</td></tr>@endforelse</tbody></table></div>
    @if($package->total_seats)<div class="card card-b text-sm">Seats: <b>{{ $package->seats_booked }}</b> / {{ $package->total_seats }} (remaining {{ max(0,$package->total_seats-$package->seats_booked) }})</div>@endif
  </div>
</div>
@endsection
