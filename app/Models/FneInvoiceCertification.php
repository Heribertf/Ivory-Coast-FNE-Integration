<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FneInvoiceCertification extends Model
{
    use HasFactory;

    protected $fillable = [
        'inv_num_auto_index',
        'invoice_number',
        'fne_reference',
        'fne_token',
        'fne_qr_url',
        'certification_status',
        'request_payload',
        'response_payload',
        'error_message',
        'balance_sticker',
        'warning',
        'certified_at'
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'warning' => 'boolean',
        'certified_at' => 'datetime'
    ];

    public function invNum(): BelongsTo
    {
        return $this->belongsTo(FneInvoice::class, 'inv_num_auto_index', 'AutoIndex');
    }

    public function scopeCertified($query)
    {
        return $query->where('certification_status', 'certified');
    }

    public function scopePending($query)
    {
        return $query->where('certification_status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('certification_status', 'failed');
    }
}