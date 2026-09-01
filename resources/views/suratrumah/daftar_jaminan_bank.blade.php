@extends('layouts.template')

{{-- VERIFIED V14-CLEAN-LABEL-FIT 2026-08-19:
     - CSS report header yang terduplikasi dikonsolidasikan.
     - Fungsi JavaScript escapeJs() yang tidak pernah dipanggil dihapus.
     - CSS .rjb-right dan .rjb-report-filter yang tidak pernah dipakai dihapus.
     - Label filter dibuat terbaca penuh tanpa mengecilkan font.
     - Lebar area label dinaikkan dan minimum workspace dibuat 900px agar zoom tidak memotong label.
     - AJAX, endpoint, getFilterData(), getData(), render data, modal, print, dan mapping field tetap.
--}}

@section('content')
<style>
    /* =========================================================
       DFTR JAMINAN BANK — VISUAL MATCH DAFTAR SERTIPIKAT PECAHAN
       V3 2026-08-18
       - Hanya mengubah tampilan/layout view.
       - ID, AJAX, filter, endpoint, dan struktur data tetap.
       - Layout dibuat zoom-stable: saat browser zoom berubah,
         struktur 2 area filter + tombol kanan tidak berubah.
       ========================================================= */

    .rjb-page {
        --rjb-ink: #172033;
        --rjb-muted: #667085;
        --rjb-line: #dbe3ef;
        --rjb-blue: #2563eb;
        --rjb-blue-dark: #1d4ed8;
        --rjb-cyan: #38bdf8;
        --rjb-violet: #6366f1;
        --rjb-green: #10b981;
        position: relative;
        isolation: auto;
        width: 100%;
        min-width: 900px;
        min-height: 100%;
        padding: 18px 12px 32px;
        overflow-x: visible;
        color: var(--rjb-ink);
        background:
            radial-gradient(circle at 95% 2%, rgba(37, 99, 235, 0.07), transparent 28%),
            radial-gradient(circle at 8% 96%, rgba(56, 189, 248, 0.05), transparent 26%),
            #f3f6fb;
    }

    .rjb-page,
    .rjb-page * {
        box-sizing: border-box;
    }

    .rjb-page,
    .rjb-page input,
    .rjb-page select,
    .rjb-page button,
    .rjb-page textarea,
    .rjb-page label,
    .rjb-page table,
    .rjb-page td,
    #rjbModal {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    }

    /* =========================================================
       HEADER WORKSPACE — sama karakter dengan Sertipikat Pecahan
       ========================================================= */
    .rjb-view-version {
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
        background: linear-gradient(90deg, #ffffff 0%, #ffffff 65%, #f8fbff 100%);
        color: #172033;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
    }

    .rjb-view-version::before {
        content: "◈";
        position: absolute;
        left: 20px;
        display: grid;
        width: 34px;
        height: 34px;
        place-items: center;
        border: 0;
        border-radius: 11px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.24);
        font-size: 18px;
    }

    .rjb-view-version::after {
        content: "";
        position: absolute;
        top: -82px;
        right: 40px;
        width: 260px;
        height: 190px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(37, 99, 235, 0.08), transparent 68%);
        pointer-events: none;
    }

    .rjb-view-version span {
        position: relative;
        z-index: 1;
        color: #172033;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: 0.11em;
        text-transform: uppercase;
    }

    .rjb-unit-badge {
        position: relative;
        z-index: 1;
        padding: 7px 12px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1e40af;
        font-family: "SFMono-Regular", Consolas, monospace !important;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.06em;
    }

    /* =========================================================
       FILTER PANEL
       Struktur grid tetap:
       kolom 1 = Blok/Sektor/Jenis,
       kolom 2 = Tanggal/Status,
       kolom 3 = tombol OK.
       ========================================================= */
    .rjb-filter-panel {
        position: relative;
        padding: 20px;
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
    }

    .rjb-filter-panel::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: linear-gradient(180deg, #38bdf8 0%, #2563eb 52%, #1d4ed8 100%);
    }

    .rjb-filter-panel::after {
        content: "Jaminan Bank";
        position: absolute;
        right: 20px;
        bottom: 12px;
        color: rgba(37, 99, 235, 0.035);
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 34px;
        font-weight: 950;
        letter-spacing: 0.08em;
        pointer-events: none;
    }

    .rjb-filter-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns:
            minmax(0, 1fr)
            minmax(0, 1fr)
            82px;
        gap: 13px 14px;
        align-items: center;
    }

    .rjb-field-row {
        display: grid;
        grid-template-columns: 118px minmax(0, 1fr);
        gap: 9px;
        align-items: center;
        min-width: 0;
    }

    /*
     * Label diberi ruang tetap yang cukup.
     * Font tidak dikecilkan. Letter-spacing dipadatkan agar label panjang
     * seperti SEKTOR/CLUSTER dan JENIS JAMINAN tetap terbaca penuh.
     */
    .rjb-label {
        overflow: visible;
        color: #475467;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: .045em;
        text-overflow: clip;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .rjb-range {
        display: grid;
        grid-template-columns:
            minmax(86px, 1fr)
            34px
            minmax(86px, 1fr);
        gap: 7px;
        align-items: center;
        min-width: 0;
    }

    .rjb-input,
    .rjb-select,
    .rjb-lookup-display {
        width: 100%;
        min-width: 0;
        height: 42px;
        padding: 8px 10px;
        border: 1px solid #c8d3e1;
        border-radius: 12px;
        background: #ffffff;
        color: #101828;
        font-size: 12px;
        font-weight: 650;
        outline: 0;
        transition:
            border-color .18s ease,
            box-shadow .18s ease,
            background .18s ease;
    }

    .rjb-input:hover,
    .rjb-select:hover,
    .rjb-lookup-display:hover {
        border-color: #aebed1;
    }

    .rjb-input:focus,
    .rjb-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
    }

    #tgl_awal_bank,
    #tgl_akhir_bank {
        padding-left: 8px;
        padding-right: 6px;
        font-variant-numeric: tabular-nums;
    }

    #tgl_awal_bank::-webkit-calendar-picker-indicator,
    #tgl_akhir_bank::-webkit-calendar-picker-indicator {
        flex: 0 0 auto;
        margin-left: 3px;
    }

    .rjb-separator {
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
        letter-spacing: .04em;
        text-align: center;
    }

    .rjb-lookup {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 42px;
        gap: 8px;
    }

    .rjb-lookup-display {
        display: flex;
        align-items: center;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .rjb-lookup-button,
    .rjb-ok-button {
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
        transition:
            transform .18s ease,
            box-shadow .18s ease,
            background .18s ease;
    }

    .rjb-lookup-button {
        width: 42px;
        border: 1px solid #bfdbfe;
        border-radius: 13px;
        background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%);
        color: #1d4ed8;
    }

    .rjb-lookup-button:hover {
        transform: translateY(-1px);
        background: linear-gradient(145deg, #dbeafe 0%, #bfdbfe 100%);
        box-shadow: 0 7px 15px rgba(37, 99, 235, 0.12);
    }

    /*
     * Tombol OK menempel ke edge kanan filter card
     * persis pola V9.5 halaman Sertipikat Pecahan.
     */
    .rjb-ok-button {
        width: 82px;
        min-width: 82px;
        grid-column: 3;
        grid-row: 1;
        justify-self: end;
        margin-right: -20px;
        border-radius: 13px 0 0 13px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .rjb-ok-button::after {
        content: "";
        position: absolute;
        inset: -40% auto -40% -55%;
        width: 45%;
        transform: skewX(-22deg);
        background: rgba(255, 255, 255, .28);
        transition: left .45s ease;
    }

    .rjb-ok-button:hover {
        transform: translateY(-1px);
        background: linear-gradient(135deg, #2f6ff0 0%, #1e4fc4 100%);
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.30);
    }

    .rjb-ok-button:hover::after {
        left: 125%;
    }

    /* =========================================================
       PRINT ACTION — aman untuk struktur grid lama
       Action stack menggantikan SATU item tombol OK lama.
       PRINT diposisikan absolute ke bawah sehingga tidak menjadi
       item grid baru dan tidak menggeser Sektor/Status/Jenis Jaminan.
       ========================================================= */
    .rjb-action-stack {
        position: relative;
        width: 82px;
        height: 42px;
        grid-column: 3;
        grid-row: 1;
        justify-self: end;
    }

    .rjb-action-stack .rjb-ok-button {
        /*
         * OK harus menempel ke edge kanan card persis seperti PRINT.
         * Negative margin pada versi sebelumnya tidak benar-benar
         * memindahkan tombol dari track grid, jadi sekarang diposisikan
         * absolute terhadap action-stack. Ini hanya perubahan visual;
         * struktur grid dan logic getData() tidak berubah.
         */
        position: absolute;
        top: 0;
        right: -20px;
        width: 82px;
        min-width: 82px;
        height: 42px;
        margin: 0;
        grid-column: auto;
        grid-row: auto;
        justify-self: auto;
    }

    .rjb-print-button {
        position: absolute;
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

    .rjb-print-button:hover {
        transform: translateY(-1px);
        background: linear-gradient(135deg, #14b88a 0%, #047857 100%);
        box-shadow: 0 12px 24px rgba(5, 150, 105, .28);
    }

    /*
     * PRINT mengikuti pola fitur Sertipikat Berakhir Hak:
     * belum ada laporan = tombol benar-benar disabled.
     * Tidak memunculkan notifikasi ketika ditekan.
     */
    .rjb-print-button:disabled,
    .rjb-print-button:disabled:hover,
    .rjb-print-button:disabled:focus {
        transform: none;
        border: 1px solid #d5dde7;
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        color: #94a3b8;
        box-shadow: none;
        cursor: not-allowed;
        filter: none;
        opacity: 1;
    }

    .rjb-radio-row {
        display: flex;
        min-height: 42px;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }

    .rjb-radio {
        display: inline-flex;
        min-height: 32px;
        align-items: center;
        gap: 7px;
        margin: 0;
        padding: 6px 10px;
        border: 1px solid #e5eaf2;
        border-radius: 999px;
        background: #fafbfc;
        color: #475467;
        font-size: 11px;
        font-weight: 750;
        cursor: pointer;
        transition:
            border-color .18s ease,
            color .18s ease,
            background .18s ease;
    }

    .rjb-radio:hover {
        border-color: #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .rjb-radio input {
        width: 15px;
        height: 15px;
        margin: 0;
        accent-color: #2563eb;
    }

    .rjb-radio:has(input:checked) {
        border-color: #bfdbfe;
        background: linear-gradient(135deg, #eff6ff, #e0f2fe);
        color: #1e40af;
        box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.05);
    }

    /*
     * Jenis Jaminan tetap berada pada row/kolom lama.
     * Tidak dipindah dan tidak mengubah id/select.
     */
    #jenis_jaminan {
        cursor: pointer;
    }

    /* =========================================================
       REPORT SHELL / INITIAL STATE
       ========================================================= */
    .rjb-report-workspace {
        position: relative;
        margin-top: 18px;
        padding: 20px;
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 26px;
        background: #ffffff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
    }

    .rjb-report-workspace::before {
        content: "";
        position: absolute;
        top: 0;
        left: 7%;
        right: 7%;
        height: 3px;
        border-radius: 0 0 999px 999px;
        background: linear-gradient(90deg, transparent, #38bdf8, #2563eb, #6366f1, transparent);
    }

    #rjbLoading {
        display: none;
        width: fit-content;
        margin: 0 0 12px;
        padding: 8px 12px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 850;
    }

    .rjb-paper {
        width: 100%;
        min-width: 0;
        min-height: 310px;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
        box-shadow: none;
        color: #344054;
    }

    .rjb-empty {
        display: flex;
        min-height: 310px;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        overflow: hidden;
        border: 1px dashed #bfdbfe;
        border-radius: 20px;
        background:
            radial-gradient(circle at center, rgba(37, 99, 235, .06), transparent 46%),
            #f8fbff;
        color: #667085;
        font-size: 13px;
        font-weight: 650;
        text-align: center;
    }

    .rjb-empty-icon {
        display: inline-flex;
        width: 52px;
        height: 52px;
        flex: 0 0 52px;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: #dbeafe;
        color: #2563eb;
        font-size: 22px;
    }

    .rjb-error-state {
        color: #b42318;
    }

    /* =========================================================
       REPORT HEADER
       ========================================================= */
    .rjb-live-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border: 1px solid #a7f3d0;
        border-radius: 999px;
        background: #ecfdf3;
        color: #047857;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .rjb-live-badge::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
    }

    /* =========================================================
       REPORT HEADER STANDARD
       Struktur visual sama dengan header laporan fitur Sertipikat:
       perusahaan (kiri) | judul (tengah) | blok/periode (kanan)
       subtitle: label sektor (kiri) | value sektor (tengah) | LIVE DATA (kanan)
       ========================================================= */
    .rjb-report-top {
        display: grid;
        grid-template-columns:
            minmax(150px, 1fr)
            minmax(260px, 1.4fr)
            minmax(180px, 1fr);
        gap: 16px;
        align-items: center;
        min-height: 72px;
        margin-bottom: 10px;
        padding: 15px 18px;
        border: 1px solid #dbeafe;
        border-radius: 18px;
        background: linear-gradient(105deg, #eff6ff 0%, #dbeafe 48%, #eef2ff 100%);
        color: #172033;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.10);
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    }

    .rjb-company {
        min-width: 0;
        overflow: hidden;
        color: #1d4ed8;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 11px;
        font-weight: 850;
        letter-spacing: .04em;
        line-height: 1.35;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .rjb-title-wrap {
        min-width: 0;
        text-align: center;
    }

    .rjb-title {
        color: #172033;
        font-family: Cambria, Georgia, "Times New Roman", serif !important;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: 1.2;
        text-align: center;
    }

    .rjb-print-info {
        min-width: 0;
        color: #475467;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 10.5px;
        font-weight: 650;
        line-height: 1.55;
        text-align: right;
    }

    .rjb-sector-line {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        min-height: 36px;
        align-items: center;
        gap: 12px;
        margin: 0 0 10px;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: linear-gradient(90deg, #ffffff, #f8fafc);
        color: #667085;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 10.5px;
    }

    .rjb-sector-label {
        min-width: 0;
        justify-self: start;
        color: #667085;
        white-space: nowrap;
    }

    .rjb-sector-value {
        justify-self: center;
        color: #344054;
        font-weight: 850;
        text-align: center;
        white-space: nowrap;
    }

    .rjb-sector-line .rjb-live-badge {
        justify-self: end;
    }

    /* =========================================================
       TABLE — ukuran font/spacing disamakan dengan Sertipikat Pecahan
       ========================================================= */
    .rjb-table-wrapper {
        width: 100%;
        max-height: calc(100vh - 285px);
        min-height: 320px;
        overflow: auto;
        border: 1px solid #dbe3ef;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 5px 16px rgba(15, 23, 42, 0.04);
        scrollbar-width: thin;
        scrollbar-color: #93c5fd #eff3f7;
    }

    .rjb-table-wrapper::-webkit-scrollbar,
    .rjb-modal-table-wrap::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .rjb-table-wrapper::-webkit-scrollbar-track,
    .rjb-modal-table-wrap::-webkit-scrollbar-track {
        background: #eff3f7;
    }

    .rjb-table-wrapper::-webkit-scrollbar-thumb,
    .rjb-modal-table-wrap::-webkit-scrollbar-thumb {
        border: 2px solid #eff3f7;
        border-radius: 999px;
        background: linear-gradient(180deg, #60a5fa, #2563eb);
    }

    .rjb-table {
        width: 1540px;
        min-width: 1540px;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
        color: #344054;
        font-size: 10.5px;
    }

    .rjb-table th,
    .rjb-table td {
        padding: 8px 9px;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        overflow-wrap: anywhere;
    }

    .rjb-table th {
        position: sticky;
        top: 0;
        z-index: 4;
        height: 54px;
        border-top: 0;
        background: linear-gradient(180deg, #eff6ff 0%, #e5effb 100%);
        color: #344054;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-weight: 900;
        line-height: 1.25;
        text-align: center;
        white-space: normal;
    }

    .rjb-table td {
        height: 46px;
        background: #ffffff;
        line-height: 1.38;
        white-space: normal;
    }

    .rjb-table tbody tr:nth-child(even) td {
        background: #fbfcfe;
    }

    .rjb-table tbody tr:hover td {
        background: #f0f7ff;
    }

    .rjb-table tbody tr:hover td:first-child {
        box-shadow: inset 4px 0 0 #2563eb;
        color: #1d4ed8;
        font-weight: 900;
    }

    .rjb-no,
    .rjb-center {
        text-align: center;
    }

    .rjb-bank-cell {
        line-height: 1.45;
    }

    .rjb-bank-cell .rjb-plafond {
        display: block;
        margin-top: 7px;
        color: #1e3a5f;
        text-align: right;
        font-weight: 750;
        font-variant-numeric: tabular-nums;
    }

    .rjb-name-cell {
        color: #172033;
        font-weight: 800;
    }

    /* =========================================================
       FOOTER TANDA TANGAN — sama dengan Daftar Sertipikat Pecahan
       Tetap berada setelah tabel dan tidak mengubah struktur data/AJAX.
       ========================================================= */
    .rjb-signature-footer {
        width: min(100%, 980px);
        min-height: 190px;
        margin: 18px auto 2px;
        padding: 0 34px 18px;
        color: #344054;
        font-size: 11px;
    }

    .rjb-signature-date {
        width: 50%;
        margin: 0 0 8px auto;
        text-align: center;
        color: #475467;
        font-weight: 600;
        line-height: 1.35;
    }

    .rjb-signature-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 90px;
        align-items: start;
    }

    .rjb-signature-box {
        min-width: 0;
        text-align: center;
    }

    .rjb-signature-role {
        min-height: 20px;
        color: #344054;
        font-weight: 600;
    }

    .rjb-signature-space {
        height: 78px;
    }

    .rjb-signature-line {
        display: inline-flex;
        width: min(100%, 220px);
        align-items: flex-end;
        justify-content: center;
        color: #667085;
        line-height: 1;
        white-space: nowrap;
    }

    .rjb-signature-line::before {
        content: "(";
        margin-right: 3px;
    }

    .rjb-signature-line::after {
        content: ")";
        margin-left: 3px;
    }

    .rjb-signature-line > span {
        display: block;
        width: 100%;
        height: 10px;
        border-bottom: 1px dotted #98a2b3;
    }

    /* =========================================================
       MODAL SEKTOR — visual match modal Sertipikat Pecahan,
       mekanisme custom modal lama tetap dipakai.
       ========================================================= */
    #rjbModal {
        position: fixed;
        inset: 0;
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(71, 85, 105, .24);
    }

    #rjbModal.show {
        display: flex;
    }

    .rjb-modal-window {
        width: min(800px, calc(100vw - 32px));
        max-height: calc(100vh - 48px);
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .22);
    }

    .rjb-modal-header {
        display: flex;
        min-height: 58px;
        align-items: center;
        justify-content: space-between;
        padding: 16px 18px;
        border-bottom: 1px solid #dbe3ef;
        background: linear-gradient(90deg, #ffffff 0%, #f8fbff 100%);
        color: #1d2939;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 15px;
        font-weight: 800;
    }

    .rjb-modal-close {
        display: inline-flex;
        width: 36px;
        height: 36px;
        align-items: center;
        justify-content: center;
        border: 1px solid #bfdbfe;
        border-radius: 11px;
        background: #eff6ff;
        color: #1d4ed8;
        cursor: pointer;
        font-size: 12px;
    }

    .rjb-modal-body {
        padding: 16px;
        background: #f8fafc;
    }

    .rjb-modal-search {
        width: 100%;
        height: 42px;
        margin-bottom: 12px;
        padding: 8px 12px;
        border: 1px solid #c8d3e1;
        border-radius: 12px;
        background: #ffffff;
        color: #101828;
        font-size: 12px;
        outline: 0;
    }

    .rjb-modal-search:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    .rjb-modal-table-wrap {
        max-height: 430px;
        overflow: auto;
        border: 1px solid #dbe3ef;
        border-radius: 16px;
        background: #ffffff;
    }

    .rjb-modal-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 12px;
    }

    .rjb-modal-table th,
    .rjb-modal-table td {
        padding: 10px 12px;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }

    .rjb-modal-table th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: linear-gradient(180deg, #eff6ff 0%, #e7f0fc 100%);
        color: #344054;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        text-align: center;
        font-weight: 850;
    }

    .rjb-modal-table tbody tr {
        cursor: pointer;
    }

    .rjb-modal-table tbody tr:hover td {
        background: #eff6ff;
        color: #1d4ed8;
    }

    /* =========================================================
       ZOOM-STABLE DESKTOP LAYOUT
       Tidak ada breakpoint yang mengubah grid menjadi 1 kolom.
       Jika viewport CSS mengecil akibat browser zoom, layout tetap.
       Minimum workspace 900px menjaga label dan date field tidak saling menekan.
       Browser memberi horizontal scroll hanya bila ruang benar-benar lebih kecil.
       ========================================================= */
    @media screen and (max-width: 900px) {
        .rjb-signature-footer {
            padding-inline: 18px;
        }

        .rjb-signature-grid {
            gap: 54px;
        }

        .rjb-signature-space {
            height: 68px;
        }
    }

    @media screen and (max-width: 899px) {
        html,
        body {
            min-width: 900px;
        }

        .rjb-page {
            min-width: 900px;
        }
    }


    @media print {
        @page {
            margin: 8mm;
        }

        .rjb-view-version,
        .rjb-filter-panel,
        #rjbLoading,
        #rjbModal,
        #rjbNoDataAlertModal {
            display: none !important;
        }

        .rjb-page,
        .rjb-report-workspace {
            min-width: 0;
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: #ffffff;
            box-shadow: none;
            overflow: visible;
        }

        .rjb-report-workspace::before {
            display: none;
        }

        .rjb-paper {
            width: 100%;
            min-height: 0;
        }

        .rjb-table-wrapper {
            max-height: none;
            overflow: visible;
            border-radius: 0;
            box-shadow: none;
        }

        .rjb-table {
            width: 100%;
            min-width: 0;
        }

        .rjb-table th {
            position: static;
        }

        /* PRINT BORDER FIX: semua sisi cell dipaksa tercetak. */
        .rjb-report-top,
        .rjb-sector-line {
            border: 1px solid #777 !important;
            border-radius: 0 !important;
            background: #fff !important;
            box-shadow: none !important;
            color: #000 !important;
        }

        .rjb-company,
        .rjb-title,
        .rjb-print-info,
        .rjb-sector-label,
        .rjb-sector-value,
        .rjb-live-badge {
            color: #000 !important;
        }

        .rjb-live-badge {
            border-color: #777 !important;
            background: #fff !important;
        }

        .rjb-table-wrapper {
            border: 0 !important;
        }

        .rjb-table {
            width: 100% !important;
            min-width: 0 !important;
            border: 1px solid #000 !important;
            border-collapse: collapse !important;
            border-spacing: 0 !important;
            table-layout: fixed !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .rjb-table thead {
            display: table-header-group !important;
        }

        .rjb-table tbody {
            display: table-row-group !important;
        }

        .rjb-table tr {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        .rjb-table th,
        .rjb-table td {
            border: 1px solid #000 !important;
            background: #fff !important;
            color: #000 !important;
            box-shadow: none !important;
        }

        .rjb-table tbody tr:nth-child(even) td,
        .rjb-table tbody tr:hover td {
            background: #fff !important;
        }

        .rjb-signature-footer {
            width: 100%;
            min-height: 170px;
            margin-top: 16px;
            padding: 0 24px 8px;
            color: #000;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .rjb-signature-date,
        .rjb-signature-role,
        .rjb-signature-line {
            color: #000;
        }

        .rjb-signature-line > span {
            border-bottom-color: #444;
        }
    }

    /* =========================================================
       ALERT DATA KOSONG — SAMA DENGAN DAFTAR SERTIFIKAT PECAHAN
       Modal informasi yang tampil saat hasil laporan tidak
       menghasilkan baris data sama sekali.
       ========================================================= */
        #rjbNoDataAlertModal .modal-dialog {
            max-width: 380px;
        }

        #rjbNoDataAlertModal .modal-content {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
        }

        #rjbNoDataAlertModal .alert-icon-wrapper {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #eff6ff;
            color: #2563eb;
            font-size: 28px;
        }

        #rjbNoDataAlertModal .alert-title {
            margin-bottom: 8px;
            color: #172033;
            font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
            font-size: 18px;
            font-weight: 700;
        }

        #rjbNoDataAlertModal .alert-message {
            margin-bottom: 24px;
            color: #475569;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            font-size: 14px;
        }

        #rjbNoDataAlertModal .alert-btn-ok {
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

        #rjbNoDataAlertModal .alert-btn-ok:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
        }

