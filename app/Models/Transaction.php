<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'external_id', 'user_id', 'voucher_id', 
        'voucher_token_id', 'total_price', 'snap_token', 'status'
    ];

    public function voucher()
{
    // Pastikan foreign key di tabel transactions adalah voucher_id
    return $this->belongsTo(Voucher::class, 'voucher_id');
}

public function voucherToken()
{
    // Pastikan foreign key di tabel transactions adalah voucher_token_id
    return $this->belongsTo(VoucherToken::class, 'voucher_token_id');
}
    
}
