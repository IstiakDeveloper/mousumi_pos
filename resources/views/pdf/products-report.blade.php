<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bank Report</title>
    <style>
  body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            margin: 15px 15px;
            color: #333;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px; /* Smaller font for detailed transactions */
        }

        .month-header {
            background: #f4f4f4;
            padding: 6px;
            margin-top: 10px;
            font-weight: bold;
            border: 1px solid #ddd;
        }

        .monthly-summary {
            background: #f8f8f8;
            font-weight: bold;
        }

        .transaction-row:nth-child(even) {
            background-color: #f9f9f9;
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
    <body>
        <div class="header">
            <h2>Products Report</h2>
        </div>

</body>
</html>
