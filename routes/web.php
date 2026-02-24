<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CertificateController;
use Illuminate\Support\Facades\Storage;
use App\Models\Event;
use App\Http\Controllers\AttendanceScanController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/admin');
});

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

// Route::view('profile', 'profile')
//     ->middleware(['auth'])
//     ->name('profile');


Route::get('downloadCertificate/{id}', [CertificateController::class, 'downloadCertificate'])->name('certificate.view');

// require __DIR__.'/auth.php';

Route::get('/download-module/{event}', function (Event $event) {

    abort_if(
        !$event->module || !Storage::disk('public')->exists($event->module),
        404
    );

    return response()->download(
        storage_path('app/public/' . $event->module)
    );
})->name('event.download.module');

Route::post('/attendance/scan', [AttendanceScanController::class, 'scan'])
    ->middleware('auth')
    ->name('attendance.scan');

Route::get('/event/{event}/qr', [AttendanceScanController::class, 'downloadPng'])
    ->name('event.qr.download');

