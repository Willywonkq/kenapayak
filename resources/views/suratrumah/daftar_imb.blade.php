@extends('layouts.template')
@section('content')
<style>
.imb-page {
    position: relative;
    isolation: auto;
    width: 100%;
    min-width: 720px;
    min-height: 100%;
    padding: 18px 12px 32px;
    overflow: visible;
    color: #172033;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    background: radial-gradient( circle at 95% 2%, rgba(37, 99, 235, 0.07), transparent 28% ), radial-gradient( circle at 8% 96%, rgba(56, 189, 248, 0.05), transparent 26% ), #f3f6fb;
}
.imb-page, .imb-page * {
    box-sizing: border-box;
}
.imb-view-version {
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
.imb-view-version::before {
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
.imb-view-version::after {
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
.imb-view-version span {
    position: relative;
    z-index: 1;
    color: #172033;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 13px;
    font-weight: 900;
    letter-spacing: 0.11em;
    text-transform: uppercase;
}
.imb-view-version code {
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
.imb-filter-panel {
    position: relative;
    padding: 20px;
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
}
.imb-filter-panel::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    background: linear-gradient( 180deg, #38bdf8 0%, #2563eb 52%, #1d4ed8 100% );
}
.imb-filter-panel::after {
    content: "Daftar IMB";
    position: absolute;
    right: 20px;
    bottom: 10px;
    color: rgba(37, 99, 235, 0.035);
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 34px;
    font-weight: 950;
    letter-spacing: 0.08em;
    pointer-events: none;
}
.imb-filter-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 82px;
    gap: 13px 14px;
    align-items: center;
}
.imb-field-row {
    display: grid;
    grid-template-columns: 72px minmax(0, 1fr);
    gap: 9px;
    align-items: center;
    min-width: 0;
}
.imb-label {
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
.imb-range {
    display: grid;
    grid-template-columns: minmax(86px, 1fr) 34px minmax(86px, 1fr);
    gap: 7px;
    align-items: center;
}
.imb-input, .imb-lookup-display {
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
.imb-input:hover, .imb-lookup-display:hover {
    border-color: #aebed1;
    background: #ffffff;
}
.imb-input:focus {
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
}
.imb-separator {
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
.imb-lookup {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px;
    gap: 8px;
}
.imb-lookup-display {
    display: flex;
    align-items: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.imb-lookup-button {
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
    box-shadow: none;
    transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}
.imb-lookup-button:hover {
    transform: translateY(-1px);
    background: linear-gradient( 145deg, #dbeafe 0%, #bfdbfe 100% );
    box-shadow: 0 7px 15px rgba(37, 99, 235, 0.12);
}
.imb-action-button {
    position: relative;
    display: inline-flex;
    width: 82px;
    min-width: 82px;
    height: 42px;
    align-items: center;
    justify-content: center;
    justify-self: end;
    margin-right: -20px;
    overflow: hidden;
    border: 0;
    border-radius: 13px 0 0 13px;
    cursor: pointer;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}
.imb-action-button:not(.imb-print-button) {
    grid-column: 3;
    grid-row: 1;
    background: linear-gradient( 135deg, #2563eb 0%, #1d4ed8 100% );
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
}
.imb-action-button:not(.imb-print-button)::after {
    content: "";
    position: absolute;
    inset: -40% auto -40% -55%;
    width: 45%;
    transform: skewX(-22deg);
    background: rgba(255, 255, 255, 0.28);
    transition: left 0.45s ease;
}
.imb-action-button:not(.imb-print-button):hover {
    transform: translateY(-1px);
    background: linear-gradient( 135deg, #2f6ff0 0%, #1e4fc4 100% );
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.30);
}
.imb-action-button:not(.imb-print-button):hover::after {
    left: 125%;
}
.imb-print-button {
    grid-column: 3;
    grid-row: 2;
    background: linear-gradient( 135deg, #10b981 0%, #059669 100% );
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(5, 150, 105, 0.22);
}
.imb-print-button:hover:not(:disabled) {
    transform: translateY(-1px);
    background: linear-gradient( 135deg, #14b88a 0%, #047857 100% );
    box-shadow: 0 12px 24px rgba(5, 150, 105, 0.28);
}
.imb-print-button:disabled, .imb-print-button:disabled:hover, .imb-print-button:disabled:focus, #imbPrintButton[disabled] {
    transform: none !important;
    border: 1px solid #d5dde7 !important;
    background: linear-gradient( 135deg, #e2e8f0 0%, #cbd5e1 100% ) !important;
    color: #94a3b8 !important;
    box-shadow: none !important;
    cursor: not-allowed !important;
    opacity: 1 !important;
    pointer-events: none;
}
.imb-location-row {
    grid-column: 1 / 2;
    grid-row: 2;
}
.imb-report-workspace {
    position: relative;
    margin-top: 18px;
    padding: 20px;
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
}
.imb-report-workspace::before {
    content: "";
    position: absolute;
    top: 0;
    left: 7%;
    right: 7%;
    height: 3px;
    border-radius: 0 0 999px 999px;
    background: linear-gradient( 90deg, transparent, #38bdf8, #2563eb, #6366f1, transparent );
}
#imbLoading {
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
    box-shadow: none;
}
.imb-paper {
    width: 100%;
    min-height: 0;
    margin: 0;
    padding: 0;
    border: 0;
    background: transparent;
    box-shadow: none;
}
.imb-initial-state {
    display: flex;
    min-height: 310px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    overflow: hidden;
    border: 1px dashed #bfdbfe;
    border-radius: 20px;
    background: radial-gradient( circle at center, rgba(37, 99, 235, 0.06), transparent 46% ), #f8fbff;
    color: #667085;
    font-size: 13px;
    font-weight: 650;
}
.imb-initial-state i {
    display: inline-flex;
    width: 52px;
    height: 52px;
    flex: 0 0 52px;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    background: #dbeafe;
    color: #2563eb;
    font-size: 20px;
}
.imb-table-wrap::-webkit-scrollbar, .imb-modal-table-wrap::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}
.imb-table-wrap::-webkit-scrollbar-track, .imb-modal-table-wrap::-webkit-scrollbar-track {
    background: #eff3f7;
}
.imb-table-wrap::-webkit-scrollbar-thumb, .imb-modal-table-wrap::-webkit-scrollbar-thumb {
    border: 2px solid #eff3f7;
    border-radius: 999px;
    background: linear-gradient( 180deg, #60a5fa, #2563eb );
}
.imb-report-table col:nth-child(1) {
    width: 48px;
}
.imb-report-table col:nth-child(2) {
    width: 90px;
}
.imb-report-table col:nth-child(3) {
    width: 230px;
}
.imb-report-table col:nth-child(4) {
    width: 175px;
}
.imb-report-table col:nth-child(5) {
    width: 90px;
}
.imb-report-table col:nth-child(6) {
    width: 175px;
}
.imb-report-table col:nth-child(7) {
    width: 90px;
}
.imb-report-table col:nth-child(8) {
    width: 100px;
}
.imb-report-table col:nth-child(9) {
    width: 160px;
}
.imb-report-table col:nth-child(10) {
    width: 90px;
}
.imb-report-table th {
    position: sticky;
    z-index: 4;
    text-align: center;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-weight: 900;
    line-height: 1.25;
    white-space: normal;
}
.imb-report-table thead tr:first-child th:nth-child(4), .imb-report-table thead tr:first-child th:nth-child(5), .imb-report-table thead tr:first-child th:nth-child(7) {
    background: linear-gradient( 135deg, #dbeafe 0%, #e0e7ff 100% );
    color: #3730a3;
}
.imb-cell-center {
    text-align: center;
}
.imb-cell-left {
    text-align: left;
}
.imb-cell-number {
    color: #1e3a5f;
    text-align: right;
    font-weight: 750;
    font-variant-numeric: tabular-nums;
}
.imb-empty {
    height: 130px;
    color: #64748b;
    text-align: center;
}
.imb-report-top {
    display: grid;
    grid-template-columns: 1fr 1.4fr 1fr;
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
.imb-report-company-wrap {
    min-width: 0;
}
.imb-company {
    padding: 0;
    border: 0;
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 850;
    letter-spacing: 0.04em;
    white-space: normal;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    line-height: 1.35;
}
.imb-title-block {
    text-align: center;
    min-width: 0;
}
.imb-report-title {
    margin: 0;
    color: #172033;
    font-family: Cambria, Georgia, "Times New Roman", serif !important;
    font-size: 18px;
    font-weight: 700 !important;
    letter-spacing: -0.02em;
    line-height: 1.2;
}
.imb-report-period {
    color: #475467;
    text-align: right;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10.5px;
    font-weight: 650;
    line-height: 1.55;
}
.imb-report-subtitle {
    display: flex;
    min-height: 40px;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: linear-gradient( 90deg, #ffffff, #f8fafc );
    color: #667085;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10.5px;
}
.imb-report-subtitle-label {
    flex: 0 0 auto;
}
.imb-report-subtitle-value {
    min-width: 0;
    flex: 1;
    color: #344054;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-weight: 850;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.imb-live-badge {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 6px;
    padding: 5px 9px;
    border: 1px solid #a7f3d0;
    border-radius: 999px;
    background: #ecfdf3;
    color: #047857;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}
.imb-live-badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
}
.imb-table-wrap {
    width: 100%;
    max-height: calc(100vh - 285px);
    min-height: 320px;
    margin-top: 0;
    overflow: auto;
    border: 1px solid #dbe3ef;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 5px 16px rgba(15, 23, 42, 0.04);
    scrollbar-width: thin;
    scrollbar-color: #93c5fd #eff3f7;
}
.imb-report-table {
    width: 1320px;
    min-width: 1320px;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
    color: #344054;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10.5px;
}
.imb-report-table th, .imb-report-table td {
    padding: 8px 9px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
    overflow-wrap: anywhere;
}
.imb-report-table thead tr:first-child th {
    top: 0;
    height: 48px;
    border-top: 0;
    background: linear-gradient( 180deg, #eff6ff 0%, #e5effb 100% );
    color: #344054;
    box-shadow: inset 0 -1px 0 rgba(37, 99, 235, 0.05);
    z-index: 4;
}
.imb-report-table thead tr:nth-child(2) th {
    top: 48px;
    z-index: 3;
    height: 36px;
    background: linear-gradient( 180deg, #f8fafc 0%, #f1f5f9 100% );
    color: #475467;
}
.imb-report-table .imb-group-heading {
    background: linear-gradient( 135deg, #dbeafe 0%, #e0e7ff 100% ) !important;
    color: #3730a3 !important;
    font-weight: 950;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.imb-report-table .imb-sub-heading {
    padding-top: 7px;
    padding-bottom: 7px;
    font-size: 9.5px;
    font-weight: 900;
}
.imb-report-table td {
    height: 46px;
    background: #ffffff;
    color: #344054;
    line-height: 1.38;
    white-space: normal;
    transition: background 0.16s ease, box-shadow 0.16s ease;
}
.imb-report-table tbody tr:nth-child(even):not(.imb-sector-row) td {
    background: #fbfcfe;
}
.imb-report-table tbody tr:not(.imb-sector-row):hover td {
    background: #f0f7ff;
}
.imb-report-table tbody tr:not(.imb-sector-row):hover td:first-child {
    box-shadow: inset 4px 0 0 #2563eb;
    color: #1d4ed8;
    font-weight: 900;
}
.imb-sector-row td {
    height: auto;
    padding: 8px 10px;
    border-right: 1px solid #e2e8f0;
    background: linear-gradient( 90deg, #ffffff 0%, #f8fafc 55%, #eff6ff 100% ) !important;
    color: #344054;
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    font-size: 10px;
    font-weight: 850;
    text-align: left;
    letter-spacing: 0.01em;
}
#imbLokasiModal {
    position: fixed;
    inset: 0;
    z-index: 1065 !important;
    overflow-x: hidden;
    overflow-y: auto;
    pointer-events: none;
}
#imbLokasiModal.show {
    pointer-events: auto;
}
#imbLokasiModal .modal-dialog {
    position: relative;
    z-index: 1066;
    width: calc(100vw - 32px);
    max-width: 800px;
    margin: 24px auto;
    pointer-events: auto;
}
#imbLokasiModal .modal-content {
    overflow: hidden;
    border: 1px solid #dbe3ef;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
}
#imbLokasiModal .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 18px;
    border-bottom: 1px solid #dbe3ef;
    background: linear-gradient( 90deg, #ffffff 0%, #f8fbff 100% );
    color: #1d2939;
}
#imbLokasiModal .modal-header .btn {
    border: 1px solid #bfdbfe;
    border-radius: 11px;
    background: #eff6ff;
    color: #1d4ed8;
}
#imbLokasiModal .modal-body {
    padding: 16px;
    background: #f8fafc;
}
.imb-modal-search {
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
.imb-modal-search:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
.imb-modal-table-wrap {
    max-height: 430px;
    overflow: auto;
    border: 1px solid #dbe3ef;
    border-radius: 16px;
    background: #ffffff;
}
.imb-modal-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
}
.imb-modal-table th, .imb-modal-table td {
    padding: 10px 12px;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}
.imb-modal-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: linear-gradient( 180deg, #eff6ff 0%, #e7f0fc 100% );
    color: #344054;
    text-align: center;
    font-weight: 850;
}
.imb-modal-table tbody tr {
    cursor: pointer;
}
.imb-modal-table tbody tr:hover td {
    background: #eff6ff;
    color: #1d4ed8;
}
.imb-owned-backdrop, .imb-fallback-backdrop {
    z-index: 1060 !important;
}
.imb-owned-backdrop.show, .imb-fallback-backdrop {
    opacity: 0.24 !important;
    background: #475569 !important;
}
.imb-page, .imb-page input, .imb-page select, .imb-page button, .imb-page textarea, .imb-page label, .imb-page table, .imb-page td, .imb-page .modal-content {
    font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
}
.imb-page .imb-label, .imb-page .imb-report-table th, .imb-page .imb-sector-row, #imbLokasiModal .modal-header {
    font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
}
.imb-page .imb-report-title {
    font-family: Cambria, Georgia, "Times New Roman", serif !important;
    font-weight: 700 !important;
}
@media screen and (max-width: 719px) {
    html, body {
        min-width: 720px;
    }
    .imb-page {
        min-width: 720px;
    }
}
@media print {
    .imb-view-version, .imb-filter-panel, #imbLoading, #imbLokasiModal,
    #imbNoDataAlertModal, .modal-backdrop, .main-sidebar, .control-sidebar, .main-header,
    .main-footer, .navbar, .sidebar {
        display: none !important;
    }
    html, body, .wrapper, .content-wrapper, .main-content, .content,
    .page-wrapper, .page-content, .container, .container-fluid,
    .imb-page, .imb-report-workspace, #imbMainDisplay, .imb-paper {
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
    .imb-table-wrap {
        max-height: none !important;
        overflow: visible !important;
    }
    .imb-report-table {
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
    #imbNoDataAlertModal .modal-dialog {
        max-width: 380px;
    }

    #imbNoDataAlertModal .modal-content {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
    }

    #imbNoDataAlertModal .alert-icon-wrapper {
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

    #imbNoDataAlertModal .alert-title {
        margin-bottom: 8px;
        color: #172033;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 18px;
        font-weight: 700;
    }

    #imbNoDataAlertModal .alert-message {
        margin-bottom: 24px;
        color: #475569;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 14px;
    }

    #imbNoDataAlertModal .alert-btn-ok {
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

    #imbNoDataAlertModal .alert-btn-ok:hover {
        transform: translateY(-1px);
        color: #ffffff;
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
    }
</style>

<!-- MODAL ALERT DATA KOSONG -->
<div class="modal fade" id="imbNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4" style="text-align: center; padding: 1.5rem;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message" id="imbNoDataMessage">Data tidak ditemukan.</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    data-dismiss="modal"
                    onclick="hideImbNoDataAlert()"
                >OK</button>
            </div>
        </div>
    </div>
</div>

<div class="imb-page">
    <div class="imb-view-version">
        <span>Daftar IMB</span>
        <code>
            UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}
        </code>
    </div>
    <section class="imb-filter-panel">
        <input
            type="hidden"
            id="imbPerusahaan"
            value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}"
        >
        <input
            type="hidden"
            id="imbNamaPerusahaan"
            value="{{ session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? '' }}"
        >
        <input
            type="hidden"
            id="imbLokasi"
            value="*"
        >
        <div class="imb-filter-grid">
            <div class="imb-field-row">
                <label
                    class="imb-label"
                    for="imbBlokAwal"
                >
                    Blok
                </label>
                <div class="imb-range">
                    <input
                        type="text"
                        id="imbBlokAwal"
                        class="imb-input"
                        value="A"
                        maxlength="30"
                    >
                    <span class="imb-separator">
                        s.d
                    </span>
                    <input
                        type="text"
                        id="imbBlokAkhir"
                        class="imb-input"
                        value="ZZ"
                        maxlength="30"
                    >
                </div>
            </div>
            <div class="imb-field-row">
                <label
                    class="imb-label"
                    for="imbTglAwal"
                >
                    Tgl. Input
                </label>
                <div class="imb-range">
                    <input
                        type="date"
                        id="imbTglAwal"
                        class="imb-input"
                    >
                    <span class="imb-separator">
                        s.d
                    </span>
                    <input
                        type="date"
                        id="imbTglAkhir"
                        class="imb-input"
                    >
                </div>
            </div>
            <button
                type="button"
                class="imb-action-button"
                onclick="getImbData()"
            >
                OK
            </button>
            <button
                type="button"
                class="imb-action-button imb-print-button"
                id="imbPrintButton"
                onclick="printImbReport()"
                autocomplete="off"
                aria-disabled="true"
                disabled
            >
                PRINT
            </button>
            <div class="imb-field-row imb-location-row">
                <span class="imb-label">
                    Lokasi
                </span>
                <div class="imb-lookup">
                    <div
                        id="imbLokasiEntry"
                        class="imb-lookup-display"
                    >
                        Semua Lokasi
                    </div>
                    <button
                        type="button"
                        class="imb-lookup-button"
                        onclick="getImbLokasiModal()"
                        aria-label="Cari lokasi"
                        title="Cari lokasi/sektor"
                    >
                        <i class="fas fa-binoculars"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>
    <section class="imb-report-workspace">
        <div id="imbLoading">
            <i class="fas fa-spinner fa-spin"></i>
            Mengambil data Rekapitulasi IMB...
        </div>
        <div id="imbMainDisplay">
            <div class="imb-paper">
                <div class="imb-initial-state">
                    <i
                        class="fas fa-table"
                        aria-hidden="true"
                    ></i>
                    <div>
                        Silakan isi filter kemudian klik OK
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div
        class="modal"
        id="imbLokasiModal"
        tabindex="-1"
        aria-hidden="true"
    >
        <div
            class="modal-dialog modal-lg modal-dialog-centered"
        >
            <div class="modal-content">
                <div class="modal-header">
                    <h5
                        id="imbLokasiModalTitle"
                        style="
                            margin: 0;
                            font-size: 15px;
                            font-weight: 800;
                        "
                    >
                        Pilih Lokasi
                    </h5>
                    <button
                        type="button"
                        class="btn btn-light"
                        onclick="toggleImbLokasiModal('hide')"
                        aria-label="Tutup"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div
                    class="modal-body"
                    id="imbLokasiModalContent"
                ></div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script>
    var lastImbRows = null;
$(document).ready(function () {
        setImbPrintEnabled(false);
        lastImbRows = null;
        setImbDefaultDate();
        $('#imbBlokAwal, #imbBlokAkhir').on('input', function () {
            $(this).val(
                String($(this).val() || '').toUpperCase()
            );
        });
    });
    $(window).on('pageshow', function (event) {
        var pageEvent = event.originalEvent || event;
        if (pageEvent.persisted) {
            setImbPrintEnabled(false);
            lastImbRows = null;
            hideImbNoDataAlert();
        }
    });
    function setImbPrintEnabled(enabled) {
        var disabled = enabled !== true;
        $('#imbPrintButton')
            .prop('disabled', disabled)
            .attr('aria-disabled', disabled ? 'true' : 'false');
    }
    function setImbDefaultDate() {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var today = year + '-' + month + '-' + day;
        $('#imbTglAwal').val(today);
        $('#imbTglAkhir').val(today);
    }
    function imbEscapeHtml(value) {
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
    function imbEscapeJs(value) {
        return String(
            value === null || value === undefined ? '' : value
        )
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r?\n/g, ' ');
    }
    function imbValue(value, fallback) {
        return value === null
            || value === undefined
            || value === ''
            ? (fallback === undefined ? '-' : fallback)
            : value;
    }
    function imbPick(item, keys) {
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
    function imbFormatDate(value) {
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
        var slash = text.match(
            /^(\d{4})\/(\d{2})\/(\d{2})/
        );
        if (slash) {
            return slash[3] + '-' + slash[2] + '-' + slash[1];
        }
        return text;
    }
    function imbFormatNumber(value) {
        if (
            value === null
            || value === undefined
            || value === ''
        ) {
            return '-';
        }
        var number = Number(value);
        if (!isFinite(number)) {
            return imbEscapeHtml(value);
        }
        return number.toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    function imbCompanyName(first) {
        var rowName = imbPick(first || {}, [
            'NAMA_PT',
            'nama_pt',
            'NAMA_PERUSAHAAN',
            'nama_perusahaan'
        ]);
        var sessionName = String(
            $('#imbNamaPerusahaan').val() || ''
        ).trim();
        var unit = String(
            $('#imbPerusahaan').val() || ''
        ).trim();
        return String(
            rowName || sessionName || unit || '-'
        ).trim();
    }
    function getImbLokasiModalElement() {
        var modal = $('#imbLokasiModal');
        if (modal.length && !modal.parent().is('body')) {
            modal.appendTo(document.body);
        }
        return modal;
    }
    function cleanupImbLokasiModal() {
        $(
            '.imb-owned-backdrop, .imb-fallback-backdrop'
        ).remove();
        if (
            !$('.modal.show:visible')
                .not('#imbLokasiModal')
                .length
        ) {
            $('body')
                .removeClass('modal-open')
                .css('padding-right', '');
        }
    }
    function toggleImbLokasiModal(action) {
        var modal = getImbLokasiModalElement();
        if (!modal.length) {
            return;
        }
        if (typeof modal.modal === 'function') {
            modal
                .off('.imbModalFix')
                .on(
                    'shown.bs.modal.imbModalFix',
                    function () {
                        $(this).css('z-index', 1065);
                        $('body > .modal-backdrop')
                            .not('.imb-owned-backdrop')
                            .last()
                            .addClass('imb-owned-backdrop');
                        $(this)
                            .find('.imb-modal-search')
                            .trigger('focus');
                    }
                )
                .on(
                    'hidden.bs.modal.imbModalFix',
                    function () {
                        cleanupImbLokasiModal();
                    }
                );
            modal.modal(action);
            return;
        }
        var show = action === 'show';
        if (show) {
            if (!$('.imb-fallback-backdrop').length) {
                $(
                    '<div class="modal-backdrop fade show imb-fallback-backdrop"></div>'
                ).appendTo(document.body);
            }
            modal
                .addClass('show')
                .css({
                    display: 'block',
                    zIndex: 1065
                })
                .attr('aria-hidden', 'false');
            $('body').addClass('modal-open');
            modal
                .find('.imb-modal-search')
                .trigger('focus');
        } else {
            modal
                .removeClass('show')
                .css('display', 'none')
                .attr('aria-hidden', 'true');
            cleanupImbLokasiModal();
        }
    }
    function selectImbLokasi(kode, deskripsi) {
        $('#imbLokasi').val(kode || '*');
        $('#imbLokasiEntry').text(
            deskripsi || 'Semua Lokasi'
        );
        toggleImbLokasiModal('hide');
    }
    function filterImbLokasiTable(keyword) {
        var search = String(
            keyword || ''
        ).toLowerCase().trim();
        $(
            '#imbLokasiModal .imb-modal-table tbody tr'
        ).each(function () {
            $(this).toggle(
                $(this)
                    .text()
                    .toLowerCase()
                    .indexOf(search) !== -1
            );
        });
    }
    function getImbLokasiModal() {
        var perusahaan = String(
            $('#imbPerusahaan').val() || ''
        ).trim();
        if (!perusahaan) {
            alert('Unit/perusahaan belum tersedia.');
            return;
        }
        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_lokasi',
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
                html += 'class="imb-modal-search" ';
                html += 'placeholder="Cari lokasi / sektor..." ';
                html += 'onkeyup="filterImbLokasiTable(this.value)">';
                html += '<div class="imb-modal-table-wrap">';
                html += '<table class="imb-modal-table">';
                html += '<thead><tr>';
                html += '<th>Kode</th>';
                html += '<th>Lokasi / Sektor / Cluster</th>';
                html += '<th>Perusahaan</th>';
                html += '</tr></thead><tbody>';
                html += '<tr onclick="selectImbLokasi(';
                html += '\'*\', \'Semua Lokasi\'';
                html += ')">';
                html += '<td>*</td>';
                html += '<td>Semua Lokasi</td>';
                html += '<td>' + imbEscapeHtml(perusahaan) + '</td>';
                html += '</tr>';
                $.each(rows, function (index, item) {
                    var kode = item.KD_LOKASI
                        || item.KD_SEKTOR
                        || item.kd_lokasi
                        || item.kd_sektor
                        || '';
                    var deskripsi = item.DESKRIPSI
                        || item.deskripsi
                        || kode;
                    var unit = item.KD_PERUSAHAAN
                        || item.kd_perusahaan
                        || perusahaan;
                    html += '<tr onclick="selectImbLokasi(\'';
                    html += imbEscapeJs(kode);
                    html += '\', \'';
                    html += imbEscapeJs(deskripsi);
                    html += '\')">';
                    html += '<td>' + imbEscapeHtml(kode) + '</td>';
                    html += '<td>' + imbEscapeHtml(deskripsi) + '</td>';
                    html += '<td>' + imbEscapeHtml(unit) + '</td>';
                    html += '</tr>';
                });
                if (rows.length < 1) {
                    html += '<tr>';
                    html += '<td colspan="3" ';
                    html += 'style="padding:22px;text-align:center;">';
                    html += 'Data lokasi tidak ditemukan.';
                    html += '</td></tr>';
                }
                html += '</tbody></table></div>';
                $('#imbLokasiModalContent').html(html);
                toggleImbLokasiModal('show');
            },
            error: function (xhr) {
                var message = 'Gagal mengambil data lokasi.';
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
    function validateImbFilter() {
        if (
            !$('#imbBlokAwal').val()
            || !$('#imbBlokAkhir').val()
        ) {
            alert('Rentang blok wajib diisi.');
            return false;
        }
        if (
            !$('#imbTglAwal').val()
            || !$('#imbTglAkhir').val()
        ) {
            alert('Rentang tanggal input wajib diisi.');
            return false;
        }
        if (
            $('#imbTglAwal').val()
            > $('#imbTglAkhir').val()
        ) {
            alert(
                'Tanggal awal tidak boleh melebihi tanggal akhir.'
            );
            return false;
        }
        return true;
    }
    function getImbFilterData() {
        return {
            _token: '{{ csrf_token() }}',
            blok_awal: String(
                $('#imbBlokAwal').val() || 'A'
            ).toUpperCase(),
            blok_akhir: String(
                $('#imbBlokAkhir').val() || 'ZZ'
            ).toUpperCase(),
            tgl_awal: $('#imbTglAwal').val(),
            tgl_akhir: $('#imbTglAkhir').val(),
            perusahaan: $('#imbPerusahaan').val(),
            lokasi: $('#imbLokasi').val() || '*'
        };
    }
    /*
     * Alert data kosong, mengikuti Daftar Sertifikat Pecahan.
     * Hanya menampilkan pesan; pengambilan data dan render laporan
     * tetap memakai alur yang sudah ada. Jika plugin modal tidak
     * tersedia pada halaman ini, modal ditampilkan secara manual
     * sehingga tampilannya tetap sama.
     */
    function showImbNoDataAlert(message) {
        var $modal = $('#imbNoDataAlertModal');

        $('#imbNoDataMessage').text(message || 'Data tidak ditemukan......!');

        if (typeof $modal.modal === 'function') {
            $modal.modal('show');
            return;
        }

        if (!$('.imb-nodata-backdrop').length) {
            $('<div class="modal-backdrop fade show imb-nodata-backdrop"></div>')
                .appendTo(document.body);
        }

        $modal
            .addClass('show')
            .css('display', 'block')
            .attr('aria-hidden', 'false');
    }

    function hideImbNoDataAlert() {
        var $modal = $('#imbNoDataAlertModal');

        if (typeof $modal.modal === 'function') {
            $modal.modal('hide');
            return;
        }

        $modal
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true');

        $('.imb-nodata-backdrop').remove();
    }

    function getImbData() {
        if (!validateImbFilter()) {
            return;
        }
        hideImbNoDataAlert();

        setImbPrintEnabled(false);
        $('#imbLoading').show();
        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_data',
            dataType: 'json',
            timeout: 120000,
            headers: {
                'Accept': 'application/json'
            },
            data: getImbFilterData(),
            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (
                        response
                        && Array.isArray(response.data)
                            ? response.data
                            : []
                    );
                lastImbRows = rows;

                if (rows.length === 0) {
                    showImbNoDataAlert('Data tidak ditemukan......!');
                }

                renderImbReport(rows);
            },
            error: function (xhr, textStatus, errorThrown) {
                setImbPrintEnabled(false);
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
                $('#imbMainDisplay').html(
                    '<div class="imb-paper">'
                    + '<div class="alert alert-danger">'
                    + 'Gagal mengambil data Rekapitulasi IMB. '
                    + imbEscapeHtml(detail)
                    + '</div></div>'
                );
            },
            complete: function () {
                $('#imbLoading').hide();
            }
        });
    }
    function groupImbRowsBySector(rows) {
        var groups = [];
        var indexMap = {};
        $.each(rows, function (index, item) {
            item = item || {};
            var sector = String(
                imbPick(item, [
                    'NAMA_SEKTOR',
                    'NAMA_LOKASI',
                    'nama_sektor',
                    'nama_lokasi'
                ])
                || $('#imbLokasiEntry').text()
                || 'TANPA SEKTOR'
            ).trim();
            if (!sector) {
                sector = 'TANPA SEKTOR';
            }
            if (indexMap[sector] === undefined) {
                indexMap[sector] = groups.length;
                groups.push({
                    sector: sector,
                    rows: []
                });
            }
            groups[indexMap[sector]].rows.push(item);
        });
        return groups;
    }
    function renderImbReport(rows) {
        rows = Array.isArray(rows) ? rows : [];
        var first = rows.length > 0 ? rows[0] : {};
        var company = imbCompanyName(first);
        var blok = String(
            $('#imbBlokAwal').val() || 'A'
        ).toUpperCase()
            + ' s/d '
            + String(
                $('#imbBlokAkhir').val() || 'ZZ'
            ).toUpperCase();
        var period = imbFormatDate(
            $('#imbTglAwal').val()
        )
            + ' s/d '
            + imbFormatDate(
                $('#imbTglAkhir').val()
            );
        var selectedLocation = String(
            $('#imbLokasiEntry').text() || 'Semua Lokasi'
        ).trim();
        var groups = groupImbRowsBySector(rows);
        var html = '';
        html += '<div class="imb-paper">';
        html += '<div class="imb-report-top">';
        html += '<div class="imb-report-company-wrap">';
        html += '<div class="imb-company">';
        html += imbEscapeHtml(company);
        html += '</div>';
        html += '</div>';
        html += '<div class="imb-title-block">';
        html += '<h2 class="imb-report-title">';
        html += 'Laporan Rekapitulasi I.M.B';
        html += '</h2>';
        html += '</div>';
        html += '<div class="imb-report-period">';
        html += 'BLOK: ' + imbEscapeHtml(blok) + '<br>';
        html += 'Tgl. Input: ' + imbEscapeHtml(period);
        html += '</div>';
        html += '</div>';
        html += '<div class="imb-report-subtitle">';
        html += '<span class="imb-report-subtitle-label">';
        html += 'Sektor/Cluster:';
        html += '</span>';
        html += '<strong class="imb-report-subtitle-value">';
        html += imbEscapeHtml(selectedLocation);
        html += '</strong>';
        html += '<span class="imb-live-badge">';
        html += 'Live Data';
        html += '</span>';
        html += '</div>';
        html += '<div class="imb-table-wrap">';
        html += '<table class="imb-report-table">';
        html += '<colgroup>';
        html += '<col style="width:48px">';
        html += '<col style="width:90px">';
        html += '<col style="width:230px">';
        html += '<col style="width:175px">';
        html += '<col style="width:90px">';
        html += '<col style="width:175px">';
        html += '<col style="width:90px">';
        html += '<col style="width:100px">';
        html += '<col style="width:160px">';
        html += '<col style="width:90px">';
        html += '</colgroup>';
        html += '<thead>';
        html += '<tr>';
        html += '<th rowspan="2">No.</th>';
        html += '<th rowspan="2">BLOK/<br>NOMOR</th>';
        html += '<th rowspan="2">Nama Pemilik</th>';
        html += '<th colspan="2" class="imb-group-heading">';
        html += 'P.I.M.B';
        html += '</th>';
        html += '<th colspan="2" class="imb-group-heading">';
        html += 'I.M.B';
        html += '</th>';
        html += '<th rowspan="2">';
        html += 'Luas Bangunan<br>(M2)';
        html += '</th>';
        html += '<th colspan="2" class="imb-group-heading">';
        html += 'I.P.B';
        html += '</th>';
        html += '</tr>';
        html += '<tr>';
        html += '<th class="imb-sub-heading">Nomor</th>';
        html += '<th class="imb-sub-heading">Tanggal</th>';
        html += '<th class="imb-sub-heading">Nomor</th>';
        html += '<th class="imb-sub-heading">Tanggal</th>';
        html += '<th class="imb-sub-heading">Nomor</th>';
        html += '<th class="imb-sub-heading">Tanggal</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';
        if (rows.length < 1) {
            html += '<tr>';
            html += '<td colspan="10" class="imb-empty">';
            html += 'Data tidak ditemukan.';
            html += '</td>';
            html += '</tr>';
        } else {
            var runningNo = 1;
            $.each(groups, function (groupIndex, group) {
                html += '<tr class="imb-sector-row">';
                html += '<td colspan="10">';
                html += 'Sektor/Cluster : ';
                html += imbEscapeHtml(group.sector);
                html += '</td>';
                html += '</tr>';
                $.each(group.rows, function (rowIndex, item) {
                    item = item || {};
                    html += '<tr>';
                    html += '<td class="imb-cell-center">';
                    html += runningNo++;
                    html += '</td>';
                    html += '<td class="imb-cell-center">';
                    html += imbEscapeHtml(
                        imbValue(
                            imbPick(item, [
                                'BLOK_NOMOR',
                                'blok_nomor'
                            ])
                        )
                    );
                    html += '</td>';
                    html += '<td class="imb-cell-left">';
                    html += imbEscapeHtml(
                        imbValue(
                            imbPick(item, [
                                'NAMA',
                                'NAMA_PEMILIK',
                                'NASABAH_NAMA',
                                'nama',
                                'nama_pemilik',
                                'nasabah_nama'
                            ])
                        )
                    );
                    html += '</td>';
                    html += '<td class="imb-cell-left">';
                    html += imbEscapeHtml(
                        imbValue(
                            imbPick(item, [
                                'NO_PIMB',
                                'NO_PBG',
                                'no_pimb',
                                'no_pbg'
                            ])
                        )
                    );
                    html += '</td>';
                    html += '<td class="imb-cell-center">';
                    html += imbEscapeHtml(
                        imbFormatDate(
                            imbPick(item, [
                                'TGL_PIMB',
                                'TGL_PBG',
                                'tgl_pimb',
                                'tgl_pbg'
                            ])
                        )
                    );
                    html += '</td>';
                    html += '<td class="imb-cell-left">';
                    html += imbEscapeHtml(
                        imbValue(
                            imbPick(item, [
                                'NO_IMB',
                                'no_imb'
                            ])
                        )
                    );
                    html += '</td>';
                    html += '<td class="imb-cell-center">';
                    html += imbEscapeHtml(
                        imbFormatDate(
                            imbPick(item, [
                                'TGL_IMB',
                                'tgl_imb'
                            ])
                        )
                    );
                    html += '</td>';
                    html += '<td class="imb-cell-number">';
                    html += imbFormatNumber(
                        imbPick(item, [
                            'LUAS_BANGUNAN',
                            'luas_bangunan'
                        ])
                    );
                    html += '</td>';
                    html += '<td class="imb-cell-left">';
                    html += imbEscapeHtml(
                        imbValue(
                            imbPick(item, [
                                'NO_IPB',
                                'no_ipb'
                            ])
                        )
                    );
                    html += '</td>';
                    html += '<td class="imb-cell-center">';
                    html += imbEscapeHtml(
                        imbFormatDate(
                            imbPick(item, [
                                'TGL_IPB',
                                'tgl_ipb'
                            ])
                        )
                    );
                    html += '</td>';
                    html += '</tr>';
                });
            });
        }
        html += '</tbody>';
        html += '</table>';
        html += '</div>';
        html += '</div>';
        $('#imbMainDisplay').html(html);
        setImbPrintEnabled(true);
    }
    function printImbReport() {
        if (
            $('#imbPrintButton').prop('disabled')
            || !Array.isArray(lastImbRows)
            || !$('#imbMainDisplay .imb-report-table').length
        ) {
            return;
        }
        printImbInNativeDialog();
    }
    function printImbInNativeDialog() {
        var reportHtml = $('#imbMainDisplay').html();
        if (!reportHtml) {
            return;
        }
        $('#imbNativePrintFrame').remove();
        var frame = document.createElement('iframe');
        frame.id = 'imbNativePrintFrame';
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
.imb-paper {
    width: calc(100% - 16mm);
    max-width: calc(100% - 16mm);
    margin: 8mm auto 0;
    padding: 0;
    background: #ffffff;
    color: #000000;
}
.imb-report-top {
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
.imb-report-company-wrap {
    min-width: 0;
}
.imb-company {
    color: #000000;
    font-size: 10.5px;
    font-weight: 700;
    line-height: 1.45;
    white-space: normal;
}
.imb-title-block {
    min-width: 0;
    text-align: center;
}
.imb-report-title {
    margin: 0;
    color: #000000;
    font-family: Cambria, Georgia, "Times New Roman", serif;
    font-size: 18px;
    font-weight: 700;
    line-height: 1.25;
}
.imb-report-period {
    color: #000000;
    text-align: right;
    font-size: 10.5px;
    font-weight: 600;
    line-height: 1.45;
}
.imb-report-subtitle {
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
.imb-report-subtitle-label {
    flex: 0 0 auto;
}
.imb-report-subtitle-value {
    min-width: 0;
    flex: 1;
    color: #000000;
    font-weight: 700;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.imb-live-badge {
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
.imb-live-badge::before {
    content: "";
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #000000;
}
.imb-table-wrap {
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
    padding: 0;
    overflow: visible;
    border: 0;
    background: #ffffff;
}
.imb-report-table {
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
.imb-report-table thead {
    display: table-header-group;
}
.imb-report-table tbody {
    display: table-row-group;
}
.imb-report-table tr {
    break-inside: avoid;
    page-break-inside: avoid;
}
.imb-report-table th, .imb-report-table td {
    position: static;
    padding: 5px 6px;
    border: 1px solid #000000;
    background: #ffffff;
    color: #000000;
    vertical-align: middle;
    overflow: visible;
    overflow-wrap: anywhere;
}
.imb-report-table th {
    text-align: center;
    font-size: 10px;
    font-weight: 700;
    line-height: 1.25;
}
.imb-report-table .imb-group-heading, .imb-report-table .imb-sub-heading {
    background: #ffffff;
    color: #000000;
}
.imb-sector-row td {
    background: #ffffff;
    color: #000000;
    font-size: 10.5px;
    font-weight: 700;
    line-height: 1.35;
    text-align: left;
}
.imb-cell-center {
    text-align: center;
}
.imb-cell-left {
    text-align: left;
}
.imb-cell-number {
    text-align: right;
    font-variant-numeric: tabular-nums;
}
.imb-empty {
    height: 100px;
    text-align: center;
}
.imb-report-table col:nth-child(1) {
    width: 3.5%;
}
.imb-report-table col:nth-child(2) {
    width: 7%;
}
.imb-report-table col:nth-child(3) {
    width: 19%;
}
.imb-report-table col:nth-child(4) {
    width: 14%;
}
.imb-report-table col:nth-child(5) {
    width: 7%;
}
.imb-report-table col:nth-child(6) {
    width: 14%;
}
.imb-report-table col:nth-child(7) {
    width: 7%;
}
.imb-report-table col:nth-child(8) {
    width: 8%;
}
.imb-report-table col:nth-child(9) {
    width: 13.5%;
}
.imb-report-table col:nth-child(10) {
    width: 7%;
}
        `;
        frameDocument.open();
        frameDocument.write(
            '<!DOCTYPE html>'
            + '<html>'
            + '<head>'
            + '<meta charset="utf-8">'
            + '<title>Laporan Rekapitulasi I.M.B</title>'
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
                    $('#imbNativePrintFrame').remove();
                }, 1200);
            }
        };
        window.setTimeout(doPrint, 150);
    }</script>
@endsection