# resources/views/reports/bank-report-pdf.blade.php
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bank Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.3;
            margin: 15px 15px;
            color: #333;
        }

        .header {
            text-align: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 5px 0;
        }

        .date-range {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        .meta-info {
            margin: 10px 0;
            padding: 8px;
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .summary-table th,
        .summary-table td {
            padding: 6px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .summary-table th {
            background: #f4f4f4;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }

        .account-section {
            margin-top: 15px;
            page-break-inside: avoid;
        }

        .account-header {
            background: #f4f4f4;
            padding: 8px;
            margin-bottom: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .account-name {
            font-size: 12px;
            font-weight: bold;
        }

        .account-details {
            font-size: 9px;
            color: #666;
            margin-top: 3px;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .transactions-table th,
        .transactions-table td {
            padding: 5px;
            border: 1px solid #ddd;
        }

        .transactions-table th {
            background: #f8f8f8;
            font-weight: bold;
            text-transform: uppercase;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .amount {
            font-family: 'DejaVu Sans Mono', monospace;
            white-space: nowrap;
        }

        .positive {
            color: #0b4;
            font-weight: bold;
        }

        .negative {
            color: #d42;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
            page-break-inside: avoid;
        }

        .page-break {
            page-break-after: always;
        }

        /* Table Column Widths */
        .col-month { width: 20%; }
        .col-amount { width: 20%; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.name', 'Company Name') }}</div>
        <div class="report-title">Bank Balance Report</div>
        <div class="date-range">Report Period: {{ $date_range['from'] }} to {{ $date_range['to'] }}</div>
    </div>

    <div class="meta-info">
        <table class="summary-table">
            <tr>
                <th>Total Accounts</th>
                <th>Total Balance</th>
                <th>Total Deposits</th>
                <th>Total Withdrawals</th>
            </tr>
            <tr>
                <td class="text-center">{{ $summary['total_accounts'] }}</td>
                <td class="text-right amount">{{ number_format($summary['total_balance'], 2) }} BDT</td>
                <td class="text-right amount positive">{{ number_format($summary['total_deposits'], 2) }} BDT</td>
                <td class="text-right amount negative">{{ number_format($summary['total_withdrawals'], 2) }} BDT</td>
            </tr>
        </table>
    </div>

    @foreach($reports as $index => $report)
        <div class="account-section">
            @if($index > 0 && $index % 2 == 0)
                <div class="page-break"></div>
            @endif

            <div class="account-header">
                <div class="account-name">{{ $report['account']['bank'] }} - {{ $report['account']['name'] }}</div>
                <div class="account-details">
                    Account No: {{ $report['account']['number'] }}<br>
                    Previous Balance: {{ number_format($report['previous_balance'], 2) }} BDT
                </div>
            </div>

            <table class="transactions-table">
                <thead>
                    <tr>
                        <th class="col-month">Month</th>
                        <th class="col-amount text-right">Deposits</th>
                        <th class="col-amount text-right">Withdrawals</th>
                        <th class="col-amount text-right">Net Change</th>
                        <th class="col-amount text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['monthly_data'] as $month)
                        <tr>
                            <td>{{ $month['month'] }}</td>
                            <td class="text-right amount">{{ number_format($month['deposits'], 2) }}</td>
                            <td class="text-right amount">{{ number_format($month['withdrawals'], 2) }}</td>
                            <td class="text-right amount {{ $month['net'] >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($month['net'], 2) }}
                            </td>
                            <td class="text-right amount {{ $month['balance'] >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($month['balance'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="footer">
        <div>Generated on: {{ now()->format('d M, Y h:i A') }}</div>
        <div>Page Generated by {{ config('app.name', 'Company Name') }}</div>
    </div>
</body>
</html>
