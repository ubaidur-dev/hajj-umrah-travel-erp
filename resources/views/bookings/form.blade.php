@extends('layouts.app')
@section('title', $booking->exists ? 'Edit '.$booking->booking_no : 'New Booking')
@section('content')
@php
  $rows = old('items', $items->map(fn($i)=>['description'=>$i->description,'quantity'=>(float)$i->quantity,'unit_price'=>(float)$i->unit_price,'unit_cost'=>(float)$i->unit_cost])->all());
  $services = \App\Http\Controllers\PackageController::TYPES;
  $service = old('service_type', $booking->service_type ?: 'umrah');
@endphp
<form method="POST" action="{{ $booking->exists ? route('bookings.update', $booking) : route('bookings.store') }}" class="space-y-5 max-w-6xl">
  @csrf @if($booking->exists) @method('PUT') @endif

  {{-- 1. Service + package --}}
  <div class="card"><div class="card-h"><div>1. What is the customer booking?<div class="card-sub">Choose the service first, then pick a ready-made package. Dates, hotels and prices fill in by themselves.</div></div></div>
    <div class="card-b space-y-4">
      <div class="flex flex-wrap gap-2" id="services">
        @foreach($services as $k => $label)
          <label class="cursor-pointer"><input type="radio" name="service_type" value="{{ $k }}" class="peer sr-only" @checked($service === $k)>
            <span class="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 peer-checked:bg-brand-700 peer-checked:text-white peer-checked:border-brand-700 peer-focus-visible:ring-2 ring-brand-500">{{ $label }}</span></label>
        @endforeach
      </div>
      <div class="grid lg:grid-cols-2 gap-4">
        <div><label class="lbl">Package / departure</label>
          <select name="package_id" id="package" class="inp"><option value="">— Custom booking (no package) —</option>
          @foreach($packages as $p)
            @php $left = $p->total_seats ? max(0, $p->total_seats - $p->seats_booked) : ''; @endphp
            <option value="{{ $p->id }}" data-type="{{ $p->type }}" data-name="{{ $p->name }}" data-sale="{{ $p->sale_price }}" data-child="{{ $p->child_price }}" data-infant="{{ $p->infant_price }}" data-cost="{{ $p->cost_price }}"
              data-dep="{{ $p->departure_date?->toDateString() }}" data-ret="{{ $p->return_date?->toDateString() }}" data-days="{{ $p->duration_days }}" data-mn="{{ $p->makkah_nights }}" data-dn="{{ $p->madinah_nights }}"
              data-mh="{{ $p->makkah_hotel }}" data-dh="{{ $p->madinah_hotel }}" data-air="{{ trim($p->airline.' '.$p->flight_no) }}" data-city="{{ $p->departure_city }}" data-left="{{ $left }}"
              @selected(old('package_id',$booking->package_id)==$p->id)>{{ $p->name }}{{ $p->departure_date ? ' — '.dt($p->departure_date) : '' }}</option>
          @endforeach</select>
          <p class="hint" id="noPkg" hidden>No active packages for this service yet. <a class="text-brand-700 underline" href="{{ route('packages.create') }}" target="_blank">Create one</a> or continue with a custom booking.</p>
        </div>
        <div id="pkgInfo" class="rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm hidden"></div>
      </div>
    </div>
  </div>

  {{-- 2. Customer + trip details --}}
  <div class="card"><div class="card-h"><div>2. Customer and trip details<div class="card-sub">Who is travelling, how many people and when.</div></div></div><div class="card-b grid sm:grid-cols-3 gap-4">
    <div class="sm:col-span-2"><label class="lbl">Customer <span class="text-red-500">*</span></label>
      <div class="flex gap-2"><select name="customer_id" id="customer" class="inp" required><option value="">— select customer —</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id',$booking->customer_id)==$c->id)>{{ $c->name }} {{ $c->phone ? '· '.$c->phone : '' }}</option>@endforeach</select>
      <button type="button" id="newCustomer" class="btn btn-light whitespace-nowrap">+ New</button></div>
      @error('customer_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
    <x-field name="booking_date" type="date" label="Booking Date" :value="$booking->booking_date?->toDateString()" required />
    <x-field name="title" label="Booking Title" :value="$booking->title" required class="sm:col-span-3" placeholder="e.g. Umrah Economy 15 Days" hint="Shown on the slip and invoice. Filled from the package automatically." />
    <x-field name="adults" type="number" min="0" label="Adults" :value="$booking->adults" required />
    <x-field name="children" type="number" min="0" label="Children (2–11)" :value="$booking->children" />
    <x-field name="infants" type="number" min="0" label="Infants (under 2)" :value="$booking->infants" />
    <x-field name="travel_date" type="date" label="Departure date" :value="$booking->travel_date?->toDateString()" />
    <x-field name="return_date" type="date" label="Return date" :value="$booking->return_date?->toDateString()" />
    <x-field name="payment_due_date" type="date" label="Full payment due by" :value="$booking->payment_due_date?->toDateString()" hint="You will be warned when this date passes with money still due." />
    <x-field name="notes" type="textarea" label="Notes" :value="$booking->notes" rows="2" class="sm:col-span-3" />
  </div></div>

  {{-- 3. Items --}}
  <div class="card"><div class="card-h"><div>3. Price and cost<div class="card-sub">Filled from the package. Change any line, or add extras like visa or insurance.</div></div>
    <span class="flex items-center gap-3"><button type="button" id="refill" class="btn btn-light btn-sm">Reset from package</button><button type="button" id="addItem" class="btn btn-light btn-sm">+ Add line</button></span></div>
    <div class="card-b overflow-x-auto">
      <div class="grid grid-cols-12 gap-2 text-xs text-slate-500 mb-1 min-w-[760px]"><span class="col-span-6">Description</span><span>Qty</span><span class="col-span-2">Sale price (each)</span><span class="col-span-2">Our cost (each)</span><span></span></div>
      <div id="items" class="space-y-2 min-w-[760px]">
      @foreach($rows as $i => $r)
        <div class="grid grid-cols-12 gap-2 item-row">
          <input name="items[{{ $i }}][description]" value="{{ $r['description'] ?? '' }}" class="inp col-span-6">
          <input name="items[{{ $i }}][quantity]" type="number" step="0.01" value="{{ $r['quantity'] ?? 1 }}" class="inp q">
          <input name="items[{{ $i }}][unit_price]" type="number" step="0.01" value="{{ $r['unit_price'] ?? 0 }}" class="inp col-span-2 p">
          <input name="items[{{ $i }}][unit_cost]" type="number" step="0.01" value="{{ $r['unit_cost'] ?? 0 }}" class="inp col-span-2 c">
          <button type="button" class="btn btn-light btn-sm justify-center rm">✕</button>
        </div>
      @endforeach
      </div>
    </div>
    <div class="card-b border-t border-slate-100 grid sm:grid-cols-4 gap-4 items-end">
      <x-field name="discount" type="number" step="0.01" label="Discount (total)" :value="$booking->discount ?? 0" />
      <div class="text-sm">Customer pays<br><b class="text-lg" id="tNet">0</b></div>
      <div class="text-sm">Our cost<br><b class="text-lg text-red-600" id="tCost">0</b></div>
      <div class="text-sm">Your profit<br><b class="text-lg text-brand-700" id="tProfit">0</b></div>
    </div>
  </div>

  @unless($booking->exists)
  <div class="card"><div class="card-h"><div>4. Advance payment<div class="card-sub">Optional. Enter it if the customer paid something today.</div></div></div><div class="card-b grid sm:grid-cols-3 gap-4">
    <x-field name="advance" type="number" step="0.01" label="Advance Amount" />
    <x-field name="advance_method" type="select" label="Method" :options="\App\Models\Payment::METHODS" value="cash" />
  </div></div>
  @endunless

  <div class="form-bar"><button class="btn btn-primary">Save Booking</button><a class="btn btn-light" href="{{ $booking->exists ? route('bookings.show', $booking) : route('bookings.index') }}">Cancel</a></div>
</form>

{{-- Quick add customer pop-up --}}
<dialog id="custDlg" class="rounded-2xl p-0 w-[min(28rem,92vw)] backdrop:bg-black/40">
  <form id="custForm" method="dialog" class="p-5 space-y-3">
    <div class="text-lg font-semibold">Add new customer</div>
    <div><label class="lbl">Full name *</label><input name="name" class="inp" required></div>
    <div class="grid grid-cols-2 gap-3"><div><label class="lbl">Mobile</label><input name="phone" class="inp" placeholder="0300-1234567" inputmode="numeric" data-phone maxlength="12"></div><div><label class="lbl">Passport no.</label><input name="passport_no" class="inp" placeholder="AB1234567" data-passport maxlength="9"></div></div>
    <div><label class="lbl">CNIC</label><input name="cnic" class="inp" data-cnic maxlength="15" inputmode="numeric" placeholder="12345-1234567-1"></div>
    <div class="flex gap-2 pt-1"><button class="btn btn-primary" id="custSave" value="save">Save customer</button><button type="button" class="btn btn-light" id="custCancel">Cancel</button></div>
  </form>
</dialog>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
const wrap = document.getElementById('items'), pkg = document.getElementById('package');
let idx = wrap.querySelectorAll('.item-row').length;
const num = el => parseFloat(el.value)||0, $ = id => document.getElementById(id);
const fmt = n => Number(n).toLocaleString();
const dateFmt = d => d ? new Date(d+'T00:00').toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) : '—';

