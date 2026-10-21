@extends('layouts.app')
@section('title', 'Settings & Users')
@section('content')
<form method="POST" action="{{ route('settings.update') }}" class="card max-w-4xl">@csrf @method('PUT')
  <div class="card-h">Agency Profile (shown on slips and invoices)</div>
  <div class="card-b grid sm:grid-cols-2 gap-4">
    <x-field name="agency_name" label="Agency Name" :value="$s['agency_name'] ?? 'Gulshan-e-Harmain'" required />
    <x-field name="agency_tagline" label="Tagline" :value="$s['agency_tagline'] ?? ''" />
    <x-field name="owner_name" label="Owner / Consultant Name" :value="$s['owner_name'] ?? ''" />
    <x-field name="facebook" type="url" label="Facebook Page" :value="$s['facebook'] ?? ''" />
    <x-field name="phone" label="Phone (up to 2 numbers)" :value="$s['phone'] ?? ''" placeholder="0341-2185055, 0333-3002438" data-phones maxlength="26" />
    <x-field name="whatsapp" label="WhatsApp" :value="$s['whatsapp'] ?? ''" placeholder="0300-1234567" inputmode="numeric" data-phone maxlength="12" />
    <x-field name="email" label="Email" :value="$s['email'] ?? ''" placeholder="name@gmail.com" type="text" inputmode="email" data-email pattern="[^@\s]+@[^@\s]+\.com" title="Email must end with .com" maxlength="120" autocomplete="off" />
    <x-field name="tax_rate" type="number" step="0.01" label="Default Tax % (invoice)" :value="$s['tax_rate'] ?? 0" />
    <div class="sm:col-span-2"><label class="lbl">Agency logo (used in the header, login page and PDFs)</label>
      <x-uploader model="agency" :media="$logoMedia" :multiple="false" :reload="true" label="Drop your logo here, or click to choose" hint="Square PNG with a transparent background looks best." /></div>
    <x-field name="address" type="textarea" label="Address" :value="$s['address'] ?? ''" rows="2" class="sm:col-span-2" />
    <x-field name="bank_details" type="textarea" label="Bank / Payment Details" :value="$s['bank_details'] ?? ''" rows="3" class="sm:col-span-2" />
    <x-field name="invoice_terms" type="textarea" label="Terms & Conditions" :value="$s['invoice_terms'] ?? ''" rows="3" class="sm:col-span-2" />
  </div>
  <div class="card-b border-t border-slate-100"><button class="btn btn-primary">Save Settings</button></div>
</form>

<div class="card max-w-4xl"><div class="card-h">Users</div>
  <table class="tbl"><thead><tr><th>Name / Email</th><th>Role</th><th>Active</th><th>New password</th><th></th></tr></thead><tbody>
  @foreach($users as $u)<tr>
    <td><input form="u{{ $u->id }}" name="name" value="{{ $u->name }}" class="inp"><div class="text-xs text-slate-500 mt-1">{{ $u->email }}</div></td>
    <td><select form="u{{ $u->id }}" name="role" class="inp"><option value="staff" @selected($u->role=='staff')>Staff</option><option value="admin" @selected($u->role=='admin')>Admin</option></select></td>
    <td><input form="u{{ $u->id }}" type="checkbox" name="is_active" value="1" @checked($u->is_active)></td><td><input form="u{{ $u->id }}" name="password" type="password" placeholder="(leave empty to keep)" class="inp"></td>
    <td class="whitespace-nowrap"><button form="u{{ $u->id }}" class="btn btn-primary btn-sm">Save</button>
      @if($u->id !== auth()->id())<button form="d{{ $u->id }}" class="btn btn-light btn-sm text-red-600">✕</button>@endif</td></tr>@endforeach
  </tbody></table>
  <form method="POST" action="{{ route('users.store') }}" class="card-b border-t border-slate-100 grid sm:grid-cols-5 gap-2">@csrf
    <input name="name" placeholder="Name" class="inp" required><input name="email" type="text" inputmode="email" data-email pattern="[^@\s]+@[^@\s]+\.com" title="Email must end with .com" placeholder="Email (…@gmail.com)" class="inp" required><input name="password" type="password" placeholder="Password" class="inp" required>
    <select name="role" class="inp"><option value="staff">Staff</option><option value="admin">Admin</option></select><button class="btn btn-primary justify-center">+ Add User</button></form>
</div>
@foreach($users as $u)
  <form id="u{{ $u->id }}" method="POST" action="{{ route('users.update', $u) }}">@csrf @method('PUT')</form>
  @if($u->id !== auth()->id())<form id="d{{ $u->id }}" method="POST" action="{{ route('users.destroy', $u) }}" data-confirm="Delete this user?">@csrf @method('DELETE')</form>@endif
@endforeach
@endsection
