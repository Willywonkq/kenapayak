@extends('layouts.template')

@section('content')
<style>
    .balik-nama-page,
    .balik-nama-page * {
        box-sizing: border-box;
    }

    .balik-nama-page {
        width: 100%;
        min-width: 820px;
        min-height: 100%;
        padding: 12px 14px 28px;
        overflow-x: auto;
        background: #eef1f4;
        color: #20252b;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    }

    .desktop-toolbar {
        min-width: 790px;
        padding: 10px 12px 11px;
        border: 1px solid #b9bec4;
        background: #e8e8e8;
        box-shadow: inset 0 1px 0 #ffffff;
    }

    .filter-row {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 34px;
        white-space: nowrap;
    }

    .filter-row + .filter-row {
        margin-top: 5px;
    }

    .field-label {
        width: 52px;
        flex: 0 0 52px;
        color: #252a30;
        font-size: 12px;
        font-weight: 600;
    }

    .date-label {
        width: 66px;
        flex-basis: 66px;
        margin-left: 12px;
    }

    .desktop-input,
    .lookup-display {
        height: 28px;
        padding: 4px 7px;
        border: 1px solid #9da4aa;
        border-radius: 2px;
        background: #ffffff;
        color: #1f252a;
        font-size: 12px;
        outline: 0;
    }

    .desktop-input:focus {
        border-color: #4b78a7;
        box-shadow: 0 0 0 1px rgba(75, 120, 167, 0.18);
    }

    .blok-input {
        width: 96px;
    }

    .date-input {
        width: 132px;
    }

    .range-text {
        color: #363b40;
        font-size: 12px;
    }

    .sector-wrap {
        display: flex;
        width: 325px;
        align-items: stretch;
        gap: 4px;
    }

    .lookup-display {
        display: flex;
        min-width: 0;
        flex: 1 1 auto;
        align-items: center;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .lookup-button,
    .ok-button {
        height: 28px;
        border: 1px solid #8d959d;
        border-radius: 2px;
        background: linear-gradient(#fdfdfd, #d7d7d7);
        color: #20252b;
        cursor: pointer;
        font-size: 11px;
        font-weight: 600;
    }

    .lookup-button {
        width: 34px;
        flex: 0 0 34px;
    }

    .lookup-button:hover,
    .ok-button:hover {
        background: linear-gradient(#ffffff, #cfd9e4);
        border-color: #6f8498;
    }

    .ok-button {
        width: 72px;
        margin-left: 10px;
    }

    .report-workspace {
        min-width: 790px;
        min-height: 610px;
        margin-top: 14px;
        padding: 38px 34px 32px;
        overflow: auto;
        border: 1px solid #b8bdc3;
        background: #dfe2e5;
    }

    .report-paper {
        width: 100%;
        min-width: 1020px;
        min-height: 560px;
        margin: 0 auto;
        padding: 12px 18px 24px;
        border: 1px solid #5a67ff;
        background: #ffffff;
    }

    .report-title {
        margin: 0;
        text-align: center;
        font-size: 18px;
        font-weight: 700;
        text-decoration: underline;
    }

    .report-meta {
        margin-top: 5px;
        text-align: center;
        color: #252525;
        font-size: 12px;
        line-height: 1.4;
    }

    .loading-info {
        display: none;
        margin: 18px auto;
        padding: 8px 12px;
        width: fit-content;
        border: 1px solid #9ba8b4;
        background: #f7f7f7;
        font-size: 12px;
    }

    .empty-report {
        min-height: 370px;
    }

    .report-table-wrap {
        margin-top: 20px;
        overflow: auto;
        border: 1px solid #b9bec4;
    }

    .report-table {
        width: 1900px;
        min-width: 1900px;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 10.5px;
    }

    .report-table th,
    .report-table td {
        padding: 6px 7px;
        border: 1px solid #bfc5cb;
        vertical-align: middle;
    }

    .report-table th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #e8ecef;
        text-align: center;
        font-weight: 700;
    }

    .report-table tbody tr:nth-child(even) td {
        background: #fafafa;
    }

    .report-table tbody tr:hover td {
        background: #eef5ff;
    }

    .center {
        text-align: center;
    }

    .right {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .modal-dialog-balik-nama {
        width: calc(100vw - 40px);
        max-width: 760px;
    }

    .modal-search {
        width: 100%;
        height: 34px;
        margin-bottom: 10px;
        padding: 6px 9px;
        border: 1px solid #aab1b8;
        outline: 0;
    }

    .modal-table-wrap {
        max-height: 430px;
        overflow: auto;
        border: 1px solid #c2c8ce;
    }

    .modal-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    .modal-table th,
    .modal-table td {
        padding: 8px 10px;
        border: 1px solid #d1d5d9;
    }

    .modal-table th {
        position: sticky;
        top: 0;
        background: #eceff2;
        text-align: left;
    }

    .modal-table tbody tr {
        cursor: pointer;
    }

    .modal-table tbody tr:hover td {
        background: #e8f1fb;
    }

    @media print {
        .desktop-toolbar,
        #balikNamaModal,
        .loading-info {
            display: none !important;
        }

        .balik-nama-page,
        .report-workspace {
            min-width: 0;
            padding: 0;
            border: 0;
            background: #fff;
        }

        .report-paper {
            min-width: 0;
            border: 0;
        }
    }


    /* =========================================================
       V2.1 MODERN BLUE MATCH
       Menyamakan visual Daftar Pengajuan Balik Nama dengan
       tampilan Daftar Sertipikat Pecahan V9.5.
       Struktur HTML/ID/AJAX/render data lama tetap dipertahankan.
       ========================================================= */

    .balik-nama-page {
        --bn-blue: #2563eb;
        --bn-blue-dark: #1d4ed8;
        --bn-cyan: #38bdf8;
        --bn-violet: #6366f1;
        --bn-ink: #172033;
        --bn-muted: #667085;
        --bn-line: #dbe3ef;

        position: relative;
        width: 100%;
        min-width: 720px;
        min-height: 100%;
        padding: 114px 12px 32px;
        overflow: visible;
        background:
            radial-gradient(circle at 95% 2%, rgba(37, 99, 235, .07), transparent 28%),
            radial-gradient(circle at 8% 96%, rgba(56, 189, 248, .05), transparent 26%),
            #f3f6fb;
        color: var(--bn-ink);
        font-family: Inter, "Segoe UI", Tahoma, Arial, sans-serif;
    }

    /* Header modern dibuat lewat CSS supaya struktur view lama tidak perlu diubah. */
    .balik-nama-page::before {
        content: "LAND DOCUMENT CONTROL — PENGAJUAN BALIK NAMA";
        position: absolute;
        top: 18px;
        left: 12px;
        right: 12px;
        z-index: 1;
        display: flex;
        min-height: 78px;
        align-items: center;
        padding: 16px 160px 16px 68px;
        border: 1px solid #e2e8f0;
        border-radius: 22px;
        background:
            radial-gradient(circle at 88% -30%, rgba(37, 99, 235, .10), transparent 44%),
            linear-gradient(90deg, #fff 0%, #fff 66%, #f8fbff 100%);
        box-shadow: 0 8px 24px rgba(15, 23, 42, .07);
        color: #172033;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: .11em;
        text-transform: uppercase;
    }

    .balik-nama-page::after {
        content: "UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}";
        position: absolute;
        top: 42px;
        right: 32px;
        z-index: 2;
        display: inline-flex;
        min-width: 76px;
        height: 30px;
        align-items: center;
        justify-content: center;
        padding: 0 12px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1e40af;
        font-family: Consolas, monospace;
        font-size: 10px;
        font-weight: 800;
    }

    .desktop-toolbar {
        position: relative;
        min-width: 0;
        padding: 20px;
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .07);
    }

    .desktop-toolbar::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: linear-gradient(180deg, #38bdf8 0%, #2563eb 52%, #1d4ed8 100%);
    }

    .desktop-toolbar::after {
        content: "Pengajuan Balik Nama";
        position: absolute;
        right: 20px;
        bottom: 12px;
        color: rgba(37, 99, 235, .035);
        font-size: 32px;
        font-weight: 950;
        letter-spacing: .06em;
        pointer-events: none;
    }

    .filter-row {
        position: relative;
        z-index: 1;
        min-height: 42px;
        gap: 8px;
        white-space: nowrap;
    }

    .filter-row:first-child {
        display: grid;
        grid-template-columns:
            56px
            minmax(86px, 1fr)
            34px
            minmax(86px, 1fr)
            72px
            minmax(118px, 1fr)
            34px
            minmax(118px, 1fr)
            82px;
        align-items: center;
    }

    .filter-row + .filter-row {
        display: grid;
        grid-template-columns: 56px minmax(0, 430px);
        margin-top: 13px;
        align-items: center;
        z-index: 1;
    }

    /*
     * V7 CLICK FIX
     * Tombol PRINT berada 55px di bawah action stack dan secara visual
     * masuk ke area baris filter kedua. Karena baris kedua dirender setelah
     * baris pertama, box transparannya dapat menangkap klik.
     *
     * Naikkan stacking baris pertama/action button supaya PRINT benar-benar
     * menjadi target pointer, bukan tertutup oleh .filter-row kedua.
     */
    .filter-row:first-child {
        z-index: 20;
    }

    .filter-row:first-child .bn-action-stack {
        z-index: 30;
        overflow: visible;
    }

    .filter-row:first-child .bn-print-button {
        z-index: 40;
        pointer-events: auto;
    }

    .field-label,
    .date-label {
        width: auto;
        flex: none;
        margin: 0;
        color: #475467;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .desktop-input,
    .lookup-display {
        width: 100%;
        min-width: 0;
        height: 42px;
        padding: 8px 12px;
        border: 1px solid #c8d3e1;
        border-radius: 12px;
        background: #fff;
        color: #101828;
        font-size: 12px;
        font-weight: 650;
        outline: none;
    }

    .blok-input,
    .date-input {
        width: 100%;
    }

    .desktop-input:hover,
    .lookup-display:hover {
        border-color: #aebed1;
    }

    .desktop-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .13);
    }

    .range-text {
        display: grid;
        width: 34px;
        height: 26px;
        place-items: center;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 9px;
        font-weight: 900;
    }

    .sector-wrap {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 42px;
        width: 100%;
        gap: 8px;
        align-items: center;
    }

    .lookup-button,
    .ok-button {
        position: relative;
        display: inline-flex;
        height: 42px;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 0;
        cursor: pointer;
        font-size: 12px;
        font-weight: 900;
    }

    .lookup-button {
        width: 42px;
        border: 1px solid #bfdbfe;
        border-radius: 13px;
        background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%);
        color: #1d4ed8;
    }

    .lookup-button:hover {
        background: linear-gradient(145deg, #dbeafe 0%, #bfdbfe 100%);
        box-shadow: 0 7px 15px rgba(37, 99, 235, .12);
    }

    /* OK dibuat seperti referensi: biru dan menempel di ujung kanan panel. */
    .ok-button {
        width: 82px;
        min-width: 82px;
        margin: 0 -20px 0 0;
        justify-self: end;
        border-radius: 13px 0 0 13px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .24);
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .ok-button:hover {
        transform: translateY(-1px);
        background: linear-gradient(135deg, #2f6ff0 0%, #1e4fc4 100%);
        box-shadow: 0 12px 24px rgba(37, 99, 235, .30);
    }

    .report-workspace {
        position: relative;
        min-width: 0;
        min-height: 0;
        margin-top: 18px;
        padding: 18px;
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 26px;
        background: #fff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, .07);
    }

    .report-workspace::before {
        content: "";
        position: absolute;
        top: 0;
        left: 7%;
        right: 7%;
        height: 3px;
        border-radius: 0 0 999px 999px;
        background: linear-gradient(
            90deg,
            transparent,
            #38bdf8,
            #2563eb,
            #6366f1,
            transparent
        );
    }

    .report-paper {
        width: 100%;
        min-width: 0;
        min-height: 310px;
        margin: 0;
        padding: 24px 20px;
        overflow: hidden;
        border: 1px dashed #bfdbfe;
        border-radius: 20px;
        background:
            radial-gradient(circle at center, rgba(37, 99, 235, .055), transparent 46%),
            #f8fbff;
    }

    .report-title {
        margin: 0;
        color: #172033;
        text-align: center;
        font-size: 18px;
        font-weight: 900;
        text-decoration: none;
    }

    .report-meta {
        margin-top: 6px;
        color: #667085;
        text-align: center;
        font-size: 10.5px;
        font-weight: 650;
        line-height: 1.55;
    }

    .empty-report {
        min-height: 230px;
    }

    .loading-info {
        position: relative;
        z-index: 4;
        margin: 0 0 12px;
        padding: 8px 12px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 850;
    }

    .report-table-wrap {
        width: 100%;
        max-height: calc(100vh - 330px);
        min-height: 300px;
        margin-top: 20px;
        overflow: auto;
        border: 1px solid #dbe3ef;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 5px 16px rgba(15, 23, 42, .04);
    }

    .report-table {
        width: 1900px;
        min-width: 1900px;
        color: #344054;
    }

    .report-table th,
    .report-table td {
        padding: 8px 9px;
        border-color: #e2e8f0;
    }

    .report-table th {
        background: linear-gradient(180deg, #eff6ff 0%, #e5effb 100%);
        color: #344054;
        font-weight: 900;
    }

    .report-table tbody tr:nth-child(even) td {
        background: #fbfcfe;
    }

    .report-table tbody tr:hover td {
        background: #f0f7ff;
    }

    #balikNamaModal .modal-content {
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .22);
    }

    #balikNamaModal .modal-header {
        border-bottom: 1px solid #dbe3ef;
        background: linear-gradient(90deg, #fff 0%, #f8fbff 100%);
        color: #1d2939;
    }

    #balikNamaModal .modal-body {
        background: #f8fafc;
    }

    .modal-search {
        height: 42px;
        border: 1px solid #c8d3e1;
        border-radius: 12px;
        background: #fff;
    }

    .modal-table-wrap {
        border-color: #dbe3ef;
        border-radius: 14px;
        background: #fff;
    }

    .modal-table th {
        background: linear-gradient(180deg, #eff6ff 0%, #e7f0fc 100%);
        color: #344054;
    }

    .modal-table tbody tr:hover td {
        background: #eff6ff;
        color: #1d4ed8;
    }


    /* =========================================================
       V2.2 INITIAL STATE
       Sebelum tombol OK ditekan, area hasil hanya menampilkan
       instruksi seperti halaman Daftar Sertipikat Pecahan.
       Setelah OK ditekan, renderReport() mengganti area ini
       dengan laporan/data tanpa mengubah endpoint atau query.
       ========================================================= */
    .initial-report-state {
        display: flex;
        width: 100%;
        min-height: 330px;
        align-items: center;
        justify-content: center;
        padding: 32px;
        border: 1px dashed #bfdbfe;
        border-radius: 20px;
        background:
            radial-gradient(circle at center, rgba(37, 99, 235, .055), transparent 46%),
            #f8fbff;
    }

    .initial-report-state-inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        color: #667085;
        text-align: center;
    }

    .initial-report-icon {
        display: inline-flex;
        width: 52px;
        height: 52px;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 16px;
        background: #dbeafe;
        color: #2563eb;
        box-shadow: none;
        font-size: 20px;
    }

    .initial-report-text {
        color: #667085;
        font-size: 11px;
        font-weight: 750;
        line-height: 1.6;
    }

    .initial-report-text strong {
        color: #475467;
        font-weight: 850;
    }

    @media screen and (max-width: 719px) {
        html,
        body,
        .balik-nama-page {
            min-width: 720px;
        }
    }

    @media print {
        .balik-nama-page {
            padding-top: 0;
            background: #fff;
        }

        .balik-nama-page::before,
        .balik-nama-page::after {
            display: none !important;
        }
    }



    /* =========================================================
       V2.4 HEADER MATCH SERTIPIKAT PECAHAN
       Header kiri atas dibuat memakai elemen nyata seperti
       Daftar Sertipikat Pecahan, bukan pseudo-element halaman.
       Fungsi/filter/AJAX tidak berubah.
       ========================================================= */

    .balik-nama-page {
        padding: 18px 12px 32px !important;
    }

    /* Matikan header pseudo versi lama agar tidak dobel. */
    .balik-nama-page::before,
    .balik-nama-page::after {
        display: none !important;
        content: none !important;
    }

    .balik-nama-view-version {
        position: relative;
        display: flex;
        min-height: 78px;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 16px;
        padding: 16px 20px 16px 68px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 22px;
        background: linear-gradient(
            90deg,
            #ffffff 0%,
            #ffffff 65%,
            #f8fbff 100%
        );
        color: #172033;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
    }

    .balik-nama-view-version::before {
        content: "◈";
        position: absolute;
        left: 20px;
        display: grid;
        width: 34px;
        height: 34px;
        place-items: center;
        border: 0;
        border-radius: 11px;
        background: linear-gradient(
            135deg,
            #2563eb 0%,
            #1d4ed8 100%
        );
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.24);
        font-size: 18px;
    }

    .balik-nama-view-version::after {
        content: "";
        position: absolute;
        top: -82px;
        right: 40px;
        width: 260px;
        height: 190px;
        border-radius: 50%;
        background: radial-gradient(
            circle,
            rgba(37, 99, 235, 0.08),
            transparent 68%
        );
        pointer-events: none;
    }

    .balik-nama-view-version span {
        position: relative;
        z-index: 1;
        color: #172033;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: 0.11em;
        text-transform: uppercase;
    }

    .balik-nama-view-version code {
        position: relative;
        z-index: 1;
        padding: 7px 12px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1e40af;
        box-shadow: none;
        font-family: "SFMono-Regular", Consolas, monospace;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.06em;
        white-space: nowrap;
    }

    @media print {
        .balik-nama-view-version {
            display: none !important;
        }
    }



    /* FONT STANDARD — MATCH DAFTAR SURAT PESANAN */
    .balik-nama-page,
    .balik-nama-page input,
    .balik-nama-page select,
    .balik-nama-page button,
    .balik-nama-page textarea,
    .balik-nama-page label,
    .balik-nama-page table,
    .balik-nama-page td,
    .balik-nama-page .report-meta,
    .balik-nama-page .initial-report-text,
    .balik-nama-page .modal-content {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
    }

    .balik-nama-page .balik-nama-view-version span,
    .balik-nama-page .balik-nama-view-version code,
    .balik-nama-page .field-label,
    .balik-nama-page .date-label,
    .balik-nama-page .report-table th,
    .balik-nama-page .modal-header {
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
    }

    .balik-nama-page .report-title {
        font-family: Cambria, Georgia, "Times New Roman", serif !important;
        font-weight: 700 !important;
    }

    /* =========================================================
       V3 — STANDARD HASIL LAPORAN SAMA DENGAN SERTIPIKAT PECAHAN
       Filter, ID elemen, AJAX, endpoint, dan struktur kolom tetap.
       ========================================================= */
    .balik-nama-page .report-workspace {
        padding: 20px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, .95);
        border-radius: 24px;
        background: rgba(255, 255, 255, .94);
        box-shadow: 0 20px 48px rgba(15, 23, 42, .10);
    }

    .balik-nama-page .report-paper {
        width: 100%;
        min-width: 0;
        min-height: 310px;
        padding: 0 0 26px;
        overflow: hidden;
        border: 0;
        border-radius: 0;
        background: transparent;
    }

    .standard-report-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 2fr) minmax(0, 1fr);
        gap: 12px;
        align-items: center;
        min-height: 76px;
        margin-bottom: 10px;
        padding: 16px 18px;
        border: 1px solid #dce9f8;
        border-radius: 17px;
        background: linear-gradient(110deg, #e9f3ff, #f2f7ff 50%, #e7f0ff);
        box-shadow: inset 0 1px 0 #fff;
    }

    .standard-report-company {
        min-width: 0;
        overflow: hidden;
        color: #2563eb;
        font-size: 12.5px;
        font-weight: 900;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .standard-report-title {
        padding: 0 8px;
        color: #172033;
        font-family: Cambria, Georgia, "Times New Roman", serif;
        font-size: 19px;
        font-weight: 800;
        line-height: 1.2;
        text-align: center;
    }

    .standard-report-period {
        color: #40546b;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.55;
        text-align: right;
    }

    .standard-report-subtitle {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        min-height: 47px;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
        padding: 9px 12px;
        border: 1px solid #e3ebf4;
        border-radius: 13px;
        background: #fff;
        color: #526174;
        font-size: 11.5px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, .035);
    }

    .standard-report-subtitle-label {
        justify-self: start;
    }

    .standard-report-sector {
        justify-self: center;
        color: #24374e;
        font-weight: 900;
        text-align: center;
    }

    .standard-report-live {
        display: inline-flex;
        align-items: center;
        justify-self: end;
        gap: 6px;
        padding: 6px 10px;
        border: 1px solid #a7f3d0;
        border-radius: 999px;
        background: #ecfdf5;
        color: #059669;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .07em;
        text-transform: uppercase;
    }

    .standard-report-live::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .13);
    }

    .balik-nama-page .report-table-wrap {
        width: 100%;
        max-width: 100%;
        max-height: calc(100vh - 330px);
        min-height: 300px;
        margin-top: 0;
        overflow: auto;
        border: 1px solid #d7e2ef;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
        -webkit-overflow-scrolling: touch;
    }

    .balik-nama-page .report-table {
        width: 2380px;
        min-width: 2380px;
        table-layout: fixed;
        border-collapse: collapse;
        color: #1e3048;
        font-size: 12.5px;
        line-height: 1.48;
    }

    .balik-nama-page .report-table th,
    .balik-nama-page .report-table td {
        padding: 9px 8px;
        overflow-wrap: anywhere;
        word-break: normal;
        white-space: normal;
        vertical-align: top;
        border: 0;
        border-right: 1px solid #d7e2ef;
        border-bottom: 1px solid #d7e2ef;
    }

    .balik-nama-page .report-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        padding: 10px 7px;
        background: linear-gradient(180deg, #e9f2ff, #dfeaff);
        color: #29415f;
        font-size: 11.5px;
        font-weight: 900;
        line-height: 1.3;
        text-align: center;
        vertical-align: middle;
    }

    .balik-nama-page .report-table tbody tr:nth-child(even) td {
        background: #f9fbfe;
    }

    .balik-nama-page .report-table tbody tr:hover td {
        background: #eef6ff;
    }

    .balik-nama-page .report-table tbody td:nth-child(3) {
        color: #203a58;
        font-weight: 800;
    }

    @media screen and (max-width: 760px) {
        .standard-report-header {
            grid-template-columns: 1fr;
        }
        .standard-report-title {
            grid-row: 1;
            font-size: 17px;
        }
        .standard-report-period {
            text-align: left;
        }
        .standard-report-subtitle {
            grid-template-columns: minmax(0, 1fr) auto;
        }
        .standard-report-subtitle-label {
            display: none;
        }
        .standard-report-sector {
            grid-column: 1;
            justify-self: start;
        }
        .standard-report-live {
            grid-column: 2;
        }
    }

    @media print {
        .standard-report-header,
        .standard-report-subtitle {
            border-color: #777;
            border-radius: 0;
            background: #fff !important;
            box-shadow: none;
        }
        .balik-nama-page .report-table-wrap {
            max-height: none;
            overflow: visible;
            border-color: #000;
            border-radius: 0;
        }
        .balik-nama-page .report-table {
            width: 100%;
            min-width: 0;
            font-size: 8px;
        }
        .balik-nama-page .report-table thead th {
            position: static;
            font-size: 7.5px;
        }
    }



    /* =========================================================
       V6 PRINT STANDARD — KONSISTEN DENGAN JAMINAN BANK
       - OK dan PRINT 82x42, menempel ke sisi kanan filter card.
       - PRINT disabled sebelum laporan selesai dirender.
       - Cetak A3 landscape, margin 8mm.
       - Hasil cetak hitam-putih dan semua border tabel utuh.
       ========================================================= */
    .bn-action-stack {
        position: relative;
        z-index: 30;
        width: 82px;
        height: 42px;
        justify-self: end;
        overflow: visible;
        pointer-events: auto;
    }

    .bn-action-stack .ok-button {
        position: absolute;
        top: 0;
        right: -20px;
        width: 82px;
        min-width: 82px;
        height: 42px;
        margin: 0;
        justify-self: auto;
        border-radius: 13px 0 0 13px;
    }

    .bn-print-button {
        position: absolute;
        z-index: 40;
        top: 55px;
        right: -20px;
        display: inline-flex;
        width: 82px;
        min-width: 82px;
        height: 42px;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 13px 0 0 13px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(5, 150, 105, .22);
        cursor: pointer;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .bn-print-button:hover:not(:disabled) {
        transform: translateY(-1px);
        background: linear-gradient(135deg, #14b88a 0%, #047857 100%);
        box-shadow: 0 12px 24px rgba(5, 150, 105, .28);
    }

    .bn-print-button:disabled,
    .bn-print-button:disabled:hover,
    .bn-print-button:disabled:focus {
        transform: none;
        border: 1px solid #d5dde7;
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        color: #94a3b8;
        box-shadow: none;
        cursor: not-allowed;
        filter: none;
        opacity: 1;
    }

    @media print {
        @page {
            margin: 8mm;
        }

        .desktop-toolbar,
        .balik-nama-view-version,
        #balikNamaModal,
        #pengajuanBalikNamaNoDataAlertModal,
        .modal-backdrop,
        .loading-info {
            display: none !important;
        }

        html,
        body,
        .balik-nama-page,
        .report-workspace,
        .report-paper {
            min-width: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: #fff !important;
            box-shadow: none !important;
        }

        .balik-nama-page::before,
        .balik-nama-page::after,
        .report-workspace::before {
            display: none !important;
        }

        .standard-report-header,
        .standard-report-subtitle {
            border: 1px solid #777 !important;
            border-radius: 0 !important;
            background: #fff !important;
            color: #000 !important;
            box-shadow: none !important;
        }

        .standard-report-company,
        .standard-report-title,
        .standard-report-period,
        .standard-report-subtitle,
        .standard-report-subtitle-label,
        .standard-report-sector,
        .standard-report-live {
            color: #000 !important;
        }

        .standard-report-live {
            border-color: #777 !important;
            background: #fff !important;
            box-shadow: none !important;
        }

        .standard-report-live::before {
            background: #000 !important;
            box-shadow: none !important;
        }

        .balik-nama-page .report-table-wrap {
            width: 100% !important;
            max-width: 100% !important;
            max-height: none !important;
            min-height: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: #fff !important;
            box-shadow: none !important;
        }

        .balik-nama-page .report-table {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            border-spacing: 0 !important;
            border: 1px solid #000 !important;
            background: #fff !important;
            color: #000 !important;
            font-size: 7.6px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .balik-nama-page .report-table thead {
            display: table-header-group !important;
        }

        .balik-nama-page .report-table tbody {
            display: table-row-group !important;
        }

        .balik-nama-page .report-table tr {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        .balik-nama-page .report-table th,
        .balik-nama-page .report-table td,
        .balik-nama-page .report-table thead th {
            position: static !important;
            padding: 3.5px !important;
            border: 1px solid #000 !important;
            background: #fff !important;
            color: #000 !important;
            box-shadow: none !important;
            overflow: visible !important;
            overflow-wrap: anywhere !important;
            word-break: normal !important;
        }

        .balik-nama-page .report-table thead th {
            font-size: 7.2px !important;
        }

        .balik-nama-page .report-table tbody tr:nth-child(even) td,
        .balik-nama-page .report-table tbody tr:hover td {
            background: #fff !important;
            color: #000 !important;
        }

        /* Proporsi 17 kolom berdasarkan colgroup tampilan layar. */
        .balik-nama-page .report-table col:nth-child(1)  { width: 2.0% !important; }
        .balik-nama-page .report-table col:nth-child(2)  { width: 4.0% !important; }
        .balik-nama-page .report-table col:nth-child(3)  { width: 8.1% !important; }
        .balik-nama-page .report-table col:nth-child(4)  { width: 7.2% !important; }
        .balik-nama-page .report-table col:nth-child(5)  { width: 3.8% !important; }
        .balik-nama-page .report-table col:nth-child(6)  { width: 3.8% !important; }
        .balik-nama-page .report-table col:nth-child(7)  { width: 8.1% !important; }
        .balik-nama-page .report-table col:nth-child(8)  { width: 6.7% !important; }
        .balik-nama-page .report-table col:nth-child(9)  { width: 6.7% !important; }
        .balik-nama-page .report-table col:nth-child(10) { width: 3.8% !important; }
        .balik-nama-page .report-table col:nth-child(11) { width: 7.0% !important; }
        .balik-nama-page .report-table col:nth-child(12) { width: 7.0% !important; }
        .balik-nama-page .report-table col:nth-child(13) { width: 6.7% !important; }
        .balik-nama-page .report-table col:nth-child(14) { width: 6.7% !important; }
        .balik-nama-page .report-table col:nth-child(15) { width: 5.8% !important; }
        .balik-nama-page .report-table col:nth-child(16) { width: 5.8% !important; }
        .balik-nama-page .report-table col:nth-child(17) { width: 6.8% !important; }
    }


    /* =========================================================
       ALERT DATA KOSONG — SAMA DENGAN DAFTAR SERTIFIKAT PECAHAN
       Modal informasi yang tampil saat hasil laporan tidak
       menghasilkan baris data sama sekali.
       ========================================================= */
    #pengajuanBalikNamaNoDataAlertModal .modal-dialog {
        max-width: 380px;
    }

    #pengajuanBalikNamaNoDataAlertModal .modal-content {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
    }

    #pengajuanBalikNamaNoDataAlertModal .alert-icon-wrapper {
        display: flex;
        width: 64px;
        height: 64px;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        border-radius: 50%;
        background: #eff6ff;
        color: #2563eb;
        font-size: 28px;
    }

    #pengajuanBalikNamaNoDataAlertModal .alert-title {
        margin-bottom: 8px;
        color: #172033;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 18px;
        font-weight: 700;
    }

    #pengajuanBalikNamaNoDataAlertModal .alert-message {
        margin-bottom: 24px;
        color: #475569;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 14px;
    }

    #pengajuanBalikNamaNoDataAlertModal .alert-btn-ok {
        width: 100%;
        padding: 10px 32px;
        border: 0;
        border-radius: 12px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        transition: transform 0.15s, box-shadow 0.15s;
    }

    #pengajuanBalikNamaNoDataAlertModal .alert-btn-ok:hover {
        transform: translateY(-1px);
        color: #ffffff;
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
    }
