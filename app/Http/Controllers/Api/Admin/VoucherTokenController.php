<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VoucherToken;
use App\Imports\VoucherTokenImport;
use Maatwebsite\Excel\Facades\Excel;

class VoucherTokenController extends Controller
{
    public function index() {
        $tokens = VoucherToken::with('voucher')->get();
        return response()->json(['data' => $tokens]);
    }


  public function importToken(Request $request)
{
    ini_set('max_execution_time', 12000);

    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls',
    ]);

    $import = new VoucherTokenImport;

    Excel::import($import, $request->file('file'));

    $failures = $import->failures();
    $failed   = count($failures);
    $PAID  = $import->getRowCount();
    $total    = $PAID + $failed;

    if ($failures->isNotEmpty()) {
        $errorData = [];
        foreach ($failures as $failure) {
            $errorData[] = [
                'row'       => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors'    => $failure->errors(),
                'values'    => $failure->values(),
            ];
        }

        return response()->json([
            'message'       => 'Import Token selesai dengan beberapa error.',
            'total_rows'    => $total,
            'PAID_count' => $PAID,
            'failed_count'  => $failed,
            'failures'      => $errorData
        ], 206);
    }

    return response()->json([
        'message'       => 'Import Token berhasil semua!',
        'total_rows'    => $total,
        'PAID_count' => $PAID,
        'failed_count'  => $failed
    ], 200);
}
    public function destroy($id) {
        $token = VoucherToken::findOrFail($id);
        $token->delete();
        return response()->json(['message' => 'Token Berhasil Dihapus']);
    }
}