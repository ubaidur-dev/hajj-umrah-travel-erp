@extends('layouts.app')
@section('title', $customer->exists ? 'Edit Customer' : 'New Customer')
@section('content')
@include('partials.cities')
<form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="card max-w-3xl">
  @csrf @if($customer->exists) @method('PUT') @endif
  <div class="card-b grid sm:grid-cols-2 gap-4">
    <x-field name="name" label="Full Name" :value="$customer->name" required class="sm:col-span-2" hint="As written on the passport." />
    <x-field name="phone" label="Mobile number" :value="$customer->phone" placeholder="0300-1234567" inputmode="numeric" data-phone maxlength="12" hint="11 digits, starts with 03." />
    <x-field name="whatsapp" label="WhatsApp (if different from mobile)" :value="$customer->whatsapp" placeholder="0300-1234567" inputmode="numeric" data-phone maxlength="12" />
    <x-field name="email" label="Email" :value="$customer->email" placeholder="name@gmail.com" type="text" inputmode="email" data-email pattern="[^@\s]+@[^@\s]+\.com" title="Email must end with .com" maxlength="120" autocomplete="off" />
    <x-field name="city" label="City" :value="$customer->city" list="pk-cities" placeholder="e.g. Karachi" />
    <x-field name="cnic" label="CNIC" :value="$customer->cnic" placeholder="12345-1234567-1" data-cnic maxlength="15" inputmode="numeric" hint="Digits only — dashes are added for you." />
    <x-field name="passport_no" label="Passport No" :value="$customer->passport_no" placeholder="AB1234567" data-passport maxlength="9" hint="2 letters + 7 digits." />
    <x-field name="address" type="textarea" label="Address" :value="$customer->address" class="sm:col-span-2" rows="2" />
    <x-field name="notes" type="textarea" label="Notes" :value="$customer->notes" class="sm:col-span-2" rows="2" />
  </div>
  <div class="card-b border-t border-slate-100 flex gap-2"><button class="btn btn-primary">Save</button><a class="btn btn-light" href="{{ route('customers.index') }}">Cancel</a></div>
</form>
@endsection
