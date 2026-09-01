@extends('layouts.template')

@section('content')
<style>
    .berakhir-page {
        --ink: #172033;
        --muted: #64748b;
        --line: #d6dee9;
        --blue: #2563eb;
        width: 100%;
        min-width: 0;
        padding: 16px 12px 32px;
        color: var(--ink);
        font-family: Inter, "Segoe UI", Tahoma, Arial, sans-serif;
        background: #f3f6fa;
    }

    .berakhir-page,
    .berakhir-page * {
        box-sizing: border-box;
    }

    .feature-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 12px;
        padding: 13px 17px;
        border-radius: 10px;
        background: linear-gradient(110deg, #10243c, #14506c);
        color: #fff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .14);
    }

    .feature-title {
        font-size: 15px;
        font-weight: 800;
        letter-spacing: .02em;
    }

    .feature-unit {
        padding: 6px 10px;
        border: 1px solid rgba(255,255,255,.25);
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
    }

    .desktop-filter-panel {
        padding: 16px;
        border: 1px solid #ccd5df;
        border-radius: 10px;
        background: #eef1f4;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
    }

    .filter-grid {
        display: grid;
        grid-template-columns: minmax(360px, 1fr) minmax(430px, 1.2fr) 86px;
        gap: 12px 20px;
        align-items: center;
    }

    .field-row {
        display: grid;
        grid-template-columns: 86px minmax(0, 1fr);
        gap: 9px;
        align-items: center;
        min-width: 0;
    }

    .desktop-label {
        color: #344054;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .range-control {
        display: grid;
        grid-template-columns: minmax(80px, 1fr) 36px minmax(80px, 1fr);
        gap: 7px;
        align-items: center;
    }

    .desktop-input,
    .lookup-display {
        width: 100%;
        min-width: 0;
        height: 38px;
        padding: 7px 10px;
        border: 1px solid #b7c2cf;
        border-radius: 5px;
        background: #fff;
        color: #172033;
        font-size: 12px;
        font-weight: 650;
        outline: none;
    }

    .desktop-input:focus {
        border-color: #4b89dc;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    .range-separator {
        color: #475569;
        font-size: 10px;
        font-weight: 800;
        text-align: center;
    }

    .lookup-control {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 40px;
        gap: 6px;
    }

    .lookup-display {
        display: flex;
        align-items: center;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .lookup-button,
    .ok-button,
    .print-button {
        border: 1px solid #8795a6;
        border-radius: 5px;
        background: linear-gradient(#fff, #dfe5eb);
        color: #1f2937;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
    }

    .lookup-button:hover,
    .ok-button:hover,
    .print-button:hover {
        border-color: #4b6f9d;
        background: #fff;
    }

    .ok-button {
        min-height: 38px;
    }

    .option-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px 14px;
        min-width: 0;
    }

    .desktop-option {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 30px;
        margin: 0;
        color: #263445;
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
    }

    .desktop-option input {
        width: 15px;
        height: 15px;
        margin: 0;
        accent-color: var(--blue);
    }

    .desktop-option.is-disabled {
        opacity: .48;
        cursor: not-allowed;
    }

    .report-shell {
        width: 100%;
        min-width: 0;
        margin-top: 14px;
        padding: 18px;
        overflow: hidden;
        border: 1px solid #cad4df;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
    }

    .report-toolbar {
        display: none;
        justify-content: flex-end;
        margin-bottom: 10px;
    }

    .print-button {
        min-height: 34px;
        padding: 7px 13px;
    }

    #loading-info {
        display: none;
        padding: 32px;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        text-align: center;
    }

    .initial-state {
        display: grid;
        min-height: 220px;
        place-items: center;
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        text-align: center;
    }

    .report-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 2fr) minmax(0, 1fr);
        gap: 12px;
        align-items: start;
        margin-bottom: 12px;
    }

    .report-company {
        padding-top: 3px;
        border-bottom: 1px solid #334155;
        font-size: 12px;
        font-weight: 800;
    }

    .report-title {
        border-bottom: 1px solid #334155;
        font-size: 17px;
        font-weight: 900;
        text-align: center;
    }

    .report-period {
        font-size: 11.5px;
        font-weight: 750;
        line-height: 1.55;
        text-align: right;
    }

    .report-subtitle {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        margin-bottom: 8px;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
    }

    .report-sector-label {
        grid-column: 1;
        justify-self: start;
        white-space: nowrap;
    }

    .report-sector-value {
        grid-column: 2;
        justify-self: center;
        text-align: center;
    }

    .live-badge {
        grid-column: 3;
        justify-self: end;
        padding: 4px 8px;
        border-radius: 999px;
        background: #eaf7ef;
        color: #16794b;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    /* Wrapper ini sengaja menangani zoom browser: tabel tidak boleh keluar
       atau menimpa halaman; pengguna dapat menggulir secara horizontal. */
    .report-scroll {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        border: 1px solid #2f3b49;
        -webkit-overflow-scrolling: touch;
    }

    .report-table {
        width: 100%;
        min-width: 1340px;
        table-layout: fixed;
        border-collapse: collapse;
        color: #172033;
        font-size: 11.5px;
        line-height: 1.36;
    }

    .report-table.with-gabungan {
        min-width: 1770px;
    }

    .report-table th,
    .report-table td {
        padding: 7px 6px;
        overflow-wrap: anywhere;
        word-break: normal;
        vertical-align: top;
        border-right: 1px solid #52606d;
        border-bottom: 1px solid #52606d;
    }

    .report-table th:last-child,
    .report-table td:last-child {
        border-right: 0;
    }

    .report-table thead th {
        padding: 7px 5px;
        background: #eef2f6;
        font-size: 10.8px;
        font-weight: 900;
        line-height: 1.22;
        text-align: center;
        vertical-align: middle;
    }

    .report-table thead tr:first-child th {
        border-bottom: 1px solid #344252;
    }

    .report-table tbody tr:nth-child(even) td {
        background: #fafbfd;
    }

    .report-table tbody tr:hover td {
        background: #eef6ff;
    }

    .center-cell { text-align: center; }
    .number-cell { text-align: right; white-space: nowrap; }
    .name-cell { font-weight: 750; }
    .multiline-cell { white-space: normal; }

    .report-signature-footer {
        width: min(760px, 100%);
        margin: 30px auto 8px;
        color: #1f2937;
        font-size: 12px;
    }

    .signature-date {
        margin-bottom: 12px;
        text-align: center;
    }

    .signature-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 100px;
    }

    .signature-box { text-align: center; }
    .signature-space { height: 64px; }
    .signature-line { white-space: nowrap; }

    .modal-search {
        width: 100%;
        margin-bottom: 12px;
        padding: 9px 11px;
        border: 1px solid #b7c2cf;
        border-radius: 5px;
        font-size: 12px;
    }

    .modal-table-wrapper { max-height: 420px; overflow: auto; }
    .modal-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .modal-table th, .modal-table td { padding: 9px; border: 1px solid #d7dee7; }
    .modal-table th { position: sticky; top: 0; background: #edf2f7; }
    .modal-table tbody tr { cursor: pointer; }
    .modal-table tbody tr:hover td { background: #eaf3ff; }

    @media (max-width: 920px) {
        .filter-grid { grid-template-columns: 1fr; }
        .ok-button { width: 120px; }
    }

    @media (max-width: 760px) {
        .report-header {
            grid-template-columns: 1fr;
        }
        .report-title {
            grid-row: 1;
        }
        .report-period {
            text-align: left;
        }
        .report-subtitle {
            grid-template-columns: auto minmax(0, 1fr) auto;
            column-gap: 8px;
        }
        .report-sector-label {
            grid-column: 1;
        }
        .report-sector-value {
            grid-column: 2;
        }
        .live-badge {
            grid-column: 3;
        }
    }

    @media (max-width: 600px) {
        .berakhir-page { padding: 10px 6px 24px; }
        .feature-bar { align-items: flex-start; flex-direction: column; }
        .desktop-filter-panel, .report-shell { padding: 12px 9px; }
        .field-row { grid-template-columns: 1fr; gap: 5px; }
        .option-row { gap: 5px 12px; }
        .desktop-option { font-size: 11px; }
        .report-table { font-size: 10.8px; }
        .report-table thead th { font-size: 10px; }
        .signature-grid { gap: 24px; }
    }

    .berakhir-page {
        --page-blue: #2563eb;
        --page-cyan: #38bdf8;
        --page-violet: #6366f1;
        --page-green: #10b981;
        position: relative;
        padding: 18px 12px 34px;
        background:
            radial-gradient(circle at 8% 2%, rgba(37, 99, 235, .12), transparent 28%),
            radial-gradient(circle at 96% 4%, rgba(99, 102, 241, .11), transparent 30%),
            linear-gradient(145deg, #f8fbff 0%, #f1f6fd 48%, #f8f8ff 100%);
    }

    .feature-bar {
        position: relative;
        justify-content: flex-start;
        min-height: 70px;
        margin-bottom: 16px;
        padding: 16px 20px 16px 66px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, .9);
        border-radius: 22px;
        background:
            radial-gradient(circle at 88% 10%, rgba(37, 99, 235, .13), transparent 34%),
            rgba(255, 255, 255, .91);
        color: #183153;
        box-shadow: 0 16px 38px rgba(15, 23, 42, .09);
        backdrop-filter: blur(16px);
    }

    .feature-bar::before {
        content: "◆";
        position: absolute;
        left: 20px;
        display: grid;
        width: 34px;
        height: 34px;
        place-items: center;
        border-radius: 10px;
        background: linear-gradient(145deg, #3b82f6, #1d4ed8);
        color: #dbeafe;
        font-size: 13px;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .28);
    }

    .feature-title {
        flex: 1;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .feature-unit {
        margin-left: auto;
        padding: 7px 13px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: rgba(239, 246, 255, .8);
        color: #2563eb;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .08em;
    }

    .desktop-filter-panel {
        position: relative;
        padding: 20px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, .92);
        border-radius: 24px;
        background: rgba(255, 255, 255, .9);
        box-shadow: 0 18px 42px rgba(15, 23, 42, .10);
        backdrop-filter: blur(18px);
    }

    .desktop-filter-panel::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: linear-gradient(180deg, var(--page-cyan), var(--page-blue), var(--page-violet));
    }

    .desktop-filter-panel::after {
        content: "Sertipikat Berakhir Hak";
        position: absolute;
        right: 22px;
        bottom: 12px;
        color: rgba(37, 99, 235, .045);
        font-size: 32px;
        font-weight: 950;
        letter-spacing: .08em;
        pointer-events: none;
    }

    .filter-grid {
        position: relative;
        z-index: 1;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr) 104px;
        gap: 13px 22px;
    }

    .field-row {
        grid-template-columns: 84px minmax(0, 1fr);
        gap: 10px;
    }

    .desktop-label {
        color: #40546b;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .desktop-input,
    .lookup-display {
        height: 43px;
        padding: 9px 13px;
        border: 1px solid #d3deeb;
        border-radius: 13px;
        background: rgba(249, 251, 253, .94);
        color: #172033;
        font-size: 12.5px;
        font-weight: 750;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .9);
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    .desktop-input:focus {
        border-color: #60a5fa;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .11);
        transform: translateY(-1px);
    }

    .range-separator {
        display: grid;
        width: 34px;
        height: 26px;
        place-items: center;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 9px;
        font-weight: 900;
    }

    .lookup-button {
        border: 1px solid #c7d7f1;
        border-radius: 12px;
        background: linear-gradient(145deg, #eff6ff, #dbeafe);
        color: #2563eb;
        box-shadow: 0 7px 16px rgba(37, 99, 235, .12);
    }

    .lookup-button:hover {
        border-color: #93c5fd;
        background: #eff6ff;
    }

    .action-stack {
        display: grid;
        gap: 8px;
        align-self: stretch;
        margin-right: -20px;
    }

    .ok-button,
    .print-button {
        width: 100%;
        min-height: 43px;
        padding: 9px 12px;
        border: 0;
        border-radius: 13px 0 0 13px;
        color: #fff;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
        transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
    }

    .ok-button {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        box-shadow: 0 11px 22px rgba(37, 99, 235, .28);
    }

    .print-button {
        background: linear-gradient(135deg, #14b8a6, #059669);
        box-shadow: 0 11px 22px rgba(5, 150, 105, .24);
    }

    .ok-button:hover,
    .ok-button:focus-visible {
        border: 0;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
        color: #fff;
        filter: brightness(1.06);
        transform: translateY(-1px);
    }

    .print-button:hover,
    .print-button:focus-visible {
        border: 0;
        background: linear-gradient(135deg, #14b8a6, #059669) !important;
        color: #fff;
        filter: brightness(1.06);
        transform: translateY(-1px);
    }

    .ok-button:disabled,
    .print-button:disabled {
        opacity: .52;
        cursor: not-allowed;
        filter: grayscale(.15);
        transform: none;
        box-shadow: none;
    }

    .desktop-option {
        min-height: 34px;
        padding: 6px 10px;
        border: 1px solid #dfe7f1;
        border-radius: 999px;
        background: rgba(248, 250, 252, .88);
        color: #40546b;
        font-size: 11.5px;
        font-weight: 750;
    }

    .desktop-option:hover {
        border-color: #bfdbfe;
        background: #f5f9ff;
    }

    .desktop-option:has(input:checked) {
        border-color: #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
        box-shadow: 0 5px 13px rgba(37, 99, 235, .09);
    }

    .report-shell {
        margin-top: 18px;
        padding: 20px;
        border: 1px solid rgba(255, 255, 255, .95);
        border-radius: 24px;
        background: rgba(255, 255, 255, .94);
        box-shadow: 0 20px 48px rgba(15, 23, 42, .10);
    }

    .report-header {
        align-items: center;
        min-height: 76px;
        margin-bottom: 10px;
        padding: 16px 18px;
        border: 1px solid #dce9f8;
        border-radius: 17px;
        background:
            linear-gradient(110deg, rgba(233, 243, 255, .98), rgba(242, 247, 255, .98) 50%, rgba(231, 240, 255, .98));
        box-shadow: inset 0 1px 0 #fff;
    }

    .report-company {
        padding: 0;
        border: 0;
        color: #2563eb;
        font-size: 12.5px;
        font-weight: 900;
    }

    .report-title {
        padding: 0 8px;
        border: 0;
        color: #172033;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 19px;
        font-weight: 900;
        line-height: 1.2;
    }

    .report-period {
        color: #40546b;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.55;
    }

    .report-subtitle {
        min-height: 47px;
        margin-bottom: 10px;
        padding: 9px 12px;
        border: 1px solid #e3ebf4;
        border-radius: 13px;
        background: #fff;
        color: #526174;
        font-size: 11.5px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, .035);
    }

    .report-sector-value {
        color: #24374e;
        font-weight: 900;
    }

    .live-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border: 1px solid #a7f3d0;
        background: #ecfdf5;
        color: #059669;
        font-size: 9px;
        box-shadow: 0 5px 12px rgba(16, 185, 129, .08);
    }

    .live-badge::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .13);
    }

    .report-scroll {
        border: 1px solid #d7e2ef;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
    }

    .report-table {
        min-width: 1520px;
        color: #1e3048;
        font-size: 12.5px;
        line-height: 1.48;
    }

    .report-table.with-gabungan {
        min-width: 2040px;
    }

    .report-table th,
    .report-table td {
        padding: 9px 8px;
        border-right: 1px solid #d7e2ef;
        border-bottom: 1px solid #d7e2ef;
    }

    .report-table thead th {
        padding: 10px 7px;
        background: #eaf2ff;
        color: #29415f;
        font-size: 11.5px;
        font-weight: 900;
        line-height: 1.3;
    }

    .report-table thead tr:first-child th {
        border-bottom: 1px solid #c8d9ec;
        background: linear-gradient(180deg, #e9f2ff, #dfeaff);
    }

    .report-table thead tr:first-child th[colspan] {
        background: linear-gradient(180deg, #dbe8ff, #cfdefa);
        color: #314f82;
    }

    .report-table thead tr:nth-child(2) th {
        background: #edf4ff;
        color: #47617e;
        font-size: 11px;
    }

    .report-table tbody td {
        background: #fff;
    }

    .report-table tbody tr:nth-child(even) td {
        background: #f9fbfe;
    }

    .report-table tbody tr:hover td {
        background: #eef6ff;
    }

    .report-table .name-cell {
        color: #203a58;
        font-weight: 850;
    }

    .report-signature-footer {
        margin-top: 36px;
        font-size: 12.5px;
        line-height: 1.5;
    }

    @media (max-width: 920px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }
        .action-stack {
            width: min(220px, 100%);
            margin-right: 0;
            grid-template-columns: 1fr 1fr;
        }
        .ok-button,
        .print-button {
            border-radius: 13px;
        }
        .ok-button {
            width: 100%;
        }
    }

    @media (max-width: 600px) {
        .feature-bar {
            align-items: center;
            flex-direction: row;
            padding-left: 62px;
        }
        .feature-title {
            font-size: 10px;
        }
        .feature-unit {
            font-size: 8px;
        }
        .desktop-filter-panel,
        .report-shell {
            border-radius: 17px;
        }
        .action-stack {
            width: 100%;
        }
        .report-table {
            font-size: 12px;
        }
        .report-table thead th {
            font-size: 11px;
        }
    }

    /* =========================================================
       PRINT FIX — SERTIPIKAT BERAKHIR HAK
       - Setiap TH/TD memiliki border penuh, bukan hanya kanan+bawah.
       - Border terakhir tidak lagi dihapus saat print.
       - Colgroup pixel dioverride ke persentase agar tabel fit di A3 landscape.
       - Header tabel diulang jika hasil print lebih dari satu halaman.
       ========================================================= */
    @media print {
        @page {
            margin: 9mm;
        }

        body * {
            visibility: hidden !important;
        }

        .report-shell,
        .report-shell * {
            visibility: visible !important;
        }

        .report-shell {
            position: absolute;
            inset: 0;
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: #fff !important;
            box-shadow: none !important;
        }

        .report-toolbar,
        #berakhirNoDataAlertModal {
            display: none !important;
        }

        .report-header,
        .report-subtitle {
            background: #fff !important;
            color: #000 !important;
            box-shadow: none !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .report-company,
        .report-title,
        .report-period,
        .report-sector-label,
        .report-sector-value,
        .live-badge {
            color: #000 !important;
        }

        .live-badge {
            border-color: #777 !important;
            background: #fff !important;
        }

        /*
         * Wrapper tidak diberi border saat print.
         * Border luar berasal langsung dari table agar tidak terjadi
         * double-line / garis terputus di PDF viewer.
         */
        .report-scroll {
            width: 100% !important;
            max-width: 100% !important;
            overflow: visible !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: #fff !important;
            box-shadow: none !important;
        }

        /*
         * FIX UTAMA.
         * Versi sebelumnya hanya mengganti border-color menjadi hitam,
         * padahal CSS layar hanya memiliki border-right + border-bottom,
         * dan last-child bahkan menghapus border-right.
         */
        .report-table,
        .report-table.with-gabungan {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            border-spacing: 0 !important;
            border: 0.8pt solid #000 !important;
            background: #fff !important;
            color: #000 !important;
            font-size: 9.2px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
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
        .report-table td,
        .report-table th:last-child,
        .report-table td:last-child {
            padding: 4px !important;
            border: 0.8pt solid #000 !important;
            background: #fff !important;
            color: #000 !important;
            box-shadow: none !important;
            overflow: visible !important;
            overflow-wrap: anywhere !important;
            word-break: normal !important;
        }

        .report-table thead th,
        .report-table thead tr:first-child th,
        .report-table thead tr:first-child th[colspan],
        .report-table thead tr:nth-child(2) th {
            border: 0.8pt solid #000 !important;
            background: #fff !important;
            color: #000 !important;
            font-size: 8.7px !important;
            box-shadow: none !important;
        }

        .report-table tbody tr:nth-child(even) td,
        .report-table tbody tr:hover td {
            background: #fff !important;
        }

        /*
         * Normal report = 10 kolom.
         * Inline width pixel pada buildColgroup() dioverride khusus print.
         * Total tepat 100%.
         */
        .report-table:not(.with-gabungan) col:nth-child(1)  { width: 4% !important; }
        .report-table:not(.with-gabungan) col:nth-child(2)  { width: 7% !important; }
        .report-table:not(.with-gabungan) col:nth-child(3)  { width: 16% !important; }
        .report-table:not(.with-gabungan) col:nth-child(4)  { width: 13% !important; }
        .report-table:not(.with-gabungan) col:nth-child(5)  { width: 13% !important; }
        .report-table:not(.with-gabungan) col:nth-child(6)  { width: 7% !important; }
        .report-table:not(.with-gabungan) col:nth-child(7)  { width: 14% !important; }
        .report-table:not(.with-gabungan) col:nth-child(8)  { width: 13% !important; }
        .report-table:not(.with-gabungan) col:nth-child(9)  { width: 7% !important; }
        .report-table:not(.with-gabungan) col:nth-child(10) { width: 6% !important; }

        /*
         * Report dengan Sertipikat Penggabungan = 13 kolom.
         * Total tepat 100%.
         */
        .report-table.with-gabungan col:nth-child(1)  { width: 3% !important; }
        .report-table.with-gabungan col:nth-child(2)  { width: 6% !important; }
        .report-table.with-gabungan col:nth-child(3)  { width: 12% !important; }
        .report-table.with-gabungan col:nth-child(4)  { width: 10% !important; }
        .report-table.with-gabungan col:nth-child(5)  { width: 9% !important; }
        .report-table.with-gabungan col:nth-child(6)  { width: 5% !important; }
        .report-table.with-gabungan col:nth-child(7)  { width: 10% !important; }
        .report-table.with-gabungan col:nth-child(8)  { width: 9% !important; }
        .report-table.with-gabungan col:nth-child(9)  { width: 5% !important; }
        .report-table.with-gabungan col:nth-child(10) { width: 10% !important; }
        .report-table.with-gabungan col:nth-child(11) { width: 9% !important; }
        .report-table.with-gabungan col:nth-child(12) { width: 5% !important; }
        .report-table.with-gabungan col:nth-child(13) { width: 7% !important; }

        .report-signature-footer {
            color: #000 !important;
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }
    }

    /* =========================================================
       ALERT DATA KOSONG — SAMA DENGAN DAFTAR SERTIFIKAT PECAHAN
       Modal informasi yang tampil saat hasil laporan tidak
       menghasilkan baris data sama sekali.
       ========================================================= */
        #berakhirNoDataAlertModal .modal-dialog {
            max-width: 380px;
        }

        #berakhirNoDataAlertModal .modal-content {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
        }

        #berakhirNoDataAlertModal .alert-icon-wrapper {
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

        #berakhirNoDataAlertModal .alert-title {
            margin-bottom: 8px;
            color: #172033;
            font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
            font-size: 18px;
            font-weight: 700;
        }

        #berakhirNoDataAlertModal .alert-message {
            margin-bottom: 24px;
            color: #475569;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            font-size: 14px;
        }

        #berakhirNoDataAlertModal .alert-btn-ok {
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

        #berakhirNoDataAlertModal .alert-btn-ok:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
        }

