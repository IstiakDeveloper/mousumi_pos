<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Analysis Report</title>
    <style>
        @page {
            margin: 15mm;
            size: landscape;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            color: #333;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .company-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #4a4a4a;
            padding-bottom: 10px;
        }

        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #1a1a1a;
        }

        .sub-company-name {
            font-size: 12pt;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 9pt;
            color: #666;
            margin-bottom: 5px;
        }

        .report-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .report-period {
            text-align: center;
            font-size: 10pt;
            color: #7f8c8d;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8pt;
        }

        table th,
        table td {
            padding: 5px 4px;
            border: 1px solid #202020;
        }

        table th {
            font-weight: bold;
            color: #2c3e50;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .bg-blue {
            background-color: #e8f4ff;
        }

        .bg-green {
            background-color: #e8fff0;
        }

        .bg-yellow {
            background-color: #fffde8;
        }

        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
        }

        .section-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 10mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #7f8c8d;
        }

        .page-number:before {
            content: counter(page);
        }

        .product-cell {
            max-width: 200px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Company Header -->
        <div class="company-header">
            <div class="company-name">{{ config('app.name', 'Your Company Name') }}</div>
            <div class="sub-company-name">Departmental Store</div>
            <div class="company-details">
                Ukilpara, Naogaon Sadar, Naogaon<br>
                Phone: (+88) 01718903743 | Email: contact@mousumiprokashon.com
            </div>
        </div>

        <!-- Report Title and Period -->
        <div class="report-title">Product Analysis Report</div>
        <div class="report-period">
            Period: {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} to
            {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}
        </div>

        <!-- Product Analysis Table -->
        <table>
            <thead>
                <tr>
                    <!-- Product Info Section -->
                    <th rowspan="2" class="text-center" style="width: 4%;">SL</th>
                    <th rowspan="2" class="text-left" style="width: 20%;">Product Name</th>
                    <!-- Buy Info Section -->
                    <th colspan="3" class="text-center bg-blue" style="width: 25%;">Buy Information</th>
                    <!-- Sale Info Section -->
                    <th colspan="3" class="text-center bg-green" style="width: 25%;">Sale Information</th>
                    <!-- Available Info Section -->
                    <th colspan="2" class="text-center bg-yellow" style="width: 26%;">Available Information</th>
                </tr>
                <tr>
                    <!-- Buy Info Headers -->
                    <th class="text-center bg-blue">Quantity</th>
                    <th class="text-center bg-blue">Price</th>
                    <th class="text-center bg-blue">Total</th>
                    <!-- Sale Info Headers -->
                    <th class="text-center bg-green">Quantity</th>
                    <th class="text-center bg-green">Price</th>
                    <th class="text-center bg-green">Total</th>
                    <!-- Available Info Headers -->
                    <th class="text-center bg-yellow">Stock</th>
                    <th class="text-center bg-yellow">Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr>
                    <td class="text-center">{{ $product['serial'] }}</td>
                    <td class="product-cell">{{ $product['product_name'] }}</td>
                    <!-- Buy Info -->
                    <td class="text-right bg-blue">{{ number_format($product['buy_quantity'], 2) }}</td>
                    <td class="text-right bg-blue">{{ number_format($product['buy_price'], 2) }}</td>
                    <td class="text-right bg-blue">{{ number_format($product['total_buy_price'], 2) }}</td>
                    <!-- Sale Info -->
                    <td class="text-right bg-green">{{ number_format($product['sale_quantity'], 2) }}</td>
                    <td class="text-right bg-green">{{ number_format($product['sale_price'], 2) }}</td>
                    <td class="text-right bg-green">{{ number_format($product['total_sale_price'], 2) }}</td>
                    <!-- Available Info -->
                    <td class="text-right bg-yellow">{{ number_format($product['available_quantity'], 2) }}</td>
                    <td class="text-right bg-yellow">{{ number_format($product['available_stock_value'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="text-right">Totals:</td>
                    <!-- Buy Info Totals -->
                    <td class="text-right bg-blue">{{ number_format($totals['buy_quantity'], 2) }}</td>
                    <td class="text-center bg-blue">-</td>
                    <td class="text-right bg-blue">{{ number_format($totals['total_buy_price'], 2) }}</td>
                    <!-- Sale Info Totals -->
                    <td class="text-right bg-green">{{ number_format($totals['sale_quantity'], 2) }}</td>
                    <td class="text-center bg-green">-</td>
                    <td class="text-right bg-green">{{ number_format($totals['total_sale_price'], 2) }}</td>
                    <!-- Available Info Totals -->
                    <td class="text-right bg-yellow">{{ number_format($totals['available_quantity'], 2) }}</td>
                    <td class="text-right bg-yellow">{{ number_format($totals['available_stock_value'], 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer -->
        <div class="footer">
            Page <span class="page-number"></span>
            <br>
            Generated on: {{ now()->format('d M Y H:i:s') }}
        </div>
    </div>
</body>
</html>
