<?php

use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\FaceMatchController;
use App\Http\Controllers\FaceSampleController;
use App\Http\Controllers\PersonController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'show'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware('auth:admin')->group(function () {
    Route::redirect('/', '/people');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    Route::resource('people', PersonController::class)->except('show');

    Route::get('/people/{person}/faces', [FaceSampleController::class, 'create'])->name('faces.create');
    Route::post('/people/{person}/faces', [FaceSampleController::class, 'store'])->name('faces.store');
    Route::get('/face-samples/{faceSample}/image', [FaceSampleController::class, 'image'])->name('faces.image');
    Route::delete('/face-samples/{faceSample}', [FaceSampleController::class, 'destroy'])->name('faces.destroy');

    Route::get('/match', [FaceMatchController::class, 'show'])->name('match.show');
    Route::post('/match', [FaceMatchController::class, 'match'])->name('match.run')->middleware('throttle:60,1');
});