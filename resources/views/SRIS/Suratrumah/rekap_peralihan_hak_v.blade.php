@extends('layouts.template')

{{-- VIEW VERSION V1-20260904-DESKTOP-LAYOUT --}}
{{-- Tata letak filter dan kolom laporan mengikuti tampilan desktop SRIS. --}}
{{-- Hasil laporan dikelompokkan per cluster di dalam satu tabel. --}}

@section('content')
<style>
.rph-page,
.rph-page * {
    box-sizing: border-box;
}

.rph-page {
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

.rph-toolbar {
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

.rph-toolbar::before {
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

.rph-toolbar::after {
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

.rph-toolbar-title {
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

.rph-unit-badge {
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

.rph-filter {
    position: relative;
    z-index: 30;
    padding: 20px;
    overflow: visible;
    border: 1px solid #dbe3ef;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
}

.rph-filter::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    border-radius: 24px 0 0 24px;
    background: linear-gradient(180deg, #38bdf8 0%, #2563eb 52%, #1d4ed8 100%);
}

.rph-filter::after {
    content: "Rekap Peralihan Hak";
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

.rph-filter-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 82px;
    gap: 13px 14px;
    align-items: center;
}

.rph-field {
    display: grid;
    grid-template-columns: 112px minmax(0, 1fr);
    gap: 9px;
    align-items: center;
    min-width: 0;
}

.rph-label {
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

.rph-range {
    display: grid;
    grid-template-columns: minmax(86px, 1fr) 34px minmax(86px, 1fr);
    gap: 7px;
    align-items: center;
}

.rph-separator {
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

.rph-input,
.rph-lookup-display {
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

.rph-input:hover,
.rph-lookup-display:hover {
    border-color: #aebed1;
}

.rph-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
}

@keyframes rphLokasiPanelIn {
    from { opacity: 0; transform: translateY(-5px) scale(0.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.rph-lookup {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px;
    gap: 8px;
}

.rph-lookup-display {
    display: flex;
    align-items: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rph-lookup-button {
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

.rph-lookup-button:hover {
    transform: translateY(-1px);
    background: linear-gradient(145deg, #dbeafe 0%, #bfdbfe 100%);
    box-shadow: 0 7px 15px rgba(37, 99, 235, 0.12);
}

.rph-action-stack {
    position: relative;
    width: 82px;
    height: 42px;
    grid-column: 3;
    grid-row: 1;
    align-self: start;
    justify-self: end;
}

.rph-action {
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

.rph-action-stack .rph-action:first-child {
    position: absolute;
    top: 0;
    right: -20px;
    border-radius: 13px 0 0 13px;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
}

.rph-action-stack .rph-action:first-child:hover {
    transform: translateY(-1px);
    background: linear-gradient(135deg, #2f6ff0 0%, #1e4fc4 100%);
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.30);
}

#rphPrintButton {
    position: absolute;
    top: 55px;
    right: -20px;
    border-radius: 13px 0 0 13px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(5, 150, 105, 0.22);
}

#rphPrintButton:hover:not(:disabled) {
    transform: translateY(-1px);
    background: linear-gradient(135deg, #14b88a 0%, #047857 100%);
    box-shadow: 0 12px 24px rgba(5, 150, 105, 0.28);
}

#rphPrintButton:disabled,
#rphPrintButton:disabled:hover,
#rphPrintButton:disabled:focus {
    transform: none;
    border: 1px solid #d5dde7;
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
    color: #94a3b8;
    box-shadow: none;
    cursor: not-allowed;
    opacity: 1;
}

.rph-report-area {
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

.rph-report-area::before {
    content: "";
    position: absolute;
    top: 0;
    left: 7%;
    right: 7%;
    height: 3px;
    border-radius: 0 0 999px 999px;
    background: linear-gradient(90deg, transparent, #38bdf8, #2563eb, #6366f1, transparent);
}

#rphLoading {
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

.rph-paper {
    width: 100%;
    min-height: 0;
    margin: 0;
    padding: 0;
    border: 0;
    background: transparent;
    color: #172033;
}

.rph-initial {
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

.rph-initial-icon {
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

.rph-report-header {
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

.rph-company {
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 850;
    letter-spacing: 0.04em;
    line-height: 1.35;
}

.rph-title-wrap {
    min-width: 0;
    text-align: center;
}

.rph-report-title {
    display: block;
    margin: 0;
    color: #172033;
    font-family: Cambria, Georgia, "Times New Roman", serif;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.rph-report-center-meta {
    margin-top: 5px;
    color: #475467;
    font-size: 10.5px;
    font-weight: 650;
    line-height: 1.45;
}

.rph-report-date {
    color: #475467;
    text-align: right;
    font-size: 10.5px;
    font-weight: 650;
    line-height: 1.55;
}

.rph-report-subtitle {
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

.rph-subtitle-label {
    text-align: left;
}

.rph-subtitle-value {
    min-width: 0;
    color: #344054;
    text-align: center;
    font-weight: 850;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rph-live-badge {
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

.rph-live-badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
}

.rph-table-wrap {
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

.rph-table-wrap::-webkit-scrollbar,
.rph-modal-table-wrap::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.rph-table-wrap::-webkit-scrollbar-track,
.rph-modal-table-wrap::-webkit-scrollbar-track {
    background: #eff3f7;
}

.rph-table-wrap::-webkit-scrollbar-thumb,
.rph-modal-table-wrap::-webkit-scrollbar-thumb {
    border: 2px solid #eff3f7;
    border-radius: 999px;
    background: linear-gradient(180deg, #60a5fa, #2563eb);
}

.rph-report-table {
    width: 1420px;
    min-width: 1420px;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
    color: #344054;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10.5px;
}

.rph-report-table th,
.rph-report-table td {
    padding: 8px 9px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
    overflow-wrap: anywhere;
}

.rph-report-table th {
    position: sticky;
    z-index: 4;
    background: linear-gradient(180deg, #eff6ff 0%, #e5effb 100%);
    color: #344054;
    text-align: center;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-weight: 900;
    line-height: 1.25;
}

.rph-report-table thead tr:first-child th {
    top: 0;
    z-index: 5;
    height: 44px;
}

.rph-report-table thead tr:first-child th[colspan] {
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    color: #3730a3;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.rph-report-table thead tr:nth-child(2) th {
    top: 44px;
    z-index: 4;
    height: 34px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    color: #475467;
    font-size: 9.5px;
}

.rph-report-table thead tr:first-child th[rowspan] {
    z-index: 6;
}

.rph-report-table td {
    height: 44px;
    background: #ffffff;
    color: #344054;
    line-height: 1.38;
}

.rph-report-table tbody tr:nth-child(even):not(.rph-sector-row) td {
    background: #fbfcfe;
}

.rph-report-table tbody tr.rph-data-row:hover td {
    background: #f0f7ff;
}

.rph-report-table tbody tr.rph-data-row:hover td:first-child {
    box-shadow: inset 4px 0 0 #2563eb;
    color: #1d4ed8;
    font-weight: 900;
}

.rph-sector-row td {
    height: auto;
    padding: 8px 10px;
    background: linear-gradient(90deg, #ffffff 0%, #f8fafc 55%, #eff6ff 100%) !important;
    color: #344054;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10px;
    font-weight: 850;
    text-align: left;
}

.rph-center {
    text-align: center;
}

.rph-left {
    text-align: left;
}

.rph-empty {
    height: 130px;
    color: #64748b;
    text-align: center;
}

.rph-number {
    color: #1e3a5f;
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.rph-modal {
    position: fixed;
    inset: 0;
    z-index: 1065;
    display: none;
    overflow: auto;
    background: rgba(71, 85, 105, 0.24);
}

.rph-modal.show {
    display: block;
}

.rph-modal-dialog {
    width: calc(100vw - 32px);
    max-width: 800px;
    margin: 24px auto;
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
}

.rph-modal-header {
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

.rph-modal-close {
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

.rph-modal-body {
    padding: 16px;
    background: #f8fafc;
}

.rph-modal-search {
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

.rph-modal-search:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.rph-modal-table-wrap {
    max-height: 430px;
    overflow: auto;
    border: 1px solid #dbe3ef;
    border-radius: 16px;
    background: #ffffff;
}

.rph-modal-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.rph-modal-table th,
.rph-modal-table td {
    padding: 10px 12px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}

.rph-modal-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: linear-gradient(180deg, #eff6ff 0%, #e7f0fc 100%);
    color: #344054;
    text-align: center;
    font-weight: 850;
}

.rph-modal-table tbody tr {
    cursor: pointer;
}

.rph-modal-table tbody tr:hover td {
    background: #eff6ff;
    color: #1d4ed8;
}

/* =========================================================
   ALERT DATA KOSONG — SAMA DENGAN DAFTAR SERTIFIKAT PECAHAN
   Modal informasi yang tampil saat hasil laporan tidak
   menghasilkan baris data sama sekali.
   ========================================================= */
#rphNoDataAlertModal .modal-dialog {
    max-width: 380px;
}

#rphNoDataAlertModal .modal-content {
    border: 0;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
}

#rphNoDataAlertModal .alert-icon-wrapper {
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

#rphNoDataAlertModal .alert-title {
    margin-bottom: 8px;
    color: #172033;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 18px;
    font-weight: 700;
}

#rphNoDataAlertModal .alert-message {
    margin-bottom: 24px;
    color: #475569;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 14px;
}

#rphNoDataAlertModal .alert-btn-ok {
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

#rphNoDataAlertModal .alert-btn-ok:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
}

/* =========================================================
   KELOMPOK CLUSTER PADA LAPORAN
   Seluruh baris tetap berada pada satu tabel. Setiap kali
   cluster berganti, sebuah baris judul disisipkan di atasnya.
   ========================================================= */
.rph-cluster-row td {
    padding: 9px 10px;
    background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 62%, #ffffff 100%) !important;
    border-left: 4px solid #2563eb;
    color: #1e40af;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 11px;
    font-weight: 850;
    text-align: left;
}

.rph-cluster-row .rph-cluster-label {
    margin-right: 7px;
    color: #64748b;
    font-size: 9.5px;
    font-weight: 900;
    letter-spacing: 0.11em;
    text-transform: uppercase;
}

.rph-cluster-row .rph-cluster-count {
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

/* =========================================================
   STATUS APPROVE
   Tiga pilihan berdampingan, mengikuti radio pada desktop.
   ========================================================= */
.rph-radio-row {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.rph-radio {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 42px;
    padding: 0 13px;
    border: 1px solid #c8d3e1;
    border-radius: 12px;
    background: #ffffff;
    color: #344054;
    cursor: pointer;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 11.5px;
    font-weight: 700;
    transition: border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
}

.rph-radio:hover {
    border-color: #aebed1;
    background: #f8fbff;
}

.rph-radio input {
    width: 15px;
    height: 15px;
    margin: 0;
    accent-color: #2563eb;
    cursor: pointer;
}

.rph-radio:has(input:checked) {
    border-color: #2563eb;
    background: #eff6ff;
    color: #1d4ed8;
}

select.rph-input {
    appearance: none;
    padding-right: 30px;
    background-image:
        linear-gradient(45deg, transparent 50%, #1d4ed8 50%),
        linear-gradient(135deg, #1d4ed8 50%, transparent 50%);
    background-position:
        calc(100% - 16px) calc(50% + 1px),
        calc(100% - 11px) calc(50% + 1px);
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;
    cursor: pointer;
}

@media screen and (max-width: 719px) {
    html, body {
        min-width: 720px;
    }
    .rph-page {
        min-width: 720px;
    }
}

@media print {
    .rph-toolbar, .rph-filter, #rphLoading, #rphClusterModal,
    #rphNoDataAlertModal, .modal-backdrop, .main-sidebar, .control-sidebar,
    .main-header, .main-footer, .navbar, .sidebar {
        display: none !important;
    }
    html, body, .wrapper, .content-wrapper, .main-content, .content,
    .page-wrapper, .page-content, .container, .container-fluid,
    .rph-page, .rph-report-area, .rph-paper {
        width: 100% !important;
        min-width: 0 !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        background: #fff !important;
        box-shadow: none !important;
    }
    .rph-table-wrap {
        max-height: none !important;
        overflow: visible !important;
    }
    .rph-report-table {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
    }
    .rph-cluster-row td {
        border-left: 1px solid #000 !important;
        background: #fff !important;
        color: #000 !important;
    }
}
</style>

<!-- MODAL ALERT DATA KOSONG -->
<div class="modal fade" id="rphNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4" style="text-align: center; padding: 1.5rem;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message" id="rphNoDataMessage">Data tidak ditemukan.</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    data-dismiss="modal"
                    onclick="hideRphNoDataAlert()"
                >OK</button>
            </div>
        </div>
    </div>
</div>

<div class="rph-page">
    <div class="rph-toolbar">
        <div class="rph-toolbar-title">
            Rekap Peralihan Hak
        </div>
        <code class="rph-unit-badge">
            UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}
        </code>
    </div>

    <section class="rph-filter">
        <input
            type="hidden"
            autocomplete="off"
            id="rphPerusahaan"
            value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}"
        >
        <input
            type="hidden"
            autocomplete="off"
            id="rphNamaPerusahaan"
            value="{{ session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? '' }}"
        >
        <input type="hidden" id="rphCluster" value="*" autocomplete="off">

        <div class="rph-filter-grid">
            <div class="rph-field">
                <label class="rph-label" for="rphTglAwal">Periode</label>
                <div class="rph-range">
                    <input type="date" id="rphTglAwal" class="rph-input" autocomplete="off">
                    <span class="rph-separator">s.d</span>
                    <input type="date" id="rphTglAkhir" class="rph-input" autocomplete="off">
                </div>
            </div>

            <div class="rph-field">
                <span class="rph-label">Cluster</span>
                <div class="rph-lookup">
                    <div id="rphClusterEntry" class="rph-lookup-display">Semua Cluster</div>
                    <button
                        type="button"
                        class="rph-lookup-button"
                        onclick="getRphClusterModal()"
                        title="Pilih cluster"
                        aria-label="Pilih cluster"
                    >
                        <i class="fas fa-binoculars"></i>
                    </button>
                </div>
            </div>

            <div class="rph-action-stack">
                <button
                    type="button"
                    class="rph-action"
                    onclick="getRphData()"
                >
                    Ok
                </button>
                <button
                    type="button"
                    class="rph-action"
                    id="rphPrintButton"
                    onclick="printRphReport()"
                    aria-disabled="true"
                    disabled
                >
                    Print
                </button>
            </div>

            <div class="rph-field">
                <label class="rph-label" for="rphStsEntry">Status Entry</label>
                <select id="rphStsEntry" class="rph-input" autocomplete="off">
                    <option value="*">Semua</option>
                    <option value="Y">Sudah Entry Pembeli Baru</option>
                    <option value="T">Belum Entry Pembeli Baru</option>
                </select>
            </div>

            <div class="rph-field">
                <span class="rph-label">Status Approve</span>
                <div class="rph-radio-row">
                    <label class="rph-radio">
                        <input
                            type="radio"
                            name="rphStsApprove"
                            value="Y"
                            autocomplete="off"
                        >
                        <span>Sudah</span>
                    </label>
                    <label class="rph-radio">
                        <input
                            type="radio"
                            name="rphStsApprove"
                            value="T"
                            autocomplete="off"
                        >
                        <span>Belum</span>
                    </label>
                    <label class="rph-radio">
                        <input
                            type="radio"
                            name="rphStsApprove"
                            value="*"
                            autocomplete="off"
                            checked
                        >
                        <span>Semua</span>
                    </label>
                </div>
            </div>
        </div>
    </section>

    <section class="rph-report-area">
        <div id="rphLoading">
            <i class="fas fa-spinner fa-spin"></i>
            Mengambil data Rekap Peralihan Hak...
        </div>

        <div id="rphMainDisplay">
            <div class="rph-paper">
                <div class="rph-initial">
                    <i class="fas fa-table rph-initial-icon" aria-hidden="true"></i>
                    <div>Silahkan isi filter kemudian klik OK</div>
                </div>
            </div>
        </div>
    </section>

    <div id="rphClusterModal" class="rph-modal" aria-hidden="true">
        <div class="rph-modal-dialog">
            <div class="rph-modal-header">
                <span>Pilih Cluster</span>
                <button
                    type="button"
                    class="rph-modal-close"
                    onclick="toggleRphClusterModal(false)"
                    aria-label="Tutup"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="rph-modal-body" id="rphClusterModalContent"></div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    var lastRphRows = null;

    $(document).ready(function () {
        /*
         * Browser memulihkan isi form saat halaman di-refresh, sehingga
         * pilihan status dan cluster dapat terbawa dari kunjungan
         * sebelumnya. Reset dipanggil bertahap karena pemulihan itu dapat
         * terjadi setelah DOMContentLoaded.
         */
        resetRphInitialState();
        window.setTimeout(resetRphInitialState, 10);
        window.setTimeout(resetRphInitialState, 100);

        $('#rphStsEntry').on('change', function () {
            resetRphPrint();
        });

        $('input[name="rphStsApprove"]').on('change', function () {
            resetRphPrint();
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                toggleRphClusterModal(false);
            }
        });
    });

    $(window).on('load', function () {
        resetRphInitialState();
    });

    $(window).on('pageshow', function (event) {
        var pageEvent = event.originalEvent || event;

        if (pageEvent.persisted) {
            resetRphInitialState();
        }
    });

    /*
     * Mengembalikan seluruh filter dan area laporan ke keadaan awal,
     * seperti saat fitur ini baru dibuka.
     */
    function resetRphInitialState() {
        $('#rphCluster').val('*');
        $('#rphClusterEntry').text('Semua Cluster');
        $('#rphStsEntry').val('*');
        $('input[name="rphStsApprove"]').prop('checked', false);
        $('input[name="rphStsApprove"][value="*"]').prop('checked', true);

        toggleRphClusterModal(false);

        setRphDefaultDate();
        resetRphPrint();

        $('#rphLoading').hide();
        $('#rphMainDisplay').html(
            '<div class="rph-paper">'
            + '<div class="rph-initial">'
            + '<i class="fas fa-table rph-initial-icon" aria-hidden="true"></i>'
            + '<div>Silahkan isi filter kemudian klik OK</div>'
            + '</div></div>'
        );
    }

    function setRphDefaultDate() {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var today = year + '-' + month + '-' + day;

        $('#rphTglAwal').val(today);
        $('#rphTglAkhir').val(today);
    }

    function rphStsEntry() {
        var nilai = String($('#rphStsEntry').val() || '*');

        return (nilai === 'Y' || nilai === 'T') ? nilai : '*';
    }

    function rphStsApprove() {
        var nilai = String(
            $('input[name="rphStsApprove"]:checked').val() || '*'
        );

        return (nilai === 'Y' || nilai === 'T') ? nilai : '*';
    }

    /* Label status untuk header laporan, sama dengan desktop. */
    function rphStsEntryLabel() {
        var nilai = rphStsEntry();

        if (nilai === 'Y') {
            return 'Sudah Entry';
        }

        if (nilai === 'T') {
            return 'Belum Entry';
        }

        return 'Semua';
    }

    function rphStsApproveLabel() {
        var nilai = rphStsApprove();

        if (nilai === 'Y') {
            return 'Sudah';
        }

        if (nilai === 'T') {
            return 'Belum';
        }

        return 'Semua';
    }

    /* Kolom luas ditampilkan dua angka di belakang koma, seperti desktop. */
    function rphFormatLuas(value) {
        if (value === null || value === undefined || String(value).trim() === '') {
            return '';
        }

        var number = Number(value);

        if (!isFinite(number)) {
            return String(value);
        }

        return number.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function setRphPrintEnabled(enabled) {
        var disabled = enabled !== true;
        $('#rphPrintButton')
            .prop('disabled', disabled)
            .attr('aria-disabled', disabled ? 'true' : 'false');
    }

    function resetRphPrint() {
        lastRphRows = null;
        setRphPrintEnabled(false);
        hideRphNoDataAlert();
    }

    /*
     * Alert data kosong, mengikuti Daftar Sertifikat Pecahan.
     */
    function showRphNoDataAlert(message) {
        var $modal = $('#rphNoDataAlertModal');

        $('#rphNoDataMessage').text(message || 'Data tidak ditemukan......!');

        if (typeof $modal.modal === 'function') {
            $modal.modal('show');
            return;
        }

        if (!$('.rph-nodata-backdrop').length) {
            $('<div class="modal-backdrop fade show rph-nodata-backdrop"></div>')
                .appendTo(document.body);
        }

        $modal
            .addClass('show')
            .css('display', 'block')
            .attr('aria-hidden', 'false');
    }

    function hideRphNoDataAlert() {
        var $modal = $('#rphNoDataAlertModal');

        if (typeof $modal.modal === 'function') {
            $modal.modal('hide');
            return;
        }

        $modal
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true');

        $('.rph-nodata-backdrop').remove();
    }

    function rphEscapeHtml(value) {
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

    function rphEscapeJs(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r?\n/g, ' ');
    }

    function rphValue(value) {
        return value === null || value === undefined || value === ''
            ? '-'
            : value;
    }

    function rphPick(item, keys) {
        item = item || {};

        for (var i = 0; i < keys.length; i++) {
            var value = item[keys[i]];
            if (value !== null && value !== undefined && value !== '') {
                return value;
            }
        }

        return null;
    }

    function rphFormatDate(value) {
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

    /* Kolom rupiah memakai pemisah ribuan tanpa desimal, seperti desktop. */
    function rphFormatCurrency(value) {
        if (value === null || value === undefined || String(value).trim() === '') {
            return '';
        }

        var number = Number(value);

        if (!isFinite(number)) {
            return rphEscapeHtml(value);
        }

        return number.toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    /* Nama PT untuk header laporan, sama dengan fitur lain. */
    function rphExtractCompanyName(value) {
        var raw = String(value || '').replace(/\u00a0/g, ' ');
        var locationMatch = raw.match(/Lokasi\s*:[^\r\n|]*?-\s*([^\r\n|]+)/i);
        var companyMatch = raw.match(/\b((?:PT\.?|CV\.?|PD\.?|PERUM)\s+[^\r\n|]+)/i);
        var name = locationMatch ? locationMatch[1] : (companyMatch ? companyMatch[1] : '');

        return String(name || '')
            .replace(/\s+(?:Hi\s*,|Logout\b|Keluar\b|LAND DOCUMENT\b|UNIT\s+[A-Z0-9_-]+\b).*$/i, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function rphCompanyNameFromLayout() {
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
                var name = rphExtractCompanyName(candidates[j]);
                if (name) {
                    return name;
                }
            }
        }

        return '';
    }

    function rphCompanyName(first) {
        var unit = String($('#rphPerusahaan').val() || '').trim().toUpperCase();
        var rowName = rphPick(first || {}, [
            'NAMA_PT', 'nama_pt',
            'ATAS_NAMA_PT', 'atas_nama_pt',
            'NAMA_PERUSAHAAN', 'nama_perusahaan'
        ]);
        var sessionName = String($('#rphNamaPerusahaan').val() || '').trim();
        var cacheKey = 'sris.report-company.' + unit;
        var cachedName = '';

        try {
            cachedName = localStorage.getItem(cacheKey) || '';
        } catch (error) {
            cachedName = '';
        }

        var company = rphExtractCompanyName(rowName)
            || String(rowName || '').trim()
            || rphCompanyNameFromLayout()
            || rphExtractCompanyName(sessionName)
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

    function toggleRphClusterModal(show) {
        $('#rphClusterModal')
            .toggleClass('show', show === true)
            .attr('aria-hidden', show === true ? 'false' : 'true');
    }

    function addRphCluster(kode, deskripsi) {
        $('#rphCluster').val(kode || '*');
        $('#rphClusterEntry').text(deskripsi || 'Semua Cluster');
        toggleRphClusterModal(false);
        resetRphPrint();
    }

    function filterRphCluster(keyword) {
        var search = String(keyword || '').toLowerCase().trim();

        $('#rphClusterModal .rph-modal-table tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(search) !== -1);
        });
    }

    function getRphClusterModal() {
        var perusahaan = String($('#rphPerusahaan').val() || '').trim();

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
                html += '<input type="text" class="rph-modal-search" ';
                html += 'placeholder="Cari cluster..." ';
                html += 'onkeyup="filterRphCluster(this.value)">';
                html += '<div class="rph-modal-table-wrap">';
                html += '<table class="rph-modal-table"><thead><tr>';
                html += '<th>Kode</th><th>Deskripsi</th><th>Perusahaan</th>';
                html += '</tr></thead><tbody>';

                html += '<tr onclick="addRphCluster(\'*\', \'Semua Cluster\')">';
                html += '<td>*</td><td>Semua Cluster</td>';
                html += '<td>' + rphEscapeHtml(perusahaan) + '</td></tr>';

                $.each(rows, function (index, item) {
                    var kode = rphPick(item, [
                        'KD_CLUSTER', 'kd_cluster', 'KD_SEKTOR', 'kd_sektor'
                    ]) || '';
                    var deskripsi = rphPick(item, ['DESKRIPSI', 'deskripsi']) || kode;
                    var unit = rphPick(item, [
                        'KD_PERUSAHAAN', 'kd_perusahaan'
                    ]) || perusahaan;

                    html += '<tr onclick="addRphCluster(\''
                        + rphEscapeJs(kode) + '\', \''
                        + rphEscapeJs(deskripsi) + '\')">';
                    html += '<td>' + rphEscapeHtml(kode) + '</td>';
                    html += '<td>' + rphEscapeHtml(deskripsi) + '</td>';
                    html += '<td>' + rphEscapeHtml(unit) + '</td></tr>';
                });

                if (rows.length < 1) {
                    html += '<tr><td colspan="3" ';
                    html += 'style="padding:22px;text-align:center;">';
                    html += 'Data cluster tidak ditemukan.</td></tr>';
                }

                html += '</tbody></table></div>';

                $('#rphClusterModalContent').html(html);
                toggleRphClusterModal(true);
                $('#rphClusterModal .rph-modal-search').trigger('focus');
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
       DATA LAPORAN
       ============================================== */

    function validateRphFilter() {
        if (!$('#rphTglAwal').val() || !$('#rphTglAkhir').val()) {
            alert('Periode tanggal wajib diisi.');
            return false;
        }

        if ($('#rphTglAwal').val() > $('#rphTglAkhir').val()) {
            alert('Tanggal awal tidak boleh melebihi tanggal akhir.');
            return false;
        }

        return true;
    }

    function getRphFilterData() {
        var hariIni = new Date();
        var tglCadangan = hariIni.getFullYear()
            + '-' + String(hariIni.getMonth() + 1).padStart(2, '0')
            + '-' + String(hariIni.getDate()).padStart(2, '0');

        return {
            _token: '{{ csrf_token() }}',
            tgl_awal: $('#rphTglAwal').val() || tglCadangan,
            tgl_akhir: $('#rphTglAkhir').val() || tglCadangan,
            perusahaan: $('#rphPerusahaan').val(),
            cluster: $('#rphCluster').val() || '*',
            sts_entry: rphStsEntry(),
            sts_approve: rphStsApprove()
        };
    }

    function getRphData() {
        if (!validateRphFilter()) {
            return;
        }

        resetRphPrint();
        $('#rphLoading').show();

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_data',
            dataType: 'json',
            timeout: 120000,
            headers: { 'Accept': 'application/json' },
            data: getRphFilterData(),
            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (response && Array.isArray(response.data) ? response.data : []);

                lastRphRows = rows;

                if (rows.length === 0) {
                    showRphNoDataAlert('Data tidak ditemukan......!');
                }

                renderRphReport(rows);
                setRphPrintEnabled(rows.length > 0);
            },
            error: function (xhr, textStatus, errorThrown) {
                resetRphPrint();

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

                $('#rphMainDisplay').html(
                    '<div class="rph-paper">'
                    + '<div style="padding:16px;color:#a00;">'
                    + 'Gagal mengambil data Rekap Peralihan Hak. '
                    + rphEscapeHtml(detail)
                    + '</div></div>'
                );
            },
            complete: function () {
                $('#rphLoading').hide();
            }
        });
    }

    /*
     * Mengelompokkan baris per cluster dengan tetap menjaga urutan yang
     * dikirim server. Baris tanpa cluster dikumpulkan pada satu kelompok
     * sendiri supaya tidak hilang dari laporan.
     */
    function groupRphRows(rows) {
        var groups = [];
        var index = {};

        $.each(rows, function (position, item) {
            item = item || {};

            var nama = String(
                rphPick(item, ['NAMA_CLUSTER', 'nama_cluster'])
                || rphPick(item, ['KD_SEKTOR', 'kd_sektor'])
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
     * ke-18 kolomnya tetap terbaca dan dapat digeser ke samping.
     */
    function rphTableStyle() {
        return 'style="width:100%;min-width:2260px;"';
    }

    function rphColgroup() {
        var lebar = [
            42,   /* No. */
            96,   /* Blok Nomor */
            70,   /* Luas Tanah */
            80,   /* Luas Bangunan */
            110,  /* Tipe Bangunan */
            92,   /* Tanggal Peralihan */
            210,  /* Peralihan Notaris */
            92,   /* Tanggal Notaris */
            140,  /* Harga Jual Inc. PPN */
            140,  /* Harga Pasar */
            120,  /* Kuitansi - Nomor */
            92,   /* Kuitansi - Tanggal */
            140,  /* Kuitansi - Jumlah */
            210,  /* Pembeli Lama */
            210,  /* Pembeli Baru */
            100,  /* Tgl. Kuitansi BPH */
            158,  /* Nama Agen Baru */
            158   /* Nama Sales Baru */
        ];

        var html = '<colgroup>';

        $.each(lebar, function (index, nilai) {
            html += '<col style="width:' + nilai + 'px">';
        });

        return html + '</colgroup>';
    }

    function rphTableHead() {
        var html = '<thead>';
        html += '<tr>';
        html += '<th rowspan="2">No.</th>';
        html += '<th rowspan="2">Blok<br>Nomor</th>';
        html += '<th rowspan="2">Luas<br>Tanah</th>';
        html += '<th rowspan="2">Luas<br>Bangunan</th>';
        html += '<th rowspan="2">Tipe<br>Bangunan</th>';
        html += '<th rowspan="2">Tanggal<br>Peralihan</th>';
        html += '<th rowspan="2">Peralihan<br>Notaris</th>';
        html += '<th rowspan="2">Tanggal<br>Notaris</th>';
        html += '<th rowspan="2">Harga Jual Inc.<br>PPN (Rp)</th>';
        html += '<th rowspan="2">Harga Pasar<br>(Rp)</th>';
        html += '<th colspan="3">Kuitansi</th>';
        html += '<th rowspan="2">Pembeli Lama</th>';
        html += '<th rowspan="2">Pembeli Baru</th>';
        html += '<th rowspan="2">Tgl. Kuitansi<br>BPH</th>';
        html += '<th rowspan="2">Nama Agen<br>Baru</th>';
        html += '<th rowspan="2">Nama Sales<br>Baru</th>';
        html += '</tr>';
        html += '<tr>';
        html += '<th>Nomor</th><th>Tanggal</th><th>Jumlah (Rp)</th>';
        html += '</tr>';
        html += '</thead>';

        return html;
    }

    function renderRphReport(rows) {
        rows = Array.isArray(rows) ? rows : [];

        var first = rows.length > 0 ? rows[0] : {};
        var company = rphCompanyName(first);

        var periode = rphFormatDate($('#rphTglAwal').val())
            + ' s.d ' + rphFormatDate($('#rphTglAkhir').val());

        var clusterTampil = String(
            $('#rphClusterEntry').text() || 'Semua Cluster'
        ).trim();

        var now = new Date();
        var today = String(now.getDate()).padStart(2, '0')
            + '-' + String(now.getMonth() + 1).padStart(2, '0')
            + '-' + now.getFullYear();

        var groups = groupRphRows(rows);

        var html = '';

        html += '<div class="rph-paper">';

        html += '<div class="rph-report-header">';
        html += '<div class="rph-company">' + rphEscapeHtml(company) + '</div>';
        html += '<div class="rph-title-wrap">';
        html += '<h2 class="rph-report-title">Rekap Peralihan Hak</h2>';
        html += '<div class="rph-report-center-meta">';
        html += 'Tanggal : ' + rphEscapeHtml(periode);
        html += '<br>Status Entry Pembeli Baru : '
            + rphEscapeHtml(rphStsEntryLabel());
        html += '<br>Status Approval : ' + rphEscapeHtml(rphStsApproveLabel());
        html += '</div></div>';
        html += '<div class="rph-report-date">';
        html += 'Tgl. Cetak : ' + rphEscapeHtml(today);
        html += '</div></div>';

        html += '<div class="rph-report-subtitle">';
        html += '<span class="rph-subtitle-label">Cluster:</span>';
        html += '<strong class="rph-subtitle-value">'
            + rphEscapeHtml(clusterTampil) + '</strong>';
        html += '<span class="rph-live-badge">Live data</span>';
        html += '</div>';

        html += '<div class="rph-table-wrap">';
        html += '<table class="rph-report-table" ' + rphTableStyle() + '>';
        html += rphColgroup();
        html += rphTableHead();
        html += '<tbody>';

        if (groups.length < 1) {
            html += '<tr><td colspan="18" class="rph-empty">';
            html += 'Data tidak ditemukan.';
            html += '</td></tr>';
        }

        /*
         * Seluruh baris berada pada satu tabel. Setiap kali cluster
         * berganti, sebuah baris judul disisipkan di atasnya, dan nomor
         * urut dimulai lagi dari satu seperti tampilan desktop.
         */
        $.each(groups, function (posisi, group) {
            html += '<tr class="rph-cluster-row"><td colspan="18">';
            html += '<span class="rph-cluster-label">Cluster :</span>';
            html += rphEscapeHtml(group.nama);
            html += '<span class="rph-cluster-count">'
                + group.rows.length + ' data</span>';
            html += '</td></tr>';

            $.each(group.rows, function (index, item) {
                html += '<tr class="rph-data-row">';
                html += '<td class="rph-center">' + (index + 1) + '</td>';
                html += '<td class="rph-center">'
                    + rphEscapeHtml(rphValue(rphPick(item, ['BLOK_NOMOR', 'blok_nomor'])))
                    + '</td>';
                html += '<td class="rph-number">'
                    + rphEscapeHtml(rphFormatLuas(rphPick(item, ['LUAS_TANAH', 'luas_tanah'])))
                    + '</td>';
                html += '<td class="rph-number">'
                    + rphEscapeHtml(rphFormatLuas(rphPick(item, ['LUAS_BANGUNAN', 'luas_bangunan'])))
                    + '</td>';
                html += '<td class="rph-left">'
                    + rphEscapeHtml(rphValue(rphPick(item, ['TIPE_BANGUNAN', 'tipe_bangunan'])))
                    + '</td>';
                html += '<td class="rph-center">'
                    + rphEscapeHtml(rphFormatDate(rphPick(item, ['TGL_PERALIHAN', 'tgl_peralihan'])))
                    + '</td>';
                html += '<td class="rph-left">'
                    + rphEscapeHtml(rphValue(rphPick(item, ['NOTARIS', 'notaris'])))
                    + '</td>';
                html += '<td class="rph-center">'
                    + rphEscapeHtml(rphFormatDate(rphPick(item, ['TGL_NOTARIS', 'tgl_notaris'])))
                    + '</td>';
                html += '<td class="rph-number">'
                    + rphEscapeHtml(rphFormatCurrency(rphPick(item, ['HARGA_INCLUDE_PPN', 'harga_include_ppn'])))
                    + '</td>';
                html += '<td class="rph-number">'
                    + rphEscapeHtml(rphFormatCurrency(rphPick(item, ['HARGA_PASAR', 'harga_pasar'])))
                    + '</td>';
                html += '<td class="rph-left">'
                    + rphEscapeHtml(rphValue(rphPick(item, ['NO_KUITANSI', 'no_kuitansi'])))
                    + '</td>';
                html += '<td class="rph-center">'
                    + rphEscapeHtml(rphFormatDate(rphPick(item, ['TGL_KUITANSI', 'tgl_kuitansi'])))
                    + '</td>';
                html += '<td class="rph-number">'
                    + rphEscapeHtml(rphFormatCurrency(rphPick(item, ['JML_KUITANSI', 'jml_kuitansi'])))
                    + '</td>';
                html += '<td class="rph-left">'
                    + rphEscapeHtml(rphValue(rphPick(item, ['PEMBELI_LAMA', 'pembeli_lama'])))
                    + '</td>';
                html += '<td class="rph-left">'
                    + rphEscapeHtml(rphValue(rphPick(item, ['PEMBELI_BARU', 'pembeli_baru'])))
                    + '</td>';
                html += '<td class="rph-center">'
                    + rphEscapeHtml(rphFormatDate(rphPick(item, ['TGL_KUITANSI_BPH', 'tgl_kuitansi_bph'])))
                    + '</td>';
                html += '<td class="rph-left">'
                    + rphEscapeHtml(rphValue(rphPick(item, ['NM_AGEN', 'nm_agen'])))
                    + '</td>';
                html += '<td class="rph-left">'
                    + rphEscapeHtml(rphValue(rphPick(item, ['NM_SALES', 'nm_sales'])))
                    + '</td>';
                html += '</tr>';
            });
        });

        html += '</tbody></table></div>';
        html += '</div>';

        $('#rphMainDisplay').html(html);
    }

/* ==============================================
       PRINT
       ============================================== */

    /*
     * Lebar kolom untuk hasil cetak, dihitung dari colgroup laporan supaya
     * proporsinya sama dengan tampilan layar. Dibuat sebagai persentase
     * agar tetap benar berapa pun lebar kertas dan orientasi yang dipilih.
     */
    function rphPrintColumnCss() {
        var lebar = [];
        var total = 0;

        $('#rphMainDisplay .rph-report-table').first().find('col').each(function () {
            var nilai = parseFloat(this.style.width) || $(this).width() || 0;

            lebar.push(nilai);
            total += nilai;
        });

        if (!total) {
            return '';
        }

        var css = '';

        $.each(lebar, function (index, nilai) {
            css += '.rph-report-table col:nth-child(' + (index + 1) + ')'
                + ' { width: ' + ((nilai / total) * 100).toFixed(3)
                + '% !important; }';
        });

        return css;
    }

    function printRphReport() {
        if (
            $('#rphPrintButton').prop('disabled')
            || !Array.isArray(lastRphRows)
            || !$('#rphMainDisplay .rph-report-table').length
        ) {
            return;
        }

        var reportHtml = $('#rphMainDisplay').html();

        if (!reportHtml) {
            return;
        }

        $('#rphNativePrintFrame').remove();

        var frame = document.createElement('iframe');
        frame.id = 'rphNativePrintFrame';
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

            .rph-paper { width: 100%; margin: 0; padding: 0; }

            .rph-report-header {
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

            .rph-company { color: #000; font-size: 11px; font-weight: 700; }
            .rph-title-wrap { text-align: center; }

            .rph-report-title {
                margin: 0;
                color: #000;
                font-family: Cambria, Georgia, "Times New Roman", serif;
                font-size: 17px;
                font-weight: 700;
                line-height: 1.2;
            }

            .rph-report-center-meta,
            .rph-report-date { color: #000; font-size: 10px; line-height: 1.35; }
            .rph-report-date { text-align: right; }

            .rph-report-subtitle {
                display: grid;
                grid-template-columns: 1fr 2fr 1fr;
                align-items: center;
                gap: 10px;
                margin-bottom: 7px;
                padding: 7px 9px;
                border: 1px solid #aaa;
                color: #000;
                font-size: 10px;
            }

            .rph-subtitle-value { text-align: center; font-weight: 700; }

            .rph-live-badge {
                justify-self: end;
                color: #000;
                font-size: 9px;
                font-weight: 700;
            }

            .rph-live-badge::before {
                content: "";
                display: inline-block;
                width: 5px;
                height: 5px;
                margin-right: 4px;
                border-radius: 50%;
                background: #000;
            }

            .rph-table-wrap { width: 100%; overflow: visible; border: 0; }

            .rph-cluster-row td {
                background: #fff !important;
                color: #000;
                font-size: 10px;
                font-weight: 700;
                text-align: left;
            }

            .rph-cluster-row .rph-cluster-label {
                margin-right: 5px;
                font-size: 9px;
                letter-spacing: .1em;
            }

            .rph-cluster-row .rph-cluster-count { float: right; font-size: 9px; }

            .rph-report-table {
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100%;
                table-layout: auto;
                border-collapse: collapse;
                border-spacing: 0;
                border: 1px solid #000;
                color: #000;
                font-size: 10px;
            }

            /*
             * Lebar kolom tidak lagi dipaksa auto. Dengan auto setiap kolom
             * mendapat lebar yang sama, sehingga kolom nama terpotong
             * menjadi dua baris sementara kolom nomor menyisakan ruang
             * kosong. Persentase per kolom dihasilkan oleh
             * rphPrintColumnCss() dari colgroup laporan.
             */
            .rph-report-table thead { display: table-header-group; }
            .rph-report-table tbody { display: table-row-group; }

            .rph-report-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .rph-report-table th,
            .rph-report-table td {
                position: static;
                height: auto;
                padding: 2.5px 4px;
                border: 1px solid #000;
                background: #fff;
                color: #000;
                box-shadow: none;
                vertical-align: middle;
                overflow: visible;
                overflow-wrap: break-word;
                line-height: 1.2;
            }

            .rph-report-table th { text-align: center; font-weight: 700; }

            /*
             * Tanggal dan angka tidak boleh dipenggal di tengah. Dengan
             * table-layout otomatis, lebar kolomnya yang menyesuaikan.
             */
            .rph-center { text-align: center; white-space: nowrap; }
            .rph-left { text-align: left; }
            .rph-number { text-align: right; white-space: nowrap; }

        `;

        frameDocument.open();
        frameDocument.write(
            '<!DOCTYPE html><html><head><meta charset="utf-8">'
            + '<meta name="viewport" content="width=device-width,initial-scale=1">'
            + '<title>Rekap Peralihan Hak</title>'
            + '<style>' + printCss + rphPrintColumnCss() + '</style>'
            + '</head><body>' + reportHtml + '</body></html>'
        );
        frameDocument.close();

        window.setTimeout(function () {
            try {
                frameWindow.focus();
                frameWindow.print();
            } finally {
                window.setTimeout(function () {
                    $('#rphNativePrintFrame').remove();
                }, 1200);
            }
        }, 180);
    }
</script>
@endsection
