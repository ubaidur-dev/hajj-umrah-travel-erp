<?php

use App\Http\Controllers\{AuthController, BookingController, CustomerController, DashboardController, ExpenseController, InvoiceController, LeadController, MediaController, SearchController, PackageController, PaymentController, ReportController, ProfileController, SettingController, UserController};
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('customers', CustomerController::class);
    Route::resource('packages', PackageController::class);
    Route::get('packages/{package}/manifest', [PackageController::class, 'manifest'])->name('packages.manifest');

    Route::resource('bookings', BookingController::class);
    Route::post('bookings/{booking}/confirm', [BookingController::class, 'confirm'])->name('bookings.confirm');
    Route::post('bookings/{booking}/status', [BookingController::class, 'setStatus'])->name('bookings.status');
    Route::get('bookings/{booking}/slip', [BookingController::class, 'slip'])->name('bookings.slip');
    Route::post('bookings/{booking}/travelers', [BookingController::class, 'addTraveler'])->name('bookings.travelers.store');
    Route::post('bookings/{booking}/travelers/{traveler}', [BookingController::class, 'updateTraveler'])->name('bookings.travelers.update');
    Route::delete('bookings/{booking}/travelers/{traveler}', [BookingController::class, 'removeTraveler'])->name('bookings.travelers.destroy');
    Route::post('bookings/{booking}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('bookings/{booking}/refunds', [PaymentController::class, 'refund'])->name('payments.refund');
    Route::post('customers-quick', [CustomerController::class, 'quick'])->name('customers.quick');
    Route::resource('leads', LeadController::class)->except('show');
    Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
    Route::post('bookings/{booking}/invoice', [InvoiceController::class, 'fromBooking'])->name('invoices.fromBooking');

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');

    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    Route::resource('expenses', ExpenseController::class)->except('show');

    Route::middleware(AdminOnly::class)->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
