@extends('layouts.template')

@section('content')

<style>
    .approval-rs-page {
        --rs-primary: #2563eb;
        --rs-primary-dark: #1d4ed8;
        --rs-primary-soft: #eff6ff;
        --rs-primary-soft-strong: #dbeafe;
        --rs-success: #15803d;
        --rs-success-soft: #eaf8ef;
        --rs-pending: #a04a00;
        --rs-pending-soft: #fff2e7;
        --rs-danger: #dc2626;
        --rs-danger-soft: #fef2f2;
        --rs-text: #172033;
        --rs-muted: #667085;
        --rs-line: #e5e7eb;
        --rs-line-strong: #dbe3ef;
        --rs-card: #ffffff;

        width: 100%;
        min-height: calc(100vh - 120px);
        padding: 10px 0 28px;
        color: var(--rs-text);
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        background: #f8fafc;
    }

    .approval-rs-page,
    .approval-rs-page * {
        box-sizing: border-box;
    }

    .approval-rs-frame {
        width: 100%;
        min-height: 620px;
        overflow: hidden;
        border: 1px solid var(--rs-line-strong);
        border-radius: 18px;
        background: var(--rs-card);
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
    }

    .approval-rs-checkbar {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 58px;
        padding: 12px 18px;
        border-bottom: 1px solid var(--rs-line);
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        font-size: 12px;
    }

    .approval-rs-checkbar label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        padding: 8px 13px;
        border: 1px solid var(--rs-line-strong);
        border-radius: 999px;
        background: #ffffff;
        color: #344054;
        font-weight: 650;
        cursor: pointer;
        transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
    }

    .approval-rs-checkbar label:hover {
        border-color: #93c5fd;
        background: var(--rs-primary-soft);
        color: var(--rs-primary-dark);
        box-shadow: 0 3px 9px rgba(37, 99, 235, 0.10);
    }

    .approval-rs-checkbar label:has(input:checked) {
        border-color: #93c5fd;
        background: var(--rs-primary-soft-strong);
        color: #1e40af;
    }

    /*
     * Checkbox direstyle jadi kotak modern bercentang biru, konsisten
     * dengan Approval Entry Final QC. Elemen <input type="checkbox">
     * dan id/class yang dipakai JS tidak diubah sama sekali.
     */
    .approval-rs-checkbar input[type="checkbox"],
    .approval-rs-table input[type="checkbox"] {
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

    .approval-rs-checkbar input[type="checkbox"]:hover,
    .approval-rs-table input[type="checkbox"]:hover {
        border-color: var(--rs-primary);
    }

    .approval-rs-checkbar input[type="checkbox"]:checked,
    .approval-rs-table input[type="checkbox"]:checked {
        border-color: var(--rs-primary);
        background-color: var(--rs-primary);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='white' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.6' d='M3.2 8.2l3 3.1 6.6-7'/%3E%3C/svg%3E");
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .approval-rs-checkbar input[type="checkbox"]:focus-visible,
    .approval-rs-table input[type="checkbox"]:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
    }

    .approval-rs-title {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
        padding: 20px 22px 6px;
        color: var(--rs-text);
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.25;
        letter-spacing: -0.2px;
    }

    .approval-rs-title::before {
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

    .approval-rs-title::after {
        content: "Daftar persetujuan perubahan jadwal undangan serah terima.";
        position: absolute;
        top: 100%;
        left: 22px;
        display: block;
        margin-top: 4px;
        color: var(--rs-muted);
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 12.5px;
        font-weight: 500;
        letter-spacing: 0;
    }

    .approval-rs-table-wrap {
        width: calc(100% - 36px);
        margin: 30px 18px 22px;
        overflow: auto;
        border: 1px solid var(--rs-line-strong);
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 5px 16px rgba(15, 23, 42, 0.04);
    }

    .approval-rs-table {
        width: max-content;
        min-width: 1120px;
        border-collapse: separate;
        border-spacing: 0;
        background: #ffffff;
        color: #344054;
        font-size: 10.5px;
    }

    .approval-rs-table th {
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

    .approval-rs-table thead tr:nth-child(2) th {
        top: 39px;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        color: #475467;
        font-size: 9.5px;
        font-weight: 900;
    }

    .approval-rs-table th:last-child,
    .approval-rs-table td:last-child {
        border-right: 0;
    }

    .approval-rs-table td {
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

    .approval-rs-table tbody tr:nth-child(even) td {
        background: #fbfcfe;
    }

    .approval-rs-table tbody tr:hover td {
        background: #f0f7ff;
    }

    .approval-rs-table tbody tr:hover td:first-child {
        box-shadow: inset 4px 0 0 #2563eb;
        color: #1d4ed8;
    }

    .approval-rs-table .no-col {
        width: 48px;
        color: #475467;
        text-align: center;
        font-weight: 750;
        font-variant-numeric: tabular-nums;
    }

    .approval-rs-table .approval-col {
        width: 72px;
        text-align: center;
    }

    .approval-rs-table .keterangan-col {
        width: 430px;
        max-width: 430px;
        color: #344054;
        line-height: 1.45;
        white-space: normal;
        text-align: left;
    }

    .approval-rs-table .status-col {
        width: 122px;
        text-align: center;
    }

    .approval-rs-table .date-col {
        width: 128px;
        color: #475467;
        text-align: center;
        font-variant-numeric: tabular-nums;
    }

    .approval-rs-table .user-col {
        width: 130px;
        color: #172033;
        text-align: center;
        font-weight: 800;
    }

    .approval-rs-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 72px;
        padding: 5px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
    }

    .approval-rs-badge.active {
        background: var(--rs-success-soft);
        color: var(--rs-success);
    }

    .approval-rs-badge.pending {
        background: var(--rs-pending-soft);
        color: var(--rs-pending);
    }

    .approval-rs-empty {
        height: 260px !important;
        background: #ffffff !important;
        color: #64748b !important;
        text-align: center;
        font-style: normal;
        font-weight: 550;
    }

    .approval-rs-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 16px 18px 0;
        padding: 10px 13px;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        background: var(--rs-primary-soft);
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 650;
    }

    .approval-rs-loading i {
        color: var(--rs-primary);
    }

    .approval-rs-alert {
        margin: 16px;
        padding: 13px 15px;
        border: 1px solid #fecaca;
        border-radius: 12px;
        background: var(--rs-danger-soft);
        color: var(--rs-danger);
        font-size: 12px;
        font-weight: 650;
    }

    @media (max-width: 768px) {
        .approval-rs-checkbar {
            flex-wrap: wrap;
        }

        .approval-rs-title {
            padding: 18px 16px 4px;
            font-size: 18px;
        }

        .approval-rs-title::after {
            left: 16px;
        }

        .approval-rs-table-wrap {
            width: calc(100% - 24px);
            margin: 28px 12px 18px;
        }
    }
</style>

<div class="approval-rs-page">
    <input type="hidden" id="perusahaan" value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}">

    <div class="approval-rs-frame">
        <div class="approval-rs-checkbar">
            <label>
                <input type="checkbox" id="check_all_1" onchange="toggleCheckAll(1, this.checked)">
                <span>Pilih Semua #1</span>
            </label>
            <label>
                <input type="checkbox" id="check_all_2" onchange="toggleCheckAll(2, this.checked)">
                <span>Pilih Semua #2</span>
            </label>
        </div>

        <h1 class="approval-rs-title">Persetujuan Tanggal Reschedule</h1>

        <div id="loading-info" class="approval-rs-loading" style="display:none;">
            <i class="fas fa-spinner fa-spin"></i>
            Memproses data persetujuan reschedule.
        </div>

        <div id="main-display" class="approval-rs-table-wrap">
            <table class="approval-rs-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="no-col">No.</th>
                        <th colspan="2">Status Persetujuan</th>
                        <th rowspan="2" class="keterangan-col">Uraian / Keterangan</th>
                        <th rowspan="2" class="status-col">Status<br>Reschedule 1</th>
                        <th rowspan="2" class="date-col">Tanggal<br>Reschedule 1</th>
                        <th rowspan="2" class="status-col">Status<br>Reschedule 2</th>
                        <th rowspan="2" class="date-col">Tanggal<br>Reschedule 2</th>
                        <th rowspan="2" class="user-col">Petugas</th>
                        <th rowspan="2" class="date-col">Tanggal Entry</th>
                    </tr>
                    <tr>
                        <th class="approval-col">1#</th>
                        <th class="approval-col">2#</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="10" class="approval-rs-empty">Memuat data persetujuan reschedule.</td>
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
        loadApprovalTglReschedule();
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

    function isStatusActive(value) {
        return String(value || '').toUpperCase().trim() === 'Y';
    }

    function statusBadgeHtml(value) {
        if (isStatusActive(value)) {
            return '<span class="approval-rs-badge active">Aktif</span>';
        }

        return '<span class="approval-rs-badge pending">Belum</span>';
    }

    function loadApprovalTglReschedule() {
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

                var message = 'Data persetujuan tanggal reschedule belum dapat dimuat.';

                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    message += ' ' + xhr.responseJSON.message;
                }

                $('#main-display').html(
                    '<div class="approval-rs-alert">' +
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

        html += '<table class="approval-rs-table">';
        html += '<thead>';
        html += '<tr>';
        html += '<th rowspan="2" class="no-col">No.</th>';
        html += '<th colspan="2">Status Persetujuan</th>';
        html += '<th rowspan="2" class="keterangan-col">Uraian / Keterangan</th>';
        html += '<th rowspan="2" class="status-col">Status<br>Reschedule 1</th>';
        html += '<th rowspan="2" class="date-col">Tanggal<br>Reschedule 1</th>';
        html += '<th rowspan="2" class="status-col">Status<br>Reschedule 2</th>';
        html += '<th rowspan="2" class="date-col">Tanggal<br>Reschedule 2</th>';
        html += '<th rowspan="2" class="user-col">Petugas</th>';
        html += '<th rowspan="2" class="date-col">Tanggal Entry</th>';
        html += '</tr>';
        html += '<tr>';
        html += '<th class="approval-col">1#</th>';
        html += '<th class="approval-col">2#</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';

        if (rows.length < 1) {
            html += '<tr>';
            html += '<td colspan="10" class="approval-rs-empty">Tidak ada data persetujuan reschedule yang perlu ditampilkan.</td>';
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
                html += '<td class="status-col">' + statusBadgeHtml(item.STS_RESCHEDULE1) + '</td>';
                html += '<td class="date-col">' + escapeHtml(formatDateIndo(item.TGL_RESCHEDULE1)) + '</td>';
                html += '<td class="status-col">' + statusBadgeHtml(item.STS_RESCHEDULE2) + '</td>';
                html += '<td class="date-col">' + escapeHtml(formatDateIndo(item.TGL_RESCHEDULE2)) + '</td>';
                html += '<td class="user-col">' + escapeHtml(valueOrBlank(item.USER_ENTRY)) + '</td>';
                html += '<td class="date-col">' + escapeHtml(formatDateIndo(item.TGL_ENTRY)) + '</td>';
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
