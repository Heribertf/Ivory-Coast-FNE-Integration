<?php

namespace App\Http\Controllers;

use App\Models\FneInvoice;
use Illuminate\Http\Request;
use App\Models\InvNum;
use App\Models\InvoiceLine;
use App\Models\FneInvoiceCertification;
use App\Services\FneApiService;
use Illuminate\Http\JsonResponse;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;

class FneInvoiceController extends Controller
{
    private FneApiService $fneService;

    public function __construct(FneApiService $fneService)
    {
        $this->fneService = $fneService;
    }

    public function index(Request $request)
    {
        $query = FneInvoice::with('fneCertification')
            ->orderBy('InvDate', 'desc');

        if ($request->has('status')) {
            $status = $request->get('status');
            if ($status === 'certified') {
                $query->whereHas('fneCertification', function ($q) {
                    $q->where('certification_status', 'certified');
                });
            } elseif ($status === 'uncertified') {
                $query->whereDoesntHave('fneCertification')
                    ->orWhereHas('fneCertification', function ($q) {
                        $q->where('certification_status', '!=', 'certified');
                    });
            } elseif ($status === 'failed') {
                $query->whereHas('fneCertification', function ($q) {
                    $q->where('certification_status', 'failed');
                });
            }
        }

        if (!empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where('InvNumber', 'like', "%{$search}%");
        }

        if (!empty($request->from_date) && !empty($request->to_date)) {
            $query->whereBetween('InvDate', [
                $request->get('from_date'),
                $request->get('to_date')
            ]);
        }

        $invoices = $query->paginate(15);

        return view('invoices.index', compact('invoices'));
    }

    /**
     * Certify an invoice
     */
    public function certify(Request $request, FneInvoice $invoice): JsonResponse
    {
        $result = $this->fneService->certifyInvoice($invoice);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Invoice certified successfully',
                'data' => [
                    'fne_reference' => $result['data']['reference'],
                    'qr_url' => $result['data']['token'],
                    'balance_sticker' => $result['data']['balance_sticker']
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    public function bulkCertify(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_ids' => 'required|array',
        ]);

        $results = [];
        $invoices = FneInvoice::whereIn('id', $request->invoice_ids)->get();

        foreach ($invoices as $invoice) {
            $result = $this->fneService->certifyInvoice($invoice);
            $results[] = [
                'invoice_number' => $invoice->InvNumber,
                'success' => $result['success'],
                'message' => $result['success'] ? 'Certified' : $result['error']
            ];
        }

        return response()->json([
            'results' => $results,
            'total' => count($results),
            'successful' => collect($results)->where('success', true)->count()
        ]);
    }

    public function show(FneInvoice $invoice)
    {
        $invoice->load('fneCertification');
        return view('invoices.show', compact('invoice'));
    }

