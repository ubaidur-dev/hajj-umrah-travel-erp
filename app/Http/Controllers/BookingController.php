<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\Search;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    protected function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id', 'package_id' => 'nullable|exists:packages,id',
            'title' => 'required|string|max:200', 'service_type' => 'required|in:umrah,hajj,tour,ticket,visa,hotel,other', 'payment_due_date' => 'nullable|date',
            'adults' => 'required|integer|min:0', 'children' => 'nullable|integer|min:0', 'infants' => 'nullable|integer|min:0',
            'booking_date' => 'required|date', 'travel_date' => 'nullable|date', 'return_date' => 'nullable|date|after_or_equal:travel_date',
            'discount' => 'nullable|numeric|min:0', 'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.description' => 'nullable|string|max:255', 'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0', 'items.*.unit_cost' => 'nullable|numeric|min:0',
            'advance' => 'nullable|numeric|min:0', 'advance_method' => 'nullable|string',
        ];
    }

    public function index(Request $r)
    {
        $q = Booking::query()->with(['customer', 'package']);
        Search::apply($q, $r->q, ['booking_no', 'title', 'notes'], [
            'customer' => ['name', 'phone', 'whatsapp', 'cnic', 'passport_no', 'email'],
            'travelers' => ['name', 'passport_no', 'ticket_no', 'visa_no'],
        ], ['booking_no', 'title', '(select name from customers where customers.id = bookings.customer_id)', '(select phone from customers where customers.id = bookings.customer_id)']);
        if ($r->status) {
            $q->where('status', $r->status);
        }
        if ($r->service) {
            $q->where('service_type', $r->service);
        }
        if ($r->overdue) {
            $q->whereDate('payment_due_date', '<', today())->whereColumn('paid_total', '<', 'net_total')->whereIn('status', ['pending', 'confirmed']);
        }
        if ($r->from) {
            $q->whereDate('booking_date', '>=', $r->from);
        }
        if ($r->to) {
            $q->whereDate('booking_date', '<=', $r->to);
        }
        if ($r->due) {
            $q->whereColumn('paid_total', '<', 'net_total')->where('status', '!=', 'cancelled');
        }

        return view('bookings.index', ['bookings' => $q->latest('booking_date')->latest('id')->paginate(10)->withQueryString()]);
    }

    public function create(Request $r)
    {
        return view('bookings.form', [
            'booking' => new Booking(['booking_date' => today(), 'service_type' => $r->service ?: 'umrah', 'adults' => 1, 'children' => 0, 'infants' => 0, 'discount' => 0, 'customer_id' => $r->customer_id, 'package_id' => $r->package_id]),
            'items' => collect(), ...$this->lookups(),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate($this->rules());

        if (! empty($data['package_id'])) {
            $data['service_type'] = Package::find($data['package_id'])->type;
        }

        $booking = DB::transaction(function () use ($data, $r) {
            $b = Booking::create([
                'booking_no' => Booking::newNumber(), 'status' => 'pending', 'created_by' => auth()->id(),
                'children' => $data['children'] ?? 0, 'infants' => $data['infants'] ?? 0, 'discount' => $data['discount'] ?? 0,
            ] + collect($data)->except(['items', 'advance', 'advance_method', 'children', 'infants', 'discount'])->all());

            $this->saveItems($b, $data['items'] ?? [], true);
            $b->recalc();

            if (($data['advance'] ?? 0) > 0) {
                Payment::create([
                    'receipt_no' => Payment::newNumber(), 'booking_id' => $b->id, 'amount' => $data['advance'],
                    'method' => $data['advance_method'] ?? 'cash', 'paid_at' => today(), 'note' => 'Advance', 'received_by' => auth()->id(),
                ]);
            }
            ActivityLog::record('create', "Booking {$b->booking_no} created", $b);

            return $b;
        });

        return redirect()->route('bookings.show', $booking)->with('ok', 'Booking created. Confirm it to generate the invoice and slip.');
    }

    public function show(Booking $booking)
    {
        $booking->load(['customer', 'package', 'items', 'travelers', 'payments.receiver', 'invoices', 'media']);

        return view('bookings.show', ['booking' => $booking, 'methods' => Payment::METHODS]);
    }

    public function edit(Booking $booking)
    {
        return view('bookings.form', ['booking' => $booking, 'items' => $booking->items, ...$this->lookups()]);
    }

    public function update(Request $r, Booking $booking)
    {
        $data = $r->validate($this->rules());
        if (! empty($data['package_id'])) {
            $data['service_type'] = Package::find($data['package_id'])->type;
        }
        DB::transaction(function () use ($booking, $data) {
            $booking->update([
                'children' => $data['children'] ?? 0, 'infants' => $data['infants'] ?? 0, 'discount' => $data['discount'] ?? 0,
            ] + collect($data)->except(['items', 'advance', 'advance_method', 'children', 'infants', 'discount'])->all());
            $this->saveItems($booking, $data['items'] ?? [], false);
            $booking->recalc();
            // keep open invoices in sync with changed items
            foreach ($booking->invoices()->where('status', '!=', 'cancelled')->get() as $inv) {
                $this->fillInvoiceFromBooking($inv, $booking);
            }
        });

        return redirect()->route('bookings.show', $booking)->with('ok', 'Booking updated.');
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();

        return redirect()->route('bookings.index')->with('ok', 'Booking deleted.');
    }

    /** Pending -> Confirmed. Auto-creates invoice and takes you to the slip. */
    public function confirm(Booking $booking)
    {
        if ($booking->items()->count() === 0) {
            return back()->with('err', 'Please add items / prices to the booking first.');
        }
        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'confirmed', 'confirmed_at' => now()]);
            if (! $booking->invoices()->where('status', '!=', 'cancelled')->exists()) {
                $this->makeInvoice($booking);
            }
            ActivityLog::record('confirm', "Booking {$booking->booking_no} confirmed", $booking);
        });

        return redirect()->route('bookings.show', $booking)->with('ok', 'Booking confirmed. The slip and invoice are ready to send to the client.');
    }

    public function setStatus(Request $r, Booking $booking)
    {
        $status = $r->validate(['status' => 'required|in:pending,confirmed,completed,cancelled'])['status'];
        $booking->update(['status' => $status]);
        $note = $status === 'cancelled' && $booking->paid_total > 0 ? ' The customer has paid '.money($booking->paid_total).' — record a refund below if you are returning money.' : '';
        if ($status === 'cancelled') {
            $booking->invoices()->update(['status' => 'cancelled']);
        }
        ActivityLog::record('status', "Booking {$booking->booking_no} -> $status", $booking);

        return back()->with('ok', 'Status changed.'.$note);
    }

    public function slip(Booking $booking)
    {
        $booking->load(['customer', 'package', 'items', 'travelers', 'payments']);
        $pdf = Pdf::loadView('pdf.slip', compact('booking'))->setPaper('a4');

        return request()->boolean('download')
            ? $pdf->download("Slip-{$booking->booking_no}.pdf")
            : $pdf->stream("Slip-{$booking->booking_no}.pdf");
    }

    // ---- Travelers -----------------------------------------------------
    public function addTraveler(Request $r, Booking $booking)
    {
        $data = $r->validate([
            'name' => 'required|string|max:150', 'type' => 'required|in:adult,child,infant', 'gender' => 'nullable|string|max:10',
            'relation' => 'nullable|string|max:50', 'passport_no' => \App\Support\Pk::passport(), 'passport_expiry' => 'nullable|date',
            'dob' => 'nullable|date', 'cnic' => \App\Support\Pk::cnic(),
            'visa_status' => 'nullable|in:'.implode(',', array_keys(\App\Models\Traveler::VISA)), 'visa_no' => 'nullable|string|max:40', 'ticket_no' => 'nullable|string|max:40',
        ], \App\Support\Pk::messages());
        $booking->travelers()->create($data);

        return back()->with('ok', 'Traveler added.');
    }

    /** Quick inline edit (visa status, visa number, ticket number) — saved without reloading the page. */
    public function updateTraveler(Request $r, Booking $booking, $traveler)
    {
        $t = $booking->travelers()->findOrFail($traveler);
        $t->update($r->validate([
            'visa_status' => 'nullable|in:'.implode(',', array_keys(\App\Models\Traveler::VISA)),
            'visa_no' => 'nullable|string|max:40', 'ticket_no' => 'nullable|string|max:40',
        ]));

        return $r->expectsJson() ? response()->json(['ok' => true, 'message' => "{$t->name}: saved"]) : back()->with('ok', 'Traveler updated.');
    }

    public function removeTraveler(Booking $booking, $traveler)
    {
        $booking->travelers()->whereKey($traveler)->delete();

        return back()->with('ok', 'Traveler removed.');
    }

    // ---- helpers -------------------------------------------------------
    protected function lookups(): array
    {
        return [
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'phone']),
            'packages' => Package::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    protected function saveItems(Booking $b, array $items, bool $isNew): void
    {
        $rows = collect($items)->filter(fn ($i) => filled($i['description'] ?? null));
        if ($rows->isEmpty() && $b->package_id && $isNew) {
            $b->load('package');
            $b->fillItemsFromPackage();

            return;
        }
        $b->items()->delete();
        foreach ($rows as $i) {
            $b->items()->create([
                'description' => $i['description'], 'quantity' => $i['quantity'] ?? 1, 'unit_price' => $i['unit_price'] ?? 0,
                'unit_cost' => $i['unit_cost'] ?? 0,
            ]);
        }
    }

    public function makeInvoice(Booking $booking): Invoice
    {
        $inv = Invoice::create([
            'invoice_no' => Invoice::newNumber(), 'booking_id' => $booking->id, 'customer_id' => $booking->customer_id,
            'issue_date' => today(), 'due_date' => $booking->travel_date?->copy()->subDays(7) ?? today()->addDays(7),
            'discount' => $booking->discount ?? 0, 'tax_rate' => (float) \App\Support\Setting::get('tax_rate', 0), 'status' => 'unpaid',
        ]);
        $this->fillInvoiceFromBooking($inv, $booking);

        return $inv;
    }

    public function fillInvoiceFromBooking(Invoice $inv, Booking $booking): void
    {
        $inv->items()->delete();
        foreach ($booking->items()->get() as $i) {
            $inv->items()->create([
                'description' => $i->description, 'quantity' => $i->quantity, 'unit_price' => $i->unit_price,
                'amount' => $i->quantity * $i->unit_price,
            ]);
        }
        $inv->discount = $booking->discount;
        $inv->save();
        $inv->recalc();
    }
}
