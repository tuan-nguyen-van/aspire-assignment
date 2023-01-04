<?php

use App\Http\Controllers\LoanController;
use App\Http\Controllers\ScheduledRepaymentController;
use App\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/token/create', [TokenController::class, 'create']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/loan', [LoanController::class, 'store']);

    Route::post('/loan/approve/{loan}', [LoanController::class, 'approve']);

    Route::get('/loan', [LoanController::class, 'index']);

    Route::patch('/scheduled-repayments/pay/{repayment}', [
        ScheduledRepaymentController::class, 'pay',
    ]);
});
