<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\PendingSale;
use App\Models\PendingSaleItem;
use App\Models\Product;
use App\Services\SaleCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class PendingSaleController extends Controller
{
    public function index(Request $request)
    {
        /** @var \Illuminate\Pagination\LengthAwarePaginator $pendingSales */
        $status = $request->input('status', 'pending');
        if (! in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $status = 'pending';
        }

        $pendingSales = PendingSale::query()
            ->with(['customer:id,name,phone', 'items.product:id,name,sku'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->input('q');
                $q->where('public_order_no', 'like', "%{$term}%")
                    ->orWhereHas('customer', function ($cq) use ($term) {
                        $cq->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%");
                    });
            })
            ->where('status', $status)
            ->latest()
            ->paginate(20)
            ->appends($request->only(['status', 'q']));

        /** @var \Illuminate\Pagination\LengthAwarePaginator $pendingSales */
        $pendingSales->getCollection()->transform(fn ($o) => [
            'id' => $o->id,
            'public_order_no' => $o->public_order_no,
            'date' => $o->created_at->format('d M, Y h:i A'),
            'status' => $o->status,
            'sale_id' => $o->sale_id,
            'approved_at' => $o->approved_at?->format('d M, Y h:i A'),
            'rejected_at' => $o->rejected_at?->format('d M, Y h:i A'),
            'customer' => $o->customer ? [
                'id' => $o->customer->id,
                'name' => $o->customer->name,
                'phone' => $o->customer->phone,
            ] : null,
            'total' => (string) $o->total,
            'note' => $o->note,
            'items' => $o->items->map(fn ($i) => [
                'id' => $i->id,
                'product' => [
                    'id' => $i->product_id,
                    'name' => $i->product?->name,
                    'sku' => $i->product?->sku,
                ],
                'quantity' => (int) $i->quantity,
                'unit_price' => (string) $i->unit_price,
                'subtotal' => (string) $i->subtotal,
            ])->values(),
        ]);

        return Inertia::render('Admin/PendingSales/Index', [
            'pendingSales' => $pendingSales,
            'filters' => [
                'status' => $status,
                'q' => (string) $request->input('q', ''),
            ],
        ]);
    }

    public function approve(Request $request, PendingSale $pendingSale, SaleCreator $creator)
    {
        if ($pendingSale->status !== 'pending') {
            return back()->withErrors(['error' => 'Order is not pending.']);
        }

        DB::beginTransaction();
        try {
            $pendingSale->load('items');

            $items = $pendingSale->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'quantity' => $i->quantity,
                'unit_price' => $i->unit_price,
            ])->values()->all();

            $orderDate = $pendingSale->created_at?->toDateString();
            $bankAccountId = (int) (config('app.public_order_bank_account_id') ?: env('PUBLIC_ORDER_BANK_ACCOUNT_ID'));
            if (! $bankAccountId) {
                $bankAccountId = (int) BankAccount::active()->value('id');
            }
            if (! $bankAccountId) {
                throw new \Exception('No active bank account found for automatic payment.');
            }

            // Approved public orders are fully paid (no due)
            $sale = $creator->create(
                items: $items,
                subtotal: (float) $pendingSale->subtotal,
                discount: (float) $pendingSale->discount,
                total: (float) $pendingSale->total,
                paid: (float) $pendingSale->total,
                bankAccountId: $bankAccountId,
                customerId: $pendingSale->customer_id,
                note: $pendingSale->note ? ("Public order {$pendingSale->public_order_no}: ".$pendingSale->note) : ("Public order {$pendingSale->public_order_no}"),
                createdBy: Auth::id(),
                date: $orderDate
            );

            $pendingSale->forceFill([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'sale_id' => $sale->id,
            ])->save();

            DB::commit();

            return back()->with('success', 'Approved and converted to Sale.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function addItem(Request $request, PendingSale $pendingSale)
    {
        if ($pendingSale->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending orders can be edited.']);
        }

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:999',
        ]);

        $available = $this->getAvailableStock((int) $data['product_id']);
        if ($available <= 0) {
            return back()->withErrors(['error' => 'Out of stock.']);
        }
        if ((int) $data['quantity'] > $available) {
            return back()->withErrors(['error' => "Only {$available} available in stock."]);
        }

        DB::beginTransaction();
        try {
            $product = Product::select('id', 'selling_price')->findOrFail($data['product_id']);
            $unitPrice = (float) $product->selling_price;

            $existing = PendingSaleItem::where('pending_sale_id', $pendingSale->id)
                ->where('product_id', $product->id)
                ->first();

            if ($existing) {
                $newQty = (int) $existing->quantity + (int) $data['quantity'];
                if ($newQty > $available) {
                    throw new \Exception("Only {$available} available in stock.");
                }
                $existing->forceFill([
                    'quantity' => $newQty,
                    'subtotal' => $newQty * (float) $existing->unit_price,
                ])->save();
            } else {
                PendingSaleItem::create([
                    'pending_sale_id' => $pendingSale->id,
                    'product_id' => $product->id,
                    'quantity' => (int) $data['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => ((int) $data['quantity']) * $unitPrice,
                ]);
            }

            $this->recalcPendingSale($pendingSale);

            DB::commit();
            return back()->with('success', 'Item added.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function updateItem(Request $request, PendingSale $pendingSale, PendingSaleItem $item)
    {
        if ($pendingSale->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending orders can be edited.']);
        }
        if ((int) $item->pending_sale_id !== (int) $pendingSale->id) {
            return back()->withErrors(['error' => 'Invalid order item.']);
        }

        $data = $request->validate([
            'quantity' => 'required|integer|min:1|max:999',
        ]);

        $available = $this->getAvailableStock((int) $item->product_id);
        if ($available <= 0) {
            return back()->withErrors(['error' => 'Out of stock.']);
        }
        if ((int) $data['quantity'] > $available) {
            return back()->withErrors(['error' => "Only {$available} available in stock."]);
        }

        DB::beginTransaction();
        try {
            $qty = (int) $data['quantity'];
            $item->forceFill([
                'quantity' => $qty,
                'subtotal' => $qty * (float) $item->unit_price,
            ])->save();

            $this->recalcPendingSale($pendingSale);

            DB::commit();
            return back()->with('success', 'Item updated.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function removeItem(Request $request, PendingSale $pendingSale, PendingSaleItem $item)
    {
        if ($pendingSale->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending orders can be edited.']);
        }
        if ((int) $item->pending_sale_id !== (int) $pendingSale->id) {
            return back()->withErrors(['error' => 'Invalid order item.']);
        }

        DB::beginTransaction();
        try {
            $item->delete();
            $this->recalcPendingSale($pendingSale);
            DB::commit();
            return back()->with('success', 'Item removed.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reject(Request $request, PendingSale $pendingSale)
    {
        if ($pendingSale->status !== 'pending') {
            return back()->withErrors(['error' => 'Order is not pending.']);
        }

        $pendingSale->forceFill([
            'status' => 'rejected',
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
        ])->save();

        return back()->with('success', 'Rejected.');
    }

    private function recalcPendingSale(PendingSale $pendingSale): void
    {
        $pendingSale->load('items');
        $subtotal = (float) $pendingSale->items->sum(function ($i) {
            return (float) $i->subtotal;
        });
        $discount = (float) ($pendingSale->discount ?? 0);
        $total = max(0, $subtotal - $discount);

        $pendingSale->forceFill([
            'subtotal' => $subtotal,
            'total' => $total,
        ])->save();
    }

    private function getAvailableStock(int $productId): int
    {
        $value = DB::table('product_stocks')
            ->where('product_id', $productId)
            ->orderByDesc('id')
            ->value('available_quantity');

        return (int) round((float) ($value ?? 0));
    }
}

