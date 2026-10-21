@extends('layouts.app')
@section('title', 'Expenses')
@section('content')
<div class="card card-b flex flex-wrap items-end justify-between gap-3">
  <form data-auto class="flex flex-wrap gap-2 items-end"><input name="q" value="{{ request('q') }}" placeholder="Search expense, category, reference…" class="inp w-64" autocomplete="off" spellcheck="false"><select name="category" class="inp w-44"><option value="">All categories</option>@foreach($categories as $k)<option value="{{ $k }}" @selected(request('category')==$k)>{{ \App\Models\Expense::CATEGORIES[$k] ?? ucfirst($k) }}</option>@endforeach</select>
    <input type="date" name="from" value="{{ request('from') }}" class="inp w-40"><input type="date" name="to" value="{{ request('to') }}" class="inp w-40"><button class="btn btn-light">Filter</button></form>
  <div class="flex items-center gap-4"><span class="text-sm" data-live>Total: <b class="text-lg text-red-600">{{ money($total) }}</b></span><a href="{{ route('expenses.create') }}" class="btn btn-primary">+ Add Expense</a></div>
</div>
<div class="card overflow-x-auto" data-live><table class="tbl"><thead><tr><th>Date</th><th>Category</th><th>Title</th><th class="text-right">Amount</th><th></th></tr></thead><tbody>
@forelse($expenses as $e)<tr><td>{{ dt($e->expense_date) }}</td><td>{{ \App\Models\Expense::CATEGORIES[$e->category] ?? ucfirst($e->category) }}</td><td>{{ $e->title }}</td><td class="text-right font-medium">{{ money($e->amount) }}</td>
<td class="text-right whitespace-nowrap"><a class="btn btn-light btn-sm" href="{{ route('expenses.edit', $e) }}">Edit</a>
<form class="inline" method="POST" action="{{ route('expenses.destroy', $e) }}" data-confirm="Delete this?">@csrf @method('DELETE')<button class="btn btn-light btn-sm text-red-600">✕</button></form></td></tr>
@empty<tr><td colspan="5" class="text-center text-slate-500 py-8">No expenses.</td></tr>@endforelse</tbody></table><div class="card-b">{{ $expenses->links() }}</div></div>
@endsection
