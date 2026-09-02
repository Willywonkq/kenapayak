<?php

// CONTROLLER VERSION SQLSERVER-V1-20260902

namespace App\Http\Controllers\SRIS\Suratrumah;

use App\Http\Controllers\Controller;
use App\Models\SRIS\Suratrumah\daftar_akta_jual_beli_m;
use Illuminate\Http\Request;

class daftar_akta_jual_beli_c extends Controller
{
    protected daftar_akta_jual_beli_m $model;

    public function __construct()
    {
        $this->model = new daftar_akta_jual_beli_m();
    }

    public function viewDaftarAktaJualBeli()
    {
        return view('SRIS.Suratrumah.daftar_akta_jual_beli_v');
    }

    public function obtainLokasi(Request $request)
    {
        $validated = $request->validate([
            'perusahaan' => ['required', 'string', 'max:20'],
        ]);

        return response()->json(
            $this->model->obtainLokasi($validated['perusahaan'])
        );
    }

    public function obtainSektor(Request $request)
    {
        $validated = $request->validate([
            'perusahaan' => ['required', 'string', 'max:20'],
        ]);

        return response()->json(
            $this->model->obtainSektor($validated['perusahaan'])
        );
    }

    public function obtainDaftarAktaJualBeli(Request $request)
    {
        $this->validateFilter($request);

        return response()->json(
            $this->model->obtainDaftarAktaJualBeli($request)
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
            'lokasi' => ['nullable', 'string', 'max:30'],
            'sektor' => ['nullable', 'string', 'max:30'],
        ]);
    }
}