function row(desc='', qty=1, price=0, cost=0){
  const d = document.createElement('div'); d.className='grid grid-cols-12 gap-2 item-row';
  d.innerHTML = `<input name="items[${idx}][description]" value="${desc.replace(/"/g,'&quot;')}" class="inp col-span-6">
   <input name="items[${idx}][quantity]" type="number" step="0.01" value="${qty}" class="inp q">
   <input name="items[${idx}][unit_price]" type="number" step="0.01" value="${price}" class="inp col-span-2 p">
   <input name="items[${idx}][unit_cost]" type="number" step="0.01" value="${cost}" class="inp col-span-2 c">
   <button type="button" class="btn btn-light btn-sm justify-center rm">✕</button>`;
  wrap.appendChild(d); idx++; calc();
}
function calc(){
  let s=0,c=0; wrap.querySelectorAll('.item-row').forEach(r=>{const q=num(r.querySelector('.q')); s+=q*num(r.querySelector('.p')); c+=q*num(r.querySelector('.c'));});
  const net = Math.max(0, s-num($('f_discount')));
  $('tNet').textContent = fmt(net); $('tCost').textContent = fmt(c); $('tProfit').textContent = fmt(net-c);
}
function pax(){ return num($('f_adults')) + num($('f_children')) + num($('f_infants')); }

