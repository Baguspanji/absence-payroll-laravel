<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FingerprintController extends Controller
{
    /**
     * Menangani request check-in dari mesin (GET /iclock/getrequest).
     * Server akan memberikan perintah kembali ke mesin.
     */
    public function getRequest(Request $request)
    {
        // Log bahwa mesin berhasil check-in
        // Log::info('Device check-in:', $request->query());

        // Perintah untuk mesin: "Kirimkan data absensi (ATTLog) yang baru"
        // C:1: adalah ID perintah, ini bisa random.
        // $response = "C:1:DATA QUERY ATTLog startDate=" . now()->subDay()->format('Y-m-d') . "\tendDate=" . now()->format('Y-m-d');
        $response = "OK";

        return $this->getResponse($response);
    }

    /**
     * Menangani kiriman data dari mesin (POST /iclock/cdata).
     */
    public function cData(Request $request)
    {
        // ==================================================================
        // BARIS BARU: Ambil Serial Number (SN) dari query string URL
        $serialNumber = $request->query('SN');
        // ==================================================================

        $rawData = $request->getContent();
        Log::info("Menerima data dari SN: {$serialNumber}", ['body' => $rawData]);

        $lines = explode("\r\n", $rawData);

        foreach ($lines as $line) {
            if (trim($line) === "" || str_starts_with($line, 'OPLOG')) {
                continue;
            }

            $data = explode("\t", $line);

            if (count($data) >= 4) {
                $nip = $data[0];
                $timestamp = $data[1];
                $status_scan = $data[3];

                DB::table('attendances')->insert([
                    'employee_nip' => $nip,
                    'timestamp'    => $timestamp,
                    'status_scan'  => $status_scan,
                    'device_sn'    => $serialNumber, // <-- SIMPAN SN DI SINI
                    'is_processed' => false,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        return $this->getResponse("OK");
    }

    private function getResponse($response, $status = 200)
    {
        // set date to GMT
        date_default_timezone_set('GMT');
        $date = date('D, d M Y H:i:s T');

        // set date to Asia/Jakarta
        date_default_timezone_set('Asia/Jakarta');

        $status = $response ? 200 : 400;

        return response($response, $status)
            ->header('Date', $date)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Length', strlen($response))
            ->header('Connection', 'close')
            ->header('Pragma', 'no-cache')
            ->header('Cache-Control', 'no-store');
    }
}
