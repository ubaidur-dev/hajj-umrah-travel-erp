@extends('layouts.app')
@section('title', 'Bookings / Orders')
@section('content')
<div class="card card-b flex flex-wrap items-end justify-between gap-3">
  <form data-auto class="flex flex-wrap gap-2 items-end">
    <input name="q" value="{{ request('q') }}" placeholder="Booking no, name, mobile, passport…" class="inp w-72" data-suggest="bookings" autocomplete="off" spellcheck="false">
    <select name="service" class="inp w-36"><option value="">All services</option>@foreach(\App\Http\Controllers\PackageController::TYPES as $k=>$v)<option value="{{ $k }}" @selected(request('service')==$k)>{{ $v }}</option>@endforeach</select>
    <select name="status" class="inp w-36"><option value="">All statuses</option>@foreach(\App\Models\Booking::STATUSES as $k=>$v)<option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>@endforeach</select>
    <input type="date" name="from" value="{{ request('from') }}" class="inp w-40"><input type="date" name="to" value="{{ request('to') }}" class="inp w-40">
    <label class="text-sm flex items-center gap-1 pb-2"><input type="checkbox" name="due" value="1" @checked(request('due'))> Outstanding only</label>
    <button class="btn btn-light">Filter</button>
  </form>
  <a href="{{ route('bookings.create') }}" class="btn btn-primary">+ New Booking</a>
</div>
<div class="card overflow-x-auto" data-live><table class="tbl">
  <thead><tr><th>No</th><th>Booked</th><th>Customer</th><th>Package</th><th>Departure</th><th>Pax</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Due</th><th class="text-right">Profit</th><th>Status</th></tr></thead>
  <tbody>@forelse($bookings as $b)
    <tr><td class="whitespace-nowrap"><a class="font-medium text-brand-700" href="{{ route('bookings.show', $b) }}">{{ $b->booking_no }}</a></td><td class="whitespace-nowrap">{{ dt($b->booking_date) }}</td><td>{{ $b->customer->name }}</td><td>{{ $b->title }}<br><span class="text-xs text-slate-500">{{ \App\Http\Controllers\PackageController::TYPES[$b->service_type] ?? $b->service_type }}</span></td><td class="whitespace-nowrap">{{ dt($b->travel_date) }}</td><td>{{ $b->pax }}</td>
    <td class="text-right whitespace-nowrap">{{ money($b->net_total) }}</td><td class="text-right whitespace-nowrap text-brand-700">{{ money($b->paid_total) }}</td><td class="text-right {{ $b->due > 0 ? 'text-red-600' : '' }}">{{ money($b->due) }}</td>
    <td class="text-right whitespace-nowrap">{{ money($b->profit) }}</td><td><x-badge :status="$b->status" />@if($b->is_overdue)<br><span class="text-xs text-red-600 font-medium">Overdue</span>@endif</td></tr>
  @empty<tr><td colspan="11" class="text-center text-slate-500 py-8">No bookings found.</td></tr>@endforelse</tbody></table>
  <div class="card-b">{{ $bookings->links() }}</div></div>
@endsection
