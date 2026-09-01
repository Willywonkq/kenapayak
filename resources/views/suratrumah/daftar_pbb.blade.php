@extends('layouts.template')
@section('content')
<style>
.pbb-page, .pbb-page * {
    box-sizing: border-box;
}
.pbb-page {
    position: relative;
    width: 100%;
    min-width: 720px;
    min-height: 100%;
    padding: 18px 12px 32px;
    overflow: visible;
    color: #172033;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    background: radial-gradient( circle at 95% 2%, rgba(37, 99, 235, 0.07), transparent 28% ), radial-gradient( circle at 8% 96%, rgba(56, 189, 248, 0.05), transparent 26% ), #f3f6fb;
}
.pbb-toolbar {
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
    background: linear-gradient( 90deg, #ffffff 0%, #ffffff 65%, #f8fbff 100% );
    color: #172033;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
}
.pbb-toolbar::before {
    content: "◈";
    position: absolute;
    left: 20px;
    display: grid;
    width: 34px;
    height: 34px;
    place-items: center;
    border: 0;
    border-radius: 11px;
    background: linear-gradient( 135deg, #2563eb 0%, #1d4ed8 100% );
    color: #ffffff;
    font-size: 18px;
    box-shadow: 0 10px 20px rgba(37, 99, 235, 0.24);
}
.pbb-toolbar::after {
    content: "";
    position: absolute;
    top: -82px;
    right: 40px;
    width: 260px;
    height: 190px;
    border-radius: 50%;
    background: radial-gradient( circle, rgba(37, 99, 235, 0.08), transparent 68% );
    pointer-events: none;
}
.pbb-toolbar-title {
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
.pbb-unit-badge {
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
.pbb-filter {
    position: relative;
    padding: 20px;
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
}
.pbb-filter::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    background: linear-gradient( 180deg, #38bdf8 0%, #2563eb 52%, #1d4ed8 100% );
}
.pbb-filter::after {
    content: "Daftar Rekap PBB";
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
.pbb-filter-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 82px;
    gap: 13px 14px;
    align-items: center;
}
.pbb-field {
    display: grid;
    grid-template-columns: 72px minmax(0, 1fr);
    gap: 9px;
    align-items: center;
    min-width: 0;
}
.pbb-label {
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
.pbb-range {
    display: grid;
    grid-template-columns: minmax(86px, 1fr) 34px minmax(86px, 1fr);
    gap: 7px;
    align-items: center;
}
.pbb-separator {
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
.pbb-input, .pbb-lookup-display {
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
    transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}
.pbb-input:hover, .pbb-lookup-display:hover {
    border-color: #aebed1;
    background: #ffffff;
}
.pbb-input:focus {
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
}
.pbb-lookup {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px;
    gap: 8px;
}
.pbb-lookup-display {
    display: flex;
    align-items: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.pbb-lookup-button {
    display: inline-flex;
    width: 42px;
    height: 42px;
    align-items: center;
    justify-content: center;
    border: 1px solid #bfdbfe;
    border-radius: 13px;
    background: linear-gradient( 145deg, #eff6ff 0%, #dbeafe 100% );
    color: #1d4ed8;
    cursor: pointer;
    transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}
.pbb-lookup-button:hover {
    transform: translateY(-1px);
    background: linear-gradient( 145deg, #dbeafe 0%, #bfdbfe 100% );
    box-shadow: 0 7px 15px rgba(37, 99, 235, 0.12);
}
.pbb-action-stack {
    position: relative;
    width: 82px;
    height: 42px;
    grid-column: 3;
    grid-row: 1;
    align-self: start;
    justify-self: end;
}
.pbb-action {
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
    letter-spacing: 0.06em;
    text-transform: uppercase;
    transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}
.pbb-action-stack .pbb-action:first-child {
    position: absolute;
    top: 0;
    right: -20px;
    margin: 0;
    border-radius: 13px 0 0 13px;
    background: linear-gradient( 135deg, #2563eb 0%, #1d4ed8 100% );
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
}
.pbb-action-stack .pbb-action:first-child:hover {
    transform: translateY(-1px);
    background: linear-gradient( 135deg, #2f6ff0 0%, #1e4fc4 100% );
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.30);
}
#pbbPrintButton {
    position: absolute;
    top: 55px;
    right: -20px;
    border-radius: 13px 0 0 13px;
    background: linear-gradient( 135deg, #10b981 0%, #059669 100% );
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(5, 150, 105, 0.22);
}
#pbbPrintButton:hover:not(:disabled) {
    transform: translateY(-1px);
    background: linear-gradient( 135deg, #14b88a 0%, #047857 100% );
    box-shadow: 0 12px 24px rgba(5, 150, 105, 0.28);
}
#pbbPrintButton:disabled, #pbbPrintButton:disabled:hover, #pbbPrintButton:disabled:focus {
    transform: none;
    border: 1px solid #d5dde7;
    background: linear-gradient( 135deg, #e2e8f0 0%, #cbd5e1 100% );
    color: #94a3b8;
    box-shadow: none;
    cursor: not-allowed;
    opacity: 1;
}
.pbb-check-row {
    display: flex;
    min-height: 42px;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.pbb-checkbox {
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
    transition: border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
}
.pbb-checkbox:hover {
    border-color: #bfdbfe;
    background: #eff6ff;
    color: #1d4ed8;
}
.pbb-checkbox input {
    width: 15px;
    height: 15px;
    margin: 0;
    accent-color: #2563eb;
}
.pbb-checkbox:has(input:checked) {
    border-color: #a7f3d0;
    background: linear-gradient( 135deg, #ecfdf3, #f0fdf4 );
    color: #047857;
}
.pbb-check-belum {
    grid-column: 1;
    grid-row: 3;
}
.pbb-check-alamat {
    grid-column: 2;
    grid-row: 3;
}
.pbb-report-area {
    position: relative;
    margin-top: 18px;
    padding: 20px;
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
}
.pbb-report-area::before {
    content: "";
    position: absolute;
    top: 0;
    left: 7%;
    right: 7%;
    height: 3px;
    border-radius: 0 0 999px 999px;
    background: linear-gradient( 90deg, transparent, #38bdf8, #2563eb, #6366f1, transparent );
}
.pbb-paper {
    width: 100%;
    min-height: 0;
    margin: 0;
    padding: 0;
    border: 0;
    background: transparent;
    color: #172033;
    box-shadow: none;
}
#pbbLoading {
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
.pbb-initial {
    display: flex;
    min-height: 310px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    border: 1px dashed #bfdbfe;
    border-radius: 20px;
    background: radial-gradient( circle at center, rgba(37, 99, 235, 0.06), transparent 46% ), #f8fbff;
    color: #667085;
    font-size: 13px;
    font-weight: 650;
}
.pbb-initial-icon {
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
.pbb-report-header {
    display: grid;
    grid-template-columns: minmax(150px, 1fr) minmax(260px, 1.4fr) minmax(180px, 1fr);
    gap: 16px;
    align-items: center;
    margin-bottom: 10px;
    padding: 15px 18px;
    border: 1px solid #dbeafe;
    border-radius: 18px;
    background: linear-gradient( 105deg, #eff6ff 0%, #dbeafe 48%, #eef2ff 100% );
    color: #172033;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.10);
}
.pbb-company {
    padding: 0;
    border: 0;
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 850;
    letter-spacing: 0.04em;
    line-height: 1.35;
}
.pbb-title-wrap {
    text-align: center;
}
.pbb-report-title {
    display: block;
    min-width: 0;
    margin: 0;
    padding: 0;
    border: 0;
    color: #172033;
    font-family: Cambria, Georgia, "Times New Roman", serif !important;
    font-size: 18px;
    font-weight: 700 !important;
    letter-spacing: -0.02em;
    line-height: 1.2;
}
.pbb-report-date {
    color: #475467;
    text-align: right;
    font-size: 10.5px;
    font-weight: 650;
    line-height: 1.55;
}
.pbb-report-subtitle {
    display: flex;
    min-height: 36px;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: linear-gradient( 90deg, #ffffff, #f8fafc );
    color: #667085;
    font-size: 10.5px;
}
.pbb-report-subtitle strong {
    color: #344054;
    font-weight: 850;
}
.pbb-live-badge {
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
.pbb-live-badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
}
.pbb-table-wrap {
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
.pbb-table-wrap::-webkit-scrollbar, .pbb-modal-table-wrap::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}
.pbb-table-wrap::-webkit-scrollbar-track, .pbb-modal-table-wrap::-webkit-scrollbar-track {
    background: #eff3f7;
}
.pbb-table-wrap::-webkit-scrollbar-thumb, .pbb-modal-table-wrap::-webkit-scrollbar-thumb {
    border: 2px solid #eff3f7;
    border-radius: 999px;
    background: linear-gradient( 180deg, #60a5fa, #2563eb );
}
.pbb-report-table {
    width: 1250px;
    min-width: 1250px;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
    color: #344054;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10.5px;
}
.pbb-report-table.pbb-detail-table {
    width: 1660px;
    min-width: 1660px;
}
.pbb-report-table.pbb-belum-table {
    width: 1250px;
    min-width: 1250px;
}
.pbb-report-table th, .pbb-report-table td {
    padding: 8px 9px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
    overflow-wrap: anywhere;
}
.pbb-report-table th {
    position: sticky;
    top: 0;
    z-index: 4;
    height: 48px;
    background: linear-gradient( 180deg, #eff6ff 0%, #e5effb 100% );
    color: #344054;
    text-align: center;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-weight: 900;
    line-height: 1.25;
    box-shadow: inset 0 -1px 0 rgba(37, 99, 235, 0.05);
}
.pbb-report-table td {
    height: 46px;
    background: #ffffff;
    color: #344054;
    line-height: 1.38;
    white-space: normal;
}
.pbb-report-table tbody tr:nth-child(even):not(.pbb-sector-row):not(.pbb-total-row):not(.pbb-grand-total-row) td {
    background: #fbfcfe;
}
.pbb-report-table tbody tr:not(.pbb-sector-row):not(.pbb-total-row):not(.pbb-grand-total-row):hover td {
    background: #f0f7ff;
}
.pbb-report-table tbody tr:not(.pbb-sector-row):not(.pbb-total-row):not(.pbb-grand-total-row):hover td:first-child {
    box-shadow: inset 4px 0 0 #2563eb;
    color: #1d4ed8;
    font-weight: 900;
}
.pbb-sector-row td {
    height: auto;
    padding: 8px 10px;
    background: linear-gradient( 90deg, #ffffff 0%, #f8fafc 55%, #eff6ff 100% ) !important;
    color: #344054;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10px;
    font-weight: 850;
    text-align: left;
}
.pbb-total-row td {
    background: #eff6ff !important;
    color: #1e3a5f;
    font-weight: 850;
}
.pbb-grand-total-row td {
    background: #e0e7ff !important;
    color: #312e81;
    font-weight: 900;
}
.pbb-center {
    text-align: center;
}
.pbb-left {
    text-align: left;
}
.pbb-number {
    color: #1e3a5f;
    text-align: right;
    font-weight: 750;
    font-variant-numeric: tabular-nums;
}
.pbb-empty {
    height: 130px;
    color: #64748b;
    text-align: center;
}
#pbbSektorModal {
    position: fixed;
    inset: 0;
    z-index: 1065;
    display: none;
    overflow: auto;
    background: rgba(71, 85, 105, 0.24);
}
#pbbSektorModal.show {
    display: block;
}
.pbb-modal-dialog {
    width: calc(100vw - 32px);
    max-width: 800px;
    margin: 24px auto;
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
}
.pbb-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 18px;
    border-bottom: 1px solid #dbe3ef;
    background: linear-gradient( 90deg, #ffffff 0%, #f8fbff 100% );
    color: #1d2939;
}
.pbb-modal-header .pbb-action {
    width: 36px !important;
    min-width: 36px !important;
    height: 36px;
    border: 1px solid #bfdbfe;
    border-radius: 11px !important;
    background: #eff6ff !important;
    color: #1d4ed8 !important;
    box-shadow: none !important;
}
.pbb-modal-body {
    padding: 16px;
    background: #f8fafc;
}
.pbb-modal-search {
    width: 100%;
    height: 42px;
    margin-bottom: 12px;
    padding: 8px 12px;
    border: 1px solid #c8d3e1;
    border-radius: 12px;
    background: #ffffff;
    color: #101828;
    outline: 0;
}
.pbb-modal-search:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
.pbb-modal-table-wrap {
    max-height: 430px;
    overflow: auto;
    border: 1px solid #dbe3ef;
    border-radius: 16px;
    background: #ffffff;
}
.pbb-modal-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}
.pbb-modal-table th, .pbb-modal-table td {
    padding: 10px 12px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}
.pbb-modal-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: linear-gradient( 180deg, #eff6ff 0%, #e7f0fc 100% );
    color: #344054;
    text-align: center;
    font-weight: 850;
}
.pbb-modal-table tbody tr {
    cursor: pointer;
}
.pbb-modal-table tbody tr:hover td {
    background: #eff6ff;
    color: #1d4ed8;
}
.pbb-page, .pbb-page input, .pbb-page select, .pbb-page button, .pbb-page textarea, .pbb-page label, .pbb-page table, .pbb-page td, .pbb-page .pbb-report-subtitle, .pbb-page .pbb-report-date {
    font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
}
.pbb-page .pbb-toolbar-title, .pbb-page .pbb-label, .pbb-page .pbb-report-table th, .pbb-page .pbb-modal-header {
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
}
.pbb-page .pbb-report-title {
    font-family: Cambria, Georgia, "Times New Roman", serif !important;
    font-weight: 700 !important;
}
@media screen and (max-width: 719px) {
    html, body {
        min-width: 720px;
    }
    .pbb-page {
        min-width: 720px;
    }
}
@media print {
    .pbb-toolbar, .pbb-filter, #pbbLoading, #pbbSektorModal,
    #pbbNoDataAlertModal, .main-sidebar, .control-sidebar, .main-header, .main-footer,
    .navbar, .sidebar {
        display: none !important;
    }
    html, body, .wrapper, .content-wrapper, .main-content, .content,
    .page-wrapper, .page-content, .container, .container-fluid,
    .pbb-page, .pbb-report-area, .pbb-paper {
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
    .pbb-table-wrap {
        max-height: none !important;
        overflow: visible !important;
    }
    .pbb-report-table,
    .pbb-report-table.pbb-detail-table,
    .pbb-report-table.pbb-belum-table {
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
    #pbbNoDataAlertModal .modal-dialog {
        max-width: 380px;
    }

    #pbbNoDataAlertModal .modal-content {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
    }

    #pbbNoDataAlertModal .alert-icon-wrapper {
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

    #pbbNoDataAlertModal .alert-title {
        margin-bottom: 8px;
        color: #172033;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 18px;
        font-weight: 700;
    }

    #pbbNoDataAlertModal .alert-message {
        margin-bottom: 24px;
        color: #475569;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 14px;
    }

    #pbbNoDataAlertModal .alert-btn-ok {
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

    #pbbNoDataAlertModal .alert-btn-ok:hover {
        transform: translateY(-1px);
        color: #ffffff;
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
    }
</style>

<!-- MODAL ALERT DATA KOSONG -->
<div class="modal fade" id="pbbNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4" style="text-align: center; padding: 1.5rem;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message" id="pbbNoDataMessage">Data tidak ditemukan.</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    data-dismiss="modal"
                    onclick="hidePbbNoDataAlert()"
                >OK</button>
            </div>
        </div>
    </div>
</div>

<div class="pbb-page">
    <div class="pbb-toolbar">
        <div class="pbb-toolbar-title">
            Daftar PBB
        </div>
        <code class="pbb-unit-badge">
            UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}
        </code>
    </div>
    <section class="pbb-filter">
        <input
            type="hidden"
            id="pbbPerusahaan"
            value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}"
        >
        <input
            type="hidden"
            id="pbbNamaPerusahaan"
            value="{{ session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? '' }}"
        >
        <input
            type="hidden"
            id="pbbSektor"
            value="*"
        >
        <div class="pbb-filter-grid">
            <div class="pbb-field">
                <label
                    class="pbb-label"
                    for="pbbBlokAwal"
                >
                    Blok
                </label>
                <div class="pbb-range">
                    <input
                        type="text"
                        id="pbbBlokAwal"
                        class="pbb-input"
                        value="A"
                        maxlength="30"
                    >
                    <span class="pbb-separator">
                        s.d
                    </span>
                    <input
                        type="text"
                        id="pbbBlokAkhir"
                        class="pbb-input"
                        value="Z"
                        maxlength="30"
                    >
                </div>
            </div>
            <div class="pbb-field">
                <span class="pbb-label">
                    Sektor/Cluster
                </span>
                <div class="pbb-lookup">
                    <div
                        id="pbbSektorEntry"
                        class="pbb-lookup-display"
                    >Semua Sektor</div>
                    <button
                        type="button"
                        class="pbb-lookup-button"
                        onclick="getPbbSektorModal()"
                        title="Pilih sektor/cluster"
                    >
                        <i class="fas fa-binoculars"></i>
                    </button>
                </div>
            </div>
            <div class="pbb-action-stack">
                <button
                    type="button"
                    class="pbb-action"
                    onclick="getPbbData()"
                >
                    Ok
                </button>
                <button
                    type="button"
                    class="pbb-action"
                    id="pbbPrintButton"
                    onclick="printPbbReport()"
                    autocomplete="off"
                    aria-disabled="true"
                    disabled
                >
                    Print
                </button>
            </div>
            <div class="pbb-field">
                <label
                    class="pbb-label"
                    for="pbbTahunAwal"
                >
                    Tahun PBB
                </label>
                <div class="pbb-range">
                    <input
                        type="number"
                        id="pbbTahunAwal"
                        class="pbb-input"
                        value="2000"
                        min="1900"
                        max="9999"
                    >
                    <span class="pbb-separator">
                        s.d
                    </span>
                    <input
                        type="number"
                        id="pbbTahunAkhir"
                        class="pbb-input"
                        value="{{ date('Y') }}"
                        min="1900"
                        max="9999"
                    >
                </div>
            </div>
            <div class="pbb-field">
                <label
                    class="pbb-label"
                    for="pbbTglAwal"
                >
                    Tgl. Input
                </label>
                <div class="pbb-range">
                    <input
                        type="date"
                        id="pbbTglAwal"
                        class="pbb-input"
                    >
                    <span class="pbb-separator">
                        s.d
                    </span>
                    <input
                        type="date"
                        id="pbbTglAkhir"
                        class="pbb-input"
                    >
                </div>
            </div>
            <div class="pbb-check-row pbb-check-alamat">
                <label class="pbb-checkbox">
                    <input
                        type="checkbox"
                        id="pbbTampilNamaAlamatWp"
                    >
                    <span>Tampilkan Nama &amp; Alamat WP</span>
                </label>
            </div>
            <div class="pbb-check-row pbb-check-belum">
                <label class="pbb-checkbox">
                    <input
                        type="checkbox"
                        id="pbbBelumAdaPbb"
                    >
                    <span>Belum Ada PBB</span>
                </label>
            </div>
        </div>
    </section>
    <section class="pbb-report-area">
        <div id="pbbLoading">
            <i class="fas fa-spinner fa-spin"></i>
            Mengambil data PBB...
        </div>
        <div id="pbbMainDisplay">
            <div class="pbb-paper">
                <div class="pbb-initial">
                    <i
                        class="fas fa-table pbb-initial-icon"
                        aria-hidden="true"
                    ></i>
                    <div>
                        Silahkan isi filter kemudian klik OK
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div
        id="pbbSektorModal"
        aria-hidden="true"
    >
        <div class="pbb-modal-dialog">
            <div class="pbb-modal-header">
                <strong>Pilih Sektor/Cluster</strong>
                <button
                    type="button"
                    class="pbb-action"
                    onclick="togglePbbSektorModal(false)"
                >
                    X
                </button>
            </div>
            <div
                class="pbb-modal-body"
                id="pbbSektorModalContent"
            ></div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script>
    var lastPbbRows = null;
$(document).ready(function () {
        setPbbDefaultDate();
        resetPbbPrint();
        $('#pbbBlokAwal, #pbbBlokAkhir').on(
            'input',
            function () {
                $(this).val(
                    String($(this).val() || '').toUpperCase()
                );
            }
        );
        $('#pbbBelumAdaPbb').on('change', function () {
            if ($(this).is(':checked')) {
                $('#pbbTampilNamaAlamatWp')
                    .prop('checked', false);
            }
            resetPbbPrint();
        });
        $('#pbbTampilNamaAlamatWp').on('change', function () {
            if ($(this).is(':checked')) {
                $('#pbbBelumAdaPbb')
                    .prop('checked', false);
            }
            resetPbbPrint();
        });
    });
    $(window).on('pageshow', function (event) {
        var pageEvent = event.originalEvent || event;
        if (pageEvent.persisted) {
            resetPbbPrint();
        }
    });
    function setPbbDefaultDate() {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var today = year + '-' + month + '-' + day;
        $('#pbbTglAwal').val(today);
        $('#pbbTglAkhir').val(today);
    }
    function setPbbPrintEnabled(enabled) {
        var disabled = enabled !== true;
        $('#pbbPrintButton')
            .prop('disabled', disabled)
            .attr('aria-disabled', disabled ? 'true' : 'false');
    }
    function resetPbbPrint() {
        lastPbbRows = null;
        setPbbPrintEnabled(false);
        hidePbbNoDataAlert();
    }

    /*
     * Alert data kosong, mengikuti Daftar Sertifikat Pecahan.
     * Hanya menampilkan pesan; pengambilan data dan render laporan
     * tetap memakai alur yang sudah ada. Jika plugin modal tidak
     * tersedia pada halaman ini, modal ditampilkan secara manual
     * sehingga tampilannya tetap sama.
     */
    function showPbbNoDataAlert(message) {
        var $modal = $('#pbbNoDataAlertModal');

        $('#pbbNoDataMessage').text(message || 'Data tidak ditemukan......!');

        if (typeof $modal.modal === 'function') {
            $modal.modal('show');
            return;
        }

        if (!$('.pbb-nodata-backdrop').length) {
            $('<div class="modal-backdrop fade show pbb-nodata-backdrop"></div>')
                .appendTo(document.body);
        }

        $modal
            .addClass('show')
            .css('display', 'block')
            .attr('aria-hidden', 'false');
    }

    function hidePbbNoDataAlert() {
        var $modal = $('#pbbNoDataAlertModal');

        if (typeof $modal.modal === 'function') {
            $modal.modal('hide');
            return;
        }

        $modal
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true');

        $('.pbb-nodata-backdrop').remove();
    }
    function pbbEscapeHtml(value) {
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
    function pbbEscapeJs(value) {
        return String(
            value === null || value === undefined ? '' : value
        )
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r?\n/g, ' ');
    }
    function pbbValue(value, fallback) {
        return value === null
            || value === undefined
            || value === ''
            ? (fallback === undefined ? '-' : fallback)
            : value;
    }
    function pbbPick(item, keys) {
        item = item || {};
        for (var i = 0; i < keys.length; i++) {
            var value = item[keys[i]];
            if (
                value !== null
                && value !== undefined
                && value !== ''
            ) {
                return value;
            }
        }
        return null;
    }
    function pbbFormatDate(value) {
        if (!value) {
            return '-';
        }
        var text = String(value);
        var match = text.match(
            /^(\d{4})-(\d{2})-(\d{2})/
        );
        if (match) {
            return match[3]
                + '-'
                + match[2]
                + '-'
                + match[1];
        }
        return text;
    }
    function pbbNumber(value) {
        var number = Number(value);
        if (!isFinite(number)) {
            number = 0;
        }
        return number;
    }
    function pbbFormatNumber(value) {
        return pbbNumber(value).toLocaleString(
            'en-US',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );
    }
    function pbbExtractCompanyName(value) {
        var raw = String(value || '')
            .replace(/\u00a0/g, ' ');
        var locationMatch = raw.match(
            /Lokasi\s*:[^\r\n|]*?-\s*([^\r\n|]+)/i
        );
        var companyMatch = raw.match(
            /\b((?:PT\.?|CV\.?|PD\.?|PERUM)\s+[^\r\n|]+)/i
        );
        var name = locationMatch
            ? locationMatch[1]
            : (companyMatch ? companyMatch[1] : '');
        return String(name || '')
            .replace(
                /\s+(?:Hi\s*,|Logout\b|Keluar\b|LAND DOCUMENT\b|UNIT\s+[A-Z0-9_-]+\b).*$/i,
                ''
            )
            .replace(/\s+/g, ' ')
            .trim();
    }
    function pbbCompanyNameFromLayout() {
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
        var elements = document.querySelectorAll(
            selectors.join(',')
        );
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
                var name = pbbExtractCompanyName(
                    candidates[j]
                );
                if (name) {
                    return name;
                }
            }
        }
        return '';
    }
    function pbbCompanyName(first) {
        var unit = String(
            $('#pbbPerusahaan').val() || ''
        ).trim().toUpperCase();
        var rowName = pbbPick(first || {}, [
            'NAMA_PT',
            'nama_pt',
            'ATAS_NAMA_PT',
            'atas_nama_pt',
            'NAMA_PERUSAHAAN',
            'nama_perusahaan'
        ]);
        var sessionName = String(
            $('#pbbNamaPerusahaan').val() || ''
        ).trim();
        var cacheKey = 'sris.report-company.' + unit;
        var cachedName = '';
        try {
            cachedName = localStorage.getItem(cacheKey) || '';
        } catch (error) {
            cachedName = '';
        }
        var company =
            pbbExtractCompanyName(rowName)
            || String(rowName || '').trim()
            || pbbCompanyNameFromLayout()
            || pbbExtractCompanyName(sessionName)
            || sessionName
            || cachedName
            || unit
            || '-';
        if (
            company
            && company !== '-'
            && company.toUpperCase() !== unit
        ) {
            try {
                localStorage.setItem(
                    cacheKey,
                    company
                );
            } catch (error) {
                // Storage dapat ditolak browser; render saat ini tetap jalan.
            }
        }
        return company;
    }
    function togglePbbSektorModal(show) {
        $('#pbbSektorModal')
            .toggleClass('show', show === true)
            .attr(
                'aria-hidden',
                show === true ? 'false' : 'true'
            );
    }
    function addPbbSektor(kode, deskripsi) {
        $('#pbbSektor').val(kode || '*');
        $('#pbbSektorEntry').text(deskripsi || 'Semua Sektor');
        togglePbbSektorModal(false);
        resetPbbPrint();
    }
    function filterPbbSektor(keyword) {
        var search = String(
            keyword || ''
        ).toLowerCase().trim();
        $('#pbbSektorModal .pbb-modal-table tbody tr')
            .each(function () {
                $(this).toggle(
                    $(this)
                        .text()
                        .toLowerCase()
                        .indexOf(search) !== -1
                );
            });
    }
    function getPbbSektorModal() {
        var perusahaan = String(
            $('#pbbPerusahaan').val() || ''
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
                        response
                        && Array.isArray(response.data)
                            ? response.data
                            : []
                    );
                var html = '';
                html += '<input type="text" ';
                html += 'class="pbb-modal-search" ';
                html += 'placeholder="Cari sektor..." ';
                html += 'onkeyup="filterPbbSektor(this.value)">';
                html += '<div class="pbb-modal-table-wrap">';
                html += '<table class="pbb-modal-table">';
                html += '<thead><tr>';
                html += '<th>Kode</th>';
                html += '<th>Sektor / Cluster</th>';
                html += '<th>Perusahaan</th>';
                html += '</tr></thead><tbody>';
                html += '<tr onclick="addPbbSektor(';
                html += '\'*\', \'Semua Sektor\'';
                html += ')">';
                html += '<td>*</td>';
                html += '<td>Semua Sektor</td>';
                html += '<td>'
                    + pbbEscapeHtml(perusahaan)
                    + '</td>';
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
                    html += '<tr onclick="addPbbSektor(\'';
                    html += pbbEscapeJs(kode);
                    html += '\', \'';
                    html += pbbEscapeJs(deskripsi);
                    html += '\')">';
                    html += '<td>'
                        + pbbEscapeHtml(kode)
                        + '</td>';
                    html += '<td>'
                        + pbbEscapeHtml(deskripsi)
                        + '</td>';
                    html += '<td>'
                        + pbbEscapeHtml(unit)
                        + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
                $('#pbbSektorModalContent').html(html);
                togglePbbSektorModal(true);
            },
            error: function (xhr) {
                var message = 'Gagal mengambil sektor.';
                if (
                    xhr.responseJSON
                    && xhr.responseJSON.message
                ) {
                    message += ' ' + xhr.responseJSON.message;
                }
                alert(message);
            }
        });
    }
    function validatePbbFilter() {
        if (
            !$('#pbbBlokAwal').val()
            || !$('#pbbBlokAkhir').val()
        ) {
            alert('Rentang blok wajib diisi.');
            return false;
        }
        var tahunAwal = Number($('#pbbTahunAwal').val());
        var tahunAkhir = Number($('#pbbTahunAkhir').val());
        if (
            !isFinite(tahunAwal)
            || !isFinite(tahunAkhir)
            || tahunAwal > tahunAkhir
        ) {
            alert('Rentang Tahun PBB tidak valid.');
            return false;
        }
        if (
            !$('#pbbTglAwal').val()
            || !$('#pbbTglAkhir').val()
        ) {
            alert('Rentang Tgl. Input wajib diisi.');
            return false;
        }
        if (
            $('#pbbTglAwal').val()
            > $('#pbbTglAkhir').val()
        ) {
            alert(
                'Tanggal awal tidak boleh melebihi tanggal akhir.'
            );
            return false;
        }
        return true;
    }
    function getPbbFilterData() {
        return {
            _token: '{{ csrf_token() }}',
            blok_awal: String(
                $('#pbbBlokAwal').val() || 'A'
            ).toUpperCase(),
            blok_akhir: String(
                $('#pbbBlokAkhir').val() || 'Z'
            ).toUpperCase(),
            tahun_awal: $('#pbbTahunAwal').val(),
            tahun_akhir: $('#pbbTahunAkhir').val(),
            tgl_awal: $('#pbbTglAwal').val(),
            tgl_akhir: $('#pbbTglAkhir').val(),
            perusahaan: $('#pbbPerusahaan').val(),
            sektor: $('#pbbSektor').val() || '*',
            tampil_nama_alamat_wp:
                $('#pbbTampilNamaAlamatWp').is(':checked')
                    ? 'Y'
                    : 'T',
            belum_ada_pbb:
                $('#pbbBelumAdaPbb').is(':checked')
                    ? 'Y'
                    : 'T'
        };
    }
    function getPbbData() {
        if (!validatePbbFilter()) {
            return;
        }
        resetPbbPrint();
        $('#pbbLoading').show();
        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_data',
            dataType: 'json',
            timeout: 120000,
            headers: {
                'Accept': 'application/json'
            },
            data: getPbbFilterData(),
            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (
                        response
                        && Array.isArray(response.data)
                            ? response.data
                            : []
                    );
                lastPbbRows = rows;

                if (rows.length === 0) {
                    showPbbNoDataAlert(
                        $('#pbbBelumAdaPbb').is(':checked')
                            ? 'Data Blok Belum Ada PBB tidak ditemukan......!'
                            : 'Data tidak ditemukan......!'
                    );
                }

                renderPbbReport(rows);
                setPbbPrintEnabled(true);
            },
            error: function (xhr, textStatus, errorThrown) {
                resetPbbPrint();
                var detail = '';
                if (
                    xhr.responseJSON
                    && xhr.responseJSON.message
                ) {
                    detail = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    detail = String(xhr.responseText)
                        .replace(/<[^>]+>/g, ' ')
                        .replace(/\s+/g, ' ')
                        .trim()
                        .substring(0, 900);
                } else {
                    detail = String(
                        errorThrown || textStatus || ''
                    );
                }
                $('#pbbMainDisplay').html(
                    '<div class="pbb-paper">'
                    + '<div style="padding:16px;color:#a00;">'
                    + 'Gagal mengambil data PBB. '
                    + pbbEscapeHtml(detail)
                    + '</div></div>'
                );
            },
            complete: function () {
                $('#pbbLoading').hide();
            }
        });
    }
    function groupPbbBySector(rows) {
        var groups = [];
        var map = {};
        $.each(rows, function (index, item) {
            item = item || {};
            var sektor = String(
                pbbPick(item, [
                    'NAMA_SEKTOR',
                    'nama_sektor'
                ])
                || 'TANPA SEKTOR'
            ).trim();
            if (!sektor) {
                sektor = 'TANPA SEKTOR';
            }
            if (map[sektor] === undefined) {
                map[sektor] = groups.length;
                groups.push({
                    sektor: sektor,
                    rows: []
                });
            }
            groups[map[sektor]].rows.push(item);
        });
        return groups;
    }
    function calculatePbbTotal(rows) {
        var total = {
            LUAS_BUMI: 0,
            LUAS_BANGUNAN: 0,
            NJOP_BUMI: 0,
            NJOP_BANGUNAN: 0,
            PBB_BAYAR: 0
        };
        $.each(rows || [], function (index, item) {
            total.LUAS_BUMI += pbbNumber(
                pbbPick(item, ['LUAS_BUMI', 'luas_bumi'])
            );
            total.LUAS_BANGUNAN += pbbNumber(
                pbbPick(item, [
                    'LUAS_BANGUNAN',
                    'luas_bangunan'
                ])
            );
            total.NJOP_BUMI += pbbNumber(
                pbbPick(item, ['NJOP_BUMI', 'njop_bumi'])
            );
            total.NJOP_BANGUNAN += pbbNumber(
                pbbPick(item, [
                    'NJOP_BANGUNAN',
                    'njop_bangunan'
                ])
            );
            total.PBB_BAYAR += pbbNumber(
                pbbPick(item, ['PBB_BAYAR', 'pbb_bayar'])
            );
        });
        return total;
    }
    function renderPbbTotalCells(total) {
        var html = '';
        html += '<td class="pbb-number">'
            + pbbFormatNumber(total.LUAS_BUMI)
            + '</td>';
        html += '<td class="pbb-number">'
            + pbbFormatNumber(total.LUAS_BANGUNAN)
            + '</td>';
        html += '<td class="pbb-number">'
            + pbbFormatNumber(total.NJOP_BUMI)
            + '</td>';
        html += '<td class="pbb-number">'
            + pbbFormatNumber(total.NJOP_BANGUNAN)
            + '</td>';
        html += '<td class="pbb-number">'
            + pbbFormatNumber(total.PBB_BAYAR)
            + '</td>';
        return html;
    }
    function renderPbbStandardHeader(showDetail) {
        var html = '<thead><tr>';
        html += '<th style="width:42px;">No.</th>';
        html += '<th style="width:58px;">Tahun<br>PBB</th>';
        html += '<th style="width:150px;">NOP<br>Pecahan</th>';
        html += '<th style="width:190px;">Nama<br>Wajib Pajak</th>';
        html += '<th style="width:210px;">Letak<br>Object Pajak</th>';
        html += '<th style="width:75px;">Luas<br>Bumi</th>';
        html += '<th style="width:75px;">Luas<br>Bgn</th>';
        html += '<th style="width:115px;">NJOP Bumi<br>(Rp)</th>';
        html += '<th style="width:115px;">NJOP Bgn<br>(Rp)</th>';
        html += '<th style="width:115px;">PBB Bayar<br>(Rp)</th>';
        if (showDetail) {
            html += '<th style="width:240px;">';
            html += 'Alamat Pembeli<br>Sesuai KTP';
            html += '</th>';
            html += '<th style="width:155px;">';
            html += 'Nomor PPJB/<br>Tgl PPJB';
            html += '</th>';
            html += '<th style="width:125px;">No KTP</th>';
            html += '<th style="width:125px;">NPWP</th>';
        }
        html += '</tr></thead>';
        return html;
    }
    function renderPbbStandardRow(item, no, showDetail) {
        var html = '<tr>';
        html += '<td class="pbb-center">'
            + no
            + '</td>';
        html += '<td class="pbb-center">'
            + pbbEscapeHtml(
                pbbValue(
                    pbbPick(item, ['TAHUN_PBB', 'tahun_pbb'])
                )
            )
            + '</td>';
        html += '<td class="pbb-center">'
            + pbbEscapeHtml(
                pbbValue(
                    pbbPick(item, ['NOP_PISAH', 'nop_pisah'])
                )
            )
            + '</td>';
        html += '<td class="pbb-left">'
            + pbbEscapeHtml(
                pbbValue(
                    pbbPick(item, ['NAMA_WP', 'nama_wp'])
                )
            )
            + '</td>';
        html += '<td class="pbb-left">'
            + pbbEscapeHtml(
                pbbValue(
                    pbbPick(item, ['LETAK_OP', 'letak_op'])
                )
            )
            + '</td>';
        html += '<td class="pbb-number">'
            + pbbFormatNumber(
                pbbPick(item, ['LUAS_BUMI', 'luas_bumi'])
            )
            + '</td>';
        html += '<td class="pbb-number">'
            + pbbFormatNumber(
                pbbPick(item, [
                    'LUAS_BANGUNAN',
                    'luas_bangunan'
                ])
            )
            + '</td>';
        html += '<td class="pbb-number">'
            + pbbFormatNumber(
                pbbPick(item, ['NJOP_BUMI', 'njop_bumi'])
            )
            + '</td>';
        html += '<td class="pbb-number">'
            + pbbFormatNumber(
                pbbPick(item, [
                    'NJOP_BANGUNAN',
                    'njop_bangunan'
                ])
            )
            + '</td>';
        html += '<td class="pbb-number">'
            + pbbFormatNumber(
                pbbPick(item, ['PBB_BAYAR', 'pbb_bayar'])
            )
            + '</td>';
        if (showDetail) {
            html += '<td class="pbb-left">'
                + pbbEscapeHtml(
                    pbbValue(
                        pbbPick(item, [
                            'ALAMAT_PEMBELI',
                            'alamat_pembeli'
                        ])
                    )
                )
                + '</td>';
            var noPpjb = pbbValue(
                pbbPick(item, ['NO_PPJB', 'no_ppjb'])
            );
            var tglPpjb = pbbFormatDate(
                pbbPick(item, ['TGL_PPJB', 'tgl_ppjb'])
            );
            html += '<td class="pbb-left">';
            html += pbbEscapeHtml(noPpjb);
            if (
                noPpjb !== '-'
                || tglPpjb !== '-'
            ) {
                html += '<br>';
                html += pbbEscapeHtml(tglPpjb);
            }
            html += '</td>';
            html += '<td class="pbb-center">'
                + pbbEscapeHtml(
                    pbbValue(
                        pbbPick(item, ['NO_KTP', 'no_ktp'])
                    )
                )
                + '</td>';
            html += '<td class="pbb-center">'
                + pbbEscapeHtml(
                    pbbValue(
                        pbbPick(item, ['NPWP', 'npwp'])
                    )
                )
                + '</td>';
        }
        html += '</tr>';
        return html;
    }
    function renderBelumPbbReport(rows, headerHtml) {
        var groups = groupPbbBySector(rows);
        var html = '';
        html += headerHtml;
        html += '<div class="pbb-table-wrap">';
        html += '<table ';
        html += 'class="pbb-report-table pbb-belum-table">';
        html += '<thead><tr>';
        html += '<th style="width:42px;">No.</th>';
        html += '<th style="width:105px;">Blok/Nomor</th>';
        html += '<th style="width:210px;">Nama<br>Wajib Pajak</th>';
        html += '<th style="width:125px;">No KTP</th>';
        html += '<th style="width:120px;">NPWP</th>';
        html += '<th style="width:300px;">';
        html += 'Alamat Pembeli<br>Sesuai KTP';
        html += '</th>';
        html += '<th style="width:120px;">No HP</th>';
        html += '<th style="width:85px;">Luas<br>Tanah</th>';
        html += '<th style="width:85px;">Luas<br>Bgn</th>';
        html += '</tr></thead><tbody>';
        if (rows.length < 1) {
            html += '<tr>';
            html += '<td colspan="9" class="pbb-empty">';
            html += 'Data tidak ditemukan.';
            html += '</td></tr>';
        } else {
            var no = 1;
            $.each(groups, function (groupIndex, group) {
                html += '<tr class="pbb-sector-row">';
                html += '<td colspan="9">';
                html += 'Sektor/Cluster : ';
                html += pbbEscapeHtml(group.sektor);
                html += '</td></tr>';
                $.each(group.rows, function (index, item) {
                    html += '<tr>';
                    html += '<td class="pbb-center">'
                        + no++
                        + '</td>';
                    html += '<td class="pbb-center">'
                        + pbbEscapeHtml(
                            pbbValue(
                                pbbPick(item, [
                                    'BLOK_NOMOR',
                                    'blok_nomor'
                                ])
                            )
                        )
                        + '</td>';
                    html += '<td class="pbb-left">'
                        + pbbEscapeHtml(
                            pbbValue(
                                pbbPick(item, [
                                    'NAMA_PPJB',
                                    'nama_ppjb'
                                ])
                            )
                        )
                        + '</td>';
                    html += '<td class="pbb-center">'
                        + pbbEscapeHtml(
                            pbbValue(
                                pbbPick(item, [
                                    'NO_KTP',
                                    'no_ktp'
                                ])
                            )
                        )
                        + '</td>';
                    html += '<td class="pbb-center">'
                        + pbbEscapeHtml(
                            pbbValue(
                                pbbPick(item, ['NPWP', 'npwp'])
                            )
                        )
                        + '</td>';
                    html += '<td class="pbb-left">'
                        + pbbEscapeHtml(
                            pbbValue(
                                pbbPick(item, [
                                    'ALAMAT_PEMBELI',
                                    'alamat_pembeli'
                                ])
                            )
                        )
                        + '</td>';
                    html += '<td class="pbb-center">'
                        + pbbEscapeHtml(
                            pbbValue(
                                pbbPick(item, ['NO_HP', 'no_hp'])
                            )
                        )
                        + '</td>';
                    html += '<td class="pbb-number">'
                        + pbbFormatNumber(
                            pbbPick(item, [
                                'LUAS_TANAH',
                                'luas_tanah'
                            ])
                        )
                        + '</td>';
                    html += '<td class="pbb-number">'
                        + pbbFormatNumber(
                            pbbPick(item, [
                                'LUAS_BANGUNAN',
                                'luas_bangunan'
                            ])
                        )
                        + '</td>';
                    html += '</tr>';
                });
            });
        }
        html += '</tbody></table></div>';
        return html;
    }
    function renderPbbReport(rows) {
        rows = Array.isArray(rows) ? rows : [];
        var first = rows.length > 0 ? rows[0] : {};
        var company = pbbCompanyName(first);
        var tahun = String($('#pbbTahunAwal').val())
            + ' s/d '
            + String($('#pbbTahunAkhir').val());
        var period = pbbFormatDate(
            $('#pbbTglAwal').val()
        )
            + ' s/d '
            + pbbFormatDate(
                $('#pbbTglAkhir').val()
            );
var showDetail = $('#pbbTampilNamaAlamatWp')
            .is(':checked');
        var belumPbb = $('#pbbBelumAdaPbb')
            .is(':checked');
        var semuaSektor = (
            String($('#pbbSektor').val() || '*')
                .toUpperCase()
            === '*'
        );
        var reportTitle = belumPbb
            ? 'DAFTAR BLOK BELUM ADA PBB'
            : 'DAFTAR REKAPITULASI PBB';
        var blok = String(
            $('#pbbBlokAwal').val() || 'A'
        ).toUpperCase()
            + ' s/d '
            + String(
                $('#pbbBlokAkhir').val() || 'Z'
            ).toUpperCase();
        var sektorTampil = String(
            $('#pbbSektorEntry').text() || 'Semua Sektor'
        ).trim();
        var headerHtml = '';
        headerHtml += '<div class="pbb-report-header">';
        headerHtml += '<div class="pbb-company">';
        headerHtml += pbbEscapeHtml(company);
        headerHtml += '</div>';
        headerHtml += '<div class="pbb-title-wrap">';
        headerHtml += '<h2 class="pbb-report-title">';
        headerHtml += pbbEscapeHtml(reportTitle);
        headerHtml += '</h2>';
        headerHtml += '</div>';
        headerHtml += '<div class="pbb-report-date">';
        headerHtml += 'BLOK: '
            + pbbEscapeHtml(blok);
        headerHtml += '<br>Tahun PBB: '
            + pbbEscapeHtml(tahun);
        if (!belumPbb) {
            headerHtml += '<br>Tgl. Input: '
                + pbbEscapeHtml(period);
        }
        headerHtml += '</div>';
        headerHtml += '</div>';
        headerHtml += '<div class="pbb-report-subtitle">';
        headerHtml += 'Sektor/Cluster: <strong>';
        headerHtml += pbbEscapeHtml(sektorTampil);
        headerHtml += '</strong>';
        headerHtml += '<span class="pbb-live-badge">';
        headerHtml += 'Live data';
        headerHtml += '</span>';
        headerHtml += '</div>';
        var html = '<div class="pbb-paper">';
        if (belumPbb) {
            html += renderBelumPbbReport(
                rows,
                headerHtml
            );
            html += '</div>';
            $('#pbbMainDisplay').html(html);
            return;
        }
        html += headerHtml;
        html += '<div class="pbb-table-wrap">';
        html += '<table class="pbb-report-table';
        html += showDetail ? ' pbb-detail-table' : '';
        html += '">';
        html += renderPbbStandardHeader(showDetail);
        html += '<tbody>';
        if (rows.length < 1) {
            var emptyColspan = showDetail ? 14 : 10;
            html += '<tr><td colspan="'
                + emptyColspan
                + '" class="pbb-empty">';
            html += 'Data tidak ditemukan.';
            html += '</td></tr>';
        } else {
            var groups = groupPbbBySector(rows);
            var runningNo = 1;
            var totalPrefixColspan = 5;
            $.each(groups, function (groupIndex, group) {
                html += '<tr class="pbb-sector-row">';
                html += '<td colspan="'
                    + (showDetail ? 14 : 10)
                    + '">';
                html += 'Sektor/Cluster : ';
                html += pbbEscapeHtml(group.sektor);
                html += '</td></tr>';
                $.each(group.rows, function (index, item) {
                    html += renderPbbStandardRow(
                        item,
                        runningNo++,
                        showDetail
                    );
                });
                var sectorTotal = calculatePbbTotal(
                    group.rows
                );
                html += '<tr class="pbb-total-row">';
                html += '<td colspan="'
                    + totalPrefixColspan
                    + '" class="pbb-left">';
                html += 'Total Cluster '
                    + pbbEscapeHtml(group.sektor)
                    + ' :';
                html += '</td>';
                html += renderPbbTotalCells(sectorTotal);
                if (showDetail) {
                    html += '<td colspan="4"></td>';
                }
                html += '</tr>';
            });
            if (semuaSektor && groups.length > 0) {
                var grand = calculatePbbTotal(rows);
                html += '<tr class="pbb-grand-total-row">';
                html += '<td colspan="'
                    + totalPrefixColspan
                    + '" class="pbb-left">';
                html += 'GRAND TOTAL :';
                html += '</td>';
                html += renderPbbTotalCells(grand);
                if (showDetail) {
                    html += '<td colspan="4"></td>';
                }
                html += '</tr>';
            }
        }
        html += '</tbody></table></div>';
        html += '</div>';
        $('#pbbMainDisplay').html(html);
    }
    function printPbbReport() {
        if (
            $('#pbbPrintButton').prop('disabled')
            || !Array.isArray(lastPbbRows)
            || !$('#pbbMainDisplay .pbb-report-table').length
        ) {
            return;
        }
        printPbbInNativeDialog();
    }
    function printPbbInNativeDialog() {
        var reportHtml = $('#pbbMainDisplay').html();
        if (!reportHtml) {
            return;
        }
        $('#pbbNativePrintFrame').remove();
        var frame = document.createElement('iframe');
        frame.id = 'pbbNativePrintFrame';
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
        var frameDocument = frame.contentDocument
            || frameWindow.document;
        var printCss = `
html, body {
    width: 100%;
    min-width: 0;
    max-width: none;
    margin: 0;
    padding: 0;
    background: #ffffff;
    color: #000000;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
}
*, *::before, *::after {
    box-sizing: border-box;
}
.pbb-paper {
    width: calc(100% - 16mm);
    max-width: calc(100% - 16mm);
    margin: 8mm auto 0;
    padding: 0;
    background: #ffffff;
    color: #000000;
}
.pbb-report-header {
    display: grid;
    width: 100%;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr) minmax(0, 1fr);
    gap: 12px;
    align-items: center;
    margin: 0 auto 6px;
    padding: 10px 12px;
    border: 1px solid #777;
    border-radius: 0;
    background: #ffffff;
    color: #000000;
}
.pbb-company {
    min-width: 0;
    color: #000000;
    font-size: 10.5px;
    font-weight: 700;
    line-height: 1.45;
}
.pbb-title-wrap {
    min-width: 0;
    text-align: center;
}
.pbb-report-title {
    display: block;
    min-width: 0;
    margin: 0;
    padding: 0;
    border: 0;
    color: #000000;
    font-family: Cambria, Georgia, "Times New Roman", serif;
    font-size: 18px;
    font-weight: 700;
    line-height: 1.25;
}
.pbb-report-date {
    color: #000000;
    text-align: right;
    font-size: 10.5px;
    font-weight: 600;
    line-height: 1.45;
}
.pbb-report-subtitle {
    display: flex;
    width: 100%;
    min-height: 38px;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 0 auto 8px;
    padding: 7px 10px;
    border: 1px solid #b8c2cc;
    border-radius: 0;
    background: #ffffff;
    color: #000000;
    font-size: 10.5px;
    line-height: 1.45;
}
.pbb-report-subtitle strong {
    min-width: 0;
    flex: 1;
    color: #000000;
    font-weight: 700;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.pbb-live-badge {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border: 1px solid #9ca3af;
    border-radius: 999px;
    background: #ffffff;
    color: #000000;
    font-size: 8.5px;
    font-weight: 700;
    text-transform: uppercase;
}
.pbb-live-badge::before {
    content: "";
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #000000;
}
.pbb-table-wrap {
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
    padding: 0;
    overflow: visible;
    border: 0;
    background: #ffffff;
}
.pbb-report-table, .pbb-report-table.pbb-detail-table, .pbb-report-table.pbb-belum-table {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    margin: 0 auto;
    table-layout: fixed;
    border-collapse: collapse;
    border-spacing: 0;
    border: 1px solid #000000;
    background: #ffffff;
    color: #000000;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10.5px;
    line-height: 1.35;
}
.pbb-report-table.pbb-detail-table {
    font-size: 8.5px;
    line-height: 1.25;
}
.pbb-report-table thead {
    display: table-header-group;
}
.pbb-report-table tbody {
    display: table-row-group;
}
.pbb-report-table tr {
    break-inside: avoid;
    page-break-inside: avoid;
}
.pbb-report-table th, .pbb-report-table td {
    position: static;
    padding: 5px 6px;
    border: 1px solid #000000;
    background: #ffffff;
    color: #000000;
    vertical-align: middle;
    overflow: visible;
    overflow-wrap: anywhere;
}
.pbb-report-table th {
    text-align: center;
    font-size: 10px;
    font-weight: 700;
    line-height: 1.25;
}
.pbb-report-table.pbb-detail-table th {
    padding: 4px;
    font-size: 8.2px;
    line-height: 1.2;
}
.pbb-sector-row td, .pbb-total-row td, .pbb-grand-total-row td {
    background: #ffffff;
    color: #000000;
    font-weight: 700;
}
.pbb-sector-row td {
    text-align: left;
}
.pbb-total-row td {
    border-top: 1px solid #000000;
}
.pbb-grand-total-row td {
    border-top: 2px double #000000;
    border-bottom: 2px double #000000;
}
.pbb-center {
    text-align: center;
}
.pbb-left {
    text-align: left;
}
.pbb-number {
    text-align: right;
    font-variant-numeric: tabular-nums;
}
.pbb-empty {
    height: 100px;
    text-align: center;
}
        `;
        frameDocument.open();
        frameDocument.write(
            '<!DOCTYPE html>'
            + '<html>'
            + '<head>'
            + '<meta charset="utf-8">'
            + '<title>Daftar Rekapitulasi PBB</title>'
            + '<style>'
            + printCss
            + '</style>'
            + '</head>'
            + '<body>'
            + reportHtml
            + '</body>'
            + '</html>'
        );
        frameDocument.close();
        var doPrint = function () {
            try {
                frameWindow.focus();
                frameWindow.print();
            } finally {
                window.setTimeout(function () {
                    $('#pbbNativePrintFrame').remove();
                }, 1200);
            }
        };
        window.setTimeout(doPrint, 150);
    }</script>
@endsection