</style>

<!-- MODAL ALERT DATA KOSONG -->
<div class="modal fade" id="berakhirNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4" style="text-align: center; padding: 1.5rem;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message" id="berakhirNoDataMessage">Data tidak ditemukan.</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    data-dismiss="modal"
                    onclick="hideBerakhirNoDataAlert()"
                >OK</button>
            </div>
        </div>
    </div>
</div>

<div class="berakhir-page">
    <div class="feature-bar">
        <div class="feature-title">DAFTAR SERTIFIKAT BERAKHIR HAK</div>
        <div class="feature-unit">
            UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}
        </div>
    </div>

    <div class="modal" id="sektorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 style="margin:0;font-size:15px;font-weight:800;">Pilih Sektor/Cluster</h5>
                    <button type="button" class="btn btn-light" onclick="toggleSektorModal('hide')" aria-label="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" id="sektorModalContent"></div>
            </div>
        </div>
    </div>

    <section class="desktop-filter-panel">
        <input type="hidden" id="perusahaan" value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}">
        <input type="hidden" id="nama_perusahaan_session" value="{{ session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? '' }}">
        <input type="hidden" id="sektor" value="*">

        <div class="filter-grid">
            <div class="field-row">
                <label class="desktop-label" for="blok_awal">Blok</label>
                <div class="range-control">
                    <input type="text" id="blok_awal" class="desktop-input" value="A" maxlength="30">
                    <span class="range-separator">s.d</span>
                    <input type="text" id="blok_akhir" class="desktop-input" value="ZZ" maxlength="30">
                </div>
            </div>

            <div class="field-row">
                <label class="desktop-label" for="tgl_awal">Tgl. Berlaku</label>
                <div class="range-control">
                    <input type="date" id="tgl_awal" class="desktop-input">
                    <span class="range-separator">s.d</span>
                    <input type="date" id="tgl_akhir" class="desktop-input">
                </div>
            </div>

            <div class="action-stack">
                <button type="button" class="ok-button" id="okButton" onclick="getData()">OK</button>
                <button type="button" class="print-button" id="printButton" onclick="printReport()" disabled>Print</button>
            </div>

            <div class="field-row">
                <span class="desktop-label">Sektor</span>
                <div class="lookup-control">
                    <div id="sektorEntry" class="lookup-display">Semua Sektor</div>
                    <button type="button" id="sektorButton" class="lookup-button" onclick="getSektorModal()" aria-label="Cari sektor">
                        <i class="fas fa-binoculars"></i>
                    </button>
                </div>
            </div>

            <div class="field-row">
                <span class="desktop-label">Status AJB</span>
                <div class="option-row" id="statusAjbOptions">
                    <label class="desktop-option">
                        <input type="radio" name="status_ajb" value="SUDAH">
                        <span>Sudah AJB</span>
                    </label>
                    <label class="desktop-option">
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

            <div class="option-row">
                <label class="desktop-option">
                    <input type="checkbox" id="apartemen">
                    <span>Apartemen</span>
                </label>
            </div>

            <div class="option-row">
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
            <div class="initial-state">Silakan isi filter kemudian klik OK.</div>
        </div>
    </section>
