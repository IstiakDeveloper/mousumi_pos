<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Balance Sheet Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1f2937;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* First Page Styles */
        .first-page {
            padding: 40px;
            position: relative;
        }

        .company-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .report-title {
            text-align: center;
            margin: 30px 0;
            padding: 15px 0;
            border-top: 2px solid #e5e7eb;
            border-bottom: 2px solid #e5e7eb;
        }

        .report-title h1 {
            font-size: 20px;
            margin: 0 0 10px 0;
        }

        .report-title p {
            color: #6b7280;
            font-size: 14px;
            margin: 0;
        }

        .summary-grid {
            margin-top: 30px;
        }

        .summary-section {
            margin-bottom: 30px;
        }

        .section-header {
            background: #f3f4f6;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-weight: bold;
            color: #374151;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .data-table th,
        .data-table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .data-table th {
            text-align: left;
            color: #6b7280;
            font-weight: normal;
        }

        .data-table td {
            text-align: right;
        }

        .total-row td {
            font-weight: bold;
            border-top: 2px solid #e5e7eb;
            padding-top: 12px;
        }

        .balance-check {
            margin-top: 30px;
            padding: 20px;
            background: #f3f4f6;
            border-radius: 4px;
        }

        .balance-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            color: #374151;
        }

        .balance-grid {
            display: table;
            width: 100%;
            margin-top: 10px;
        }

        .balance-row {
            display: table-row;
        }

        .balance-label {
            display: table-cell;
            padding: 5px;
            color: #6b7280;
        }

        .balance-value {
            display: table-cell;
            text-align: right;
            padding: 5px;
            font-weight: bold;
        }

        .profit { color: #059669; }
        .expense { color: #dc2626; }

        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="first-page">
        <!-- Company Header -->
        <div class="company-header">
            <div class="company-name">{{ config('app.name', 'Your Company Name') }}/ Variety Store</div>
            <div class="sub-company-name"></div>
            <div class="company-details">
                Ukilpara, Naogaon Sadar, Naogaon.<br>
                Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
            </div>
        </div>

        <!-- Report Title -->
        <div class="report-title">
            <h1>Balance Sheet</h1>
            <p>As of {{ $month }} {{ $year }}</p>
            <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
        </div>

        <!-- Summary Grid -->
        <div class="summary-grid">
            <!-- Funds & Liabilities -->
            <div class="summary-section">
                <div class="section-header">Funds & Liabilities</div>
                <table class="data-table">
                    <tr>
                        <th width="60%">Total Investment</th>
                        <td>৳{{ number_format($fundsAndLiabilities['cumulative']['total_investment'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Retained Earnings</th>
                        <td>৳{{ number_format($fundsAndLiabilities['cumulative']['retained_earnings'], 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <th>Total Funds & Liabilities</th>
                        <td>৳{{ number_format($fundsAndLiabilities['cumulative']['total'], 2) }}</td>
                    </tr>
                </table>
            </div>

            <!-- Property & Assets -->
            <div class="summary-section">
                <div class="section-header">Property & Assets</div>
                <table class="data-table">
                    <!-- Bank Accounts -->
                    @foreach($propertyAndAssets['bank_accounts'] as $account)
                    <tr>
                        <th width="60%">{{ $account['account_name'] }}</th>
                        <td>৳{{ number_format($account['balance'], 2) }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <th>Customer Due</th>
                        <td>৳{{ number_format($propertyAndAssets['customer_due'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>Stock Value</th>
                        <td>৳{{ number_format($propertyAndAssets['stock_value'], 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <th>Total Property & Assets</th>
                        <td>৳{{ number_format($propertyAndAssets['total'], 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Balance Check -->
        <div class="balance-check">
            <div class="balance-title">Balance Summary</div>
            <div class="balance-grid">
                <div class="balance-row">
                    <div class="balance-label">Total Funds & Liabilities</div>
                    <div class="balance-value">৳{{ number_format($fundsAndLiabilities['cumulative']['total'], 2) }}</div>
                </div>
                <div class="balance-row">
                    <div class="balance-label">Total Property & Assets</div>
                    <div class="balance-value">৳{{ number_format($propertyAndAssets['total'], 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Balance Sheet Report - {{ $dateRange['start'] }} to {{ $dateRange['end'] }}</p>
            <p>Page 1 of 1</p>
        </div>
    </div>
</body>
</html>
