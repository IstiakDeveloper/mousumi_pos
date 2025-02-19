<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Income & Expenditure Statement</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.6;
            color: #333;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .company-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4a4a4a;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #1a1a1a;
        }

        .sub-company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 9pt;
            color: #666;
            margin-bottom: 10px;
        }

        .report-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .report-period {
            text-align: center;
            font-size: 9pt;
            color: #7f8c8d;
            margin-bottom: 20px;
        }

        /* Side by side layout like balance sheet */
        .statement-content {
            width: 100%;
            display: table;
        }

        .section {
            display: table-cell;
            width: 50%;
            padding: 10px;
        }

        .section-header {
            background-color: #f2f2f2;
            padding: 8px 10px;
            border: 1px solid #202020;
            font-weight: bold;
            font-size: 9pt;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table th,
        table td {
            padding: 6px 10px;
            border: 1px solid #202020;
            font-size: 7pt;
        }

        table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
        }

        .green {
            color: #27ae60;
        }

        .red {
            color: #c0392b;
        }

        .net-result {
            margin-top: 20px;
            border: 1px solid #202020;
            padding: 10px;
            background-color: #f8f9fa;
            clear: both;
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
        <div class="report-title">Income & Expenditure Statement</div>
        <div class="report-period">
            Period: {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} to
            {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}
        </div>

        <!-- Statement Content in side by side layout -->
        <div class="statement-content">
            <!-- Income Section -->
            <div class="section">
                <div class="section-header">Income</div>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Month </th>
                            <th class="text-right">Cumulative</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sales Profit</td>
                            <td class="text-right {{ $income['sales_profit']['period'] >= 0 ? 'green' : 'red' }}">
                                {{ number_format($income['sales_profit']['period'], 2) }}
                            </td>
                            <td class="text-right {{ $income['sales_profit']['cumulative'] >= 0 ? 'green' : 'red' }}">
                                {{ number_format($income['sales_profit']['cumulative'], 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>Extra Income</td>
                            <td class="text-right green">{{ number_format($income['extra_income']['period'], 2) }}</td>
                            <td class="text-right green">{{ number_format($income['extra_income']['cumulative'], 2) }}</td>
                        </tr>
                        <!-- Add empty rows to match balance sheet spacing -->
                        <tr class="total-row">
                            <td>Total Income</td>
                            <td class="text-right {{ $income['total']['period'] >= 0 ? 'green' : 'red' }}">
                                {{ number_format($income['total']['period'], 2) }}
                            </td>
                            <td class="text-right {{ $income['total']['cumulative'] >= 0 ? 'green' : 'red' }}">
                                {{ number_format($income['total']['cumulative'], 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Expenditure Section -->
            <div class="section">
                <div class="section-header">Expenditure</div>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Month </th>
                            <th class="text-right">Cumulative</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Expense</td>
                            <td class="text-right red">{{ number_format($expenditure['total']['period'], 2) }}</td>
                            <td class="text-right red">{{ number_format($expenditure['total']['cumulative'], 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" style="height: 20px;"></td>
                        </tr>
                        <tr class="total-row">
                            <td>Total Expenditure</td>
                            <td class="text-right red">{{ number_format($expenditure['total']['period'], 2) }}</td>
                            <td class="text-right red">{{ number_format($expenditure['total']['cumulative'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Net Result Section -->
        <div class="net-result">
            <table>
                <tr>
                    <td width="25%"><strong>Net Result (Month)</strong></td>
                    <td width="25%" class="text-right {{ ($income['total']['period'] - $expenditure['total']['period']) >= 0 ? 'green' : 'red' }}">
                        {{ number_format($income['total']['period'] - $expenditure['total']['period'], 2) }}
                    </td>
                    <td width="25%"><strong>Net Result (Cumulative)</strong></td>
                    <td width="25%" class="text-right {{ ($income['total']['cumulative'] - $expenditure['total']['cumulative']) >= 0 ? 'green' : 'red' }}">
                        {{ number_format($income['total']['cumulative'] - $expenditure['total']['cumulative'], 2) }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer with Page Number -->
        <div class="footer">
            Page <span class="page-number"></span>
            <br>
            Generated on: {{ now()->format('d M Y H:i:s') }}
        </div>
    </div>
</body>
</html>
