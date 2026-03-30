<?php

namespace App\Imports;

use App\Models\VoucherToken;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VoucherTokenImport implements ToModel, WithHeadingRow
{
    private $voucher_id;

    public function __construct($voucher_id)
    {
        $this->voucher_id = $voucher_id;
    }

    public function model(array $row)
    {
        return new VoucherToken([
            'voucher_id' => $this->voucher_id,
            'token_code' => $row['kode_voucher'], // Nama kolom di file Excel
            'status'     => 'available',
        ]);
    }
}