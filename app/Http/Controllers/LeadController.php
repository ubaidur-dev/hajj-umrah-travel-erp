<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lead;
use App\Support\Pk;
use App\Support\Search;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public const INTERESTS = ['umrah' => 'Umrah', 'hajj' => 'Hajj', 'tour' => 'Tour', 'ticket' => 'Air Ticket', 'visa' => 'Visa', 'hotel' => 'Hotel Booking', 'other' => 'Other'];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:150', 'phone' => Pk::phone(),
            'interest' => 'required|in:'.implode(',', array_keys(self::INTERESTS)),
            'persons' => 'nullable|integer|min:1', 'expected_travel' => 'nullable|date',
            'status' => 'required|in:'.implode(',', array_keys(Lead::STATUSES)),
            'next_follow_up' => 'nullable|date', 'notes' => 'nullable|string',
        ];
    }

    public function index(Request $r)
    {
        $q = Lead::query();
        if ($r->status) {
            $q->where('status', $r->status);
        } else {
            $q->whereNotIn('status', ['won', 'lost']);
        }
        Search::apply($q, $r->q, ['name', 'phone', 'notes'], [], ['name', 'phone']);
        if ($r->due) {
            $q->whereDate('next_follow_up', '<=', today());
        }

        return view('leads.index', [
            'leads' => $q->orderByRaw('next_follow_up is null')->orderBy('next_follow_up')->latest('id')->paginate(10)->withQueryString(),
            'dueCount' => Lead::whereNotIn('status', ['won', 'lost'])->whereDate('next_follow_up', '<=', today())->count(),
        ]);
    }

    public function create() { return view('leads.form', ['lead' => new Lead(['status' => 'new', 'interest' => 'umrah'])]); }

    public function store(Request $r)
    {
        Lead::create($r->validate($this->rules(), Pk::messages()) + ['created_by' => auth()->id()]);

        return redirect()->route('leads.index')->with('ok', 'Lead saved.');
    }

    public function edit(Lead $lead) { return view('leads.form', compact('lead')); }

    public function update(Request $r, Lead $lead)
    {
        $lead->update($r->validate($this->rules(), Pk::messages()));

        return redirect()->route('leads.index')->with('ok', 'Lead updated.');
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();

        return redirect()->route('leads.index')->with('ok', 'Lead deleted.');
    }

    public function convert(Lead $lead)
    {
        $customer = $lead->customer ?? Customer::create(['name' => $lead->name, 'phone' => $lead->phone, 'notes' => $lead->notes]);
        $lead->update(['customer_id' => $customer->id, 'status' => 'won']);

        return redirect()->route('bookings.create', ['customer_id' => $customer->id, 'service' => $lead->interest])
            ->with('ok', "{$customer->name} is now a customer. Create the booking below.");
    }
}
