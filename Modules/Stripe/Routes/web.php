<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Stripe\Http\Controllers\StripeController;

Route::prefix('stripe')->group(function() {
    Route::post('/setting/store', 'StripeController@setting')->name('stripe.setting.store')->middleware(['auth']);
    Route::post('/plan/company/payment', 'StripeController@planPayWithStripe')->name('plan.pay.with.stripe')->middleware(['auth']);
    Route::get('/plan/company/status', 'StripeController@planGetStripeStatus')->name('plan.get.payment.status')->middleware(['auth']);
});
Route::post('/invoice-pay-with-stripe', [StripeController::class, 'invoicePayWithStripe'])->name('invoice.pay.with.stripe');
Route::get('/invoice/stripe/{invoice_id}/{type}', [StripeController::class, 'getInvoicePaymentStatus'])->name('invoice.stripe');
