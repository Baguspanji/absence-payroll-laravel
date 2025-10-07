<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji {{ $payroll->employee->name }} - {{ $payroll->period_start->format('F Y') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto max-w-2xl my-8 p-8 bg-white shadow-lg">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold">SLIP GAJI KARYAWAN</h1>
            <p class="text-gray-500">Periode: {{ \Carbon\Carbon::parse($payroll->period_start)->translatedFormat('F Y') }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-8">
            <div>
                <h3 class="font-bold">NAMA PERUSAHAAN</h3>
                <p>Alamat Perusahaan Anda</p>
            </div>
            <div class="text-right">
                <h3 class="font-bold">{{ $payroll->employee->name }}</h3>
                <p>NIP: {{ $payroll->employee->nip }}</p>
                <p>Jabatan: {{ $payroll->employee->position }}</p> {{-- Sesuaikan jika relasi jabatan ada --}}
            </div>
        </div>

        <div class="grid grid-cols-2 gap-8">
            {{-- PENDAPATAN --}}
            <div>
                <h3 class="font-bold border-b pb-2 mb-2">PENDAPATAN</h3>
                @foreach ($payroll->details->where('type', 'earning') as $detail)
                    <div class="flex justify-between py-1">
                        <span>{{ $detail->description }}</span>
                        <span>Rp {{ number_format($detail->amount, 0, ',', '.') }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between font-bold border-t pt-2 mt-2">
                    <span>TOTAL PENDAPATAN</span>
                    <span>Rp
                        {{ number_format($payroll->details->where('type', 'earning')->sum('amount'), 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- POTONGAN --}}
            <div>
                <h3 class="font-bold border-b pb-2 mb-2">POTONGAN</h3>
                @foreach ($payroll->details->where('type', 'deduction') as $detail)
                    <div class="flex justify-between py-1">
                        <span>{{ $detail->description }}</span>
                        <span>Rp {{ number_format($detail->amount, 0, ',', '.') }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between font-bold border-t pt-2 mt-2">
                    <span>TOTAL POTONGAN</span>
                    <span>Rp
                        {{ number_format($payroll->details->where('type', 'deduction')->sum('amount'), 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-gray-100 p-4 text-center">
            <h3 class="font-bold text-lg">GAJI BERSIH (NET SALARY)</h3>
            <p class="text-2xl font-bold">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</p>
        </div>
    </div>
</body>

</html>
