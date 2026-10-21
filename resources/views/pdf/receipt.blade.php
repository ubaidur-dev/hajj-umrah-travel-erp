<!doctype html><html><head><meta charset="utf-8"><title>Receipt {{ $payment->receipt_no }}</title></head><body>
@include('pdf._head')
<div class="title">{{ $payment->is_refund ? 'REFUND VOUCHER' : 'PAYMENT RECEIPT' }}</div>
<table><tr><td class="box" style="width:50%">Receipt No: <b>{{ $payment->receipt_no }}</b><br>Date: {{ dt($payment->paid_at) }}<br>Method: {{ \App\Models\Payment::METHODS[$payment->method] ?? $payment->method }} {{ $payment->reference ? '('.$payment->reference.')' : '' }}</td>
<td class="box">{{ $payment->is_refund ? 'Refunded to' : 'Received from' }}: <b>{{ $payment->booking->customer->name }}</b><br>Booking: {{ $payment->booking->booking_no }} — {{ $payment->booking->title }}<br>Received by: {{ $payment->receiver?->name }}</td></tr></table>
<table style="margin-top:12px"><tr><td class="b" style="font-size:16px">{{ $payment->is_refund ? 'Amount Refunded' : 'Amount Received' }}</td><td class="r b" style="font-size:18px;color:#065f46">{{ money(abs($payment->amount)) }}</td></tr>
<tr><td>Booking total / Total received / Balance</td><td class="r">{{ money($payment->booking->net_total) }} / {{ money($payment->booking->paid_total) }} / <b>{{ money($payment->booking->due) }}</b></td></tr></table>
<div class="foot">Thank you! — {{ \App\Support\Setting::get('agency_name') }}</div>
</body></html>
