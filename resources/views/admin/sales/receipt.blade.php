<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt - {{ $sale->invoice_no }}</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
        }

        .container {
            padding: 20mm;
            max-width: 190mm;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 15pt;
            border-bottom: 1px dashed #000;
            padding-bottom: 10pt;
        }

        .company-name {
            font-size: 24pt;
            font-weight: bold;
            margin-bottom: 5pt;
        }

        .logo {
            max-width: 200pt;
            height: auto;
            margin-bottom: 10pt;
        }

        .small-text {
            font-size: 9pt;
            line-height: 1.3;
        }

        .info-block {
            margin-bottom: 15pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15pt;
        }

        th, td {
            text-align: left;
            padding: 5pt;
            font-size: 10pt;
        }

        th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            background-color: #f8f8f8;
        }

        .amount-col {
            text-align: right;
            width: 80pt;
        }

        .qty-col {
            text-align: center;
            width: 60pt;
        }

        .summary-table {
            margin-left: auto;
            width: 50%;
            border-top: 1px dashed #000;
            margin-top: 10pt;
        }

        .summary-table th {
            background: none;
            border: none;
            text-align: right;
            padding-right: 15pt;
            font-size: 10pt;
        }

        .summary-table td {
            text-align: right;
            min-width: 80pt;
            font-size: 10pt;
        }

        .summary-table .total-row {
            font-weight: bold;
            font-size: 12pt;
        }

        .payment-info {
            font-size: 10pt;
            text-align: left;
            margin: 10pt 0;
            padding: 8pt;
            border: 1px solid #000;
            background-color: #f8f8f8;
        }

        .item-sku {
            font-size: 8pt;
            color: #666;
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

        .footer {
            margin-top: 20pt;
            text-align: center;
            font-size: 9pt;
            border-top: 1px dashed #000;
            padding-top: 10pt;
            position: relative;
            bottom: 0;
        }

        .dashed-separator {
            border-top: 1px dashed #000;
            margin: 10pt 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10pt;
            margin-bottom: 15pt;
        }

        .info-grid-item {
            font-size: 10pt;
            line-height: 1.4;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                width: 210mm;
            }

            .container {
                width: 190mm;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-name">{{ $company['name'] }}</div>
            <div class="small-text">{{ $company['address'] }}</div>
            <div class="small-text">Phone: 01334766435</div>
            <div class="small-text">Email: mou.prokashon@gmail.com</div>
        </div>

        <div class="info-grid">
            <div class="info-grid-item">
                <strong>Invoice No:</strong> {{ $sale->invoice_no }}<br>
                <strong>Date:</strong> {{ $sale->created_at->format('d/m/y H:i') }}<br>
                <strong>Served by:</strong> {{ $sale->createdBy->name }}
            </div>
            @if($sale->customer)
            <div class="info-grid-item text-right">
                <strong>Customer Details:</strong><br>
                {{ $sale->customer->name }}<br>
                {{ $sale->customer->phone }}
            </div>
            @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="qty-col">Qty</th>
                    <th class="amount-col">Unit Price</th>
                    <th class="amount-col">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->saleItems as $item)
                <tr>
                    <td>
                        {{ $item->product->name }}
                        <div class="item-sku">{{ $item->product->sku }}</div>
                    </td>
                    <td class="qty-col">{{ $item->quantity }}</td>
                    <td class="amount-col">BDT {{ number_format($item->unit_price, 2) }}</td>
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
            @if($sale->discount > 0)
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
            @if($sale->due > 0)
            <tr>
                <th>Due:</th>
                <td>BDT {{ number_format($sale->due, 2) }}</td>
            </tr>
            @endif
        </table>

        @if($sale->bank_account)
        <div class="payment-info">
            <strong>Payment Information:</strong><br>
            Bank: {{ $sale->bank_account->bank_name }}<br>
            Account Name: {{ $sale->bank_account->account_name }}
        </div>
        @endif

        <div class="footer">
            @if($sale->note)
            <div class="small-text" style="margin-bottom: 5pt;">
                <strong>Note:</strong> {{ $sale->note }}
            </div>
            @endif
            <div class="bold">Thank you for your business!</div>
            <div class="dashed-separator"></div>
            <div class="small-text">Keep this receipt for proof of purchase</div>
            <div class="small-text">{{ $sale->created_at->format('d/m/Y h:i A') }}</div>
        </div>
    </div>
</body>
</html>
