<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Receipt & Payment Statement</title>
    <style>
        @page {
            margin: 10mm 15mm;
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
            font-size: 14pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .report-period {
            text-align: center;
            font-size: 10pt;
            color: #7f8c8d;
            margin-bottom: 20px;
        }

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
            font-size: 11pt;
            color: #2c3e50;
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
        <div class="report-title">Receipt & Payment Statement</div>
        <div class="report-period">
            Period: {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} to
            {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}
        </div>

        <!-- Statement Content -->
        <div class="statement-content">
            <!-- Receipt Section -->
            <div class="section">
                <div class="section-header">Receipt</div>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Amount ({{ config('app.currency', 'BDT') }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Opening Cash On the Bank</td>
                            <td class="text-right green">
                                {{ number_format(floatval($receipt['opening_cash_on_bank']), 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>Sale Collection</td>
                            <td class="text-right green">
                                {{ number_format(floatval($receipt['sale_collection']), 2) }}
                            </td>
                        </tr>
                        <!-- Extra Income Header Row -->
                        <tr>
                            <td colspan="2" style="padding: 8px; font-weight: 600; background-color: #f3f4f6;">
                                Others Income
                            </td>
                        </tr>
                        <!-- Individual Extra Income Category Rows -->
                        @if (isset($receipt['extra_income']['categories']) && count($receipt['extra_income']['categories']) > 0)
                            @foreach ($receipt['extra_income']['categories'] as $category)
                                <tr>
                                    <td style="padding: 8px; padding-left: 24px; font-size: 0.7em;">
                                        {{ $category['category'] }}
                                    </td>
                                    <td style="padding: 8px; text-align: right; color: #059669;">
                                        {{ number_format(floatval($category['amount']), 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td
                                    style="padding: 8px; padding-left: 24px; font-size: 0.7em; font-style: italic; color: #6b7280;">
                                    No extra income this period
                                </td>
                                <td style="padding: 8px; text-align: right; color: #6b7280;">
                                    0.00
                                </td>
                            </tr>
                        @endif
                        <!-- Extra Income Total Row -->
                        <tr style="background-color: #f3f4f6;">
                            <td style="padding: 8px; font-weight: 600;">
                                Total Others Income
                            </td>
                            <td style="padding: 8px; text-align: right; color: #059669; font-weight: 700;">
                                {{ number_format(floatval($receipt['extra_income']['total']), 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>Fund Receive</td>
                            <td class="text-right green">
                                {{ number_format(floatval($receipt['fund_receive']), 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="height: 25px;"></td>
                        </tr>
                        <tr class="total-row">
                            <td>Total Receipt</td>
                            <td class="text-right green">
                                {{ number_format(floatval($receipt['total']), 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Payment Section -->
            <div class="section">
                <div class="section-header">Payment</div>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Amount ({{ config('app.currency', 'BDT') }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Purchase</td>
                            <td class="text-right red">
                                {{ number_format(floatval($payment['purchase']), 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>Fund Refund</td>
                            <td class="text-right red">
                                {{ number_format(floatval($payment['fund_refund']), 2) }}
                            </td>
                        </tr>
                        <!-- Expenses Header Row -->
                        <tr>
                            <td colspan="2" style="padding: 8px; font-weight: 600; background-color: #f3f4f6;">
                                Expenses
                            </td>
                        </tr>
                        <!-- Individual Expense Category Rows -->
                        @if (isset($payment['expenses']['categories']) && count($payment['expenses']['categories']) > 0)
                            @foreach ($payment['expenses']['categories'] as $category)
                                <tr>
                                    <td style="padding: 8px; padding-left: 24px; font-size: 0.7em;">
                                        {{ $category['category'] }}
                                    </td>
                                    <td style="padding: 8px; text-align: right; color: #dc2626;">
                                        {{ number_format(floatval($category['amount']), 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td
                                    style="padding: 8px; padding-left: 24px; font-size: 0.7em; font-style: italic; color: #6b7280;">
                                    No expenses this period
                                </td>
                                <td style="padding: 8px; text-align: right; color: #6b7280;">
                                    0.00
                                </td>
                            </tr>
                        @endif
                        <!-- Expenses Total Row -->
                        <tr style="background-color: #f3f4f6;">
                            <td style="padding: 8px; font-weight: 600;">
                                Total Expenses
                            </td>
                            <td style="padding: 8px; text-align: right; color: #dc2626; font-weight: 700;">
                                {{ number_format(floatval($payment['expenses']['total']), 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>Closing Cash at Bank</td>
                            <td class="text-right red">
                                {{ number_format(floatval($payment['closing_cash_at_bank']), 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="height: 25px;"></td>
                        </tr>
                        <tr class="total-row">
                            <td>Total Payment</td>
                            <td class="text-right red">
                                {{ number_format(floatval($payment['total']), 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Page <span class="page-number"></span>
            <br>
            Generated on: {{ now()->format('d M Y H:i:s') }}
        </div>
    </div>
</body>

</html>
