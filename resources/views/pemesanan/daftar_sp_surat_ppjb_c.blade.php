@extends('layouts.template')

@section('content')

<style>
    :root {
        --sp-primary: #2563eb;
        --sp-primary-soft: #dbeafe;
        --sp-success: #16a34a;
        --sp-success-soft: #dcfce7;
        --sp-warning-soft: #fef3c7;
        --sp-border: #e5e7eb;
        --sp-border-strong: #d1d5db;
        --sp-text: #0f172a;
        --sp-text-soft: #475569;
        --sp-muted: #64748b;
        --sp-bg: #f8fafc;
        --sp-white: #ffffff;
        --sp-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        --sp-shadow-sm: 0 4px 14px rgba(15, 23, 42, 0.06);
        --sp-radius: 18px;
        --sp-radius-sm: 12px;
    }

    .filter-panel,
    .result-panel {
        border: 1px solid var(--sp-border);
        border-radius: var(--sp-radius);
        box-shadow: var(--sp-shadow-sm);
        background: var(--sp-white);
    }

    .filter-panel,
    .result-panel {
        padding: 20px;
        margin-bottom: 16px;
    }

    .sris-page-header {
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

    .sris-page-header::before {
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

    .sris-page-header::after {
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

    .sris-page-header-title {
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

    .sris-page-header-unit {
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
        white-space: nowrap;
    }

    .filter-label {
        font-size: 13px;
        font-weight: 700;
        width: 120px;
        color: var(--sp-text-soft);
        letter-spacing: 0.01em;
    }

    .lookup-row {
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .lookup-control {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 42px;
        flex-grow: 1;
        gap: 8px;
        width: 100%;
    }

    .lookup-display,
    .filter-panel .form-control,
    .filter-panel select,
    .filter-panel .input-group-text {
        min-height: 44px;
        border-radius: 12px !important;
        border: 1px solid var(--sp-border-strong) !important;
        box-shadow: none !important;
        transition: all 0.2s ease;
        font-size: 14px;
    }

    .filter-panel .form-control,
    .filter-panel select {
        background: #fbfdff;
        color: var(--sp-text);
        padding-left: 14px;
        padding-right: 14px;
    }

    .filter-panel .form-control:focus,
    .filter-panel select:focus,
    .lookup-display:focus {
        border-color: var(--sp-primary) !important;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12) !important;
        background: #fff;
    }

    .lookup-display {
        flex-grow: 1;
        background-color: #fbfdff;
        display: flex;
        align-items: center;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        color: var(--sp-text);
        padding: 0 14px;
        cursor: pointer;
    }

    .lookup-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        min-width: 42px;
        height: 42px;
        min-height: 42px;
        padding: 0 !important;
        color: #1d4ed8 !important;
        border: 1px solid #bfdbfe !important;
        border-radius: 13px !important;
        background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%) !important;
        box-shadow: none !important;
        font-size: 16px;
        transition:
            transform 0.18s ease,
            box-shadow 0.18s ease,
            background 0.18s ease;
    }

    .lookup-button:hover {
        color: #1d4ed8 !important;
        border-color: #bfdbfe !important;
        background: linear-gradient(145deg, #dbeafe 0%, #bfdbfe 100%) !important;
        transform: translateY(-1px);
        box-shadow: 0 7px 15px rgba(37, 99, 235, 0.12) !important;
    }

    .lookup-button:focus {
        position: relative;
        z-index: 1;
        color: #1d4ed8 !important;
        border-color: #93c5fd !important;
        outline: 0;
        background: linear-gradient(145deg, #dbeafe 0%, #bfdbfe 100%) !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13) !important;
    }

    .lookup-button:active {
        transform: translateY(0);
    }

    .action-buttons {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 9px;
        justify-content: flex-end;
        margin-top: 14px;
        flex-wrap: wrap;
    }

    .action-btn {
        width: 104px;
        min-width: 104px;
        height: 36px;
        min-height: 36px;
        padding: 6px 14px;
        border: 1px solid transparent !important;
        border-radius: 8px !important;
        font-weight: 600;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        line-height: 1;
        cursor: pointer;
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease,
            background-color 0.15s ease;
    }

    .action-btn svg {
        width: 17px;
        height: 17px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 1.8;
    }

    .action-btn-view {
        color: #fff !important;
        background: var(--sp-primary) !important;
        box-shadow: 0 5px 12px rgba(37, 99, 235, 0.22);
    }

    .action-btn-excel {
        color: var(--sp-success) !important;
        border-color: #bbdec8 !important;
        background: #f2fbf5 !important;
        box-shadow: none;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        filter: none;
    }

    .action-btn-view:hover,
    .action-btn-view:focus {
        color: #fff !important;
        background: #1d4ed8 !important;
    }

    .action-btn-excel:hover,
    .action-btn-excel:focus {
        color: var(--sp-success) !important;
        border-color: #86c99d !important;
        background: #e7f7ec !important;
    }

    .action-btn:active {
        transform: translateY(0);
    }

    .action-buttons .action-btn:disabled,
    .action-buttons .action-btn[disabled] {
        color: #94a3b8 !important;
        border-color: #e2e8f0 !important;
        background: #f1f5f9 !important;
        box-shadow: none !important;
        cursor: not-allowed;
        transform: none !important;
    }

    .modal-content {
        border: 0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.20);
    }

    .modal-header {
        padding: 18px 22px;
        background: linear-gradient(135deg, #f8fafc, #eff6ff);
        border-bottom: 1px solid var(--sp-border);
    }

    .modal-header h5 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: var(--sp-text);
    }

    .modal-header .btn {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        border: 1px solid var(--sp-border);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--sp-text-soft);
        background: #fff;
    }

    .modal-body {
        padding: 20px 22px 22px;
        background: #fff;
    }

    .modal-search-wrapper {
        margin-bottom: 14px;
    }

    .modal-search-wrapper .form-control {
        min-height: 44px;
        border-radius: 12px;
        border: 1px solid var(--sp-border-strong);
        padding: 0 14px;
        box-shadow: none;
    }

    .modal-search-wrapper .form-control:focus {
        border-color: var(--sp-primary);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .modal-table {
        width: 100%;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }

    .modal-table th {
        text-align: center;
        vertical-align: middle;
        background-color: #eff6ff;
        color: var(--sp-text);
        font-size: 13px;
        font-weight: 700;
        padding: 10px 12px;
        position: sticky;
        top: 0;
        z-index: 2;
        border-bottom: 1px solid var(--sp-border);
    }

    .modal-table td {
        text-align: center;
        vertical-align: middle;
        cursor: pointer;
        font-size: 13px;
        color: var(--sp-text-soft);
        padding: 10px 12px;
        border-bottom: 1px solid #eef2f7;
        background: #fff;
    }

    .modal-table tr:hover td {
        background-color: #f8fbff;
        color: var(--sp-text);
    }

    .modal-table-wrapper {
        max-height: 430px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid var(--sp-border);
        border-radius: 14px;
    }

    .result-wrapper {
        overflow-x: hidden;
        max-width: 100%;
    }

    #loading-info {
        display: none;
        margin-bottom: 14px;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 600;
    }

    .empty-state-panel {
        min-height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        border: 1px dashed var(--sp-border-strong);
        border-radius: 16px;
        color: var(--sp-muted);
        background: linear-gradient(180deg, #fcfdff 0%, #f8fafc 100%);
        text-align: center;
        padding: 24px;
    }

    .empty-state-panel i {
        width: 52px;
        height: 52px;
        margin-bottom: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: var(--sp-primary-soft);
        color: var(--sp-primary);
        font-size: 20px;
    }

    .report-wrapper {
        background: #fff;
        padding: 24px;
        min-height: 420px;
        font-family: Arial, sans-serif;
        border: 1px solid var(--sp-border);
        border-radius: 18px;
        box-shadow: var(--sp-shadow-sm);
    }

    .report-title {
        text-align: center;
        font-weight: 700;
        font-size: 18px;
        margin-bottom: 10px;
        color: var(--sp-text);
        letter-spacing: 0.02em;
    }

    .report-subtitle {
        text-align: center;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 12px;
        color: var(--sp-text-soft);
    }

    .report-table-container {
        width: 100%;
        max-width: 100%;
        max-height: calc(100vh - 220px);
        min-height: 260px;
        overflow: auto;
        margin-top: 12px;
        border: 1px solid var(--sp-border);
        border-radius: 16px;
        background: #fff;
    }

    .report-table {
        width: max-content;
        min-width: 1500px;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 11px;
        background: white;
        margin-bottom: 0;
    }

    .report-table th {
        border-bottom: 1px solid #cbd5e1 !important;
        border-right: 1px solid #e2e8f0 !important;
        text-align: center;
        vertical-align: middle;
        background: #eff6ff !important;
        color: #0f172a !important;
        font-weight: 700;
        padding: 8px 10px;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .report-table td {
        border-bottom: 1px solid #e2e8f0 !important;
        border-right: 1px solid #f1f5f9 !important;
        text-align: center;
        vertical-align: middle;
        padding: 7px 10px;
        white-space: nowrap;
        color: #334155;
        background: #fff;
    }

    .report-table tbody tr:nth-child(even) td {
        background: #fcfdff;
    }

    .report-table tbody tr:hover td {
        background: #f8fbff;
    }

    .report-total-row td {
        background: #f8fafc !important;
        font-weight: 700;
        color: #0f172a;
    }

    .report-total-row td.total-value {
        border-top: 2px solid #334155 !important;
        background: #eff6ff !important;
    }

    .empty-row {
        height: 90px;
        color: #64748b;
        font-style: italic;
        text-align: center;
        background: #fcfdff;
    }

    @media (max-width: 991.98px) {
        .lookup-row {
            flex-direction: column;
            align-items: stretch;
            gap: 6px;
        }

        .filter-label {
            width: 100%;
        }

        .action-buttons {
            justify-content: stretch;
        }

        .action-btn {
            flex: 1 1 auto;
        }
    }

    .filter-panel,
    .result-panel,
    #spSudahPpjbModal {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        color: #263449;
    }

    .sris-page-header-title,
    .filter-label,
    .modal-header h5,
    .action-btn {
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
    }

    .filter-panel .form-control,
    .filter-panel select,
    .filter-panel input,
    .filter-panel button,
    .filter-panel label,
    .lookup-display,
    .modal-body,
    .modal-search-wrapper .form-control,
    .modal-table td {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    }

    .modal-table th {
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-weight: 600;
    }

    .report-wrapper {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        color: #263449;
    }

    .report-title {
        font-family: Cambria, Georgia, "Times New Roman", serif;
        font-weight: 700;
        letter-spacing: 0.45px;
    }

    .report-subtitle,
    .report-table td {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-variant-numeric: tabular-nums;
    }

    .report-table th {
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-weight: 600;
    }

    .filter-panel .unit-readonly-control {
        color: #667085 !important;
        border-color: #d8dee8 !important;
        background: #f3f6fa !important;
        cursor: not-allowed !important;
        pointer-events: none;
        user-select: none;
        -webkit-user-select: none;
    }

    .report-wrapper {
        position: relative;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 20px !important;
        overflow: hidden;
        border: 1px solid #dbe3ef !important;
        border-radius: 26px !important;
        background: #ffffff !important;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07) !important;
        color: #172033 !important;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
    }

    .report-wrapper::before {
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
        line-height: 1.45;
    }

    .report-title {
        margin: 0 !important;
        color: #172033 !important;
        text-align: center !important;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
        font-size: 18px !important;
        font-weight: 950 !important;
        letter-spacing: -0.02em !important;
        line-height: 1.25;
    }

    .report-period {
        color: #475467;
        text-align: right;
        font-size: 10.5px;
        font-weight: 650;
        line-height: 1.55;
    }

    .report-subtitle {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        min-height: 36px;
        align-items: center;
        gap: 12px;
        margin: 0 0 10px !important;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: linear-gradient(90deg, #ffffff, #f8fafc);
        color: #667085 !important;
        text-align: left !important;
        font-size: 10.5px !important;
        font-weight: 500 !important;
    }

    .report-subtitle-label {
        justify-self: start;
        color: #667085;
        white-space: nowrap;
    }

    .report-subtitle-value {
        justify-self: center;
        color: #344054;
        font-weight: 850;
        text-align: center;
    }

    .report-subtitle strong {
        color: #344054;
        font-weight: 850;
    }

    .report-live-badge {
        display: inline-flex;
        flex: 0 0 auto;
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

    .report-live-badge::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
    }

    .report-table-container {
        width: 100%;
        max-width: 100%;
        max-height: calc(100vh - 285px) !important;
        min-height: 320px !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: auto !important;
        border: 1px solid #dbe3ef !important;
        border-radius: 18px !important;
        background: #ffffff !important;
        box-shadow: 0 5px 16px rgba(15, 23, 42, 0.04) !important;
        scrollbar-width: thin;
        scrollbar-color: #93c5fd #eff3f7;
    }

    .report-table-container::-webkit-scrollbar {
        width: 11px;
        height: 13px;
    }

    .report-table-container::-webkit-scrollbar-track {
        border-radius: 999px;
        background: #eff3f7;
    }

    .report-table-container::-webkit-scrollbar-thumb {
        border: 2px solid #eff3f7;
        border-radius: 999px;
        background: #93c5fd;
    }

    .report-table-container::-webkit-scrollbar-thumb:hover {
        background: #60a5fa;
    }

    .report-table {
        margin-bottom: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        background: #ffffff !important;
        color: #344054 !important;
        font-size: 10.5px !important;
    }

    .report-table th {
        position: sticky;
        top: 0;
        z-index: 4;
        height: 48px;
        padding: 8px 9px !important;
        color: #344054 !important;
        border-top: 0 !important;
        border-left: 0 !important;
        border-right: 1px solid #e2e8f0 !important;
        border-bottom: 1px solid #c8d3e1 !important;
        background: linear-gradient(180deg, #eff6ff 0%, #e5effb 100%) !important;
        box-shadow: inset 0 -1px 0 rgba(37, 99, 235, 0.05);
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
        font-size: 10.5px !important;
        font-weight: 900 !important;
        line-height: 1.25 !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap !important;
    }

    .report-table td {
        height: 46px;
        padding: 8px 9px !important;
        color: #344054 !important;
        border-top: 0 !important;
        border-left: 0 !important;
        border-right: 1px solid #e2e8f0 !important;
        border-bottom: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
        font-size: 10.5px !important;
        line-height: 1.38 !important;
        vertical-align: middle !important;
        white-space: nowrap !important;
        font-variant-numeric: tabular-nums;
    }

    .report-table tbody tr:nth-child(even) td {
        background: #fbfcfe !important;
    }

    .report-table tbody tr:hover td {
        background: #f0f7ff !important;
    }

    .report-table tbody tr:hover td:first-child {
        color: #1d4ed8 !important;
        box-shadow: inset 4px 0 0 #2563eb !important;
    }

    .report-table .empty-row {
        height: 130px !important;
        color: #64748b !important;
        background: #ffffff !important;
        font-style: normal !important;
        text-align: center !important;
    }

    @media (max-width: 767.98px) {
        .report-header {
            grid-template-columns: minmax(0, 1fr);
            gap: 8px;
            text-align: center;
        }

        .report-period {
            text-align: center;
        }

        .report-subtitle {
            grid-template-columns: minmax(0, 1fr);
            justify-items: center;
            text-align: center !important;
        }

        .report-subtitle-label,
        .report-live-badge {
            justify-self: center;
        }
    }

    .report-table tbody tr.report-total-row td {
        color: #0f172a !important;
        background: #eff6ff !important;
        font-weight: 900 !important;
    }

    .report-table tbody tr.report-total-row td.total-value {
        border-top: 2px solid #334155 !important;
    }

    #spSudahPpjbNoDataAlertModal .modal-dialog {
        max-width: 380px;
    }

    #spSudahPpjbNoDataAlertModal .modal-content {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
    }

    #spSudahPpjbNoDataAlertModal .alert-icon-wrapper {
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

    #spSudahPpjbNoDataAlertModal .alert-title {
        margin-bottom: 8px;
        color: #172033;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 18px;
        font-weight: 700;
    }

    #spSudahPpjbNoDataAlertModal .alert-message {
        margin-bottom: 24px;
        color: #475569;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 14px;
    }

    #spSudahPpjbNoDataAlertModal .alert-btn-ok {
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

    #spSudahPpjbNoDataAlertModal .alert-btn-ok:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
    }

