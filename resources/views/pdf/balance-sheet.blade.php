<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A4 Invoice Receipt</title>
    <style>
        /* মূল মার্জিন সেটিং - প্রতিটি পৃষ্ঠার জন্য */
        @page {
            size: A4 portrait;
            margin: 25.4mm;
            /* কমানো হয়েছে */
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.2;
            /* কমানো হয়েছে */
            font-size: 10pt;
            margin: 0;
            padding: 0;
            color: #000;
            background: #fff;
        }

        /* টেবিল সাধারণ স্টাইল */
        table {
            width: 100%;
            border-collapse: collapse;
        }


        /* কোম্পানি হেডার */
        .company-header {
            margin-bottom: 1mm;
            /* কমানো হয়েছে */
        }

        .company-name {
            font-size: 16pt;
            /* কমানো হয়েছে */
            font-weight: bold;
            margin-bottom: 1mm;
            /* কমানো হয়েছে */
        }

        .company-details {
            font-size: 12pt;
            /* কমানো হয়েছে */
            line-height: 1;
        }

        /* ইনভয়েস ডিটেইলস */
        .invoice-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3mm;
            /* যোগ করা হয়েছে */
        }

        .invoice-section .invoice-title {
            margin: 2mm 0;
        }

        .invoice-details,
        .customer-details {
            font-size: 9pt;
            line-height: 1.2;
            /* যোগ করা হয়েছে */
        }

        .invoice-title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 0.5mm;
            /* যোগ করা হয়েছে */
        }

        /* প্যারাগ্রাফ স্পেসিং */
        p {
            margin: 0;
            margin-bottom: 1mm;
            /* যোগ করা হয়েছে */
        }

        /* আইটেম টেবিল */
        .items-table {
            margin-bottom: 10mm;
        }

        .items-table th {
            background: #f5f5f5;
            padding: 3mm 2mm;
            text-align: left;
            font-weight: bold;
            border-bottom: 0.5pt solid #000;
            font-size: 9pt;
        }

        .items-table td {
            padding: 2mm;
            border-bottom: 0.2pt solid #ddd;
            vertical-align: top;
            font-size: 9pt;
        }

        .items-table .amount {
            text-align: right;
        }

        .items-table .quantity {
            text-align: center;
        }

        /* ক্যাটাগরি হেডার */
        .category-header td {
            background: #eaeaea;
            font-weight: bold;
            text-transform: uppercase;
            padding: 2mm;
            font-size: 9pt;
            border-bottom: 0.5pt solid #999;
        }

        /* ক্যাটাগরি সাবটোটাল */
        .category-subtotal td {
            background: #f8f8f8;
            font-weight: bold;
            font-style: italic;
            border-bottom: 0.5pt solid #666;
            padding: 2mm;
        }

        /* টোটাল সেকশন */
        .totals-section {
            width: 60%;
            margin-left: auto;
            margin-bottom: 10mm;
        }

        .totals-table {
            width: 100%;
        }

        .totals-table td {
            padding: 1mm 2mm;
            border: none;
        }

        .totals-table .right-align {
            text-align: right;
        }

        .total-row td {
            font-weight: bold;
            font-size: 11pt;
            border-top: 0.5pt solid #000;
            border-bottom: 0.5pt solid #000;
            padding: 2mm;
        }

        /* বারকোড */
        .barcode {
            text-align: center;
            margin: 5mm 0;
            font-size: 14pt;
        }

        /* ফুটার */
        .footer {
            text-align: center;
            font-size: 8pt;
            color: #666;
            margin-top: 10mm;
            padding-top: 3mm;
            border-top: 0.5pt solid #ddd;
        }

        /* স্পেসার */
        .spacer {
            height: 5mm;
        }

        /* মেইন কন্টেইনার - মার্জিন নিয়ন্ত্রণ শুধু DOMPDF এর জন্য */
        .main-container {
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <!-- কোম্পানি হেডার -->
        <div class="company-header">
            <div class="company-name">{{ config('app.name', 'Your Company Name') }}/ Departmental Store</div>
            <div class="company-details">
                Ukilpara, Naogaon Sadar, Naogaon.<br>
                Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
            </div>
        </div>

        <!-- ইনভয়েস ডিটেইলস সেকশন -->
        <div class="invoice-section">
            <div class="invoice-details">
                <div class="invoice-title">SALE INVOICE</div>
                <p><strong>Invoice No:</strong> {{ $sale->invoice_no }}</p>
                <p><strong>Date:</strong> {{ $sale->created_at->format('d/m/Y H:i') }}</p>
                <p><strong>Payment Method:</strong>
                    {{ $sale->payment_method ? ucfirst($sale->payment_method) : 'Cash' }}</p>
            </div>
            <div class="customer-details">
                <p><strong>Customer Name:</strong> {{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}
                </p>
            </div>
        </div>

        <!-- আইটেম টেবিল -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45%">Description</th>
                    <th style="width: 15%" class="quantity">Qty</th>
                    <th style="width: 20%" class="amount">Price</th>
                    <th style="width: 20%" class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($itemsByCategory as $categoryName => $items)
                    <tr class="category-header">
                        <td colspan="4">{{ $categoryName }}</td>
                    </tr>

                    <!-- আইটেমস ইন দিস ক্যাটাগরি -->
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item->product->name }}</td>
                            <td class="quantity">{{ $item->quantity }}</td>
                            <td class="amount">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="amount">{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach

                    <!-- ক্যাটাগরি সাবটোটাল -->
                    <tr class="category-subtotal">
                        <td colspan="3" style="text-align: right">{{ $categoryName }} Subtotal:</td>
                        <td class="amount">{{ number_format($categoryTotals[$categoryName], 2) }}</td>
                    </tr>

                    <!-- স্পেসার রো -->
                    <tr>
                        <td colspan="4" style="height: 5mm; border: none;"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- টোটালস সেকশন -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>Total:</td>
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
                    <td>Grand Total:</td>
                    <td class="right-align">{{ number_format($sale->total, 2) }}</td>
                </tr>
                <tr>
                    <td>Paid Amount:</td>
                    <td class="right-align">{{ number_format($sale->paid, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- বারকোড -->
        <div class="barcode">
            *{{ $sale->invoice_no }}*
        </div>

        <!-- ফুটার -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Returns accepted within 7 days with original receipt</p>
            <p>&copy; {{ date('Y') }} {{ $company['name'] }}</p>
        </div>
    </div>
</body>

</html>