function showInfo(){
  const o = pkg.selectedOptions[0], box = $('pkgInfo');
  if(!o || !o.value){ box.classList.add('hidden'); return; }
  const d = o.dataset, nights = [d.mn && `${d.mn} nights Makkah`, d.dn && `${d.dn} nights Madinah`].filter(Boolean).join(' + ');
  const left = d.left === '' ? null : Number(d.left);
  const warn = left !== null && pax() > left ? `<div class="mt-2 rounded-lg bg-red-100 text-red-700 px-3 py-1.5 font-medium">Only ${left} seats left — this booking has ${pax()} travelers.</div>` : '';
  box.innerHTML = `<div class="font-semibold text-brand-800">${d.name}</div>
    <div class="mt-1 grid grid-cols-2 gap-x-4 gap-y-1 text-slate-700">
      <div>Departure: <b>${dateFmt(d.dep)}</b></div><div>Return: <b>${dateFmt(d.ret)}</b></div>
      <div>Duration: <b>${d.days ? d.days+' days' : '—'}</b></div><div>Stay: <b>${nights || '—'}</b></div>
      <div>Makkah hotel: <b>${d.mh || '—'}</b></div><div>Madinah hotel: <b>${d.dh || '—'}</b></div>
      <div>Flight: <b>${[d.city, d.air].filter(Boolean).join(' · ') || '—'}</b></div><div>Seats left: <b>${left === null ? 'Not limited' : left}</b></div>
      <div>Adult: <b>${fmt(d.sale)}</b></div><div>Child / Infant: <b>${fmt(d.child)} / ${fmt(d.infant)}</b></div></div>${warn}`;
  box.classList.remove('hidden');
}
function fromPackage(){
  const o = pkg.selectedOptions[0]; showInfo(); if(!o || !o.value) return;
  wrap.innerHTML=''; idx=0;
  const n = o.dataset.name, a=num($('f_adults')), c=num($('f_children')), i=num($('f_infants'));
  if(a>0) row(n+' – Adult', a, o.dataset.sale, o.dataset.cost);
  if(c>0) row(n+' – Child', c, o.dataset.child, o.dataset.cost);
  if(i>0) row(n+' – Infant', i, o.dataset.infant, 0);
  $('f_title').value = n;
  if(o.dataset.dep) $('f_travel_date').value = o.dataset.dep;
  if(o.dataset.ret) $('f_return_date').value = o.dataset.ret;
  if(o.dataset.dep && !$('f_payment_due_date').value){ const d=new Date(o.dataset.dep+'T00:00'); d.setDate(d.getDate()-15); $('f_payment_due_date').value = d.toISOString().slice(0,10); }
  calc();
}
function filterPackages(){
  const t = document.querySelector('[name=service_type]:checked')?.value; let shown = 0;
  [...pkg.options].forEach(o => { if(!o.value) return; const ok = o.dataset.type === t; o.hidden = !ok; o.disabled = !ok; if(ok) shown++; });
  if(pkg.selectedOptions[0]?.disabled){ pkg.value=''; showInfo(); }
  $('noPkg').hidden = shown > 0;
}
document.querySelectorAll('[name=service_type]').forEach(r => r.addEventListener('change', filterPackages));
pkg.addEventListener('change', fromPackage);
['f_adults','f_children','f_infants'].forEach(id => $(id).addEventListener('input', () => { showInfo(); }));
$('refill').onclick = fromPackage;
$('addItem').onclick = () => row();
wrap.addEventListener('input', calc);
wrap.addEventListener('click', e => { if(e.target.classList.contains('rm')){ e.target.closest('.item-row').remove(); calc(); }});
$('f_discount').addEventListener('input', calc);

// quick add customer
const dlg = $('custDlg');
$('newCustomer').onclick = () => dlg.showModal();
$('custCancel').onclick = () => dlg.close();
$('custForm').addEventListener('submit', async e => {
  e.preventDefault();
  const res = await fetch('{{ route('customers.quick') }}', {method:'POST', body:new FormData($('custForm')), headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content, Accept:'application/json'}});
  if(!res.ok){ const e = await res.json().catch(()=>({})); toast(e.errors ? Object.values(e.errors).flat()[0] : 'Please check the customer details', 'error', 6000); return; }
  const c = await res.json(); const o = new Option(c.label, c.id, true, true); $('customer').add(o); $('customer').value = c.id;
  $('custForm').reset(); dlg.close(); toast('Customer added', 'success', 2500);
});

filterPackages();
@if(! $booking->exists) if (pkg.value) fromPackage(); else if(!idx) row(); @else showInfo(); @endif
calc();
});
</script>
@endpush
