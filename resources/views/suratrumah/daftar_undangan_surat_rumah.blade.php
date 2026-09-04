@extends('layouts.template')

@section('content')
<style>
.undangan-page,
.undangan-page * {
    box-sizing: border-box;
}

.undangan-page {
    position: relative;
    width: 100%;
    min-width: 720px;
    min-height: 100%;
    padding: 18px 12px 32px;
    overflow: visible;
    color: #172033;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    background:
        radial-gradient(circle at 95% 2%, rgba(37, 99, 235, 0.07), transparent 28%),
        radial-gradient(circle at 8% 96%, rgba(56, 189, 248, 0.05), transparent 26%),
        #f3f6fb;
}

.undangan-toolbar {
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
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
}

.undangan-toolbar::before {
    content: "◈";
    position: absolute;
    left: 20px;
    display: grid;
    width: 34px;
    height: 34px;
    place-items: center;
    border-radius: 11px;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    font-size: 18px;
    box-shadow: 0 10px 20px rgba(37, 99, 235, 0.24);
}

.undangan-toolbar::after {
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

.undangan-toolbar-title {
    position: relative;
    z-index: 1;
    margin: 0;
    color: #172033;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 13px;
    font-weight: 900;
    letter-spacing: 0.11em;
    text-transform: uppercase;
}

.undangan-unit-badge {
    position: relative;
    z-index: 1;
    padding: 7px 12px;
    border: 1px solid #bfdbfe;
    border-radius: 999px;
    background: #eff6ff;
    color: #1e40af;
    font-family: "SFMono-Regular", Consolas, monospace;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.06em;
}

.undangan-filter {
    position: relative;
    padding: 20px;
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
}

.undangan-filter::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    background: linear-gradient(180deg, #38bdf8 0%, #2563eb 52%, #1d4ed8 100%);
}

.undangan-filter::after {
    content: "Daftar Surat Undangan";
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

.undangan-filter-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 82px;
    gap: 13px 14px;
    align-items: center;
}

.undangan-field {
    display: grid;
    grid-template-columns: 92px minmax(0, 1fr);
    gap: 9px;
    align-items: center;
    min-width: 0;
}

.undangan-label {
    overflow: hidden;
    color: #475467;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.10em;
    text-overflow: ellipsis;
    text-transform: uppercase;
    white-space: nowrap;
}

.undangan-range {
    display: grid;
    grid-template-columns: minmax(90px, 1fr) 34px minmax(90px, 1fr);
    gap: 7px;
    align-items: center;
}

.undangan-block-range {
    grid-template-columns: minmax(110px, 1fr) 34px minmax(110px, 1fr);
}

.undangan-lookup-field,
.undangan-lookup {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px;
    gap: 8px;
    min-width: 0;
}

.undangan-separator {
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
}

.undangan-input,
.undangan-select,
.undangan-lookup-display {
    width: 100%;
    min-width: 0;
    height: 42px;
    padding: 8px 12px;
    border: 1px solid #c8d3e1;
    border-radius: 12px;
    background: #ffffff;
    color: #101828;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 12px;
    font-weight: 650;
    outline: 0;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}

.undangan-input:focus,
.undangan-select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
}

.undangan-lookup-display {
    display: flex;
    align-items: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.undangan-lookup-button {
    display: inline-flex;
    width: 42px;
    height: 42px;
    align-items: center;
    justify-content: center;
    border: 1px solid #bfdbfe;
    border-radius: 13px;
    background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%);
    color: #1d4ed8;
    cursor: pointer;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}

.undangan-lookup-button:hover {
    transform: translateY(-1px);
    background: linear-gradient(145deg, #dbeafe 0%, #bfdbfe 100%);
    box-shadow: 0 7px 15px rgba(37, 99, 235, 0.12);
}

.undangan-action-stack {
    position: relative;
    width: 82px;
    height: 42px;
    grid-column: 3;
    grid-row: 1;
    align-self: start;
    justify-self: end;
}

.undangan-action {
    display: inline-flex;
    width: 82px;
    min-width: 82px;
    height: 42px;
    align-items: center;
    justify-content: center;
    border: 0;
    cursor: pointer;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}

.undangan-action-stack .undangan-action:first-child {
    position: absolute;
    top: 0;
    right: -20px;
    border-radius: 13px 0 0 13px;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
}

.undangan-action-stack .undangan-action:first-child:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.30);
}

#undanganPrintButton {
    position: absolute;
    top: 55px;
    right: -20px;
    border-radius: 13px 0 0 13px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
    box-shadow: 0 8px 18px rgba(5, 150, 105, 0.22);
}

#undanganPrintButton:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(5, 150, 105, 0.28);
}

#undanganPrintButton:disabled {
    border: 1px solid #d5dde7;
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
    color: #94a3b8;
    box-shadow: none;
    cursor: not-allowed;
}

