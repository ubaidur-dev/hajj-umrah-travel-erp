@extends('layouts.app')
@section('title', $invoice->invoice_no)
@section('content')
@php $phone = $invoice->customer->whatsapp ?: $invoice->customer->phone;
 $waText = "Hello {$invoice->customer->name}, here is invoice {$invoice->invoice_no} — Total ".money($invoice->total).", Balance ".money($invoice->balance).". PDF: ".route('invoices.pdf', $invoice); @endphp
<div class="flex flex-wrap gap-2">
  <a class="btn btn-primary" target="_blank" href="{{ route('invoices.pdf', $invoice) }}">View PDF</a>
  <a class="btn btn-light" href="{{ route('invoices.pdf', $invoice) }}?download=1">Download</a>
  @if($w = wa_link($phone, $waText))<x-whatsapp :href="$w" label="Send on WhatsApp" title="Send this invoice to the customer on WhatsApp" />@endif
  @if($invoice->booking)<a class="btn btn-light" href="{{ route('bookings.show', $invoice->booking) }}">Booking {{ $invoice->booking->booking_no }}</a>@endif
  <a class="btn btn-light" href="{{ route('invoices.edit', $invoice) }}">Edit</a>
  @if($invoice->status !== 'cancelled')<form method="POST" action="{{ route('invoices.cancel', $invoice) }}" data-confirm="Cancel this invoice?">@csrf<button class="btn btn-light text-red-600">Cancel</button></form>@endif
  <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" data-confirm="Delete this?">@csrf @method('DELETE')<button class="btn btn-light text-red-600">Delete</button></form>
</div>
<div class="card max-w-4xl"><div class="card-b space-y-4">
  <div class="flex justify-between"><div><div class="text-2xl font-bold">{{ $invoice->invoice_no }}</div><x-badge :status="$invoice->status" /></div>
    <div class="text-sm text-right">Date: {{ dt($invoice->issue_date) }}<br>Due: {{ dt($invoice->due_date) }}</div></div>
  <div class="text-sm"><b>{{ $invoice->customer->name }}</b><br>{{ $invoice->customer->phone }}<br>{{ $invoice->customer->address }}</div>
  <table class="tbl"><thead><tr><th>Description</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Amount</th></tr></thead><tbody>
  @foreach($invoice->items as $i)<tr><td>{{ $i->description }}</td><td class="text-right">{{ rtrim(rtrim($i->quantity,'0'),'.') }}</td><td class="text-right">{{ money($i->unit_price) }}</td><td class="text-right">{{ money($i->quantity*$i->unit_price) }}</td></tr>@endforeach</tbody></table>
  <div class="ml-auto w-64 text-sm space-y-1">
    <div class="flex justify-between"><span>Subtotal</span><span>{{ money($invoice->subtotal) }}</span></div>
    @if($invoice->discount > 0)<div class="flex justify-between"><span>Discount</span><span>- {{ money($invoice->discount) }}</span></div>@endif
    @if($invoice->tax > 0)<div class="flex justify-between"><span>Tax ({{ $invoice->tax_rate+0 }}%)</span><span>{{ money($invoice->tax) }}</span></div>@endif
    <div class="flex justify-between font-bold text-base border-t pt-1"><span>Total</span><span>{{ money($invoice->total) }}</span></div>
    <div class="flex justify-between text-brand-700"><span>Paid</span><span>{{ money($invoice->paid) }}</span></div>
    <div class="flex justify-between text-red-600 font-semibold"><span>Balance</span><span>{{ money($invoice->balance) }}</span></div>
  </div>
</div></div>
@endsection
