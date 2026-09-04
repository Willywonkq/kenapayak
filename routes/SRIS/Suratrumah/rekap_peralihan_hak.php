<?php

use App\Http\Controllers\SRIS\Suratrumah\rekap_peralihan_hak_c as MainController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['portalAuth']], function () {
    Route::group(
        ['prefix' => 'suratrumah/rekap_peralihan_hak'],
        function () {
            Route::get('/', [
                MainController::class,
                'viewRekapPeralihanHak',
            ]);

            Route::group(['middleware' => ['portalAuth.view']], function () {
                Route::post('/get_cluster', [
                    MainController::class,
                    'obtainCluster',
                ]);

                Route::post('/get_data', [
                    MainController::class,
                    'obtainRekapPeralihanHak',
                ]);
            });
        }
    );
});
