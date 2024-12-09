<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductStockController extends Controller
{
    public function index()
    {
        $stocks = ProductStock::with('product')->paginate(10);
        return Inertia::render('Admin/ProductStocks/Index', [
            'stocks' => $stocks,
        ]);
    }


    public function create()
    {
        return Inertia::render('Admin/ProductStocks/Create', [
            'products' => Product::all(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        $stock = ProductStock::updateOrCreate(
            ['product_id' => $validated['product_id']],
            ['quantity' => $validated['quantity']]
        );
        dd($stock);

        return redirect()->route('admin.product-stocks.index')
            ->with('success', 'Stock updated successfully.');
    }
}
