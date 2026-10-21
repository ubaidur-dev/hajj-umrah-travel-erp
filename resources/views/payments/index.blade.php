@extends('layouts.app')
@section('title', 'Payments & Refunds')
@section('content')
<div class="card card-b flex flex-wrap items-end justify-between gap-3">
  <form data-auto class="flex flex-wrap gap-2 items-end"><input name="q" value="{{ request('q') }}" placeholder="Receipt, booking, name, mobile…" class="inp w-64" data-suggest="payments" autocomplete="off" spellcheck="false"><input type="date" name="from" value="{{ request('from') }}" class="inp w-40"><input type="date" name="to" value="{{ request('to') }}" class="inp w-40">
    <select name="method" class="inp w-40"><option value="">All methods</option>@foreach($methods as $k=>$v)<option value="{{ $k }}" @selected(request('method')==$k)>{{ $v }}</option>@endforeach</select><button class="btn btn-light">Filter</button></form>
  <div class="text-sm" data-live>Net received: <b class="text-lg text-brand-700">{{ money($total) }}</b></div>
</div>
<div class="card overflow-x-auto" data-live><table class="tbl"><thead><tr><th>Receipt</th><th>Date</th><th>Booking</th><th>Customer</th><th>Method</th><th>Ref</th><th class="text-right">Amount</th><th></th></tr></thead><tbody>
@forelse($payments as $p)<tr><td>{{ $p->receipt_no }}@if($p->is_refund) <span class="badge bg-red-100 text-red-700">Refund</span>@endif</td><td>{{ dt($p->paid_at) }}</td><td><a class="text-brand-700" href="{{ route('bookings.show', $p->booking) }}">{{ $p->booking->booking_no }}</a></td><td>{{ $p->booking->customer->name }}</td><td>{{ $methods[$p->method] ?? $p->method }}</td><td>{{ $p->reference }}</td><td class="text-right font-medium {{ $p->is_refund ? 'text-red-600' : '' }}">{{ money($p->amount) }}</td><td><a class="btn btn-light btn-sm" target="_blank" href="{{ route('payments.receipt', $p) }}">Receipt</a></td></tr>
@empty<tr><td colspan="8" class="text-center text-slate-500 py-8">No payments.</td></tr>@endforelse</tbody></table><div class="card-b">{{ $payments->links() }}</div></div>
@endsection
