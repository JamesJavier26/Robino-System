<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/bookings/completed', [BookingController::class, 'completed'])
    ->name('bookings.completed');

Route::get('/queues', [QueueController::class, 'index'])->name('queues.index');
Route::post('/queues', [QueueController::class, 'store'])->name('queues.store');
Route::delete('/queues/{queue}', [QueueController::class, 'remove'])->name('queues.remove');
Route::post('/queues/matchup', [QueueController::class, 'matchup'])->name('queues.matchup');
Route::post('/queues/reset-matchups', [QueueController::class, 'resetMatchups'])->name('queues.resetMatchups');
Route::post('/queues/replace', [QueueController::class, 'replace'])->name('queues.replace');
Route::post('/queues/match/{match}/done', [QueueController::class, 'markMatchDone'])->name('queues.match.done');
Route::get('/matches/records', [QueueController::class, 'matchRecords'])->name('matches.records');


Route::resource('bookings', BookingController::class);


Route::resource('players', PlayerController::class);

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('users', UserController::class);
});
require __DIR__.'/auth.php';
