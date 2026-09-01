@extends('layouts.template')

@section('content')
<style>
    .sertipikat-page {
        --sp-ink: #172033;
        --sp-muted: #667085;
        --sp-line: #dbe3ef;
        --sp-card: #ffffff;
        --sp-blue: #2563eb;
        --sp-cyan: #38bdf8;
        --sp-violet: #6366f1;
        --sp-emerald: #059669;
        --sp-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
        position: relative;
        isolation: auto;
        width: 100%;
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

    .sertipikat-page,
    .sertipikat-page * {
        box-sizing: border-box;
    }

    .sertipikat-view-version {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        min-height: 78px;
        margin-bottom: 16px;
        padding: 16px 20px 16px 68px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 22px;
        background: linear-gradient(90deg, #ffffff 0%, #ffffff 65%, #f8fbff 100%);
        color: #172033;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
    }

    .sertipikat-view-version::before {
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

    .sertipikat-view-version::after {
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

    .sertipikat-view-version span {
        position: relative;
        z-index: 1;
        color: #172033;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: 0.11em;
        text-transform: uppercase;
    }

    .sertipikat-view-version code {
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

    .desktop-filter-panel {
        position: relative;
        padding: 20px;
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
    }

    .desktop-filter-panel::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: linear-gradient(180deg, #38bdf8 0%, #2563eb 52%, #1d4ed8 100%);
    }

    .desktop-filter-panel::after {
        content: "Sertifikat Pecahan";
        position: absolute;
        right: 20px;
        bottom: 12px;
        color: rgba(37, 99, 235, 0.035);
        font-size: 34px;
        font-weight: 950;
        letter-spacing: 0.08em;
        pointer-events: none;
    }

    .desktop-filter-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 82px;
        gap: 13px 14px;
        align-items: center;
    }

    .desktop-field-row {
        display: grid;
        grid-template-columns: 72px minmax(0, 1fr);
        gap: 9px;
        align-items: center;
        min-width: 0;
    }

    .desktop-label {
        overflow: hidden;
        color: #475467;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.10em;
        text-overflow: ellipsis;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .range-control {
        display: grid;
        grid-template-columns: minmax(86px, 1fr) 34px minmax(86px, 1fr);
        gap: 7px;
        align-items: center;
    }

    .desktop-input,
    .lookup-display {
        width: 100%;
        min-width: 0;
        height: 42px;
        padding: 8px 12px;
        border: 1px solid #c8d3e1;
        border-radius: 12px;
        background: #ffffff;
        color: #101828;
        font-size: 12px;
        font-weight: 650;
        outline: 0;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .desktop-input:hover,
    .lookup-display:hover {
        border-color: #aebed1;
        background: #ffffff;
    }

    .desktop-input:focus {
        border-color: #2563eb;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
    }

    .range-separator {
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

    .lookup-control {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 42px;
        gap: 8px;
    }

    .lookup-display {
        display: flex;
        align-items: center;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .lookup-button {
        position: relative;
        display: inline-flex;
        height: 42px;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid #bfdbfe;
        border-radius: 13px;
        background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%);
        color: #1d4ed8;
        cursor: pointer;
        font-size: 12px;
        font-weight: 900;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .lookup-button:hover {
        transform: translateY(-1px);
        background: linear-gradient(145deg, #dbeafe 0%, #bfdbfe 100%);
        box-shadow: 0 7px 15px rgba(37, 99, 235, 0.12);
    }

    .sp-action-stack {
        position: relative;
        width: 82px;
        height: 42px;
        grid-column: 3;
        grid-row: 1;
        justify-self: end;
    }

    .sp-action-stack .ok-button {
        position: absolute;
        top: 0;
        right: -20px;
        width: 82px;
        min-width: 82px;
        height: 42px;
        margin: 0;
        border-radius: 13px 0 0 13px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
        border: 0;
        cursor: pointer;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .sp-action-stack .ok-button:hover {
        transform: translateY(-1px);
        background: linear-gradient(135deg, #2f6ff0 0%, #1e4fc4 100%);
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.30);
    }

    .sp-print-button {
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
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .sp-print-button:hover:not(:disabled) {
        transform: translateY(-1px);
        background: linear-gradient(135deg, #14b88a 0%, #047857 100%);
        box-shadow: 0 12px 24px rgba(5, 150, 105, .28);
    }

    .sp-print-button:disabled,
    .sp-print-button:disabled:hover,
    .sp-print-button:disabled:focus {
        transform: none;
        border: 1px solid #d5dde7;
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        color: #94a3b8;
        box-shadow: none;
        cursor: not-allowed;
        opacity: 1;
    }

    .radio-row,
    .checkbox-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        min-height: 42px;
    }

    .desktop-option {
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
        transition: border-color 0.2s ease, color 0.2s ease, background 0.2s ease;
    }

    .desktop-option:hover {
        border-color: #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .desktop-option input {
        width: 15px;
        height: 15px;
        margin: 0;
        accent-color: #2563eb;
    }

    .desktop-option:has(input:checked),
    .radio-row .desktop-option:has(input[name="status_ajb"]:checked) {
        border-color: #bfdbfe;
        background: linear-gradient(135deg, #eff6ff, #e0f2fe);
        color: #1e40af;
    }

    .checkbox-row .desktop-option:has(input:checked) {
        border-color: #a7f3d0;
        background: linear-gradient(135deg, #ecfdf3, #f0fdf4);
        color: #047857;
    }

    .report-shell {
        position: relative;
        margin-top: 18px;
        padding: 20px;
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 26px;
        background: #ffffff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
    }

    .report-shell::before {
        content: "";
        position: absolute;
        top: 0;
        left: 7%;
        right: 7%;
        height: 3px;
        border-radius: 0 0 999px 999px;
        background: linear-gradient(90deg, transparent, #38bdf8, #2563eb, #6366f1, transparent);
    }

    .report-header {
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

    .report-company {
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 850;
        letter-spacing: 0.04em;
    }

    .report-title {
        text-align: center;
        font-size: 18px;
        font-weight: 950;
        letter-spacing: -0.02em;
        color: #172033;
    }

    .report-period {
        color: #475467;
        text-align: right;
        font-size: 10.5px;
        font-weight: 650;
        line-height: 1.55;
    }

    .report-subtitle {
        display: flex;
        min-height: 36px;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: linear-gradient(90deg, #ffffff, #f8fafc);
        color: #667085;
        font-size: 10.5px;
    }

    .report-subtitle strong {
        color: #344054;
        font-weight: 850;
    }

    .report-live-badge {
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

    .report-live-badge::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
    }

    .report-table-wrapper {
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

    .report-table {
        width: 1620px;
        min-width: 1620px;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
        color: #344054;
        font-size: 10.5px;
    }

    .report-table.with-gabungan {
        width: 1850px;
        min-width: 1850px;
    }

    .report-table th,
    .report-table td {
        padding: 8px 9px;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        overflow-wrap: anywhere;
    }

    .report-table thead tr:first-child th {
        top: 0;
        z-index: 4;
        height: 48px;
        border-bottom-color: #c8d3e1;
        background: linear-gradient(180deg, #eff6ff 0%, #e5effb 100%);
        color: #344054;
        box-shadow: inset 0 -1px 0 rgba(37, 99, 235, 0.05);
    }

    .report-table thead tr:nth-child(2) th {
        top: 48px;
        z-index: 3;
        height: 36px;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        color: #475467;
    }

    .report-table th {
        position: sticky;
        text-align: center;
        font-weight: 900;
        line-height: 1.25;
    }

    .report-table .group-heading {
        background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%) !important;
        color: #3730a3 !important;
        font-weight: 950;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .report-table .sub-heading {
        padding-top: 7px;
        padding-bottom: 7px;
        font-size: 9.5px;
        font-weight: 900;
    }

    .report-table td {
        height: 46px;
        background: #ffffff;
        color: #344054;
        line-height: 1.38;
    }

    .report-table tbody tr:nth-child(even) td {
        background: #fbfcfe;
    }

    .report-table tbody tr:hover td {
        background: #f0f7ff;
    }

    .report-table tbody tr:hover td:first-child {
        box-shadow: inset 4px 0 0 #2563eb;
        color: #1d4ed8;
    }

    .report-table .name-cell {
        color: #172033;
        font-weight: 800;
        text-align: left;
    }

    .report-table .multiline-cell {
        text-align: left;
        line-height: 1.45;
    }

    .number-cell {
        color: #1e3a5f;
        text-align: right;
        font-weight: 750;
        font-variant-numeric: tabular-nums;
    }

    .center-cell {
        text-align: center;
    }

    .initial-state {
        position: relative;
        display: flex;
        min-height: 310px;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        border: 1px dashed #bfdbfe;
        border-radius: 20px;
        background:
            radial-gradient(circle at center, rgba(37, 99, 235, 0.06), transparent 46%),
            #f8fbff;
        color: #667085;
        font-size: 13px;
        font-weight: 650;
    }

    .initial-state-icon {
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

    #loading-info {
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

    /* Modal Sektor */
    #sertipikatModal {
        position: fixed;
        inset: 0;
        z-index: 1065 !important;
        overflow-x: hidden;
        overflow-y: auto;
        pointer-events: none;
    }

    #sertipikatModal.show {
        pointer-events: auto;
    }

    #sertipikatModal .modal-dialog {
        position: relative;
        z-index: 1066;
        pointer-events: auto;
        width: calc(100vw - 32px);
        max-width: 800px;
        margin: 24px auto;
    }

    .sertipikat-owned-backdrop,
    .sertipikat-fallback-backdrop {
        z-index: 1060 !important;
        opacity: 0.24 !important;
        background: #475569 !important;
    }

    #sertipikatModal .modal-content {
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
    }

    #sertipikatModal .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 18px;
        border-bottom: 1px solid #dbe3ef;
        background: linear-gradient(90deg, #ffffff 0%, #f8fbff 100%);
        color: #1d2939;
    }

    #sertipikatModal .modal-header .btn {
        border: 1px solid #bfdbfe;
        border-radius: 11px;
        background: #eff6ff;
        color: #1d4ed8;
    }

    #sertipikatModal .modal-body {
        padding: 16px;
        background: #f8fafc;
    }

    .modal-search {
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

    .modal-search:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .modal-table-wrapper {
        max-height: 430px;
        overflow: auto;
        border: 1px solid #dbe3ef;
        border-radius: 16px;
        background: #ffffff;
    }

    .modal-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 12px;
    }

    .modal-table th,
    .modal-table td {
        padding: 10px 12px;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }

    .modal-table th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: linear-gradient(180deg, #eff6ff 0%, #e7f0fc 100%);
        color: #344054;
        text-align: center;
        font-weight: 850;
    }

    .modal-table tbody tr {
        cursor: pointer;
    }

    .modal-table tbody tr:hover td {
        background: #eff6ff;
        color: #1d4ed8;
    }

    /* Modal Alert Data Kosong (Menyerupai Daftar SP Sudah PPJB) */
    #sertipikatNoDataAlertModal .modal-dialog {
        max-width: 380px;
    }

    #sertipikatNoDataAlertModal .modal-content {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
    }

    #sertipikatNoDataAlertModal .alert-icon-wrapper {
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

    #sertipikatNoDataAlertModal .alert-title {
        margin-bottom: 8px;
        color: #172033;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 18px;
        font-weight: 700;
    }

    #sertipikatNoDataAlertModal .alert-message {
        margin-bottom: 24px;
        color: #475569;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 14px;
    }

    #sertipikatNoDataAlertModal .alert-btn-ok {
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

    #sertipikatNoDataAlertModal .alert-btn-ok:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
    }

    /* Kartu Surat Tanah */
    .kst-list {
        display: grid;
        gap: 26px;
        padding: 6px;
    }

    .kst-card {
        position: relative;
        width: min(100%, 920px);
        margin: 0 auto;
        padding: 38px 36px 30px;
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 28px;
        background:
            linear-gradient(#ffffff, #ffffff) padding-box,
            linear-gradient(135deg, #38bdf8, #2563eb, #6366f1) border-box;
        color: #111827;
        box-shadow: 0 16px 38px rgba(15, 23, 42, 0.09);
        break-after: page;
        page-break-after: always;
    }

    .kst-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto;
        height: 8px;
        background: linear-gradient(90deg, #38bdf8, #2563eb, #6366f1);
    }

    .kst-card-title {
        margin: 20px 0 20px;
        background: linear-gradient(110deg, #1d4ed8, #2563eb, #4f46e5);
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
        text-align: center;
        font-size: 27px;
        font-weight: 950;
        letter-spacing: 0.04em;
    }

    .kst-card-meta {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 200px;
        margin-bottom: 16px;
        overflow: hidden;
        border: 1px solid #c8d3e1;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
    }

    .kst-info-table,
    .kst-log-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .kst-info-table td {
        height: 38px;
        padding: 7px 10px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 12.5px;
        font-weight: 650;
        vertical-align: middle;
    }

    .kst-info-label {
        width: 180px;
        color: #475569;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .kst-info-separator {
        width: 16px;
        color: #94a3b8;
        text-align: center;
    }

    .kst-block-box {
        display: flex;
        flex-direction: column;
        justify-content: center;
        border-left: 1px solid #bfdbfe;
        padding: 14px 20px;
        background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 72%, #eef2ff 100%);
        color: #172033;
    }

    .kst-block-label {
        text-align: center;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.35em;
    }

    .kst-block-value {
        margin-top: 18px;
        text-align: center;
        font-size: 23px;
        font-weight: 950;
    }

    .kst-log-table th,
    .kst-log-table td {
        border: 1px solid #d6e0ec;
        vertical-align: middle;
    }

    .kst-log-table th {
        padding: 7px 6px;
        background: linear-gradient(135deg, #dcfce7 0%, #ecfdf3 52%, #e0f2fe 100%);
        color: #166534;
        text-align: center;
        font-size: 11px;
        font-weight: 900;
    }

    .kst-log-table td {
        height: 32px;
        padding: 4px 7px;
        background: #fff;
        font-size: 11.5px;
    }

    .kst-log-no {
        width: 45px;
        text-align: center;
    }

    .kst-log-date {
        width: 110px;
        text-align: center;
    }

    .kst-log-paraf {
        width: 75px;
        text-align: center;
    }

    .kst-card-caption {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-top: 10px;
        color: #64748b;
        font-size: 9.5px;
        font-weight: 700;
    }

    .kst-navigator {
        position: sticky;
        top: 10px;
        z-index: 20;
        display: grid;
        grid-template-columns: minmax(250px, 1fr) auto auto;
        gap: 10px 14px;
        align-items: center;
        width: min(100%, 920px);
        margin: 0 auto 14px;
        padding: 11px 12px;
        border: 1px solid #dbe3ef;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.10);
    }

    .kst-navigator-main {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .kst-navigator-label {
        color: #475467;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .kst-block-search-wrap {
        position: relative;
        width: 100%;
    }

    .kst-block-search-icon {
        position: absolute;
        top: 50%;
        left: 11px;
        z-index: 2;
        transform: translateY(-50%);
        color: #2563eb;
        font-size: 11px;
    }

    .kst-block-search {
        width: 100%;
        height: 38px;
        padding: 7px 34px 7px 31px;
        border: 1px solid #c8d3e1;
        border-radius: 9px;
        background: #ffffff;
        color: #172033;
        font-size: 12px;
        font-weight: 750;
        outline: 0;
    }

    .kst-block-search-clear {
        position: absolute;
        top: 50%;
        right: 6px;
        z-index: 3;
        display: none;
        width: 26px;
        height: 26px;
        align-items: center;
        justify-content: center;
        transform: translateY(-50%);
        border: 0;
        border-radius: 7px;
        background: transparent;
        color: #64748b;
        cursor: pointer;
    }

    .kst-block-search-wrap.has-value .kst-block-search-clear {
        display: inline-flex;
    }

    .kst-block-suggestions {
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        right: 0;
        z-index: 80;
        display: none;
        max-height: 250px;
        overflow-y: auto;
        border: 1px solid #c8d3e1;
        border-radius: 9px;
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .16);
    }

    .kst-block-suggestions.show {
        display: block;
    }

    .kst-block-suggestion {
        display: flex;
        width: 100%;
        min-height: 34px;
        align-items: center;
        padding: 7px 10px;
        border: 0;
        border-bottom: 1px solid #eef2f6;
        background: #ffffff;
        color: #172033;
        cursor: pointer;
        font-size: 12px;
        font-weight: 700;
    }

    .kst-nav-buttons {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .kst-nav-button {
        display: inline-flex;
        width: 36px;
        height: 36px;
        align-items: center;
        justify-content: center;
        border: 1px solid #bfdbfe;
        border-radius: 9px;
        background: linear-gradient(145deg, #eff6ff, #dbeafe);
        color: #1d4ed8;
        cursor: pointer;
    }

    .kst-nav-counter {
        display: inline-flex;
        min-width: 88px;
        height: 36px;
        align-items: center;
        justify-content: center;
        padding: 0 11px;
        border: 1px solid #dbe3ef;
        border-radius: 999px;
        background: #f8fafc;
        color: #475467;
        font-size: 11px;
        font-weight: 800;
    }

    .kst-card {
        display: none;
    }

    .kst-card.is-active {
        display: block;
    }

    /* Footer Tanda Tangan */
    .report-signature-footer {
        width: min(100%, 980px);
        min-height: 190px;
        margin: 18px auto 2px;
        padding: 0 34px 18px;
        color: #344054;
        font-size: 11px;
    }

    .report-signature-footer-date {
        width: 50%;
        margin: 0 0 8px auto;
        text-align: center;
        color: #475467;
        font-weight: 600;
    }

    .report-signature-footer-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 90px;
    }

    .report-signature-footer-box {
        text-align: center;
    }

    .report-signature-footer-role {
        min-height: 20px;
        color: #344054;
        font-weight: 600;
    }

    .report-signature-footer-space {
        height: 78px;
    }

    .report-signature-footer-line {
        display: inline-flex;
        width: min(100%, 220px);
        align-items: flex-end;
        justify-content: center;
        color: #667085;
    }

    .report-signature-footer-line::before {
        content: "(";
        margin-right: 3px;
    }

    .report-signature-footer-line::after {
        content: ")";
        margin-left: 3px;
    }

    .report-signature-footer-line > span {
        display: block;
        width: 100%;
        height: 10px;
        border-bottom: 1px dotted #98a2b3;
    }

    /* =========================================================
       ACTION PRINT STANDAR DAFTAR PBB
       ========================================================= */
    @media print {
        .desktop-filter-panel,
        .sertipikat-view-version,
        #loading-info,
        #sertipikatModal,
        #sertipikatNoDataAlertModal,
        .modal-backdrop,
        .kst-navigator,
        .main-sidebar,
        .control-sidebar,
        .main-header,
        .main-footer,
        .navbar,
        .sidebar {
            display: none !important;
        }

        html,
        body,
        .wrapper,
        .content-wrapper,
        .main-content,
        .content,
        .page-wrapper,
        .page-content,
        .container,
        .container-fluid,
        .sertipikat-page,
        .report-shell {
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
            transform: none !important;
        }

        .sertipikat-page::before,
        .sertipikat-page::after,
        .report-shell::before {
            display: none !important;
        }

        .report-header,
        .report-subtitle {
            border: 1px solid #777 !important;
            border-radius: 0 !important;
            background: #fff !important;
            color: #000 !important;
            box-shadow: none !important;
        }

        .report-company,
        .report-title,
        .report-period,
        .report-subtitle,
        .report-subtitle strong,
        .report-live-badge {
            color: #000 !important;
        }

        .report-live-badge {
            border-color: #777 !important;
            background: #fff !important;
            box-shadow: none !important;
        }

        .report-live-badge::before {
            background: #000 !important;
            box-shadow: none !important;
        }

        .report-table-wrapper {
            width: 100% !important;
            max-width: 100% !important;
            max-height: none !important;
            min-height: 0 !important;
            overflow: visible !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: #fff !important;
            box-shadow: none !important;
        }

        .report-table,
        .report-table.with-gabungan {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            border-spacing: 0 !important;
            border: 1px solid #000 !important;
            background: #fff !important;
            color: #000 !important;
            font-size: 8.5px !important;
        }

        .report-table thead {
            display: table-header-group !important;
        }

        .report-table tbody {
            display: table-row-group !important;
        }

        .report-table tr {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        .report-table th,
        .report-table td {
            position: static !important;
            padding: 4px !important;
            border: 1px solid #000 !important;
            background: #fff !important;
            color: #000 !important;
            box-shadow: none !important;
        }

        .report-signature-footer {
            width: 100% !important;
            margin-top: 16px;
            padding: 0 24px 8px;
            color: #000 !important;
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        .report-signature-footer-date,
        .report-signature-footer-role,
        .report-signature-footer-line {
            color: #000 !important;
        }

        .report-signature-footer-line > span {
            border-bottom-color: #444 !important;
        }

        .kst-list {
            display: block !important;
            padding: 0 !important;
        }

        .kst-card,
        .kst-card.is-active {
            display: block !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 18px !important;
            border: 1px solid #000 !important;
            border-radius: 0 !important;
            background: #fff !important;
            color: #000 !important;
            box-shadow: none !important;
            break-after: page !important;
            page-break-after: always !important;
        }
    }
</style>

<!-- MODAL ALERT DATA KOSONG -->
<div class="modal fade" id="sertipikatNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4" style="text-align: center; padding: 1.5rem;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message" id="sertipikatNoDataMessage">Data tidak ditemukan.</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    onclick="$('#sertipikatNoDataAlertModal').modal('hide')"
                >OK</button>
            </div>
        </div>
    </div>
</div>

<div class="sertipikat-page">
    <div class="sertipikat-view-version" id="sertipikatViewVersion">
        <span>Daftar Sertifikat Pecahan</span>
        <code id="sertipikatUnitBadge">
            UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}
        </code>
    </div>

    <!-- MODAL PENCARIAN SEKTOR -->
    <div class="modal" id="sertipikatModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="sertipikatModalTitle" style="margin:0;font-size:15px;font-weight:800;"></h5>
                    <button type="button" class="btn btn-light" onclick="toggleModal('hide')" aria-label="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" id="sertipikatModalContent"></div>
            </div>
        </div>
    </div>

    <section class="desktop-filter-panel">
        <input type="hidden" id="perusahaan" value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}">
        <input type="hidden" id="nama_perusahaan_session" value="{{ session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? '' }}">
        <input type="hidden" id="sektor" value="*">

        <div class="desktop-filter-grid">
            <div class="desktop-field-row">
                <label class="desktop-label" for="blok_awal">Blok</label>
                <div class="range-control">
                    <input type="text" id="blok_awal" class="desktop-input" value="A" maxlength="30">
                    <span class="range-separator">s.d</span>
                    <input type="text" id="blok_akhir" class="desktop-input" value="ZZ" maxlength="30">
                </div>
            </div>

            <div class="desktop-field-row">
                <label class="desktop-label" for="tgl_awal">Tgl. Input</label>
                <div class="range-control">
                    <input type="date" id="tgl_awal" class="desktop-input">
                    <span class="range-separator">s.d</span>
                    <input type="date" id="tgl_akhir" class="desktop-input">
                </div>
            </div>

            <div class="sp-action-stack">
                <button type="button" class="ok-button" onclick="getData()">
                    OK
                </button>
                <button
                    type="button"
                    class="sp-print-button"
                    id="sertipikatPrintButton"
                    onclick="printSertipikatReport()"
                    aria-disabled="true"
                    disabled
                >
                    PRINT
                </button>
            </div>

            <div class="desktop-field-row">
                <span class="desktop-label">Sektor</span>
                <div class="lookup-control">
                    <div id="sektorEntry" class="lookup-display">Semua Sektor</div>
                    <button type="button" class="lookup-button" onclick="getSektorModal()" aria-label="Cari sektor">
                        <i class="fas fa-binoculars"></i>
                    </button>
                </div>
            </div>

            <div class="desktop-field-row">
                <span class="desktop-label">Status AJB</span>
                <div class="radio-row">
                    <label class="desktop-option" title="Sertipikat yang memiliki AKTA dengan NO_AKTA terisi.">
                        <input type="radio" name="status_ajb" value="SUDAH">
                        <span>Sudah AJB</span>
                    </label>
                    <label class="desktop-option" title="Sertipikat yang belum memiliki AKTA atau NO_AKTA masih kosong.">
                        <input type="radio" name="status_ajb" value="BELUM">
                        <span>Belum AJB</span>
                    </label>
                    <label class="desktop-option">
                        <input type="radio" name="status_ajb" value="SEMUA" checked>
                        <span>Semua</span>
                    </label>
                </div>
            </div>

            <div></div>

            <div class="checkbox-row">
                <label class="desktop-option">
                    <input type="checkbox" id="apartemen">
                    <span>Apartemen</span>
                </label>
                <label class="desktop-option">
                    <input type="checkbox" id="kartu_surat_tanah">
                    <span>Kartu Surat Tanah</span>
                </label>
            </div>

            <div class="checkbox-row">
                <label class="desktop-option">
                    <input type="checkbox" id="tampil_penggabungan">
                    <span>Tampilkan Sertipikat Penggabungan</span>
                </label>
            </div>
        </div>
    </section>

    <section class="report-shell">
        <div id="loading-info">
            <i class="fas fa-spinner fa-spin"></i>
            Mengambil data sertipikat...
        </div>
        <div id="mainDisplay">
            <div class="initial-state">
                <i class="fas fa-table initial-state-icon" aria-hidden="true"></i>
                <div>Silahkan Isi filter kemudian klik OK</div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('js')
<script>
    var lastSertipikatRows = null;
    var kstActiveIndex = 0;
    var kstTotalCards = 0;
    var kstSuggestionIndex = -1;

    $(document).ready(function () {
        resetInitialState();
        syncSertipikatUnitBadge();

        $('#blok_awal, #blok_akhir').on('input', function () {
            $(this).val(String($(this).val() || '').toUpperCase());
        });

        $('#apartemen, input[name="status_ajb"]').on('change', function () {
            if (Array.isArray(lastSertipikatRows)) {
                getData();
            }
        });

        $('#tampil_penggabungan').on('change', function () {
            if ($(this).is(':checked')) {
                $('#kartu_surat_tanah').prop('checked', false);
            }
            if (Array.isArray(lastSertipikatRows)) {
                getData();
            }
        });

        $('#kartu_surat_tanah').on('change', function () {
            if ($(this).is(':checked')) {
                $('#tampil_penggabungan').prop('checked', false);
            }
            if (validateFilter()) {
                getData();
            }
        });

        $(document).on('keydown.kstNavigator', function (event) {
            if (!$('#kartu_surat_tanah').is(':checked') || !$('.kst-card').length) return;
            var tagName = String(event.target && event.target.tagName || '').toLowerCase();
            if (['input', 'select', 'textarea', 'button'].indexOf(tagName) !== -1) return;

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                moveKstCard(-1);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                moveKstCard(1);
            }
        });

        $(document).on('mousedown.kstSuggestionClose', function (event) {
            if (!$(event.target).closest('.kst-block-search-wrap').length) {
                hideKstBlockSuggestions();
            }
        });
    });

    $(window).on('pageshow', function (event) {
        if ((event.originalEvent || event).persisted) {
            resetInitialState();
        }
    });

    function setPrintEnabled(enabled) {
        var $btn = $('#sertipikatPrintButton');
        $btn.prop('disabled', !enabled).attr('aria-disabled', enabled ? 'false' : 'true');
    }

    function printSertipikatReport() {
        if (
            $('#sertipikatPrintButton').prop('disabled')
            || !Array.isArray(lastSertipikatRows)
            || !$('#mainDisplay').children().length
        ) {
            return;
        }

        var reportHtml = $('#mainDisplay').html();
        if (!reportHtml) {
            return;
        }

        $('#sertipikatNativePrintFrame').remove();

        var frame = document.createElement('iframe');
        frame.id = 'sertipikatNativePrintFrame';
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
            html, body { width: 100%; margin: 0; padding: 0; background: #fff; color: #000; font-family: "Segoe UI", Tahoma, Arial, sans-serif; }
            *, *::before, *::after { box-sizing: border-box; }
            .report-header { display: grid; grid-template-columns: 1fr 1.45fr 1fr; gap: 12px; align-items: center; margin-bottom: 7px; padding: 10px 12px; border: 1px solid #777; background: #fff; color: #000; }
            .report-company { color: #000; font-size: 10px; font-weight: 700; line-height: 1.35; }
            .report-title { color: #000; text-align: center; font-family: Cambria, Georgia, "Times New Roman", serif; font-size: 16px; font-weight: 700; line-height: 1.2; }
            .report-period { color: #000; text-align: right; font-size: 9px; line-height: 1.4; }
            .report-subtitle { display: flex; min-height: 30px; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 7px; padding: 7px 9px; border: 1px solid #aaa; background: #fff; color: #000; font-size: 9px; }
            .report-subtitle strong, .report-live-badge { color: #000; }
            .report-live-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 8px; font-weight: 700; }
            .report-live-badge::before { content: ""; display: inline-block; width: 5px; height: 5px; border-radius: 50%; background: #000; }
            .report-table-wrapper { width: 100%; max-width: 100%; max-height: none; min-height: 0; overflow: visible; border: 0; background: #fff; }
            .report-table, .report-table.with-gabungan { width: 100%; min-width: 0; max-width: 100%; table-layout: fixed; border-collapse: collapse; border-spacing: 0; border: 1px solid #000; background: #fff; color: #000; font-size: 7.5px; }
            .report-table col { width: auto !important; }
            .report-table thead { display: table-header-group; }
            .report-table tbody { display: table-row-group; }
            .report-table tr { break-inside: avoid; page-break-inside: avoid; }
            .report-table th, .report-table td { position: static; height: auto; padding: 3px; border: 1px solid #000; background: #fff; color: #000; box-shadow: none; vertical-align: middle; overflow: visible; overflow-wrap: anywhere; word-break: break-word; line-height: 1.25; }
            .report-table th { text-align: center; font-weight: 700; }
            .report-table .group-heading, .report-table .sub-heading { background: #fff !important; color: #000 !important; font-weight: 700; }
            .report-table .name-cell, .report-table .multiline-cell, .number-cell, .center-cell { color: #000; }
            .number-cell { text-align: right; }
            .center-cell { text-align: center; }
            .report-signature-footer { width: 100%; min-height: 170px; margin: 16px auto 0; padding: 0 24px 8px; color: #000; font-size: 10px; break-inside: avoid; page-break-inside: avoid; }
            .report-signature-footer-date { width: 50%; margin: 0 0 8px auto; text-align: center; color: #000; font-weight: 600; }
            .report-signature-footer-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 90px; }
            .report-signature-footer-box { text-align: center; }
            .report-signature-footer-role, .report-signature-footer-line { color: #000; }
            .report-signature-footer-space { height: 70px; }
            .report-signature-footer-line { display: inline-flex; width: min(100%, 220px); align-items: flex-end; justify-content: center; }
            .report-signature-footer-line::before { content: "("; margin-right: 3px; }
            .report-signature-footer-line::after { content: ")"; margin-left: 3px; }
            .report-signature-footer-line > span { display: block; width: 100%; height: 10px; border-bottom: 1px dotted #444; }
            .kst-viewer, .kst-list { width: 100%; }
            .kst-navigator { display: none !important; }
            .kst-list { display: block; padding: 0; }
            .kst-card, .kst-card.is-active { position: relative; display: block !important; width: 100%; margin: 0; padding: 18px; border: 1px solid #000; border-radius: 0; background: #fff; color: #000; box-shadow: none; break-after: page; page-break-after: always; }
            .kst-card:last-child { break-after: auto; page-break-after: auto; }
            .kst-card::before { display: none; }
            .kst-card-title { margin: 8px 0 14px; background: none; color: #000; text-align: center; font-size: 22px; font-weight: 700; letter-spacing: .04em; }
            .kst-card-meta { display: grid; grid-template-columns: minmax(0, 1fr) 180px; margin-bottom: 12px; overflow: hidden; border: 1px solid #000; border-radius: 0; background: #fff; box-shadow: none; }
            .kst-info-table, .kst-log-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
            .kst-info-table td, .kst-log-table th, .kst-log-table td { border: 1px solid #000; background: #fff; color: #000; }
            .kst-info-table td { height: 32px; padding: 5px 7px; font-size: 10px; }
            .kst-info-label { width: 160px; font-weight: 700; white-space: nowrap; }
            .kst-info-separator { width: 16px; text-align: center; }
            .kst-block-box { display: flex; flex-direction: column; justify-content: center; border-left: 1px solid #000; padding: 12px; background: #fff; color: #000; }
            .kst-block-label { text-align: center; color: #000; font-size: 10px; font-weight: 700; letter-spacing: .25em; }
            .kst-block-value { margin-top: 14px; text-align: center; font-size: 20px; font-weight: 700; }
            .kst-log-table th { padding: 5px 4px; text-align: center; font-size: 9px; font-weight: 700; }
            .kst-log-table td { height: 27px; padding: 3px 5px; font-size: 9px; }
            .kst-card-caption { display: flex; justify-content: space-between; gap: 12px; margin-top: 8px; color: #000; font-size: 8px; font-weight: 600; }
        `;

        frameDocument.open();
        frameDocument.write(
            '<!DOCTYPE html>'
            + '<html>'
            + '<head>'
            + '<meta charset="utf-8">'
            + '<meta name="viewport" content="width=device-width,initial-scale=1">'
            + '<title>Daftar Sertifikat Pecahan</title>'
            + '<style>' + printCss + '</style>'
            + '</head>'
            + '<body>' + reportHtml + '</body>'
            + '</html>'
        );
        frameDocument.close();

        window.setTimeout(function () {
            try {
                frameWindow.focus();
                frameWindow.print();
            } finally {
                window.setTimeout(function () {
                    $('#sertipikatNativePrintFrame').remove();
                }, 1200);
            }
        }, 180);
    }

    function resetInitialState() {
        $('#blok_awal').val('A');
        $('#blok_akhir').val('ZZ');
        setDefaultDate();
        $('#sektor').val('*');
        $('#sektorEntry').text('Semua Sektor');
        $('input[name="status_ajb"][value="SEMUA"]').prop('checked', true);
        $('#apartemen, #kartu_surat_tanah, #tampil_penggabungan').prop('checked', false);

        lastSertipikatRows = null;
        kstActiveIndex = 0;
        kstTotalCards = 0;
        kstSuggestionIndex = -1;
        
        $('#sertipikatNoDataAlertModal').modal('hide');

        setPrintEnabled(false);
        $('#loading-info').hide();
        $('#mainDisplay').html(
            '<div class="initial-state">'
            + '<i class="fas fa-table initial-state-icon" aria-hidden="true"></i>'
            + '<div>Silahkan Isi filter kemudian klik OK</div>'
            + '</div>'
        );
    }

    function syncSertipikatUnitBadge() {
        var unit = String($('#perusahaan').val() || '').trim().toUpperCase() || 'DTSA';
        $('#sertipikatUnitBadge').text('UNIT ' + unit);
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
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeJs(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r?\n/g, ' ');
    }

    function getSertipikatModalElement() {
        var modal = $('#sertipikatModal');
        if (modal.length && !modal.parent().is('body')) {
            modal.appendTo(document.body);
        }
        return modal;
    }

    function cleanupSertipikatModal() {
        $('.sertipikat-owned-backdrop, .sertipikat-fallback-backdrop').remove();
        if (!$('.modal.show:visible').not('#sertipikatModal').length) {
            $('body').removeClass('modal-open').css('padding-right', '');
        }
    }

    function toggleModal(action) {
        var modal = getSertipikatModalElement();
        if (!modal.length) return;

        if (typeof modal.modal === 'function') {
            modal
                .off('.sertipikatModalFix')
                .on('shown.bs.modal.sertipikatModalFix', function () {
                    $(this).css('z-index', 1065);
                    $('body > .modal-backdrop').not('.sertipikat-owned-backdrop').last().addClass('sertipikat-owned-backdrop');
                    $(this).find('.modal-search').trigger('focus');
                })
                .on('hidden.bs.modal.sertipikatModalFix', function () {
                    cleanupSertipikatModal();
                });

            modal.modal(action);
            return;
        }

        var show = action === 'show';
        if (show) {
            if (!$('.sertipikat-fallback-backdrop').length) {
                $('<div class="modal-backdrop fade show sertipikat-fallback-backdrop"></div>').appendTo(document.body);
            }
            modal.addClass('show').css({ display: 'block', zIndex: 1065 }).attr('aria-hidden', 'false');
            $('body').addClass('modal-open');
            modal.find('.modal-search').trigger('focus');
        } else {
            modal.removeClass('show').css('display', 'none').attr('aria-hidden', 'true');
            cleanupSertipikatModal();
        }
    }

    function addSektor(kode, deskripsi) {
        $('#sektor').val(kode || '*');
        $('#sektorEntry').text(deskripsi || 'Semua Sektor');
        toggleModal('hide');
    }

    function filterModalTable(keyword) {
        var search = String(keyword || '').toLowerCase().trim();
        $('#sertipikatModal .modal-table tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(search) !== -1);
        });
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
            data: { _token: '{{ csrf_token() }}', perusahaan: perusahaan },
            success: function (response) {
                var rows = Array.isArray(response) ? response : (response && Array.isArray(response.data) ? response.data : []);
                var html = '<input type="text" class="modal-search" placeholder="Cari sektor..." onkeyup="filterModalTable(this.value)">';
                html += '<div class="modal-table-wrapper"><table class="modal-table"><thead><tr>';
                html += '<th>Kode Sektor</th><th>Deskripsi</th><th>Perusahaan</th></tr></thead><tbody>';
                html += '<tr onclick="addSektor(\'*\', \'Semua Sektor\')"><td>*</td><td>Semua Sektor</td><td>' + escapeHtml(perusahaan) + '</td></tr>';

                $.each(rows, function (index, item) {
                    var kode = item.KD_SEKTOR || item.kd_sektor || '';
                    var deskripsi = item.DESKRIPSI || item.deskripsi || kode;
                    var unit = item.KD_PERUSAHAAN || item.kd_perusahaan || perusahaan;

                    html += '<tr onclick="addSektor(\'' + escapeJs(kode) + '\', \'' + escapeJs(deskripsi) + '\')">';
                    html += '<td>' + escapeHtml(kode) + '</td><td>' + escapeHtml(deskripsi) + '</td><td>' + escapeHtml(unit) + '</td></tr>';
                });

                if (rows.length < 1) {
                    html += '<tr><td colspan="3" style="padding:24px;text-align:center;">Data sektor tidak ditemukan untuk unit <strong>' + escapeHtml(perusahaan) + '</strong>.</td></tr>';
                }

                html += '</tbody></table></div>';
                $('#sertipikatModalTitle').text('Pilih Sektor/Cluster');
                $('#sertipikatModalContent').html(html);
                toggleModal('show');
            },
            error: function (xhr) {
                var message = 'Gagal mengambil data sektor.';
                if (xhr.responseJSON && xhr.responseJSON.message) message += ' ' + xhr.responseJSON.message;
                alert(message);
            }
        });
    }

    function getFilterData() {
        return {
            _token: '{{ csrf_token() }}',
            blok_awal: $('#blok_awal').val() || 'A',
            blok_akhir: $('#blok_akhir').val() || 'ZZ',
            tgl_awal: $('#tgl_awal').val(),
            tgl_akhir: $('#tgl_akhir').val(),
            perusahaan: $('#perusahaan').val(),
            sektor: $('#sektor').val() || '*',
            status_ajb: $('input[name="status_ajb"]:checked').val() || 'SEMUA',
            apartemen: $('#apartemen').is(':checked') ? 'Y' : 'T',
            tampil_penggabungan: $('#tampil_penggabungan').is(':checked') ? 'Y' : 'T',
            kartu_surat_tanah: $('#kartu_surat_tanah').is(':checked') ? 'Y' : 'T'
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

        return true;
    }

    function getData() {
        if (!validateFilter()) return;
        
        $('#sertipikatNoDataAlertModal').modal('hide');

        setPrintEnabled(false);
        $('#loading-info').show();
        $('#mainDisplay').html('');

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_data',
            dataType: 'json',
            timeout: 120000,
            headers: { 'Accept': 'application/json' },
            data: getFilterData(),
            success: function (response) {
                var rows = Array.isArray(response) ? response : (response && Array.isArray(response.data) ? response.data : []);
                lastSertipikatRows = rows;
                
                // --- PENANGANAN DATA KOSONG ---
                if (rows.length === 0) {
                    var pesanKosong = 'Data tidak ditemukan......!';
                    if ($('#kartu_surat_tanah').is(':checked')) {
                        pesanKosong = 'Data Kartu Surat Tanah tidak ditemukan......!';
                    } else if ($('#apartemen').is(':checked')) {
                        pesanKosong = 'Data Laporan Apartemen tidak ditemukan......!';
                    }
                    
                    $('#sertipikatNoDataMessage').text(pesanKosong);
                    $('#sertipikatNoDataAlertModal').modal('show');
                }
                // ------------------------------
                
                renderReport(rows);
            },
            error: function (xhr, textStatus, errorThrown) {
                setPrintEnabled(false);
                var detail = '';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    detail = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    detail = String(xhr.responseText).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 700);
                } else {
                    detail = String(errorThrown || textStatus || '');
                }

                $('#mainDisplay').html('<div class="alert alert-danger">Gagal mengambil data. ' + escapeHtml(detail) + '</div>');
            },
            complete: function () {
                $('#loading-info').hide();
            }
        });
    }

    function formatDate(value) {
        if (!value) return '-';
        var text = String(value);
        var match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? match[3] + '-' + match[2] + '-' + match[1] : text;
    }

    function formatTanggalIndonesia(dateValue) {
        var date = dateValue instanceof Date ? dateValue : new Date(dateValue || new Date());
        if (isNaN(date.getTime())) date = new Date();

        var bulan = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        return date.getDate() + ' ' + bulan[date.getMonth()] + ' ' + date.getFullYear();
    }

    function formatNumber(value) {
        if (value === null || value === undefined || value === '') return '-';
        var number = Number(value);
        return !isFinite(number) ? '-' : number.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function valueOrDash(value) {
        return value === null || value === undefined || value === '' ? '-' : value;
    }

    function pickValue(item, keys) {
        item = item || {};
        for (var index = 0; index < keys.length; index++) {
            var key = keys[index];
            var value = item[key];
            if (value !== null && value !== undefined && value !== '') return value;
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

    function resolveReportCompany(first) {
        var unit = String($('#perusahaan').val() || '').trim().toUpperCase();
        var rowName = pickValue(first || {}, ['NAMA_PT', 'nama_pt', 'ATAS_NAMA_PT', 'atas_nama_pt', 'NAMA_PERUSAHAAN', 'nama_perusahaan']);
        var sessionName = String($('#nama_perusahaan_session').val() || '').trim();

        return extractCompanyName(rowName) || String(rowName || '').trim() || extractCompanyName(sessionName) || sessionName || unit || '-';
    }

    function pairText(number, date) {
        return escapeHtml(valueOrDash(number)) + '<br>' + escapeHtml(formatDate(date));
    }

    function kstPair(number, date) {
        var numberText = valueOrDash(number);
        var dateText = formatDate(date);
        return escapeHtml(numberText) + (dateText === '-' ? '' : ' &nbsp;&nbsp; Tgl. ' + escapeHtml(dateText));
    }

    function updateKstNavigatorState() {
        var total = Number(kstTotalCards || $('.kst-card').length || 0);
        var current = total > 0 ? (kstActiveIndex + 1) : 0;
        var $activeCard = $('.kst-card').eq(kstActiveIndex);
        var activeBlock = String($activeCard.attr('data-kst-block') || '');

        $('#kstNavCurrent').text(current);
        $('#kstNavTotal').text(total);
        $('#kstBlockSearch').val(activeBlock);
        syncKstSearchClearState();

        $('#kstNavFirst, #kstNavPrev').prop('disabled', total < 2 || kstActiveIndex <= 0);
        $('#kstNavNext, #kstNavLast').prop('disabled', total < 2 || kstActiveIndex >= (total - 1));
    }

    function showKstCard(index, scrollToTop) {
        var $cards = $('.kst-card');
        var total = $cards.length;

        if (total < 1) {
            kstActiveIndex = 0;
            kstTotalCards = 0;
            updateKstNavigatorState();
            return;
        }

        var nextIndex = parseInt(index, 10);
        if (isNaN(nextIndex)) nextIndex = 0;
        nextIndex = Math.max(0, Math.min(nextIndex, total - 1));

        kstActiveIndex = nextIndex;
        kstTotalCards = total;

        $cards.removeClass('is-active').attr('aria-hidden', 'true');
        $cards.eq(nextIndex).addClass('is-active').attr('aria-hidden', 'false');

        updateKstNavigatorState();

        if (scrollToTop !== false) {
            var $navigator = $('.kst-navigator');
            if ($navigator.length) {
                $('html, body').stop(true).animate({ scrollTop: Math.max(0, $navigator.offset().top - 12) }, 140);
            }
        }
    }

    function normalizeKstBlock(value) {
        return String(value || '').trim().toUpperCase();
    }

    function findKstCardIndexByBlock(value, allowPartial) {
        var search = normalizeKstBlock(value);
        if (!search) return -1;

        var exactIndex = -1;
        var partialIndex = -1;

        $('.kst-card').each(function (index) {
            var block = normalizeKstBlock($(this).attr('data-kst-block'));
            if (block === search) {
                exactIndex = index;
                return false;
            }
            if (allowPartial === true && partialIndex < 0 && block.indexOf(search) !== -1) {
                partialIndex = index;
            }
        });

        return exactIndex >= 0 ? exactIndex : partialIndex;
    }

    function searchKstBlock(value, allowPartial, scrollToTop) {
        var index = findKstCardIndexByBlock(value, allowPartial);
        if (index < 0) return false;

        showKstCard(index, scrollToTop !== false);
        return true;
    }

    function getKstBlockSuggestions(keyword) {
        var search = normalizeKstBlock(keyword);
        var results = [];
        var seen = {};

        $('.kst-card').each(function (index) {
            var block = String($(this).attr('data-kst-block') || '').trim();
            var normalized = normalizeKstBlock(block);

            if (!normalized || (search && normalized.indexOf(search) === -1) || seen[normalized]) return;
            seen[normalized] = true;
            results.push({ block: block, index: index });
        });

        return results;
    }

    function renderKstBlockSuggestions(keyword) {
        var suggestions = getKstBlockSuggestions(keyword);
        var html = '';
        kstSuggestionIndex = -1;

        if (suggestions.length < 1) {
            html += '<div class="kst-block-suggestion-empty">Blok tidak ditemukan.</div>';
        } else {
            $.each(suggestions, function (index, item) {
                html += '<button type="button" class="kst-block-suggestion" data-suggestion-index="' + index + '" data-card-index="' + item.index + '" data-block="' + escapeHtml(item.block) + '" onmousedown="selectKstBlockSuggestion(event, ' + item.index + ', \'' + escapeJs(item.block) + '\')">' + escapeHtml(item.block) + '</button>';
            });
        }

        $('#kstBlockSuggestions').html(html).addClass('show').attr('aria-hidden', 'false');
    }

    function showKstBlockSuggestions() {
        renderKstBlockSuggestions($('#kstBlockSearch').val());
    }

    function hideKstBlockSuggestions() {
        $('#kstBlockSuggestions').removeClass('show').attr('aria-hidden', 'true');
        kstSuggestionIndex = -1;
    }

    function selectKstBlockSuggestion(event, cardIndex, block) {
        if (event) event.preventDefault();
        $('#kstBlockSearch').val(block);
        syncKstSearchClearState();
        hideKstBlockSuggestions();
        showKstCard(cardIndex, true);
    }

    function handleKstBlockSearchInput(value) {
        syncKstSearchClearState();
        renderKstBlockSuggestions(value);
        searchKstBlock(value, false, false);
    }

    function handleKstBlockSearchFocus() {
        showKstBlockSuggestions();
    }

    function syncKstSearchClearState() {
        var hasValue = String($('#kstBlockSearch').val() || '').trim() !== '';
        $('.kst-block-search-wrap').toggleClass('has-value', hasValue);
    }

    function clearKstBlockSearch() {
        $('#kstBlockSearch').val('');
        syncKstSearchClearState();
        renderKstBlockSuggestions('');
        $('#kstBlockSearch').trigger('focus');
    }

    function moveKstCard(step) {
        showKstCard(kstActiveIndex + Number(step || 0), true);
    }

    function firstKstCard() {
        showKstCard(0, true);
    }

    function lastKstCard() {
        showKstCard(kstTotalCards - 1, true);
    }

    function renderKartuSuratTanah(rows) {
        var html = '';
        var total = Array.isArray(rows) ? rows.length : 0;

        if (total < 1) {
            $('#mainDisplay').html('<div class="kst-empty">Data Kartu Surat Tanah tidak ditemukan.</div>');
            setPrintEnabled(false);
            return;
        }

        html += '<div class="kst-viewer"><div class="kst-navigator"><div class="kst-navigator-main">';
        html += '<span class="kst-navigator-label">Pilih / Cari Blok</span>';
        html += '<div class="kst-block-search-wrap has-value"><i class="fas fa-search kst-block-search-icon"></i>';
        html += '<input type="text" id="kstBlockSearch" class="kst-block-search" autocomplete="off" placeholder="Ketik blok, contoh: AA/001" onfocus="handleKstBlockSearchFocus()" onclick="handleKstBlockSearchFocus()" oninput="handleKstBlockSearchInput(this.value)">';
        html += '<button type="button" class="kst-block-search-clear" onclick="clearKstBlockSearch()" title="Hapus pencarian"><i class="fas fa-times"></i></button>';
        html += '<div id="kstBlockSuggestions" class="kst-block-suggestions" aria-hidden="true"></div></div></div>';

        html += '<div class="kst-nav-buttons">';
        html += '<button type="button" id="kstNavFirst" class="kst-nav-button" onclick="firstKstCard()">First</button>';
        html += '<button type="button" id="kstNavPrev" class="kst-nav-button" onclick="moveKstCard(-1)"><i class="fas fa-chevron-left"></i></button>';
        html += '<button type="button" id="kstNavNext" class="kst-nav-button" onclick="moveKstCard(1)"><i class="fas fa-chevron-right"></i></button>';
        html += '<button type="button" id="kstNavLast" class="kst-nav-button" onclick="lastKstCard()">Last</button></div>';

        html += '<div class="kst-nav-counter"><strong id="kstNavCurrent">1</strong> / <span id="kstNavTotal">' + total + '</span></div></div>';
        html += '<div class="kst-list">';

        $.each(rows, function (index, item) {
            item = item || {};
            var noSertipikat = pickValue(item, ['NO_SERTIPIKAT', 'NO_SERTIPIKAT_GABUNGAN', 'SER_PISAH_IDK']);
            var tglSertipikat = pickValue(item, ['TGL_SERTIPIKAT', 'TGL_SERTIPIKAT_GABUNGAN', 'TGL_SER_PISAH_IDK']);
            var noSuratUkur = pickValue(item, ['SU_PISAH', 'NO_SU_GABUNGAN', 'SU_PISAH_IDK']);
            var tglSuratUkur = pickValue(item, ['TGL_SU_PISAH', 'TGL_SU_GABUNGAN', 'TGL_SU_PISAH_IDK']);
            var atasNama = pickValue(item, ['ATAS_NAMA_PT', 'NAMA_PT', 'NASABAH_NAMA']);
            var berakhirHak = pickValue(item, ['TGL_BERLAKU']);
            var blokNomor = pickValue(item, ['BLOK_NOMOR']);
            var tanggalInputSer = pickValue(item, ['TGL_INPUT_SER', 'TGL_INPUT']);

            html += '<article class="kst-card' + (index === 0 ? ' is-active' : '') + '" data-kst-index="' + index + '" data-kst-block="' + escapeHtml(blokNomor || '') + '" aria-hidden="' + (index === 0 ? 'false' : 'true') + '">';
            html += '<div class="kst-card-title">KARTU SURAT TANAH</div><div class="kst-card-meta"><table class="kst-info-table"><tbody>';
            html += '<tr><td class="kst-info-label">No. &amp; Tgl. Sertipikat</td><td class="kst-info-separator">:</td><td>' + kstPair(noSertipikat, tglSertipikat) + '</td></tr>';
            html += '<tr><td class="kst-info-label">No. &amp; Tgl. Surat Ukur</td><td class="kst-info-separator">:</td><td>' + kstPair(noSuratUkur, tglSuratUkur) + '</td></tr>';
            html += '<tr><td class="kst-info-label">Atas Nama</td><td class="kst-info-separator">:</td><td>' + escapeHtml(valueOrDash(atasNama)) + '</td></tr>';
            html += '<tr><td class="kst-info-label">Berakhir Hak</td><td class="kst-info-separator">:</td><td>' + escapeHtml(formatDate(berakhirHak)) + '</td></tr>';
            html += '</tbody></table><div class="kst-block-box"><div class="kst-block-label">BLOK</div><div class="kst-block-value">' + escapeHtml(valueOrDash(blokNomor)) + '</div></div></div>';

            html += '<table class="kst-log-table"><thead><tr><th rowspan="2" class="kst-log-no">NO.</th><th rowspan="2" class="kst-log-date">TANGGAL</th><th rowspan="2">URAIAN / KETERANGAN</th><th colspan="2">PENYIMPANAN</th><th colspan="3">PENGAMBILAN</th></tr>';
            html += '<tr><th class="kst-log-paraf">Paraf Land Doc.</th><th class="kst-log-paraf">Paraf Keu.</th><th class="kst-log-paraf">Paraf Land Doc.</th><th class="kst-log-paraf">Paraf Keu.</th><th class="kst-log-paraf">Paraf Legal K.</th></tr></thead><tbody>';

            for (var rowNumber = 1; rowNumber <= 15; rowNumber++) {
                var rowDate = rowNumber === 1 ? formatDate(tanggalInputSer) : '';
                html += '<tr><td class="kst-log-no">' + rowNumber + '.</td><td class="kst-log-date">' + escapeHtml(rowDate === '-' ? '' : rowDate) + '</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
            }

            html += '</tbody></table><div class="kst-card-caption"><span>Kartu ' + (index + 1) + ' dari ' + total + '</span><span>Input Sertipikat: ' + escapeHtml(formatDate(tanggalInputSer)) + '</span></div></article>';
        });

        html += '</div></div>';
        $('#mainDisplay').html(html);

        kstTotalCards = total;
        kstActiveIndex = 0;
        showKstCard(0, false);
        setPrintEnabled(true);
    }

    function renderReport(rows) {
        if ($('#kartu_surat_tanah').is(':checked')) {
            renderKartuSuratTanah(rows);
            return;
        }

        var showGabungan = $('#tampil_penggabungan').is(':checked');
        var totalColumns = showGabungan ? 14 : 13;
        var first = rows.length > 0 ? rows[0] : {};
        var company = resolveReportCompany(first);
        var sector = $('#sektorEntry').text() || 'Semua Sektor';
        var blok = String($('#blok_awal').val() || 'A').toUpperCase() + ' s/d ' + String($('#blok_akhir').val() || 'ZZ').toUpperCase();
        var period = formatDate($('#tgl_awal').val()) + ' s/d ' + formatDate($('#tgl_akhir').val());
        var statusAjb = $('input[name="status_ajb"]:checked').val() || 'SEMUA';
        var isApartemen = $('#apartemen').is(':checked');
        var reportTitle = 'Laporan Daftar Sertifikat Pemisahan';
        var tanggalTandaTangan = formatTanggalIndonesia(new Date());

        if (isApartemen) reportTitle += ' Apartemen';
        if (statusAjb === 'SUDAH') reportTitle += ' (Sudah AJB)';
        else if (statusAjb === 'BELUM') reportTitle += ' (Belum AJB)';

        var html = '<div class="report-header"><div class="report-company">' + escapeHtml(company) + '</div>';
        html += '<div class="report-title">' + escapeHtml(reportTitle) + '</div>';
        html += '<div class="report-period">BLOK: ' + escapeHtml(blok) + '<br>Tgl. Input Sert/Gabung: ' + escapeHtml(period) + '</div></div>';
        html += '<div class="report-subtitle">Sektor/Cluster: <strong>' + escapeHtml(sector) + '</strong><span class="report-live-badge">Live data</span></div>';

        html += '<div class="report-table-wrapper"><table class="report-table' + (showGabungan ? ' with-gabungan' : '') + '"><colgroup>';
        html += '<col style="width:48px"><col style="width:90px"><col style="width:220px"><col style="width:160px"><col style="width:155px"><col style="width:82px"><col style="width:170px"><col style="width:165px"><col style="width:155px"><col style="width:82px">';

        if (showGabungan) {
            html += '<col style="width:175px"><col style="width:155px"><col style="width:82px">';
        } else {
            html += '<col style="width:90px"><col style="width:90px">';
        }

        html += '<col style="width:110px"></colgroup><thead><tr>';
        html += '<th rowspan="2">No.</th><th rowspan="2">BLOK/<br>NOMOR</th><th rowspan="2">Nama Pemilik</th><th rowspan="2">Sertipikat Induk<br>Nomor dan Tanggal</th>';
        html += '<th colspan="2" class="group-heading">Surat Ukur Induk</th><th rowspan="2">Permohonan Pemecahan<br>Nomor dan Tanggal</th><th rowspan="2">Sertipikat Pemisahan<br>Nomor dan Tanggal</th>';
        html += '<th colspan="2" class="group-heading">Surat Ukur Pemisahan</th>';

        if (showGabungan) {
            html += '<th rowspan="2">Sertipikat Penggabungan<br>Nomor dan Tanggal</th><th colspan="2" class="group-heading">Surat Ukur Penggabungan</th>';
        } else {
            html += '<th rowspan="2">Luas PPJB<br>(M2)</th><th rowspan="2">Selisih Luas<br>(M2)</th>';
        }

        html += '<th rowspan="2">Masa Berlaku</th></tr><tr>';
        html += '<th class="sub-heading">Nomor dan Tanggal</th><th class="sub-heading">Luas<br>(M2)</th><th class="sub-heading">Nomor dan Tanggal</th><th class="sub-heading">Luas<br>(M2)</th>';

        if (showGabungan) {
            html += '<th class="sub-heading">Nomor dan Tanggal</th><th class="sub-heading">Luas<br>(M2)</th>';
        }

        html += '</tr></thead><tbody>';

        if (rows.length < 1) {
            html += '<tr><td colspan="' + totalColumns + '" style="height:130px;text-align:center;color:#64748b;">Data tidak ditemukan.</td></tr>';
        } else {
            $.each(rows, function (index, item) {
                item = item || {};
                var noSertipikatPemisahan = pickValue(item, ['NO_SERTIPIKAT_PEMISAHAN', 'SER_PISAH_IDK']);
                var tglSertipikatPemisahan = pickValue(item, ['TGL_SERTIPIKAT_PEMISAHAN', 'TGL_SER_PISAH_IDK']);
                var noSuPemisahan = pickValue(item, ['NO_SU_PEMISAHAN', 'SU_PISAH_IDK']);
                var tglSuPemisahan = pickValue(item, ['TGL_SU_PEMISAHAN', 'TGL_SU_PISAH_IDK']);
                var luasPemisahan = pickValue(item, ['LUAS_SU_PEMISAHAN', 'LUAS_SU_PISAH_IDK']);

                var noSertipikatGabungan = pickValue(item, ['NO_SERTIPIKAT_GABUNGAN', 'NO_SERTIPIKAT']);
                var tglSertipikatGabungan = pickValue(item, ['TGL_SERTIPIKAT_GABUNGAN', 'TGL_SERTIPIKAT']);
                var noSuGabungan = pickValue(item, ['NO_SU_GABUNGAN', 'SU_PISAH']);
                var tglSuGabungan = pickValue(item, ['TGL_SU_GABUNGAN', 'TGL_SU_PISAH']);
                var luasGabungan = pickValue(item, ['LUAS_SU_GABUNGAN', 'LUAS_SUP']);

                html += '<tr><td class="center-cell">' + (index + 1) + '</td>';
                html += '<td class="center-cell">' + escapeHtml(valueOrDash(pickValue(item, ['BLOK_NOMOR']))) + '</td>';
                html += '<td class="name-cell">' + escapeHtml(valueOrDash(pickValue(item, ['NASABAH_NAMA']))) + '</td>';
                html += '<td class="multiline-cell">' + pairText(pickValue(item, ['SERTIPIKAT_IDK']), pickValue(item, ['TGL_SER_IDK'])) + '</td>';
                html += '<td class="multiline-cell">' + pairText(pickValue(item, ['SU_INDUK']), pickValue(item, ['TGL_SU_INDUK'])) + '</td>';
                html += '<td class="number-cell">' + formatNumber(pickValue(item, ['LUAS_SU_INDUK'])) + '</td>';
                html += '<td class="multiline-cell">' + pairText(pickValue(item, ['MOHON_PISAH']), pickValue(item, ['TGL_MOHON_PISAH'])) + '</td>';
                html += '<td class="multiline-cell">' + pairText(noSertipikatPemisahan, tglSertipikatPemisahan) + '</td>';
                html += '<td class="multiline-cell">' + pairText(noSuPemisahan, tglSuPemisahan) + '</td>';
                html += '<td class="number-cell">' + formatNumber(luasPemisahan) + '</td>';

                if (showGabungan) {
                    html += '<td class="multiline-cell">' + pairText(noSertipikatGabungan, tglSertipikatGabungan) + '</td>';
                    html += '<td class="multiline-cell">' + pairText(noSuGabungan, tglSuGabungan) + '</td>';
                    html += '<td class="number-cell">' + formatNumber(luasGabungan) + '</td>';
                } else {
                    html += '<td class="number-cell">' + formatNumber(pickValue(item, ['LUAS_PPJB'])) + '</td>';
                    html += '<td class="number-cell">' + formatNumber(pickValue(item, ['SELISIH_LUAS_PEMISAHAN', 'SELISIH_LUAS'])) + '</td>';
                }

                html += '<td class="center-cell">' + escapeHtml(formatDate(pickValue(item, ['TGL_BERLAKU']))) + '</td></tr>';
            });
        }

        html += '</tbody></table></div>';

        html += '<div class="report-signature-footer"><div class="report-signature-footer-date">Jakarta, ' + escapeHtml(tanggalTandaTangan) + '</div>';
        html += '<div class="report-signature-footer-grid"><div class="report-signature-footer-box"><div class="report-signature-footer-role">Yang menyerahkan,</div><div class="report-signature-footer-space"></div><div class="report-signature-footer-line"><span></span></div></div>';
        html += '<div class="report-signature-footer-box"><div class="report-signature-footer-role">Yang menerima,</div><div class="report-signature-footer-space"></div><div class="report-signature-footer-line"><span></span></div></div></div></div>';

        $('#mainDisplay').html(html);
        setPrintEnabled(rows.length > 0);
    }
</script>
@endsection