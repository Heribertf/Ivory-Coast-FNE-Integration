<?php

namespace App\Services;

use App\Models\FneInvoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\InvNum;
use App\Models\InvoiceLine;
use App\Models\FneInvoiceCertification;
use Exception;
use Illuminate\Support\Collection;

class FneApiService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('fne.base_url', 'http://54.247.95.108/ws');
        $this->apiKey = config('fne.api_key');
    }

    public function certifyInvoice(FneInvoice $invoice): array
    {
        try {
            // Check if already certified
            $existingCertification = $invoice->fneCertification;
            if ($existingCertification && $existingCertification->certification_status === 'certified') {
                throw new Exception('Invoice already certified');
            }

            // Prepare the request payload
            $payload = $this->prepareInvoicePayload($invoice);

            // Create or update certification record
            $certification = FneInvoiceCertification::updateOrCreate(
                ['inv_num_auto_index' => $invoice->AutoIndex],
                [
                    'invoice_number' => $invoice->InvNumber,
                    'certification_status' => 'pending',
                    'request_payload' => $payload
                ]
            );

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post($this->baseUrl . '/external/invoices/sign', $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                $certification->update([
                    'certification_status' => 'certified',
                    'fne_reference' => $responseData['reference'],
                    'fne_token' => $responseData['token'],
                    'fne_qr_url' => $responseData['token'], // The token IS the QR URL
                    'response_payload' => $responseData,
                    'balance_sticker' => $responseData['balance_sticker'] ?? null,
                    'warning' => $responseData['warning'] ?? false,
                    'certified_at' => now(),
                    'error_message' => null,
                    'pdf_url' => route('fne.invoices.pdf', $invoice->id),
                ]);

                return [
                    'success' => true,
                    'data' => $responseData,
                    'certification' => $certification,
                    'pdf_url' => route('fne.invoices.pdf', $invoice->id)
                ];
            }

            $errorData = $response->json();
            $certification->update([
                'certification_status' => 'failed',
                'response_payload' => $errorData,
                'error_message' => $errorData['message'] ?? 'Unknown error'
            ]);

            return [
                'success' => false,
                'error' => $errorData['message'] ?? 'Certification failed',
                'status_code' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('FNE API Error: ' . $e->getMessage(), [
                'invoice_id' => $invoice->AutoIndex,
                'invoice_number' => $invoice->InvNumber
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create credit note/refund
     */
    public function createCreditNote(string $originalInvoiceId, array $items): array
    {
        try {
            $payload = ['items' => $items];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post($this->baseUrl . "/external/invoices/{$originalInvoiceId}/refund", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Credit note creation failed',
                'status_code' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('FNE Credit Note Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function prepareInvoicePayload(FneInvoice $invoice): array
    {
        $items = $this->prepareInvoiceItems($invoice);

        $discountPercentage = $this->calculateDiscountPercentage($invoice);

        // Determine if this is related to a receipt (RNE)
        $isRne = !empty($invoice->GrvNumber);

        return [
            'invoiceType' => 'sale', // or 'purchase' if applicable
            'paymentMethod' => $this->determinePaymentMethod($invoice),
            'template' => $this->determineTemplate($invoice),
            'isRne' => $isRne,
            'rne' => $isRne ? $invoice->GrvNumber : null,
            'clientNcc' => $invoice->cTaxNumber ?? '',
            'clientCompanyName' => $invoice->cAccountName ?? 'N/A',
            'clientPhone' => '2722544963',
            'clientEmail' => 'invoice-ivoirycoast@Cargill.com',
            'clientSellerName' => $invoice->InvNum_iCreatedAgentID ?? '', // Map to salesperson if available
            'pointOfSale' => config('fne.point_of_sale', '23'),
            'establishment' => config('fne.establishment_name', 'Main Store'),
            'commercialMessage' => $invoice->Message1 ?? '',
            'footer' => $invoice->Message2 ?? '',
            'foreignCurrency' => $this->getForeignCurrency($invoice),
            'foreignCurrencyRate' => $this->getForeignCurrencyRate($invoice),
            'items' => $items,
            // 'customTaxes' => $this->getCustomTaxes($items),
            'discount' => $discountPercentage
        ];
    }

    private function determinePaymentMethod(FneInvoice $invoice): string
    {
        // Adjust this mapping based on actual Sage UDF values(Not currently defined)
        $paymentMethods = [
            'CASH' => 'cash',
            'CARD' => 'card',
            'CHECK' => 'check',
            'MOBILE' => 'mobile-money',
            'TRANSFER' => 'transfer',
            'CREDIT' => 'deferred'
        ];

        $method = strtoupper($invoice->PaymentMethodID ?? 'CASH');

        return $paymentMethods[$method] ?? 'cash';
    }

    private function determineTemplate(FneInvoice $invoice): string
    {
        // Determine customer type
        // B2B: Business with NCC, B2C: Individual, B2G: Government, B2F: International
        if (!empty($invoice->cTaxNumber)) {
            return 'B2B';
        }
        return 'B2C';
    }

    private function cleanPhoneNumber(?string $phone): string
    {
        if (!$phone)
            return '';
        return preg_replace('/[^0-9]/', '', $phone);
    }

    private function getForeignCurrency(FneInvoice $invoice): string
    {
        // Map ForeignCurrencyID to currency codes if applicable
        return ''; // Default to empty (XOF - local currency)
    }

    private function getForeignCurrencyRate(FneInvoice $invoice): string
    {
        return $invoice->fExchangeRate ?? '';
    }

    private function prepareInvoiceItems(FneInvoice $invoice): array
    {
        $items = [];

        $lineItems = $this->getInvoiceLineItems($invoice);

        foreach ($lineItems as $lineItem) {
            $items[] = [
                // 'taxes' => $this->determineTaxTypes($lineItem),
                'taxes' => ['TVA'], // Normal VAT of 18%
                'customTaxes' => [],
                'reference' => $lineItem->StockCode ?? '',
                'description' => $lineItem->Description ?? 'Product',
                'quantity' => (float) $lineItem->Quantity,
                'amount' => (float) $lineItem->UnitPriceExcl,
                'discount' => (float) $lineItem->DiscountPercentage,
                'measurementUnit' => $lineItem->UnitOfMeasure ?? 'pcs'
            ];
        }

        return $items;
    }

    private function getInvoiceLineItems(FneInvoice $invoice): Collection
    {
        $lines = InvoiceLine::with('unit')
            ->where('iInvoiceID', $invoice->AutoIndex)
            ->get();

        if ($lines->isEmpty()) {
            return collect([
                (object) [
                    'StockCode' => $invoice->InvNumber,
                    'Description' => $invoice->Description ?? 'Invoice Item',
                    'Quantity' => 1,
                    'UnitPriceExcl' => $invoice->InvTotExcl,
                    'DiscountPercentage' => $this->calculateDiscountPercentage($invoice),
                    'UnitOfMeasure' => 'pcs',
                    'TaxRate' => 18
                ]
            ]);
        }

        return $lines->map(function ($line) {
            return (object) [
                'StockCode' => $line->iStockCodeID,
                'Description' => $line->cDescription,
                'Quantity' => $line->fQuantity,
                'UnitPriceExcl' => $line->fUnitPriceExcl,
                'DiscountPercentage' => $line->fLineDiscount ?? 0,
                'UnitOfMeasure' => optional($line->unit)->cUnitCode ?? 'pcs',
                'TaxRate' => $line->fTaxRate ?? 18
            ];
        });
    }



    private function calculateDiscountPercentage(FneInvoice $invoice): float
    {
        if ($invoice->InvTotExcl > 0 && $invoice->InvDiscAmnt > 0) {
            return round(($invoice->InvDiscAmnt / $invoice->InvTotExcl) * 100, 2);
        }
        return 0;
    }


    /**
     * Check certification status for an invoice
     */
    public function checkCertificationStatus(string $fneReference): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/external/invoices/status/' . $fneReference);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Status check failed',
                'status_code' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('FNE Status Check Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get remaining sticker balance
     */
    public function getStickerBalance(): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/external/account/balance');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'balance' => $response->json()['balance'] ?? 0
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Balance check failed',
                'status_code' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('FNE Balance Check Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function checkHealth(): array
    {
        return cache()->remember('fne_api_status', now()->addMinutes(5), function () {
            try {
                $startTime = microtime(true);

                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->get($this->baseUrl . '/external/health');

                $responseTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'success' => true,
                        'status' => 'online',
                        'response_time_ms' => $responseTime,
                        'version' => $data['version'] ?? null,
                        'timestamp' => $data['timestamp'] ?? null,
                    ];
                }

                return [
                    'success' => false,
                    'status' => 'error',
                    'response_time_ms' => $responseTime,
                    'error' => $response->json()['message'] ?? 'API returned status: ' . $response->status(),
                    'status_code' => $response->status()
                ];

            } catch (Exception $e) {
                return [
                    'success' => false,
                    'status' => 'offline',
                    'error' => $e->getMessage()
                ];
            }
        });
    }
}