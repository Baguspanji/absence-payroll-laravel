<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi - {{ $employee->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        .container {
            padding: 20px;
            max-width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header .subtitle {
            font-size: 10px;
            color: #666;
        }

        .employee-info {
            margin-bottom: 15px;
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 6px 10px;
            border: none;
            font-size: 11px;
        }

        .info-table td:first-child {
            font-weight: bold;
            width: 50%;
        }

        .info-table td:last-child {
            padding-left: 10px;
        }

        .date-range {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #2c3e50;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #ddd;
        }

        td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 10px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f0f0f0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .summary-section {
            margin-top: 20px;
            padding: 10px;
            background-color: #ecf0f1;
            border-radius: 4px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 8px 10px;
            border: none;
            font-size: 11px;
        }

        .summary-table td:first-child {
            font-weight: bold;
            width: 70%;
        }

        .summary-table td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .summary-table tr {
            border-bottom: 1px dotted #999;
        }

        .summary-table tr:last-child {
            border-bottom: none;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-around;
        }

        .signature-box {
            width: 150px;
            text-align: center;
            font-size: 9px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            margin-bottom: 5px;
        }

        .empty-message {
            text-align: center;
            padding: 20px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>REKAP ABSENSI KARYAWAN</h1>
            <div class="subtitle">Attendance Summary Report</div>
        </div>

        <!-- Employee Information -->
        <div class="employee-info">
            <table class="info-table">
                <tr>
                    <td>Nama Karyawan</td>
                    <td>: {{ $employee->name }}</td>
                </tr>
                <tr>
                    <td>NIP (Nomor Induk Pegawai)</td>
                    <td>: {{ $employee->nip }}</td>
                </tr>
                <tr>
                    <td>Cabang</td>
                    <td>: {{ $employee->branch?->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Posisi</td>
                    <td>: {{ $employee->position ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <!-- Date Range -->
        <div class="date-range">
            Periode: {{ $startDate->translatedFormat('d F Y') }} - {{ $endDate->translatedFormat('d F Y') }}
        </div>

        <!-- Attendance Table -->
        @if ($attendanceSummaries->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">No.</th>
                        <th style="width: 12%;">Tanggal</th>
                        <th style="width: 12%;">Shift</th>
                        <th style="width: 12%;">Jam Masuk</th>
                        <th style="width: 12%;">Jam Pulang</th>
                        <th style="width: 12%;">Total Jam</th>
                        <th style="width: 12%;">Terlambat</th>
                        <th style="width: 12%;">Lembur</th>
                        <th style="width: 8%;">Frekuensi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendanceSummaries as $key => $summary)
                        <tr>
                            <td class="text-center">{{ $key + 1 }}</td>
                            <td class="text-center">
                                {{ \Carbon\Carbon::parse($summary->date)->translatedFormat('d M Y') }}</td>
                            <td class="text-center">{{ $summary->shift_name ?? '-' }}</td>
                            <td class="text-center">{{ $summary->clock_in ?? '-' }}</td>
                            <td class="text-center">{{ $summary->clock_out ?? '-' }}</td>
                            <td class="text-right">{{ number_format($summary->work_hours, 2) }} jam</td>
                            <td class="text-right">{{ $summary->late_minutes }} menit</td>
                            <td class="text-right">{{ number_format($summary->overtime_hours, 2) }} jam</td>
                            <td class="text-center">{{ $summary->total_attendances }}x</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-message">
                Tidak ada data absensi untuk periode yang dipilih
            </div>
        @endif

        <!-- Summary Section -->
        @if ($attendanceSummaries->count() > 0)
            <div class="summary-section">
                <h3 style="margin-bottom: 10px; font-size: 12px;">RINGKASAN PERIODE</h3>

                <table class="summary-table">
                    <tr>
                        <td>Total Hari Kerja :</td>
                        <td>{{ $attendanceSummaries->count() }} hari</td>
                    </tr>
                    <tr>
                        <td>Total Jam Kerja :</td>
                        <td>{{ number_format($totalWorkHours, 2) }} jam</td>
                    </tr>
                    <tr>
                        <td>Total Keterlambatan :</td>
                        <td>{{ $totalLateMinutes }} menit</td>
                    </tr>
                    <tr>
                        <td>Total Lembur :</td>
                        <td>{{ number_format($totalOvertimeHours, 2) }} jam</td>
                    </tr>
                    <tr>
                        <td>Total Frekuensi Absensi :</td>
                        <td>{{ $totalAttendances }}x</td>
                    </tr>
                </table>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Dokumen ini dicetak secara otomatis pada {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}</p>
        </div>
    </div>
</body>

</html>
