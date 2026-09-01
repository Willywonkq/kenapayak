@extends('layouts.template')

@section('content')


<style>
    /* =========================================================
       V31 CLEAN — DAFTAR SERTIPIKAT BALIK NAMA
       Satu stylesheet final, tanpa tumpukan override V2–V30.
       ========================================================= */

    .sertifikat-balik-nama-page,
    .sertifikat-balik-nama-page * {
        box-sizing: border-box;
    }

    .sertifikat-balik-nama-page {
        --ui-ink: #172033;
        --ui-muted: #667085;
        --ui-line: #dbe3ef;
        --ui-blue: #2563eb;
        --ui-blue-dark: #1d4ed8;
        --ui-card: #ffffff;
        --ui-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);

        width: 100%;
        min-width: 0;
        min-height: calc(100vh - 70px);
        padding: 18px 12px 32px;
        overflow-x: hidden;
        background:
            radial-gradient(circle at 95% 2%, rgba(37, 99, 235, 0.07), transparent 28%),
            radial-gradient(circle at 8% 96%, rgba(56, 189, 248, 0.05), transparent 26%),
            #f3f6fb;
        color: var(--ui-ink);
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 12px;
    }

    /* =========================================================
       HEADER WORKSPACE
       ========================================================= */
    .baliknama-view-header {
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

    .baliknama-view-header::before {
        content: "◈";
        position: absolute;
        left: 20px;
        display: grid;
        width: 34px;
        height: 34px;
        place-items: center;
        border-radius: 11px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.24);
        font-size: 18px;
    }

    .baliknama-view-header::after {
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

    .baliknama-view-header span {
        position: relative;
        z-index: 1;
        color: #172033;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 13px;
        font-weight: 900;
        letter-spacing: 0.11em;
        text-transform: uppercase;
    }

    .baliknama-view-header code {
        position: relative;
        z-index: 1;
        padding: 7px 12px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1e40af;
        font-family: Consolas, "Courier New", monospace;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.06em;
    }

    /* =========================================================
       FILTER CARD
       ========================================================= */
    .desktop-filter-panel {
        position: relative;
        z-index: 30;
        width: 100%;
        min-width: 0;
        padding: 16px;
        overflow: visible;
        border: 1px solid var(--ui-line);
        border-radius: 24px;
        background: var(--ui-card);
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
    }

    .desktop-filter-panel::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        border-radius: 24px 0 0 24px;
        background: linear-gradient(180deg, #38bdf8 0%, #2563eb 52%, #1d4ed8 100%);
    }

    .desktop-filter-panel::after {
        content: "Sertifikat Balik Nama";
        position: absolute;
        right: 24px;
        bottom: 10px;
        color: rgba(37, 99, 235, 0.04);
        font-size: 30px;
        font-weight: 800;
        letter-spacing: 0.06em;
        pointer-events: none;
    }

    /*
     * Satu sistem grid untuk baris 1 dan baris 2:
     *
     * 1 label kiri
     * 2 input/lookup kiri
     * 3 s.d / bagian lookup
     * 4 input/lookup kiri
     * 5 label kanan
     * 6 field kanan
     * 7 s.d / field kanan
     * 8 field kanan
     * 9 action
     *
     * Label besar diberi ruang sendiri, sehingga tidak tertabrak field.
     */
    .desktop-filter-panel .filter-row {
        position: relative;
        z-index: 2;
        display: grid;
        width: 100%;
        min-width: 0;
        min-height: 42px;
        align-items: center;
        column-gap: 7px;
        row-gap: 0;
        white-space: nowrap;
    }

    .desktop-filter-panel .filter-row + .filter-row {
        margin-top: 8px;
    }

    .desktop-filter-panel .filter-row:first-of-type,
    .desktop-filter-panel .filter-row:nth-of-type(2),
    .desktop-filter-panel .baliknama-options-row {
        grid-template-columns:
            60px
            minmax(76px, .64fr)
            28px
            minmax(76px, .64fr)
            132px
            minmax(126px, 1fr)
            28px
            minmax(126px, 1fr)
            72px;
    }

    /* Semua LABEL UTAMA sengaja sama persis. */
    .desktop-filter-panel .label-blok,
    .desktop-filter-panel .label-tanggal,
    .desktop-filter-panel .label-sektor,
    .desktop-filter-panel .label-ttd {
        display: flex;
        width: auto;
        min-width: 0;
        max-width: none;
        align-items: center;
        margin: 0;
        padding-right: 8px;
        overflow: visible;
        color: #344054;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: .025em;
        text-overflow: clip;
        text-transform: uppercase;
        white-space: nowrap;
    }

    /* Row 1 */
    .filter-row:first-of-type .label-blok { grid-column: 1; }
    .filter-row:first-of-type #blok_awal { grid-column: 2; }
    .filter-row:first-of-type .range-label:first-of-type { grid-column: 3; }
    .filter-row:first-of-type #blok_akhir { grid-column: 4; }
    .filter-row:first-of-type .label-tanggal { grid-column: 5; }
    .filter-row:first-of-type #tgl_awal { grid-column: 6; }
    .filter-row:first-of-type .range-label:last-of-type { grid-column: 7; }
    .filter-row:first-of-type #tgl_akhir { grid-column: 8; }
    .filter-row:first-of-type .ok-button { grid-column: 9; }

    /* Row 2 */
    .filter-row:nth-of-type(2) .label-sektor { grid-column: 1; }
    .filter-row:nth-of-type(2) .lookup-wrap { grid-column: 2 / 5; }
    .filter-row:nth-of-type(2) .label-ttd { grid-column: 5; }
    .filter-row:nth-of-type(2) .ttd-dropdown { grid-column: 6 / 9; }
    .filter-row:nth-of-type(2) .baliknama-print-button { grid-column: 9; }

    /* Row 3 */
    .baliknama-options-row {
        min-height: 34px !important;
        margin-top: 8px !important;
    }

    .baliknama-apartemen-slot {
        grid-column: 1 / 5;
        display: flex;
        min-width: 0;
        align-items: center;
        justify-content: flex-start;
    }

    /* =========================================================
       INPUT / LOOKUP
       ========================================================= */
    .desktop-input,
    .lookup-value {
        width: 100%;
        min-width: 0;
        height: 42px;
        border: 1px solid #c8d3e1;
        border-radius: 12px;
        background: #ffffff;
        color: #101828;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 11.5px;
        font-weight: 600;
        outline: none;
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .desktop-input {
        padding: 8px 10px;
    }

    .desktop-input:hover,
    .lookup-value:hover {
        border-color: #aebed1;
    }

    .desktop-input:focus {
        border-color: var(--ui-blue);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
    }

    #tgl_awal,
    #tgl_akhir {
        padding-left: 9px;
        padding-right: 7px;
        font-variant-numeric: tabular-nums;
    }

    input[type="date"]::-webkit-calendar-picker-indicator {
        flex: 0 0 auto;
        margin-left: 4px;
        opacity: 1;
        cursor: pointer;
    }

    .range-label {
        display: inline-flex;
        width: 28px;
        min-width: 28px;
        height: 26px;
        align-items: center;
        justify-content: center;
        justify-self: center;
        padding: 0;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 8.5px;
        font-weight: 900;
        letter-spacing: 0.03em;
    }

    .lookup-wrap {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 40px;
        gap: 7px;
        width: 100%;
        min-width: 0;
    }

    .lookup-value {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .lookup-button {
        display: inline-flex;
        width: 40px;
        min-width: 40px;
        height: 42px;
        align-items: center;
        justify-content: center;
        margin: 0;
        border: 1px solid #bfdbfe;
        border-radius: 13px;
        background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%);
        color: #1d4ed8;
        cursor: pointer;
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .lookup-button:hover {
        transform: translateY(-1px);
        background: linear-gradient(145deg, #dbeafe 0%, #bfdbfe 100%);
        box-shadow: 0 7px 15px rgba(37, 99, 235, 0.12);
    }

    /* =========================================================
       ACTION BUTTONS — MENTOK DI EDGE CARD PUTIH
       ========================================================= */
    .ok-button,
    .baliknama-print-button {
        width: calc(100% + 16px);
        min-width: 0;
        max-width: none;
        height: 42px;
        margin: 0;
        justify-self: stretch;
        border: 0;
        border-radius: 12px 0 0 12px;
        color: #ffffff;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 11px;
        font-weight: 850;
        letter-spacing: .06em;
        text-transform: uppercase;
        cursor: pointer;
        transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
    }

    .ok-button {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
    }

    .baliknama-print-button {
        background: linear-gradient(135deg, #14b8a6 0%, #059669 100%);
        box-shadow: 0 8px 18px rgba(5, 150, 105, .24);
    }

    .ok-button:hover,
    .baliknama-print-button:hover:not(:disabled) {
        transform: translateY(-1px);
    }

    .baliknama-print-button:disabled {
        opacity: .48;
        cursor: not-allowed;
        box-shadow: none;
        filter: grayscale(.12);
    }

    /* =========================================================
       APARTEMEN
       ========================================================= */
    .apartemen-wrap {
        display: inline-flex;
        width: max-content;
        min-width: 96px;
        min-height: 32px;
        align-items: center;
        justify-content: flex-start;
        gap: 7px;
        margin: 0;
        padding: 6px 10px;
        overflow: visible;
        border: 1px solid #e5eaf2;
        border-radius: 999px;
        background: #fafbfc;
        color: #475467;
        font-size: 10.5px;
        font-weight: 700;
        white-space: nowrap;
    }

    .apartemen-wrap input {
        width: 15px;
        height: 15px;
        margin: 0;
        accent-color: var(--ui-blue);
    }

    /* =========================================================
       TTD MENGETAHUI
       ========================================================= */
    .ttd-dropdown {
        position: relative;
        z-index: 90;
        width: 100%;
        min-width: 0;
        max-width: none;
    }

    .ttd-selected {
        position: relative;
        display: flex;
        width: 100%;
        min-width: 0;
        height: 44px;
        align-items: center;
        padding: 0;
        overflow: hidden;
        border: 1px solid #cbd7e5;
        border-radius: 14px;
        background: #ffffff;
        color: #101828;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 3px 10px rgba(15, 23, 42, 0.04);
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .ttd-selected:hover {
        border-color: #9eb5cc;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.07);
    }

    .ttd-dropdown.is-open .ttd-selected {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .11), 0 8px 20px rgba(15, 23, 42, .08);
    }

    .ttd-selected-code {
        display: inline-flex;
        width: 62px;
        min-width: 62px;
        height: 42px;
        flex: 0 0 62px;
        align-items: center;
        padding: 0 7px;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0;
    }

    .ttd-selected-code::before {
        content: "";
        width: 7px;
        height: 7px;
        flex: 0 0 7px;
        margin-right: 7px;
        border-radius: 50%;
        background: #60a5fa;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, .12);
    }

    .ttd-selected-name {
        display: flex;
        width: auto;
        min-width: 0;
        height: 42px;
        flex: 1 1 auto;
        align-items: center;
        padding: 0 7px;
        overflow: hidden;
        color: #344054;
        font-size: 11.5px;
        font-weight: 650;
        letter-spacing: 0;
        text-overflow: clip;
        white-space: nowrap;
    }

    .ttd-arrow {
        display: flex;
        width: 34px;
        min-width: 34px;
        height: 32px;
        flex: 0 0 34px;
        align-items: center;
        justify-content: center;
        margin: 5px;
        border: 0;
        border-radius: 10px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 9px;
        transition: transform .18s ease, background .18s ease;
    }

    .ttd-dropdown.is-open .ttd-arrow {
        transform: rotate(180deg);
        background: #dbeafe;
    }

    .ttd-dropdown-panel {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        z-index: 10050;
        display: none;
        width: max(100%, 420px);
        max-width: min(560px, calc(100vw - 48px));
        max-height: 290px;
        padding: 7px;
        overflow: auto;
        border: 1px solid #d8e2ee;
        border-radius: 16px;
        background: rgba(255,255,255,.99);
        box-shadow: 0 20px 45px rgba(15, 23, 42, .16);
        scrollbar-width: thin;
        scrollbar-color: #93c5fd #f4f7fb;
        transform-origin: top left;
    }

    .ttd-dropdown-panel[style*="display: block"] {
        animation: ttdDropdownIn .14s ease-out;
    }

    @keyframes ttdDropdownIn {
        from { opacity: 0; transform: translateY(-5px) scale(.985); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .ttd-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 4px;
        table-layout: fixed;
        background: transparent;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 12px;
    }

    .ttd-table td {
        height: 42px;
        padding: 8px 11px;
        border: 0;
        background: #ffffff;
        color: #475467;
    }

    .ttd-table td:first-child {
        width: 88px;
        border-radius: 11px 0 0 11px;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 800;
    }

    .ttd-table td:last-child {
        position: relative;
        padding-right: 36px;
        border-radius: 0 11px 11px 0;
        font-weight: 650;
    }

    .ttd-table tr {
        cursor: pointer;
    }

    .ttd-table tr:hover td {
        background: #f4f8ff;
        color: #1d4ed8;
        box-shadow: 0 5px 14px rgba(37, 99, 235, .07);
    }

    .ttd-table tr.active td {
        background: #eff6ff;
        color: #1d4ed8;
        box-shadow: inset 3px 0 0 #2563eb;
    }

    .ttd-table tr.active td:last-child::after {
        content: "✓";
        position: absolute;
        top: 50%;
        right: 12px;
        display: inline-flex;
        width: 20px;
        height: 20px;
        align-items: center;
        justify-content: center;
        transform: translateY(-50%);
        border-radius: 50%;
        background: #dbeafe;
        color: #2563eb;
        font-size: 11px;
        font-weight: 900;
    }

    /* =========================================================
       INITIAL / LOADING
       ========================================================= */
    .report-workspace {
        position: relative;
        z-index: 1;
        width: 100%;
        min-width: 0;
        min-height: 650px;
        margin-top: 18px;
        padding: 20px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, .95);
        border-radius: 24px;
        background: rgba(255, 255, 255, .94);
        box-shadow: 0 20px 48px rgba(15, 23, 42, .10);
    }

    .report-workspace::before {
        content: "";
        position: absolute;
        top: 0;
        left: 7%;
        right: 7%;
        height: 3px;
        border-radius: 0 0 999px 999px;
        background: linear-gradient(90deg, transparent, #38bdf8, #2563eb, #6366f1, transparent);
    }

    .initial-report {
        position: relative;
        display: flex;
        width: 100%;
        min-width: 0;
        min-height: 360px;
        align-items: center;
        justify-content: center;
        border: 1px dashed #bfdbfe;
        border-radius: 20px;
        background:
            radial-gradient(circle at center, rgba(37, 99, 235, 0.06), transparent 46%),
            #f8fbff;
    }

    .initial-report::before {
        content: "▦";
        display: flex;
        width: 52px;
        height: 52px;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: #dbeafe;
        color: #2563eb;
        font-size: 24px;
        font-weight: 700;
        transform: translateY(-14px);
    }

    .initial-report::after {
        content: "Silahkan Isi filter kemudian klik OK";
        position: absolute;
        top: calc(50% + 34px);
        left: 50%;
        transform: translateX(-50%);
        color: #667085;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }

    .loading-info {
        display: none;
        width: fit-content;
        margin: 0 0 12px;
        padding: 8px 12px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 700;
    }

    /* =========================================================
       REPORT
       ========================================================= */
    .report-paper {
        width: 100%;
        min-width: 0;
        max-width: 100%;
        padding: 0 0 28px;
        overflow: hidden;
        background: transparent;
    }

    .baliknama-report-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 2fr) minmax(0, 1fr);
        gap: 12px;
        min-height: 76px;
        align-items: center;
        margin-bottom: 10px;
        padding: 16px 18px;
        border: 1px solid #dce9f8;
        border-radius: 17px;
        background: linear-gradient(110deg, #e9f3ff, #f2f7ff 50%, #e7f0ff);
        box-shadow: inset 0 1px 0 #fff;
    }

    .baliknama-report-company {
        min-width: 0;
        overflow: hidden;
        color: #2563eb;
        font-size: 12.5px;
        font-weight: 900;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .baliknama-report-title {
        padding: 0 8px;
        color: #172033;
        font-family: Cambria, Georgia, "Times New Roman", serif;
        font-size: 19px;
        font-weight: 800;
        line-height: 1.2;
        text-align: center;
    }

    .baliknama-report-period {
        color: #40546b;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.55;
        text-align: right;
    }

    .baliknama-report-subtitle {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        min-height: 47px;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
        padding: 9px 12px;
        border: 1px solid #e3ebf4;
        border-radius: 13px;
        background: #fff;
        color: #526174;
        font-size: 11.5px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, .035);
    }

    .baliknama-report-subtitle-label {
        justify-self: start;
        white-space: nowrap;
    }

    .baliknama-report-sector-value {
        justify-self: center;
        color: #24374e;
        font-weight: 900;
        white-space: nowrap;
    }

    .baliknama-report-live-badge {
        display: inline-flex;
        justify-self: end;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border: 1px solid #a7f3d0;
        border-radius: 999px;
        background: #ecfdf5;
        color: #059669;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .baliknama-report-live-badge::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, .10);
    }

    .desktop-report-table-wrap {
        width: 100%;
        min-width: 0;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        border: 1px solid #d7e2ef;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
        scrollbar-color: #79a7d8 #edf4fb;
        scrollbar-width: auto;
        scrollbar-gutter: stable;
    }

    .desktop-report-table {
        width: 100%;
        min-width: 1770px;
        table-layout: fixed;
        border-collapse: collapse;
        color: #1e3048;
        font-family: Arial, "Segoe UI", sans-serif;
        font-size: 12.5px;
        line-height: 1.48;
    }

    .desktop-report-table th,
    .desktop-report-table td {
        padding: 9px 8px;
        overflow: hidden;
        border: 0;
        border-right: 1px solid #d7e2ef;
        border-bottom: 1px solid #d7e2ef;
        overflow-wrap: anywhere;
        word-break: normal;
        white-space: normal;
        vertical-align: middle;
    }

    .desktop-report-table thead th {
        padding: 10px 7px;
        background: linear-gradient(180deg, #e9f2ff, #dfeaff);
        color: #29415f;
        font-size: 11.5px;
        font-weight: 900;
        line-height: 1.3;
        text-align: center;
    }

    .desktop-report-table thead tr:first-child th[colspan] {
        background: linear-gradient(180deg, #dbe8ff, #cfdefa);
        color: #314f82;
    }

    .desktop-report-table thead tr:nth-child(2) th {
        background: #edf4ff;
        color: #47617e;
        font-size: 11px;
    }

    .desktop-report-table tbody td {
        min-height: 44px;
        color: #1e3048;
        font-size: 12.5px;
        line-height: 1.48;
    }

    .desktop-report-table tbody tr:nth-child(even) td {
        background: #f9fbfe;
    }

    .desktop-report-table tbody tr:hover td {
        background: #eef6ff;
    }

    .desktop-report-table tbody td:nth-child(3) {
        color: #203a58;
        font-weight: 800;
    }

    .desktop-report-table .center { text-align: center; }
    .desktop-report-table .right { text-align: right; font-variant-numeric: tabular-nums; }

    .empty-data-row td {
        height: 330px;
        background: #ffffff !important;
    }

    /* =========================================================
       SIGNATURE
       ========================================================= */
    .report-signature-section {
        width: min(100%, 900px);
        margin: 34px auto 8px;
        padding: 0 30px 8px;
        color: #344054;
        font-size: 11px;
    }

    .report-signature-date {
        width: 50%;
        margin: 0 0 8px auto;
        text-align: center;
        color: #475467;
        font-weight: 600;
    }

    .report-signature-primary {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 90px;
    }

    .report-signature-box {
        min-width: 0;
        text-align: center;
    }

    .report-signature-role {
        min-height: 18px;
        font-weight: 600;
    }

    .report-signature-space {
        height: 76px;
    }

    .report-signature-line {
        display: inline-block;
        min-width: 205px;
        padding: 0 8px 4px;
        color: #667085;
        border-bottom: 1px dotted #98a2b3;
        line-height: 1;
    }

    .report-signature-approval {
        width: 50%;
        margin: 42px auto 0;
        text-align: center;
    }

    .report-signature-approval .report-signature-space {
        height: 72px;
    }

    .report-signature-name {
        display: inline-block;
        min-width: 230px;
        color: #344054;
        font-weight: 650;
    }

    /* =========================================================
       MODAL SEKTOR
       ========================================================= */
    #sektorModal .modal-content {
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 20px;
        background: #ffffff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
    }

    #sektorModal .modal-header {
        border-bottom: 1px solid #dbe3ef;
        background: linear-gradient(90deg, #ffffff 0%, #f8fbff 100%);
        color: #1d2939;
    }

    #sektorModal .modal-body {
        background: #f8fafc;
    }

    .modal-search {
        width: 100%;
        height: 42px;
        margin-bottom: 8px;
        padding: 5px 8px;
        border: 1px solid #c8d3e1;
        border-radius: 12px;
        background: #ffffff;
        color: #101828;
        outline: none;
    }

    .modal-search:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .modal-table-wrap {
        max-height: 400px;
        overflow: auto;
        border: 1px solid #dbe3ef;
        border-radius: 14px;
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
        padding: 9px 10px;
        border: 0;
        border-bottom: 1px solid #e8eef5;
    }

    .modal-table th {
        background: #eff6ff;
        color: #344054;
        font-weight: 700;
    }

    .modal-table tbody tr {
        cursor: pointer;
    }

    .modal-table tbody tr:hover td {
        background: #eff6ff;
        color: #1d4ed8;
    }

    /* =========================================================
       ZOOM / VIEWPORT
       ========================================================= */
    @media screen and (max-width: 880px) {
        .desktop-filter-panel {
            padding-left: 12px;
            padding-right: 12px;
        }

        .desktop-filter-panel .filter-row:first-of-type,
        .desktop-filter-panel .filter-row:nth-of-type(2),
        .desktop-filter-panel .baliknama-options-row {
            grid-template-columns:
                56px
                minmax(68px, .60fr)
                26px
                minmax(68px, .60fr)
                124px
                minmax(116px, .94fr)
                26px
                minmax(116px, .94fr)
                64px;
            column-gap: 5px;
        }

        .desktop-filter-panel .label-blok,
        .desktop-filter-panel .label-tanggal,
        .desktop-filter-panel .label-sektor,
        .desktop-filter-panel .label-ttd {
            padding-right: 6px;
            font-size: 12.5px;
        }

        .ok-button,
        .baliknama-print-button {
            width: calc(100% + 12px);
        }
    }

    /* =========================================================
       PRINT — BLACK & WHITE + FULL BORDERS
       Orientation sengaja tidak dikunci agar user dapat memilih
       Portrait / Landscape dari dialog print browser.
       ========================================================= */
    @media print {
        @page {
            size: auto;
            margin: 8mm;
        }

        html,
        body {
            width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
        }

        .baliknama-view-header,
        .desktop-filter-panel,
        #sektorModal,
        #sertifikatBalikNamaNoDataAlertModal,
        .loading-info,
        .baliknama-print-button {
            display: none !important;
        }

        .sertifikat-balik-nama-page,
        .report-workspace,
        #mainDisplay,
        .report-paper,
        .desktop-report-table-wrap {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            overflow: visible !important;
            border-radius: 0 !important;
            background: #ffffff !important;
            box-shadow: none !important;
        }

        .sertifikat-balik-nama-page,
        .report-workspace {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            border: 0 !important;
        }

        .report-workspace::before {
            display: none !important;
        }

        .report-paper {
            padding-top: 0 !important;
            padding-bottom: 8px !important;
        }

        .baliknama-report-header,
        .baliknama-report-subtitle {
            border: 0.8pt solid #777 !important;
            border-radius: 0 !important;
            background: #ffffff !important;
            box-shadow: none !important;
            color: #000000 !important;
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        .baliknama-report-company,
        .baliknama-report-title,
        .baliknama-report-period,
        .baliknama-report-subtitle,
        .baliknama-report-subtitle-label,
        .baliknama-report-sector-value,
        .baliknama-report-live-badge {
            color: #000000 !important;
        }

        .baliknama-report-live-badge {
            border-color: #777 !important;
            background: #ffffff !important;
        }

        .desktop-report-table-wrap {
            border: 0 !important;
        }

        .desktop-report-table {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            border-spacing: 0 !important;
            border: 0.8pt solid #000 !important;
            background: #ffffff !important;
            color: #000000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .desktop-report-table col:nth-child(1)  { width: 3% !important; }
        .desktop-report-table col:nth-child(2)  { width: 6% !important; }
        .desktop-report-table col:nth-child(3)  { width: 15% !important; }
        .desktop-report-table col:nth-child(4)  { width: 10% !important; }
        .desktop-report-table col:nth-child(5)  { width: 7% !important; }
        .desktop-report-table col:nth-child(6)  { width: 10% !important; }
        .desktop-report-table col:nth-child(7)  { width: 7% !important; }
        .desktop-report-table col:nth-child(8)  { width: 6% !important; }
        .desktop-report-table col:nth-child(9)  { width: 8% !important; }
        .desktop-report-table col:nth-child(10) { width: 9% !important; }
        .desktop-report-table col:nth-child(11) { width: 9% !important; }
        .desktop-report-table col:nth-child(12) { width: 10% !important; }

        .desktop-report-table thead {
            display: table-header-group !important;
        }

        .desktop-report-table tr {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        .desktop-report-table,
        .desktop-report-table thead,
        .desktop-report-table tbody,
        .desktop-report-table tr,
        .desktop-report-table th,
        .desktop-report-table td {
            background: #ffffff !important;
            background-image: none !important;
            color: #000000 !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }

        .desktop-report-table th,
        .desktop-report-table td {
            width: auto !important;
            min-width: 0 !important;
            padding: 4px 3px !important;
            border: 0.8pt solid #000000 !important;
            font-size: 8.4px !important;
            line-height: 1.28 !important;
            overflow: visible !important;
            overflow-wrap: anywhere !important;
            word-break: normal !important;
            white-space: normal !important;
        }

        .desktop-report-table thead th {
            font-weight: 700 !important;
            text-align: center !important;
        }

        .report-signature-section {
            color: #000000 !important;
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        .report-signature-date,
        .report-signature-role,
        .report-signature-name,
        .report-signature-line {
            color: #000000 !important;
        }
    }

    /* =========================================================
       ALERT DATA KOSONG — SAMA DENGAN DAFTAR SERTIFIKAT PECAHAN
       Modal informasi yang tampil saat hasil laporan tidak
       menghasilkan baris data sama sekali.
       ========================================================= */
        #sertifikatBalikNamaNoDataAlertModal .modal-dialog {
            max-width: 380px;
        }

        #sertifikatBalikNamaNoDataAlertModal .modal-content {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
        }

        #sertifikatBalikNamaNoDataAlertModal .alert-icon-wrapper {
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

        #sertifikatBalikNamaNoDataAlertModal .alert-title {
            margin-bottom: 8px;
            color: #172033;
            font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
            font-size: 18px;
            font-weight: 700;
        }

        #sertifikatBalikNamaNoDataAlertModal .alert-message {
            margin-bottom: 24px;
            color: #475569;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            font-size: 14px;
        }

        #sertifikatBalikNamaNoDataAlertModal .alert-btn-ok {
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

        #sertifikatBalikNamaNoDataAlertModal .alert-btn-ok:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.2);
        }

</style>

<!-- MODAL ALERT DATA KOSONG -->
<div class="modal fade" id="sertifikatBalikNamaNoDataAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4" style="text-align: center; padding: 1.5rem;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-title">Information</div>
                <div class="alert-message" id="sertifikatBalikNamaNoDataMessage">Data tidak ditemukan.</div>
                <button
                    type="button"
                    class="btn alert-btn-ok"
                    data-dismiss="modal"
                    onclick="hideSertifikatBalikNamaNoDataAlert()"
                >OK</button>
            </div>
        </div>
    </div>
</div>


<div class="sertifikat-balik-nama-page">


    <div class="baliknama-view-header" id="balikNamaViewHeader">
        <span>DAFTAR SERTIFIKAT BALIK NAMA </span>
        <code>
            UNIT {{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}
        </code>
    </div>

    <input
        type="hidden"
        id="perusahaan"
        value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}"
    >

    <input
        type="hidden"
        id="nama_perusahaan_session"
        value="{{ session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? '' }}"
    >

    <div
        id="balikNamaConfig"
        hidden
        data-csrf-token="{{ csrf_token() }}"
        data-url-ttd="{{ url()->current() }}/get_ttd_mengetahui"
        data-url-sektor="{{ url()->current() }}/get_sektor"
        data-url-data="{{ url()->current() }}/get_data"
    ></div>

    <input type="hidden" id="sektor" value="*">

    <section class="desktop-filter-panel">

        <div class="filter-row">

            <label class="filter-label label-blok" for="blok_awal">
                Blok:
            </label>

            <input
                type="text"
                id="blok_awal"
                class="desktop-input blok-input"
                value="A"
                maxlength="30"
            >

            <span class="range-label">s.d</span>

            <input
                type="text"
                id="blok_akhir"
                class="desktop-input blok-input"
                value="Z"
                maxlength="30"
            >

            <label class="filter-label label-tanggal" for="tgl_awal">
                Tgl. Input:
            </label>

            <input
                type="date"
                id="tgl_awal"
                class="desktop-input tanggal-input"
            >

            <span class="range-label">s.d</span>

            <input
                type="date"
                id="tgl_akhir"
                class="desktop-input tanggal-input"
            >

            <button
                type="button"
                class="ok-button"
                onclick="getData()"
            >
                Ok
            </button>

        </div>

        <div class="filter-row">

            <span class="filter-label label-sektor">
                Sektor:
            </span>

            <div class="lookup-wrap">
                <div id="sektorEntry" class="lookup-value">
                    Semua Sektor
                </div>

                <button
                    type="button"
                    class="lookup-button"
                    onclick="getSektorModal()"
                    title="Pilih sektor"
                    aria-label="Pilih sektor"
                >
                    <i class="fas fa-binoculars"></i>
                </button>
            </div>

            <label class="filter-label label-ttd">
                TTD Mengetahui:
            </label>

            <div class="ttd-dropdown" id="ttdDropdown">
                <input type="hidden" id="ttd_mengetahui" value="">
                <input type="hidden" id="ttd_mengetahui_nama" value="">

                <button
                    type="button"
                    class="ttd-selected"
                    onclick="toggleTTDDropdown(event)"
                >
                    <span id="ttdSelectedCode" class="ttd-selected-code">-</span>
                    <span id="ttdSelectedName" class="ttd-selected-name">Memuat...</span>
                    <span class="ttd-arrow">▼</span>
                </button>

                <div class="ttd-dropdown-panel" id="ttdDropdownPanel">
                    <table class="ttd-table">
                        <tbody id="ttdDropdownBody">
                            <tr>
                                <td colspan="2">Memuat...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>


            <button
                type="button"
                id="btnPrintBalikNama"
                class="baliknama-print-button"
                onclick="printBalikNamaReport()"
                disabled
            >
                Print
            </button>

        </div>


        <div class="filter-row baliknama-options-row">
            <div class="baliknama-apartemen-slot">

            <label class="apartemen-wrap">
                <input
                    type="checkbox"
                    id="apartemen"
                    value="1"
                >
                <span>Apartemen</span>
            </label>
            </div>
        </div>

    </section>

    <section class="report-workspace">

        <div id="loadingInfo" class="loading-info">
            Mengambil data...
        </div>

        <div id="mainDisplay">
            <div class="initial-report"></div>
        </div>

    </section>

</div>


<div
    class="modal fade"
    id="sektorModal"
    tabindex="-1"
    aria-hidden="true"
>
    <div
        class="modal-dialog modal-dialog-centered"
        style="max-width:700px;"
    >
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Pilih Sektor
                </h5>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="Close"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div
                class="modal-body"
                id="sektorModalContent"
            ></div>

        </div>
    </div>
</div>

@endsection


@section('js')

<script>
    $(document).ready(function () {

        console.info('BALIK_NAMA_REPORT_VIEW=V31-20260819-CLEAN-CSS');
        document.body.setAttribute('data-balik-nama-report-view', 'V31-20260819-CLEAN-CSS');

        resetInitialState();

        $('#blok_awal, #blok_akhir').on('input', function () {
            $(this).val(
                String($(this).val() || '').toUpperCase()
            );
        });

        loadTTDMengetahui();
    });

    $(window).on('pageshow', function (event) {
        var pageEvent = event.originalEvent || event;

        if (pageEvent.persisted) {
            resetInitialState();
            loadTTDMengetahui();
        }
    });

    function resetInitialState() {
        $('#blok_awal').val('A');
        $('#blok_akhir').val('Z');
        setDefaultDate();
        $('#sektor').val('*');
        $('#sektorEntry').text('Semua Sektor');
        $('#apartemen').prop('checked', false);

        $('#ttd_mengetahui, #ttd_mengetahui_nama').val('');
        $('#ttdSelectedCode').text('-');
        $('#ttdSelectedName').text('Memuat...');
        $('#ttdDropdownPanel').hide();
        $('#ttdDropdown').removeClass('is-open');

        $('#loadingInfo').hide();
        $('#btnPrintBalikNama').prop('disabled', true);
        hideSertifikatBalikNamaNoDataAlert();
        $('#mainDisplay').html('<div class="initial-report"></div>');
    }


    function getBalikNamaConfig() {
        var $config = $('#balikNamaConfig');

        return {
            csrfToken: String($config.attr('data-csrf-token') || ''),
            urlTtd: String($config.attr('data-url-ttd') || ''),
            urlSektor: String($config.attr('data-url-sektor') || ''),
            urlData: String($config.attr('data-url-data') || '')
        };
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
        return String(
            value === null || value === undefined ? '' : value
        )
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r?\n/g, ' ');
    }


    function valueOrDash(value) {
        return (
            value === null
            || value === undefined
            || value === ''
        ) ? '-' : value;
    }


    function formatDate(value) {
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

        return text;
    }


    function formatTanggalIndonesia(dateValue) {
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
            + ' '
            + bulan[date.getMonth()]
            + ' '
            + date.getFullYear();
    }


    function formatLuas(value) {
        if (
            value === null
            || value === undefined
            || value === ''
        ) {
            return '-';
        }

        var number = Number(value);

        if (!isFinite(number)) {
            return escapeHtml(value);
        }

        return number.toLocaleString(
            'id-ID',
            {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }
        );
    }


    function pickValue(item, keys) {
        item = item || {};

        for (var i = 0; i < keys.length; i++) {
            if (
                item[keys[i]] !== null
                && item[keys[i]] !== undefined
                && item[keys[i]] !== ''
            ) {
                return item[keys[i]];
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


    function getFilterData() {
        return {
            _token: getBalikNamaConfig().csrfToken,

            blok_awal:
                $('#blok_awal').val() || 'A',

            blok_akhir:
                $('#blok_akhir').val() || 'Z',

            tgl_awal:
                $('#tgl_awal').val(),

            tgl_akhir:
                $('#tgl_akhir').val(),

            perusahaan:
                $('#perusahaan').val(),

            sektor:
                $('#sektor').val() || '*',

            ttd_mengetahui:
                $('#ttd_mengetahui').val() || '',

            ttd_mengetahui_nama:
                $('#ttd_mengetahui_nama').val() || '',

            apartemen:
                $('#apartemen').is(':checked') ? 1 : 0
        };
    }


    function validateFilter() {
        if (!$('#blok_awal').val() || !$('#blok_akhir').val()) {
            alert('Rentang blok wajib diisi.');
            return false;
        }

        if (!$('#tgl_awal').val() || !$('#tgl_akhir').val()) {
            alert('Rentang tanggal wajib diisi.');
            return false;
        }

        if ($('#tgl_awal').val() > $('#tgl_akhir').val()) {
            alert('Tanggal awal tidak boleh melebihi tanggal akhir.');
            return false;
        }

        if (!$('#perusahaan').val()) {
            alert('Unit/perusahaan tidak tersedia.');
            return false;
        }

        return true;
    }


    /* ==============================================
       TTD MENGETAHUI
       ============================================== */

    function toggleTTDDropdown(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        var $panel = $('#ttdDropdownPanel');
        var willOpen = !$panel.is(':visible');

        // Tetap memakai mekanisme dropdown lama, hanya ditambahkan state visual modern.
        $panel.stop(true, true).toggle(willOpen);
        $('#ttdDropdown').toggleClass('is-open', willOpen);
    }


    function chooseTTD(kode, nama) {
        kode = kode || '';
        nama = nama || kode || '';

        $('#ttd_mengetahui').val(kode);
        $('#ttd_mengetahui_nama').val(nama);

        $('#ttdSelectedCode').text(kode || '-');
        $('#ttdSelectedName').text(nama || '-');

        $('#ttdDropdownBody tr').removeClass('active');

        $('#ttdDropdownBody tr').each(function () {
            if (String($(this).data('kode') || '') === String(kode)) {
                $(this).addClass('active');
            }
        });

        $('#ttdDropdownPanel').hide();
        $('#ttdDropdown').removeClass('is-open');
    }


    function loadTTDMengetahui() {
        var perusahaan = $('#perusahaan').val();

        $('#ttdSelectedCode').text('-');
        $('#ttdSelectedName').text('Memuat...');

        $('#ttdDropdownBody').html(
            '<tr><td colspan="2">Memuat...</td></tr>'
        );

        $.ajax({
            method: 'POST',
            url: getBalikNamaConfig().urlTtd,
            dataType: 'json',

            headers: {
                'Accept': 'application/json'
            },

            data: {
                _token: getBalikNamaConfig().csrfToken,
                perusahaan: perusahaan
            },

            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (
                        response && Array.isArray(response.data)
                            ? response.data
                            : []
                    );

                var html = '';

                $.each(rows, function (index, item) {
                    var kode =
                        item.KODE
                        || item.kode
                        || '';

                    var nama =
                        item.NAMA
                        || item.nama
                        || kode;

                    html +=
                        '<tr data-kode="'
                        + escapeHtml(kode)
                        + '" onclick="chooseTTD(\''
                        + escapeJs(kode)
                        + '\', \''
                        + escapeJs(nama)
                        + '\')">';

                    html +=
                        '<td>'
                        + escapeHtml(kode)
                        + '</td>';

                    html +=
                        '<td>'
                        + escapeHtml(nama)
                        + '</td>';

                    html += '</tr>';
                });

                if (!rows.length) {
                    html =
                        '<tr>'
                        + '<td colspan="2" style="text-align:center;">'
                        + 'Data TTD belum ditemukan'
                        + '</td>'
                        + '</tr>';

                    $('#ttdSelectedCode').text('-');
                    $('#ttdSelectedName').text('Data belum ditemukan');
                }

                $('#ttdDropdownBody').html(html);

                /*
                 * Pilih data pertama agar perilakunya mendekati desktop.
                 * Jika backend sudah memberikan KODE + NAMA, keduanya
                 * akan langsung tampil.
                 */
                if (rows.length) {
                    var firstKode =
                        rows[0].KODE
                        || rows[0].kode
                        || '';

                    var firstNama =
                        rows[0].NAMA
                        || rows[0].nama
                        || firstKode;

                    chooseTTD(firstKode, firstNama);
                }
            },

            error: function (xhr) {
                var detail = '';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    detail = ' ' + xhr.responseJSON.message;
                }

                $('#ttdSelectedCode').text('-');
                $('#ttdSelectedName').text('Gagal mengambil TTD');

                $('#ttdDropdownBody').html(
                    '<tr>'
                    + '<td colspan="2">'
                    + 'Gagal mengambil data TTD.'
                    + escapeHtml(detail)
                    + '</td>'
                    + '</tr>'
                );
            }
        });
    }


    $(document).on('click', function (event) {
        if (!$(event.target).closest('#ttdDropdown').length) {
            $('#ttdDropdownPanel').hide();
            $('#ttdDropdown').removeClass('is-open');
        }
    });


    /* ==============================================
       SEKTOR
       ============================================== */

    function addSektor(kode, deskripsi) {
        $('#sektor').val(kode || '*');
        $('#sektorEntry').text(deskripsi || 'Semua Sektor');

        if (typeof $('#sektorModal').modal === 'function') {
            $('#sektorModal').modal('hide');
        }
    }


    function filterSektorModal(keyword) {
        var search =
            String(keyword || '').toLowerCase().trim();

        $('#sektorModal .modal-table tbody tr').each(function () {
            $(this).toggle(
                $(this).text().toLowerCase().indexOf(search) !== -1
            );
        });
    }


    function getSektorModal() {
        var perusahaan =
            String($('#perusahaan').val() || '').trim();

        if (!perusahaan) {
            alert('Unit/perusahaan belum tersedia.');
            return;
        }

        $.ajax({
            method: 'POST',
            url: getBalikNamaConfig().urlSektor,
            dataType: 'json',

            headers: {
                'Accept': 'application/json'
            },

            data: {
                _token: getBalikNamaConfig().csrfToken,
                perusahaan: perusahaan
            },

            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (
                        response && Array.isArray(response.data)
                            ? response.data
                            : []
                    );

                var html = '';

                html +=
                    '<input '
                    + 'type="text" '
                    + 'class="modal-search" '
                    + 'placeholder="Cari sektor..." '
                    + 'onkeyup="filterSektorModal(this.value)">';

                html += '<div class="modal-table-wrap">';
                html += '<table class="modal-table">';

                html += '<thead>';
                html += '<tr>';
                html += '<th>Kode</th>';
                html += '<th>Deskripsi</th>';
                html += '<th>Perusahaan</th>';
                html += '</tr>';
                html += '</thead>';

                html += '<tbody>';

                html +=
                    '<tr onclick="addSektor(\'*\', \'Semua Sektor\')">';

                html += '<td>*</td>';
                html += '<td>Semua Sektor</td>';
                html += '<td>' + escapeHtml(perusahaan) + '</td>';
                html += '</tr>';

                $.each(rows, function (index, item) {
                    var kode =
                        item.KD_SEKTOR
                        || item.kd_sektor
                        || '';

                    var deskripsi =
                        item.DESKRIPSI
                        || item.deskripsi
                        || kode;

                    var unit =
                        item.KD_PERUSAHAAN
                        || item.kd_perusahaan
                        || perusahaan;

                    html +=
                        '<tr onclick="addSektor(\''
                        + escapeJs(kode)
                        + '\', \''
                        + escapeJs(deskripsi)
                        + '\')">';

                    html +=
                        '<td>'
                        + escapeHtml(kode)
                        + '</td>';

                    html +=
                        '<td>'
                        + escapeHtml(deskripsi)
                        + '</td>';

                    html +=
                        '<td>'
                        + escapeHtml(unit)
                        + '</td>';

                    html += '</tr>';
                });

                if (!rows.length) {
                    html +=
                        '<tr>'
                        + '<td colspan="3" '
                        + 'style="text-align:center;padding:20px;">'
                        + 'Data sektor tidak ditemukan.'
                        + '</td>'
                        + '</tr>';
                }

                html += '</tbody>';
                html += '</table>';
                html += '</div>';

                $('#sektorModalContent').html(html);

                if (typeof $('#sektorModal').modal === 'function') {
                    $('#sektorModal').modal('show');
                }
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


    function printBalikNamaReport() {
        /*
         * Tidak membuat request baru dan tidak mengubah data.
         * Yang dicetak adalah report yang sudah berhasil dirender oleh tombol OK.
         */
        if (!$('#mainDisplay .report-paper').length) {
            alert('Silahkan tampilkan laporan terlebih dahulu dengan menekan OK.');
            return;
        }

        window.print();
    }


    /* ==============================================
       DATA LAPORAN
       ============================================== */

    /*
     * Alert data kosong, mengikuti Daftar Sertifikat Pecahan.
     * Hanya menampilkan pesan; pengambilan data dan render laporan
     * tetap memakai alur yang sudah ada.
     */
    function showSertifikatBalikNamaNoDataAlert(message) {
        var text = message || 'Data tidak ditemukan......!';

        $('#sertifikatBalikNamaNoDataMessage').text(text);

        if (typeof $('#sertifikatBalikNamaNoDataAlertModal').modal === 'function') {
            $('#sertifikatBalikNamaNoDataAlertModal').modal('show');
        } else {
            alert(text);
        }
    }

    function hideSertifikatBalikNamaNoDataAlert() {
        if (typeof $('#sertifikatBalikNamaNoDataAlertModal').modal === 'function') {
            $('#sertifikatBalikNamaNoDataAlertModal').modal('hide');
        }
    }

    function getData() {
        if (!validateFilter()) {
            return;
        }

        hideSertifikatBalikNamaNoDataAlert();

        $('#loadingInfo').show();
        $('#btnPrintBalikNama').prop('disabled', true);

        $.ajax({
            method: 'POST',
            url: getBalikNamaConfig().urlData,
            dataType: 'json',
            timeout: 120000,

            headers: {
                'Accept': 'application/json'
            },

            data: getFilterData(),

            success: function (response) {
                var rows = Array.isArray(response)
                    ? response
                    : (
                        response && Array.isArray(response.data)
                            ? response.data
                            : []
                    );

                if (rows.length === 0) {
                    showSertifikatBalikNamaNoDataAlert(
                        $('#apartemen').is(':checked')
                            ? 'Data Laporan Apartemen tidak ditemukan......!'
                            : 'Data tidak ditemukan......!'
                    );
                }

                /*
                 * Hanya setelah tombol OK berhasil,
                 * laporan lengkap dirender seperti desktop.
                 */
                renderReport(rows);
            },

            error: function (
                xhr,
                textStatus,
                errorThrown
            ) {
                var detail = '';

                if (
                    xhr.responseJSON
                    && xhr.responseJSON.message
                ) {
                    detail = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    detail =
                        String(xhr.responseText)
                            .replace(/<[^>]+>/g, ' ')
                            .replace(/\s+/g, ' ')
                            .trim()
                            .substring(0, 700);
                } else {
                    detail =
                        String(
                            errorThrown
                            || textStatus
                            || ''
                        );
                }

                alert(
                    'Gagal mengambil data. '
                    + detail
                );
            },

            complete: function () {
                $('#loadingInfo').hide();
            }
        });
    }


    /* ==============================================
       RENDER LAPORAN - MENIRU DESKTOP
       ============================================== */

    function renderReport(rows) {
        rows = Array.isArray(rows) ? rows : [];

        var blokAwal =
            String(
                $('#blok_awal').val() || 'A'
            ).toUpperCase();

        var blokAkhir =
            String(
                $('#blok_akhir').val() || 'Z'
            ).toUpperCase();

        var periode =
            formatDate($('#tgl_awal').val())
            + ' s/d '
            + formatDate($('#tgl_akhir').val());

        var ttdMengetahuiNama = String(
            $('#ttd_mengetahui_nama').val()
            || $('#ttdSelectedName').text()
            || '-'
        ).trim();

        if (!ttdMengetahuiNama || ttdMengetahuiNama === 'Memuat...') {
            ttdMengetahuiNama = '-';
        }

        var tanggalTandaTangan = formatTanggalIndonesia(new Date());

        var html = '';

        html += '<div class="report-paper">';

        /* HEADER REPORT — MODEL SAMA DENGAN SERTIPIKAT PECAHAN */
        var first = rows.length > 0 ? rows[0] : {};
        var company = resolveReportCompany(first);

        var sector = String(
            $('#sektorEntry').text() || 'Semua Sektor'
        ).trim();

        var blok = blokAwal + ' s/d ' + blokAkhir;

        html += '<div class="baliknama-report-header">';
        html += '<div class="baliknama-report-company">' + escapeHtml(company) + '</div>';
        html += '<div class="baliknama-report-title">Laporan Daftar Sertifikat Balik Nama</div>';
        html += '<div class="baliknama-report-period">';
        html += 'BLOK: ' + escapeHtml(blok) + '<br>';
        html += 'Tgl. Input Balik Nama: ' + escapeHtml(periode);
        html += '</div>';
        html += '</div>';

        html += '<div class="baliknama-report-subtitle">';
        html += '<span class="baliknama-report-subtitle-label">Sektor/Cluster:</span>';
        html += '<strong class="baliknama-report-sector-value">' + escapeHtml(sector) + '</strong>';
        html += '<span class="baliknama-report-live-badge">Live data</span>';
        html += '</div>';

        /* TABLE */
        html += '<div class="desktop-report-table-wrap">';
        html += '<table class="desktop-report-table">';

        /*
         * V10 ZOOM-STABLE COLGROUP
         * Total = 100%. Karena semua kolom memakai persentase, tabel selalu
         * mengikuti lebar report-paper dan kolom Kantor tidak terdorong keluar.
         */
        html += '<colgroup>';
        html += '<col style="width:52px">';   // No.
        html += '<col style="width:110px">';  // Blok/Nomor
        html += '<col style="width:260px">';  // Nama Pemilik
        html += '<col style="width:180px">';  // Sertipikat Nomor
        html += '<col style="width:130px">';  // Sertipikat Tanggal
        html += '<col style="width:180px">';  // Surat Ukur Nomor
        html += '<col style="width:130px">';  // Surat Ukur Tanggal
        html += '<col style="width:100px">';  // Luas
        html += '<col style="width:140px">';  // Tanggal Input Balik Nama
        html += '<col style="width:130px">';  // Telepon Rumah
        html += '<col style="width:130px">';  // HP
        html += '<col style="width:130px">';  // Kantor
        html += '</colgroup>';

        html += '<thead>';

        /* HEADER BARIS 1 */
        html += '<tr>';

        html +=
            '<th rowspan="2" class="w-no">'
            + 'No.'
            + '</th>';

        html +=
            '<th rowspan="2" class="w-blok">'
            + 'BLOK/<br>NOMOR'
            + '</th>';

        html +=
            '<th rowspan="2" class="w-pemilik">'
            + 'Nama Pemilik'
            + '</th>';

        html +=
            '<th colspan="2">'
            + 'Sertipikat'
            + '</th>';

        html +=
            '<th colspan="3">'
            + 'Surat Ukur'
            + '</th>';

        html +=
            '<th rowspan="2" class="w-input">'
            + 'Tanggal<br>Input<br>Balik Nama'
            + '</th>';

        html +=
            '<th colspan="3">'
            + 'Telepon'
            + '</th>';

        html += '</tr>';

        /* HEADER BARIS 2 */
        html += '<tr>';

        html +=
            '<th class="w-sertifikat">'
            + 'Nomor'
            + '</th>';

        html +=
            '<th class="w-tanggal">'
            + 'Tanggal'
            + '</th>';

        html +=
            '<th class="w-su">'
            + 'Nomor'
            + '</th>';

        html +=
            '<th class="w-tanggal">'
            + 'Tanggal'
            + '</th>';

        html +=
            '<th class="w-luas">'
            + 'Luas<br>(M2)'
            + '</th>';

        html +=
            '<th class="w-telp">'
            + 'Rumah'
            + '</th>';

        html +=
            '<th class="w-telp">'
            + 'HP'
            + '</th>';

        html +=
            '<th class="w-telp">'
            + 'Kantor'
            + '</th>';

        html += '</tr>';

        html += '</thead>';

        /* BODY */
        html += '<tbody>';

        $.each(rows, function (index, item) {
            item = item || {};

            var blokNomor =
                pickValue(
                    item,
                    ['BLOK_NOMOR', 'blok_nomor']
                );

            var pemilik =
                pickValue(
                    item,
                    ['NASABAH_NAMA', 'nasabah_nama']
                );

            var noSertipikat =
                pickValue(
                    item,
                    ['NO_SERTIPIKAT', 'no_sertipikat']
                );

            var tglSertipikat =
                pickValue(
                    item,
                    ['TGL_SERTIPIKAT', 'tgl_sertipikat']
                );

            var su =
                pickValue(
                    item,
                    ['SU_PISAH', 'su_pisah']
                );

            var tglSu =
                pickValue(
                    item,
                    ['TGL_SU_PISAH', 'tgl_su_pisah']
                );

            var luasSu =
                pickValue(
                    item,
                    ['LUAS_SUP', 'luas_sup']
                );

            var tglInput =
                pickValue(
                    item,
                    ['TGL_INPUT_BLK_NM', 'tgl_input_blk_nm']
                );

            var telpRumah =
                pickValue(
                    item,
                    ['TELP_RMH', 'telp_rmh']
                );

            var hp =
                pickValue(
                    item,
                    ['NO_HP', 'no_hp']
                );

            var telpKantor =
                pickValue(
                    item,
                    ['TELP_KTR', 'telp_ktr']
                );

            html += '<tr>';

            html +=
                '<td class="center">'
                + (index + 1)
                + '</td>';

            html +=
                '<td class="center">'
                + escapeHtml(
                    valueOrDash(blokNomor)
                )
                + '</td>';

            html +=
                '<td>'
                + escapeHtml(
                    valueOrDash(pemilik)
                )
                + '</td>';

            html +=
                '<td>'
                + escapeHtml(
                    valueOrDash(noSertipikat)
                )
                + '</td>';

            html +=
                '<td class="center">'
                + escapeHtml(
                    formatDate(tglSertipikat)
                )
                + '</td>';

            html +=
                '<td>'
                + escapeHtml(
                    valueOrDash(su)
                )
                + '</td>';

            html +=
                '<td class="center">'
                + escapeHtml(
                    formatDate(tglSu)
                )
                + '</td>';

            html +=
                '<td class="right">'
                + formatLuas(luasSu)
                + '</td>';

            html +=
                '<td class="center">'
                + escapeHtml(
                    formatDate(tglInput)
                )
                + '</td>';

            html +=
                '<td>'
                + escapeHtml(
                    valueOrDash(telpRumah)
                )
                + '</td>';

            html +=
                '<td>'
                + escapeHtml(
                    valueOrDash(hp)
                )
                + '</td>';

            html +=
                '<td>'
                + escapeHtml(
                    valueOrDash(telpKantor)
                )
                + '</td>';

            html += '</tr>';
        });

        /*
         * Kalau query berhasil tetapi tidak ada data,
         * header laporan dan header tabel tetap tampil,
         * sama seperti report desktop.
         */
        if (!rows.length) {
            html += '<tr class="empty-data-row">';
            html += '<td colspan="12">&nbsp;</td>';
            html += '</tr>';
        }

        html += '</tbody>';
        html += '</table>';
        html += '</div>';

        /* TANDA TANGAN — mengikuti report desktop */
        html += '<div class="report-signature-section">';

        html += '<div class="report-signature-date">';
        html += 'Jakarta, ' + escapeHtml(tanggalTandaTangan);
        html += '</div>';

        html += '<div class="report-signature-primary">';

        html += '<div class="report-signature-box">';
        html += '<div class="report-signature-role">Menyerahkan,</div>';
        html += '<div class="report-signature-space"></div>';
        html += '<div class="report-signature-line">&nbsp;</div>';
        html += '</div>';

        html += '<div class="report-signature-box">';
        html += '<div class="report-signature-role">Yang menerima,</div>';
        html += '<div class="report-signature-space"></div>';
        html += '<div class="report-signature-line">&nbsp;</div>';
        html += '</div>';

        html += '</div>';

        html += '<div class="report-signature-approval">';
        html += '<div class="report-signature-role">Mengetahui,</div>';
        html += '<div class="report-signature-space"></div>';
        html += '<div class="report-signature-name">( ' +
            escapeHtml(ttdMengetahuiNama.toUpperCase()) +
            ' )</div>';
        html += '</div>';

        html += '</div>';
        html += '</div>';

        $('#mainDisplay').html(html);
        $('#btnPrintBalikNama').prop('disabled', false);
    }
</script>

@endsection