<?php

// CONTROLLER VERSION SQLSERVER-V1-20260904

namespace App\Http\Controllers\SRIS\Suratrumah;

use App\Http\Controllers\Controller;
use App\Models\SRIS\Suratrumah\rekap_peralihan_hak_m;
use Illuminate\Http\Request;

class rekap_peralihan_hak_c extends Controller
{
    protected rekap_peralihan_hak_m $model;

    public function __construct()
    {
        $this->model = new rekap_peralihan_hak_m();
    }

    public function viewRekapPeralihanHak()
    {
        return view('SRIS.Suratrumah.rekap_peralihan_hak_v');
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

    public function obtainRekapPeralihanHak(Request $request)
    {
        $this->validateFilter($request);

        return response()->json(
            $this->model->obtainRekapPeralihanHak($request)
        );
    }

    private function validateFilter(Request $request): void
    {
        $request->validate([
            'tgl_awal' => ['required', 'date_format:Y-m-d'],
            'tgl_akhir' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:tgl_awal',
            ],
            'perusahaan' => ['required', 'string', 'max:20'],
            'cluster' => ['nullable', 'string', 'max:30'],
            'sts_entry' => ['nullable', 'in:Y,T,*'],
            'sts_approve' => ['nullable', 'in:Y,T,*'],
        ]);
    }
}
