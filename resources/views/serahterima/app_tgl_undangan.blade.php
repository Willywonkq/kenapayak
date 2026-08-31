@extends('layouts.template')

@section('content')

<style>
    .approval-undangan-page {
        --au-primary: #2563eb;
        --au-primary-dark: #1d4ed8;
        --au-primary-soft: #eff6ff;
        --au-primary-soft-strong: #dbeafe;
        --au-success: #15803d;
        --au-success-soft: #eaf8ef;
        --au-pending: #a04a00;
        --au-pending-soft: #fff2e7;
        --au-danger: #dc2626;
        --au-danger-soft: #fef2f2;
        --au-text: #172033;
        --au-muted: #667085;
        --au-line: #e5e7eb;
        --au-line-strong: #dbe3ef;
        --au-card: #ffffff;

        width: 100%;
        min-height: calc(100vh - 120px);
        padding: 10px 0 28px;
        color: var(--au-text);
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        background: #f8fafc;
    }

    .approval-undangan-page,
    .approval-undangan-page * {
        box-sizing: border-box;
    }

    .approval-undangan-frame {
        width: 100%;
        min-height: 620px;
        overflow: hidden;
        border: 1px solid var(--au-line-strong);
        border-radius: 18px;
        background: var(--au-card);
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
    }

    .approval-undangan-checkbar {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 58px;
        padding: 12px 18px;
        border-bottom: 1px solid var(--au-line);
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        font-size: 12px;
    }

    .approval-undangan-checkbar label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        padding: 8px 13px;
        border: 1px solid var(--au-line-strong);
        border-radius: 999px;
        background: #ffffff;
        color: #344054;
        font-weight: 650;
        cursor: pointer;
        transition: background-color 0.18s ease,
                    border-color 0.18s ease,
                    color 0.18s ease,
                    box-shadow 0.18s ease;
    }

    .approval-undangan-checkbar label:hover {
        border-color: #93c5fd;
        background: var(--au-primary-soft);
        color: var(--au-primary-dark);
        box-shadow: 0 3px 9px rgba(37, 99, 235, 0.10);
    }

    .approval-undangan-checkbar label:has(input:checked) {
        border-color: #93c5fd;
        background: var(--au-primary-soft-strong);
        color: #1e40af;
    }

    /*
     * Checkbox direstyle jadi kotak modern bercentang biru, konsisten
     * dengan Approval Entry Final QC. Elemen <input type="checkbox">
     * dan id/class yang dipakai JS tidak diubah sama sekali.
     */
    .approval-undangan-checkbar input[type="checkbox"],
    .approval-undangan-table input[type="checkbox"] {
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

    .approval-undangan-checkbar input[type="checkbox"]:hover,
    .approval-undangan-table input[type="checkbox"]:hover {
        border-color: var(--au-primary);
    }

    .approval-undangan-checkbar input[type="checkbox"]:checked,
    .approval-undangan-table input[type="checkbox"]:checked {
        border-color: var(--au-primary);
        background-color: var(--au-primary);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='white' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.6' d='M3.2 8.2l3 3.1 6.6-7'/%3E%3C/svg%3E");
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .approval-undangan-checkbar input[type="checkbox"]:focus-visible,
    .approval-undangan-table input[type="checkbox"]:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
    }

    .approval-undangan-title {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
        padding: 20px 22px 6px;
        color: var(--au-text);
        font-family: "Segoe UI Semibold", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.25;
        letter-spacing: -0.2px;
    }

    .approval-undangan-title::before {
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

    .approval-undangan-title::after {
        content: "Daftar persetujuan tanggal undangan serah terima.";
        position: absolute;
        top: 100%;
        left: 22px;
        display: block;
        margin-top: 4px;
        color: var(--au-muted);
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 12.5px;
        font-weight: 500;
        letter-spacing: 0;
    }

    .approval-undangan-table-wrap {
        width: calc(100% - 36px);
        margin: 30px 18px 22px;
        overflow: auto;
        border: 1px solid var(--au-line-strong);
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 5px 16px rgba(15, 23, 42, 0.04);
    }

    .approval-undangan-table {
        width: max-content;
        min-width: 1320px;
        border-collapse: separate;
        border-spacing: 0;
        background: #ffffff;
        color: #344054;
        font-size: 10.5px;
    }

    .approval-undangan-table th {
        position: sticky;
        top: 0;
        z-index: 2;
        padding: 10px;
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

    .approval-undangan-table thead tr:nth-child(2) th {
        top: 39px;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        color: #475467;
        font-size: 9.5px;
        font-weight: 900;
    }

    .approval-undangan-table th:last-child,
    .approval-undangan-table td:last-child {
        border-right: 0;
    }

    .approval-undangan-table td {
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

    .approval-undangan-table tbody tr:nth-child(even) td {
        background: #fbfcfe;
    }

    .approval-undangan-table tbody tr:hover td {
        background: #f0f7ff;
    }

    .approval-undangan-table tbody tr:hover td:first-child {
        box-shadow: inset 4px 0 0 #2563eb;
        color: #1d4ed8;
    }

    .approval-undangan-table .no-col {
        width: 48px;
        color: #475467;
        text-align: center;
        font-weight: 750;
        font-variant-numeric: tabular-nums;
    }

    .approval-undangan-table .approval-col {
        width: 68px;
        text-align: center;
    }

    .approval-undangan-table .keterangan-col {
        width: 420px;
        max-width: 420px;
        color: #344054;
        line-height: 1.45;
        white-space: normal;
        text-align: left;
    }

    .approval-undangan-table .status-col {
        width: 126px;
        text-align: center;
    }

    .approval-undangan-table .date-col {
        width: 132px;
        color: #475467;
        text-align: center;
        font-variant-numeric: tabular-nums;
    }

    /* Dipertahankan karena pada tampilan desktop terdapat satu kolom tanpa judul. */
    .approval-undangan-table .legacy-empty-col {
        width: 118px;
        min-width: 118px;
        text-align: center;
    }

    .approval-undangan-table .user-col {
        width: 132px;
        color: #172033;
        text-align: center;
        font-weight: 800;
    }

    .approval-undangan-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 78px;
        padding: 5px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
    }

    .approval-undangan-badge.approved {
        background: var(--au-success-soft);
        color: var(--au-success);
    }

    .approval-undangan-badge.pending {
        background: var(--au-pending-soft);
        color: var(--au-pending);
    }

    .approval-undangan-empty {
        height: 260px !important;
        background: #ffffff !important;
        color: #64748b !important;
        text-align: center;
        font-style: normal;
        font-weight: 550;
    }

    .approval-undangan-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 16px 18px 0;
        padding: 10px 13px;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        background: var(--au-primary-soft);
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 650;
    }

    .approval-undangan-loading i {
        color: var(--au-primary);
    }

    .approval-undangan-alert {
        margin: 16px;
        padding: 13px 15px;
        border: 1px solid #fecaca;
        border-radius: 12px;
        background: var(--au-danger-soft);
        color: var(--au-danger);
        font-size: 12px;
        font-weight: 650;
    }

    @media (max-width: 768px) {
        .approval-undangan-checkbar {
            flex-wrap: wrap;
        }

        .approval-undangan-title {
            padding: 18px 16px 4px;
            font-size: 18px;
        }

        .approval-undangan-title::after {
            left: 16px;
        }

        .approval-undangan-table-wrap {
            width: calc(100% - 24px);
            margin: 28px 12px 18px;
        }
    }
</style>

<div class="approval-undangan-page">
    <input
        type="hidden"
        id="perusahaan"
        value="{{ session('kd_unit') ?? session('kd_perusahaan') ?? 'DTSA' }}"
    >

    <div class="approval-undangan-frame">
        <div class="approval-undangan-checkbar">
            <label>
                <input
                    type="checkbox"
                    id="check_all_1"
                    onchange="toggleCheckAll(1, this.checked)"
                >
                <span>Pilih Semua #1</span>
            </label>

            <label>
                <input
                    type="checkbox"
                    id="check_all_2"
                    onchange="toggleCheckAll(2, this.checked)"
                >
                <span>Pilih Semua #2</span>
            </label>
        </div>

        <h1 class="approval-undangan-title">Persetujuan Tanggal Undangan</h1>

        <div id="loading-info" class="approval-undangan-loading" style="display:none;">
            <i class="fas fa-spinner fa-spin"></i>
            Memproses data persetujuan tanggal undangan.
        </div>

        <div id="main-display" class="approval-undangan-table-wrap">
            <table class="approval-undangan-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="no-col">No.</th>
                        <th colspan="2">Approval</th>
                        <th rowspan="2" class="keterangan-col">Keterangan</th>
                        <th rowspan="2" class="status-col">Status<br>Undangan 2</th>
                        <th rowspan="2" class="date-col">Tanggal<br>Undangan 2</th>
                        <th rowspan="2" class="status-col">Status<br>Undangan 3</th>
                        <th rowspan="2" class="date-col">Tanggal<br>Undangan 3</th>
                        <th rowspan="2" class="legacy-empty-col" aria-label="Kolom kosong"></th>
                        <th rowspan="2" class="user-col">User</th>
                        <th rowspan="2" class="date-col">Tgl Entry</th>
                    </tr>
                    <tr>
                        <th class="approval-col">1#</th>
                        <th class="approval-col">2#</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="11" class="approval-undangan-empty">
                            Memuat data persetujuan tanggal undangan.
                        </td>
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
        loadApprovalTglUndangan();

        $(document).on('change', '.approval-check', function () {
            syncCheckAllState();
        });
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

    /*
     * Model lama menggunakan APPROVE1 = 'T', sedangkan view referensi
     * menggunakan 'Y'. Keduanya diterima agar kompatibel.
     */
    function isApproved(value) {
        var status = String(value || '').toUpperCase().trim();

        return status === 'Y' ||
            status === 'T' ||
            status === '1' ||
            status === 'TRUE';
    }

    function statusBadgeHtml(value) {
        if (isApproved(value)) {
            return '<span class="approval-undangan-badge approved">Disetujui</span>';
        }

        return '<span class="approval-undangan-badge pending">Belum</span>';
    }

    function loadApprovalTglUndangan() {
        $('#loading-info').show();
        $('#check_all_1, #check_all_2').prop({
            checked: false,
            indeterminate: false
        });

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

                var message = 'Data persetujuan tanggal undangan belum dapat dimuat.';

                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    message += ' ' + xhr.responseJSON.message;
                }

                $('#main-display').html(
                    '<div class="approval-undangan-alert">' +
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

        html += '<table class="approval-undangan-table">';
        html += '<thead>';
        html += '<tr>';
        html += '<th rowspan="2" class="no-col">No.</th>';
        html += '<th colspan="2">Approval</th>';
        html += '<th rowspan="2" class="keterangan-col">Keterangan</th>';
        html += '<th rowspan="2" class="status-col">Status<br>Undangan 2</th>';
        html += '<th rowspan="2" class="date-col">Tanggal<br>Undangan 2</th>';
        html += '<th rowspan="2" class="status-col">Status<br>Undangan 3</th>';
        html += '<th rowspan="2" class="date-col">Tanggal<br>Undangan 3</th>';
        html += '<th rowspan="2" class="legacy-empty-col" aria-label="Kolom kosong"></th>';
        html += '<th rowspan="2" class="user-col">User</th>';
        html += '<th rowspan="2" class="date-col">Tgl Entry</th>';
        html += '</tr>';
        html += '<tr>';
        html += '<th class="approval-col">1#</th>';
        html += '<th class="approval-col">2#</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';

        if (rows.length < 1) {
            html += '<tr>';
            html += '<td colspan="11" class="approval-undangan-empty">';
            html += 'Tidak ada data persetujuan tanggal undangan yang perlu ditampilkan.';
            html += '</td>';
            html += '</tr>';
        } else {
            $.each(rows, function (index, item) {
                item = item || {};

                var ppjbId = escapeHtml(valueOrBlank(item.PPJB_ID));
                var urut = escapeHtml(valueOrBlank(item.URUT));

                html += '<tr data-ppjb-id="' + ppjbId + '" data-urut="' + urut + '">';
                html += '<td class="no-col">' + (index + 1) + '</td>';

                /* Approval #1 pada halaman desktop dipasangkan dengan APPROVE2. */
                html += '<td class="approval-col">';
                html += '<input type="checkbox" class="approval-check approval-1" ';
                html += 'name="approval_1[]" value="' + ppjbId + '" ';
                html += 'aria-label="Approval nomor 1" ';
                html += isApproved(item.APPROVE2) ? 'checked' : '';
                html += '>';
                html += '</td>';

                /* Approval #2 pada halaman desktop dipasangkan dengan APPROVE3. */
                html += '<td class="approval-col">';
                html += '<input type="checkbox" class="approval-check approval-2" ';
                html += 'name="approval_2[]" value="' + ppjbId + '" ';
                html += 'aria-label="Approval nomor 2" ';
                html += isApproved(item.APPROVE3) ? 'checked' : '';
                html += '>';
                html += '</td>';

                html += '<td class="keterangan-col">' +
                    escapeHtml(valueOrBlank(item.KETERANGAN)) +
                    '</td>';

                html += '<td class="status-col">' +
                    statusBadgeHtml(item.APPROVE2) +
                    '</td>';

                html += '<td class="date-col">' +
                    escapeHtml(formatDateIndo(item.TANGGAL2)) +
                    '</td>';

                html += '<td class="status-col">' +
                    statusBadgeHtml(item.APPROVE3) +
                    '</td>';

                html += '<td class="date-col">' +
                    escapeHtml(formatDateIndo(item.TANGGAL3)) +
                    '</td>';

                /* Kolom kosong mengikuti struktur grid aplikasi desktop. */
                html += '<td class="legacy-empty-col"></td>';

                html += '<td class="user-col">' +
                    escapeHtml(valueOrBlank(item.USER_ENTRY)) +
                    '</td>';

                html += '<td class="date-col">' +
                    escapeHtml(formatDateIndo(item.TGL_ENTRY)) +
                    '</td>';

                html += '</tr>';
            });
        }

        html += '</tbody>';
        html += '</table>';

        $('#main-display').html(html);
        syncCheckAllState();
    }

    function toggleCheckAll(level, checked) {
        var selector = level === 1 ? '.approval-1' : '.approval-2';

        $(selector).prop('checked', checked);
        syncCheckAllState();
    }

    function syncCheckAllState() {
        syncOneCheckAll(1, '.approval-1');
        syncOneCheckAll(2, '.approval-2');
    }

    function syncOneCheckAll(level, selector) {
        var $items = $(selector);
        var $checkAll = $('#check_all_' + level);
        var total = $items.length;
        var checked = $items.filter(':checked').length;

        $checkAll.prop('checked', total > 0 && checked === total);
        $checkAll.prop('indeterminate', checked > 0 && checked < total);
    }
</script>
@endsection
