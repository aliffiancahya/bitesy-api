<?php

use App\Http\Controllers\BitesyMidtransController;
use Illuminate\Support\Facades\Route;

Route::post('/checkout', [BitesyMidtransController::class, 'checkout']);
Route::post('/initiate-payment', [BitesyMidtransController::class, 'initiatePayment']);
Route::post('/handle-notification', [BitesyMidtransController::class, 'handleNotification']);
