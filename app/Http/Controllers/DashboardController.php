<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Traveler;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Brand-new system with no bookings yet: show a fully populated sample dashboard so the owner sees the finished design.
        if (Booking::count() === 0) {
            return view('dashboard', $this->demo());
        }

        $counted = fn () => Booking::whereIn('status', ['confirmed', 'completed']);
        $monthStart = now()->startOfMonth();

        $stats = [
            'sales' => (float) $counted()->sum('net_total'),
            'cost' => (float) $counted()->sum('cost_total'),
            'received' => (float) Payment::sum('amount'), // refunds are stored as negative amounts
            'expenses' => (float) Expense::sum('amount'),
            'month_sales' => (float) $counted()->where('booking_date', '>=', $monthStart)->sum('net_total'),
            'month_profit' => (float) $counted()->where('booking_date', '>=', $monthStart)->selectRaw('COALESCE(SUM(net_total - cost_total),0) p')->value('p'),
            'pending' => Booking::where('status', 'pending')->count(),
            'confirmed' => $counted()->count(),
        ];
        $stats['gross_profit'] = $stats['sales'] - $stats['cost'];
        $stats['net_profit'] = $stats['gross_profit'] - $stats['expenses'];
        $paidOnSales = (float) $counted()->sum('paid_total');
        $stats['receivable'] = $stats['sales'] - $paidOnSales;

        // ---- last 6 months (sales, profit, cash in) -------------------------------------------
        $months = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $series = [
            'labels' => $months->map->format('M')->all(),
            'sales' => $months->map(fn (Carbon $m) => (float) $counted()->whereBetween('booking_date', [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()])->sum('net_total'))->all(),
            'profit' => $months->map(fn (Carbon $m) => (float) $counted()->whereBetween('booking_date', [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()])->selectRaw('COALESCE(SUM(net_total - cost_total),0) p')->value('p'))->all(),
            'received' => $months->map(fn (Carbon $m) => (float) Payment::whereBetween('paid_at', [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()])->sum('amount'))->all(),
        ];
        $delta = fn (array $a) => ($a[4] ?? 0) > 0 ? round((($a[5] - $a[4]) / $a[4]) * 100, 1) : null; // this month vs last month
        $deltas = ['sales' => $delta($series['sales']), 'profit' => $delta($series['profit']), 'received' => $delta($series['received'])];

        $chart = $series + ['pl' => ['cost' => $stats['cost'], 'exp' => $stats['expenses'], 'profit' => max(0, $stats['net_profit'])]];

        // ---- rings -----------------------------------------------------------------------------
        $upcomingPkgs = Package::where('is_active', true)->whereNotNull('total_seats')->where(fn ($q) => $q->whereNull('departure_date')->orWhere('departure_date', '>=', today()))->get();
        $seatsTotal = (int) $upcomingPkgs->sum('total_seats');
        $seatsBooked = (int) $upcomingPkgs->sum(fn ($p) => min($p->total_seats, $p->seats_booked));
        $rings = [
            'margin' => $stats['sales'] > 0 ? round($stats['gross_profit'] / $stats['sales'] * 100, 1) : 0,
            'collected' => $stats['sales'] > 0 ? round(min(100, $paidOnSales / $stats['sales'] * 100), 1) : 0,
            'cost' => $stats['sales'] > 0 ? round($stats['cost'] / $stats['sales'] * 100, 1) : 0,
            'seats' => $seatsTotal > 0 ? round($seatsBooked / $seatsTotal * 100, 1) : 0,
            'seats_booked' => $seatsBooked, 'seats_total' => $seatsTotal, 'paid_on_sales' => $paidOnSales,
        ];

        // ---- breakdowns ------------------------------------------------------------------------
        $byService = $counted()->selectRaw('service_type, COUNT(*) c, SUM(net_total) s')->groupBy('service_type')->orderByDesc('s')->get();
        $byStatus = Booking::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $byMethod = Payment::where('amount', '>', 0)->selectRaw('method, SUM(amount) s')->groupBy('method')->pluck('s', 'method');

        $overview = [
            ['Customers', Customer::count(), 'M17 20h5v-2a4 4 0 00-5-3.9M9 11a4 4 0 100-8 4 4 0 000 8zm-7 9v-2a4 4 0 014-4h6a4 4 0 014 4v2', route('customers.index')],
            ['Bookings', Booking::count(), 'M9 12h6M9 16h6M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z', route('bookings.index')],
            ['Active packages', Package::where('is_active', true)->count(), 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', route('packages.index')],
            ['Open leads', Lead::whereNotIn('status', ['won', 'lost'])->count(), 'M8 10h.01M12 10h.01M16 10h.01M21 12c0 4-4 7-9 7a10 10 0 01-4-.8L3 20l1.3-3.9A6.7 6.7 0 013 12c0-4 4-7 9-7s9 3 9 7z', route('leads.index')],
            ['Unpaid invoices', Invoice::whereIn('status', ['unpaid', 'partial'])->count(), 'M9 14l2 2 4-4M7 3h10a2 2 0 012 2v16l-3-2-2 2-2-2-2 2-2-2-3 2V5a2 2 0 012-2z', route('invoices.index', ['status' => 'unpaid'])],
            ['Travelers', Traveler::count(), 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', route('bookings.index')],
        ];

        $attention = [
            'overdue' => Booking::whereDate('payment_due_date', '<', today())->whereColumn('paid_total', '<', 'net_total')->whereIn('status', ['pending', 'confirmed'])->count(),
            'followups' => Lead::whereNotIn('status', ['won', 'lost'])->whereDate('next_follow_up', '<=', today())->count(),
            'visa' => Traveler::whereIn('visa_status', ['not_applied', 'applied'])->whereHas('booking', fn ($q) => $q->whereIn('status', ['confirmed', 'pending'])->whereBetween('travel_date', [today(), today()->addDays(30)]))->count(),
            'passports' => Traveler::with('booking')->whereNotNull('passport_expiry')->whereHas('booking', fn ($q) => $q->whereIn('status', ['confirmed', 'pending'])->whereBetween('travel_date', [today(), today()->addDays(60)]))->get()->filter(fn ($t) => $t->passport_expiry->lt($t->booking->travel_date->copy()->addMonths(6)))->count(),
        ];

        $topPackages = Package::withCount(['bookings as sold' => fn ($q) => $q->whereIn('status', ['confirmed', 'completed'])])
            ->withSum(['bookings as revenue' => fn ($q) => $q->whereIn('status', ['confirmed', 'completed'])], 'net_total')
            ->orderByDesc('sold')->limit(5)->get();

        $setup = ['packages' => Package::count(), 'customers' => Customer::count(), 'bookings' => Booking::count()];

        return view('dashboard', [
            'isNew' => $setup['bookings'] === 0, 'setup' => $setup,
            'stats' => $stats, 'deltas' => $deltas, 'series' => $series, 'chart' => $chart, 'rings' => $rings,
            'byService' => $byService, 'byStatus' => $byStatus, 'byMethod' => $byMethod, 'overview' => $overview, 'attention' => $attention,
            'recent' => Booking::with('customer')->latest()->limit(6)->get(),
            'dues' => Booking::with('customer')->whereIn('status', ['confirmed', 'completed', 'pending'])->whereColumn('paid_total', '<', 'net_total')->orderByDesc('net_total')->limit(6)->get(),
            'upcoming' => Booking::with('customer')->whereIn('status', ['pending', 'confirmed'])->whereBetween('travel_date', [today(), today()->addDays(30)])->orderBy('travel_date')->limit(6)->get(),
            'followups' => Lead::whereNotIn('status', ['won', 'lost'])->whereNotNull('next_follow_up')->orderBy('next_follow_up')->limit(5)->get(),
            'topPackages' => $topPackages,
        ]);
    }

    /** Sample numbers shown only until the first real booking is created. */
    protected function demo(): array
    {
        $c = fn ($n) => tap(new Customer(['name' => $n]), fn ($x) => $x->exists = false);
        $b = function (string $cust, string $no, string $title, float $net, float $paid, string $status, array $extra = []) use ($c) {
            $m = new Booking(array_merge(['booking_no' => $no, 'title' => $title, 'net_total' => $net, 'paid_total' => $paid, 'status' => $status, 'adults' => 2, 'children' => 0, 'infants' => 0], $extra));
            $m->setRelation('customer', $c($cust));

            return $m;
        };
        $pk = fn ($n, $sold, $rev) => tap(new Package(['name' => $n]), fn ($x) => $x->forceFill(['sold' => $sold, 'revenue' => $rev]));

        $stats = ['sales' => 9640000.0, 'cost' => 7580000.0, 'received' => 7410000.0, 'expenses' => 640000.0, 'month_sales' => 2310000.0, 'month_profit' => 486000.0, 'pending' => 4, 'confirmed' => 33];
        $stats['gross_profit'] = $stats['sales'] - $stats['cost'];
        $stats['net_profit'] = $stats['gross_profit'] - $stats['expenses'];
        $stats['receivable'] = 2230000.0;

        $series = [
            'labels' => collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i)->format('M'))->all(),
            'sales' => [820000, 1140000, 1010000, 1680000, 1960000, 2310000],
            'profit' => [168000, 236000, 205000, 352000, 418000, 486000],
            'received' => [690000, 980000, 940000, 1420000, 1640000, 1920000],
        ];
        $chart = $series + ['pl' => ['cost' => $stats['cost'], 'exp' => $stats['expenses'], 'profit' => $stats['net_profit']]];

        $d = fn (int $days) => now()->addDays($days)->toDateString();

        return [
            'demo' => true, 'isNew' => true, 'setup' => ['packages' => 0, 'customers' => 0, 'bookings' => 0],
            'stats' => $stats, 'series' => $series, 'chart' => $chart,
            'deltas' => ['sales' => 17.8, 'profit' => 16.3, 'received' => 17.1],
            'rings' => ['margin' => 21.4, 'collected' => 76.9, 'cost' => 78.6, 'seats' => 68.0, 'seats_booked' => 102, 'seats_total' => 150, 'paid_on_sales' => 7410000.0],
            'byService' => collect([
                (object) ['service_type' => 'umrah', 'c' => 21, 's' => 5120000], (object) ['service_type' => 'hajj', 'c' => 3, 's' => 3180000],
                (object) ['service_type' => 'tour', 'c' => 6, 's' => 860000], (object) ['service_type' => 'ticket', 'c' => 9, 's' => 480000],
            ]),
            'byStatus' => collect(['pending' => 4, 'confirmed' => 21, 'completed' => 12, 'cancelled' => 2]),
            'byMethod' => collect(['cash' => 3120000, 'bank' => 2640000, 'jazzcash' => 980000, 'easypaisa' => 670000]),
            'overview' => [
                ['Customers', 118, 'M17 20h5v-2a4 4 0 00-5-3.9M9 11a4 4 0 100-8 4 4 0 000 8zm-7 9v-2a4 4 0 014-4h6a4 4 0 014 4v2', route('customers.index')],
                ['Bookings', 39, 'M9 12h6M9 16h6M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z', route('bookings.index')],
                ['Active packages', 7, 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', route('packages.index')],
                ['Open leads', 14, 'M8 10h.01M12 10h.01M16 10h.01M21 12c0 4-4 7-9 7a10 10 0 01-4-.8L3 20l1.3-3.9A6.7 6.7 0 013 12c0-4 4-7 9-7s9 3 9 7z', route('leads.index')],
                ['Unpaid invoices', 11, 'M9 14l2 2 4-4M7 3h10a2 2 0 012 2v16l-3-2-2 2-2-2-2 2-2-2-3 2V5a2 2 0 012-2z', route('invoices.index')],
                ['Travelers', 164, 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', route('bookings.index')],
            ],
            'attention' => ['overdue' => 3, 'followups' => 5, 'visa' => 4, 'passports' => 2],
            'recent' => collect([
                $b('Ahmed Ali', 'BK-2026-0039', 'Umrah Economy 15 Days', 1240000, 620000, 'confirmed'),
                $b('Fatima Noor', 'BK-2026-0038', 'Hajj Special 2027', 3180000, 1500000, 'confirmed'),
                $b('Bilal Hussain', 'BK-2026-0037', 'Umrah Premium 21 Days', 1040000, 1040000, 'completed'),
                $b('Ayesha Khan', 'BK-2026-0036', 'Dubai Tour 5 Days', 360000, 0, 'pending'),
                $b('Usman Tariq', 'BK-2026-0035', 'Umrah Economy 15 Days', 900000, 450000, 'confirmed'),
                $b('Hina Malik', 'BK-2026-0034', 'Air Ticket Karachi–Jeddah', 168000, 168000, 'completed'),
            ]),
            'dues' => collect([
                $b('Fatima Noor', 'BK-2026-0038', 'Hajj Special 2027', 3180000, 1500000, 'confirmed', ['payment_due_date' => $d(-4)]),
                $b('Ahmed Ali', 'BK-2026-0039', 'Umrah Economy', 1240000, 620000, 'confirmed', ['payment_due_date' => $d(9)]),
                $b('Usman Tariq', 'BK-2026-0035', 'Umrah Economy', 900000, 450000, 'confirmed', ['payment_due_date' => $d(-1)]),
                $b('Ayesha Khan', 'BK-2026-0036', 'Dubai Tour', 360000, 0, 'pending', ['payment_due_date' => $d(14)]),
                $b('Zeeshan Raza', 'BK-2026-0033', 'Umrah Premium', 780000, 520000, 'confirmed', ['payment_due_date' => $d(6)]),
            ]),
            'upcoming' => collect([
                $b('Ahmed Ali', 'BK-2026-0039', 'Umrah Economy 15 Days', 1240000, 620000, 'confirmed', ['travel_date' => $d(4), 'adults' => 4]),
                $b('Imran Sheikh', 'BK-2026-0031', 'Umrah Premium 21 Days', 1560000, 1560000, 'confirmed', ['travel_date' => $d(9), 'adults' => 3]),
                $b('Usman Tariq', 'BK-2026-0035', 'Umrah Economy 15 Days', 900000, 450000, 'confirmed', ['travel_date' => $d(17), 'adults' => 2]),
                $b('Sana Mir', 'BK-2026-0030', 'Dubai Tour 5 Days', 360000, 360000, 'confirmed', ['travel_date' => $d(24), 'adults' => 2]),
            ]),
            'followups' => collect([
                tap(new Lead(['name' => 'Kamran Butt', 'interest' => 'umrah', 'persons' => 4, 'next_follow_up' => $d(0), 'status' => 'follow_up']), fn ($x) => $x->exists = false),
                tap(new Lead(['name' => 'Sana Mir', 'interest' => 'hajj', 'persons' => 2, 'next_follow_up' => $d(2), 'status' => 'contacted']), fn ($x) => $x->exists = false),
                tap(new Lead(['name' => 'Rizwan Ahmed', 'interest' => 'umrah', 'persons' => 6, 'next_follow_up' => $d(5), 'status' => 'new']), fn ($x) => $x->exists = false),
            ]),
            'topPackages' => collect([
                $pk('Hajj Special 2027', 3, 3180000), $pk('Umrah Premium 21 Days', 8, 4160000), $pk('Umrah Economy 15 Days', 13, 3900000),
                $pk('Dubai Tour 5 Days', 6, 860000), $pk('Air Ticket Karachi–Jeddah', 9, 480000),
            ])->sortByDesc('revenue')->values(),
        ];
    }
}
