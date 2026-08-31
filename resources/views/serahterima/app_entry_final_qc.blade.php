@extends('layouts.template')

@section('content')

<style>
    .approval-qc-page {
        --qc-primary: #2563eb;
        --qc-primary-dark: #1d4ed8;
        --qc-primary-soft: #eff6ff;
        --qc-primary-soft-strong: #dbeafe;
        --qc-success: #16a34a;
        --qc-success-soft: #f2fbf5;
        --qc-danger: #dc2626;
        --qc-danger-soft: #fef2f2;
        --qc-text: #172033;
        --qc-muted: #667085;
        --qc-line: #e5e7eb;
        --qc-line-strong: #dbe3ef;
        --qc-card: #ffffff;

        width: 100%;
        min-height: calc(100vh - 120px);
        padding: 10px 0 28px;
        color: var(--qc-text);
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        background: #f8fafc;
    }

    .approval-qc-page,
    .approval-qc-page * {
        box-sizing: border-box;
    }

    .approval-qc-desktop-frame {
        width: 100%;
        min-height: 620px;
        overflow: hidden;
        border: 1px solid var(--qc-line-strong);
        border-radius: 18px;
        background: var(--qc-card);
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
    }

    .approval-qc-checkbar {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 58px;
        padding: 12px 18px;
        border-bottom: 1px solid var(--qc-line);
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        font-size: 12px;
    }

    .approval-qc-checkbar label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        padding: 8px 13px;
        border: 1px solid var(--qc-line-strong);
        border-radius: 999px;
        background: #ffffff;
        color: #344054;
        font-weight: 650;
        cursor: pointer;
        transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
    }

    .approval-qc-checkbar label:hover {
        border-color: #93c5fd;
        background: var(--qc-primary-soft);
        color: var(--qc-primary-dark);
        box-shadow: 0 3px 9px rgba(37, 99, 235, 0.10);
    }

    .approval-qc-checkbar label:has(input:checked) {
        border-color: #93c5fd;
        background: var(--qc-primary-soft-strong);
        color: #1e40af;
    }

    /*
     * Checkbox direstyle jadi kotak modern bercentang biru, konsisten
     * dengan komponen modern-checkbox-label yang dipakai fitur lain.
     * Ini murni gaya CSS — elemen <input type="checkbox"> dan id/class
     * yang dipakai JS tidak diubah sama sekali.
     */
    .approval-qc-checkbar input[type="checkbox"],
    .approval-qc-table input[type="checkbox"] {
        appearance: none;
        -webkit-appearance: none;
        width: 17px;
        height: 17px;
        margin: 0;
        border: 1.5px solid #b6c0cf;
        border-radius: 5px;
        background-color: #ffffff;
        background-position: center;
        background-repeat: no-repeat;
        background-size: 11px 11px;
        cursor: pointer;
        transition: border-color 0.16s ease, background-color 0.16s ease, box-shadow 0.16s ease;
    }

    .approval-qc-checkbar input[type="checkbox"]:hover,
    .approval-qc-table input[type="checkbox"]:hover {
        border-color: var(--qc-primary);
    }

    .approval-qc-checkbar input[type="checkbox"]:checked,
    .approval-qc-table input[type="checkbox"]:checked {
        border-color: var(--qc-primary);
        background-color: var(--qc-primary);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='white' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.6' d='M3.2 8.2l3 3.1 6.6-7'/%3E%3C/svg%3E");
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .approval-qc-checkbar input[type="checkbox"]:focus-visible,
    .approval-qc-table input[type="checkbox"]:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
    }

    .approval-qc-title {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
        padding: 20px 22px 6px;
        color: var(--qc-text);
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.25;
        letter-spacing: -0.2px;
    }

    .approval-qc-title::before {
        content: "\25C8";
        display: inline-flex;
        width: 34px;
        height: 34px;
        min-width: 34px;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 11px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.24);
        font-size: 16px;
        font-weight: 700;
        line-height: 1;
    }

    .approval-qc-title::after {
        content: "Daftar persetujuan tanggal Final QC untuk validasi internal proyek.";
        position: absolute;
        top: 100%;
        left: 22px;
        display: block;
        margin-top: 4px;
        color: var(--qc-muted);
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 12.5px;
        font-weight: 500;
        letter-spacing: 0;
    }

    .approval-qc-table-wrap {
        width: calc(100% - 36px);
        margin: 30px 18px 22px;
        overflow: auto;
        border: 1px solid var(--qc-line-strong);
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 5px 16px rgba(15, 23, 42, 0.04);
    }

    .approval-qc-table {
        width: max-content;
        min-width: 1040px;
        border-collapse: separate;
        border-spacing: 0;
        background: #ffffff;
        color: #344054;
        font-size: 10.5px;
    }

    .approval-qc-table th {
        position: sticky;
        top: 0;
        z-index: 2;
        padding: 10px 10px;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #c8d3e1;
        background: linear-gradient(180deg, #eff6ff 0%, #e5effb 100%);
        color: #344054;
        text-align: center;
        vertical-align: middle;
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-weight: 900;
        line-height: 1.25;
        white-space: nowrap;
        box-shadow: inset 0 -1px 0 rgba(37, 99, 235, 0.05);
    }

    .approval-qc-table thead tr:nth-child(2) th {
        top: 39px;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        color: #475467;
        font-size: 9.5px;
        font-weight: 900;
    }

    .approval-qc-table th:first-child,
    .approval-qc-table td:first-child {
        border-left: 0;
    }

    .approval-qc-table th:last-child,
    .approval-qc-table td:last-child {
        border-right: 0;
    }

    .approval-qc-table td {
        height: 46px;
        padding: 8px 10px;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
        color: #344054;
        vertical-align: middle;
        line-height: 1.38;
        white-space: nowrap;
    }

    .approval-qc-table tbody tr:nth-child(even) td {
        background: #fbfcfe;
    }

    .approval-qc-table tbody tr:hover td {
        background: #f0f7ff;
    }

    .approval-qc-table tbody tr:hover td:first-child {
        box-shadow: inset 4px 0 0 #2563eb;
        color: #1d4ed8;
    }

    .approval-qc-table .no-col {
        width: 48px;
        color: #475467;
        text-align: center;
        font-weight: 750;
        font-variant-numeric: tabular-nums;
    }

    .approval-qc-table .approval-col {
        width: 72px;
        text-align: center;
    }

    .approval-qc-table .keterangan-col {
        width: 470px;
        max-width: 470px;
        color: #344054;
        line-height: 1.45;
        white-space: normal;
        text-align: left;
    }

    .approval-qc-table .date-col {
        width: 112px;
        color: #475467;
        text-align: center;
        font-variant-numeric: tabular-nums;
    }

    .approval-qc-table .user-col {
        width: 120px;
        color: #172033;
        text-align: center;
        font-weight: 800;
    }

    .approval-qc-table .entry-date-col {
        width: 132px;
        color: #475467;
        text-align: center;
        font-variant-numeric: tabular-nums;
    }

    .approval-qc-empty {
        height: 260px !important;
        background: #ffffff !important;
        color: #64748b !important;
        text-align: center;
        font-style: normal;
        font-weight: 550;
    }

    .approval-qc-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 16px 18px 0;
        padding: 10px 13px;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        background: var(--qc-primary-soft);
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 650;
    }

    .approval-qc-loading i {
        color: var(--qc-primary);
    }

    .approval-qc-alert {
        margin: 16px;
        padding: 13px 15px;
        border: 1px solid #fecaca;
        border-radius: 12px;
        background: var(--qc-danger-soft);
        color: var(--qc-danger);
        font-size: 12px;
        font-weight: 650;
    }

    @media (max-width: 768px) {
        .approval-qc-checkbar {
            flex-wrap: wrap;
        }

        .approval-qc-title {
            padding: 18px 16px 4px;
            font-size: 18px;
        }

        .approval-qc-title::after {
            left: 16px;
        }

        .approval-qc-table-wrap {
            width: calc(100% - 24px);
            margin: 28px 12px 18px;
        }
    }
