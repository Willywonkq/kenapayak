@extends('layouts.template')

{{-- VIEW VERSION V1-20260902-DESKTOP-LAYOUT --}}
{{-- Tata letak filter dan kolom laporan mengikuti tampilan desktop SRIS. --}}

@section('content')
<style>
.rajb-page,
.rajb-page * {
    box-sizing: border-box;
}

.rajb-page {
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

.rajb-toolbar {
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

.rajb-toolbar::before {
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

.rajb-toolbar::after {
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

.rajb-toolbar-title {
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

.rajb-unit-badge {
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

.rajb-filter {
    position: relative;
    z-index: 30;
    padding: 20px;
    overflow: visible;
    border: 1px solid #dbe3ef;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
}

.rajb-filter::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    border-radius: 24px 0 0 24px;
    background: linear-gradient(180deg, #38bdf8 0%, #2563eb 52%, #1d4ed8 100%);
}

.rajb-filter::after {
    content: "Rekap Akta Jual Beli";
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

.rajb-filter-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 82px;
    gap: 13px 14px;
    align-items: center;
}

.rajb-field {
    display: grid;
    grid-template-columns: 112px minmax(0, 1fr);
    gap: 9px;
    align-items: center;
    min-width: 0;
}

.rajb-label {
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

.rajb-range {
    display: grid;
    grid-template-columns: minmax(86px, 1fr) 34px minmax(86px, 1fr);
    gap: 7px;
    align-items: center;
}

.rajb-separator {
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

.rajb-input,
.rajb-lookup-display {
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

.rajb-input:hover,
.rajb-lookup-display:hover {
    border-color: #aebed1;
}

.rajb-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
}

/* =========================================================
   DROPDOWN LOKASI
   Memakai tabel dua kolom, bukan <select>, supaya deskripsi
   selalu rata betapa pun panjang kode lokasinya.
   ========================================================= */
.rajb-lokasi {
    position: relative;
    z-index: 60;
    width: 100%;
    min-width: 0;
}

.rajb-lokasi-selected {
    display: flex;
    width: 100%;
    min-width: 0;
    height: 42px;
    align-items: center;
    padding: 0;
    overflow: hidden;
    border: 1px solid #c8d3e1;
    border-radius: 12px;
    background: #ffffff;
    color: #101828;
    cursor: pointer;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.rajb-lokasi-selected:hover {
    border-color: #aebed1;
}

.rajb-lokasi.is-open .rajb-lokasi-selected {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
}

.rajb-lokasi-code {
    display: inline-flex;
    width: 62px;
    min-width: 62px;
    flex: 0 0 62px;
    height: 30px;
    align-items: center;
    justify-content: center;
    margin-left: 6px;
    border-radius: 8px;
    background: #eff6ff;
    color: #1d4ed8;
    font-family: "SFMono-Regular", Consolas, monospace;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.04em;
}

.rajb-lokasi-name {
    display: flex;
    min-width: 0;
    flex: 1 1 auto;
    align-items: center;
    padding: 0 8px;
    overflow: hidden;
    color: #101828;
    font-size: 12px;
    font-weight: 650;
    text-align: left;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rajb-lokasi-arrow {
    display: flex;
    width: 30px;
    min-width: 30px;
    flex: 0 0 30px;
    height: 30px;
    align-items: center;
    justify-content: center;
    margin-right: 6px;
    border-radius: 8px;
    background: #eff6ff;
    color: #2563eb;
    font-size: 9px;
    transition: transform 0.18s ease, background 0.18s ease;
}

.rajb-lokasi.is-open .rajb-lokasi-arrow {
    transform: rotate(180deg);
    background: #dbeafe;
}

.rajb-lokasi-panel {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    z-index: 10050;
    display: none;
    width: max(100%, 380px);
    max-width: min(560px, calc(100vw - 48px));
    padding: 8px;
    border: 1px solid #d8e2ee;
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.16);
}

.rajb-lokasi.is-open .rajb-lokasi-panel {
    display: block;
    animation: rajbLokasiPanelIn 0.14s ease-out;
}

@keyframes rajbLokasiPanelIn {
    from { opacity: 0; transform: translateY(-5px) scale(0.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.rajb-lokasi-search {
    width: 100%;
    height: 38px;
    margin-bottom: 8px;
    padding: 8px 12px;
    border: 1px solid #c8d3e1;
    border-radius: 10px;
    background: #ffffff;
    color: #101828;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 12px;
    outline: 0;
}

.rajb-lokasi-search:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.rajb-lokasi-scroll {
    max-height: 280px;
    overflow: auto;
    scrollbar-width: thin;
    scrollbar-color: #93c5fd #eff3f7;
}

.rajb-lokasi-scroll::-webkit-scrollbar {
    width: 10px;
}

.rajb-lokasi-scroll::-webkit-scrollbar-track {
    background: #eff3f7;
}

.rajb-lokasi-scroll::-webkit-scrollbar-thumb {
    border: 2px solid #eff3f7;
    border-radius: 999px;
    background: linear-gradient(180deg, #60a5fa, #2563eb);
}

.rajb-lokasi-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 3px;
    table-layout: fixed;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 12px;
}

.rajb-lokasi-table td {
    height: 36px;
    padding: 6px 10px;
    border: 0;
    background: #ffffff;
    color: #475467;
    vertical-align: middle;
}

/* Lebar kolom kode dikunci, jadi deskripsi selalu mulai di titik sama. */
.rajb-lokasi-table td:first-child {
    width: 78px;
    border-radius: 10px 0 0 10px;
    color: #1d4ed8;
    font-family: "SFMono-Regular", Consolas, monospace;
    font-size: 10.5px;
    font-weight: 800;
}

.rajb-lokasi-table td:last-child {
    border-radius: 0 10px 10px 0;
    overflow: hidden;
    font-weight: 650;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rajb-lokasi-table tbody tr {
    cursor: pointer;
}

.rajb-lokasi-table tbody tr:hover td {
    background: #f4f8ff;
    color: #1d4ed8;
}

.rajb-lokasi-table tbody tr.is-active td {
    background: #eff6ff;
    color: #1d4ed8;
}

.rajb-lokasi-table tbody tr.is-active td:first-child {
    box-shadow: inset 3px 0 0 #2563eb;
}

.rajb-lokasi-empty td {
    color: #94a3b8;
    cursor: default;
    text-align: center;
}

.rajb-lokasi-empty:hover td {
    background: #ffffff;
    color: #94a3b8;
}

.rajb-lookup {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px;
    gap: 8px;
}

.rajb-lookup-display {
    display: flex;
    align-items: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rajb-lookup-button {
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

.rajb-lookup-button:hover {
    transform: translateY(-1px);
    background: linear-gradient(145deg, #dbeafe 0%, #bfdbfe 100%);
    box-shadow: 0 7px 15px rgba(37, 99, 235, 0.12);
}

.rajb-action-stack {
    position: relative;
    width: 82px;
    height: 42px;
    grid-column: 3;
    grid-row: 1;
    align-self: start;
    justify-self: end;
}

.rajb-action {
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

.rajb-action-stack .rajb-action:first-child {
    position: absolute;
    top: 0;
    right: -20px;
    border-radius: 13px 0 0 13px;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
}

.rajb-action-stack .rajb-action:first-child:hover {
    transform: translateY(-1px);
    background: linear-gradient(135deg, #2f6ff0 0%, #1e4fc4 100%);
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.30);
}

#rajbPrintButton {
    position: absolute;
    top: 55px;
    right: -20px;
    border-radius: 13px 0 0 13px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(5, 150, 105, 0.22);
}

#rajbPrintButton:hover:not(:disabled) {
    transform: translateY(-1px);
    background: linear-gradient(135deg, #14b88a 0%, #047857 100%);
    box-shadow: 0 12px 24px rgba(5, 150, 105, 0.28);
}

#rajbPrintButton:disabled,
#rajbPrintButton:disabled:hover,
#rajbPrintButton:disabled:focus {
    transform: none;
    border: 1px solid #d5dde7;
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
    color: #94a3b8;
    box-shadow: none;
    cursor: not-allowed;
    opacity: 1;
}

.rajb-report-area {
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

.rajb-report-area::before {
    content: "";
    position: absolute;
    top: 0;
    left: 7%;
    right: 7%;
    height: 3px;
    border-radius: 0 0 999px 999px;
    background: linear-gradient(90deg, transparent, #38bdf8, #2563eb, #6366f1, transparent);
}

#rajbLoading {
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

.rajb-paper {
    width: 100%;
    min-height: 0;
    margin: 0;
    padding: 0;
    border: 0;
    background: transparent;
    color: #172033;
}

.rajb-initial {
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

.rajb-initial-icon {
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

.rajb-report-header {
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

.rajb-company {
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 850;
    letter-spacing: 0.04em;
    line-height: 1.35;
}

.rajb-title-wrap {
    min-width: 0;
    text-align: center;
}

.rajb-report-title {
    display: block;
    margin: 0;
    color: #172033;
    font-family: Cambria, Georgia, "Times New Roman", serif;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.rajb-report-center-meta {
    margin-top: 5px;
    color: #475467;
    font-size: 10.5px;
    font-weight: 650;
    line-height: 1.45;
}

.rajb-report-date {
    color: #475467;
    text-align: right;
    font-size: 10.5px;
    font-weight: 650;
    line-height: 1.55;
}

.rajb-report-subtitle {
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

.rajb-subtitle-label {
    text-align: left;
}

.rajb-subtitle-value {
    min-width: 0;
    color: #344054;
    text-align: center;
    font-weight: 850;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rajb-live-badge {
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

.rajb-live-badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
}

.rajb-table-wrap {
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

.rajb-table-wrap::-webkit-scrollbar,
.rajb-modal-table-wrap::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.rajb-table-wrap::-webkit-scrollbar-track,
.rajb-modal-table-wrap::-webkit-scrollbar-track {
    background: #eff3f7;
}

.rajb-table-wrap::-webkit-scrollbar-thumb,
.rajb-modal-table-wrap::-webkit-scrollbar-thumb {
    border: 2px solid #eff3f7;
    border-radius: 999px;
    background: linear-gradient(180deg, #60a5fa, #2563eb);
}

.rajb-report-table {
    width: 1420px;
    min-width: 1420px;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
    color: #344054;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10.5px;
}

.rajb-report-table th,
.rajb-report-table td {
    padding: 8px 9px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
    overflow-wrap: anywhere;
}

.rajb-report-table th {
    position: sticky;
    z-index: 4;
    background: linear-gradient(180deg, #eff6ff 0%, #e5effb 100%);
    color: #344054;
    text-align: center;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-weight: 900;
    line-height: 1.25;
}

.rajb-report-table thead tr:first-child th {
    top: 0;
    z-index: 5;
    height: 44px;
}

.rajb-report-table thead tr:first-child th[colspan] {
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    color: #3730a3;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.rajb-report-table thead tr:nth-child(2) th {
    top: 44px;
    z-index: 4;
    height: 34px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    color: #475467;
    font-size: 9.5px;
}

.rajb-report-table thead tr:first-child th[rowspan] {
    z-index: 6;
}

.rajb-report-table td {
    height: 44px;
    background: #ffffff;
    color: #344054;
    line-height: 1.38;
}

.rajb-report-table tbody tr:nth-child(even):not(.rajb-sector-row) td {
    background: #fbfcfe;
}

.rajb-report-table tbody tr.rajb-data-row:hover td {
    background: #f0f7ff;
}

.rajb-report-table tbody tr.rajb-data-row:hover td:first-child {
    box-shadow: inset 4px 0 0 #2563eb;
    color: #1d4ed8;
    font-weight: 900;
}

.rajb-sector-row td {
    height: auto;
    padding: 8px 10px;
    background: linear-gradient(90deg, #ffffff 0%, #f8fafc 55%, #eff6ff 100%) !important;
    color: #344054;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10px;
    font-weight: 850;
    text-align: left;
}

.rajb-center {
    text-align: center;
}

.rajb-left {
    text-align: left;
}

.rajb-empty {
    height: 130px;
    color: #64748b;
    text-align: center;
}

/* =========================================================
   FOOTER TANDA TANGAN
   Nilai mengikuti Daftar Sertifikat Pecahan.
   ========================================================= */
.rajb-signature-footer {
    width: min(100%, 980px);
    min-height: 190px;
    margin: 18px auto 2px;
    padding: 0 34px 18px;
    color: #344054;
    font-size: 11px;
}

.rajb-signature-footer-date {
    width: 50%;
    margin: 0 0 8px auto;
    text-align: center;
    color: #475467;
    font-weight: 600;
}

.rajb-signature-footer-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 90px;
}

.rajb-signature-footer-box {
    text-align: center;
}

.rajb-signature-footer-role {
    min-height: 20px;
    color: #344054;
    font-weight: 600;
}

.rajb-signature-footer-space {
    height: 78px;
}

.rajb-signature-footer-line {
    display: inline-flex;
    width: min(100%, 220px);
    align-items: flex-end;
    justify-content: center;
    color: #667085;
}

.rajb-signature-footer-line::before {
    content: "(";
    margin-right: 3px;
}

.rajb-signature-footer-line::after {
    content: ")";
    margin-left: 3px;
}

.rajb-signature-footer-line > span {
    display: block;
    width: 100%;
    height: 10px;
    border-bottom: 1px dotted #98a2b3;
}


/* =========================================================
   TAMBAHAN KHUSUS REKAP
   ========================================================= */
.rajb-check-row {
    display: flex;
    min-height: 42px;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.rajb-checkbox {
    display: inline-flex;
    min-height: 32px;
    align-items: center;
    gap: 7px;
    margin: 0;
    padding: 6px 12px;
    border: 1px solid #e5eaf2;
    border-radius: 999px;
    background: #fafbfc;
    color: #475467;
    cursor: pointer;
    font-size: 11px;
    font-weight: 750;
    white-space: nowrap;
    transition: border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
}

.rajb-checkbox:hover {
    border-color: #bfdbfe;
    background: #eff6ff;
    color: #1d4ed8;
}

.rajb-checkbox input {
    width: 15px;
    height: 15px;
    margin: 0;
    accent-color: #2563eb;
}

.rajb-checkbox:has(input:checked) {
    border-color: #bfdbfe;
    background: linear-gradient(135deg, #eff6ff, #e0f2fe);
    color: #1e40af;
}

.rajb-number {
    color: #1e3a5f;
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.rajb-total-row td {
    background: #eff6ff !important;
    color: #1e3a5f;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-weight: 900;
}

.rajb-total-label {
    letter-spacing: 0.18em;
    text-align: right;
}

#rajbSektorModal {
    position: fixed;
    inset: 0;
    z-index: 1065;
    display: none;
    overflow: auto;
    background: rgba(71, 85, 105, 0.24);
}

#rajbSektorModal.show {
    display: block;
}

.rajb-modal-dialog {
    width: calc(100vw - 32px);
    max-width: 800px;
    margin: 24px auto;
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
}

.rajb-modal-header {
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

.rajb-modal-close {
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

.rajb-modal-body {
    padding: 16px;
    background: #f8fafc;
}

.rajb-modal-search {
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

.rajb-modal-search:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.rajb-modal-table-wrap {
    max-height: 430px;
    overflow: auto;
    border: 1px solid #dbe3ef;
    border-radius: 16px;
    background: #ffffff;
}

.rajb-modal-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.rajb-modal-table th,
.rajb-modal-table td {
    padding: 10px 12px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}

.rajb-modal-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: linear-gradient(180deg, #eff6ff 0%, #e7f0fc 100%);
    color: #344054;
    text-align: center;
    font-weight: 850;
}

.rajb-modal-table tbody tr {
    cursor: pointer;
}

.rajb-modal-table tbody tr:hover td {
    background: #eff6ff;
    color: #1d4ed8;
}

/* =========================================================
   ALERT DATA KOSONG — SAMA DENGAN DAFTAR SERTIFIKAT PECAHAN
   Modal informasi yang tampil saat hasil laporan tidak
   menghasilkan baris data sama sekali.
   ========================================================= */
#rajbNoDataAlertModal .modal-dialog {
    max-width: 380px;
}

#rajbNoDataAlertModal .modal-content {
    border: 0;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
}

#rajbNoDataAlertModal .alert-icon-wrapper {
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

#rajbNoDataAlertModal .alert-title {
    margin-bottom: 8px;
    color: #172033;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 18px;
    font-weight: 700;
}

#rajbNoDataAlertModal .alert-message {
    margin-bottom: 24px;
    color: #475569;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 14px;
}

#rajbNoDataAlertModal .alert-btn-ok {
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

#rajbNoDataAlertModal .alert-btn-ok:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
}

@media screen and (max-width: 719px) {
    html, body {
        min-width: 720px;
    }
    .rajb-page {
        min-width: 720px;
    }
}

@media print {
    .rajb-toolbar, .rajb-filter, #rajbLoading, #rajbSektorModal,
    #rajbNoDataAlertModal, .modal-backdrop, .main-sidebar, .control-sidebar,
    .main-header, .main-footer, .navbar, .sidebar {
        display: none !important;
    }
    html, body, .wrapper, .content-wrapper, .main-content, .content,
    .page-wrapper, .page-content, .container, .container-fluid,
    .rajb-page, .rajb-report-area, .rajb-paper {
        width: 100% !important;
        min-width: 0 !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        background: #fff !important;
        box-shadow: none !important;
    }
    .rajb-table-wrap {
        max-height: none !important;
        overflow: visible !important;
    }
    .rajb-report-table {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
    }
    .rajb-signature-footer {
        color: #000 !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
    .rajb-signature-footer-date,
    .rajb-signature-footer-role,
    .rajb-signature-footer-line {
        color: #000 !important;
    }
    .rajb-signature-footer-line > span {
        border-bottom-color: #444 !important;
    }
}
</style>

<!-- MODAL ALERT DATA KOSONG -->
<div class="modal fade" id="rajbNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4" style="text-align: center; padding: 1.5rem;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message" id="rajbNoDataMessage">Data tidak ditemukan.</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    data-dismiss="modal"
                    onclick="hideRajbNoDataAlert()"
                >OK</button>
            </div>
        </div>
    </div>
</div>

<div class="rajb-page">
    <div class="rajb-toolbar">
        <div class="rajb-toolbar-title">
            Daftar Rekap PPAT/Akta Jual Beli
        </div>
        <code class="rajb-unit-badge">
            UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}
        </code>
    </div>

    <section class="rajb-filter">
        <input
            type="hidden"
            id="rajbPerusahaan"
            value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}"
        >
        <input
            type="hidden"
            id="rajbNamaPerusahaan"
            value="{{ session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? '' }}"
        >
        <input type="hidden" id="rajbSektor" value="*">

        <div class="rajb-filter-grid">
            <div class="rajb-field">
                <label class="rajb-label" for="rajbBlokAwal">Blok</label>
                <div class="rajb-range">
                    <input
                        type="text"
                        id="rajbBlokAwal"
                        class="rajb-input"
                        value="A"
                        maxlength="30"
                        autocomplete="off"
                    >
                    <span class="rajb-separator">s.d</span>
                    <input
                        type="text"
                        id="rajbBlokAkhir"
                        class="rajb-input"
                        value="Z"
                        maxlength="30"
                        autocomplete="off"
                    >
                </div>
            </div>

            <div class="rajb-field">
                <label class="rajb-label" for="rajbTglAwal">Tanggal AJB</label>
                <div class="rajb-range">
                    <input type="date" id="rajbTglAwal" class="rajb-input">
                    <span class="rajb-separator">s.d</span>
                    <input type="date" id="rajbTglAkhir" class="rajb-input">
                </div>
            </div>

            <div class="rajb-action-stack">
                <button
                    type="button"
                    class="rajb-action"
                    onclick="getRajbData()"
                >
                    Ok
                </button>
                <button
                    type="button"
                    class="rajb-action"
                    id="rajbPrintButton"
                    onclick="printRajbReport()"
                    aria-disabled="true"
                    disabled
                >
                    Print
                </button>
            </div>

            <div class="rajb-field">
                <span class="rajb-label">Lokasi</span>
                <div class="rajb-lokasi" id="rajbLokasiDropdown">
                    <input type="hidden" id="rajbLokasi" value="*">
                    <input type="hidden" id="rajbLokasiNama" value="Semua Lokasi">

                    <button
                        type="button"
                        class="rajb-lokasi-selected"
                        onclick="toggleRajbLokasiPanel(event)"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                    >
                        <span class="rajb-lokasi-code" id="rajbLokasiCode">*</span>
                        <span class="rajb-lokasi-name" id="rajbLokasiName">Semua Lokasi</span>
                        <span class="rajb-lokasi-arrow" aria-hidden="true">
                            <i class="fas fa-chevron-down"></i>
                        </span>
                    </button>

                    <div class="rajb-lokasi-panel" id="rajbLokasiPanel" role="listbox">
                        <input
                            type="text"
                            class="rajb-lokasi-search"
                            id="rajbLokasiSearch"
                            placeholder="Cari kode atau nama lokasi..."
                            onkeyup="filterRajbLokasi(this.value)"
                            autocomplete="off"
                        >
                        <div class="rajb-lokasi-scroll">
                            <table class="rajb-lokasi-table">
                                <tbody id="rajbLokasiBody">
                                    <tr class="is-active" data-kode="*" data-nama="Semua Lokasi">
                                        <td>*</td>
                                        <td>Semua Lokasi</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Baris 2 kolom 2 dan 3 dikosongkan. Pada desktop kolom itu
                 berisi label "Status Berkas:" tanpa isian. --}}
            <div></div>
            <div></div>

            <div class="rajb-field">
                <span class="rajb-label">Sektor/Cluster</span>
                <div class="rajb-lookup">
                    <div id="rajbSektorEntry" class="rajb-lookup-display">Semua Sektor</div>
                    <button
                        type="button"
                        class="rajb-lookup-button"
                        onclick="getRajbSektorModal()"
                        title="Pilih sektor/cluster"
                        aria-label="Pilih sektor/cluster"
                    >
                        <i class="fas fa-binoculars"></i>
                    </button>
                </div>
            </div>

            <div class="rajb-field">
                <span class="rajb-label">Status Akta</span>
                <div class="rajb-check-row">
                    <label class="rajb-checkbox">
                        <input type="checkbox" id="rajbBelumTtdAkta" autocomplete="off">
                        <span>Belum Ttd Akta</span>
                    </label>
                </div>
            </div>
        </div>
    </section>

    <section class="rajb-report-area">
        <div id="rajbLoading">
            <i class="fas fa-spinner fa-spin"></i>
            Mengambil data Rekap PPAT/Akta Jual Beli...
        </div>

        <div id="rajbMainDisplay">
            <div class="rajb-paper">
                <div class="rajb-initial">
                    <i class="fas fa-table rajb-initial-icon" aria-hidden="true"></i>
                    <div>Silahkan isi filter kemudian klik OK</div>
                </div>
            </div>
        </div>
    </section>

    <div id="rajbSektorModal" aria-hidden="true">
        <div class="rajb-modal-dialog">
            <div class="rajb-modal-header">
                <span>Pilih Sektor/Cluster</span>
                <button
                    type="button"
                    class="rajb-modal-close"
                    onclick="toggleRajbSektorModal(false)"
                    aria-label="Tutup"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="rajb-modal-body" id="rajbSektorModalContent"></div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    var lastRajbRows = null;

    $(document).ready(function () {
        /*
         * Browser memulihkan isi form saat halaman di-refresh, sehingga
         * centang Belum Ttd Akta dan isian blok dapat terbawa dari
         * kunjungan sebelumnya. Reset dipanggil bertahap karena pemulihan
         * itu dapat terjadi setelah DOMContentLoaded.
         */
        resetRajbInitialState();
        window.setTimeout(resetRajbInitialState, 10);
        window.setTimeout(resetRajbInitialState, 100);

        loadRajbLokasi();

        $('#rajbBlokAwal, #rajbBlokAkhir').on('input', function () {
            $(this).val(String($(this).val() || '').toUpperCase());
        });

        /*
         * Mengubah pilihan Belum Ttd Akta langsung memuat ulang laporan,
         * tanpa perlu menekan OK lagi. resetRajbPrint() dipanggil lebih
         * dulu supaya tombol Print tidak sempat aktif untuk laporan lama
         * bila filter ternyata belum sah dan permintaan dibatalkan.
         */
        $('#rajbBelumTtdAkta').on('change', function () {
            syncRajbTanggalState();
            resetRajbPrint();
            getRajbData();
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                toggleRajbSektorModal(false);
                closeRajbLokasiPanel();
            }
        });

        $(document).on('click', '#rajbLokasiBody tr', function () {
            if ($(this).hasClass('rajb-lokasi-empty')) {
                return;
            }

            chooseRajbLokasi(
                $(this).attr('data-kode'),
                $(this).attr('data-nama')
            );
        });

        $(document).on('click', function (event) {
            if (!$(event.target).closest('#rajbLokasiDropdown').length) {
                closeRajbLokasiPanel();
            }
        });
    });

    $(window).on('load', function () {
        resetRajbInitialState();
    });

    $(window).on('pageshow', function (event) {
        var pageEvent = event.originalEvent || event;

        if (pageEvent.persisted) {
            resetRajbInitialState();
        }
    });

    /*
     * Mengembalikan seluruh filter dan area laporan ke keadaan awal,
     * seperti saat fitur ini baru dibuka.
     */
    function resetRajbInitialState() {
        $('#rajbBlokAwal').val('A');
        $('#rajbBlokAkhir').val('Z');
        $('#rajbBelumTtdAkta').prop('checked', false);

        $('#rajbSektor').val('*');
        $('#rajbSektorEntry').text('Semua Sektor');

        $('#rajbLokasi').val('*');
        $('#rajbLokasiNama').val('Semua Lokasi');
        $('#rajbLokasiCode').text('*');
        $('#rajbLokasiName').text('Semua Lokasi');
        $('#rajbLokasiBody tr').removeClass('is-active');
        $('#rajbLokasiBody tr').filter(function () {
            return String($(this).attr('data-kode') || '') === '*';
        }).addClass('is-active');
        closeRajbLokasiPanel();
        toggleRajbSektorModal(false);

        setRajbDefaultDate();
        syncRajbTanggalState();
        resetRajbPrint();

        $('#rajbLoading').hide();
        $('#rajbMainDisplay').html(
            '<div class="rajb-paper">'
            + '<div class="rajb-initial">'
            + '<i class="fas fa-table rajb-initial-icon" aria-hidden="true"></i>'
            + '<div>Silahkan isi filter kemudian klik OK</div>'
            + '</div></div>'
        );
    }

    function setRajbDefaultDate() {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var today = year + '-' + month + '-' + day;

        $('#rajbTglAwal').val(today);
        $('#rajbTglAkhir').val(today);
    }

    function isRajbBelumTtdAkta() {
        return $('#rajbBelumTtdAkta').is(':checked');
    }

    /*
     * Query desktop untuk "Belum Ttd Akta" tidak memakai rentang tanggal,
     * jadi isian tanggal dinonaktifkan agar tidak terlihat berpengaruh.
     * Nilainya tetap dikirim supaya aturan validasi controller terpenuhi.
     */
    function syncRajbTanggalState() {
        var nonaktif = isRajbBelumTtdAkta();

        $('#rajbTglAwal, #rajbTglAkhir')
            .prop('disabled', nonaktif)
            .css('opacity', nonaktif ? 0.55 : 1)
            .attr(
                'title',
                nonaktif
                    ? 'Tidak dipakai saat Belum Ttd Akta dicentang'
                    : ''
            );
    }

    function setRajbPrintEnabled(enabled) {
        var disabled = enabled !== true;
        $('#rajbPrintButton')
            .prop('disabled', disabled)
            .attr('aria-disabled', disabled ? 'true' : 'false');
    }

    function resetRajbPrint() {
        lastRajbRows = null;
        setRajbPrintEnabled(false);
        hideRajbNoDataAlert();
    }

    /*
     * Alert data kosong, mengikuti Daftar Sertifikat Pecahan.
     */
    function showRajbNoDataAlert(message) {
        var $modal = $('#rajbNoDataAlertModal');

        $('#rajbNoDataMessage').text(message || 'Data tidak ditemukan......!');

        if (typeof $modal.modal === 'function') {
            $modal.modal('show');
            return;
        }

        if (!$('.rajb-nodata-backdrop').length) {
            $('<div class="modal-backdrop fade show rajb-nodata-backdrop"></div>')
                .appendTo(document.body);
        }

        $modal
            .addClass('show')
            .css('display', 'block')
            .attr('aria-hidden', 'false');
    }

    function hideRajbNoDataAlert() {
        var $modal = $('#rajbNoDataAlertModal');

        if (typeof $modal.modal === 'function') {
            $modal.modal('hide');
            return;
        }

        $modal
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true');

        $('.rajb-nodata-backdrop').remove();
    }

    function rajbEscapeHtml(value) {
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

    function rajbEscapeJs(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r?\n/g, ' ');
    }

    function rajbValue(value) {
        return value === null || value === undefined || value === ''
            ? '-'
            : value;
    }

    function rajbPick(item, keys) {
        item = item || {};

        for (var i = 0; i < keys.length; i++) {
            var value = item[keys[i]];
            if (value !== null && value !== undefined && value !== '') {
                return value;
            }
        }

        return null;
    }

    function rajbFormatDate(value) {
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

    function rajbNumber(value) {
        var number = Number(value);
        return isFinite(number) ? number : 0;
    }

    /* Kolom rupiah memakai pemisah ribuan tanpa desimal, seperti desktop. */
    function rajbFormatCurrency(value) {
        if (value === null || value === undefined || String(value).trim() === '') {
            return '';
        }

        var number = Number(value);

        if (!isFinite(number)) {
            return rajbEscapeHtml(value);
        }

        return number.toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    function rajbFormatTanggalIndonesia(dateValue) {
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

        return String(date.getDate()).padStart(2, '0')
            + ' ' + bulan[date.getMonth()]
            + ' ' + date.getFullYear();
    }

    /* Nama PT untuk header laporan, sama dengan fitur lain. */
    function rajbExtractCompanyName(value) {
        var raw = String(value || '').replace(/\u00a0/g, ' ');
        var locationMatch = raw.match(/Lokasi\s*:[^\r\n|]*?-\s*([^\r\n|]+)/i);
        var companyMatch = raw.match(/\b((?:PT\.?|CV\.?|PD\.?|PERUM)\s+[^\r\n|]+)/i);
        var name = locationMatch ? locationMatch[1] : (companyMatch ? companyMatch[1] : '');

        return String(name || '')
            .replace(/\s+(?:Hi\s*,|Logout\b|Keluar\b|LAND DOCUMENT\b|UNIT\s+[A-Z0-9_-]+\b).*$/i, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function rajbCompanyNameFromLayout() {
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
                var name = rajbExtractCompanyName(candidates[j]);
                if (name) {
                    return name;
                }
            }
        }

        return '';
    }

    function rajbCompanyName(first) {
        var unit = String($('#rajbPerusahaan').val() || '').trim().toUpperCase();
        var rowName = rajbPick(first || {}, [
            'NAMA_PT', 'nama_pt',
            'ATAS_NAMA_PT', 'atas_nama_pt',
            'NAMA_PERUSAHAAN', 'nama_perusahaan'
        ]);
        var sessionName = String($('#rajbNamaPerusahaan').val() || '').trim();
        var cacheKey = 'sris.report-company.' + unit;
        var cachedName = '';

        try {
            cachedName = localStorage.getItem(cacheKey) || '';
        } catch (error) {
            cachedName = '';
        }

        var company = rajbExtractCompanyName(rowName)
            || String(rowName || '').trim()
            || rajbCompanyNameFromLayout()
            || rajbExtractCompanyName(sessionName)
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
       LOKASI
       ============================================== */

    function toggleRajbLokasiPanel(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        var $dropdown = $('#rajbLokasiDropdown');
        var akanDibuka = !$dropdown.hasClass('is-open');

        $dropdown.toggleClass('is-open', akanDibuka);
        $dropdown.find('.rajb-lokasi-selected')
            .attr('aria-expanded', akanDibuka ? 'true' : 'false');

        if (akanDibuka) {
            $('#rajbLokasiSearch').val('');
            filterRajbLokasi('');
            $('#rajbLokasiSearch').trigger('focus');
        }
    }

    function closeRajbLokasiPanel() {
        $('#rajbLokasiDropdown').removeClass('is-open')
            .find('.rajb-lokasi-selected')
            .attr('aria-expanded', 'false');
    }

    function chooseRajbLokasi(kode, nama) {
        kode = String(kode || '*');
        nama = String(nama || 'Semua Lokasi');

        $('#rajbLokasi').val(kode);
        $('#rajbLokasiNama').val(nama);
        $('#rajbLokasiCode').text(kode);
        $('#rajbLokasiName').text(nama);

        $('#rajbLokasiBody tr').removeClass('is-active');
        $('#rajbLokasiBody tr').each(function () {
            if (String($(this).attr('data-kode') || '') === kode) {
                $(this).addClass('is-active');
            }
        });

        closeRajbLokasiPanel();
        resetRajbPrint();
    }

    function filterRajbLokasi(keyword) {
        var search = String(keyword || '').toLowerCase().trim();

        $('#rajbLokasiBody tr').not('.rajb-lokasi-empty').each(function () {
            $(this).toggle(
                $(this).text().toLowerCase().indexOf(search) !== -1
            );
        });
    }

    function loadRajbLokasi() {
        var perusahaan = String($('#rajbPerusahaan').val() || '').trim();

        if (!perusahaan) {
            return;
        }

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_lokasi',
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

                html += '<tr class="is-active" data-kode="*" ';
                html += 'data-nama="Semua Lokasi">';
                html += '<td>*</td><td>Semua Lokasi</td></tr>';

                $.each(rows, function (index, item) {
                    var kode = String(item.KD_LOKASI || item.kd_lokasi || '').trim();
                    var nama = String(
                        item.DESKRIPSI || item.deskripsi || kode
                    ).trim();

                    html += '<tr data-kode="' + rajbEscapeHtml(kode) + '" ';
                    html += 'data-nama="' + rajbEscapeHtml(nama) + '">';
                    html += '<td>' + rajbEscapeHtml(kode) + '</td>';
                    html += '<td title="' + rajbEscapeHtml(nama) + '">'
                        + rajbEscapeHtml(nama) + '</td>';
                    html += '</tr>';
                });

                $('#rajbLokasiBody').html(html);
            },
            error: function (xhr) {
                var keterangan = 'Daftar lokasi gagal dimuat';

                if (xhr && xhr.status) {
                    keterangan += ' (HTTP ' + xhr.status + ')';
                }

                $('#rajbLokasiBody').html(
                    '<tr class="is-active" data-kode="*" '
                    + 'data-nama="Semua Lokasi">'
                    + '<td>*</td><td>Semua Lokasi</td></tr>'
                    + '<tr class="rajb-lokasi-empty"><td colspan="2">'
                    + rajbEscapeHtml(keterangan)
                    + '</td></tr>'
                );
            }
        });
    }

    /* ==============================================
       SEKTOR
       ============================================== */

    function toggleRajbSektorModal(show) {
        $('#rajbSektorModal')
            .toggleClass('show', show === true)
            .attr('aria-hidden', show === true ? 'false' : 'true');
    }

    function addRajbSektor(kode, deskripsi) {
        $('#rajbSektor').val(kode || '*');
        $('#rajbSektorEntry').text(deskripsi || 'Semua Sektor');
        toggleRajbSektorModal(false);
        resetRajbPrint();
    }

    function filterRajbSektor(keyword) {
        var search = String(keyword || '').toLowerCase().trim();

        $('#rajbSektorModal .rajb-modal-table tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(search) !== -1);
        });
    }

    function getRajbSektorModal() {
        var perusahaan = String($('#rajbPerusahaan').val() || '').trim();

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
                html += '<input type="text" class="rajb-modal-search" ';
                html += 'placeholder="Cari sektor/cluster..." ';
                html += 'onkeyup="filterRajbSektor(this.value)">';
                html += '<div class="rajb-modal-table-wrap">';
                html += '<table class="rajb-modal-table"><thead><tr>';
                html += '<th>Kode</th><th>Deskripsi</th><th>Perusahaan</th>';
                html += '</tr></thead><tbody>';

                html += '<tr onclick="addRajbSektor(\'*\', \'Semua Sektor\')">';
                html += '<td>*</td><td>Semua Sektor</td>';
                html += '<td>' + rajbEscapeHtml(perusahaan) + '</td></tr>';

                $.each(rows, function (index, item) {
                    var kode = item.KD_SEKTOR || item.kd_sektor || '';
                    var deskripsi = item.DESKRIPSI || item.deskripsi || kode;
                    var unit = item.KD_PERUSAHAAN || item.kd_perusahaan || perusahaan;

                    html += '<tr onclick="addRajbSektor(\''
                        + rajbEscapeJs(kode) + '\', \''
                        + rajbEscapeJs(deskripsi) + '\')">';
                    html += '<td>' + rajbEscapeHtml(kode) + '</td>';
                    html += '<td>' + rajbEscapeHtml(deskripsi) + '</td>';
                    html += '<td>' + rajbEscapeHtml(unit) + '</td></tr>';
                });

                if (rows.length < 1) {
                    html += '<tr><td colspan="3" ';
                    html += 'style="padding:22px;text-align:center;">';
                    html += 'Data sektor tidak ditemukan.</td></tr>';
                }

                html += '</tbody></table></div>';

                $('#rajbSektorModalContent').html(html);
                toggleRajbSektorModal(true);
                $('#rajbSektorModal .rajb-modal-search').trigger('focus');
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

    /* ==============================================
       DATA LAPORAN
       ============================================== */

    function validateRajbFilter() {
        if (!$('#rajbBlokAwal').val() || !$('#rajbBlokAkhir').val()) {
            alert('Rentang blok wajib diisi.');
            return false;
        }

        if (isRajbBelumTtdAkta()) {
            return true;
        }

        if (!$('#rajbTglAwal').val() || !$('#rajbTglAkhir').val()) {
            alert('Rentang Tanggal AJB wajib diisi.');
            return false;
        }

        if ($('#rajbTglAwal').val() > $('#rajbTglAkhir').val()) {
            alert('Tanggal awal tidak boleh melebihi tanggal akhir.');
            return false;
        }

        return true;
    }

    function getRajbFilterData() {
        var hariIni = new Date();
        var tglCadangan = hariIni.getFullYear()
            + '-' + String(hariIni.getMonth() + 1).padStart(2, '0')
            + '-' + String(hariIni.getDate()).padStart(2, '0');

        return {
            _token: '{{ csrf_token() }}',
            blok_awal: String($('#rajbBlokAwal').val() || 'A').toUpperCase(),
            blok_akhir: String($('#rajbBlokAkhir').val() || 'ZZ').toUpperCase(),
            tgl_awal: $('#rajbTglAwal').val() || tglCadangan,
            tgl_akhir: $('#rajbTglAkhir').val() || tglCadangan,
            perusahaan: $('#rajbPerusahaan').val(),
            lokasi: $('#rajbLokasi').val() || '*',
            sektor: $('#rajbSektor').val() || '*',
            belum_ttd_akta: isRajbBelumTtdAkta() ? 'Y' : 'T'
        };
    }

    function getRajbData() {
        if (!validateRajbFilter()) {
            return;
        }

        resetRajbPrint();
        $('#rajbLoading').show();

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_data',
            dataType: 'json',
            timeout: 120000,
            headers: { 'Accept': 'application/json' },
            data: getRajbFilterData(),
            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (response && Array.isArray(response.data) ? response.data : []);

                lastRajbRows = rows;

                if (rows.length === 0) {
                    showRajbNoDataAlert(
                        isRajbBelumTtdAkta()
                            ? 'Data Belum Ttd Akta tidak ditemukan......!'
                            : 'Data tidak ditemukan......!'
                    );
                }

                renderRajbReport(rows);
                setRajbPrintEnabled(rows.length > 0);
            },
            error: function (xhr, textStatus, errorThrown) {
                resetRajbPrint();

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

                $('#rajbMainDisplay').html(
                    '<div class="rajb-paper">'
                    + '<div style="padding:16px;color:#a00;">'
                    + 'Gagal mengambil data Rekap PPAT/Akta Jual Beli. '
                    + rajbEscapeHtml(detail)
                    + '</div></div>'
                );
            },
            complete: function () {
                $('#rajbLoading').hide();
            }
        });
    }

    function renderRajbReport(rows) {
        rows = Array.isArray(rows) ? rows : [];

        var belumTtd = isRajbBelumTtdAkta();
        var first = rows.length > 0 ? rows[0] : {};
        var company = rajbCompanyName(first);

        var blok = String($('#rajbBlokAwal').val() || 'A').toUpperCase()
            + ' s/d '
            + String($('#rajbBlokAkhir').val() || 'ZZ').toUpperCase();

        var periode = rajbFormatDate($('#rajbTglAwal').val())
            + ' s/d '
            + rajbFormatDate($('#rajbTglAkhir').val());

        var lokasiTampil = String(
            $('#rajbLokasiNama').val() || 'Semua Lokasi'
        ).trim();

        var sektorTampil = String(
            $('#rajbSektorEntry').text() || 'Semua Sektor'
        ).trim();

        var now = new Date();
        var today = String(now.getDate()).padStart(2, '0')
            + '-' + String(now.getMonth() + 1).padStart(2, '0')
            + '-' + now.getFullYear();

        /* Saat Belum Ttd Akta, kolom akta dan harga tidak ditampilkan. */
        var jumlahKolom = belumTtd ? 10 : 14;

        var html = '';

        html += '<div class="rajb-paper">';

        html += '<div class="rajb-report-header">';
        html += '<div class="rajb-company">' + rajbEscapeHtml(company) + '</div>';
        html += '<div class="rajb-title-wrap">';
        html += '<h2 class="rajb-report-title">Rekapitulasi PPAT/Akta Jual Beli';
        html += belumTtd ? ' (Belum Ttd Akta)' : '';
        html += '</h2>';
        html += '<div class="rajb-report-center-meta">';
        html += 'BLOK : ' + rajbEscapeHtml(blok);

        if (!belumTtd) {
            html += '<br>Tgl. Akta Jual Beli : ' + rajbEscapeHtml(periode);
        }

        html += '</div></div>';
        html += '<div class="rajb-report-date">';
        html += 'Lokasi : ' + rajbEscapeHtml(lokasiTampil);
        html += '<br>Tanggal : ' + rajbEscapeHtml(today);
        html += '<br>Jumlah Data : ' + rows.length;
        html += '</div></div>';

        html += '<div class="rajb-report-subtitle">';
        html += '<span class="rajb-subtitle-label">Sektor/Cluster:</span>';
        html += '<strong class="rajb-subtitle-value">'
            + rajbEscapeHtml(sektorTampil) + '</strong>';
        html += '<span class="rajb-live-badge">Live data</span>';
        html += '</div>';

        html += '<div class="rajb-table-wrap">';
        html += '<table class="rajb-report-table"';
        html += belumTtd ? ' style="width:1180px;min-width:1180px;"' : '';
        html += '>';

        html += '<colgroup>';
        html += '<col style="width:46px">';
        html += '<col style="width:105px">';
        html += '<col style="width:250px">';
        html += '<col style="width:210px">';
        html += '<col style="width:110px">';
        html += '<col style="width:230px">';
        html += '<col style="width:110px">';
        html += '<col style="width:100px">';
        html += '<col style="width:180px">';
        html += '<col style="width:100px">';

        if (!belumTtd) {
            html += '<col style="width:140px">';
            html += '<col style="width:130px">';
            html += '<col style="width:150px">';
            html += '<col style="width:140px">';
        }

        html += '</colgroup>';

        html += '<thead>';
        html += '<tr>';
        html += '<th rowspan="2">No.</th>';
        html += '<th rowspan="2">BLOK/<br>NOMOR</th>';
        html += '<th rowspan="2">Nama Pemilik</th>';
        html += '<th rowspan="2">Nama Penanda Tangan</th>';
        html += '<th colspan="2">PPAT</th>';
        html += '<th colspan="2">Akta Jual Beli</th>';
        html += '<th colspan="2">PPJB</th>';

        if (!belumTtd) {
            html += '<th rowspan="2">Harga Jual<br>di AJB (Rp)</th>';
            html += '<th rowspan="2">Harga NJOP<br>(Rp)</th>';
            html += '<th rowspan="2">Cara Bayar</th>';
            html += '<th rowspan="2">Bank KPR</th>';
        }

        html += '</tr>';
        html += '<tr>';
        html += '<th>Nomor</th><th>Nama</th>';
        html += '<th>Nomor</th><th>Tanggal</th>';
        html += '<th>Nomor</th><th>Tanggal</th>';
        html += '</tr>';
        html += '</thead><tbody>';

        if (rows.length < 1) {
            html += '<tr><td colspan="' + jumlahKolom + '" class="rajb-empty">';
            html += 'Data tidak ditemukan.';
            html += '</td></tr>';
        } else {
            var totalHarga = 0;
            var totalNjop = 0;

            $.each(rows, function (index, item) {
                item = item || {};

                totalHarga += rajbNumber(rajbPick(item, ['HARGA', 'harga']));
                totalNjop += rajbNumber(
                    rajbPick(item, ['HARGA_NJOP', 'harga_njop'])
                );

                html += '<tr class="rajb-data-row">';
                html += '<td class="rajb-center">' + (index + 1) + '</td>';
                html += '<td class="rajb-center">'
                    + rajbEscapeHtml(rajbValue(rajbPick(item, ['BLOK_NOMOR', 'blok_nomor'])))
                    + '</td>';
                html += '<td class="rajb-left">'
                    + rajbEscapeHtml(rajbValue(rajbPick(item, ['NAMA', 'nama'])))
                    + '</td>';
                html += '<td class="rajb-left">'
                    + rajbEscapeHtml(rajbValue(rajbPick(item, ['TTD_AKTA', 'ttd_akta'])))
                    + '</td>';
                html += '<td class="rajb-left">'
                    + rajbEscapeHtml(rajbValue(rajbPick(item, ['NO_NOTARIS', 'no_notaris'])))
                    + '</td>';
                html += '<td class="rajb-left">'
                    + rajbEscapeHtml(rajbValue(rajbPick(item, ['NOTARIS', 'notaris'])))
                    + '</td>';
                html += '<td class="rajb-left">'
                    + rajbEscapeHtml(rajbValue(rajbPick(item, ['NO_AKTA', 'no_akta'])))
                    + '</td>';
                html += '<td class="rajb-center">'
                    + rajbEscapeHtml(rajbFormatDate(rajbPick(item, ['TGL_AKTA', 'tgl_akta'])))
                    + '</td>';
                html += '<td class="rajb-left">'
                    + rajbEscapeHtml(rajbValue(rajbPick(item, ['NO_PPJB', 'no_ppjb'])))
                    + '</td>';
                html += '<td class="rajb-center">'
                    + rajbEscapeHtml(rajbFormatDate(rajbPick(item, ['TGL_PPJB', 'tgl_ppjb'])))
                    + '</td>';

                if (!belumTtd) {
                    html += '<td class="rajb-number">'
                        + rajbEscapeHtml(rajbFormatCurrency(rajbPick(item, ['HARGA', 'harga'])))
                        + '</td>';
                    html += '<td class="rajb-number">'
                        + rajbEscapeHtml(rajbFormatCurrency(rajbPick(item, ['HARGA_NJOP', 'harga_njop'])))
                        + '</td>';
                    html += '<td class="rajb-left">'
                        + rajbEscapeHtml(rajbValue(rajbPick(item, ['TIPE_BAYAR', 'tipe_bayar'])))
                        + '</td>';
                    html += '<td class="rajb-left">'
                        + rajbEscapeHtml(rajbValue(rajbPick(item, ['BANK', 'bank'])))
                        + '</td>';
                }

                html += '</tr>';
            });

            if (!belumTtd) {
                html += '<tr class="rajb-total-row">';
                html += '<td colspan="10" class="rajb-total-label">T O T A L :</td>';
                html += '<td class="rajb-number">'
                    + rajbEscapeHtml(rajbFormatCurrency(totalHarga)) + '</td>';
                html += '<td class="rajb-number">'
                    + rajbEscapeHtml(rajbFormatCurrency(totalNjop)) + '</td>';
                html += '<td colspan="2"></td>';
                html += '</tr>';
            }
        }

        html += '</tbody></table></div>';

        var tanggalTandaTangan = rajbFormatTanggalIndonesia(new Date());

        html += '<div class="rajb-signature-footer">';
        html += '<div class="rajb-signature-footer-date">Jakarta, '
            + rajbEscapeHtml(tanggalTandaTangan) + '</div>';
        html += '<div class="rajb-signature-footer-grid">';
        html += '<div class="rajb-signature-footer-box">';
        html += '<div class="rajb-signature-footer-role">Yang menyerahkan,</div>';
        html += '<div class="rajb-signature-footer-space"></div>';
        html += '<div class="rajb-signature-footer-line"><span></span></div>';
        html += '</div>';
        html += '<div class="rajb-signature-footer-box">';
        html += '<div class="rajb-signature-footer-role">Yang menerima,</div>';
        html += '<div class="rajb-signature-footer-space"></div>';
        html += '<div class="rajb-signature-footer-line"><span></span></div>';
        html += '</div>';
        html += '</div></div>';

        html += '</div>';

        $('#rajbMainDisplay').html(html);
    }

    /* ==============================================
       PRINT
       ============================================== */

    function printRajbReport() {
        if (
            $('#rajbPrintButton').prop('disabled')
            || !Array.isArray(lastRajbRows)
            || !$('#rajbMainDisplay .rajb-report-table').length
        ) {
            return;
        }

        var reportHtml = $('#rajbMainDisplay').html();

        if (!reportHtml) {
            return;
        }

        $('#rajbNativePrintFrame').remove();

        var frame = document.createElement('iframe');
        frame.id = 'rajbNativePrintFrame';
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

            .rajb-paper { width: 100%; margin: 0; padding: 0; }

            .rajb-report-header {
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

            .rajb-company { color: #000; font-size: 10px; font-weight: 700; }
            .rajb-title-wrap { text-align: center; }

            .rajb-report-title {
                margin: 0;
                color: #000;
                font-family: Cambria, Georgia, "Times New Roman", serif;
                font-size: 16px;
                font-weight: 700;
                line-height: 1.2;
            }

            .rajb-report-center-meta,
            .rajb-report-date { color: #000; font-size: 9px; line-height: 1.4; }
            .rajb-report-date { text-align: right; }

            .rajb-report-subtitle {
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

            .rajb-subtitle-value { text-align: center; font-weight: 700; }

            .rajb-live-badge {
                justify-self: end;
                color: #000;
                font-size: 8px;
                font-weight: 700;
            }

            .rajb-live-badge::before {
                content: "";
                display: inline-block;
                width: 5px;
                height: 5px;
                margin-right: 4px;
                border-radius: 50%;
                background: #000;
            }

            .rajb-table-wrap { width: 100%; overflow: visible; border: 0; }

            .rajb-report-table {
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

            .rajb-report-table col { width: auto !important; }
            .rajb-report-table thead { display: table-header-group; }
            .rajb-report-table tbody { display: table-row-group; }

            .rajb-report-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .rajb-report-table th,
            .rajb-report-table td {
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

            .rajb-report-table th { text-align: center; font-weight: 700; }

            .rajb-total-row td {
                background: #fff !important;
                color: #000;
                font-weight: 700;
            }

            .rajb-center { text-align: center; }
            .rajb-left { text-align: left; }
            .rajb-number { text-align: right; }
            .rajb-total-label { text-align: right; letter-spacing: .18em; }

            .rajb-signature-footer {
                width: 100%;
                min-height: 170px;
                margin: 16px auto 0;
                padding: 0 24px 8px;
                color: #000;
                font-size: 10px;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .rajb-signature-footer-date {
                width: 50%;
                margin: 0 0 8px auto;
                text-align: center;
                color: #000;
                font-weight: 600;
            }

            .rajb-signature-footer-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: 90px;
            }

            .rajb-signature-footer-box { text-align: center; }
            .rajb-signature-footer-role,
            .rajb-signature-footer-line { color: #000; }
            .rajb-signature-footer-space { height: 70px; }

            .rajb-signature-footer-line {
                display: inline-flex;
                width: min(100%, 220px);
                align-items: flex-end;
                justify-content: center;
            }

            .rajb-signature-footer-line::before { content: "("; margin-right: 3px; }
            .rajb-signature-footer-line::after { content: ")"; margin-left: 3px; }

            .rajb-signature-footer-line > span {
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
            + '<title>Rekapitulasi PPAT/Akta Jual Beli</title>'
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
                    $('#rajbNativePrintFrame').remove();
                }, 1200);
            }
        }, 180);
    }
</script>
@endsection