    public function retry(FneInvoice $invoice): JsonResponse
    {
        $certification = $invoice->fneCertification;

        if (!$certification || $certification->certification_status === 'certified') {
            return response()->json([
                'success' => false,
                'message' => 'Invoice is already certified or not found'
            ], 400);
        }

        $result = $this->fneService->certifyInvoice($invoice);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Certification retry successful' : $result['error']
        ]);
    }


    /**
     * Create a credit note/refund
     */
    public function refund(Request $request, FneInvoice $invoice): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1'
        ]);

        // Verify the invoice is certified
        if (!$invoice->isCertified()) {
            return response()->json([
                'success' => false,
                'error' => 'Original invoice must be certified first'
            ], 400);
        }

        $result = $this->fneService->createCreditNote(
            $invoice->fneCertification->response_payload['invoice']['id'],
            $request->items
        );

        if ($result['success']) {
            // Create a record for the credit note
            FneInvoiceCertification::create([
                'inv_num_auto_index' => $invoice->AutoIndex,
                'invoice_number' => 'A' . $invoice->InvNumber, // Prefix for credit note
                'certification_status' => 'certified',
                'fne_reference' => $result['data']['reference'],
                'fne_token' => $result['data']['token'],
                'fne_qr_url' => $result['data']['token'],
                'request_payload' => $request->items,
                'response_payload' => $result['data'],
                'balance_sticker' => $result['data']['balance_sticker'] ?? null,
                'warning' => $result['data']['warning'] ?? false,
                'certified_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Credit note created successfully',
                'data' => $result['data']
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error']
        ], 400);
    }

    /**
     * Sync invoices with FNE to update certification statuses
     */
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'sync_from_date' => 'nullable|date',
            'sync_to_date' => 'nullable|date|after_or_equal:sync_from_date',
            'force_resync' => 'nullable|boolean'
        ]);

        try {
            $query = FneInvoice::query();

            if ($request->sync_from_date) {
                $query->where('InvDate', '>=', $request->sync_from_date);
            }
            if ($request->sync_to_date) {
                $query->where('InvDate', '<=', $request->sync_to_date);
            }

            // Limit to uncertified or failed if not forcing resync
            if (!$request->force_resync) {
                $query->whereDoesntHave('fneCertification')
                    ->orWhereHas('fneCertification', function ($q) {
                        $q->where('certification_status', '!=', 'certified');
                    });
            }

            $invoices = $query->limit(10)->get();

            $updated = 0;

            foreach ($invoices as $invoice) {
                if ($invoice->isCertified() && !$request->force_resync) {
                    continue;
                }

                // Perform certification (this will update existing records)
                $result = $this->fneService->certifyInvoice($invoice);

                if ($result['success']) {
                    $updated++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Sync completed successfully',
                'updated' => $updated
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:certified,uncertified,failed',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date'
        ]);

        $query = FneInvoice::with('fneCertification')
            ->orderBy('InvDate', 'desc');

        if ($request->status === 'certified') {
            $query->whereHas('fneCertification', function ($q) {
                $q->where('certification_status', 'certified');
            });
        } elseif ($request->status === 'uncertified') {
            $query->whereDoesntHave('fneCertification')
                ->orWhereHas('fneCertification', function ($q) {
                    $q->where('certification_status', '!=', 'certified');
                });
        } elseif ($request->status === 'failed') {
            $query->whereHas('fneCertification', function ($q) {
                $q->where('certification_status', 'failed');
            });
        }

        if ($request->from_date) {
            $query->where('InvDate', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->where('InvDate', '<=', $request->to_date);
        }

        $invoices = $query->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=fne_invoices_" . date('Ymd_His') . ".csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Invoice Number',
                'Date',
                'Customer',
                'NCC',
                'Amount (XOF)',
                'Status',
                'FNE Reference',
                'Certified At',
                'QR Code URL'
            ]);

            // Data rows
            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->InvNumber,
                    $invoice->InvDate ? $invoice->InvDate->format('Y-m-d') : '',
                    $invoice->cAccountName ?? '',
                    $invoice->cTaxNumber ?? '',
                    number_format($invoice->InvTotIncl, 2),
                    $invoice->fneCertification ? ucfirst($invoice->fneCertification->certification_status) : 'Not Processed',
                    $invoice->fneCertification->fne_reference ?? '',
                    $invoice->fneCertification->certified_at ? $invoice->fneCertification->certified_at->format('Y-m-d H:i') : '',
                    $invoice->fneCertification->fne_qr_url ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate PDF for certified invoice
     */
    public function pdf(FneInvoice $invoice)
    {
        if (!$invoice->isCertified()) {
            abort(404, 'This invoice has not been certified yet');
        }

        // Fetch invoice line items from Sage database
        $lines = InvoiceLine::where('iInvoiceID', $invoice->AutoIndex)
            ->with('unit')
            ->get();

        // Transform the line items
        $items = $lines->map(function ($line) {
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


        // $items = $invoice->items->map(function ($line) {
        //     return (object) [
        //         'StockCode' => $line->iStockCodeID,
        //         'Description' => $line->cDescription,
        //         'Quantity' => $line->fQuantity,
        //         'UnitPriceExcl' => $line->fUnitPriceExcl,
        //         'DiscountPercentage' => $line->fLineDiscount ?? 0,
        //         'UnitOfMeasure' => optional($line->unit)->cUnitCode ?? 'pcs',
        //         'TaxRate' => $line->fTaxRate ?? 18
        //     ];
        // });

        // Generate QR code as base64 image
        $qrCode = QrCode::format('png')
            ->size(150)
            ->generate($invoice->fneCertification->fne_qr_url);

        $pdf = PDF::loadView('fne.invoices.pdf', [
            'invoice' => $invoice,
            'items' => $items,
            'certification' => $invoice->fneCertification,
            'qrCode' => $qrCode
        ]);

        return $pdf->stream("invoice_{$invoice->InvNumber}.pdf");
    }
}
