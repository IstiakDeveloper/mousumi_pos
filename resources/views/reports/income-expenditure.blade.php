<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Income & Expenditure Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #333;
            line-height: 1.6;
        }
        .container {
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .report-period {
            font-size: 16px;
            color: #666;
        }
        .summary-section {
            margin-bottom: 30px;
        }
        .summary-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #ddd;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-row {
            display: table-row;
        }
        .summary-label {
            display: table-cell;
            padding: 5px;
            font-weight: bold;
            width: 60%;
        }
        .summary-value {
            display: table-cell;
            padding: 5px;
            text-align: right;
        }
        .positive {
            color: #0f766e;
        }
        .negative {
            color: #dc2626;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f3f4f6;
            text-align: left;
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }
        .amount {
            text-align: right;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0 10px 0;
        }
        .page-break {
            page-break-after: always;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 12px;
            color: #666;
            padding: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="company-header">
            <div class="company-name">{{ config('app.name', 'Your Company Name') }}/ Departmental Store</div>
            <div class="sub-company-name"></div>
            <div class="company-details">
                Ukilpara, Naogaon Sadar, Naogaon.<br>
                Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
            </div>
        </div>

            <div class="report-period">{{ $filters['monthName'] }} {{ $filters['year'] }}</div>


        <!-- Monthly Summary Section -->
        <div class="summary-section">
            <div class="summary-title">Monthly Summary</div>
            <div class="summary-grid">
                <div class="summary-row">
                    <div class="summary-label">Sales Profit</div>
                    <div class="summary-value">BDT {{ number_format($summary['monthly']['sales_profit'], 2) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Extra Income</div>
                    <div class="summary-value">BDT {{ number_format($summary['monthly']['extra_income'], 2) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Total Income</div>
                    <div class="summary-value positive">BDT {{ number_format($summary['monthly']['total_income'], 2) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Total Expenses</div>
                    <div class="summary-value negative">BDT {{ number_format($summary['monthly']['expenses'], 2) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Net Profit</div>
                    <div class="summary-value {{ $summary['monthly']['net_profit'] >= 0 ? 'positive' : 'negative' }}">
                        BDT {{ number_format($summary['monthly']['net_profit'], 2) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Year to Date Summary -->
        <div class="summary-section">
            <div class="summary-title">Year to Date Summary (Jan - {{ $filters['monthName'] }} {{ $filters['year'] }})</div>
            <div class="summary-grid">
                <div class="summary-row">
                    <div class="summary-label">Sales Profit</div>
                    <div class="summary-value">BDT {{ number_format($summary['cumulative']['sales_profit'], 2) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Extra Income</div>
                    <div class="summary-value">BDT {{ number_format($summary['cumulative']['extra_income'], 2) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Total Income</div>
                    <div class="summary-value positive">BDT {{ number_format($summary['cumulative']['total_income'], 2) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Total Expenses</div>
                    <div class="summary-value negative">BDT {{ number_format($summary['cumulative']['expenses'], 2) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Net Profit</div>
                    <div class="summary-value {{ $summary['cumulative']['net_profit'] >= 0 ? 'positive' : 'negative' }}">
                        BDT {{ number_format($summary['cumulative']['net_profit'], 2) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Extra Income Details -->
        <div class="section-title">Extra Income Details</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Title</th>
                    <th>Account</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($extraIncome as $income)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($income->date)->format('d/m/Y') }}</td>
                    <td>{{ $income->title }}</td>
                    <td>{{ $income->bankAccount->account_name }}</td>
                    <td class="amount">BDT {{ number_format($income->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Expenses Details -->
        <div class="section-title">Expense Details</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $expense)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($expense->date)->format('d/m/Y') }}</td>
                    <td>{{ $expense->expenseCategory->name }}</td>
                    <td>{{ $expense->description }}</td>
                    <td class="amount">BDT {{ number_format($expense->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            Generated on {{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}
        </div>
    </div>
</body>
</html>
