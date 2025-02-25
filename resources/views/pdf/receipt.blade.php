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
            background: #f0f0f0;
            font-family: Arial, sans-serif;
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
        .header {
            margin-bottom: 15mm;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 3mm;
        }

        .company-info {
            font-size: 10px;
            line-height: 1.4;
        }

        /* Invoice Details */
        .invoice-section {
            margin-bottom: 10mm;
            display: flex;
            justify-content: space-between;
        }

        .invoice-details, .customer-details {
            font-size: 10px;
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
            font-size: 10px;
        }

        .items-table th {
            background: #f8f8f8;
            padding: 2mm;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .items-table td {
            padding: 2mm;
            border-bottom: 1px solid #eee;
        }

        .items-table .amount {
            text-align: right;
        }

        .items-table .quantity {
            text-align: center;
        }

        /* Category Header */
        .category-header {
            background: #f0f0f0;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Category Subtotal */
        .category-subtotal {
            background: #f8f8f8;
            font-weight: bold;
            font-style: italic;
            border-bottom: 2px solid #ddd;
        }

        /* Category Section */
        .category-section {
            margin-bottom: 8mm;
        }

        /* Totals Section */
        .totals-section {
            width: 60%;
            margin-left: auto;
            margin-bottom: 10mm;
        }

        .totals-table {
            width: 100%;
            font-size: 10px;
        }

        .totals-table td {
            padding: 1mm 2mm;
        }

        .totals-table .right-align {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            font-size: 12px;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }

        /* Payment Info */
        .payment-info {
            margin-bottom: 10mm;
            font-size: 10px;
        }

        /* Barcode */
        .barcode {
            text-align: center;
            margin: 5mm 0;
            font-size: 16px;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin-top: 10mm;
            padding-top: 5mm;
            border-top: 1px solid #eee;
        }

        @media print {
            body {
                background: none;
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
                <div class="sub-company-name"></div>
                <div class="company-details">
                    Ukilpara, Naogaon Sadar, Naogaon.<br>
                    Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
                </div>
            </div>

            <!-- Invoice Details Section -->
            <div class="invoice-section">
                <div class="invoice-details">
                    <div class="invoice-title">SALE INVOICE</div>
                    <p><strong>Invoice No:</strong> {{ $sale->invoice_no }}</p>
                    <p><strong>Date:</strong> {{ $sale->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Payment Method:</strong> {{ ucfirst($sale->payment_method) }}</p>
                </div>
                <div class="customer-details">
                    <p><strong>Bill To:</strong></p>
                    <p>{{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}</p>
                </div>
            </div>

            <!-- Items Table Grouped by Category -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 45%">Description</th>
                        <th style="width: 15%" class="quantity">Qty</th>
                        <th style="width: 20%" class="amount">Price</th>
                        <th style="width: 20%" class="amount">Amount</th>
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
                            <tr class="category-header">
                                <td colspan="4">{{ $categoryName }}</td>
                            </tr>

                            <!-- Items in this category -->
                            @foreach($items as $item)
                            <tr>
                                <td>{{ $item->product->name }}</td>
                                <td class="quantity">{{ $item->quantity }}</td>
                                <td class="amount">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="amount">{{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                            @endforeach

                            <!-- Category Subtotal -->
                            <tr class="category-subtotal">
                                <td colspan="3" style="text-align: right; padding-right: 10mm;">{{ $categoryName }} Subtotal:</td>
                                <td class="amount">{{ number_format($categoryTotals[$categoryName], 2) }}</td>
                            </tr>

                            <!-- Empty spacer row for margin -->
                            <tr style="height: 5mm">
                                <td colspan="4" style="border: none;"></td>
                            </tr>
                        </tbody>
                    @endforeach
                </tbody>
            </table>

            <!-- Totals Section -->
            <div class="totals-section">
                <table class="totals-table">
                    <tr>
                        <td>Subtotal:</td>
                        <td class="right-align">{{ number_format($sale->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Tax:</td>
                        <td class="right-align">{{ number_format($sale->tax, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Discount:</td>
                        <td class="right-align">{{ number_format($sale->discount, 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total Amount:</td>
                        <td class="right-align">{{ number_format($sale->total, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Paid Amount:</td>
                        <td class="right-align">{{ number_format($sale->paid, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Change:</td>
                        <td class="right-align">{{ number_format(max(0, $sale->paid - $sale->total), 2) }}</td>
                    </tr>
                </table>
            </div>

            <!-- Barcode -->
            <div class="barcode">
                *{{ $sale->invoice_no }}*
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>Thank you for your business!</p>
                <p>Returns accepted within 7 days with original receipt</p>
                <p>&copy; {{ date('Y') }} {{ $company['name'] }}</p>
            </div>
        </div>
    </div>
</body>
</html>