</style>

<!-- MODAL ALERT DATA KOSONG -->
<div class="modal fade" id="rjbNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4" style="text-align: center; padding: 1.5rem;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message" id="rjbNoDataMessage">Data tidak ditemukan.</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    data-dismiss="modal"
                    onclick="hideRjbNoDataAlert()"
                >OK</button>
            </div>
        </div>
    </div>
</div>

<div class="rjb-page">
    <div class="rjb-view-version" id="rjbViewVersion">
        <span>DAFTAR JAMINAN BANK</span>
        <code class="rjb-unit-badge">
            UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}
        </code>
    </div>
    <section class="rjb-filter-panel">
        <input type="hidden" id="perusahaan" value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}">
        <input type="hidden" id="nama_perusahaan" value="{{ session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? '' }}">
        <input type="hidden" id="sektor" value="*">

        <div class="rjb-filter-grid">
            <div class="rjb-field-row">
                <label class="rjb-label" for="blok_awal">Blok :</label>
                <div class="rjb-range">
                    <input type="text" id="blok_awal" class="rjb-input" value="A" maxlength="30">
                    <span class="rjb-separator">s.d</span>
                    <input type="text" id="blok_akhir" class="rjb-input" value="ZZ" maxlength="30">
                </div>
            </div>

            <div class="rjb-field-row">
                <label class="rjb-label" for="tgl_awal_bank">Tgl.Bank:</label>
                <div class="rjb-range">
                    <input type="date" id="tgl_awal_bank" class="rjb-input">
                    <span class="rjb-separator">s.d</span>
                    <input type="date" id="tgl_akhir_bank" class="rjb-input">
                </div>
            </div>

            <div class="rjb-action-stack">
                <button type="button" class="rjb-ok-button" onclick="getData()">OK</button>
                <button type="button" class="rjb-print-button" id="rjbPrintButton" onclick="printRjbReport()" disabled>PRINT</button>
            </div>

            <div class="rjb-field-row">
                <span class="rjb-label">Sektor/Cluster :</span>
                <div class="rjb-lookup">
                    <div id="sektorEntry" class="rjb-lookup-display">Semua Cluster</div>
                    <button type="button" class="rjb-lookup-button" onclick="getSektorModal()" title="Pilih sektor/cluster">
                        <i class="fas fa-binoculars"></i>
                    </button>
                </div>
            </div>

            <div class="rjb-field-row">
                <span class="rjb-label">Status AJB :</span>
                <div class="rjb-radio-row">
                    <label class="rjb-radio">
                        <input type="radio" name="status_ajb" value="SUDAH">
                        <span>Sudah</span>
                    </label>
                    <label class="rjb-radio">
                        <input type="radio" name="status_ajb" value="BELUM">
                        <span>Belum</span>
                    </label>
                    <label class="rjb-radio">
                        <input type="radio" name="status_ajb" value="SEMUA" checked>
                        <span>Semua</span>
                    </label>
                </div>
            </div>

            <div></div>

            <div class="rjb-field-row">
                <label class="rjb-label" for="jenis_jaminan">Jenis Jaminan :</label>
                <select id="jenis_jaminan" class="rjb-select">
                    <option value="*">Semua</option>
                    <option value="IMB">IMB</option>
                    <option value="Akta Jual Beli">Akta Jual Beli</option>
                    <option value="Sertipikat">Sertipikat</option>
                    <option value="PPJB">PPJB</option>
                    <option value="Peralihan Hak">Peralihan Hak</option>
                </select>
            </div>
        </div>
    </section>

    <section class="rjb-report-workspace">
        <div id="rjbLoading">
            <i class="fas fa-spinner fa-spin"></i>
            Mengambil data Rekapitulasi Jaminan Bank...
        </div>

        <div id="mainDisplay">
            <div class="rjb-paper">
                <div class="rjb-empty">
                    <i class="fas fa-table rjb-empty-icon" aria-hidden="true"></i>
                    <div>Laporan belum ditampilkan</div>
                </div>
            </div>
        </div>
    </section>
