<?php
namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FingerprintController extends Controller
{
    public function getCData()
    {
        $serialNumber = request()->query('SN');
        // $SV  = request()->query('pushver');
        // $PCK = request()->query('pushcommkey');

        $device = Device::where('serial_number', $serialNumber)->first();

        try {
            DB::beginTransaction();
            $response = null;

            if (! $device) {
                $device = Device::create([
                    'name'          => 'Unknown Device',
                    'serial_number' => $serialNumber,
                ]);

                Log::info("Device baru terdaftar: {$serialNumber}");
            } else {
                if (! $device->is_active) {
                    $response = null;
                } else {
                    $device->last_sync_at = now();
                    $device->save();

                    $response = "GET OPTION FROM: $device->serial_number\n";
                    $response .= "Stamp=9999\n";
                    $response .= "OpStamp=" . time() . "\n";
                    // $response .= "ATTLOGStamp=None\n";
                    // $response .= "OPERLOGStamp=9999\n";
                    // $response .= "ATTPHOTOStamp=None\n";
                    $response .= "ErrorDelay=60\n";
                    $response .= "Delay=30\n";
                    // $response .= "ResLogDay=18250\n";
                    // $response .= "ResLogDelCount=10000\n";
                    // $response .= "ResLogCount=50000\n";
                    $response .= "TransTimes=00:00;14:05\n";
                    $response .= "Transinterval=1\n";
                    // $response .= "TransFlag=TransData AttLog OpLog AttPhoto EnrollUser ChgUser EnrollFP ChgFP UserPic\n";
                    $response .= "TransFlag=1111000000\n";
                    $response .= "TimeZone=7\n";
                    $response .= "Realtime=1\n";
                    $response .= "Encrypt=0\n";
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
        }

        return $this->getResponse($response);
    }

    /**
     * Menangani request check-in dari mesin (GET /iclock/getrequest).
     * Server akan memberikan perintah kembali ke mesin.
     */
    public function getRequest(Request $request)
    {
        $serialNumber = $request->query('SN');

        $device = Device::where([
            'serial_number' => $serialNumber,
            'is_active'     => true,
        ])->first();

        $response = null;
        try {
            DB::beginTransaction();

            if ($device) {
                $response = "OK";

                $command = cache()->get("device_command_{$serialNumber}");

                if ($command) {
                    $response = $command;

                    Log::info("Mengirim perintah ke SN: {$serialNumber}", ['command' => $command]);

                    // clear command after sending
                    cache()->forget("device_command_{$serialNumber}");
                }

                $device->update([
                    'last_sync_at' => now(),
                ]);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
        }

        return $this->getResponse($response);
    }

    /**
     * Menangani kiriman data dari mesin (POST /iclock/cdata).
     */
    public function cData(Request $request)
    {
        $serialNumber = $request->query('SN');
        $table        = request()->query('table');

        $device = Device::where([
            'serial_number' => $serialNumber,
            'is_active'     => true,
        ])->first();

        if ($device && $table == 'ATTLOG') {
            $rawData = $request->getContent();
            Log::info("Menerima data dari SN: {$serialNumber}", ['body' => $rawData]);

            $lines = explode("\r\n", $rawData);

            foreach ($lines as $line) {
                if (trim($line) === "" || str_starts_with($line, 'OPLOG')) {
                    continue;
                }

                $data = explode("\t", $line);

                if (count($data) >= 4) {
                    $nip         = $data[0];
                    $timestamp   = $data[1];
                    $status_scan = $data[3];

                    // Cek attendance terakhir karyawan pada hari yang sama
                    $today          = date('Y-m-d', strtotime($timestamp));
                    $lastAttendance = DB::table('attendances')
                        ->where('employee_nip', $nip)
                        ->whereDate('timestamp', $today)
                        ->orderBy('timestamp', 'desc')
                        ->first();

                                       // Tentukan status berdasarkan attendance terakhir
                    $actualStatus = 0; // Default check-in
                    if ($lastAttendance) {
                        $lastTimestamp    = strtotime($lastAttendance->timestamp);
                        $currentTimestamp = strtotime($timestamp);
                        $timeDiff         = ($currentTimestamp - $lastTimestamp) / 3600; // Selisih dalam jam

                        // Jika selisih kurang dari 1 jam, skip data (kemungkinan duplikasi)
                        if ($timeDiff < 1) {
                            continue; // Skip record ini
                        }

                        // Jika attendance terakhir adalah check-in (1), maka ini check-out (0)
                        $actualStatus = $lastAttendance->status_scan == 1 ? 1 : 0;
                    }

                    DB::table('attendances')->insert([
                        'employee_nip' => $nip,
                        'timestamp'    => $timestamp,
                        'status_scan'  => $actualStatus, // Gunakan status yang sudah dihitung
                                                         // 'original_status' => $status_scan,  // Simpan status asli dari mesin
                        'device_sn'    => $serialNumber,
                        'is_processed' => false,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
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
