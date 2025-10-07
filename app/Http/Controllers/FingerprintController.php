<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FingerprintController extends Controller
{
    public function receiveData(Request $request)
    {
        // Ambil data mentah dari body request
        $rawData = $request->getContent();

        // Log data mentah untuk debugging
        Log::info('Data dari fingerprint: ' . $rawData);

        // Data dari mesin biasanya berupa plain text, perlu kita parsing
        // Formatnya kira-kira: "PIN=1234\tTime=2025-10-07 17:30:00\tStatus=1"
        $lines = explode("\r\n", $rawData);

        foreach ($lines as $line) {
            if (trim($line) != "") {
                $data  = [];
                $pairs = explode("\t", $line);
                foreach ($pairs as $pair) {
                    list($key, $value) = explode("=", $pair, 2);
                    $data[$key]        = $value;
                }

                // Simpan ke tabel 'attendances'
                DB::table('attendances')->insert([
                    'employee_nip' => $data['PIN'], // PIN di mesin adalah NIP karyawan
                    'timestamp'    => $data['Time'],
                    'status_scan'  => $data['Status'], // Status: 0=Masuk, 1=Pulang, dll.
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        // Mesin butuh response "OK" agar tahu datanya sudah diterima
        return response("OK");
    }
}
