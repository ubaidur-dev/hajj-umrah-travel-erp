<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Package;
use App\Support\Search;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public const TYPES = ['umrah' => 'Umrah', 'hajj' => 'Hajj', 'tour' => 'Tour', 'ticket' => 'Air Ticket', 'visa' => 'Visa', 'hotel' => 'Hotel Booking', 'other' => 'Other'];

    protected function rules(): array
    {
        return [
            'code' => 'nullable|string|max:30', 'name' => 'required|string|max:200',
            'type' => 'required|in:'.implode(',', array_keys(self::TYPES)),
            'description' => 'nullable|string', 'duration_days' => 'nullable|integer|min:1',
            'departure_date' => 'nullable|date', 'return_date' => 'nullable|date|after_or_equal:departure_date',
            'makkah_nights' => 'nullable|integer|min:0|max:60', 'madinah_nights' => 'nullable|integer|min:0|max:60', 'departure_city' => 'nullable|string|max:80', 'flight_no' => 'nullable|string|max:40', 'transport' => 'nullable|string|max:100', 'meals' => 'nullable|string|max:100',
            'makkah_hotel' => 'nullable|string|max:150', 'madinah_hotel' => 'nullable|string|max:150', 'airline' => 'nullable|string|max:100',
            'inclusions' => 'nullable|string', 'exclusions' => 'nullable|string',
            'sale_price' => 'required|numeric|min:0', 'child_price' => 'nullable|numeric|min:0', 'infant_price' => 'nullable|numeric|min:0',
            'total_seats' => 'nullable|integer|min:1',
            'costs.*.title' => 'nullable|string|max:150', 'costs.*.amount' => 'nullable|numeric|min:0',
            'images.*' => 'nullable|image|max:10240',
        ];
    }

    public function index(Request $r)
    {
        $q = Package::query()->withCount(['bookings as sold' => fn ($q) => $q->whereIn('status', ['confirmed', 'completed'])]);
        Search::apply($q, $r->q, ['name', 'code', 'airline', 'flight_no', 'makkah_hotel', 'madinah_hotel', 'departure_city', 'description'], [], ['name', 'code']);
        if ($t = $r->type) {
            $q->where('type', $t);
        }

        return view('packages.index', ['packages' => $q->latest('id')->paginate(12)->withQueryString(), 'types' => self::TYPES]);
    }

    public function create() { return view('packages.form', ['package' => new Package(['is_active' => true]), 'types' => self::TYPES]); }

    public function store(Request $r)
    {
        $data = $r->validate($this->rules());
        $p = Package::create($this->clean($data) + ['code' => ($data['code'] ?? null) ?: 'PKG-'.strtoupper(substr(uniqid(), -6))]);
        $this->syncCosts($p, $data['costs'] ?? []);
        $this->saveImages($p, $r);
        ActivityLog::record('create', "Package created: {$p->name}", $p);

        return redirect()->route('packages.show', $p)->with('ok', 'Package saved.');
    }

    public function show(Package $package)
    {
        $package->load(['costs', 'bookings.customer']);

        return view('packages.show', ['package' => $package, 'types' => self::TYPES]);
    }

    /** Printable passenger list (manifest) for one departure; ?csv=1 downloads it for Excel. */
    public function manifest(Request $r, Package $package)
    {
        $travelers = \App\Models\Traveler::with('booking.customer')
            ->whereHas('booking', fn ($q) => $q->where('package_id', $package->id)->where('status', '!=', 'cancelled'))
            ->orderBy('booking_id')->orderBy('id')->get();

        if ($r->boolean('csv')) {
            return response()->streamDownload(function () use ($travelers) {
                $o = fopen('php://output', 'w');
                fputcsv($o, ['#', 'Name', 'Type', 'Passport', 'Passport expiry', 'DOB', 'Visa status', 'Visa no', 'Ticket no', 'Booking', 'Contact']);
                foreach ($travelers as $i => $t) {
                    fputcsv($o, [$i + 1, $t->name, $t->type, $t->passport_no, $t->passport_expiry?->toDateString(), $t->dob?->toDateString(), \App\Models\Traveler::VISA[$t->visa_status] ?? $t->visa_status, $t->visa_no, $t->ticket_no, $t->booking->booking_no, $t->booking->customer->phone]);
                }
                fclose($o);
            }, "manifest-{$package->code}.csv", ['Content-Type' => 'text/csv']);
        }

        return view('packages.manifest', compact('package', 'travelers'));
    }

    public function edit(Package $package)
    {
        $package->load('costs');

        return view('packages.form', ['package' => $package, 'types' => self::TYPES]);
    }

    public function update(Request $r, Package $package)
    {
        $data = $r->validate($this->rules());
        $package->update($this->clean($data) + ['is_active' => $r->boolean('is_active')]);
        if ($data['code'] ?? null) {
            $package->update(['code' => $data['code']]);
        }
        $this->syncCosts($package, $data['costs'] ?? []);
        $this->saveImages($package, $r);

        return redirect()->route('packages.show', $package)->with('ok', 'Package updated.');
    }

    public function destroy(Package $package)
    {
        $package->delete();

        return redirect()->route('packages.index')->with('ok', 'Package deleted (existing bookings are safe).');
    }

    protected function clean(array $d): array
    {
        unset($d['costs'], $d['images'], $d['code']);
        $d['child_price'] = $d['child_price'] ?? 0;
        $d['infant_price'] = $d['infant_price'] ?? 0;

        return $d;
    }

    protected function syncCosts(Package $p, array $costs): void
    {
        $p->costs()->delete();
        foreach ($costs as $c) {
            if (filled($c['title'] ?? null) && filled($c['amount'] ?? null)) {
                $p->costs()->create(['title' => $c['title'], 'amount' => $c['amount']]);
            }
        }
        $p->recalcCost();
    }

    protected function saveImages(Package $p, Request $r): void
    {
        foreach ($r->file('images', []) as $img) {
            $p->addMedia($img)->toMediaCollection('images');
        }
    }
}
