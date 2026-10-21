@extends('layouts.app')
@section('title', 'Customers')
@section('content')
<div class="card">
  <div class="card-h">
    <form data-auto class="flex gap-2"><input name="q" value="{{ request('q') }}" placeholder="Search name, mobile, CNIC, passport…" class="inp w-72" data-suggest="customers" autocomplete="off" spellcheck="false"><button class="btn btn-light">Search</button></form>
    <a href="{{ route('customers.create') }}" class="btn btn-primary">+ Add Customer</a>
  </div>
  <div data-live>
  <div class="overflow-x-auto"><table class="tbl">
    <thead><tr><th>Name</th><th>Phone</th><th>City</th><th>Passport</th><th>Bookings</th><th></th></tr></thead>
    <tbody>@forelse($customers as $c)
      <tr><td><a class="font-medium text-brand-700" href="{{ route('customers.show', $c) }}">{{ $c->name }}</a></td><td>{{ $c->phone }}</td><td>{{ $c->city }}</td><td>{{ $c->passport_no }}</td><td>{{ $c->bookings_count }}</td>
      <td class="text-right"><a class="btn btn-light btn-sm" href="{{ route('customers.edit', $c) }}">Edit</a></td></tr>
    @empty <tr><td colspan="6" class="text-center text-slate-500 py-8">No customers found.</td></tr> @endforelse</tbody>
  </table></div>
  <div class="card-b">{{ $customers->links() }}</div>
  </div>
</div>
@endsection
