@extends('layouts.app')
@section('title', 'Reports & Profit')
@section('content')
<form class="card card-b flex flex-wrap items-end gap-3">
  <div><label class="lbl">From</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="inp"></div>
  <div><label class="lbl">To</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="inp"></div>
  <button class="btn btn-primary">Show</button>
  @foreach(['This month'=>[now()->startOfMonth(),now()->endOfMonth()],'This year'=>[now()->startOfYear(),now()->endOfYear()],'Last month'=>[now()->subMonth()->startOfMonth(),now()->subMonth()->endOfMonth()]] as $l=>[$a,$b])
    <a class="btn btn-light" href="?from={{ $a->toDateString() }}&to={{ $b->toDateString() }}">{{ $l }}</a>@endforeach
  <button type="button" onclick="window.print()" class="btn btn-light ml-auto">Print</button>
</form>
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
  <x-stat label="Sales" :value="money($sales)" :sub="$bookings->count().' bookings'" />
  <x-stat label="Package Cost" :value="money($cost)" tone="red" />
  <x-stat label="Gross Profit" :value="money($gross)" tone="green" />
  <x-stat label="Office Expenses" :value="money($opex)" tone="red" />
  <x-stat label="NET PROFIT / Savings" :value="money($net)" tone="gold" />
  <x-stat label="Cash Received" :value="money($received)" tone="blue" />
  <x-stat label="Receivable (outstanding)" :value="money($receivable)" tone="red" />
</div>
<div class="grid lg:grid-cols-2 gap-5">
  <div class="card"><div class="card-h">Package-wise Profit</div><div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Package</th><th>Bookings</th><th>Pax</th><th class="text-right">Sales</th><th class="text-right">Cost</th><th class="text-right">Profit</th></tr></thead><tbody>
    @forelse($byPackage as $r)<tr><td>{{ $r['name'] }}</td><td>{{ $r['count'] }}</td><td>{{ $r['pax'] }}</td><td class="text-right">{{ money($r['sales']) }}</td><td class="text-right">{{ money($r['cost']) }}</td><td class="text-right font-semibold text-brand-700">{{ money($r['profit']) }}</td></tr>
    @empty<tr><td colspan="6" class="text-center text-slate-500 py-6">No confirmed bookings in this period.</td></tr>@endforelse</tbody></table></div></div>
  <div class="space-y-5">
    <div class="card"><div class="card-h">Expenses by category</div><table class="tbl"><tbody>
      @forelse($expenseByCat as $c=>$t)<tr><td>{{ \App\Models\Expense::CATEGORIES[$c] ?? ucfirst($c) }}</td><td class="text-right">{{ money($t) }}</td></tr>@empty<tr><td class="text-center text-slate-500 py-4">No expenses.</td></tr>@endforelse</tbody></table></div>
  </div>
</div>
<div class="card"><div class="card-h"><div>Daily cash book<div class="card-sub">Money in (customer payments minus refunds) and money out (all expenses), day by day.</div></div></div><div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Date</th><th class="text-right">Cash in</th><th class="text-right">Cash out</th><th class="text-right">Day balance</th><th class="text-right">Running total</th></tr></thead><tbody>
  @php $run = 0; @endphp
  @forelse($cashBook as $r)@php $run += $r['in'] - $r['out']; @endphp<tr><td>{{ dt($r['date']) }}</td><td class="text-right text-brand-700">{{ money($r['in']) }}</td><td class="text-right text-red-600">{{ money($r['out']) }}</td><td class="text-right">{{ money($r['in'] - $r['out']) }}</td><td class="text-right font-medium">{{ money($run) }}</td></tr>
  @empty<tr><td colspan="5" class="text-center text-slate-500 py-6">No money moved in this period.</td></tr>@endforelse</tbody></table></div></div>
<div class="card"><div class="card-h">Booking-wise detail</div><div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Date</th><th>No</th><th>Customer</th><th>Title</th><th class="text-right">Sale</th><th class="text-right">Cost</th><th class="text-right">Profit</th><th class="text-right">Paid</th><th class="text-right">Due</th></tr></thead><tbody>
  @foreach($bookings as $b)<tr><td>{{ dt($b->booking_date) }}</td><td><a class="text-brand-700" href="{{ route('bookings.show', $b) }}">{{ $b->booking_no }}</a></td><td>{{ $b->customer->name }}</td><td>{{ $b->title }}</td><td class="text-right">{{ money($b->net_total) }}</td><td class="text-right">{{ money($b->cost_total) }}</td><td class="text-right text-brand-700">{{ money($b->profit) }}</td><td class="text-right">{{ money($b->paid_total) }}</td><td class="text-right text-red-600">{{ money($b->due) }}</td></tr>@endforeach
  </tbody></table></div></div>
@endsection
