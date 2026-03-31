<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index() {
    // Hanya tampilkan voucher yang punya stok available
    $vouchers = \App\Models\Voucher::withCount(['tokens' => function($q) {
        $q->where('status', 'available');
    }])->get();

    return response()->json(['data' => $vouchers]);
}
}
