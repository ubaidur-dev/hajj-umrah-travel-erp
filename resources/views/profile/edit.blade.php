@extends('layouts.app')
@section('title', 'My Profile')
@section('content')
<div class="grid lg:grid-cols-3 gap-5 max-w-5xl">
  <div class="card">
    <div class="card-h">Profile photo</div>
    <div class="card-b space-y-4 text-center">
      <div class="flex justify-center">@include('partials.avatar', ['user' => $user, 'size' => 'h-28 w-28 text-3xl'])</div>
      <div><div class="font-semibold">{{ $user->name }}</div><div class="text-sm text-slate-500">{{ $user->email }} · <span class="capitalize">{{ $user->role }}</span></div></div>
      <x-uploader model="user" :id="$user->id" :media="$user->getMedia('avatar')" :multiple="false" :reload="true" label="Drop a photo here, or click to choose" hint="Shown in the header. JPG or PNG." />
    </div>
  </div>
  <form method="POST" action="{{ route('profile.update') }}" class="card lg:col-span-2">@csrf @method('PUT')
    <div class="card-h">Your details</div>
    <div class="card-b grid sm:grid-cols-2 gap-4">
      <x-field name="name" label="Name" :value="$user->name" required />
      <x-field name="phone" label="Mobile" :value="$user->phone" placeholder="0300-1234567" inputmode="numeric" data-phone maxlength="12" />
      <div class="sm:col-span-2 border-t border-slate-100 pt-4 text-sm font-semibold">Change password (optional)</div>
      <x-field name="current_password" type="password" label="Current Password" class="sm:col-span-2" autocomplete="current-password" />
      <x-field name="password" type="password" label="New Password" autocomplete="new-password" />
      <x-field name="password_confirmation" type="password" label="Confirm New Password" autocomplete="new-password" />
    </div>
    <div class="card-b border-t border-slate-100"><button class="btn btn-primary">Save Profile</button></div>
  </form>
</div>
@endsection
