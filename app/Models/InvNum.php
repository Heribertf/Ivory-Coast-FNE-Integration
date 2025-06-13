<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InvNum extends Model
{
    protected $connection = 'sage_db_connection';
    protected $table = 'InvNum';
    protected $primaryKey = 'AutoIndex';
    public $timestamps = false;

    protected $fillable = [
        'InvNumber',
        'AccountID',
        'Description',
        'InvDate',
        'InvTotExcl',
        'InvTotTax',
        'InvTotIncl',
        'cAccountName',
        'cTaxNumber',
        'cEmail',
        'cTelephone'
    ];

    protected $casts = [
        'InvDate' => 'datetime',
        'InvTotExcl' => 'decimal:2',
        'InvTotTax' => 'decimal:2',
        'InvTotIncl' => 'decimal:2'
    ];

    public function fneCertification(): HasOne
    {
        return $this->hasOne(FneInvoiceCertification::class, 'inv_num_auto_index', 'AutoIndex');
    }

    public function isCertified(): bool
    {
        return $this->fneCertification && $this->fneCertification->certification_status === 'certified';
    }
}
