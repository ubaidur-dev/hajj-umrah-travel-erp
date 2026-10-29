<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\Search;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $r)
    {
        $q = Payment::with('booking.customer');
        Search::apply($q, $r->q, ['receipt_no', 'reference', 'note'], [
            'booking' => ['booking_no', 'title'], 'booking.customer' => ['name', 'phone', 'cnic', 'passport_no'],
        ], ['receipt_no', 'reference', '(select booking_no from bookings where bookings.id = payments.booking_id)']);
        if ($r->from) {
            $q->whereDate('paid_at', '>=', $r->from);
        }
        if ($r->to) {
            $q->whereDate('paid_at', '<=', $r->to);
        }
        if ($r->method) {
            $q->where('method', $r->method);
        }
        $total = (clone $q)->reorder()->sum('amount');

        return view('payments.index', ['payments' => $q->latest('paid_at')->latest('id')->paginate(10)->withQueryString(), 'total' => $total, 'methods' => Payment::METHODS]);
    }

    public function store(Request $r, Booking $booking)
    {
        $data = $r->validate([
            'amount' => 'required|numeric|min:1', 'method' => 'required|in:'.implode(',', array_keys(Payment::METHODS)),
            'paid_at' => 'required|date', 'reference' => 'nullable|string|max:100', 'note' => 'nullable|string',
        ]);
        $p = Payment::create($data + ['booking_id' => $booking->id, 'receipt_no' => Payment::newNumber(), 'received_by' => auth()->id()]);
        ActivityLog::record('payment', "Payment {$p->receipt_no}: {$p->amount} ({$booking->booking_no})", $p);

        return redirect()->route('bookings.show', $booking)->with('ok', "Payment received — Receipt {$p->receipt_no}.");
    }

    public function refund(Request $r, Booking $booking)
    {
        $data = $r->validate([
            'amount' => 'required|numeric|min:1|max:'.max(1, (float) $booking->paid_total),
            'method' => 'required|in:'.implode(',', array_keys(Payment::METHODS)), 'paid_at' => 'required|date', 'note' => 'nullable|string|max:255',
        ], ['amount.max' => 'You cannot refund more than the customer has paid ('.money($booking->paid_total).').']);
        $p = Payment::create([
            'booking_id' => $booking->id, 'kind' => 'refund', 'receipt_no' => Payment::newNumber(), 'amount' => -1 * $data['amount'],
            'method' => $data['method'], 'paid_at' => $data['paid_at'], 'note' => $data['note'] ?? 'Refund', 'received_by' => auth()->id(),
        ]);
        ActivityLog::record('refund', "Refund {$p->receipt_no}: {$data['amount']} ({$booking->booking_no})", $p);

        return redirect()->route('bookings.show', $booking)->with('ok', "Refund recorded — voucher {$p->receipt_no}.");
    }

    public function destroy(Payment $payment)
    {
        $booking = $payment->booking;
        $payment->delete();

        return redirect()->route('bookings.show', $booking)->with('ok', 'Payment deleted.');
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['booking.customer', 'receiver']);
        $pdf = Pdf::loadView('pdf.receipt', compact('payment'))->setPaper('a5', 'landscape');

        return request()->boolean('download') ? $pdf->download("Receipt-{$payment->receipt_no}.pdf") : $pdf->stream("Receipt-{$payment->receipt_no}.pdf");
    }
}
