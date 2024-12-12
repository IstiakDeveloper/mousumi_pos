<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>POS Receipt</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace; /* Standard receipt font */
            font-size: 10px;
            width: 80mm; /* Standard thermal receipt width */
            margin: 0 auto;
            padding: 5mm;
        }

        /* Header Styles */
        .receipt-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .logo-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .store-info {
            font-size: 9px;
            margin-bottom: 3px;
        }

        /* Invoice Details */
        .invoice-details {
            margin: 10px 0;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }

        .invoice-details p {
            font-size: 9px;
            margin-bottom: 2px;
        }

        /* Items Table */
        .receipt-table {
            width: 100%;
            margin: 10px 0;
        }

        .receipt-table th {
            font-size: 9px;
            text-align: left;
            padding: 3px 0;
            border-bottom: 1px dashed #000;
        }

        .receipt-table td {
            font-size: 9px;
            padding: 3px 0;
        }

        .receipt-table .quantity {
            width: 15%;
            text-align: center;
        }

        .receipt-table .price {
            width: 25%;
            text-align: right;
        }

        .receipt-table .amount {
            width: 25%;
            text-align: right;
        }

        /* Totals Section */
        .receipt-totals {
            margin-top: 10px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }

        .totals-table {
            width: 100%;
            margin-top: 5px;
        }

        .totals-table td {
            font-size: 9px;
            padding: 2px 0;
        }

        .totals-table td:last-child {
            text-align: right;
        }

        .total-amount {
            font-size: 12px;
            font-weight: bold;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
            margin: 5px 0;
        }

        /* Payment Info */
        .payment-info {
            margin: 10px 0;
            padding: 5px 0;
            border-bottom: 1px dashed #000;
        }

        /* Footer */
        .receipt-footer {
            text-align: center;
            margin-top: 10px;
            font-size: 9px;
        }

        .footer-message {
            margin: 5px 0;
        }

        .barcode {
            text-align: center;
            margin: 10px 0;
            font-family: 'Libre Barcode 39', cursive;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="receipt-header">
        <div class="logo-name">{{ $company['name'] }}</div>
        <div class="store-info">{{ $company['address'] }}</div>
        <div class="store-info">Tel: {{ $company['phone'] }}</div>
        <div class="store-info">{{ $company['email'] }}</div>
    </div>

    <div class="invoice-details">
        <p><strong>Invoice:</strong> {{ $sale->invoice_no }}</p>
        <p><strong>Date:</strong> {{ $sale->created_at->format('d/m/Y H:i') }}</p>
        <p><strong>Customer:</strong> {{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}</p>
        <p><strong>Cashier:</strong> {{ $sale->created_by }}</p>
    </div>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>Item</th>
                <th class="quantity">Qty</th>
                <th class="price">Price</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->saleItems as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td class="quantity">{{ $item->quantity }}</td>
                <td class="price">${{ number_format($item->unit_price, 2) }}</td>
                <td class="amount">${{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="receipt-totals">
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td>${{ number_format($sale->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Tax:</td>
                <td>${{ number_format($sale->tax, 2) }}</td>
            </tr>
            <tr>
                <td>Discount:</td>
                <td>${{ number_format($sale->discount, 2) }}</td>
            </tr>
        </table>

        <div class="total-amount">
            <table class="totals-table">
                <tr>
                    <td><strong>TOTAL:</strong></td>
                    <td><strong>${{ number_format($sale->total, 2) }}</strong></td>
                </tr>
            </table>
        </div>

        <div class="payment-info">
            <table class="totals-table">
                <tr>
                    <td>Paid Amount:</td>
                    <td>${{ number_format($sale->paid, 2) }}</td>
                </tr>
                <tr>
                    <td>Change:</td>
                    <td>${{ number_format(max(0, $sale->paid - $sale->total), 2) }}</td>
                </tr>
                <tr>
                    <td>Payment Method:</td>
                    <td>{{ ucfirst($sale->payment_method) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="barcode">
        *{{ $sale->invoice_no }}*
    </div>

    <div class="receipt-footer">
        <div class="footer-message">Thank you for your purchase!</div>
        <div class="footer-message">Please keep this receipt for any returns</div>
        <div class="footer-message">Returns accepted within 7 days</div>
        <div class="footer-message">with original receipt</div>
    </div>
</body>
</html>
