<!doctype html><html><head><meta charset="utf-8"><title>Slip {{ $booking->booking_no }}</title></head><body>
@include('pdf._head')
<table style="margin-top:14px"><tr>
  <td style="border:0;padding:0"><div class="title" style="margin-top:0">BOOKING CONFIRMATION SLIP</div>
    Booking No: <b>{{ $booking->booking_no }}</b><br>Date: {{ dt($booking->booking_date) }}</td>
  <td style="border:0;padding:0;text-align:right">
    <span class="tag {{ in_array($booking->status,['confirmed','completed']) ? 'ok' : ($booking->status==='cancelled' ? 'bad' : 'warn') }}">{{ strtoupper($booking->status) }}</span></td></tr></table>

<table style="margin-top:10px"><tr>
  <td class="box" style="width:50%;vertical-align:top"><b>CLIENT</b><br>{{ $booking->customer->name }}<br>{{ $booking->customer->phone }}<br>{{ $booking->customer->cnic ? 'CNIC: '.$booking->customer->cnic : '' }} {{ $booking->customer->passport_no ? 'Passport: '.$booking->customer->passport_no : '' }}</td>
  <td class="box" style="vertical-align:top"><b>TRIP</b><br>{{ $booking->title }}<br>
    @if($booking->package?->airline)Airline: {{ $booking->package->airline }}<br>@endif
    @if($booking->package?->makkah_hotel)Makkah: {{ $booking->package->makkah_hotel }}<br>@endif
    @if($booking->package?->madinah_hotel)Madinah: {{ $booking->package->madinah_hotel }}<br>@endif
    Travel: {{ dt($booking->travel_date) }} — Return: {{ dt($booking->return_date) }}<br>
    Passengers: {{ $booking->adults }} Adult, {{ $booking->children }} Child, {{ $booking->infants }} Infant</td></tr></table>

<div class="title">Charges</div>
<table><thead><tr><th>Description</th><th class="r">Qty</th><th class="r">Price</th><th class="r">Amount</th></tr></thead><tbody>
@foreach($booking->items as $i)<tr><td>{{ $i->description }}</td><td class="r">{{ rtrim(rtrim($i->quantity,'0'),'.') }}</td><td class="r">{{ money($i->unit_price) }}</td><td class="r">{{ money($i->total) }}</td></tr>@endforeach
@if($booking->discount > 0)<tr><td colspan="3" class="r">Discount</td><td class="r">- {{ money($booking->discount) }}</td></tr>@endif
<tr><td colspan="3" class="r b">Net Total</td><td class="r b">{{ money($booking->net_total) }}</td></tr>
<tr><td colspan="3" class="r">Received</td><td class="r">{{ money($booking->paid_total) }}</td></tr>
<tr><td colspan="3" class="r b">Balance Due</td><td class="r b" style="color:#b91c1c">{{ money($booking->due) }}</td></tr></tbody></table>

@if($booking->travelers->count())
<div class="title">Travelers</div>
<table><thead><tr><th>#</th><th>Name</th><th>Type</th><th>Passport</th><th>Expiry</th></tr></thead><tbody>
@foreach($booking->travelers as $k=>$t)<tr><td>{{ $k+1 }}</td><td>{{ $t->name }}</td><td>{{ ucfirst($t->type) }}</td><td>{{ $t->passport_no }}</td><td>{{ dt($t->passport_expiry) }}</td></tr>@endforeach</tbody></table>@endif

@if($booking->payments->count())
<div class="title">Payment History</div>
<table><thead><tr><th>Receipt</th><th>Date</th><th>Method</th><th class="r">Amount</th></tr></thead><tbody>
@foreach($booking->payments as $p)<tr><td>{{ $p->receipt_no }}</td><td>{{ dt($p->paid_at) }}</td><td>{{ \App\Models\Payment::METHODS[$p->method] ?? $p->method }}</td><td class="r">{{ money($p->amount) }}</td></tr>@endforeach</tbody></table>@endif

@if($booking->package?->inclusions)<div class="title">Inclusions</div><div style="white-space:pre-line">{{ $booking->package->inclusions }}</div>@endif
@if($b = \App\Support\Setting::get('bank_details'))<div class="title">Payment Details</div><div style="white-space:pre-line">{{ $b }}</div>@endif
<div class="foot">{{ \App\Support\Setting::get('invoice_terms', 'Yeh computer-generated slip hai.') }}<br>Generated {{ now()->format('d M Y H:i') }}</div>
</body></html>
