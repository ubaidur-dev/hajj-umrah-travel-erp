<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\Search;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $r)
    {
        $q = Invoice::with(['customer', 'booking']);
        if ($r->status) {
            $q->where('status', $r->status);
        }
        Search::apply($q, $r->q, ['invoice_no', 'notes'], [
            'customer' => ['name', 'phone', 'cnic', 'passport_no'], 'booking' => ['booking_no', 'title'],
        ], ['invoice_no', '(select name from customers where customers.id = invoices.customer_id)']);

        return view('invoices.index', ['invoices' => $q->latest('issue_date')->latest('id')->paginate(10)->withQueryString()]);
    }

    public function create()
    {
        return view('invoices.form', ['invoice' => new Invoice(['issue_date' => today(), 'tax_rate' => \App\Support\Setting::get('tax_rate', 0), 'discount' => 0]), 'customers' => Customer::orderBy('name')->get(['id', 'name']), 'items' => collect()]);
    }

    public function store(Request $r)
    {
        $data = $r->validate($this->rules());
        $inv = Invoice::create([
            'invoice_no' => Invoice::newNumber(), 'customer_id' => $data['customer_id'], 'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'] ?? null, 'discount' => $data['discount'] ?? 0, 'tax_rate' => $data['tax_rate'] ?? 0, 'notes' => $data['notes'] ?? null,
        ]);
        $this->saveItems($inv, $data['items']);

        return redirect()->route('invoices.show', $inv)->with('ok', 'Invoice ban gayi.');
    }

    public function fromBooking(Booking $booking)
    {
        $inv = app(BookingController::class)->makeInvoice($booking);

        return redirect()->route('invoices.show', $inv)->with('ok', 'Invoice ban gayi.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'booking', 'items']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        return view('invoices.form', ['invoice' => $invoice, 'customers' => Customer::orderBy('name')->get(['id', 'name']), 'items' => $invoice->items]);
    }

    public function update(Request $r, Invoice $invoice)
    {
        $data = $r->validate($this->rules());
        $invoice->update([
            'customer_id' => $data['customer_id'], 'issue_date' => $data['issue_date'], 'due_date' => $data['due_date'] ?? null,
            'discount' => $data['discount'] ?? 0, 'tax_rate' => $data['tax_rate'] ?? 0, 'notes' => $data['notes'] ?? null,
        ]);
        $this->saveItems($invoice, $data['items']);

        return redirect()->route('invoices.show', $invoice)->with('ok', 'Invoice updated.');
    }

    public function cancel(Invoice $invoice)
    {
        $invoice->update(['status' => 'cancelled']);

        return back()->with('ok', 'Invoice cancelled.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return redirect()->route('invoices.index')->with('ok', 'Invoice deleted.');
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['customer', 'booking', 'items']);
        $pdf = Pdf::loadView('pdf.invoice', compact('invoice'))->setPaper('a4');

        return request()->boolean('download') ? $pdf->download("{$invoice->invoice_no}.pdf") : $pdf->stream("{$invoice->invoice_no}.pdf");
    }

    protected function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id', 'issue_date' => 'required|date', 'due_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0', 'tax_rate' => 'nullable|numeric|min:0|max:100', 'notes' => 'nullable|string',
            'items' => 'required|array|min:1', 'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0', 'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    protected function saveItems(Invoice $inv, array $items): void
    {
        $inv->items()->delete();
        foreach ($items as $i) {
            $inv->items()->create($i + ['amount' => $i['quantity'] * $i['unit_price']]);
        }
        $inv->recalc();
    }
}
