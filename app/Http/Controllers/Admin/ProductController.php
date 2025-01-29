<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductImage;
use App\Models\ProductStock;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $products = Product::query()
            ->with(['category', 'brand', 'unit', 'images'])
            ->select('products.*')
            ->selectRaw('(
                SELECT ps.available_quantity
                FROM product_stocks ps
                WHERE ps.product_id = products.id
                ORDER BY ps.id DESC
                LIMIT 1
            ) as available_quantity')
            ->selectRaw('(
                SELECT COALESCE(SUM(
                    CASE WHEN ps.quantity > 0
                    THEN ps.quantity
                    ELSE 0
                    END
                ), 0)
                FROM product_stocks ps
                WHERE ps.product_id = products.id
                AND ps.type = "purchase"
            ) as total_purchased')
            ->selectRaw('(
                SELECT COALESCE(SUM(
                    CASE WHEN ps.quantity > 0
                    THEN (ps.quantity * ps.unit_cost)
                    ELSE 0
                    END
                ), 0)
                FROM product_stocks ps
                WHERE ps.product_id = products.id
                AND ps.type = "purchase"
            ) as total_purchase_value')
            ->selectRaw('CASE
                WHEN (
                    SELECT available_quantity
                    FROM product_stocks
                    WHERE product_id = products.id
                    ORDER BY id DESC
                    LIMIT 1
                ) <= alert_quantity THEN "low"
                WHEN (
                    SELECT available_quantity
                    FROM product_stocks
                    WHERE product_id = products.id
                    ORDER BY id DESC
                    LIMIT 1
                ) <= 0 THEN "out"
                ELSE "in"
            END as stock_status')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            })
            ->when($request->category_id, function ($query, $category_id) {
                $query->where('category_id', $category_id);
            })
            ->when($request->brand_id, function ($query, $brand_id) {
                $query->where('brand_id', $brand_id);
            })
            ->when($request->status !== null, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate(10)
            ->through(function ($product) {
                // Calculate weighted average cost
                $averageUnitCost = $product->total_purchased > 0
                    ? bcdiv($product->total_purchase_value, $product->total_purchased, 6)
                    : $product->cost_price;

                // Calculate current stock value
                $currentStockValue = bcmul($product->available_quantity ?? 0, $averageUnitCost, 6);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name
                    ] : null,
                    'brand' => $product->brand ? [
                        'id' => $product->brand->id,
                        'name' => $product->brand->name
                    ] : null,
                    'unit' => $product->unit ? [
                        'id' => $product->unit->id,
                        'name' => $product->unit->name
                    ] : null,
                    'cost_price' => round($averageUnitCost, 2),
                    'selling_price' => $product->selling_price,
                    'alert_quantity' => $product->alert_quantity,
                    'available_quantity' => $product->available_quantity ?? 0,
                    'total_purchased' => $product->total_purchased,
                    'current_stock_value' => round($currentStockValue, 2),
                    'status' => $product->status,
                    'stock_status' => $product->stock_status,
                    'images' => $product->images->map(function ($image) {
                        return [
                            'image' => $image->image,
                            'is_primary' => $image->is_primary
                        ];
                    }),
                    'created_at' => $product->created_at
                ];
            })
            ->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'category_id', 'brand_id', 'status'])
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('query');

        return Product::where('status', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'sku', 'selling_price', 'stock')
            ->get();
    }

    public function create()
    {
        return Inertia::render('Admin/Products/Create', [
            'categories' => Category::active()->get(['id', 'name']),
            'brands' => Brand::active()->get(['id', 'name']),
            'units' => Unit::active()->get(['id', 'name', 'short_name'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products',
            'barcode' => 'nullable|string|unique:products',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'required|exists:units,id',
            'selling_price' => 'required|numeric|min:0',
            'alert_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'specifications' => 'nullable|array',
            'status' => 'boolean',
            'images.*' => 'image|max:2048',
            'primary_image_index' => 'required|integer|min:0'
        ]);

        $validated['slug'] = Str::slug($request->name);

        $product = Product::create($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $product->images()->create([
                    'image' => $image->store('products', 'public'),
                    'is_primary' => $index === (int) $request->primary_image_index,
                    'sort_order' => $index
                ]);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'brand', 'unit', 'images']);

        return Inertia::render('Admin/Products/Show', [
            'product' => $product
        ]);
    }

    public function edit(Product $product)
    {
        $product->load('images');

        return Inertia::render('Admin/Products/Edit', [
            'product' => $product,
            'categories' => Category::active()->get(['id', 'name']),
            'brands' => Brand::active()->get(['id', 'name']),
            'units' => Unit::active()->get(['id', 'name', 'short_name'])
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'required|exists:units,id',
            'selling_price' => 'required|numeric|min:0|gte:cost_price',
            'alert_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'specifications' => 'nullable|array',
            'status' => 'boolean',
            'images.*' => 'image|max:2048'
        ]);

        $validated['slug'] = Str::slug($request->name);

        $product->update($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $product->images()->create([
                    'image' => $image->store('products', 'public')
                ]);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        // Delete product images
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image);
            $image->delete();
        }

        $product->delete();

        return redirect()->back()
            ->with('success', 'Product deleted successfully.');
    }

    public function deleteImage(ProductImage $image)
    {
        Storage::disk('public')->delete($image->image);
        $image->delete();

        return response()->json(['success' => true]);
    }
    public function downloadPdf()
    {
        $products = Product::with(['category', 'brand', 'unit', 'stocks'])
            ->get()
            ->map(function ($product) {
                // Calculate total stock
                $totalStock = $product->stocks->sum('quantity');

                // Calculate average cost price from stocks
                $averageCost = $product->stocks->count() > 0
                    ? $product->stocks->sum('total_cost') / $product->stocks->sum('quantity')
                    : $product->cost_price ?? 0;

                return [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category->name,
                    'brand' => $product->brand ? $product->brand->name : '-',
                    'unit' => $product->unit->short_name,
                    'cost_price' => $product->cost_price,
                    'selling_price' => number_format($product->selling_price, 2),
                    'stock' => $totalStock,
                    'alert_quantity' => $product->alert_quantity,
                ];
            });

        return Inertia::render('Admin/Products/Report', [
            'products' => $products
        ]);
    }
}
