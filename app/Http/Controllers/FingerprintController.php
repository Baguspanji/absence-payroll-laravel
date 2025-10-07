<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FingerprintController extends Controller
{
    public function receiveData(Request $request)
    {
        Log::info('Fingerprint data received', [
            'method' => $request->method(),
            'path'   => $request->path(),
            'query'  => $request->query(),
            'body'   => $request->all(),
        ]);

        // Handle different ADMS request types
        if ($request->path() == 'iclock/getrequest') {
            return $this->handleGetRequest($request);
        } elseif ($request->path() == 'iclock/cdata') {
            return $this->handleCData($request);
        }

        return response('OK');
    }

    private function handleGetRequest(Request $request)
    {
        // Extract device information
        $deviceSN   = $request->query('SN');
        $deviceInfo = $request->query('INFO');

        // Store or update device information
        DB::table('devices')->updateOrInsert(
            ['serial_number' => $deviceSN],
            [
                'info'       => $deviceInfo,
                'last_seen'  => now(),
                'updated_at' => now(),
            ]
        );

        // Return appropriate response for the device
        return response('OK');
    }

    private function handleCData(Request $request)
    {
        $deviceSN = $request->query('SN');
        $data     = $request->input('data');

        if (empty($data)) {
            return response('OK');
        }

        // Process attendance data
        foreach ($data as $record) {
            // Example format: "TRANSACT FP 1 0 2023-10-07 08:30:25 123456"
            // Where 123456 would be the employee ID/PIN
            $parts = explode(' ', $record);

            if (count($parts) >= 7 && $parts[0] == 'TRANSACT') {
                $timestamp   = $parts[5] . ' ' . $parts[6];
                $employeePin = $parts[7] ?? null;

                if ($employeePin) {
                    // Store in attendance table
                    DB::table('attendances')->insert([
                        'employee_nip' => $employeePin,
                        'device_sn'    => $deviceSN,
                        'timestamp'    => Carbon::parse($timestamp),
                        'is_processed' => false,
                        'created_at'   => now(),
                    ]);
                }
            }
        }

        return response('OK');
    }
}
