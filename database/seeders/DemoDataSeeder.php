<?php

namespace Database\Seeders;

use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Lead;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Traveler;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Realistic Pakistani Hajj / Umrah demo data (prices in PKR, 2026-27 season) for every part of the system.
 * Run:  php artisan db:seed --class=DemoDataSeeder
 * Reset: php artisan migrate:fresh --seed   (removes the demo data again)
 */
class DemoDataSeeder extends Seeder
{
    protected array $methods = ['bank', 'cash', 'jazzcash', 'raast', 'easypaisa', 'bank', 'cash'];

    public function run(): void
    {
        if (Booking::count() > 0 || Customer::count() > 0) {
            $this->command?->warn('The database already has customers or bookings. Run "php artisan migrate:fresh --seed" first if you want a clean demo.');

            return;
        }
        mt_srand(2026); // same demo data every time
        auth()->loginUsingId(1);

        $packages = $this->packages();
        $customers = $this->customers();
        $this->leads();
        $this->bookings($packages, $customers);
        $this->expenses();

        $this->command?->info('Demo data added: '.Package::count().' packages, '.Customer::count().' customers, '.Booking::count().' bookings, '.Payment::count().' payments, '.Traveler::count().' travelers, '.Lead::count().' leads, '.Expense::count().' expenses.');
    }

