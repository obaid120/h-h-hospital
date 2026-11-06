<?php

use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\CancellationController;
use App\Http\Controllers\DesignPreviewController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DoctorPhotoController;
use App\Http\Controllers\DoctorQueueController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HospitalSettingsController;
use App\Http\Controllers\OnlineBookingController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\StaffHomeController;
use App\Http\Controllers\StaffSessionController;
use App\Http\Controllers\StaffUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/doctors/{doctor}/photo', DoctorPhotoController::class)->name('doctors.photo');
Route::get('/booking/request-key', [OnlineBookingController::class, 'key'])->middleware('throttle:booking-read')->name('booking.key');
Route::get('/booking/doctors/{doctor}/availability', [OnlineBookingController::class, 'availability'])->middleware('throttle:booking-read')->name('booking.availability');
Route::get('/booking/doctors/{doctor}/month', [OnlineBookingController::class, 'month'])->middleware('throttle:booking-read')->name('booking.month');
Route::post('/bookings', [OnlineBookingController::class, 'store'])->middleware('throttle:public-booking')->name('booking.store');
Route::get('/doctors/{doctor}/book', [PublicBookingController::class, 'create'])->middleware('throttle:booking-read')->name('booking.create');
Route::post('/booking/doctors/{doctor}/reserve', [PublicBookingController::class, 'store'])->middleware('throttle:public-booking')->name('booking.form.store');
Route::get('/booking/confirmation/{reference}', [PublicBookingController::class, 'confirmation'])->middleware('throttle:booking-read')->name('booking.confirmation');

Route::get('/_design/staff', [DesignPreviewController::class, 'staff'])->name('design.staff');
Route::get('/_design/components', [DesignPreviewController::class, 'components'])->name('design.components');
Route::post('/_design/components', [DesignPreviewController::class, 'checkForm'])->name('design.components.check');

Route::prefix('staff')->name('staff.')->middleware('staff.no-cache')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [StaffSessionController::class, 'create'])->name('login');
        Route::post('/login', [StaffSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'staff.active'])->group(function (): void {
        Route::get('/', [StaffHomeController::class, 'redirect'])->name('home');
        Route::get('/session', [StaffSessionController::class, 'status'])->name('session');
        Route::post('/logout', [StaffSessionController::class, 'destroy'])->name('logout');
        Route::get('/profile/{user}', [StaffHomeController::class, 'profile'])->name('profile');
        Route::get('/reception', [ReceptionController::class, 'index'])->middleware('staff.role:receptionist')->name('receptionist.home');
        Route::get('/appointments', [ReceptionController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/{appointment}', [ReceptionController::class, 'show'])->name('appointments.show');
        Route::post('/appointments/{appointment}/cancel', [CancellationController::class, 'store'])->name('appointments.cancel');
        Route::post('/payments/{payment}/refunds', [RefundController::class, 'store'])->name('payments.refunds.store');
        Route::get('/reception/walk-in', [ReceptionController::class, 'create'])->name('reception.walk-in');
        Route::post('/reception/walk-in', [ReceptionController::class, 'store'])->name('reception.walk-in.store');
        Route::post('/reception/appointments/{appointment}/confirm', [ReceptionController::class, 'confirm'])->name('reception.confirm');
        Route::get('/receipts/{payment}', [ReceiptController::class, 'show'])->name('receipts.show');
        Route::get('/receipts/{payment}/print', [ReceiptController::class, 'show'])->name('receipts.print');
        Route::get('/doctor', [DoctorQueueController::class, 'index'])->middleware('staff.role:doctor')->name('doctor.home');
        Route::get('/doctor/queue/refresh', [DoctorQueueController::class, 'refresh'])->middleware('staff.role:doctor')->name('doctor.queue.refresh');
        Route::post('/doctor/queue/next', [DoctorQueueController::class, 'next'])->middleware('staff.role:doctor')->name('doctor.queue.next');
        Route::get('/consultations/{appointment}/prescription', [PrescriptionController::class, 'edit'])->name('prescriptions.edit');
        Route::put('/consultations/{appointment}/prescription', [PrescriptionController::class, 'store'])->name('prescriptions.store');
        Route::post('/consultations/{appointment}/complete', [PrescriptionController::class, 'complete'])->name('prescriptions.complete');
        Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show'])->name('prescriptions.show');
        Route::get('/prescriptions/{prescription}/print', [PrescriptionController::class, 'show'])->name('prescriptions.print');
        Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
        Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('doctors.show');
        Route::resource('patients', PatientController::class)->only(['index', 'create', 'store', 'show']);

        Route::prefix('admin')->name('admin.')->middleware('staff.role:admin')->group(function (): void {
            Route::get('/', [AdminManagementController::class, 'appointments'])->name('home');
            Route::get('/appointments', [AdminManagementController::class, 'appointments'])->name('appointments');
            Route::get('/finances', [AdminManagementController::class, 'finances'])->name('finances');
            Route::get('/activity', [AdminManagementController::class, 'activity'])->name('activity');
            Route::get('/settings', [HospitalSettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [HospitalSettingsController::class, 'update'])->name('settings.update');
            Route::resource('users', StaffUserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
            Route::resource('doctors', DoctorController::class)->only(['index', 'create', 'store', 'edit', 'update']);
            Route::put('/doctors/{doctor}/weekly', [DoctorController::class, 'weekly'])->name('doctors.weekly');
            Route::put('/doctors/{doctor}/date', [DoctorController::class, 'override'])->name('doctors.override');
        });
    });
});
