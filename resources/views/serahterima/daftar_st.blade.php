@extends('layouts.template')

@section('content')

<style>
    .serah-st-page {
        --st-navy: #082c55;
        --st-navy-dark: #061f3c;
        --st-orange: #ff7600;
        --st-orange-dark: #e85f00;
        --st-orange-soft: #fff1e4;
        --st-green: #159447;
        --st-green-soft: #eaf8ef;
        --st-blue: #2f6fed;
        --st-blue-soft: #edf3ff;
        --st-red: #dc2626;
        --st-red-soft: #fff1f2;
        --st-amber: #ef7a13;
        --st-amber-soft: #fff2e7;
        --st-text: #10233f;
        --st-muted: #6b7280;
        --st-line: #e7e9ee;
        width: 100%;
        padding: 2px 0 28px;
        color: var(--st-text);
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    }

    .serah-st-page,
    .serah-st-page * {
        box-sizing: border-box;
    }

    .serah-st-page .modern-card {
        border: 1px solid rgba(15, 35, 65, 0.08);
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 7px 24px rgba(15, 35, 65, 0.08);
    }

    .page-hero {
        position: relative;
        display: flex;
        min-height: 88px;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 16px;
        padding: 18px 22px;
        overflow: hidden;
        background:
            radial-gradient(circle at 92% 0%, rgba(255, 118, 0, 0.11), transparent 33%),
            linear-gradient(90deg, #ffffff 0%, #ffffff 57%, #fffaf5 100%);
    }

    .page-heading {
        position: relative;
        z-index: 2;
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 15px;
    }

    .page-heading-icon {
        display: inline-flex;
        width: 58px;
        height: 58px;
        flex: 0 0 58px;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        background: linear-gradient(145deg, #ff8a16, #f36500);
        color: #ffffff;
        box-shadow: 0 10px 22px rgba(255, 118, 0, 0.27);
        font-size: 25px;
    }

    .page-heading-copy {
        min-width: 0;
    }

    .page-heading h1 {
        margin: 0 0 4px;
        color: var(--st-text);
        font-size: clamp(19px, 1.65vw, 27px);
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -0.35px;
    }

    .page-heading p {
        margin: 0;
        color: #5f6878;
        font-size: 13px;
        font-weight: 500;
    }

    .page-hero-art {
        position: absolute;
        right: 16px;
        bottom: -5px;
        width: min(430px, 34vw);
        height: 82px;
        color: rgba(255, 118, 0, 0.34);
        pointer-events: none;
    }

    .page-hero-art svg {
        width: 100%;
        height: 100%;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 16px;
    }

    .summary-card {
        display: flex;
        min-height: 98px;
        align-items: center;
        gap: 15px;
        padding: 16px 18px;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(15, 35, 65, 0.11);
    }

    .summary-icon {
        display: inline-flex;
        width: 58px;
        height: 58px;
        flex: 0 0 58px;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        font-size: 26px;
    }

    .summary-icon.orange { color: var(--st-orange); background: var(--st-orange-soft); }
    .summary-icon.green { color: var(--st-green); background: var(--st-green-soft); }
    .summary-icon.amber { color: var(--st-amber); background: var(--st-amber-soft); }
    .summary-icon.blue { color: var(--st-blue); background: var(--st-blue-soft); }
    .summary-icon.red { color: var(--st-red); background: var(--st-red-soft); }

    .summary-copy { min-width: 0; }

    .summary-label {
        margin-bottom: 2px;
        color: #22304a;
        font-size: 13px;
        font-weight: 700;
    }

    .summary-value {
        color: #0d2548;
        font-size: 27px;
        font-weight: 800;
        line-height: 1.05;
        font-variant-numeric: tabular-nums;
    }

    .summary-caption {
        margin-top: 3px;
        overflow: hidden;
        color: #687080;
        font-size: 11.5px;
        font-weight: 500;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .summary-caption strong { color: #263b5d; }

    .filter-card {
        position: relative;
        margin-bottom: 16px;
        padding: 0;
        overflow: hidden;
        border-left: 3px solid var(--st-orange) !important;
    }

    .filter-card-header {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 13px 20px 5px;
        color: #1b2e4b;
        font-size: 13px;
        font-weight: 800;
    }

    .filter-card-header i { color: var(--st-orange); }

    .filter-layout {
        display: grid;
        grid-template-columns: minmax(320px, 1.25fr) minmax(230px, 0.95fr) minmax(245px, 0.95fr) 175px;
        gap: 20px 28px;
        align-items: start;
        padding: 12px 20px 18px;
    }

    .filter-section { min-width: 0; }

    .filter-field {
        min-width: 0;
        margin-bottom: 12px;
    }

    .filter-field:last-child { margin-bottom: 0; }

    .filter-label {
        display: block;
        margin-bottom: 7px;
        color: #263957;
        font-size: 12px;
        font-weight: 750;
    }

    .field-control,
    .lookup-display {
        width: 100%;
        min-height: 42px;
        padding: 9px 12px;
        border: 1px solid #d8dde6;
        border-radius: 9px;
        background: #ffffff;
        color: #243650;
        font-size: 12.5px;
        font-weight: 500;
        box-shadow: 0 1px 2px rgba(15, 35, 65, 0.025);
        transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .field-control:hover,
    .lookup-display:hover { border-color: #b7c0cd; }

    .field-control:focus {
        border-color: var(--st-orange);
        box-shadow: 0 0 0 3px rgba(255, 118, 0, 0.12);
        outline: none;
    }

    input[type="date"].field-control { font-variant-numeric: tabular-nums; }

    .date-range,
    .block-range {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 34px minmax(0, 1fr);
        align-items: center;
        gap: 8px;
    }

    .range-text {
        color: #667085;
        font-size: 11px;
        font-weight: 700;
        text-align: center;
        white-space: nowrap;
    }

    .lookup-control {
        display: flex;
        min-width: 0;
        align-items: stretch;
    }

    .lookup-display {
        display: flex;
        min-width: 0;
        flex: 1;
        align-items: center;
        overflow: hidden;
        border-radius: 9px 0 0 9px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .lookup-button {
        display: inline-flex;
        width: 48px;
        min-width: 48px;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid #d8dde6;
        border-left: 0;
        border-radius: 0 9px 9px 0;
        background: #ffffff;
        color: var(--st-navy);
        cursor: pointer;
        transition: color 0.18s ease, background 0.18s ease;
    }

    .lookup-button:hover {
        color: var(--st-orange-dark);
        background: #fff8f1;
    }

    .choice-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 18px;
        min-height: 42px;
        align-items: center;
    }

    .choice-pill,
    .accounting-check {
        display: inline-flex;
        min-height: 38px;
        align-items: center;
        gap: 8px;
        margin: 0;
        padding: 6px 2px;
        color: #344054;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }

    .choice-pill input[type="radio"] {
        appearance: none;
        -webkit-appearance: none;
        width: 19px;
        height: 19px;
        flex: 0 0 19px;
        margin: 0;
        border: 1.5px solid #c4cad4;
        border-radius: 50%;
        background: #ffffff;
        box-shadow: inset 0 0 0 4px #ffffff;
        cursor: pointer;
        transition: all 0.16s ease;
    }

    .choice-pill input[type="radio"]:checked {
        border-color: var(--st-orange);
        background: var(--st-orange);
        box-shadow: inset 0 0 0 4px #ffffff, 0 0 0 3px rgba(255, 118, 0, 0.10);
    }

    .choice-pill.is-selected,
    .accounting-check.is-selected { color: #172b48; }

    .accounting-check input[type="checkbox"] {
        appearance: none;
        -webkit-appearance: none;
        position: relative;
        width: 19px;
        height: 19px;
        flex: 0 0 19px;
        margin: 0;
        border: 1.5px solid #c4cad4;
        border-radius: 5px;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.16s ease;
    }

    .accounting-check input[type="checkbox"]:checked {
        border-color: var(--st-orange);
        background: var(--st-orange);
        box-shadow: 0 0 0 3px rgba(255, 118, 0, 0.10);
    }

    .accounting-check input[type="checkbox"]:checked::after {
        position: absolute;
        top: 1px;
        left: 5px;
        width: 6px;
        height: 11px;
        border: solid #ffffff;
        border-width: 0 2px 2px 0;
        content: "";
        transform: rotate(45deg);
    }

    .filter-actions {
        display: flex;
        min-height: 142px;
        flex-direction: column;
        justify-content: center;
        gap: 10px;
        padding-left: 20px;
        border-left: 1px solid #e3e6eb;
    }

    .action-button {
        display: inline-flex;
        width: 100%;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        gap: 9px;
        border: 0;
        border-radius: 9px;
        color: #ffffff;
        font-size: 13px;
        font-weight: 750;
        cursor: pointer;
        transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
    }

    .action-button:hover {
        color: #ffffff;
        filter: brightness(1.04);
        transform: translateY(-1px);
    }

    .view-button {
        background: linear-gradient(135deg, #082c55, #061f3c);
        box-shadow: 0 7px 15px rgba(8, 44, 85, 0.22);
    }

    .excel-button {
        background: linear-gradient(135deg, #22a953, #12863e);
        box-shadow: 0 7px 15px rgba(21, 148, 71, 0.20);
    }

    .result-card {
        position: relative;
        min-height: 250px;
        overflow: hidden;
    }

    .serah-st-page #loading-info {
        position: absolute;
        top: 14px;
        right: 16px;
        z-index: 5;
        padding: 9px 12px;
        color: #8a4300;
        border: 1px solid #ffd3ae;
        border-radius: 9px;
        background: #fff5eb;
        box-shadow: 0 5px 15px rgba(140, 68, 0, 0.08);
        font-size: 12px;
        font-weight: 700;
    }

    .serah-st-page #loading-info i {
        margin-right: 6px;
        color: var(--st-orange);
    }

    .initial-state {
        display: flex;
        min-height: 165px;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 10px;
        margin: 14px;
        border: 1px dashed #d9dee7;
        border-radius: 13px;
        color: #778195;
        background: #fcfdff;
        text-align: center;
    }

    .initial-state-icon {
        display: inline-flex;
        width: 48px;
        height: 48px;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: var(--st-blue-soft);
        color: var(--st-blue);
        font-size: 18px;
    }

    .report-wrapper {
        padding: 14px 18px 18px;
        background: #ffffff;
    }

    .report-title {
        margin: 0 0 10px;
        color: #10233f;
        text-align: center;
        font-family: Cambria, Georgia, "Times New Roman", serif;
        font-size: 18px;
        font-weight: 800;
        letter-spacing: 0.35px;
    }

    .report-table-container {
        width: 100%;
        max-height: calc(100vh - 260px);
        min-height: 260px;
        overflow: auto;
        border: 1px solid #f0d8c5;
        border-radius: 12px;
        background: #ffffff;
    }

    .report-table {
        width: max-content;
        min-width: 1520px;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 11px;
    }

    .report-table.accounting-report-table {
        min-width: 1080px;
    }

    .report-table th {
        position: sticky;
        top: 0;
        z-index: 3;
        padding: 10px 11px;
        color: #142842;
        border-top: 0;
        border-left: 0;
        border-right: 1px solid #f0cfb5;
        border-bottom: 1px solid #e9c29f;
        background: linear-gradient(180deg, #fff7ef, #ffead9);
        text-align: center;
        vertical-align: middle;
        font-size: 11.5px;
        font-weight: 800;
        line-height: 1.25;
        white-space: nowrap;
    }

    .report-table td {
        padding: 10px 11px;
        color: #283b57;
        border-top: 0;
        border-left: 0;
        border-right: 1px solid #e5e8ed;
        border-bottom: 1px solid #e5e8ed;
        background: #ffffff;
        text-align: center;
        vertical-align: middle;
        font-size: 11.5px;
        line-height: 1.32;
        white-space: nowrap;
    }

    .report-table tbody tr:nth-child(even) td { background: #fbfcfe; }
    .report-table tbody tr:hover td { background: #fff8f2; }

    .report-table .name-cell {
        min-width: 230px;
        text-align: left;
    }

    .report-table .multiline-cell {
        min-width: 185px;
        white-space: normal;
    }

    .report-table .address-cell {
        min-width: 360px;
        max-width: 500px;
        text-align: left;
        white-space: normal;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 70px;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
    }

    .status-badge.realized {
        color: #12653a;
        background: #eaf8ef;
    }

    .status-badge.pending {
        color: #a04a00;
        background: #fff2e7;
    }

    .status-badge.cancelled {
        color: #b91c1c;
        background: #fff1f2;
    }

    .empty-row {
        height: 105px;
        color: #718096 !important;
        background: #fcfdff !important;
        text-align: center !important;
        font-style: italic;
    }

    #rencanaSTModal .modal-dialog {
        width: calc(100vw - 32px);
        max-width: 850px;
        margin: 24px auto;
    }

    #rencanaSTModal .modal-content {
        overflow: hidden;
        border: 0;
        border-radius: 16px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
    }

    #rencanaSTModal .modal-header {
        padding: 15px 18px;
        border-bottom: 1px solid #ebecef;
        background: #fffaf5;
    }

    #rencanaSTModal .modal-header h5 {
        color: #142842;
        font-size: 16px;
        font-weight: 800;
    }

    #rencanaSTModal .modal-body {
        padding: 18px;
        background: #f8fafc;
    }

    .modal-search-wrapper {
        position: relative;
        margin-bottom: 12px;
    }

    .modal-search-wrapper i {
        position: absolute;
        top: 50%;
        left: 14px;
        z-index: 2;
        color: #7b8492;
        transform: translateY(-50%);
    }

    #modalSearchInput { padding-left: 39px; }

    .modal-table-wrapper {
        max-height: 430px;
        overflow: auto;
        border: 1px solid #e0e4ea;
        border-radius: 11px;
        background: #ffffff;
    }

    .modal-table {
        width: 100%;
        min-width: 620px;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    #rencanaSTModal .modal-table th {
        position: sticky;
        top: 0;
        z-index: 2;
        padding: 11px 12px;
        color: #142842;
        border-right: 1px solid #f0cfb5;
        border-bottom: 1px solid #e9c29f;
        background: #fff0e2;
        text-align: center;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    #rencanaSTModal .modal-table td {
        padding: 10px 12px;
        color: #475569;
        border-right: 1px solid #eef0f4;
        border-bottom: 1px solid #eef0f4;
        background: #ffffff;
        text-align: center;
        font-size: 12px;
        cursor: pointer;
    }

    #rencanaSTModal .modal-table tbody tr:nth-child(even) td { background: #fbfcfe; }
    #rencanaSTModal .modal-table tbody tr:hover td { color: #142842; background: #fff8f2; }

    @media (max-width: 1290px) {
        .filter-layout {
            grid-template-columns: minmax(300px, 1.25fr) minmax(210px, 0.9fr) minmax(230px, 0.9fr) 150px;
            gap: 18px;
        }

        .filter-actions { padding-left: 16px; }
    }

    @media (max-width: 1120px) {
        .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .filter-layout { grid-template-columns: repeat(2, minmax(0, 1fr)); }

        .filter-actions {
            min-height: 0;
            flex-direction: row;
            grid-column: 1 / -1;
            padding: 16px 0 0;
            border-top: 1px solid #e8eaee;
            border-left: 0;
        }

        .filter-actions .action-button { width: 150px; }
    }

    @media (max-width: 720px) {
        .page-hero { padding: 16px; }
        .page-heading-icon { width: 50px; height: 50px; flex-basis: 50px; }
        .page-heading p, .page-hero-art { display: none; }
        .summary-grid, .filter-layout { grid-template-columns: 1fr; }
        .summary-card { min-height: 88px; }
        .filter-layout { padding: 12px 14px 16px; }
        .filter-card-header { padding-left: 14px; }
        .filter-actions { flex-direction: column; }
        .filter-actions .action-button { width: 100%; }
        .date-range, .block-range { grid-template-columns: 1fr; }
        .range-text { text-align: left; }
    }


    /* =========================================================
       BLUE THEME OVERRIDE — STRUKTUR TETAP
       Menyamakan warna Daftar Serah Terima dengan
       Daftar Rencana Serah Terima.
       Perubahan hanya visual serta ringkasan atas menjadi 3 kartu.
       ========================================================= */
    .serah-st-page {
        --st-navy: #2563eb;
        --st-navy-dark: #1d4ed8;
        --st-orange: #2563eb;
        --st-orange-dark: #1d4ed8;
        --st-orange-soft: #dbeafe;
        --st-green: #16a34a;
        --st-green-soft: #f2fbf5;
        --st-blue: #2563eb;
        --st-blue-soft: #dbeafe;
        --st-amber: #d97706;
        --st-amber-soft: #fffbeb;
        --st-text: #0f172a;
        --st-muted: #64748b;
        --st-line: #e5e7eb;
    }

    .serah-st-page .modern-card {
        border-color: #e5e7eb;
        border-radius: 18px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
    }

    .serah-st-page .page-hero {
        background:
            radial-gradient(
                circle at 92% 0%,
                rgba(37, 99, 235, 0.08),
                transparent 34%
            ),
            linear-gradient(
                90deg,
                #ffffff 0%,
                #ffffff 58%,
                #f8fafc 100%
            );
    }

    .serah-st-page .page-heading-icon {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25);
    }

    .serah-st-page .page-hero-art {
        color: rgba(37, 99, 235, 0.24);
    }

    .serah-st-page .summary-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .serah-st-page .summary-icon.orange,
    .serah-st-page .summary-icon.blue {
        color: #2563eb;
        background: #dbeafe;
    }

    .serah-st-page .summary-icon.green {
        color: #16a34a;
        background: #f2fbf5;
    }

    .serah-st-page .summary-icon.amber {
        color: #d97706;
        background: #fffbeb;
    }

    .serah-st-page .filter-card {
        border-left-color: #2563eb !important;
    }

    .serah-st-page .filter-card-header i {
        color: #2563eb;
    }

    .serah-st-page .field-control,
    .serah-st-page .lookup-display {
        color: #0f172a;
        border-color: #d1d5db;
        background: #fbfdff;
    }

    .serah-st-page .field-control:focus {
        border-color: #2563eb;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .serah-st-page .lookup-button {
        color: #2563eb;
        border-color: #d1d5db;
        background: #f8fafc;
    }

    .serah-st-page .lookup-button:hover,
    .serah-st-page .lookup-button:focus {
        color: #1d4ed8;
        border-color: #2563eb;
        background: #dbeafe;
        outline: 0;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .serah-st-page .choice-pill input[type="radio"]:checked {
        border-color: #2563eb;
        background: #2563eb;
        box-shadow:
            inset 0 0 0 4px #ffffff,
            0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .serah-st-page .accounting-check input[type="checkbox"]:checked {
        border-color: #2563eb;
        background: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .serah-st-page .action-button {
        border: 1px solid transparent;
        border-radius: 8px;
    }

    .serah-st-page .view-button {
        color: #ffffff;
        background: #2563eb;
        box-shadow: 0 5px 12px rgba(37, 99, 235, 0.22);
    }

    .serah-st-page .view-button:hover,
    .serah-st-page .view-button:focus {
        color: #ffffff;
        background: #1d4ed8;
    }

    .serah-st-page .excel-button {
        color: #16a34a;
        border-color: #bbdec8;
        background: #f2fbf5;
        box-shadow: none;
    }

    .serah-st-page .excel-button:hover,
    .serah-st-page .excel-button:focus {
        color: #16a34a;
        border-color: #86c99d;
        background: #e7f7ec;
    }

    .serah-st-page #loading-info {
        color: #1d4ed8;
        border-color: #bfdbfe;
        background: #eff6ff;
        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.08);
    }

    .serah-st-page #loading-info i {
        color: #2563eb;
    }

    .serah-st-page .initial-state {
        border-color: #d1d5db;
        background: linear-gradient(180deg, #fcfdff 0%, #f8fafc 100%);
    }

    #rencanaSTModal .modal-header {
        border-bottom-color: #e5e7eb;
        background: #f8fafc;
    }

    #rencanaSTModal .modal-table th {
        color: #0f172a;
        border-right-color: #dbe4f0;
        border-bottom-color: #cbd5e1;
        background: #eff6ff;
    }

    #rencanaSTModal .modal-table td {
        color: #475569;
        border-right-color: #eef2f7;
        border-bottom-color: #eef2f7;
    }

    #rencanaSTModal .modal-table tbody tr:nth-child(even) td {
        background: #f8fafc;
    }

    #rencanaSTModal .modal-table tbody tr:hover td {
        color: #0f172a;
        background: #f8fbff;
    }

    @media (max-width: 1120px) {
        .serah-st-page .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 720px) {
        .serah-st-page .summary-grid {
            grid-template-columns: 1fr;
        }
    }



    /* =========================================================
       HEADER ICON MATCH — DAFTAR SERTIPIKAT PECAHAN
       Hanya ikon kiri atas yang disamakan. Struktur/fungsi lain tetap.
       ========================================================= */
    .serah-st-page .page-heading-icon.sertipikat-style-heading-icon {

        
        width: 34px !important;
        height: 34px !important;
        min-width: 34px !important;
        flex: 0 0 34px !important;
        border: 0 !important;
        border-radius: 11px !important;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.24) !important;
        font-size: 18px !important;
        font-weight: 700 !important;
        line-height: 1 !important;

    }

    @media (max-width: 720px) {
        .serah-st-page .page-heading-icon.sertipikat-style-heading-icon {
            width: 34px !important;
            height: 34px !important;
            flex-basis: 34px !important;
        }
    }



    /* FONT STANDARD — MATCH DAFTAR SURAT PESANAN */
    .serah-st-page,
    .serah-st-page input,
    .serah-st-page select,
    .serah-st-page button,
    .serah-st-page textarea,
    .serah-st-page label,
    .serah-st-page table,
    .serah-st-page td,
    .serah-st-page .page-heading p {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
    }

    .serah-st-page .page-heading h1,
    .serah-st-page .filter-label,
    .serah-st-page .summary-label,
    .serah-st-page .report-table th,
    .serah-st-page .modal-header h5 {
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
    }

    /* =========================================================
       TAMPILAN HASIL LAPORAN — MENGIKUTI DAFTAR SERTIFIKAT PECAHAN
       Struktur: 3 grid (header → subtitle → tabel).
       Catatan: HANYA tampilan/gaya. Kolom, urutan kolom, dan isi
       data laporan tidak diubah sama sekali.
       ========================================================= */
    .serah-st-page .report-wrapper {
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

    .serah-st-page .report-wrapper::before {
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
    .serah-st-page .report-header {
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

    .serah-st-page .report-company {
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 850;
        letter-spacing: 0.04em;
        line-height: 1.45;
    }

    .serah-st-page .report-title {
        margin: 0 !important;
        color: #172033 !important;
        text-align: center !important;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
        font-size: 18px !important;
        font-weight: 950 !important;
        letter-spacing: -0.02em !important;
        line-height: 1.25;
    }

    .serah-st-page .report-period {
        color: #475467;
        text-align: right;
        font-size: 10.5px;
        font-weight: 650;
        line-height: 1.55;
    }

    /* --- GRID 2 : BARIS SEKTOR/CLUSTER --- */
    .serah-st-page .report-subtitle {
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

    .serah-st-page .report-subtitle-label {
        justify-self: start;
        color: #667085;
        white-space: nowrap;
    }

    .serah-st-page .report-subtitle-value {
        justify-self: center;
        color: #344054;
        font-weight: 850;
        text-align: center;
    }

    .serah-st-page .report-subtitle strong {
        color: #344054;
        font-weight: 850;
    }

    .serah-st-page .report-live-badge {
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

    .serah-st-page .report-live-badge::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
    }

    /* --- GRID 3 : TABEL LAPORAN --- */
    .serah-st-page .report-table-container {
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

    .serah-st-page .report-table-container::-webkit-scrollbar {
        width: 11px;
        height: 13px;
    }

    .serah-st-page .report-table-container::-webkit-scrollbar-track {
        border-radius: 999px;
        background: #eff3f7;
    }

    .serah-st-page .report-table-container::-webkit-scrollbar-thumb {
        border: 2px solid #eff3f7;
        border-radius: 999px;
        background: #93c5fd;
    }

    .serah-st-page .report-table-container::-webkit-scrollbar-thumb:hover {
        background: #60a5fa;
    }

    .serah-st-page .report-table {
        margin-bottom: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        background: #ffffff !important;
        color: #344054 !important;
        font-size: 10.5px !important;
    }

    .serah-st-page .report-table th {
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

    .serah-st-page .report-table td {
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

    .serah-st-page .report-table tbody tr:nth-child(even) td {
        background: #fbfcfe !important;
    }

    .serah-st-page .report-table tbody tr:hover td {
        background: #f0f7ff !important;
    }

    .serah-st-page .report-table tbody tr:hover td:first-child {
        color: #1d4ed8 !important;
        box-shadow: inset 4px 0 0 #2563eb !important;
    }

    .serah-st-page .report-table .empty-row {
        height: 130px !important;
        color: #64748b !important;
        background: #ffffff !important;
        font-style: normal !important;
        text-align: center !important;
    }

    @media (max-width: 767.98px) {
        .serah-st-page .report-header {
            grid-template-columns: minmax(0, 1fr);
            gap: 8px;
            text-align: center;
        }

        .serah-st-page .report-period {
            text-align: center;
        }

        .serah-st-page .report-subtitle {
            grid-template-columns: minmax(0, 1fr);
            justify-items: center;
            text-align: center !important;
        }

        .serah-st-page .report-subtitle-label,
        .serah-st-page .report-live-badge {
            justify-self: center;
        }
    }


    /*
     * Panel hasil menjadi bingkai luar seperti fitur lain, sehingga kartu
     * laporan di dalamnya tidak menempel pada tepi panel.
     */
    .serah-st-page .result-card {
        padding: 20px;
    }

    .serah-st-page .initial-state {
        margin: 0;
    }

    /* Modal alert data kosong, mengikuti Daftar Surat Pesanan. */
    #serahSTNoDataAlertModal .modal-dialog {
        max-width: 380px;
    }

    #serahSTNoDataAlertModal .modal-content {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
    }

    #serahSTNoDataAlertModal .alert-icon-wrapper {
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

    #serahSTNoDataAlertModal .alert-title {
        margin-bottom: 8px;
        color: #172033;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 18px;
        font-weight: 700;
    }

    #serahSTNoDataAlertModal .alert-message {
        margin-bottom: 24px;
        color: #475569;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 14px;
    }

    #serahSTNoDataAlertModal .alert-btn-ok {
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

    #serahSTNoDataAlertModal .alert-btn-ok:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
    }

</style>

<div class="serah-st-page">
    <div class="modal fade" id="serahSTNoDataAlertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="alert-icon-wrapper">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="alert-title">Information</div>
                    <div class="alert-message">Data Serah Terima Periode tidak ada......!</div>
                    <button
                        type="button"
                        class="btn alert-btn-ok"
                        onclick="$('#serahSTNoDataAlertModal').modal('hide')"
                    >OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="rencanaSTModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header d-flex" style="justify-content:space-between;">
                    <h5 id="rencanaSTModalTitle" style="margin:0;"></h5>
                    <button type="button" class="btn btn-light" onclick="toggleModal('hide')" aria-label="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" id="rencanaSTModalContent"></div>
            </div>
        </div>
    </div>

    <section class="modern-card page-hero">
        <div class="page-heading">
            <div class="page-heading-icon sertipikat-style-heading-icon" aria-hidden="true">◈</div>
            <div class="page-heading-copy">
                <h1>Daftar Serah Terima</h1>
            </div>
        </div>

        <div class="page-hero-art" aria-hidden="true">
            <svg viewBox="0 0 520 105" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 91C70 87 112 89 162 87C215 85 240 74 277 76C324 78 360 92 520 88" stroke="currentColor" stroke-width="1.5"/>
                <path d="M183 84V47L217 28L250 47V84M192 84V52H240V84M205 60H214V70H205V60ZM223 60H232V70H223V60Z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M260 84V39L298 15L337 39V84M270 84V43H327V84M283 51H293V62H283V51ZM305 51H315V62H305V51ZM283 68H293V79H283V68ZM305 68H315V79H305V68Z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M346 84V55L374 39L403 55V84M355 84V59H394V84M367 66H377V77H367V66Z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M428 84V68M428 69C418 66 415 58 420 51C424 44 434 45 438 52C442 59 438 67 428 69ZM462 84V72M462 72C453 69 451 62 455 56C459 50 467 50 471 56C475 62 471 70 462 72Z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M407 21C446 8 481 8 520 18M402 28C449 13 483 14 520 25M397 35C445 19 483 20 520 33M393 42C443 26 483 27 520 40" stroke="currentColor" stroke-width="1" opacity="0.6"/>
            </svg>
        </div>
    </section>

    <section class="summary-grid" aria-label="Ringkasan daftar serah terima">
        <article class="modern-card summary-card">
            <div class="summary-icon orange"><i class="fas fa-list-ol"></i></div>
            <div class="summary-copy">
                <div class="summary-label">Total Data</div>
                <div class="summary-value" id="stat-total">0</div>
                <div class="summary-caption">Semua hasil filter</div>
            </div>
        </article>

        <article class="modern-card summary-card">
            <div class="summary-icon green"><i class="far fa-check-circle"></i></div>
            <div class="summary-copy">
                <div class="summary-label">Sudah Realisasi</div>
                <div class="summary-value" id="stat-realized">0</div>
                <div class="summary-caption"><strong id="stat-realized-percent">0.00%</strong> dari total</div>
            </div>
        </article>

        <article class="modern-card summary-card">
            <div class="summary-icon amber"><i class="fas fa-hourglass-half"></i></div>
            <div class="summary-copy">
                <div class="summary-label">Belum Realisasi</div>
                <div class="summary-value" id="stat-pending">0</div>
                <div class="summary-caption"><strong id="stat-pending-percent">0.00%</strong> dari total</div>
            </div>
        </article>

        
    </section>

    <section class="modern-card filter-card">
        <div class="filter-card-header">
            <i class="fas fa-filter"></i>
            <span>Filter Pencarian</span>
        </div>

        <input type="hidden" id="perusahaan" value="{{ session('kd_unit') ?? 'DTSA' }}">
        <input
            type="hidden"
            id="nama_perusahaan_session"
            value="{{ $namaPerusahaan ?? $nama_perusahaan ?? $namaPt ?? $nama_pt ?? session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? session('nama_lokasi') ?? session('deskripsi_lokasi') ?? session('lokasi') ?? '' }}"
        >

        <div class="filter-layout">
            <div class="filter-section">
                <div class="filter-field">
                    <label class="filter-label" for="tgl_awal">Tgl. Surat ST</label>
                    <div class="date-range">
                        <input type="date" id="tgl_awal" class="field-control" value="{{ now()->format('Y-m-d') }}" autocomplete="off">
                        <span class="range-text">s.d</span>
                        <input type="date" id="tgl_akhir" class="field-control" value="{{ now()->format('Y-m-d') }}" autocomplete="off">
                    </div>
                </div>

                <div class="filter-field">
                    <label class="filter-label" for="tgl_st_awal">Tgl. Realisasi ST</label>
                    <div class="date-range">
                        <input type="date" id="tgl_st_awal" class="field-control" value="{{ now()->format('Y-m-d') }}" autocomplete="off">
                        <span class="range-text">s.d</span>
                        <input type="date" id="tgl_st_akhir" class="field-control" value="{{ now()->format('Y-m-d') }}" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="filter-section">
                <div class="filter-field">
                    <label class="filter-label">Sektor/Cluster</label>
                    <div class="lookup-control">
                        <input type="hidden" id="sektor" value="*">
                        <div id="sektorentry" class="lookup-display">Semua Sektor</div>
                        <button type="button" class="lookup-button" onclick="getSektorModal()" aria-label="Cari sektor atau cluster">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <div class="filter-field">
                    <label class="filter-label" for="jenis">Jenis</label>
                    <select id="jenis" class="field-control">
                        <option value="*">Semua</option>
                        @foreach (($jenisList ?? []) as $jenis)
                            <option value="{{ $jenis->FLAG_LAPORAN }}">
                                {{ $jenis->DESKRIPSI }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="filter-section">
                <div class="filter-field">
                    <label class="filter-label" for="blok_awal">Blok</label>
                    <div class="block-range">
                        <input type="text" id="blok_awal" class="field-control" value="A" maxlength="20">
                        <span class="range-text">s.d</span>
                        <input type="text" id="blok_akhir" class="field-control" value="Z" maxlength="20">
                    </div>
                </div>

                <div class="filter-field">
                    <label class="filter-label" for="status_realisasi">Sts Realisasi</label>
                    <select id="status_realisasi" class="field-control">
                        <option value="*">Semua</option>
                        <option value="Y">Sudah Realisasi</option>
                        <option value="T">Belum Realisasi</option>
                    </select>
                </div>

                <div class="filter-field">
                    <span class="filter-label">Sts Aktif</span>
                    <div class="choice-group">
                        <label class="choice-pill">
                            <input type="radio" name="aktif" value="A" checked>
                            <span>Aktif</span>
                        </label>
                        <label class="choice-pill">
                            <input type="radio" name="aktif" value="B">
                            <span>Batal</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="filter-section">
                <div class="filter-field">
                    <label class="filter-label" for="sts_dtp">Ikut PPN DTP</label>
                    <select id="sts_dtp" class="field-control">
                        <option value="*">Semua</option>
                        <option value="Y">Ikut PPN DTP</option>
                        <option value="T">Tidak Ikut PPN DTP</option>
                    </select>
                </div>

                <div class="filter-field">
                    <span class="filter-label">Mode Laporan</span>
                    <label class="accounting-check">
                        <input type="checkbox" id="versi_accounting" value="Y">
                        <span>Versi Accounting</span>
                    </label>
                </div>

                <div class="filter-actions">
                    <button type="button" class="action-button view-button" onclick="getSummary()">
                        <i class="far fa-eye"></i>
                        <span>View</span>
                    </button>
                    <button type="button" class="action-button excel-button" onclick="printSerahSTReport()">
                        <i class="fas fa-print"></i>
                        <span>Print</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="modern-card result-card">
        <div id="loading-info" style="display:none;">
            <i class="fas fa-spinner fa-spin"></i>
            Mengambil data...
        </div>
        <div id="main-display">
            <div class="initial-state">
                <div class="initial-state-icon"><i class="fas fa-table"></i></div>
                <div>Silakan pilih filter lalu klik <strong>View</strong>.</div>
            </div>
        </div>
    </section>
</div>

@endsection

@section('js')
<script>
    $(document).ready(function () {
        bindUppercaseBlock();
        bindFilterState();
        resetPageState();

        window.addEventListener('pageshow', function () {
            resetPageState();
        });
    });

    function setDefaultDate() {
        var now = new Date();
        var year = now.getFullYear();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var today = year + '-' + month + '-' + day;

        $('#tgl_awal').val(today);
        $('#tgl_akhir').val(today);
        $('#tgl_st_awal').val(today);
        $('#tgl_st_akhir').val(today);
    }

    function resetPageState() {
        $('#serahSTNoDataAlertModal').modal('hide');
        setDefaultDate();

        $('#sektor').val('*');
        $('#sektorentry').text('Semua Sektor');
        $('#jenis').val('*');
        $('#blok_awal').val('A');
        $('#blok_akhir').val('Z');
        $('#status_realisasi').val('*');
        $('#sts_dtp').val('*');
        $('input[name="aktif"][value="A"]').prop('checked', true);
        $('#versi_accounting').prop('checked', false);

        $('#loading-info').hide();
        $('#main-display').html(
            '<div class="initial-state">' +
                '<div class="initial-state-icon"><i class="fas fa-table"></i></div>' +
                '<div>Silakan pilih filter lalu klik <strong>View</strong>.</div>' +
            '</div>'
        );

        resetSummaryCards();
        syncFilterVisualState();
    }

    function bindUppercaseBlock() {
        $('#blok_awal, #blok_akhir').on('input', function () {
            $(this).val(String($(this).val() || '').toUpperCase());
        });
    }

    function bindFilterState() {
        $('input[name="aktif"], #versi_accounting').on('change', syncFilterVisualState);
    }

    function syncFilterVisualState() {
        $('input[name="aktif"]').each(function () {
            $(this)
                .closest('.choice-pill')
                .toggleClass('is-selected', this.checked);
        });

        var accounting = $('#versi_accounting').is(':checked');

        $('#versi_accounting')
            .closest('.accounting-check')
            .toggleClass('is-selected', accounting);

        $('#stat-accounting').text(accounting ? '1' : '0');
        $('#stat-accounting-caption').text(accounting ? 'Aktif' : 'Tidak aktif');
    }

    function toggleModal(action) {
        var modal = $('#rencanaSTModal');

        if (typeof modal.modal === 'function') {
            if (action === 'show') {
                modal.modal('show');
            } else if (action === 'hide') {
                modal.modal('hide');
            } else {
                modal.modal('toggle');
            }
            return;
        }

        var shouldShow = action === 'show' || (action !== 'hide' && !modal.is(':visible'));

        modal
            .toggleClass('show', shouldShow)
            .css('display', shouldShow ? 'block' : 'none')
            .attr('aria-hidden', shouldShow ? 'false' : 'true');

        $('body').toggleClass('modal-open', shouldShow);
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

    function insertModal(title, content) {
        var finalContent = '';

        finalContent += '<div class="modal-search-wrapper">';
        finalContent += '<i class="fas fa-search"></i>';
        finalContent += '<input type="text" id="modalSearchInput" class="field-control" placeholder="Cari kode atau nama sektor..." autocomplete="off" onkeyup="filterModalTable(this.value)">';
        finalContent += '</div>';
        finalContent += '<div class="modal-table-wrapper">' + content + '</div>';

        $('#rencanaSTModalTitle').text(title);
        $('#rencanaSTModalContent').html(finalContent);

        setTimeout(function () {
            $('#modalSearchInput').trigger('focus');
        }, 250);
    }

    function filterModalTable(keyword) {
        var searchText = String(keyword || '').toLowerCase().trim();

        $('#rencanaSTModal .modal-table tbody tr').each(function () {
            var rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.indexOf(searchText) !== -1);
        });
    }

    function addSektor(kode, deskripsi) {
        $('#sektor').val(kode || '*');
        $('#sektorentry').text(deskripsi || 'Semua Sektor');
        toggleModal('hide');
    }

    function getSektorModal() {
        var perusahaan = String($('#perusahaan').val() || '').trim();

        if (!perusahaan) {
            alert('Unit/perusahaan belum tersedia.');
            return;
        }

        var button = $('.lookup-button');
        var originalHtml = button.html();

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_sektor',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                perusahaan: perusahaan
            },
            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (response && Array.isArray(response.data) ? response.data : []);

                var html = '';

                html += '<table class="modal-table">';
                html += '<thead><tr>';
                html += '<th>Kode Sektor</th>';
                html += '<th>Deskripsi</th>';
                html += '<th>Perusahaan</th>';
                html += '</tr></thead><tbody>';

                html += '<tr onclick="addSektor(\'*\', \'Semua Sektor\')">';
                html += '<td>*</td>';
                html += '<td>Semua Sektor</td>';
                html += '<td>' + escapeHtml(perusahaan) + '</td>';
                html += '</tr>';

                $.each(rows, function (index, item) {
                    item = item || {};

                    var kode = item.KD_SEKTOR !== undefined && item.KD_SEKTOR !== null
                        ? item.KD_SEKTOR
                        : (item.kd_sektor !== undefined && item.kd_sektor !== null ? item.kd_sektor : '');

                    var deskripsi = item.DESKRIPSI !== undefined && item.DESKRIPSI !== null
                        ? item.DESKRIPSI
                        : (item.deskripsi !== undefined && item.deskripsi !== null ? item.deskripsi : '');

                    var kdPerusahaan = item.KD_PERUSAHAAN !== undefined && item.KD_PERUSAHAAN !== null
                        ? item.KD_PERUSAHAAN
                        : (item.kd_perusahaan !== undefined && item.kd_perusahaan !== null ? item.kd_perusahaan : perusahaan);

                    html += '<tr onclick="addSektor(\'' + escapeJs(kode) + '\', \'' + escapeJs(deskripsi) + '\')">';
                    html += '<td>' + escapeHtml(kode) + '</td>';
                    html += '<td>' + escapeHtml(deskripsi) + '</td>';
                    html += '<td>' + escapeHtml(kdPerusahaan) + '</td>';
                    html += '</tr>';
                });

                if (rows.length < 1) {
                    html += '<tr>';
                    html += '<td colspan="3" style="padding:28px;">';
                    html += 'Data sektor untuk unit <strong>' + escapeHtml(perusahaan) + '</strong> tidak ditemukan.';
                    html += '</td>';
                    html += '</tr>';
                }

                html += '</tbody></table>';

                insertModal('Pilih Sektor/Cluster', html);
                toggleModal('show');
            },
            error: function (xhr) {
                console.log(xhr.responseText);

                var message = 'Gagal mengambil data sektor.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += ' ' + xhr.responseJSON.message;
                }

                alert(message);
            },
            complete: function () {
                button.prop('disabled', false).html(originalHtml);
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

        $('input, textarea').not('.serah-st-page, script, style, noscript').each(function () {
            push($(this).val());
        });

        $('[title]').not('.serah-st-page, script, style, noscript').each(function () {
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
                $clone.find('.serah-st-page, script, style, noscript').remove();
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


    function getFilterData() {
        return {
            _token: '{{ csrf_token() }}',
            tgl_awal: $('#tgl_awal').val(),
            tgl_akhir: $('#tgl_akhir').val(),
            tgl_all: 'T',
            tgl_st_awal: $('#tgl_st_awal').val(),
            tgl_st_akhir: $('#tgl_st_akhir').val(),
            tgl_st_all: 'T',
            jenis: $('#jenis').val() || '*',
            sektor: $('#sektor').val() || '*',
            blok_awal: $('#blok_awal').val() || 'A',
            blok_akhir: $('#blok_akhir').val() || 'Z',
            status_realisasi: $('#status_realisasi').val() || '*',
            aktif: $('input[name="aktif"]:checked').val() || 'A',
            sts_dtp: $('#sts_dtp').val() || '*',
            perusahaan: $('#perusahaan').val(),
            versi_accounting: $('#versi_accounting').is(':checked') ? 'Y' : 'T'
        };
    }

    function validateFilter() {
        if (!$('#tgl_awal').val() || !$('#tgl_akhir').val()) {
            alert('Periode tanggal surat ST harus diisi.');
            return false;
        }

        if (!$('#tgl_st_awal').val() || !$('#tgl_st_akhir').val()) {
            alert('Periode tanggal realisasi ST harus diisi.');
            return false;
        }

        if ($('#tgl_awal').val() > $('#tgl_akhir').val()) {
            alert('Tanggal awal surat ST tidak boleh melebihi tanggal akhir.');
            return false;
        }

        if ($('#tgl_st_awal').val() > $('#tgl_st_akhir').val()) {
            alert('Tanggal awal realisasi ST tidak boleh melebihi tanggal akhir.');
            return false;
        }

        if (!$('#blok_awal').val() || !$('#blok_akhir').val()) {
            alert('Rentang blok harus diisi.');
            return false;
        }

        return true;
    }

    function getSummary() {
        if (!validateFilter()) {
            return;
        }

        $('#serahSTNoDataAlertModal').modal('hide');
        $('#loading-info').show();
        $('#main-display').html('');

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_summary',
            dataType: 'json',
            data: getFilterData(),
            success: function (data) {
                $('#loading-info').hide();
                renderReport(data);
            },
            error: function (xhr) {
                $('#loading-info').hide();
                console.log(xhr.responseText);

                var message = 'Gagal mengambil data serah terima.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message += ' ' + xhr.responseJSON.message;
                }

                $('#main-display').html(
                    '<div class="alert alert-danger" style="margin:14px;">' +
                    escapeHtml(message) +
                    '</div>'
                );
            }
        });
    }

    function formatDateIndo(value) {
        if (!value) {
            return '-';
        }

        var text = String(value);
        var match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);

        if (match) {
            return match[3] + '-' + match[2] + '-' + match[1];
        }

        var date = new Date(value);

        if (isNaN(date.getTime())) {
            return text;
        }

        return String(date.getDate()).padStart(2, '0') + '-' +
            String(date.getMonth() + 1).padStart(2, '0') + '-' +
            date.getFullYear();
    }

    function valueOrDash(value) {
        return value === null || value === undefined || value === '' ? '-' : value;
    }

    function normalizeSummaryRows(payload) {
        if (Array.isArray(payload)) {
            return payload;
        }

        if (payload && Array.isArray(payload.data)) {
            return payload.data;
        }

        if (payload && Array.isArray(payload.rows)) {
            return payload.rows;
        }

        if (payload && Array.isArray(payload.result)) {
            return payload.result;
        }

        return [];
    }

    function getItemValue(item, upperKey, lowerKey) {
        item = item || {};

        if (item[upperKey] !== undefined && item[upperKey] !== null) {
            return item[upperKey];
        }

        if (item[lowerKey] !== undefined && item[lowerKey] !== null) {
            return item[lowerKey];
        }

        return null;
    }

    function hasRealizationDate(item) {
        var value =
            getItemValue(item, 'TGL_SERAH_TERIMA', 'tgl_serah_terima') ||
            getItemValue(item, 'TGL_REALISASI', 'tgl_realisasi') ||
            '';

        value = String(value).trim();

        return value !== '' &&
            value !== '-' &&
            value !== '0000-00-00' &&
            value !== '0000-00-00 00:00:00';
    }

    function isCancelled(item) {
        var statusAktif = String(
            getItemValue(item, 'STATUS_AKTIF', 'status_aktif') ||
            getItemValue(item, 'FLAG_AKTIF', 'flag_aktif') ||
            ''
        ).toUpperCase().trim();

        var tglBatal = String(
            getItemValue(item, 'TGL_BATAL', 'tgl_batal') || ''
        ).trim();

        return statusAktif === 'B' ||
            statusAktif === 'T' ||
            (
                tglBatal !== '' &&
                tglBatal !== '-' &&
                tglBatal !== '0000-00-00' &&
                tglBatal !== '0000-00-00 00:00:00'
            );
    }

    function resetSummaryCards() {
        $('#stat-total').text('0');
        $('#stat-realized').text('0');
        $('#stat-pending').text('0');
        $('#stat-realized-percent').text('0.00%');
        $('#stat-pending-percent').text('0.00%');
        syncFilterVisualState();
    }

    function updateDashboardStats(rows) {
        var total = rows.length;
        var realized = 0;

        $.each(rows, function (index, item) {
            if (hasRealizationDate(item || {})) {
                realized += 1;
            }
        });

        var pending = Math.max(total - realized, 0);
        var realizedPercent = total > 0 ? ((realized / total) * 100).toFixed(2) : '0.00';
        var pendingPercent = total > 0 ? ((pending / total) * 100).toFixed(2) : '0.00';

        $('#stat-total').text(total.toLocaleString('id-ID'));
        $('#stat-realized').text(realized.toLocaleString('id-ID'));
        $('#stat-pending').text(pending.toLocaleString('id-ID'));
        $('#stat-realized-percent').text(realizedPercent + '%');
        $('#stat-pending-percent').text(pendingPercent + '%');
        syncFilterVisualState();
    }

    function renderReport(data) {
        var rows = normalizeSummaryRows(data);
        var periodeSurat = formatDateIndo($('#tgl_awal').val()) + ' s.d ' + formatDateIndo($('#tgl_akhir').val());
        var periodeRealisasi = formatDateIndo($('#tgl_st_awal').val()) + ' s.d ' + formatDateIndo($('#tgl_st_akhir').val());
        var blokAwal = String($('#blok_awal').val() || 'A').trim().toUpperCase();
        var blokAkhir = String($('#blok_akhir').val() || 'Z').trim().toUpperCase();
        var sektorText = $.trim($('#sektorentry').text()) || 'Semua Sektor';
        var jenisText = $('#jenis option:selected').text() || 'Semua';
        var statusRealisasiText = $('#status_realisasi option:selected').text() || 'Semua';
        var stsDtpText = $('#sts_dtp option:selected').text() || 'Semua';
        var aktifText = $('input[name="aktif"]:checked').next('span').text() || 'Aktif';
        var versiAccounting = $('#versi_accounting').is(':checked');
        var companyText = resolveReportCompany(
            rows.length > 0 ? rows[0] : {},
            String($('#nama_perusahaan_session').val() || '').trim()
        );
        var html = '';

        updateDashboardStats(rows);

        html += '<div class="report-wrapper">';

        /*
         * GRID 1 — HEADER LAPORAN
         * Kiri: identitas perusahaan unit aktif. Tengah: judul laporan.
         * Kanan: periode surat, periode realisasi, blok, jenis, tanggal cetak,
         * serta baris status (isi sama seperti keterangan yang sebelumnya
         * berdiri sendiri di atas tabel).
         */
        html += '<div class="report-header">';
        html += '<div class="report-company">' + escapeHtml(companyText) + '</div>';
        html += '<div class="report-title">';
        html += versiAccounting ? 'DAFTAR SERAH TERIMA (ACCOUNTING)' : 'DAFTAR SERAH TERIMA';
        html += '</div>';

        html += '<div class="report-period">';
        html += 'Periode Surat: ' + escapeHtml(periodeSurat);
        html += '<br>Periode Realisasi: ' + escapeHtml(periodeRealisasi);
        html += '<br>Blok: ' + escapeHtml(blokAwal) + ' s.d ' + escapeHtml(blokAkhir);
        html += '<br>Jenis: ' + escapeHtml($.trim(jenisText));
        html += '<br>Tgl: ' + escapeHtml(formatDateIndo(new Date()));

        if (!versiAccounting) {
            html += '<br>Status Realisasi: ' + escapeHtml($.trim(statusRealisasiText)) +
                ' | Status Aktif: ' + escapeHtml($.trim(aktifText)) +
                ' | PPN DTP: ' + escapeHtml($.trim(stsDtpText));
        }

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
        html += '<div class="report-table-container">';
        html += '<table class="report-table' + (versiAccounting ? ' accounting-report-table' : '') + '">';
        html += '<thead><tr>';
        html += '<th>No.</th>';
        html += '<th>Blok/<br>Nomor</th>';
        html += '<th>Nama Pembeli</th>';
        html += '<th>Nomor dan Tanggal<br>PPJB</th>';
        html += '<th>Rencana<br>Serah Terima</th>';

        if (!versiAccounting) {
            html += '<th>Nomor &amp; Tgl Surat<br>Serah Terima</th>';
            html += '<th>Realisasi<br>Serah Terima</th>';
        }

        if (!versiAccounting) {
            html += '<th>Status</th>';
        }

        html += '<th>Telepon<br>Blok</th>';
        html += '<th>Alamat</th>';
        html += '</tr></thead><tbody>';

        if (rows.length < 1) {
            html += '<tr>';
            html += '<td colspan="' + (versiAccounting ? 7 : 10) + '" class="empty-row">Data tidak ditemukan.</td>';
            html += '</tr>';
        } else {
            $.each(rows, function (index, item) {
                item = item || {};

                var alamat = valueOrDash(getItemValue(item, 'ALAMAT', 'alamat'));
                var kota = valueOrDash(getItemValue(item, 'KOTA', 'kota'));
                var blokNomor = getItemValue(item, 'BLOK_NOMOR', 'blok_nomor');
                var nama = getItemValue(item, 'NAMA', 'nama');
                var noPpjb = getItemValue(item, 'NO_PPJB', 'no_ppjb');
                var tglPpjb = getItemValue(item, 'TGL_PPJB', 'tgl_ppjb');
                var tglRencana = getItemValue(item, 'TGL_RENCANA_SS', 'tgl_rencana_ss');
                var noSurat = getItemValue(item, 'NO_SURAT', 'no_surat');
                var tglSurat = getItemValue(item, 'TGL_SURAT', 'tgl_surat');
                var tglSerahTerima = getItemValue(item, 'TGL_SERAH_TERIMA', 'tgl_serah_terima');
                var noTelp = getItemValue(item, 'NO_TELP', 'no_telp');
                var telpRmh = getItemValue(item, 'TELP_RMH', 'telp_rmh');
                var telepon = noTelp && noTelp !== '-' ? noTelp : telpRmh;
                var statusClass = 'pending';
                var statusText = 'Belum Realisasi';

                if (isCancelled(item)) {
                    statusClass = 'cancelled';
                    statusText = 'Batal';
                } else if (hasRealizationDate(item)) {
                    statusClass = 'realized';
                    statusText = 'Realisasi';
                }

                html += '<tr>';
                html += '<td>' + (index + 1) + '</td>';
                html += '<td>' + escapeHtml(valueOrDash(blokNomor)) + '</td>';
                html += '<td class="name-cell">' + escapeHtml(valueOrDash(nama)) + '</td>';
                html += '<td class="multiline-cell">' + escapeHtml(valueOrDash(noPpjb)) + '<br>' + formatDateIndo(tglPpjb) + '</td>';
                html += '<td>' + formatDateIndo(tglRencana) + '</td>';

                if (!versiAccounting) {
                    html += '<td class="multiline-cell">' + escapeHtml(valueOrDash(noSurat)) + '<br>' + formatDateIndo(tglSurat) + '</td>';
                    html += '<td>' + formatDateIndo(tglSerahTerima) + '</td>';
                }

                if (!versiAccounting) {
                    html += '<td><span class="status-badge ' + statusClass + '">' + escapeHtml(statusText) + '</span></td>';
                }

                html += '<td>' + escapeHtml(valueOrDash(telepon)) + '</td>';
                html += '<td class="address-cell">' + escapeHtml(alamat).replace(/\n/g, '<br>');

                if (kota !== '-') {
                    html += '<br>' + escapeHtml(kota);
                }

                html += '</td>';
                html += '</tr>';
            });
        }

        html += '</tbody></table></div></div>';

        $('#main-display').html(html);

        if (rows.length === 0) {
            $('#serahSTNoDataAlertModal').modal('show');
        }
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

    function printSerahSTReport() {
        if (!$('#main-display .report-wrapper').length) {
            alert('Silakan klik View terlebih dahulu untuk menampilkan laporan.');
            return;
        }

        var reportHtml = $('#main-display').html();

        if (!reportHtml) {
            return;
        }

        /*
         * Print terisolasi mengikuti Daftar Sertifikat Pecahan.
         * Orientation dan ukuran kertas tidak dikunci dari CSS.
         */
        $('#serahSTNativePrintFrame').remove();

        var frame = document.createElement('iframe');
        frame.id = 'serahSTNativePrintFrame';
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

            .report-table,
            .report-table.accounting-report-table {
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
                padding: 3px !important;
                border: 1px solid #000 !important;
                background: #fff !important;
                color: #000 !important;
                box-shadow: none !important;
                overflow: visible !important;
                white-space: normal !important;
                overflow-wrap: break-word !important;
                word-break: normal !important;
                vertical-align: middle !important;
                font-size: 10px !important;
                line-height: 1.18 !important;
            }

            .report-table th {
                text-align: center !important;
                font-weight: 700 !important;
            }

            .report-table .name-cell,
            .report-table .multiline-cell,
            .report-table .address-cell {
                min-width: 0 !important;
                max-width: none !important;
                color: #000 !important;
            }

            .status-badge {
                display: inline !important;
                min-width: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                background: #fff !important;
                color: #000 !important;
                font-size: inherit !important;
                font-weight: 700 !important;
            }

            .report-table tbody tr:nth-child(even) td,
            .report-table tbody tr:hover td {
                background: #fff !important;
            }

            .empty-row {
                height: 40px !important;
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
            + '<title>Daftar Serah Terima</title>'
            + '<style>' + printCss + '</style>'
            + '</head>'
            + '<body>' + reportHtml + '</body>'
            + '</html>'
        );
        frameDocument.close();
        applyPrintTableRules(frameDocument);

        var cleanupPrintFrame = function () {
            $('#serahSTNativePrintFrame').remove();
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
                console.error('Gagal membuka dialog print Daftar ST:', error);
                cleanupPrintFrame();
                alert('Dialog print gagal dibuka. Silakan coba kembali.');
            }
        }, 180);

        window.setTimeout(cleanupPrintFrame, 30000);
    }

    function excelSummary() {
        if (!validateFilter()) {
            return;
        }

        var data = getFilterData();
        data.sektor_name = $.trim($('#sektorentry').text()) || 'Semua Sektor';
        data.jenis_name = $('#jenis option:selected').text() || 'Semua';
        data.status_realisasi_name = $('#status_realisasi option:selected').text() || 'Semua';
        data.sts_dtp_name = $('#sts_dtp option:selected').text() || 'Semua';
        data.aktif_name = $('input[name="aktif"]:checked').next('span').text() || 'Aktif';

        var form = $('<form>', {
            method: 'POST',
            action: '{{ url()->current() }}/export_excel',
            target: '_blank'
        });

        $.each(data, function (key, value) {
            form.append($('<input>', {
                type: 'hidden',
                name: key,
                value: value == null ? '' : value
            }));
        });

        $('body').append(form);
        form.submit();
        form.remove();
    }
</script>

@endsection
