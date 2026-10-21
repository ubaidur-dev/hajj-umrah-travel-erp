@extends('layouts.app')
@section('title', $booking->booking_no)
@section('content')
@php
  $phone = $booking->customer->whatsapp ?: $booking->customer->phone;
  $slipUrl = route('bookings.slip', $booking);
  $waText = "Hello {$booking->customer->name},\nYour booking {$booking->booking_no} ({$booking->title}) with ".\App\Support\Setting::get('agency_name','Gulshan-e-Harmain')." is ".$booking->status.'.'
    ."\nTotal: ".money($booking->net_total)."\nPaid: ".money($booking->paid_total)."\nBalance: ".money($booking->due)."\nSlip: ".$slipUrl;
  $invoice = $booking->invoices->where('status','!=','cancelled')->first();
@endphp
<div class="card card-b flex flex-wrap items-center justify-between gap-3">
  <div><div class="text-xl font-bold">{{ $booking->booking_no }} <x-badge :status="$booking->status" /> <span class="badge bg-slate-100 text-slate-700">{{ \App\Http\Controllers\PackageController::TYPES[$booking->service_type] ?? $booking->service_type }}</span>@if($booking->is_overdue) <span class="badge bg-red-100 text-red-700">Payment overdue</span>@endif</div>
    <div class="text-sm text-slate-500">{{ $booking->title }} · {{ dt($booking->booking_date) }} · <a class="text-brand-700" href="{{ route('customers.show', $booking->customer) }}">{{ $booking->customer->name }}</a></div></div>
  <div class="flex flex-wrap gap-2">
    @if($booking->status === 'pending')<form method="POST" action="{{ route('bookings.confirm', $booking) }}" data-confirm="Confirm this booking? An invoice will be created automatically.">@csrf<button class="btn btn-gold">✔ Confirm Booking</button></form>@endif
    @if($booking->status !== 'pending')
      <a class="btn btn-primary" href="{{ $slipUrl }}" target="_blank">Slip (PDF)</a>
      <a class="btn btn-light" href="{{ $slipUrl }}?download=1">Download</a>
      @if($w = wa_link($phone, $waText))<x-whatsapp :href="$w" label="Send on WhatsApp" title="Send booking details to the customer on WhatsApp" />@endif
    @endif
    @if($invoice)<a class="btn btn-light" href="{{ route('invoices.show', $invoice) }}">Invoice {{ $invoice->invoice_no }}</a>
    @elseif($booking->status !== 'pending')<form method="POST" action="{{ route('invoices.fromBooking', $booking) }}">@csrf<button class="btn btn-light">Create Invoice</button></form>@endif
    <a class="btn btn-light" href="{{ route('bookings.edit', $booking) }}">Edit</a>
    <form method="POST" action="{{ route('bookings.destroy', $booking) }}" data-confirm="Delete this booking?">@csrf @method('DELETE')<button class="btn btn-light text-red-600">Delete</button></form>
  </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
  <x-stat label="Net Total" :value="money($booking->net_total)" :sub="$booking->discount > 0 ? 'Discount '.money($booking->discount) : null" />
  <x-stat label="Received" :value="money($booking->paid_total)" tone="green" />
  <x-stat label="Balance Due" :value="money($booking->due)" tone="red" />
  <x-stat label="Package Cost" :value="money($booking->cost_total)" />
  <x-stat label="Profit" :value="money($booking->profit)" tone="gold" :sub="$booking->net_total > 0 ? round($booking->profit/$booking->net_total*100,1).'% margin' : null" />
</div>

