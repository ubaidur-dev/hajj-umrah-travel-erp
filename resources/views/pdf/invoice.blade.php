<!doctype html><html><head><meta charset="utf-8"><title>{{ $invoice->invoice_no }}</title></head><body>
@include('pdf._head')
<table style="margin-top:14px"><tr><td style="border:0;padding:0"><div class="title" style="margin-top:0">INVOICE</div>No: <b>{{ $invoice->invoice_no }}</b><br>Date: {{ dt($invoice->issue_date) }}<br>Due: {{ dt($invoice->due_date) }}</td>
<td style="border:0;padding:0;text-align:right"><span class="tag {{ $invoice->status==='paid' ? 'ok' : ($invoice->status==='cancelled' || $invoice->status==='unpaid' ? 'bad' : 'warn') }}">{{ strtoupper($invoice->status) }}</span></td></tr></table>
<div class="box" style="margin-top:10px"><b>Bill To</b><br>{{ $invoice->customer->name }}<br>{{ $invoice->customer->phone }}<br>{{ $invoice->customer->address }}@if($invoice->booking)<br>Booking: {{ $invoice->booking->booking_no }} — {{ $invoice->booking->title }}@endif</div>
<table style="margin-top:12px"><thead><tr><th>Description</th><th class="r">Qty</th><th class="r">Price</th><th class="r">Amount</th></tr></thead><tbody>
@foreach($invoice->items as $i)<tr><td>{{ $i->description }}</td><td class="r">{{ rtrim(rtrim($i->quantity,'0'),'.') }}</td><td class="r">{{ money($i->unit_price) }}</td><td class="r">{{ money($i->quantity*$i->unit_price) }}</td></tr>@endforeach
<tr><td colspan="3" class="r">Subtotal</td><td class="r">{{ money($invoice->subtotal) }}</td></tr>
@if($invoice->discount>0)<tr><td colspan="3" class="r">Discount</td><td class="r">- {{ money($invoice->discount) }}</td></tr>@endif
@if($invoice->tax>0)<tr><td colspan="3" class="r">Tax ({{ $invoice->tax_rate+0 }}%)</td><td class="r">{{ money($invoice->tax) }}</td></tr>@endif
<tr><td colspan="3" class="r b">Total</td><td class="r b">{{ money($invoice->total) }}</td></tr>
<tr><td colspan="3" class="r">Paid</td><td class="r">{{ money($invoice->paid) }}</td></tr>
<tr><td colspan="3" class="r b">Balance</td><td class="r b" style="color:#b91c1c">{{ money($invoice->balance) }}</td></tr></tbody></table>
@if($invoice->notes)<p>{{ $invoice->notes }}</p>@endif
@if($b = \App\Support\Setting::get('bank_details'))<div class="title">Payment Details</div><div style="white-space:pre-line">{{ $b }}</div>@endif
<div class="foot">{{ \App\Support\Setting::get('invoice_terms') }}</div>
</body></html>
