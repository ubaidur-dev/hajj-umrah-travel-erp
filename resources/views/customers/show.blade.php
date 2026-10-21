@extends('layouts.app')
@section('title', $customer->name)
@section('content')
<div class="grid lg:grid-cols-3 gap-5">
  <div class="card"><div class="card-h">Details <a class="btn btn-light btn-sm" href="{{ route('customers.edit', $customer) }}">Edit</a></div>
    <dl class="card-b text-sm space-y-2">
      @foreach(['phone'=>'Phone','whatsapp'=>'WhatsApp','email'=>'Email','city'=>'City','cnic'=>'CNIC','passport_no'=>'Passport','address'=>'Address','notes'=>'Notes'] as $k=>$l)
        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ $l }}</dt><dd class="text-right">{{ $customer->$k ?: '—' }}</dd></div>
      @endforeach
      <div class="flex justify-between border-t pt-2"><dt class="font-semibold">Balance Due</dt><dd class="font-bold text-red-600">{{ money($customer->balance) }}</dd></div>
      @if($w = wa_link($customer->whatsapp ?: $customer->phone, 'Hello '.$customer->name.', this is '.\App\Support\Setting::get('agency_name', 'Gulshan-e-Harmain').'.'))<x-whatsapp :href="$w" label="Message on WhatsApp" class="w-full" title="Chat on WhatsApp" />@endif
    </dl></div>
  <div class="lg:col-span-2 space-y-5">
  <div class="card"><div class="card-h">Bookings <a class="btn btn-primary btn-sm" href="{{ route('bookings.create', ['customer_id' => $customer->id]) }}">+ New Booking</a></div>
    <table class="tbl"><thead><tr><th>No</th><th>Package</th><th>Total</th><th>Due</th><th>Status</th></tr></thead><tbody>
    @forelse($customer->bookings as $b)
      <tr><td><a class="text-brand-700 font-medium" href="{{ route('bookings.show', $b) }}">{{ $b->booking_no }}</a></td><td>{{ $b->title }}</td><td>{{ money($b->net_total) }}</td><td>{{ money($b->due) }}</td><td><x-badge :status="$b->status" /></td></tr>
    @empty <tr><td colspan="5" class="text-center text-slate-500 py-6">No bookings.</td></tr> @endforelse</tbody></table></div>
  <div class="card"><div class="card-h"><div>Documents<div class="card-sub">Passport, CNIC, photos, vaccination certificate — kept safely with this customer.</div></div></div>
    <div class="card-b"><x-uploader model="customer" :id="$customer->id" :media="$customer->getMedia('documents')" accept="image/*,.pdf,.doc,.docx" label="Drag & drop documents here, or click to browse" /></div></div>
  </div>
</div>
@endsection
