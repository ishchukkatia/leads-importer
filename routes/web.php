<?php

use App\Http\Controllers\LeadImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LeadImportController::class, 'index'])->name('home');
Route::post('/', [LeadImportController::class, 'store'])->name('leads.import');
