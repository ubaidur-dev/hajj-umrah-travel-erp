@extends('layouts.app')
@section('title', $invoice->exists ? 'Edit '.$invoice->invoice_no : 'Manual Invoice')
@section('content')
@php $rows = old('items', $items->map(fn($i)=>['description'=>$i->description,'quantity'=>(float)$i->quantity,'unit_price'=>(float)$i->unit_price])->all()) ?: [['description'=>'','quantity'=>1,'unit_price'=>0]]; @endphp
<form method="POST" action="{{ $invoice->exists ? route('invoices.update', $invoice) : route('invoices.store') }}" class="space-y-5 max-w-4xl">
  @csrf @if($invoice->exists) @method('PUT') @endif
  <div class="card card-b grid sm:grid-cols-4 gap-4">
    <x-field name="customer_id" type="select" label="Customer" :options="$customers->pluck('name','id')" placeholder="— select —" :value="$invoice->customer_id" required class="sm:col-span-2" />
    <x-field name="issue_date" type="date" label="Issue Date" :value="$invoice->issue_date?->toDateString()" required />
    <x-field name="due_date" type="date" label="Due Date" :value="$invoice->due_date?->toDateString()" />
    <x-field name="discount" type="number" step="0.01" label="Discount" :value="$invoice->discount" />
    <x-field name="tax_rate" type="number" step="0.01" label="Tax %" :value="$invoice->tax_rate" />
    <x-field name="notes" label="Notes" :value="$invoice->notes" class="sm:col-span-4" />
  </div>
  <div class="card"><div class="card-h">Items <button type="button" id="add" class="btn btn-light btn-sm">+ Row</button></div><div class="card-b space-y-2" id="rows">
    @foreach($rows as $i => $r)<div class="grid grid-cols-12 gap-2 r"><input name="items[{{ $i }}][description]" value="{{ $r['description'] }}" placeholder="Description" class="inp col-span-6" required>
      <input name="items[{{ $i }}][quantity]" type="number" step="0.01" value="{{ $r['quantity'] }}" class="inp col-span-2"><input name="items[{{ $i }}][unit_price]" type="number" step="0.01" value="{{ $r['unit_price'] }}" class="inp col-span-3"><button type="button" class="btn btn-light btn-sm rm">✕</button></div>@endforeach
  </div></div>
  <button class="btn btn-primary">Save Invoice</button>
</form>
@endsection
@push('scripts')
<script>
const rows=document.getElementById('rows'); let n=rows.children.length;
document.getElementById('add').onclick=()=>{const d=document.createElement('div');d.className='grid grid-cols-12 gap-2 r';
 d.innerHTML=`<input name="items[${n}][description]" placeholder="Description" class="inp col-span-6" required><input name="items[${n}][quantity]" type="number" step="0.01" value="1" class="inp col-span-2"><input name="items[${n}][unit_price]" type="number" step="0.01" value="0" class="inp col-span-3"><button type="button" class="btn btn-light btn-sm rm">✕</button>`;rows.appendChild(d);n++;};
rows.addEventListener('click',e=>{if(e.target.classList.contains('rm')&&rows.children.length>1)e.target.closest('.r').remove();});
</script>
@endpush
