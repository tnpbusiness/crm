<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id',
        'date',
        'amount',
        'account_id',
        'payment_method',
        'order_id',
        'currency',
        'txn_id',
        'payment_type',
        'receipt',
        'add_receipt',
        'reference',
        'description',
    ];

    public function bankAccount()
    {
        return $this->hasOne(\Modules\Account\Entities\BankAccount::class, 'id', 'account_id');
    }
}
