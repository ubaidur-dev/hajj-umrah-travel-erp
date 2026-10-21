@extends('layouts.app')
@section('title', 'Leads / Inquiries')
@section('content')
<div class="card card-b flex flex-wrap items-end justify-between gap-3">
  <form data-auto class="flex flex-wrap gap-2 items-end">
    <input name="q" value="{{ request('q') }}" placeholder="Search name or mobile…" class="inp w-64" data-suggest="leads" autocomplete="off" spellcheck="false">
    <select name="status" class="inp w-40"><option value="">Open leads</option>@foreach(\App\Models\Lead::STATUSES as $k=>$v)<option value="{{ $k }}" @selected(request('status')==$k)>{{ $v }}</option>@endforeach</select>
    <label class="text-sm flex items-center gap-1 pb-2.5"><input type="checkbox" name="due" value="1" @checked(request('due'))> Follow-up due</label>
    <button class="btn btn-light">Filter</button>
  </form>
  <a href="{{ route('leads.create') }}" class="btn btn-primary">+ Add Lead</a>
</div>
@if($dueCount)<div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm"><b>{{ $dueCount }}</b> {{ Str::plural('lead', $dueCount) }} need a follow-up today or earlier.</div>@endif
<div class="card overflow-x-auto" data-live><table class="tbl"><thead><tr><th>Name</th><th>Phone</th><th>Interested in</th><th>Persons</th><th>Expected travel</th><th>Follow-up</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($leads as $l)
<tr><td class="font-medium">{{ $l->name }}</td><td>{{ $l->phone }} @if($w = wa_link($l->phone, 'Hello '.$l->name.', thank you for contacting '.\App\Support\Setting::get('agency_name').'.'))<x-whatsapp :href="$w" size="xs" label="Chat" class="ml-2" title="Message this lead on WhatsApp" />@endif</td>
<td>{{ \App\Http\Controllers\LeadController::INTERESTS[$l->interest] ?? $l->interest }}</td><td>{{ $l->persons }}</td><td>{{ dt($l->expected_travel) }}</td>
<td class="{{ $l->is_due ? 'text-red-600 font-semibold' : '' }}">{{ dt($l->next_follow_up) }}</td><td><span class="badge bg-slate-100">{{ \App\Models\Lead::STATUSES[$l->status] ?? $l->status }}</span></td>
<td class="text-right whitespace-nowrap">
  @if($l->status !== 'won')<form class="inline" method="POST" action="{{ route('leads.convert', $l) }}">@csrf<button class="btn btn-gold btn-sm">Make booking</button></form>@endif
  <a class="btn btn-light btn-sm" href="{{ route('leads.edit', $l) }}">Edit</a></td></tr>
@empty<tr><td colspan="8" class="text-center text-slate-500 py-8">No leads. Add every phone or WhatsApp inquiry here so nobody is forgotten.</td></tr>@endforelse</tbody></table>
<div class="card-b">{{ $leads->links() }}</div></div>
@endsection
