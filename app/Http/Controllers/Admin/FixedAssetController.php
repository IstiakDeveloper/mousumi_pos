<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\FixedAsset;
use App\Models\FixedAssetItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
        $assets = FixedAsset::query()
            ->with(['items' => function ($query) use ($request) {
                $query->with('bankAccount')
                    ->when($request->from_date, fn ($q, $from) => $q->whereDate('purchase_date', '>=', $from))
                    ->when($request->to_date, fn ($q, $to) => $q->whereDate('purchase_date', '<=', $to))
                    ->orderBy('purchase_date')
                    ->orderBy('id');
            }])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_code', 'like', "%{$search}%")
                        ->orWhereHas('items', fn ($items) => $items->where('item_name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('asset_code')
            ->get();

        return Inertia::render('Admin/FixedAssets/Index', [
            'assets' => $assets,
            'bankAccounts' => BankAccount::where('status', true)->orderBy('account_name')->get(),
            'nextCode' => $this->nextAssetCode(),
            'filters' => $request->only(['search', 'from_date', 'to_date']),
            'summary' => [
                'count' => FixedAssetItem::count(),
                'purchase_value' => (float) FixedAssetItem::sum('purchase_price'),
                'active_value' => (float) FixedAssetItem::where('status', 'active')->sum('current_value'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_code' => 'nullable|string|max:50|unique:fixed_assets,asset_code',
            'description' => 'nullable|string',
            'item_name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0.01',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'item_description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $asset = FixedAsset::create([
                'name' => $validated['name'],
                'asset_code' => $validated['asset_code'] ?: $this->nextAssetCode(),
                'status' => 'active',
                'description' => $validated['description'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $this->createItem($asset, $validated);
        });

        return redirect()->route('admin.fixed-assets.index')
            ->with('success', 'Fixed asset recorded.');
    }

    public function update(Request $request, FixedAsset $fixedAsset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_code' => 'nullable|string|max:50|unique:fixed_assets,asset_code,'.$fixedAsset->id,
            'status' => 'required|in:active,disposed',
            'description' => 'nullable|string',
        ]);

        $fixedAsset->update($validated);

        return redirect()->route('admin.fixed-assets.index')
            ->with('success', 'Fixed asset updated.');
    }

    public function destroy(FixedAsset $fixedAsset)
    {
        DB::transaction(function () use ($fixedAsset) {
            foreach ($fixedAsset->items as $item) {
                $this->removeItem($item);
            }
            $fixedAsset->delete();
        });

        return redirect()->route('admin.fixed-assets.index')
            ->with('success', 'Fixed asset removed.');
    }

    public function storeItem(Request $request, FixedAsset $fixedAsset)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0.01',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'item_description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($fixedAsset, $validated) {
            $this->createItem($fixedAsset, $validated);
        });

        return redirect()->route('admin.fixed-assets.index')
            ->with('success', 'Asset item recorded.');
    }

    public function updateItem(Request $request, FixedAssetItem $item)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0.01',
            'status' => 'required|in:active,disposed,damaged',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($item, $validated) {
            $item->load('asset');
            $previousDate = $item->purchase_date?->toDateString();
            $price = round((float) $validated['purchase_price'], 2);

            $item->update([
                'item_name' => $validated['item_name'],
                'purchase_date' => $validated['purchase_date'],
                'purchase_price' => $price,
                'current_value' => $price,
                'status' => $validated['status'],
                'description' => $validated['description'] ?? null,
            ]);

            $transaction = $item->bankTransaction;
            if (! $transaction) {
                return;
            }

            $changes = [
                'amount' => $price,
                'description' => "Fixed Asset: {$item->item_name} ({$item->asset->name})",
            ];

            if ($previousDate && $transaction->date?->toDateString() === $previousDate) {
                $changes['date'] = $item->purchase_date->toDateString();
            }

            $transaction->update($changes);
        });

        return redirect()->route('admin.fixed-assets.index')
            ->with('success', 'Asset item updated.');
    }

    public function destroyItem(FixedAssetItem $item)
    {
        DB::transaction(function () use ($item) {
            $this->removeItem($item);
        });

        return redirect()->route('admin.fixed-assets.index')
            ->with('success', 'Asset item removed.');
    }

    private function createItem(FixedAsset $asset, array $validated): FixedAssetItem
    {
        $price = round((float) $validated['purchase_price'], 2);

        $transaction = BankTransaction::create([
            'bank_account_id' => $validated['bank_account_id'],
            'transaction_type' => 'out',
            'amount' => $price,
            'description' => "Fixed Asset: {$validated['item_name']} ({$asset->name})",
            'date' => $validated['purchase_date'],
            'created_by' => auth()->id(),
        ]);

        return FixedAssetItem::create([
            'fixed_asset_id' => $asset->id,
            'item_name' => $validated['item_name'],
            'purchase_date' => $validated['purchase_date'],
            'purchase_price' => $price,
            'current_value' => $price,
            'bank_account_id' => $validated['bank_account_id'],
            'bank_transaction_id' => $transaction->id,
            'status' => 'active',
            'description' => $validated['item_description'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    private function removeItem(FixedAssetItem $item): void
    {
        if ($item->bank_transaction_id) {
            BankTransaction::query()->find($item->bank_transaction_id)?->delete();
        }

        $item->delete();
    }

    private function nextAssetCode(): string
    {
        $max = 0;
        foreach (FixedAsset::withTrashed()->pluck('asset_code') as $code) {
            if ($code && preg_match('/FA-(\d+)/i', $code, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return 'FA-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