    // ------------------------------------------------------------------ packages
    protected function packages(): array
    {
        $umrahInc = "Return air ticket (economy)\nUmrah visa & insurance\nMakkah & Madinah hotel (shared rooms)\nAC transport: airport, hotels & ziyarat\nDaily breakfast\nGuide & Umrah assistance";
        $hajjInc = "Return air ticket\nHajj visa & mina/arafat arrangements\nMakkah hotel near Haram\nMadinah hotel\nAll meals\nTransport & maktab\nExperienced Hajj guide (Mu'allim)";
        $exc = "Personal expenses & shopping\nExtra luggage\nLaundry\nTravel insurance upgrade";

        // name, type, code, sale, cost, depart, days, makkahN, madinahN, makkah hotel, madinah hotel, airline, flight, seats, active, inclusions
        $rows = [
            ['Umrah Economy 14 Days – Nov 2026', 'umrah', 'UMR-1126', 289000, 241000, '2026-11-12', 14, 8, 5, 'Anjum Hotel Makkah (850m)', 'Al Eiman Taibah (300m)', 'Saudia', 'SV 705', 45, true, $umrahInc],
            ['Umrah Standard 15 Days – Dec 2026', 'umrah', 'UMR-1226', 345000, 289000, '2026-12-05', 15, 8, 6, 'Pullman ZamZam (200m)', 'Millennium Taiba (150m)', 'Saudia', 'SV 705', 40, true, $umrahInc],
            ['Umrah Premium 21 Days – Dec 2026', 'umrah', 'UMR-1226P', 545000, 455000, '2026-12-22', 21, 12, 8, 'Hilton Suites Makkah (Haram view)', 'Dar Al Taqwa (100m)', 'Saudia', 'SV 701', 30, true, $umrahInc."\nFive-star buffet meals\nPrivate transport option"],
            ['Umrah Winter Special 10 Days – Jan 2027', 'umrah', 'UMR-0127', 265000, 221000, '2027-01-10', 10, 6, 3, 'Dar Al Eiman Royal (400m)', 'Dallah Taibah (350m)', 'PIA', 'PK 741', 50, true, $umrahInc],
            ['Ramadan Umrah – Last 10 Days 2027', 'umrah', 'RAM-0227', 780000, 655000, '2027-02-26', 12, 8, 4, 'Swissotel Makkah (Haram)', 'Anwar Al Madinah Movenpick', 'Saudia', 'SV 707', 24, true, $umrahInc."\nSuhoor & iftar included\nItikaf arrangements"],
            ['Hajj 2027 – Special Package', 'hajj', 'HAJ-27S', 1350000, 1140000, '2027-05-04', 40, 24, 12, 'Fairmont Clock Tower (Haram)', 'Pullman Zamzam Madinah', 'Saudia', 'SV 3715', 36, true, $hajjInc],
            ['Hajj 2027 – Economy Package', 'hajj', 'HAJ-27E', 1150000, 985000, '2027-05-06', 40, 25, 11, 'Aziziyah Building (shuttle)', 'Markazia Madinah', 'PIA', 'PK 3141', 60, true, $hajjInc],
            ['Dubai Tour 5N/6D', 'tour', 'DXB-1126', 165000, 128000, '2026-11-20', 6, null, null, 'Rove Downtown Dubai', null, 'Emirates', 'EK 601', 20, true, "Return air ticket\n5 nights hotel with breakfast\nDesert safari & dinner\nDubai city tour\nUAE visa"],
            ['Istanbul & Cappadocia Tour 7D', 'tour', 'IST-0127', 395000, 320000, '2027-01-24', 7, null, null, 'Hotel Barin Istanbul', null, 'Turkish Airlines', 'TK 709', 18, true, "Return air ticket\nHotel with breakfast\nBosphorus cruise\nHot-air balloon (optional)\nTurkey e-visa"],
            ['Air Ticket Karachi – Jeddah (Return)', 'ticket', 'TKT-KHI-JED', 185000, 162000, '2026-11-14', 21, null, null, null, null, 'Saudia', 'SV 705', null, true, "Return economy ticket\n30 kg baggage"],
            ['Saudi Umrah Visa + Insurance', 'visa', 'VISA-UMR', 48000, 36000, null, null, null, null, null, null, null, null, null, true, "Umrah e-visa\nMedical insurance\nVisa processing & follow-up"],
            ['Umrah Economy 15 Days – Sep 2026', 'umrah', 'UMR-0926', 279000, 235000, '2026-09-18', 15, 9, 5, 'Anjum Hotel Makkah (850m)', 'Al Eiman Taibah (300m)', 'Saudia', 'SV 705', 45, false, $umrahInc],
            ['Umrah Standard 15 Days – Oct 2026', 'umrah', 'UMR-1026', 329000, 276000, '2026-10-02', 15, 8, 6, 'Pullman ZamZam (200m)', 'Millennium Taiba (150m)', 'PIA', 'PK 741', 40, false, $umrahInc],
        ];

        $out = [];
        foreach ($rows as $i => [$name, $type, $code, $sale, $cost, $dep, $days, $mn, $dn, $mh, $dh, $air, $fl, $seats, $active, $inc]) {
            $p = Package::create([
                'code' => $code, 'name' => $name, 'type' => $type, 'duration_days' => $days, 'makkah_nights' => $mn, 'madinah_nights' => $dn,
                'departure_date' => $dep, 'return_date' => $dep && $days ? Carbon::parse($dep)->addDays($days)->toDateString() : null,
                'makkah_hotel' => $mh, 'madinah_hotel' => $dh, 'airline' => $air, 'flight_no' => $fl, 'departure_city' => $air ? 'Karachi' : null,
                'transport' => in_array($type, ['umrah', 'hajj']) ? 'AC bus / coaster' : null, 'meals' => in_array($type, ['umrah', 'hajj']) ? ($type === 'hajj' ? 'All meals' : 'Breakfast & dinner') : ($type === 'tour' ? 'Breakfast' : null),
                'description' => match ($type) {
                    'umrah' => 'Complete Umrah package with Makkah and Madinah stay, visa, flight and ziyarat.',
                    'hajj' => 'Fully guided Hajj package with Mu\'allim, maktab and all arrangements.',
                    'tour' => 'Guided holiday package with flights, hotel and sightseeing.',
                    'ticket' => 'Return air ticket booking with baggage.',
                    default => 'Visa processing service.',
                },
                'inclusions' => $inc, 'exclusions' => in_array($type, ['umrah', 'hajj', 'tour']) ? $exc : null,
                'sale_price' => $sale, 'child_price' => round($type === 'visa' ? $sale : $sale * 0.88, -3), 'infant_price' => $type === 'visa' ? 0 : round($sale * 0.12, -3),
                'total_seats' => $seats, 'is_active' => $active,
            ]);
            // cost breakdown per adult (adds up exactly to the cost)
            $split = match ($type) {
                'umrah', 'hajj' => ['Air ticket' => .36, 'Makkah hotel' => .26, 'Madinah hotel' => .14, 'Visa & insurance' => .10, 'Transport & ziyarat' => .09, 'Meals & services' => .05],
                'tour' => ['Air ticket' => .45, 'Hotel' => .30, 'Visa' => .08, 'Tours & transport' => .17],
                'ticket' => ['Airline fare & taxes' => 1],
                default => ['Visa fee' => .7, 'Insurance' => .3],
            };
            $left = $cost;
            $n = count($split);
            $k = 0;
            foreach ($split as $title => $share) {
                $amt = ++$k === $n ? $left : round($cost * $share, -2);
                $left -= $amt;
                $p->costs()->create(['title' => $title, 'amount' => $amt]);
            }
            $p->recalcCost();
            $this->poster($p, $type, $i);
            $out[$i] = $p;
        }

        return $out;
    }

