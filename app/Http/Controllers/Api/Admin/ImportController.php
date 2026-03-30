<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\VoucherTokenImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Voucher;

class ImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'voucher_id' => 'required|exists:vouchers,id',
            'file'       => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new VoucherTokenImport($request->voucher_id), $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Token voucher berhasil di-import!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data: ' . $e->getMessage()
            ], 500);
        }
    }
}