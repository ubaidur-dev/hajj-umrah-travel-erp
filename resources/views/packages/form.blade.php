@extends('layouts.app')
@section('title', $package->exists ? 'Edit Package' : 'New Package')
@section('content')
@include('partials.cities')
<form method="POST" enctype="multipart/form-data" action="{{ $package->exists ? route('packages.update', $package) : route('packages.store') }}" class="space-y-5 max-w-5xl">
  @csrf @if($package->exists) @method('PUT') @endif
  <div class="card"><div class="card-h"><div>Package details<div class="card-sub">Basic information shown to your team and on the booking slip.</div></div></div><div class="card-b grid sm:grid-cols-3 gap-4">
    <x-field name="name" label="Package Name" :value="$package->name" required class="sm:col-span-2" />
    <x-field name="type" type="select" label="Type" :options="$types" :value="$package->type ?: 'umrah'" />
    <x-field name="code" label="Code" :value="$package->code" hint="Leave empty to create one automatically." />
    <x-field name="duration_days" type="number" label="Total days" :value="$package->duration_days" />
    <x-field name="makkah_nights" type="number" min="0" label="Makkah nights" :value="$package->makkah_nights" />
    <x-field name="madinah_nights" type="number" min="0" label="Madinah nights" :value="$package->madinah_nights" />
    <x-field name="total_seats" type="number" label="Total Seats" :value="$package->total_seats" />
    <x-field name="departure_date" type="date" label="Departure" :value="$package->departure_date?->toDateString()" />
    <x-field name="return_date" type="date" label="Return" :value="$package->return_date?->toDateString()" />
    <x-field name="airline" label="Airline" :value="$package->airline" />
    <x-field name="departure_city" label="Departure city" :value="$package->departure_city" list="pk-cities" placeholder="e.g. Karachi" />
    <x-field name="flight_no" label="Flight no." :value="$package->flight_no" />
    <x-field name="transport" label="Transport" :value="$package->transport" placeholder="e.g. AC bus / Private car" />
    <x-field name="meals" label="Meals" :value="$package->meals" placeholder="e.g. Breakfast & dinner" />
    <x-field name="makkah_hotel" label="Makkah Hotel" :value="$package->makkah_hotel" />
    <x-field name="madinah_hotel" label="Madinah Hotel" :value="$package->madinah_hotel" />
    <label class="flex items-center gap-2 text-sm self-end pb-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $package->is_active))> Active (available when creating bookings)</label>
    <x-field name="description" type="textarea" label="Description" :value="$package->description" class="sm:col-span-3" />
    <x-field name="inclusions" type="textarea" label="Inclusions (one per line)" :value="$package->inclusions" class="sm:col-span-3" />
    <x-field name="exclusions" type="textarea" label="Exclusions" :value="$package->exclusions" class="sm:col-span-3" rows="2" />
  </div></div>

  <div class="card"><div class="card-h"><div>Selling prices<div class="card-sub">What the customer pays, per person.</div></div></div><div class="card-b grid sm:grid-cols-3 gap-4">
    <x-field name="sale_price" type="number" step="0.01" label="Adult Price" :value="$package->sale_price" required />
    <x-field name="child_price" type="number" step="0.01" label="Child Price" :value="$package->child_price" />
    <x-field name="infant_price" type="number" step="0.01" label="Infant Price" :value="$package->infant_price" />
  </div></div>

  <div class="card"><div class="card-h"><div>Cost breakdown (per adult)<div class="card-sub">What the package costs you. Profit is calculated automatically.</div></div> <button type="button" id="addCost" class="btn btn-light btn-sm">+ Row</button></div>
    <div class="card-b space-y-2" id="costs">
      @php $rows = old('costs', $package->costs->map(fn($c)=>['title'=>$c->title,'amount'=>$c->amount])->all()); @endphp
      @foreach($rows as $i => $c)
        <div class="grid grid-cols-12 gap-2 cost-row">
          <input name="costs[{{ $i }}][title]" value="{{ $c['title'] ?? '' }}" placeholder="Hotel / Ticket / Visa / Transport..." class="inp col-span-8">
          <input name="costs[{{ $i }}][amount]" type="number" step="0.01" value="{{ $c['amount'] ?? '' }}" placeholder="Amount" class="inp col-span-3 cost-amt">
          <button type="button" class="btn btn-light btn-sm col-span-1 justify-center rm">✕</button>
        </div>
      @endforeach
    </div>
    <div class="card-b border-t border-slate-100 text-sm flex justify-between"><span>Total Cost / adult: <b id="costTotal">0</b></span><span>Margin / adult: <b id="margin" class="text-brand-700">0</b></span></div>
  </div>

  <div class="card"><div class="card-h"><div>Photos<div class="card-sub">Hotel, city or brochure photos. They appear on the package card.</div></div></div><div class="card-b">
    <x-uploader model="package" :id="$package->exists ? $package->id : null" name="images" :media="$package->exists ? $package->getMedia('images') : collect()" label="Drag & drop photos here, or click to browse" />
    @unless($package->exists)<p class="hint mt-2">Photos are saved when you press "Save Package".</p>@endunless
  </div></div>

  <div class="form-bar"><button class="btn btn-primary">Save Package</button><a class="btn btn-light" href="{{ route('packages.index') }}">Cancel</a></div>
</form>
@endsection
@push('scripts')
<script>
const wrap = document.getElementById('costs');
let idx = wrap.querySelectorAll('.cost-row').length;
function addRow(){
  const d = document.createElement('div'); d.className='grid grid-cols-12 gap-2 cost-row';
  d.innerHTML = `<input name="costs[${idx}][title]" placeholder="Hotel / Ticket / Visa / Transport..." class="inp col-span-8">
   <input name="costs[${idx}][amount]" type="number" step="0.01" placeholder="Amount" class="inp col-span-3 cost-amt">
   <button type="button" class="btn btn-light btn-sm col-span-1 justify-center rm">✕</button>`;
  wrap.appendChild(d); idx++;
}
document.getElementById('addCost').onclick = addRow;
if (!idx) addRow();
wrap.addEventListener('click', e => { if (e.target.classList.contains('rm')) { e.target.closest('.cost-row').remove(); calc(); }});
wrap.addEventListener('input', calc);
document.getElementById('f_sale_price').addEventListener('input', calc);
function calc(){
  let t = 0; wrap.querySelectorAll('.cost-amt').forEach(i => t += parseFloat(i.value)||0);
  const sp = parseFloat(document.getElementById('f_sale_price').value)||0;
  document.getElementById('costTotal').textContent = t.toLocaleString();
  document.getElementById('margin').textContent = (sp-t).toLocaleString();
}
calc();
</script>
@endpush
