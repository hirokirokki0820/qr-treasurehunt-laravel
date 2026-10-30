<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JoinController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\PrizeController;
use App\Http\Controllers\RedemptionController;
use App\Http\Controllers\TreasureController;
use App\Http\Middleware\RememberGuestVisitor;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

/*
| 主催者（/host 以下。参加者とは別のセッションクッキーを使う）
*/
Route::prefix('host')->group(function () {
    Route::redirect('/', '/host/events')->name('host');

    Route::middleware('guest:web')->group(function () {
        Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [RegisteredUserController::class, 'store']);
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
    });

    Route::middleware('auth:web')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::delete('events', [EventController::class, 'destroyMany'])->name('events.destroy-many');
        Route::resource('events', EventController::class);
        Route::get('events/{event}/qrcodes', [EventController::class, 'qrCodes'])->name('events.qrcodes');
        Route::get('events/{event}/join-qrcode', [EventController::class, 'joinQrCode'])->name('events.join-qrcode');
        Route::get('events/{event}/participants', [EventController::class, 'participants'])->name('events.participants');

        Route::get('scan', [RedemptionController::class, 'scan'])->name('redemption.scan');
        Route::get('redeem/{participant:redeem_token}', [RedemptionController::class, 'lookup'])->name('redemption.lookup');

        Route::delete('events/{event}/prizes', [PrizeController::class, 'destroyMany'])->name('events.prizes.destroy-many');
        Route::post('events/{event}/reset-acquisitions', [EventController::class, 'resetAcquisitions'])->name('events.reset-acquisitions');
        Route::post('events/{event}/end', [EventController::class, 'end'])->name('events.end');

        Route::scopeBindings()->group(function () {
            Route::resource('events.prizes', PrizeController::class)->except('index');
            Route::get('events/{event}/participants/{participant}', [ParticipantController::class, 'show'])->name('events.participants.show');
            Route::post('events/{event}/participants/{participant}/redeem', [RedemptionController::class, 'redeemAll'])->name('events.participants.redeem');
            Route::post('events/{event}/prizes/{prize}/redeem', [RedemptionController::class, 'redeem'])->name('events.prizes.redeem');
            Route::delete('events/{event}/prizes/{prize}/redeem', [RedemptionController::class, 'cancel'])->name('events.prizes.redeem.cancel');
        });
    });
});

/*
| 参加者
*/
Route::middleware(RememberGuestVisitor::class)->group(function () {
    Route::get('guest', [GuestController::class, 'index'])->name('guest.home');
    Route::get('participant/required', [GuestController::class, 'required'])->name('participant.required');
    Route::get('join/{event:join_token}', [JoinController::class, 'create'])->name('join');
    Route::post('join/{event:join_token}', [JoinController::class, 'store'])->name('join.store');

    Route::get('treasure/{prize:token}', [TreasureController::class, 'show'])->name('treasure.show');

    Route::middleware('auth:participant')->group(function () {
        Route::post('treasure/{prize:token}', [TreasureController::class, 'claim'])->name('treasure.claim');

        Route::prefix('play')->name('play.')->group(function () {
            Route::get('/', [PlayController::class, 'index'])->name('index');
            Route::get('scan', [PlayController::class, 'scan'])->name('scan');
            Route::post('logout', [PlayController::class, 'logout'])->name('logout');
        });
    });
});
