<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Vendors/suppliers are not used by this agency — remove them everywhere. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('expenses')->where('category', 'supplier')->update(['category' => 'Other']);

        Schema::table('package_costs', fn (Blueprint $t) => $t->dropConstrainedForeignId('supplier_id'));
        Schema::table('booking_items', fn (Blueprint $t) => $t->dropConstrainedForeignId('supplier_id'));
        Schema::table('expenses', function (Blueprint $t) {
            $t->dropConstrainedForeignId('supplier_id');
            $t->dropConstrainedForeignId('booking_id');
        });
        Schema::dropIfExists('suppliers');
    }

    public function down(): void
    {
        // intentionally empty: the vendors feature was removed on purpose
    }
};