</style>

<div class="approval-qc-page">
    <input type="hidden" id="perusahaan" value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}">

    <div class="approval-qc-desktop-frame">
        <div class="approval-qc-checkbar">
            <label>
                <input type="checkbox" id="check_all_1" onchange="toggleCheckAll(1, this.checked)">
                <span>Pilih Semua #1</span>
            </label>
            <label>
                <input type="checkbox" id="check_all_2" onchange="toggleCheckAll(2, this.checked)">
                <span>Pilih Semua #2</span>
            </label>
        </div>

        <h1 class="approval-qc-title">Persetujuan Tanggal Final QC</h1>

        <div id="loading-info" class="approval-qc-loading" style="display:none;">
            <i class="fas fa-spinner fa-spin"></i>
            Memproses data persetujuan Final QC.
        </div>

        <div id="main-display" class="approval-qc-table-wrap">
            <table class="approval-qc-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="no-col">No.</th>
                        <th colspan="2">Status Persetujuan</th>
                        <th rowspan="2" class="keterangan-col">Uraian / Keterangan</th>
                        <th rowspan="2" class="date-col">Tanggal Entry<br>Final QC</th>
                        <th rowspan="2" class="date-col">Tanggal Diterima<br>dari Proyek</th>
                        <th rowspan="2" class="date-col">Tanggal<br>Penjadwalan Ulang</th>
                        <th rowspan="2" class="user-col">Petugas</th>
                        <th rowspan="2" class="entry-date-col">Tanggal Entry</th>
                    </tr>
                    <tr>
                        <th class="approval-col"># 1</th>
                        <th class="approval-col"># 2</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="9" class="approval-qc-empty">Memuat data persetujuan.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
    $(document).ready(function () {
        loadApprovalFinalQc();
    });

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

    function isApproved(value) {
        return String(value || '').toUpperCase().trim() === 'Y';
    }

    function getRescheduleText(item) {
        var status1 = String(item.STS_RESCHEDULE1 || '').toUpperCase().trim();
        var status2 = String(item.STS_RESCHEDULE2 || '').toUpperCase().trim();

        if (status2 === 'Y') {
            return formatDateIndo(item.TGL_RESCHEDULE2);
        }

        if (status1 === 'Y') {
            return formatDateIndo(item.TGL_RESCHEDULE1);
        }

        return '';
    }

    function loadApprovalFinalQc() {
        $('#loading-info').show();
        $('#check_all_1, #check_all_2').prop('checked', false);

        $.ajax({
            method: 'POST',
            url: '{{ url()->current() }}/get_data',
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            data: {
                _token: '{{ csrf_token() }}',
                perusahaan: $('#perusahaan').val() || 'DTSA'
            },
            success: function (response) {
                renderApprovalTable(response);
            },
            error: function (xhr) {
                console.log(xhr ? xhr.responseText : '');

                var message = 'Data persetujuan Final QC belum dapat dimuat.';

                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    message += ' ' + xhr.responseJSON.message;
                }

                $('#main-display').html(
                    '<div class="approval-qc-alert">' +
                    escapeHtml(message) +
                    '</div>'
                );
            },
            complete: function () {
                $('#loading-info').hide();
            }
        });
    }

    function renderApprovalTable(payload) {
        var rows = normalizeRows(payload);
        var html = '';

        html += '<table class="approval-qc-table">';
        html += '<thead>';
        html += '<tr>';
        html += '<th rowspan="2" class="no-col">No.</th>';
        html += '<th colspan="2">Status Persetujuan</th>';
        html += '<th rowspan="2" class="keterangan-col">Uraian / Keterangan</th>';
        html += '<th rowspan="2" class="date-col">Tanggal Entry<br>Final QC</th>';
        html += '<th rowspan="2" class="date-col">Tanggal Diterima<br>dari Proyek</th>';
        html += '<th rowspan="2" class="date-col">Tanggal<br>Penjadwalan Ulang</th>';
        html += '<th rowspan="2" class="user-col">Petugas</th>';
        html += '<th rowspan="2" class="entry-date-col">Tanggal Entry</th>';
        html += '</tr>';
        html += '<tr>';
        html += '<th class="approval-col">1#</th>';
        html += '<th class="approval-col">2#</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';

        if (rows.length < 1) {
            html += '<tr>';
            html += '<td colspan="9" class="approval-qc-empty">Tidak ada data persetujuan Final QC yang perlu ditampilkan.</td>';
            html += '</tr>';
        } else {
            $.each(rows, function (index, item) {
                item = item || {};

                html += '<tr>';
                html += '<td class="no-col">' + (index + 1) + '</td>';
                html += '<td class="approval-col">';
                html += '<input type="checkbox" class="approval-check approval-1" value="1" aria-label="Approval 1#" ';
                html += isApproved(item.APPROVE1) ? 'checked' : '';
                html += '>';
                html += '</td>';
                html += '<td class="approval-col">';
                html += '<input type="checkbox" class="approval-check approval-2" value="2" aria-label="Approval 2#" ';
                html += isApproved(item.APPROVE2) ? 'checked' : '';
                html += '>';
                html += '</td>';
                html += '<td class="keterangan-col">' + escapeHtml(valueOrBlank(item.KETERANGAN)) + '</td>';
                html += '<td class="date-col">' + escapeHtml(formatDateIndo(item.TGL_ENTRY_FINAL_QC)) + '</td>';
                html += '<td class="date-col">' + escapeHtml(formatDateIndo(item.TGL_DITERIMA_PROYEK)) + '</td>';
                html += '<td class="date-col">' + escapeHtml(getRescheduleText(item)) + '</td>';
                html += '<td class="user-col">' + escapeHtml(valueOrBlank(item.USER_ENTRY)) + '</td>';
                html += '<td class="entry-date-col">' + escapeHtml(formatDateIndo(item.TGL_ENTRY)) + '</td>';
                html += '</tr>';
            });
        }

        html += '</tbody>';
        html += '</table>';

        $('#main-display').html(html);
    }

    function toggleCheckAll(level, checked) {
        var selector = level === 1 ? '.approval-1' : '.approval-2';
        $(selector).prop('checked', checked);
    }
</script>
@endsection