    /** Creates a clean branded banner image for the package (stored with Spatie Media Library). */
    protected function poster(Package $p, string $type, int $i): void
    {
        if (! function_exists('imagecreatetruecolor') || ! is_file(storage_path('fonts/Inter-700.ttf'))) {
            return;
        }
        [$c1, $c2] = match ($type) {
            'umrah' => [[12, 58, 28], [47, 158, 68]], 'hajj' => [[60, 40, 10], [201, 162, 39]],
            'tour' => [[7, 60, 100], [56, 189, 248]], 'ticket' => [[30, 41, 59], [100, 116, 139]], default => [[76, 29, 149], [167, 139, 250]],
        };
        $w = 900; $h = 400; $im = imagecreatetruecolor($w, $h);
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            imagefilledrectangle($im, 0, $y, $w, $y, imagecolorallocate($im, (int) ($c1[0] + ($c2[0] - $c1[0]) * $t), (int) ($c1[1] + ($c2[1] - $c1[1]) * $t), (int) ($c1[2] + ($c2[2] - $c1[2]) * $t)));
        }
        imagealphablending($im, true);
        $soft = imagecolorallocatealpha($im, 255, 255, 255, 105);
        imagefilledellipse($im, $w - 90, 70, 300, 300, $soft);
        imagefilledellipse($im, 80, $h - 10, 340, 340, $soft);
        $white = imagecolorallocate($im, 255, 255, 255);
        $gold = imagecolorallocate($im, 245, 211, 122);
        $font = storage_path('fonts/Inter-700.ttf');
        $label = strtoupper(['umrah' => 'Umrah', 'hajj' => 'Hajj', 'tour' => 'Holiday tour', 'ticket' => 'Air ticket', 'visa' => 'Visa service'][$type]);
        imagettftext($im, 17, 0, 56, 84, $gold, $font, $label.'  ·  GULSHAN-E-HARMAIN');
        $words = explode(' ', $p->name); $lines = ['']; $li = 0;
        foreach ($words as $wd) {
            if (strlen($lines[$li].' '.$wd) > 24 && $lines[$li] !== '') { $lines[++$li] = ''; }
            $lines[$li] = trim($lines[$li].' '.$wd);
        }
        $y = 205 - (count($lines) - 1) * 26;
        foreach ($lines as $ln) { imagettftext($im, 38, 0, 56, $y, $white, $font, $ln); $y += 56; }
        if ($p->departure_date) {
            imagettftext($im, 19, 0, 56, $h - 78, $white, $font, 'Departure '.$p->departure_date->format('d M Y').($p->duration_days ? '  ·  '.$p->duration_days.' days' : ''));
        }
        $tmp = sys_get_temp_dir().'/pkg-'.$i.'.jpg';
        imagejpeg($im, $tmp, 88);
        imagedestroy($im);
        $p->addMedia($tmp)->usingFileName('package-'.$p->code.'.jpg')->toMediaCollection('images');
    }

    // ------------------------------------------------------------------ customers
    protected function customers(): array
    {
        // name, gender, city
        $list = [
            ['Muhammad Tahir Mehmood', 'm', 'Karachi'], ['Abdul Rehman Qureshi', 'm', 'Karachi'], ['Syed Hassan Raza Naqvi', 'm', 'Lahore'],
            ['Fatima Zahra', 'f', 'Karachi'], ['Ayesha Siddiqui', 'f', 'Karachi'], ['Muhammad Usman Ghani', 'm', 'Lahore'],
            ['Hafiz Bilal Ahmed', 'm', 'Faisalabad'], ['Khalid Mehmood Butt', 'm', 'Lahore'], ['Shabana Kausar', 'f', 'Rawalpindi'],
            ['Nasreen Akhtar', 'f', 'Islamabad'], ['Muhammad Imran Sheikh', 'm', 'Karachi'], ['Rukhsana Parveen', 'f', 'Multan'],
            ['Zubair Ahmed Khan', 'm', 'Peshawar'], ['Saima Noreen', 'f', 'Hyderabad'], ['Tariq Mahmood Chaudhry', 'm', 'Sialkot'],
            ['Mubashir Hussain Shah', 'm', 'Multan'], ['Samina Yasmin', 'f', 'Karachi'], ['Rizwan ul Haq', 'm', 'Islamabad'],
            ['Abdul Qadir Memon', 'm', 'Hyderabad'], ['Sajida Bibi', 'f', 'Faisalabad'], ['Muhammad Ashraf Arain', 'm', 'Lahore'],
            ['Haji Abdul Ghafoor', 'm', 'Quetta'], ['Nadeem Iqbal Malik', 'm', 'Rawalpindi'], ['Kiran Shahzadi', 'f', 'Karachi'],
        ];
        $cnic = ['Karachi' => '42101', 'Lahore' => '35202', 'Islamabad' => '61101', 'Rawalpindi' => '37405', 'Faisalabad' => '33100', 'Multan' => '36302', 'Peshawar' => '17301', 'Hyderabad' => '41303', 'Sialkot' => '34603', 'Quetta' => '54400'];
        $area = ['Karachi' => ['Gulshan-e-Iqbal Block 13', 'North Nazimabad Block H', 'Gulistan-e-Jauhar Block 7', 'Korangi Sector 33', 'Malir Cantt'], 'Lahore' => ['Johar Town', 'Model Town', 'Gulberg III', 'Iqbal Town', 'Samanabad'], 'Islamabad' => ['G-11/3', 'F-10/2', 'I-8/4'], 'Rawalpindi' => ['Satellite Town', 'Chaklala Scheme 3', 'Bahria Town Ph-4'], 'Faisalabad' => ['Madina Town', 'Peoples Colony'], 'Multan' => ['Gulgasht Colony', 'Shah Rukn-e-Alam Colony'], 'Peshawar' => ['Hayatabad Phase 3', 'University Town'], 'Hyderabad' => ['Latifabad Unit 7', 'Qasimabad'], 'Sialkot' => ['Cantt', 'Paris Road'], 'Quetta' => ['Satellite Town', 'Jinnah Town']];
        $pre = ['0300', '0301', '0302', '0303', '0304', '0305', '0306', '0307', '0311', '0312', '0313', '0314', '0315', '0321', '0322', '0323', '0331', '0332', '0333', '0334', '0335', '0336', '0340', '0341', '0342', '0343', '0344', '0345', '0346', '0347'];
        $doms = ['gmail.com', 'gmail.com', 'gmail.com', 'yahoo.com', 'hotmail.com'];

        $out = [];
        foreach ($list as $i => [$name, $g, $city]) {
            $parts = explode(' ', strtolower($name));
            $phone = $pre[array_rand($pre)].'-'.mt_rand(1000000, 9999999);
            $wa = mt_rand(0, 3) === 0 ? $pre[array_rand($pre)].'-'.mt_rand(1000000, 9999999) : null;
            $out[$i] = Customer::create([
                'name' => $name, 'phone' => $phone, 'whatsapp' => $wa,
                'email' => mt_rand(0, 9) < 6 ? preg_replace('/[^a-z.]/', '', $parts[0].'.'.end($parts)).mt_rand(1, 99).'@'.$doms[array_rand($doms)] : null,
                'cnic' => $cnic[$city].'-'.mt_rand(1000000, 9999999).'-'.($g === 'm' ? [1, 3, 5, 7, 9][mt_rand(0, 4)] : [2, 4, 6, 8][mt_rand(0, 3)]),
                'passport_no' => $this->passport(), 'city' => $city,
                'address' => 'House # '.mt_rand(1, 480).', Street '.mt_rand(1, 30).', '.$area[$city][array_rand($area[$city])].', '.$city,
                'notes' => $i % 7 === 0 ? 'Repeat customer — prefers hotels close to the Haram.' : null,
                'created_at' => now()->subDays(mt_rand(10, 220)), 'updated_at' => now(),
            ]);
        }
        $out['_g'] = array_column($list, 1);

        return $out;
    }

    protected function passport(): string
    {
        $l = ['AA', 'AB', 'AK', 'BM', 'BQ', 'CD', 'CR', 'DH', 'EF', 'FA', 'GH', 'HK', 'JK', 'KL', 'LP'];

        return $l[array_rand($l)].mt_rand(1000000, 9999999);
    }

    // ------------------------------------------------------------------ leads
    protected function leads(): void
    {
        $rows = [
            ['Adeel Shafique', '0321-4455667', 'umrah', 4, '2026-12-15', 'new', 0], ['Sobia Rashid', '0333-1122334', 'hajj', 2, '2027-05-05', 'follow_up', 0],
            ['Majid Hameed', '0300-7788990', 'umrah', 6, '2027-02-26', 'contacted', 2], ['Rubina Kanwal', '0345-9988776', 'umrah', 2, '2026-11-25', 'follow_up', -1],
            ['Zahid Mehmood', '0312-3344556', 'tour', 5, '2027-01-24', 'new', 3], ['Hira Fatima', '0322-6677889', 'ticket', 1, '2026-11-14', 'contacted', 1],
            ['Salman Farooq', '0301-2233445', 'hajj', 3, '2027-05-04', 'follow_up', 0], ['Naveed Anjum', '0334-5566778', 'umrah', 3, '2027-01-10', 'new', 4],
            ['Bushra Aslam', '0343-1234987', 'umrah', 2, '2026-12-05', 'contacted', 5], ['Waqas Ali Raza', '0302-8899001', 'visa', 4, null, 'new', -2],
            ['Farhan Saeed', '0315-4321098', 'umrah', 5, '2027-02-20', 'follow_up', 2], ['Mehwish Tariq', '0346-7654321', 'tour', 2, '2026-11-20', 'contacted', 6],
            ['Shahid Afridi Khan', '0300-1357924', 'hajj', 2, '2027-05-06', 'new', 7], ['Lubna Hassan', '0332-2468013', 'umrah', 4, '2026-12-22', 'follow_up', 1],
            ['Irfan Yousuf', '0311-9876501', 'umrah', 8, '2027-03-01', 'contacted', 3], ['Amna Bibi', '0323-1209384', 'hotel', 2, '2026-12-01', 'new', 8],
            ['Qasim Raza', '0304-5647382', 'umrah', 2, '2026-11-12', 'won', null], ['Tahira Perveen', '0341-3216549', 'hajj', 2, '2027-05-04', 'won', null],
            ['Danish Iqbal', '0307-9081726', 'tour', 3, '2026-12-10', 'lost', null], ['Faisal Mushtaq', '0344-1928374', 'ticket', 2, '2026-12-20', 'lost', null],
            ['Ghazala Khan', '0336-5544332', 'umrah', 3, '2027-01-18', 'lost', null], ['Hamid Nawaz', '0313-6677112', 'umrah', 2, '2026-12-28', 'follow_up', -4],
        ];
        foreach ($rows as [$n, $ph, $int, $pp, $exp, $st, $off]) {
            Lead::create([
                'name' => $n, 'phone' => $ph, 'interest' => $int, 'persons' => $pp, 'expected_travel' => $exp, 'status' => $st,
                'next_follow_up' => $off === null ? null : today()->addDays($off)->toDateString(), 'created_by' => 1,
                'notes' => ['Asked for family package, wants Haram-view hotel.', 'Will decide after talking to family.', 'Wants to pay in installments.', 'Referred by an existing customer.', null][mt_rand(0, 4)],
                'created_at' => now()->subDays(mt_rand(1, 40)),
            ]);
        }
    }

    // ------------------------------------------------------------------ bookings
    protected function bookings(array $pk, array $cu): void
    {
        $g = $cu['_g'];
        // customer, package, adults, children, infants, status, days ago, payment plan, extras, discount, overdue-days (due date in the past)
        $specs = [
            [0, 11, 2, 0, 0, 'completed', 152, 'full', [], 0, null], [1, 11, 4, 1, 0, 'completed', 140, 'full', ['ziyarat'], 10000, null],
            [2, 11, 1, 0, 0, 'completed', 127, 'full', [], 0, null], [3, 11, 3, 0, 1, 'completed', 116, 'full', [], 5000, null],
            [4, 12, 2, 0, 0, 'completed', 101, 'full', [], 0, null], [5, 12, 6, 2, 0, 'completed', 88, 'full', ['ziyarat'], 20000, null],
            [6, 12, 2, 1, 0, 'completed', 76, 'full', [], 0, null],
            [7, 0, 2, 0, 0, 'confirmed', 52, 'most', [], 0, null], [8, 0, 3, 1, 0, 'confirmed', 47, 'full', [], 10000, null],
            [9, 0, 4, 0, 1, 'confirmed', 41, 'adv', [], 0, 6], [10, 1, 2, 0, 0, 'confirmed', 44, 'most', ['visa_fast'], 0, null],
            [11, 1, 5, 2, 0, 'confirmed', 38, 'adv', [], 15000, null], [12, 2, 2, 0, 0, 'confirmed', 35, 'most', [], 0, null],
            [13, 2, 3, 0, 0, 'confirmed', 31, 'adv', ['ziyarat'], 0, 2], [14, 3, 2, 0, 0, 'confirmed', 27, 'adv', [], 0, 9],
            [15, 4, 2, 0, 0, 'confirmed', 24, 'adv', [], 0, null], [16, 4, 4, 0, 0, 'confirmed', 21, 'adv', [], 20000, null],
            [17, 5, 2, 0, 0, 'confirmed', 33, 'adv', [], 0, null], [18, 5, 1, 0, 0, 'confirmed', 29, 'adv', [], 0, null],
            [19, 6, 2, 0, 0, 'confirmed', 22, 'adv', [], 0, null], [20, 7, 2, 2, 0, 'confirmed', 18, 'most', [], 0, 1],
            [21, 8, 2, 0, 0, 'confirmed', 12, 'adv', [], 0, null], [22, 9, 3, 0, 0, 'confirmed', 9, 'full', [], 0, null],
            [23, 1, 2, 0, 0, 'pending', 6, 'none', [], 0, null], [0, 2, 4, 1, 0, 'pending', 5, 'adv', [], 0, null],
            [12, 4, 3, 0, 0, 'pending', 3, 'none', [], 0, null], [15, 6, 2, 0, 0, 'pending', 2, 'none', [], 0, null],
            [18, 10, 2, 0, 0, 'pending', 1, 'none', [], 0, null],
            [20, 0, 2, 0, 0, 'cancelled', 58, 'refund', [], 0, null], [21, 3, 1, 0, 0, 'cancelled', 40, 'cancelled', [], 0, null],
        ];
        usort($specs, fn ($a, $b) => $b[6] <=> $a[6]); // oldest first => booking numbers follow the calendar

        $maleFirst = ['Muhammad', 'Ahmed', 'Ali', 'Hamza', 'Bilal', 'Usman', 'Zain', 'Hassan', 'Abdullah', 'Salman', 'Talha', 'Faizan'];
        $femaleFirst = ['Ayesha', 'Fatima', 'Zainab', 'Maryam', 'Khadija', 'Hira', 'Sana', 'Amna', 'Iqra', 'Mahnoor'];
        $payCounter = 0;
        $controller = app(BookingController::class);

        foreach ($specs as [$ci, $pi, $ad, $ch, $in, $status, $ago, $plan, $extras, $disc, $overdue]) {
            $c = $cu[$ci]; $p = $pk[$pi];
            $date = today()->subDays($ago);
            $due = $overdue !== null ? today()->subDays($overdue) : ($p->departure_date ? $p->departure_date->copy()->subDays(15) : $date->copy()->addDays(14));
            if ($status === 'completed') { $due = $date->copy()->addDays(30); }

            $b = Booking::create([
                'booking_no' => Booking::newNumber(), 'customer_id' => $c->id, 'package_id' => $p->id, 'service_type' => $p->type, 'title' => $p->name,
                'adults' => $ad, 'children' => $ch, 'infants' => $in, 'booking_date' => $date->toDateString(),
                'travel_date' => $p->departure_date?->toDateString(), 'return_date' => $p->return_date?->toDateString(),
                'payment_due_date' => $due->toDateString(), 'discount' => $disc, 'status' => 'pending', 'created_by' => 1,
                'notes' => ['cancelled' => 'Customer cancelled — family issue.'][$status] ?? ($disc ? 'Special discount approved by management.' : null),
                'created_at' => $date->copy()->setTime(mt_rand(10, 18), mt_rand(0, 59)), 'updated_at' => $date,
            ]);
            $b->load('package');
            $b->fillItemsFromPackage();
            foreach ($extras as $x) {
                $b->items()->create($x === 'ziyarat'
                    ? ['description' => 'Extra ziyarat tour (Makkah & Madinah)', 'quantity' => $ad + $ch, 'unit_price' => 12000, 'unit_cost' => 8000]
                    : ['description' => 'Fast-track visa processing', 'quantity' => $ad, 'unit_price' => 9000, 'unit_cost' => 5500]);
            }
            $b->recalc();

            // confirm → invoice, exactly like the real "Confirm booking" button
            if (in_array($status, ['confirmed', 'completed'])) {
                $b->update(['status' => $status, 'confirmed_at' => $date->copy()->addDay()]);
                $inv = $controller->makeInvoice($b);
                $inv->update(['issue_date' => $date->copy()->addDay()->toDateString()]);
            } else {
                $b->update(['status' => $status]);
            }
            $b->refresh();

            // payments
            $net = (float) $b->net_total;
            $steps = match ($plan) {
                'full' => [[.4, 0], [.35, 12], [.25, 25]], 'most' => [[.4, 0], [.4, 14]], 'adv' => [[mt_rand(0, 1) ? .3 : .45, 0]],
                'refund' => [[.5, 0]], default => [],
            };
            $paid = 0.0;
            foreach ($steps as $k => [$frac, $off]) {
                $amt = $plan === 'full' && $k === count($steps) - 1 ? $net - $paid : round($net * $frac, -3);
                $when = $date->copy()->addDays($off);
                if ($when->gt(today())) { $when = today(); }
                $m = $this->methods[$payCounter++ % count($this->methods)];
                Payment::create([
                    'receipt_no' => Payment::newNumber(), 'booking_id' => $b->id, 'amount' => $amt, 'method' => $m, 'paid_at' => $when->toDateString(),
                    'reference' => in_array($m, ['bank', 'raast', 'jazzcash', 'easypaisa']) ? strtoupper(substr(md5($b->id.$k), 0, 10)) : null,
                    'note' => $k === 0 ? 'Advance' : 'Installment '.($k + 1), 'received_by' => 1, 'created_at' => $when, 'updated_at' => $when,
                ]);
                $paid += $amt;
            }
            if ($plan === 'refund') {
                Payment::create(['receipt_no' => Payment::newNumber(), 'booking_id' => $b->id, 'kind' => 'refund', 'amount' => -round($paid * 0.8, -3),
                    'method' => 'bank', 'paid_at' => $date->copy()->addDays(9)->toDateString(), 'note' => 'Refund after cancellation (20% retained)', 'received_by' => 1]);
            }
            if ($status === 'cancelled') { $b->invoices()->update(['status' => 'cancelled']); }

            // travelers
            if ($status !== 'cancelled') {
                $this->travelers($b, $c, $g[$ci] ?? 'm', $p, $status, $maleFirst, $femaleFirst);
            }
        }
    }

    protected function travelers(Booking $b, Customer $c, string $gender, Package $p, string $status, array $mf, array $ff): void
    {
        $surname = collect(explode(' ', $c->name))->last();
        $total = $b->adults + $b->children + $b->infants;
        if ($status === 'pending' && mt_rand(0, 1)) { return; } // some pending bookings have no traveler list yet

        $days = $p->departure_date ? today()->diffInDays($p->departure_date, false) : 999;
        $nextTrip = $days >= 0 && $days <= 30;
        $visa = static fn (string $st) => $st;
        for ($i = 0; $i < $total; $i++) {
            $type = $i < $b->adults ? 'adult' : ($i < $b->adults + $b->children ? 'child' : 'infant');
            if ($i === 0) { $name = $c->name; $sex = $gender; }
            elseif ($i === 1 && $type === 'adult') { $sex = $gender === 'm' ? 'f' : 'm'; $name = ($sex === 'f' ? $ff : $mf)[array_rand($sex === 'f' ? $ff : $mf)].' '.$surname; }
            else { $sex = mt_rand(0, 1) ? 'm' : 'f'; $name = ($sex === 'f' ? $ff : $mf)[array_rand($sex === 'f' ? $ff : $mf)].' '.$surname; }

            $vs = $status === 'completed' ? 'issued' : ($status === 'pending' ? 'not_applied'
                : ($nextTrip ? ['issued', 'approved', 'approved', 'applied'][mt_rand(0, 3)] : ($days <= 70 ? ['applied', 'approved', 'applied'][mt_rand(0, 2)] : ['not_applied', 'applied'][mt_rand(0, 1)])));
            $expiry = ($i === 1 && $status === 'confirmed' && $b->id % 5 === 0) ? now()->addMonths(2)->toDateString() // triggers the "under 6 months" warning
                : now()->addMonths(mt_rand(14, 90))->toDateString();
            Traveler::create([
                'booking_id' => $b->id, 'name' => $name, 'type' => $type, 'gender' => $sex === 'm' ? 'Male' : 'Female',
                'relation' => $i === 0 ? 'Self' : ($i === 1 && $type === 'adult' ? ($gender === 'm' ? 'Wife' : 'Husband') : ($type === 'adult' ? 'Family' : 'Child')),
                'passport_no' => $this->passport(), 'passport_expiry' => $expiry,
                'dob' => (match ($type) { 'adult' => now()->subYears(mt_rand(28, 68))->subDays(mt_rand(0, 300)), 'child' => now()->subYears(mt_rand(3, 10)), default => now()->subMonths(mt_rand(4, 20)) })->toDateString(),
                'cnic' => $type === 'adult' && $i === 0 ? $c->cnic : null,
                'visa_status' => $visa($vs), 'visa_no' => $vs === 'issued' ? 'V'.mt_rand(10000000, 99999999) : null,
                'ticket_no' => in_array($vs, ['issued']) && $p->airline ? strtoupper(substr(md5($b->id.$i), 0, 6)) : null,
            ]);
        }
    }

    // ------------------------------------------------------------------ expenses
    protected function expenses(): void
    {
        $rows = [];
        foreach ([[5, 'May'], [4, 'June'], [3, 'July'], [2, 'August'], [1, 'September'], [0, 'October']] as [$m, $label]) {
            $d = today()->startOfMonth()->subMonths($m);
            $rows[] = ['Rent', "Office rent — Gulshan-e-Iqbal shop ($label)", 85000, $d->copy()->addDays(2), 'bank'];
            $rows[] = ['Salary', "Staff salaries — 2 staff ($label)", 165000, $d->copy()->addDays(4), 'bank'];
        }
        $extra = [
            ['Utilities', 'K-Electric bill', 21400, 38, 'cash'], ['Utilities', 'K-Electric bill', 27800, 69, 'cash'], ['Utilities', 'K-Electric bill', 24950, 8, 'jazzcash'],
            ['Utilities', 'PTCL internet bill', 7500, 15, 'easypaisa'], ['Utilities', 'PTCL internet bill', 7500, 45, 'easypaisa'],
            ['Marketing', 'Facebook & Instagram ads — Umrah season', 42000, 12, 'bank'], ['Marketing', 'Facebook ads — Hajj 2027 campaign', 35000, 4, 'bank'],
            ['Marketing', 'Banners & pamphlets printing', 18500, 33, 'cash'], ['Marketing', 'Google ads (local search)', 24000, 25, 'bank'],
            ['Office', 'Stationery & files', 6200, 20, 'cash'], ['Office', 'Tea, refreshments for customers', 9500, 6, 'cash'],
            ['Office', 'Laptop & printer repair', 12000, 52, 'cash'], ['Other', 'TCS courier — passports to embassy agent', 4800, 10, 'cash'],
            ['Other', 'Passport photocopy & lamination', 3200, 3, 'cash'],
        ];
        foreach ($extra as [$cat, $t, $amt, $ago, $m]) { $rows[] = [$cat, $t, $amt, today()->subDays($ago), $m]; }
        foreach ($rows as [$cat, $title, $amt, $d, $m]) {
            if ($d->gt(today())) { $d = today(); }
            Expense::create(['category' => $cat, 'title' => $title, 'amount' => $amt, 'expense_date' => $d->toDateString(), 'method' => $m, 'created_by' => 1,
                'reference' => $m === 'bank' ? strtoupper(substr(md5($title.$amt), 0, 8)) : null]);
        }
    }
}
