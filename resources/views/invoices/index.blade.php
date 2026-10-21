@extends('layouts.app')
@section('title', 'Invoices')
@section('content')
<div class="card card-b flex flex-wrap items-center justify-between gap-3">
  <form data-auto class="flex gap-2"><input name="q" value="{{ request('q') }}" placeholder="Invoice no, customer, mobile…" class="inp w-64" data-suggest="invoices" autocomplete="off" spellcheck="false">
    <select name="status" class="inp w-36"><option value="">All</option>@foreach(['unpaid','partial','paid','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst($s) }}</option>@endforeach</select><button class="btn btn-light">Filter</button></form>
  <a href="{{ route('invoices.create') }}" class="btn btn-primary">+ Manual Invoice</a>
</div>
<div class="card overflow-x-auto" data-live><table class="tbl"><thead><tr><th>Invoice</th><th>Date</th><th>Customer</th><th>Booking</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Balance</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($invoices as $i)<tr><td><a class="font-medium text-brand-700" href="{{ route('invoices.show', $i) }}">{{ $i->invoice_no }}</a></td><td>{{ dt($i->issue_date) }}</td><td>{{ $i->customer->name }}</td><td>{{ $i->booking?->booking_no ?? '—' }}</td>
<td class="text-right">{{ money($i->total) }}</td><td class="text-right">{{ money($i->paid) }}</td><td class="text-right">{{ money($i->balance) }}</td><td><x-badge :status="$i->status" /></td><td><a class="btn btn-light btn-sm" target="_blank" href="{{ route('invoices.pdf', $i) }}">PDF</a></td></tr>
@empty<tr><td colspan="9" class="text-center text-slate-500 py-8">No invoices.</td></tr>@endforelse</tbody></table><div class="card-b">{{ $invoices->links() }}</div></div>
@endsection