.undangan-check-row {
    display: flex;
    min-height: 42px;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.undangan-check-field {
    grid-column: 1;
    grid-row: 3;
    align-self: center;
}

.undangan-check-field .undangan-check-row {
    justify-content: flex-start;
}

.undangan-checkbox {
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
}

.undangan-checkbox input {
    width: 15px;
    height: 15px;
    margin: 0;
    accent-color: #2563eb;
}

.undangan-checkbox:has(input:checked) {
    border-color: #a7f3d0;
    background: linear-gradient(135deg, #ecfdf3, #f0fdf4);
    color: #047857;
}

.undangan-report-area {
    position: relative;
    margin-top: 18px;
    padding: 20px;
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
}

.undangan-report-area::before {
    content: "";
    position: absolute;
    top: 0;
    left: 7%;
    right: 7%;
    height: 3px;
    border-radius: 0 0 999px 999px;
    background: linear-gradient(90deg, transparent, #38bdf8, #2563eb, #6366f1, transparent);
}

#loadingInfo {
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

.undangan-paper {
    width: 100%;
    min-height: 0;
    margin: 0;
    padding: 0;
    border: 0;
    background: transparent;
    color: #172033;
}

.undangan-initial {
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
}

.undangan-initial-icon {
    display: inline-flex;
    width: 52px;
    height: 52px;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    background: #dbeafe;
    color: #2563eb;
    font-size: 20px;
}

.undangan-report-header {
    display: grid;
    grid-template-columns: minmax(150px, 1fr) minmax(260px, 1.4fr) minmax(190px, 1fr);
    gap: 16px;
    align-items: center;
    margin-bottom: 10px;
    padding: 15px 18px;
    border: 1px solid #dbeafe;
    border-radius: 18px;
    background: linear-gradient(105deg, #eff6ff 0%, #dbeafe 48%, #eef2ff 100%);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.10);
}

.undangan-company {
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 850;
    letter-spacing: .04em;
    line-height: 1.35;
}

.undangan-title-wrap {
    min-width: 0;
    text-align: center;
}

.undangan-report-title {
    display: block;
    margin: 0;
    color: #172033;
    font-family: Cambria, Georgia, "Times New Roman", serif;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -.02em;
    line-height: 1.2;
}

.undangan-report-center-meta {
    margin-top: 5px;
    color: #475467;
    font-size: 10.5px;
    font-weight: 650;
    line-height: 1.45;
}

.undangan-report-date {
    color: #475467;
    text-align: right;
    font-size: 10.5px;
    font-weight: 650;
    line-height: 1.55;
}

.undangan-report-subtitle {
    display: grid;
    grid-template-columns: minmax(130px, 1fr) minmax(220px, 2fr) minmax(110px, 1fr);
    min-height: 36px;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: linear-gradient(90deg, #ffffff, #f8fafc);
    color: #667085;
    font-size: 10.5px;
}

.undangan-sector-label {
    text-align: left;
}

.undangan-sector-value {
    min-width: 0;
    color: #344054;
    text-align: center;
    font-weight: 850;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.undangan-live-badge {
    display: inline-flex;
    justify-self: end;
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
}

.undangan-live-badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
}

.undangan-table-wrap {
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

.undangan-table-wrap::-webkit-scrollbar,
.lookup-scroll::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.undangan-table-wrap::-webkit-scrollbar-track,
.lookup-scroll::-webkit-scrollbar-track {
    background: #eff3f7;
}

.undangan-table-wrap::-webkit-scrollbar-thumb,
.lookup-scroll::-webkit-scrollbar-thumb {
    border: 2px solid #eff3f7;
    border-radius: 999px;
    background: linear-gradient(180deg, #60a5fa, #2563eb);
}

.report-table {
    width: 1250px;
    min-width: 1250px;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
    color: #344054;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10.5px;
}

.report-table th,
.report-table td {
    padding: 8px 9px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
    overflow-wrap: anywhere;
}

.report-table th {
    position: sticky;
    top: 0;
    z-index: 4;
    height: 48px;
    background: linear-gradient(180deg, #eff6ff 0%, #e5effb 100%);
    color: #344054;
    text-align: center;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-weight: 900;
    line-height: 1.25;
}

.report-table thead tr.report-head-row-main th {
    top: 0;
    z-index: 7;
}

.report-table thead tr.report-head-row-sub th {
    top: 48px;
    z-index: 6;
}

.report-table thead tr.report-head-row-main th[rowspan="2"] {
    z-index: 8;
}

.report-table td {
    height: 46px;
    background: #ffffff;
    color: #344054;
    line-height: 1.38;
}

.report-table tbody tr:nth-child(even):not(.sector-row) td {
    background: #fbfcfe;
}

.report-table tbody tr.data-row:hover td {
    background: #f0f7ff;
}

.sector-row td {
    height: auto;
    padding: 8px 10px;
    background: linear-gradient(90deg, #ffffff 0%, #f8fafc 55%, #eff6ff 100%) !important;
    color: #344054;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10px;
    font-weight: 850;
    text-align: left;
}

.center {
    text-align: center;
}

.empty-state {
    height: 130px;
    color: #64748b;
    text-align: center;
}

#undanganModal {
    z-index: 1065;
}

#undanganModal .modal-content {
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 20px;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
}

#undanganModal .modal-header {
    border-bottom: 1px solid #dbe3ef;
    background: linear-gradient(90deg, #ffffff 0%, #f8fbff 100%);
}

#undanganModal .modal-body {
    background: #f8fafc;
}

.lookup-search {
    width: 100%;
    height: 42px;
    margin-bottom: 10px;
    padding: 8px 12px;
    border: 1px solid #c8d3e1;
    border-radius: 12px;
    background: #fff;
    outline: 0;
}

.lookup-search:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.lookup-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 11px;
}

.lookup-table th,
.lookup-table td {
    padding: 7px 8px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}

.lookup-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #eaf2ff;
    color: #344054;
    font-weight: 850;
}

.lookup-table tbody tr {
    cursor: pointer;
}

.lookup-table tbody tr:hover td {
    background: #eff6ff;
}

/* =========================================================
   ALERT DATA KOSONG — SAMA DENGAN DAFTAR SERTIFIKAT PECAHAN
   Modal informasi yang tampil saat hasil laporan tidak
   menghasilkan baris data sama sekali.
   ========================================================= */
    #undanganNoDataAlertModal .modal-dialog {
        max-width: 380px;
    }

    #undanganNoDataAlertModal .modal-content {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
    }

    #undanganNoDataAlertModal .alert-icon-wrapper {
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

    #undanganNoDataAlertModal .alert-title {
        margin-bottom: 8px;
        color: #172033;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 18px;
        font-weight: 700;
    }

    #undanganNoDataAlertModal .alert-message {
        margin-bottom: 24px;
        color: #475569;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 14px;
    }

    #undanganNoDataAlertModal .alert-btn-ok {
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

    #undanganNoDataAlertModal .alert-btn-ok:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
    }


