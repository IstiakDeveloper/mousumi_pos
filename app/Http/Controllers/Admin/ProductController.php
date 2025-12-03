<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
            ->appends($request->query());

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => Category::select('id', 'name')->orderBy('name')->get(),
            'brands' => Brand::select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'category_id', 'brand_id', 'status']),
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
            'units' => Unit::active()->get(['id', 'name', 'short_name']),
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
            'images' => 'nullable|array',
            'images.*' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    // Manual file validation
                    if (! is_file($value)) {
                        $fail('Invalid file upload.');
                    }

                    // Check file extension
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (! in_array($extension, $allowedExtensions)) {
                        $fail('Invalid image type. Allowed types: '.implode(', ', $allowedExtensions));
                    }

                    // Check file size (2MB = 2048 KB)
                    $maxSize = 2048;
                    if ($value->getSize() > $maxSize * 1024) {
                        $fail("Image cannot be larger than {$maxSize}KB.");
                    }
                },
            ],
            'primary_image_index' => 'required|integer|min:0',
        ]);

        $validated['slug'] = Str::slug($request->name);

        $product = Product::create($validated);

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $primaryIndex = (int) $request->primary_image_index;

            foreach ($images as $index => $image) {
                // Generate a unique filename
                $filename = uniqid().'.'.$image->getClientOriginalExtension();

                // Ensure products directory exists
                $uploadDir = public_path('storage/products');
                if (! File::exists($uploadDir)) {
                    File::makeDirectory($uploadDir, 0755, true);
                }

                // Move the file manually to the public storage
                $path = $image->move($uploadDir, $filename);

                // Create the database entry with the relative path
                $product->images()->create([
                    'image' => 'products/'.$filename,
                    'is_primary' => $index === $primaryIndex,
                    'sort_order' => $index,
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
            'product' => $product,
        ]);
    }

    public function edit(Product $product)
    {
        $product->load('images');

        return Inertia::render('Admin/Products/Edit', [
            'product' => $product,
            'categories' => Category::active()->get(['id', 'name']),
            'brands' => Brand::active()->get(['id', 'name']),
            'units' => Unit::active()->get(['id', 'name', 'short_name']),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,'.$product->id,
            'barcode' => 'nullable|string|unique:products,barcode,'.$product->id,
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'required|exists:units,id',
            'selling_price' => 'required|numeric|min:0',
            'alert_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'specifications' => 'nullable|array',
            'status' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    // Manual file validation
                    if (! is_file($value)) {
                        $fail('Invalid file upload.');
                    }

                    // Check file extension
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (! in_array($extension, $allowedExtensions)) {
                        $fail('Invalid image type. Allowed types: '.implode(', ', $allowedExtensions));
                    }

                    // Check file size (2MB = 2048 KB)
                    $maxSize = 2048;
                    if ($value->getSize() > $maxSize * 1024) {
                        $fail("Image cannot be larger than {$maxSize}KB.");
                    }
                },
            ],
            'primary_image_index' => 'nullable|integer',
            'primary_image_id' => 'nullable|exists:product_images,id',
        ]);

        // Add slug
        $validated['slug'] = Str::slug($request->name);

        // Perform the update with the validated data
        $product->update($validated);

        // Handle primary image update if a specific existing image was selected
        if ($request->has('primary_image_id') && $request->primary_image_id) {
            // Reset all images to non-primary
            $product->images()->update(['is_primary' => false]);

            // Set the selected image as primary
            $product->images()->where('id', $request->primary_image_id)->update(['is_primary' => true]);
        }

        // Handle new uploaded images
        if ($request->hasFile('images')) {
            $newImageCount = count($request->file('images'));
            $primaryIndex = $request->input('primary_image_index');

            // If no primary image exists and primary_image_index refers to a new image
            $setPrimaryForNew = ! $request->has('primary_image_id') &&
                is_numeric($primaryIndex) &&
                $primaryIndex < $newImageCount;

            foreach ($request->file('images') as $index => $image) {
                // Generate a unique filename
                $filename = uniqid().'.'.$image->getClientOriginalExtension();

                // Move the file manually to the public storage
                $path = $image->move(public_path('storage/products'), $filename);

                // Create the database entry with the relative path
                $product->images()->create([
                    'image' => 'products/'.$filename,
                    'is_primary' => $setPrimaryForNew && $index == $primaryIndex,
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        try {
            DB::beginTransaction();

            // Get all stock purchases for this product
            $stockPurchases = ProductStock::where('product_id', $product->id)
                ->where('type', 'purchase')
                ->get();

            // Group total amount by bank account
            $bankRefunds = [];

            foreach ($stockPurchases as $stock) {
                // Find the original bank transaction
                $bankTransaction = BankTransaction::where([
                    'transaction_type' => 'out',
                    'amount' => $stock->total_cost,
                ])
                    ->where('created_at', '>=', $stock->created_at->startOfDay())
                    ->where('created_at', '<=', $stock->created_at->endOfDay())
                    ->first();

                if ($bankTransaction) {
                    $bankAccountId = $bankTransaction->bank_account_id;
                    if (! isset($bankRefunds[$bankAccountId])) {
                        $bankRefunds[$bankAccountId] = 0;
                    }
                    $bankRefunds[$bankAccountId] = bcadd($bankRefunds[$bankAccountId], $stock->total_cost, 4);
                }
            }

            // Process refunds for each bank account
            foreach ($bankRefunds as $bankAccountId => $totalRefund) {
                $bankAccount = BankAccount::find($bankAccountId);
                if ($bankAccount) {
                    // Create single refund transaction
                    BankTransaction::create([
                        'bank_account_id' => $bankAccountId,
                        'transaction_type' => 'in',
                        'amount' => $totalRefund,
                        'description' => "Total refund for deleted product ID: {$product->id}",
                        'date' => now(),
                        'created_by' => Auth::id(),
                    ]);

                    // Update bank balance
                    $newBalance = bcadd($bankAccount->current_balance, $totalRefund, 4);
                    $bankAccount->update(['current_balance' => $newBalance]);
                }
            }

            // Delete stock movements
            StockMovement::where('product_id', $product->id)->delete();

            // Delete stocks
            ProductStock::where('product_id', $product->id)->delete();

            // Delete product images
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image);
                $image->delete();
            }

            // Finally delete the product
            $product->delete();

            DB::commit();

            return redirect()->back()
                ->with('success', 'Product and all related transactions deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Product deletion error', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Error deleting product: '.$e->getMessage());
        }
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
            'products' => $products,
        ]);
    }
}
