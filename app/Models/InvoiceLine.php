<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    protected $connection = 'sage_db_connection';
    protected $table = '_btblInvoiceLines';
    protected $primaryKey = 'idInvoiceLines';
    public $timestamps = false;

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'iUnitsOfMeasureID', 'idUnits');
    }
}
