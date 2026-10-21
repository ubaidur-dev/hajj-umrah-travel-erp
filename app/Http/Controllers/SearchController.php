<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Package;
use App\Models\Payment;
use App\Support\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Quick "type-ahead" suggestions under the search boxes (best 6 matches, same engine as the lists). */
class SearchController extends Controller
{
    public function suggest(Request $r): JsonResponse
    {
        $term = trim((string) $r->q);
        if (mb_strlen($term) < 1) {
            return response()->json([]);
        }

        $items = match ($r->scope) {
            'customers' => Search::apply(Customer::query(), $term, ['name', 'phone', 'whatsapp', 'email', 'cnic', 'passport_no', 'city', 'address'], [], ['name', 'phone', 'cnic', 'passport_no'])
                ->latest('id')->limit(6)->get()->map(fn ($c) => [
                    'title' => $c->name, 'sub' => collect([$c->phone, $c->cnic, $c->passport_no, $c->city])->filter()->implode(' · '), 'url' => route('customers.show', $c)]),
            'bookings' => Search::apply(Booking::with('customer'), $term, ['booking_no', 'title', 'notes'], [
                'customer' => ['name', 'phone', 'whatsapp', 'cnic', 'passport_no', 'email'], 'travelers' => ['name', 'passport_no', 'ticket_no', 'visa_no'],
            ], ['booking_no', 'title', '(select name from customers where customers.id = bookings.customer_id)', '(select phone from customers where customers.id = bookings.customer_id)'])
                ->latest('booking_date')->limit(6)->get()->map(fn ($b) => [
                    'title' => $b->booking_no.' · '.$b->customer->name, 'sub' => $b->title.' · '.ucfirst($b->status), 'url' => route('bookings.show', $b)]),
            'packages' => Search::apply(Package::query(), $term, ['name', 'code', 'airline', 'flight_no', 'makkah_hotel', 'madinah_hotel', 'departure_city', 'description'], [], ['name', 'code'])
                ->latest('id')->limit(6)->get()->map(fn ($p) => [
                    'title' => $p->name, 'sub' => $p->code.' · '.money($p->sale_price), 'url' => route('packages.show', $p)]),
            'invoices' => Search::apply(Invoice::with('customer'), $term, ['invoice_no', 'notes'], [
                'customer' => ['name', 'phone', 'cnic', 'passport_no'], 'booking' => ['booking_no', 'title'],
            ], ['invoice_no', '(select name from customers where customers.id = invoices.customer_id)'])
                ->latest('issue_date')->limit(6)->get()->map(fn ($i) => [
                    'title' => $i->invoice_no.' · '.$i->customer->name, 'sub' => money($i->total).' · '.ucfirst($i->status), 'url' => route('invoices.show', $i)]),
            'leads' => Search::apply(Lead::query(), $term, ['name', 'phone', 'notes'], [], ['name', 'phone'])
                ->latest('id')->limit(6)->get()->map(fn ($l) => [
                    'title' => $l->name, 'sub' => collect([$l->phone, LeadController::INTERESTS[$l->interest] ?? $l->interest, Lead::STATUSES[$l->status] ?? null])->filter()->implode(' · '), 'url' => route('leads.edit', $l)]),
            'payments' => Search::apply(Payment::with('booking.customer'), $term, ['receipt_no', 'reference', 'note'], [
                'booking' => ['booking_no', 'title'], 'booking.customer' => ['name', 'phone', 'cnic', 'passport_no'],
            ], ['receipt_no', 'reference', '(select booking_no from bookings where bookings.id = payments.booking_id)'])
                ->latest('paid_at')->limit(6)->get()->map(fn ($p) => [
                    'title' => $p->receipt_no.' · '.$p->booking->customer->name, 'sub' => money($p->amount).' · '.($p->booking->booking_no ?? ''), 'url' => route('bookings.show', $p->booking)]),
            default => collect(),
        };

        return response()->json($items->values());
    }
}