<div class="grid lg:grid-cols-3 gap-5">
  <div class="lg:col-span-2 space-y-5">
    <div class="card"><div class="card-h">Items</div><div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Description</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Total</th><th class="text-right">Cost</th></tr></thead><tbody>
      @foreach($booking->items as $i)<tr><td>{{ $i->description }}</td><td class="text-right">{{ rtrim(rtrim($i->quantity,'0'),'.') }}</td><td class="text-right">{{ money($i->unit_price) }}</td><td class="text-right">{{ money($i->total) }}</td><td class="text-right text-red-600">{{ money($i->cost_total) }}</td></tr>@endforeach
    </tbody></table></div></div>

    <div class="card"><div class="card-h">Travelers ({{ $booking->travelers->count() }} / {{ $booking->pax }})</div>
      <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Name</th><th>Passport</th><th>Visa status</th><th>Visa no.</th><th>Ticket no.</th><th></th></tr></thead><tbody>
      @forelse($booking->travelers as $t)
        @php $bad = $t->passport_expiry && $booking->travel_date && $t->passport_expiry->lt($booking->travel_date->copy()->addMonths(6)); @endphp
        <tr><td class="font-medium">{{ $t->name }}<br><span class="text-xs text-slate-500">{{ ucfirst($t->type) }}</span></td>
          <td class="whitespace-nowrap">{{ $t->passport_no ?: '—' }}<br><span class="text-xs {{ $bad ? 'text-red-600 font-semibold' : 'text-slate-500' }}">Exp {{ dt($t->passport_expiry) }}@if($bad) ⚠ &lt;6 months @endif</span></td>
          <td><select form="tv{{ $t->id }}" name="visa_status" data-autosave class="inp !py-1.5 !text-sm !w-36">@foreach(\App\Models\Traveler::VISA as $k=>$v)<option value="{{ $k }}" @selected($t->visa_status==$k)>{{ $v }}</option>@endforeach</select></td>
          <td><input form="tv{{ $t->id }}" name="visa_no" value="{{ $t->visa_no }}" data-autosave class="inp !py-1.5 !text-sm !w-32"></td>
          <td><input form="tv{{ $t->id }}" name="ticket_no" value="{{ $t->ticket_no }}" data-autosave class="inp !py-1.5 !text-sm !w-32"></td>
          <td><button form="td{{ $t->id }}" class="text-red-600 text-xs">Remove</button></td></tr>
      @empty<tr><td colspan="6" class="text-center text-slate-500 py-4">Add traveler details below.</td></tr>@endforelse</tbody></table></div>
      @foreach($booking->travelers as $t)
        <form id="tv{{ $t->id }}" data-ajax method="POST" action="{{ route('bookings.travelers.update', [$booking, $t->id]) }}">@csrf</form>
        <form id="td{{ $t->id }}" method="POST" action="{{ route('bookings.travelers.destroy', [$booking, $t->id]) }}" data-confirm="Remove this traveler?">@csrf @method('DELETE')</form>
      @endforeach
      <form method="POST" action="{{ route('bookings.travelers.store', $booking) }}" class="card-b border-t border-slate-100 grid sm:grid-cols-6 gap-3">@csrf
        <div class="sm:col-span-3"><label class="lbl">Full name</label><input name="name" class="inp" required></div>
        <div class="sm:col-span-1"><label class="lbl">Type</label><select name="type" class="inp"><option value="adult">Adult</option><option value="child">Child</option><option value="infant">Infant</option></select></div>
        <div class="sm:col-span-2"><label class="lbl">Passport no.</label><input name="passport_no" class="inp" placeholder="AB1234567" data-passport maxlength="9"></div>
        <div class="sm:col-span-3"><label class="lbl">Passport expiry</label><input type="date" name="passport_expiry" class="inp"></div>
        <div class="sm:col-span-3"><label class="lbl">Date of birth</label><input type="date" name="dob" class="inp"></div>
        <button class="btn btn-primary sm:col-span-6 justify-center">+ Add Traveler</button></form>
    </div>

    <div class="card"><div class="card-h">Payments</div><div class="overflow-x-auto"><table class="tbl tbl-compact"><thead><tr><th>Receipt</th><th>Date</th><th>Method / Ref</th><th class="text-right">Amount</th><th></th></tr></thead><tbody>
      @forelse($booking->payments as $p)<tr><td class="whitespace-nowrap">{{ $p->receipt_no }}@if($p->is_refund) <span class="badge bg-red-100 text-red-700">Refund</span>@endif</td><td class="whitespace-nowrap">{{ dt($p->paid_at) }}</td><td>{{ \App\Models\Payment::METHODS[$p->method] ?? $p->method }}@if($p->reference)<br><span class="text-xs text-slate-500">{{ $p->reference }}</span>@endif</td><td class="text-right font-medium whitespace-nowrap {{ $p->is_refund ? 'text-red-600' : '' }}">{{ money($p->amount) }}</td>
        <td class="text-right"><div class="flex flex-wrap justify-end gap-1 w-[148px] ml-auto"><a class="btn btn-light btn-sm" target="_blank" href="{{ route('payments.receipt', $p) }}">{{ $p->is_refund ? 'Voucher' : 'Receipt' }}</a>
        @if($w = wa_link($phone, "Payment received: ".money($p->amount)." ({$p->receipt_no}). Balance: ".money($booking->due)))<x-whatsapp :href="$w" size="xs" label="Send" title="Send this payment receipt on WhatsApp" />@endif
        <form class="inline" method="POST" action="{{ route('payments.destroy', $p) }}" data-confirm="Delete this payment?">@csrf @method('DELETE')<button class="btn btn-light btn-sm text-red-600">✕</button></form></div></td></tr>
      @empty<tr><td colspan="5" class="text-center text-slate-500 py-4">No payments yet.</td></tr>@endforelse</tbody></table></div>
      @if($booking->status !== 'cancelled')
      <form method="POST" action="{{ route('payments.store', $booking) }}" class="card-b border-t border-slate-100 grid sm:grid-cols-4 gap-3">@csrf
        <div><label class="lbl">Amount received</label><input name="amount" type="number" step="0.01" value="{{ $booking->due ?: '' }}" class="inp" required></div>
        <div><label class="lbl">Method</label><select name="method" class="inp">@foreach($methods as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
        <div><label class="lbl">Date</label><input type="date" name="paid_at" value="{{ today()->toDateString() }}" class="inp" required></div>
        <div><label class="lbl">Reference / transaction ID</label><input name="reference" class="inp"></div>
        <button class="btn btn-primary sm:col-span-4 justify-center">+ Receive Payment</button></form>@endif
      @if($booking->paid_total > 0)
      <details class="border-t border-slate-100"><summary class="px-5 py-3 text-sm text-red-600 cursor-pointer font-medium">Return money to the customer (refund)</summary>
        <form method="POST" action="{{ route('payments.refund', $booking) }}" class="card-b pt-0 grid sm:grid-cols-4 gap-3">@csrf
          <div><label class="lbl">Refund amount</label><input name="amount" type="number" step="0.01" max="{{ $booking->paid_total }}" class="inp" required></div>
          <div><label class="lbl">Method</label><select name="method" class="inp">@foreach($methods as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
          <div><label class="lbl">Date</label><input type="date" name="paid_at" value="{{ today()->toDateString() }}" class="inp" required></div>
          <div><label class="lbl">Reason</label><input name="note" class="inp" placeholder="e.g. Booking cancelled"></div>
          <button class="btn btn-danger sm:col-span-4 justify-center">Record Refund</button></form></details>
      @endif
    </div>

    <div class="card"><div class="card-h"><div>Documents<div class="card-sub">Visa copies, e-tickets, hotel vouchers and anything else for this booking.</div></div></div>
      <div class="card-b"><x-uploader model="booking" :id="$booking->id" :media="$booking->getMedia('documents')" accept="image/*,.pdf,.doc,.docx" label="Drag & drop files here, or click to browse" /></div></div>
  </div>

  <div class="space-y-5">
    <div class="card"><div class="card-h">Trip</div><dl class="card-b text-sm space-y-2">
      <div class="flex justify-between"><dt class="text-slate-500">Package</dt><dd>@if($booking->package)<a class="text-brand-700" href="{{ route('packages.show', $booking->package) }}">{{ $booking->package->name }}</a>@else — @endif</dd></div>
      <div class="flex justify-between"><dt class="text-slate-500">Pax</dt><dd>{{ $booking->adults }}A · {{ $booking->children }}C · {{ $booking->infants }}I</dd></div>
      <div class="flex justify-between"><dt class="text-slate-500">Travel</dt><dd>{{ dt($booking->travel_date) }}</dd></div>
      <div class="flex justify-between"><dt class="text-slate-500">Return</dt><dd>{{ dt($booking->return_date) }}</dd></div>
      <div class="flex justify-between"><dt class="text-slate-500">Payment due by</dt><dd class="{{ $booking->is_overdue ? 'text-red-600 font-semibold' : '' }}">{{ dt($booking->payment_due_date) }}</dd></div>
      <div class="flex justify-between"><dt class="text-slate-500">Confirmed</dt><dd>{{ dt($booking->confirmed_at, 'd M Y H:i') }}</dd></div>
      <div class="flex justify-between"><dt class="text-slate-500">Created by</dt><dd>{{ $booking->creator?->name ?? '—' }}</dd></div>
      @if($booking->notes)<p class="border-t pt-2 whitespace-pre-line">{{ $booking->notes }}</p>@endif
    </dl></div>
    <div class="card"><div class="card-h">Change Status</div><form method="POST" action="{{ route('bookings.status', $booking) }}" class="card-b flex gap-2">@csrf
      <select name="status" class="inp">@foreach(\App\Models\Booking::STATUSES as $k=>$v)<option value="{{ $k }}" @selected($booking->status==$k)>{{ $v }}</option>@endforeach</select><button class="btn btn-light">Set</button></form></div>
  </div>
</div>
@endsection
