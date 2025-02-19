<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Stock Movement Report</title>
    <style>
        /* Base styles - Reduced spacing */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
            font-size: 12px;
            line-height: 1.3;
            color: #333;
        }

        /* Header section - More compact */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .company-details {
            color: #666;
            margin-bottom: 10px;
            font-size: 11px;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .date-range {
            color: #666;
            font-size: 11px;
        }

        /* Product section - Reduced margins */
        .product-section {
            margin-bottom: 25px;
            break-inside: avoid;
        }

        .product-header {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .product-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .product-info {
            color: #666;
            font-size: 11px;
        }

        /* Summary box - Grid layout */
        .summary-box {
            width: 100%;
            margin-bottom: 10px;
            background: #fff;
            border: 1px solid #e5e7eb;
        }

        .summary-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .summary-row {
            display: table-row;
        }

        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 8px;
            border-right: 1px solid #e5e7eb;
        }

        .summary-item:last-child {
            border-right: none;
        }

        .summary-label {
            font-size: 8px;
            color: #666;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 10px;
            font-weight: bold;
            color: #1f2937;
        }

        /* Tables - More compact */
        .table-container {
            margin-bottom: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }

        th,
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }

        th {
            background: #f8f9fa;
            font-weight: bold;
            text-align: left;
        }

        thead tr:first-child th {
            background: #e9ecef;
            text-align: center;
            padding: 5px;
        }

        /* Text utilities */
        .text-right {
            text-align: right;
        }

        .text-green {
            color: #28a745;
        }

        .text-red {
            color: #dc3545;
        }

        /* Footer - Reduced size */
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 10px;
        }

        /* Print specific styles */
        @media print {
            body {
                padding: 10px;
            }

            .product-section {
                page-break-inside: avoid;
            }

            .table-container {
                page-break-inside: avoid;
            }

            /* Adjust table for print */
            table {
                font-size: 10px;
            }

            th,
            td {
                padding: 4px 6px;
            }

            .footer {
                position: fixed;
                bottom: 0;
                width: 100%;
                background: white;
                padding: 5px 10px;
                margin-top: 10px;
            }
        }


        @media screen and (max-width: 768px) {


            table {
                min-width: 500px;
            }
        }

        @media screen and (max-width: 480px) {}
    </style>
</head>

<body>
    <div class="company-header">
        <div class="company-name">{{ config('app.name', 'Your Company Name') }}/ Departmental Store</div>
        <div class="sub-company-name"></div>
        <div class="company-details">
            Ukilpara, Naogaon Sadar, Naogaon.<br>
            Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
        </div>
    </div>
    <div class="report-title">Stock Movement Report</div>
    <div class="date-range">{{ $filters['from_date'] }} to {{ $filters['to_date'] }}</div>


    @foreach ($reports as $report)
        <div class="product-section">
            <div class="product-header">
                <div class="product-name">
                    {{ $report['product']['name'] }}
                    <span style="font-size: 10px; color: #666;">({{ $report['product']['sku'] }})</span>
                </div>
                <div class="product-info">
                    Category: {{ $report['product']['category'] }} |
                    Unit: {{ $report['product']['unit'] }}
                </div>
            </div>

            <div class="summary-box">
                <div class="summary-grid">
                    <div class="summary-row">
                        <div class="summary-item">
                            <div class="summary-label">Opening Stock</div>
                            <div class="summary-value">{{ $report['summary']['opening_stock'] }}</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Total Purchased</div>
                            <div class="summary-value text-green">{{ $report['summary']['total_purchased'] }}</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Total Sold</div>
                            <div class="summary-value text-red">{{ $report['summary']['total_sold'] }}</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Current Stock</div>
                            <div class="summary-value">{{ $report['summary']['current_stock'] }}</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Average Cost</div>
                            <div class="summary-value">BDT{{ number_format($report['summary']['avg_cost'], 2) }}</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Stock Value</div>
                            <div class="summary-value">BDT{{ number_format($report['summary']['stock_value'], 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (count($report['purchases']) > 0)
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th colspan="5">Purchase History</th>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Unit Cost</th>
                                <th class="text-right">Total Cost</th>
                                <th class="text-right">Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['purchases'] as $purchase)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($purchase['date'])->format('d M, Y') }}</td>
                                    <td class="text-right">{{ $purchase['quantity'] }}</td>
                                    <td class="text-right">BDT{{ number_format($purchase['unit_cost'], 2) }}</td>
                                    <td class="text-right">BDT{{ number_format($purchase['total_cost'], 2) }}</td>
                                    <td class="text-right">{{ $purchase['available_quantity'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if (count($report['sales']) > 0)
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th colspan="6">Sales History</th>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['sales'] as $sale)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($sale['date'])->format('d M, Y') }}</td>
                                    <td>{{ $sale['invoice_no'] }}</td>
                                    <td class="text-right text-red">{{ $sale['quantity'] }}</td>
                                    <td class="text-right">BDT{{ number_format($sale['unit_price'], 2) }}</td>
                                    <td class="text-right">BDT{{ number_format($sale['total'], 2) }}</td>
                                    <td class="text-right">{{ $sale['available_quantity'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach

    <div class="footer">
        <div>Generated on: {{ now()->format('d M, Y h:i A') }}</div>
        <div>Page {PAGE_NUM} of {PAGE_COUNT}</div>
    </div>
</body>

</html>
