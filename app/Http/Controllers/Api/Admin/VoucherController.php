<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel; 

class VoucherController extends Controller
{
    // Melihat semua jenis voucher (Bisa diakses siswa untuk memilih)
    public function index()
    {
        $vouchers = Voucher::withCount(['tokens' => function($query) {
            $query->where('status', 'available');
        }])->get();

        return response()->json([
            'success' => true,
            'data' => $vouchers
        ]);
    }

    // Menambah jenis voucher baru (Admin Only)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $voucher = Voucher::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Jenis voucher berhasil dibuat',
            'data' => $voucher
        ], 201);
    }

    // Menghapus jenis voucher (Admin Only)
    public function destroy($id)
    {
        $voucher = Voucher::find($id);
        if (!$voucher) {
            return response()->json(['message' => 'Voucher tidak ditemukan'], 404);
        }

        $voucher->delete();
        return response()->json(['message' => 'Voucher berhasil dihapus']);
    }

   
}