<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'external_id', 'user_id', 'voucher_id', 
        'voucher_token_id', 'total_price', 'snap_token', 'status'
    ];

    public function voucher() {
        return $this->belongsTo(Voucher::class);
    }

    public function voucherToken() {
        return $this->belongsTo(VoucherToken::class);
    }
}
