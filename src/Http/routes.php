<?php

const WIRECARD_CONTROLER = 'Fineweb\Wirecard\Http\Controllers\WirecardController@';

Route::group(['middleware' => ['web']], function () {
    Route::prefix('wirecard')->group(function () {
        Route::get('/redirect', WIRECARD_CONTROLER . 'redirect')->name('wirecard.redirect');
        Route::post('/notify', WIRECARD_CONTROLER . 'notify')->name('wirecard.notify');
        Route::get('/success', WIRECARD_CONTROLER . 'success')->name('wirecard.success');
        Route::get('/cancel', WIRECARD_CONTROLER . 'cancel')->name('wirecard.cancel');
    });
});
