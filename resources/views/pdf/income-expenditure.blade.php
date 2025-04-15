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
            font-size: 8pt;
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
            font-size: 8pt;
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
            <div class="company-name">{{ config('app.name', 'Your Company Name') }}/ Variety Store</div>
            <div class="sub-company-name"></div>
            <div class="company-details">
                Ukilpara, Naogaon Sadar, Naogaon.<br>
                Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
            </div>
        </div>

        <!-- Report Title and Period -->
        <div class="report-title">Income & Expenditure Statement</div>
        <div class="report-period">
            @if (isset($filters['month_name']))
                Period: {{ $filters['month_name'] }} {{ $filters['year'] }}
            @else
                Period: {{ \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') }} to
                {{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}
            @endif
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
                            <th class="text-right">Month</th>
                            <th class="text-right">Cumulative</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Sales Profit Row -->
                        <tr>
                            <td class="font-bold">Sales Profit</td>
                            <td class="text-right {{ $income['sales_profit']['period'] >= 0 ? 'green' : 'red' }}">
                                {{ number_format($income['sales_profit']['period'], 2) }}
                            </td>
                            <td class="text-right {{ $income['sales_profit']['cumulative'] >= 0 ? 'green' : 'red' }}">
                                {{ number_format($income['sales_profit']['cumulative'], 2) }}
                            </td>
                        </tr>

                        <!-- Extra Income Categories -->
                        @foreach ($income['extra_income']['categories'] as $category)
                            <tr>
                                <td>{{ $category['name'] }} <span class="text-muted">(Others Income)</span></td>
                                <td class="text-right green">{{ number_format($category['period'], 2) }}</td>
                                <td class="text-right green">{{ number_format($category['cumulative'], 2) }}</td>
                            </tr>
                        @endforeach

                        <!-- Total Income Row -->
                        <tr class="total-row">
                            <td><strong>Total Income</strong></td>
                            <td style="font-size: 9px;" class="text-right green">
                                {{ number_format($income['total']['period'], 2) }}</td>
                            <td style="font-size: 9px;" class="text-right green">
                                {{ number_format($income['total']['cumulative'], 2) }}</td>
                        </tr>

                        <!-- Surplus Row -->
                        <tr class="total-row">
                            <td><strong>Surplus</strong></td>
                            <td class="text-right {{ $income['total']['period'] - $expenditure['total']['period'] >= 0 ? 'green' : 'red' }}"
                                style="font-size: 9px;">
                                {{ number_format($income['total']['period'] - $expenditure['total']['period'], 2) }}
                            </td>
                            <td class="text-right {{ $income['total']['cumulative'] - $expenditure['total']['cumulative'] >= 0 ? 'green' : 'red' }}"
                                style="font-size: 9px;">
                                {{ number_format($income['total']['cumulative'] - $expenditure['total']['cumulative'], 2) }}
                            </td>
                        </tr>

                        <!-- Grand Total Row -->
                        <tr class="total-row">
                            <td><strong>Grand Total</strong></td>
                            <td style="font-size: 9px;" class="text-right red">
                                {{ number_format($expenditure['total']['period'], 2) }}</td>
                            <td style="font-size: 9px;" class="text-right red">
                                {{ number_format($expenditure['total']['cumulative'], 2) }}</td>
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
                            <th class="text-right">Month</th>
                            <th class="text-right">Cumulative</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Expenditure Categories -->
                        @foreach ($expenditure['categories'] as $category)
                            <tr>
                                <td>{{ $category['name'] }}</td>
                                <td class="text-right red">{{ number_format($category['period'], 2) }}</td>
                                <td class="text-right red">{{ number_format($category['cumulative'], 2) }}</td>
                            </tr>
                        @endforeach

                        <!-- Total Expenditure Row -->
                        <tr class="total-row">
                            <td><strong>Total Expenditure</strong></td>
                            <td class="text-right red">{{ number_format($expenditure['total']['period'], 2) }}</td>
                            <td class="text-right red">{{ number_format($expenditure['total']['cumulative'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
