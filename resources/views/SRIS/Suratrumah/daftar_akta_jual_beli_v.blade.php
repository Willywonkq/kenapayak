@extends('layouts.template')

{{-- VIEW VERSION V1-20260902-DESKTOP-LAYOUT --}}
{{-- Tata letak filter dan kolom laporan mengikuti tampilan desktop SRIS. --}}

@section('content')
<style>
.ajb-page,
.ajb-page * {
    box-sizing: border-box;
}

.ajb-page {
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

.ajb-toolbar {
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

.ajb-toolbar::before {
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

.ajb-toolbar::after {
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

.ajb-toolbar-title {
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

.ajb-unit-badge {
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

.ajb-filter {
    position: relative;
    z-index: 30;
    padding: 20px;
    overflow: visible;
    border: 1px solid #dbe3ef;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
}

.ajb-filter::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    border-radius: 24px 0 0 24px;
    background: linear-gradient(180deg, #38bdf8 0%, #2563eb 52%, #1d4ed8 100%);
}

.ajb-filter::after {
    content: "Akta Jual Beli";
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

.ajb-filter-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 82px;
    gap: 13px 14px;
    align-items: center;
}

.ajb-field {
    display: grid;
    grid-template-columns: 112px minmax(0, 1fr);
    gap: 9px;
    align-items: center;
    min-width: 0;
}

.ajb-label {
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

.ajb-range {
    display: grid;
    grid-template-columns: minmax(86px, 1fr) 34px minmax(86px, 1fr);
    gap: 7px;
    align-items: center;
}

.ajb-separator {
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

.ajb-input,
.ajb-lookup-display {
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

.ajb-input:hover,
.ajb-lookup-display:hover {
    border-color: #aebed1;
}

.ajb-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
}

/* =========================================================
   DROPDOWN LOKASI
   Memakai tabel dua kolom, bukan <select>, supaya deskripsi
   selalu rata betapa pun panjang kode lokasinya.
   ========================================================= */
.ajb-lokasi {
    position: relative;
    z-index: 60;
    width: 100%;
    min-width: 0;
}

.ajb-lokasi-selected {
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

.ajb-lokasi-selected:hover {
    border-color: #aebed1;
}

.ajb-lokasi.is-open .ajb-lokasi-selected {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
}

.ajb-lokasi-code {
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

.ajb-lokasi-name {
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

.ajb-lokasi-arrow {
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

.ajb-lokasi.is-open .ajb-lokasi-arrow {
    transform: rotate(180deg);
    background: #dbeafe;
}

.ajb-lokasi-panel {
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

.ajb-lokasi.is-open .ajb-lokasi-panel {
    display: block;
    animation: ajbLokasiPanelIn 0.14s ease-out;
}

@keyframes ajbLokasiPanelIn {
    from { opacity: 0; transform: translateY(-5px) scale(0.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.ajb-lokasi-search {
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

.ajb-lokasi-search:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.ajb-lokasi-scroll {
    max-height: 280px;
    overflow: auto;
    scrollbar-width: thin;
    scrollbar-color: #93c5fd #eff3f7;
}

.ajb-lokasi-scroll::-webkit-scrollbar {
    width: 10px;
}

.ajb-lokasi-scroll::-webkit-scrollbar-track {
    background: #eff3f7;
}

.ajb-lokasi-scroll::-webkit-scrollbar-thumb {
    border: 2px solid #eff3f7;
    border-radius: 999px;
    background: linear-gradient(180deg, #60a5fa, #2563eb);
}

.ajb-lokasi-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 3px;
    table-layout: fixed;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 12px;
}

.ajb-lokasi-table td {
    height: 36px;
    padding: 6px 10px;
    border: 0;
    background: #ffffff;
    color: #475467;
    vertical-align: middle;
}

/* Lebar kolom kode dikunci, jadi deskripsi selalu mulai di titik sama. */
.ajb-lokasi-table td:first-child {
    width: 78px;
    border-radius: 10px 0 0 10px;
    color: #1d4ed8;
    font-family: "SFMono-Regular", Consolas, monospace;
    font-size: 10.5px;
    font-weight: 800;
}

.ajb-lokasi-table td:last-child {
    border-radius: 0 10px 10px 0;
    overflow: hidden;
    font-weight: 650;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ajb-lokasi-table tbody tr {
    cursor: pointer;
}

.ajb-lokasi-table tbody tr:hover td {
    background: #f4f8ff;
    color: #1d4ed8;
}

.ajb-lokasi-table tbody tr.is-active td {
    background: #eff6ff;
    color: #1d4ed8;
}

.ajb-lokasi-table tbody tr.is-active td:first-child {
    box-shadow: inset 3px 0 0 #2563eb;
}

.ajb-lokasi-empty td {
    color: #94a3b8;
    cursor: default;
    text-align: center;
}

.ajb-lokasi-empty:hover td {
    background: #ffffff;
    color: #94a3b8;
}

.ajb-lookup {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px;
    gap: 8px;
}

.ajb-lookup-display {
    display: flex;
    align-items: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ajb-lookup-button {
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

.ajb-lookup-button:hover {
    transform: translateY(-1px);
    background: linear-gradient(145deg, #dbeafe 0%, #bfdbfe 100%);
    box-shadow: 0 7px 15px rgba(37, 99, 235, 0.12);
}

.ajb-action-stack {
    position: relative;
    width: 82px;
    height: 42px;
    grid-column: 3;
    grid-row: 1;
    align-self: start;
    justify-self: end;
}

.ajb-action {
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

.ajb-action-stack .ajb-action:first-child {
    position: absolute;
    top: 0;
    right: -20px;
    border-radius: 13px 0 0 13px;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
}

.ajb-action-stack .ajb-action:first-child:hover {
    transform: translateY(-1px);
    background: linear-gradient(135deg, #2f6ff0 0%, #1e4fc4 100%);
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.30);
}

#ajbPrintButton {
    position: absolute;
    top: 55px;
    right: -20px;
    border-radius: 13px 0 0 13px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(5, 150, 105, 0.22);
}

#ajbPrintButton:hover:not(:disabled) {
    transform: translateY(-1px);
    background: linear-gradient(135deg, #14b88a 0%, #047857 100%);
    box-shadow: 0 12px 24px rgba(5, 150, 105, 0.28);
}

#ajbPrintButton:disabled,
#ajbPrintButton:disabled:hover,
#ajbPrintButton:disabled:focus {
    transform: none;
    border: 1px solid #d5dde7;
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
    color: #94a3b8;
    box-shadow: none;
    cursor: not-allowed;
    opacity: 1;
}

.ajb-report-area {
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

.ajb-report-area::before {
    content: "";
    position: absolute;
    top: 0;
    left: 7%;
    right: 7%;
    height: 3px;
    border-radius: 0 0 999px 999px;
    background: linear-gradient(90deg, transparent, #38bdf8, #2563eb, #6366f1, transparent);
}

#ajbLoading {
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

.ajb-paper {
    width: 100%;
    min-height: 0;
    margin: 0;
    padding: 0;
    border: 0;
    background: transparent;
    color: #172033;
}

.ajb-initial {
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

.ajb-initial-icon {
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

.ajb-report-header {
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

.ajb-company {
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 850;
    letter-spacing: 0.04em;
    line-height: 1.35;
}

.ajb-title-wrap {
    min-width: 0;
    text-align: center;
}

.ajb-report-title {
    display: block;
    margin: 0;
    color: #172033;
    font-family: Cambria, Georgia, "Times New Roman", serif;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.ajb-report-center-meta {
    margin-top: 5px;
    color: #475467;
    font-size: 10.5px;
    font-weight: 650;
    line-height: 1.45;
}

.ajb-report-date {
    color: #475467;
    text-align: right;
    font-size: 10.5px;
    font-weight: 650;
    line-height: 1.55;
}

.ajb-report-subtitle {
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

.ajb-subtitle-label {
    text-align: left;
}

.ajb-subtitle-value {
    min-width: 0;
    color: #344054;
    text-align: center;
    font-weight: 850;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ajb-live-badge {
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

.ajb-live-badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
}

.ajb-table-wrap {
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

.ajb-table-wrap::-webkit-scrollbar,
.ajb-modal-table-wrap::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.ajb-table-wrap::-webkit-scrollbar-track,
.ajb-modal-table-wrap::-webkit-scrollbar-track {
    background: #eff3f7;
}

.ajb-table-wrap::-webkit-scrollbar-thumb,
.ajb-modal-table-wrap::-webkit-scrollbar-thumb {
    border: 2px solid #eff3f7;
    border-radius: 999px;
    background: linear-gradient(180deg, #60a5fa, #2563eb);
}

.ajb-report-table {
    width: 1420px;
    min-width: 1420px;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
    color: #344054;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10.5px;
}

.ajb-report-table th,
.ajb-report-table td {
    padding: 8px 9px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
    overflow-wrap: anywhere;
}

.ajb-report-table th {
    position: sticky;
    z-index: 4;
    background: linear-gradient(180deg, #eff6ff 0%, #e5effb 100%);
    color: #344054;
    text-align: center;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-weight: 900;
    line-height: 1.25;
}

.ajb-report-table thead tr:first-child th {
    top: 0;
    z-index: 5;
    height: 44px;
}

.ajb-report-table thead tr:first-child th[colspan] {
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    color: #3730a3;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.ajb-report-table thead tr:nth-child(2) th {
    top: 44px;
    z-index: 4;
    height: 34px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    color: #475467;
    font-size: 9.5px;
}

.ajb-report-table thead tr:first-child th[rowspan] {
    z-index: 6;
}

.ajb-report-table td {
    height: 44px;
    background: #ffffff;
    color: #344054;
    line-height: 1.38;
}

.ajb-report-table tbody tr:nth-child(even):not(.ajb-sector-row) td {
    background: #fbfcfe;
}

.ajb-report-table tbody tr.ajb-data-row:hover td {
    background: #f0f7ff;
}

.ajb-report-table tbody tr.ajb-data-row:hover td:first-child {
    box-shadow: inset 4px 0 0 #2563eb;
    color: #1d4ed8;
    font-weight: 900;
}

.ajb-sector-row td {
    height: auto;
    padding: 8px 10px;
    background: linear-gradient(90deg, #ffffff 0%, #f8fafc 55%, #eff6ff 100%) !important;
    color: #344054;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10px;
    font-weight: 850;
    text-align: left;
}

.ajb-center {
    text-align: center;
}

.ajb-left {
    text-align: left;
}

.ajb-empty {
    height: 130px;
    color: #64748b;
    text-align: center;
}

/* =========================================================
   FOOTER TANDA TANGAN
   Nilai mengikuti Daftar Sertifikat Pecahan.
   ========================================================= */
.ajb-signature-footer {
    width: min(100%, 980px);
    min-height: 190px;
    margin: 18px auto 2px;
    padding: 0 34px 18px;
    color: #344054;
    font-size: 11px;
}

.ajb-signature-footer-date {
    width: 50%;
    margin: 0 0 8px auto;
    text-align: center;
    color: #475467;
    font-weight: 600;
}

.ajb-signature-footer-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 90px;
}

.ajb-signature-footer-box {
    text-align: center;
}

.ajb-signature-footer-role {
    min-height: 20px;
    color: #344054;
    font-weight: 600;
}

.ajb-signature-footer-space {
    height: 78px;
}

.ajb-signature-footer-line {
    display: inline-flex;
    width: min(100%, 220px);
    align-items: flex-end;
    justify-content: center;
    color: #667085;
}

.ajb-signature-footer-line::before {
    content: "(";
    margin-right: 3px;
}

.ajb-signature-footer-line::after {
    content: ")";
    margin-left: 3px;
}

.ajb-signature-footer-line > span {
    display: block;
    width: 100%;
    height: 10px;
    border-bottom: 1px dotted #98a2b3;
}

#ajbSektorModal {
    position: fixed;
    inset: 0;
    z-index: 1065;
    display: none;
    overflow: auto;
    background: rgba(71, 85, 105, 0.24);
}

#ajbSektorModal.show {
    display: block;
}

.ajb-modal-dialog {
    width: calc(100vw - 32px);
    max-width: 800px;
    margin: 24px auto;
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
}

.ajb-modal-header {
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

.ajb-modal-close {
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

.ajb-modal-body {
    padding: 16px;
    background: #f8fafc;
}

.ajb-modal-search {
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

.ajb-modal-search:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.ajb-modal-table-wrap {
    max-height: 430px;
    overflow: auto;
    border: 1px solid #dbe3ef;
    border-radius: 16px;
    background: #ffffff;
}

.ajb-modal-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}

.ajb-modal-table th,
.ajb-modal-table td {
    padding: 10px 12px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}

.ajb-modal-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: linear-gradient(180deg, #eff6ff 0%, #e7f0fc 100%);
    color: #344054;
    text-align: center;
    font-weight: 850;
}

.ajb-modal-table tbody tr {
    cursor: pointer;
}

.ajb-modal-table tbody tr:hover td {
    background: #eff6ff;
    color: #1d4ed8;
}

/* =========================================================
   ALERT DATA KOSONG — SAMA DENGAN DAFTAR SERTIFIKAT PECAHAN
   Modal informasi yang tampil saat hasil laporan tidak
   menghasilkan baris data sama sekali.
   ========================================================= */
#ajbNoDataAlertModal .modal-dialog {
    max-width: 380px;
}

#ajbNoDataAlertModal .modal-content {
    border: 0;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
}

#ajbNoDataAlertModal .alert-icon-wrapper {
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

#ajbNoDataAlertModal .alert-title {
    margin-bottom: 8px;
    color: #172033;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 18px;
    font-weight: 700;
}

#ajbNoDataAlertModal .alert-message {
    margin-bottom: 24px;
    color: #475569;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 14px;
}

#ajbNoDataAlertModal .alert-btn-ok {
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

#ajbNoDataAlertModal .alert-btn-ok:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
}

@media screen and (max-width: 719px) {
    html, body {
        min-width: 720px;
    }
    .ajb-page {
        min-width: 720px;
    }
}

@media print {
    .ajb-toolbar, .ajb-filter, #ajbLoading, #ajbSektorModal,
    #ajbNoDataAlertModal, .modal-backdrop, .main-sidebar, .control-sidebar,
    .main-header, .main-footer, .navbar, .sidebar {
        display: none !important;
    }
    html, body, .wrapper, .content-wrapper, .main-content, .content,
    .page-wrapper, .page-content, .container, .container-fluid,
    .ajb-page, .ajb-report-area, .ajb-paper {
        width: 100% !important;
        min-width: 0 !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        background: #fff !important;
        box-shadow: none !important;
    }
    .ajb-table-wrap {
        max-height: none !important;
        overflow: visible !important;
    }
    .ajb-report-table {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
    }
    .ajb-signature-footer {
        color: #000 !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
    .ajb-signature-footer-date,
    .ajb-signature-footer-role,
    .ajb-signature-footer-line {
        color: #000 !important;
    }
    .ajb-signature-footer-line > span {
        border-bottom-color: #444 !important;
    }
}
</style>

<!-- MODAL ALERT DATA KOSONG -->
<div class="modal fade" id="ajbNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4" style="text-align: center; padding: 1.5rem;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message" id="ajbNoDataMessage">Data tidak ditemukan.</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    data-dismiss="modal"
                    onclick="hideAjbNoDataAlert()"
                >OK</button>
            </div>
        </div>
    </div>
</div>

<div class="ajb-page">
    <div class="ajb-toolbar">
        <div class="ajb-toolbar-title">
            Daftar Akta Jual Beli
        </div>
        <code class="ajb-unit-badge">
            UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}
        </code>
    </div>

    <section class="ajb-filter">
        <input
            type="hidden"
            autocomplete="off"
            id="ajbPerusahaan"
            value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}"
        >
        <input
            type="hidden"
            autocomplete="off"
            id="ajbNamaPerusahaan"
            value="{{ session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? '' }}"
        >
        <input type="hidden" id="ajbSektor" value="*" autocomplete="off">

        <div class="ajb-filter-grid">
            <div class="ajb-field">
                <label class="ajb-label" for="ajbBlokAwal">Blok</label>
                <div class="ajb-range">
                    <input
                        type="text"
                        id="ajbBlokAwal"
                        class="ajb-input"
                        value="A"
                        maxlength="30"
                        autocomplete="off"
                    >
                    <span class="ajb-separator">s.d</span>
                    <input
                        type="text"
                        id="ajbBlokAkhir"
                        class="ajb-input"
                        value="Z"
                        maxlength="30"
                        autocomplete="off"
                    >
                </div>
            </div>

            <div class="ajb-field">
                <label class="ajb-label" for="ajbTglAwal">Tanggal AJB</label>
                <div class="ajb-range">
                    <input type="date" id="ajbTglAwal" class="ajb-input" autocomplete="off">
                    <span class="ajb-separator">s.d</span>
                    <input type="date" id="ajbTglAkhir" class="ajb-input" autocomplete="off">
                </div>
            </div>

            <div class="ajb-action-stack">
                <button
                    type="button"
                    class="ajb-action"
                    onclick="getAjbData()"
                >
                    Ok
                </button>
                <button
                    type="button"
                    class="ajb-action"
                    id="ajbPrintButton"
                    onclick="printAjbReport()"
                    aria-disabled="true"
                    disabled
                >
                    Print
                </button>
            </div>

            <div class="ajb-field">
                <span class="ajb-label">Lokasi</span>
                <div class="ajb-lokasi" id="ajbLokasiDropdown">
                    <input type="hidden" id="ajbLokasi" value="*" autocomplete="off">
                    <input type="hidden" id="ajbLokasiNama" value="Semua Lokasi" autocomplete="off">

                    <button
                        type="button"
                        class="ajb-lokasi-selected"
                        onclick="toggleAjbLokasiPanel(event)"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                    >
                        <span class="ajb-lokasi-code" id="ajbLokasiCode">*</span>
                        <span class="ajb-lokasi-name" id="ajbLokasiName">Semua Lokasi</span>
                        <span class="ajb-lokasi-arrow" aria-hidden="true">
                            <i class="fas fa-chevron-down"></i>
                        </span>
                    </button>

                    <div class="ajb-lokasi-panel" id="ajbLokasiPanel" role="listbox">
                        <input
                            type="text"
                            class="ajb-lokasi-search"
                            id="ajbLokasiSearch"
                            placeholder="Cari kode atau nama lokasi..."
                            onkeyup="filterAjbLokasi(this.value)"
                            autocomplete="off"
                        >
                        <div class="ajb-lokasi-scroll">
                            <table class="ajb-lokasi-table">
                                <tbody id="ajbLokasiBody">
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

            <div class="ajb-field">
                <span class="ajb-label">Sektor/Cluster</span>
                <div class="ajb-lookup">
                    <div id="ajbSektorEntry" class="ajb-lookup-display">Semua Sektor</div>
                    <button
                        type="button"
                        class="ajb-lookup-button"
                        onclick="getAjbSektorModal()"
                        title="Pilih sektor/cluster"
                        aria-label="Pilih sektor/cluster"
                    >
                        <i class="fas fa-binoculars"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="ajb-report-area">
        <div id="ajbLoading">
            <i class="fas fa-spinner fa-spin"></i>
            Mengambil data Akta Jual Beli...
        </div>

        <div id="ajbMainDisplay">
            <div class="ajb-paper">
                <div class="ajb-initial">
                    <i class="fas fa-table ajb-initial-icon" aria-hidden="true"></i>
                    <div>Silahkan isi filter kemudian klik OK</div>
                </div>
            </div>
        </div>
    </section>

    <div id="ajbSektorModal" aria-hidden="true">
        <div class="ajb-modal-dialog">
            <div class="ajb-modal-header">
                <span>Pilih Sektor/Cluster</span>
                <button
                    type="button"
                    class="ajb-modal-close"
                    onclick="toggleAjbSektorModal(false)"
                    aria-label="Tutup"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="ajb-modal-body" id="ajbSektorModalContent"></div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    var lastAjbRows = null;

    $(document).ready(function () {
        /*
         * Browser memulihkan isi form saat halaman di-refresh, termasuk
         * input hidden penampung kode lokasi dan sektor. Akibatnya filter
         * yang terlihat kembali ke "Semua" tetapi nilai yang dikirim ke
         * server masih memakai pilihan sebelumnya. Reset dipanggil
         * bertahap karena pemulihan itu dapat terjadi setelah
         * DOMContentLoaded.
         */
        resetAjbInitialState();
        window.setTimeout(resetAjbInitialState, 10);
        window.setTimeout(resetAjbInitialState, 100);

        loadAjbLokasi();

        $('#ajbBlokAwal, #ajbBlokAkhir').on('input', function () {
            $(this).val(String($(this).val() || '').toUpperCase());
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                toggleAjbSektorModal(false);
                closeAjbLokasiPanel();
            }
        });

        $(document).on('click', '#ajbLokasiBody tr', function () {
            if ($(this).hasClass('ajb-lokasi-empty')) {
                return;
            }

            chooseAjbLokasi(
                $(this).attr('data-kode'),
                $(this).attr('data-nama')
            );
        });

        $(document).on('click', function (event) {
            if (!$(event.target).closest('#ajbLokasiDropdown').length) {
                closeAjbLokasiPanel();
            }
        });
    });

    $(window).on('load', function () {
        resetAjbInitialState();
    });

    $(window).on('pageshow', function (event) {
        var pageEvent = event.originalEvent || event;

        if (pageEvent.persisted) {
            resetAjbInitialState();
        }
    });

    /*
     * Mengembalikan seluruh filter dan area laporan ke keadaan awal,
     * seperti saat fitur ini baru dibuka.
     */
    function resetAjbInitialState() {
        $('#ajbBlokAwal').val('A');
        $('#ajbBlokAkhir').val('Z');

        $('#ajbSektor').val('*');
        $('#ajbSektorEntry').text('Semua Sektor');

        $('#ajbLokasi').val('*');
        $('#ajbLokasiNama').val('Semua Lokasi');
        $('#ajbLokasiCode').text('*');
        $('#ajbLokasiName').text('Semua Lokasi');
        $('#ajbLokasiSearch').val('');
        $('#ajbLokasiBody tr').removeClass('is-active').show();
        $('#ajbLokasiBody tr').filter(function () {
            return String($(this).attr('data-kode') || '') === '*';
        }).addClass('is-active');
        closeAjbLokasiPanel();
        toggleAjbSektorModal(false);

        setAjbDefaultDate();
        resetAjbPrint();

        $('#ajbLoading').hide();
        $('#ajbMainDisplay').html(
            '<div class="ajb-paper">'
            + '<div class="ajb-initial">'
            + '<i class="fas fa-table ajb-initial-icon" aria-hidden="true"></i>'
            + '<div>Silahkan isi filter kemudian klik OK</div>'
            + '</div></div>'
        );
    }

    function setAjbDefaultDate() {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var today = year + '-' + month + '-' + day;

        $('#ajbTglAwal').val(today);
        $('#ajbTglAkhir').val(today);
    }

    function setAjbPrintEnabled(enabled) {
        var disabled = enabled !== true;
        $('#ajbPrintButton')
            .prop('disabled', disabled)
            .attr('aria-disabled', disabled ? 'true' : 'false');
    }

    function resetAjbPrint() {
        lastAjbRows = null;
        setAjbPrintEnabled(false);
        hideAjbNoDataAlert();
    }

    /*
     * Alert data kosong, mengikuti Daftar Sertifikat Pecahan.
     * Hanya menampilkan pesan; pengambilan data dan render laporan
     * tetap memakai alur yang sudah ada. Jika plugin modal tidak
     * tersedia pada halaman ini, modal ditampilkan secara manual
     * sehingga tampilannya tetap sama.
     */
    function showAjbNoDataAlert(message) {
        var $modal = $('#ajbNoDataAlertModal');

        $('#ajbNoDataMessage').text(message || 'Data tidak ditemukan......!');

        if (typeof $modal.modal === 'function') {
            $modal.modal('show');
            return;
        }

        if (!$('.ajb-nodata-backdrop').length) {
            $('<div class="modal-backdrop fade show ajb-nodata-backdrop"></div>')
                .appendTo(document.body);
        }

        $modal
            .addClass('show')
            .css('display', 'block')
            .attr('aria-hidden', 'false');
    }

    function hideAjbNoDataAlert() {
        var $modal = $('#ajbNoDataAlertModal');

        if (typeof $modal.modal === 'function') {
            $modal.modal('hide');
            return;
        }

        $modal
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true');

        $('.ajb-nodata-backdrop').remove();
    }

    function ajbEscapeHtml(value) {
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

    function ajbEscapeJs(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r?\n/g, ' ');
    }

    function ajbValue(value) {
        return value === null || value === undefined || value === ''
            ? '-'
            : value;
    }

    function ajbPick(item, keys) {
        item = item || {};

        for (var i = 0; i < keys.length; i++) {
            var value = item[keys[i]];
            if (value !== null && value !== undefined && value !== '') {
                return value;
            }
        }

        return null;
    }

    function ajbFormatDate(value) {
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

    function ajbFormatTanggalIndonesia(dateValue) {
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
            + ' ' + bulan[date.getMonth()]
            + ' ' + date.getFullYear();
    }

    /* Nama PT untuk header laporan, sama dengan fitur lain. */
    function ajbExtractCompanyName(value) {
        var raw = String(value || '').replace(/\u00a0/g, ' ');
        var locationMatch = raw.match(/Lokasi\s*:[^\r\n|]*?-\s*([^\r\n|]+)/i);
        var companyMatch = raw.match(/\b((?:PT\.?|CV\.?|PD\.?|PERUM)\s+[^\r\n|]+)/i);
        var name = locationMatch ? locationMatch[1] : (companyMatch ? companyMatch[1] : '');

        return String(name || '')
            .replace(/\s+(?:Hi\s*,|Logout\b|Keluar\b|LAND DOCUMENT\b|UNIT\s+[A-Z0-9_-]+\b).*$/i, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function ajbCompanyNameFromLayout() {
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
                var name = ajbExtractCompanyName(candidates[j]);
                if (name) {
                    return name;
                }
            }
        }

        return '';
    }

    function ajbCompanyName(first) {
        var unit = String($('#ajbPerusahaan').val() || '').trim().toUpperCase();
        var rowName = ajbPick(first || {}, [
            'NAMA_PT', 'nama_pt',
            'ATAS_NAMA_PT', 'atas_nama_pt',
            'NAMA_PERUSAHAAN', 'nama_perusahaan'
        ]);
        var sessionName = String($('#ajbNamaPerusahaan').val() || '').trim();
        var cacheKey = 'sris.report-company.' + unit;
        var cachedName = '';

        try {
            cachedName = localStorage.getItem(cacheKey) || '';
        } catch (error) {
            cachedName = '';
        }

        var company = ajbExtractCompanyName(rowName)
            || String(rowName || '').trim()
            || ajbCompanyNameFromLayout()
            || ajbExtractCompanyName(sessionName)
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

    function toggleAjbLokasiPanel(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        var $dropdown = $('#ajbLokasiDropdown');
        var akanDibuka = !$dropdown.hasClass('is-open');

        $dropdown.toggleClass('is-open', akanDibuka);
        $dropdown.find('.ajb-lokasi-selected')
            .attr('aria-expanded', akanDibuka ? 'true' : 'false');

        if (akanDibuka) {
            $('#ajbLokasiSearch').val('');
            filterAjbLokasi('');
            $('#ajbLokasiSearch').trigger('focus');
        }
    }

    function closeAjbLokasiPanel() {
        $('#ajbLokasiDropdown').removeClass('is-open')
            .find('.ajb-lokasi-selected')
            .attr('aria-expanded', 'false');
    }

    function chooseAjbLokasi(kode, nama) {
        kode = String(kode || '*');
        nama = String(nama || 'Semua Lokasi');

        $('#ajbLokasi').val(kode);
        $('#ajbLokasiNama').val(nama);
        $('#ajbLokasiCode').text(kode);
        $('#ajbLokasiName').text(nama);

        $('#ajbLokasiBody tr').removeClass('is-active');
        $('#ajbLokasiBody tr').each(function () {
            if (String($(this).attr('data-kode') || '') === kode) {
                $(this).addClass('is-active');
            }
        });

        closeAjbLokasiPanel();
        resetAjbPrint();
    }

    function filterAjbLokasi(keyword) {
        var search = String(keyword || '').toLowerCase().trim();

        $('#ajbLokasiBody tr').not('.ajb-lokasi-empty').each(function () {
            $(this).toggle(
                $(this).text().toLowerCase().indexOf(search) !== -1
            );
        });
    }

    function loadAjbLokasi() {
        var perusahaan = String($('#ajbPerusahaan').val() || '').trim();

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

                    html += '<tr data-kode="' + ajbEscapeHtml(kode) + '" ';
                    html += 'data-nama="' + ajbEscapeHtml(nama) + '">';
                    html += '<td>' + ajbEscapeHtml(kode) + '</td>';
                    html += '<td title="' + ajbEscapeHtml(nama) + '">'
                        + ajbEscapeHtml(nama) + '</td>';
                    html += '</tr>';
                });

                $('#ajbLokasiBody').html(html);
            },
            error: function (xhr) {
                /*
                 * Jangan gagal diam-diam. Pilihan Semua Lokasi tetap dapat
                 * dipakai, tetapi penyebabnya terlihat pada daftar.
                 */
                var keterangan = 'Daftar lokasi gagal dimuat';

                if (xhr && xhr.status) {
                    keterangan += ' (HTTP ' + xhr.status + ')';
                }

                $('#ajbLokasiBody').html(
                    '<tr class="is-active" data-kode="*" '
                    + 'data-nama="Semua Lokasi">'
                    + '<td>*</td><td>Semua Lokasi</td></tr>'
                    + '<tr class="ajb-lokasi-empty"><td colspan="2">'
                    + ajbEscapeHtml(keterangan)
                    + '</td></tr>'
                );
            }
        });
    }

    /* ==============================================
       SEKTOR
       ============================================== */

    function toggleAjbSektorModal(show) {
        $('#ajbSektorModal')
            .toggleClass('show', show === true)
            .attr('aria-hidden', show === true ? 'false' : 'true');
    }

    function addAjbSektor(kode, deskripsi) {
        $('#ajbSektor').val(kode || '*');
        $('#ajbSektorEntry').text(deskripsi || 'Semua Sektor');
        toggleAjbSektorModal(false);
        resetAjbPrint();
    }

    function filterAjbSektor(keyword) {
        var search = String(keyword || '').toLowerCase().trim();

        $('#ajbSektorModal .ajb-modal-table tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(search) !== -1);
        });
    }

    function getAjbSektorModal() {
        var perusahaan = String($('#ajbPerusahaan').val() || '').trim();

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
                html += '<input type="text" class="ajb-modal-search" ';
                html += 'placeholder="Cari sektor/cluster..." ';
                html += 'onkeyup="filterAjbSektor(this.value)">';
                html += '<div class="ajb-modal-table-wrap">';
                html += '<table class="ajb-modal-table"><thead><tr>';
                html += '<th>Kode</th><th>Deskripsi</th><th>Perusahaan</th>';
                html += '</tr></thead><tbody>';

                html += '<tr onclick="addAjbSektor(\'*\', \'Semua Sektor\')">';
                html += '<td>*</td><td>Semua Sektor</td>';
                html += '<td>' + ajbEscapeHtml(perusahaan) + '</td></tr>';

                $.each(rows, function (index, item) {
                    var kode = item.KD_SEKTOR || item.kd_sektor || '';
                    var deskripsi = item.DESKRIPSI || item.deskripsi || kode;
                    var unit = item.KD_PERUSAHAAN || item.kd_perusahaan || perusahaan;

                    html += '<tr onclick="addAjbSektor(\''
                        + ajbEscapeJs(kode) + '\', \''
                        + ajbEscapeJs(deskripsi) + '\')">';
                    html += '<td>' + ajbEscapeHtml(kode) + '</td>';
                    html += '<td>' + ajbEscapeHtml(deskripsi) + '</td>';
                    html += '<td>' + ajbEscapeHtml(unit) + '</td></tr>';
                });

                if (rows.length < 1) {
                    html += '<tr><td colspan="3" ';
                    html += 'style="padding:22px;text-align:center;">';
                    html += 'Data sektor tidak ditemukan.</td></tr>';
                }

                html += '</tbody></table></div>';

                $('#ajbSektorModalContent').html(html);
                toggleAjbSektorModal(true);
                $('#ajbSektorModal .ajb-modal-search').trigger('focus');
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

    function validateAjbFilter() {
        if (!$('#ajbBlokAwal').val() || !$('#ajbBlokAkhir').val()) {
            alert('Rentang blok wajib diisi.');
            return false;
        }

        if (!$('#ajbTglAwal').val() || !$('#ajbTglAkhir').val()) {
            alert('Rentang Tanggal AJB wajib diisi.');
            return false;
        }

        if ($('#ajbTglAwal').val() > $('#ajbTglAkhir').val()) {
            alert('Tanggal awal tidak boleh melebihi tanggal akhir.');
            return false;
        }

        return true;
    }

    function getAjbFilterData() {
        return {
            _token: '{{ csrf_token() }}',
            blok_awal: String($('#ajbBlokAwal').val() || 'A').toUpperCase(),
            blok_akhir: String($('#ajbBlokAkhir').val() || 'ZZ').toUpperCase(),
            tgl_awal: $('#ajbTglAwal').val(),
            tgl_akhir: $('#ajbTglAkhir').val(),
            perusahaan: $('#ajbPerusahaan').val(),
            lokasi: $('#ajbLokasi').val() || '*',
            sektor: $('#ajbSektor').val() || '*'
        };
    }

    function getAjbData() {
        if (!validateAjbFilter()) {
            return;
        }

        resetAjbPrint();
        $('#ajbLoading').show();

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_data',
            dataType: 'json',
            timeout: 120000,
            headers: { 'Accept': 'application/json' },
            data: getAjbFilterData(),
            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (response && Array.isArray(response.data) ? response.data : []);

                lastAjbRows = rows;

                if (rows.length === 0) {
                    showAjbNoDataAlert('Data tidak ditemukan......!');
                }

                renderAjbReport(rows);
                setAjbPrintEnabled(rows.length > 0);
            },
            error: function (xhr, textStatus, errorThrown) {
                resetAjbPrint();

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

                $('#ajbMainDisplay').html(
                    '<div class="ajb-paper">'
                    + '<div style="padding:16px;color:#a00;">'
                    + 'Gagal mengambil data Akta Jual Beli. '
                    + ajbEscapeHtml(detail)
                    + '</div></div>'
                );
            },
            complete: function () {
                $('#ajbLoading').hide();
            }
        });
    }

    function groupAjbBySector(rows) {
        var groups = [];
        var map = {};

        $.each(rows, function (index, item) {
            item = item || {};

            var sektor = String(
                ajbPick(item, ['NAMA_SEKTOR', 'nama_sektor']) || 'TANPA SEKTOR'
            ).trim();

            if (!sektor) {
                sektor = 'TANPA SEKTOR';
            }

            if (map[sektor] === undefined) {
                map[sektor] = groups.length;
                groups.push({ sektor: sektor, rows: [] });
            }

            groups[map[sektor]].rows.push(item);
        });

        return groups;
    }

    function renderAjbReport(rows) {
        rows = Array.isArray(rows) ? rows : [];

        var first = rows.length > 0 ? rows[0] : {};
        var company = ajbCompanyName(first);

        var blok = String($('#ajbBlokAwal').val() || 'A').toUpperCase()
            + ' s/d '
            + String($('#ajbBlokAkhir').val() || 'ZZ').toUpperCase();

        var periode = ajbFormatDate($('#ajbTglAwal').val())
            + ' s/d '
            + ajbFormatDate($('#ajbTglAkhir').val());

        var lokasiTampil = String(
            $('#ajbLokasiNama').val() || 'Semua Lokasi'
        ).trim();

        var sektorTampil = String(
            $('#ajbSektorEntry').text() || 'Semua Sektor'
        ).trim();

        var now = new Date();
        var today = String(now.getDate()).padStart(2, '0')
            + '-' + String(now.getMonth() + 1).padStart(2, '0')
            + '-' + now.getFullYear();

        var html = '';

        html += '<div class="ajb-paper">';

        html += '<div class="ajb-report-header">';
        html += '<div class="ajb-company">' + ajbEscapeHtml(company) + '</div>';
        html += '<div class="ajb-title-wrap">';
        html += '<h2 class="ajb-report-title">Laporan Daftar Akta Jual Beli</h2>';
        html += '<div class="ajb-report-center-meta">';
        html += 'BLOK : ' + ajbEscapeHtml(blok);
        html += '<br>Tgl. Akta Jual Beli : ' + ajbEscapeHtml(periode);
        html += '</div></div>';
        html += '<div class="ajb-report-date">';
        html += 'Lokasi : ' + ajbEscapeHtml(lokasiTampil);
        html += '<br>Tanggal : ' + ajbEscapeHtml(today);
        html += '<br>Jumlah Data : ' + rows.length;
        html += '</div></div>';

        html += '<div class="ajb-report-subtitle">';
        html += '<span class="ajb-subtitle-label">Sektor/Cluster:</span>';
        html += '<strong class="ajb-subtitle-value">'
            + ajbEscapeHtml(sektorTampil) + '</strong>';
        html += '<span class="ajb-live-badge">Live data</span>';
        html += '</div>';

        html += '<div class="ajb-table-wrap">';
        html += '<table class="ajb-report-table">';
        html += '<colgroup>';
        html += '<col style="width:46px">';
        html += '<col style="width:105px">';
        html += '<col style="width:255px">';
        html += '<col style="width:130px">';
        html += '<col style="width:105px">';
        html += '<col style="width:235px">';
        html += '<col style="width:110px">';
        html += '<col style="width:110px">';
        html += '<col style="width:110px">';
        html += '<col style="width:120px">';
        html += '</colgroup>';

        html += '<thead>';
        html += '<tr>';
        html += '<th rowspan="2">No.</th>';
        html += '<th rowspan="2">BLOK/<br>NOMOR</th>';
        html += '<th rowspan="2">Nama Pemilik</th>';
        html += '<th colspan="2">Akta Jual Beli</th>';
        html += '<th rowspan="2">Nama<br>PPAT</th>';
        html += '<th rowspan="2">Tanggal<br>Input</th>';
        html += '<th rowspan="2">Tanggal<br>Cetak</th>';
        html += '<th rowspan="2">Tanggal<br>Ambil</th>';
        html += '<th rowspan="2">Tgl. Kuitansi<br>BBN</th>';
        html += '</tr>';
        html += '<tr>';
        html += '<th>Nomor</th>';
        html += '<th>Tanggal</th>';
        html += '</tr>';
        html += '</thead><tbody>';

        if (rows.length < 1) {
            html += '<tr><td colspan="10" class="ajb-empty">';
            html += 'Data tidak ditemukan.';
            html += '</td></tr>';
        } else {
            var groups = groupAjbBySector(rows);
            var runningNo = 1;

            $.each(groups, function (groupIndex, group) {
                html += '<tr class="ajb-sector-row"><td colspan="10">';
                html += 'Sektor/Cluster : ' + ajbEscapeHtml(group.sektor);
                html += '</td></tr>';

                $.each(group.rows, function (rowIndex, item) {
                    item = item || {};

                    html += '<tr class="ajb-data-row">';
                    html += '<td class="ajb-center">' + (runningNo++) + '</td>';
                    html += '<td class="ajb-center">'
                        + ajbEscapeHtml(ajbValue(ajbPick(item, ['BLOK_NOMOR', 'blok_nomor'])))
                        + '</td>';
                    html += '<td class="ajb-left">'
                        + ajbEscapeHtml(ajbValue(ajbPick(item, ['NAMA', 'nama'])))
                        + '</td>';
                    html += '<td class="ajb-left">'
                        + ajbEscapeHtml(ajbValue(ajbPick(item, ['NO_AKTA', 'no_akta'])))
                        + '</td>';
                    html += '<td class="ajb-center">'
                        + ajbEscapeHtml(ajbFormatDate(ajbPick(item, ['TGL_AKTA', 'tgl_akta'])))
                        + '</td>';
                    html += '<td class="ajb-left">'
                        + ajbEscapeHtml(ajbValue(ajbPick(item, ['NOTARIS', 'notaris'])))
                        + '</td>';
                    html += '<td class="ajb-center">'
                        + ajbEscapeHtml(ajbFormatDate(ajbPick(item, ['TGL_INPUT', 'tgl_input'])))
                        + '</td>';
                    html += '<td class="ajb-center">'
                        + ajbEscapeHtml(ajbFormatDate(ajbPick(item, ['TGL_CETAK_AKTA', 'tgl_cetak_akta'])))
                        + '</td>';
                    html += '<td class="ajb-center">'
                        + ajbEscapeHtml(ajbFormatDate(ajbPick(item, ['TGL_AMBIL_AKTA', 'tgl_ambil_akta'])))
                        + '</td>';
                    html += '<td class="ajb-center">'
                        + ajbEscapeHtml(ajbFormatDate(ajbPick(item, ['TGL_KUITANSI_BBN', 'tgl_kuitansi_bbn'])))
                        + '</td>';
                    html += '</tr>';
                });
            });
        }

        html += '</tbody></table></div>';

        var tanggalTandaTangan = ajbFormatTanggalIndonesia(new Date());

        html += '<div class="ajb-signature-footer">';
        html += '<div class="ajb-signature-footer-date">Jakarta, '
            + ajbEscapeHtml(tanggalTandaTangan) + '</div>';
        html += '<div class="ajb-signature-footer-grid">';
        html += '<div class="ajb-signature-footer-box">';
        html += '<div class="ajb-signature-footer-role">Yang menyerahkan,</div>';
        html += '<div class="ajb-signature-footer-space"></div>';
        html += '<div class="ajb-signature-footer-line"><span></span></div>';
        html += '</div>';
        html += '<div class="ajb-signature-footer-box">';
        html += '<div class="ajb-signature-footer-role">Yang menerima,</div>';
        html += '<div class="ajb-signature-footer-space"></div>';
        html += '<div class="ajb-signature-footer-line"><span></span></div>';
        html += '</div>';
        html += '</div></div>';

        html += '</div>';

        $('#ajbMainDisplay').html(html);
    }

    /* ==============================================
       PRINT
       ============================================== */

    /*
     * Lebar kolom untuk hasil cetak, dihitung dari colgroup laporan supaya
     * proporsinya sama dengan tampilan layar. Dibuat sebagai persentase
     * agar tetap benar berapa pun lebar kertas dan orientasi yang dipilih.
     */
    function ajbPrintColumnCss() {
        var lebar = [];
        var total = 0;

        $('#ajbMainDisplay .ajb-report-table').first().find('col').each(function () {
            var nilai = parseFloat(this.style.width) || $(this).width() || 0;

            lebar.push(nilai);
            total += nilai;
        });

        if (!total) {
            return '';
        }

        var css = '';

        $.each(lebar, function (index, nilai) {
            css += '.ajb-report-table col:nth-child(' + (index + 1) + ')'
                + ' { width: ' + ((nilai / total) * 100).toFixed(3)
                + '% !important; }';
        });

        return css;
    }

    function printAjbReport() {
        if (
            $('#ajbPrintButton').prop('disabled')
            || !Array.isArray(lastAjbRows)
            || !$('#ajbMainDisplay .ajb-report-table').length
        ) {
            return;
        }

        var reportHtml = $('#ajbMainDisplay').html();

        if (!reportHtml) {
            return;
        }

        $('#ajbNativePrintFrame').remove();

        var frame = document.createElement('iframe');
        frame.id = 'ajbNativePrintFrame';
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
         * sehingga pilihan Portrait/Landscape tetap tersedia di dialog print.
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

            .ajb-paper { width: 100%; margin: 0; padding: 0; }

            .ajb-report-header {
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

            .ajb-company { color: #000; font-size: 11px; font-weight: 700; }
            .ajb-title-wrap { text-align: center; }

            .ajb-report-title {
                margin: 0;
                color: #000;
                font-family: Cambria, Georgia, "Times New Roman", serif;
                font-size: 17px;
                font-weight: 700;
                line-height: 1.2;
            }

            .ajb-report-center-meta,
            .ajb-report-date { color: #000; font-size: 10px; line-height: 1.35; }
            .ajb-report-date { text-align: right; }

            .ajb-report-subtitle {
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

            .ajb-subtitle-value { text-align: center; font-weight: 700; }

            .ajb-live-badge {
                justify-self: end;
                color: #000;
                font-size: 9px;
                font-weight: 700;
            }

            .ajb-live-badge::before {
                content: "";
                display: inline-block;
                width: 5px;
                height: 5px;
                margin-right: 4px;
                border-radius: 50%;
                background: #000;
            }

            .ajb-table-wrap { width: 100%; overflow: visible; border: 0; }

            .ajb-report-table {
                width: 100%;
                min-width: 0;
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
             * ajbPrintColumnCss() dari colgroup laporan.
             */
            .ajb-report-table thead { display: table-header-group; }
            .ajb-report-table tbody { display: table-row-group; }

            .ajb-report-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .ajb-report-table th,
            .ajb-report-table td {
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

            .ajb-report-table th { text-align: center; font-weight: 700; }

            .ajb-sector-row td {
                background: #fff !important;
                color: #000;
                font-weight: 700;
                text-align: left;
            }

            /*
             * Tanggal dan nomor tidak boleh dipenggal di tengah. Dengan
             * table-layout otomatis, lebar kolomnya yang menyesuaikan.
             */
            .ajb-center { text-align: center; white-space: nowrap; }
            .ajb-left { text-align: left; }

            .ajb-signature-footer {
                width: 100%;
                min-height: 170px;
                margin: 16px auto 0;
                padding: 0 24px 8px;
                color: #000;
                font-size: 10px;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .ajb-signature-footer-date {
                width: 50%;
                margin: 0 0 8px auto;
                text-align: center;
                color: #000;
                font-weight: 600;
            }

            .ajb-signature-footer-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: 90px;
            }

            .ajb-signature-footer-box { text-align: center; }
            .ajb-signature-footer-role,
            .ajb-signature-footer-line { color: #000; }
            .ajb-signature-footer-space { height: 70px; }

            .ajb-signature-footer-line {
                display: inline-flex;
                width: min(100%, 220px);
                align-items: flex-end;
                justify-content: center;
            }

            .ajb-signature-footer-line::before { content: "("; margin-right: 3px; }
            .ajb-signature-footer-line::after { content: ")"; margin-left: 3px; }

            .ajb-signature-footer-line > span {
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
            + '<title>Daftar Akta Jual Beli</title>'
            + '<style>' + printCss + ajbPrintColumnCss() + '</style>'
            + '</head><body>' + reportHtml + '</body></html>'
        );
        frameDocument.close();

        window.setTimeout(function () {
            try {
                frameWindow.focus();
                frameWindow.print();
            } finally {
                window.setTimeout(function () {
                    $('#ajbNativePrintFrame').remove();
                }, 1200);
            }
        }, 180);
    }
</script>
@endsection
