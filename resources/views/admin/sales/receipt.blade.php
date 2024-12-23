<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Receipt - {{ $sale->invoice_no }}</title>
    <style>
        @page {
            margin: 0;
            /* Size for 80mm printer (converting 80mm to points) */
            size: 226.772pt auto;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 8pt;
            font-size: 8pt;
            line-height: 1.2;
        }

        .header {
            text-align: center;
            margin-bottom: 10pt;
            border-bottom: 1px dashed #000;
            padding-bottom: 5pt;
        }

        .company-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 3pt;
        }

        .logo {
            max-width: 150pt;
            height: auto;
            margin-bottom: 5pt;
        }

        .info-block {
            margin-bottom: 8pt;
        }

        .info-block-title {
            font-weight: bold;
            border-bottom: 1px solid #000;
            margin-bottom: 3pt;
            text-transform: uppercase;
            font-size: 7pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8pt;
            font-size: 7pt;
        }

        th,
        td {
            text-align: left;
            padding: 2pt;
        }

        th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .amount-col {
            text-align: right;
            width: 50pt;
        }

        .qty-col {
            text-align: center;
            width: 30pt;
        }

        .summary-table {
            margin-left: auto;
            width: auto;
            border-top: 1px dashed #000;
            margin-top: 5pt;
        }

        .summary-table th {
            border: none;
            text-align: right;
            padding-right: 10pt;
        }

        .summary-table td {
            text-align: right;
            min-width: 50pt;
        }

        .summary-table .total-row {
            font-weight: bold;
            font-size: 9pt;
        }

        .footer {
            margin-top: 10pt;
            text-align: center;
            font-size: 7pt;
            border-top: 1px dashed #000;
            padding-top: 5pt;
        }

        .signature-section {
            display: none;
            /* Usually not needed for POS receipts */
        }

        .payment-info {
            font-size: 7pt;
            text-align: left;
            margin: 5pt 0;
            padding: 3pt;
            border: 1px solid #000;
        }

        .item-sku {
            font-size: 6pt;
            color: #666;
        }

        .dashed-separator {
            border-top: 1px dashed #000;
            margin: 5pt 0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .small-text {
            font-size: 6pt;
        }
    </style>
</head>

<body>
    <div class="header">
        @if (isset($company['logo']))
            <img src="{{ $company['logo'] }}" alt="Company Logo" class="logo">
        @endif
        <div class="company-name">{{ $company['name'] }}</div>
        <div class="small-text">{{ $company['address'] }}</div>
        <div class="small-text">Phone: {{ $company['phone'] }}</div>
        <div class="small-text">Email: {{ $company['email'] }}</div>
    </div>

    <div class="info-block">
        <table>
            <tr>
                <td><strong>INV:</strong> {{ $sale->invoice_no }}</td>
                <td class="text-right"><strong>Date:</strong> {{ $sale->created_at->format('d/m/y H:i') }}</td>
            </tr>
            @if ($sale->customer)
                <tr>
                    <td colspan="2">
                        <strong>Customer:</strong> {{ $sale->customer->name }}
                        <br>
                        <span class="small-text">{{ $sale->customer->phone }}</span>
                    </td>
                </tr>
            @endif
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="qty-col">Qty</th>
                <th class="amount-col">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->saleItems as $item)
                <tr>
                    <td>
                        {{ $item->product->name }}
                        <div class="item-sku">{{ $item->product->sku }}</div>
                        <div class="small-text">BDT {{ number_format($item->unit_price, 2) }} × {{ $item->quantity }}
                        </div>
                    </td>
                    <td class="qty-col">{{ $item->quantity }}</td>
                    <td class="amount-col">BDT {{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <th>Subtotal:</th>
            <td>BDT {{ number_format($sale->subtotal, 2) }}</td>
        </tr>
        @if ($sale->discount > 0)
            <tr>
                <th>Discount:</th>
                <td>BDT {{ number_format($sale->discount, 2) }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <th>Total:</th>
            <td>BDT {{ number_format($sale->total, 2) }}</td>
        </tr>
        <tr>
            <th>Paid:</th>
            <td>BDT {{ number_format($sale->paid, 2) }}</td>
        </tr>
        @if ($sale->due > 0)
            <tr>
                <th>Due:</th>
                <td>BDT {{ number_format($sale->due, 2) }}</td>
            </tr>
        @endif
    </table>

    @if ($sale->bank_account)
        <div class="payment-info">
            <div class="small-text">
                <strong>Payment Info:</strong>
                {{ $sale->bank_account->bank_name }} / {{ $sale->bank_account->account_name }}
            </div>
        </div>
    @endif

    <div class="footer">
        @if ($sale->note)
            <div class="small-text" style="margin-bottom: 3pt;">
                <strong>Note:</strong> {{ $sale->note }}
            </div>
        @endif
        <div>Thank you for your business!</div>
        <div class="small-text">Served by: {{ $sale->createdBy->name }}</div>
        <div class="small-text">{{ $sale->created_at->format('d/m/Y h:i A') }}</div>
        <div class="dashed-separator"></div>
        <div class="small-text">Keep receipt for proof of purchase</div>
    </div>
</body>

</html>
