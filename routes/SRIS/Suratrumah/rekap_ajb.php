<?php

use App\Http\Controllers\SRIS\Suratrumah\rekap_ajb_c as MainController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['portalAuth']], function () {
    Route::group(
        ['prefix' => 'suratrumah/rekap_ajb'],
        function () {
            Route::get('/', [
                MainController::class,
                'viewRekapAktaJualBeli',
            ]);

            Route::group(['middleware' => ['portalAuth.view']], function () {
                Route::post('/get_lokasi', [
                    MainController::class,
                    'obtainLokasi',
                ]);

                Route::post('/get_sektor', [
                    MainController::class,
                    'obtainSektor',
                ]);

                Route::post('/get_data', [
                    MainController::class,
                    'obtainRekapAktaJualBeli',
                ]);
            });
        }
    );
});
