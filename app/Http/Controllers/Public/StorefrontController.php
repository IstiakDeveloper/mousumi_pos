<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PendingSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StorefrontController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $products */
        $products = Product::query()
            ->with(['primaryImage'])
            ->where('status', true)
            ->select('products.*')
            ->selectRaw('(
                SELECT ps.available_quantity
                FROM product_stocks ps
                WHERE ps.product_id = products.id
                ORDER BY ps.id DESC
                LIMIT 1
            ) as available_quantity')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->input('q');
                $q->where(function ($qq) use ($term) {
                    $qq->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%")
                        ->orWhere('barcode', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->where('category_id', (int) $request->input('category_id'));
            })
            ->latest()
            ->paginate(24);

        $products->getCollection()->transform(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'selling_price' => (float) $p->selling_price,
            'image' => $p->primaryImage?->image,
            'available_quantity' => (int) round((float) ($p->available_quantity ?? 0)),
        ]);

        [$cartItems, $cartSubtotal] = $this->buildCartView($request);

        return Inertia::render('Public/Home', [
            'products' => $products,
            'categories' => $categories,
            'query' => $request->only(['q', 'category_id']),
            'cartCount' => $this->cartCount($request),
            'cart' => [
                'items' => $cartItems,
                'subtotal' => $cartSubtotal,
            ],
        ]);
    }

    public function cart(Request $request)
    {
        [$items, $subtotal] = $this->buildCartView($request);

        return Inertia::render('Public/Cart', [
            'items' => $items,
            'subtotal' => $subtotal,
            'cartCount' => $this->cartCount($request),
        ]);
    }

    public function addToCart(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1|max:999',
        ]);

        $qty = (int) ($data['quantity'] ?? 1);
        $available = $this->getAvailableStock((int) $data['product_id']);
        if ($available <= 0) {
            return back()->with('error', 'Out of stock.');
        }

        $cart = $this->getCart($request);
        $id = (string) $data['product_id'];
        $desired = min(999, ((int) ($cart[$id] ?? 0)) + $qty);
        if ($desired > $available) {
            return back()->with('error', "Only {$available} available in stock.");
        }
        $cart[$id] = $desired;
        $request->session()->put('public_cart', $cart);

        return back()->with('success', 'Added to cart.');
    }

    public function updateCart(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
        ]);

        $cart = [];
        foreach ($data['items'] as $item) {
            $available = $this->getAvailableStock((int) $item['product_id']);
            if ($available <= 0) {
                return back()->with('error', 'Some items are out of stock.');
            }
            if ((int) $item['quantity'] > $available) {
                return back()->with('error', "Quantity exceeds stock for a product (available: {$available}).");
            }
            $cart[(string) $item['product_id']] = (int) $item['quantity'];
        }
        $request->session()->put('public_cart', $cart);

        return back()->with('success', 'Cart updated.');
    }

    public function removeFromCart(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
        ]);

        $cart = $this->getCart($request);
        unset($cart[(string) $data['product_id']]);
        $request->session()->put('public_cart', $cart);

        return back()->with('success', 'Removed from cart.');
    }

    public function checkout(Request $request)
    {
        $cart = $this->getCart($request);
        if (count($cart) === 0) {
            return to_route('cart')->with('error', 'Your cart is empty.');
        }

        $customers = Customer::active()
            ->select('id', 'name', 'phone', 'balance', 'credit_limit', 'branch_code', 'branch_name')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'balance' => (string) $c->balance,
                'credit_limit' => (string) $c->credit_limit,
                'branch_code' => $c->branch_code,
                'branch_name' => $c->branch_name,
            ]);

        return Inertia::render('Public/Checkout', [
            'customers' => $customers,
            'cartCount' => $this->cartCount($request),
        ]);
    }

    public function submitOrder(Request $request)
    {
        $cart = $this->getCart($request);
        if (count($cart) === 0) {
            return to_route('cart')->with('error', 'Your cart is empty.');
        }

        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'note' => 'nullable|string|max:2000',
        ]);

        $productIds = array_keys($cart);
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->select('id', 'selling_price')
            ->get()
            ->keyBy('id');

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $itemsPayload = [];
            foreach ($cart as $productId => $qty) {
                $p = $products->get((int) $productId);
                if (! $p) {
                    continue;
                }
                $unitPrice = (float) $p->selling_price;
                $lineSubtotal = $unitPrice * (int) $qty;
                $subtotal += $lineSubtotal;
                $itemsPayload[] = [
                    'product_id' => (int) $productId,
                    'quantity' => (int) $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ];
            }

            if (count($itemsPayload) === 0) {
                throw new \Exception('No valid items found in cart.');
            }

            $discount = 0;
            $total = $subtotal - $discount;

            $orderNo = 'PUB-'.date('Ymd').'-'.Str::upper(Str::random(6));

            $pending = PendingSale::create([
                'public_order_no' => $orderNo,
                'customer_id' => $data['customer_id'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'status' => 'pending',
                'note' => $data['note'] ?? null,
            ]);

            foreach ($itemsPayload as $i) {
                $pending->items()->create($i);
            }

            DB::commit();

            $request->session()->forget('public_cart');

            return to_route('home')->with('success', "Order submitted. Order no: {$orderNo}. Waiting for approval.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function getCart(Request $request): array
    {
        $cart = $request->session()->get('public_cart', []);
        return is_array($cart) ? $cart : [];
    }

    /**
     * @return array{0: array<int, array{product: array{id:int,name:string,sku:?string,selling_price:float,image:?string}, quantity:int, line_total:float}>, 1: float}
     */
    private function buildCartView(Request $request): array
    {
        $cart = $this->getCart($request);
        $productIds = array_keys($cart);

        if (count($productIds) === 0) {
            return [[], 0.0];
        }

        $products = Product::query()
            ->with(['primaryImage'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $items = [];
        $subtotal = 0.0;
        foreach ($cart as $productId => $qty) {
            $p = $products->get((int) $productId);
            if (! $p) {
                continue;
            }
            $available = $this->getAvailableStock((int) $productId);
            $line = (float) $p->selling_price * (int) $qty;
            $subtotal += $line;
            $items[] = [
                'product' => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'selling_price' => (float) $p->selling_price,
                    'image' => $p->primaryImage?->image,
                    'available_quantity' => (int) $available,
                ],
                'quantity' => (int) $qty,
                'line_total' => $line,
            ];
        }

        return [$items, $subtotal];
    }

    private function getAvailableStock(int $productId): int
    {
        $value = DB::table('product_stocks')
            ->where('product_id', $productId)
            ->orderByDesc('id')
            ->value('available_quantity');

        return (int) round((float) ($value ?? 0));
    }

    private function cartCount(Request $request): int
    {
        $cart = $this->getCart($request);
        return array_sum(array_map('intval', $cart));
    }
}

