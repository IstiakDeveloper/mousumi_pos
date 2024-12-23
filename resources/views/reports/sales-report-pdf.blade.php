<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            margin: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .company-name { font-size: 18px; font-weight: bold; }
        .report-title { font-size: 14px; margin: 5px 0; }
        .date-range { font-size: 11px; color: #666; }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #ddd;
            padding: 5px;
        }

        .month-section {
            margin-top: 15px;
            page-break-inside: avoid;
        }

        .month-header {
            background: #f4f4f4;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
        }

        .sales-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8px;
        }

        .sales-table th,
        .sales-table td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }

        .sales-table th { background: #f8f8f8; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .paid { color: #0b4; }
        .due { color: #d42; }

        .day-total {
            background: #f4f4f4;
            font-weight: bold;
        }

        .payment-summary {
            margin: 10px 0;
            padding: 5px;
            background: #f8f8f8;
            border: 1px solid #ddd;
        }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.name') }}</div>
        <div class="report-title">Sales Report</div>
        <div class="date-range">Period: {{ $filters['from_date'] }} to {{ $filters['to_date'] }}</div>
    </div>

    <!-- Summary -->
    <table class="summary-table">
        <tr>
            <th>Total Sales</th>
            <th>Gross Total</th>
            <th>Discount</th>
            <th>Tax</th>
            <th>Net Total</th>
            <th>Received</th>
            <th>Due</th>
        </tr>
        <tr>
            <td class="text-center">{{ $summary['total_sales'] }}</td>
            <td class="text-right">{{ number_format($summary['gross_total'], 2) }} BDT</td>
            <td class="text-right">{{ number_format($summary['discount'], 2) }} BDT</td>
            <td class="text-right">{{ number_format($summary['tax'], 2) }} BDT</td>
            <td class="text-right">{{ number_format($summary['net_total'], 2) }} BDT</td>
            <td class="text-right paid">{{ number_format($summary['received'], 2) }} BDT</td>
            <td class="text-right due">{{ number_format($summary['due'], 2) }} BDT</td>
        </tr>
    </table>

    @foreach($monthly_reports as $report)
        <div class="month-section">
            <div class="month-header">
                <div style="font-size: 12px; font-weight: bold;">{{ $report['month'] }}</div>
                <div>
                    Total Sales: {{ $report['summary']['total_sales'] }} |
                    Items Sold: {{ $report['summary']['total_items'] }}
                </div>
            </div>

            <!-- Payment Methods Summary -->
            <div class="payment-summary">
                <table style="width: 100%">
                    <tr>
                        @foreach($report['summary']['payment_methods'] as $method => $amount)
                            <td style="text-align: center; padding: 5px;">
                                <div style="color: #666; font-size: 8px;">{{ ucfirst($method) }}</div>
                                <div>{{ number_format($amount, 2) }} BDT</div>
                            </td>
                        @endforeach
                    </tr>
                </table>
            </div>

            <!-- Daily Sales -->
            @foreach($report['daily_sales'] as $day)
                <div style="margin-top: 10px;">
                    <div style="font-weight: bold; margin-bottom: 5px;">{{ $day['date'] }}</div>
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th class="text-right">Items</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Paid</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($day['sales'] as $sale)
                                <tr>
                                    <td>{{ $sale['created_at'] }}</td>
                                    <td>{{ $sale['invoice_no'] }}</td>
                                    <td>{{ $sale['customer'] }}</td>
                                    <td class="text-right">{{ count($sale['items']) }}</td>
                                    <td class="text-right">{{ number_format($sale['total'], 2) }}</td>
                                    <td class="text-right">{{ number_format($sale['paid'], 2) }}</td>
                                    <td>{{ ucfirst($sale['payment_status']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="day-total">
                                <td colspan="3">Day Total</td>
                                <td class="text-right">{{ $day['summary']['total_items'] }}</td>
                                <td class="text-right">{{ number_format($day['summary']['net_total'], 2) }}</td>
                                <td class="text-right">{{ number_format($day['summary']['received'], 2) }}</td>
                                <td class="text-right">{{ number_format($day['summary']['due'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach

            <div style="margin-top: 15px; padding: 10px; background: #f8f8f8; border: 1px solid #ddd;">
                <div>Monthly Summary</div>
                <div>
                    Net Total: {{ number_format($report['summary']['net_total'], 2) }} BDT |
                    Received: {{ number_format($report['summary']['received'], 2) }} BDT |
                    Due: {{ number_format($report['summary']['due'], 2) }} BDT
                </div>
            </div>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

    <div style="text-align: center; margin-top: 20px; font-size: 8px; color: #666;">
        Generated on {{ now()->format('d M, Y h:i A') }}
    </div>
</body>
</html>
