<?php

// CONTROLLER VERSION SQLSERVER-V1-20260903

namespace App\Http\Controllers\SRIS\Suratrumah;

use App\Http\Controllers\Controller;
use App\Models\SRIS\Suratrumah\rekap_estimasi_biaya_ajb_m;
use Illuminate\Http\Request;

class rekap_estimasi_biaya_ajb_c extends Controller
{
    protected rekap_estimasi_biaya_ajb_m $model;

    public function __construct()
    {
        $this->model = new rekap_estimasi_biaya_ajb_m();
    }

    public function viewRekapEstimasiBiayaAjb()
    {
        return view('SRIS.Suratrumah.rekap_estimasi_biaya_ajb_v');
    }

    public function obtainCluster(Request $request)
    {
        $validated = $request->validate([
            'perusahaan' => ['required', 'string', 'max:20'],
        ]);

        return response()->json(
            $this->model->obtainCluster($validated['perusahaan'])
        );
    }

    public function obtainBlok(Request $request)
    {
        $validated = $request->validate([
            'perusahaan' => ['required', 'string', 'max:20'],
        ]);

        return response()->json(
            $this->model->obtainBlok($validated['perusahaan'])
        );
    }

    public function obtainRekapEstimasiBiayaAjb(Request $request)
    {
        $this->validateFilter($request);

        return response()->json(
            $this->model->obtainRekapEstimasiBiayaAjb($request)
        );
    }

    private function validateFilter(Request $request): void
    {
        $request->validate([
            'blok_awal' => ['required', 'string', 'max:30'],
            'blok_akhir' => ['required', 'string', 'max:30'],
            'tgl_awal' => ['required', 'date_format:Y-m-d'],
            'tgl_akhir' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:tgl_awal',
            ],
            'perusahaan' => ['required', 'string', 'max:20'],
            'cluster' => ['nullable', 'string', 'max:30'],
        ]);
    }
}
