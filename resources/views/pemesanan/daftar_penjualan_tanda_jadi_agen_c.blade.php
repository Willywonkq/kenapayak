@extends('layouts.template')

@section('content')

<style>
    .filter-label {
        font-size: 13px;
        font-weight: 600;
        width: 120px;
    }

    .lookup-row {
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .lookup-control {
        display: flex;
        flex-grow: 1;
        gap: 5px;
        width: 100%;
    }

    .lookup-display {
        flex-grow: 1;
        min-height: 34px;
        background-color: #fff;
        display: flex;
        align-items: center;
        overflow: hidden;
        white-space: nowrap;
    }

    .lookup-button {
        min-width: 45px;
    }

    .modal-table {
        width: 100%;
        table-layout: fixed;
    }

    .modal-table th {
        text-align: center;
        vertical-align: middle;
        background-color: lightgrey;
        font-size: 13px;
    }

    .modal-table td {
        text-align: center;
        vertical-align: middle;
        cursor: pointer;
        font-size: 13px;
    }

    .modal-table tr:hover td {
        background-color: #ffffcc;
    }

    .modal-search-wrapper {
        margin-bottom: 12px;
    }

    .modal-table-wrapper {
        max-height: 430px;
        overflow-y: auto;
        overflow-x: auto;
    }

    .result-wrapper {
        overflow-x: hidden;
        max-width: 100%;
    }

    .report-wrapper {
        background: #fff;
        padding: 24px 20px;
        min-height: 420px;
        font-family: Arial, sans-serif;
    }

    .report-company {
        font-size: 12px;
        font-weight: bold;
        margin-bottom: 2px;
    }

    .report-title {
        text-align: center;
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 6px;
    }

    .report-subtitle {
        text-align: center;
        font-size: 13px;
        margin-bottom: 16px;
    }

    .report-table-container {
        width: 100%;
        max-width: 100%;
        max-height: calc(100vh - 180px);
        min-height: 260px;
        overflow: auto;
        margin-top: 6px;
        padding-bottom: 4px;
    }

    .report-table {
        width: max-content;
        min-width: 1650px;
        border-collapse: collapse;
        font-size: 11px;
        background: white;
    }

    .report-table th {
        border: 1px solid #333 !important;
        text-align: center;
        vertical-align: middle;
        background: #fff !important;
        color: #000 !important;
        font-weight: bold;
        padding: 5px;
        white-space: nowrap;
    }

    .report-table td {
        border: 1px solid #333 !important;
        text-align: center;
        vertical-align: middle;
        padding: 5px;
        white-space: nowrap;
    }

    .empty-row {
        height: 90px;
        color: #777;
        font-style: italic;
        text-align: center;
    }


    :root {
        --ptj-primary: #2563eb;
        --ptj-primary-dark: #1d4ed8;
        --ptj-primary-soft: #dbeafe;
        --ptj-success: #16a34a;
        --ptj-success-dark: #15803d;
        --ptj-text: #0f172a;
        --ptj-text-soft: #475569;
        --ptj-muted: #64748b;
        --ptj-border: #e5e7eb;
        --ptj-border-strong: #d1d5db;
        --ptj-white: #ffffff;
        --ptj-bg-soft: #f8fafc;
        --ptj-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
        --ptj-radius: 18px;
    }

    .penjualan-tanda-jadi-content {
        width: 100%;
        margin: 0;
        padding: 0;
        color: var(--ptj-text-soft);
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    }

    .penjualan-tanda-jadi-content .page-panel,
    .penjualan-tanda-jadi-content .filter-panel,
    .penjualan-tanda-jadi-content .result-panel {
        margin-bottom: 16px;
        padding: 20px;
        border: 1px solid var(--ptj-border);
        border-radius: var(--ptj-radius);
        background: var(--ptj-white);
        box-shadow: var(--ptj-shadow);
    }

    .penjualan-tanda-jadi-content .page-panel {
        padding: 18px 20px;
    }

    .penjualan-tanda-jadi-content .page-title-wrap {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .penjualan-tanda-jadi-content .page-title-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        flex: 0 0 42px;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--ptj-primary), var(--ptj-primary-dark));
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25);
        font-size: 16px;
    }

    .penjualan-tanda-jadi-content .page-title-text {
        margin: 0;
        color: var(--ptj-text);
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 20px;
        font-weight: 700;
        letter-spacing: -0.01em;
    }

    .penjualan-tanda-jadi-content .filter-label {
        width: 120px;
        flex: 0 0 120px;
        color: #344054;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 13px;
        font-weight: 600;
    }

    .penjualan-tanda-jadi-content .filter-panel .lookup-row {
        margin-bottom: 12px;
        gap: 10px;
    }

    .penjualan-tanda-jadi-content .filter-panel .form-control,
    .penjualan-tanda-jadi-content .filter-panel select.form-control,
    .penjualan-tanda-jadi-content .filter-panel input.form-control,
    .penjualan-tanda-jadi-content .filter-panel .lookup-display {
        min-height: 44px;
        height: 44px;
        padding-left: 14px;
        padding-right: 14px;
        color: var(--ptj-text);
        border: 1px solid var(--ptj-border-strong);
        border-radius: 12px;
        background: #fbfdff;
        box-shadow: none;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 13px;
        transition:
            border-color 0.18s ease,
            box-shadow 0.18s ease,
            background-color 0.18s ease;
    }

    .penjualan-tanda-jadi-content .filter-panel .form-control:hover,
    .penjualan-tanda-jadi-content .filter-panel .lookup-display:hover {
        border-color: #aebbd0;
    }

    .penjualan-tanda-jadi-content .filter-panel .form-control:focus,
    .penjualan-tanda-jadi-content .filter-panel select.form-control:focus,
    .penjualan-tanda-jadi-content .filter-panel input.form-control:focus {
        border-color: var(--ptj-primary);
        outline: none;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .penjualan-tanda-jadi-content .filter-panel input[readonly].form-control {
        color: #667085;
        border-color: #d8dee8;
        background: #f3f6fa;
        cursor: not-allowed;
    }

    .penjualan-tanda-jadi-content .filter-panel select.form-control {
        cursor: pointer;
    }

    .penjualan-tanda-jadi-content .lookup-control {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 42px;
        width: 100%;
        gap: 0;
    }

    .penjualan-tanda-jadi-content .lookup-display {
        display: flex;
        align-items: center;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        cursor: pointer;
    }

    .penjualan-tanda-jadi-content .lookup-button {
        width: 42px;
        min-width: 42px;
        height: 44px;
        min-height: 44px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--ptj-primary);
        border: 1px solid var(--ptj-border-strong);
        border-left: 0;
        border-radius: 0 12px 12px 0;
        background: #f8fafc;
        box-shadow: none;
        transition:
            border-color 0.18s ease,
            background-color 0.18s ease,
            color 0.18s ease;
    }

    .penjualan-tanda-jadi-content .lookup-button:hover,
    .penjualan-tanda-jadi-content .lookup-button:focus {
        position: relative;
        z-index: 1;
        color: #1d4ed8;
        border-color: var(--ptj-primary);
        outline: 0;
        background: var(--ptj-primary-soft);
        transform: none;
        filter: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .penjualan-tanda-jadi-content .lookup-button svg {
        width: 18px;
        height: 18px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 2;
    }

    .penjualan-tanda-jadi-content .action-buttons {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
        gap: 9px;
        margin-top: 14px;
    }

    .penjualan-tanda-jadi-content .action-btn {
        width: 104px;
        min-width: 104px;
        height: 36px;
        min-height: 36px;
        padding: 6px 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border: 1px solid transparent;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease,
            background-color 0.15s ease;
    }

    .penjualan-tanda-jadi-content .action-btn svg {
        width: 17px;
        height: 17px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 1.8;
    }

    .penjualan-tanda-jadi-content .action-btn-view {
        color: #ffffff;
        background: var(--ptj-primary);
        box-shadow: 0 5px 12px rgba(37, 99, 235, 0.22);
    }

    .penjualan-tanda-jadi-content .action-btn-excel {
        color: var(--ptj-success);
        border-color: #bbdec8;
        background: #f2fbf5;
        box-shadow: none;
    }

    .penjualan-tanda-jadi-content .action-btn:hover {
        transform: translateY(-1px);
        filter: none;
    }

    .penjualan-tanda-jadi-content .action-btn-view:hover,
    .penjualan-tanda-jadi-content .action-btn-view:focus {
        color: #ffffff;
        background: #1d4ed8;
    }

    .penjualan-tanda-jadi-content .action-btn-excel:hover,
    .penjualan-tanda-jadi-content .action-btn-excel:focus {
        color: var(--ptj-success);
        border-color: #86c99d;
        background: #e7f7ec;
    }

    .penjualan-tanda-jadi-content .action-btn:active {
        transform: translateY(0);
    }

    .penjualan-tanda-jadi-content #loading-info {
        margin-bottom: 14px;
        padding: 12px 14px;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        background: #eff6ff;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 13px;
        font-weight: 600;
    }

    .penjualan-tanda-jadi-content .empty-state-panel {
        min-height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 24px;
        color: var(--ptj-muted);
        border: 1px dashed var(--ptj-border-strong);
        border-radius: 16px;
        background: linear-gradient(180deg, #fcfdff 0%, #f8fafc 100%);
        text-align: center;
    }

    .penjualan-tanda-jadi-content .empty-state-panel i {
        width: 52px;
        height: 52px;
        margin-bottom: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--ptj-primary);
        border-radius: 16px;
        background: var(--ptj-primary-soft);
        font-size: 20px;
    }


    #penjualanTandaJadiAgenModal .modal-dialog {
        width: min(920px, calc(100vw - 32px));
        max-width: 920px;
        margin: 24px auto;
    }

    #penjualanTandaJadiAgenModal .modal-content {
        overflow: hidden;
        border: 0;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
    }

    #penjualanTandaJadiAgenModal .modal-header {
        min-height: 66px;
        padding: 16px 20px;
        align-items: center;
        border-bottom: 1px solid #e8edf5;
        background: linear-gradient(135deg, #ffffff 0%, #f5f8ff 100%);
    }

    #penjualanTandaJadiAgenModalTitle {
        margin: 0;
        color: #172033;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: -0.2px;
    }

    #penjualanTandaJadiAgenModalTitle::before {
        content: "\f03a";
        width: 34px;
        height: 34px;
        margin-right: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--ptj-primary), var(--ptj-primary-dark));
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.24);
        font-family: "Font Awesome 5 Free";
        font-size: 14px;
        font-weight: 900;
        vertical-align: middle;
    }

    #penjualanTandaJadiAgenModal .modal-header .btn-light {
        width: 38px;
        height: 38px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #667085;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
        background: #ffffff;
        transition: all 0.18s ease;
    }

    #penjualanTandaJadiAgenModal .modal-header .btn-light:hover {
        color: #b42318;
        border-color: #fecdca;
        background: #fff4f3;
        transform: translateY(-1px);
    }

    #penjualanTandaJadiAgenModal .modal-body {
        padding: 18px 20px 20px;
        background: #f8fafc;
    }

    #penjualanTandaJadiAgenModal .modal-search-wrapper {
        position: relative;
        margin-bottom: 14px;
    }

    #penjualanTandaJadiAgenModal .modal-search-box {
        position: relative;
    }

    #penjualanTandaJadiAgenModal .modal-search-icon {
        position: absolute;
        top: 50%;
        left: 14px;
        z-index: 2;
        color: #667085;
        transform: translateY(-50%);
        pointer-events: none;
    }

    #penjualanTandaJadiAgenModal #modalSearchInput {
        height: 44px;
        padding: 9px 14px 9px 42px;
        color: #172033;
        border: 1px solid #d7deea;
        border-radius: 11px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        font-size: 13px;
        transition:
            border-color 0.18s ease,
            box-shadow 0.18s ease;
    }

    #penjualanTandaJadiAgenModal #modalSearchInput:focus {
        border-color: var(--ptj-primary);
        outline: none;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    #penjualanTandaJadiAgenModal .modal-search-hint {
        display: block;
        margin-top: 7px;
        color: #7b8798;
        font-size: 11px;
    }

    #penjualanTandaJadiAgenModal .modal-table-wrapper {
        max-height: 430px;
        overflow: auto;
        border: 1px solid #e1e7f0;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    #penjualanTandaJadiAgenModal .modal-table {
        width: 100%;
        min-width: 560px;
        margin: 0;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
        color: #344054;
        background: #ffffff;
    }

    #penjualanTandaJadiAgenModal .modal-table th {
        position: sticky;
        top: 0;
        z-index: 3;
        padding: 13px 14px;
        color: #0f172a;
        border: 0 !important;
        border-right: 1px solid #dbe4f0 !important;
        border-bottom: 1px solid #cbd5e1 !important;
        background: #eff6ff !important;
        font-size: 12px;
        font-weight: 700;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }

    #penjualanTandaJadiAgenModal .modal-table td {
        padding: 12px 14px;
        color: #475569;
        border: 0 !important;
        border-right: 1px solid #eef2f7 !important;
        border-bottom: 1px solid #eef2f7 !important;
        background: #ffffff;
        font-size: 12px;
        line-height: 1.45;
        text-align: center;
        vertical-align: middle;
        white-space: normal;
        word-break: break-word;
        transition:
            background-color 0.16s ease,
            color 0.16s ease;
    }

    #penjualanTandaJadiAgenModal .modal-table tr:nth-child(even) td {
        background: #f8fafc;
    }

    #penjualanTandaJadiAgenModal .modal-table tr:not(:first-child) {
        cursor: pointer;
    }

    #penjualanTandaJadiAgenModal .modal-table tr:not(:first-child):hover td {
        color: #0f172a;
        background: #f8fbff !important;
    }

    #penjualanTandaJadiAgenModal .modal-empty-state {
        min-height: 240px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 36px 20px;
        color: #667085;
        text-align: center;
    }

    #penjualanTandaJadiAgenModal .modal-empty-state i {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--ptj-primary);
        border-radius: 14px;
        background: #eaf2ff;
        font-size: 20px;
    }

    #penjualanTandaJadiAgenModal .modal-empty-state strong {
        color: #344054;
        font-size: 14px;
    }

    @media (max-width: 767.98px) {
        .penjualan-tanda-jadi-content .filter-label {
            width: 100%;
            flex-basis: auto;
        }

        .penjualan-tanda-jadi-content .filter-panel .lookup-row {
            flex-direction: column;
            align-items: stretch;
        }

        .penjualan-tanda-jadi-content .action-buttons {
            justify-content: stretch;
        }

        .penjualan-tanda-jadi-content .action-btn {
            flex: 1 1 0;
        }

        #penjualanTandaJadiAgenModal .modal-dialog {
            width: calc(100vw - 20px);
            margin: 10px auto;
        }

        #penjualanTandaJadiAgenModal .modal-header,
        #penjualanTandaJadiAgenModal .modal-body {
            padding-left: 14px;
            padding-right: 14px;
        }
    }

    /*
     * Struktur baru:
     * - Header menjelaskan konteks laporan dan status transaksi.
     * - Filter mengikuti urutan cakupan laporan, detail penjualan, lalu aksi.
     * - ID field dan handler JavaScript lama tetap dipertahankan.
     */
    .penjualan-tanda-jadi-content .page-title-wrap {
        justify-content: space-between;
    }

    .penjualan-tanda-jadi-content .page-title-main {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .penjualan-tanda-jadi-content .page-title-copy {
        min-width: 0;
    }

    .penjualan-tanda-jadi-content .page-title-description {
        margin: 4px 0 0;
        color: var(--ptj-muted);
        font-size: 12px;
        line-height: 1.5;
    }

    .penjualan-tanda-jadi-content .status-badge {
        flex: 0 0 auto;
        padding: 7px 11px;
        color: #9a6700;
        border: 1px solid #fde68a;
        border-radius: 999px;
        background: #fffbeb;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .penjualan-tanda-jadi-content .filter-panel {
        padding: 0;
        overflow: hidden;
    }

    .penjualan-tanda-jadi-content .filter-panel-header {
        min-height: 58px;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        border-bottom: 1px solid var(--ptj-border);
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    }

    .penjualan-tanda-jadi-content .filter-panel-heading {
        margin: 0;
        color: var(--ptj-text);
        font-size: 14px;
        font-weight: 700;
    }

    .penjualan-tanda-jadi-content .filter-panel-hint {
        margin: 3px 0 0;
        color: var(--ptj-muted);
        font-size: 11px;
        line-height: 1.45;
    }

    .penjualan-tanda-jadi-content .filter-panel-body {
        padding: 18px 20px 20px;
    }

    .penjualan-tanda-jadi-content .filter-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 18px 16px;
    }

    .penjualan-tanda-jadi-content .filter-field {
        min-width: 0;
        grid-column: span 4;
    }

    .penjualan-tanda-jadi-content .filter-field-period {
        grid-column: span 6;
    }

    .penjualan-tanda-jadi-content .filter-field-location,
    .penjualan-tanda-jadi-content .filter-field-unit {
        grid-column: span 3;
    }

    .penjualan-tanda-jadi-content .filter-field .filter-label {
        width: auto;
        margin: 0 0 7px;
        display: block;
        color: #344054;
        font-size: 12px;
        line-height: 1.3;
    }

    .penjualan-tanda-jadi-content .filter-field .field-required {
        color: #dc2626;
    }

    .penjualan-tanda-jadi-content .period-range {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 24px minmax(0, 1fr);
        align-items: center;
        gap: 6px;
    }

    .penjualan-tanda-jadi-content .period-separator {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #98a2b3;
        font-size: 12px;
    }

    .penjualan-tanda-jadi-content .unit-control-wrap {
        position: relative;
    }

    .penjualan-tanda-jadi-content .unit-control-icon {
        position: absolute;
        top: 50%;
        left: 13px;
        z-index: 1;
        color: #7b8798;
        font-size: 12px;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .penjualan-tanda-jadi-content .unit-control-wrap .form-control {
        padding-left: 36px;
    }

    .penjualan-tanda-jadi-content .filter-actions {
        margin-top: 18px;
        padding-top: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        border-top: 1px solid var(--ptj-border);
    }

    .penjualan-tanda-jadi-content .filter-actions-note {
        margin: 0;
        color: var(--ptj-muted);
        font-size: 11px;
    }

    .penjualan-tanda-jadi-content .filter-actions .action-buttons {
        margin: 0;
    }

    .penjualan-tanda-jadi-content .filter-actions .action-btn {
        width: 112px;
        min-width: 112px;
        height: 38px;
    }

    @media (max-width: 991.98px) {
        .penjualan-tanda-jadi-content .filter-field-period {
            grid-column: span 12;
        }

        .penjualan-tanda-jadi-content .filter-field,
        .penjualan-tanda-jadi-content .filter-field-location,
        .penjualan-tanda-jadi-content .filter-field-unit {
            grid-column: span 6;
        }
    }

    @media (max-width: 767.98px) {
        .penjualan-tanda-jadi-content .page-title-wrap,
        .penjualan-tanda-jadi-content .filter-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .penjualan-tanda-jadi-content .status-badge {
            align-self: flex-start;
        }

        .penjualan-tanda-jadi-content .filter-field,
        .penjualan-tanda-jadi-content .filter-field-period,
        .penjualan-tanda-jadi-content .filter-field-location,
        .penjualan-tanda-jadi-content .filter-field-unit {
            grid-column: span 12;
        }

        .penjualan-tanda-jadi-content .filter-actions .action-buttons {
            width: 100%;
        }

        .penjualan-tanda-jadi-content .filter-actions .action-btn {
            width: auto;
            min-width: 0;
        }
    }



    /* Unit mengikuti unit aktif di header aplikasi dan tidak dapat diinteraksikan. */
    .penjualan-tanda-jadi-content .filter-panel .unit-readonly-control {
        color: #667085 !important;
        border-color: #d8dee8 !important;
        background: #f3f6fa !important;
        cursor: not-allowed !important;
        pointer-events: none;
        user-select: none;
        -webkit-user-select: none;
    }


    /* =========================================================
       HEADER ICON CONSISTENCY — MATCH DAFTAR SERTIPIKAT PECAHAN
       Hanya icon kiri atas header yang diubah.
       ========================================================= */
    .page-title-icon.sertipikat-style-heading-icon {
        width: 34px !important;
        height: 34px !important;
        min-width: 34px !important;
        flex: 0 0 34px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border: 0 !important;
        border-radius: 11px !important;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.24) !important;
        font-size: 18px !important;
        font-weight: 700 !important;
        line-height: 1 !important;
    }


    /* =========================================================
       DESKTOP FILTER STRUCTURE
       Susunan mengikuti aplikasi desktop:
       Row 1 : Periode | Agen | Unit
       Row 2 : Lokasi  | Tipe Bayar | Action
       Row 3 : Sektor/Cluster
       Hanya struktur/posisi yang diubah. Style control existing tetap.
       ========================================================= */
    .penjualan-tanda-jadi-content .desktop-filter-layout {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .penjualan-tanda-jadi-content .desktop-filter-row {
        display: grid;
        grid-template-columns:
            minmax(0, 1.7fr)
            minmax(0, 1.15fr)
            minmax(0, 0.95fr);
        gap: 16px;
        align-items: end;
    }

    .penjualan-tanda-jadi-content .desktop-filter-row .filter-field {
        min-width: 0;
        grid-column: auto;
    }

    .penjualan-tanda-jadi-content .desktop-field-actions {
        min-width: 0;
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
        height: 100%;
    }

    .penjualan-tanda-jadi-content .desktop-field-actions .action-buttons {
        width: 100%;
        margin: 0;
        justify-content: flex-end;
    }

    .penjualan-tanda-jadi-content .desktop-filter-row-bottom {
        align-items: center;
    }

    .penjualan-tanda-jadi-content .desktop-field-sector {
        grid-column: 1 / 2;
    }

    .penjualan-tanda-jadi-content .desktop-filter-note {
        grid-column: 2 / 4;
        margin: 0;
        align-self: end;
        color: var(--ptj-muted);
        font-size: 11px;
        text-align: right;
    }

    @media (max-width: 991.98px) {
        .penjualan-tanda-jadi-content .desktop-filter-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .penjualan-tanda-jadi-content .desktop-field-period,
        .penjualan-tanda-jadi-content .desktop-field-sector {
            grid-column: 1 / 3;
        }

        .penjualan-tanda-jadi-content .desktop-field-actions {
            grid-column: 2 / 3;
        }

        .penjualan-tanda-jadi-content .desktop-filter-note {
            grid-column: 1 / 3;
            text-align: left;
        }
    }

    @media (max-width: 767.98px) {
        .penjualan-tanda-jadi-content .desktop-filter-row {
            grid-template-columns: 1fr;
        }

        .penjualan-tanda-jadi-content .desktop-field-period,
        .penjualan-tanda-jadi-content .desktop-field-sector,
        .penjualan-tanda-jadi-content .desktop-field-actions,
        .penjualan-tanda-jadi-content .desktop-filter-note {
            grid-column: 1;
        }

        .penjualan-tanda-jadi-content .desktop-field-actions .action-buttons {
            justify-content: stretch;
        }

        .penjualan-tanda-jadi-content .desktop-filter-note {
            text-align: left;
        }
    }

    /* =========================================================
       TAMPILAN HASIL LAPORAN — MENGIKUTI DAFTAR SERTIFIKAT PECAHAN
       Struktur: 3 grid (header → subtitle → tabel).
       Catatan: HANYA tampilan/gaya. Kolom, urutan kolom, dan isi
       data laporan tidak diubah sama sekali.
       ========================================================= */
    .penjualan-tanda-jadi-content .report-wrapper {
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

    .penjualan-tanda-jadi-content .report-wrapper::before {
        content: "";
        position: absolute;
        top: 0;
        left: 7%;
        right: 7%;
        height: 3px;
        border-radius: 0 0 999px 999px;
        background: linear-gradient(90deg, transparent, #38bdf8, #2563eb, #6366f1, transparent);
    }

    /* --- GRID 1 : HEADER LAPORAN --- */
    .penjualan-tanda-jadi-content .report-header {
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

    .penjualan-tanda-jadi-content .report-company {
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 850;
        letter-spacing: 0.04em;
        line-height: 1.45;
    }

    .penjualan-tanda-jadi-content .report-title {
        margin: 0 !important;
        color: #172033 !important;
        text-align: center !important;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
        font-size: 18px !important;
        font-weight: 950 !important;
        letter-spacing: -0.02em !important;
        line-height: 1.25;
    }

    .penjualan-tanda-jadi-content .report-period {
        color: #475467;
        text-align: right;
        font-size: 10.5px;
        font-weight: 650;
        line-height: 1.55;
    }

    /* --- GRID 2 : BARIS SEKTOR/CLUSTER --- */
    .penjualan-tanda-jadi-content .report-subtitle {
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

    .penjualan-tanda-jadi-content .report-subtitle-label {
        justify-self: start;
        color: #667085;
        white-space: nowrap;
    }

    .penjualan-tanda-jadi-content .report-subtitle-value {
        justify-self: center;
        color: #344054;
        font-weight: 850;
        text-align: center;
    }

    .penjualan-tanda-jadi-content .report-subtitle strong {
        color: #344054;
        font-weight: 850;
    }

    .penjualan-tanda-jadi-content .report-live-badge {
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

    .penjualan-tanda-jadi-content .report-live-badge::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
    }

    /* --- GRID 3 : TABEL LAPORAN --- */
    .penjualan-tanda-jadi-content .report-table-container {
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

    .penjualan-tanda-jadi-content .report-table-container::-webkit-scrollbar {
        width: 11px;
        height: 13px;
    }

    .penjualan-tanda-jadi-content .report-table-container::-webkit-scrollbar-track {
        border-radius: 999px;
        background: #eff3f7;
    }

    .penjualan-tanda-jadi-content .report-table-container::-webkit-scrollbar-thumb {
        border: 2px solid #eff3f7;
        border-radius: 999px;
        background: #93c5fd;
    }

    .penjualan-tanda-jadi-content .report-table-container::-webkit-scrollbar-thumb:hover {
        background: #60a5fa;
    }

    .penjualan-tanda-jadi-content .report-table {
        margin-bottom: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        background: #ffffff !important;
        color: #344054 !important;
        font-size: 10.5px !important;
    }

    .penjualan-tanda-jadi-content .report-table th {
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

    .penjualan-tanda-jadi-content .report-table td {
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

    .penjualan-tanda-jadi-content .report-table tbody tr:nth-child(even) td {
        background: #fbfcfe !important;
    }

    .penjualan-tanda-jadi-content .report-table tbody tr:hover td {
        background: #f0f7ff !important;
    }

    .penjualan-tanda-jadi-content .report-table tbody tr:hover td:first-child {
        color: #1d4ed8 !important;
        box-shadow: inset 4px 0 0 #2563eb !important;
    }

    .penjualan-tanda-jadi-content .report-table .empty-row {
        height: 130px !important;
        color: #64748b !important;
        background: #ffffff !important;
        font-style: normal !important;
        text-align: center !important;
    }

    @media (max-width: 767.98px) {
        .penjualan-tanda-jadi-content .report-header {
            grid-template-columns: minmax(0, 1fr);
            gap: 8px;
            text-align: center;
        }

        .penjualan-tanda-jadi-content .report-period {
            text-align: center;
        }

        .penjualan-tanda-jadi-content .report-subtitle {
            grid-template-columns: minmax(0, 1fr);
            justify-items: center;
            text-align: center !important;
        }

        .penjualan-tanda-jadi-content .report-subtitle-label,
        .penjualan-tanda-jadi-content .report-live-badge {
            justify-self: center;
        }
    }


</style>

<div class="penjualan-tanda-jadi-content">

<div class="modal" id="penjualanTandaJadiAgenModal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header d-flex" style="justify-content:space-between">
                <h5 id="penjualanTandaJadiAgenModalTitle"></h5>
                <button class="btn btn-light" type="button" onclick="toggle_modal()">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="modal-body" id="penjualanTandaJadiAgenModalContent"></div>

        </div>
    </div>
</div>

<div class="page-panel">
    <div class="page-title-wrap">
        <div class="page-title-main">
            <div class="page-title-icon sertipikat-style-heading-icon" aria-hidden="true">◈</div>

            <div class="page-title-copy">
                <h5 class="page-title-text">
                    Daftar Penjualan Per Tanggal Tanda Jadi Per Agen
                </h5>
            </div>
        </div>

        <span class="status-badge">Belum PPJB</span>
    </div>
</div>

<div class="filter-panel">
    <input
        type="hidden"
        id="nama_perusahaan_session"
        value="{{ $namaPerusahaan ?? $nama_perusahaan ?? $namaPt ?? $nama_pt ?? session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? session('nama_lokasi') ?? session('deskripsi_lokasi') ?? session('lokasi') ?? '' }}"
    >

    <div class="filter-panel-header">
        <div>
            <h6 class="filter-panel-heading">Filter Data Penjualan</h6>
            <p class="filter-panel-hint">
                Tentukan cakupan laporan sebelum menampilkan data.
            </p>
        </div>
    </div>

    <div class="filter-panel-body">
        <div class="desktop-filter-layout">

            {{-- ROW 1: seperti desktop -> Periode | Agen | Unit --}}
            <div class="desktop-filter-row desktop-filter-row-top">

                <div class="filter-field filter-field-period desktop-field-period">
                    <label class="filter-label" for="tgl_awal">
                        Periode Tanda Jadi <span class="field-required">*</span>
                    </label>

                    <div class="period-range">
                        <input
                            type="date"
                            id="tgl_awal"
                            class="form-control"
                            aria-label="Tanggal awal periode tanda jadi"
                        >

                        <span class="period-separator" aria-hidden="true">
                            <i class="fas fa-arrow-right"></i>
                        </span>

                        <input
                            type="date"
                            id="tgl_akhir"
                            class="form-control"
                            aria-label="Tanggal akhir periode tanda jadi"
                        >
                    </div>
                </div>

                <div class="filter-field desktop-field-agen">
                    <div class="filter-label">Agen</div>

                    <div class="lookup-control">
                        <input type="hidden" id="agen" value="*****">
                        <div
                            class="form-control lookup-display"
                            id="agenentry"
                            role="button"
                            tabindex="0"
                            onclick="get_agen_modal()"
                            onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); get_agen_modal(); }"
                        >Semua Agen</div>
                        <button
                            class="btn lookup-button"
                            type="button"
                            onclick="get_agen_modal()"
                            title="Cari Agen"
                            aria-label="Cari Agen"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-4-4"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="filter-field filter-field-unit desktop-field-unit">
                    <label class="filter-label" for="perusahaan">Unit</label>

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

            </div>

            {{-- ROW 2: seperti desktop -> Lokasi | Tipe Bayar | Action --}}
            <div class="desktop-filter-row desktop-filter-row-middle">

                <div class="filter-field filter-field-location desktop-field-location">
                    <label class="filter-label" for="lokasi">Lokasi</label>
                    <select id="lokasi" class="form-control">
                        <option value="*">Semua Lokasi</option>
                        @foreach (($lokasiList ?? []) as $lokasi)
                            <option value="{{ $lokasi->KD_LOKASI }}">
                                {{ $lokasi->KD_LOKASI }} - {{ $lokasi->DESKRIPSI }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-field desktop-field-tipe-bayar">
                    <label class="filter-label" for="tipe_bayar">Tipe Bayar</label>
                    <select id="tipe_bayar" class="form-control">
                        <option value="*">Semua Tipe Bayar</option>
                        @foreach (($tipeBayarList ?? []) as $tipeBayar)
                            <option value="{{ $tipeBayar->TIPE_BAYAR }}">
                                {{ $tipeBayar->TIPE_BAYAR }} - {{ $tipeBayar->NAMA }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="desktop-field-actions">
                    <div class="action-buttons">
                        <button type="button" class="btn action-btn action-btn-view" onclick="getSummary()">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
                                <circle cx="12" cy="12" r="2.5"></circle>
                            </svg>
                            <span>View</span>
                        </button>

                        <button type="button" class="btn action-btn action-btn-excel" onclick="printPenjualanTandaJadiReport()">
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

            {{-- ROW 3: seperti desktop -> Sektor/Cluster di bawah Lokasi --}}
            <div class="desktop-filter-row desktop-filter-row-bottom">

                <div class="filter-field desktop-field-sector">
                    <div class="filter-label">Sektor/Cluster</div>

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
                            title="Cari Sektor/Cluster"
                            aria-label="Cari Sektor/Cluster"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-4-4"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <p class="desktop-filter-note">
                    Kolom bertanda <span class="field-required">*</span> wajib diisi.
                </p>

            </div>

        </div>
    </div>
</div>

<div class="result-panel">
    <div id="loading-info" style="display:none;">
        <i class="fas fa-spinner fa-spin"></i>
        Mengambil data...
    </div>

    <div id="main-display" class="result-wrapper">
        <div class="empty-state-panel">
            <i class="fas fa-table"></i>
            <div>
                Silakan tentukan periode dan filter, lalu klik <strong>View</strong>.
            </div>
        </div>
    </div>
</div>

</div>

@endsection

@section('js')

<script>
    var activeSummaryRequest = null;
    var summaryRequestSequence = 0;

    $(document).ready(function () {
        initializeReportPageState();

        $('#lokasi, #tipe_bayar').on('change', function () {
            cancelActiveSummaryRequest();
            clearSummaryReport();
        });
    });

    /*
     * Firefox dapat memulihkan hidden input / DOM dari history atau BFCache.
     * Akibatnya label filter bisa kembali ke "Semua Sektor", tetapi hasil
     * laporan lama masih berasal dari sektor sebelumnya.
     *
     * pageshow dipakai supaya state filter dan report selalu sinkron setelah
     * reload, back/forward, maupun restore BFCache.
     */
    window.addEventListener('pageshow', function () {
        initializeReportPageState();

        /*
         * Re-apply sekali lagi setelah browser selesai restore form state.
         * Tidak melakukan request data, hanya mengembalikan filter ke default.
         */
        window.requestAnimationFrame(function () {
            resetAllFilterState();
            clearSummaryReport();
        });
    });

    function initializeReportPageState() {
        cancelActiveSummaryRequest();

        /*
         * Setiap halaman dibuka ulang / refresh / restore BFCache,
         * kembalikan SELURUH filter ke kondisi awal halaman.
         */
        resetAllFilterState();
        clearSummaryReport();
    }

    function resetAllFilterState() {
        /*
         * Periode kembali ke tanggal hari ini seperti kondisi awal halaman.
         * Ini sengaja dijalankan juga pada event pageshow karena Firefox dapat
         * memulihkan nilai input tanggal sebelumnya setelah refresh/BFCache.
         */
        setDefaultDate();

        $('#sektor').val('*');
        $('#sektorentry').text('Semua Sektor');

        $('#agen').val('*****');
        $('#agenentry').text('Semua Agen');

        $('#lokasi').val('*');
        $('#tipe_bayar').val('*');
    }

    function clearSummaryReport() {
        $('#loading-info').hide();

        $('#main-display').html(
            '<div class="empty-state-panel">' +
                '<i class="fas fa-table"></i>' +
                '<div>Silakan tentukan periode dan filter, lalu klik <strong>View</strong>.</div>' +
            '</div>'
        );
    }

    function cancelActiveSummaryRequest() {
        /*
         * Naikkan sequence juga saat reset. Dengan begitu response lama yang
         * kebetulan selesai setelah reload/reset tidak boleh dirender.
         */
        summaryRequestSequence++;

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
            lokasi: $('#lokasi').val() || '*',
            sektor: $('#sektor').val() || '*',
            agen: $('#agen').val() || '*****',
            tipe_bayar: $('#tipe_bayar').val() || '*',
            perusahaan: $('#perusahaan').val()
        };
    }

    function toggle_modal() {
        $('#penjualanTandaJadiAgenModal').modal('toggle');
    }

    function insert_modal(title, content) {
        var finalContent = `
            <div class="modal-search-wrapper">
                <div class="modal-search-box">
                    <span class="modal-search-icon">
                        <i class="fas fa-search"></i>
                    </span>

                    <input
                        type="text"
                        id="modalSearchInput"
                        class="form-control"
                        placeholder="Cari berdasarkan kode atau nama..."
                        onkeyup="filter_modal_table(this.value)"
                        autocomplete="off"
                    >
                </div>

                <small class="modal-search-hint">
                    Klik salah satu baris untuk memilih data.
                </small>
            </div>

            <div class="modal-table-wrapper">
                ${content}
            </div>
        `;

        $('#penjualanTandaJadiAgenModalContent').html(finalContent);
        $('#penjualanTandaJadiAgenModalTitle').html(title);

        setTimeout(function() {
            $('#modalSearchInput').focus();
        }, 300);
    }

    function filter_modal_table(keyword) {
        keyword = String(keyword || '').toLowerCase();

        $('#penjualanTandaJadiAgenModal .modal-table tr').each(function(index) {
            if (index === 0) {
                $(this).show();
                return;
            }

            var rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.indexOf(keyword) > -1);
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
        $('#penjualanTandaJadiAgenModal').modal('toggle');

        /*
         * Setelah sektor berubah, report yang sedang terlihat sudah tidak
         * merepresentasikan filter aktif. Bersihkan sampai user klik View.
         */
        cancelActiveSummaryRequest();
        clearSummaryReport();
    }

    function add_agen(value, descriptor) {
        $('#agen').val(value);
        $('#agenentry').text(descriptor || 'Semua Agen');
        $('#penjualanTandaJadiAgenModal').modal('toggle');

        cancelActiveSummaryRequest();
        clearSummaryReport();
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

    function get_agen_modal() {
        $.ajax({
            method: 'GET',
            url: '{{ url()->current() }}/get_agen',
            success: function (data) {
                var html = '';

                html += '<table class="modal-table table table-bordered">';
                html += '<tr>';
                html += '<th>Kode Agen</th>';
                html += '<th>Nama Agen</th>';
                                html += '</tr>';

                html += '<tr onclick="add_agen(\'*****\', \'Semua Agen\')">';
                html += '<td>*****</td>';
                html += '<td>Semua Agen</td>';
                                html += '</tr>';

                if (data && data.length > 0) {
                    $.each(data, function (index, item) {
                        var kode = item.KD_AGEN == null ? '' : item.KD_AGEN;
                        var namaAgen = item.NAMA_AGEN == null ? '' : item.NAMA_AGEN;
                        html += '<tr onclick="add_agen(\'' + escapeJs(kode) + '\', \'' + escapeJs(namaAgen) + '\')">';
                        html += '<td>' + escapeHtml(kode) + '</td>';
                        html += '<td>' + escapeHtml(namaAgen) + '</td>';
                        html += '</tr>';
                    });
                }

                html += '</table>';

                insert_modal('Pilih Agen', html);
                toggle_modal();
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                alert('Gagal mengambil data agen.');
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

    /*
     * Nilai dianggap nama panjang perusahaan bila BUKAN sekadar kode unit.
     * Kode unit (mis. "DTSA", "CGTK") ditolak agar header tidak menampilkan
     * singkatan seperti sebelumnya.
     */
    function isLongCompanyName(value, unit) {
        var name = String(value === null || value === undefined ? '' : value).trim();

        if (name === '') {
            return false;
        }

        if (unit && name.toUpperCase() === String(unit).toUpperCase()) {
            return false;
        }

        // Kode unit umumnya satu kata pendek tanpa spasi.
        return /\s/.test(name) || name.length > 8;
    }

    /*
     * Sumber terakhir: teks header aplikasi yang selalu memuat baris
     * "Unit : DTSA & Lokasi : PDSA - PT. Duta Sumara Abadi".
     * Inilah string yang dibaca extractCompanyName() pada view Daftar
     * Sertifikat Pecahan sehingga di sana nama PT bisa tampil penuh.
     */
    function escapeRegExp(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /*
     * Kumpulkan teks halaman yang berpotensi memuat identitas unit aktif.
     * Nilai <input> ikut dibaca karena kotak unit pada topbar aplikasi
     * berupa input readonly berisi
     * "Unit : DTSA & Lokasi : PDSA - PT. Duta Sumara Abadi",
     * dan jQuery .text() tidak membaca value input.
     */
    function collectUnitTextCandidates() {
        var candidates = [];

        function push(value) {
            var text = String(value === null || value === undefined ? '' : value).trim();

            if (text !== '') {
                candidates.push(text);
            }
        }

        $('input, textarea').not('.penjualan-tanda-jadi-content, script, style, noscript').each(function () {
            push($(this).val());
        });

        $('[title]').not('.penjualan-tanda-jadi-content, script, style, noscript').each(function () {
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
                $clone.find('.penjualan-tanda-jadi-content, script, style, noscript').remove();
                push($clone.text());
            });
        }

        return candidates;
    }

    /*
     * Ambil nama PT HANYA dari teks yang menyebut kode unit yang sedang
     * dipilih. Pencarian bebas di seluruh halaman tidak dipakai karena
     * layout juga memuat nama grup induk (mis. pada sidebar/footer),
     * sehingga nama yang terbaca bisa bukan milik unit aktif.
     */
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

    /*
     * Header memakai nama panjang perusahaan (nama PT) milik unit aktif,
     * bukan singkatan unit dan bukan nama grup induk.
     * Urutan sumber:
     *   1. kolom nama PT pada baris data hasil query;
     *   2. teks halaman yang menyebut kode unit aktif (topbar "Unit : ... &
     *      Lokasi : ... - PT ...");
     *   3. variabel controller / session (hidden input);
     *   4. kode unit, hanya bila ketiganya kosong.
     */
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

        /*
         * Batalkan request sebelumnya agar response filter lama tidak dapat
         * menimpa report filter terbaru.
         */
        if (activeSummaryRequest && activeSummaryRequest.readyState !== 4) {
            activeSummaryRequest.abort();
        }

        var requestSequence = ++summaryRequestSequence;
        var filterData = getFilterData();

        /*
         * Simpan snapshot filter saat request dikirim.
         * renderTable tidak lagi membaca label filter yang mungkin sudah berubah
         * ketika response selesai.
         */
        var renderContext = {
            tgl_awal: filterData.tgl_awal,
            tgl_akhir: filterData.tgl_akhir,
            lokasi_text: $('#lokasi option:selected').text() || 'Semua Lokasi',
            sektor_text: $.trim($('#sektorentry').text()) || 'Semua Sektor',
            company_session_text: String($('#nama_perusahaan_session').val() || '').trim()
        };

        $('#loading-info').show();
        $('#main-display').html('');

        activeSummaryRequest = $.ajax({
            url: '{{ url()->current() }}/get_summary',
            type: 'POST',
            dataType: 'json',
            data: filterData,
            success: function (data) {
                if (requestSequence !== summaryRequestSequence) {
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

                if (requestSequence !== summaryRequestSequence) {
                    return;
                }

                $('#loading-info').hide();
                activeSummaryRequest = null;
                console.log(xhr.responseText);
                $('#main-display').html('<div class="alert alert-danger">Gagal mengambil data. Cek console atau log Laravel.</div>');
            }
        });
    }

    function printPenjualanTandaJadiReport() {
        if (!$('#main-display .report-wrapper').length) {
            alert('Silakan klik View terlebih dahulu untuk menampilkan laporan.');
            return;
        }

        var reportHtml = $('#main-display').html();

        if (!reportHtml) {
            return;
        }

        /*
         * Mekanisme print disamakan dengan fitur sebelumnya:
         * - hidden iframe
         * - tidak membuka tab/window baru
         * - langsung native print dialog browser
         * - orientation dan paper size tidak dikunci dari CSS
         */
        $('#penjualanTandaJadiNativePrintFrame').remove();

        var frame = document.createElement('iframe');
        frame.id = 'penjualanTandaJadiNativePrintFrame';
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
         * Tidak memakai @page size supaya dialog browser tetap menampilkan:
         * Portrait/Landscape, Paper Size A4/A3, Scale, Margins, Pages per sheet.
         */
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

            /* GRID 1 — HEADER */
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
                font-size: 8px !important;
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
                font-size: 7.5px !important;
                font-weight: 500 !important;
                line-height: 1.35 !important;
            }

            /* GRID 2 — BARIS SEKTOR/CLUSTER */
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
                font-size: 7.5px !important;
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
                font-size: 7px !important;
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


            /* GRID 3 — TABEL */
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
                table-layout: fixed !important;
                border-collapse: collapse !important;
                border-spacing: 0 !important;
                border: 1px solid #000 !important;
                background: #fff !important;
                color: #000 !important;
                font-size: 5.2px !important;
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
                overflow-wrap: anywhere !important;
                word-break: normal !important;
                vertical-align: middle !important;
                font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
                font-size: 5px !important;
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

            .report-table .empty-row {
                height: 35px !important;
                text-align: center !important;
                color: #000 !important;
                background: #fff !important;
                font-style: italic !important;
            }
        `;

        frameDocument.open();
        frameDocument.write(
            '<!DOCTYPE html>'
            + '<html>'
            + '<head>'
            + '<meta charset="utf-8">'
            + '<meta name="viewport" content="width=device-width,initial-scale=1">'
            + '<title>Daftar Penjualan Per Tanggal Surat Pesanan</title>'
            + '<style>' + printCss + '</style>'
            + '</head>'
            + '<body>' + reportHtml + '</body>'
            + '</html>'
        );
        frameDocument.close();

        var cleanupPrintFrame = function () {
            $('#penjualanTandaJadiNativePrintFrame').remove();
        };

        try {
            frameWindow.onafterprint = cleanupPrintFrame;
        } catch (error) {
            // Cleanup fallback tetap dijalankan di timeout.
        }

        window.setTimeout(function () {
            try {
                frameWindow.focus();
                frameWindow.print();
            } catch (error) {
                console.error('Gagal membuka dialog print Daftar Penjualan:', error);
                cleanupPrintFrame();
                alert('Dialog print gagal dibuka. Silakan coba kembali.');
            }
        }, 180);

        window.setTimeout(cleanupPrintFrame, 30000);
    }

    function renderTable(data, context) {
        context = context || {};

        var tglAwal = formatDateIndo(context.tgl_awal || $('#tgl_awal').val());
        var tglAkhir = formatDateIndo(context.tgl_akhir || $('#tgl_akhir').val());
        var lokasiText = context.lokasi_text || $('#lokasi option:selected').text() || 'Semua';
        var sektorText = context.sektor_text || $.trim($('#sektorentry').text()) || 'Semua Sektor';

        var companyText = resolveReportCompany(
            (data && data.length > 0) ? data[0] : {},
            context.company_session_text
        );

        var html = '';

        html += '<div class="report-wrapper">';

        /*
         * GRID 1 — HEADER LAPORAN
         * Kiri: identitas perusahaan unit aktif. Tengah: judul laporan.
         * Kanan: periode tanda jadi dan lokasi (isi sama dengan keterangan
         * yang sebelumnya berdiri sendiri di atas tabel).
         */
        html += '<div class="report-header">';
        html += '<div class="report-company">' + escapeHtml(companyText) + '</div>';
        html += '<div class="report-title">DAFTAR PENJUALAN PER TANGGAL SURAT PESANAN</div>';
        html += '<div class="report-period">';
        html += 'Tanggal Tanda Jadi: ' + escapeHtml(tglAwal) + ' s.d ' + escapeHtml(tglAkhir);
        html += '<br>Lokasi: ' + escapeHtml(lokasiText);
        html += '</div>';
        html += '</div>';

        /*
         * GRID 2 — BARIS SEKTOR/CLUSTER
         * Label di kiri, nilai sektor di tengah, badge status di kanan.
         */
        html += '<div class="report-subtitle">';
        html += '<span class="report-subtitle-label">Sektor/Cluster:</span>';
        html += '<span class="report-subtitle-value">' + escapeHtml(sektorText) + '</span>';
        html += '<span class="report-live-badge">Live data</span>';
        html += '</div>';

        /*
         * GRID 3 — TABEL LAPORAN
         * Kolom, urutan, isi data, dan baris total tidak diubah.
         */
        html += '<div class="report-table-container">';
        html += '<table class="table table-bordered report-table">';
        html += '<thead>';
        html += '<tr>';
        html += '<th>No.</th>';
        html += '<th>Blok/<br>Nomor</th>';
        html += '<th>Tanggal<br>Tanda Jadi</th>';
        html += '<th>Tipe</th>';
        html += '<th>Nama<br>Pembeli</th>';
        html += '<th>L.Tanah<br>(M2)</th>';
        html += '<th>L.Bgn<br>(M2)</th>';
        html += '<th>Harga Pokok<br>(Rp.)</th>';
        html += '<th>Discount<br>(Rp.)</th>';
        html += '<th>Biaya-Biaya<br>(Rp.)</th>';
        html += '<th>PPN<br>(Rp.)</th>';
        html += '<th>Total Harga<br>Jual (Rp.)</th>';
        html += '<th>Jenis<br>Bayar</th>';
        html += '<th>Nama<br>Agen</th>';
        html += '<th>Sales</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';

        if (!data || data.length === 0) {
            html += '<tr>';
            html += '<td colspan="15" class="empty-row">Data tidak ditemukan.</td>';
            html += '</tr>';
        } else {
            var totalHargaPokok = 0;
            var totalDiscount = 0;
            var totalBiaya = 0;
            var totalPpn = 0;
            var totalHargaJual = 0;

            $.each(data, function (index, item) {
                totalHargaPokok += parseNumber(item.HARGA_RUMAH);
                totalDiscount += parseNumber(item.DISCOUNT);
                totalBiaya += parseNumber(item.BIAYA);
                totalPpn += parseNumber(item.PPN);
                totalHargaJual += parseNumber(item.HARGA_JUAL);

                html += '<tr>';
                html += '<td style="text-align:center;">' + (index + 1) + '.</td>';
                html += '<td>' + escapeHtml(valueOrEmpty(item.STOK_BLOK)) + '</td>';
                html += '<td style="text-align:center;">' + formatDateIndo(item.TGL_UANG_MUKA) + '</td>';
                html += '<td>' + escapeHtml(valueOrEmpty(item.TIPE_BANGUNAN)) + '</td>';
                html += '<td>' + escapeHtml(cleanCustomerName(item.NASABAH_NAMA)) + '</td>';
                html += '<td style="text-align:right;">' + formatNumber(item.LUAS_TANAH) + '</td>';
                html += '<td style="text-align:right;">' + formatNumber(item.LUAS_BANGUNAN) + '</td>';
                html += '<td style="text-align:right;">' + formatNumber(item.HARGA_RUMAH) + '</td>';
                html += '<td style="text-align:right;">' + formatNumber(item.DISCOUNT) + '</td>';
                html += '<td style="text-align:right;">' + formatNumber(item.BIAYA) + '</td>';
                html += '<td style="text-align:right;">' + formatNumber(item.PPN) + '</td>';
                html += '<td style="text-align:right;">' + formatNumber(item.HARGA_JUAL) + '</td>';
                html += '<td>' + escapeHtml(valueOrEmpty(item.TIPE_PEMBAYARAN)) + '</td>';
                html += '<td>' + escapeHtml(valueOrEmpty(item.NAMA_AGEN)) + '</td>';
                html += '<td>' + escapeHtml(valueOrEmpty(item.NAMA_SALES)) + '</td>';
                html += '</tr>';
            });

            html += '<tr>';
            html += '<td colspan="4" style="text-align:right;font-weight:bold;">TOTAL PENJUALAN :</td>';
            html += '<td style="text-align:center;font-weight:bold;">' + data.length + '</td>';
            html += '<td style="font-weight:bold;">UNIT</td>';
            html += '<td></td>';
            html += '<td style="text-align:right;font-weight:bold;border-top:2px solid #333;">' + formatNumber(totalHargaPokok) + '</td>';
            html += '<td style="text-align:right;font-weight:bold;border-top:2px solid #333;">' + formatNumber(totalDiscount) + '</td>';
            html += '<td style="text-align:right;font-weight:bold;border-top:2px solid #333;">' + formatNumber(totalBiaya) + '</td>';
            html += '<td style="text-align:right;font-weight:bold;border-top:2px solid #333;">' + formatNumber(totalPpn) + '</td>';
            html += '<td style="text-align:right;font-weight:bold;border-top:2px solid #333;">' + formatNumber(totalHargaJual) + '</td>';
            html += '<td colspan="3"></td>';
            html += '</tr>';
        }

        html += '</tbody>';
        html += '</table>';
        html += '</div>';
        html += '</div>';

        $('#main-display').html(html);
    }



    function cleanCustomerName(value) {
        if (value === null || value === undefined) {
            return '';
        }

        var name = String(value).trim();

        
        if (name.length >= 2 && name.charAt(0) === '(' && name.charAt(name.length - 1) === ')') {
            name = name.substring(1, name.length - 1).trim();
        }

    
        name = name
            .replace(/^["']+/, '')
            .replace(/["']+$/, '')
            .trim();

        
        name = name.replace(/""/g, '"');

        return name;
    }

    function valueOrEmpty(value) {
        if (value === null || value === undefined) {
            return '';
        }

        return value;
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
</script>

@endsection
