<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Transaction Report</title>
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
            text-align: center;
        }

        table th {
            font-weight: bold;
            color: #2c3e50;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .bg-deposit {
            background-color: #e8fff0;
        }

        .bg-withdrawal {
            background-color: #fff3e8;
        }

        .text-green {
            color: #059669;
        }

        .text-red {
            color: #dc2626;
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
    </style>
</head>

<body>
    <div class="container">
        <!-- Company Header -->
        <div class="company-header">
            <div class="company-name">{{ config('app.name', 'Your Company Name') }}/ Variety Store</div>
            <div class="sub-company-name"></div>
            <div class="company-details">
                Ukilpara, Naogaon Sadar, Naogaon.<br>
                Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
            </div>
        </div>

        <!-- Report Title and Period -->
        <div class="report-title">Bank Transaction Report</div>
        <div class="report-period">
            Account: {{ $bankAccount->bank_name }} - {{ $bankAccount->account_name }}<br>
            Period: {{ $month }} {{ $year }}
        </div>

        <!-- Transaction Table -->
        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 8%;">Date</th>
                    <th colspan="5" class="bg-deposit" style="width: 46%;">Deposit</th>
                    <th colspan="4" class="bg-withdrawal" style="width: 36%;">Withdrawal</th>
                    <th rowspan="2" style="width: 10%;">Bank Balance</th>
                </tr>
                <tr>
                    <!-- Deposit Headers -->
                    <th class="bg-deposit">Fund</th>
                    <th class="bg-deposit">Receive</th>
                    <th class="bg-deposit">Others</th>
                    <th class="bg-deposit">Refund</th>
                    <th class="bg-deposit">Total</th>

                    <!-- Withdrawal Headers -->
                    <th class="bg-withdrawal">Fund</th>
                    <th class="bg-withdrawal">Purchase</th>
                    <th class="bg-withdrawal">Expense</th>
                    <th class="bg-withdrawal">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-left">Previous Balance</td>
                    <td class="text-center bg-deposit">0.00</td>
                    <td class="text-center bg-deposit">0.00</td>
                    <td class="text-center bg-deposit">0.00</td>
                    <td class="text-center bg-deposit">0.00</td>
                    <td class="text-center bg-deposit">0.00</td>
                    <td class="text-center bg-withdrawal">0.00</td>
                    <td class="text-center bg-withdrawal">0.00</td>
                    <td class="text-center bg-withdrawal">0.00</td>
                    <td class="text-center bg-withdrawal">0.00</td>
                    <td class="text-center">{{ number_format($previousMonthBalance, 2) }}</td>
                </tr>

                @foreach($dailyTransactions as $transaction)
                <tr>
                    <td class="text-left">{{ \Carbon\Carbon::parse($transaction['date'])->format('d/m/Y') }}</td>

                    <!-- Deposit Columns -->
                    <td class="text-center bg-deposit text-green">{{ number_format($transaction['in']['fund'], 2) }}</td>
                    <td class="text-center bg-deposit text-green">{{ number_format($transaction['in']['payment'], 2) }}</td>
                    <td class="text-center bg-deposit text-green">{{ number_format($transaction['in']['extra'], 2) }}</td>
                    <td class="text-center bg-deposit text-green">{{ number_format($transaction['in']['refund'], 2) }}</td>
                    <td class="text-center bg-deposit text-green">{{ number_format($transaction['in']['total'], 2) }}</td>

                    <!-- Withdrawal Columns -->
                    <td class="text-center bg-withdrawal text-red">{{ number_format($transaction['out']['fund'], 2) }}</td>
                    <td class="text-center bg-withdrawal text-red">{{ number_format($transaction['out']['purchase'], 2) }}</td>
                    <td class="text-center bg-withdrawal text-red">{{ number_format($transaction['out']['expense'], 2) }}</td>
                    <td class="text-center bg-withdrawal text-red">{{ number_format($transaction['out']['total'], 2) }}</td>

                    <td class="text-center">{{ number_format($transaction['balance'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td class="text-left">Month Total:</td>
                    <!-- Deposit Totals -->
                    <td class="text-center bg-deposit text-green">{{ number_format($monthTotals['in']['fund'], 2) }}</td>
                    <td class="text-center bg-deposit text-green">{{ number_format($monthTotals['in']['payment'], 2) }}</td>
                    <td class="text-center bg-deposit text-green">{{ number_format($monthTotals['in']['extra'], 2) }}</td>
                    <td class="text-center bg-deposit text-green">{{ number_format($monthTotals['in']['refund'], 2) }}</td>
                    <td class="text-center bg-deposit text-green">{{ number_format($monthTotals['in']['total'], 2) }}</td>
                    <!-- Withdrawal Totals -->
                    <td class="text-center bg-withdrawal text-red">{{ number_format($monthTotals['out']['fund'], 2) }}</td>
                    <td class="text-center bg-withdrawal text-red">{{ number_format($monthTotals['out']['purchase'], 2) }}</td>
                    <td class="text-center bg-withdrawal text-red">{{ number_format($monthTotals['out']['expense'], 2) }}</td>
                    <td class="text-center bg-withdrawal text-red">{{ number_format($monthTotals['out']['total'], 2) }}</td>
                    <td class="text-center">{{ number_format(end($dailyTransactions)['balance'], 2) }}</td>
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