@media (max-width: 1100px) {
    .undangan-filter-grid {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 82px;
    }
    .undangan-field {
        grid-template-columns: 78px minmax(0, 1fr);
    }
}

@media print {
    .undangan-toolbar, .undangan-filter, #loadingInfo, #undanganModal, #undanganNoDataAlertModal, .main-sidebar, .control-sidebar, .main-header, .main-footer, .navbar, .sidebar {
        display: none !important;
    }
    html, body, .wrapper, .content-wrapper, .main-content, .content, .page-wrapper, .page-content, .container, .container-fluid, .undangan-page, .undangan-report-area, .undangan-paper {
        width: 100% !important; min-width: 0 !important; max-width: none !important; margin: 0 !important; padding: 0 !important; overflow: visible !important; background: #fff !important; box-shadow: none !important;
    }
    .undangan-table-wrap { max-height: none !important; overflow: visible !important; }
    .report-table { width: 100% !important; min-width: 0 !important; max-width: 100% !important; }
}
</style>

<div class="undangan-page">
    <div class="undangan-toolbar">
        <div class="undangan-toolbar-title">
            Daftar Undangan Surat Rumah
        </div>
        <code class="undangan-unit-badge">
            UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}
        </code>
    </div>

    <section class="undangan-filter">
        <input
            type="hidden"
            id="perusahaan"
            value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}"
        >
        <input
            type="hidden"
            id="nama_perusahaan"
            value="{{ session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? '' }}"
        >
        <input type="hidden" id="cluster" value="*">

        <div class="undangan-filter-grid">
            <div class="undangan-field">
                <label class="undangan-label" for="blok_awal">
                    Blok/Nomor
                </label>
                <div class="undangan-range undangan-block-range">
                    <div class="undangan-lookup-field">
                        <input
                            type="text"
                            id="blok_awal"
                            class="undangan-input"
                            value="*"
                            maxlength="30"
                        >
                        <button
                            type="button"
                            class="undangan-lookup-button"
                            onclick="getBlokModal('awal')"
                            title="Pilih Blok/Nomor Awal"
                        >
                            <i class="fas fa-binoculars"></i>
                        </button>
                    </div>

                    <span class="undangan-separator">s.d</span>

                    <div class="undangan-lookup-field">
                        <input
                            type="text"
                            id="blok_akhir"
                            class="undangan-input"
                            value="*"
                            maxlength="30"
                        >
                        <button
                            type="button"
                            class="undangan-lookup-button"
                            onclick="getBlokModal('akhir')"
                            title="Pilih Blok/Nomor Akhir"
                        >
                            <i class="fas fa-binoculars"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="undangan-field">
                <span class="undangan-label">Sektor</span>
                <div class="undangan-lookup">
                    <div
                        id="clusterEntry"
                        class="undangan-lookup-display"
                    >Semua Sektor</div>
                    <button
                        type="button"
                        class="undangan-lookup-button"
                        onclick="getClusterModal()"
                        title="Cari Cluster"
                    >
                        <i class="fas fa-binoculars"></i>
                    </button>
                </div>
            </div>

            <div class="undangan-action-stack">
                <button
                    type="button"
                    class="undangan-action"
                    onclick="getData()"
                >
                    OK
                </button>
                <button
                    type="button"
                    class="undangan-action"
                    id="undanganPrintButton"
                    onclick="printUndanganReport()"
                    aria-disabled="true"
                    disabled
                >
                    Print
                </button>
            </div>

            <div class="undangan-field">
                <label class="undangan-label" for="tgl_awal">
                    Periode
                </label>
                <div class="undangan-range">
                    <input type="date" id="tgl_awal" class="undangan-input">
                    <span class="undangan-separator">s.d</span>
                    <input type="date" id="tgl_akhir" class="undangan-input">
                </div>
            </div>

            <div class="undangan-field">
                <label class="undangan-label" for="jenis_report">
                    Jenis Report
                </label>
                <select id="jenis_report" class="undangan-select">
                    <option value="1">Undangan PPJB (PPSRS)</option>
                    <option value="2">Undangan PPJB (Notaris)</option>
                    <option value="3">Undangan Serah Terima</option>
                    <option value="4">Undangan AJB</option>
                    <option value="5">Undangan SKB</option>
                    <option value="6">Perpanjangan Sertipikat</option>
                </select>
            </div>

            <div class="undangan-field undangan-check-field">
                <span class="undangan-label" aria-hidden="true"></span>
                <div class="undangan-check-row">
                    <label class="undangan-checkbox">
                        <input type="checkbox" id="belum_diundang">
                        <span>Belum Diundang</span>
                    </label>
                </div>
            </div>
        </div>
    </section>

    <section class="undangan-report-area">
        <div id="loadingInfo">
            <i class="fas fa-spinner fa-spin"></i>
            Mengambil data undangan...
        </div>

        <div id="mainDisplay">
            <div class="undangan-paper">
                <div class="undangan-initial">
                    <i
                        class="fas fa-envelope-open-text undangan-initial-icon"
                        aria-hidden="true"
                    ></i>
                    <div>
                        Silahkan isi filter kemudian klik OK
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Pencarian/Lookup -->
<div class="modal fade" id="undanganModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="padding:10px 12px;">
                <h5
                    class="modal-title"
                    id="undanganModalTitle"
                    style="font-size:14px;margin:0;"
                >
                    Lookup
                </h5>
                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="Tutup"
                    onclick="closeUndanganModal()"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div
                class="modal-body"
                id="undanganModalContent"
            ></div>
        </div>
    </div>
