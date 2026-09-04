@extends('layouts.template')

@section('content')

<style>
    .filter-label {
        font-size: 13px;
        font-weight: 600;
        width: 140px;
    }

    .data-table th {
        background-color: gray;
        color: white;
        text-align: center;
        font-size: 12px;
        vertical-align: middle;
        white-space: nowrap;
    }

    .data-table td {
        font-size: 12px;
        vertical-align: middle;
        white-space: nowrap;
    }

    .lookup-row {
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .result-wrapper {
        overflow-x: visible;
        max-width: 100%;
    }

    .lokasi-select {
    height: 34px;
    font-size: 13px;
    max-width: 100%;
    }

    .surat-pesanan-content {
        width: 100%;
        padding: 0;
        margin: 0;
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

    .modal-table tr:hover {
        background-color: lightyellow;
    }

    .lookup-control {
        display: flex;
        flex-grow: 1;
        gap: 5px;
        width: 100%;
    }

    .lookup-display {
        flex-grow: 1;
        min-height: 38px;
        background-color: #fff;
        display: flex;
        align-items: center;
        overflow: hidden;
        white-space: nowrap;
    }

    .lookup-button {
        min-width: 45px;
    }

    .modal-search-wrapper {
        margin-bottom: 12px;
    }

    .modal-table-wrapper {
        max-height: 430px;
        overflow-y: auto;
        overflow-x: auto;
    }

    .modal-table tbody tr,
    .modal-table tr {
        cursor: pointer;
    }

    .modal-table tr:hover td {
        background-color: #ffffcc;
    }


    .report-wrapper {
        background: #fff;
        padding: 24px 20px;
        min-height: 420px;
        font-family: Arial, sans-serif;
    }

    .report-title {
        text-align: center;
        font-weight: bold;
        font-size: 15px;
        margin-bottom: 8px;
    }

    .report-subtitle {
        text-align: center;
        font-size: 13px;
        margin-bottom: 4px;
    }

    .report-info {
        margin-top: 22px;
        margin-bottom: 10px;
        font-size: 12px;
        font-weight: bold;
    }

    .report-info table td {
        padding: 2px 8px 2px 0;
    }

    .report-table-container {
        width: 100%;
        max-width: 100%;
        max-height: calc(100vh - 200px);
        min-height: 260px;
        overflow: auto;
        margin-top: 6px;
        padding-bottom: 4px;
    }

    .report-table {
        width: max-content;
        min-width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        background: white;
    }

    .report-table-container::-webkit-scrollbar {
        height: 14px;
    }

    .report-table-container::-webkit-scrollbar-track {
        background: #eeeeee;
    }

    .report-table-container::-webkit-scrollbar-thumb {
        background: #b5b5b5;
        border-radius: 8px;
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
    }


    
    #suratPesananModal .modal-dialog {
        width: min(920px, calc(100vw - 32px));
        max-width: 920px;
        margin: 24px auto;
    }

    #suratPesananModal .modal-content {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
    }

    #suratPesananModal .modal-header {
        min-height: 66px;
        padding: 16px 20px;
        align-items: center;
        border-bottom: 1px solid #e8edf5;
        background: linear-gradient(135deg, #ffffff 0%, #f5f8ff 100%);
    }

    #suratPesananModalTitle {
        margin: 0;
        color: #172033;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: -0.2px;
    }

    #suratPesananModalTitle::before {
        content: "\f03a";
        display: inline-flex;
        width: 34px;
        height: 34px;
        margin-right: 10px;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        color: #ffffff;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.24);
        font-family: "Font Awesome 5 Free";
        font-size: 14px;
        font-weight: 900;
        vertical-align: middle;
    }

    #suratPesananModal .modal-header .btn-light {
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

    #suratPesananModal .modal-header .btn-light:hover {
        color: #b42318;
        border-color: #fecdca;
        background: #fff4f3;
        transform: translateY(-1px);
    }

    #suratPesananModal .modal-body {
        padding: 18px 20px 20px;
        background: #f8fafc;
    }

    #suratPesananModal .modal-search-wrapper {
        position: relative;
        margin-bottom: 14px;
    }

    #suratPesananModal .modal-search-box {
        position: relative;
    }

    #suratPesananModal .modal-search-icon {
        position: absolute;
        top: 50%;
        left: 14px;
        z-index: 2;
        color: #667085;
        transform: translateY(-50%);
        pointer-events: none;
    }

    #suratPesananModal #modalSearchInput {
        height: 44px;
        padding: 9px 14px 9px 42px;
        color: #172033;
        border: 1px solid #d7deea;
        border-radius: 11px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        font-size: 13px;
        transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }

    #suratPesananModal #modalSearchInput:focus {
        border-color: #2563eb;
        outline: none;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    #suratPesananModal .modal-search-hint {
        display: block;
        margin-top: 7px;
        color: #7b8798;
        font-size: 11px;
    }

    #suratPesananModal .modal-table-wrapper {
        max-height: 430px;
        overflow: auto;
        border: 1px solid #e1e7f0;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    #suratPesananModal .modal-table-wrapper::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    #suratPesananModal .modal-table-wrapper::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    #suratPesananModal .modal-table-wrapper::-webkit-scrollbar-thumb {
        border: 2px solid #f1f5f9;
        border-radius: 999px;
        background: #aeb9c8;
    }

    #suratPesananModal .modal-table {
        width: 100%;
        min-width: 620px;
        margin: 0;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
        color: #344054;
        background: #ffffff;
    }

    #suratPesananModal .modal-table th {
        position: sticky;
        top: 0;
        z-index: 3;
        padding: 13px 14px;
        color: #ffffff;
        border: 0 !important;
        border-right: 1px solid rgba(255, 255, 255, 0.14) !important;
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
        font-size: 12px;
        font-weight: 700;
        text-align: left;
        letter-spacing: 0.15px;
        white-space: nowrap;
    }

    #suratPesananModal .modal-table th:first-child {
        border-top-left-radius: 10px;
    }

    #suratPesananModal .modal-table th:last-child {
        border-right: 0 !important;
        border-top-right-radius: 10px;
    }

    #suratPesananModal .modal-table td {
        padding: 12px 14px;
        color: #344054;
        border: 0 !important;
        border-right: 1px solid #edf1f6 !important;
        border-bottom: 1px solid #edf1f6 !important;
        background: #ffffff;
        font-size: 12px;
        line-height: 1.45;
        text-align: left;
        vertical-align: middle;
        white-space: normal;
        word-break: break-word;
        transition: background-color 0.16s ease, color 0.16s ease;
    }

    #suratPesananModal .modal-table td:first-child {
        color: #1d4ed8;
        font-weight: 700;
    }

    #suratPesananModal .modal-table td:last-child {
        border-right: 0 !important;
    }

    #suratPesananModal .modal-table tr:nth-child(even) td {
        background: #f8fafc;
    }

    #suratPesananModal .modal-table tr:not(:first-child) {
        cursor: pointer;
    }

    #suratPesananModal .modal-table tr:not(:first-child):hover td {
        color: #17336b;
        background: #eaf2ff !important;
    }

    #suratPesananModal .modal-table tr:not(:first-child):active td {
        background: #dbeafe !important;
    }

    #suratPesananModal .modal-table tr:last-child td:first-child {
        border-bottom-left-radius: 10px;
    }

    #suratPesananModal .modal-table tr:last-child td:last-child {
        border-bottom-right-radius: 10px;
    }

    #suratPesananModal .modal-empty-state {
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

    #suratPesananModal .modal-empty-state i {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        border-radius: 14px;
        background: #eaf2ff;
        font-size: 20px;
    }

    #suratPesananModal .modal-empty-state strong {
        color: #344054;
        font-size: 14px;
    }

    @media (max-width: 767.98px) {
        #suratPesananModal .modal-dialog {
            width: calc(100vw - 20px);
            margin: 10px auto;
        }

        #suratPesananModal .modal-header,
        #suratPesananModal .modal-body {
            padding-left: 14px;
            padding-right: 14px;
        }

        #suratPesananModal .modal-table {
            min-width: 560px;
        }
    }


    

    .surat-pesanan-content {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        color: #263449;
    }

    .surat-pesanan-content .filter-label {
        color: #344054;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, sans-serif;
        font-weight: 600;
        letter-spacing: 0.05px;
    }

    .surat-pesanan-content .lookup-row {
        margin-bottom: 10px;
    }

    .surat-pesanan-content .form-control,
    .surat-pesanan-content select.form-control,
    .surat-pesanan-content input.form-control,
    .surat-pesanan-content .lookup-display {
        min-height: 38px;
        color: #27364b;
        border: 1px solid #d4dce8;
        border-radius: 9px;
        background-color: #ffffff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 13px;
        transition:
            border-color 0.18s ease,
            box-shadow 0.18s ease,
            background-color 0.18s ease;
    }

    .surat-pesanan-content .form-control:hover,
    .surat-pesanan-content .lookup-display:hover {
        border-color: #aebbd0;
    }

    .surat-pesanan-content .form-control:focus,
    .surat-pesanan-content select.form-control:focus,
    .surat-pesanan-content input.form-control:focus {
        border-color: #3b82f6;
        outline: none;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.13);
    }

    .surat-pesanan-content input[readonly].form-control {
        color: #667085;
        border-color: #d8dee8;
        background-color: #f3f6fa;
        cursor: not-allowed;
    }

    .surat-pesanan-content select.form-control {
        cursor: pointer;
    }

    .surat-pesanan-content .lookup-button {
        min-width: 45px;
        min-height: 38px;
        color: #ffffff;
        border: 1px solid #34445e;
        border-radius: 9px;
        background: linear-gradient(135deg, #475569 0%, #334155 100%);
        box-shadow: 0 3px 8px rgba(51, 65, 85, 0.18);
        transition:
            transform 0.16s ease,
            box-shadow 0.16s ease,
            background 0.16s ease;
    }

    .surat-pesanan-content .lookup-button:hover,
    .surat-pesanan-content .lookup-button:focus {
        color: #ffffff;
        border-color: #1e3a5f;
        background: linear-gradient(135deg, #315b8d 0%, #25466e 100%);
        box-shadow: 0 5px 13px rgba(37, 70, 110, 0.24);
        transform: translateY(-1px);
    }

    .surat-pesanan-content .lookup-button:active {
        transform: translateY(0);
    }

    .surat-pesanan-content input[type="radio"],
    .surat-pesanan-content input[type="checkbox"] {
        width: 15px;
        height: 15px;
        margin-right: 4px;
        accent-color: #2563eb;
        vertical-align: -2px;
        cursor: pointer;
    }

    .surat-pesanan-content label {
        color: #344054;
        font-size: 13px;
        cursor: pointer;
    }

    #loading-info {
        padding: 12px 14px;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        border-radius: 9px;
        background: #eff6ff;
        font-weight: 600;
    }

    #main-display > p {
        margin: 0;
        padding: 4px 0;
        color: #667085;
    }

    .report-wrapper {
        color: #263449;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    }

    .report-title {
        color: #172033;
        font-family: Cambria, Georgia, "Times New Roman", serif;
        font-size: 17px;
        font-weight: 700;
        letter-spacing: 0.35px;
    }

    .report-subtitle {
        color: #475467;
        font-size: 12px;
        font-weight: 500;
    }

    .report-info {
        color: #344054;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, sans-serif;
        font-weight: 600;
    }

    .report-table-container {
        border: 1px solid #d8e0eb;
        border-radius: 11px;
        background: #ffffff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .report-table-container::-webkit-scrollbar {
        width: 11px;
        height: 13px;
    }

    .report-table-container::-webkit-scrollbar-track {
        border-radius: 999px;
        background: #eef2f7;
    }

    .report-table-container::-webkit-scrollbar-thumb {
        border: 2px solid #eef2f7;
        border-radius: 999px;
        background: #9aa8ba;
    }

    .report-table-container::-webkit-scrollbar-thumb:hover {
        background: #7d8ca1;
    }

    .report-table th {
        position: sticky;
        top: 0;
        z-index: 3;
        padding: 9px 8px;
        color: #ffffff !important;
        border: 1px solid #31405a !important;
        background: #172238 !important;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, sans-serif;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.12px;
        line-height: 1.3;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }

    .report-table td {
        padding: 8px 8px;
        color: #344054;
        border: 1px solid #dde4ee !important;
        background: #ffffff;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 11px;
        line-height: 1.35;
        vertical-align: middle;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        transition: background-color 0.14s ease;
    }

    .report-table tbody tr:nth-child(even) td {
        background: #f8fafc;
    }

    .report-table tbody tr:hover td {
        background: #eaf2ff;
    }

    .report-table tbody tr:hover td:first-child {
        box-shadow: inset 3px 0 0 #2563eb;
    }

    .report-table .empty-row {
        color: #667085;
        background: #f8fafc !important;
        font-size: 12px;
        font-style: normal;
        text-align: center;
    }

    @media (max-width: 767.98px) {
        .surat-pesanan-content .form-control,
        .surat-pesanan-content .lookup-display,
        .surat-pesanan-content .lookup-button {
            min-height: 40px;
        }

        .report-table th,
        .report-table td {
            padding: 7px 6px;
        }
    }


    

    .surat-pesanan-content {
        font-family: inherit;
    }

    .surat-pesanan-content .filter-label,
    .surat-pesanan-content .form-control,
    .surat-pesanan-content select.form-control,
    .surat-pesanan-content input.form-control,
    .surat-pesanan-content .lookup-display,
    .surat-pesanan-content label {
        font-family: inherit;
    }

    .report-wrapper {
        font-family: Arial, sans-serif;
    }

    .report-title,
    .report-subtitle,
    .report-info,
    .report-table th,
    .report-table td {
        font-family: Arial, sans-serif;
    }

    .surat-pesanan-content input[type="radio"],
    .surat-pesanan-content input[type="checkbox"] {
        width: auto;
        height: auto;
        margin-right: 0;
        accent-color: auto;
        vertical-align: middle;
        cursor: default;
    }


    

    .surat-pesanan-content {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        color: #263449;
    }

    .surat-pesanan-content h5 {
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, sans-serif;
        font-weight: 600;
        letter-spacing: 0.1px;
    }

    .surat-pesanan-content .filter-label {
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, sans-serif;
        font-weight: 600;
        color: #344054;
    }

    .surat-pesanan-content .form-control,
    .surat-pesanan-content select,
    .surat-pesanan-content input,
    .surat-pesanan-content button,
    .surat-pesanan-content label,
    .surat-pesanan-content .lookup-display {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    }

    .report-wrapper {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    }

    .report-title {
        font-family: Cambria, Georgia, "Times New Roman", serif;
        font-size: 17px;
        font-weight: 700;
        letter-spacing: 0.45px;
    }

    .report-subtitle {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-weight: 500;
    }

    .report-info,
    .report-table th {
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, sans-serif;
        font-weight: 600;
    }

    .report-table td {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-weight: 400;
        font-variant-numeric: tabular-nums;
    }

    

    .surat-pesanan-content input[type="radio"] {
        width: 16px;
        height: 16px;
        margin-right: 5px;
        accent-color: #2563eb;
        vertical-align: -3px;
        cursor: pointer;
    }

    

    .modern-checkbox-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 155px;
        min-height: 35px;
        margin: 0 0 7px 0 !important;
        padding: 7px 11px;
        border: 1px solid #d6deea;
        border-radius: 9px;
        background: #ffffff;
        color: #344054;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, sans-serif !important;
        font-size: 12px !important;
        font-weight: 600;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        cursor: pointer !important;
        transition:
            border-color 0.18s ease,
            background-color 0.18s ease,
            box-shadow 0.18s ease,
            transform 0.18s ease;
    }

    .modern-checkbox-label:hover {
        border-color: #9db6da;
        background: #f8fbff;
        box-shadow: 0 3px 9px rgba(37, 99, 235, 0.08);
    }

    .modern-checkbox-label input[type="checkbox"] {
        appearance: none;
        -webkit-appearance: none;
        width: 18px;
        height: 18px;
        flex: 0 0 18px;
        margin: 0;
        border: 1.5px solid #aab6c6;
        border-radius: 5px;
        background-color: #ffffff;
        background-position: center;
        background-repeat: no-repeat;
        background-size: 12px 12px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.45);
        cursor: pointer;
        transition:
            border-color 0.16s ease,
            background-color 0.16s ease,
            box-shadow 0.16s ease,
            transform 0.12s ease;
    }

    .modern-checkbox-label input[type="checkbox"]:hover {
        border-color: #3b82f6;
    }

    .modern-checkbox-label input[type="checkbox"]:checked {
        border-color: #2563eb;
        background-color: #2563eb;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='white' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.3' d='M3.2 8.2l3 3.1 6.6-7'/%3E%3C/svg%3E");
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .modern-checkbox-label input[type="checkbox"]:focus-visible {
        outline: none;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.16);
    }

    .modern-checkbox-label input[type="checkbox"]:active {
        transform: scale(0.92);
    }

    .modern-checkbox-label:has(input[type="checkbox"]:checked) {
        border-color: #93b4ea;
        background: #eff6ff;
        color: #1d4ed8;
        box-shadow: 0 3px 10px rgba(37, 99, 235, 0.11);
    }


    

    .modern-radio-group {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        min-width: 0;
    }

    .modern-radio-group-vertical {
        align-items: flex-start;
        flex-direction: column;
        gap: 7px;
    }

    .modern-radio-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 34px;
        margin: 0 !important;
        padding: 7px 11px;
        color: #344054;
        border: 1px solid #d6deea;
        border-radius: 9px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, sans-serif !important;
        font-size: 12px !important;
        font-weight: 600;
        line-height: 1.2;
        white-space: nowrap;
        cursor: pointer !important;
        transition:
            border-color 0.18s ease,
            background-color 0.18s ease,
            color 0.18s ease,
            box-shadow 0.18s ease,
            transform 0.16s ease;
    }

    .modern-radio-label-compact {
        min-width: 74px;
        justify-content: center;
    }

    .modern-radio-label:hover {
        color: #1d4ed8;
        border-color: #9db6da;
        background: #f8fbff;
        box-shadow: 0 3px 9px rgba(37, 99, 235, 0.08);
        transform: translateY(-1px);
    }

    .modern-radio-label input[type="radio"] {
        appearance: none;
        -webkit-appearance: none;
        width: 18px;
        height: 18px;
        flex: 0 0 18px;
        margin: 0;
        border: 1.5px solid #aab6c6;
        border-radius: 50%;
        background: #ffffff;
        box-shadow: inset 0 0 0 4px #ffffff;
        cursor: pointer;
        transition:
            border-color 0.16s ease,
            background-color 0.16s ease,
            box-shadow 0.16s ease,
            transform 0.12s ease;
    }

    .modern-radio-label input[type="radio"]:checked {
        border-color: #2563eb;
        background-color: #2563eb;
        box-shadow:
            inset 0 0 0 4px #ffffff,
            0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .modern-radio-label input[type="radio"]:focus-visible {
        outline: none;
        box-shadow:
            inset 0 0 0 4px #ffffff,
            0 0 0 4px rgba(37, 99, 235, 0.18);
    }

    .modern-radio-label input[type="radio"]:active {
        transform: scale(0.9);
    }

    .modern-radio-label:has(input[type="radio"]:checked) {
        color: #1d4ed8;
        border-color: #93b4ea;
        background: #eff6ff;
        box-shadow: 0 3px 10px rgba(37, 99, 235, 0.11);
    }

    @media (max-width: 767.98px) {
        .modern-radio-group {
            gap: 6px;
        }

        .modern-radio-label {
            min-height: 36px;
            padding: 8px 10px;
        }
    }


    

    .surat-pesanan-content .opsi-row {
        align-items: flex-start;
    }

    .surat-pesanan-content .modern-option-group {
        display: flex;
        flex: 1 1 auto;
        width: 100%;
        min-width: 0;
        flex-direction: column;
        gap: 7px;
    }

    .surat-pesanan-content .modern-option-group .modern-checkbox-label {
        width: 100%;
        min-width: 0;
        min-height: 38px;
        margin: 0 !important;
        box-sizing: border-box;
    }

    .surat-pesanan-content .modern-option-group .modern-checkbox-label span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }


    
    .surat-pesanan-content .filter-date-radio-group {
        display: grid;
        grid-template-columns: 1fr;
        width: 155px;
        max-width: 155px;
        gap: 7px;
    }

    .surat-pesanan-content
    .filter-date-radio-group
    .modern-radio-label {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
        justify-content: flex-start;
    }

    
    .surat-pesanan-content .bgb-radio-group {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        width: 100%;
        max-width: 100%;
        gap: 7px;
    }

    .surat-pesanan-content
    .bgb-radio-group
    .modern-radio-label-compact {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
        justify-content: center;
        padding-left: 6px;
        padding-right: 6px;
    }

    .surat-pesanan-content
    .bgb-radio-group
    .modern-radio-label-compact span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .surat-pesanan-content .filter-date-radio-group {
            width: 100%;
            max-width: 100%;
        }

        .surat-pesanan-content .bgb-radio-group {
            grid-template-columns: repeat(3, minmax(70px, 1fr));
        }
    }


    

    .surat-pesanan-content .bgb-radio-group {
        display: grid;
        grid-template-columns: repeat(2, 90px);
        grid-auto-rows: 36px;
        width: auto;
        max-width: none;
        gap: 7px;
        justify-content: start;
    }

    .surat-pesanan-content
    .bgb-radio-group
    .modern-radio-label-compact {
        width: 90px;
        min-width: 90px;
        max-width: 90px;
        min-height: 36px;
        margin: 0 !important;
        padding: 6px 8px;
        box-sizing: border-box;
        justify-content: flex-start;
    }

    .surat-pesanan-content
    .bgb-radio-group
    .modern-radio-label-compact span {
        display: inline-block;
        overflow: visible;
        text-overflow: clip;
        white-space: nowrap;
    }

    
    .surat-pesanan-content
    .bgb-radio-group
    .modern-radio-label-compact:nth-child(3) {
        grid-column: 1;
    }

    @media (max-width: 767.98px) {
        .surat-pesanan-content .bgb-radio-group {
            grid-template-columns: repeat(2, 90px);
        }
    }



    .surat-pesanan-content .bgb-radio-group {
        display: grid;
        flex: 1 1 0;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-auto-rows: 38px;
        width: 100%;
        min-width: 0;
        max-width: none;
        gap: 7px;
        justify-content: stretch;
    }

    .surat-pesanan-content
    .bgb-radio-group
    .modern-radio-label-compact {
        width: 100%;
        min-width: 0;
        max-width: none;
        min-height: 38px;
        margin: 0 !important;
        padding: 7px 10px;
        box-sizing: border-box;
        justify-content: flex-start;
    }

    .surat-pesanan-content
    .bgb-radio-group
    .modern-radio-label-compact span {
        display: inline-block;
        overflow: visible;
        text-overflow: clip;
        white-space: nowrap;
    }

    .surat-pesanan-content
    .bgb-radio-group
    .modern-radio-label-compact:nth-child(3) {
        grid-column: 1;
    }

    @media (max-width: 767.98px) {
        .surat-pesanan-content .bgb-radio-group {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
        }
    }


    

    .surat-pesanan-content .bgb-radio-group {
        display: grid;

        
        flex: 0 1 auto;
        width: 100%;
        min-width: 0;
        max-width: 100%;

        
        grid-template-columns: repeat(2, 90px);
        grid-auto-rows: 38px;
        gap: 7px;

        
        justify-content: start;
        align-content: start;
    }

    .surat-pesanan-content
    .bgb-radio-group
    .modern-radio-label-compact {
        width: 90px;
        min-width: 90px;
        max-width: 90px;
        min-height: 38px;
        margin: 0 !important;
        padding: 7px 9px;
        box-sizing: border-box;
        justify-content: flex-start;
    }

    .surat-pesanan-content
    .bgb-radio-group
    .modern-radio-label-compact:nth-child(3) {
        grid-column: 1;
    }

    .surat-pesanan-content
    .bgb-radio-group
    .modern-radio-label-compact span {
        overflow: visible;
        text-overflow: clip;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .surat-pesanan-content .bgb-radio-group {
            grid-template-columns: repeat(2, 90px);
            justify-content: start;
        }
    }



    

    :root {
        --dsp-primary: #2563eb;
        --dsp-primary-soft: #dbeafe;
        --dsp-success: #16a34a;
        --dsp-border: #e5e7eb;
        --dsp-border-strong: #d1d5db;
        --dsp-text: #0f172a;
        --dsp-text-soft: #475569;
        --dsp-muted: #64748b;
        --dsp-white: #ffffff;
        --dsp-shadow-sm: 0 4px 14px rgba(15, 23, 42, 0.06);
        --dsp-radius: 18px;
    }

    .surat-pesanan-content {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
        color: var(--dsp-text-soft);
    }

    .surat-pesanan-content .page-panel,
    .surat-pesanan-content .filter-panel,
    .surat-pesanan-content .result-panel {
        margin-bottom: 16px;
        padding: 20px;
        border: 1px solid var(--dsp-border) !important;
        border-radius: var(--dsp-radius) !important;
        background: var(--dsp-white) !important;
        box-shadow: var(--dsp-shadow-sm) !important;
    }

    .surat-pesanan-content .page-panel {
        padding: 18px 20px;
    }

    .surat-pesanan-content .page-title-wrap {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .surat-pesanan-content .page-title-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        flex: 0 0 42px;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        border-radius: 14px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25);
        font-size: 16px;
    }

    .surat-pesanan-content .page-title-text {
        margin: 0;
        color: var(--dsp-text);
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 20px;
        font-weight: 700;
        letter-spacing: -0.01em;
    }

    .surat-pesanan-content .filter-label {
        width: 120px !important;
        flex: 0 0 120px !important;
        color: var(--dsp-text-soft) !important;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
        font-size: 13px !important;
        font-weight: 600 !important;
    }

    .surat-pesanan-content .filter-panel .lookup-row {
        margin-bottom: 12px !important;
        gap: 10px !important;
    }

    .surat-pesanan-content .filter-panel .form-control,
    .surat-pesanan-content .filter-panel select.form-control,
    .surat-pesanan-content .filter-panel input.form-control,
    .surat-pesanan-content .filter-panel .lookup-display {
        min-height: 44px !important;
        height: 44px;
        padding-left: 14px;
        padding-right: 14px;
        color: var(--dsp-text) !important;
        border: 1px solid var(--dsp-border-strong) !important;
        border-radius: 12px !important;
        background: #fbfdff !important;
        box-shadow: none !important;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
        font-size: 13px !important;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .surat-pesanan-content .filter-panel .form-control:focus,
    .surat-pesanan-content .filter-panel select.form-control:focus,
    .surat-pesanan-content .filter-panel input.form-control:focus {
        border-color: var(--dsp-primary) !important;
        background: #ffffff !important;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12) !important;
    }

    .surat-pesanan-content .filter-panel input[readonly].form-control {
        color: #667085 !important;
        background: #f3f6fa !important;
    }

    .surat-pesanan-content .filter-panel .lookup-control {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 42px;
        width: 100%;
        gap: 0 !important;
    }

    .surat-pesanan-content .filter-panel .lookup-display {
        display: flex;
        align-items: center;
        overflow: hidden;
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
        white-space: nowrap;
        text-overflow: ellipsis;
        cursor: pointer;
    }

    .surat-pesanan-content .filter-panel .lookup-button {
        display: inline-flex;
        min-width: 42px !important;
        width: 42px;
        height: 44px !important;
        min-height: 44px !important;
        align-items: center;
        justify-content: center;
        padding: 0 !important;
        color: var(--dsp-primary) !important;
        border: 1px solid var(--dsp-border-strong) !important;
        border-left: 0 !important;
        border-radius: 0 12px 12px 0 !important;
        background: #f8fafc !important;
        box-shadow: none !important;
        transition:
            border-color 0.18s ease,
            background-color 0.18s ease,
            color 0.18s ease;
    }

    .surat-pesanan-content .filter-panel .lookup-button:hover {
        color: #1d4ed8 !important;
        border-color: var(--dsp-primary) !important;
        background: var(--dsp-primary-soft) !important;
        transform: none;
        box-shadow: none !important;
    }

    .surat-pesanan-content .filter-panel .lookup-button:focus {
        position: relative;
        z-index: 1;
        color: #1d4ed8 !important;
        border-color: var(--dsp-primary) !important;
        outline: 0;
        background: var(--dsp-primary-soft) !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
    }

    .surat-pesanan-content .filter-panel .lookup-button svg {
        width: 18px;
        height: 18px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 2;
    }

    
    .surat-pesanan-content .filter-date-radio-group {
        display: flex !important;
        flex: 1 1 auto !important;
        width: auto !important;
        max-width: none !important;
        align-items: flex-start !important;
        flex-direction: column !important;
        gap: 7px !important;
    }

    .surat-pesanan-content .filter-date-radio-group .modern-radio-label {
        width: 150px !important;
        min-width: 150px !important;
        max-width: 150px !important;
        min-height: 38px !important;
        justify-content: flex-start !important;
        box-sizing: border-box;
    }

    .surat-pesanan-content .modern-radio-label,
    .surat-pesanan-content .modern-checkbox-label {
        border-radius: 10px !important;
    }

    .surat-pesanan-content .action-buttons {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
        gap: 9px;
        margin-top: 14px;
    }

    .surat-pesanan-content .action-btn {
        min-width: 104px;
        width: 104px;
        height: 36px;
        min-height: 36px;
        padding: 6px 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border: 1px solid transparent !important;
        border-radius: 8px !important;
        font-size: 13px;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease,
            background-color 0.15s ease;
    }

    .surat-pesanan-content .action-btn svg {
        width: 17px;
        height: 17px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 1.8;
    }

    .surat-pesanan-content .action-btn-view {
        color: #ffffff !important;
        background: var(--dsp-primary) !important;
        box-shadow: 0 5px 12px rgba(37, 99, 235, 0.22);
    }

    .surat-pesanan-content .action-btn-excel {
        color: var(--dsp-success) !important;
        border-color: #bbdec8 !important;
        background: #f2fbf5 !important;
        box-shadow: none;
    }

    .surat-pesanan-content .action-btn:hover {
        transform: translateY(-1px);
        filter: none;
    }

    .surat-pesanan-content .action-btn-view:hover,
    .surat-pesanan-content .action-btn-view:focus {
        color: #ffffff !important;
        background: #1d4ed8 !important;
    }

    .surat-pesanan-content .action-btn-excel:hover,
    .surat-pesanan-content .action-btn-excel:focus {
        color: var(--dsp-success) !important;
        border-color: #86c99d !important;
        background: #e7f7ec !important;
    }

    .surat-pesanan-content .action-btn:active {
        transform: translateY(0);
    }

    .surat-pesanan-content #loading-info {
        margin-bottom: 14px;
        padding: 12px 14px;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        background: #eff6ff;
        font-weight: 600;
    }

    .surat-pesanan-content .empty-state-panel {
        min-height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 24px;
        color: var(--dsp-muted);
        border: 1px dashed var(--dsp-border-strong);
        border-radius: 16px;
        background: linear-gradient(180deg, #fcfdff 0%, #f8fafc 100%);
        text-align: center;
    }

    .surat-pesanan-content .empty-state-panel i {
        width: 52px;
        height: 52px;
        margin-bottom: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--dsp-primary);
        border-radius: 16px;
        background: var(--dsp-primary-soft);
        font-size: 20px;
    }

    @media (max-width: 767.98px) {
        .surat-pesanan-content .filter-label {
            width: 100% !important;
            flex-basis: auto !important;
        }

        .surat-pesanan-content .filter-panel .lookup-row {
            flex-direction: column;
            align-items: stretch;
        }

        .surat-pesanan-content .filter-date-radio-group,
        .surat-pesanan-content .filter-date-radio-group .modern-radio-label {
            width: 100% !important;
            min-width: 0 !important;
            max-width: none !important;
        }
    }



    
    #suratPesananModal .modal-table th {
        background: #eff6ff !important;
        color: #0f172a !important;
        text-align: center !important;
        vertical-align: middle !important;
        border-right: 1px solid #dbe4f0 !important;
        border-bottom: 1px solid #cbd5e1 !important;
        font-weight: 700 !important;
    }

    #suratPesananModal .modal-table td {
        background: #ffffff !important;
        color: #475569 !important;
        text-align: center !important;
        vertical-align: middle !important;
        border-right: 1px solid #eef2f7 !important;
        border-bottom: 1px solid #eef2f7 !important;
        white-space: normal !important;
        word-break: break-word;
    }

    #suratPesananModal .modal-table td:first-child {
        color: #475569 !important;
        font-weight: 600;
        text-align: center !important;
    }

    #suratPesananModal .modal-table tr:nth-child(even) td {
        background: #f8fafc !important;
    }

    #suratPesananModal .modal-table tr:not(:first-child):hover td {
        background: #f8fbff !important;
        color: #0f172a !important;
    }

    #suratPesananModal .modal-table tr:not(:first-child):active td {
        background: #eaf2ff !important;
    }

    
    .surat-pesanan-content .filter-panel > .row > .col-md-4:nth-child(2) .filter-label {
        width: 88px !important;
        flex: 0 0 88px !important;
    }

    .surat-pesanan-content .filter-panel > .row > .col-md-4:nth-child(3) .filter-label {
        width: 62px !important;
        flex: 0 0 62px !important;
    }

    .surat-pesanan-content .filter-panel > .row > .col-md-4:nth-child(2) .lookup-row,
    .surat-pesanan-content .filter-panel > .row > .col-md-4:nth-child(3) .lookup-row {
        gap: 7px !important;
    }

    
    .surat-pesanan-content .filter-panel > .row > .col-md-4:nth-child(2) .lookup-row > .form-control,
    .surat-pesanan-content .filter-panel > .row > .col-md-4:nth-child(2) .lookup-row > .modern-radio-group,
    .surat-pesanan-content .filter-panel > .row > .col-md-4:nth-child(3) .lookup-row > .form-control,
    .surat-pesanan-content .filter-panel > .row > .col-md-4:nth-child(3) .lookup-row > .lookup-control,
    .surat-pesanan-content .filter-panel > .row > .col-md-4:nth-child(3) .lookup-row > .modern-option-group {
        min-width: 0;
        flex: 1 1 auto;
    }

    @media (max-width: 767.98px) {
        .surat-pesanan-content .filter-panel > .row > .col-md-4:nth-child(2) .filter-label,
        .surat-pesanan-content .filter-panel > .row > .col-md-4:nth-child(3) .filter-label {
            width: 100% !important;
            flex-basis: auto !important;
        }
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
       TAMPILAN HASIL LAPORAN — MENGIKUTI DAFTAR SERTIFIKAT PECAHAN
       Struktur: 3 grid (header → subtitle → tabel).
       Catatan: HANYA tampilan/gaya. Kolom, urutan kolom, dan isi
       data laporan tidak diubah sama sekali.
       ========================================================= */
    .surat-pesanan-content .report-wrapper {
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

    .surat-pesanan-content .report-wrapper::before {
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
    .surat-pesanan-content .report-header {
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

    .surat-pesanan-content .report-company {
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 850;
        letter-spacing: 0.04em;
        line-height: 1.45;
    }

    .surat-pesanan-content .report-title {
        margin: 0 !important;
        color: #172033 !important;
        text-align: center !important;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
        font-size: 18px !important;
        font-weight: 950 !important;
        letter-spacing: -0.02em !important;
        line-height: 1.25;
    }

    .surat-pesanan-content .report-period {
        color: #475467;
        text-align: right;
        font-size: 10.5px;
        font-weight: 650;
        line-height: 1.55;
    }

    /* --- GRID 2 : BARIS SEKTOR/CLUSTER --- */
    .surat-pesanan-content .report-subtitle {
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

    .surat-pesanan-content .report-subtitle-label {
        justify-self: start;
        color: #667085;
        white-space: nowrap;
    }

    .surat-pesanan-content .report-subtitle-value {
        justify-self: center;
        color: #344054;
        font-weight: 850;
        text-align: center;
    }

    .surat-pesanan-content .report-subtitle strong {
        color: #344054;
        font-weight: 850;
    }

    .surat-pesanan-content .report-live-badge {
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

    .surat-pesanan-content .report-live-badge::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
    }

    /* --- GRID 3 : TABEL LAPORAN --- */
    .surat-pesanan-content .report-table-container {
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

    .surat-pesanan-content .report-table-container::-webkit-scrollbar {
        width: 11px;
        height: 13px;
    }

    .surat-pesanan-content .report-table-container::-webkit-scrollbar-track {
        border-radius: 999px;
        background: #eff3f7;
    }

    .surat-pesanan-content .report-table-container::-webkit-scrollbar-thumb {
        border: 2px solid #eff3f7;
        border-radius: 999px;
        background: #93c5fd;
    }

    .surat-pesanan-content .report-table-container::-webkit-scrollbar-thumb:hover {
        background: #60a5fa;
    }

    .surat-pesanan-content .report-table {
        margin-bottom: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        background: #ffffff !important;
        color: #344054 !important;
        font-size: 10.5px !important;
    }

    .surat-pesanan-content .report-table th {
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

    .surat-pesanan-content .report-table td {
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

    .surat-pesanan-content .report-table tbody tr:nth-child(even) td {
        background: #fbfcfe !important;
    }

    .surat-pesanan-content .report-table tbody tr:hover td {
        background: #f0f7ff !important;
    }

    .surat-pesanan-content .report-table tbody tr:hover td:first-child {
        color: #1d4ed8 !important;
        box-shadow: inset 4px 0 0 #2563eb !important;
    }

    .surat-pesanan-content .report-table .empty-row {
        height: 130px !important;
        color: #64748b !important;
        background: #ffffff !important;
        font-style: normal !important;
        text-align: center !important;
    }

    @media (max-width: 767.98px) {
        .surat-pesanan-content .report-header {
            grid-template-columns: minmax(0, 1fr);
            gap: 8px;
            text-align: center;
        }

        .surat-pesanan-content .report-period {
            text-align: center;
        }

        .surat-pesanan-content .report-subtitle {
            grid-template-columns: minmax(0, 1fr);
            justify-items: center;
            text-align: center !important;
        }

        .surat-pesanan-content .report-subtitle-label,
        .surat-pesanan-content .report-live-badge {
            justify-self: center;
        }
    }

    /* =========================================================
       NOTIFIKASI TOAST — PERCOBAAN DI FITUR INI DULU
       Muncul ketika hasil View tidak memuat data.
       Bentuk kartu, radius, bayangan, dan palet biru mengikuti
       panel-panel yang sudah ada agar tetap satu tema.
       ========================================================= */
    .surat-pesanan-content .sp-toast-stack {
        position: fixed;
        top: 88px;
        right: 22px;
        z-index: 1080;
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: min(340px, calc(100vw - 32px));
        pointer-events: none;
    }

    .surat-pesanan-content .sp-toast {
        position: relative;
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr) 28px;
        align-items: center;
        gap: 12px;
        padding: 14px 14px 15px 18px;
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.14);
        color: #172033;
        opacity: 0;
        transform: translateY(-10px) scale(0.98);
        pointer-events: auto;
        transition: opacity 0.22s ease, transform 0.22s ease;
    }

    .surat-pesanan-content .sp-toast::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: linear-gradient(180deg, #38bdf8 0%, #2563eb 55%, #1d4ed8 100%);
    }

    .surat-pesanan-content .sp-toast.is-visible {
        opacity: 1;
        transform: none;
    }

    .surat-pesanan-content .sp-toast.is-leaving {
        opacity: 0;
        transform: translateY(-8px) scale(0.98);
    }

    .surat-pesanan-content .sp-toast-icon {
        display: inline-flex;
        width: 38px;
        height: 38px;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 15px;
    }

    .surat-pesanan-content .sp-toast-text {
        min-width: 0;
        color: #172033;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 13.5px;
        font-weight: 800;
        letter-spacing: -0.01em;
        line-height: 1.35;
    }

    .surat-pesanan-content .sp-toast-close {
        display: inline-flex;
        width: 28px;
        height: 28px;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid #e4e7ec;
        border-radius: 9px;
        background: #ffffff;
        color: #667085;
        cursor: pointer;
        font-size: 11px;
        transition: color 0.16s ease, border-color 0.16s ease, background 0.16s ease;
    }

    .surat-pesanan-content .sp-toast-close:hover {
        color: #1d4ed8;
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .surat-pesanan-content .sp-toast-progress {
        position: absolute;
        left: 0;
        bottom: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, #38bdf8, #2563eb);
        transform-origin: left center;
        animation-name: spToastProgress;
        animation-timing-function: linear;
        animation-fill-mode: forwards;
    }

    @keyframes spToastProgress {
        from { transform: scaleX(1); }
        to { transform: scaleX(0); }
    }

    @media (prefers-reduced-motion: reduce) {
        .surat-pesanan-content .sp-toast {
            transition: none;
        }

        .surat-pesanan-content .sp-toast-progress {
            animation: none;
        }
    }

    @media (max-width: 767.98px) {
        .surat-pesanan-content .sp-toast-stack {
            top: 12px;
            right: 12px;
            left: 12px;
            width: auto;
        }
    }

</style>

<div class="surat-pesanan-content">

    <div
        class="sp-toast-stack"
        id="suratPesananToastStack"
        aria-live="polite"
        aria-atomic="true"
    ></div>

    <div class="modal" id="suratPesananModal">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header d-flex" style="justify-content:space-between">
                    <h5 id="suratPesananModalTitle"></h5>
                    <button class="btn btn-light" type="button" onclick="toggle_modal()">
                        <i class="fa fa-times"></i>
                    </button>
                </div>

                <div class="modal-body" id="suratPesananModalContent"></div>

            </div>
        </div>
    </div>

<div class="page-panel">
    <div class="page-title-wrap">
        <div class="page-title-icon sertipikat-style-heading-icon" aria-hidden="true">◈</div>

        <div>
            <h5 class="page-title-text">Daftar Surat Pesanan</h5>
        </div>
    </div>
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
                <div class="filter-label">Filter Tanggal</div>
                <div class="modern-radio-group modern-radio-group-vertical filter-date-radio-group">
                    <label class="modern-radio-label">
                        <input type="radio" name="flag_tgl" value="1" checked>
                        <span>Tgl. Entry SP</span>
                    </label>

                    <label class="modern-radio-label">
                        <input type="radio" name="flag_tgl" value="2">
                        <span>Tgl. Surat Pesanan</span>
                    </label>
                </div>
            </div>

            <div class="lookup-row">
                <div class="filter-label">Tgl Awal</div>
                <input type="date" id="tgl_awal" class="form-control">
            </div>

            <div class="lookup-row">
                <div class="filter-label">Tgl Akhir</div>
                <input type="date" id="tgl_akhir" class="form-control">
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
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m20 20-4-4"></path>
                        </svg>
                    </button>
                </div>
            </div>

        </div>

        <div class="col-md-4">

            <div class="lookup-row">
                <div class="filter-label">Status</div>
                <select id="status" class="form-control">
                    <option value="*">Semua</option>
                    <option value="1">Sudah PPJB</option>
                    <option value="T">Belum PPJB</option>
                    <option value="2">Belum Verifikasi</option>
                    <option value="B">Batal</option>
                </select>
            </div>

            <div class="lookup-row">
                <div class="filter-label">Jenis Bgn</div>
                <select id="jenis" class="form-control">
                    <option value="*">Semua</option>
                    <option value="Rumah">Rumah</option>
                    <option value="Kavling">Kavling</option>
                    <option value="Rukan">Rukan</option>
                    <option value="Apartemen">Apartemen</option>
                    <option value="Kantor">Kantor</option>
                </select>
            </div>

            <div class="lookup-row">
                <div class="filter-label">Tipe Bayar</div>
                <select id="tipe_bayar" class="form-control lokasi-select">
                    <option value="*" selected>Harap pilih tipe pembayaran</option>

                    @foreach (($tipeBayarList ?? []) as $tipeBayar)
                        <option value="{{ $tipeBayar->TIPE_BAYAR }}">
                            {{ $tipeBayar->TIPE_BAYAR }} - {{ $tipeBayar->NAMA }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lookup-row">
                <div class="filter-label">Tgl Bayar</div>
                <input type="date" id="tgl_bayar" class="form-control">
            </div>

            <div class="lookup-row">
                <div class="filter-label">BGB</div>
                <div class="modern-radio-group bgb-radio-group">
                    <label class="modern-radio-label modern-radio-label-compact">
                        <input type="radio" name="bgb" value="Y">
                        <span>BGB</span>
                    </label>

                    <label class="modern-radio-label modern-radio-label-compact">
                        <input type="radio" name="bgb" value="T">
                        <span>Non BGB</span>
                    </label>

                    <label class="modern-radio-label modern-radio-label-compact">
                        <input type="radio" name="bgb" value="*" checked>
                        <span>Semua</span>
                    </label>
                </div>
            </div>

        </div>

        <div class="col-md-4">

            <div class="lookup-row">
                <div class="filter-label">Unit</div>
                <input type="text" id="perusahaan" class="form-control" value="{{ session('kd_unit') ?? 'CGTK' }}" readonly>
            </div>

            <div class="lookup-row">
                <div class="filter-label">Agen</div>
                <div class="lookup-control">
                    <input type="hidden" id="agen" value="*">
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

            <div class="lookup-row">
                <div class="filter-label">Sales</div>
                <div class="lookup-control">
                    <input type="hidden" id="sales" value="*">
                    <div
                        class="form-control lookup-display"
                        id="salesentry"
                        role="button"
                        tabindex="0"
                        onclick="get_sales_modal()"
                        onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); get_sales_modal(); }"
                    >Semua Sales</div>
                    <button
                        class="btn lookup-button"
                        type="button"
                        onclick="get_sales_modal()"
                        title="Cari Sales"
                        aria-label="Cari Sales"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m20 20-4-4"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="lookup-row opsi-row">
                <div class="filter-label">Opsi</div>

                <div class="modern-option-group">
                    <label class="modern-checkbox-label">
                        <input type="checkbox" id="per_tgl_bayar" value="Y">
                        <span>Per Tgl Bayar</span>
                    </label>

                    <label class="modern-checkbox-label">
                        <input type="checkbox" id="tanda_jadi_aktif" value="Y">
                        <span>Tanda Jadi Aktif</span>
                    </label>
                </div>
            </div>

            <div class="action-buttons">
                <button type="button" class="btn action-btn action-btn-view" onclick="getSummary()">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
                        <circle cx="12" cy="12" r="2.5"></circle>
                    </svg>
                    <span>View</span>
                </button>

                <button type="button" class="btn action-btn action-btn-excel" onclick="printSuratPesananReport()">
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
    <div id="loading-info" style="display:none;">
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
    var activeSummaryRequest = null;
    var summaryRequestSerial = 0;

    $(document).ready(function () {
        setDefaultDate();
        resetLookupDefaults();
        resetReportDisplay();
        bindSummaryInvalidation();
    });

    /*
     * Firefox dapat memulihkan value form/hidden input ketika reload atau
     * kembali dari bfcache. Karena label sektor berupa <div>, nilai hidden
     * dapat tetap VLA sementara layar sudah menulis "Semua Sektor".
     * pageshow dipakai agar state lookup dan laporan selalu sinkron.
     */
    window.addEventListener('pageshow', function () {
        resetLookupDefaults();
        resetReportDisplay();
    });

    function resetLookupDefaults() {
        $('#sektor').val('*');
        $('#sektorentry').text('Semua Sektor');

        $('#agen').val('*');
        $('#agenentry').text('Semua Agen');

        $('#sales').val('*');
        $('#salesentry').text('Semua Sales');
    }

    function emptyReportHtml() {
        return '' +
            '<div class="empty-state-panel">' +
                '<i class="fas fa-table"></i>' +
                '<div>Silakan pilih filter lalu klik <strong>View</strong>.</div>' +
            '</div>';
    }

    function resetReportDisplay() {
        cancelActiveSummaryRequest();
        hideSuratPesananToast(true);
        $('#loading-info').hide();
        $('#main-display').html(emptyReportHtml());
    }

    function cancelActiveSummaryRequest() {
        summaryRequestSerial += 1;

        if (
            activeSummaryRequest &&
            activeSummaryRequest.readyState !== 4
        ) {
            activeSummaryRequest.abort();
        }

        activeSummaryRequest = null;
    }

    function bindSummaryInvalidation() {
        var selector = [
            '#tgl_awal',
            '#tgl_akhir',
            '#tgl_bayar',
            '#lokasi',
            '#status',
            '#jenis',
            '#tipe_bayar',
            '#per_tgl_bayar',
            '#tanda_jadi_aktif',
            'input[name="flag_tgl"]',
            'input[name="bgb"]'
        ].join(',');

        $(document).on('change', selector, function () {
            /*
             * Jangan biarkan response request lama menimpa filter yang baru.
             * Laporan yang sudah tampil tidak dihapus; hanya request in-flight
             * yang dibatalkan.
             */
            cancelActiveSummaryRequest();
        });
    }

    function setDefaultDate() {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var today = year + '-' + month + '-' + day;

        $('#tgl_awal').val(today);
        $('#tgl_akhir').val(today);
        $('#tgl_bayar').val(today);
    }

    function getFilterData() {
        return {
            _token: '{{ csrf_token() }}',
            flag_tgl: $('input[name="flag_tgl"]:checked').val(),
            tgl_awal: $('#tgl_awal').val(),
            tgl_akhir: $('#tgl_akhir').val(),
            tgl_bayar: $('#tgl_bayar').val(),
            perusahaan: $('#perusahaan').val(),
            lokasi: $('#lokasi').val() || '*',
            sektor: $('#sektor').val() || '*',
            jenis: $('#jenis').val() || '*',
            status: $('#status').val() || '*',
            tipe_bayar: $('#tipe_bayar').val() || '*',
            agen: $('#agen').val() || '*',
            sales: $('#sales').val() || '*',
            bgb: $('input[name="bgb"]:checked').val() || '*',
            per_tgl_bayar: $('#per_tgl_bayar').is(':checked') ? 'Y' : 'T',
            tanda_jadi_aktif: $('#tanda_jadi_aktif').is(':checked') ? 'Y' : 'T'
        };
    }


    function toggle_modal() {
        $('#suratPesananModal').modal('toggle');
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

        $('#suratPesananModalContent').html(finalContent);
        $('#suratPesananModalTitle').html(title);

        setTimeout(function() {
            $('#modalSearchInput').focus();
        }, 300);
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

    /*
     * ================================================================
     * NOTIFIKASI TOAST — PERCOBAAN DI FITUR INI DULU
     * Dipakai ketika hasil View tidak memuat data. Baris
     * "Data tidak ditemukan." pada tabel tetap dipertahankan;
     * toast hanya menambah penanda visual, tidak mengubah isi laporan.
     * ================================================================
     */
    var suratPesananToastTimer = null;
    var suratPesananToastRemoveTimer = null;

    function hideSuratPesananToast(immediate) {
        window.clearTimeout(suratPesananToastTimer);
        window.clearTimeout(suratPesananToastRemoveTimer);
        suratPesananToastTimer = null;
        suratPesananToastRemoveTimer = null;

        var $toast = $('#suratPesananToastStack .sp-toast');

        if (!$toast.length) {
            return;
        }

        if (immediate === true) {
            $toast.remove();
            return;
        }

        $toast.removeClass('is-visible').addClass('is-leaving');

        suratPesananToastRemoveTimer = window.setTimeout(function () {
            $toast.remove();
        }, 240);
    }

    function showSuratPesananToast(message) {
        var $stack = $('#suratPesananToastStack');

        if (!$stack.length) {
            return;
        }

        /* Toast lama dibersihkan agar tidak menumpuk saat View diklik berulang. */
        hideSuratPesananToast(true);

        var duration = 4000;
        var html = '';

        html += '<div class="sp-toast" role="status">';
        html += '<span class="sp-toast-icon" aria-hidden="true"><i class="fas fa-inbox"></i></span>';
        html += '<span class="sp-toast-text">' + escapeHtml(message) + '</span>';
        html += '<button type="button" class="sp-toast-close" onclick="hideSuratPesananToast()" aria-label="Tutup notifikasi">';
        html += '<i class="fas fa-times"></i>';
        html += '</button>';
        html += '<span class="sp-toast-progress" style="animation-duration:' + duration + 'ms"></span>';
        html += '</div>';

        var $toast = $(html).appendTo($stack);

        /* Beri jeda satu frame supaya transisi masuk tetap berjalan. */
        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(function () {
                $toast.addClass('is-visible');
            });
        } else {
            $toast.addClass('is-visible');
        }

        suratPesananToastTimer = window.setTimeout(function () {
            hideSuratPesananToast();
        }, duration);
    }

    function missing_item() {
        return "<div class='modal-empty-state'>" +
            "<i class='fas fa-search'></i>" +
            "<strong>Data tidak ditemukan</strong>" +
            "<span>Coba gunakan kata kunci lain atau periksa kembali data pilihan.</span>" +
            "</div>";
    }

    function filter_modal_table(keyword) {
        keyword = String(keyword || '').toLowerCase();

        $('#suratPesananModal .modal-table tr').each(function(index) {
            
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

    function add_sektor(value, descriptor) {
        cancelActiveSummaryRequest();
        $('#sektor').val(value);
        $('#sektorentry').html(escapeHtml(descriptor));
        $('#suratPesananModal').modal('toggle');
    }

    function add_agen(value, descriptor) {
        cancelActiveSummaryRequest();
        $('#agen').val(value);
        $('#agenentry').html(escapeHtml(descriptor));

        $('#sales').val('*');
        $('#salesentry').html('Semua Sales');

        $('#suratPesananModal').modal('toggle');
    }

    function add_sales(value, descriptor) {
        cancelActiveSummaryRequest();
        $('#sales').val(value);
        $('#salesentry').html(escapeHtml(descriptor));
        $('#suratPesananModal').modal('toggle');
    }

    function get_sektor_modal() {
        var perusahaan = $('#perusahaan').val();

        if (perusahaan === '' || perusahaan === null || perusahaan === undefined) {
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

                if (!data || data.length < 1) {
                    html = missing_item();
                } else {
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

                    html += '</table>';
                }

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
                html += '<th>Nama PT</th>';
                html += '</tr>';

                html += '<tr onclick="add_agen(\'*\', \'Semua Agen\')">';
                html += '<td>*</td>';
                html += '<td>Semua Agen</td>';
                html += '<td>-</td>';
                html += '</tr>';

                if (!data || data.length < 1) {
                    html = missing_item();
                } else {
                    $.each(data, function (index, item) {
                        var kode = item.KD_AGEN == null ? '' : item.KD_AGEN;
                        var namaAgen = item.NAMA_AGEN == null ? '' : item.NAMA_AGEN;
                        var namaPt = item.NAMA_PT == null ? '' : item.NAMA_PT;

                        html += '<tr onclick="add_agen(\'' + escapeJs(kode) + '\', \'' + escapeJs(namaAgen) + '\')">';
                        html += '<td>' + escapeHtml(kode) + '</td>';
                        html += '<td>' + escapeHtml(namaAgen) + '</td>';
                        html += '<td>' + escapeHtml(namaPt) + '</td>';
                        html += '</tr>';
                    });

                    html += '</table>';
                }

                insert_modal('Pilih Agen', html);
                toggle_modal();
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                alert('Gagal mengambil data agen.');
            }
        });
    }

    function get_sales_modal() {
        var agen = $('#agen').val();

        if (agen === '' || agen === null || agen === undefined) {
            agen = '*';
        }

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_sales',
            data: {
                _token: '{{ csrf_token() }}',
                agen: agen
            },
            success: function (data) {
                var html = '';

                html += '<table class="modal-table table table-bordered">';
                html += '<tr>';
                html += '<th>Kode Sales</th>';
                html += '<th>Nama Sales</th>';
                html += '<th>Nama Agen</th>';
                html += '</tr>';

                html += '<tr onclick="add_sales(\'*\', \'Semua Sales\')">';
                html += '<td>*</td>';
                html += '<td>Semua Sales</td>';
                html += '<td>-</td>';
                html += '</tr>';

                if (!data || data.length < 1) {
                    html = missing_item();
                } else {
                    $.each(data, function (index, item) {
                        var kode = item.KD_SALES == null ? '' : item.KD_SALES;
                        var namaSales = item.DESKRIPSI == null || item.DESKRIPSI === '' ? item.KD_SALES : item.DESKRIPSI;
                        var namaAgen = item.NAMA_AGEN == null ? '' : item.NAMA_AGEN;

                        html += '<tr onclick="add_sales(\'' + escapeJs(kode) + '\', \'' + escapeJs(namaSales) + '\')">';
                        html += '<td>' + escapeHtml(kode) + '</td>';
                        html += '<td>' + escapeHtml(namaSales) + '</td>';
                        html += '<td>' + escapeHtml(namaAgen) + '</td>';
                        html += '</tr>';
                    });

                    html += '</table>';
                }

                insert_modal('Pilih Sales', html);
                toggle_modal();
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                alert('Gagal mengambil data sales.');
            }
        });
    }


    function loadSektor() {
        $.ajax({
            url: '{{ url()->current() }}/get_sektor',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                perusahaan: $('#perusahaan').val()
            },
            success: function (data) {
                var html = '<option value="*">Semua Sektor</option>';

                $.each(data, function (index, item) {
                    html += '<option value="' + item.KD_SEKTOR + '">' +
                        item.KD_SEKTOR + ' - ' + item.DESKRIPSI +
                    '</option>';
                });

                $('#sektor').html(html);
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                alert('Gagal mengambil data sektor.');
            }
        });
    }

    function loadAgen() {
        $.ajax({
            url: '{{ url()->current() }}/get_agen',
            type: 'GET',
            success: function (data) {
                var html = '<option value="*">Semua Agen</option>';

                $.each(data, function (index, item) {
                    html += '<option value="' + item.KD_AGEN + '">' +
                        item.KD_AGEN + ' - ' + item.NAMA_AGEN +
                    '</option>';
                });

                $('#agen').html(html);
                loadSales();
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                alert('Gagal mengambil data agen.');
            }
        });
    }

    function loadSales() {
        $.ajax({
            url: '{{ url()->current() }}/get_sales',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                agen: $('#agen').val() || '*'
            },
            success: function (data) {
                var html = '<option value="*">Semua Sales</option>';

                $.each(data, function (index, item) {
                    html += '<option value="' + item.KD_SALES + '">' +
                        item.KD_SALES + ' - ' + item.DESKRIPSI +
                    '</option>';
                });

                $('#sales').html(html);
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                alert('Gagal mengambil data sales.');
            }
        });
    }

    /*
     * Nama perusahaan hanya dipakai sebagai identitas pada header laporan
     * (kolom kiri grid pertama), mengikuti tampilan Daftar Sertifikat Pecahan.
     * Tidak memengaruhi filter maupun data yang diambil dari server.
     */
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

        $('input, textarea').not('.surat-pesanan-content input, .surat-pesanan-content textarea').each(function () {
            push($(this).val());
        });

        $('[title]').not('.surat-pesanan-content [title]').each(function () {
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
                $clone.find('.surat-pesanan-content, script, style, noscript').remove();
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

    function getSummaryRenderContext(filterData) {
        return {
            tgl_awal: filterData.tgl_awal,
            tgl_akhir: filterData.tgl_akhir,
            tgl_bayar: filterData.tgl_bayar,
            flag_tgl: filterData.flag_tgl,
            company_session_text: String($('#nama_perusahaan_session').val() || '').trim(),
            lokasi_text: $('#lokasi option:selected').text() || 'Semua Lokasi',
            sektor_text: $.trim($('#sektorentry').text()) || 'Semua Sektor',
            jenis_text: $('#jenis option:selected').text() || 'Semua',
            per_tgl_bayar: filterData.per_tgl_bayar === 'Y',
            tanda_jadi_aktif: filterData.tanda_jadi_aktif === 'Y'
        };
    }

    function getSummary() {
        cancelActiveSummaryRequest();

        var requestSerial = ++summaryRequestSerial;
        var filterData = getFilterData();
        var renderContext = getSummaryRenderContext(filterData);

        hideSuratPesananToast(true);
        $('#loading-info').show();
        $('#main-display').html('');

        activeSummaryRequest = $.ajax({
            url: '{{ url()->current() }}/get_summary',
            type: 'POST',
            dataType: 'json',
            cache: false,
            data: filterData,
            success: function (data) {
                /*
                 * Hanya request terbaru yang boleh menggambar laporan.
                 * Ini mencegah response sektor lama menimpa response filter baru.
                 */
                if (requestSerial !== summaryRequestSerial) {
                    return;
                }

                renderTable(data, renderContext);
            },
            error: function (xhr, statusText) {
                if (statusText === 'abort') {
                    return;
                }

                if (requestSerial !== summaryRequestSerial) {
                    return;
                }

                console.log(xhr.responseText);
                $('#main-display').html('<div class="alert alert-danger">Gagal mengambil data surat pesanan. Cek console/log Laravel.</div>');
            },
            complete: function () {
                if (requestSerial === summaryRequestSerial) {
                    $('#loading-info').hide();
                    activeSummaryRequest = null;
                }
            }
        });
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

    function renderTable(data, renderContext) {
        renderContext = renderContext || getSummaryRenderContext(getFilterData());

        /*
         * Metadata laporan berasal dari snapshot filter yang dipakai saat request
         * dikirim, bukan dari kondisi form saat response baru selesai.
         */
        var tglAwal = renderContext.tgl_awal;
        var tglAkhir = renderContext.tgl_akhir;
        var tglBayar = renderContext.tgl_bayar;

        var companyText = resolveReportCompany(
            (data && data.length > 0) ? data[0] : {},
            renderContext.company_session_text
        );
        var lokasiText = renderContext.lokasi_text;
        var sektorText = renderContext.sektor_text;
        var jenisText = renderContext.jenis_text;
        var flagTgl = renderContext.flag_tgl;

        var perTglBayar = renderContext.per_tgl_bayar;
        var tandaJadiAktif = renderContext.tanda_jadi_aktif;

        var modeGabungan = perTglBayar && tandaJadiAktif;
        var modePerTglBayar = perTglBayar && !tandaJadiAktif;
        var modeTandaJadiAktif = tandaJadiAktif && !perTglBayar;

        var judulTanggal = flagTgl === '1' ? 'Tanggal Entry SP' : 'Tanggal Surat Pesanan';

        var columns = [];

        function addColumn(title, render, align) {
            columns.push({
                title: title,
                render: render,
                align: align || 'left'
            });
        }

        function getNamaSalesAgen(item) {
            var namaSalesAgen = valueOrEmpty(item.NAMA_SALES);

            if (namaSalesAgen === '') {
                namaSalesAgen = valueOrEmpty(item.NAMA_AGEN);
            }

            return namaSalesAgen;
        }

        function getNoBuktiTahap1(item) {
            return valueOrEmpty(
                item.NO_BUKTI_TAHAP_1 ||
                item.NO_BUKTI_TAHAP_I ||
                item.NO_KUITANSI ||
                item.NO_KWITANSI ||
                item.NO_BUKTI ||
                item.NO_BUKTI_BAYAR ||
                item.NO_BAYAR
            );
        }

        function getTglBuktiTahap1(item) {
            return (
                item.TGL_BUKTI_TAHAP_1 ||
                item.TGL_BUKTI_TAHAP_I ||
                item.TGL_KUITANSI ||
                item.TGL_KWITANSI ||
                item.TGL_BUKTI ||
                item.TGL_BUKTI_BAYAR ||
                item.TGL_BAYAR
            );
        }

        addColumn('No.', function (item, index) {
            return (index + 1) + '.';
        }, 'center');

        addColumn('Tgl Surat<br>Pesanan', function (item) {
            return formatDateIndo(item.TGL_UANG_MUKA);
        }, 'center');

        addColumn('Nomor<br>Surat Pesanan', function (item) {
            return escapeHtml(valueOrEmpty(item.NO_UANG_MUKA));
        });

        addColumn('Blok/<br>Nomor', function (item) {
            return escapeHtml(valueOrEmpty(item.BLOK_NOMOR));
        });

        addColumn('Tipe', function (item) {
            return escapeHtml(valueOrEmpty(item.TIPE_BGN));
        });

        addColumn('Model', function (item) {
            return escapeHtml(valueOrEmpty(item.MODEL));
        });

        addColumn('Spesifikasi', function (item) {
            return escapeHtml(valueOrEmpty(item.SPESIFIKASI));
        });

        addColumn('Luas<br>Tanah', function (item) {
            return formatNumber(item.LUAS_TANAH);
        }, 'right');

        addColumn('Luas<br>Bgn (m2)', function (item) {
            return formatNumber(item.LUAS_BANGUNAN);
        }, 'right');

        addColumn('Nama<br>Pembeli', function (item) {
            return escapeHtml(cleanCustomerName(item.NASABAH_NAMA));
        });

        addColumn('Harga DPP<br>(Excl. PPN)', function (item) {
            return formatNumber(item.HRGJUAL_SBLM_PPN);
        }, 'right');

        addColumn('Harga Jual<br>(Incl. PPN)', function (item) {
            return formatNumber(item.HARGA_JUAL);
        }, 'right');

        if (!modeTandaJadiAktif || modeGabungan) {
            addColumn('Jumlah Bayar<br>Tanda Jadi', function (item) {
                return formatNumber(item.JUMLAH_BAYAR);
            }, 'right');

            addColumn('Tanggal<br>Bayar BF', function (item) {
                return formatDateIndo(item.TGL_BAYAR);
            }, 'center');

            addColumn('Nama Sales/<br>Agen', function (item) {
                return escapeHtml(getNamaSalesAgen(item));
            });

            if (!modePerTglBayar || modeGabungan) {
                addColumn('Nomor<br>PPJB', function (item) {
                    return escapeHtml(valueOrEmpty(item.NO_PPJB));
                });
            }

            addColumn('Tanggal<br>PPJB', function (item) {
                return formatDateIndo(item.TGL_PPJB);
            }, 'center');

            addColumn('Tgl TTD<br>PPJB', function (item) {
                return formatDateIndo(item.TGL_TTD_NOTARIS || item.TGL_TANDA_TANGAN);
            }, 'center');

            addColumn('Harga Jual<br>Setelah PPJB', function (item) {
                return formatNumber(item.HARGA_JUAL_PPJB);
            }, 'right');

            if (modePerTglBayar || modeGabungan) {
                addColumn('Total Bayar<br>per ' + formatDateIndo(tglBayar), function (item) {
                    return formatNumber(item.TOTAL_BAYAR_PER_TGL || item.TOTAL_BAYAR);
                }, 'right');
            }

            addColumn('%<br>Bayar', function (item) {
                return valueOrEmpty(item.PROSENTASE_BAYAR_PER_TGL || item.PROSENTASE_BAYAR);
            }, 'right');

            addColumn('Nilai BGB', function (item) {
                return formatNumber(item.NILAI_BGB);
            }, 'right');
        }

        if (modeTandaJadiAktif || modeGabungan) {
            if (modeTandaJadiAktif && !modeGabungan) {
                addColumn('Harga Jual<br>Setelah PPJB', function (item) {
                    return formatNumber(item.HARGA_JUAL_PPJB);
                }, 'right');
            }

            addColumn('No. Bukti<br>Tahap I', function (item) {
                return escapeHtml(getNoBuktiTahap1(item));
            });

            addColumn('Tgl. Bukti<br>Tahap I', function (item) {
                return formatDateIndo(getTglBuktiTahap1(item));
            }, 'center');
        }

        var html = '';

        html += '<div class="report-wrapper">';

        /*
         * GRID 1 — HEADER LAPORAN
         * Kiri: identitas perusahaan/unit. Tengah: judul laporan.
         * Kanan: periode & keterangan mode (isi tetap seperti sebelumnya).
         */
        html += '<div class="report-header">';
        html += '<div class="report-company">' + escapeHtml(companyText) + '</div>';
        html += '<div class="report-title">DAFTAR PENERIMAAN TANDA JADI SEBELUM DAN SESUDAH PPJB</div>';

        html += '<div class="report-period">';
        html += escapeHtml(judulTanggal) + ': ' + escapeHtml(formatDateIndo(tglAwal)) + ' s.d ' + escapeHtml(formatDateIndo(tglAkhir));

        if (perTglBayar) {
            html += '<br>Pembayaran per: ' + escapeHtml(formatDateIndo(tglBayar));
        }

        if (modeGabungan) {
            html += '<br>Mode: Per Tgl Bayar + Tanda Jadi Aktif';
        }

        html += '<br>Lokasi: ' + escapeHtml(lokasiText);
        html += '<br>Jenis Bgn: ' + escapeHtml(jenisText);
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
         * Kolom, urutan, dan isi data tidak diubah.
         */
        var tableMinWidth = Math.max(1500, columns.length * 115);

        html += '<div id="reportTableContainer" class="report-table-container">';
        html += '<table id="reportGeneratedTable" class="table table-bordered report-table" style="min-width:' + tableMinWidth + 'px;">';
        html += '<thead>';
        html += '<tr>';

        $.each(columns, function (index, column) {
            html += '<th>' + column.title + '</th>';
        });

        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';

        if (!data || data.length === 0) {
            html += '<tr>';
            html += '<td colspan="' + columns.length + '" class="empty-row">Data tidak ditemukan.</td>';
            html += '</tr>';
        } else {
            $.each(data, function (index, item) {
                html += '<tr>';

                $.each(columns, function (columnIndex, column) {
                    var style = '';

                    if (column.align === 'right') {
                        style = ' style="text-align:right;"';
                    } else if (column.align === 'center') {
                        style = ' style="text-align:center;"';
                    }

                    html += '<td' + style + '>' + column.render(item, index) + '</td>';
                });

                html += '</tr>';
            });
        }

        html += '</tbody>';
        html += '</table>';
        html += '</div>';
        html += '</div>';

        $('#main-display').html(html);

        if (!data || data.length === 0) {
            showSuratPesananToast('Data tidak ada');
        }
    }

    
    function cleanCustomerName(value) {
        if (value === null || value === undefined) {
            return '';
        }

        var name = String(value).trim();

        if (name === '') {
            return '';
        }


        name = name
            .replace(/""/g, '"')
            .replace(/\)\s*,\s*\(/g, ' / ')
            .replace(/\)\s*;\s*\(/g, ' / ')
            .replace(/\)\s*\|\s*\(/g, ' / ')
            .replace(/[()"']/g, '')
            .replace(/\s*\/\s*/g, ' / ')
            .replace(/\s*,\s*/g, ', ')
            .replace(/\s+/g, ' ')
            .trim();

        return name;
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
            maximumFractionDigits: 6
        });
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

    function printSuratPesananReport() {
        if (!$('#main-display .report-wrapper').length) {
            alert('Silakan klik View terlebih dahulu untuk menampilkan laporan.');
            return;
        }

        var reportHtml = $('#main-display').html();

        if (!reportHtml) {
            return;
        }

        /*
         * Mekanisme print disamakan dengan Daftar Rencana Serah Terima:
         * - menggunakan iframe terisolasi;
         * - tidak membuka tab/window baru;
         * - langsung memanggil native print dialog browser;
         * - tidak mengunci size/orientation pada @page sehingga browser tetap
         *   menampilkan pilihan Portrait / Landscape dan Paper size.
         */
        $('#suratPesananNativePrintFrame').remove();

        var frame = document.createElement('iframe');
        frame.id = 'suratPesananNativePrintFrame';
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
                line-height: 1.15 !important;
            }

            .report-table th {
                text-align: center !important;
                font-weight: 700 !important;
            }

            .report-table tbody tr:nth-child(even) td,
            .report-table tbody tr:hover td {
                background: #fff !important;
            }

            .empty-row {
                height: 50px !important;
                color: #000 !important;
                background: #fff !important;
                text-align: center !important;
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
            + '<title>Daftar Surat Pesanan</title>'
            + '<style>' + printCss + '</style>'
            + '</head>'
            + '<body>' + reportHtml + '</body>'
            + '</html>'
        );
        frameDocument.close();
        applyPrintTableRules(frameDocument);

        var cleanupPrintFrame = function () {
            $('#suratPesananNativePrintFrame').remove();
        };

        try {
            frameWindow.onafterprint = cleanupPrintFrame;
        } catch (error) {
            // Fallback cleanup di bawah.
        }

        window.setTimeout(function () {
            try {
                frameWindow.focus();
                frameWindow.print();
            } catch (error) {
                console.error('Gagal membuka dialog print Surat Pesanan:', error);
                cleanupPrintFrame();
                alert('Dialog print gagal dibuka. Silakan coba kembali.');
            }
        }, 180);

        window.setTimeout(cleanupPrintFrame, 30000);
    }

    function excelSummary() {
        var form = $('<form>', {
            method: 'POST',
            action: '{{ url()->current() }}/export_excel',
            target: '_blank'
        });

        var data = getFilterData();

        data.tgl_awal = $('#tgl_awal').val();
        data.tgl_akhir = $('#tgl_akhir').val();
        data.tgl_bayar = $('#tgl_bayar').val();
        data.flag_tgl = $('input[name="flag_tgl"]:checked').val();
        data.per_tgl_bayar = $('#per_tgl_bayar').is(':checked') ? 'Y' : 'T';
        data.tanda_jadi_aktif = $('#tanda_jadi_aktif').is(':checked') ? 'Y' : 'T';

        data.lokasi_name = $('#lokasi option:selected').text();
        data.sektor_name = $.trim($('#sektorentry').text());
        data.agen_name = $.trim($('#agenentry').text());
        data.sales_name = $.trim($('#salesentry').text());
        data.jenis_name = $('#jenis option:selected').text();
        data.status_name = $('#status option:selected').text();

        $.each(data, function (key, value) {
            form.append($('<input>', {
                type: 'hidden',
                name: key,
                value: value
            }));
        });

        $('body').append(form);
        form.submit();
        form.remove();
    }
</script>

@endsection
