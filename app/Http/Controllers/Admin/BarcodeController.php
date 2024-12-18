<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\Storage;

class BarcodeController extends Controller
{
    public function generate(Product $product)
    {
        $generator = new BarcodeGeneratorPNG();

        if (!$product->barcode) {
            $product->barcode = $this->generateUniqueBarcode();
            $product->save();
        }

        $barcode = $generator->getBarcode($product->barcode, $generator::TYPE_CODE_128);

        // Ensure the directory exists
        Storage::disk('public')->makeDirectory('barcodes');

        // Store barcode image
        $path = 'barcodes/' . $product->barcode . '.png';
        Storage::disk('public')->put($path, $barcode);

        // Verify file exists and is readable
        if (!Storage::disk('public')->exists($path)) {
            return response()->json([
                'error' => 'Failed to generate barcode image'
            ], 500);
        }

        return response()->json([
            'barcode' => $product->barcode,
            'image_url' => Storage::disk('public')->url($path),
            'product' => $product->only(['id', 'name', 'sku', 'selling_price'])
        ]);
    }


    public function print(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.copies' => 'required|integer|min:1|max:100',
            'paperSize' => 'required|in:A4,Letter,80mm'
        ]);

        $products = Product::whereIn('id', collect($request->products)->pluck('id'))
            ->get()
            ->map(function ($product) use ($request) {
                // Find the copies for this product
                $productRequest = collect($request->products)
                    ->firstWhere('id', $product->id);

                // Generate barcode if it doesn't exist
                if (!$product->barcode) {
                    $this->generate($product);
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'sku' => $product->sku,
                    'price' => $product->selling_price,
                    'copies' => $productRequest['copies'],
                    'image_url' => Storage::disk('public')->url('barcodes/' . $product->barcode . '.png')
                ];
            });

        return Inertia::render('Admin/Products/BarcodePrint', [
            'products' => $products,
            'paperSize' => $request->paperSize
        ]);
    }

    private function generateUniqueBarcode()
    {
        do {
            // Generate a 12-digit EAN-13 compatible number
            $barcode = '200' . str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            // Add check digit
            $barcode .= $this->calculateEAN13CheckDigit($barcode);
        } while (Product::where('barcode', $barcode)->exists());

        return $barcode;
    }

    private function calculateEAN13CheckDigit($barcode)
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $barcode[$i] * ($i % 2 ? 3 : 1);
        }
        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit;
    }
}