</style>

<div class="modal fade" id="spSudahPpjbNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message">Data tidak ada......!</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    onclick="$('#spSudahPpjbNoDataAlertModal').modal('hide')"
                >OK</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="spSudahPpjbModal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header d-flex" style="justify-content:space-between">
                <h5 id="spSudahPpjbModalTitle"></h5>
                <button class="btn btn-light" type="button" onclick="toggle_modal()">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="modal-body" id="spSudahPpjbModalContent"></div>

        </div>
    </div>
</div>

<div class="sris-page-header">
    <span class="sris-page-header-title">Daftar SP Sudah PPJB</span>
    <code class="sris-page-header-unit">UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}</code>
</div>

<div class="filter-panel">
    <input
        type="hidden"
        id="nama_perusahaan_session"
        value="{{ $namaPerusahaan ?? $nama_perusahaan ?? $namaPt ?? $nama_pt ?? session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? session('nama_lokasi') ?? session('deskripsi_lokasi') ?? session('lokasi') ?? '' }}"
    >

    <div class="row">

        <div class="col-md-4">
            <div class="lookup-row">
                <div class="filter-label">Tanggal</div>
                <input type="date" id="tgl_awal" class="form-control">
            </div>

            <div class="lookup-row">
                <div class="filter-label"></div>
                <input type="date" id="tgl_akhir" class="form-control">
            </div>

            <div class="lookup-row">
                <div class="filter-label">Sektor</div>
                <div class="lookup-control">
                    <input type="hidden" id="sektor" value="*">
                    <div
                        class="form-control lookup-display"
                        id="sektorentry"
                        role="button"
                        tabindex="0"
                        onclick="get_sektor_modal()"
                        onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); get_sektor_modal(); }"
                    >Semua Sektor</div>
                    <button
                        class="btn lookup-button"
                        type="button"
                        onclick="get_sektor_modal()"
                        title="Cari Sektor"
                        aria-label="Cari Sektor"
                    >
                        <i class="fas fa-binoculars" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="lookup-row">
                <div class="filter-label">Jenis</div>
                <select id="jenis" class="form-control">
                    <option value="*">Semua</option>
                    @foreach (($jenisList ?? []) as $jenis)
                        <option value="{{ $jenis->FLAG_LAPORAN }}">
                            {{ $jenis->DESKRIPSI }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lookup-row">
                <div class="filter-label">Lokasi</div>
                <select id="lokasi" class="form-control">
                    <option value="*">Semua Lokasi</option>
                    @foreach (($lokasiList ?? []) as $lokasi)
                        <option value="{{ $lokasi->KD_LOKASI }}">
                            {{ $lokasi->KD_LOKASI }} - {{ $lokasi->DESKRIPSI }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lookup-row">
                <div class="filter-label">Pembayaran</div>
                <div class="input-group">
                    <input type="number" id="persen" class="form-control" value="100.00" step="0.01">
                    <div class="input-group-append">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="lookup-row">
                <div class="filter-label">Unit</div>
                <input
                    type="text"
                    id="perusahaan"
                    class="form-control unit-readonly-control"
                    value="{{ session('kd_unit') ?? 'DTSA' }}"
                    readonly
                    aria-readonly="true"
                    tabindex="-1"
                >
            </div>

            <div class="action-buttons">
                <button type="button" class="btn action-btn action-btn-view" onclick="getSummary()">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
                        <circle cx="12" cy="12" r="2.5"></circle>
                    </svg>
                    <span>View</span>
                </button>

                <button
                        type="button"
                        class="btn action-btn action-btn-excel"
                        id="spSudahPpjbPrintButton"
                        onclick="printSpSudahPpjbReport()"
                        aria-disabled="true"
                        disabled
                    >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6 9V3h12v6"></path>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <path d="M6 14h12v7H6z"></path>
                    </svg>
                    <span>Print</span>
                </button>
            </div>
        </div>

    </div>
</div>

<div class="result-panel">
    <div id="loading-info">
        <i class="fas fa-spinner fa-spin"></i> Mengambil data...
    </div>

    <div id="main-display" class="result-wrapper">
        <div class="empty-state-panel">
            <i class="fas fa-table"></i>
            <div>Silakan pilih filter lalu klik <strong>View</strong>.</div>
        </div>
    </div>
</div>

@endsection

@section('js')

<script>
    if (window.history && 'scrollRestoration' in window.history) {
        window.history.scrollRestoration = 'manual';
    }

    var activeSummaryRequest = null;
    var summaryRequestId = 0;

    $(document).ready(function () {
        initializePageState();
    });

    window.addEventListener('pageshow', function () {
        initializePageState();
    });

    function initializePageState() {
        setDefaultDate();
        resetSectorState();
        clearReportState();
        cancelActiveSummaryRequest();
    }

    function resetSectorState() {
        $('#sektor').val('*');
        $('#sektorentry').text('Semua Sektor');
    }

    function clearReportState() {
        $('#spSudahPpjbNoDataAlertModal').modal('hide');
        $('#loading-info').hide();
        $('#main-display').html(
            '<div class="empty-state-panel">' +
                '<i class="fas fa-table"></i>' +
                '<div>Silakan pilih filter lalu klik <strong>View</strong>.</div>' +
            '</div>'
        );
    syncSpSudahPpjbPrintState();
    }

    function cancelActiveSummaryRequest() {
        if (activeSummaryRequest && activeSummaryRequest.readyState !== 4) {
            activeSummaryRequest.abort();
        }

        activeSummaryRequest = null;
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

    function getFilterData() {
        return {
            _token: '{{ csrf_token() }}',
            tgl_awal: $('#tgl_awal').val(),
            tgl_akhir: $('#tgl_akhir').val(),
            jenis: $('#jenis').val() || '*',
            lokasi: $('#lokasi').val() || '*',
            sektor: $('#sektor').val() || '*',
            perusahaan: $('#perusahaan').val(),
            persen: $('#persen').val() || 100
        };
    }

    function toggle_modal() {
        $('#spSudahPpjbModal').modal('toggle');
    }

    function insert_modal(title, content) {
        var finalContent = `
            <div class="modal-search-wrapper">
                <input
                    type="text"
                    id="modalSearchInput"
                    class="form-control"
                    placeholder="Cari data..."
                    onkeyup="filter_modal_table(this.value)"
                    autocomplete="off"
                >
            </div>

            <div class="modal-table-wrapper">
                ${content}
            </div>
        `;

        $('#spSudahPpjbModalContent').html(finalContent);
        $('#spSudahPpjbModalTitle').html(title);

        setTimeout(function() {
            $('#modalSearchInput').focus();
        }, 300);
    }

    function filter_modal_table(keyword) {
        keyword = String(keyword || '').toLowerCase();

        $('#spSudahPpjbModal .modal-table tr').each(function(index) {
            if (index === 0) {
                $(this).show();
                return;
            }

            var rowText = $(this).text().toLowerCase();

            if (rowText.indexOf(keyword) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
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

    function escapeJs(value) {
        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r?\n/g, ' ');
    }

    function add_sektor(value, descriptor) {
        $('#sektor').val(value);
        $('#sektorentry').text(descriptor || 'Semua Sektor');
        $('#spSudahPpjbModal').modal('toggle');

        clearReportState();
    }

    function get_sektor_modal() {
        var perusahaan = $('#perusahaan').val();

        if (!perusahaan) {
            alert('Unit/perusahaan belum terisi.');
            return;
        }

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_sektor',
            data: {
                _token: '{{ csrf_token() }}',
                perusahaan: perusahaan
            },
            success: function (data) {
                var html = '';

                html += '<table class="modal-table table table-bordered">';
                html += '<tr>';
                html += '<th>Kode Sektor</th>';
                html += '<th>Deskripsi</th>';
                html += '<th>Perusahaan</th>';
                html += '</tr>';

                html += '<tr onclick="add_sektor(\'*\', \'Semua Sektor\')">';
                html += '<td>*</td>';
                html += '<td>Semua Sektor</td>';
                html += '<td>' + escapeHtml(perusahaan) + '</td>';
                html += '</tr>';

                if (data && data.length > 0) {
                    $.each(data, function (index, item) {
                        var kode = item.KD_SEKTOR == null ? '' : item.KD_SEKTOR;
                        var deskripsi = item.DESKRIPSI == null ? '' : item.DESKRIPSI;
                        var kdPerusahaan = item.KD_PERUSAHAAN == null ? '' : item.KD_PERUSAHAAN;

                        html += '<tr onclick="add_sektor(\'' + escapeJs(kode) + '\', \'' + escapeJs(deskripsi) + '\')">';
                        html += '<td>' + escapeHtml(kode) + '</td>';
                        html += '<td>' + escapeHtml(deskripsi) + '</td>';
                        html += '<td>' + escapeHtml(kdPerusahaan) + '</td>';
                        html += '</tr>';
                    });
                }

                html += '</table>';

                insert_modal('Pilih Sektor', html);
                toggle_modal();
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                alert('Gagal mengambil data sektor.');
            }
        });
    }

    function extractCompanyName(value) {
        var raw = String(value || '').replace(/\u00a0/g, ' ');
        var locationMatch = raw.match(/Lokasi\s*:[^\r\n|]*?-\s*([^\r\n|]+)/i);
        var companyMatch = raw.match(/\b((?:PT\.?|CV\.?|PD\.?|PERUM)\s+[^\r\n|]+)/i);
        var name = locationMatch ? locationMatch[1] : (companyMatch ? companyMatch[1] : '');

        return String(name || '')
            .replace(/\s+(?:Hi\s*,|Logout\b|Keluar\b|UNIT\s+[A-Z0-9_-]+\b).*$/i, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function pickValue(item, keys) {
        item = item || {};

        for (var index = 0; index < keys.length; index++) {
            var value = item[keys[index]];

            if (value !== null && value !== undefined && value !== '') {
                return value;
            }
        }

        return null;
    }

    function isLongCompanyName(value, unit) {
        var name = String(value === null || value === undefined ? '' : value).trim();

        if (name === '') {
            return false;
        }

        if (unit && name.toUpperCase() === String(unit).toUpperCase()) {
            return false;
        }

        return /\s/.test(name) || name.length > 8;
    }

    function escapeRegExp(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function collectUnitTextCandidates() {
        var candidates = [];

        function push(value) {
            var text = String(value === null || value === undefined ? '' : value).trim();

            if (text !== '') {
                candidates.push(text);
            }
        }

        $('input, textarea').not('#main-display, .filter-panel, .sris-page-header, #spSudahPpjbModal, script, style, noscript').each(function () {
            push($(this).val());
        });

        $('[title]').not('#main-display, .filter-panel, .sris-page-header, #spSudahPpjbModal, script, style, noscript').each(function () {
            push($(this).attr('title'));
        });

        var headerSelectors = [
            '.main-header',
            '.navbar',
            '.topbar',
            '.app-header',
            '.content-header',
            '#header',
            'header'
        ];

        for (var index = 0; index < headerSelectors.length; index++) {
            $(headerSelectors[index]).each(function () {
                var $clone = $(this).clone();
                $clone.find('#main-display, .filter-panel, .sris-page-header, #spSudahPpjbModal, script, style, noscript').remove();
                push($clone.text());
            });
        }

        return candidates;
    }

    function scrapeCompanyNameForUnit(unit) {
        unit = String(unit || '').trim();

        if (unit === '') {
            return '';
        }

        var unitPattern = new RegExp('\\b' + escapeRegExp(unit) + '\\b', 'i');
        var candidates = collectUnitTextCandidates();

        for (var index = 0; index < candidates.length; index++) {
            if (!unitPattern.test(candidates[index])) {
                continue;
            }

            var name = extractCompanyName(candidates[index]);

            if (isLongCompanyName(name, unit)) {
                return name;
            }
        }

        return '';
    }

    function resolveReportCompany(firstRow, sessionName) {
        var unit = String($('#perusahaan').val() || '').trim().toUpperCase();

        var rowName = pickValue(firstRow || {}, [
            'NAMA_PT',
            'nama_pt',
            'NAMA_PERUSAHAAN',
            'nama_perusahaan',
            'ATAS_NAMA_PT',
            'atas_nama_pt',
            'NAMA_UNIT',
            'nama_unit'
        ]);

        sessionName = String(
            sessionName === undefined ? $('#nama_perusahaan_session').val() : sessionName
        ).trim();

        var candidates = [
            extractCompanyName(rowName),
            rowName,
            scrapeCompanyNameForUnit(unit),
            extractCompanyName(sessionName),
            sessionName
        ];

        for (var index = 0; index < candidates.length; index++) {
            if (isLongCompanyName(candidates[index], unit)) {
                return String(candidates[index]).trim();
            }
        }

        return unit || '-';
    }

    function getSummary() {
        if (!$('#tgl_awal').val() || !$('#tgl_akhir').val()) {
            alert('Tanggal awal dan tanggal akhir wajib diisi.');
            return;
        }

        cancelActiveSummaryRequest();

        var requestId = ++summaryRequestId;
        var filterData = getFilterData();

        $('#spSudahPpjbNoDataAlertModal').modal('hide');

        var renderContext = {
            tgl_awal: filterData.tgl_awal,
            tgl_akhir: filterData.tgl_akhir,
            persen: filterData.persen,
            sektor_text: $.trim($('#sektorentry').text()) || 'Semua Sektor',
            company_session_text: String($('#nama_perusahaan_session').val() || '').trim()
        };

        $('#loading-info').show();
        $('#main-display').html('');
        syncSpSudahPpjbPrintState();

        activeSummaryRequest = $.ajax({
            url: '{{ url()->current() }}/get_summary',
            type: 'POST',
            dataType: 'json',
            data: filterData,
            success: function (data) {
                if (requestId !== summaryRequestId) {
                    return;
                }

                $('#loading-info').hide();
                activeSummaryRequest = null;
                renderTable(data, renderContext);
            },
            error: function (xhr, status) {
                if (status === 'abort') {
                    return;
                }

                if (requestId !== summaryRequestId) {
                    return;
                }

                $('#loading-info').hide();
                activeSummaryRequest = null;
                console.log(xhr.responseText);
                $('#main-display').html('<div class="alert alert-danger">Gagal mengambil data. Cek console atau log Laravel.</div>');
                syncSpSudahPpjbPrintState();
            }
        });
    }

    function renderTable(data, context) {
        context = context || {};

        var tglAwal = formatDateIndo(context.tgl_awal || $('#tgl_awal').val());
        var tglAkhir = formatDateIndo(context.tgl_akhir || $('#tgl_akhir').val());
        var persen = context.persen || $('#persen').val() || '100.00';
        var sektorText = context.sektor_text || $.trim($('#sektorentry').text()) || 'Semua Sektor';
        var tglCetak = (data && data.length > 0 && data[0].TGL_CETAK)
            ? formatDateIndo(data[0].TGL_CETAK)
            : formatDateIndo(new Date());

        var companyText = resolveReportCompany(
            (data && data.length > 0) ? data[0] : {},
            context.company_session_text
        );

        var html = '';

        html += '<div class="report-wrapper">';

        html += '<div class="report-header">';
        html += '<div class="report-company">' + escapeHtml(companyText) + '</div>';
        html += '<div class="report-title">DAFTAR RUMAH TERJUAL PERTANGGAL ( SUDAH PPJB )</div>';
        html += '<div class="report-period">';
        html += 'Periode PPJB: ' + escapeHtml(tglAwal) + ' s.d ' + escapeHtml(tglAkhir);
        html += '<br>Pembayaran mencapai: ' + escapeHtml(persen) + ' %';
        html += '<br>Tgl: ' + escapeHtml(tglCetak);
        html += '</div>';
        html += '</div>';

        html += '<div class="report-subtitle">';
        html += '<span class="report-subtitle-label">Sektor/Cluster:</span>';
        html += '<span class="report-subtitle-value">' + escapeHtml(sektorText) + '</span>';
        html += '<span class="report-live-badge">Live data</span>';
        html += '</div>';

        html += '<div class="report-table-container">';
        html += '<table class="table table-bordered report-table">';
        html += '<thead>';
        html += '<tr>';
        html += '<th>NO.</th>';
        html += '<th>TANGGAL<br>PPJB</th>';
        html += '<th>NOMOR<br>PPJB</th>';
        html += '<th>BLOK<br>NOMOR</th>';
        html += '<th>Luas<br>Tanah</th>';
        html += '<th>Luas<br>Bgn (m2)</th>';
        html += '<th>MODEL</th>';
        html += '<th>HARGA JUAL<br>(Rp)</th>';
        html += '<th>NAMA<br>PEMBELI</th>';
        html += '<th>JML BAYAR<br>(Rp)</th>';
        html += '<th>%<br>BAYAR</th>';
        html += '<th>NAMA<br>AGEN</th>';
        html += '<th>NAMA<br>SALES</th>';
        html += '<th>TANGGAL<br>MENCAPAI</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';

        if (!data || data.length === 0) {
            html += '<tr>';
            html += '<td colspan="14" class="empty-row">Data tidak ditemukan.</td>';
            html += '</tr>';
        } else {
            var totalHargaJual = 0;
            var totalJmlBayar = 0;

            $.each(data, function (index, item) {
                totalHargaJual += parseNumber(item.HARGA_JUAL);
                totalJmlBayar += parseNumber(item.JML_BAYAR);

                html += '<tr>';
                html += '<td style="text-align:center;">' + (index + 1) + '.</td>';
                html += '<td style="text-align:center;">' + formatDateIndo(item.TGL_PPJB) + '</td>';
                html += '<td>' + escapeHtml(valueOrEmpty(item.NO_PPJB)) + '</td>';
                html += '<td>' + escapeHtml(valueOrEmpty(item.BLOK_NOMOR)) + '</td>';
                html += '<td style="text-align:right;">' + formatNumber(item.LUAS_TANAH_REPORT) + '</td>';
                html += '<td style="text-align:right;">' + formatNumber(item.LUAS_BANGUNAN_REPORT) + '</td>';
                html += '<td>' + escapeHtml(valueOrEmpty(item.MODEL)) + '</td>';
                html += '<td style="text-align:right;">' + formatNumber(item.HARGA_JUAL) + '</td>';
                html += '<td>' + escapeHtml(valueOrEmpty(item.NASABAH_NAMA)) + '</td>';
                html += '<td style="text-align:right;">' + formatNumber(item.JML_BAYAR) + '</td>';
                html += '<td style="text-align:right;">' + formatPercent(item.PROSENTASE) + '</td>';
                html += '<td>' + escapeHtml(valueOrEmpty(item.NAMA_AGEN)) + '</td>';
                html += '<td>' + escapeHtml(valueOrEmpty(item.NAMA_SALES)) + '</td>';
                html += '<td style="text-align:center;">' + formatDateIndo(item.TGL_CAPAI) + '</td>';
                html += '</tr>';
            });

            html += '<tr class="report-total-row">';
            html += '<td colspan="7" style="text-align:right;">TOTAL</td>';
            html += '<td class="total-value" style="text-align:right;">' + formatNumber(totalHargaJual) + '</td>';
            html += '<td></td>';
            html += '<td class="total-value" style="text-align:right;">' + formatNumber(totalJmlBayar) + '</td>';
            html += '<td colspan="4"></td>';
            html += '</tr>';
        }

        html += '</tbody>';
        html += '</table>';
        html += '</div>';
        html += '</div>';

        $('#main-display').html(html);
        syncSpSudahPpjbPrintState();

        if (!data || data.length === 0) {
            $('#spSudahPpjbNoDataAlertModal').modal('show');
        }
    }

    function valueOrEmpty(value) {
        if (value === null || value === undefined) {
            return '';
        }

        return value;
    }

    function formatNumber(value) {
        if (value === null || value === undefined || value === '') {
            return '';
        }

        var number = Number(value);

        if (isNaN(number)) {
            return value;
        }

        return number.toLocaleString('id-ID');
    }

    function formatPercent(value) {
        if (value === null || value === undefined || value === '') {
            return '';
        }

        var number = Number(value);

        if (isNaN(number)) {
            return value;
        }

        return number.toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function parseNumber(value) {
        if (value === null || value === undefined || value === '') {
            return 0;
        }

        var number = Number(value);

        if (isNaN(number)) {
            return 0;
        }

        return number;
    }

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

    function syncSpSudahPpjbPrintState() {
        var siap = $('#main-display .report-wrapper').length > 0;

        $('#spSudahPpjbPrintButton')
            .prop('disabled', !siap)
            .attr('aria-disabled', siap ? 'false' : 'true');
    }

    function printSpSudahPpjbReport() {
        if (!$('#main-display .report-wrapper').length) {
            alert('Silakan klik View terlebih dahulu untuk menampilkan laporan.');
            return;
        }

        var reportHtml = $('#main-display').html();

        if (!reportHtml) {
            return;
        }

        $('#spSudahPpjbNativePrintFrame').remove();

        var frame = document.createElement('iframe');
        frame.id = 'spSudahPpjbNativePrintFrame';
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

            html,
            body {
                width: 100%;
                margin: 0;
                padding: 0;
                background: #fff;
                color: #000;
                font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            }

            *,
            *::before,
            *::after {
                box-sizing: border-box;
            }

            .report-wrapper {
                width: 100% !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                background: #fff !important;
                color: #000 !important;
                box-shadow: none !important;
                font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
            }

            .report-wrapper::before {
                display: none !important;
            }

            .report-header {
                display: grid !important;
                grid-template-columns: 1fr 1.45fr 1fr !important;
                gap: 10px !important;
                align-items: center !important;
                margin: 0 0 6px !important;
                padding: 8px 10px !important;
                border: 1px solid #777 !important;
                border-radius: 0 !important;
                background: #fff !important;
                color: #000 !important;
                box-shadow: none !important;
            }

            .report-company {
                color: #000 !important;
                text-align: left !important;
                font-size: 10px !important;
                font-weight: 700 !important;
                line-height: 1.3 !important;
            }

            .report-title {
                margin: 0 !important;
                color: #000 !important;
                text-align: center !important;
                font-family: Cambria, Georgia, "Times New Roman", serif !important;
                font-size: 13px !important;
                font-weight: 700 !important;
                line-height: 1.2 !important;
            }

            .report-period {
                color: #000 !important;
                text-align: right !important;
                font-size: 10px !important;
                font-weight: 500 !important;
                line-height: 1.35 !important;
            }

            .report-subtitle {
                display: grid !important;
                grid-template-columns: 1fr auto 1fr !important;
                min-height: 0 !important;
                align-items: center !important;
                gap: 8px !important;
                margin: 0 0 6px !important;
                padding: 5px 8px !important;
                border: 1px solid #aaa !important;
                border-radius: 0 !important;
                background: #fff !important;
                color: #000 !important;
                text-align: left !important;
                font-size: 10px !important;
                font-weight: 500 !important;
            }

            .report-subtitle-label {
                justify-self: start !important;
                color: #000 !important;
                white-space: nowrap !important;
            }

            .report-subtitle-value {
                justify-self: center !important;
                color: #000 !important;
                text-align: center !important;
                font-weight: 700 !important;
            }

            .report-subtitle strong {
                color: #000 !important;
                font-weight: 700 !important;
            }

            .report-live-badge {
                display: inline-flex !important;
                justify-self: end !important;
                align-items: center !important;
                gap: 4px !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                background: #fff !important;
                color: #000 !important;
                font-size: 10px !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
            }

            .report-live-badge::before {
                content: "";
                display: inline-block;
                width: 4px;
                height: 4px;
                border-radius: 50%;
                background: #000;
                box-shadow: none !important;
            }

            .report-table-container {
                width: 100% !important;
                max-width: 100% !important;
                max-height: none !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
                border: 0 !important;
                border-radius: 0 !important;
                background: #fff !important;
                box-shadow: none !important;
            }

            .report-table {
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                margin: 0 !important;
                table-layout: auto !important;
                border-collapse: collapse !important;
                border-spacing: 0 !important;
                border: 1px solid #000 !important;
                background: #fff !important;
                color: #000 !important;
                font-size: 10px !important;
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
                min-width: 0 !important;
                max-width: none !important;
                height: auto !important;
                padding: 2px !important;
                border: 1px solid #000 !important;
                background: #fff !important;
                color: #000 !important;
                box-shadow: none !important;
                overflow: visible !important;
                white-space: normal !important;
                overflow-wrap: break-word !important;
                word-break: normal !important;
                vertical-align: middle !important;
                font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
                font-size: 10px !important;
                line-height: 1.12 !important;
            }

            .report-table th {
                text-align: center !important;
                font-weight: 700 !important;
            }

            .report-table tbody tr:nth-child(even) td,
            .report-table tbody tr:hover td {
                background: #fff !important;
            }

            .report-table tbody tr.report-total-row td {
                background: #fff !important;
                color: #000 !important;
                font-weight: 700 !important;
            }

            .report-table tbody tr.report-total-row td.total-value {
                border-top: 1px solid #000 !important;
                background: #fff !important;
            }

            .empty-row {
                height: 40px !important;
                color: #000 !important;
                background: #fff !important;
                text-align: center !important;
                font-style: italic !important;
            }

            html,
            body,
            .report-table,
            .report-table th,
            .report-table td {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .report-table {
                border-collapse: collapse !important;
                border: 1px solid #9a9a9a !important;
            }

            .report-table th,
            .report-table td {
                border: 1px solid #d5d5d5 !important;
                line-height: 1.3 !important;
            }

            .report-table thead th {
                padding-top: 5px !important;
                padding-bottom: 5px !important;
                border-color: #9a9a9a !important;
                background: #f1f3f5 !important;
            }

            .report-table tbody td {
                padding-top: 4px !important;
                padding-bottom: 4px !important;
            }

            .report-table tbody tr:nth-child(even) > td {
                background: #fbfcfe !important;
            }

            .report-table tbody tr[class*="sector-row"] > td,
            .report-table tbody tr[class*="sektor-row"] > td,
            .report-table tbody tr[class*="cluster-row"] > td,
            .report-table tbody tr[class*="group-heading"] > td,
            .report-table tbody tr[class*="head-row"] > td {
                background: #f1f3f5 !important;
            }

            .report-table tbody tr[class*="total-row"] > td,
            .report-table tbody tr[class*="grand-total"] > td {
                background: #eff6ff !important;
            }

            .report-table tbody tr[class*="subtotal"] > td {
                background: #f8fafc !important;
            }
`;

        frameDocument.open();
        frameDocument.write(
            '<!DOCTYPE html>'
            + '<html>'
            + '<head>'
            + '<meta charset="utf-8">'
            + '<meta name="viewport" content="width=device-width,initial-scale=1">'
            + '<title>Daftar SP Sudah PPJB</title>'
            + '<style>' + printCss + '</style>'
            + '</head>'
            + '<body>' + reportHtml + '</body>'
            + '</html>'
        );
        frameDocument.close();
        applyPrintTableRules(frameDocument);

        var cleanupPrintFrame = function () {
            $('#spSudahPpjbNativePrintFrame').remove();
        };

        try {
            frameWindow.onafterprint = cleanupPrintFrame;
        } catch (error) {
        }

        window.setTimeout(function () {
            try {
                frameWindow.focus();
                frameWindow.print();
            } catch (error) {
                console.error('Gagal membuka dialog print SP Sudah PPJB:', error);
                cleanupPrintFrame();
                alert('Dialog print gagal dibuka. Silakan coba kembali.');
            }
        }, 180);

        window.setTimeout(cleanupPrintFrame, 30000);
    }

    function formatDateIndo(dateValue) {
        if (!dateValue) {
            return '-';
        }

        var date = new Date(dateValue);

        if (isNaN(date.getTime())) {
            return dateValue;
        }

        var day = String(date.getDate()).padStart(2, '0');
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var year = date.getFullYear();

        return day + '-' + month + '-' + year;
    }

    (function () {
        var PILIH_TABEL = 'table[class*="modal-table"], table[class*="lookup-table"]';
        var gayaUmumTerpasang = false;
        var gayaTabelTerpasang = {};

        function sisipkanGaya(penanda, isi) {
            var gaya = document.createElement('style');

            gaya.setAttribute('data-lookup-gaya', penanda);
            gaya.textContent = isi;
            (document.head || document.documentElement).appendChild(gaya);
        }

        function pasangGayaUmum() {
            if (gayaUmumTerpasang) {
                return;
            }

            gayaUmumTerpasang = true;

            sisipkanGaya('umum',
                '[class*="modal-table-wrap"],[class*="lookup-table-wrap"]{'
                + 'max-height:430px!important;overflow:auto!important;'
                + 'border:1px solid #dbe3ef!important;border-radius:16px!important;'
                + 'background:#fff!important;box-shadow:none!important}'

                + 'input[class*="modal-search"],input[class*="lookup-search"],'
                + 'input[id="modalSearchInput"]{'
                + 'width:100%!important;height:42px!important;'
                + 'border:1px solid #c8d3e1!important;'
                + 'border-radius:12px!important;background:#fff!important;'
                + 'color:#101828!important;box-shadow:none!important;'
                + 'font-family:"Segoe UI",Tahoma,Arial,sans-serif!important;'
                + 'font-size:12px!important;outline:0!important}'
                + 'input[class*="modal-search"]:focus,input[class*="lookup-search"]:focus,'
                + 'input[id="modalSearchInput"]:focus{'
                + 'border-color:#2563eb!important;'
                + 'box-shadow:0 0 0 3px rgba(37,99,235,.12)!important}'

                + '.lookup-sort-bar{display:flex;align-items:center;gap:8px;'
                + 'margin:0 0 8px;padding:0;flex-wrap:wrap}'
                + '.lookup-sort-bar label{margin:0;color:#475467;'
                + 'font-family:"Segoe UI Semibold","Segoe UI",Tahoma,Arial,sans-serif;'
                + 'font-size:10px;font-weight:900;letter-spacing:.08em;'
                + 'text-transform:uppercase;white-space:nowrap}'
                + '.lookup-sort-bar select{min-width:170px;max-width:100%;height:34px;'
                + 'padding:0 30px 0 10px;border:1px solid #c8d3e1;border-radius:10px;'
                + 'background:#fff;color:#101828;cursor:pointer;'
                + 'font-family:"Segoe UI",Tahoma,Arial,sans-serif;font-size:12px;'
                + 'font-weight:650;outline:0;appearance:none;-webkit-appearance:none;'
                + 'background-image:url("data:image/svg+xml;charset=utf-8,'
                + '%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 16 16\' fill=\'none\''
                + ' stroke=\'%23475467\' stroke-width=\'2\' stroke-linecap=\'round\''
                + ' stroke-linejoin=\'round\'%3E%3Cpath d=\'M4 6l4 4 4-4\'/%3E%3C/svg%3E");'
                + 'background-repeat:no-repeat;background-position:right 9px center;'
                + 'background-size:12px}'
                + '.lookup-sort-bar select:hover{border-color:#aebed1}'
                + '.lookup-sort-bar select:focus{border-color:#2563eb;'
                + 'box-shadow:0 0 0 3px rgba(37,99,235,.13)}');
        }

        function idAman(elemen) {
            var id = elemen && elemen.id ? String(elemen.id) : '';

            return /^[A-Za-z][A-Za-z0-9_-]*$/.test(id) ? id : '';
        }

        function pasangGayaTabel(tabel) {
            var wadah = tabel.closest ? tabel.closest('[id]') : null;
            var id = idAman(wadah);
            var awalan = id ? '#' + id + ' ' : '';

            if (gayaTabelTerpasang[awalan]) {
                return;
            }

            gayaTabelTerpasang[awalan] = true;

            var dasar = ['table[class*="modal-table"]', 'table[class*="lookup-table"]'];

            function gabung(akhiran) {
                return dasar.map(function (t) {
                    return awalan + t + akhiran;
                }).join(',');
            }

            sisipkanGaya('tabel' + (id || '-umum'),
                gabung('') + '{width:100%!important;margin:0!important;'
                + 'border:0!important;border-collapse:separate!important;'
                + 'border-spacing:0!important;background:#fff!important;'
                + 'font-family:"Segoe UI",Tahoma,Arial,sans-serif!important;'
                + 'font-size:12px!important}'

                + gabung(' th') + ',' + gabung(' td')
                + '{padding:10px 12px!important;border:0!important;'
                + 'border-right:1px solid #e2e8f0!important;'
                + 'border-bottom:1px solid #e2e8f0!important;'
                + 'text-align:center!important;vertical-align:middle!important;'
                + 'white-space:normal!important;word-break:normal!important;'
                + 'border-radius:0!important}'

                + gabung(' th:last-child') + ',' + gabung(' td:last-child')
                + '{border-right:0!important}'

                + gabung(' th')
                + '{position:sticky!important;top:0!important;z-index:2!important;'
                + 'background:linear-gradient(180deg,#eff6ff 0%,#e7f0fc 100%)!important;'
                + 'color:#344054!important;'
                + 'font-family:"Segoe UI Semibold","Segoe UI",Tahoma,Arial,sans-serif!important;'
                + 'font-weight:850!important;letter-spacing:normal!important;'
                + 'text-transform:none!important}'

                + gabung(' tbody tr') + '{cursor:pointer!important}'
                + gabung(' tbody tr td') + '{color:#344054!important;'
                + 'background:#fff!important;font-weight:400!important}'

                + gabung(' tbody tr td:first-child') + ','
                + gabung(' tbody tr td:last-child')
                + '{color:#344054!important;background:#fff!important;'
                + 'font-weight:400!important}'

                + gabung(' tbody tr:hover td') + ','
                + gabung(' tbody tr:hover td:first-child') + ','
                + gabung(' tbody tr:hover td:last-child')
                + '{background:#eff6ff!important;color:#1d4ed8!important}');
        }

        function judulKolom(tabel) {
            var barisJudul = tabel.querySelector('tr');

            if (!barisJudul) {
                return [];
            }

            var sel = barisJudul.querySelectorAll('th');

            if (sel.length === 0) {
                return [];
            }

            return Array.prototype.map.call(sel, function (th) {
                return String(th.textContent || '')
                    .replace(/\s+/g, ' ')
                    .trim();
            });
        }

        function barisData(tabel) {
            var semua = tabel.querySelectorAll('tr');

            return Array.prototype.filter.call(semua, function (tr) {
                return tr.querySelector('td') !== null;
            });
        }

        function barisSemua(tr) {
            var sel = tr.querySelectorAll('td');
            var i;

            for (i = 0; i < sel.length; i += 1) {
                if (/^\*+$/.test(String(sel[i].textContent || '').trim())) {
                    return true;
                }
            }

            return false;
        }

        function nilaiSel(tr, indeks) {
            var sel = tr.querySelectorAll('td');

            if (indeks >= sel.length) {
                return '';
            }

            return String(sel[indeks].textContent || '')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function urutkan(tabel, indeks) {
            var baris = barisData(tabel);

            if (baris.length < 2) {
                return;
            }

            var induk = baris[0].parentNode;
            var ditahan = [];
            var diurut = [];

            baris.forEach(function (tr) {
                if (barisSemua(tr)) {
                    ditahan.push(tr);
                } else {
                    diurut.push(tr);
                }
            });

            if (indeks >= 0) {
                diurut.sort(function (a, b) {
                    var kiri = nilaiSel(a, indeks);
                    var kanan = nilaiSel(b, indeks);

                    if (kiri === '' && kanan !== '') {
                        return 1;
                    }

                    if (kanan === '' && kiri !== '') {
                        return -1;
                    }

                    return kiri.localeCompare(kanan, 'id', {
                        numeric: true,
                        sensitivity: 'base'
                    });
                });
            } else {
                diurut.sort(function (a, b) {
                    return Number(a.getAttribute('data-lookup-urutan-asal'))
                        - Number(b.getAttribute('data-lookup-urutan-asal'));
                });
            }

            ditahan.concat(diurut).forEach(function (tr) {
                induk.appendChild(tr);
            });
        }

        function pasang(tabel) {
            if (!tabel || tabel.getAttribute('data-lookup-sort') === 'sudah') {
                return;
            }

            tabel.setAttribute('data-lookup-sort', 'sudah');
            pasangGayaUmum();
            pasangGayaTabel(tabel);

            var judul = judulKolom(tabel);
            var baris = barisData(tabel);

            if (judul.length < 2 || baris.length < 2) {
                return;
            }

            baris.forEach(function (tr, i) {
                tr.setAttribute('data-lookup-urutan-asal', String(i));
            });

            var bar = document.createElement('div');

            bar.className = 'lookup-sort-bar';

            var label = document.createElement('label');

            label.textContent = 'Urutkan';
            bar.appendChild(label);

            var pilihan = document.createElement('select');

            pilihan.setAttribute('aria-label', 'Urutkan daftar');

            var bawaan = document.createElement('option');

            bawaan.value = '-1';
            bawaan.textContent = 'Urutan bawaan';
            pilihan.appendChild(bawaan);

            judul.forEach(function (nama, i) {
                if (nama === '') {
                    return;
                }

                var opsi = document.createElement('option');

                opsi.value = String(i);
                opsi.textContent = nama;
                pilihan.appendChild(opsi);
            });

            pilihan.addEventListener('change', function () {
                urutkan(tabel, Number(pilihan.value));
            });

            bar.appendChild(pilihan);

            var sasaran = tabel;

            while (
                sasaran.parentNode
                && sasaran.parentNode.nodeType === 1
                && /(-wrap|-wrapper)\b/.test(sasaran.parentNode.className || '')
            ) {
                sasaran = sasaran.parentNode;
            }

            if (sasaran.parentNode) {
                sasaran.parentNode.insertBefore(bar, sasaran);
            }
        }

        function pindai(akar) {
            if (!akar || !akar.querySelectorAll) {
                return;
            }

            if (akar.matches && akar.matches(PILIH_TABEL)) {
                pasang(akar);
            }

            Array.prototype.forEach.call(
                akar.querySelectorAll(PILIH_TABEL),
                pasang
            );
        }

        function mulai() {
            pindai(document.body);

            if (typeof MutationObserver !== 'function') {
                return;
            }

            new MutationObserver(function (daftar) {
                daftar.forEach(function (rekaman) {
                    Array.prototype.forEach.call(
                        rekaman.addedNodes,
                        function (simpul) {
                            if (simpul.nodeType === 1) {
                                pindai(simpul);
                            }
                        }
                    );
                });
            }).observe(document.body, { childList: true, subtree: true });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', mulai);
        } else {
            mulai();
        }
    })();

</script>

@endsection