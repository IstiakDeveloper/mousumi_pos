<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A4 Invoice Receipt</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.3;
        }

        .a4-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .inner-content {
            padding: 20mm;
            width: 100%;
            max-width: 170mm;
            margin: 0 auto;
        }

        /* Logo and Header */
        .company-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 9px;
            line-height: 1.4;
            color: #555;
        }

        /* Invoice Details */
        .invoice-section {
            margin-bottom: 10mm;
            display: flex;
            justify-content: space-between;
        }

        .invoice-details, .customer-details {
            font-size: 9px;
            line-height: 1.5;
        }

        .invoice-title {
            font-size: 14px;
            margin-bottom: 3mm;
            font-weight: bold;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10mm;
            font-size: 8px;
        }

        .items-table th {
            background: #f8f8f8;
            padding: 5px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        .items-table td {
            padding: 5px;
            border: 1px solid #ddd;
        }

        .items-table .amount {
            text-align: right;
        }

        .items-table .quantity {
            text-align: center;
        }

        /* Category Section Styles */
        .category-section {
            margin-bottom: 5px;
        }

        .category-header {
            background: #f4f4f4;
            font-weight: bold;
            border: 1px solid #ddd;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 5px;
        }

        .category-subtotal {
            background: #f8f8f8;
            font-weight: bold;
            border: 1px solid #ddd;
        }

        /* Category Summary Section */
        .category-summary {
            margin: 10px 0;
            padding: 8px;
            background: #f8f8f8;
            border: 1px solid #ddd;
        }

        .category-summary h4 {
            font-size: 10px;
            margin-bottom: 5px;
            text-transform: uppercase;
            color: #555;
        }

        .category-summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .category-summary-table th,
        .category-summary-table td {
            border: 1px solid #ddd;
            padding: 4px;
        }

        .category-summary-table th {
            background: #f4f4f4;
            font-weight: bold;
            text-align: left;
        }

        /* Totals Section */
        .totals-section {
            width: 60%;
            margin-left: auto;
            margin-bottom: 10mm;
        }

        .totals-table {
            width: 100%;
            font-size: 9px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 4px;
            border: 1px solid #ddd;
        }

        .totals-table .right-align {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            font-size: 10px;
            background: #f4f4f4;
        }

        /* Payment Info */
        .payment-methods {
            margin: 10px 0;
            padding: 8px;
            background: #f8f8f8;
            border: 1px solid #ddd;
            font-size: 8px;
        }

        /* Barcode */
        .barcode {
            text-align: center;
            margin: 10px 0;
            font-size: 12px;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 8px;
            color: #666;
            margin-top: 10mm;
            padding-top: 5mm;
            border-top: 1px solid #eee;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .paid {
            color: #0b4;
        }

        .due {
            color: #d42;
        }

        .partial {
            color: #f90;
        }

        @media print {
            body {
                background: none;
                margin: 0;
            }

            .a4-container {
                width: 210mm;
                height: 297mm;
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="a4-container">
        <div class="inner-content">
            <!-- Header Section -->
            <div class="company-header">
                <div class="company-name">{{ config('app.name', 'Your Company Name') }}/ Departmental Store</div>
                <div class="company-details">
                    Ukilpara, Naogaon Sadar, Naogaon.<br>
                    Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
                </div>
            </div>

            <!-- Invoice Details Section -->
            <div class="invoice-section">
                <div class="invoice-details">
                    <div class="invoice-title">TAX INVOICE</div>
                    <p><strong>Invoice No:</strong> {{ $sale->invoice_no }}</p>
                    <p><strong>Date:</strong> {{ $sale->created_at->format('d/m/Y H:i') }}</p>
                    {{-- <p><strong>Payment Method:</strong> {{ ucfirst($sale->payment_method) }}</p> --}}
                </div>
                <div class="customer-details">
                    <p><strong>Bill To:</strong> {{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}</p>
                    <p><strong>Served by:</strong> {{ $sale->createdBy->name }}</p>
                </div>
            </div>

            <!-- Items Table Grouped by Category -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 45%">Description</th>
                        <th style="width: 15%" class="text-center">Qty</th>
                        <th style="width: 20%" class="text-right">Price</th>
                        <th style="width: 20%" class="text-right">Amount</th>
                    </tr>
                </thead>
                @php
                    // Group items by category
                    $itemsByCategory = $sale->saleItems->groupBy(function($item) {
                        return $item->product->category->name ?? 'Uncategorized';
                    });

                    // Calculate totals per category
                    $categoryTotals = [];
                    foreach ($itemsByCategory as $category => $items) {
                        $categoryTotals[$category] = $items->sum('subtotal');
                    }
                @endphp

                @foreach($itemsByCategory as $categoryName => $items)
                    <tbody class="category-section">
                        <!-- Category Header -->
                        <tr>
                            <td colspan="4" class="category-header">{{ $categoryName }}</td>
                        </tr>

                        <!-- Items in this category -->
                        @foreach($items as $item)
                        <tr>
                            <td>
                                {{ $item->product->name }}
                                @if(isset($item->product->sku))
                                <div style="font-size: 7px; color: #666;">{{ $item->product->sku }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach

                        <!-- Category Subtotal -->
                        <tr class="category-subtotal">
                            <td colspan="3" class="text-right">{{ $categoryName }} Subtotal:</td>
                            <td class="text-right">{{ number_format($categoryTotals[$categoryName], 2) }}</td>
                        </tr>
                    </tbody>
                @endforeach
            </table>


            <!-- Totals Section -->
            <div class="totals-section">
                <table class="totals-table">
                    <tr>
                        <td>Subtotal:</td>
                        <td class="text-right">{{ number_format($sale->subtotal, 2) }}</td>
                    </tr>
                    @if($sale->tax > 0)
                    <tr>
                        <td>Tax:</td>
                        <td class="text-right">{{ number_format($sale->tax, 2) }}</td>
                    </tr>
                    @endif
                    @if($sale->discount > 0)
                    <tr>
                        <td>Discount:</td>
                        <td class="text-right">{{ number_format($sale->discount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td>Total Amount:</td>
                        <td class="text-right">{{ number_format($sale->total, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Paid Amount:</td>
                        <td class="text-right paid">{{ number_format($sale->paid, 2) }}</td>
                    </tr>
                    @if($sale->paid > $sale->total)
                    <tr>
                        <td>Change:</td>
                        <td class="text-right">{{ number_format($sale->paid - $sale->total, 2) }}</td>
                    </tr>
                    @endif
                    @if($sale->total > $sale->paid)
                    <tr>
                        <td>Due:</td>
                        <td class="text-right due">{{ number_format($sale->total - $sale->paid, 2) }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            <!-- Payment Methods (if available) -->
            @if(isset($sale->bankAccount) && $sale->bankAccount)
            <div class="payment-methods">
                <div style="font-weight: bold; margin-bottom: 5px;">Payment Information</div>
                <div>Bank: {{ $sale->bankAccount->bank_name }}</div>
                <div>Account: {{ $sale->bankAccount->account_name }}</div>
                @if(isset($sale->bankAccount->account_number))
                <div>Account #: {{ $sale->bankAccount->account_number }}</div>
                @endif
            </div>
            @endif

            <!-- Barcode -->
            <div class="barcode">
                *{{ $sale->invoice_no }}*
            </div>

            <!-- Footer -->
            <div class="footer">
                @if($sale->note)
                <p style="margin-bottom: 5px;"><strong>Note:</strong> {{ $sale->note }}</p>
                @endif
                <p>Thank you for your business!</p>
                <p>Returns accepted within 7 days with original receipt</p>
                <p>Generated on {{ now()->format('d M, Y h:i A') }}</p>
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
