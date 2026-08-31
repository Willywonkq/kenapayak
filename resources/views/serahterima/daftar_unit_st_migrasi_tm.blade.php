@extends('layouts.template')

@section('content')
<style>
    .dst-page {
        --dst-primary: #2563eb;
        --dst-primary-dark: #1d4ed8;
        --dst-primary-soft: #eff6ff;
        --dst-success: #15803d;
        --dst-success-soft: #f0fdf4;
        --dst-danger: #dc2626;
        --dst-danger-soft: #fef2f2;
        --dst-warning: #b45309;
        --dst-warning-soft: #fffbeb;
        --dst-text: #172033;
        --dst-muted: #687386;
        --dst-border: #dce3ec;
        --dst-surface: #ffffff;
        --dst-background: #f4f7fb;
        color: var(--dst-text);
        font-family: Inter, "Segoe UI", Arial, sans-serif;
        font-size: 13px;
    }

    .dst-page,
    .dst-page * {
        box-sizing: border-box;
    }

    .dst-filter-panel {
        margin-bottom: 16px;
        overflow: hidden;
        border: 1px solid var(--dst-border);
        border-radius: 14px;
        background: var(--dst-surface);
        box-shadow: 0 8px 24px rgba(30, 50, 80, .07);
    }

    .dst-filter-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 13px 18px;
        border-bottom: 1px solid #e8edf3;
        background: linear-gradient(135deg, #ffffff 0%, #f7faff 100%);
    }

    .dst-filter-title-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .dst-filter-icon {
        display: inline-flex;
        width: 30px;
        height: 30px;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: var(--dst-primary-soft);
        color: var(--dst-primary);
    }

    .dst-filter-icon svg,
    .dst-button svg {
        width: 17px;
        height: 17px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 1.8;
    }

    .dst-filter-title {
        margin: 0;
        color: #172033;
        font-size: 14px;
        font-weight: 700;
    }

    .dst-filter-subtitle {
        margin: 2px 0 0;
        color: var(--dst-muted);
        font-size: 11px;
    }

    .dst-filter-grid {
        display: grid;
        grid-template-columns: minmax(430px, 1.35fr) minmax(295px, .9fr) minmax(50px, .15fr) 112px;
        gap: 12px 24px;
        align-items: start;
        padding: 18px;
    }

    /* Menjaga posisi tombol View dan Print tetap di kanan pada zoom berbeda */
    .dst-filter-grid {
        grid-template-columns: minmax(420px, 1.35fr) minmax(300px, .95fr) 40px 120px;
        min-width: 980px;
    }

    .dst-actions {
        grid-column: 4;
        grid-row: 1 / span 2;
        justify-content: center;
    }

    .dst-filter-panel {
        overflow-x: auto;
    }

    .dst-filter-column {
        display: grid;
        gap: 12px;
    }

    .dst-filter-row {
        display: grid;
        grid-template-columns: 112px minmax(130px, 1fr);
        gap: 10px;
        align-items: center;
        min-height: 36px;
    }

    .dst-filter-label {
        color: #39455a;
        font-weight: 600;
        white-space: nowrap;
    }

    .dst-date-range,
    .dst-block-range {
        display: grid;
        grid-template-columns: minmax(122px, 1fr) 28px minmax(122px, 1fr);
        gap: 7px;
        align-items: center;
    }

    .dst-block-range {
        grid-template-columns: minmax(90px, 1fr) 28px minmax(90px, 1fr);
    }

    .dst-range-separator {
        color: #8b95a5;
        text-align: center;
        font-size: 11px;
        font-weight: 600;
    }

    .dst-control {
        width: 100%;
        height: 36px;
        padding: 6px 10px;
        border: 1px solid #cfd8e5;
        border-radius: 8px;
        outline: 0;
        background: #fff;
        color: var(--dst-text);
        font: inherit;
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .dst-control:hover {
        border-color: #aab8ca;
    }

    .dst-control:focus {
        border-color: var(--dst-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    .dst-control:disabled {
        border-color: #e4e9f0;
        background: #f3f5f8;
        color: #99a2b0;
        cursor: not-allowed;
    }

    .dst-lookup-control {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 42px;
        width: 100%;
    }

    .dst-lookup-display {
        overflow: hidden;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        background: #fff;
        text-align: left;
        text-overflow: ellipsis;
        white-space: nowrap;
        cursor: pointer;
    }

    .dst-lookup-display:focus {
        position: relative;
        z-index: 1;
    }

    .dst-lookup-button {
        display: inline-flex;
        width: 42px;
        height: 36px;
        align-items: center;
        justify-content: center;
        border: 1px solid #cfd8e5;
        border-left: 0;
        border-radius: 0 8px 8px 0;
        background: #f8fafc;
        color: var(--dst-primary);
        cursor: pointer;
        transition: border-color .18s ease, background .18s ease, color .18s ease;
    }

    .dst-lookup-button:hover {
        border-color: var(--dst-primary);
        background: var(--dst-primary-soft);
        color: var(--dst-primary-dark);
    }

    .dst-lookup-button:focus {
        position: relative;
        z-index: 1;
        border-color: var(--dst-primary);
        outline: 0;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    .dst-lookup-button:disabled {
        color: #98a2b3;
        cursor: wait;
        opacity: .7;
    }

    .dst-lookup-button.is-loading svg {
        animation: dst-spin .7s linear infinite;
    }

    .dst-lookup-button svg,
    .dst-modal-close svg,
    .dst-modal-search-icon svg {
        width: 18px;
        height: 18px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 2;
    }

    .dst-radio {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        color: #465268;
        font-weight: 500;
        white-space: nowrap;
        cursor: pointer;
    }

    .dst-radio input {
        margin: 0;
        accent-color: var(--dst-primary);
    }

    .dst-status-box {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        min-height: 36px;
        padding: 3px;
        border: 1px solid #cfd8e5;
        border-radius: 9px;
        background: #f5f7fa;
    }

    .dst-status-box .dst-radio {
        position: relative;
        justify-content: center;
        min-width: 66px;
        padding: 0;
        border-radius: 6px;
    }

    .dst-status-box .dst-radio > span {
        display: inline-flex;
        width: 100%;
        min-height: 30px;
        align-items: center;
        justify-content: center;
        padding: 6px 9px;
        border: 1px solid transparent;
        border-radius: 6px;
        transition:
            background .18s ease,
            border-color .18s ease,
            color .18s ease,
            box-shadow .18s ease;
    }

    .dst-status-box .dst-radio:hover > span {
        background: #e9eef7;
    }

    .dst-status-box input:focus-visible + span {
        outline: 2px solid rgba(37, 99, 235, .35);
        outline-offset: 1px;
    }

    .dst-status-box input[value="A"]:checked + span {
        border-color: #86efac;
        background: #dcfce7;
        color: #15803d;
        box-shadow: 0 1px 4px rgba(21, 128, 61, .16);
    }

    .dst-status-box input[value="T"]:checked + span {
        border-color: #fca5a5;
        background: #fee2e2;
        color: #b91c1c;
        box-shadow: 0 1px 4px rgba(185, 28, 28, .14);
    }

    .dst-status-box input[value="*"]:checked + span {
        border-color: #93c5fd;
        background: #dbeafe;
        color: #1d4ed8;
        box-shadow: 0 1px 4px rgba(29, 78, 216, .15);
    }

    .dst-status-box input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .dst-actions {
        display: flex;
        flex-direction: column;
        gap: 9px;
    }

    .dst-button {
        display: inline-flex;
        min-width: 104px;
        height: 36px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 6px 14px;
        border: 1px solid transparent;
        border-radius: 8px;
        font: inherit;
        font-weight: 600;
        cursor: pointer;
        transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
    }

    .dst-button:hover {
        transform: translateY(-1px);
    }

    .dst-button:active {
        transform: translateY(0);
    }

    .dst-button:disabled {
        cursor: wait;
        opacity: .65;
        transform: none;
    }

    .dst-primary-button {
        background: var(--dst-primary);
        color: #fff;
        box-shadow: 0 5px 12px rgba(37, 99, 235, .22);
    }

    .dst-primary-button:hover {
        background: var(--dst-primary-dark);
    }

    .dst-export-button {
        border-color: #bbdec8;
        background: #f2fbf5;
        color: var(--dst-success);
    }

    .dst-export-button:hover {
        background: #e7f7ec;
    }

    body.dst-modal-open {
        overflow: hidden;
    }

    .dst-modal {
        position: fixed;
        inset: 0;
        z-index: 1055;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(15, 23, 42, .56);
        backdrop-filter: blur(2px);
    }

    .dst-modal.is-open {
        display: flex;
    }

    .dst-modal-dialog {
        width: min(760px, 100%);
        max-height: min(720px, calc(100vh - 40px));
        overflow: hidden;
        border: 1px solid #dbe3ed;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
        animation: dst-modal-in .18s ease-out;
    }

    @keyframes dst-modal-in {
        from {
            transform: translateY(8px) scale(.985);
            opacity: 0;
        }

        to {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }

    .dst-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 15px 18px;
        border-bottom: 1px solid #e7ecf2;
    }

    .dst-modal-title {
        margin: 0;
        color: #172033;
        font-size: 15px;
        font-weight: 750;
    }

    .dst-modal-close {
        display: inline-flex;
        width: 34px;
        height: 34px;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: #667085;
        cursor: pointer;
    }

    .dst-modal-close:hover {
        background: #f1f4f8;
        color: #172033;
    }

    .dst-modal-body {
        padding: 16px 18px 18px;
    }

    .dst-modal-search {
        position: relative;
        margin-bottom: 12px;
    }

    .dst-modal-search-icon {
        position: absolute;
        top: 50%;
        left: 12px;
        display: inline-flex;
        color: #8390a3;
        pointer-events: none;
        transform: translateY(-50%);
    }

    .dst-modal-search .dst-control {
        padding-left: 39px;
    }

    .dst-modal-table-wrap {
        max-height: min(500px, calc(100vh - 190px));
        overflow: auto;
        border: 1px solid var(--dst-border);
        border-radius: 9px;
    }

    .dst-modal-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 12px;
    }

    .dst-modal-table th,
    .dst-modal-table td {
        height: 40px;
        padding: 8px 12px;
        border-right: 1px solid #e5eaf1;
        border-bottom: 1px solid #e5eaf1;
        text-align: left;
        vertical-align: middle;
    }

    .dst-modal-table th:last-child,
    .dst-modal-table td:last-child {
        border-right: 0;
    }

    .dst-modal-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .dst-modal-table th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: #f7f9fc;
        color: #475467;
        font-size: 11px;
        font-weight: 700;
    }

    .dst-sector-row {
        cursor: pointer;
    }

    .dst-sector-row:nth-child(even) td {
        background: #fbfcfe;
    }

    .dst-sector-row:hover td,
    .dst-sector-row:focus td {
        background: var(--dst-primary-soft);
    }

    .dst-sector-row.is-selected td {
        background: #eaf2ff;
        color: #1746a2;
        font-weight: 650;
    }

    .dst-modal-state {
        padding: 38px 18px !important;
        color: var(--dst-muted);
        text-align: center !important;
    }

    .dst-modal-error {
        color: var(--dst-danger);
    }

    .dst-loading {
        display: none;
        margin: 0 0 12px;
        padding: 10px 13px;
        border: 1px solid #bfdbfe;
        border-radius: 9px;
        background: var(--dst-primary-soft);
        color: #1e4ea8;
    }

    .dst-loading-content {
        display: flex;
        align-items: center;
        gap: 9px;
    }


    /*
     * V2 Loading State Fix
     * Saat request berjalan, panel initial/report disembunyikan seluruhnya.
     * Dengan begitu icon fa-table + teks "Silakan pilih filter..." tidak
     * tetap terlihat di bawah bar "Memuat data...".
     */
    .dst-report-shell.dst-report-shell--loading {
        display: none !important;
    }

    .dst-spinner {
        width: 16px;
        height: 16px;
        flex: 0 0 auto;
        border: 2px solid #bfdbfe;
        border-top-color: var(--dst-primary);
        border-radius: 50%;
        animation: dst-spin .7s linear infinite;
    }

    @keyframes dst-spin {
        to {
            transform: rotate(360deg);
        }
    }

    .dst-report-shell {
        min-height: 420px;
        padding: 26px;
        overflow: auto;
        border: 1px solid var(--dst-border);
        border-radius: 14px;
        background:
            radial-gradient(circle at top right, rgba(37, 99, 235, .06), transparent 32%),
            var(--dst-background);
    }

    /*
     * .dst-paper kini hanya pembungkus layout polos. Kartu laporan yang
     * sebenarnya (border, radius, shadow, garis aksen atas) disediakan oleh
     * .report-wrapper — sama seperti fitur-fitur lain — supaya tidak ada
     * dua lapis kartu yang bertumpuk.
     */
    .dst-paper {
        width: 100%;
        margin: 0 auto;
    }

    .dst-report-shell.dst-report-shell--initial {
        min-height: 0;
        padding: 0;
        overflow: visible;
        border: 0;
        background: transparent;
    }

    .dst-paper.dst-paper--initial {
        width: 100%;
        min-width: 0;
        min-height: 120px;
        border: 1px dashed #cbd5e1;
        background: linear-gradient(180deg, #fcfdff 0%, #f8fafc 100%);
        box-shadow: none;
    }

    .dst-center {
        text-align: center;
    }

    .dst-number-cell {
        color: #7a8494 !important;
        font-variant-numeric: tabular-nums;
    }

    .dst-status-badge {
        display: inline-flex;
        min-width: 50px;
        align-items: center;
        justify-content: center;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
    }

    .dst-status-active {
        background: var(--dst-success-soft);
        color: var(--dst-success);
    }

    .dst-status-cancelled {
        background: var(--dst-danger-soft);
        color: var(--dst-danger);
    }

    .dst-cancelled-unit {
        color: var(--dst-danger);
        font-weight: 700;
    }

    .dst-initial,
    .dst-error {
        padding: 75px 22px !important;
        color: var(--dst-muted);
        text-align: center;
        white-space: normal !important;
    }

    .dst-initial {
        display: flex;
        min-height: 120px;
        padding: 24px !important;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .dst-initial-icon {
        display: inline-flex;
        width: 52px;
        height: 52px;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 16px;
        background: #dbeafe;
        color: #2563eb;
        box-shadow: none;
        font-size: 20px;
    }

    .dst-initial-icon i {
        line-height: 1;
    }

    .dst-initial-text {
        color: var(--dst-muted);
        font-size: 13px;
    }

    .dst-error {
        color: var(--dst-danger);
    }

    @media (max-width: 900px) {
        .dst-filter-grid {
            grid-template-columns: 1fr;
        }

        .dst-filter-grid > .dst-spacer {
            display: none;
        }

        .dst-actions {
            grid-column: auto;
            flex-direction: row;
            justify-content: flex-end;
        }
    }

    @media (max-width: 760px) {
        .dst-filter-header {
            align-items: flex-start;
        }

        .dst-filter-grid {
            grid-template-columns: 1fr;
            padding: 14px;
        }

        .dst-filter-row {
            grid-template-columns: 1fr;
            gap: 6px;
        }

        .dst-date-range {
            grid-template-columns: minmax(110px, 1fr) 25px minmax(110px, 1fr);
        }

        .dst-actions {
            grid-column: auto;
        }

        .dst-actions .dst-button {
            flex: 1;
        }

        .dst-report-shell {
            padding: 12px;
        }
    }

    @media print {
        body * {
            visibility: hidden;
        }

        .dst-report-shell,
        .dst-report-shell * {
            visibility: visible;
        }

        .dst-report-shell {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .dst-page-hero {
            display:none!important;
        }
    }

    @media print {
        .dst-filter-panel,
        .dst-loading {
            display: none !important;
        }

        .dst-report-shell {
            padding: 0;
            overflow: visible;
            border: 0;
            background: #fff;
        }

        .dst-paper {
            min-width: 0;
            overflow: visible;
            border: 0;
            box-shadow: none;
        }

        .report-wrapper {
            padding: 0 !important;
            border: 0 !important;
            box-shadow: none !important;
            background: #fff !important;
        }

        .report-wrapper::before {
            display: none !important;
        }

        .report-table-container {
            overflow: visible;
        }

        .report-table th {
            position: static;
        }
    }


    /* Hero judul mengikuti Daftar Serah Terima, tanpa ilustrasi rumah. */
    .dst-page-hero {

    display: flex;
    min-height: 88px;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 16px;
    padding: 18px 22px;
    overflow: hidden;
    border: 1px solid rgba(15, 35, 65, 0.08);
    border-radius: 18px;
    background: linear-gradient(90deg, #ffffff 0%, #ffffff 62%, #f8fafc 100%);
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);

    }

    .dst-page-heading {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 15px;
    }

    .dst-page-heading-icon {
        display: inline-flex;
        width: 58px;
        height: 58px;
        flex: 0 0 58px;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25);
        font-size: 25px;
    }

    .dst-page-heading h1 {
        margin: 0;
        color: #0f172a;
        font-size: clamp(19px, 1.65vw, 27px);
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -0.35px;
    }

    .dst-page-unit-badge {
        display: inline-flex;
        min-height: 36px;
        align-items: center;
        gap: 8px;
        margin-left: auto;
        padding: 8px 12px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1e40af;
        font-size: 12px;
        white-space: nowrap;
    }

    @media (max-width: 680px) {
        .dst-page-hero { padding: 16px; }
        .dst-page-heading-icon {
            width: 50px;
            height: 50px;
            flex-basis: 50px;
        }
        .dst-page-unit-badge { display: none; }
    }



    /* =========================================================
       HEADER ICON MATCH — DAFTAR SERTIPIKAT PECAHAN
       Hanya ikon kiri atas yang disamakan. Struktur/fungsi lain tetap.
       ========================================================= */
    .dst-page-heading-icon.sertipikat-style-heading-icon {

        
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

    @media (max-width: 680px) {
        .dst-page-heading-icon.sertipikat-style-heading-icon {
            width: 34px !important;
            height: 34px !important;
            flex-basis: 34px !important;
        }
    }



    /* FONT STANDARD — MATCH DAFTAR SURAT PESANAN */
    .dst-page,
    .dst-page input,
    .dst-page select,
    .dst-page button,
    .dst-page textarea,
    .dst-page label,
    .dst-page table,
    .dst-page td,
    .dst-page .dst-filter-subtitle {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
    }

    .dst-page .dst-page-heading h1,
    .dst-page .dst-filter-title,
    .dst-page .dst-filter-label,
    .dst-page .dst-modal-title,
    .dst-page th {
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
    }

    /* =========================================================
       TAMPILAN HASIL LAPORAN — MENGIKUTI DAFTAR SERTIFIKAT PECAHAN
       Struktur: 3 grid (header → subtitle → tabel).
       Catatan: HANYA tampilan/gaya. Kolom, urutan kolom, dan isi
       data laporan tidak diubah sama sekali.
       ========================================================= */
    .dst-page .report-wrapper {
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

    .dst-page .report-wrapper::before {
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
    .dst-page .report-header {
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

    .dst-page .report-company {
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 850;
        letter-spacing: 0.04em;
        line-height: 1.45;
    }

    .dst-page .report-title {
        margin: 0 !important;
        color: #172033 !important;
        text-align: center !important;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
        font-size: 18px !important;
        font-weight: 950 !important;
        letter-spacing: -0.02em !important;
        line-height: 1.25;
    }

    .dst-page .report-period {
        color: #475467;
        text-align: right;
        font-size: 10.5px;
        font-weight: 650;
        line-height: 1.55;
    }

    /* --- GRID 2 : BARIS SEKTOR/CLUSTER --- */
    .dst-page .report-subtitle {
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

    .dst-page .report-subtitle-label {
        justify-self: start;
        color: #667085;
        white-space: nowrap;
    }

    .dst-page .report-subtitle-value {
        justify-self: center;
        color: #344054;
        font-weight: 850;
        text-align: center;
    }

    .dst-page .report-subtitle strong {
        color: #344054;
        font-weight: 850;
    }

    .dst-page .report-live-badge {
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

    .dst-page .report-live-badge::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.10);
    }

    /* --- GRID 3 : TABEL LAPORAN --- */
    .dst-page .report-table-container {
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

    .dst-page .report-table-container::-webkit-scrollbar {
        width: 11px;
        height: 13px;
    }

    .dst-page .report-table-container::-webkit-scrollbar-track {
        border-radius: 999px;
        background: #eff3f7;
    }

    .dst-page .report-table-container::-webkit-scrollbar-thumb {
        border: 2px solid #eff3f7;
        border-radius: 999px;
        background: #93c5fd;
    }

    .dst-page .report-table-container::-webkit-scrollbar-thumb:hover {
        background: #60a5fa;
    }

    .dst-page .report-table {
        margin-bottom: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        background: #ffffff !important;
        color: #344054 !important;
        font-size: 10.5px !important;
    }

    .dst-page .report-table th {
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

    .dst-page .report-table td {
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

    .dst-page .report-table tbody tr:nth-child(even) td {
        background: #fbfcfe !important;
    }

    .dst-page .report-table tbody tr:hover td {
        background: #f0f7ff !important;
    }

    .dst-page .report-table tbody tr:hover td:first-child {
        color: #1d4ed8 !important;
        box-shadow: inset 4px 0 0 #2563eb !important;
    }

    .dst-page .report-table .empty-row {
        height: 130px !important;
        color: #64748b !important;
        background: #ffffff !important;
        font-style: normal !important;
        text-align: center !important;
    }

    @media (max-width: 767.98px) {
        .dst-page .report-header {
            grid-template-columns: minmax(0, 1fr);
            gap: 8px;
            text-align: center;
        }

        .dst-page .report-period {
            text-align: center;
        }

        .dst-page .report-subtitle {
            grid-template-columns: minmax(0, 1fr);
            justify-items: center;
            text-align: center !important;
        }

        .dst-page .report-subtitle-label,
        .dst-page .report-live-badge {
            justify-self: center;
        }
    }

    /* =========================================================
       NOTIFIKASI TOAST — PERCOBAAN DI FITUR INI DULU
       Muncul ketika hasil View tidak memuat data.
       Bentuk kartu, radius, bayangan, dan palet biru mengikuti
       panel-panel yang sudah ada agar tetap satu tema.
       ========================================================= */
    .dst-page .sp-toast-stack {
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

    .dst-page .sp-toast {
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

    .dst-page .sp-toast::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: linear-gradient(180deg, #38bdf8 0%, #2563eb 55%, #1d4ed8 100%);
    }

    .dst-page .sp-toast.is-visible {
        opacity: 1;
        transform: none;
    }

    .dst-page .sp-toast.is-leaving {
        opacity: 0;
        transform: translateY(-8px) scale(0.98);
    }

    .dst-page .sp-toast-icon {
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

    .dst-page .sp-toast-text {
        min-width: 0;
        color: #172033;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 13.5px;
        font-weight: 800;
        letter-spacing: -0.01em;
        line-height: 1.35;
    }

    .dst-page .sp-toast-close {
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

    .dst-page .sp-toast-close:hover {
        color: #1d4ed8;
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .dst-page .sp-toast-progress {
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
        .dst-page .sp-toast {
            transition: none;
        }

        .dst-page .sp-toast-progress {
            animation: none;
        }
    }

    @media (max-width: 767.98px) {
        .dst-page .sp-toast-stack {
            top: 12px;
            right: 12px;
            left: 12px;
            width: auto;
        }
    }


</style>

<div class="dst-page">
    <input
        type="hidden"
        id="perusahaan"
        value="{{ session('kd_unit') ?? 'DTSA' }}"
    >
    <input
        type="hidden"
        id="nama_perusahaan_session"
        value="{{ $namaPerusahaan ?? $nama_perusahaan ?? $namaPt ?? $nama_pt ?? session('nama_pt') ?? session('nama_perusahaan') ?? session('nama_unit') ?? session('nama_lokasi') ?? session('deskripsi_lokasi') ?? session('lokasi') ?? '' }}"
    >

    <section class="dst-page-hero">
        <div class="dst-page-heading">
            <div class="dst-page-heading-icon sertipikat-style-heading-icon" aria-hidden="true">◈</div>
            <h1>Daftar Unit ST dan Migrasi TM</h1>
        </div>

        <div class="dst-page-unit-badge" title="Unit aktif">
            <i class="fas fa-building"></i>
            <span>Unit: <strong>{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}</strong></span>
        </div>
    </section>

    <div
        id="sektor-modal"
        class="dst-modal"
        aria-hidden="true"
        aria-labelledby="sektor-modal-title"
    >
        <div class="dst-modal-dialog" role="dialog" aria-modal="true">
            <div class="dst-modal-header">
                <h3 id="sektor-modal-title" class="dst-modal-title">
                    Pilih Sektor/Cluster
                </h3>
                <button
                    type="button"
                    id="sektor-modal-close"
                    class="dst-modal-close"
                    aria-label="Tutup daftar Sektor/Cluster"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="dst-modal-body">
                <div class="dst-modal-search">
                    <span class="dst-modal-search-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m20 20-4-4"></path>
                        </svg>
                    </span>
                    <input
                        type="search"
                        id="sektor-search"
                        class="dst-control"
                        placeholder="Cari kode atau nama sektor..."
                        autocomplete="off"
                    >
                </div>
                <div class="dst-modal-table-wrap">
                    <table class="dst-modal-table">
                        <thead>
                            <tr>
                                <th style="width: 24%;">Kode Sektor</th>
                                <th>Deskripsi</th>
                                <th style="width: 22%;">Perusahaan</th>
                            </tr>
                        </thead>
                        <tbody id="sektor-table-body">
                            <tr>
                                <td colspan="3" class="dst-modal-state">
                                    Tekan ikon pencarian untuk memuat data.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="dst-filter-panel">
        <div class="dst-filter-header">
            <div class="dst-filter-title-wrap">
                <span class="dst-filter-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 5h16M7 12h10M10 19h4"></path>
                    </svg>
                </span>
                <div>
                    <h2 class="dst-filter-title">Filter Laporan</h2>
                    <p class="dst-filter-subtitle">Daftar Serah Terima</p>
                </div>
            </div>
        </div>

        <div class="dst-filter-grid">
            <div class="dst-filter-column">
                <div class="dst-filter-row">
                    <label for="tgl_awal" class="dst-filter-label">Tgl. Surat ST:</label>
                    <div class="dst-date-range">
                        <input
                            type="date"
                            id="tgl_awal"
                            class="dst-control"
                            value="{{ now()->startOfYear()->format('Y-m-d') }}"
                        >
                        <span class="dst-range-separator">s.d</span>
                        <input
                            type="date"
                            id="tgl_akhir"
                            class="dst-control"
                            value="{{ now()->format('Y-m-d') }}"
                        >
                    </div>
                </div>

                <div class="dst-filter-row">
                    <label for="tgl_st1" class="dst-filter-label">Tgl. Realisasi ST:</label>
                    <div class="dst-date-range">
                        <input
                            type="date"
                            id="tgl_st1"
                            class="dst-control"
                            value="{{ now()->startOfYear()->format('Y-m-d') }}"
                        >
                        <span class="dst-range-separator">s.d</span>
                        <input
                            type="date"
                            id="tgl_st2"
                            class="dst-control"
                            value="{{ now()->format('Y-m-d') }}"
                        >
                    </div>
                </div>

                <div class="dst-filter-row">
                    <label for="sektor-display" class="dst-filter-label">Sektor/Cluster:</label>
                    <div class="dst-lookup-control">
                        <input type="hidden" id="sektor" value="*">
                        <input
                            type="text"
                            id="sektor-display"
                            class="dst-control dst-lookup-display"
                            value="Semua Sektor"
                            readonly
                            aria-haspopup="dialog"
                            aria-controls="sektor-modal"
                        >
                        <button
                            type="button"
                            id="btn-sektor-lookup"
                            class="dst-lookup-button"
                            title="Cari Sektor/Cluster"
                            aria-label="Cari Sektor/Cluster"
                            aria-haspopup="dialog"
                            aria-controls="sektor-modal"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-4-4"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="dst-filter-column">
                <div class="dst-filter-row">
                    <label for="blok_awal" class="dst-filter-label">Blok:</label>
                    <div class="dst-block-range">
                        <input
                            type="text"
                            id="blok_awal"
                            class="dst-control"
                            value="A"
                            maxlength="30"
                            autocomplete="off"
                        >
                        <span class="dst-range-separator">s.d</span>
                        <input
                            type="text"
                            id="blok_akhir"
                            class="dst-control"
                            value="ZZ"
                            maxlength="30"
                            autocomplete="off"
                        >
                    </div>
                </div>

                <div class="dst-filter-row">
                    <span class="dst-filter-label">Sts BAST:</span>
                    <div class="dst-status-box">
                        <label class="dst-radio">
                            <input type="radio" name="st_aktif" value="A" checked>
                            <span>Aktif</span>
                        </label>
                        <label class="dst-radio">
                            <input type="radio" name="st_aktif" value="T">
                            <span>Batal</span>
                        </label>
                        <label class="dst-radio">
                            <input type="radio" name="st_aktif" value="*">
                            <span>Semua</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="dst-spacer" aria-hidden="true"></div>

            <div class="dst-actions">
                <button type="button" id="btn-ok" class="dst-button dst-primary-button">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
                        <circle cx="12" cy="12" r="2.5"></circle>
                    </svg>
                    <span>View</span>
                </button>
                <button
                    type="button"
                    id="btn-print"
                    class="dst-button dst-export-button"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6 9V2h12v7"></path>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <span>Print</span>
                </button>
            </div>
        </div>
    </div>

    <div id="loading-info" class="dst-loading" role="status" aria-live="polite">
        <div class="dst-loading-content">
            <span class="dst-spinner" aria-hidden="true"></span>
            <span>Memuat data daftar serah terima...</span>
        </div>
    </div>

    <div class="dst-report-shell dst-report-shell--initial">
        <div id="main-display" class="dst-paper dst-paper--initial" aria-live="polite">
            <div class="dst-initial">
                <span class="dst-initial-icon" aria-hidden="true">
                    <i class="fas fa-table"></i>
                </span>
                <span class="dst-initial-text">Silakan pilih filter lalu klik <strong>View</strong>.</span>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const baseUrl = "{{ url()->current() }}";
    const csrfToken = "{{ csrf_token() }}";

    const perusahaan = document.getElementById('perusahaan');
    const namaPerusahaanSession = document.getElementById('nama_perusahaan_session');
    const sektor = document.getElementById('sektor');
    const sektorDisplay = document.getElementById('sektor-display');
    const sektorLookupButton = document.getElementById('btn-sektor-lookup');
    const sektorModal = document.getElementById('sektor-modal');
    const sektorModalClose = document.getElementById('sektor-modal-close');
    const sektorSearch = document.getElementById('sektor-search');
    const sektorTableBody = document.getElementById('sektor-table-body');
    const mainDisplay = document.getElementById('main-display');
    const loadingInfo = document.getElementById('loading-info');
    const okButton = document.getElementById('btn-ok');
    const printButton = document.getElementById('btn-print');
    let sektorLastFocus = null;

    /* Label sektor bersih (tanpa kode) untuk grid 2 hasil laporan. */
    let sektorLabel = 'Semua Sektor';

    okButton.addEventListener('click', getSummary);
    printButton.addEventListener('click', printReport);
    sektorLookupButton.addEventListener('click', openSektorModal);
    sektorDisplay.addEventListener('click', openSektorModal);
    sektorModalClose.addEventListener('click', closeSektorModal);
    sektorSearch.addEventListener('input', filterSektorRows);
    sektorModal.addEventListener('click', function (event) {
        if (event.target === sektorModal) {
            closeSektorModal();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && sektorModal.classList.contains('is-open')) {
            closeSektorModal();
        }
    });

    async function openSektorModal() {
        if (!perusahaan.value) {
            alert('Kode perusahaan/unit tidak tersedia pada session.');
            return;
        }

        sektorLastFocus = document.activeElement;
        sektorModal.classList.add('is-open');
        sektorModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('dst-modal-open');
        sektorSearch.value = '';
        renderSektorState('Memuat daftar Sektor/Cluster...', false);
        setSektorLookupLoading(true);

        try {
            const response = await postForm(baseUrl + '/get_sektor', {
                _token: csrfToken,
                perusahaan: perusahaan.value
            });

            const rows = Array.isArray(response)
                ? response
                : (response && Array.isArray(response.data) ? response.data : []);

            renderSektorRows(rows);
        } catch (error) {
            console.error(error);
            renderSektorState(error.message || 'Gagal mengambil data sektor.', true);
        } finally {
            setSektorLookupLoading(false);
            window.setTimeout(function () {
                sektorSearch.focus();
            }, 0);
        }
    }

    function closeSektorModal() {
        sektorModal.classList.remove('is-open');
        sektorModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('dst-modal-open');

        if (sektorLastFocus && typeof sektorLastFocus.focus === 'function') {
            sektorLastFocus.focus();
        }
    }

    function setSektorLookupLoading(isLoading) {
        sektorLookupButton.disabled = isLoading;
        sektorLookupButton.classList.toggle('is-loading', isLoading);
        sektorLookupButton.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }

    function renderSektorState(message, isError) {
        sektorTableBody.innerHTML = '';

        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 3;
        cell.className = 'dst-modal-state' + (isError ? ' dst-modal-error' : '');
        cell.textContent = message;
        row.appendChild(cell);
        sektorTableBody.appendChild(row);
    }

    function renderSektorRows(rows) {
        sektorTableBody.innerHTML = '';

        const normalizedRows = [{
            kode: '*',
            deskripsi: 'Semua Sektor',
            perusahaan: perusahaan.value
        }];
        const seen = new Set(['*']);

        rows.forEach(function (item) {
            const kode = String(item.KD_SEKTOR ?? item.kd_sektor ?? '').trim();
            const deskripsi = String(item.DESKRIPSI ?? item.deskripsi ?? '').trim();
            const kdPerusahaan = String(
                item.KD_PERUSAHAAN ?? item.kd_perusahaan ?? perusahaan.value
            ).trim();
            const normalizedCode = kode.toUpperCase();

            if (!kode || seen.has(normalizedCode)) {
                return;
            }

            seen.add(normalizedCode);
            normalizedRows.push({
                kode: kode,
                deskripsi: deskripsi,
                perusahaan: kdPerusahaan
            });
        });

        normalizedRows.forEach(function (item) {
            const row = document.createElement('tr');
            row.className = 'dst-sector-row';
            row.tabIndex = 0;
            row.setAttribute('role', 'button');
            row.dataset.search = (
                item.kode + ' ' + item.deskripsi + ' ' + item.perusahaan
            ).toLowerCase();

            if (String(sektor.value).toUpperCase() === item.kode.toUpperCase()) {
                row.classList.add('is-selected');
                row.setAttribute('aria-current', 'true');
            }

            [item.kode, item.deskripsi || '-', item.perusahaan || '-']
                .forEach(function (text) {
                    const cell = document.createElement('td');
                    cell.textContent = text;
                    row.appendChild(cell);
                });

            row.addEventListener('click', function () {
                selectSektor(item.kode, item.deskripsi);
            });
            row.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectSektor(item.kode, item.deskripsi);
                }
            });

            sektorTableBody.appendChild(row);
        });

        const emptyRow = document.createElement('tr');
        emptyRow.id = 'sektor-no-result';
        emptyRow.hidden = true;

        const emptyCell = document.createElement('td');
        emptyCell.colSpan = 3;
        emptyCell.className = 'dst-modal-state';
        emptyCell.textContent = 'Sektor/Cluster yang dicari tidak ditemukan.';

        emptyRow.appendChild(emptyCell);
        sektorTableBody.appendChild(emptyRow);
    }

    function filterSektorRows() {
        const keyword = sektorSearch.value.toLowerCase().trim();
        const rows = Array.from(sektorTableBody.querySelectorAll('.dst-sector-row'));
        let visibleCount = 0;

        rows.forEach(function (row) {
            const isVisible = !keyword || row.dataset.search.includes(keyword);
            row.hidden = !isVisible;

            if (isVisible) {
                visibleCount += 1;
            }
        });

        const emptyRow = document.getElementById('sektor-no-result');

        if (emptyRow) {
            emptyRow.hidden = visibleCount > 0;
        }
    }

    function selectSektor(kode, deskripsi) {
        const selectedCode = kode || '*';
        const selectedDescription = deskripsi || 'Semua Sektor/Cluster';

        sektor.value = selectedCode;
        sektorDisplay.value = selectedCode + ' - ' + selectedDescription;
        sektorLabel = deskripsi || 'Semua Sektor';
        closeSektorModal();
    }

    function getFilterData() {
        return {
            _token: csrfToken,
            perusahaan: perusahaan.value,
            tgl_awal: document.getElementById('tgl_awal').value,
            tgl_akhir: document.getElementById('tgl_akhir').value,
            tgl_all: 'T',
            tgl_st1: document.getElementById('tgl_st1').value,
            tgl_st2: document.getElementById('tgl_st2').value,
            tgl_st_all: 'T',
            sektor: sektor.value || '*',
            blok_awal: (document.getElementById('blok_awal').value || 'A').toUpperCase(),
            blok_akhir: (document.getElementById('blok_akhir').value || 'ZZ').toUpperCase(),
            st_aktif: document.querySelector('input[name="st_aktif"]:checked').value
        };
    }

    function validateFilter(data) {
        if (!data.tgl_awal || !data.tgl_akhir || !data.tgl_st1 || !data.tgl_st2) {
            alert('Seluruh nilai tanggal harus tersedia.');
            return false;
        }

        if (data.tgl_awal > data.tgl_akhir) {
            alert('Tanggal akhir PPJB tidak boleh lebih kecil dari tanggal awal.');
            return false;
        }

        if (data.tgl_st1 > data.tgl_st2) {
            alert('Tanggal akhir realisasi tidak boleh lebih kecil dari tanggal awal.');
            return false;
        }

        if (!data.perusahaan) {
            alert('Kode perusahaan/unit tidak tersedia pada session.');
            return false;
        }

        return true;
    }

    async function getSummary() {
        const data = getFilterData();

        if (!validateFilter(data)) {
            return;
        }

        const reportShell = mainDisplay.closest('.dst-report-shell');
        reportShell?.classList.remove('dst-report-shell--initial');
        mainDisplay.classList.remove('dst-paper--initial');

        setLoading(true);

        try {
            const response = await postForm(baseUrl + '/get_summary', data);
            const rows = Array.isArray(response)
                ? response
                : (Array.isArray(response.data) ? response.data : []);

            renderReport(rows, data);
        } catch (error) {
            mainDisplay.innerHTML =
                '<div class="dst-error">' + escapeHtml(error.message) + '</div>';
        } finally {
            setLoading(false);
        }
    }

    /*
     * ================================================================
     * RESOLVER NAMA PERUSAHAAN — versi vanilla JS, setara dengan
     * resolveReportCompany() pada Daftar Surat Pesanan dkk (jQuery).
     * Logika dan urutan sumbernya sengaja disamakan agar hasilnya
     * konsisten di semua fitur.
     * ================================================================
     */
    function extractCompanyName(value) {
        const raw = String(value || '').replace(/\u00a0/g, ' ');
        const locationMatch = raw.match(/Lokasi\s*:[^\r\n|]*?-\s*([^\r\n|]+)/i);
        const companyMatch = raw.match(/\b((?:PT\.?|CV\.?|PD\.?|PERUM)\s+[^\r\n|]+)/i);
        const name = locationMatch ? locationMatch[1] : (companyMatch ? companyMatch[1] : '');

        return String(name || '')
            .replace(/\s+(?:Hi\s*,|Logout\b|Keluar\b|UNIT\s+[A-Z0-9_-]+\b).*$/i, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function pickValue(item, keys) {
        item = item || {};

        for (let index = 0; index < keys.length; index++) {
            const value = item[keys[index]];

            if (value !== null && value !== undefined && value !== '') {
                return value;
            }
        }

        return null;
    }

    function isLongCompanyName(value, unit) {
        const name = String(value === null || value === undefined ? '' : value).trim();

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
        const candidates = [];

        function push(value) {
            const text = String(value === null || value === undefined ? '' : value).trim();

            if (text !== '') {
                candidates.push(text);
            }
        }

        document.querySelectorAll('input, textarea').forEach(function (el) {
            if (el.closest('.dst-page')) {
                return;
            }

            push(el.value);
        });

        document.querySelectorAll('[title]').forEach(function (el) {
            if (el.closest('.dst-page')) {
                return;
            }

            push(el.getAttribute('title'));
        });

        const headerSelectors = [
            '.main-header',
            '.navbar',
            '.topbar',
            '.app-header',
            '.content-header',
            '#header',
            'header'
        ];

        headerSelectors.forEach(function (selector) {
            document.querySelectorAll(selector).forEach(function (el) {
                const clone = el.cloneNode(true);

                clone.querySelectorAll('.dst-page, script, style, noscript').forEach(function (node) {
                    node.remove();
                });

                push(clone.textContent);
            });
        });

        return candidates;
    }

    function scrapeCompanyNameForUnit(unit) {
        unit = String(unit || '').trim();

        if (unit === '') {
            return '';
        }

        const unitPattern = new RegExp('\\b' + escapeRegExp(unit) + '\\b', 'i');
        const candidates = collectUnitTextCandidates();

        for (let index = 0; index < candidates.length; index++) {
            if (!unitPattern.test(candidates[index])) {
                continue;
            }

            const name = extractCompanyName(candidates[index]);

            if (isLongCompanyName(name, unit)) {
                return name;
            }
        }

        return '';
    }

    function resolveReportCompany(firstRow, sessionName) {
        const unit = String(perusahaan.value || '').trim().toUpperCase();

        const rowName = pickValue(firstRow || {}, [
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
            sessionName === undefined ? namaPerusahaanSession.value : sessionName
        ).trim();

        const candidates = [
            extractCompanyName(rowName),
            rowName,
            scrapeCompanyNameForUnit(unit),
            extractCompanyName(sessionName),
            sessionName
        ];

        for (let index = 0; index < candidates.length; index++) {
            if (isLongCompanyName(candidates[index], unit)) {
                return String(candidates[index]).trim();
            }
        }

        return unit || '-';
    }

    function renderReport(rows, filter) {
        const title = reportTitle(filter.st_aktif);
        const periodePpjb =
            formatDate(filter.tgl_awal) + ' s.d ' + formatDate(filter.tgl_akhir);
        const periodeSt =
            formatDate(filter.tgl_st1) + ' s.d ' + formatDate(filter.tgl_st2);
        const companyText = resolveReportCompany(
            rows.length > 0 ? rows[0] : {},
            namaPerusahaanSession.value
        );

        let html = '';
        html += '<div class="report-wrapper">';

        /*
         * GRID 1 — HEADER LAPORAN
         * Kiri: identitas perusahaan unit aktif. Tengah: judul laporan.
         * Kanan: periode PPJB, periode realisasi, blok, status BAST, dan
         * catatan "[Blok/Nomor] = PPJB Batal" (isi sama seperti keterangan
         * yang sebelumnya berdiri sendiri di atas tabel).
         */
        html += '<div class="report-header">';
        html += '<div class="report-company">' + escapeHtml(companyText) + '</div>';
        html += '<div class="report-title">' + escapeHtml(title) + '</div>';
        html += '<div class="report-period">';
        html += 'Periode PPJB: ' + escapeHtml(periodePpjb);
        html += '<br>Periode Realisasi: ' + escapeHtml(periodeSt);
        html += '<br>Mulai Blok: ' + escapeHtml(filter.blok_awal + ' s/d ' + filter.blok_akhir);
        html += '<br>Status BAST: ' + escapeHtml(statusLabel(filter.st_aktif));
        html += '<br>** [Blok/Nomor] = PPJB Batal';
        html += '</div>';
        html += '</div>';

        /*
         * GRID 2 — BARIS SEKTOR/CLUSTER
         * Label di kiri, nilai sektor di tengah, badge status di kanan.
         */
        html += '<div class="report-subtitle">';
        html += '<span class="report-subtitle-label">Sektor/Cluster:</span>';
        html += '<span class="report-subtitle-value">' + escapeHtml(sektorLabel) + '</span>';
        html += '<span class="report-live-badge">Live data</span>';
        html += '</div>';

        /*
         * GRID 3 — TABEL LAPORAN
         * Kolom, urutan, dan isi data tidak diubah.
         */
        html += '<div class="report-table-container">';
        html += '<table class="table table-bordered report-table" style="min-width:1100px;">';
        html += '<thead><tr>';
        html += '<th>No.</th>';
        html += '<th>Cluster</th>';
        html += '<th>Blok/Nomor</th>';
        html += '<th>Tipe Bangunan</th>';
        html += '<th>Nama Pembeli</th>';
        html += '<th>Tgl. PPJB</th>';
        html += '<th>No. BAST</th>';
        html += '<th>Tgl. ST</th>';
        html += '<th>Status</th>';
        html += '</tr></thead><tbody>';

        if (!rows.length) {
            html += '<tr><td colspan="9" class="empty-row">Data tidak ditemukan.</td></tr>';
        } else {
            rows.forEach(function (item, index) {
                const statusSt = String(value(item, 'STATUS_ST') || 'A').toUpperCase();
                const isCancelled = statusSt === 'T';
                const tipeBangunan =
                    value(item, 'TIPE') || value(item, 'JENIS_BANGUNAN') || '-';
                const blokNomor = value(item, 'BLOK_NOMOR') || '-';

                html += '<tr>';
                html += '<td class="dst-center dst-number-cell">' + (index + 1) + '</td>';
                html += '<td>' + escapeHtml(value(item, 'NAMA_CLUSTER') || '-') + '</td>';
                html += '<td class="dst-center' +
                    (String(blokNomor).indexOf('**') === 0 ? ' dst-cancelled-unit' : '') +
                    '">' + escapeHtml(blokNomor) + '</td>';
                html += '<td>' + escapeHtml(tipeBangunan) + '</td>';
                html += '<td>' + escapeHtml(value(item, 'NAMA_PEMBELI') || '-') + '</td>';
                html += '<td class="dst-center">' +
                    escapeHtml(formatDate(value(item, 'TGL_PPJB'))) + '</td>';
                html += '<td>' + escapeHtml(value(item, 'NO_BAST') || '-') + '</td>';
                html += '<td class="dst-center">' +
                    escapeHtml(formatDate(value(item, 'TGL_SERAH_TERIMA'))) + '</td>';
                html += '<td class="dst-center"><span class="dst-status-badge ' +
                    (isCancelled ? 'dst-status-cancelled' : 'dst-status-active') +
                    '">' + (isCancelled ? 'Batal' : 'Aktif') + '</span></td>';
                html += '</tr>';
            });
        }

        html += '</tbody></table></div></div>';
        mainDisplay.innerHTML = html;
    }

    function printReport() {
        const report = document.getElementById('main-display');

        if (!report || !report.querySelector('.report-wrapper')) {
            alert('Silakan klik View terlebih dahulu untuk menampilkan laporan.');
            return;
        }

        const reportHtml = report.innerHTML;

        /*
         * Print terisolasi mengikuti Daftar Sertifikat Pecahan.
         * Tidak mengunci orientation ataupun paper size.
         */
        const oldFrame = document.getElementById('unitSTNativePrintFrame');

        if (oldFrame) {
            oldFrame.remove();
        }

        const frame = document.createElement('iframe');
        frame.id = 'unitSTNativePrintFrame';
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

        const frameWindow = frame.contentWindow;
        const frameDocument = frame.contentDocument || frameWindow.document;

        const printCss = `
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
                font-size: 7px !important;
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
                overflow-wrap: anywhere !important;
                word-break: normal !important;
                vertical-align: middle !important;
                font-size: 6.8px !important;
                line-height: 1.18 !important;
            }

            .report-table th {
                text-align: center !important;
                font-weight: 700 !important;
            }

            .report-table tbody tr:nth-child(even) td,
            .report-table tbody tr:hover td {
                background: #fff !important;
            }

            .dst-status-badge {
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

            .dst-cancelled-unit,
            .dst-number-cell {
                color: #000 !important;
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
            + '<title>Daftar Unit ST dan Migrasi TM</title>'
            + '<style>' + printCss + '</style>'
            + '</head>'
            + '<body>' + reportHtml + '</body>'
            + '</html>'
        );
        frameDocument.close();

        const cleanupPrintFrame = function () {
            const currentFrame = document.getElementById('unitSTNativePrintFrame');

            if (currentFrame) {
                currentFrame.remove();
            }
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
                console.error('Gagal membuka dialog print Daftar Unit ST:', error);
                cleanupPrintFrame();
                alert('Dialog print gagal dibuka. Silakan coba kembali.');
            }
        }, 180);

        window.setTimeout(cleanupPrintFrame, 30000);
    }


    async function postForm(url, data) {
        const body = new URLSearchParams();

        Object.keys(data).forEach(function (key) {
            body.append(key, data[key] ?? '');
        });

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body,
            credentials: 'same-origin'
        });

        const result = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            const validation = result.errors
                ? Object.values(result.errors).flat().join(' ')
                : '';
            throw new Error(validation || result.message || 'Gagal mengambil data.');
        }

        return result;
    }

    function setLoading(isLoading) {
        const reportShell = mainDisplay.closest('.dst-report-shell');

        loadingInfo.style.display = isLoading ? 'block' : 'none';

        if (reportShell) {
            reportShell.classList.toggle(
                'dst-report-shell--loading',
                isLoading
            );
        }

        okButton.disabled = isLoading;
        printButton.disabled = isLoading;
    }

    function reportTitle(value) {
        if (value === 'T') {
            return 'DAFTAR SERAH TERIMA YANG BATAL';
        }

        if (value === '*') {
            return 'DAFTAR SERAH TERIMA (ALL)';
        }

        return 'DAFTAR SERAH TERIMA';
    }

    function statusLabel(value) {
        if (value === 'A') {
            return 'AKTIF';
        }

        if (value === 'T') {
            return 'BATAL';
        }

        return 'SEMUA';
    }

    function value(item, key) {
        return item[key] ?? item[key.toLowerCase()] ?? null;
    }

    function formatDate(value) {
        if (!value) {
            return '-';
        }

        const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);

        if (!match) {
            return String(value);
        }

        return match[3] + '-' + match[2] + '-' + match[1];
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endsection
