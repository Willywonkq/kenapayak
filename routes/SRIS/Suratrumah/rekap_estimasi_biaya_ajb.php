<?php

use App\Http\Controllers\SRIS\Suratrumah\rekap_estimasi_biaya_ajb_c as MainController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['portalAuth']], function () {
    Route::group(
        ['prefix' => 'suratrumah/rekap_estimasi_biaya_ajb'],
        function () {
            Route::get('/', [
                MainController::class,
                'viewRekapEstimasiBiayaAjb',
            ]);

            Route::group(['middleware' => ['portalAuth.view']], function () {
                Route::post('/get_cluster', [
                    MainController::class,
                    'obtainCluster',
                ]);

                Route::post('/get_blok', [
                    MainController::class,
                    'obtainBlok',
                ]);

                Route::post('/get_data', [
                    MainController::class,
                    'obtainRekapEstimasiBiayaAjb',
                ]);
            });
        }
    );
});
