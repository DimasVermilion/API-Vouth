<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;
use Carbon\Carbon;

class PaymentController extends Controller
{
    private $apiInstance;

   public function __construct()
{
    $config = new Configuration();
    $config->setApiKey(config('services.xendit.secret_key'));
    
    $this->apiInstance = new InvoiceApi(null, $config);
}
    public function createInvoice(Request $request)
    {
        $request->validate([
            'voucher_id' => 'required|exists:vouchers,id',
        ]);

        $voucher = Voucher::findOrFail($request->voucher_id);
        
        $hasToken = VoucherToken::where('voucher_id', $voucher->id)
                                ->where('status', 'available')
                                ->exists();

        if (!$hasToken) {
            return response()->json(['message' => 'Stok voucher ini sudah habis!'], 400);
        }

        $externalId = 'VOUTH-' . time() . '-' . auth()->id();

        $createInvoiceRequest = new CreateInvoiceRequest([
            'external_id' => $externalId,
            'amount' => (double) $voucher->price,
            'description' => 'Pembelian ' . $voucher->name . ' oleh ' . auth()->user()->name,
            'currency' => 'IDR',
            'invoice_duration' => 600, // 10 menit
            'reminder_control' => ['enabled' => true],
        ]);

        try {
    $result = $this->apiInstance->createInvoice($createInvoiceRequest);

    // Cari link QRIS di dalam response available_qr_codes
    $qrCodeUrl = null;
    if (isset($result['available_qr_codes'])) {
        foreach ($result['available_qr_codes'] as $qr) {
            if ($qr['qr_code_type'] === 'QRIS') {
                $qrCodeUrl = $qr['qr_link']; // Ini link gambar QR-nya
                break;
            }
        }
    }

    
    $transaction = Transaction::create([
        'external_id' => $externalId,
        'user_id' => auth()->id(),
        'voucher_id' => $voucher->id,
        'total_price' => $voucher->price,
        'checkout_url' => $result['invoice_url'],
        'status' => 'PENDING'
    ]);

    return response()->json([
        'message' => 'Invoice berhasil dibuat, silakan lakukan pembayaran. akan expired dalam 10 menit.',
        'data' => [
            'external_id' => $externalId,
            'checkout_url' => $result['invoice_url'], 
            'qr_url' => $qrCodeUrl, // LINK buwat QRIS  
            'status' => 'PENDING'
        ]
    ]);
} catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membuat invoice: ' . $e->getMessage()], 500);
        }
    }

    public function checkStatus($external_id)
{
    try {
        $transaction = Transaction::where('external_id', $external_id)
                                  ->where('user_id', auth()->id())
                                  ->first();
        
        if (!$transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        if ($transaction->status === 'PAID') {
            $token = VoucherToken::find($transaction->voucher_token_id);
            return response()->json([
                'status' => 'PAID',
                'message' => 'Pembayaran sudah lunas!',
                'token_code' => $token ? $token->token_code : 'Gagal mengambil kode'
            ]);
        }

        // Ambil status terbaru dari Xendit
        $invoices = $this->apiInstance->getInvoices(null, $external_id);
        $xenditInvoice = $invoices[0] ?? null;

        if (!$xenditInvoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan di Xendit'], 404);
        }

        $xenditStatus = data_get($xenditInvoice, 'status');

        if ($xenditStatus === 'PAID' || $xenditStatus === 'SETTLED') {
            return DB::transaction(function () use ($transaction) {
                // 1. Cari Token yang masih available
                $token = VoucherToken::where('voucher_id', $transaction->voucher_id)
                                     ->where('status', 'available')
                                     ->lockForUpdate()
                                     ->first();

                if ($token) {
                    $token->update(['status' => 'sold']);

                    $transaction->update([
                        'status' => 'PAID',
                        'voucher_token_id' => $token->id
                    ]);
                    
                    return response()->json([
                        'status' => 'PAID',
                        'message' => 'Pembayaran berhasil dikonfirmasi!',
                        'token_code' => $token->token_code
                    ]);
                }
                
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Pembayaran lunas tapi stok voucher habis! Segera hubungi admin.'
                ], 500);
            });
        }

        return response()->json([
            'status' => $xenditStatus,
            'message' => 'Pembayaran masih berstatus ' . $xenditStatus
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Gagal: ' . $e->getMessage()], 500);
    }
}
public function myVouchers()
{
    $userId = auth()->id();

   
    $vouchers = Transaction::with(['voucher', 'voucherToken'])
        ->where('user_id', $userId)
        ->where('status', 'PAID')
        ->orderBy('created_at', 'desc')
        ->get();

    $data = $vouchers->map(function ($transaction) {
        return [
            'transaction_id' => $transaction->id,
            'external_id'    => $transaction->external_id,
            'voucher_name'   => $transaction->voucher->name ?? 'Voucher Tidak Ditemukan',
            'price'          => $transaction->total_price,
            'duration'       => $transaction->voucher->duration ?? 0,
            'purchased_at'   => $transaction->created_at->format('d M Y, H:i'),
            'token_code'     => $transaction->voucherToken->token_code ?? 'Token Tidak Tersedia'
        ];
    });

    if ($data->isEmpty()) {
        return response()->json([
            'message' => 'Kamu belum memiliki voucher yang dibeli.',
            'data' => []
        ], 200);
    }

    return response()->json([
        'message' => 'Daftar voucher berhasil diambil',
        'data' => $data
    ], 200);
}
public function purchaseHistory()
{
    $userId = auth()->id();

    // Mengambil semua transaksi user, diurutkan dari yang terbaru
    $history = Transaction::with(['voucher'])
        ->where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->get();

    $data = $history->map(function ($transaction) {
        return [
            'id'             => $transaction->id,
            'external_id'    => $transaction->external_id,
            'voucher_name'   => $transaction->voucher->name ?? 'Voucher Terhapus',
            'amount'         => $transaction->total_price,
            'status'         => $transaction->status, 
            'created_at'     => $transaction->created_at->format('d M Y, H:i'),
            // Kita kirim checkout_url juga kalau statusnya masih PENDING
            // Jadi user bisa bayar lagi kalau belum expired
            'checkout_url'   => $transaction->status === 'PENDING' ? $transaction->checkout_url : null,
        ];
    });

    return response()->json([
        'success' => true,
        'message' => 'Riwayat pembelian berhasil diambil',
        'data'    => $data
    ]);
}

public function rekapHarian($periode = null) 
{
    if ($periode) {
        $parts = explode('-', $periode);
        $tahun = (int) $parts[0];
        $bulan = (int) $parts[1];
    } else {
        $bulan = Carbon::now()->month;
        $tahun = Carbon::now()->year;
    }

    $labels = [];
    $counts = [];
    $revenue = [];
    $hariDalamBulan = Carbon::create($tahun, $bulan)->daysInMonth;

    for ($day = 1; $day <= $hariDalamBulan; $day++) {
        $stats = Transaction::whereDay('created_at', $day)
            ->whereMonth('created_at', $bulan)
            ->whereYear('created_at', $tahun)
            ->where('status', 'PAID')
            ->select(
                DB::raw('count(*) as total_qty'),
                DB::raw('sum(total_price) as total_money')
            )->first();

        $labels[] = $day; 
        $counts[] = (int) $stats->total_qty;
        $revenue[] = (float) ($stats->total_money ?? 0);
    }

    return response()->json([
        'period' => Carbon::create($tahun, $bulan)->format('F Y'),
        'labels' => $labels,
        'datasets' => [
            ['label' => 'Voucher Terjual', 'data' => $counts],
            ['label' => 'Pendapatan', 'data' => $revenue]
        ]
    ]);
}

/**
 * REKAP BULANAN (Data per bulan dalam 1 tahun)
 * Digunakan untuk grafik batang di Vue.js
 */
public function rekapBulanan($tahun = null)
{
    $tahun = $tahun ?? Carbon::now()->year;
    $labels = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
    $counts = [];
    $revenue = [];

    for ($m = 1; $m <= 12; $m++) {
        $stats = Transaction::whereYear('created_at', $tahun)
            ->whereMonth('created_at', $m)
            ->where('status', 'PAID')
            ->select(
                DB::raw('count(*) as total_qty'),
                DB::raw('sum(total_price) as total_money')
            )->first();

        $counts[] = (int) $stats->total_qty;
        $revenue[] = (float) ($stats->total_money ?? 0);
    }

    return response()->json([
        'year' => $tahun,
        'labels' => $labels,
        'datasets' => [
            ['label' => 'Total Terjual', 'data' => $counts],
            ['label' => 'Total Pendapatan', 'data' => $revenue]
        ]
    ]);
}
}