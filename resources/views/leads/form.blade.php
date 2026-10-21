@extends('layouts.app')
@section('title', $lead->exists ? 'Edit Lead' : 'New Lead')
@section('content')
<form method="POST" action="{{ $lead->exists ? route('leads.update', $lead) : route('leads.store') }}" class="card max-w-3xl">
  @csrf @if($lead->exists) @method('PUT') @endif
  <div class="card-b grid sm:grid-cols-2 gap-4">
    <x-field name="name" label="Name" :value="$lead->name" required />
    <x-field name="phone" label="Mobile / WhatsApp" :value="$lead->phone" placeholder="0300-1234567" inputmode="numeric" data-phone maxlength="12" />
    <x-field name="interest" type="select" label="Interested in" :options="\App\Http\Controllers\LeadController::INTERESTS" :value="$lead->interest" />
    <x-field name="persons" type="number" min="1" label="How many persons?" :value="$lead->persons" />
    <x-field name="expected_travel" type="date" label="Expected travel date" :value="$lead->expected_travel?->toDateString()" />
    <x-field name="status" type="select" label="Status" :options="\App\Models\Lead::STATUSES" :value="$lead->status" />
    <x-field name="next_follow_up" type="date" label="Next follow-up date" :value="$lead->next_follow_up?->toDateString()" hint="You will see this lead highlighted on the day." />
    <x-field name="notes" type="textarea" label="Notes" :value="$lead->notes" rows="3" class="sm:col-span-2" />
  </div>
  <div class="card-b border-t border-slate-100 flex gap-2"><button class="btn btn-primary">Save Lead</button><a class="btn btn-light" href="{{ route('leads.index') }}">Cancel</a></div>
</form>
@endsection
