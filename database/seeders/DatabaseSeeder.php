<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Setting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // The agency owner. The password is only set the first time, so re-seeding never resets it.
        $owner = User::firstOrNew(['email' => env('ADMIN_EMAIL', 'admin@example.com')]);
        $owner->name = 'Tahir Mustafa Marwat';
        $owner->role = 'admin';
        $owner->is_active = true;
        if (! $owner->exists) {
            $owner->password = env('ADMIN_PASSWORD') ?: throw new \RuntimeException('Set ADMIN_PASSWORD in .env before seeding.');
        }
        $owner->save();

        foreach ([
            'agency_name' => 'Gulshan-e-Harmain', 'owner_name' => 'Tahir Mustafa Marwat',
            'facebook' => 'https://www.facebook.com/61556125090638',
            'phone' => '0341-2185055, 0333-3002438', 'whatsapp' => '0317-1272477', 'email' => 'gulshanharmain@gmail.com',
            'address' => 'Shop # 6/1, Al-Shams Plazza, Block-06, Gulshan-e-Iqbal, Karachi, Pakistan, 75300',
            'agency_tagline' => '(Travel Consultant Hajj & Umrah)',
            'tax_rate' => '0', 'invoice_terms' => "Payments are non-refundable once visa processing has started.\nThe client is responsible for keeping travel documents valid.",
        ] as $k => $v) {
            if (Setting::get($k) === null) {
                Setting::set($k, $v);
            }
        }
    }
}