</div>
@endsection

@section('js')
<script>
    var lastReportRows = null;
    var activeReportRequest = null;
    var autoRefreshTimer = null;
    var reportRequiresOk = false;

    $(document).ready(function () {
        resetInitialState();

        $('#blok_awal, #blok_akhir').on('input', function () {
            $(this).val(String($(this).val() || '').toUpperCase());
        });

        $('#apartemen').on('change', function () {
            syncApartemenState();
            holdCurrentReportUntilOk();
        });

        $('input[name="status_ajb"]').on('change', function () {
            if (Array.isArray(lastReportRows) && !reportRequiresOk) {
                scheduleReportRefresh();
            }
        });

        $('#tampil_penggabungan').on('change', function () {
            if (Array.isArray(lastReportRows) && !reportRequiresOk) {
                renderReport(lastReportRows);
            }
        });
    });

    $(window).on('pageshow', function (event) {
        var pageEvent = event.originalEvent || event;

        if (pageEvent.persisted) {
            resetInitialState();
        }
    });

    function resetInitialState() {
        window.clearTimeout(autoRefreshTimer);

        if (activeReportRequest && activeReportRequest.readyState !== 4) {
            activeReportRequest.abort();
        }

        activeReportRequest = null;
        lastReportRows = null;
        reportRequiresOk = false;

        $('#blok_awal').val('A');
        $('#blok_akhir').val('ZZ');
        setDefaultDate();
        $('#sektor').val('*');
        $('#sektorEntry').text('Semua Sektor');
        $('input[name="status_ajb"][value="SEMUA"]').prop('checked', true);
        $('#apartemen, #tampil_penggabungan').prop('checked', false);
        $('#sektorButton').prop('disabled', false);
        $('#okButton').prop('disabled', false);
        $('#printButton').prop('disabled', true);
        $('#loading-info').hide();
        hideBerakhirNoDataAlert();
        $('#mainDisplay').html(
            '<div class="initial-state">Silakan isi filter kemudian klik OK.</div>'
        );
    }

    function setDefaultDate() {
        var today = toInputDate(new Date());

        $('#tgl_awal').val(today);
        $('#tgl_akhir').val(today);
    }

    function toInputDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function syncApartemenState() {
        var active = $('#apartemen').is(':checked');

        if (active) {
            $('#sektor').val('*');
            $('#sektorEntry').text('Semua Sektor');
        }

        $('#sektorButton').prop('disabled', active);
    }

    function scheduleReportRefresh() {
        window.clearTimeout(autoRefreshTimer);
        autoRefreshTimer = window.setTimeout(function () {
            getData();
        }, 120);
    }

    function holdCurrentReportUntilOk() {
        reportRequiresOk = true;
        window.clearTimeout(autoRefreshTimer);

        if (activeReportRequest && activeReportRequest.readyState !== 4) {
            activeReportRequest.abort();
        }

        activeReportRequest = null;
        $('#loading-info').hide();
        $('#okButton').prop('disabled', false);
    }

    function clearReport(message) {
        reportRequiresOk = true;
        window.clearTimeout(autoRefreshTimer);

        if (activeReportRequest && activeReportRequest.readyState !== 4) {
            activeReportRequest.abort();
        }

        activeReportRequest = null;
        lastReportRows = null;
        $('#printButton').prop('disabled', true);
        $('#loading-info').hide();
        $('#okButton').prop('disabled', false);
        $('#mainDisplay').html(
            '<div class="initial-state">'
            + escapeHtml(
                message || 'Filter berubah. Klik OK untuk menampilkan laporan.'
            )
            + '</div>'
        );
    }

    function printReport() {
        if (
            !Array.isArray(lastReportRows)
            || $('#printButton').prop('disabled')
            || !$('#mainDisplay .report-header').length
        ) {
            return;
        }

        var reportHtml = $('#mainDisplay').html();
        if (!reportHtml) {
            return;
        }

        $('#berakhirHakNativePrintFrame').remove();

        var frame = document.createElement('iframe');
        frame.id = 'berakhirHakNativePrintFrame';
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
         * Print dibuat terisolasi seperti fitur yang sudah benar.
         * @page hanya mengatur margin dan TIDAK mengunci size/orientation,
         * sehingga browser tetap dapat menampilkan pilihan Portrait/Landscape.
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

            .report-header {
                display: grid;
                grid-template-columns: 1fr 2fr 1fr;
                gap: 12px;
                align-items: center;
                margin-bottom: 8px;
                padding: 9px 10px;
                border: 1px solid #777;
                background: #fff;
            }

            .report-company {
                color: #000;
                font-size: 9.5px;
                font-weight: 700;
            }

            .report-title {
                color: #000;
                font-family: Cambria, Georgia, "Times New Roman", serif;
                font-size: 14px;
                font-weight: 700;
                line-height: 1.2;
                text-align: center;
            }

            .report-period {
                color: #000;
                font-size: 8.5px;
                font-weight: 600;
                line-height: 1.4;
                text-align: right;
            }

            .report-subtitle {
                display: grid;
                grid-template-columns: 1fr auto 1fr;
                align-items: center;
                gap: 10px;
                margin-bottom: 7px;
                padding: 6px 8px;
                border: 1px solid #aaa;
                background: #fff;
                color: #000;
                font-size: 8.5px;
            }

            .report-sector-label { justify-self: start; }
            .report-sector-value {
                justify-self: center;
                color: #000;
                font-weight: 700;
            }

            .live-badge {
                justify-self: end;
                color: #000;
                font-size: 7.5px;
                font-weight: 700;
            }

            .report-scroll {
                width: 100%;
                max-width: 100%;
                overflow: visible;
                border: 0;
                background: #fff;
            }

            .report-table,
            .report-table.with-gabungan {
                width: 100%;
                min-width: 0;
                max-width: 100%;
                table-layout: fixed;
                border-collapse: collapse;
                border-spacing: 0;
                border: 1px solid #000;
                background: #fff;
                color: #000;
                font-size: 7.4px;
            }

            .report-table col { width: auto !important; }

            .report-table thead { display: table-header-group; }
            .report-table tbody { display: table-row-group; }

            .report-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .report-table th,
            .report-table td,
            .report-table th:last-child,
            .report-table td:last-child {
                position: static;
                padding: 3px;
                border: 1px solid #000;
                background: #fff;
                color: #000;
                box-shadow: none;
                vertical-align: middle;
                overflow: visible;
                overflow-wrap: anywhere;
                word-break: normal;
                line-height: 1.2;
            }

            .report-table th {
                text-align: center;
                font-weight: 700;
            }

            .center-cell { text-align: center; }
            .number-cell { text-align: right; white-space: nowrap; }
            .name-cell { font-weight: 700; }

            .report-signature-footer {
                width: 100%;
                margin: 14px auto 0;
                padding: 0 24px 8px;
                color: #000;
                font-size: 9px;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .signature-date {
                margin-bottom: 8px;
                text-align: center;
            }

            .signature-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 90px;
            }

            .signature-box { text-align: center; }
            .signature-space { height: 58px; }
            .signature-line { white-space: nowrap; }
        `;

        frameDocument.open();
        frameDocument.write(
            '<!DOCTYPE html><html><head><meta charset="utf-8">'
            + '<meta name="viewport" content="width=device-width,initial-scale=1">'
            + '<title>Daftar Sertifikat Berakhir Hak</title>'
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
                    $('#berakhirHakNativePrintFrame').remove();
                }, 1200);
            }
        }, 180);
    }

    function toggleSektorModal(action) {
        var modal = $('#sektorModal');

        if (typeof modal.modal === 'function') {
            modal.modal(action);
            return;
        }

        if (action === 'show') {
            modal.addClass('show').css('display', 'block').attr('aria-hidden', 'false');
            $('<div class="modal-backdrop fade show sektor-fallback-backdrop"></div>').appendTo(document.body);
            $('body').addClass('modal-open');
        } else {
            modal.removeClass('show').css('display', 'none').attr('aria-hidden', 'true');
            $('.sektor-fallback-backdrop').remove();
            $('body').removeClass('modal-open');
        }
    }

    function getSektorModal() {
        if ($('#apartemen').is(':checked')) {
            return;
        }

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
            data: {
                _token: '{{ csrf_token() }}',
                perusahaan: perusahaan
            },
            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (response && Array.isArray(response.data) ? response.data : []);
                var html = '';

                html += '<input type="text" class="modal-search" placeholder="Cari sektor..." onkeyup="filterSektor(this.value)">';
                html += '<div class="modal-table-wrapper"><table class="modal-table">';
                html += '<thead><tr><th>Kode Sektor</th><th>Deskripsi</th><th>Perusahaan</th></tr></thead><tbody>';
                html += '<tr data-kode="*" data-deskripsi="Semua Sektor">';
                html += '<td>*</td><td>Semua Sektor</td><td>' + escapeHtml(perusahaan) + '</td></tr>';

                $.each(rows, function (_, item) {
                    var kode = item.KD_SEKTOR || item.kd_sektor || '';
                    var deskripsi = item.DESKRIPSI || item.deskripsi || kode;
                    var unit = item.KD_PERUSAHAAN || item.kd_perusahaan || perusahaan;

                    html += '<tr data-kode="' + escapeHtml(kode) + '" data-deskripsi="' + escapeHtml(deskripsi) + '">';
                    html += '<td>' + escapeHtml(kode) + '</td>';
                    html += '<td>' + escapeHtml(deskripsi) + '</td>';
                    html += '<td>' + escapeHtml(unit) + '</td></tr>';
                });

                if (rows.length < 1) {
                    html += '<tr class="empty-row"><td colspan="3" style="padding:22px;text-align:center;">Data sektor tidak ditemukan.</td></tr>';
                }

                html += '</tbody></table></div>';
                $('#sektorModalContent').html(html);
                $('#sektorModalContent tbody tr').not('.empty-row').on('click', function () {
                    $('#sektor').val($(this).attr('data-kode') || '*');
                    $('#sektorEntry').text($(this).attr('data-deskripsi') || 'Semua Sektor');
                    toggleSektorModal('hide');
                    holdCurrentReportUntilOk();
                });
                toggleSektorModal('show');
            },
            error: function (xhr) {
                showAjaxError(xhr, 'Gagal mengambil data sektor.');
            }
        });
    }

    function filterSektor(keyword) {
        var search = String(keyword || '').toLowerCase().trim();
        $('#sektorModalContent tbody tr').not('.empty-row').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(search) !== -1);
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
            tampil_penggabungan: $('#tampil_penggabungan').is(':checked') ? 'Y' : 'T'
        };
    }

    function validateFilter() {
        if (!$('#blok_awal').val() || !$('#blok_akhir').val()) {
            alert('Rentang blok wajib diisi.');
            return false;
        }
        if (!$('#tgl_awal').val() || !$('#tgl_akhir').val()) {
            alert('Rentang tanggal berlaku wajib diisi.');
            return false;
        }
        if ($('#tgl_awal').val() > $('#tgl_akhir').val()) {
            alert('Tanggal awal tidak boleh melebihi tanggal akhir.');
            return false;
        }
        if (!$('#perusahaan').val()) {
            alert('Unit/perusahaan belum tersedia.');
            return false;
        }
        return true;
    }

    /*
     * Alert data kosong, mengikuti Daftar Sertifikat Pecahan.
     * Hanya menampilkan pesan; pengambilan data dan render laporan
     * tetap memakai alur yang sudah ada. Jika plugin modal tidak
     * tersedia pada halaman ini, modal ditampilkan secara manual
     * sehingga tampilannya tetap sama.
     */
    function showBerakhirNoDataAlert(message) {
        var $modal = $('#berakhirNoDataAlertModal');

        $('#berakhirNoDataMessage').text(message || 'Data tidak ditemukan......!');

        if (typeof $modal.modal === 'function') {
            $modal.modal('show');
            return;
        }

        if (!$('.berakhir-nodata-backdrop').length) {
            $('<div class="modal-backdrop fade show berakhir-nodata-backdrop"></div>')
                .appendTo(document.body);
        }

        $modal
            .addClass('show')
            .css('display', 'block')
            .attr('aria-hidden', 'false');
    }

    function hideBerakhirNoDataAlert() {
        var $modal = $('#berakhirNoDataAlertModal');

        if (typeof $modal.modal === 'function') {
            $modal.modal('hide');
            return;
        }

        $modal
            .removeClass('show')
            .css('display', 'none')
            .attr('aria-hidden', 'true');

        $('.berakhir-nodata-backdrop').remove();
    }

    function getData() {
        if (!validateFilter()) {
            return;
        }

        if (activeReportRequest && activeReportRequest.readyState !== 4) {
            activeReportRequest.abort();
        }

        hideBerakhirNoDataAlert();

        $('#okButton').prop('disabled', true);
        $('#printButton').prop('disabled', true);

        if (!Array.isArray(lastReportRows)) {
            $('#mainDisplay').empty();
        }

        $('#loading-info').show();

        var request = $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_data',
            dataType: 'json',
            timeout: 120000,
            headers: { 'Accept': 'application/json' },
            data: getFilterData(),
            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (response && Array.isArray(response.data) ? response.data : []);
                lastReportRows = rows;
                reportRequiresOk = false;

                if (rows.length === 0) {
                    showBerakhirNoDataAlert(
                        $('#apartemen').is(':checked')
                            ? 'Data Laporan Apartemen tidak ditemukan......!'
                            : 'Data tidak ditemukan......!'
                    );
                }

                renderReport(rows);
            },
            error: function (xhr) {
                if (xhr && (xhr.statusText === 'abort' || xhr.status === 0)) {
                    return;
                }

                lastReportRows = null;
                $('#printButton').prop('disabled', true);
                $('#mainDisplay').html('<div class="initial-state">Laporan gagal ditampilkan.</div>');
                showAjaxError(xhr, 'Gagal mengambil laporan.');
            },
            complete: function () {
                if (activeReportRequest === request) {
                    activeReportRequest = null;
                    $('#loading-info').hide();
                    $('#okButton').prop('disabled', false);
                }
            }
        });

        activeReportRequest = request;
    }

    function renderReport(rows) {
        var showGabungan = $('#tampil_penggabungan').is(':checked');
        var isApartemen = $('#apartemen').is(':checked');
        var statusAjb = $('input[name="status_ajb"]:checked').val() || 'SEMUA';
        var first = rows.length ? rows[0] : {};
        var company = resolveReportCompany(first);
        var sector = isApartemen ? 'Semua Sektor' : ($('#sektorEntry').text() || 'Semua Sektor');
        var blok = String($('#blok_awal').val() || 'A').toUpperCase()
            + ' s/d ' + String($('#blok_akhir').val() || 'ZZ').toUpperCase();
        var period = formatDate($('#tgl_awal').val())
            + ' s/d ' + formatDate($('#tgl_akhir').val());
        var title = 'Laporan Daftar Sertifikat Yang Berakhir Haknya';
        var totalColumns = showGabungan ? 13 : 10;
        var html = '';

        if (isApartemen) {
            title += ' Apartemen';
        }

        if (statusAjb === 'SUDAH') {
            title += ' (Sudah AJB)';
        } else if (statusAjb === 'BELUM') {
            title += ' (Belum AJB)';
        }

        html += '<div class="report-header">';
        html += '<div class="report-company">' + escapeHtml(company) + '</div>';
        html += '<div class="report-title">' + escapeHtml(title) + '</div>';
        html += '<div class="report-period">BLOK: ' + escapeHtml(blok) + '<br>Tgl. Berlaku: ' + escapeHtml(period) + '</div>';
        html += '</div>';
        html += '<div class="report-subtitle">';
        html += '<span class="report-sector-label">Sektor/Cluster:</span>';
        html += '<strong class="report-sector-value">' + escapeHtml(sector) + '</strong>';
        html += '<span class="live-badge">Live data</span></div>';

        html += '<div class="report-scroll">';
        html += '<table class="report-table' + (showGabungan ? ' with-gabungan' : '') + '">';
        html += buildColgroup(showGabungan);
        html += '<thead><tr>';
        html += '<th rowspan="2">No.</th>';
        html += '<th rowspan="2">BLOK/<br>NOMOR</th>';
        html += '<th rowspan="2">Nama Pemilik</th>';
        html += '<th rowspan="2">Sertipikat Induk<br>Nomor dan Tanggal</th>';
        html += '<th colspan="2">Surat Ukur Induk</th>';
        html += '<th rowspan="2">Sertipikat Pemisahan<br>Nomor dan Tanggal</th>';
        html += '<th colspan="2">Surat Ukur Pemisahan</th>';

        if (showGabungan) {
            html += '<th rowspan="2">Sertipikat Penggabungan<br>Nomor dan Tanggal</th>';
            html += '<th colspan="2">Surat Ukur Penggabungan</th>';
        }

        html += '<th rowspan="2">Masa Berlaku</th></tr><tr>';
        html += '<th>Nomor dan Tanggal</th><th>Luas<br>(M2)</th>';
        html += '<th>Nomor dan Tanggal</th><th>Luas<br>(M2)</th>';
        if (showGabungan) {
            html += '<th>Nomor dan Tanggal</th><th>Luas<br>(M2)</th>';
        }
        html += '</tr></thead><tbody>';

        if (!rows.length) {
            html += '<tr><td colspan="' + totalColumns + '" style="height:120px;text-align:center;color:#64748b;vertical-align:middle;">Data tidak ditemukan.</td></tr>';
        } else {
            $.each(rows, function (index, item) {
                item = item || {};
                html += '<tr>';
                html += '<td class="center-cell">' + (index + 1) + '</td>';
                html += '<td class="center-cell">' + escapeHtml(valueOrDash(pickValue(item, ['BLOK_NOMOR', 'blok_nomor']))) + '</td>';
                html += '<td class="name-cell">' + escapeHtml(valueOrDash(pickValue(item, ['NASABAH_NAMA', 'nasabah_nama']))) + '</td>';
                html += '<td class="multiline-cell">' + pairText(pickValue(item, ['SERTIPIKAT_IDK', 'sertipikat_idk']), pickValue(item, ['TGL_SER_IDK', 'tgl_ser_idk'])) + '</td>';
                html += '<td class="multiline-cell">' + pairText(pickValue(item, ['SU_INDUK', 'su_induk']), pickValue(item, ['TGL_SU_INDUK', 'tgl_su_induk'])) + '</td>';
                html += '<td class="number-cell">' + formatNumber(pickValue(item, ['LUAS_SU_INDUK', 'luas_su_induk'])) + '</td>';
                html += '<td class="multiline-cell">' + pairText(pickValue(item, ['SER_PISAH', 'ser_pisah']), pickValue(item, ['TGL_SER_PISAH', 'tgl_ser_pisah'])) + '</td>';
                html += '<td class="multiline-cell">' + pairText(pickValue(item, ['SU_PISAH_IDK', 'su_pisah_idk']), pickValue(item, ['TGL_SU_PISAH_IDK', 'tgl_su_pisah_idk'])) + '</td>';
                html += '<td class="number-cell">' + formatNumber(pickValue(item, ['LUAS_SU_PISAH', 'luas_su_pisah'])) + '</td>';

                if (showGabungan) {
                    html += '<td class="multiline-cell">' + pairText(pickValue(item, ['NO_SERTIPIKAT', 'no_sertipikat']), pickValue(item, ['TGL_SERTIPIKAT', 'tgl_sertipikat'])) + '</td>';
                    html += '<td class="multiline-cell">' + pairText(pickValue(item, ['SU_PISAH', 'su_pisah']), pickValue(item, ['TGL_SU_PISAH', 'tgl_su_pisah'])) + '</td>';
                    html += '<td class="number-cell">' + formatNumber(pickValue(item, ['LUAS_SUP', 'luas_sup'])) + '</td>';
                }

                html += '<td class="center-cell">' + escapeHtml(formatDate(pickValue(item, ['TGL_BERLAKU', 'tgl_berlaku']))) + '</td>';
                html += '</tr>';
            });
        }

        html += '</tbody></table></div>';
        html += '<div class="report-signature-footer">';
        html += '<div class="signature-date">Jakarta, ' + escapeHtml(formatTanggalIndonesia(new Date())) + '</div>';
        html += '<div class="signature-grid">';
        html += '<div class="signature-box">Yang menyerahkan,<div class="signature-space"></div><div class="signature-line">(........................................)</div></div>';
        html += '<div class="signature-box">Yang menerima,<div class="signature-space"></div><div class="signature-line">(........................................)</div></div>';
        html += '</div></div>';

        $('#mainDisplay').html(html);
        $('#printButton').prop('disabled', false);
    }

    function buildColgroup(showGabungan) {
        var html = '<colgroup>';
        html += '<col style="width:52px"><col style="width:110px"><col style="width:260px">';
        html += '<col style="width:180px"><col style="width:180px"><col style="width:100px">';
        html += '<col style="width:190px"><col style="width:180px"><col style="width:100px">';
        if (showGabungan) {
            html += '<col style="width:200px"><col style="width:180px"><col style="width:100px">';
        }
        html += '<col style="width:130px"></colgroup>';
        return html;
    }

    function pickValue(object, keys) {
        for (var i = 0; i < keys.length; i++) {
            if (object[keys[i]] !== undefined && object[keys[i]] !== null) {
                return object[keys[i]];
            }
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

    function companyNameFromLayout() {
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
                var name = extractCompanyName(candidates[j]);
                if (name) {
                    return name;
                }
            }
        }

        return '';
    }

    function resolveReportCompany(first) {
        var unit = String($('#perusahaan').val() || '').trim().toUpperCase();
        var rowName = pickValue(first || {}, [
            'NAMA_PT', 'nama_pt',
            'ATAS_NAMA_PT', 'atas_nama_pt',
            'NAMA_PERUSAHAAN', 'nama_perusahaan'
        ]);
        var sessionName = String($('#nama_perusahaan_session').val() || '').trim();
        var cacheKey = 'sris.report-company.' + unit;
        var cachedName = '';

        try {
            cachedName = localStorage.getItem(cacheKey) || '';
        } catch (error) {
            cachedName = '';
        }

        var company = extractCompanyName(rowName) || String(rowName || '').trim()
            || companyNameFromLayout()
            || extractCompanyName(sessionName) || sessionName
            || cachedName
            || unit
            || '-';

        if (company && company !== '-' && company.toUpperCase() !== unit) {
            try {
                localStorage.setItem(cacheKey, company);
            } catch (error) {
                // Browser dapat menolak storage; nama tetap dipakai untuk render saat ini.
            }
        }

        return company;
    }

    function valueOrDash(value) {
        var text = String(value === null || value === undefined ? '' : value).trim();
        return text === '' ? '-' : text;
    }

    function pairText(number, date) {
        var numberText = escapeHtml(valueOrDash(number));
        var dateText = escapeHtml(formatDate(date));
        return numberText + '<br>' + dateText;
    }

    function formatDate(value) {
        if (value === null || value === undefined || String(value).trim() === '') {
            return '-';
        }

        var text = String(value).trim();
        var match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (match) {
            return match[3] + '-' + match[2] + '-' + match[1];
        }

        match = text.match(/^(\d{4})(\d{2})(\d{2})$/);
        if (match) {
            return match[3] + '-' + match[2] + '-' + match[1];
        }

        var date = new Date(text);
        if (!isNaN(date.getTime())) {
            return String(date.getDate()).padStart(2, '0') + '-'
                + String(date.getMonth() + 1).padStart(2, '0') + '-'
                + date.getFullYear();
        }

        return text;
    }

    function formatNumber(value) {
        if (value === null || value === undefined || String(value).trim() === '') {
            return '-';
        }
        var number = Number(value);
        if (isNaN(number)) {
            return escapeHtml(value);
        }
        return number.toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatTanggalIndonesia(date) {
        var bulan = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        return date.getDate() + ' ' + bulan[date.getMonth()] + ' ' + date.getFullYear();
    }

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showAjaxError(xhr, fallback) {
        var message = fallback;
        if (xhr.responseJSON) {
            if (xhr.responseJSON.message) {
                message += '\n' + xhr.responseJSON.message;
            }
            if (xhr.responseJSON.errors) {
                var errors = [];
                $.each(xhr.responseJSON.errors, function (_, values) {
                    errors = errors.concat(values);
                });
                if (errors.length) {
                    message += '\n' + errors.join('\n');
                }
            }
        }
        alert(message);
    }
</script>
@endsection