<?php

use App\Http\Controllers\SRIS\Suratrumah\daftar_akta_jual_beli_c as MainController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['portalAuth']], function () {
    Route::group(
        ['prefix' => 'suratrumah/dftr_akta_jual_beli'],
        function () {
            Route::get('/', [
                MainController::class,
                'viewDaftarAktaJualBeli',
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
                    'obtainDaftarAktaJualBeli',
                ]);
            });
        }
    );
});