</div>

<!-- MODAL ALERT DATA KOSONG -->
<div class="modal fade" id="undanganNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4" style="text-align: center; padding: 1.5rem;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message" id="undanganNoDataMessage">Data tidak ditemukan.</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    data-dismiss="modal"
                    onclick="hideUndanganNoDataAlert()"
                >OK</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
    var lastUndanganRows = null;
    var lastLoadedUndanganFilter = null;
    var activeUndanganDataRequest = null;
    var undanganRequestSequence = 0;

    $(document).ready(function () {
        setDefaultDate();
        normalizeUndanganClusterState(false);
        setUndanganPrintEnabled(false);

        $('#blok_awal, #blok_akhir').on('input', function () {
            $(this).val(
                String($(this).val() || '').toUpperCase()
            );
        });

        $('#belum_diundang, #jenis_report, #tgl_awal, #tgl_akhir').on(
            'change',
            function () {
                syncUndanganPrintState();
            }
        );

        $('#blok_awal, #blok_akhir').on(
            'input change',
            function () {
                syncUndanganPrintState();
            }
        );
    });

    $(window).on('pageshow', function (event) {
        window.setTimeout(function () {
            if (isUndanganReloadOrHistoryRestore(event)) {
                $('#cluster').val('*');
                $('#clusterEntry').text('Semua Sektor');

                lastUndanganRows = null;
                lastLoadedUndanganFilter = null;
                setUndanganPrintEnabled(false);
                hideUndanganNoDataAlert();
            } else {
                normalizeUndanganClusterState(false);
                syncUndanganPrintState();
            }
        }, 0);
    });

    function isUndanganReloadOrHistoryRestore(event) {
        var pageEvent = event && (event.originalEvent || event);
        if (pageEvent && pageEvent.persisted) {
            return true;
        }
        if (window.performance && performance.getEntriesByType) {
            var entries = performance.getEntriesByType('navigation');
            if (entries && entries.length) {
                return entries[0].type === 'reload'
                    || entries[0].type === 'back_forward';
            }
        }
        return false;
    }

    function normalizeUndanganClusterState(forceAll) {
        var display = String(
            $('#clusterEntry').text() || ''
        ).trim();

        var isAllDisplay = !display
            || /^semua\s+(sektor|cluster)$/i.test(display);

        if (forceAll === true || isAllDisplay) {
            $('#cluster').val('*');
            $('#clusterEntry').text('Semua Sektor');
        }
    }

    function setDefaultDate() {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');

        $('#tgl_awal').val(year + '-' + month + '-01');
        $('#tgl_akhir').val(year + '-' + month + '-' + day);
    }

    function escapeHtml(value) {
        return String(
            value === null || value === undefined ? '' : value
        )
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
        var match = text.match(
            /^(\d{4})-(\d{2})-(\d{2})/
        );
        if (match) {
            return match[3] + '-' + match[2] + '-' + match[1];
        }
        return text;
    }

    function valueOrDash(value) {
        return value === null
            || value === undefined
            || value === ''
            ? '-'
            : value;
    }

    function getSelectedJenisLabel() {
        return String(
            $('#jenis_report option:selected').text() || ''
        ).trim();
    }

    function setUndanganPrintEnabled(enabled) {
        var disabled = enabled !== true;
        $('#undanganPrintButton')
            .prop('disabled', disabled)
            .attr('aria-disabled', disabled ? 'true' : 'false');
    }

    function getUndanganFilterState() {
        return {
            blok_awal: String($('#blok_awal').val() || '*').toUpperCase(),
            blok_akhir: String($('#blok_akhir').val() || '*').toUpperCase(),
            cluster: String($('#cluster').val() || '*'),
            tgl_awal: String($('#tgl_awal').val() || ''),
            tgl_akhir: String($('#tgl_akhir').val() || ''),
            perusahaan: String($('#perusahaan').val() || ''),
            jenis_report: String($('#jenis_report').val() || ''),
            belum_diundang: $('#belum_diundang').is(':checked') ? 'Y' : 'T'
        };
    }

    function isSameUndanganFilter(left, right) {
        if (!left || !right) {
            return false;
        }
        return JSON.stringify(left) === JSON.stringify(right);
    }

    function syncUndanganPrintState() {
        var sameAsLoaded = isSameUndanganFilter(
            getUndanganFilterState(),
            lastLoadedUndanganFilter
        );
        var reportReady = Array.isArray(lastUndanganRows)
            && $('#mainDisplay .report-table').length > 0;
        setUndanganPrintEnabled(sameAsLoaded && reportReady);
    }

    function resolveSectorDisplay() {
        var clusterCode = String(
            $('#cluster').val() || '*'
        ).trim();
        var clusterText = String(
            $('#clusterEntry').text() || ''
        ).trim();
        if (clusterCode === '*' || clusterCode === '') {
            return 'Semua Sektor';
        }
        return clusterText || clusterCode;
    }

    function getUndanganReportTitle() {
        var jenis = String($('#jenis_report').val() || '1');
        var titles = {
            '1': 'REKAP UNDANGAN PPJB (PPSRS)',
            '2': 'REKAP UNDANGAN PPJB (NOTARIS)',
            '3': 'REKAP UNDANGAN SERAH TERIMA',
            '4': 'REKAP UNDANGAN AKTA JUAL BELI',
            '5': 'REKAP UNDANGAN SKB',
            '6': 'REKAP PEMBERITAHUAN PERPANJANGAN SERTIPIKAT'
        };
        return titles[jenis]
            || ('REKAP ' + getSelectedJenisLabel().toUpperCase());
    }

    function reportUsesBlockMeta() {
        var jenis = String($('#jenis_report').val() || '1');
        return jenis !== '3' && jenis !== '6';
    }

    function buildUndanganReportHeader(title) {
        var company = resolveCompany();
        var meta = getHeaderMeta();
        var today = resolveToday();
        var sector = resolveSectorDisplay();
        var html = '';

        html += '<div class="undangan-report-header">';
        html += '<div class="undangan-company">' + escapeHtml(company) + '</div>';
        html += '<div class="undangan-title-wrap">';
        html += '<h2 class="undangan-report-title">' + escapeHtml(title) + '</h2>';

        if (reportUsesBlockMeta()) {
            html += '<div class="undangan-report-center-meta">';
            html += 'BLOK : ' + escapeHtml(meta.blokAwal) + ' s/d ' + escapeHtml(meta.blokAkhir);
            html += '</div>';
        }

        html += '</div>';
        html += '<div class="undangan-report-date">';
        html += 'Periode Tgl. : ' + escapeHtml(meta.periodeAwal) + ' s/d ' + escapeHtml(meta.periodeAkhir);
        html += '<br>Tanggal : ' + escapeHtml(today);
        html += '</div></div>';

        html += '<div class="undangan-report-subtitle">';
        html += '<span class="undangan-sector-label">Sektor/Cluster:</span>';
        html += '<strong class="undangan-sector-value">' + escapeHtml(sector) + '</strong>';
        html += '<span class="undangan-live-badge">Live data</span>';
        html += '</div>';

        return html;
    }

    function showUndanganModal(title, html) {
        $('#undanganModalTitle').text(title);
        $('#undanganModalContent').html(html);

        var modal = $('#undanganModal');
        if (typeof modal.modal === 'function') {
            modal.modal('show');
            return;
        }
        modal.addClass('show').css('display', 'block').attr('aria-hidden', 'false');
    }

    function closeUndanganModal() {
        var modal = $('#undanganModal');
        if (typeof modal.modal === 'function') {
            modal.modal('hide');
            return;
        }
        modal.removeClass('show').css('display', 'none').attr('aria-hidden', 'true');
    }

    function filterLookup(keyword) {
        var search = String(keyword || '').toLowerCase().trim();
        $('#undanganModal .lookup-table tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(search) !== -1);
        });
    }

    function getBlokModal(target) {
        var perusahaan = String($('#perusahaan').val() || '').trim();

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_blok',
            dataType: 'json',
            headers: { 'Accept': 'application/json' },
            data: { _token: '{{ csrf_token() }}', perusahaan: perusahaan },
            success: function (response) {
                var rows = Array.isArray(response) ? response : (response && Array.isArray(response.data) ? response.data : []);
                var html = '<input type="text" class="lookup-search" placeholder="Cari blok, pemilik, PPJB..." onkeyup="filterLookup(this.value)">';
                html += '<div style="max-height:430px;overflow:auto;"><table class="lookup-table">';
                html += '<thead><tr><th style="width:120px;">Blok/Nomor</th><th>Nama Pembeli</th><th style="width:180px;">No. PPJB</th><th style="width:150px;">Cluster</th></tr></thead><tbody>';
                html += '<tr onclick="addBlok(\'' + escapeJs(target) + '\', \'*\')"><td>*</td><td>Semua Blok</td><td>-</td><td>-</td></tr>';

                $.each(rows, function (index, item) {
                    item = item || {};
                    var blok = item.BLOK_NOMOR || '';
                    var nama = item.NAMA_PEMBELI || '';
                    var noPpjb = item.NO_PPJB || '';
                    var cluster = item.NM_CLUSTER || '';
                    html += '<tr onclick="addBlok(\'' + escapeJs(target) + '\', \'' + escapeJs(blok) + '\')">';
                    html += '<td>' + escapeHtml(blok) + '</td><td>' + escapeHtml(nama) + '</td><td>' + escapeHtml(noPpjb) + '</td><td>' + escapeHtml(cluster) + '</td></tr>';
                });

                html += '</tbody></table></div>';
                showUndanganModal(target === 'awal' ? 'Pilih Blok/Nomor Awal' : 'Pilih Blok/Nomor Akhir', html);
            },
            error: function (xhr) {
                var message = 'Gagal mengambil daftar blok.';
                if (xhr.responseJSON && xhr.responseJSON.message) message += ' ' + xhr.responseJSON.message;
                alert(message);
            }
        });
    }

    function addBlok(target, value) {
        if (target === 'akhir') {
            $('#blok_akhir').val(String(value || '*').toUpperCase());
        } else {
            $('#blok_awal').val(String(value || '*').toUpperCase());
        }
        closeUndanganModal();
        syncUndanganPrintState();
    }

    function getClusterModal() {
        var perusahaan = String($('#perusahaan').val() || '').trim();

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_cluster',
            dataType: 'json',
            headers: { 'Accept': 'application/json' },
            data: { _token: '{{ csrf_token() }}', perusahaan: perusahaan },
            success: function (response) {
                var rows = Array.isArray(response) ? response : (response && Array.isArray(response.data) ? response.data : []);
                var html = '<input type="text" class="lookup-search" placeholder="Cari cluster..." onkeyup="filterLookup(this.value)">';
                html += '<div style="max-height:420px;overflow:auto;"><table class="lookup-table">';
                html += '<thead><tr><th style="width:150px;">Kode</th><th>Deskripsi</th></tr></thead><tbody>';
                html += '<tr onclick="addCluster(\'*\', \'\')"><td>*</td><td>Semua Cluster</td></tr>';

                $.each(rows, function (index, item) {
                    var kode = item.KD_CLUSTER || item.KD_SEKTOR || '';
                    var deskripsi = item.DESKRIPSI || kode;
                    html += '<tr onclick="addCluster(\'' + escapeJs(kode) + '\', \'' + escapeJs(deskripsi) + '\')">';
                    html += '<td>' + escapeHtml(kode) + '</td><td>' + escapeHtml(deskripsi) + '</td></tr>';
                });

                html += '</tbody></table></div>';
                showUndanganModal('Pilih Cluster', html);
            },
            error: function (xhr) {
                var message = 'Gagal mengambil data cluster.';
                if (xhr.responseJSON && xhr.responseJSON.message) message += ' ' + xhr.responseJSON.message;
                alert(message);
            }
        });
    }

    function addCluster(kode, deskripsi) {
        $('#cluster').val(kode || '*');
        $('#clusterEntry').text(deskripsi || '');
        closeUndanganModal();
        syncUndanganPrintState();
    }

    function validateFilter() {
        if (!$('#blok_awal').val() || !$('#blok_akhir').val()) {
            alert('Blok/Nomor awal dan akhir wajib diisi. Gunakan * untuk semua blok.');
            return false;
        }
        if (!$('#tgl_awal').val() || !$('#tgl_akhir').val()) {
            alert('Periode tanggal wajib diisi.');
            return false;
        }
        if ($('#tgl_awal').val() > $('#tgl_akhir').val()) {
            alert('Tanggal awal tidak boleh melebihi tanggal akhir.');
            return false;
        }
        return true;
    }

    function getFilterData() {
        return {
            _token: '{{ csrf_token() }}',
            blok_awal: $('#blok_awal').val() || '*',
            blok_akhir: $('#blok_akhir').val() || '*',
            cluster: $('#cluster').val() || '*',
            tgl_awal: $('#tgl_awal').val(),
            tgl_akhir: $('#tgl_akhir').val(),
            perusahaan: $('#perusahaan').val(),
            jenis_report: $('#jenis_report').val(),
            belum_diundang: $('#belum_diundang').is(':checked') ? 'Y' : 'T'
        };
    }

    /*
     * Alert data kosong, mengikuti Daftar Sertifikat Pecahan.
     * Hanya menampilkan pesan; pengambilan data dan render laporan
     * tetap memakai alur yang sudah ada. Jika plugin modal tidak
     * tersedia pada halaman ini, modal ditampilkan secara manual
     * sehingga tampilannya tetap sama.
     */
    function showUndanganNoDataAlert(message) {
        var $modal = $('#undanganNoDataAlertModal');

        $('#undanganNoDataMessage').text(message || 'Data tidak ditemukan......!');

        if (typeof $modal.modal === 'function') {
            $modal.modal('show');
            return;
        }

        if (!$('.undangan-nodata-backdrop').length) {
            $('<div class="modal-backdrop fade show undangan-nodata-backdrop"></div>')
                .appendTo(document.body);
        }

        $modal
            .addClass('show')
            .css('display', 'block')
            .attr('aria-hidden', 'false');
    }

    function hideUndanganNoDataAlert() {
        var $modal = $('#undanganNoDataAlertModal');

        if (typeof $modal.modal === 'function') {
            $modal.modal('hide');
            return;
        }

        $modal
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true');

        $('.undangan-nodata-backdrop').remove();
    }

    function getData() {
        normalizeUndanganClusterState(false);
        if (!validateFilter()) {
            return;
        }

        var requestFilterState = getUndanganFilterState();
        var requestData = getFilterData();
        var requestId = ++undanganRequestSequence;

        if (activeUndanganDataRequest && activeUndanganDataRequest.readyState !== 4) {
            activeUndanganDataRequest.abort();
        }

        lastUndanganRows = null;
        lastLoadedUndanganFilter = null;
        setUndanganPrintEnabled(false);
        hideUndanganNoDataAlert();
        $('#loadingInfo').show();

        $('#mainDisplay').html(
            '<div class="undangan-paper"><div class="undangan-initial">'
            + '<i class="fas fa-spinner fa-spin undangan-initial-icon"></i>'
            + '<div>Memuat hasil berdasarkan filter terbaru...</div>'
            + '</div></div>'
        );

        activeUndanganDataRequest = $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_data',
            dataType: 'json',
            timeout: 120000,
            cache: false,
            headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' },
            data: requestData,
            success: function (response) {
                if (requestId !== undanganRequestSequence) {
                    return;
                }

                if (!isSameUndanganFilter(getUndanganFilterState(), requestFilterState)) {
                    lastUndanganRows = null;
                    lastLoadedUndanganFilter = null;
                    setUndanganPrintEnabled(false);
                    $('#mainDisplay').html(
                        '<div class="undangan-paper"><div class="undangan-initial">'
                        + '<i class="fas fa-filter undangan-initial-icon"></i>'
                        + '<div>Filter berubah. Klik OK untuk memuat data terbaru.</div>'
                        + '</div></div>'
                    );
                    return;
                }

                var rows = Array.isArray(response) ? response : (response && Array.isArray(response.data) ? response.data : []);
                lastUndanganRows = rows;
                lastLoadedUndanganFilter = $.extend({}, requestFilterState);

                renderReport(rows);
                syncUndanganPrintState();

                if (rows.length === 0) {
                    var jenisVal = $('#jenis_report').val();
                    var reportName = "";
                    switch (jenisVal) {
                        case '1': reportName = "Undangan PPJB (PPSRS)"; break;
                        case '2': reportName = "Undangan PPJB (Notaris)"; break;
                        case '3': reportName = "Undangan Serah Terima"; break;
                        case '4': reportName = "Undangan AJB"; break;
                        case '5': reportName = "Undangan Pengurusan SKB"; break;
                        case '6': reportName = "Undangan Perpanjangan Sertipikat"; break;
                        default: reportName = getSelectedJenisLabel(); break;
                    }

                    var isBelumDiundang = $('#belum_diundang').is(':checked');
                    var alertMsg = "Data " + reportName + " periode ini tidak ditemukan......!";

                    if (isBelumDiundang) {
                        alertMsg = "Data Konsumen Belum Ada " + reportName + " periode ini tidak ditemukan......!";
                    }

                    showUndanganNoDataAlert(alertMsg);
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                if (textStatus === 'abort') {
                    return;
                }
                if (requestId !== undanganRequestSequence) {
                    return;
                }

                var message = 'Gagal mengambil data.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += ' ' + xhr.responseJSON.message;
                } else if (errorThrown) {
                    message += ' ' + errorThrown;
                }
                alert(message);
                syncUndanganPrintState();
            },
            complete: function () {
                if (requestId === undanganRequestSequence) {
                    $('#loadingInfo').hide();
                    activeUndanganDataRequest = null;
                }
            }
        });
    }

    function groupRowsBySector(rows) {
        var groups = {};
        $.each(rows || [], function (index, item) {
            item = item || {};
            var nama = item.NAMA_SEKTOR || item.DESKRIPSI || item.NAMA_CLUSTER || 'TANPA CLUSTER';
            if (!groups[nama]) {
                groups[nama] = [];
            }
            groups[nama].push(item);
        });
        return groups;
    }

    function resolveCompany() {
        var company = String($('#nama_perusahaan').val() || '').trim();
        return company || 'PT. Duta Sumara Abadi';
    }

    function resolveToday() {
        var now = new Date();
        return String(now.getDate()).padStart(2, '0') + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + now.getFullYear();
    }

    function getHeaderMeta() {
        var blokAwal = String($('#blok_awal').val() || '*').toUpperCase();
        var blokAkhir = String($('#blok_akhir').val() || '*').toUpperCase();
        return {
            blokAwal: blokAwal,
            blokAkhir: blokAkhir,
            periodeAwal: formatDate($('#tgl_awal').val()),
            periodeAkhir: formatDate($('#tgl_akhir').val())
        };
    }

    function renderReport(rows) {
        rows = Array.isArray(rows) ? rows : [];
        if ($('#belum_diundang').is(':checked')) {
            renderBelumDiundangReport(rows);
            return;
        }
        renderUndanganReport(rows);
    }

    function renderUndanganReport(rows) {
        var jenisLabel = getSelectedJenisLabel();
        var reportTitle = getUndanganReportTitle();
        var html = '';

        html += '<div class="undangan-paper">';
        html += buildUndanganReportHeader(reportTitle);
        html += '<div class="undangan-table-wrap">';
        html += '<table class="report-table"><colgroup><col style="width:45px"><col style="width:105px"><col style="width:265px"><col style="width:62px"><col style="width:190px"><col style="width:105px"><col style="width:115px"><col style="width:105px"><col style="width:215px"></colgroup>';
        html += '<thead><tr class="report-head-row report-head-row-main"><th rowspan="2">No.</th><th rowspan="2">BLOK/<br>NOMOR</th><th rowspan="2">Nama Pemilik</th><th rowspan="2">Surat<br>Ke</th><th colspan="2">' + escapeHtml(jenisLabel) + '</th><th rowspan="2">Tanggal<br>Undangan</th><th rowspan="2">Waktu<br>Undangan</th><th rowspan="2">Tempat<br>Undangan</th></tr>';
        html += '<tr class="report-head-row report-head-row-sub"><th>Nomor</th><th>Tanggal</th></tr></thead><tbody>';

        if (rows.length < 1) {
            html += '<tr><td colspan="9" class="empty-state">Data tidak ditemukan.</td></tr>';
        } else {
            var groups = groupRowsBySector(rows);
            var number = 1;
            $.each(groups, function (sectorName, sectorRows) {
                html += '<tr class="sector-row"><td colspan="9">Sektor/Cluster : ' + escapeHtml(sectorName) + '</td></tr>';
                $.each(sectorRows, function (index, item) {
                    item = item || {};
                    html += '<tr class="data-row"><td class="center">' + number + '</td>';
                    html += '<td>' + escapeHtml(valueOrDash(item.BLOK_NOMOR)) + '</td>';
                    html += '<td>' + escapeHtml(valueOrDash(item.NASABAH_NAMA)) + '</td>';
                    html += '<td class="center">' + escapeHtml(valueOrDash(item.SURAT_KE || item.URUT)) + '</td>';
                    html += '<td>' + escapeHtml(valueOrDash(item.NO_SURAT)) + '</td>';
                    html += '<td class="center">' + escapeHtml(formatDate(item.TGL_SURAT)) + '</td>';
                    html += '<td class="center">' + escapeHtml(formatDate(item.TGL_UNDANGAN)) + '</td>';
                    html += '<td class="center">' + escapeHtml(valueOrDash(item.WAKTU_UNDANGAN || item.WAKTU)) + '</td>';
                    html += '<td>' + escapeHtml(valueOrDash(item.TEMPAT_UNDANGAN || item.TEMPAT)) + '</td></tr>';
                    number++;
                });
            });
        }
        html += '</tbody></table></div></div>';
        $('#mainDisplay').html(html);
    }

    function renderBelumDiundangReport(rows) {
        var jenisLabel = getSelectedJenisLabel();
        var title = 'DAFTAR KONSUMEN BELUM ADA ' + jenisLabel.toUpperCase();
        var html = '';

        html += '<div class="undangan-paper">';
        html += buildUndanganReportHeader(title);
        html += '<div class="undangan-table-wrap">';
        html += '<table class="report-table"><colgroup><col style="width:45px"><col style="width:105px"><col style="width:285px"><col style="width:200px"><col style="width:110px"><col style="width:125px"><col style="width:125px"></colgroup>';
        html += '<thead><tr><th>No.</th><th>BLOK/<br>NOMOR</th><th>Nama Konsumen</th><th>Nomor<br>PPJB</th><th>Tgl. PPJB</th><th>Tgl. Tanda Tangan<br>PPJB</th><th>Tgl. Tanda Tangan<br>Notaris</th></tr></thead><tbody>';

        if (rows.length < 1) {
            html += '<tr><td colspan="7" class="empty-state">Data tidak ditemukan.</td></tr>';
        } else {
            var groups = groupRowsBySector(rows);
            var number = 1;
            $.each(groups, function (sectorName, sectorRows) {
                html += '<tr class="sector-row"><td colspan="7">SEKTOR : ' + escapeHtml(sectorName) + '</td></tr>';
                $.each(sectorRows, function (index, item) {
                    item = item || {};
                    html += '<tr class="data-row"><td class="center">' + number + '</td>';
                    html += '<td>' + escapeHtml(valueOrDash(item.BLOK_NOMOR)) + '</td>';
                    html += '<td>' + escapeHtml(valueOrDash(item.NAMA_PEMBELI)) + '</td>';
                    html += '<td>' + escapeHtml(valueOrDash(item.NO_PPJB)) + '</td>';
                    html += '<td class="center">' + escapeHtml(formatDate(item.TGL_PPJB)) + '</td>';
                    html += '<td class="center">' + escapeHtml(formatDate(item.TGL_TANDA_TANGAN)) + '</td>';
                    html += '<td class="center">' + escapeHtml(formatDate(item.TGL_TTD_NOTARIS)) + '</td></tr>';
                    number++;
                });
            });
        }
        html += '</tbody></table></div></div>';
        $('#mainDisplay').html(html);
    }

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

    function printUndanganReport() {
        if ($('#undanganPrintButton').prop('disabled') || !Array.isArray(lastUndanganRows) || !$('#mainDisplay .report-table').length) {
            return;
        }

        var reportHtml = $('#mainDisplay').html();
        if (!reportHtml) {
            return;
        }

        $('#undanganNativePrintFrame').remove();

        var frame = document.createElement('iframe');
        frame.id = 'undanganNativePrintFrame';
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
            html, body { width: 100%; margin: 0; padding: 0; background: #fff; color: #000; font-family: "Segoe UI", Tahoma, Arial, sans-serif; }
            *, *::before, *::after { box-sizing: border-box; }
            .undangan-paper { width: calc(100% - 16mm); max-width: calc(100% - 16mm); margin: 8mm auto 0; padding: 0; }
            .undangan-report-header { display: grid; grid-template-columns: 1fr 1.45fr 1fr; gap: 12px; align-items: center; margin-bottom: 7px; padding: 10px 12px; border: 1px solid #777; background: #fff; }
            .undangan-company { color: #000; font-size: 11px; font-weight: 700; }
            .undangan-title-wrap { text-align: center; }
            .undangan-report-title { margin: 0; color: #000; font-family: Cambria, Georgia, "Times New Roman", serif; font-size: 17px; font-weight: 700; line-height: 1.2; }
            .undangan-report-center-meta, .undangan-report-date { color: #000; font-size: 10px; line-height: 1.4; }
            .undangan-report-date { text-align: right; }
            .undangan-report-subtitle { display: grid; grid-template-columns: 1fr 2fr 1fr; align-items: center; gap: 10px; margin-bottom: 7px; padding: 7px 9px; border: 1px solid #aaa; color: #000; font-size: 10px; }
            .undangan-sector-value { text-align: center; font-weight: 700; }
            .undangan-live-badge { justify-self: end; color: #000; font-size: 10px; font-weight: 700; }
            .undangan-live-badge::before { content: ""; display: inline-block; width: 5px; height: 5px; margin-right: 4px; border-radius: 50%; background: #000; }
            .undangan-table-wrap { width: 100%; overflow: visible; border: 0; }
            .report-table { width: 100%; min-width: 0; table-layout: auto; border-collapse: collapse; color: #000; font-size: 10px; }
            .report-table thead { display: table-header-group; }
            .report-table tr { page-break-inside: avoid; break-inside: avoid; }
            .report-table th, .report-table td { position: static; height: auto; padding: 4px; border: 1px solid #000; background: #fff; color: #000; overflow: visible; overflow-wrap: break-word; }
            .report-table th { font-weight: 700; text-align: center; }
            .sector-row td { background: #fff !important; color: #000; font-weight: 700; }
            .center { text-align: center; }
        `;

        frameDocument.open();
        frameDocument.write(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Daftar Surat Undangan</title><style>' + printCss + '</style></head><body>' + reportHtml + '</body></html>'
        );
        frameDocument.close();
        applyPrintTableRules(frameDocument);

        window.setTimeout(function () {
            try {
                frameWindow.focus();
                frameWindow.print();
            } finally {
                window.setTimeout(function () {
                    $('#undanganNativePrintFrame').remove();
                }, 1200);
            }
        }, 150);
    }
</script>
@endsection