</style>

<!-- MODAL ALERT DATA KOSONG -->
<div class="modal fade" id="pengajuanBalikNamaNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4" style="text-align: center; padding: 1.5rem;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message" id="pengajuanBalikNamaNoDataMessage">Data tidak ditemukan.</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    data-dismiss="modal"
                    onclick="hidePengajuanBalikNamaNoDataAlert()"
                >OK</button>
            </div>
        </div>
    </div>
</div>

<div class="balik-nama-page">
    <input
        type="hidden"
        id="perusahaan"
        value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}"
    >
    <input
        type="hidden"
        id="nama_perusahaan_session"
        value="{{ session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? '' }}"
    >
    <input type="hidden" id="sektor" value="*">

    <div class="balik-nama-view-version" id="balikNamaViewVersion">
        <span>DAFTAR PENGAJUAN BALIK NAMA</span>
        <code id="balikNamaUnitBadge">
            UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}
        </code>
    </div>

    <section class="desktop-toolbar">
        <div class="filter-row">
            <label class="field-label" for="blok_awal">Blok:</label>

            <input
                type="text"
                id="blok_awal"
                class="desktop-input blok-input"
                value="A"
                maxlength="30"
            >

            <span class="range-text">s.d</span>

            <input
                type="text"
                id="blok_akhir"
                class="desktop-input blok-input"
                value="Z"
                maxlength="30"
            >

            <label class="field-label date-label" for="tgl_awal">Tgl. Input:</label>

            <input
                type="date"
                id="tgl_awal"
                class="desktop-input date-input"
            >

            <span class="range-text">s.d</span>

            <input
                type="date"
                id="tgl_akhir"
                class="desktop-input date-input"
            >

            <div class="bn-action-stack">
                <button
                    type="button"
                    class="ok-button"
                    onclick="getData()"
                >
                    OK
                </button>

                <button
                    type="button"
                    class="bn-print-button"
                    id="balikNamaPrintButton"
                    data-print-version="V8-ORIENTATION-FIX"
                    onclick="printBalikNamaReport()"
                    disabled
                >
                    PRINT
                </button>
            </div>
        </div>

        <div class="filter-row">
            <span class="field-label">Sektor:</span>

            <div class="sector-wrap">
                <div id="sektorEntry" class="lookup-display">
                    Semua Sektor
                </div>

                <button
                    type="button"
                    class="lookup-button"
                    onclick="getSektorModal()"
                    title="Pilih sektor"
                    aria-label="Pilih sektor"
                >
                    <i class="fas fa-binoculars"></i>
                </button>
            </div>
        </div>
    </section>

    <section class="report-workspace">
        <div id="loadingInfo" class="loading-info">
            <i class="fas fa-spinner fa-spin"></i>
            Mengambil data pengajuan balik nama...
        </div>

        <div id="mainDisplay">
            <div class="initial-report-state">
                <div class="initial-report-state-inner">
                    <div class="initial-report-icon">
                        <i class="fas fa-table"></i>
                    </div>
                    <div class="initial-report-text">
                        Silahkan isi filter kemudian klik <strong>OK</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="balikNamaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-balik-nama">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="balikNamaModalTitle" class="modal-title">
                    Pilih Sektor
                </h5>
                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="Close"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body" id="balikNamaModalContent"></div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function () {
        console.info(
            'DFTR_PENGAJUAN_BALIK_NAMA_VIEW=V8-20260827-PRINT-ORIENTATION-FIX'
        );

        resetInitialState();

        $('#blok_awal, #blok_akhir').on('input', function () {
            $(this).val(
                String($(this).val() || '').toUpperCase()
            );
        });

    });

    $(window).on('pageshow', function (event) {
        var pageEvent = event.originalEvent || event;

        if (pageEvent.persisted) {
            resetInitialState();
        }
    });

    function resetInitialState() {
        $('#blok_awal').val('A');
        $('#blok_akhir').val('Z');
        setDefaultDate();
        $('#sektor').val('*');
        $('#sektorEntry').text('Semua Sektor');
        $('#balikNamaPrintButton').prop('disabled', true);
        $('#loadingInfo').hide();
        hidePengajuanBalikNamaNoDataAlert();
        renderInitialState();
    }

    function setDefaultDate() {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var today = year + '-' + month + '-' + day;

        $('#tgl_awal').val(today);
        $('#tgl_akhir').val(today);
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeJs(value) {
        return String(
            value === null || value === undefined ? '' : value
        )
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r?\n/g, ' ');
    }

    function formatDate(value) {
        if (!value) {
            return '-';
        }

        var text = String(value);
        var match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);

        if (match) {
            return match[3] + '-' + match[2] + '-' + match[1];
        }

        return text;
    }

    function formatNumber(value) {
        if (value === null || value === undefined || value === '') {
            return '-';
        }

        var number = Number(value);

        if (!isFinite(number)) {
            return escapeHtml(value);
        }

        return number.toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function valueOrDash(value) {
        return value === null || value === undefined || value === ''
            ? '-'
            : value;
    }

    function pickValue(item, keys) {
        item = item || {};

        for (var i = 0; i < keys.length; i++) {
            if (
                item[keys[i]] !== null
                && item[keys[i]] !== undefined
                && item[keys[i]] !== ''
            ) {
                return item[keys[i]];
            }
        }

        return null;
    }

    function extractCompanyName(value) {
        var raw = String(value || '').replace(/\u00a0/g, ' ');
        var locationMatch = raw.match(/Lokasi\s*:[^\r\n|]*?-\s*([^\r\n|]+)/i);
        var companyMatch = raw.match(/\b((?:PT\.?|CV\.?|PD\.?|PERUM)\s+[^\r\n|]+)/i);
        var name = locationMatch ? locationMatch[1] : (companyMatch ? companyMatch[1] : '');

        return String(name || '')
            .replace(/\s+(?:Hi\s*,|Logout\b|Keluar\b|LAND DOCUMENT\b|UNIT\s+[A-Z0-9_-]+\b).*$/i, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function companyNameFromLayout() {
        var selectors = [
            '[data-nama-perusahaan]', '[data-company-name]', '[data-unit-name]',
            'input[name="nama_perusahaan"]', 'input[name="nama_pt"]',
            '#nama_perusahaan', '#nama_pt',
            'header input', 'nav input', '.navbar input', '.topbar input',
            'header [title]', 'nav [title]', '.navbar [title]', '.topbar [title]',
            '[class*="unit"]', '[id*="unit"]'
        ];
        var elements = document.querySelectorAll(selectors.join(','));

        for (var i = 0; i < elements.length; i++) {
            var element = elements[i];
            var candidates = [
                element.value,
                element.getAttribute('data-nama-perusahaan'),
                element.getAttribute('data-company-name'),
                element.getAttribute('data-unit-name'),
                element.getAttribute('title'),
                element.getAttribute('aria-label'),
                element.textContent
            ];

            for (var j = 0; j < candidates.length; j++) {
                var name = extractCompanyName(candidates[j]);
                if (name) {
                    return name;
                }
            }
        }

        return '';
    }

    function resolveReportCompany(first) {
        var unit = String($('#perusahaan').val() || '').trim().toUpperCase();
        var rowName = pickValue(first || {}, [
            'NAMA_PT', 'nama_pt',
            'ATAS_NAMA_PT', 'atas_nama_pt',
            'NAMA_PERUSAHAAN', 'nama_perusahaan'
        ]);
        var sessionName = String($('#nama_perusahaan_session').val() || '').trim();
        var cacheKey = 'sris.report-company.' + unit;
        var cachedName = '';

        try {
            cachedName = localStorage.getItem(cacheKey) || '';
        } catch (error) {
            cachedName = '';
        }

        var company = extractCompanyName(rowName) || String(rowName || '').trim()
            || companyNameFromLayout()
            || extractCompanyName(sessionName) || sessionName
            || cachedName
            || unit
            || '-';

        if (company && company !== '-' && company.toUpperCase() !== unit) {
            try {
                localStorage.setItem(cacheKey, company);
            } catch (error) {
                // Browser dapat menolak storage; nama tetap dipakai untuk render saat ini.
            }
        }

        return company;
    }

    function pairText(number, date) {
        return escapeHtml(valueOrDash(number))
            + '<br>'
            + escapeHtml(formatDate(date));
    }

    function getFilterData() {
        return {
            _token: '{{ csrf_token() }}',
            blok_awal: $('#blok_awal').val() || 'A',
            blok_akhir: $('#blok_akhir').val() || 'Z',
            tgl_awal: $('#tgl_awal').val(),
            tgl_akhir: $('#tgl_akhir').val(),
            perusahaan: $('#perusahaan').val(),
            sektor: $('#sektor').val() || '*'
        };
    }

    function validateFilter() {
        if (!$('#blok_awal').val() || !$('#blok_akhir').val()) {
            alert('Rentang blok wajib diisi.');
            return false;
        }

        if (!$('#tgl_awal').val() || !$('#tgl_akhir').val()) {
            alert('Rentang tanggal input wajib diisi.');
            return false;
        }

        if ($('#tgl_awal').val() > $('#tgl_akhir').val()) {
            alert('Tanggal awal tidak boleh melebihi tanggal akhir.');
            return false;
        }

        if (!$('#perusahaan').val()) {
            alert('Unit/perusahaan tidak tersedia.');
            return false;
        }

        return true;
    }

    function renderInitialState() {
        var html = '';

        html += '<div class="initial-report-state">';
        html += '<div class="initial-report-state-inner">';
        html += '<div class="initial-report-icon">';
        html += '<i class="fas fa-table"></i>';
        html += '</div>';
        html += '<div class="initial-report-text">';
        html += 'Silahkan isi filter kemudian klik <strong>OK</strong>';
        html += '</div>';
        html += '</div>';
        html += '</div>';

        $('#mainDisplay').html(html);
    }

    function renderEmptyReport() {
        var blok = String($('#blok_awal').val() || 'A').toUpperCase()
            + ' s/d '
            + String($('#blok_akhir').val() || 'Z').toUpperCase();

        var period = formatDate($('#tgl_awal').val())
            + ' s/d '
            + formatDate($('#tgl_akhir').val());

        var html = '';

        html += '<div class="report-paper">';
        html += '<h2 class="report-title">';
        html += 'Daftar Pengajuan Sertipikat Balik Nama';
        html += '</h2>';
        html += '<div class="report-meta">';
        html += '<div>BLOK: ' + escapeHtml(blok) + '</div>';
        html += '<div>Tgl. Input AJB: ' + escapeHtml(period) + '</div>';
        html += '</div>';
        html += '<div class="empty-report"></div>';
        html += '</div>';

        $('#mainDisplay').html(html);
    }

    function addSektor(kode, deskripsi) {
        $('#sektor').val(kode || '*');
        $('#sektorEntry').text(deskripsi || 'Semua Sektor');

        if (typeof $('#balikNamaModal').modal === 'function') {
            $('#balikNamaModal').modal('hide');
        }
    }

    function filterSektorModal(keyword) {
        var search = String(keyword || '').toLowerCase().trim();

        $('#balikNamaModal .modal-table tbody tr').each(function () {
            $(this).toggle(
                $(this).text().toLowerCase().indexOf(search) !== -1
            );
        });
    }

    function getSektorModal() {
        var perusahaan = String(
            $('#perusahaan').val() || ''
        ).trim();

        if (!perusahaan) {
            alert('Unit/perusahaan belum tersedia.');
            return;
        }

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_sektor',
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            data: {
                _token: '{{ csrf_token() }}',
                perusahaan: perusahaan
            },
            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (
                        response && Array.isArray(response.data)
                            ? response.data
                            : []
                    );

                var html = '';

                html += '<input type="text" class="modal-search" ';
                html += 'placeholder="Cari sektor..." ';
                html += 'onkeyup="filterSektorModal(this.value)">';

                html += '<div class="modal-table-wrap">';
                html += '<table class="modal-table">';
                html += '<thead><tr>';
                html += '<th>Kode</th>';
                html += '<th>Deskripsi</th>';
                html += '<th>Perusahaan</th>';
                html += '</tr></thead><tbody>';

                html += '<tr onclick="addSektor(\'*\', \'Semua Sektor\')">';
                html += '<td>*</td>';
                html += '<td>Semua Sektor</td>';
                html += '<td>' + escapeHtml(perusahaan) + '</td>';
                html += '</tr>';

                $.each(rows, function (index, item) {
                    var kode = item.KD_SEKTOR
                        || item.kd_sektor
                        || '';

                    var deskripsi = item.DESKRIPSI
                        || item.deskripsi
                        || kode;

                    var unit = item.KD_PERUSAHAAN
                        || item.kd_perusahaan
                        || perusahaan;

                    html += '<tr onclick="addSektor(\''
                        + escapeJs(kode)
                        + '\', \''
                        + escapeJs(deskripsi)
                        + '\')">';

                    html += '<td>' + escapeHtml(kode) + '</td>';
                    html += '<td>' + escapeHtml(deskripsi) + '</td>';
                    html += '<td>' + escapeHtml(unit) + '</td>';
                    html += '</tr>';
                });

                if (rows.length < 1) {
                    html += '<tr>';
                    html += '<td colspan="3" style="padding:20px;text-align:center;">';
                    html += 'Data sektor tidak ditemukan.';
                    html += '</td></tr>';
                }

                html += '</tbody></table></div>';

                $('#balikNamaModalTitle').text('Pilih Sektor');
                $('#balikNamaModalContent').html(html);

                if (typeof $('#balikNamaModal').modal === 'function') {
                    $('#balikNamaModal').modal('show');
                }
            },
            error: function (xhr) {
                var message = 'Gagal mengambil data sektor.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += ' ' + xhr.responseJSON.message;
                }

                alert(message);
            }
        });
    }

    /*
     * Alert data kosong, mengikuti Daftar Sertifikat Pecahan.
     * Hanya menampilkan pesan; pengambilan data dan render laporan
     * tetap memakai alur yang sudah ada.
     */
    function showPengajuanBalikNamaNoDataAlert(message) {
        var text = message || 'Data tidak ditemukan......!';

        $('#pengajuanBalikNamaNoDataMessage').text(text);

        if (typeof $('#pengajuanBalikNamaNoDataAlertModal').modal === 'function') {
            $('#pengajuanBalikNamaNoDataAlertModal').modal('show');
        } else {
            alert(text);
        }
    }

    function hidePengajuanBalikNamaNoDataAlert() {
        if (typeof $('#pengajuanBalikNamaNoDataAlertModal').modal === 'function') {
            $('#pengajuanBalikNamaNoDataAlertModal').modal('hide');
        }
    }

    function getData() {
        if (!validateFilter()) {
            return;
        }

        hidePengajuanBalikNamaNoDataAlert();

        $('#balikNamaPrintButton').prop('disabled', true);
        $('#loadingInfo').show();

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_data',
            dataType: 'json',
            timeout: 120000,
            headers: {
                'Accept': 'application/json'
            },
            data: getFilterData(),
            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (
                        response && Array.isArray(response.data)
                            ? response.data
                            : []
                    );

                if (rows.length === 0) {
                    showPengajuanBalikNamaNoDataAlert('Data tidak ditemukan......!');
                }

                renderReport(rows);
            },
            error: function (xhr, textStatus, errorThrown) {
                $('#balikNamaPrintButton').prop('disabled', true);

                var detail = '';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    detail = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    detail = String(xhr.responseText)
                        .replace(/<[^>]+>/g, ' ')
                        .replace(/\s+/g, ' ')
                        .trim()
                        .substring(0, 700);
                } else {
                    detail = String(errorThrown || textStatus || '');
                }

                $('#mainDisplay').html(
                    '<div class="alert alert-danger">'
                    + 'Gagal mengambil data. '
                    + escapeHtml(detail)
                    + '</div>'
                );
            },
            complete: function () {
                $('#loadingInfo').hide();
            }
        });
    }

    function printBalikNamaReport() {
        /*
         * V8 PRINT + ORIENTATION FIX
         *
         * V7 sudah memperbaiki area klik tombol PRINT dengan z-index.
         * Di V8 mekanisme print kembali memakai iframe TERISOLASI seperti
         * fitur Daftar Undangan Surat Rumah yang menampilkan pilihan
         * Portrait / Landscape pada dialog print.
         *
         * Penting:
         * - Button click fix V7 tetap dipertahankan.
         * - Tidak ada @page { size: ... } agar orientation tidak dikunci.
         * - CSS layout/template utama tidak ikut masuk ke iframe.
         */

        var $button = $('#balikNamaPrintButton');

        console.info('BALIK_NAMA_PRINT_V8: click diterima');

        if ($button.prop('disabled')) {
            console.warn('BALIK_NAMA_PRINT_V8: tombol masih disabled');
            return;
        }

        if (!$('#mainDisplay .standard-report-header').length) {
            console.warn('BALIK_NAMA_PRINT_V8: report belum siap');
            return;
        }

        var reportHtml = $('#mainDisplay').html();

        if (!reportHtml) {
            console.warn('BALIK_NAMA_PRINT_V8: HTML report kosong');
            return;
        }

        $('#balikNamaNativePrintFrame').remove();

        var frame = document.createElement('iframe');
        frame.id = 'balikNamaNativePrintFrame';
        frame.setAttribute('aria-hidden', 'true');

        /*
         * Jangan pakai display:none.
         * Iframe tetap ada di DOM tetapi diletakkan di luar viewport.
         * Ini lebih aman untuk Firefox dibanding iframe 0x0/display:none.
         */
        frame.style.position = 'fixed';
        frame.style.left = '-10000px';
        frame.style.top = '0';
        frame.style.width = '1200px';
        frame.style.height = '900px';
        frame.style.border = '0';
        frame.style.opacity = '0';
        frame.style.pointerEvents = 'none';

        document.body.appendChild(frame);

        var frameWindow = frame.contentWindow;
        var frameDocument = frame.contentDocument || frameWindow.document;

        var printCss = `
            /*
             * JANGAN menambahkan size: A3 landscape / landscape / portrait.
             * Dengan size tidak dikunci, browser dapat menampilkan
             * pilihan Orientation: Portrait / Landscape.
             */
            @page {
                margin: 8mm;
            }

            html,
            body {
                width: 100%;
                margin: 0;
                padding: 0;
                background: #fff;
                color: #000;
                font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            }

            *,
            *::before,
            *::after {
                box-sizing: border-box;
            }

            .report-paper {
                width: 100%;
                min-width: 0;
                margin: 0;
                padding: 0;
                background: #fff;
                color: #000;
            }

            .standard-report-header {
                display: grid;
                grid-template-columns: 1fr 2fr 1fr;
                gap: 12px;
                align-items: center;
                margin-bottom: 7px;
                padding: 9px 10px;
                border: 1px solid #777;
                background: #fff;
            }

            .standard-report-company {
                color: #000;
                font-size: 9.5px;
                font-weight: 700;
            }

            .standard-report-title {
                color: #000;
                font-family: Cambria, Georgia, "Times New Roman", serif;
                font-size: 14px;
                font-weight: 700;
                line-height: 1.2;
                text-align: center;
            }

            .standard-report-period {
                color: #000;
                font-size: 8.5px;
                font-weight: 600;
                line-height: 1.4;
                text-align: right;
            }

            .standard-report-subtitle {
                display: grid;
                grid-template-columns: 1fr auto 1fr;
                align-items: center;
                gap: 10px;
                margin-bottom: 7px;
                padding: 6px 8px;
                border: 1px solid #aaa;
                background: #fff;
                color: #000;
                font-size: 8.5px;
            }

            .standard-report-subtitle-label {
                justify-self: start;
            }

            .standard-report-sector {
                justify-self: center;
                color: #000;
                font-weight: 700;
            }

            .standard-report-live {
                justify-self: end;
                color: #000;
                font-size: 7.5px;
                font-weight: 700;
            }

            .report-table-wrap {
                width: 100%;
                max-width: 100%;
                max-height: none;
                min-height: 0;
                margin: 0;
                overflow: visible;
                border: 0;
                background: #fff;
            }

            .report-table {
                width: 100%;
                min-width: 0;
                max-width: 100%;
                table-layout: fixed;
                border-collapse: collapse;
                border-spacing: 0;
                border: 1px solid #000;
                background: #fff;
                color: #000;
                font-size: 6.8px;
                line-height: 1.2;
            }

            /*
             * Hilangkan width pixel dari colgroup layar.
             * Browser akan membagi tabel sesuai ukuran/orientasi kertas
             * yang dipilih user pada dialog print.
             */
            .report-table col {
                width: auto !important;
            }

            .report-table thead {
                display: table-header-group;
            }

            .report-table tbody {
                display: table-row-group;
            }

            .report-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .report-table th,
            .report-table td {
                position: static;
                padding: 2.5px;
                border: 1px solid #000;
                background: #fff;
                color: #000;
                box-shadow: none;
                vertical-align: middle;
                overflow: visible;
                overflow-wrap: anywhere;
                word-break: normal;
            }

            .report-table th {
                text-align: center;
                font-size: 6.5px;
                font-weight: 700;
                line-height: 1.18;
            }

            .center {
                text-align: center;
            }

            .right {
                text-align: right;
                white-space: nowrap;
            }

            .report-table tbody td:nth-child(3) {
                color: #000;
                font-weight: 700;
            }
        `;

        frameDocument.open();
        frameDocument.write(
            '<!DOCTYPE html>'
            + '<html>'
            + '<head>'
            + '<meta charset="utf-8">'
            + '<meta name="viewport" content="width=device-width,initial-scale=1">'
            + '<title>Daftar Pengajuan Balik Nama</title>'
            + '<style>' + printCss + '</style>'
            + '</head>'
            + '<body>' + reportHtml + '</body>'
            + '</html>'
        );
        frameDocument.close();

        /*
         * Tunggu document iframe selesai dibuat.
         * Karena click-layer sudah diperbaiki di V7, event click benar-benar
         * sampai ke fungsi ini. Iframe hanya dipakai untuk print isolation.
         */
        window.setTimeout(function () {
            try {
                frameWindow.focus();
                frameWindow.print();
            } catch (error) {
                console.error('BALIK_NAMA_PRINT_V8: gagal membuka print', error);

                /*
                 * Fallback terakhir apabila browser menolak print iframe.
                 */
                try {
                    window.focus();
                    window.print();
                } catch (fallbackError) {
                    console.error(
                        'BALIK_NAMA_PRINT_V8: fallback native print gagal',
                        fallbackError
                    );
                    alert('Dialog print gagal dibuka. Silakan coba kembali.');
                }
            } finally {
                window.setTimeout(function () {
                    $('#balikNamaNativePrintFrame').remove();
                }, 1500);
            }
        }, 250);
    }

    function renderReport(rows) {
        rows = Array.isArray(rows) ? rows : [];

        var blok = String($('#blok_awal').val() || 'A').toUpperCase()
            + ' s/d '
            + String($('#blok_akhir').val() || 'Z').toUpperCase();

        var period = formatDate($('#tgl_awal').val())
            + ' s/d '
            + formatDate($('#tgl_akhir').val());

        var html = '';

        var first = rows.length > 0 ? rows[0] : {};
        var company = resolveReportCompany(first);
        var sector = String($('#sektorEntry').text() || 'Semua Sektor').trim();

        html += '<div class="report-paper">';
        html += '<div class="standard-report-header">';
        html += '<div class="standard-report-company">' + escapeHtml(company) + '</div>';
        html += '<div class="standard-report-title">Daftar Pengajuan Sertipikat Balik Nama</div>';
        html += '<div class="standard-report-period">';
        html += 'BLOK: ' + escapeHtml(blok) + '<br>';
        html += 'Tgl. Input AJB: ' + escapeHtml(period);
        html += '</div></div>';

        html += '<div class="standard-report-subtitle">';
        html += '<span class="standard-report-subtitle-label">Sektor/Cluster:</span>';
        html += '<strong class="standard-report-sector">' + escapeHtml(sector) + '</strong>';
        html += '<span class="standard-report-live">Live data</span>';
        html += '</div>';

        html += '<div class="report-table-wrap">';
        html += '<table class="report-table">';

        html += '<colgroup>';
        html += '<col style="width:45px">';
        html += '<col style="width:90px">';
        html += '<col style="width:180px">';
        html += '<col style="width:160px">';
        html += '<col style="width:85px">';
        html += '<col style="width:85px">';
        html += '<col style="width:180px">';
        html += '<col style="width:150px">';
        html += '<col style="width:150px">';
        html += '<col style="width:85px">';
        html += '<col style="width:155px">';
        html += '<col style="width:155px">';
        html += '<col style="width:150px">';
        html += '<col style="width:150px">';
        html += '<col style="width:130px">';
        html += '<col style="width:130px">';
        html += '<col style="width:150px">';
        html += '</colgroup>';

        html += '<thead><tr>';
        html += '<th>No.</th>';
        html += '<th>Blok/Nomor</th>';
        html += '<th>Nama Pembeli</th>';
        html += '<th>Nama PT</th>';
        html += '<th>Luas Tanah</th>';
        html += '<th>Luas Bangunan</th>';
        html += '<th>Jalan</th>';
        html += '<th>No./Tgl. Sertipikat</th>';
        html += '<th>No./Tgl. Surat Ukur</th>';
        html += '<th>Luas SU</th>';
        html += '<th>Permohonan Balik Nama</th>';
        html += '<th>No./Tgl. Akta</th>';
        html += '<th>No. Notaris</th>';
        html += '<th>Notaris</th>';
        html += '<th>Harga</th>';
        html += '<th>Masa Berlaku</th>';
        html += '<th>Sektor</th>';
        html += '</tr></thead><tbody>';

        $.each(rows, function (index, item) {
            item = item || {};

            html += '<tr>';
            html += '<td class="center">' + (index + 1) + '</td>';

            html += '<td class="center">'
                + escapeHtml(
                    valueOrDash(
                        pickValue(item, ['BLOK_NOMOR', 'blok_nomor'])
                    )
                )
                + '</td>';

            html += '<td>'
                + escapeHtml(
                    valueOrDash(
                        pickValue(item, ['NASABAH_NAMA', 'nasabah_nama'])
                    )
                )
                + '</td>';

            html += '<td>'
                + escapeHtml(
                    valueOrDash(
                        pickValue(item, ['NAMA_PT', 'nama_pt'])
                    )
                )
                + '</td>';

            html += '<td class="right">'
                + formatNumber(
                    pickValue(item, ['LUAS_TANAH', 'luas_tanah'])
                )
                + '</td>';

            html += '<td class="right">'
                + formatNumber(
                    pickValue(item, ['LUAS_BANGUNAN', 'luas_bangunan'])
                )
                + '</td>';

            html += '<td>'
                + escapeHtml(
                    valueOrDash(
                        pickValue(item, ['JALAN', 'jalan'])
                    )
                )
                + '</td>';

            html += '<td>'
                + pairText(
                    pickValue(item, ['NO_SERTIPIKAT', 'no_sertipikat']),
                    pickValue(item, ['TGL_SERTIPIKAT', 'tgl_sertipikat'])
                )
                + '</td>';

            html += '<td>'
                + pairText(
                    pickValue(item, ['SU_PISAH', 'su_pisah']),
                    pickValue(item, ['TGL_SU_PISAH', 'tgl_su_pisah'])
                )
                + '</td>';

            html += '<td class="right">'
                + formatNumber(
                    pickValue(item, ['LUAS_SUP', 'luas_sup'])
                )
                + '</td>';

            html += '<td>'
                + pairText(
                    pickValue(item, ['MOHON_BLK_NM', 'mohon_blk_nm']),
                    pickValue(item, ['TGL_MOHON_BLK_NM', 'tgl_mohon_blk_nm'])
                )
                + '</td>';

            html += '<td>'
                + pairText(
                    pickValue(item, ['NO_AKTA', 'no_akta']),
                    pickValue(item, ['TGL_AKTA', 'tgl_akta'])
                )
                + '</td>';

            html += '<td>'
                + escapeHtml(
                    valueOrDash(
                        pickValue(item, ['NO_NOTARIS', 'no_notaris'])
                    )
                )
                + '</td>';

            html += '<td>'
                + escapeHtml(
                    valueOrDash(
                        pickValue(item, ['NOTARIS', 'notaris'])
                    )
                )
                + '</td>';

            html += '<td class="right">'
                + formatNumber(
                    pickValue(item, ['HARGA', 'harga'])
                )
                + '</td>';

            html += '<td class="center">'
                + escapeHtml(
                    formatDate(
                        pickValue(item, ['TGL_BERLAKU', 'tgl_berlaku'])
                    )
                )
                + '</td>';

            html += '<td>'
                + escapeHtml(
                    valueOrDash(
                        pickValue(item, ['NAMA_SEKTOR', 'nama_sektor'])
                    )
                )
                + '</td>';

            html += '</tr>';
        });

        if (!rows.length) {
            html += '<tr><td colspan="17" style="height:130px;text-align:center;color:#64748b;vertical-align:middle;">';
            html += 'Data pengajuan balik nama tidak ditemukan untuk filter yang dipilih.';
            html += '</td></tr>';
        }

        html += '</tbody></table></div>';
        html += '</div>';

        $('#mainDisplay').html(html);
        $('#balikNamaPrintButton').prop('disabled', false);
    }
</script>
@endsection