<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $t) {
            $t->unsignedSmallInteger('makkah_nights')->nullable()->after('duration_days');
            $t->unsignedSmallInteger('madinah_nights')->nullable()->after('makkah_nights');
            $t->string('departure_city')->nullable()->after('airline');
            $t->string('flight_no')->nullable()->after('departure_city');
            $t->string('transport')->nullable()->after('flight_no');
            $t->string('meals')->nullable()->after('transport');
        });

        Schema::table('bookings', function (Blueprint $t) {
            $t->string('service_type')->default('umrah')->after('package_id');
            $t->date('payment_due_date')->nullable()->after('return_date');
        });
        DB::table('bookings')->update(['service_type' => 'other']);

        Schema::table('travelers', function (Blueprint $t) {
            $t->string('visa_status')->default('not_applied')->after('cnic'); // not_applied, applied, approved, issued, rejected
            $t->string('visa_no')->nullable()->after('visa_status');
            $t->string('ticket_no')->nullable()->after('visa_no');
            $t->text('notes')->nullable()->after('ticket_no');
        });

        Schema::table('payments', function (Blueprint $t) {
            $t->string('kind')->default('payment')->after('booking_id'); // payment | refund (refund amounts are stored negative)
        });

        Schema::create('leads', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone')->nullable();
            $t->string('interest')->default('umrah');
            $t->unsignedSmallInteger('persons')->nullable();
            $t->date('expected_travel')->nullable();
            $t->string('status')->default('new'); // new, contacted, follow_up, won, lost
            $t->date('next_follow_up')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        // single row that owns the agency logo (Spatie Media Library needs a model)
        Schema::create('agency_profiles', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
        });
        DB::table('agency_profiles')->insert(['created_at' => now(), 'updated_at' => now()]);

        // avatars now live in Spatie Media Library
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('avatar'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->string('avatar')->nullable());
        Schema::dropIfExists('agency_profiles');
        Schema::dropIfExists('leads');
        Schema::table('payments', fn (Blueprint $t) => $t->dropColumn('kind'));
        Schema::table('travelers', fn (Blueprint $t) => $t->dropColumn(['visa_status', 'visa_no', 'ticket_no', 'notes']));
        Schema::table('bookings', fn (Blueprint $t) => $t->dropColumn(['service_type', 'payment_due_date']));
        Schema::table('packages', fn (Blueprint $t) => $t->dropColumn(['makkah_nights', 'madinah_nights', 'departure_city', 'flight_no', 'transport', 'meals']));
    }
};
