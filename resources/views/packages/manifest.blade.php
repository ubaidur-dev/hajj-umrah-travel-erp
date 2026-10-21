@extends('layouts.app')
@section('title', 'Passenger Manifest')
@section('content')
<div class="flex flex-wrap items-center gap-2 print:hidden">
  <button onclick="window.print()" class="btn btn-primary">Print</button>
  <a class="btn btn-light" href="{{ route('packages.manifest', [$package, 'csv' => 1]) }}">Download for Excel (CSV)</a>
  <a class="btn btn-light" href="{{ route('packages.show', $package) }}">Back to package</a>
</div>
<div class="card">
  <div class="card-h"><div>{{ $package->name }}<div class="card-sub">{{ $package->code }} · Departure {{ dt($package->departure_date) }} · Return {{ dt($package->return_date) }} · {{ $package->airline ?: 'Airline not set' }} {{ $package->flight_no }}</div></div><div class="text-sm">{{ $travelers->count() }} passengers</div></div>
  <div class="overflow-x-auto"><table class="tbl">
    <thead><tr><th>#</th><th>Name</th><th>Type</th><th>Passport</th><th>Expiry</th><th>DOB</th><th>Visa</th><th>Ticket</th><th>Booking</th><th>Contact</th></tr></thead>
    <tbody>
    @forelse($travelers as $i => $t)
      @php $bad = $t->passport_expiry && $package->departure_date && $t->passport_expiry->lt($package->departure_date->copy()->addMonths(6)); @endphp
      <tr><td>{{ $i+1 }}</td><td class="font-medium">{{ $t->name }}</td><td>{{ ucfirst($t->type) }}</td><td>{{ $t->passport_no ?: '—' }}</td>
        <td class="{{ $bad ? 'text-red-600 font-semibold' : '' }}">{{ dt($t->passport_expiry) }}@if($bad) <span title="Expires within 6 months of travel">⚠</span>@endif</td>
        <td>{{ dt($t->dob) }}</td><td>{{ \App\Models\Traveler::VISA[$t->visa_status] ?? $t->visa_status }}</td><td>{{ $t->ticket_no ?: '—' }}</td>
        <td>{{ $t->booking->booking_no }}</td><td>{{ $t->booking->customer->name }}<br><span class="text-xs text-slate-500">{{ $t->booking->customer->phone }}</span></td></tr>
    @empty<tr><td colspan="10" class="text-center text-slate-500 py-8">No travelers added yet. Add them inside each booking.</td></tr>@endforelse
    </tbody></table></div>
</div>
<p class="text-xs text-slate-500 print:hidden">⚠ Passport valid for less than 6 months after departure — most embassies will reject it.</p>
@endsection
