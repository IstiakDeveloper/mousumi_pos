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

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 14px;
            margin: 5px 0;
        }

        .date-range {
            font-size: 11px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 5px;
            font-size: 8px;
        }

        th {
            background: #f8f8f8;
            font-weight: bold;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        .paid { color: #0b4; }
        .due { color: #d42; }
        .partial { color: #f90; }

        .month-section {
            margin-top: 20px;
            page-break-before: avoid;
        }

        .month-header {
            background: #f4f4f4;
            padding: 8px;
            margin-bottom: 10px;
            font-weight: bold;
            border: 1px solid #ddd;
        }

        .payment-methods {
            margin: 10px 0;
            padding: 8px;
            background: #f8f8f8;
            border: 1px solid #ddd;
        }

        .day-section {
            margin-top: 15px;
        }

        .day-header {
            background: #f8f8f8;
            padding: 5px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .subtotal-row {
            background: #f4f4f4;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 10px;
            font-size: 8px;
            color: #666;
        }

        .payment-badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7px;
        }

        .status-paid { background: #d1fae5; }
        .status-partial { background: #fef3c7; }
        .status-due { background: #fee2e2; }

        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.name') }}</div>
        <div class="report-title">Sales Report</div>
        <div class="date-range">{{ $filters['from_date'] }} to {{ $filters['to_date'] }}</div>
    </div>

    <!-- Overall Summary -->
    <table>
        <tr>
            <th>Total Sales</th>
            <th>Subtotal</th>
            <th>Discount</th>
            <th>Tax</th>
            <th>Total Amount</th>
            <th>Received</th>
            <th>Due</th>
        </tr>
        <tr>
            <td class="text-center">{{ $summary['total_sales'] }}</td>
            <td class="text-right">{{ number_format($summary['subtotal'], 2) }}</td>
            <td class="text-right">{{ number_format($summary['discount'], 2) }}</td>
            <td class="text-right">{{ number_format($summary['tax'], 2) }}</td>
            <td class="text-right">{{ number_format($summary['total_amount'], 2) }}</td>
            <td class="text-right paid">{{ number_format($summary['received'], 2) }}</td>
            <td class="text-right due">{{ number_format($summary['due'], 2) }}</td>
        </tr>
    </table>

    <!-- Monthly Reports -->
    @foreach($monthly_reports as $report)
    <div class="month-section">
        <div class="month-header">
            {{ $report['month'] }}
            <div style="font-size: 8px; font-weight: normal; margin-top: 5px;">
                Sales: {{ $report['summary']['total_sales'] }} |
                Amount: {{ number_format($report['summary']['total_amount'], 2) }} |
                Received: {{ number_format($report['summary']['received'], 2) }} |
                Due: {{ number_format($report['summary']['due'], 2) }}
            </div>
        </div>

        <!-- Payment Methods Summary -->
        <div class="payment-methods">
            <div style="font-weight: bold; margin-bottom: 5px;">Payment Methods</div>
            @foreach($report['summary']['payment_methods'] as $method => $details)
                <span style="margin-right: 15px;">
                    {{ ucfirst($method) }}: {{ number_format($details['amount'], 2) }}
                </span>
            @endforeach
        </div>

        <!-- Daily Sales -->
        @foreach($report['daily_sales'] as $day)
        <div class="day-section">
            <div class="day-header">{{ $day['date'] }}</div>

            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Due</th>
                        <th class="text-center">Status</th>
                        <th>Payment Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($day['sales'] as $sale)
                    <tr>
                        <td>{{ $sale['created_at'] }}</td>
                        <td>{{ $sale['invoice_no'] }}</td>
                        <td>{{ $sale['customer'] }}</td>
                        <td class="text-right">{{ number_format($sale['total'], 2) }}</td>
                        <td class="text-right paid">{{ number_format($sale['paid'], 2) }}</td>
                        <td class="text-right due">{{ number_format($sale['due'], 2) }}</td>
                        <td class="text-center">
                            <span class="payment-badge status-{{ $sale['payment_status'] }}">
                                {{ ucfirst($sale['payment_status']) }}
                            </span>
                        </td>
                        <td>
                            @foreach($sale['payments'] as $payment)
                                {{ ucfirst($payment['method']) }}: {{ number_format($payment['amount'], 2) }}
                                @if(!empty($payment['bank_account']))
                                    <br>
                                    <small style="color: #666;">
                                        {{ $payment['bank_account']['name'] }}
                                        ({{ $payment['bank_account']['account'] }})
                                        @if($payment['transaction_id'])
                                            #{{ $payment['transaction_id'] }}
                                        @endif
                                    </small>
                                @endif
                                @if(!$loop->last)<br>@endif
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="3">Day Total</td>
                        <td class="text-right">{{ number_format($day['summary']['total_amount'], 2) }}</td>
                        <td class="text-right paid">{{ number_format($day['summary']['received'], 2) }}</td>
                        <td class="text-right due">{{ number_format($day['summary']['due'], 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>

            <!-- Daily Payment Methods -->
            <div class="payment-methods" style="margin-top: 5px;">
                @foreach($day['summary']['payment_methods'] as $method => $details)
                    <span style="margin-right: 15px;">
                        {{ ucfirst($method) }}: {{ number_format($details['amount'], 2) }}
                        @if(!empty($details['bank_details']))
                            @foreach($details['bank_details'] as $bank)
                                <br>
                                <small style="color: #666;">
                                    {{ $bank['bank_name'] }}: {{ number_format($bank['amount'], 2) }}
                                </small>
                            @endforeach
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
        @endforeach

        <!-- Monthly Summary -->
        <div class="payment-methods" style="margin-top: 15px;">
            <div style="font-weight: bold;">Monthly Summary</div>
            <div style="margin-top: 5px;">
                Total: {{ number_format($report['summary']['total_amount'], 2) }} |
                Received: {{ number_format($report['summary']['received'], 2) }} |
                Due: {{ number_format($report['summary']['due'], 2) }}
            </div>
        </div>
    </div>
    @endforeach

    <div class="footer">
        Generated on {{ now()->format('d M, Y h:i A') }}
    </div>
</body>
</html>
