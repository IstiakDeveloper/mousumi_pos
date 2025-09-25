<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A4 Invoice Receipt</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 12mm 12mm 12mm;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.1;
            font-size: 8pt;
            margin: 0;
            padding: 0;
            color: #000;
            background: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* কোম্পানি হেডার - শুধু নিচে বর্ডার */
        .company-header {
            text-align: center;
            margin-bottom: 4mm;
            padding: 3mm 0;
            border-bottom: 2px solid #000;
        }

        .company-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }

        .company-details {
            font-size: 8pt;
            line-height: 1.2;
            color: #333;
        }

        /* ইনভয়েস ডিটেইলস - হালকা বর্ডার */
        .invoice-section {
            display: table;
            width: 100%;
            margin: 3mm 0;
            border: 1px solid #ccc;
            background: #f9f9f9;
        }

        .invoice-left,
        .invoice-right {
            display: table-cell;
            width: 50%;
            padding: 3mm;
            vertical-align: top;
            font-size: 8pt;
        }

        .invoice-left {
            border-right: 1px solid #ccc;
        }

        .invoice-title {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 2mm;
            text-transform: uppercase;
            color: #333;
        }

        .invoice-details p,
        .customer-details p {
            margin: 1mm 0;
        }

        /* আইটেম টেবিল - কম বর্ডার */
        .items-table {
            margin: 3mm 0;
            border: 1px solid #333;
        }

        .items-table th {
            background: #f0f0f0;
            padding: 2mm 1mm;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #333;
            font-size: 8pt;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 1.5mm 1mm;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
            font-size: 8pt;
        }

        .items-table .amount {
            text-align: right;
            font-weight: 500;
        }

        .items-table .quantity {
            text-align: center;
            font-weight: 500;
        }

        /* ক্যাটাগরি হেডার - হালকা */
        .category-header td {
            background: #e8e8e8;
            font-weight: bold;
            text-transform: uppercase;
            padding: 2mm 1mm;
            font-size: 8pt;
            text-align: left;
            border-bottom: 1px solid #999;
        }

        /* ক্যাটাগরি সাবটোটাল */
        .category-subtotal td {
            background: #f5f5f5;
            font-weight: bold;
            font-style: italic;
            padding: 1.5mm 1mm;
            font-size: 8pt;
            border-bottom: 1px solid #bbb;
        }

        /* টোটাল সেকশন - minimal border */
        .totals-section {
            width: 55%;
            margin: 3mm 0 3mm auto;
        }

        .totals-table {
            border: 1px solid #333;
        }

        .totals-table td {
            padding: 1.5mm;
            font-size: 8pt;
            border-bottom: 1px solid #ddd;
        }

        .totals-table .right-align {
            text-align: right;
            font-weight: 600;
        }

        .total-row td {
            font-weight: bold;
            font-size: 9pt;
            background: #f0f0f0;
            text-transform: uppercase;
            border-bottom: 2px solid #333;
        }

        /* Amount in Words - clean design */
        /* Amount in Words - one line */
        .amount-in-words {
            margin: 3mm 0;
            padding: 2mm;
            background: #f9f9f9;
            border: 1px solid #ccc;
            font-size: 8pt;
        }

        .amount-in-words strong {
            font-weight: bold;
            text-transform: capitalize;
            color: #333;
        }

        .amount-in-words-text {
            font-weight: 800;
            text-transform: capitalize;
            font-style: italic;
            color: #131010;
        }


        /* বারকোড - সিম্পল */
        .barcode {
            text-align: center;
            margin: 3mm 0;
            padding: 2mm;
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
        }

        .barcode-text {
            font-size: 10pt;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            color: #333;
        }

        /* ফুটার - minimal */
        .footer {
            text-align: center;
            font-size: 7pt;
            margin-top: 3mm;
            padding-top: 3mm;
            border-top: 1px solid #ccc;
            color: #666;
        }

        .footer p {
            margin: 1mm 0;
        }

        .footer .thank-you {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 2mm;
            color: #333;
        }

        /* মেইন কন্টেইনার */
        .main-container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
        }

        /* স্পেসার */
        tr.spacer-row td {
            height: 1.5mm;
            border: none;
            background: transparent;
        }

        /* Strong text styling */
        strong {
            font-weight: 700;
        }

        /* Currency formatting */
        .currency {
            font-family: Arial, sans-serif;
            font-weight: 500;
        }

        /* Compact spacing */
        p {
            margin: 0;
            margin-bottom: 0.5mm;
        }

        /* Remove unnecessary borders */
        .items-table td:last-child {
            border-right: none;
        }

        .totals-table td {
            border-left: none;
            border-right: none;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <!-- কোম্পানি হেডার -->
        <div class="company-header">
            <div class="company-name">{{ config('app.name', 'Your Company Name') }} / Variety Store</div>
            <div class="company-details">
                Ukilpara, Naogaon Sadar, Naogaon.<br>
                Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
            </div>
        </div>

        <!-- ইনভয়েস ডিটেইলস সেকশন -->
        <div class="invoice-section">
            <div class="invoice-left">
                <div class="invoice-title">Sale Invoice</div>
                <div class="invoice-details">
                    <p><strong>Invoice No:</strong> {{ $sale->invoice_no }}</p>
                    <p><strong>Date:</strong> {{ $sale->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Payment Method:</strong> {{ ucfirst($sale->payment_method) }}</p>
                </div>
            </div>
            <div class="invoice-right">
                <div class="customer-details">
                    <p><strong>Customer Information:</strong></p>
                    <p><strong>Name:</strong> {{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}</p>
                    @if ($sale->customer && $sale->customer->address)
                        <p><strong>Address:</strong> {{ $sale->customer->address }}</p>
                    @endif
                    @if ($sale->customer && $sale->customer->phone)
                        <p><strong>Phone:</strong> {{ $sale->customer->phone }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- আইটেম টেবিল -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%">SL</th>
                    <th style="width: 45%">Description</th>
                    <th style="width: 10%">Qty</th>
                    <th style="width: 20%">Unit Price</th>
                    <th style="width: 20%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $serialNo = 1; @endphp
                @foreach ($itemsByCategory as $categoryName => $items)
                    <tr class="category-header">
                        <td colspan="5">{{ strtoupper($categoryName) }}</td>
                    </tr>

                    @foreach ($items as $item)
                        <tr>
                            <td style="text-align: center;">{{ $serialNo++ }}</td>
                            <td>{{ $item->product->name }}</td>
                            <td class="quantity">{{ number_format($item->quantity) }}</td>
                            <td class="amount currency">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="amount currency">{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach

                    <tr class="category-subtotal">
                        <td colspan="4" style="text-align: right; font-weight: bold;">{{ $categoryName }} Subtotal:
                        </td>
                        <td class="amount currency">{{ number_format($categoryTotals[$categoryName], 2) }}</td>
                    </tr>

                    <tr class="spacer-row">
                        <td colspan="5"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- টোটালস সেকশন -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td class="right-align currency">{{ number_format($sale->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Tax:</strong></td>
                    <td class="right-align currency">{{ number_format($sale->tax, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Discount:</strong></td>
                    <td class="right-align currency">{{ number_format($sale->discount, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td><strong>Grand Total:</strong></td>
                    <td class="right-align currency">{{ number_format($sale->total, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Paid Amount:</strong></td>
                    <td class="right-align currency">{{ number_format($sale->paid, 2) }}</td>
                </tr>
                @if ($sale->paid < $sale->total)
                    <tr>
                        <td><strong>Due Amount:</strong></td>
                        <td class="right-align currency">{{ number_format($sale->total - $sale->paid, 2) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <!-- Amount in Words -->
        <div class="amount-in-words">
            <strong>In Words:</strong> <span
                class="amount-in-words-text">{{ $amountInWords ?? 'One Thousand Two Hundred Fifty Taka Only' }}</span>
        </div>

        <!-- বারকোড -->
        <div class="barcode">
            <div class="barcode-text">{{ $sale->invoice_no }}</div>
        </div>

        <!-- ফুটার -->
        <div class="footer">
            <p class="thank-you">Thank You For Your Business!</p>
            <p>Returns accepted within 7 days with original receipt</p>
            <p>For any queries, contact us at mou.prokashon@gmail.com</p>
            <p>&copy; {{ date('Y') }} {{ $company['name'] ?? 'Your Company Name' }} - All Rights Reserved</p>
        </div>
    </div>
</body>

</html>
