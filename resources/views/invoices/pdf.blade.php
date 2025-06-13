@php
    use SimpleSoftwareIO\QrCode\Facades\QrCode;
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->InvNumber }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .company-info {
            margin-bottom: 30px;
        }

        .invoice-info {
            margin-bottom: 20px;
        }

        .customer-info {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .total-row {
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            font-size: 0.8em;
            text-align: center;
        }

        .qr-code {
            text-align: center;
            margin-top: 20px;
        }

        .certification-badge {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>{{ config('app.name', 'FNE Certified Invoice') }}</h2>
    </div>

    <div class="company-info">
        <p><strong>{{ config('fne.establishment_name') }}</strong></p>
        <p>Point of Sale: {{ config('fne.point_of_sale') }}</p>
    </div>

    <div class="certification-badge">
        FNE CERTIFIED INVOICE
    </div>

    <div class="invoice-info">
        <p><strong>Invoice Number:</strong> {{ $invoice->InvNumber }}</p>
        <p><strong>Date:</strong> {{ $invoice->InvDate->format('Y-m-d H:i') }}</p>
        <p><strong>FNE Reference:</strong> {{ $certification->fne_reference }}</p>
    </div>

    <div class="customer-info">
        <p><strong>Customer:</strong> {{ $invoice->cAccountName }}</p>
        @if ($invoice->cTaxNumber)
            <p><strong>NCC:</strong> {{ $invoice->cTaxNumber }}</p>
        @endif
        @if ($invoice->cEmail)
            <p><strong>Email:</strong> {{ $invoice->cEmail }}</p>
        @endif
        @if ($invoice->cTelephone)
            <p><strong>Phone:</strong> {{ $invoice->cTelephone }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->StockCode ?? 'N/A' }}</td>
                    <td>{{ $item->Description }}</td>
                    <td>{{ $item->Quantity }}</td>
                    <td>{{ number_format($item->UnitPriceIncl, 2) }} XOF</td>
                    <td>{{ number_format($item->Quantity * $item->UnitPriceIncl, 2) }} XOF</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" style="text-align: right;">Subtotal:</td>
                <td>{{ number_format($invoice->InvTotExcl, 2) }} XOF</td>
            </tr>
            <tr class="total-row">
                <td colspan="4" style="text-align: right;">Tax:</td>
                <td>{{ number_format($invoice->InvTotTax, 2) }} XOF</td>
            </tr>
            <tr class="total-row">
                <td colspan="4" style="text-align: right;">Total:</td>
                <td>{{ number_format($invoice->InvTotIncl, 2) }} XOF</td>
            </tr>
        </tfoot>
    </table>

    <div class="qr-code">
        <p><strong>FNE Verification QR Code</strong></p>
        <img src="data:image/png;base64, {!! base64_encode(QrCode::format('png')->size(150)->generate($certification->fne_qr_url)) !!}">
        <p>Scan to verify with FNE</p>
    </div>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>{{ config('fne.establishment_name') }} | {{ config('app.name') }}</p>
        <p>Printed on: {{ now()->format('Y-m-d H:i') }}</p>
    </div>
</body>

</html>
