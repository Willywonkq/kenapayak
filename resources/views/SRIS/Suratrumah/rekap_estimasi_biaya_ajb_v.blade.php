@extends('layouts.template')

{{-- VIEW VERSION V1-20260903-DESKTOP-LAYOUT --}}
{{-- Tata letak filter dan kolom laporan mengikuti tampilan desktop SRIS. --}}
{{-- Hasil laporan dikelompokkan per cluster, satu tabel untuk setiap cluster. --}}

@section('content')
<style>
.reba-page,
.reba-page * {
    box-sizing: border-box;
}

.reba-page {
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

.reba-toolbar {
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

.reba-toolbar::before {
    content: "\25C8";
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

.reba-toolbar::after {
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

.reba-toolbar-title {
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

.reba-unit-badge {
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

.reba-filter {
    position: relative;
    z-index: 30;
    padding: 20px;
    overflow: visible;
    border: 1px solid #dbe3ef;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
}

.reba-filter::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    border-radius: 24px 0 0 24px;
    background: linear-gradient(180deg, #38bdf8 0%, #2563eb 52%, #1d4ed8 100%);
}

.reba-filter::after {
    content: "Rekap Estimasi Biaya AJB";
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

.reba-filter-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 82px;
    gap: 13px 14px;
    align-items: center;
}

.reba-field {
    display: grid;
    grid-template-columns: 112px minmax(0, 1fr);
    gap: 9px;
    align-items: center;
    min-width: 0;
}

.reba-label {
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

.reba-range {
    display: grid;
    grid-template-columns: minmax(86px, 1fr) 34px minmax(86px, 1fr);
    gap: 7px;
    align-items: center;
}

.reba-separator {
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

.reba-input,
.reba-lookup-display {
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

.reba-input:hover,
.reba-lookup-display:hover {
    border-color: #aebed1;
}

.reba-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
}

@keyframes rebaLokasiPanelIn {
    from { opacity: 0; transform: translateY(-5px) scale(0.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.reba-lookup {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px;
    gap: 8px;
}

.reba-lookup-display {
    display: flex;
    align-items: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.reba-lookup-button {
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
    transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

.reba-lookup-button:hover {
    transform: translateY(-1px);
    background: linear-gradient(145deg, #dbeafe 0%, #bfdbfe 100%);
    box-shadow: 0 7px 15px rgba(37, 99, 235, 0.12);
}

.reba-action-stack {
    position: relative;
    width: 82px;
    height: 42px;
    grid-column: 3;
    grid-row: 1;
    align-self: start;
    justify-self: end;
}

.reba-action {
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

.reba-action-stack .reba-action:first-child {
    position: absolute;
    top: 0;
    right: -20px;
    border-radius: 13px 0 0 13px;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
}

.reba-action-stack .reba-action:first-child:hover {
    transform: translateY(-1px);
    background: linear-gradient(135deg, #2f6ff0 0%, #1e4fc4 100%);
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.30);
}

#rebaPrintButton {
    position: absolute;
    top: 55px;
    right: -20px;
    border-radius: 13px 0 0 13px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(5, 150, 105, 0.22);
}

#rebaPrintButton:hover:not(:disabled) {
    transform: translateY(-1px);
    background: linear-gradient(135deg, #14b88a 0%, #047857 100%);
    box-shadow: 0 12px 24px rgba(5, 150, 105, 0.28);
}

#rebaPrintButton:disabled,
#rebaPrintButton:disabled:hover,
#rebaPrintButton:disabled:focus {
    transform: none;
    border: 1px solid #d5dde7;
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
    color: #94a3b8;
    box-shadow: none;
    cursor: not-allowed;
    opacity: 1;
}

.reba-report-area {
    position: relative;
    z-index: 1;
    margin-top: 18px;
    padding: 20px;
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
}

.reba-report-area::before {
    content: "";
    position: absolute;
    top: 0;
    left: 7%;
    right: 7%;
    height: 3px;
    border-radius: 0 0 999px 999px;
    background: linear-gradient(90deg, transparent, #38bdf8, #2563eb, #6366f1, transparent);
}

#rebaLoading {
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

.reba-paper {
    width: 100%;
    min-height: 0;
    margin: 0;
    padding: 0;
    border: 0;
    background: transparent;
    color: #172033;
}

.reba-initial {
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

.reba-initial-icon {
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

.reba-report-header {
    display: grid;
    grid-template-columns: minmax(150px, 1fr) minmax(260px, 1.4fr) minmax(180px, 1fr);
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

.reba-company {
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 850;
    letter-spacing: 0.04em;
    line-height: 1.35;
}

.reba-title-wrap {
    min-width: 0;
    text-align: center;
}

.reba-report-title {
    display: block;
    margin: 0;
    color: #172033;
    font-family: Cambria, Georgia, "Times New Roman", serif;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.reba-report-center-meta {
    margin-top: 5px;
    color: #475467;
    font-size: 10.5px;
    font-weight: 650;
    line-height: 1.45;
}

.reba-report-date {
    color: #475467;
    text-align: right;
    font-size: 10.5px;
    font-weight: 650;
    line-height: 1.55;
}

.reba-report-subtitle {
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

.reba-subtitle-label {
    text-align: left;
}

.reba-subtitle-value {
    min-width: 0;
    color: #344054;
    text-align: center;
    font-weight: 850;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.reba-live-badge {
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
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.reba-live-badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
}

.reba-table-wrap {
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

.reba-table-wrap::-webkit-scrollbar,
.reba-modal-table-wrap::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.reba-table-wrap::-webkit-scrollbar-track,
.reba-modal-table-wrap::-webkit-scrollbar-track {
    background: #eff3f7;
}

.reba-table-wrap::-webkit-scrollbar-thumb,
.reba-modal-table-wrap::-webkit-scrollbar-thumb {
    border: 2px solid #eff3f7;
    border-radius: 999px;
    background: linear-gradient(180deg, #60a5fa, #2563eb);
}

.reba-report-table {
    width: 1420px;
    min-width: 1420px;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
    color: #344054;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10.5px;
}

.reba-report-table th,
.reba-report-table td {
    padding: 8px 9px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
    overflow-wrap: anywhere;
}

.reba-report-table th {
    position: sticky;
    z-index: 4;
    background: linear-gradient(180deg, #eff6ff 0%, #e5effb 100%);
    color: #344054;
    text-align: center;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-weight: 900;
    line-height: 1.25;
}

.reba-report-table thead tr:first-child th {
    top: 0;
    z-index: 5;
    height: 44px;
}

.reba-report-table thead tr:first-child th[colspan] {
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    color: #3730a3;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.reba-report-table thead tr:nth-child(2) th {
    top: 44px;
    z-index: 4;
    height: 34px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    color: #475467;
    font-size: 9.5px;
}

.reba-report-table thead tr:first-child th[rowspan] {
    z-index: 6;
}

.reba-report-table td {
    height: 44px;
    background: #ffffff;
    color: #344054;
    line-height: 1.38;
}

.reba-report-table tbody tr:nth-child(even):not(.reba-sector-row) td {
    background: #fbfcfe;
}

.reba-report-table tbody tr.reba-data-row:hover td {
    background: #f0f7ff;
}

.reba-report-table tbody tr.reba-data-row:hover td:first-child {
    box-shadow: inset 4px 0 0 #2563eb;
    color: #1d4ed8;
    font-weight: 900;
}

.reba-sector-row td {
    height: auto;
    padding: 8px 10px;
    background: linear-gradient(90deg, #ffffff 0%, #f8fafc 55%, #eff6ff 100%) !important;
    color: #344054;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10px;
    font-weight: 850;
    text-align: left;
}

.reba-center {
    text-align: center;
}

.reba-left {
    text-align: left;
}

.reba-empty {
    height: 130px;
    color: #64748b;
    text-align: center;
}

.reba-number {
    color: #1e3a5f;
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.reba-total-row td {
    background: #eff6ff !important;
    color: #1e3a5f;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-weight: 900;
}

.reba-total-label {
    letter-spacing: 0.18em;
    text-align: right;
}

/* Dipakai bersama oleh modal Cluster dan modal Blok/Nomor. */
.reba-modal {
    position: fixed;
    inset: 0;
    z-index: 1065;
    display: none;
    overflow: auto;
    background: rgba(71, 85, 105, 0.24);
}

.reba-modal.show {
    display: block;
}

.reba-modal-dialog {
    width: calc(100vw - 32px);
    max-width: 800px;
    margin: 24px auto;
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
}

.reba-modal-header {
    display: flex;
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

.reba-modal-close {
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

.reba-modal-body {
    padding: 16px;
    background: #f8fafc;
}

.reba-modal-search {
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

.reba-modal-search:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.reba-modal-table-wrap {
    max-height: 430px;
    overflow: auto;
    border: 1px solid #dbe3ef;
    border-radius: 16px;
    background: #ffffff;
}

.reba-modal-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.reba-modal-table th,
.reba-modal-table td {
    padding: 10px 12px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}

.reba-modal-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: linear-gradient(180deg, #eff6ff 0%, #e7f0fc 100%);
    color: #344054;
    text-align: center;
    font-weight: 850;
}

.reba-modal-table tbody tr {
    cursor: pointer;
}

.reba-modal-table tbody tr:hover td {
    background: #eff6ff;
    color: #1d4ed8;
}

/* =========================================================
   ALERT DATA KOSONG — SAMA DENGAN DAFTAR SERTIFIKAT PECAHAN
   Modal informasi yang tampil saat hasil laporan tidak
   menghasilkan baris data sama sekali.
   ========================================================= */
#rebaNoDataAlertModal .modal-dialog {
    max-width: 380px;
}

#rebaNoDataAlertModal .modal-content {
    border: 0;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
}

#rebaNoDataAlertModal .alert-icon-wrapper {
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

#rebaNoDataAlertModal .alert-title {
    margin-bottom: 8px;
    color: #172033;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 18px;
    font-weight: 700;
}

#rebaNoDataAlertModal .alert-message {
    margin-bottom: 24px;
    color: #475569;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 14px;
}

#rebaNoDataAlertModal .alert-btn-ok {
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

#rebaNoDataAlertModal .alert-btn-ok:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
}

/* =========================================================
   BLOK/NOMOR
   Setiap sisi rentang memiliki tombol lookup sendiri, sama
   seperti tampilan desktop.
   ========================================================= */
.reba-range-lookup {
    display: grid;
    grid-template-columns:
        minmax(70px, 1fr) 42px 34px minmax(70px, 1fr) 42px;
    gap: 7px;
    align-items: center;
}

/* =========================================================
   KELOMPOK CLUSTER PADA LAPORAN
   Seluruh baris tetap berada pada satu tabel. Setiap kali
   cluster berganti, sebuah baris judul disisipkan di atasnya.
   ========================================================= */
.reba-cluster-row td {
    padding: 9px 10px;
    background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 62%, #ffffff 100%) !important;
    border-left: 4px solid #2563eb;
    color: #1e40af;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 11px;
    font-weight: 850;
    text-align: left;
}

.reba-cluster-row .reba-cluster-label {
    margin-right: 7px;
    color: #64748b;
    font-size: 9.5px;
    font-weight: 900;
    letter-spacing: 0.11em;
    text-transform: uppercase;
}

.reba-cluster-row .reba-cluster-count {
    float: right;
    padding: 2px 9px;
    border: 1px solid #bfdbfe;
    border-radius: 999px;
    background: #ffffff;
    color: #1d4ed8;
    font-family: "SFMono-Regular", Consolas, monospace;
    font-size: 9.5px;
    font-weight: 800;
}

/* Modal lookup blok memuat banyak kolom, jadi dibuat lebih lebar. */
.reba-modal-dialog.is-wide {
    max-width: 1180px;
}

.reba-grand-total-row td {
    background: #eff6ff;
    color: #1e40af;
    font-weight: 900;
}

@media screen and (max-width: 719px) {
    html, body {
        min-width: 720px;
    }
    .reba-page {
        min-width: 720px;
    }
}

@media print {
    .reba-toolbar, .reba-filter, #rebaLoading, #rebaClusterModal, #rebaBlokModal,
    #rebaNoDataAlertModal, .modal-backdrop, .main-sidebar, .control-sidebar,
    .main-header, .main-footer, .navbar, .sidebar {
        display: none !important;
    }
    html, body, .wrapper, .content-wrapper, .main-content, .content,
    .page-wrapper, .page-content, .container, .container-fluid,
    .reba-page, .reba-report-area, .reba-paper {
        width: 100% !important;
        min-width: 0 !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        background: #fff !important;
        box-shadow: none !important;
    }
    .reba-table-wrap {
        max-height: none !important;
        overflow: visible !important;
    }
    .reba-report-table {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
    }
    .reba-cluster-row td {
        border-left: 1px solid #000 !important;
        background: #fff !important;
        color: #000 !important;
    }
}
</style>

<!-- MODAL ALERT DATA KOSONG -->
<div class="modal fade" id="rebaNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4" style="text-align: center; padding: 1.5rem;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message" id="rebaNoDataMessage">Data tidak ditemukan.</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    data-dismiss="modal"
                    onclick="hideRebaNoDataAlert()"
                >OK</button>
            </div>
        </div>
    </div>
</div>

<div class="reba-page">
    <div class="reba-toolbar">
        <div class="reba-toolbar-title">
            Rekap Estimasi Biaya AJB
        </div>
        <code class="reba-unit-badge">
            UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}
        </code>
    </div>

    <section class="reba-filter">
        <input
            type="hidden"
            autocomplete="off"
            id="rebaPerusahaan"
            value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}"
        >
        <input
            type="hidden"
            autocomplete="off"
            id="rebaNamaPerusahaan"
            value="{{ session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? '' }}"
        >
        <input type="hidden" id="rebaCluster" value="*" autocomplete="off">

        <div class="reba-filter-grid">
            <div class="reba-field">
                <label class="reba-label" for="rebaBlokAwal">Blok/Nomor</label>
                <div class="reba-range-lookup">
                    <input
                        type="text"
                        id="rebaBlokAwal"
                        class="reba-input"
                        maxlength="30"
                        autocomplete="off"
                    >
                    <button
                        type="button"
                        class="reba-lookup-button"
                        onclick="getRebaBlokModal('rebaBlokAwal')"
                        title="Pilih blok/nomor awal"
                        aria-label="Pilih blok/nomor awal"
                    >
                        <i class="fas fa-binoculars"></i>
                    </button>
                    <span class="reba-separator">s.d</span>
                    <input
                        type="text"
                        id="rebaBlokAkhir"
                        class="reba-input"
                        maxlength="30"
                        autocomplete="off"
                    >
                    <button
                        type="button"
                        class="reba-lookup-button"
                        onclick="getRebaBlokModal('rebaBlokAkhir')"
                        title="Pilih blok/nomor akhir"
                        aria-label="Pilih blok/nomor akhir"
                    >
                        <i class="fas fa-binoculars"></i>
                    </button>
                </div>
            </div>

            <div class="reba-field">
                <span class="reba-label">Cluster</span>
                <div class="reba-lookup">
                    <div id="rebaClusterEntry" class="reba-lookup-display">Semua Cluster</div>
                    <button
                        type="button"
                        class="reba-lookup-button"
                        onclick="getRebaClusterModal()"
                        title="Pilih cluster"
                        aria-label="Pilih cluster"
                    >
                        <i class="fas fa-binoculars"></i>
                    </button>
                </div>
            </div>

            <div class="reba-action-stack">
                <button
                    type="button"
                    class="reba-action"
                    onclick="getRebaData()"
                >
                    Ok
                </button>
                <button
                    type="button"
                    class="reba-action"
                    id="rebaPrintButton"
                    onclick="printRebaReport()"
                    aria-disabled="true"
                    disabled
                >
                    Print
                </button>
            </div>

            <div class="reba-field">
                <label class="reba-label" for="rebaTglAwal">Periode Tgl.</label>
                <div class="reba-range">
                    <input type="date" id="rebaTglAwal" class="reba-input" autocomplete="off">
                    <span class="reba-separator">s.d</span>
                    <input type="date" id="rebaTglAkhir" class="reba-input" autocomplete="off">
                </div>
            </div>
        </div>
    </section>

    <section class="reba-report-area">
        <div id="rebaLoading">
            <i class="fas fa-spinner fa-spin"></i>
            Mengambil data Rekap Estimasi Biaya AJB...
        </div>

        <div id="rebaMainDisplay">
            <div class="reba-paper">
                <div class="reba-initial">
                    <i class="fas fa-table reba-initial-icon" aria-hidden="true"></i>
                    <div>Silahkan isi filter kemudian klik OK</div>
                </div>
            </div>
        </div>
    </section>

    <div id="rebaClusterModal" class="reba-modal" aria-hidden="true">
        <div class="reba-modal-dialog">
            <div class="reba-modal-header">
                <span>Pilih Cluster</span>
                <button
                    type="button"
                    class="reba-modal-close"
                    onclick="toggleRebaClusterModal(false)"
                    aria-label="Tutup"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="reba-modal-body" id="rebaClusterModalContent"></div>
        </div>
    </div>

    <div id="rebaBlokModal" class="reba-modal" aria-hidden="true">
        <div class="reba-modal-dialog is-wide">
            <div class="reba-modal-header">
                <span id="rebaBlokModalTitle">Pilih Blok/Nomor</span>
                <button
                    type="button"
                    class="reba-modal-close"
                    onclick="toggleRebaBlokModal(false)"
                    aria-label="Tutup"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="reba-modal-body" id="rebaBlokModalContent"></div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    var lastRebaRows = null;
    var rebaBlokRows = null;
    var rebaBlokTarget = 'rebaBlokAwal';

    $(document).ready(function () {
        /*
         * Browser memulihkan isi form saat halaman di-refresh, sehingga
         * isian blok dan pilihan cluster dapat terbawa dari kunjungan
         * sebelumnya. Reset dipanggil bertahap karena pemulihan itu dapat
         * terjadi setelah DOMContentLoaded.
         */
        resetRebaInitialState();
        window.setTimeout(resetRebaInitialState, 10);
        window.setTimeout(resetRebaInitialState, 100);

        $('#rebaBlokAwal, #rebaBlokAkhir').on('input', function () {
            $(this).val(String($(this).val() || '').toUpperCase());
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                toggleRebaClusterModal(false);
                toggleRebaBlokModal(false);
            }
        });
    });

    $(window).on('load', function () {
        resetRebaInitialState();
    });

    $(window).on('pageshow', function (event) {
        var pageEvent = event.originalEvent || event;

        if (pageEvent.persisted) {
            resetRebaInitialState();
        }
    });

    /*
     * Mengembalikan seluruh filter dan area laporan ke keadaan awal,
     * seperti saat fitur ini baru dibuka.
     */
    function resetRebaInitialState() {
        $('#rebaBlokAwal').val('');
        $('#rebaBlokAkhir').val('');

        $('#rebaCluster').val('*');
        $('#rebaClusterEntry').text('Semua Cluster');

        toggleRebaClusterModal(false);
        toggleRebaBlokModal(false);

        setRebaDefaultDate();
        resetRebaPrint();

        $('#rebaLoading').hide();
        $('#rebaMainDisplay').html(
            '<div class="reba-paper">'
            + '<div class="reba-initial">'
            + '<i class="fas fa-table reba-initial-icon" aria-hidden="true"></i>'
            + '<div>Silahkan isi filter kemudian klik OK</div>'
            + '</div></div>'
        );
    }

    function setRebaDefaultDate() {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var today = year + '-' + month + '-' + day;

        $('#rebaTglAwal').val(today);
        $('#rebaTglAkhir').val(today);
    }

    /*
     * Blok/Nomor boleh dikosongkan. Desktop memperlakukan isian kosong
     * sebagai rentang penuh, dan header laporannya menulis A s/d ZZ.
     */
    function rebaBlokAwal() {
        return String($('#rebaBlokAwal').val() || 'A').toUpperCase().trim() || 'A';
    }

    function rebaBlokAkhir() {
        return String($('#rebaBlokAkhir').val() || 'ZZ').toUpperCase().trim() || 'ZZ';
    }

    function setRebaPrintEnabled(enabled) {
        var disabled = enabled !== true;
        $('#rebaPrintButton')
            .prop('disabled', disabled)
            .attr('aria-disabled', disabled ? 'true' : 'false');
    }

    function resetRebaPrint() {
        lastRebaRows = null;
        setRebaPrintEnabled(false);
        hideRebaNoDataAlert();
    }

    /*
     * Alert data kosong, mengikuti Daftar Sertifikat Pecahan.
     */
    function showRebaNoDataAlert(message) {
        var $modal = $('#rebaNoDataAlertModal');

        $('#rebaNoDataMessage').text(message || 'Data tidak ditemukan......!');

        if (typeof $modal.modal === 'function') {
            $modal.modal('show');
            return;
        }

        if (!$('.reba-nodata-backdrop').length) {
            $('<div class="modal-backdrop fade show reba-nodata-backdrop"></div>')
                .appendTo(document.body);
        }

        $modal
            .addClass('show')
            .css('display', 'block')
            .attr('aria-hidden', 'false');
    }

    function hideRebaNoDataAlert() {
        var $modal = $('#rebaNoDataAlertModal');

        if (typeof $modal.modal === 'function') {
            $modal.modal('hide');
            return;
        }

        $modal
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true');

        $('.reba-nodata-backdrop').remove();
    }

    function rebaEscapeHtml(value) {
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

    function rebaEscapeJs(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r?\n/g, ' ');
    }

    function rebaValue(value) {
        return value === null || value === undefined || value === ''
            ? '-'
            : value;
    }

    function rebaPick(item, keys) {
        item = item || {};

        for (var i = 0; i < keys.length; i++) {
            var value = item[keys[i]];
            if (value !== null && value !== undefined && value !== '') {
                return value;
            }
        }

        return null;
    }

    function rebaFormatDate(value) {
        if (!value) {
            return '-';
        }

        var text = String(value);
        var match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);

        if (match) {
            return match[3] + '-' + match[2] + '-' + match[1];
        }

        match = text.match(/^(\d{4})(\d{2})(\d{2})$/);

        if (match) {
            return match[3] + '-' + match[2] + '-' + match[1];
        }

        return text;
    }

    function rebaNumber(value) {
        var number = Number(value);
        return isFinite(number) ? number : 0;
    }

    /* Kolom rupiah memakai pemisah ribuan tanpa desimal, seperti desktop. */
    function rebaFormatCurrency(value) {
        if (value === null || value === undefined || String(value).trim() === '') {
            return '';
        }

        var number = Number(value);

        if (!isFinite(number)) {
            return rebaEscapeHtml(value);
        }

        return number.toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    /* Nama PT untuk header laporan, sama dengan fitur lain. */
    function rebaExtractCompanyName(value) {
        var raw = String(value || '').replace(/\u00a0/g, ' ');
        var locationMatch = raw.match(/Lokasi\s*:[^\r\n|]*?-\s*([^\r\n|]+)/i);
        var companyMatch = raw.match(/\b((?:PT\.?|CV\.?|PD\.?|PERUM)\s+[^\r\n|]+)/i);
        var name = locationMatch ? locationMatch[1] : (companyMatch ? companyMatch[1] : '');

        return String(name || '')
            .replace(/\s+(?:Hi\s*,|Logout\b|Keluar\b|LAND DOCUMENT\b|UNIT\s+[A-Z0-9_-]+\b).*$/i, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function rebaCompanyNameFromLayout() {
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
                var name = rebaExtractCompanyName(candidates[j]);
                if (name) {
                    return name;
                }
            }
        }

        return '';
    }

    function rebaCompanyName(first) {
        var unit = String($('#rebaPerusahaan').val() || '').trim().toUpperCase();
        var rowName = rebaPick(first || {}, [
            'NAMA_PT', 'nama_pt',
            'ATAS_NAMA_PT', 'atas_nama_pt',
            'NAMA_PERUSAHAAN', 'nama_perusahaan'
        ]);
        var sessionName = String($('#rebaNamaPerusahaan').val() || '').trim();
        var cacheKey = 'sris.report-company.' + unit;
        var cachedName = '';

        try {
            cachedName = localStorage.getItem(cacheKey) || '';
        } catch (error) {
            cachedName = '';
        }

        var company = rebaExtractCompanyName(rowName)
            || String(rowName || '').trim()
            || rebaCompanyNameFromLayout()
            || rebaExtractCompanyName(sessionName)
            || sessionName
            || cachedName
            || unit
            || '-';

        if (company && company !== '-' && company.toUpperCase() !== unit) {
            try {
                localStorage.setItem(cacheKey, company);
            } catch (error) {
                // Browser dapat menolak storage; nama tetap dipakai saat ini.
            }
        }

        return company;
    }

    /* ==============================================
       CLUSTER
       ============================================== */

    function toggleRebaClusterModal(show) {
        $('#rebaClusterModal')
            .toggleClass('show', show === true)
            .attr('aria-hidden', show === true ? 'false' : 'true');
    }

    function addRebaCluster(kode, deskripsi) {
        $('#rebaCluster').val(kode || '*');
        $('#rebaClusterEntry').text(deskripsi || 'Semua Cluster');
        toggleRebaClusterModal(false);
        resetRebaPrint();
    }

    function filterRebaCluster(keyword) {
        var search = String(keyword || '').toLowerCase().trim();

        $('#rebaClusterModal .reba-modal-table tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(search) !== -1);
        });
    }

    function getRebaClusterModal() {
        var perusahaan = String($('#rebaPerusahaan').val() || '').trim();

        if (!perusahaan) {
            alert('Unit/perusahaan belum tersedia.');
            return;
        }

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_cluster',
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
                html += '<input type="text" class="reba-modal-search" ';
                html += 'placeholder="Cari cluster..." ';
                html += 'onkeyup="filterRebaCluster(this.value)">';
                html += '<div class="reba-modal-table-wrap">';
                html += '<table class="reba-modal-table"><thead><tr>';
                html += '<th>Kode</th><th>Deskripsi</th><th>Perusahaan</th>';
                html += '</tr></thead><tbody>';

                html += '<tr onclick="addRebaCluster(\'*\', \'Semua Cluster\')">';
                html += '<td>*</td><td>Semua Cluster</td>';
                html += '<td>' + rebaEscapeHtml(perusahaan) + '</td></tr>';

                $.each(rows, function (index, item) {
                    var kode = rebaPick(item, [
                        'KD_CLUSTER', 'kd_cluster', 'KD_SEKTOR', 'kd_sektor'
                    ]) || '';
                    var deskripsi = rebaPick(item, ['DESKRIPSI', 'deskripsi']) || kode;
                    var unit = rebaPick(item, [
                        'KD_PERUSAHAAN', 'kd_perusahaan'
                    ]) || perusahaan;

                    html += '<tr onclick="addRebaCluster(\''
                        + rebaEscapeJs(kode) + '\', \''
                        + rebaEscapeJs(deskripsi) + '\')">';
                    html += '<td>' + rebaEscapeHtml(kode) + '</td>';
                    html += '<td>' + rebaEscapeHtml(deskripsi) + '</td>';
                    html += '<td>' + rebaEscapeHtml(unit) + '</td></tr>';
                });

                if (rows.length < 1) {
                    html += '<tr><td colspan="3" ';
                    html += 'style="padding:22px;text-align:center;">';
                    html += 'Data cluster tidak ditemukan.</td></tr>';
                }

                html += '</tbody></table></div>';

                $('#rebaClusterModalContent').html(html);
                toggleRebaClusterModal(true);
                $('#rebaClusterModal .reba-modal-search').trigger('focus');
            },
            error: function (xhr) {
                var message = 'Gagal mengambil data cluster.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += ' ' + xhr.responseJSON.message;
                }
                alert(message);
            }
        });
    }

    /* ==============================================
       BLOK/NOMOR
       ============================================== */

    function toggleRebaBlokModal(show) {
        $('#rebaBlokModal')
            .toggleClass('show', show === true)
            .attr('aria-hidden', show === true ? 'false' : 'true');
    }

    function addRebaBlok(blokNomor) {
        $('#' + rebaBlokTarget).val(String(blokNomor || '').toUpperCase());
        toggleRebaBlokModal(false);
        resetRebaPrint();
    }

    function filterRebaBlok(keyword) {
        var search = String(keyword || '').toLowerCase().trim();

        $('#rebaBlokModal .reba-modal-table tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(search) !== -1);
        });
    }

    /*
     * Daftar blok dipakai oleh kedua sisi rentang, jadi hasilnya disimpan
     * agar pembukaan berikutnya tidak memanggil server lagi.
     */
    function getRebaBlokModal(target) {
        var perusahaan = String($('#rebaPerusahaan').val() || '').trim();

        rebaBlokTarget = target === 'rebaBlokAkhir'
            ? 'rebaBlokAkhir'
            : 'rebaBlokAwal';

        $('#rebaBlokModalTitle').text(
            rebaBlokTarget === 'rebaBlokAkhir'
                ? 'Pilih Blok/Nomor Akhir'
                : 'Pilih Blok/Nomor Awal'
        );

        if (!perusahaan) {
            alert('Unit/perusahaan belum tersedia.');
            return;
        }

        if (Array.isArray(rebaBlokRows)) {
            renderRebaBlokModal(rebaBlokRows);
            return;
        }

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_blok',
            dataType: 'json',
            timeout: 120000,
            headers: { 'Accept': 'application/json' },
            data: {
                _token: '{{ csrf_token() }}',
                perusahaan: perusahaan
            },
            success: function (response) {
                rebaBlokRows = Array.isArray(response)
                    ? response
                    : (response && Array.isArray(response.data) ? response.data : []);

                renderRebaBlokModal(rebaBlokRows);
            },
            error: function (xhr) {
                var message = 'Gagal mengambil data blok/nomor.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += ' ' + xhr.responseJSON.message;
                }
                alert(message);
            }
        });
    }

    /*
     * Kolom lookup mengikuti tampilan Search pada desktop: Blok Nomor,
     * Nama Pembeli, No Virtual Acc, No Uang Muka, Tgl Uang Muka, Tipe,
     * dan Lokasi, ditambah Cluster yang juga tersedia pada query.
     */
    var REBA_BLOK_COLUMNS = [
        { judul: 'Blok Nomor', keys: ['BLOK_NOMOR', 'blok_nomor'] },
        { judul: 'Nama Pembeli', keys: ['NAMA_PEMBELI', 'nama_pembeli'] },
        { judul: 'No Virtual Acc', keys: ['NO_VIRTUAL_ACC', 'no_virtual_acc'] },
        { judul: 'No Uang Muka', keys: ['NO_PPJB', 'no_ppjb'] },
        { judul: 'Tgl Uang Muka', keys: ['TGL_PPJB', 'tgl_ppjb'], tanggal: true },
        { judul: 'Tipe', keys: ['TIPE', 'tipe'] },
        { judul: 'Lokasi', keys: ['LOKASI', 'lokasi'] },
        { judul: 'Cluster', keys: ['NM_CLUSTER', 'nm_cluster'] }
    ];

    function renderRebaBlokModal(rows) {
        rows = Array.isArray(rows) ? rows : [];

        var html = '';
        html += '<input type="text" class="reba-modal-search" ';
        html += 'placeholder="Cari blok, nomor, nama pembeli, tipe, lokasi, atau cluster..." ';
        html += 'onkeyup="filterRebaBlok(this.value)">';
        html += '<div class="reba-modal-table-wrap">';
        html += '<table class="reba-modal-table" ';
        html += 'style="min-width:1080px;"><thead><tr>';

        $.each(REBA_BLOK_COLUMNS, function (index, kolom) {
            html += '<th>' + rebaEscapeHtml(kolom.judul) + '</th>';
        });

        html += '</tr></thead><tbody>';

        $.each(rows, function (index, item) {
            var blokNomor = rebaPick(item, ['BLOK_NOMOR', 'blok_nomor']) || '';

            html += '<tr onclick="addRebaBlok(\''
                + rebaEscapeJs(blokNomor) + '\')">';

            $.each(REBA_BLOK_COLUMNS, function (posisi, kolom) {
                var isi = rebaPick(item, kolom.keys);

                html += '<td>' + rebaEscapeHtml(
                    kolom.tanggal ? rebaFormatDate(isi) : rebaValue(isi)
                ) + '</td>';
            });

            html += '</tr>';
        });

        if (rows.length < 1) {
            html += '<tr><td colspan="' + REBA_BLOK_COLUMNS.length + '" ';
            html += 'style="padding:22px;text-align:center;">';
            html += 'Data blok/nomor tidak ditemukan.</td></tr>';
        }

        html += '</tbody></table></div>';

        $('#rebaBlokModalContent').html(html);
        toggleRebaBlokModal(true);
        $('#rebaBlokModal .reba-modal-search').trigger('focus');
    }

    /* ==============================================
       DATA LAPORAN
       ============================================== */

    function validateRebaFilter() {
        if (!$('#rebaTglAwal').val() || !$('#rebaTglAkhir').val()) {
            alert('Periode tanggal wajib diisi.');
            return false;
        }

        if ($('#rebaTglAwal').val() > $('#rebaTglAkhir').val()) {
            alert('Tanggal awal tidak boleh melebihi tanggal akhir.');
            return false;
        }

        if (rebaBlokAwal() > rebaBlokAkhir()) {
            alert('Blok/Nomor awal tidak boleh melebihi blok/nomor akhir.');
            return false;
        }

        return true;
    }

    function getRebaFilterData() {
        var hariIni = new Date();
        var tglCadangan = hariIni.getFullYear()
            + '-' + String(hariIni.getMonth() + 1).padStart(2, '0')
            + '-' + String(hariIni.getDate()).padStart(2, '0');

        return {
            _token: '{{ csrf_token() }}',
            blok_awal: rebaBlokAwal(),
            blok_akhir: rebaBlokAkhir(),
            tgl_awal: $('#rebaTglAwal').val() || tglCadangan,
            tgl_akhir: $('#rebaTglAkhir').val() || tglCadangan,
            perusahaan: $('#rebaPerusahaan').val(),
            cluster: $('#rebaCluster').val() || '*'
        };
    }

    function getRebaData() {
        if (!validateRebaFilter()) {
            return;
        }

        resetRebaPrint();
        $('#rebaLoading').show();

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_data',
            dataType: 'json',
            timeout: 120000,
            headers: { 'Accept': 'application/json' },
            data: getRebaFilterData(),
            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (response && Array.isArray(response.data) ? response.data : []);

                lastRebaRows = rows;

                if (rows.length === 0) {
                    showRebaNoDataAlert('Data tidak ditemukan......!');
                }

                renderRebaReport(rows);
                setRebaPrintEnabled(rows.length > 0);
            },
            error: function (xhr, textStatus, errorThrown) {
                resetRebaPrint();

                var detail = '';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    detail = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    detail = String(xhr.responseText)
                        .replace(/<[^>]+>/g, ' ')
                        .replace(/\s+/g, ' ')
                        .trim()
                        .substring(0, 900);
                } else {
                    detail = String(errorThrown || textStatus || '');
                }

                $('#rebaMainDisplay').html(
                    '<div class="reba-paper">'
                    + '<div style="padding:16px;color:#a00;">'
                    + 'Gagal mengambil data Rekap Estimasi Biaya AJB. '
                    + rebaEscapeHtml(detail)
                    + '</div></div>'
                );
            },
            complete: function () {
                $('#rebaLoading').hide();
            }
        });
    }

    /*
     * Mengelompokkan baris per cluster dengan tetap menjaga urutan yang
     * dikirim server. Baris tanpa cluster dikumpulkan pada satu kelompok
     * sendiri supaya tidak hilang dari laporan.
     */
    function groupRebaRows(rows) {
        var groups = [];
        var index = {};

        $.each(rows, function (position, item) {
            item = item || {};

            var nama = String(
                rebaPick(item, ['NM_CLUSTER', 'nm_cluster'])
                || rebaPick(item, ['KD_SEKTOR', 'kd_sektor'])
                || ''
            ).trim();

            if (nama === '') {
                nama = '(Tanpa Cluster)';
            }

            if (!Object.prototype.hasOwnProperty.call(index, nama)) {
                index[nama] = groups.length;
                groups.push({ nama: nama, rows: [] });
            }

            groups[index[nama]].rows.push(item);
        });

        return groups;
    }

    /*
     * Tabel dibuat selebar area laporan, dengan batas minimum supaya
     * kesembilan kolom tetap terbaca pada layar sempit.
     */
    function rebaTableStyle() {
        return 'style="width:100%;min-width:1180px;"';
    }

    function rebaColgroup() {
        var html = '<colgroup>';
        html += '<col style="width:46px">';
        html += '<col style="width:230px">';
        html += '<col style="width:96px">';
        html += '<col style="width:200px">';
        html += '<col style="width:96px">';
        html += '<col style="width:96px">';
        html += '<col style="width:210px">';
        html += '<col style="width:103px">';
        html += '<col style="width:103px">';
        html += '</colgroup>';

        return html;
    }

    function renderRebaReport(rows) {
        rows = Array.isArray(rows) ? rows : [];

        var first = rows.length > 0 ? rows[0] : {};
        var company = rebaCompanyName(first);

        var blok = rebaBlokAwal() + ' s/d ' + rebaBlokAkhir();

        var periode = rebaFormatDate($('#rebaTglAwal').val())
            + ' s/d '
            + rebaFormatDate($('#rebaTglAkhir').val());

        var clusterTampil = String(
            $('#rebaClusterEntry').text() || 'Semua Cluster'
        ).trim();

        var now = new Date();
        var today = String(now.getDate()).padStart(2, '0')
            + '-' + String(now.getMonth() + 1).padStart(2, '0')
            + '-' + now.getFullYear();

        var groups = groupRebaRows(rows);
        var totalDevSemua = 0;
        var totalNotarisSemua = 0;

        var html = '';

        html += '<div class="reba-paper">';

        html += '<div class="reba-report-header">';
        html += '<div class="reba-company">' + rebaEscapeHtml(company) + '</div>';
        html += '<div class="reba-title-wrap">';
        html += '<h2 class="reba-report-title">Rekap Estimasi Biaya AJB</h2>';
        html += '<div class="reba-report-center-meta">';
        html += 'BLOK : ' + rebaEscapeHtml(blok);
        html += '<br>Periode Tanggal : ' + rebaEscapeHtml(periode);
        html += '</div></div>';
        html += '<div class="reba-report-date">';
        html += 'Tanggal : ' + rebaEscapeHtml(today);
        html += '</div></div>';

        html += '<div class="reba-report-subtitle">';
        html += '<span class="reba-subtitle-label">Cluster:</span>';
        html += '<strong class="reba-subtitle-value">'
            + rebaEscapeHtml(clusterTampil) + '</strong>';
        html += '<span class="reba-live-badge">Live data</span>';
        html += '</div>';

        html += '<div class="reba-table-wrap">';
        html += '<table class="reba-report-table" ' + rebaTableStyle() + '>';
        html += rebaColgroup();
        html += rebaTableHead();
        html += '<tbody>';

        if (groups.length < 1) {
            html += '<tr><td colspan="9" class="reba-empty">';
            html += 'Data tidak ditemukan.';
            html += '</td></tr>';
        }

        /*
         * Seluruh baris berada pada satu tabel. Setiap kali cluster
         * berganti, sebuah baris judul disisipkan di atasnya, dan nomor
         * urut dimulai lagi dari satu seperti tampilan desktop.
         */
        $.each(groups, function (posisi, group) {
            var totalDev = 0;
            var totalNotaris = 0;

            html += '<tr class="reba-cluster-row"><td colspan="9">';
            html += '<span class="reba-cluster-label">Cluster :</span>';
            html += rebaEscapeHtml(group.nama);
            html += '<span class="reba-cluster-count">'
                + group.rows.length + ' data</span>';
            html += '</td></tr>';

            $.each(group.rows, function (index, item) {
                var dev = rebaNumber(rebaPick(item, ['TOTAL_DEV', 'total_dev']));
                var notaris = rebaNumber(
                    rebaPick(item, ['TOTAL_NOTARIS', 'total_notaris'])
                );

                totalDev += dev;
                totalNotaris += notaris;

                html += '<tr class="reba-data-row">';
                html += '<td class="reba-center">' + (index + 1) + '</td>';
                html += '<td class="reba-left">'
                    + rebaEscapeHtml(rebaValue(rebaPick(item, ['NAMA_PEMBELI', 'nama_pembeli'])))
                    + '</td>';
                html += '<td class="reba-center">'
                    + rebaEscapeHtml(rebaValue(rebaPick(item, ['BLOK_NOMOR', 'blok_nomor'])))
                    + '</td>';
                html += '<td class="reba-left">'
                    + rebaEscapeHtml(rebaValue(rebaPick(item, ['NO_DOKUMEN', 'no_dokumen'])))
                    + '</td>';
                html += '<td class="reba-center">'
                    + rebaEscapeHtml(rebaFormatDate(rebaPick(item, ['TGL_DOKUMEN', 'tgl_dokumen'])))
                    + '</td>';
                html += '<td class="reba-center">'
                    + rebaEscapeHtml(rebaFormatDate(rebaPick(item, ['TGL_PPJB', 'tgl_ppjb'])))
                    + '</td>';
                html += '<td class="reba-left">'
                    + rebaEscapeHtml(rebaValue(rebaPick(item, ['NM_NOTARIS', 'nm_notaris'])))
                    + '</td>';
                html += '<td class="reba-number">'
                    + rebaEscapeHtml(rebaFormatCurrency(rebaPick(item, ['TOTAL_DEV', 'total_dev'])))
                    + '</td>';
                html += '<td class="reba-number">'
                    + rebaEscapeHtml(rebaFormatCurrency(rebaPick(item, ['TOTAL_NOTARIS', 'total_notaris'])))
                    + '</td>';
                html += '</tr>';
            });

            totalDevSemua += totalDev;
            totalNotarisSemua += totalNotaris;

            html += '<tr class="reba-total-row">';
            html += '<td colspan="7" class="reba-total-label">';
            html += 'Sub Total ' + rebaEscapeHtml(group.nama) + ' :</td>';
            html += '<td class="reba-number">'
                + rebaEscapeHtml(rebaFormatCurrency(totalDev)) + '</td>';
            html += '<td class="reba-number">'
                + rebaEscapeHtml(rebaFormatCurrency(totalNotaris)) + '</td>';
            html += '</tr>';
        });

        /* Total keseluruhan hanya berarti bila cluster lebih dari satu. */
        if (groups.length > 1) {
            html += '<tr class="reba-grand-total-row">';
            html += '<td colspan="7" class="reba-total-label">';
            html += 'T O T A L :</td>';
            html += '<td class="reba-number">'
                + rebaEscapeHtml(rebaFormatCurrency(totalDevSemua)) + '</td>';
            html += '<td class="reba-number">'
                + rebaEscapeHtml(rebaFormatCurrency(totalNotarisSemua)) + '</td>';
            html += '</tr>';
        }

        html += '</tbody></table></div>';

        html += '</div>';

        $('#rebaMainDisplay').html(html);
    }

    function rebaTableHead() {
        var html = '<thead>';
        html += '<tr>';
        html += '<th rowspan="2">No.</th>';
        html += '<th rowspan="2">Nama<br>Pembeli</th>';
        html += '<th rowspan="2">Blok/<br>Nomor</th>';
        html += '<th colspan="2">Estimasi Biaya AJB</th>';
        html += '<th rowspan="2">Tanggal<br>PPJB</th>';
        html += '<th rowspan="2">Nama Notaris</th>';
        html += '<th colspan="2">Total</th>';
        html += '</tr>';
        html += '<tr>';
        html += '<th>Nomor</th><th>Tanggal</th>';
        html += '<th>Dev</th><th>Notaris</th>';
        html += '</tr>';
        html += '</thead>';

        return html;
    }

    /* ==============================================
       PRINT
       ============================================== */

    function printRebaReport() {
        if (
            $('#rebaPrintButton').prop('disabled')
            || !Array.isArray(lastRebaRows)
            || !$('#rebaMainDisplay .reba-report-table').length
        ) {
            return;
        }

        var reportHtml = $('#rebaMainDisplay').html();

        if (!reportHtml) {
            return;
        }

        $('#rebaNativePrintFrame').remove();

        var frame = document.createElement('iframe');
        frame.id = 'rebaNativePrintFrame';
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

        /*
         * @page hanya mengatur margin dan tidak mengunci size/orientation,
         * sehingga pilihan Portrait/Landscape tetap tersedia.
         */
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

            .reba-paper { width: 100%; margin: 0; padding: 0; }

            .reba-report-header {
                display: grid;
                grid-template-columns: 1fr 1.45fr 1fr;
                gap: 12px;
                align-items: center;
                margin-bottom: 7px;
                padding: 10px 12px;
                border: 1px solid #777;
                background: #fff;
                color: #000;
            }

            .reba-company { color: #000; font-size: 10px; font-weight: 700; }
            .reba-title-wrap { text-align: center; }

            .reba-report-title {
                margin: 0;
                color: #000;
                font-family: Cambria, Georgia, "Times New Roman", serif;
                font-size: 16px;
                font-weight: 700;
                line-height: 1.2;
            }

            .reba-report-center-meta,
            .reba-report-date { color: #000; font-size: 9px; line-height: 1.4; }
            .reba-report-date { text-align: right; }

            .reba-report-subtitle {
                display: grid;
                grid-template-columns: 1fr 2fr 1fr;
                align-items: center;
                gap: 10px;
                margin-bottom: 7px;
                padding: 7px 9px;
                border: 1px solid #aaa;
                color: #000;
                font-size: 9px;
            }

            .reba-subtitle-value { text-align: center; font-weight: 700; }

            .reba-live-badge {
                justify-self: end;
                color: #000;
                font-size: 8px;
                font-weight: 700;
            }

            .reba-live-badge::before {
                content: "";
                display: inline-block;
                width: 5px;
                height: 5px;
                margin-right: 4px;
                border-radius: 50%;
                background: #000;
            }

            .reba-table-wrap { width: 100%; overflow: visible; border: 0; }

            .reba-cluster-row td {
                background: #fff !important;
                color: #000;
                font-size: 8.4px;
                font-weight: 700;
                text-align: left;
            }

            .reba-cluster-row .reba-cluster-label {
                margin-right: 5px;
                font-size: 7.6px;
                letter-spacing: .1em;
            }

            .reba-cluster-row .reba-cluster-count { float: right; font-size: 7.6px; }

            .reba-grand-total-row td {
                background: #fff !important;
                color: #000;
                font-weight: 700;
            }

            .reba-report-table {
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100%;
                table-layout: fixed;
                border-collapse: collapse;
                border-spacing: 0;
                border: 1px solid #000;
                color: #000;
                font-size: 7.6px;
            }

            .reba-report-table col { width: auto !important; }
            .reba-report-table thead { display: table-header-group; }
            .reba-report-table tbody { display: table-row-group; }

            .reba-report-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .reba-report-table th,
            .reba-report-table td {
                position: static;
                height: auto;
                padding: 3.5px;
                border: 1px solid #000;
                background: #fff;
                color: #000;
                box-shadow: none;
                vertical-align: middle;
                overflow: visible;
                overflow-wrap: anywhere;
                line-height: 1.25;
            }

            .reba-report-table th { text-align: center; font-weight: 700; }

            .reba-total-row td {
                background: #fff !important;
                color: #000;
                font-weight: 700;
            }

            .reba-center { text-align: center; }
            .reba-left { text-align: left; }
            .reba-number { text-align: right; }
            .reba-total-label { text-align: right; letter-spacing: .18em; }

        `;

        frameDocument.open();
        frameDocument.write(
            '<!DOCTYPE html><html><head><meta charset="utf-8">'
            + '<meta name="viewport" content="width=device-width,initial-scale=1">'
            + '<title>Rekap Estimasi Biaya AJB</title>'
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
                    $('#rebaNativePrintFrame').remove();
                }, 1200);
            }
        }, 180);
    }
</script>
@endsection