</div>


<div id="rjbModal" aria-hidden="true">
    <div class="rjb-modal-window">
        <div class="rjb-modal-header">
            <span id="rjbModalTitle">Pilih Sektor/Cluster</span>
            <button type="button" class="rjb-modal-close" onclick="toggleRjbModal(false)" aria-label="Tutup"><i class="fas fa-times"></i></button>
        </div>
        <div class="rjb-modal-body" id="rjbModalBody"></div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function () {
        setInitialDesktopValues();

        $('#blok_awal, #blok_akhir').on('input', function () {
            $(this).val(String($(this).val() || '').toUpperCase());
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                toggleRjbModal(false);
            }
        });
    });


    function setInitialDesktopValues() {
        /*
         * Tanggal awal dan tanggal akhir Bank sama-sama default ke hari ini.
         * User tetap dapat mengubah rentang tanggal setelah halaman dibuka.
         */
        var today = new Date();
        var yyyy = today.getFullYear();
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var dd = String(today.getDate()).padStart(2, '0');
        var todayValue = yyyy + '-' + mm + '-' + dd;

        $('#tgl_awal_bank').val(todayValue);
        $('#tgl_akhir_bank').val(todayValue);
        $('#rjbPrintButton').prop('disabled', true);
        hideRjbNoDataAlert();
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

    function formatDate(value) {
        if (value === null || value === undefined || String(value).trim() === '') {
            return '-';
        }

        var text = String(value).trim();
        var match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);

        if (match) {
            return match[3] + '-' + match[2] + '-' + match[1];
        }

        var slashMatch = text.match(/^(\d{4})(\d{2})(\d{2})$/);
        if (slashMatch) {
            return slashMatch[3] + '-' + slashMatch[2] + '-' + slashMatch[1];
        }

        return text;
    }

    function formatTanggalIndonesia(dateValue) {
        var date = dateValue instanceof Date
            ? dateValue
            : new Date(dateValue || new Date());

        if (isNaN(date.getTime())) {
            date = new Date();
        }

        var bulan = [
            'Januari', 'Februari', 'Maret', 'April',
            'Mei', 'Juni', 'Juli', 'Agustus',
            'September', 'Oktober', 'November', 'Desember'
        ];

        return date.getDate()
            + ' '
            + bulan[date.getMonth()]
            + ' '
            + date.getFullYear();
    }

    function formatCurrency(value) {
        if (value === null || value === undefined || String(value).trim() === '') {
            return '0';
        }

        var number = Number(value);
        if (!isFinite(number)) {
            return escapeHtml(value);
        }

        return number.toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }


    /*
     * Khusus kolom Nama Bank/Alamat Bank/Plafond:
     * nilai kosong tetap mengambil barisnya, tetapi tanpa tanda '-'/angka buatan.
     * Nilai 0 yang benar-benar berasal dari database tetap ditampilkan 0.
     */
    function formatCurrencyOrBlank(value) {
        if (value === null || value === undefined || String(value).trim() === '') {
            return '';
        }

        return formatCurrency(value);
    }

    function valueOrDash(value) {
        return value === null
            || value === undefined
            || String(value).trim() === ''
                ? '-'
                : value;
    }

    /*
     * Dipakai khusus di dua kolom gabungan:
     * - Nama yg Mengajukan / Nomor Pengajuan / Tanggal Pengajuan
     * - Nama Bank / Alamat Bank / Plafond
     * Nilai kosong pada dua kolom tersebut tidak diberi placeholder '-'.
     */
    function valueOrBlank(value) {
        return value === null
            || value === undefined
            || String(value).trim() === ''
                ? ''
                : value;
    }

    function formatDateOrBlank(value) {
        if (value === null || value === undefined || String(value).trim() === '') {
            return '';
        }

        return formatDate(value);
    }

    function pick(item, upper, lower) {
        if (!item) {
            return null;
        }

        if (item[upper] !== undefined && item[upper] !== null) {
            return item[upper];
        }

        if (lower && item[lower] !== undefined && item[lower] !== null) {
            return item[lower];
        }

        return null;
    }

    function toggleRjbModal(show) {
        $('#rjbModal')
            .toggleClass('show', !!show)
            .attr('aria-hidden', show ? 'false' : 'true');
    }

    function filterRjbModal(keyword) {
        var search = String(keyword || '').toLowerCase();

        $('#rjbModal .rjb-modal-table tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(search) !== -1);
        });
    }

    function addSektor(kode, deskripsi) {
        $('#sektor').val(kode || '*');
        $('#sektorEntry').text(deskripsi || 'Semua Cluster');
        toggleRjbModal(false);
    }

    function getSektorModal() {
        var perusahaan = String($('#perusahaan').val() || '').trim();

        if (!perusahaan) {
            alert('Unit/perusahaan belum tersedia.');
            return;
        }

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_sektor',
            dataType: 'json',
            headers: { 'Accept': 'application/json' },
            data: {
                _token: '{{ csrf_token() }}',
                perusahaan: perusahaan
            },
            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (response && Array.isArray(response.data) ? response.data : []);

                var html = '';
                html += '<input type="text" class="rjb-modal-search" placeholder="Cari sektor/cluster..." onkeyup="filterRjbModal(this.value)">';
                html += '<div class="rjb-modal-table-wrap">';
                html += '<table class="rjb-modal-table"><thead><tr>';
                html += '<th style="width:130px">Kode</th><th>Deskripsi</th><th style="width:120px">Perusahaan</th>';
                html += '</tr></thead><tbody>';

                html += '<tr class="rjb-sector-row" data-kode="*" data-deskripsi="Semua Cluster">';
                html += '<td>*</td><td>Semua Cluster</td><td>' + escapeHtml(perusahaan) + '</td>';
                html += '</tr>';

                $.each(rows, function (index, item) {
                    var kode = item.KD_SEKTOR || item.kd_sektor || '';
                    var deskripsi = item.DESKRIPSI || item.deskripsi || kode;
                    var unit = item.KD_PERUSAHAAN || item.kd_perusahaan || perusahaan;

                    html += '<tr class="rjb-sector-row" data-kode="' + escapeHtml(kode) + '" data-deskripsi="' + escapeHtml(deskripsi) + '">';
                    html += '<td>' + escapeHtml(kode) + '</td>';
                    html += '<td>' + escapeHtml(deskripsi) + '</td>';
                    html += '<td>' + escapeHtml(unit) + '</td>';
                    html += '</tr>';
                });

                if (rows.length < 1) {
                    html += '<tr><td colspan="3" style="text-align:center;padding:16px">Data sektor tidak ditemukan.</td></tr>';
                }

                html += '</tbody></table></div>';

                $('#rjbModalTitle').text('Pilih Sektor/Cluster');
                $('#rjbModalBody').html(html);

                $('#rjbModalBody')
                    .off('click.rjbSector', '.rjb-sector-row')
                    .on('click.rjbSector', '.rjb-sector-row', function () {
                        addSektor(
                            String($(this).attr('data-kode') || '*'),
                            String($(this).attr('data-deskripsi') || 'Semua Cluster')
                        );
                    });

                toggleRjbModal(true);
                $('#rjbModal .rjb-modal-search').trigger('focus');
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
     * Jenis Jaminan tidak diambil DISTINCT dari database.
     * Pilihan dibuat tetap agar sama dengan combobox aplikasi desktop:
     * Semua, IMB, Akta Jual Beli, Sertipikat, PPJB, Peralihan Hak.
     */

    function validateFilter() {
        if (!$('#blok_awal').val() || !$('#blok_akhir').val()) {
            alert('Blok awal dan blok akhir wajib diisi.');
            return false;
        }

        var awal = $('#tgl_awal_bank').val();
        var akhir = $('#tgl_akhir_bank').val();

        if ((awal && !akhir) || (!awal && akhir)) {
            alert('Tanggal bank awal dan akhir harus diisi bersama-sama.');
            return false;
        }

        if (awal && akhir && awal > akhir) {
            alert('Tanggal bank awal tidak boleh melebihi tanggal bank akhir.');
            return false;
        }

        return true;
    }

    function getFilterData() {
        return {
            _token: '{{ csrf_token() }}',
            perusahaan: $('#perusahaan').val(),
            blok_awal: String($('#blok_awal').val() || 'A').toUpperCase(),
            blok_akhir: String($('#blok_akhir').val() || 'ZZ').toUpperCase(),
            tgl_awal_bank: $('#tgl_awal_bank').val(),
            tgl_akhir_bank: $('#tgl_akhir_bank').val(),
            sektor: $('#sektor').val() || '*',
            jenis_jaminan: $('#jenis_jaminan').val() || '*',
            status_ajb: $('input[name="status_ajb"]:checked').val() || 'SEMUA'
        };
    }

    /*
     * Alert data kosong, mengikuti Daftar Sertifikat Pecahan.
     * Hanya menampilkan pesan; pengambilan data dan render laporan
     * tetap memakai alur yang sudah ada. Jika plugin modal tidak
     * tersedia pada halaman ini, modal ditampilkan secara manual
     * sehingga tampilannya tetap sama.
     */
    function showRjbNoDataAlert(message) {
        var $modal = $('#rjbNoDataAlertModal');

        $('#rjbNoDataMessage').text(message || 'Data tidak ditemukan......!');

        if (typeof $modal.modal === 'function') {
            $modal.modal('show');
            return;
        }

        if (!$('.rjb-nodata-backdrop').length) {
            $('<div class="modal-backdrop fade show rjb-nodata-backdrop"></div>')
                .appendTo(document.body);
        }

        $modal
            .addClass('show')
            .css('display', 'block')
            .attr('aria-hidden', 'false');
    }

    function hideRjbNoDataAlert() {
        var $modal = $('#rjbNoDataAlertModal');

        if (typeof $modal.modal === 'function') {
            $modal.modal('hide');
            return;
        }

        $modal
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true');

        $('.rjb-nodata-backdrop').remove();
    }

    function getData() {
        if (!validateFilter()) {
            return;
        }

        hideRjbNoDataAlert();

        $('#rjbPrintButton').prop('disabled', true);
        $('#rjbLoading').show();
        $('#mainDisplay').html('');

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_data',
            dataType: 'json',
            timeout: 120000,
            headers: { 'Accept': 'application/json' },
            data: getFilterData(),
            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (response && Array.isArray(response.data) ? response.data : []);

                if (rows.length === 0) {
                    showRjbNoDataAlert('Data tidak ditemukan......!');
                }

                renderReport(rows);
            },
            error: function (xhr, textStatus, errorThrown) {
                $('#rjbPrintButton').prop('disabled', true);

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
                    '<div class="rjb-paper"><div class="rjb-empty rjb-error-state">' +
                    '<i class="fas fa-exclamation-triangle rjb-empty-icon" aria-hidden="true"></i>' +
                    '<div>Gagal mengambil data. ' + escapeHtml(detail) + '</div>' +
                    '</div></div>'
                );
            },
            complete: function () {
                $('#rjbLoading').hide();
            }
        });
    }

    /* =========================================================
       NAMA PERUSAHAAN — resolver disamakan dengan Sertipikat Pecahan
       Tujuan: header report menampilkan nama PT, bukan hanya kode UNIT.
       ========================================================= */
    function extractCompanyName(value) {
        var raw = String(value || '').replace(/\u00a0/g, ' ');
        var locationMatch = raw.match(/Lokasi\s*:[^\r\n|]*?-\s*([^\r\n|]+)/i);
        var companyMatch = raw.match(/\b((?:PT\.?|CV\.?|PD\.?|PERUM)\s+[^\r\n|]+)/i);
        var name = locationMatch
            ? locationMatch[1]
            : (companyMatch ? companyMatch[1] : '');

        return String(name || '')
            .replace(/\s+(?:Hi\s*,|Logout\b|Keluar\b|LAND DOCUMENT\b|UNIT\s+[A-Z0-9_-]+\b).*$/i, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function companyNameFromLayout() {
        var selectors = [
            '[data-nama-perusahaan]',
            '[data-company-name]',
            '[data-unit-name]',
            'input[name="nama_perusahaan"]',
            'input[name="nama_pt"]',
            '#nama_perusahaan',
            '#nama_pt',
            'header input',
            'nav input',
            '.navbar input',
            '.topbar input',
            'header [title]',
            'nav [title]',
            '.navbar [title]',
            '.topbar [title]',
            '[class*="unit"]',
            '[id*="unit"]'
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

    function getCompanyName(firstRow) {
        var first = firstRow || {};
        var unit = String($('#perusahaan').val() || '').trim().toUpperCase();
        var sessionName = String($('#nama_perusahaan').val() || '').trim();
        var rowCompany =
            pick(first, 'NAMA_PT', 'nama_pt')
            || pick(first, 'ATAS_NAMA_PT', 'atas_nama_pt')
            || pick(first, 'NAMA_PERUSAHAAN', 'nama_perusahaan');

        var cacheKey = 'sris.report-company.' + unit;
        var cachedName = '';

        try {
            cachedName = localStorage.getItem(cacheKey) || '';
        } catch (error) {
            cachedName = '';
        }

        var company =
            extractCompanyName(rowCompany)
            || String(rowCompany || '').trim()
            || companyNameFromLayout()
            || extractCompanyName(sessionName)
            || sessionName
            || cachedName
            || unit
            || '-';

        if (company && company !== '-' && company.toUpperCase() !== unit) {
            try {
                localStorage.setItem(cacheKey, company);
            } catch (error) {
                // Storage boleh gagal; nama tetap digunakan untuk render saat ini.
            }
        }

        return company;
    }

    function printRjbReport() {
        if (
            $('#rjbPrintButton').prop('disabled')
            || !$('#mainDisplay .rjb-report-top').length
        ) {
            return;
        }

        var reportHtml = $('#mainDisplay').html();
        if (!reportHtml) {
            return;
        }

        $('#rjbNativePrintFrame').remove();

        var frame = document.createElement('iframe');
        frame.id = 'rjbNativePrintFrame';
        frame.setAttribute('aria-hidden', 'true');
        frame.style.position = 'fixed';
        frame.style.right = '0';
        frame.style.bottom = '0';
        frame.style.width = '0';
        frame.style.height = '0';
        frame.style.border = '0';
        frame.style.opacity = '0';
        frame.style.pointerEvents = 'none';
        document.body.appendChild(frame);

        var frameWindow = frame.contentWindow;
        var frameDocument = frame.contentDocument || frameWindow.document;

        var printCss = `
            @page { margin: 8mm; }

            html, body {
                width: 100%;
                margin: 0;
                padding: 0;
                background: #fff;
                color: #000;
                font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            }

            *, *::before, *::after { box-sizing: border-box; }

            .rjb-paper {
                width: 100%;
                min-height: 0;
                margin: 0;
                padding: 0;
                background: #fff;
                color: #000;
            }

            .rjb-report-top {
                display: grid;
                grid-template-columns: 1fr 1.45fr 1fr;
                gap: 12px;
                align-items: center;
                margin-bottom: 7px;
                padding: 9px 10px;
                border: 1px solid #777;
                background: #fff;
            }

            .rjb-company {
                color: #000;
                font-size: 9.5px;
                font-weight: 700;
                line-height: 1.3;
            }

            .rjb-title-wrap { text-align: center; }

            .rjb-title {
                color: #000;
                font-family: Cambria, Georgia, "Times New Roman", serif;
                font-size: 14px;
                font-weight: 700;
                line-height: 1.2;
            }

            .rjb-print-info {
                color: #000;
                font-size: 8.5px;
                line-height: 1.4;
                text-align: right;
            }

            .rjb-sector-line {
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

            .rjb-sector-label { justify-self: start; }
            .rjb-sector-value {
                justify-self: center;
                color: #000;
                font-weight: 700;
            }

            .rjb-live-badge {
                justify-self: end;
                color: #000;
                font-size: 7.5px;
                font-weight: 700;
            }

            .rjb-table-wrapper {
                width: 100%;
                max-width: 100%;
                max-height: none;
                min-height: 0;
                overflow: visible;
                border: 0;
                background: #fff;
            }

            .rjb-table {
                width: 100%;
                min-width: 0;
                max-width: 100%;
                table-layout: fixed;
                border-collapse: collapse;
                border-spacing: 0;
                border: 1px solid #000;
                background: #fff;
                color: #000;
                font-size: 7.6px;
            }

            .rjb-table col { width: auto !important; }

            .rjb-table thead { display: table-header-group; }
            .rjb-table tbody { display: table-row-group; }

            .rjb-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .rjb-table th,
            .rjb-table td {
                position: static;
                height: auto;
                padding: 3px;
                border: 1px solid #000;
                background: #fff;
                color: #000;
                box-shadow: none;
                vertical-align: middle;
                overflow: visible;
                overflow-wrap: anywhere;
                word-break: normal;
                line-height: 1.22;
            }

            .rjb-table th {
                text-align: center;
                font-weight: 700;
            }

            .rjb-no,
            .rjb-center { text-align: center; }

            .rjb-name-cell { font-weight: 700; }

            .rjb-bank-cell .rjb-plafond {
                display: block;
                margin-top: 4px;
                color: #000;
                text-align: right;
                font-weight: 600;
            }

            .rjb-signature-footer {
                width: 100%;
                min-height: 150px;
                margin: 14px auto 0;
                padding: 0 24px 8px;
                color: #000;
                font-size: 9px;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .rjb-signature-date {
                width: 50%;
                margin: 0 0 8px auto;
                text-align: center;
                color: #000;
            }

            .rjb-signature-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 90px;
            }

            .rjb-signature-box { text-align: center; }
            .rjb-signature-role { color: #000; }
            .rjb-signature-space { height: 58px; }

            .rjb-signature-line {
                display: inline-flex;
                width: min(100%, 220px);
                align-items: flex-end;
                justify-content: center;
                color: #000;
            }

            .rjb-signature-line::before { content: "("; margin-right: 3px; }
            .rjb-signature-line::after { content: ")"; margin-left: 3px; }

            .rjb-signature-line > span {
                display: block;
                width: 100%;
                height: 10px;
                border-bottom: 1px dotted #444;
            }
        `;

        frameDocument.open();
        frameDocument.write(
            '<!DOCTYPE html><html><head><meta charset="utf-8">'
            + '<meta name="viewport" content="width=device-width,initial-scale=1">'
            + '<title>Daftar Jaminan Bank</title>'
            + '<style>' + printCss + '</style>'
            + '</head><body>' + reportHtml + '</body></html>'
        );
        frameDocument.close();

        window.setTimeout(function () {
            try {
                frameWindow.focus();
                frameWindow.print();
            } finally {
                window.setTimeout(function () {
                    $('#rjbNativePrintFrame').remove();
                }, 1200);
            }
        }, 180);
    }


    function renderReport(rows) {
        var first = rows.length ? rows[0] : {};
        var company = getCompanyName(first);
        var filter = getFilterData();
        var sector = String($('#sektorEntry').text() || 'Semua Cluster').trim();
        var statusAjb = $('input[name="status_ajb"]:checked').val() || 'SEMUA';
        var reportTitle = 'Rekapitulasi Jaminan Bank';
        var tanggalTandaTangan = formatTanggalIndonesia(new Date());

        if (statusAjb === 'SUDAH') {
            reportTitle += ' (Sudah AJB)';
        } else if (statusAjb === 'BELUM') {
            reportTitle += ' (Belum AJB)';
        }

        var html = '';
        html += '<div class="rjb-paper">';

        /*
         * Header report mengikuti struktur visual halaman
         * Daftar Sertipikat Pecahan:
         * perusahaan | judul | periode/filter.
         */
        html += '<div class="rjb-report-top">';
        html += '<div class="rjb-company">' + escapeHtml(company) + '</div>';

        html += '<div class="rjb-title-wrap">';
        html += '<div class="rjb-title">' + escapeHtml(reportTitle) + '</div>';
        html += '</div>';

        html += '<div class="rjb-print-info">';
        html += 'BLOK: ' + escapeHtml(filter.blok_awal) + ' s/d ' + escapeHtml(filter.blok_akhir) + '<br>';
        html += 'Tgl. BANK: ' + escapeHtml(formatDate(filter.tgl_awal_bank)) + ' s/d ' + escapeHtml(formatDate(filter.tgl_akhir_bank));
        html += '</div>';
        html += '</div>';

        html += '<div class="rjb-sector-line">';
        html += '<span class="rjb-sector-label">Sektor/Cluster:</span>';
        html += '<strong class="rjb-sector-value">' + escapeHtml(sector) + '</strong>';
        html += '<span class="rjb-live-badge">Live data</span>';
        html += '</div>';

        html += '<div class="rjb-table-wrapper">';
        html += '<table class="rjb-table">';

        /*
         * Lebar kolom tetap supaya proporsi tidak berubah saat browser zoom.
         * Bila ruang layar mengecil, wrapper yang scroll horizontal,
         * bukan struktur tabel/filter yang berubah.
         */
        html += '<colgroup>';
        html += '<col style="width:48px">';
        html += '<col style="width:90px">';
        html += '<col style="width:245px">';
        html += '<col style="width:165px">';
        html += '<col style="width:300px">';
        html += '<col style="width:105px">';
        html += '<col style="width:220px">';
        html += '<col style="width:175px">';
        html += '<col style="width:175px">';
        html += '</colgroup>';

        html += '<thead><tr>';
        html += '<th>No.</th>';
        html += '<th>BLOK/<br>NOMOR</th>';
        html += '<th>Nama yg Mengajukan/<br>Nomor Pengajuan/<br>Tanggal Pengajuan</th>';
        html += '<th>Nomor Bank/<br>Tanggal Bank</th>';
        html += '<th>Nama Bank/<br>Alamat Bank/<br>Plafond Bank di PPJB (Rp)</th>';
        html += '<th>Jenis<br>Jaminan</th>';
        html += '<th>Nama Pengambil/<br>Tanggal Pengambilan</th>';
        html += '<th>Nomor Pelunasan/<br>Tanggal Pelunasan</th>';
        html += '<th>Nomor Pembatalan/<br>Tanggal Pembatalan</th>';
        html += '</tr></thead><tbody>';

        if (rows.length < 1) {
            html += '<tr><td colspan="9" style="height:130px;text-align:center;color:#64748b;">Data tidak ditemukan.</td></tr>';
        } else {
            $.each(rows, function (index, item) {
                var nama = pick(item, 'NAMA', 'nama');
                var pengajuan = pick(item, 'PENGAJUAN', 'pengajuan');
                var tglPengajuan = pick(item, 'TGL_PENGAJUAN', 'tgl_pengajuan');
                var noJaminan = pick(item, 'NO_JAMINAN', 'no_jaminan');
                var tglJaminan = pick(item, 'TGL_JAMINAN', 'tgl_jaminan');
                var namaBank = pick(item, 'NAMA_BANK', 'nama_bank');
                var alamatBank = pick(item, 'ALAMAT_BANK', 'alamat_bank');
                var plafond = pick(item, 'PLAFOND_KPR', 'plafond_kpr');
                var jenis = pick(item, 'JENIS_JAMINAN', 'jenis_jaminan');
                var namaAmbil = pick(item, 'NAMA_AMBIL', 'nama_ambil');
                var tglAmbil = pick(item, 'TGL_AMBIL', 'tgl_ambil');
                var noLunas = pick(item, 'NO_LUNAS', 'no_lunas');
                var tglLunas = pick(item, 'TGL_LUNAS', 'tgl_lunas');
                var noBatal = pick(item, 'NO_BATAL', 'no_batal');
                var tglBatal = pick(item, 'TGL_BATAL', 'tgl_batal');

                html += '<tr>';
                html += '<td class="rjb-no">' + (index + 1) + '</td>';
                html += '<td class="rjb-center">' +
                    escapeHtml(valueOrDash(pick(item, 'BLOK_NOMOR', 'blok_nomor'))) +
                    '</td>';

                /*
                 * KHUSUS dua kolom gabungan ini:
                 * - data yang ada tetap ditampilkan seperti biasa;
                 * - data yang kosong TIDAK diberi tanda '-';
                 * - posisi/barisnya tetap dipertahankan, jadi field kosong tidak dihapus.
                 *
                 * Contoh:
                 * NAMA PENGAJU
                 * [baris nomor pengajuan tetap ada tetapi kosong]
                 * [baris tanggal pengajuan tetap ada tetapi kosong]
                 */
                var namaText = valueOrBlank(nama);
                var pengajuanText = valueOrBlank(pengajuan);
                var tglPengajuanText = formatDateOrBlank(tglPengajuan);

                html += '<td class="rjb-name-cell">';
                html += escapeHtml(namaText) + '<br>';
                html += escapeHtml(pengajuanText) + '<br>';
                html += escapeHtml(tglPengajuanText);
                html += '</td>';

                html += '<td>';
                html += escapeHtml(valueOrDash(noJaminan)) + '<br>';
                html += escapeHtml(formatDate(tglJaminan));
                html += '</td>';

                var namaBankText = valueOrBlank(namaBank);
                var alamatBankText = valueOrBlank(alamatBank);
                var plafondText = formatCurrencyOrBlank(plafond);

                html += '<td class="rjb-bank-cell">';
                html += escapeHtml(namaBankText) + '<br>';
                html += escapeHtml(alamatBankText);
                html += '<span class="rjb-plafond">' + plafondText + '</span>';
                html += '</td>';

                html += '<td class="rjb-center">' + escapeHtml(valueOrDash(jenis)) + '</td>';

                html += '<td>';
                html += escapeHtml(valueOrDash(namaAmbil)) + '<br>';
                html += escapeHtml(formatDate(tglAmbil));
                html += '</td>';

                html += '<td>';
                html += escapeHtml(valueOrDash(noLunas)) + '<br>';
                html += escapeHtml(formatDate(tglLunas));
                html += '</td>';

                html += '<td>';
                html += escapeHtml(valueOrDash(noBatal)) + '<br>';
                html += escapeHtml(formatDate(tglBatal));
                html += '</td>';
                html += '</tr>';
            });
        }

        html += '</tbody></table>';
        html += '</div>';

        /* Footer tanda tangan mengikuti Daftar Sertipikat Pecahan / desktop. */
        html += '<div class="rjb-signature-footer">';
        html += '<div class="rjb-signature-date">';
        html += 'Jakarta, ' + escapeHtml(tanggalTandaTangan);
        html += '</div>';

        html += '<div class="rjb-signature-grid">';

        html += '<div class="rjb-signature-box">';
        html += '<div class="rjb-signature-role">Yang menyerahkan,</div>';
        html += '<div class="rjb-signature-space"></div>';
        html += '<div class="rjb-signature-line"><span></span></div>';
        html += '</div>';

        html += '<div class="rjb-signature-box">';
        html += '<div class="rjb-signature-role">Yang menerima,</div>';
        html += '<div class="rjb-signature-space"></div>';
        html += '<div class="rjb-signature-line"><span></span></div>';
        html += '</div>';

        html += '</div>';
        html += '</div>';
        html += '</div>';

        $('#mainDisplay').html(html);
        $('#rjbPrintButton').prop('disabled', false);
    }
</script>
@endsection