{{-- FONT STANDARD SURAT PESANAN V1-20260812 --}}
{{-- HEADER ICON EXACT SERTIPIKAT V2-20260812 --}}
{{-- INITIAL ICON VERSION V1-20260812-TABLE-CONSISTENT --}}
{{-- UI REFRESH V1-20260901-VISUAL-ONLY --}}
@extends('layouts.template')

@section('content')

<style>
    .lampiran-st-page {
        --ls-primary: #2563eb;
        --ls-primary-dark: #1d4ed8;
        --ls-primary-soft: #eff6ff;
        --ls-success: #059669;
        --ls-success-dark: #047857;
        --ls-warning: #d97706;
        --ls-danger: #dc2626;
        --ls-bg: #f3f6fb;
        --ls-card: #ffffff;
        --ls-border: #dbe3ef;
        --ls-border-strong: #c8d3e1;
        --ls-text: #172033;
        --ls-muted: #667085;
        --ls-subtle: #98a2b3;
        --ls-header: #f6f8fc;
        width: 100%;
        min-height: calc(100vh - 100px);
        padding: 18px;
        color: var(--ls-text);
        font-family: "Inter", "Segoe UI Variable", "Segoe UI", Roboto, Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        font-size: 13px;
        background:
            radial-gradient(circle at top right, rgba(37, 99, 235, 0.07), transparent 30%),
            var(--ls-bg);
    }

    .lampiran-st-page,
    .lampiran-st-page * {
        box-sizing: border-box;
    }

    .lampiran-st-frame {
        width: 100%;
        min-height: 680px;
        overflow: hidden;
        border: 1px solid var(--ls-border);
        border-radius: 16px;
        background: var(--ls-card);
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
    }

    .lampiran-st-toolbar {
        padding: 22px;
        border-bottom: 1px solid var(--ls-border);
        background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
    }

    .lampiran-st-heading {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 14px;
    }

    .lampiran-st-heading-icon {
        display: inline-flex;
        width: 48px;
        height: 48px;
        flex: 0 0 48px;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--ls-primary), var(--ls-primary-dark));
        color: #ffffff;
        font-size: 20px;
        box-shadow: 0 9px 20px rgba(37, 99, 235, 0.24);
    }

    .lampiran-st-title {
        margin: 0;
        color: var(--ls-text);
        font-size: 23px;
        font-weight: 700;
        letter-spacing: -0.25px;
        line-height: 1.25;
    }

    .lampiran-st-subtitle {
        margin: 4px 0 0;
        color: var(--ls-muted);
        font-size: 12.5px;
        line-height: 1.5;
    }

    .lampiran-st-unit-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
        padding: 9px 12px;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: var(--ls-primary-soft);
        color: #1e40af;
        font-size: 12px;
        white-space: nowrap;
    }

    .lampiran-st-formrow {
        display: grid;
        grid-template-columns: minmax(650px, 1fr) minmax(155px, 0.22fr) minmax(165px, 0.24fr);
        gap: 12px;
        align-items: stretch;
    }

    .lampiran-st-box,
    .lampiran-st-choicebox,
    .lampiran-st-emailbox {
        border: 1px solid var(--ls-border);
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 3px 10px rgba(15, 23, 42, 0.035);
    }

    .lampiran-st-box {
        min-height: 138px;
        padding: 15px;
    }

    .lampiran-st-fields {
        width: 100%;
        max-width: none;
    }

    .lampiran-st-section-label {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 12px;
        color: #344054;
        font-size: 12px;
        font-weight: 750;
        letter-spacing: 0.2px;
        text-transform: uppercase;
    }

    .lampiran-st-section-label i {
        color: var(--ls-primary);
    }

    .lampiran-st-filter-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 12px;
    }

    .lampiran-st-filter-head .lampiran-st-section-label {
        margin-bottom: 0;
    }

    .lampiran-st-filter-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        margin-left: auto;
    }

    .lampiran-st-filter-actions .lampiran-st-button {
        min-width: 118px;
    }

    .lampiran-st-row--parameters {
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 9px 12px;
    }

    .lampiran-st-row--parameters > .lampiran-st-label {
        align-self: center;
    }

    .lampiran-st-parameter-item {
        display: flex;
        min-width: 0;
        flex-direction: column;
        gap: 5px;
    }

    .lampiran-st-parameter-item .lampiran-st-small-label {
        line-height: 1.1;
    }

    .lampiran-st-row {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 38px;
        margin-bottom: 9px;
    }

    .lampiran-st-row:last-child {
        margin-bottom: 0;
    }

    .lampiran-st-label {
        width: 92px;
        margin: 0;
        color: #475467;
        text-align: left;
        font-size: 12px;
        font-weight: 650;
        white-space: nowrap;
    }

    .lampiran-st-required {
        display: inline-flex;
        align-items: center;
        min-height: 26px;
        padding: 4px 8px;
        border-radius: 999px;
        background: #fff1f2;
        color: #be123c;
        font-size: 10.5px;
        font-weight: 750;
        white-space: nowrap;
    }

    /*
     * Setelah Cluster dipilih, badge validasi berubah menjadi deskripsi
     * Cluster seperti pada aplikasi desktop. Data deskripsi berasal dari
     * response DESKRIPSI endpoint get_sektor; tidak ada query tulis database.
     */
    .lampiran-st-required.is-cluster-description {
        max-width: 250px;
        min-width: 150px;
        justify-content: flex-start;
        padding: 4px 2px;
        overflow: hidden;
        border-radius: 0;
        background: transparent;
        color: #dc2626;
        font-size: 11.5px;
        font-style: italic;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .lampiran-st-input,
    .lampiran-st-number {
        height: 36px;
        min-height: 36px;
        padding: 7px 10px;
        border: 1px solid var(--ls-border-strong);
        border-radius: 8px;
        background: #ffffff;
        color: #101828;
        font-family: inherit;
        font-size: 12.5px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    .lampiran-st-input[readonly] {
        background: #f8fafc;
        cursor: default;
    }

    .lampiran-st-input:hover,
    .lampiran-st-number:hover {
        border-color: #aebed1;
    }

    .lampiran-st-input:focus,
    .lampiran-st-number:focus,
    .lampiran-st-modal-search:focus {
        outline: 0;
        border-color: var(--ls-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.13);
    }

    .lampiran-st-small-label {
        color: #667085;
        font-size: 11.5px;
        font-weight: 650;
        white-space: nowrap;
    }

    .lampiran-st-lookup {
        display: inline-flex;
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        background: var(--ls-primary-soft);
        color: var(--ls-primary-dark);
        cursor: pointer;
        font-size: 13px;
        transition: transform 0.15s ease, background 0.15s ease;
    }

    .lampiran-st-lookup:hover {
        background: #dbeafe;
        transform: translateY(-1px);
    }

    .lampiran-st-button {
        display: inline-flex;
        min-width: 96px;
        height: 40px;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 7px 13px;
        border: 1px solid var(--ls-border-strong);
        border-radius: 9px;
        background: #ffffff;
        color: #344054;
        cursor: pointer;
        font-family: inherit;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.05);
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease, background 0.15s ease;
    }

    .lampiran-st-button:hover:not(:disabled) {
        border-color: #aebed1;
        background: #f8fafc;
        transform: translateY(-1px);
        box-shadow: 0 5px 12px rgba(15, 23, 42, 0.08);
    }

    .lampiran-st-button:disabled {
        cursor: not-allowed;
        opacity: 0.58;
    }

    .lampiran-st-button--primary {
        border-color: var(--ls-primary);
        background: linear-gradient(135deg, var(--ls-primary), var(--ls-primary-dark));
        color: #ffffff;
        box-shadow: 0 7px 16px rgba(37, 99, 235, 0.22);
    }

    .lampiran-st-button--primary:hover:not(:disabled) {
        border-color: var(--ls-primary-dark);
        background: linear-gradient(135deg, #2f6ff0, #1e4fc4);
        color: #ffffff;
    }

    .lampiran-st-button--success {
        border-color: #a7f3d0;
        background: #ecfdf3;
        color: var(--ls-success-dark);
    }

    .lampiran-st-emailbox {
        display: flex;
        min-height: 138px;
        flex-direction: column;
        justify-content: flex-start;
        gap: 9px;
        padding: 15px;
    }

    .lampiran-st-emailbox .lampiran-st-button {
        width: 100%;
        min-width: 0;
    }

    .lampiran-st-choicebox {
        width: auto;
        min-height: 138px;
        padding: 15px;
    }

    .lampiran-st-choicebox label {
        display: flex;
        min-height: 38px;
        align-items: center;
        gap: 8px;
        margin: 0 0 8px;
        padding: 8px 10px;
        border: 1px solid #e5eaf2;
        border-radius: 9px;
        background: #fafbfc;
        color: #475467;
        cursor: pointer;
        font-size: 11.5px;
        font-weight: 650;
        white-space: nowrap;
    }

    .lampiran-st-choicebox label:last-child {
        margin-bottom: 0;
    }

    .lampiran-st-choicebox label:hover {
        border-color: #bfdbfe;
        background: var(--ls-primary-soft);
    }

    .lampiran-st-choicebox input[type="radio"],
    .lampiran-st-table input[type="checkbox"] {
        width: 15px;
        height: 15px;
        accent-color: var(--ls-primary);
        cursor: pointer;
    }

    .lampiran-st-tablearea {
        min-height: 540px;
        padding: 18px;
        overflow: auto;
        background: #f8fafc;
    }

    .lampiran-st-tablebar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 12px;
        padding: 2px 2px 0;
    }

    .lampiran-st-tablebar h2 {
        margin: 0;
        color: #1d2939;
        font-size: 15px;
        font-weight: 750;
    }

    .lampiran-st-tablebar p {
        margin: 3px 0 0;
        color: var(--ls-muted);
        font-size: 11.5px;
    }

    .lampiran-st-tablemeta {
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    .lampiran-st-meta-badge {
        display: inline-flex;
        min-height: 30px;
        align-items: center;
        gap: 5px;
        padding: 6px 10px;
        border: 1px solid var(--ls-border);
        border-radius: 999px;
        background: #ffffff;
        color: #475467;
        font-size: 11.5px;
        font-weight: 650;
    }

    .lampiran-st-meta-badge strong {
        color: var(--ls-primary-dark);
        font-size: 12px;
    }

    .lampiran-st-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 12px;
        padding: 10px 12px;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        background: var(--ls-primary-soft);
        color: #1e40af;
        font-weight: 700;
    }

    .lampiran-st-alert {
        margin: 0;
        padding: 13px 14px;
        border: 1px solid #fecaca;
        border-radius: 10px;
        background: #fef2f2;
        color: #991b1b;
        font-weight: 650;
        line-height: 1.5;
    }

    #main-display {
        overflow: auto;
        border: 1px solid var(--ls-border);
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
    }

    .lampiran-st-table {
        width: max-content;
        min-width: 1280px;
        border-collapse: separate;
        border-spacing: 0;
        background: #ffffff;
        font-size: 12px;
    }

    .lampiran-st-table th {
        position: sticky;
        top: 0;
        z-index: 2;
        height: 44px;
        padding: 9px 10px;
        border-right: 1px solid #e4e9f0;
        border-bottom: 1px solid var(--ls-border-strong);
        background: var(--ls-header);
        color: #344054;
        text-align: center;
        font-size: 11px;
        font-weight: 750;
        letter-spacing: 0.15px;
        white-space: nowrap;
    }

    .lampiran-st-table th:last-child,
    .lampiran-st-table td:last-child {
        border-right: 0;
    }

    .lampiran-st-table td {
        height: 42px;
        padding: 8px 10px;
        border-right: 1px solid #eef1f5;
        border-bottom: 1px solid #eef1f5;
        background: #ffffff;
        color: #344054;
        white-space: nowrap;
    }

    .lampiran-st-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .lampiran-st-table tbody tr:nth-child(even) td {
        background: #fbfcfe;
    }

    .lampiran-st-table tbody tr:hover td {
        background: #f0f7ff;
    }

    .lampiran-st-table .center-cell {
        text-align: center;
    }

    .lampiran-st-table .right-cell {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .lampiran-st-table .name-cell {
        min-width: 220px;
        font-weight: 650;
    }

    .lampiran-st-table .email-cell {
        min-width: 220px;
        color: #475467;
    }

    .lampiran-st-table .empty-row {
        height: 330px;
        padding: 0;
        background: #ffffff !important;
        text-align: center;
    }

    .lampiran-st-empty {
        display: flex;
        min-width: 100%;
        min-height: 300px;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 30px;
        color: var(--ls-muted);
    }

    .lampiran-st-empty-icon {
        display: inline-flex;
        width: 52px;
        height: 52px;
        align-items: center;
        justify-content: center;
        margin-bottom: 13px;
        border: 0;
        border-radius: 16px;
        background: #dbeafe;
        color: #2563eb;
        box-shadow: none;
        font-size: 20px;
    }

    .lampiran-st-empty strong {
        margin-bottom: 5px;
        color: #344054;
        font-size: 14px;
    }

    .lampiran-st-empty span:last-child {
        max-width: 410px;
        line-height: 1.55;
    }

    .lampiran-st-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1050;
        padding: 24px;
        overflow-y: auto;
        background: rgba(15, 23, 42, 0.48);
        backdrop-filter: blur(2px);
    }

    .lampiran-st-modal.show {
        display: block;
    }

    .lampiran-st-modal-dialog {
        width: min(880px, 100%);
        margin: 34px auto;
        overflow: hidden;
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 15px;
        background: #ffffff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.3);
    }

    .lampiran-st-modal-dialog.lampiran-st-modal-dialog--wide {
        width: min(1480px, 100%);
    }

    .lampiran-st-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px 18px;
        border-bottom: 1px solid var(--ls-border);
        background: #ffffff;
        color: #1d2939;
        font-size: 14px;
        font-weight: 750;
    }

    .lampiran-st-modal-body {
        padding: 16px;
        background: #f8fafc;
    }

    .lampiran-st-modal-search {
        width: 100%;
        height: 40px;
        margin-bottom: 11px;
        padding: 8px 12px;
        border: 1px solid var(--ls-border-strong);
        border-radius: 9px;
        background: #ffffff;
        font-family: inherit;
        font-size: 12.5px;
    }

    .lampiran-st-modal-table-wrap {
        max-height: 440px;
        overflow: auto;
        border: 1px solid var(--ls-border);
        border-radius: 10px;
        background: #ffffff;
    }

    .lampiran-st-modal-table {
        width: 100%;
        min-width: 620px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .lampiran-st-modal-table--blok {
        min-width: 1720px;
    }

    .lampiran-st-modal-table--blok th,
    .lampiran-st-modal-table--blok td {
        white-space: nowrap;
    }

    .lampiran-st-modal-table--blok th:first-child,
    .lampiran-st-modal-table--blok td:first-child {
        position: sticky;
        left: 0;
    }

    .lampiran-st-modal-table--blok th:first-child {
        z-index: 3;
        background: var(--ls-header);
    }

    .lampiran-st-modal-table--blok td:first-child {
        z-index: 1;
        background: #ffffff;
        font-weight: 700;
    }

    .lampiran-st-modal-table--blok tbody tr:nth-child(even) td:first-child {
        background: #fbfcfe;
    }

    .lampiran-st-modal-table--blok tbody tr:hover td:first-child {
        background: #eff6ff;
    }

    .lampiran-st-modal-table th,
    .lampiran-st-modal-table td {
        padding: 10px;
        border-right: 1px solid #eef1f5;
        border-bottom: 1px solid #eef1f5;
        font-size: 12px;
    }

    .lampiran-st-modal-table th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: var(--ls-header);
        color: #344054;
        text-align: center;
        font-weight: 750;
    }

    .lampiran-st-modal-table tbody tr {
        cursor: pointer;
    }

    .lampiran-st-modal-table tbody tr:hover td {
        background: #eff6ff;
    }

    /* LOOKUP BLOK V1: Column Criteria 20 kolom seperti desktop */
    .lampiran-st-blok-criteria {
        display: grid;
        grid-template-columns: minmax(250px, 1.2fr) minmax(240px, 1fr) auto auto;
        gap: 10px 12px;
        align-items: end;
        margin-bottom: 12px;
        padding: 12px;
        border: 1px solid var(--ls-border);
        border-radius: 11px;
        background: #fff;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .035);
    }
    .lampiran-st-criteria-group { display:flex; min-width:0; flex-direction:column; gap:5px; }
    .lampiran-st-criteria-label { color:#667085; font-size:10.5px; font-weight:750; letter-spacing:.16px; text-transform:uppercase; }
    .lampiran-st-criteria-select,
    .lampiran-st-criteria-input {
        width:100%; height:38px; padding:7px 10px;
        border:1px solid var(--ls-border-strong); border-radius:8px;
        background:#fff; color:#101828; font-family:inherit; font-size:12px;
    }
    .lampiran-st-criteria-select:focus,
    .lampiran-st-criteria-input:focus { outline:0; border-color:var(--ls-primary); box-shadow:0 0 0 3px rgba(37,99,235,.12); }
    .lampiran-st-criteria-column-wrap { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:8px; align-items:center; }
    .lampiran-st-criteria-counter {
        display:inline-flex; height:38px; align-items:center; justify-content:center;
        padding:0 10px; border:1px solid #d7deea; border-radius:8px;
        background:#f8fafc; color:#667085; font-size:11px; font-weight:700; white-space:nowrap;
    }
    .lampiran-st-criteria-mode {
        display:flex; height:38px; align-items:center; gap:10px; padding:0 10px;
        border:1px solid var(--ls-border); border-radius:8px; background:#f8fafc; white-space:nowrap;
    }
    .lampiran-st-criteria-mode label { display:inline-flex; align-items:center; gap:5px; margin:0; color:#475467; cursor:pointer; font-size:11.5px; font-weight:650; }
    .lampiran-st-criteria-mode input { margin:0; accent-color:var(--ls-primary); }
    .lampiran-st-criteria-actions { display:flex; gap:7px; align-items:center; }
    .lampiran-st-criteria-actions .lampiran-st-button { min-width:82px; height:38px; }
    .lampiran-st-modal-table--blok tbody tr.blok-criteria-hit td { background:#dbeafe !important; }
    .lampiran-st-modal-table--blok tbody tr.blok-criteria-hit td:first-child { background:#dbeafe !important; box-shadow:inset 4px 0 0 #2563eb; }
    @media (max-width:920px) { .lampiran-st-blok-criteria { grid-template-columns:1fr 1fr; } }
    @media (max-width:640px) { .lampiran-st-blok-criteria { grid-template-columns:1fr; } }


    /* LOOKUP BLOK V2
       Filter = pilih kolom -> langsung sort, criteria opsional dan live.
       Search = filter selector + criteria disembunyikan, diganti 1 field pencarian lebar. */
    .lampiran-st-blok-criteria {
        grid-template-columns: minmax(280px, 1.2fr) minmax(280px, 1fr) auto auto;
        align-items: end;
    }

    .lampiran-st-criteria-search-group {
        display: none;
        grid-column: 1 / span 2;
        min-width: 0;
    }

    .lampiran-st-blok-criteria.is-search-mode .lampiran-st-criteria-filter-field,
    .lampiran-st-blok-criteria.is-search-mode .lampiran-st-criteria-filter-value {
        display: none;
    }

    .lampiran-st-blok-criteria.is-search-mode .lampiran-st-criteria-search-group {
        display: flex;
    }

    .lampiran-st-criteria-search-input {
        width: 100%;
        height: 38px;
        padding: 7px 12px;
        border: 1px solid var(--ls-border-strong);
        border-radius: 8px;
        background: #ffffff;
        color: #101828;
        font-family: inherit;
        font-size: 12px;
    }

    .lampiran-st-criteria-search-input:focus {
        outline: 0;
        border-color: var(--ls-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    .lampiran-st-criteria-actions .lampiran-st-button {
        min-width: 76px;
    }

    @media (max-width: 920px) {
        .lampiran-st-blok-criteria {
            grid-template-columns: 1fr 1fr;
        }

        .lampiran-st-criteria-search-group {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 640px) {
        .lampiran-st-blok-criteria {
            grid-template-columns: 1fr;
        }

        .lampiran-st-criteria-search-group {
            grid-column: 1;
        }
    }

    .lampiran-st-notice {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 2200;
        align-items: center;
        justify-content: center;
        padding: 18px;
        background: rgba(15, 23, 42, 0.48);
        backdrop-filter: blur(2px);
    }

    .lampiran-st-notice.show {
        display: flex;
    }

    .lampiran-st-notice-dialog {
        width: min(410px, calc(100vw - 34px));
        overflow: hidden;
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.35);
        font-family: inherit;
        animation: lampiranNoticePop 0.18s ease-out;
    }

    @keyframes lampiranNoticePop {
        from { opacity: 0; transform: translateY(8px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .lampiran-st-notice-top {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 17px 18px 12px;
        border-bottom: 1px solid #edf0f4;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .lampiran-st-notice-icon {
        display: inline-flex;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 18px;
    }

    .lampiran-st-notice.is-warning .lampiran-st-notice-icon {
        color: #9a5a00;
        background: #fff4d8;
        border: 1px solid #ffd993;
    }

    .lampiran-st-notice.is-error .lampiran-st-notice-icon {
        color: #a82424;
        background: #fff0f0;
        border: 1px solid #ffc7c7;
    }

    .lampiran-st-notice.is-info .lampiran-st-notice-icon {
        color: #225b9f;
        background: #eef6ff;
        border: 1px solid #c8e1ff;
    }

    .lampiran-st-notice.is-success .lampiran-st-notice-icon {
        color: #237545;
        background: #eaf8ef;
        border: 1px solid #bde6ca;
    }

    .lampiran-st-notice-title {
        margin: 0;
        color: #17212b;
        font-size: 15px;
        font-weight: 750;
        line-height: 1.2;
    }

    .lampiran-st-notice-subtitle {
        margin-top: 3px;
        color: #6b7280;
        font-size: 11.5px;
        font-weight: 600;
    }

    .lampiran-st-notice-body {
        padding: 15px 18px 4px;
        color: #344054;
        font-size: 12.5px;
        font-weight: 600;
        line-height: 1.55;
    }

    .lampiran-st-notice-actions {
        display: flex;
        justify-content: flex-end;
        padding: 14px 18px 17px;
    }

    .lampiran-st-notice-button {
        min-width: 96px;
        height: 36px;
        padding: 6px 16px;
        border: 0;
        border-radius: 9px;
        background: linear-gradient(135deg, var(--ls-primary), var(--ls-primary-dark));
        color: #ffffff;
        cursor: pointer;
        font-family: inherit;
        font-size: 12px;
        font-weight: 750;
        box-shadow: 0 7px 15px rgba(37, 99, 235, 0.22);
    }

    .lampiran-st-notice-button:hover {
        filter: brightness(1.05);
    }

    @media (max-width: 1180px) {
        .lampiran-st-formrow {
            grid-template-columns: minmax(560px, 1fr) minmax(150px, 0.28fr) minmax(160px, 0.3fr);
        }
    }

    @media (max-width: 980px) {
        .lampiran-st-page {
            padding: 10px;
        }

        .lampiran-st-formrow {
            grid-template-columns: 1fr 1fr;
        }

        .lampiran-st-fields {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 680px) {
        .lampiran-st-toolbar,
        .lampiran-st-tablearea {
            padding: 14px;
        }

        .lampiran-st-heading {
            align-items: flex-start;
        }

        .lampiran-st-unit-badge {
            display: none;
        }

        .lampiran-st-formrow {
            grid-template-columns: 1fr;
        }

        .lampiran-st-filter-head {
            align-items: stretch;
            flex-direction: column;
        }

        .lampiran-st-filter-actions {
            width: 100%;
            margin-left: 0;
        }

        .lampiran-st-filter-actions .lampiran-st-button {
            flex: 1 1 0;
            min-width: 0;
        }

        .lampiran-st-row {
            flex-wrap: wrap;
        }

        .lampiran-st-label {
            width: 100%;
        }

        .lampiran-st-tablebar {
            align-items: flex-start;
            flex-direction: column;
        }
    }


    /* Hero judul mengikuti Daftar Serah Terima, tanpa ilustrasi rumah. */
    .lampiran-st-page-hero {

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

    .lampiran-st-page-heading {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 15px;
    }

    .lampiran-st-page-heading-icon {
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

    .lampiran-st-page-heading h1 {
        margin: 0;
        color: #0f172a;
        font-size: clamp(19px, 1.65vw, 27px);
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -0.35px;
    }

    .lampiran-st-page-unit-badge {
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
        .lampiran-st-page-hero { padding: 16px; }
        .lampiran-st-page-heading-icon {
            width: 50px;
            height: 50px;
            flex-basis: 50px;
        }
        .lampiran-st-page-unit-badge { display: none; }
    }



    /* =========================================================
       HEADER ICON MATCH — DAFTAR SERTIPIKAT PECAHAN
       Hanya ikon kiri atas yang disamakan. Struktur/fungsi lain tetap.
       ========================================================= */
    .lampiran-st-page-heading-icon.sertipikat-style-heading-icon {

        
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
        .lampiran-st-page-heading-icon.sertipikat-style-heading-icon {
            width: 34px !important;
            height: 34px !important;
            flex-basis: 34px !important;
        }
    }



    /* FONT STANDARD — MATCH DAFTAR SURAT PESANAN */
    .lampiran-st-page,
    .lampiran-st-page input,
    .lampiran-st-page select,
    .lampiran-st-page button,
    .lampiran-st-page textarea,
    .lampiran-st-page label,
    .lampiran-st-page table,
    .lampiran-st-page td,
    .lampiran-st-page .lampiran-st-subtitle,
    .lampiran-st-page .lampiran-st-small-label,
    .lampiran-st-page .lampiran-st-notice-subtitle {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
    }

    .lampiran-st-page .lampiran-st-page-heading h1,
    .lampiran-st-page .lampiran-st-title,
    .lampiran-st-page .lampiran-st-heading,
    .lampiran-st-page .lampiran-st-label,
    .lampiran-st-page .lampiran-st-section-label,
    .lampiran-st-page .lampiran-st-criteria-label,
    .lampiran-st-page .lampiran-st-tablemeta,
    .lampiran-st-page .lampiran-st-notice-title,
    .lampiran-st-page th {
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif !important;
    }

    /* =========================================================
       UI REFRESH V1-20260901 — VISUAL ONLY
       Blok ini hanya menimpa tampilan (warna, radius, bayangan,
       spasi). Tidak ada perubahan struktur HTML, id, class, atau
       logika JavaScript. Palet mengikuti fitur Daftar Surat
       Pesanan / Daftar Serah Terima / Approval agar konsisten.
       ========================================================= */

    /* --- Kanvas halaman --- */
    .lampiran-st-page {
        background:
            radial-gradient(1100px 340px at 12% -10%, rgba(37, 99, 235, 0.10), transparent 62%),
            radial-gradient(900px 300px at 100% 0%, rgba(37, 99, 235, 0.06), transparent 60%),
            #f4f7fc;
    }

    /* --- Hero judul --- */
    .lampiran-st-page .lampiran-st-page-hero {
        position: relative;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: linear-gradient(135deg, #ffffff 0%, #ffffff 55%, #f5f9ff 100%);
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
    }

    .lampiran-st-page .lampiran-st-page-hero::before {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        border-radius: 16px 16px 0 0;
        background: linear-gradient(90deg, #2563eb, #60a5fa 55%, rgba(96, 165, 250, 0));
        content: "";
    }

    .lampiran-st-page .lampiran-st-page-unit-badge {
        border-color: #cfe0fb;
        background: linear-gradient(180deg, #eff6ff, #dbeafe);
        color: #1e40af;
        font-weight: 700;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    /* --- Frame utama --- */
    .lampiran-st-page .lampiran-st-frame {
        border-color: #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.07);
    }

    .lampiran-st-page .lampiran-st-toolbar {
        position: relative;
        padding: 20px;
        border-bottom: 1px solid #e6ecf5;
        background: linear-gradient(180deg, #f7faff 0%, #ffffff 100%);
    }

    /* --- Kartu filter / pilih / email --- */
    .lampiran-st-page .lampiran-st-box,
    .lampiran-st-page .lampiran-st-choicebox,
    .lampiran-st-page .lampiran-st-emailbox {
        position: relative;
        overflow: hidden;
        border-color: #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 3px 12px rgba(15, 23, 42, 0.05);
        transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .lampiran-st-page .lampiran-st-box::before,
    .lampiran-st-page .lampiran-st-choicebox::before,
    .lampiran-st-page .lampiran-st-emailbox::before {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, #2563eb, #93c5fd);
        content: "";
    }

    .lampiran-st-page .lampiran-st-choicebox::before {
        background: linear-gradient(90deg, #2563eb, #bfdbfe);
    }

    .lampiran-st-page .lampiran-st-emailbox::before {
        background: linear-gradient(90deg, #059669, #6ee7b7);
    }

    .lampiran-st-page .lampiran-st-box:hover,
    .lampiran-st-page .lampiran-st-choicebox:hover,
    .lampiran-st-page .lampiran-st-emailbox:hover {
        border-color: #cfe0fb;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
    }

    .lampiran-st-page .lampiran-st-box,
    .lampiran-st-page .lampiran-st-choicebox,
    .lampiran-st-page .lampiran-st-emailbox {
        padding: 16px 16px 15px;
    }

    /* --- Label seksi jadi chip ikon --- */
    .lampiran-st-page .lampiran-st-section-label {
        gap: 9px;
        color: #1e293b;
        font-size: 11.5px;
        letter-spacing: 0.5px;
    }

    .lampiran-st-page .lampiran-st-section-label i {
        display: inline-flex;
        width: 26px;
        height: 26px;
        flex: 0 0 26px;
        align-items: center;
        justify-content: center;
        border: 1px solid #cfe0fb;
        border-radius: 9px;
        background: linear-gradient(180deg, #eff6ff, #dbeafe);
        color: #1d4ed8;
        font-size: 11px;
    }

    .lampiran-st-page .lampiran-st-emailbox .lampiran-st-section-label i {
        border-color: #a7f3d0;
        background: linear-gradient(180deg, #ecfdf3, #d1fae5);
        color: #047857;
    }

    .lampiran-st-page .lampiran-st-filter-head {
        padding-bottom: 12px;
        border-bottom: 1px dashed #e4ebf5;
    }

    /* --- Kontrol input --- */
    .lampiran-st-page .lampiran-st-label {
        color: #334155;
        font-weight: 700;
    }

    .lampiran-st-page .lampiran-st-input,
    .lampiran-st-page .lampiran-st-number,
    .lampiran-st-page .lampiran-st-criteria-select,
    .lampiran-st-page .lampiran-st-criteria-input,
    .lampiran-st-page .lampiran-st-modal-search {
        border-color: #d7e0ec;
        border-radius: 9px;
        background: #fcfdff;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }

    .lampiran-st-page .lampiran-st-input:hover,
    .lampiran-st-page .lampiran-st-number:hover {
        border-color: #bcd0ea;
        background: #ffffff;
    }

    .lampiran-st-page .lampiran-st-input:focus,
    .lampiran-st-page .lampiran-st-number:focus,
    .lampiran-st-page .lampiran-st-criteria-select:focus,
    .lampiran-st-page .lampiran-st-criteria-input:focus,
    .lampiran-st-page .lampiran-st-modal-search:focus {
        border-color: #2563eb;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
    }

    .lampiran-st-page .lampiran-st-input[readonly] {
        background: #f3f7fd;
        color: #334155;
    }

    .lampiran-st-page .lampiran-st-lookup {
        border-color: #cfe0fb;
        border-radius: 9px;
        background: linear-gradient(180deg, #eff6ff, #dbeafe);
        color: #1d4ed8;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.10);
    }

    .lampiran-st-page .lampiran-st-lookup:hover {
        border-color: #2563eb;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        box-shadow: 0 6px 14px rgba(37, 99, 235, 0.26);
    }

    /* --- Parameter jadi mini-card --- */
    .lampiran-st-page .lampiran-st-parameter-item {
        padding: 8px 10px 9px;
        border: 1px solid #e6ecf5;
        border-radius: 10px;
        background: linear-gradient(180deg, #fbfdff, #f5f9ff);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .lampiran-st-page .lampiran-st-parameter-item:focus-within {
        border-color: #bfdbfe;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.10);
    }

    .lampiran-st-page .lampiran-st-parameter-item .lampiran-st-small-label {
        color: #64748b;
        font-size: 10.5px;
        font-weight: 750;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }

    .lampiran-st-page .lampiran-st-row--parameters {
        margin-top: 2px;
        padding-top: 11px;
        border-top: 1px dashed #e4ebf5;
    }

    /* --- Tombol --- */
    .lampiran-st-page .lampiran-st-button {
        border-color: #d7e0ec;
        border-radius: 10px;
        color: #334155;
    }

    .lampiran-st-page .lampiran-st-button:hover:not(:disabled) {
        border-color: #bcd0ea;
        background: #f5f9ff;
    }

    .lampiran-st-page .lampiran-st-button--primary {
        border-color: #1d4ed8;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
    }

    .lampiran-st-page .lampiran-st-button--primary:hover:not(:disabled) {
        background: linear-gradient(135deg, #1d4ed8, #1e40af);
        box-shadow: 0 10px 22px rgba(37, 99, 235, 0.30);
    }

    .lampiran-st-page .lampiran-st-button--success {
        border-color: #34d399;
        background: linear-gradient(135deg, #059669, #047857);
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(5, 150, 105, 0.22);
    }

    .lampiran-st-page .lampiran-st-button--success:hover:not(:disabled) {
        border-color: #047857;
        background: linear-gradient(135deg, #047857, #036049);
        color: #ffffff;
        box-shadow: 0 10px 22px rgba(5, 150, 105, 0.28);
    }

    /* --- Pilihan Check All / Uncheck All --- */
    .lampiran-st-page .lampiran-st-choicebox label {
        border-color: #e6ecf5;
        border-radius: 10px;
        background: #fbfdff;
        color: #475569;
        transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    }

    .lampiran-st-page .lampiran-st-choicebox label:hover {
        border-color: #cfe0fb;
        background: #f5f9ff;
    }

    .lampiran-st-page .lampiran-st-choicebox label:has(input:checked) {
        border-color: #93c5fd;
        background: linear-gradient(180deg, #eff6ff, #dbeafe);
        color: #1e40af;
        font-weight: 750;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.12);
    }

    .lampiran-st-page .lampiran-st-choicebox input[type="radio"] {
        width: 16px;
        height: 16px;
        accent-color: #2563eb;
    }

    /* --- Checkbox baris tabel --- */
    .lampiran-st-page .lampiran-st-table input[type="checkbox"] {
        width: 17px;
        height: 17px;
        border: 1.5px solid #b6c0cf;
        border-radius: 5px;
        background-color: #ffffff;
        background-position: center;
        background-repeat: no-repeat;
        background-size: 12px 12px;
        -webkit-appearance: none;
        appearance: none;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
    }

    .lampiran-st-page .lampiran-st-table input[type="checkbox"]:hover {
        border-color: #2563eb;
    }

    .lampiran-st-page .lampiran-st-table input[type="checkbox"]:checked {
        border-color: #2563eb;
        background-color: #2563eb;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%23ffffff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3.2 8.2l3 3.1 6.6-7'/%3E%3C/svg%3E");
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.28);
    }

    /* --- Area tabel --- */
    .lampiran-st-page .lampiran-st-tablearea {
        padding: 18px 20px 20px;
        background: linear-gradient(180deg, #f7faff 0%, #f4f7fc 100%);
    }

    .lampiran-st-page .lampiran-st-tablebar h2 {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        color: #0f172a;
        font-size: 15.5px;
        font-weight: 800;
        letter-spacing: -0.15px;
    }

    .lampiran-st-page .lampiran-st-tablebar h2::before {
        display: inline-block;
        width: 4px;
        height: 18px;
        border-radius: 3px;
        background: linear-gradient(180deg, #2563eb, #1d4ed8);
        content: "";
    }

    .lampiran-st-page .lampiran-st-meta-badge {
        border-color: #cfe0fb;
        background: linear-gradient(180deg, #ffffff, #f5f9ff);
        color: #334155;
        font-weight: 700;
    }

    .lampiran-st-page .lampiran-st-meta-badge:last-child {
        border-color: #bbf7d0;
        background: linear-gradient(180deg, #ffffff, #f0fdf5);
    }

    .lampiran-st-page .lampiran-st-meta-badge:last-child strong {
        color: #047857;
    }

    .lampiran-st-page #main-display {
        border-color: #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
    }

    .lampiran-st-page #main-display::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .lampiran-st-page #main-display::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .lampiran-st-page #main-display::-webkit-scrollbar-thumb {
        border: 2px solid #f1f5f9;
        border-radius: 999px;
        background: #c7d5e8;
    }

    .lampiran-st-page #main-display::-webkit-scrollbar-thumb:hover {
        background: #9fb6d4;
    }

    /* Header tabel biru — samakan dengan Daftar Surat Pesanan dkk. */
    .lampiran-st-page .lampiran-st-table th {
        height: 42px;
        border-right: 1px solid #dbe3ef;
        border-bottom: 1px solid #cfdcee;
        background: linear-gradient(180deg, #eff6ff, #e5effb);
        color: #1e3a8a;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.25px;
        text-transform: uppercase;
    }

    .lampiran-st-page .lampiran-st-table td {
        border-right: 1px solid #eef2f7;
        border-bottom: 1px solid #eef2f7;
        color: #334155;
    }

    .lampiran-st-page .lampiran-st-table tbody tr:nth-child(even) td {
        background: #fbfcfe;
    }

    .lampiran-st-page .lampiran-st-table tbody tr:hover td {
        background: #f0f7ff;
    }

    .lampiran-st-page .lampiran-st-table tbody tr:hover td:first-child:not(.empty-row) {
        box-shadow: inset 3px 0 0 #2563eb;
    }

    .lampiran-st-page .lampiran-st-table .name-cell {
        color: #0f172a;
        font-weight: 700;
    }

    .lampiran-st-page .lampiran-st-table .email-cell {
        color: #475569;
    }

    /* --- Empty state --- */
    .lampiran-st-page .lampiran-st-empty {
        min-height: 280px;
        margin: 18px;
        padding: 34px 26px;
        border: 1.5px dashed #cfe0fb;
        border-radius: 14px;
        background:
            radial-gradient(560px 180px at 50% 0%, rgba(37, 99, 235, 0.07), transparent 70%),
            #fbfdff;
        color: #64748b;
    }

    .lampiran-st-page .lampiran-st-empty-icon {
        width: 56px;
        height: 56px;
        border: 1px solid #cfe0fb;
        border-radius: 18px;
        background: linear-gradient(180deg, #eff6ff, #dbeafe);
        color: #1d4ed8;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.14);
        font-size: 21px;
    }

    .lampiran-st-page .lampiran-st-empty strong {
        color: #0f172a;
        font-size: 14.5px;
        font-weight: 800;
    }

    .lampiran-st-page .lampiran-st-empty span:last-child {
        color: #64748b;
        font-size: 12px;
    }

    /* --- Loading & alert --- */
    .lampiran-st-page .lampiran-st-loading {
        border-color: #cfe0fb;
        border-radius: 999px;
        background: linear-gradient(180deg, #eff6ff, #dbeafe);
        color: #1e40af;
        font-size: 12px;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
    }

    .lampiran-st-page .lampiran-st-alert {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.08);
    }

    /* --- Modal lookup --- */
    .lampiran-st-page .lampiran-st-modal-dialog {
        border-radius: 16px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
    }

    .lampiran-st-page .lampiran-st-modal-header {
        border-bottom: 1px solid #dbe3ef;
        background: linear-gradient(180deg, #eff6ff, #e5effb);
        color: #1e3a8a;
        font-weight: 800;
    }

    .lampiran-st-page .lampiran-st-modal-table th {
        border-bottom: 1px solid #cfdcee;
        background: linear-gradient(180deg, #eff6ff, #e5effb);
        color: #1e3a8a;
        font-weight: 800;
    }

    .lampiran-st-page .lampiran-st-modal-table tbody tr:hover td {
        background: #f0f7ff;
    }

    .lampiran-st-page .lampiran-st-criteria-label {
        color: #64748b;
    }

    /* --- Notice dialog --- */
    .lampiran-st-page .lampiran-st-notice-dialog {
        border-radius: 16px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.30);
    }

    .lampiran-st-page .lampiran-st-notice-button {
        border-radius: 10px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24);
    }

    .lampiran-st-page .lampiran-st-notice-button:hover {
        background: linear-gradient(135deg, #1d4ed8, #1e40af);
    }

    @media (max-width: 680px) {
        .lampiran-st-page .lampiran-st-empty {
            margin: 12px;
            padding: 26px 16px;
        }
    }
</style>

<div class="lampiran-st-page" autocomplete="off">
    <input type="hidden" id="perusahaan" value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}">
    <input type="hidden" id="endpoint_base" value="{{ url('serahterima/cetak_lampiran_undangan_st') }}">

    <div id="lampiranSTModal" class="lampiran-st-modal" aria-hidden="true">
        <div class="lampiran-st-modal-dialog">
            <div class="lampiran-st-modal-header">
                <span id="modal-title">Lookup</span>
                <button type="button" class="lampiran-st-button" onclick="toggleLampiranModal(false)">Tutup</button>
            </div>
            <div class="lampiran-st-modal-body" id="modal-content"></div>
        </div>
    </div>


    <div id="lampiranNotice" class="lampiran-st-notice is-warning" aria-hidden="true">
        <div class="lampiran-st-notice-dialog" role="dialog" aria-modal="true" aria-labelledby="lampiranNoticeTitle">
            <div class="lampiran-st-notice-top">
                <div class="lampiran-st-notice-icon" id="lampiranNoticeIcon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div>
                    <div class="lampiran-st-notice-title" id="lampiranNoticeTitle">Perhatian</div>
                    <div class="lampiran-st-notice-subtitle" id="lampiranNoticeSubtitle">Lengkapi data terlebih dahulu</div>
                </div>
            </div>
            <div class="lampiran-st-notice-body" id="lampiranNoticeMessage"></div>
            <div class="lampiran-st-notice-actions">
                <button type="button" class="lampiran-st-notice-button" id="lampiranNoticeButton" onclick="closeLampiranNotice()">Mengerti</button>
            </div>
        </div>
    </div>

    <section class="lampiran-st-page-hero">
        <div class="lampiran-st-page-heading">
            <div class="lampiran-st-page-heading-icon sertipikat-style-heading-icon" aria-hidden="true">◈</div>
            <h1>Lampiran Surat Undangan Serah Terima</h1>
        </div>

        <div class="lampiran-st-page-unit-badge" title="Unit aktif">
            <i class="fas fa-building"></i>
            <span>Unit: <strong>{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}</strong></span>
        </div>
    </section>

    <div class="lampiran-st-frame">
        <div class="lampiran-st-toolbar">
            <div class="lampiran-st-formrow">
                <div class="lampiran-st-box lampiran-st-fields">
                    <div class="lampiran-st-filter-head">
                        <div class="lampiran-st-section-label">
                            <i class="fas fa-filter"></i>
                            <span>Pilih Unit</span>
                        </div>
                        <div class="lampiran-st-filter-actions">
                            <button type="button" id="btn-load-data" class="lampiran-st-button lampiran-st-button--primary" onclick="getLampiranData()" title="OK">
                                <i class="fas fa-check"></i>
                                <span>OK</span>
                            </button>
                            <button type="button" id="btn-reload-data" class="lampiran-st-button" onclick="getLampiranData()" title="Proses">
                                <i class="fas fa-cog"></i>
                                <span>Proses</span>
                            </button>
                        </div>
                    </div>

                    <div class="lampiran-st-row">
                        <label class="lampiran-st-label" for="clusterentry">Cluster</label>
                        <input type="hidden" id="sektor" value="">
                        <input type="text" id="clusterentry" class="lampiran-st-input" style="width:130px;" value="" readonly autocomplete="off" placeholder="Cluster">
                        <button type="button" class="lampiran-st-lookup" onclick="getSektorModal()" title="Cari Cluster" aria-label="Cari Cluster">
                            <i class="fas fa-search"></i>
                        </button>
                        <span id="cluster-description" class="lampiran-st-required" aria-live="polite">*) Harus diisi</span>
                    </div>

                    <div class="lampiran-st-row">
                        <label class="lampiran-st-label" for="blok_awal">Blok</label>
                        <input type="text" id="blok_awal" class="lampiran-st-input" style="width:112px;" maxlength="40" autocomplete="off" placeholder="Blok awal">
                        <button type="button" class="lampiran-st-lookup" data-blok-lookup="1" onclick="getBlokModal('awal')" title="Cari Blok awal" aria-label="Cari Blok awal">
                            <i class="fas fa-search"></i>
                        </button>
                        <span class="lampiran-st-small-label">s.d.</span>
                        <input type="text" id="blok_akhir" class="lampiran-st-input" style="width:112px;" maxlength="40" autocomplete="off" placeholder="Blok akhir">
                        <button type="button" class="lampiran-st-lookup" data-blok-lookup="1" onclick="getBlokModal('akhir')" title="Cari Blok akhir" aria-label="Cari Blok akhir">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                    <div class="lampiran-st-row lampiran-st-row--parameters">
                        <label class="lampiran-st-label" for="urut">Parameter</label>
                        <div class="lampiran-st-parameter-item">
                            <label class="lampiran-st-small-label" for="urut">Undangan Ke</label>
                            <input type="number" id="urut" class="lampiran-st-number" style="width:84px;" value="1" min="1" max="99" autocomplete="off" title="Nomor urutan undangan">
                        </div>
                        <div class="lampiran-st-parameter-item">
                            <label class="lampiran-st-small-label" for="ipl">IPL (m2)</label>
                            <input type="number" id="ipl" class="lampiran-st-number" style="width:96px;" value="0" min="0" step="0.01" autocomplete="off">
                        </div>
                        <div class="lampiran-st-parameter-item">
                            <label class="lampiran-st-small-label" for="sf">SF (m2)</label>
                            <input type="number" id="sf" class="lampiran-st-number" style="width:96px;" value="0" min="0" step="0.01" autocomplete="off">
                        </div>
                        <div class="lampiran-st-parameter-item">
                            <label class="lampiran-st-small-label" for="abo">ABO</label>
                            <input type="number" id="abo" class="lampiran-st-number" style="width:96px;" value="0" min="0" step="0.01" autocomplete="off">
                        </div>
                    </div>
                </div>


                <div class="lampiran-st-choicebox">
                    <div class="lampiran-st-section-label">
                        <i class="fas fa-check-square"></i>
                        <span>Pilih</span>
                    </div>
                    <label title="Check All">
                        <input type="radio" name="check_mode" value="all" onclick="setAllRows(true)">
                        <span>Check All</span>
                    </label>
                    <label title="Uncheck All">
                        <input type="radio" name="check_mode" value="none" checked onclick="setAllRows(false)">
                        <span>Uncheck All</span>
                    </label>
                </div>

                <div class="lampiran-st-emailbox">
                    <div class="lampiran-st-section-label">
                        <i class="fas fa-envelope"></i>
                        <span>Email</span>
                    </div>
                    <button type="button" class="lampiran-st-button lampiran-st-button--success" onclick="sendSelectedEmail()" title="Kirim email ke data yang dipilih">
                        <i class="fas fa-paper-plane"></i>
                        <span>Kirim Email</span>
                    </button>
                    <button type="button" class="lampiran-st-button" onclick="showEmailQueue()" title="Lihat Antrian Email">
                        <i class="fas fa-list-ul"></i>
                        <span>Lihat Antrian Email</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="lampiran-st-tablearea">
            <div class="lampiran-st-tablebar">
                <div>
                    <h2>Daftar Lampiran Undangan</h2>
                </div>
                <div class="lampiran-st-tablemeta">
                    <span class="lampiran-st-meta-badge"><strong id="result-count">0</strong> data</span>
                    <span class="lampiran-st-meta-badge"><strong id="selected-count">0</strong> dipilih</span>
                </div>
            </div>

            <div id="loading-info" class="lampiran-st-loading" style="display:none;">
                <i class="fas fa-spinner fa-spin"></i>
                Memproses data lampiran undangan.
            </div>

            <div id="main-display">
                <table class="lampiran-st-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Pilih</th>
                            <th>Click</th>
                            <th>Blok</th>
                            <th>Nomor</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>IPL</th>
                            <th>SF</th>
                            <th>ABO</th>
                            <th>Tunggakan</th>
                            <th>Denda</th>
                            <th>Flag Cetak</th>
                            <th>Tgl Cetak</th>
                            <th>User Cetak</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="15" class="empty-row"><div class="lampiran-st-empty"><span class="lampiran-st-empty-icon"><i class="fas fa-table"></i></span><strong>Belum ada data</strong></div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
    // LOOKUP_BLOK_FILTER_VERSION=V2-20260811-AUTO-SORT-DYNAMIC-SEARCH
    var blokLookupTarget = 'awal';
    var blokLookupXhr = null;
    var blokLookupCache = {};
    var blokLookupCacheTtl = 30000;
    var blokPembeliCache = {};
    var blokPembeliXhrs = [];
    var blokLookupGeneration = 0;
    var blokCriteriaField = 'BLOK_NOMOR';
    var blokCriteriaMode = 'filter';
    var blokCriteriaSearchIndex = -1;

    $(document).ready(function () {
        resetLampiranFormOnPageLoad();
        bindUppercaseInputs();
        bindLampiranShortcuts();

        $(document).on('change', '.row-check', function () {
            updateSelectedCount();
        });
    });

    $(window).on('pageshow', function () {
        resetLampiranFormOnPageLoad();
    });

    function resetLampiranFormOnPageLoad() {
        abortAllBlokRequests();

        blokLookupTarget = 'awal';
        blokLookupCache = {};
        blokPembeliCache = {};
        blokLookupGeneration++;
        blokCriteriaField = 'BLOK_NOMOR';
        blokCriteriaMode = 'filter';
        blokCriteriaSearchIndex = -1;

        $('#sektor').val('');
        $('#clusterentry').val('').attr('title', '');
        setClusterDescription('', '');
        $('#blok_awal, #blok_akhir').val('');
        $('#urut').val('1');
        $('#ipl, #sf, #abo').val('0');

        $('input[name="check_mode"][value="none"]').prop('checked', true);
        $('input[name="check_mode"][value="all"]').prop('checked', false);

        $('#loading-info').hide();
        toggleLampiranModal(false);
        closeLampiranNotice();
        renderInitialLampiranTable();
        updateTableMeta(0);
        setProcessingState(false);
        setBlokLookupState(false);
    }

    function renderInitialLampiranTable() {
        var html = '';

        html += '<table class="lampiran-st-table">';
        html += '<thead><tr>';
        html += '<th>No.</th>';
        html += '<th>Pilih</th>';
        html += '<th>Click</th>';
        html += '<th>Blok</th>';
        html += '<th>Nomor</th>';
        html += '<th>Nama</th>';
        html += '<th>Email</th>';
        html += '<th>IPL</th>';
        html += '<th>SF</th>';
        html += '<th>ABO</th>';
        html += '<th>Tunggakan</th>';
        html += '<th>Denda</th>';
        html += '<th>Flag Cetak</th>';
        html += '<th>Tgl Cetak</th>';
        html += '<th>User Cetak</th>';
        html += '</tr></thead><tbody>';
        html += '<tr>';
        html += '<td colspan="15" class="empty-row"><div class="lampiran-st-empty"><span class="lampiran-st-empty-icon"><i class="fas fa-table"></i></span><strong>Belum ada data</strong></div></td>';
        html += '</tr>';
        html += '</tbody></table>';

        $('#main-display').html(html);
    }

    function bindUppercaseInputs() {
        $('#blok_awal, #blok_akhir').on('input', function () {
            $(this).val(String($(this).val() || '').toUpperCase());
        });
    }

    function bindLampiranShortcuts() {
        $('#clusterentry, #blok_awal, #blok_akhir, #urut, #ipl, #sf, #abo').on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                getLampiranData();
            }
        });
    }

    function updateTableMeta(total) {
        if (total !== undefined && total !== null) {
            $('#result-count').text(Number(total) || 0);
        }

        updateSelectedCount();
    }

    function updateSelectedCount() {
        $('#selected-count').text($('.row-check:checked').length);
    }

    function setProcessingState(isProcessing) {
        var $buttons = $('#btn-load-data, #btn-reload-data');

        $buttons.prop('disabled', !!isProcessing);

        if (isProcessing) {
            $('#btn-load-data').html('<i class="fas fa-spinner fa-spin"></i><span>Proses...</span>');
        } else {
            $('#btn-load-data').html('<i class="fas fa-check"></i><span>OK</span>');
        }
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

    function formatDateIndo(value) {
        if (!value) {
            return '';
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

    function formatNumber(value) {
        if (value === null || value === undefined || value === '') {
            return '';
        }

        var number = Number(value);

        if (isNaN(number)) {
            return String(value);
        }

        return number.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    function normalizeRows(payload) {
        if (Array.isArray(payload)) {
            return payload;
        }

        if (payload && Array.isArray(payload.data)) {
            return payload.data;
        }

        if (payload && Array.isArray(payload.rows)) {
            return payload.rows;
        }

        return [];
    }

    function valueOrBlank(value) {
        return value === null || value === undefined ? '' : value;
    }

    function getRowValue(item, upperKey, lowerKey) {
        lowerKey = lowerKey || upperKey.toLowerCase();

        if (!item) {
            return '';
        }

        return item[upperKey] !== undefined && item[upperKey] !== null
            ? item[upperKey]
            : (
                item[lowerKey] !== undefined && item[lowerKey] !== null
                    ? item[lowerKey]
                    : ''
            );
    }

    function toggleLampiranModal(show) {
        $('#lampiranSTModal')
            .toggleClass('show', !!show)
            .attr('aria-hidden', show ? 'false' : 'true');
    }


    function showLampiranNotice(message, type, title, subtitle) {
        type = type || 'warning';

        var config = {
            warning: {
                title: title || 'Perhatian',
                subtitle: subtitle || 'Lengkapi data terlebih dahulu',
                icon: 'fas fa-exclamation-circle'
            },
            error: {
                title: title || 'Terjadi Kendala',
                subtitle: subtitle || 'Silakan periksa kembali prosesnya',
                icon: 'fas fa-times-circle'
            },
            info: {
                title: title || 'Informasi',
                subtitle: subtitle || 'Detail fitur',
                icon: 'fas fa-info-circle'
            },
            success: {
                title: title || 'Berhasil',
                subtitle: subtitle || 'Proses selesai',
                icon: 'fas fa-check-circle'
            }
        };

        var selected = config[type] || config.warning;

        $('#lampiranNotice')
            .removeClass('is-warning is-error is-info is-success')
            .addClass('show is-' + type)
            .attr('aria-hidden', 'false');

        $('#lampiranNoticeIcon').html('<i class="' + selected.icon + '"></i>');
        $('#lampiranNoticeTitle').text(selected.title);
        $('#lampiranNoticeSubtitle').text(selected.subtitle);
        $('#lampiranNoticeMessage').text(message || 'Terjadi kesalahan.');

        setTimeout(function () {
            $('#lampiranNoticeButton').trigger('focus');
        }, 80);
    }

    function closeLampiranNotice() {
        $('#lampiranNotice')
            .removeClass('show')
            .attr('aria-hidden', 'true');
    }

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            closeLampiranNotice();
        }
    });

    $('#lampiranNotice').on('click', function (event) {
        if (event.target === this) {
            closeLampiranNotice();
        }
    });

    function insertModal(title, content, options) {
        options = options || {};

        var finalHtml = '';

        if (options.customTopHtml) {
            finalHtml += options.customTopHtml;
        }

        if (options.hideDefaultSearch !== true) {
            finalHtml += '<input type="text" class="lampiran-st-modal-search"';
            finalHtml += ' placeholder="Cari data." onkeyup="filterModalRows(this.value)">';
        }

        finalHtml += '<div class="lampiran-st-modal-table-wrap">';
        finalHtml += content;
        finalHtml += '</div>';

        $('#lampiranSTModal .lampiran-st-modal-dialog')
            .toggleClass('lampiran-st-modal-dialog--wide', options.wide === true);

        $('#modal-title').text(title);
        $('#modal-content').html(finalHtml);
        toggleLampiranModal(true);
    }

    function filterModalRows(keyword) {
        var text = String(keyword || '').toLowerCase().trim();

        $('.lampiran-st-modal-table tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(text) !== -1);
        });
    }

    function getBlokCriteriaColumns() {
        return [
            { key: 'BLOK_NOMOR', label: 'Blok Nomor' },
            { key: 'PPJB_ID', label: 'PPJB ID' },
            { key: 'NAMA_PEMBELI', label: 'Nama Pembeli' },
            { key: 'NO_PPJB', label: 'No PPJB' },
            { key: 'TGL_PPJB', label: 'Tgl PPJB' },
            { key: 'TIPE', label: 'Tipe' },
            { key: 'LOKASI', label: 'Lokasi' },
            { key: 'STOK_ID', label: 'Stok ID' },
            { key: 'NO_VIRTUAL_ACC', label: 'No Virtual Acc' },
            { key: 'DESKRIPSI', label: 'Sektor Deskripsi' },
            { key: 'BLOK', label: 'Stok Blok' },
            { key: 'NOMOR', label: 'Stok Nomor' },
            { key: 'KD_JENIS', label: 'KD Jenis' },
            { key: 'KD_TIPE', label: 'KD Tipe' },
            { key: 'JENIS', label: 'Jenis' },
            { key: 'EMAIL', label: 'Nasabah Email' },
            { key: 'LUAS_SEMI_GROSS', label: 'Stok Luas Semi Gross' },
            { key: 'FLAG_SMILE', label: 'PPJB Flag Smile' },
            { key: 'TGL_PO_SMILE', label: 'PPJB Tgl PO Smile' },
            { key: 'LUAS_BANGUNAN', label: 'PPJB Luas Bangunan' }
        ];
    }

    function buildBlokCriteriaPanel() {
        var columns = getBlokCriteriaColumns();
        var html = '';

        html += '<div id="blokCriteriaPanel" class="lampiran-st-blok-criteria' +
            (blokCriteriaMode === 'search' ? ' is-search-mode' : '') + '">';

        /*
         * MODE FILTER
         * Memilih kolom langsung mengurutkan tabel.
         * Criteria hanya opsional untuk mempersempit isi kolom aktif.
         */
        html += '<div class="lampiran-st-criteria-group lampiran-st-criteria-filter-field">';
        html += '<span class="lampiran-st-criteria-label">Column Criteria</span>';
        html += '<div class="lampiran-st-criteria-column-wrap">';
        html += '<select id="blokCriteriaField" class="lampiran-st-criteria-select" ' +
            'onchange="changeBlokCriteriaField(this.value)">';

        $.each(columns, function (index, column) {
            var selected = column.key === blokCriteriaField ? ' selected' : '';

            html += '<option value="' + escapeHtml(column.key) + '"' + selected + '>' +
                escapeHtml(column.label) +
                '</option>';
        });

        html += '</select>';
        html += '<span id="blokCriteriaCounter" class="lampiran-st-criteria-counter">1 of 20</span>';
        html += '</div>';
        html += '</div>';

        html += '<div class="lampiran-st-criteria-group lampiran-st-criteria-filter-value">';
        html += '<span class="lampiran-st-criteria-label">Criteria</span>';
        html += '<input type="text" id="blokCriteriaKeyword" class="lampiran-st-criteria-input" ' +
            'placeholder="Maukkan Criteria" autocomplete="off" ' +
            'oninput="applyBlokFilterLive()">';
        html += '</div>';

        /*
         * MODE SEARCH
         * Satu field membentang dari posisi paling kiri Column Criteria
         * sampai ujung kanan Criteria. Pemilihan 20 kolom disembunyikan.
         */
        html += '<div class="lampiran-st-criteria-group lampiran-st-criteria-search-group">';
        html += '<span class="lampiran-st-criteria-label">Search</span>';
        html += '<input type="text" id="blokSearchKeyword" class="lampiran-st-criteria-search-input" ' +
            'placeholder="Cari data pada seluruh tabel..." autocomplete="off" ' +
            'oninput="applyBlokSearchLive()">';
        html += '</div>';

        html += '<div class="lampiran-st-criteria-group">';
        html += '<span class="lampiran-st-criteria-label">Mode</span>';
        html += '<div class="lampiran-st-criteria-mode">';
        html += '<label><input type="radio" name="blok_criteria_mode" value="search"' +
            (blokCriteriaMode === 'search' ? ' checked' : '') +
            ' onchange="changeBlokCriteriaMode(this.value)"> Search</label>';
        html += '<label><input type="radio" name="blok_criteria_mode" value="filter"' +
            (blokCriteriaMode === 'filter' ? ' checked' : '') +
            ' onchange="changeBlokCriteriaMode(this.value)"> Filter</label>';
        html += '</div>';
        html += '</div>';

        html += '<div class="lampiran-st-criteria-actions">';
        html += '<button type="button" class="lampiran-st-button" onclick="resetBlokCriteria()">Reset</button>';
        html += '</div>';

        html += '</div>';

        return html;
    }

    function changeBlokCriteriaField(value) {
        blokCriteriaField = String(value || 'BLOK_NOMOR');
        blokCriteriaSearchIndex = -1;

        syncBlokCriteriaCounter();

        /*
         * Tidak menunggu tombol OK.
         * Begitu field berubah, tabel langsung diurutkan berdasarkan kolom aktif.
         */
        applyBlokFilterLive();
    }

    function changeBlokCriteriaMode(value) {
        blokCriteriaMode = value === 'search' ? 'search' : 'filter';
        blokCriteriaSearchIndex = -1;

        syncBlokCriteriaModeUI();

        $('.lampiran-st-modal-table--blok tbody tr')
            .removeClass('blok-criteria-hit');

        if (blokCriteriaMode === 'search') {
            /*
             * Saat Search aktif, selection filter + criteria hilang.
             * Search bekerja terhadap seluruh kolom dan langsung aktif saat mengetik.
             */
            $('.lampiran-st-modal-table--blok tbody tr[data-lookup-row="1"]').show();
            applyBlokSearchLive();

            setTimeout(function () {
                $('#blokSearchKeyword').trigger('focus');
            }, 20);
        } else {
            /*
             * Saat kembali ke Filter, langsung gunakan sort/filter terakhir.
             */
            applyBlokFilterLive();

            setTimeout(function () {
                $('#blokCriteriaField').trigger('focus');
            }, 20);
        }
    }

    function syncBlokCriteriaModeUI() {
        var $panel = $('#blokCriteriaPanel');

        if (!$panel.length) {
            return;
        }

        $panel.toggleClass('is-search-mode', blokCriteriaMode === 'search');
    }

    function syncBlokCriteriaCounter() {
        var columns = getBlokCriteriaColumns();
        var currentIndex = 0;

        $.each(columns, function (index, column) {
            if (column.key === blokCriteriaField) {
                currentIndex = index;
                return false;
            }
        });

        $('#blokCriteriaCounter').text((currentIndex + 1) + ' of ' + columns.length);
    }

    function getBlokCriteriaCell($row, field) {
        return $row.find('td[data-filter-field="' + field + '"]').first();
    }

    function getBlokCriteriaCellText($row, field) {
        var $cell = getBlokCriteriaCell($row, field);

        return String(
            $cell.attr('data-sort-value') !== undefined
                ? $cell.attr('data-sort-value')
                : $cell.text()
        ).trim();
    }

    function normalizeBlokSortValue(value, field) {
        var text = String(value === null || value === undefined ? '' : value).trim();

        var numericFields = {
            LUAS_SEMI_GROSS: true,
            LUAS_BANGUNAN: true
        };

        var dateFields = {
            TGL_PPJB: true,
            TGL_PO_SMILE: true
        };

        if (numericFields[field]) {
            var numberText = text
                .replace(/\./g, '')
                .replace(',', '.')
                .replace(/[^0-9.+-]/g, '');

            var number = parseFloat(numberText);

            return {
                type: 'number',
                value: isNaN(number) ? Number.POSITIVE_INFINITY : number
            };
        }

        if (dateFields[field]) {
            var dateValue = Date.parse(text);

            if (isNaN(dateValue)) {
                var indo = text.match(/^(\d{2})-(\d{2})-(\d{4})/);

                if (indo) {
                    dateValue = Date.parse(
                        indo[3] + '-' + indo[2] + '-' + indo[1]
                    );
                }
            }

            return {
                type: 'number',
                value: isNaN(dateValue) ? Number.POSITIVE_INFINITY : dateValue
            };
        }

        return {
            type: 'text',
            value: text.toLocaleLowerCase('id-ID')
        };
    }

    function compareBlokLookupValues(aValue, bValue, field) {
        var a = normalizeBlokSortValue(aValue, field);
        var b = normalizeBlokSortValue(bValue, field);

        if (a.type === 'number' && b.type === 'number') {
            if (a.value < b.value) return -1;
            if (a.value > b.value) return 1;
            return 0;
        }

        return String(a.value).localeCompare(
            String(b.value),
            'id-ID',
            {
                numeric: true,
                sensitivity: 'base'
            }
        );
    }

    function sortBlokRowsByField(field) {
        var $tbody = $('.lampiran-st-modal-table--blok tbody');
        var rows = $tbody.find('tr[data-lookup-row="1"]').get();

        rows.sort(function (rowA, rowB) {
            var $a = $(rowA);
            var $b = $(rowB);

            var aValue = getBlokCriteriaCellText($a, field);
            var bValue = getBlokCriteriaCellText($b, field);

            var compared = compareBlokLookupValues(aValue, bValue, field);

            if (compared !== 0) {
                return compared;
            }

            /*
             * Stable fallback: Blok/Nomor menjaga urutan konsisten
             * bila nilai kolom yang dipilih sama.
             */
            return compareBlokLookupValues(
                getBlokCriteriaCellText($a, 'BLOK_NOMOR'),
                getBlokCriteriaCellText($b, 'BLOK_NOMOR'),
                'BLOK_NOMOR'
            );
        });

        $.each(rows, function (index, row) {
            $tbody.append(row);
        });
    }

    function applyBlokFilterLive() {
        if (blokCriteriaMode !== 'filter') {
            return;
        }

        var field = String(
            $('#blokCriteriaField').val()
            || blokCriteriaField
            || 'BLOK_NOMOR'
        );

        var keyword = String(
            $('#blokCriteriaKeyword').val() || ''
        ).toLocaleLowerCase('id-ID').trim();

        blokCriteriaField = field;

        /*
         * POIN UTAMA V2:
         * sort SELALU dijalankan walaupun criteria kosong.
         */
        sortBlokRowsByField(field);

        var $rows = $('.lampiran-st-modal-table--blok tbody tr[data-lookup-row="1"]');

        $rows
            .removeClass('blok-criteria-hit')
            .each(function () {
                var $row = $(this);

                if (!keyword) {
                    $row.show();
                    return;
                }

                var cellText = getBlokCriteriaCellText($row, field)
                    .toLocaleLowerCase('id-ID');

                $row.toggle(cellText.indexOf(keyword) !== -1);
            });
    }

    function applyBlokSearchLive() {
        if (blokCriteriaMode !== 'search') {
            return;
        }

        var keyword = String(
            $('#blokSearchKeyword').val() || ''
        ).toLocaleLowerCase('id-ID').trim();

        var $rows = $('.lampiran-st-modal-table--blok tbody tr[data-lookup-row="1"]');

        $rows
            .removeClass('blok-criteria-hit')
            .each(function () {
                var $row = $(this);

                if (!keyword) {
                    $row.show();
                    return;
                }

                /*
                 * Search mode mencari seluruh isi baris,
                 * bukan hanya salah satu dari 20 field.
                 */
                var rowText = String($row.text() || '')
                    .toLocaleLowerCase('id-ID');

                $row.toggle(rowText.indexOf(keyword) !== -1);
            });
    }

    function resetBlokCriteria() {
        blokCriteriaField = 'BLOK_NOMOR';
        blokCriteriaMode = 'filter';
        blokCriteriaSearchIndex = -1;

        $('#blokCriteriaField').val(blokCriteriaField);
        $('#blokCriteriaKeyword').val('');
        $('#blokSearchKeyword').val('');

        $('input[name="blok_criteria_mode"][value="filter"]').prop('checked', true);
        $('input[name="blok_criteria_mode"][value="search"]').prop('checked', false);

        syncBlokCriteriaModeUI();
        syncBlokCriteriaCounter();

        $('.lampiran-st-modal-table--blok tbody tr')
            .show()
            .removeClass('blok-criteria-hit');

        /*
         * Kondisi awal desktop: urut berdasarkan Blok/Nomor.
         */
        applyBlokFilterLive();
    }

    function refreshActiveBlokFilter() {
        /*
         * Nama Pembeli / Email dimuat asynchronous.
         * Setelah batch masuk, sort/filter/search aktif dihitung ulang agar
         * posisi tabel langsung benar tanpa user menekan tombol apa pun.
         */
        if (blokCriteriaMode === 'search') {
            applyBlokSearchLive();
        } else {
            applyBlokFilterLive();
        }
    }

    function getEndpoint(path) {
        var base = String($('#endpoint_base').val() || '{{ url()->current() }}');
        return base.replace(/\/+$/, '') + '/' + String(path || '').replace(/^\/+/, '');
    }

    /**
     * Mengubah teks di sebelah field Cluster.
     * - Belum dipilih: tampilkan "*) Harus diisi".
     * - Sudah dipilih: tampilkan DESKRIPSI Cluster, misalnya
     *   "VIOLA RESIDENCE", seperti perilaku aplikasi desktop.
     */
    function setClusterDescription(kode, deskripsi) {
        var clusterCode = String(kode || '').trim();
        var clusterDescription = String(deskripsi || '').trim();
        var $label = $('#cluster-description');

        if (!$label.length) {
            return;
        }

        if (!clusterCode) {
            $label
                .removeClass('is-cluster-description')
                .text('*) Harus diisi')
                .attr('title', 'Cluster wajib dipilih');
            return;
        }

        var displayText = clusterDescription || clusterCode;

        $label
            .addClass('is-cluster-description')
            .text(displayText)
            .attr('title', displayText);
    }

    function getSektorModal() {
        if (isBlokLookupRunning()) {
            showLampiranNotice(
                'Pencarian blok masih berjalan. Tunggu proses tersebut selesai sebelum mengganti Cluster.',
                'info',
                'Pencarian Blok Masih Aktif'
            );
            return;
        }

        var perusahaan = String($('#perusahaan').val() || '').trim();

        if (!perusahaan) {
            showLampiranNotice('Unit/perusahaan belum tersedia.', 'warning');
            return;
        }

        $.ajax({
            method: 'POST',
            url: getEndpoint('get_sektor'),
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            data: {
                _token: '{{ csrf_token() }}',
                perusahaan: perusahaan
            },
            success: function (response) {
                var rows = normalizeRows(response);
                var html = '';

                html += '<table class="lampiran-st-modal-table">';
                html += '<thead><tr>';
                html += '<th>Kode Cluster</th>';
                html += '<th>Deskripsi</th>';
                html += '<th>Perusahaan</th>';
                html += '</tr></thead><tbody>';


                $.each(rows, function (index, item) {
                    var kode = getRowValue(item, 'KD_SEKTOR', 'kd_sektor');
                    var deskripsi = getRowValue(item, 'DESKRIPSI', 'deskripsi');
                    var kdPerusahaan = getRowValue(item, 'KD_PERUSAHAAN', 'kd_perusahaan') || perusahaan;

                    html += '<tr onclick="addSektor(\'' + escapeJs(kode) + '\', \'' + escapeJs(deskripsi) + '\')">';
                    html += '<td>' + escapeHtml(kode) + '</td>';
                    html += '<td>' + escapeHtml(deskripsi) + '</td>';
                    html += '<td>' + escapeHtml(kdPerusahaan) + '</td>';
                    html += '</tr>';
                });

                if (rows.length < 1) {
                    html += '<tr><td colspan="3" style="text-align:center;padding:24px;">Data Cluster tidak ditemukan.</td></tr>';
                }

                html += '</tbody></table>';

                insertModal('Pilih Cluster', html);
            },
            error: function (xhr) {
                console.log(xhr ? xhr.responseText : '');
                showLampiranNotice(getAjaxMessage(xhr, 'Gagal mengambil data Cluster.'), 'error', 'Data Cluster Gagal Dimuat');
            }
        });
    }

    function addSektor(kode, deskripsi) {
        kode = String(kode || '').trim();

        abortAllBlokRequests();
        blokLookupCache = {};
        blokPembeliCache = {};
        blokLookupGeneration++;

        $('#sektor').val(kode);
        $('#clusterentry').val(kode);
        $('#clusterentry').attr('title', deskripsi || kode);
        setClusterDescription(kode, deskripsi);

        $('#blok_awal, #blok_akhir').val('');

        toggleLampiranModal(false);
    }

    function isClusterSelected() {
        var sektor = String($('#sektor').val() || '').trim();
        var clusterText = String($('#clusterentry').val() || '').trim();

        return sektor !== '' && sektor !== '*' && clusterText !== '';
    }

    function setBlokLookupState(isLoading) {
        var $buttons = $('[data-blok-lookup="1"]');

        $buttons.prop('disabled', !!isLoading);

        if (isLoading) {
            $buttons.attr('aria-busy', 'true');
        } else {
            $buttons.removeAttr('aria-busy');
        }
    }

    function isBlokLookupRunning() {
        return !!(blokLookupXhr && blokLookupXhr.readyState !== 4);
    }

    function abortBlokLookupRequest() {
        if (blokLookupXhr && blokLookupXhr.readyState !== 4) {
            blokLookupXhr.abort();
        }

        blokLookupXhr = null;
    }

    function abortAllBlokRequests() {
        abortBlokLookupRequest();

        $.each(blokPembeliXhrs || [], function (index, xhr) {
            if (xhr && xhr.readyState !== 4) {
                xhr.abort();
            }
        });

        blokPembeliXhrs = [];
    }

    function getBlokCacheKey(data) {
        return [
            String(data.perusahaan || '').trim().toUpperCase(),
            String(data.sektor || '').trim().toUpperCase()
        ].join('|');
    }

    function getCachedBlokRows(cacheKey) {
        var cached = blokLookupCache[cacheKey];

        if (!cached) {
            return null;
        }

        if ((Date.now() - cached.createdAt) > blokLookupCacheTtl) {
            delete blokLookupCache[cacheKey];
            return null;
        }

        return cached.rows;
    }

    function getBlokModalTitle() {
        return blokLookupTarget === 'akhir'
            ? 'Pilih Blok/Nomor Akhir'
            : 'Pilih Blok/Nomor Awal';
    }

    function showBlokLoadingModal() {
        var html = '';

        html += '<table class="lampiran-st-modal-table lampiran-st-modal-table--blok">';
        html += '<thead><tr><th>Memuat Data Blok</th></tr></thead>';
        html += '<tbody><tr>';
        html += '<td style="text-align:center;padding:42px;">';
        html += '<i class="fas fa-spinner fa-spin"></i> ';
        html += 'Daftar blok sedang disiapkan';
        html += '</td>';
        html += '</tr></tbody></table>';

        insertModal(getBlokModalTitle(), html, { wide: true });
    }

    function getBlokModal(target) {
        if (!isClusterSelected()) {
            showLampiranNotice('Silakan pilih Cluster terlebih dahulu sebelum memilih Blok atau memproses data.', 'warning', 'Cluster Belum Dipilih', 'Pilih Cluster terlebih dahulu');
            return;
        }

        blokLookupTarget = target === 'akhir' ? 'akhir' : 'awal';

        var data = getFilterData(false);

        if (!data.perusahaan) {
            showLampiranNotice('Unit/perusahaan belum tersedia.', 'warning');
            return;
        }

        /*
         * Popup blok menampilkan semua blok dalam Cluster terpilih.
         * Filter blok yang sudah dipilih user tidak membatasi isi modal.
         */
        data.blok_awal = 'A';
        data.blok_akhir = 'ZZ';
        data.lookup_all = 'Y';

        var cacheKey = getBlokCacheKey(data);
        var cachedRows = getCachedBlokRows(cacheKey);

        /*
         * Jangan membatalkan request lalu langsung membuat request baru.
         * Abort AJAX hanya menghentikan browser; query PostgreSQL pada server
         * dapat tetap berjalan. Pada php artisan serve, request berikutnya akan
         * mengantre dan terlihat sebagai Queued/Blocked selama beberapa menit.
         */
        if (isBlokLookupRunning()) {
            showLampiranNotice(
                'Permintaan data blok masih diproses. Mohon tunggu sampai proses sebelumnya selesai.',
                'info',
                'Data Blok Sedang Diproses',
                'Permintaan ganda tidak dikirim'
            );
            return;
        }

        var requestId = ++blokLookupGeneration;

        if (cachedRows !== null) {
            renderBlokModal(cachedRows, requestId);
            return;
        }

        showBlokLoadingModal();
        setBlokLookupState(true);

        blokLookupXhr = $.ajax({
            method: 'POST',
            url: getEndpoint('get_blok'),
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            timeout: 45000,
            data: data,
            success: function (response) {
                var rows = normalizeRows(response);

                blokLookupCache[cacheKey] = {
                    createdAt: Date.now(),
                    rows: rows
                };

                if (requestId !== blokLookupGeneration) {
                    return;
                }

                renderBlokModal(rows, requestId);
            },
            error: function (xhr, textStatus, errorThrown) {
                if (textStatus === 'abort') {
                    return;
                }

                console.log(xhr ? xhr.responseText : '');

                if (requestId === blokLookupGeneration) {
                    toggleLampiranModal(false);

                    var defaultMessage = textStatus === 'timeout'
                        ? 'Pencarian blok melewati batas waktu. Pastikan migration index sudah dijalankan dan tidak ada request get_blok sebelumnya yang masih aktif.'
                        : 'Gagal mengambil data blok.';

                    showLampiranNotice(
                        getAjaxMessage(xhr, defaultMessage, textStatus, errorThrown),
                        'error',
                        'Data Blok Gagal Dimuat'
                    );
                }
            },
            complete: function () {
                blokLookupXhr = null;
                setBlokLookupState(false);
            }
        });
    }

    function renderBlokModal(rows, requestId) {
        rows = rows || [];
        var html = '';
        html += '<table class="lampiran-st-modal-table lampiran-st-modal-table--blok">';
        html += '<thead><tr>';
        html += '<th>Blok/Nomor</th><th>PPJB ID</th><th>Nama Pembeli</th><th>No. PPJB</th><th>Tgl PPJB</th>';
        html += '<th>Tipe</th><th>Lokasi</th><th>Stok ID</th><th>No. Virtual Acc</th><th>Sektor Deskripsi</th>';
        html += '<th>Stok Blok</th><th>Stok Nomor</th><th>KD Jenis</th><th>KD Tipe</th><th>Jenis</th>';
        html += '<th>Nasabah Email</th><th>Stok Luas Semi Gross</th><th>PPJB Flag Smile</th><th>PPJB Tgl PO Smile</th><th>PPJB Luas Bangunan</th>';
        html += '</tr></thead><tbody>';

        $.each(rows, function (index, item) {
            var blokNomor = getRowValue(item, 'BLOK_NOMOR', 'blok_nomor');
            var ppjbId = String(getRowValue(item, 'PPJB_ID', 'ppjb_id') || '').trim();
            var nama = getRowValue(item, 'NAMA_PEMBELI', 'nama_pembeli');
            var noPpjb = getRowValue(item, 'NO_PPJB', 'no_ppjb');
            var tglPpjb = getRowValue(item, 'TGL_PPJB', 'tgl_ppjb');
            var tipe = getRowValue(item, 'TIPE', 'tipe');
            var lokasi = getRowValue(item, 'LOKASI', 'lokasi');
            var stokId = getRowValue(item, 'STOK_ID', 'stok_id');
            var virtualAcc = getRowValue(item, 'NO_VIRTUAL_ACC', 'no_virtual_acc');
            var sektor = getRowValue(item, 'DESKRIPSI', 'deskripsi');
            var stokBlok = getRowValue(item, 'BLOK', 'blok');
            var stokNomor = getRowValue(item, 'NOMOR', 'nomor');
            var kdJenis = getRowValue(item, 'KD_JENIS', 'kd_jenis');
            var kdTipe = getRowValue(item, 'KD_TIPE', 'kd_tipe');
            var jenis = getRowValue(item, 'JENIS', 'jenis');
            var email = getRowValue(item, 'EMAIL', 'email');
            var luasSemiGross = getRowValue(item, 'LUAS_SEMI_GROSS', 'luas_semi_gross');
            var flagSmile = getRowValue(item, 'FLAG_SMILE', 'flag_smile');
            var tglPoSmile = getRowValue(item, 'TGL_PO_SMILE', 'tgl_po_smile');
            var luasBangunan = getRowValue(item, 'LUAS_BANGUNAN', 'luas_bangunan');
            var dataStatus = getRowValue(item, 'DATA_STATUS', 'data_status');
            var cachedBuyer = ppjbId ? blokPembeliCache[ppjbId] : null;

            if (cachedBuyer) {
                nama = cachedBuyer.nama;
                email = cachedBuyer.email;
                dataStatus = cachedBuyer.status || dataStatus;
            } else if (ppjbId && nama) {
                blokPembeliCache[ppjbId] = { nama:String(nama || ''), email:String(email || ''), status:dataStatus || 'LENGKAP' };
            }

            var buyerAlreadyLoaded = !!cachedBuyer || !!nama;
            var namaLabel = nama || (ppjbId ? (buyerAlreadyLoaded ? '-' : 'Memuat...') : '-');
            var emailLabel = email || (ppjbId ? (buyerAlreadyLoaded ? '-' : 'Memuat...') : '-');

            html += '<tr data-lookup-row="1" title="' + escapeHtml(dataStatus || 'LENGKAP') + '" data-ppjb-id="' + escapeHtml(ppjbId) + '" onclick="addBlok(\'' + escapeJs(blokNomor) + '\')">';
            html += '<td data-filter-field="BLOK_NOMOR" data-sort-value="' + escapeHtml(blokNomor || '') + '">' + escapeHtml(blokNomor || '-') + '</td>';
            html += '<td data-filter-field="PPJB_ID" data-sort-value="' + escapeHtml(ppjbId || '') + '">' + escapeHtml(ppjbId || '-') + '</td>';
            html += '<td data-filter-field="NAMA_PEMBELI" data-sort-value="' + escapeHtml(nama || '') + '" class="blok-nama-pembeli" data-ppjb-id="' + escapeHtml(ppjbId) + '">' + escapeHtml(namaLabel) + '</td>';
            html += '<td data-filter-field="NO_PPJB" data-sort-value="' + escapeHtml(noPpjb || '') + '">' + escapeHtml(noPpjb || '-') + '</td>';
            html += '<td data-filter-field="TGL_PPJB" data-sort-value="' + escapeHtml(tglPpjb || '') + '">' + escapeHtml(formatDateIndo(tglPpjb) || '-') + '</td>';
            html += '<td data-filter-field="TIPE" data-sort-value="' + escapeHtml(tipe || '') + '">' + escapeHtml(tipe || '-') + '</td>';
            html += '<td data-filter-field="LOKASI" data-sort-value="' + escapeHtml(lokasi || '') + '">' + escapeHtml(lokasi || '-') + '</td>';
            html += '<td data-filter-field="STOK_ID" data-sort-value="' + escapeHtml(stokId || '') + '">' + escapeHtml(stokId || '-') + '</td>';
            html += '<td data-filter-field="NO_VIRTUAL_ACC" data-sort-value="' + escapeHtml(virtualAcc || '') + '">' + escapeHtml(virtualAcc || '-') + '</td>';
            html += '<td data-filter-field="DESKRIPSI" data-sort-value="' + escapeHtml(sektor || '') + '">' + escapeHtml(sektor || '-') + '</td>';
            html += '<td data-filter-field="BLOK" data-sort-value="' + escapeHtml(stokBlok || '') + '">' + escapeHtml(stokBlok || '-') + '</td>';
            html += '<td data-filter-field="NOMOR" data-sort-value="' + escapeHtml(stokNomor || '') + '">' + escapeHtml(stokNomor || '-') + '</td>';
            html += '<td data-filter-field="KD_JENIS" data-sort-value="' + escapeHtml(kdJenis || '') + '">' + escapeHtml(kdJenis || '-') + '</td>';
            html += '<td data-filter-field="KD_TIPE" data-sort-value="' + escapeHtml(kdTipe || '') + '">' + escapeHtml(kdTipe || '-') + '</td>';
            html += '<td data-filter-field="JENIS" data-sort-value="' + escapeHtml(jenis || '') + '">' + escapeHtml(jenis || '-') + '</td>';
            html += '<td data-filter-field="EMAIL" data-sort-value="' + escapeHtml(email || '') + '" class="blok-email" data-ppjb-id="' + escapeHtml(ppjbId) + '">' + escapeHtml(emailLabel) + '</td>';
            html += '<td data-filter-field="LUAS_SEMI_GROSS" data-sort-value="' + escapeHtml(luasSemiGross || '') + '" style="text-align:right;">' + escapeHtml(formatNumber(luasSemiGross) || '-') + '</td>';
            html += '<td data-filter-field="FLAG_SMILE" data-sort-value="' + escapeHtml(flagSmile || '') + '" style="text-align:center;">' + escapeHtml(flagSmile || '-') + '</td>';
            html += '<td data-filter-field="TGL_PO_SMILE" data-sort-value="' + escapeHtml(tglPoSmile || '') + '">' + escapeHtml(formatDateIndo(tglPoSmile) || '-') + '</td>';
            html += '<td data-filter-field="LUAS_BANGUNAN" data-sort-value="' + escapeHtml(luasBangunan || '') + '" style="text-align:right;">' + escapeHtml(formatNumber(luasBangunan) || '-') + '</td>';
            html += '</tr>';
        });

        if (rows.length < 1) {
            html += '<tr><td colspan="20" style="text-align:center;padding:24px;">Data blok tidak ditemukan. Pastikan Cluster yang dipilih adalah kode KD_SEKTOR, bukan KD_LOKASI.</td></tr>';
        }
        html += '</tbody></table>';

        insertModal(getBlokModalTitle(), html, {
            wide: true,
            hideDefaultSearch: true,
            customTopHtml: buildBlokCriteriaPanel()
        });

        syncBlokCriteriaCounter();
        syncBlokCriteriaModeUI();

        if (blokCriteriaMode === 'search') {
            applyBlokSearchLive();
        } else {
            applyBlokFilterLive();
        }

        loadBlokPembeliNames(rows, requestId);
    }

    function updateBlokCellByPpjbId(className, ppjbId, value) {
        $('.' + className).each(function () {
            if (String($(this).attr('data-ppjb-id') || '') === String(ppjbId || '')) {
                $(this)
                    .text(value || '-')
                    .attr('data-sort-value', String(value || ''));
            }
        });
    }

    function removeBlokPembeliXhr(xhr) {
        blokPembeliXhrs = $.grep(blokPembeliXhrs, function (item) {
            return item !== xhr;
        });
    }

    function loadBlokPembeliNames(rows, requestId) {
        var ppjbIds = [];
        var seen = {};

        $.each(rows || [], function (index, item) {
            var ppjbId = String(getRowValue(item, 'PPJB_ID', 'ppjb_id') || '').trim();
            var nama = String(getRowValue(item, 'NAMA_PEMBELI', 'nama_pembeli') || '').trim();
            var email = String(getRowValue(item, 'EMAIL', 'email') || '').trim();

            if (!ppjbId) {
                return;
            }

            if (nama && !blokPembeliCache[ppjbId]) {
                blokPembeliCache[ppjbId] = {
                    nama: nama,
                    email: email,
                    status: 'LENGKAP'
                };
            }

            if (!blokPembeliCache[ppjbId] && !seen[ppjbId]) {
                seen[ppjbId] = true;
                ppjbIds.push(ppjbId);
            }
        });

        if (ppjbIds.length < 1) {
            return;
        }

        /*
         * Request nama pembeli dipecah per 150 PPJB dan maksimal dua request
         * paralel. Modal dapat tampil lebih cepat tanpa membanjiri database.
         */
        var batches = [];
        var batchSize = 150;

        for (var offset = 0; offset < ppjbIds.length; offset += batchSize) {
            batches.push(ppjbIds.slice(offset, offset + batchSize));
        }

        var nextBatch = 0;
        var activeRequests = 0;
        var maxParallel = 2;

        function pumpQueue() {
            while (activeRequests < maxParallel && nextBatch < batches.length) {
                sendBatch(batches[nextBatch]);
                nextBatch++;
            }
        }

        function sendBatch(batch) {
            activeRequests++;

            var xhr = $.ajax({
                method: 'POST',
                url: getEndpoint('get_blok_pembeli'),
                dataType: 'json',
                headers: {
                    'Accept': 'application/json'
                },
                timeout: 45000,
                data: {
                    _token: '{{ csrf_token() }}',
                    ppjb_ids: batch
                },
                success: function (response) {
                    var resultRows = normalizeRows(response);
                    var found = {};

                    $.each(resultRows, function (index, item) {
                        var ppjbId = String(getRowValue(item, 'PPJB_ID', 'ppjb_id') || '').trim();
                        var nama = String(getRowValue(item, 'NAMA_PEMBELI', 'nama_pembeli') || '').trim();
                        var email = String(getRowValue(item, 'EMAIL', 'email') || '').trim();
                        var status = String(getRowValue(item, 'DATA_STATUS', 'data_status') || '').trim();

                        if (!ppjbId) {
                            return;
                        }

                        found[ppjbId] = true;
                        blokPembeliCache[ppjbId] = {
                            nama: nama,
                            email: email,
                            status: status
                        };

                        if (requestId === blokLookupGeneration) {
                            updateBlokCellByPpjbId('blok-nama-pembeli', ppjbId, nama || '-');
                            updateBlokCellByPpjbId('blok-email', ppjbId, email || '-');
                        }
                    });

                    if (requestId === blokLookupGeneration) {
                        refreshActiveBlokFilter();
                    }

                    $.each(batch, function (index, ppjbId) {
                        if (!found[ppjbId]) {
                            blokPembeliCache[ppjbId] = {
                                nama: '',
                                email: '',
                                status: 'DATA_PEMBELI_TIDAK_DITEMUKAN'
                            };

                            if (requestId === blokLookupGeneration) {
                                updateBlokCellByPpjbId('blok-nama-pembeli', ppjbId, '-');
                                updateBlokCellByPpjbId('blok-email', ppjbId, '-');
                            }
                        }
                    });
                },
                error: function (xhr, textStatus) {
                    if (textStatus === 'abort') {
                        return;
                    }

                    console.log(xhr ? xhr.responseText : '');

                    if (requestId === blokLookupGeneration) {
                        $.each(batch, function (index, ppjbId) {
                            updateBlokCellByPpjbId('blok-nama-pembeli', ppjbId, '-');
                            updateBlokCellByPpjbId('blok-email', ppjbId, '-');
                        });
                    }
                },
                complete: function () {
                    activeRequests--;
                    removeBlokPembeliXhr(xhr);
                    pumpQueue();
                }
            });

            blokPembeliXhrs.push(xhr);
        }

        pumpQueue();
    }

    function addBlok(blokNomor) {
        if (blokLookupTarget === 'akhir') {
            $('#blok_akhir').val(blokNomor || '');
        } else {
            $('#blok_awal').val(blokNomor || '');
        }

        toggleLampiranModal(false);
    }

    function getFilterData(includeUrut) {
        var data = {
            _token: '{{ csrf_token() }}',
            perusahaan: $('#perusahaan').val() || 'DTSA',
            sektor: $('#sektor').val() || '',
            blok_awal: String($('#blok_awal').val() || 'A').toUpperCase(),
            blok_akhir: String($('#blok_akhir').val() || 'ZZ').toUpperCase(),
            ipl: $('#ipl').val() || '0',
            sf: $('#sf').val() || '0',
            abo: $('#abo').val() || '0'
        };

        if (includeUrut !== false) {
            data.urut = $('#urut').val() || '1';
            data.undangan_ke = data.urut;
        }

        return data;
    }

    function estimateBlockRangeSize(blokAwal, blokAkhir) {
        var awal = parseBlokNomor(blokAwal);
        var akhir = parseBlokNomor(blokAkhir);

        if (!awal || !akhir || awal.blok !== akhir.blok) {
            return 0;
        }

        if (akhir.nomor < awal.nomor) {
            return 999999;
        }

        return akhir.nomor - awal.nomor + 1;
    }

    function parseBlokNomor(value) {
        var text = String(value || '').toUpperCase().trim();
        var match = text.match(/^([A-Z0-9]+)\/(\d+)$/);

        if (!match) {
            return null;
        }

        return {
            blok: match[1],
            nomor: parseInt(match[2], 10)
        };
    }

    function validateFilter() {
        if (!$('#perusahaan').val()) {
            showLampiranNotice('Unit/perusahaan belum tersedia.', 'warning');
            return false;
        }

        if (!isClusterSelected()) {
            showLampiranNotice('Silakan pilih Cluster terlebih dahulu sebelum memilih Blok atau memproses data.', 'warning', 'Cluster Belum Dipilih', 'Pilih Cluster terlebih dahulu');
            return false;
        }

        if (!$('#blok_awal').val() || !$('#blok_akhir').val()) {
            showLampiranNotice('Blok awal dan Blok akhir wajib diisi.', 'warning');
            return false;
        }

        var blokAwal = String($('#blok_awal').val() || '').toUpperCase();
        var blokAkhir = String($('#blok_akhir').val() || '').toUpperCase();

        if (blokAwal > blokAkhir) {
            showLampiranNotice('Blok awal tidak boleh lebih besar dari blok akhir.', 'warning');
            return false;
        }

        var rangeEstimate = estimateBlockRangeSize(blokAwal, blokAkhir);

        if (rangeEstimate > 300) {
            showLampiranNotice('Range blok terlalu besar. Untuk menghindari timeout, pilih maksimal sekitar 300 unit dalam satu kali proses.', 'warning', 'Range Blok Terlalu Besar');
            return false;
        }

        var urut = parseInt($('#urut').val(), 10);

        if (isNaN(urut) || urut < 1) {
            showLampiranNotice('Undangan Ke wajib diisi minimal 1.', 'warning');
            return false;
        }

        return true;
    }

    function getLampiranData() {
        if (!validateFilter()) {
            return;
        }

        setProcessingState(true);
        $('#loading-info').show();
        $('#main-display').html('');
        updateTableMeta(0);
        $('input[name="check_mode"][value="none"]').prop('checked', true);

        $.ajax({
            method: 'POST',
            url: getEndpoint('get_data'),
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            timeout: 300000,
            data: getFilterData(true),
            success: function (response) {
                renderLampiranTable(response);
            },
            error: function (xhr, textStatus, errorThrown) {
                console.log(xhr ? xhr.responseText : '');
                $('#main-display').html(
                    '<div class="lampiran-st-alert">' +
                    escapeHtml(getAjaxMessage(xhr, 'Data lampiran undangan gagal dimuat.', textStatus, errorThrown)) +
                    '</div>'
                );
            },
            complete: function () {
                $('#loading-info').hide();
                setProcessingState(false);
            }
        });
    }

    function renderLampiranTable(payload) {
        var rows = normalizeRows(payload);
        var html = '';

        if (rows.length >= 300) {
            showLampiranNotice('Data yang tampil dibatasi 300 baris agar tidak timeout. Persempit range blok bila data yang dicari belum muncul semua.', 'info', 'Data Dibatasi');
        }

        html += '<table class="lampiran-st-table">';
        html += '<thead><tr>';
        html += '<th>No.</th>';
        html += '<th>Pilih</th>';
        html += '<th>Click</th>';
        html += '<th>Blok</th>';
        html += '<th>Nomor</th>';
        html += '<th>Nama</th>';
        html += '<th>Email</th>';
        html += '<th>IPL</th>';
        html += '<th>SF</th>';
        html += '<th>ABO</th>';
        html += '<th>Tunggakan</th>';
        html += '<th>Denda</th>';
        html += '<th>Flag Cetak</th>';
        html += '<th>Tgl Cetak</th>';
        html += '<th>User Cetak</th>';
        html += '</tr></thead><tbody>';

        if (rows.length < 1) {
            html += '<tr><td colspan="15" class="empty-row"><div class="lampiran-st-empty"><span class="lampiran-st-empty-icon"><i class="fas fa-search"></i></span><strong>Data tidak ditemukan</strong><span>Periksa kembali Cluster, Blok, atau parameter yang digunakan.</span></div></td></tr>';
        } else {
            $.each(rows, function (index, item) {
                item = item || {};

                var lampiranId = getRowValue(item, 'LAMP_UNDANGAN_ID', 'lamp_undangan_id');
                var ppjbId = getRowValue(item, 'PPJB_ID', 'ppjb_id');
                var pilih = getRowValue(item, 'PILIH', 'pilih');
                var click = getRowValue(item, 'CLICK', 'click');
                var checked = String(pilih || '').toUpperCase().trim() === 'Y' ? 'checked' : '';

                html += '<tr data-lampiran-id="' + escapeHtml(lampiranId) + '" data-ppjb-id="' + escapeHtml(ppjbId) + '">';
                html += '<td class="center-cell">' + (index + 1) + '</td>';
                html += '<td class="center-cell"><input type="checkbox" class="row-check" ' + checked + '></td>';
                html += '<td class="center-cell">' + escapeHtml(click || 'T') + '</td>';
                html += '<td class="center-cell">' + escapeHtml(valueOrBlank(getRowValue(item, 'BLOK', 'blok'))) + '</td>';
                html += '<td class="center-cell">' + escapeHtml(valueOrBlank(getRowValue(item, 'NOMOR', 'nomor'))) + '</td>';
                html += '<td class="name-cell">' + escapeHtml(valueOrBlank(getRowValue(item, 'NAMA', 'nama'))) + '</td>';
                html += '<td class="email-cell">' + escapeHtml(valueOrBlank(getRowValue(item, 'EMAIL', 'email'))) + '</td>';
                html += '<td class="right-cell">' + escapeHtml(formatNumber(getRowValue(item, 'IPL', 'ipl'))) + '</td>';
                html += '<td class="right-cell">' + escapeHtml(formatNumber(getRowValue(item, 'SF', 'sf'))) + '</td>';
                html += '<td class="right-cell">' + escapeHtml(formatNumber(getRowValue(item, 'ABO', 'abo'))) + '</td>';
                html += '<td class="right-cell">' + escapeHtml(formatNumber(getRowValue(item, 'TUNGGAKAN', 'tunggakan'))) + '</td>';
                html += '<td class="right-cell">' + escapeHtml(formatNumber(getRowValue(item, 'DENDA', 'denda'))) + '</td>';
                html += '<td class="center-cell">' + escapeHtml(valueOrBlank(getRowValue(item, 'FLAG_CETAK', 'flag_cetak'))) + '</td>';
                html += '<td class="center-cell">' + escapeHtml(formatDateIndo(getRowValue(item, 'TGL_CETAK', 'tgl_cetak'))) + '</td>';
                html += '<td class="center-cell">' + escapeHtml(valueOrBlank(getRowValue(item, 'USER_CETAK', 'user_cetak'))) + '</td>';
                html += '</tr>';
            });
        }

        html += '</tbody></table>';

        $('#main-display').html(html);
        updateTableMeta(rows.length);
    }

    function setAllRows(checked) {
        $('.row-check').prop('checked', checked);
        updateSelectedCount();
    }

    function sendSelectedEmail() {
        var selected = $('.row-check:checked').length;

        if (selected < 1) {
            showLampiranNotice('Pilih data lampiran yang akan dikirim email terlebih dahulu.', 'warning');
            return;
        }

        showLampiranNotice('Fungsi kirim email belum diaktifkan karena belum ada query/procedure kirim email yang diberikan. Data terpilih: ' + selected + '.', 'info', 'Fitur Belum Aktif');
    }

    function showEmailQueue() {
        showLampiranNotice('Fitur lihat antrian email belum diaktifkan karena query antrian email belum diberikan.', 'info', 'Fitur Belum Aktif');
    }

    function getAjaxMessage(xhr, defaultMessage, textStatus, errorThrown) {
        var message = defaultMessage || 'Terjadi kesalahan.';
        var detail = '';

        if (xhr && xhr.responseJSON) {
            var responseMessage = xhr.responseJSON.message || '';
            var responseError = xhr.responseJSON.error || xhr.responseJSON.exception || '';

            detail = responseMessage;

            if (responseError && responseError !== responseMessage) {
                detail += (detail ? ' Detail: ' : '') + responseError;
            }
        }

        if (!detail && xhr && xhr.responseText) {
            detail = String(xhr.responseText)
                .replace(/<script[\s\S]*?<\/script>/gi, ' ')
                .replace(/<style[\s\S]*?<\/style>/gi, ' ')
                .replace(/<[^>]+>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();

            if (detail.length > 450) {
                detail = detail.substring(0, 450) + '...';
            }
        }

        if (!detail && errorThrown) {
            detail = String(errorThrown);
        }

        if (xhr && xhr.status) {
            message += ' Status HTTP: ' + xhr.status + '.';
        }

        if (textStatus) {
            message += ' Status AJAX: ' + textStatus + '.';
        }

        if (detail) {
            message += ' Detail: ' + detail;
        }

        return message;
    }
</script>
@endsection
