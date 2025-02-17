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
            font-size: 8pt;
            line-height: 1.3;
            color: #333;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .company-header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #4a4a4a;
            padding-bottom: 8px;
        }

        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #1a1a1a;
        }

        .sub-company-name {
            font-size: 11pt;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .company-details {
            font-size: 9pt;
            color: #666;
            margin-bottom: 4px;
        }

        .report-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .report-period {
            text-align: center;
            font-size: 9pt;
            color: #7f8c8d;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 7pt;
        }

        table th,
        table td {
            padding: 4px 3px;
            border: 1px solid #202020;
        }

        table th {
            font-weight: bold;
            color: #2c3e50;
            text-align: center;
        }

        .text-center {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .bg-purple {
            background-color: #f8efff;
        }

        .bg-blue {
            background-color: #e8f4ff;
        }

        .bg-green {
            background-color: #e8fff0;
        }

        .bg-orange {
            background-color: #fff3e8;
        }

        .bg-yellow {
            background-color: #fffde8;
        }

        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
        }

        .footer {
            position: fixed;
            bottom: 8mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7pt;
            color: #7f8c8d;
        }

        .page-number:before {
            content: counter(page);
        }

        .product-cell {
            max-width: 150px;
            word-wrap: break-word;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Company Header -->
        <div class="company-header">
            <div class="company-name">{{ config('app.name', 'Your Company Name') }}/ Departmental Store</div>
            <div class="sub-company-name"></div>
            <div class="company-details">
                Ukilpara, Naogaon Sadar, Naogaon.<br>
                Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
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
                    <th rowspan="2" style="width: 3%;">SL</th>
                    <th rowspan="2" style="width: 15%;">Product Name</th>

                    <!-- Before Stock Section -->
                    <th colspan="3" class="bg-purple" style="width: 16%;">Before Stock Information</th>

                    <!-- Buy Info Section -->
                    <th colspan="3" class="bg-blue" style="width: 16%;">Buy Information</th>

                    <!-- Sale Info Section -->
                    <th colspan="3" class="bg-green" style="width: 16%;">Sale Information</th>

                    <!-- Profit Info Section -->
                    <th colspan="2" class="bg-orange" style="width: 14%;">Profit Information</th>

                    <!-- Available Info Section -->
                    <th colspan="2" class="bg-yellow" style="width: 20%;">Available Information</th>
                </tr>
                <tr>
                    <!-- Before Stock Headers -->
                    <th class="bg-purple">Quantity</th>
                    <th class="bg-purple">Price</th>
                    <th class="bg-purple">Value</th>

                    <!-- Buy Info Headers -->
                    <th class="bg-blue">Quantity</th>
                    <th class="bg-blue">Price</th>
                    <th class="bg-blue">Total</th>

                    <!-- Sale Info Headers -->
                    <th class="bg-green">Quantity</th>
                    <th class="bg-green">Price</th>
                    <th class="bg-green">Total</th>

                    <!-- Profit Info Headers -->
                    <th class="bg-orange">Per Unit</th>
                    <th class="bg-orange">Total</th>

                    <!-- Available Info Headers -->
                    <th class="bg-yellow">Stock</th>
                    <th class="bg-yellow">Value</th>
                </tr>
            </thead>
            <tbody>

                @foreach ($products as $product)
                    <tr>
                        <td class="text-center">{{ $product['serial'] }}</td>
                        <td class="product-cell">{{ $product['product_name'] }}</td>

                        <!-- Before Stock Info -->
                        <td class="text-center bg-purple">{{$product['before_quantity']}}</td>
                        <td class="text-center bg-purple">{{ number_format($product['before_price'], 2) }}</td>
                        <td class="text-center bg-purple">{{ number_format($product['before_value'], 2) }}</td>

                        <!-- Buy Info -->
                        <td class="text-center bg-blue">{{ $product['buy_quantity'] }}</td>
                        <td class="text-center bg-blue">{{ number_format($product['buy_price'], 2) }}</td>
                        <td class="text-center bg-blue">{{ number_format($product['total_buy_price'], 2) }}</td>

                        <!-- Sale Info -->
                        <td class="text-center bg-green">{{$product['sale_quantity']}}</td>
                        <td class="text-center bg-green">{{ number_format($product['sale_price'], 2) }}</td>
                        <td class="text-center bg-green">{{ number_format($product['total_sale_price'], 2) }}</td>

                        <!-- Profit Info -->
                        <td class="text-center bg-orange">{{ number_format($product['profit_per_unit'], 2) }}</td>
                        <td class="text-center bg-orange">{{ number_format($product['total_profit'], 2) }}</td>

                        <!-- Available Info -->
                        <td class="text-center bg-yellow">{{$product['available_quantity'] }}</td>
                        <td class="text-center bg-yellow">{{ number_format($product['available_stock_value'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="text-center">Totals:</td>
                    <!-- Before Stock Totals -->
                    <td class="text-center bg-purple">{{ $totals['before_quantity']}}</td>
                    <td class="text-center bg-purple">-</td>
                    <td class="text-center bg-purple">{{ number_format($totals['before_value'], 2) }}</td>
                    <!-- Buy Info Totals -->
                    <td class="text-center bg-blue">{{ $totals['buy_quantity']}}</td>
                    <td class="text-center bg-blue">-</td>
                    <td class="text-center bg-blue">{{ number_format($totals['total_buy_price'], 2) }}</td>
                    <!-- Sale Info Totals -->
                    <td class="text-center bg-green">{{ $totals['sale_quantity'] }}</td>
                    <td class="text-center bg-green">-</td>
                    <td class="text-center bg-green">{{ number_format($totals['total_sale_price'], 2) }}</td>
                    <!-- Profit Info Totals -->
                    <td class="text-center bg-orange">-</td>
                    <td class="text-center bg-orange">{{ number_format($totals['total_profit'], 2) }}</td>
                    <!-- Available Info Totals -->
                    <td class="text-center bg-yellow">{{ $totals['available_quantity'] }}</td>
                    <td class="text-center bg-yellow">{{ number_format($totals['available_stock_value'], 2) }}</td>
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
