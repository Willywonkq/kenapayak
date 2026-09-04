@extends('layouts.template')

@section('content')
<style>
    /* =========================================================
       STANDARD FONT & PAGE LAYOUT MATCHING DAFTAR PBB & DESKTOP
       ========================================================= */
    .dps-page,
    .dps-page input,
    .dps-page select,
    .dps-page button,
    .dps-page textarea,
    .dps-page label,
    .dps-page table,
    .dps-page td,
    .dps-page .dps-report-subtitle,
    .dps-page .dps-print-meta {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
        box-sizing: border-box;
    }

    .dps-page * {
        box-sizing: border-box;
    }

    .dps-page .dps-toolbar-title,
    .dps-page .dps-label,
    .dps-page .dps-table th,
    .dps-page .dps-action {
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
    }

    .dps-page .dps-report-title h2 {
        font-family: Cambria, Georgia, "Times New Roman", serif !important;
        font-weight: 700 !important;
    }

    .dps-page {
        position: relative;
        width: 100%;
        min-width: 720px;
        min-height: 100%;
        padding: 18px 12px 32px;
        overflow: visible;
        color: #172033;
        background:
            radial-gradient(circle at 95% 2%, rgba(37, 99, 235, 0.07), transparent 28%),
            radial-gradient(circle at 8% 96%, rgba(56, 189, 248, 0.05), transparent 26%),
            #f3f6fb;
    }

    /* Command Ribbon Header Bar */
    .dps-toolbar {
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

    .dps-toolbar::before {
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
        font-size: 18px;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.24);
    }

    .dps-toolbar::after {
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

    .dps-toolbar-title {
        position: relative;
        z-index: 1;
        margin: 0;
        color: #172033;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: 0.11em;
        text-transform: uppercase;
    }

    .dps-unit-badge {
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

    /* Filter Card Panel Form */
    .dps-filter-panel {
        position: relative;
        margin: 0;
        padding: 20px;
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
    }

    .dps-filter-panel::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: linear-gradient(180deg, #38bdf8 0%, #2563eb 52%, #1d4ed8 100%);
    }

    .dps-filter-panel::after {
        content: "Daftar Pengambilan Surat";
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

    .dps-filter-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 82px;
        gap: 13px 14px;
        align-items: center;
    }

    /* POSISI KHUSUS SAAT MODE PT AKTIF: Tgl. Ambil & Tgl. PPJB Sejajar di Baris 2 */
    .dps-filter-panel.dps-mode-pt-active #fieldAmbil {
        grid-column: 1;
        grid-row: 2;
    }

    .dps-filter-panel.dps-mode-pt-active #fieldPpjb {
        grid-column: 2;
        grid-row: 2;
    }

    .dps-filter-panel.dps-mode-pt-active #fieldPh {
        grid-column: 1;
        grid-row: 3;
    }

    .dps-field {
        display: grid;
        grid-template-columns: 96px minmax(0, 1fr);
        gap: 9px;
        align-items: center;
        min-width: 0;
    }

    .dps-field label,
    .dps-label {
        overflow: hidden;
        color: #475467;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: 0.10em;
        text-overflow: ellipsis;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .dps-range {
        display: grid;
        grid-template-columns: minmax(86px, 1fr) 34px minmax(86px, 1fr);
        gap: 7px;
        align-items: center;
    }

    .dps-range span {
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
        letter-spacing: 0.04em;
        text-align: center;
    }

    .dps-input,
    .dps-select {
        width: 100%;
        min-width: 0;
        height: 42px;
        padding: 8px 12px;
        border: 1px solid #c8d3e1;
        border-radius: 12px;
        background: #ffffff;
        color: #101828;
        font-size: 9px;
        font-weight: 650;
        outline: 0;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .dps-input:hover,
    .dps-select:hover {
        border-color: #aebed1;
        background: #ffffff;
    }

    .dps-input:focus,
    .dps-select:focus {
        border-color: #2563eb;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
    }

    /* Action Stack */
    .dps-action-stack {
        position: relative;
        width: 82px;
        height: 42px;
        grid-column: 3;
        grid-row: 1;
        align-self: start;
        justify-self: end;
    }

    .dps-submit,
    .dps-print-button {
        display: inline-flex;
        width: 82px;
        min-width: 82px;
        height: 42px;
        align-items: center;
        justify-content: center;
        border: 0;
        cursor: pointer;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .dps-submit {
        position: absolute;
        top: 0;
        right: -20px;
        margin: 0;
        border-radius: 13px 0 0 13px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
    }

    .dps-submit:hover:not(:disabled) {
        transform: translateY(-1px);
        background: linear-gradient(135deg, #2f6ff0 0%, #1e4fc4 100%);
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.30);
    }

    .dps-submit:disabled {
        cursor: not-allowed;
        opacity: 0.7;
    }

    .dps-print-button {
        position: absolute;
        top: 55px;
        right: -20px;
        border-radius: 13px 0 0 13px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(5, 150, 105, 0.22);
    }

    .dps-print-button:hover:not(:disabled) {
        transform: translateY(-1px);
        background: linear-gradient(135deg, #14b88a 0%, #047857 100%);
        box-shadow: 0 12px 24px rgba(5, 150, 105, 0.28);
    }

    .dps-print-button:disabled,
    .dps-print-button:disabled:hover,
    .dps-print-button:disabled:focus {
        transform: none;
        border: 1px solid #d5dde7;
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        color: #94a3b8;
        box-shadow: none;
        cursor: not-allowed;
        opacity: 1;
    }

    /* Checkbox Row di Bagian Bawah Filter */
    .dps-mode-row {
        grid-column: 1 / span 2;
        display: flex;
        align-items: center;
        gap: 16px;
        min-height: 38px;
        padding-top: 6px;
    }

    .dps-checkbox {
        display: inline-flex;
        min-height: 34px;
        align-items: center;
        gap: 8px;
        margin: 0;
        padding: 6px 14px;
        border: 1px solid #e5eaf2;
        border-radius: 999px;
        background: #fafbfc;
        color: #344054;
        font-size: 11px;
        font-weight: 750;
        cursor: pointer;
        white-space: nowrap;
        transition: border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
    }

    .dps-checkbox:hover {
        border-color: #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .dps-checkbox input {
        width: 15px;
        height: 15px;
        margin: 0;
        accent-color: #2563eb;
    }

    .dps-checkbox:has(input:checked) {
        border-color: #bfdbfe;
        background: linear-gradient(135deg, #eff6ff, #e0f2fe);
        color: #1e40af;
    }

    .dps-hidden {
        display: none !important;
    }

    /* Workspace & Report Shell Area */
    .dps-workspace {
        position: relative;
        margin-top: 18px;
        padding: 20px;
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 26px;
        background: #ffffff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
    }

    .dps-workspace::before {
        content: "";
        position: absolute;
        top: 0;
        left: 7%;
        right: 7%;
        height: 3px;
        border-radius: 0 0 999px 999px;
        background: linear-gradient(90deg, transparent, #38bdf8, #2563eb, #6366f1, transparent);
    }

    .dps-paper {
        width: 100%;
        min-height: 0;
        padding: 0;
        border: 0;
        background: transparent;
        box-shadow: none;
    }

    .dps-loading,
    .dps-empty,
    .dps-error {
        display: flex;
        min-height: 310px;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        border: 1px dashed #bfdbfe;
        border-radius: 20px;
        background: radial-gradient(circle at center, rgba(37, 99, 235, 0.06), transparent 46%), #f8fbff;
        color: #667085;
        font-size: 13px;
        font-weight: 650;
        text-align: center;
    }

    .dps-empty::before {
        content: "⌁";
        display: inline-flex;
        width: 52px;
        height: 52px;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: #dbeafe;
        color: #2563eb;
        font-size: 24px;
        font-weight: 900;
    }

    .dps-error {
        border-color: #fca5a5;
        background: #fef2f2;
        color: #dc2626;
    }

    /* Header Hasil Laporan */
    .dps-report-head {
        display: grid;
        grid-template-columns: minmax(150px, 1fr) minmax(280px, 1.5fr) minmax(180px, 1fr);
        gap: 16px;
        align-items: center;
        margin-bottom: 10px;
        padding: 15px 18px;
        border: 1px solid #dbeafe;
        border-radius: 18px;
        background: linear-gradient(105deg, #eff6ff 0%, #dbeafe 48%, #eef2ff 100%);
        color: #172033;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.10);
    }

    .dps-company {
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 850;
        letter-spacing: 0.03em;
        line-height: 1.35;
    }

    .dps-report-title {
        text-align: center;
    }

    .dps-report-title h2 {
        margin: 0 0 4px;
        color: #172033;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .dps-report-title div {
        color: #475467;
        font-size: 10px;
        line-height: 1.5;
    }

    .dps-print-meta {
        color: #475467;
        font-size: 10.5px;
        font-weight: 650;
        line-height: 1.55;
        text-align: right;
    }

    /* Sub-header Sektor/Cluster */
    .dps-report-subtitle {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 12px;
        min-height: 36px;
        margin-bottom: 10px;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: linear-gradient(90deg, #ffffff, #f8fafc);
        color: #475467;
        font-size: 10.5px;
    }

    .dps-report-subtitle-label {
        font-weight: 700;
        color: #475467;
    }

    .dps-report-subtitle-value {
        text-align: center;
        font-weight: 800;
        color: #172033;
    }

    .dps-live-badge {
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
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .dps-live-badge::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
    }

    /* Table Design & Sticky Sub-Headers */
    .dps-table-wrap {
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

    .dps-table-wrap::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .dps-table-wrap::-webkit-scrollbar-track {
        background: #eff3f7;
    }

    .dps-table-wrap::-webkit-scrollbar-thumb {
        border: 2px solid #eff3f7;
        border-radius: 999px;
        background: linear-gradient(180deg, #60a5fa, #2563eb);
    }

    .dps-table {
        width: 100%;
        min-width: 1480px;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: fixed;
        color: #344054;
        font-size: 10.5px;
    }

    .dps-table.dps-pt-table {
        min-width: 1350px;
    }

    .dps-table th,
    .dps-table td {
        padding: 8px 9px;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        overflow-wrap: anywhere;
    }

    .dps-table thead tr:first-child th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: linear-gradient(180deg, #eff6ff 0%, #e5effb 100%);
        color: #1e3a5f;
        text-align: center;
        font-weight: 900;
        line-height: 1.25;
        box-shadow: inset 0 -1px 0 rgba(37, 99, 235, 0.08);
    }

    .dps-table thead tr:nth-child(2) th {
        position: sticky;
        top: 32px;
        z-index: 4;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        color: #475467;
        text-align: center;
        font-weight: 850;
        line-height: 1.2;
    }

    .dps-table td {
        height: 44px;
        background: #ffffff;
        color: #344054;
        line-height: 1.38;
    }

    .dps-table tbody tr:nth-child(even) td {
        background: #fbfcfe;
    }

    .dps-table tbody tr:hover td {
        background: #f0f7ff;
    }

    .dps-table tbody tr:hover td:first-child {
        box-shadow: inset 4px 0 0 #2563eb;
        color: #1d4ed8;
        font-weight: 900;
    }

    .dps-center {
        text-align: center;
    }

    .dps-right {
        color: #1e3a5f;
        text-align: right;
        font-weight: 750;
        font-variant-numeric: tabular-nums;
    }

    .dps-name {
        color: #172033;
        font-weight: 800;
    }

    .dps-table tfoot td {
        position: sticky;
        bottom: 0;
        z-index: 2;
        height: 38px;
        border-top: 2px solid #2563eb;
        background: #eff6ff;
        color: #1e3a5f;
        font-weight: 850;
    }

    .dps-total-label {
        text-align: right;
    }

    .dps-total-count {
        text-align: center;
        font-variant-numeric: tabular-nums;
    }

    @media screen and (max-width: 719px) {
        html, body {
            min-width: 720px;
        }
        .dps-page {
            min-width: 720px;
        }
    }

    @media print {
        .dps-toolbar,
        .dps-filter-panel,
        #dpsSektorModal,
        #dpsNoDataAlertModal,
        .main-sidebar,
        .control-sidebar,
        .main-header,
        .main-footer,
        .navbar,
        .sidebar {
            display: none !important;
        }

        html, body, .wrapper, .content-wrapper, .main-content, .content,
        .page-wrapper, .page-content, .container, .container-fluid,
        .dps-page, .dps-workspace, .dps-paper {
            width: 100% !important;
            min-width: 0 !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            background: #fff !important;
            box-shadow: none !important;
            transform: none !important;
        }

        .dps-table-wrap {
            max-height: none !important;
            overflow: visible !important;
        }

        .dps-table, .dps-table.dps-pt-table {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
        }
    }

    /* =========================================================
       ALERT DATA KOSONG — SAMA DENGAN DAFTAR SERTIFIKAT PECAHAN
       Modal informasi yang tampil saat hasil laporan tidak
       menghasilkan baris data sama sekali.
       ========================================================= */
        #dpsNoDataAlertModal .modal-dialog {
            max-width: 380px;
        }

        #dpsNoDataAlertModal .modal-content {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
        }

        #dpsNoDataAlertModal .alert-icon-wrapper {
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

        #dpsNoDataAlertModal .alert-title {
            margin-bottom: 8px;
            color: #172033;
            font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
            font-size: 18px;
            font-weight: 700;
        }

        #dpsNoDataAlertModal .alert-message {
            margin-bottom: 24px;
            color: #475569;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            font-size: 14px;
        }

        #dpsNoDataAlertModal .alert-btn-ok {
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

        #dpsNoDataAlertModal .alert-btn-ok:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
        }

</style>

<!-- MODAL ALERT DATA KOSONG -->
<div class="modal fade" id="dpsNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4" style="text-align: center; padding: 1.5rem;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message" id="dpsNoDataMessage">Data tidak ditemukan.</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    id="dpsNoDataAlertOk"
                    data-dismiss="modal"
                >OK</button>
            </div>
        </div>
    </div>
</div>


<div class="dps-page">
    <div class="dps-toolbar">
        <h1 class="dps-toolbar-title">Daftar Pengambilan Surat-Surat</h1>
        <code class="dps-unit-badge">
            UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? '-' }}
        </code>
    </div>

    {{-- Form Filter dengan autocomplete="off" --}}
    <form id="dpsFilterForm" class="dps-filter-panel" autocomplete="off" onsubmit="return false;">
        <input type="hidden" id="dpsPerusahaan" value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? '' }}">
        <input type="hidden" id="dpsNamaPerusahaan" value="{{ session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? '' }}">

        <div class="dps-filter-grid">
            {{-- Row 1: Blok & Sektor --}}
            <div class="dps-field">
                <label for="dpsBlokAwal" class="dps-label">Blok</label>
                <div class="dps-range">
                    <input type="text" id="dpsBlokAwal" class="dps-input" value="A" maxlength="30" autocomplete="off">
                    <span>s.d</span>
                    <input type="text" id="dpsBlokAkhir" class="dps-input" value="ZZ" maxlength="30" autocomplete="off">
                </div>
            </div>

            <div class="dps-field">
                <label for="dpsSektor" class="dps-label">Sektor/Cluster</label>
                <select id="dpsSektor" class="dps-select" autocomplete="off">
                    <option value="*">Semua Sektor</option>
                </select>
            </div>

            {{-- Action Stack --}}
            <div class="dps-action-stack">
                <button type="button" class="dps-submit" id="dpsSubmitButton">OK</button>
                <button type="button" class="dps-print-button" id="dpsPrintButton" disabled aria-disabled="true">
                    PRINT
                </button>
            </div>

            {{-- Filter Tanggal Terima IMB (DEFAULT HARI INI PERSIS DESKTOP) --}}
            <div class="dps-regular-filter dps-field">
                <label class="dps-label">Tgl. Terima IMB</label>
                <div class="dps-range">
                    <input type="date" id="dpsImbAwal" class="dps-input" value="{{ date('Y-m-d') }}" autocomplete="off">
                    <span>s.d</span>
                    <input type="date" id="dpsImbAkhir" class="dps-input" value="{{ date('Y-m-d') }}" autocomplete="off">
                </div>
            </div>

            <div id="fieldAmbil" class="dps-special-filter dps-field dps-hidden">
                <label class="dps-label">Tgl. Ambil</label>
                <div class="dps-range">
                    <input type="date" id="dpsAmbilAwal" class="dps-input" value="{{ date('Y-m-d') }}" autocomplete="off">
                    <span>s.d</span>
                    <input type="date" id="dpsAmbilAkhir" class="dps-input" value="{{ date('Y-m-d') }}" autocomplete="off">
                </div>
            </div>

            <div class="dps-regular-filter dps-field">
                <label class="dps-label">Tgl. Terima Sert.</label>
                <div class="dps-range">
                    <input type="date" id="dpsSertAwal" class="dps-input" autocomplete="off">
                    <span>s.d</span>
                    <input type="date" id="dpsSertAkhir" class="dps-input" autocomplete="off">
                </div>
            </div>

            <div class="dps-regular-filter"></div>

            {{-- Filter Tanggal Terima AJB & SHM --}}
            <div class="dps-regular-filter dps-field">
                <label class="dps-label">Tgl. Terima AJB</label>
                <div class="dps-range">
                    <input type="date" id="dpsAjbAwal" class="dps-input" autocomplete="off">
                    <span>s.d</span>
                    <input type="date" id="dpsAjbAkhir" class="dps-input" autocomplete="off">
                </div>
            </div>

            <div class="dps-regular-filter dps-field">
                <label class="dps-label">Tgl. Terima SHM</label>
                <div class="dps-range">
                    <input type="date" id="dpsShmAwal" class="dps-input" autocomplete="off">
                    <span>s.d</span>
                    <input type="date" id="dpsShmAkhir" class="dps-input" autocomplete="off">
                </div>
            </div>

            <div class="dps-regular-filter"></div>

            {{-- Filter Tanggal Terima PH & PPJB --}}
            <div id="fieldPh" class="dps-field">
                <label class="dps-label">Tgl. Terima PH</label>
                <div class="dps-range">
                    <input type="date" id="dpsPhAwal" class="dps-input" autocomplete="off">
                    <span>s.d</span>
                    <input type="date" id="dpsPhAkhir" class="dps-input" autocomplete="off">
                </div>
            </div>

            <div id="fieldPpjb" class="dps-field">
                <label class="dps-label">Tgl. Terima PPJB</label>
                <div class="dps-range">
                    <input type="date" id="dpsPpjbAwal" class="dps-input" autocomplete="off">
                    <span>s.d</span>
                    <input type="date" id="dpsPpjbAkhir" class="dps-input" autocomplete="off">
                </div>
            </div>

            <div></div>

            {{-- Checkbox Row di Bagian Bawah Filter --}}
            <div class="dps-mode-row">
                <label class="dps-checkbox">
                    <input type="checkbox" id="dpsModePt" autocomplete="off">
                    <span>Pengambilan Sertipikat a/n PT</span>
                </label>
            </div>
        </div>
    </form>

    <section class="dps-workspace">
        <div class="dps-paper" id="dpsReport" aria-live="polite">
            <div class="dps-empty">Isi filter, lalu klik tombol OK untuk menampilkan laporan.</div>
        </div>
    </section>
</div>
@endsection

@section('js')
<script>
(function () {
    'use strict';

    var baseUrl = "{{ url()->current() }}";
    var csrfToken = "{{ csrf_token() }}";
    var lastRows = [];
    var lastMode = 'biasa';

    var regularDateFields = [
        ['tgl_terima_imb_awal', 'tgl_terima_imb_akhir', 'dpsImbAwal', 'dpsImbAkhir', 'IMB'],
        ['tgl_terima_sertipikat_awal', 'tgl_terima_sertipikat_akhir', 'dpsSertAwal', 'dpsSertAkhir', 'Sert. HGB'],
        ['tgl_terima_ajb_awal', 'tgl_terima_ajb_akhir', 'dpsAjbAwal', 'dpsAjbAkhir', 'AJB'],
        ['tgl_terima_shm_awal', 'tgl_terima_shm_akhir', 'dpsShmAwal', 'dpsShmAkhir', 'SHM'],
        ['tgl_terima_ph_awal', 'tgl_terima_ph_akhir', 'dpsPhAwal', 'dpsPhAkhir', 'PH'],
        ['tgl_terima_ppjb_awal', 'tgl_terima_ppjb_akhir', 'dpsPpjbAwal', 'dpsPpjbAkhir', 'PPJB']
    ];

    function getInputValue(id) {
        var el = document.getElementById(id);
        return el ? el.value : '';
    }

    function setInputValue(id, val) {
        var el = document.getElementById(id);
        if (el) el.value = val;
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Reset bertahap untuk memastikan Tgl Terima IMB langsung terisi tanggal hari ini
        resetPage();
        setTimeout(resetPage, 10);
        setTimeout(resetPage, 100);

        var btnSubmit = document.getElementById('dpsSubmitButton');
        var btnPrint = document.getElementById('dpsPrintButton');
        var chkModePt = document.getElementById('dpsModePt');

        if (btnSubmit) btnSubmit.addEventListener('click', obtainData);
        if (btnPrint) {
            btnPrint.addEventListener('click', function () {
                if (!this.disabled) {
                    printDpsInNativeDialog();
                }
            });
        }
        if (chkModePt) chkModePt.addEventListener('change', syncMode);

        var btnNoDataOk = document.getElementById('dpsNoDataAlertOk');
        if (btnNoDataOk) btnNoDataOk.addEventListener('click', hideDpsNoDataAlert);

        ['dpsBlokAwal', 'dpsBlokAkhir'].forEach(function (id) {
            var input = document.getElementById(id);
            if (input) {
                input.addEventListener('input', function () {
                    this.value = String(this.value || '').toUpperCase();
                });
            }
        });

        obtainSektor();
    });

    window.addEventListener('pageshow', function () {
        resetPage();
    });

    window.addEventListener('load', function () {
        resetPage();
    });

    function getMode() {
        var chkModePt = document.getElementById('dpsModePt');
        return (chkModePt && chkModePt.checked) ? 'pengambilan_pt' : 'biasa';
    }

    function syncMode() {
        var special = getMode() === 'pengambilan_pt';

        document.querySelectorAll('.dps-regular-filter').forEach(function (element) {
            element.classList.toggle('dps-hidden', special);
        });
        document.querySelectorAll('.dps-special-filter').forEach(function (element) {
            element.classList.toggle('dps-hidden', !special);
        });

        var filterPanel = document.querySelector('.dps-filter-panel');
        if (filterPanel) {
            filterPanel.classList.toggle('dps-mode-pt-active', special);
        }
    }

    /* HARD RESET FUNCTION SAAT DI REFRESH */
    function resetPage() {
        try { localStorage.removeItem('sris.dps.savedFilters'); } catch (e) {}

        var form = document.getElementById('dpsFilterForm');
        if (form && typeof form.reset === 'function') {
            form.reset();
        }

        setInputValue('dpsBlokAwal', 'A');
        setInputValue('dpsBlokAkhir', 'ZZ');
        setInputValue('dpsSektor', '*');
        
        var chkModePt = document.getElementById('dpsModePt');
        if (chkModePt) chkModePt.checked = false;

        // Kosongkan semua filter tanggal selain IMB & Ambil
        regularDateFields.forEach(function (field) {
            setInputValue(field[2], '');
            setInputValue(field[3], '');
        });

        // Set TGL TERIMA IMB Awal & Akhir ke TANGGAL HARI INI (Sama persis seperti Desktop SRIS)
        var today = localToday();
        setInputValue('dpsImbAwal', today);
        setInputValue('dpsImbAkhir', today);

        setInputValue('dpsAmbilAwal', today);
        setInputValue('dpsAmbilAkhir', today);
        
        var btnPrint = document.getElementById('dpsPrintButton');
        if (btnPrint) {
            btnPrint.disabled = true;
            btnPrint.setAttribute('aria-disabled', 'true');
        }

        var report = document.getElementById('dpsReport');
        if (report) {
            report.innerHTML = '<div class="dps-empty">Isi filter, lalu klik tombol OK untuk menampilkan laporan.</div>';
        }

        lastRows = [];
        lastMode = 'biasa';
        hideDpsNoDataAlert();
        syncMode();
    }

    function localToday() {
        var today = new Date();
        var year = today.getFullYear();
        var month = String(today.getMonth() + 1).padStart(2, '0');
        var day = String(today.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function obtainSektor() {
        var perusahaan = getInputValue('dpsPerusahaan').trim();
        if (!perusahaan) return;

        postForm(baseUrl + '/get_sektor', {
            perusahaan: perusahaan
        }).then(function (rows) {
            var select = document.getElementById('dpsSektor');
            if (!select) return;

            select.innerHTML = '<option value="*">Semua Sektor</option>';

            (Array.isArray(rows) ? rows : []).forEach(function (item) {
                var option = document.createElement('option');
                option.value = pick(item, ['KD_SEKTOR', 'kd_sektor']) || '';
                option.textContent = pick(item, ['DESKRIPSI', 'deskripsi']) || option.value;
                select.appendChild(option);
            });
            select.value = '*';
        }).catch(function () {
            // Pilihan Semua Sektor tetap dapat dipakai
        });
    }

    function collectFilters() {
        var data = {
            mode: getMode(),
            blok_awal: getInputValue('dpsBlokAwal') || 'A',
            blok_akhir: getInputValue('dpsBlokAkhir') || 'ZZ',
            perusahaan: getInputValue('dpsPerusahaan'),
            sektor: getInputValue('dpsSektor') || '*',
            tgl_ambil_awal: getInputValue('dpsAmbilAwal'),
            tgl_ambil_akhir: getInputValue('dpsAmbilAkhir')
        };

        regularDateFields.forEach(function (field) {
            data[field[0]] = getInputValue(field[2]);
            data[field[1]] = getInputValue(field[3]);
        });

        return data;
    }

    function validateFilters(data) {
        if (!String(data.perusahaan || '').trim()) {
            return 'Kode unit/perusahaan tidak tersedia pada session.';
        }

        if (!String(data.blok_awal || '').trim() || !String(data.blok_akhir || '').trim()) {
            return 'Rentang blok wajib diisi.';
        }

        if (data.mode === 'pengambilan_pt') {
            if ((data.tgl_ambil_awal && !data.tgl_ambil_akhir) || (!data.tgl_ambil_awal && data.tgl_ambil_akhir)) {
                return 'Tanggal awal dan akhir Tanggal Ambil harus diisi berpasangan.';
            }
            if (data.tgl_ambil_awal && data.tgl_ambil_akhir && data.tgl_ambil_awal > data.tgl_ambil_akhir) {
                return 'Tanggal ambil awal tidak boleh melebihi tanggal akhir.';
            }
            return '';
        }

        for (var i = 0; i < regularDateFields.length; i += 1) {
            var field = regularDateFields[i];
            var start = data[field[0]];
            var end = data[field[1]];

            if ((start && !end) || (!start && end)) {
                return 'Tanggal awal dan akhir ' + field[4] + ' harus diisi berpasangan.';
            }
            if (start && end && start > end) {
                return 'Tanggal awal ' + field[4] + ' tidak boleh melebihi tanggal akhir.';
            }
        }

        return '';
    }

    /*
     * Alert data kosong, mengikuti Daftar Sertifikat Pecahan.
     * Skrip halaman ini berada di dalam IIFE, jadi tombol OK dipasang
     * lewat addEventListener, bukan atribut onclick. Jika plugin modal
     * tidak tersedia, modal ditampilkan manual agar tampilannya sama.
     */
    function showDpsNoDataAlert(message) {
        var modal = document.getElementById('dpsNoDataAlertModal');
        var messageElement = document.getElementById('dpsNoDataMessage');
        var text = message || 'Data tidak ditemukan......!';

        if (messageElement) {
            messageElement.textContent = text;
        }

        if (!modal) {
            window.alert(text);
            return;
        }

        if (window.jQuery && typeof window.jQuery(modal).modal === 'function') {
            window.jQuery(modal).modal('show');
            return;
        }

        if (!document.querySelector('.dps-nodata-backdrop')) {
            var backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show dps-nodata-backdrop';
            document.body.appendChild(backdrop);
        }

        modal.classList.add('show');
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
    }

    function hideDpsNoDataAlert() {
        var modal = document.getElementById('dpsNoDataAlertModal');

        if (!modal) {
            return;
        }

        if (window.jQuery && typeof window.jQuery(modal).modal === 'function') {
            window.jQuery(modal).modal('hide');
            return;
        }

        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');

        var backdrop = document.querySelector('.dps-nodata-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }

    function obtainData() {
        var filters = collectFilters();
        var error = validateFilters(filters);

        if (error) {
            window.alert(error);
            return;
        }

        hideDpsNoDataAlert();
        setBusy(true);

        var report = document.getElementById('dpsReport');
        if (report) {
            report.innerHTML = '<div class="dps-loading">Mengambil data laporan...</div>';
        }

        postForm(baseUrl + '/get_data', filters)
            .then(function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (response && Array.isArray(response.data) ? response.data : []);

                lastRows = rows;
                lastMode = filters.mode;
                renderReport(rows, filters);
                
                var btnPrint = document.getElementById('dpsPrintButton');
                if (btnPrint) {
                    btnPrint.disabled = rows.length === 0;
                    btnPrint.setAttribute('aria-disabled', rows.length === 0 ? 'true' : 'false');
                }

                if (rows.length === 0) {
                    showDpsNoDataAlert(
                        filters.mode === 'pengambilan_pt'
                            ? 'Data Pengambilan Sertipikat a/n PT tidak ditemukan......!'
                            : 'Data tidak ditemukan......!'
                    );
                }
            })
            .catch(function (errorObject) {
                var message = errorObject && errorObject.message
                    ? errorObject.message
                    : 'Gagal mengambil data laporan.';

                if (report) {
                    report.innerHTML = '<div class="dps-error">' + escapeHtml(message) + '</div>';
                }
                var btnPrint = document.getElementById('dpsPrintButton');
                if (btnPrint) {
                    btnPrint.disabled = true;
                    btnPrint.setAttribute('aria-disabled', 'true');
                }
            })
            .finally(function () {
                setBusy(false);
            });
    }

    function setBusy(busy) {
        var button = document.getElementById('dpsSubmitButton');
        if (button) {
            button.disabled = busy;
            button.textContent = busy ? 'Proses...' : 'OK';
        }
    }

    function postForm(url, data) {
        var payload = new URLSearchParams();
        payload.append('_token', csrfToken);

        Object.keys(data).forEach(function (key) {
            payload.append(key, data[key] === null || data[key] === undefined ? '' : data[key]);
        });

        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
            },
            body: payload.toString()
        }).then(async function (response) {
            var body;
            try { body = await response.json(); } catch (parseError) { body = null; }

            if (!response.ok) {
                var message = body && body.message ? body.message : 'Permintaan gagal.';
                if (body && body.errors) {
                    var firstError = Object.keys(body.errors)[0];
                    if (firstError && body.errors[firstError] && body.errors[firstError][0]) {
                        message = body.errors[firstError][0];
                    }
                }
                throw new Error(message);
            }
            return body;
        });
    }

    function renderReport(rows, filters) {
        var report = document.getElementById('dpsReport');
        if (!report) return;

        if (!rows.length) {
            report.innerHTML = '<div class="dps-empty">Data tidak ditemukan untuk filter yang dipilih.</div>';
            return;
        }

        report.innerHTML = filters.mode === 'pengambilan_pt'
            ? buildSpecialReport(rows, filters)
            : buildRegularReport(rows, filters);
    }

    /* EKSTRAKSI NAMA PT LENGKAP KIRI ATAS */
    function dpsExtractCompanyName(value) {
        var raw = String(value || '').replace(/\u00a0/g, ' ');
        var locationMatch = raw.match(/Lokasi\s*:[^\r\n|]*?-\s*([^\r\n|]+)/i);
        var companyMatch = raw.match(/\b((?:PT\.?|CV\.?|PD\.?|PERUM)\s+[^\r\n|]+)/i);
        var name = locationMatch ? locationMatch[1] : (companyMatch ? companyMatch[1] : '');
        return String(name || '')
            .replace(/\s+(?:Hi\s*,|Logout\b|Keluar\b|LAND DOCUMENT\b|UNIT\s+[A-Z0-9_-]+\b).*$/i, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function dpsCompanyNameFromLayout() {
        var selectors = [
            '[data-nama-perusahaan]', '[data-company-name]', '[data-unit-name]',
            'input[name="nama_perusahaan"]', 'input[name="nama_pt"]', '#nama_perusahaan', '#nama_pt',
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
                var name = dpsExtractCompanyName(candidates[j]);
                if (name) return name;
            }
        }
        return '';
    }

    function dpsCompanyName(first) {
        var unit = String(getInputValue('dpsPerusahaan') || '').trim().toUpperCase();
        var rowName = pick(first || {}, [
            'NAMA_PT', 'nama_pt', 'ATAS_NAMA_PT', 'atas_nama_pt', 'NAMA_PERUSAHAAN', 'nama_perusahaan'
        ]);
        var sessionName = String(getInputValue('dpsNamaPerusahaan') || '').trim();
        var cacheKey = 'sris.report-company.' + unit;
        var cachedName = '';
        try { cachedName = localStorage.getItem(cacheKey) || ''; } catch (error) {}

        var company =
            dpsExtractCompanyName(rowName)
            || String(rowName || '').trim()
            || dpsCompanyNameFromLayout()
            || dpsExtractCompanyName(sessionName)
            || sessionName
            || cachedName
            || unit
            || '-';

        if (company && company !== '-' && company.toUpperCase() !== unit) {
            try { localStorage.setItem(cacheKey, company); } catch (error) {}
        }
        return company;
    }

    function formatDateRange(start, end) {
        var s = start ? formatDate(start) : '00-00-0000';
        var e = end ? formatDate(end) : '00-00-0000';
        return s + ' s/d ' + e;
    }

    /* BUILD HEADER LAPORAN */
    function buildRegularHeaderHtml(rows, filters) {
        var first = rows.length > 0 ? rows[0] : {};
        var company = dpsCompanyName(first);
        
        var sektorSelect = document.getElementById('dpsSektor');
        var sektorText = 'Semua Sektor';
        if (sektorSelect && sektorSelect.selectedIndex >= 0 && sektorSelect.options[sektorSelect.selectedIndex]) {
            sektorText = sektorSelect.options[sektorSelect.selectedIndex].text;
        }

        return ''
            + '<div class="dps-report-head">'
            + '  <div class="dps-company">' + escapeHtml(company) + '</div>'
            + '  <div class="dps-report-title">'
            + '      <h2>Rekapitulasi Pengambilan Surat-Surat</h2>'
            + '      <div>BLOK : ' + escapeHtml(filters.blok_awal) + ' s/d ' + escapeHtml(filters.blok_akhir) + '</div>'
            + '      <div>Tgl. Terima IMB : ' + escapeHtml(formatDateRange(filters.tgl_terima_imb_awal, filters.tgl_terima_imb_akhir)) + '</div>'
            + '      <div>Tgl. Terima Sertipikat : ' + escapeHtml(formatDateRange(filters.tgl_terima_sertipikat_awal, filters.tgl_terima_sertipikat_akhir)) + '</div>'
            + '      <div>Tgl. Terima AJB : ' + escapeHtml(formatDateRange(filters.tgl_terima_ajb_awal, filters.tgl_terima_ajb_akhir)) + '</div>'
            + '      <div>Tgl. Terima SHM : ' + escapeHtml(formatDateRange(filters.tgl_terima_shm_awal, filters.tgl_terima_shm_akhir)) + '</div>'
            + '      <div>Tgl. Terima PH : ' + escapeHtml(formatDateRange(filters.tgl_terima_ph_awal, filters.tgl_terima_ph_akhir)) + '</div>'
            + '      <div>Tgl. Terima PPJB : ' + escapeHtml(formatDateRange(filters.tgl_terima_ppjb_awal, filters.tgl_terima_ppjb_akhir)) + '</div>'
            + '  </div>'
            + '  <div class="dps-print-meta">'
            + '      Tanggal Cetak: ' + escapeHtml(formatDate(localToday())) + '<br>'
            + '      Jumlah Data: ' + rows.length
            + '  </div>'
            + '</div>'
            + '<div class="dps-report-subtitle">'
            + '  <span class="dps-report-subtitle-label">Sektor/Cluster:</span>'
            + '  <span class="dps-report-subtitle-value">' + escapeHtml(sektorText) + '</span>'
            + '  <span class="dps-live-badge">Live Data</span>'
            + '</div>';
    }

    function buildSpecialHeaderHtml(rows, filters) {
        var first = rows.length > 0 ? rows[0] : {};
        var company = dpsCompanyName(first);

        var sektorSelect = document.getElementById('dpsSektor');
        var sektorText = 'Semua Sektor';
        if (sektorSelect && sektorSelect.selectedIndex >= 0 && sektorSelect.options[sektorSelect.selectedIndex]) {
            sektorText = sektorSelect.options[sektorSelect.selectedIndex].text;
        }

        return ''
            + '<div class="dps-report-head">'
            + '  <div class="dps-company">' + escapeHtml(company) + '</div>'
            + '  <div class="dps-report-title">'
            + '      <h2>Rekap Pengambilan Sertipikat a/n PT oleh Bagian Legal</h2>'
            + '      <div>BLOK : ' + escapeHtml(filters.blok_awal) + ' s/d ' + escapeHtml(filters.blok_akhir) + '</div>'
            + '      <div>Tgl. Ambil : ' + escapeHtml(formatDateRange(filters.tgl_ambil_awal, filters.tgl_ambil_akhir)) + '</div>'
            + '  </div>'
            + '  <div class="dps-print-meta">'
            + '      Tanggal Cetak: ' + escapeHtml(formatDate(localToday())) + '<br>'
            + '      Jumlah Data: ' + rows.length
            + '  </div>'
            + '</div>'
            + '<div class="dps-report-subtitle">'
            + '  <span class="dps-report-subtitle-label">Sektor/Cluster:</span>'
            + '  <span class="dps-report-subtitle-value">' + escapeHtml(sektorText) + '</span>'
            + '  <span class="dps-live-badge">Live Data</span>'
            + '</div>';
    }

    /* REPORT REGULAR */
    function buildRegularReport(rows, filters) {
        var inputKeys = [
            'TGL_INPUT_IMB', 'TGL_INPUT_SER', 'TGL_INPUT_AKTA',
            'TGL_INPUT_SHM', 'TGL_INPUT_PH', 'TGL_INPUT_PPJB'
        ];
        var takeKeys = [
            'TGL_AMBIL_IMB', 'TGL_AMBIL_SER', 'TGL_AMBIL_AKTA',
            'TGL_AMBIL_SHM', 'TGL_AMBIL_PH', 'TGL_AMBIL_PPJB'
        ];
        var allDateKeys = inputKeys.concat(takeKeys);

        var html = buildRegularHeaderHtml(rows, filters);

        html += '<div class="dps-table-wrap"><table class="dps-table">';
        html += '<thead>';
        html += '<tr>'
            + '<th rowspan="2" style="width:48px;">No.</th>'
            + '<th rowspan="2" style="width:90px;">BLOK/<br>NOMOR</th>'
            + '<th rowspan="2" style="width:190px;">Nama Pemilik</th>'
            + '<th colspan="6" style="width:528px;">Tanggal Terima</th>'
            + '<th colspan="6" style="width:528px;">Tanggal Ambil</th>'
            + '</tr>';
        
        html += '<tr>'
            + '<th style="width:88px;">IMB</th><th style="width:88px;">Sert. HGB</th><th style="width:88px;">AJB</th><th style="width:88px;">SHM</th><th style="width:88px;">PH</th><th style="width:88px;">PPJB</th>'
            + '<th style="width:88px;">IMB</th><th style="width:88px;">Sert. HGB</th><th style="width:88px;">AJB</th><th style="width:88px;">SHM</th><th style="width:88px;">PH</th><th style="width:88px;">PPJB</th>'
            + '</tr></thead><tbody>';

        rows.forEach(function (item, index) {
            html += '<tr>';
            html += '<td class="dps-center">' + (index + 1) + '</td>';
            html += '<td class="dps-center">' + cell(item, ['BLOK_NOMOR', 'blok_nomor']) + '</td>';
            html += '<td class="dps-name">' + cell(item, ['NAMA', 'nama']) + '</td>';

            allDateKeys.forEach(function (key) {
                html += '<td class="dps-center">' + escapeHtml(formatDate(pick(item, [key, key.toLowerCase()]))) + '</td>';
            });

            html += '</tr>';
        });

        html += '</tbody><tfoot><tr>';
        html += '<td colspan="3" class="dps-total-label">TOTAL DATA: ' + rows.length + '</td>';
        allDateKeys.forEach(function (key) {
            html += '<td class="dps-total-count">' + countFilled(rows, key) + '</td>';
        });
        html += '</tr></tfoot></table></div>';

        return html;
    }

    /* REPORT KHUSUS LEGAL / PT DENGAN ANAKAN */
    function buildSpecialReport(rows, filters) {
        var html = buildSpecialHeaderHtml(rows, filters);

        html += '<div class="dps-table-wrap"><table class="dps-table dps-pt-table">';
        html += '<thead><tr>'
            + '<th rowspan="2" style="width:48px;">No.</th>'
            + '<th rowspan="2" style="width:90px;">Blok/<br>Nomor</th>'
            + '<th rowspan="2" style="width:190px;">Nama Pemilik/PT</th>'
            + '<th rowspan="2" style="width:90px;">Tanggal<br>Ambil</th>'
            + '<th rowspan="2" style="width:155px;">Diambil Oleh</th>'
            + '<th colspan="2" style="width:240px;">Sertipikat Pemisahan</th>'
            + '<th colspan="3" style="width:320px;">Surat Ukur Pemisahan</th>'
            + '<th rowspan="2" style="width:90px;">Masa Berlaku</th>'
            + '</tr><tr>'
            + '<th style="width:150px;">Nomor</th><th style="width:90px;">Tanggal</th>'
            + '<th style="width:150px;">Nomor</th><th style="width:90px;">Tanggal</th><th style="width:80px;">Luas (m²)</th>'
            + '</tr></thead><tbody>';

        rows.forEach(function (item, index) {
            html += '<tr>';
            html += '<td class="dps-center">' + (index + 1) + '</td>';
            html += '<td class="dps-center">' + cell(item, ['BLOK_NOMOR', 'blok_nomor']) + '</td>';
            html += '<td class="dps-name">' + cell(item, ['NAMA_PEMILIK', 'nama_pemilik', 'NASABAH_NAMA', 'nasabah_nama']) + '</td>';
            html += '<td class="dps-center">' + dateCell(item, ['TGL_AMBIL_LEGAL', 'tgl_ambil_legal']) + '</td>';
            html += '<td>' + cell(item, ['USER_AMBIL_LEGAL', 'user_ambil_legal']) + '</td>';
            html += '<td>' + cell(item, ['NO_SERTIPIKAT_PEMISAHAN', 'no_sertipikat_pemisahan', 'NO_SERTIPIKAT', 'no_sertipikat']) + '</td>';
            html += '<td class="dps-center">' + dateCell(item, ['TGL_SERTIPIKAT_PEMISAHAN', 'tgl_sertipikat_pemisahan', 'TGL_SERTIPIKAT', 'tgl_sertipikat']) + '</td>';
            html += '<td>' + cell(item, ['NO_SU_PEMISAHAN', 'no_su_pemisahan', 'SU_PISAH', 'su_pisah']) + '</td>';
            html += '<td class="dps-center">' + dateCell(item, ['TGL_SU_PEMISAHAN', 'tgl_su_pemisahan', 'TGL_SU_PISAH', 'tgl_su_pisah']) + '</td>';
            html += '<td class="dps-right">' + numberCell(item, ['LUAS_SU_PEMISAHAN', 'luas_su_pemisahan', 'LUAS_SUP', 'luas_sup']) + '</td>';
            html += '<td class="dps-center">' + dateCell(item, ['TGL_BERLAKU', 'tgl_berlaku']) + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';

        return html;
    }

    function pick(item, keys) {
        for (var i = 0; i < keys.length; i += 1) {
            if (Object.prototype.hasOwnProperty.call(item, keys[i])
                && item[keys[i]] !== null
                && item[keys[i]] !== undefined) {
                return item[keys[i]];
            }
        }
        return '';
    }

    function cell(item, keys) {
        var value = pick(item, keys);
        return escapeHtml(value === '' ? '-' : value);
    }

    function dateCell(item, keys) {
        return escapeHtml(formatDate(pick(item, keys)));
    }

    function numberCell(item, keys) {
        var value = pick(item, keys);
        if (value === '' || value === null) return '-';

        var number = Number(value);
        return Number.isFinite(number)
            ? escapeHtml(number.toLocaleString('id-ID', { maximumFractionDigits: 2 }))
            : escapeHtml(value);
    }

    function countFilled(rows, key) {
        return rows.reduce(function (total, item) {
            return total + (pick(item, [key, key.toLowerCase()]) ? 1 : 0);
        }, 0);
    }

    function formatDate(value) {
        if (!value) return '-';

        var text = String(value).trim();
        var match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!match) match = text.match(/^(\d{4})(\d{2})(\d{2})$/);

        if (match) {
            return match[3] + '-' + match[2] + '-' + match[1];
        }
        return text;
    }

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /* PRINT NATIVE DENGAN TOMBOL ORIENTASI & KERTAS DI DIALOG BROWSER */
    /*
     * Penyesuaian tabel pada dokumen cetak.
     *
     * 1. Lebar kolom dihitung dari colgroup laporan supaya proporsinya sama
     *    dengan tampilan layar. Tanpa ini setiap kolom mendapat lebar yang
     *    sama, sehingga kolom nama terpotong menjadi dua baris sementara
     *    kolom nomor menyisakan ruang kosong.
     * 2. Kolom yang seluruh isinya berupa tanggal atau angka diberi
     *    white-space nowrap, supaya nilai seperti 1,572,346,080 tidak pecah
     *    menjadi dua baris.
     *
     * Dijalankan pada dokumen frame cetak sehingga tidak bergantung pada
     * nama kelas maupun struktur pembungkus laporan tiap fitur.
     */
    function applyPrintTableRules(doc) {
        if (!doc || !doc.querySelectorAll) {
            return;
        }

        var polaAngka = /^[0-9][0-9.,\/-]*$/;
        var tabel = doc.querySelectorAll('table');
        var aturan = '';

        for (var i = 0; i < tabel.length; i++) {
            var penanda = 'print-table-' + i;

            tabel[i].setAttribute('data-print-table', penanda);
            aturan += printTableColumnCss(tabel[i], penanda);
            aturan += printTableNowrapCss(tabel[i], penanda, polaAngka);
        }

        if (aturan === '') {
            return;
        }

        var gaya = doc.createElement('style');

        gaya.setAttribute('data-print-table-rules', 'true');
        gaya.appendChild(doc.createTextNode(aturan));
        (doc.head || doc.documentElement).appendChild(gaya);
    }

    function printTableColumnCss(tabel, penanda) {
        var kolom = tabel.querySelectorAll('colgroup > col');
        var lebar = [];
        var total = 0;

        for (var i = 0; i < kolom.length; i++) {
            var nilai = parseFloat(kolom[i].style.width) || 0;

            lebar.push(nilai);
            total += nilai;
        }

        if (total <= 0) {
            return '';
        }

        var css = '';

        for (var k = 0; k < lebar.length; k++) {
            css += '[data-print-table="' + penanda + '"] col:nth-child(' + (k + 1) + ')'
                + '{width:' + ((lebar[k] / total) * 100).toFixed(3) + '% !important}';
        }

        return css;
    }

    /*
     * Baris yang memuat sel bergabung dilewati karena urutan selnya tidak
     * lagi sejajar dengan urutan kolom.
     */
    function printTableNowrapCss(tabel, penanda, polaAngka) {
        var baris = tabel.querySelectorAll('tbody > tr');
        var jumlahIsi = [];
        var jumlahCocok = [];

        for (var r = 0; r < baris.length; r++) {
            var sel = baris[r].children;
            var bergabung = false;

            for (var c = 0; c < sel.length; c++) {
                if ((sel[c].colSpan || 1) > 1 || (sel[c].rowSpan || 1) > 1) {
                    bergabung = true;
                    break;
                }
            }

            if (bergabung) {
                continue;
            }

            for (var k = 0; k < sel.length; k++) {
                var teks = String(sel[k].textContent || '').trim();

                if (teks === '' || teks === '-') {
                    continue;
                }

                jumlahIsi[k] = (jumlahIsi[k] || 0) + 1;

                if (polaAngka.test(teks)) {
                    jumlahCocok[k] = (jumlahCocok[k] || 0) + 1;
                }
            }
        }

        var css = '';

        for (var i = 0; i < jumlahIsi.length; i++) {
            if (jumlahIsi[i] > 0 && jumlahCocok[i] === jumlahIsi[i]) {
                css += '[data-print-table="' + penanda + '"] tbody > tr > td:nth-child('
                    + (i + 1) + '){white-space:nowrap}';
            }
        }

        return css;
    }

    function printDpsInNativeDialog() {
        var report = document.getElementById('dpsReport');
        if (!report) return;

        var reportHtml = report.innerHTML;
        if (!reportHtml || reportHtml.indexOf('dps-empty') !== -1 || reportHtml.indexOf('dps-loading') !== -1) {
            return;
        }

        var oldFrame = document.getElementById('dpsNativePrintFrame');
        if (oldFrame) oldFrame.remove();

        var frame = document.createElement('iframe');
        frame.id = 'dpsNativePrintFrame';
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
            @page {
                margin: 8mm;
            }
            html, body {
                width: 100%;
                margin: 0;
                padding: 0;
                background: #ffffff;
                color: #000000;
                font-family: "Segoe UI", Arial, sans-serif;
            }
            *, *::before, *::after {
                box-sizing: border-box;
            }
            .dps-paper {
                width: 100%;
                margin: 0;
                padding: 0;
                background: #ffffff;
                color: #000000;
            }
            .dps-report-head {
                display: grid;
                width: 100%;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr) minmax(0, 1fr);
                gap: 12px;
                align-items: center;
                margin: 0 0 8px;
                padding: 10px 12px;
                border: 1px solid #444;
                background: #ffffff;
                color: #000000;
            }
            .dps-company {
                color: #000000;
                font-size: 11pt;
                font-weight: 700;
                line-height: 1.35;
            }
            .dps-report-title {
                text-align: center;
            }
            .dps-report-title h2 {
                margin: 0 0 4px;
                color: #000000;
                font-family: Cambria, Georgia, serif;
                font-size: 15pt;
                font-weight: 700;
                line-height: 1.2;
            }
            .dps-report-title div {
                color: #000000;
                font-size: 9.5pt;
            }
            .dps-print-meta {
                color: #000000;
                text-align: right;
                font-size: 9.5pt;
                font-weight: 600;
                line-height: 1.45;
            }
            .dps-report-subtitle {
                display: grid;
                grid-template-columns: auto 1fr auto;
                align-items: center;
                gap: 12px;
                width: 100%;
                min-height: 32px;
                margin: 0 0 10px;
                padding: 6px 10px;
                border: 1px solid #666;
                background: #ffffff;
                color: #000000;
                font-size: 9.5pt;
            }
            .dps-report-subtitle-label {
                font-weight: 700;
                color: #000000;
            }
            .dps-report-subtitle-value {
                text-align: center;
                font-weight: 800;
                color: #000000;
            }
            .dps-live-badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 3px 8px;
                border: 1px solid #666;
                border-radius: 999px;
                color: #000000;
                font-size: 8.5pt;
                font-weight: 700;
                text-transform: uppercase;
            }
            .dps-live-badge::before {
                content: "";
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: #000000;
            }
            .dps-table-wrap {
                width: 100%;
                overflow: visible;
                border: 0;
            }
            .dps-table, .dps-table.dps-pt-table {
                width: 100%;
                min-width: 0;
                max-width: 100%;
                table-layout: auto;
                border-collapse: collapse;
                border-spacing: 0;
                border: 1px solid #000000;
                color: #000000;
                font-size: 9pt;
                line-height: 1.3;
            }
            .dps-table thead {
                display: table-header-group;
            }
            .dps-table tbody {
                display: table-row-group;
            }
            .dps-table tfoot {
                display: table-footer-group;
            }
            .dps-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .dps-table th, .dps-table td {
                position: static;
                padding: 5px 6px;
                border: 1px solid #000000;
                background: #ffffff !important;
                color: #000000 !important;
                vertical-align: middle;
                overflow-wrap: break-word;
            }
            .dps-table th {
                text-align: center;
                font-size: 9.5pt;
                font-weight: 700;
                line-height: 1.2;
            }
            .dps-table tfoot td {
                border-top: 2px solid #000000;
                font-weight: 700;
                font-size: 9pt;
            }
            .dps-center { text-align: center; }
            .dps-right { text-align: right; font-variant-numeric: tabular-nums; }
            .dps-name { color: #000000; font-weight: 700; }
            .dps-total-label { text-align: right; font-weight: 700; }
            .dps-total-count { text-align: center; font-weight: 700; }
        `;

        frameDocument.open();
        frameDocument.write(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Daftar Pengambilan Surat-Surat</title>' +
            '<style>' + printCss + '</style></head><body><div class="dps-paper">' + reportHtml + '</div></body></html>'
        );
        frameDocument.close();
        applyPrintTableRules(frameDocument);

        var doPrint = function () {
            try {
                frameWindow.focus();
                frameWindow.print();
            } finally {
                window.setTimeout(function () {
                    var printFrame = document.getElementById('dpsNativePrintFrame');
                    if (printFrame) printFrame.remove();
                }, 1200);
            }
        };

        window.setTimeout(doPrint, 200);
    }
}());
</script>
@endsection