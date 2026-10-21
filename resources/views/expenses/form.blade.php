@extends('layouts.app')
@section('title', $expense->exists ? 'Edit Expense' : 'New Expense')
@section('content')
<form method="POST" action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}" class="card max-w-3xl">
  @csrf @if($expense->exists) @method('PUT') @endif
  <div class="card-b grid sm:grid-cols-2 gap-4">
    <div><label class="lbl">Category <span class="text-red-500">*</span></label><input name="category" list="cats" value="{{ old('category', $expense->category) }}" class="inp" required placeholder="Choose or type a new one"><datalist id="cats">@foreach($categories as $c)<option value="{{ $c }}">@endforeach</datalist><p class="hint">Type any new category — it will be remembered.</p></div>
    <x-field name="expense_date" type="date" label="Date" :value="$expense->expense_date?->toDateString()" required />
    <x-field name="title" label="Title" :value="$expense->title" required class="sm:col-span-2" placeholder="e.g. Hotel Makkah advance, Office rent Oct" />
    <x-field name="amount" type="number" step="0.01" label="Amount" :value="$expense->amount" required />
    <x-field name="method" type="select" label="Method" :options="\App\Models\Payment::METHODS" :value="$expense->method" />
    <x-field name="reference" label="Reference" :value="$expense->reference" />
    <x-field name="note" type="textarea" label="Note" :value="$expense->note" rows="2" class="sm:col-span-2" />
  </div>
  <div class="card-b border-t border-slate-100 flex gap-2"><button class="btn btn-primary">Save</button><a class="btn btn-light" href="{{ route('expenses.index') }}">Cancel</a></div>
</form>
@endsection
