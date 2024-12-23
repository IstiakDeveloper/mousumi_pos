<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock Report</title>
    <style>
        /* Include the base styles we created earlier */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            margin: 15px;
        }

        /* Add Stock Report specific styles */
        .stock-movement {
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .transaction-table th {
            background: #f4f4f4;
            font-weight: bold;
            text-align: left;
            padding: 4px;
            font-size: 8px;
        }

        .transaction-table td {
            padding: 4px;
            border: 1px solid #ddd;
        }

        .monthly-summary {
            background: #f8f8f8;
            font-weight: bold;
        }

        /* Keep other necessary styles */
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.name') }}</div>
        <div class="report-title">Stock Movement Report</div>
        <div class="date-range">Period: {{ $filters['from_date'] }} to {{ $filters['to_date'] }}</div>
    </div>

    @foreach($reports as $report)
        <div class="product-section">
            <div class="section-header">
                <h3>{{ $report['product']['name'] }} ({{ $report['product']['sku'] }})</h3>
                <div class="details">
                    Category: {{ $report['product']['category'] }} |
                    Unit: {{ $report['product']['unit'] }} |
                    Opening Stock: {{ $report['opening_stock'] }}
                </div>
            </div>

            @foreach($report['monthly_data'] as $monthData)
                <div class="stock-movement">
                    <div class="month-header">
                        {{ $monthData['month'] }} - Opening Stock: {{ $monthData['opening_stock'] }}
                    </div>

                    @if(count($monthData['stock_ins']) > 0)
                        <div class="movement-section">
                            <h4>Stock In</h4>
                            <table class="transaction-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Quantity</th>
                                        <th>Unit Cost</th>
                                        <th>Total Cost</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monthData['stock_ins'] as $stockIn)
                                        <tr>
                                            <td>{{ $stockIn['date'] }}</td>
                                            <td class="text-right">{{ $stockIn['quantity'] }}</td>
                                            <td class="text-right">{{ number_format($stockIn['unit_cost'], 2) }}</td>
                                            <td class="text-right">{{ number_format($stockIn['total_cost'], 2) }}</td>
                                            <td>{{ $stockIn['note'] }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="monthly-summary">
                                        <td>Total Stock In</td>
                                        <td class="text-right">{{ $monthData['total_in'] }}</td>
                                        <td></td>
                                        <td class="text-right">{{ number_format($monthData['in_value'], 2) }}</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if(count($monthData['stock_outs']) > 0)
                        <div class="movement-section">
                            <h4>Stock Out (Sales)</h4>
                            <table class="transaction-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Invoice No</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monthData['stock_outs'] as $stockOut)
                                        <tr>
                                            <td>{{ $stockOut['date'] }}</td>
                                            <td>{{ $stockOut['invoice_no'] }}</td>
                                            <td class="text-right">{{ $stockOut['quantity'] }}</td>
                                            <td class="text-right">{{ number_format($stockOut['unit_price'], 2) }}</td>
                                            <td class="text-right">{{ number_format($stockOut['total'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="monthly-summary">
                                        <td colspan="2">Total Stock Out</td>
                                        <td class="text-right">{{ $monthData['total_out'] }}</td>
                                        <td></td>
                                        <td class="text-right">{{ number_format($monthData['out_value'], 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="monthly-summary">
                        <table class="transaction-table">
                            <tr>
                                <td colspan="2">Closing Stock: {{ $monthData['closing_stock'] }}</td>
                                <td>Net Movement: {{ $monthData['total_in'] - $monthData['total_out'] }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endforeach

            <div class="product-summary">
                <table class="transaction-table">
                    <tr class="monthly-summary">
                        <td>Current Stock: {{ $report['current_stock'] }}</td>
                        <td>Total Value: {{ number_format($report['current_stock'] * $report['product']['cost_price'], 2) }} BDT</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="page-break"></div>
    @endforeach

    <div class="footer">
        <div>Generated on: {{ now()->format('d M, Y h:i A') }}</div>
    </div>
</body>
</html>
