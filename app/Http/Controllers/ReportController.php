<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\Package;
use App\Models\Payment;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $r)
    {
        $from = $r->date('from') ?? now()->startOfMonth();
        $to = $r->date('to') ?? now()->endOfMonth();

        $bookings = Booking::with(['customer', 'package'])->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('booking_date', [$from->toDateString(), $to->toDateString()])->orderBy('booking_date')->get();

        $sales = $bookings->sum('net_total');
        $cost = $bookings->sum('cost_total');
        $opex = Expense::whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])->sum('amount');
        $received = Payment::whereBetween('paid_at', [$from->toDateString(), $to->toDateString()])->sum('amount');

        $byPackage = $bookings->groupBy(fn ($b) => $b->package?->name ?? $b->title)->map(fn ($g, $name) => [
            'name' => $name, 'count' => $g->count(), 'pax' => $g->sum('pax'), 'sales' => $g->sum('net_total'),
            'cost' => $g->sum('cost_total'), 'profit' => $g->sum('net_total') - $g->sum('cost_total'),
        ])->sortByDesc('profit')->values();

        $expenseByCat = Expense::selectRaw('category, SUM(amount) t')->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])->groupBy('category')->pluck('t', 'category');

        $inByDay = Payment::selectRaw('paid_at as d, SUM(amount) as t')->whereBetween('paid_at', [$from->toDateString(), $to->toDateString()])->groupBy('paid_at')->pluck('t', 'd');
        $outByDay = Expense::selectRaw('expense_date as d, SUM(amount) as t')->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])->groupBy('expense_date')->pluck('t', 'd');
        $days = $inByDay->keys()->merge($outByDay->keys())->map(fn ($d) => substr((string) $d, 0, 10))->unique()->sort()->values();
        $cashBook = $days->map(fn ($d) => ['date' => $d, 'in' => (float) ($inByDay[$d] ?? $inByDay[$d.' 00:00:00'] ?? 0), 'out' => (float) ($outByDay[$d] ?? $outByDay[$d.' 00:00:00'] ?? 0)]);

        return view('reports.index', [
            'cashBook' => $cashBook,
            'from' => $from, 'to' => $to, 'bookings' => $bookings, 'sales' => $sales, 'cost' => $cost, 'opex' => $opex,
            'received' => $received, 'gross' => $sales - $cost, 'net' => $sales - $cost - $opex,
            'byPackage' => $byPackage, 'expenseByCat' => $expenseByCat,
            'receivable' => $bookings->sum(fn ($b) => $b->due),
        ]);
    }
}
