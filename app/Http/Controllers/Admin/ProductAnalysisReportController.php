<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProductAnalysisReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Use application timezone
        $timezone = config('app.timezone', 'Asia/Dhaka');

        $startDate = $request->start_date
            ? Carbon::parse($request->start_date, $timezone)->startOfDay()
            : Carbon::now($timezone)->startOfMonth();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date, $timezone)->endOfDay()
            : Carbon::now($timezone)->endOfDay();

        // Validate date range
        if ($endDate->lt($startDate)) {
            return back()->withErrors(['end_date' => 'End date must be after start date']);
        }

        $analysis = Product::getProductAnalysis($startDate, $endDate);

        return Inertia::render('Admin/Reports/ProductAnalysis', [
            'products' => $analysis['products'],
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'totals' => $analysis['totals'],
        ]);
    }

    public function downloadPdf(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $timezone = config('app.timezone', 'Asia/Dhaka');

            $startDate = $request->start_date
                ? Carbon::parse($request->start_date, $timezone)->startOfDay()
                : Carbon::now($timezone)->startOfMonth();

            $endDate = $request->end_date
                ? Carbon::parse($request->end_date, $timezone)->endOfDay()
                : Carbon::now($timezone)->endOfDay();

            // Validate date range
            if ($endDate->lt($startDate)) {
                return response()->json([
                    'message' => 'End date must be after start date',
                ], 422);
            }

            // Get analysis data
            $analysis = Product::getProductAnalysis($startDate, $endDate);

            // Prepare data for PDF
            $data = [
                'products' => $analysis['products'],
                'totals' => $analysis['totals'],
                'start_date' => $startDate->format('d M Y'),
                'end_date' => $endDate->format('d M Y'),
                'generated_at' => Carbon::now($timezone)->format('d M Y h:i A'),
                'company_name' => config('app.name', 'Your Company Name'),
            ];

            // Generate PDF
            $pdf = PDF::loadView('pdf.product-analysis', $data);

            // Configure PDF options
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'dpi' => 150,
                'debugCss' => false,
                'margin-top' => 10,
                'margin-bottom' => 10,
                'margin-left' => 10,
                'margin-right' => 10,
            ]);

            $filename = 'product-analysis-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('PDF Generation Error: '.$e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'message' => 'Error generating PDF. Please try again later.',
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $timezone = config('app.timezone', 'Asia/Dhaka');

        $startDate = $request->start_date
            ? Carbon::parse($request->start_date, $timezone)->startOfDay()
            : Carbon::now($timezone)->startOfMonth();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date, $timezone)->endOfDay()
            : Carbon::now($timezone)->endOfDay();

        $analysis = Product::getProductAnalysis($startDate, $endDate);

        $headers = [
            'SL',
            'Product Name',
            'Model/SKU',
            'Category',
            'Unit',
            'Before Quantity',
            'Before Price',
            'Before Value',
            'Buy Quantity',
            'Buy Price',
            'Buy Total',
            'Sale Quantity',
            'Sale Price',
            'Sale Total',
            'Profit Per Unit',
            'Total Profit',
            'Available Stock',
            'Stock Value',
        ];

        $data = array_merge([$headers], $analysis['products']->map(function ($product) {
            return [
                $product['serial'],
                $product['product_name'],
                $product['product_model'],
                $product['category'],
                $product['unit'],
                number_format($product['before_quantity'], 2),
                number_format($product['before_price'], 2),
                number_format($product['before_value'], 2),
                number_format($product['buy_quantity'], 2),
                number_format($product['buy_price'], 2),
                number_format($product['total_buy_price'], 2),
                number_format($product['sale_quantity'], 2),
                number_format($product['sale_price'], 2),
                number_format($product['total_sale_price'], 2),
                number_format($product['profit_per_unit'], 2),
                number_format($product['total_profit'], 2),
                number_format($product['available_quantity'], 2),
                number_format($product['available_stock_value'], 2),
            ];
        })->toArray());

        // Add totals row
        $data[] = [
            '',
            'TOTALS',
            '',
            '',
            '',
            number_format($analysis['totals']['before_quantity'], 2),
            '',
            number_format($analysis['totals']['before_value'], 2),
            number_format($analysis['totals']['buy_quantity'], 2),
            '',
            number_format($analysis['totals']['total_buy_price'], 2),
            number_format($analysis['totals']['sale_quantity'], 2),
            '',
            number_format($analysis['totals']['total_sale_price'], 2),
            '',
            number_format($analysis['totals']['total_profit'], 2),
            number_format($analysis['totals']['available_quantity'], 2),
            number_format($analysis['totals']['available_stock_value'], 2),
        ];

        $filename = 'product-analysis-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.csv';

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
