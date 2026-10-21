<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone')->nullable();
            $t->string('whatsapp')->nullable();
            $t->string('email')->nullable();
            $t->string('cnic')->nullable();
            $t->string('passport_no')->nullable();
            $t->string('city')->nullable();
            $t->text('address')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('type')->default('other'); // hotel, airline, transport, visa, other
            $t->string('contact_person')->nullable();
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->text('address')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('packages', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('type')->default('umrah'); // umrah, hajj, tour, ticket, visa, hotel, other
            $t->text('description')->nullable();
            $t->unsignedSmallInteger('duration_days')->nullable();
            $t->date('departure_date')->nullable();
            $t->date('return_date')->nullable();
            $t->string('makkah_hotel')->nullable();
            $t->string('madinah_hotel')->nullable();
            $t->string('airline')->nullable();
            $t->text('inclusions')->nullable();
            $t->text('exclusions')->nullable();
            $t->decimal('cost_price', 14, 2)->default(0);   // per adult, sum of package_costs
            $t->decimal('sale_price', 14, 2)->default(0);   // adult
            $t->decimal('child_price', 14, 2)->default(0);
            $t->decimal('infant_price', 14, 2)->default(0);
            $t->unsignedInteger('total_seats')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('package_costs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('package_id')->constrained()->cascadeOnDelete();
            $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->decimal('amount', 14, 2)->default(0); // per adult
            $t->timestamps();
        });

        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->string('booking_no')->unique();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->unsignedSmallInteger('adults')->default(1);
            $t->unsignedSmallInteger('children')->default(0);
            $t->unsignedSmallInteger('infants')->default(0);
            $t->date('booking_date');
            $t->date('travel_date')->nullable();
            $t->date('return_date')->nullable();
            $t->decimal('discount', 14, 2)->default(0);
            $t->decimal('sale_total', 14, 2)->default(0);   // before discount
            $t->decimal('cost_total', 14, 2)->default(0);
            $t->decimal('net_total', 14, 2)->default(0);    // sale_total - discount
            $t->decimal('paid_total', 14, 2)->default(0);
            $t->string('status')->default('pending'); // pending, confirmed, completed, cancelled
            $t->timestamp('confirmed_at')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['status', 'booking_date']);
        });

        Schema::create('booking_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $t->string('description');
            $t->decimal('quantity', 10, 2)->default(1);
            $t->decimal('unit_price', 14, 2)->default(0);
            $t->decimal('unit_cost', 14, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('travelers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('type')->default('adult'); // adult, child, infant
            $t->string('gender')->nullable();
            $t->string('relation')->nullable();
            $t->string('passport_no')->nullable();
            $t->date('passport_expiry')->nullable();
            $t->date('dob')->nullable();
            $t->string('cnic')->nullable();
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->string('receipt_no')->unique();
            $t->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 14, 2);
            $t->string('method')->default('cash'); // cash, bank, jazzcash, easypaisa, cheque, other
            $t->date('paid_at');
            $t->string('reference')->nullable();
            $t->text('note')->nullable();
            $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_no')->unique();
            $t->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->date('issue_date');
            $t->date('due_date')->nullable();
            $t->decimal('subtotal', 14, 2)->default(0);
            $t->decimal('discount', 14, 2)->default(0);
            $t->decimal('tax_rate', 5, 2)->default(0);
            $t->decimal('tax', 14, 2)->default(0);
            $t->decimal('total', 14, 2)->default(0);
            $t->decimal('paid', 14, 2)->default(0);
            $t->string('status')->default('unpaid'); // unpaid, partial, paid, cancelled
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->string('description');
            $t->decimal('quantity', 10, 2)->default(1);
            $t->decimal('unit_price', 14, 2)->default(0);
            $t->decimal('amount', 14, 2)->default(0);
        });

        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->string('category'); // supplier, rent, salary, utilities, marketing, office, other
            $t->string('title');
            $t->decimal('amount', 14, 2);
            $t->date('expense_date');
            $t->string('method')->default('cash');
            $t->string('reference')->nullable();
            $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $t->text('note')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->string('description');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['activity_logs','settings','expenses','invoice_items','invoices','payments','travelers','booking_items','bookings','package_costs','packages','suppliers','customers'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
