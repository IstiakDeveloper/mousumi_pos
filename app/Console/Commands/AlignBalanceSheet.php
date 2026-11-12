<?php

namespace App\Console\Commands;

use App\Models\BankTransaction;
use App\Models\ExtraIncome;
use App\Models\ExtraIncomeCategory;
use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AlignBalanceSheet extends Command
{
    protected $signature = 'books:align-balance-sheet {--dry-run : Show the alignment without writing}';

    protected $description = 'Match balance-sheet income to cash already in the bank, without changing the bank total';

    /**
     * Document amount was saved lower than the bank row created with it.
     * The bank row is left as posted. Only the document is raised.
     *
     * @var array<int, array{date: string, title: string, document: string, bank: string, description: string}>
     */
    private array $extraIncomePairs = [
        [
            'date' => '2025-02-25',
            'title' => 'Jan interest',
            'document' => '6.00',
            'bank' => '96.00',
            'description' => 'Extra Income (Bank Interest): Jan interest',
        ],
        [
            'date' => '2025-02-25',
            'title' => 'Gp Feb',
            'document' => '19.00',
            'bank' => '109.00',
            'description' => 'Extra Income (Flexiload): Gp Feb',
        ],
        [
            'date' => '2025-02-25',
            'title' => 'Photocopy Feb',
            'document' => '26.00',
            'bank' => '376.00',
            'description' => 'Extra Income (Photocopy): Photocopy Feb',
        ],
    ];

    /**
     * Cash received above the invoice. The sale lines stay as they are.
     * The extra is moved from the sale receipt into extra income, same bank account, same date.
     *
     * @var array<int, array{invoice: string, sale: string, bank: string}>
     */
    private array $overReceipts = [
        ['invoice' => 'INV-20250917-0036', 'sale' => '2400.00', 'bank' => '2440.00'],
        ['invoice' => 'INV-20250106-0049', 'sale' => '55.00', 'bank' => '75.00'],
        ['invoice' => 'INV-20250601-0011', 'sale' => '1960.00', 'bank' => '1965.00'],
    ];

    public function handle(): int
    {
        try {
            $plan = $this->plan();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $gap = $this->documentGap();
        $projected = round($gap + $plan['gap_change'], 2);

        $this->table(
            ['Step', 'What will happen'],
            $plan['rows'] === []
                ? [['—', 'Nothing. The rows are already aligned.']]
                : $plan['rows']
        );

        $this->line('Document gap now: '.number_format($gap, 2));
        $this->line('Document gap after: '.number_format($projected, 2));
        $this->line('Bank total change: 0.00');

        if (abs($projected) >= 0.01) {
            $this->error('These edits would leave a document gap of '.number_format($projected, 2).'. Nothing was written.');

            return self::FAILURE;
        }

        if ($plan['rows'] === []) {
            $this->info('Already aligned. Nothing to do.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run only. Nothing was written.');

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($plan) {
                $bankBefore = $this->bankNet();
                $cacheBefore = $this->cachedBalance();

                foreach ($plan['writes'] as $write) {
                    $write();
                }

                $bankAfter = $this->bankNet();
                $cacheAfter = $this->cachedBalance();
                $gapAfter = $this->documentGap();

                if (abs($bankBefore - $bankAfter) >= 0.01) {
                    throw new RuntimeException('Bank total changed. Rolled back.');
                }

                if (abs($cacheBefore - $cacheAfter) >= 0.01) {
                    throw new RuntimeException('Stored bank balance changed. Rolled back.');
                }

                if (abs($gapAfter) >= 0.01) {
                    throw new RuntimeException('Document gap is '.number_format($gapAfter, 2).' after the write. Rolled back.');
                }
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Aligned. Cash in the bank is unchanged and the document gap is 0.00.');
        $this->line('Open the balance sheet once. Profit is higher by the cash that was already in the bank.');

        return self::SUCCESS;
    }

    /**
     * @return array{rows: array<int, array{0: string, 1: string}>, writes: array<int, callable>, gap_change: float}
     */
    private function plan(): array
    {
        $rows = [];
        $writes = [];
        $gapChange = 0.0;

        foreach ($this->extraIncomePairs as $pair) {
            $step = $this->planExtraIncome($pair);
            if ($step === null) {
                continue;
            }
            $rows[] = $step['row'];
            $writes[] = $step['write'];
            $gapChange += $step['gap_change'];
        }

        $rounding = $this->planRoundingRemoval();
        if ($rounding !== null) {
            $rows[] = $rounding['row'];
            $writes[] = $rounding['write'];
            $gapChange += $rounding['gap_change'];
        }

        foreach ($this->overReceipts as $over) {
            $step = $this->planOverReceipt($over);
            if ($step === null) {
                continue;
            }
            $rows[] = $step['row'];
            $writes[] = $step['write'];
            $gapChange += $step['gap_change'];
        }

        return [
            'rows' => $rows,
            'writes' => $writes,
            'gap_change' => $gapChange,
        ];
    }

    /**
     * @param  array{date: string, title: string, document: string, bank: string, description: string}  $pair
     * @return array{row: array{0: string, 1: string}, write: callable, gap_change: float}|null
     */
    private function planExtraIncome(array $pair): ?array
    {
        $income = ExtraIncome::query()
            ->whereDate('date', $pair['date'])
            ->where('title', $pair['title'])
            ->get();

        if ($income->count() !== 1) {
            throw new RuntimeException($pair['title'].' on '.$pair['date'].' matched '.$income->count().' extra income rows. Nothing was written.');
        }

        $document = $income->first();
        $bankRows = BankTransaction::query()
            ->where('description', $pair['description'])
            ->where('transaction_type', 'in')
            ->get();

        if ($bankRows->count() !== 1) {
            throw new RuntimeException($pair['description'].' matched '.$bankRows->count().' bank rows. Nothing was written.');
        }

        $bank = $bankRows->first();

        if (! $this->sameMoney($bank->amount, $pair['bank'])) {
            throw new RuntimeException($pair['title'].' bank row is '.$this->money($bank->amount).', expected '.$pair['bank'].'. Nothing was written.');
        }

        if ($this->sameMoney($document->amount, $pair['bank'])) {
            return null;
        }

        if (! $this->sameMoney($document->amount, $pair['document'])) {
            throw new RuntimeException($pair['title'].' document is '.$this->money($document->amount).', expected '.$pair['document'].' or '.$pair['bank'].'. Nothing was written.');
        }

        $incomeId = $document->id;
        $from = $pair['document'];
        $to = $pair['bank'];

        return [
            'row' => [
                'Extra income '.$pair['title'],
                'Document '.$from.' → '.$to.' so it matches the bank row. Bank row stays '.$to.'.',
            ],
            'gap_change' => -((float) $to - (float) $from),
            'write' => function () use ($incomeId, $from, $to) {
                $income = ExtraIncome::query()->lockForUpdate()->find($incomeId);

                if (! $income || ! $this->sameMoney($income->amount, $from)) {
                    throw new RuntimeException('Extra income '.$incomeId.' was not in the expected state. Rolled back.');
                }

                if ($income->description === null || $income->description === '') {
                    $income->description = 'Aligned to the bank receipt. Previous document amount was '.$from.'.';
                }

                $income->amount = $to;
                $income->save();
            },
        ];
    }

    /**
     * @return array{row: array{0: string, 1: string}, write: callable, gap_change: float}|null
     */
    private function planRoundingRemoval(): ?array
    {
        $rows = ExtraIncome::query()
            ->where('title', 'Rounding Adjustment')
            ->where('description', 'like', 'Balance sheet rounding adjustment%')
            ->get();

        if ($rows->count() > 1) {
            throw new RuntimeException('More than one rounding-adjustment income exists. Nothing was written.');
        }

        if ($rows->isEmpty()) {
            return null;
        }

        $rounding = $rows->first();

        if (! $this->sameMoney($rounding->amount, '39.94')) {
            throw new RuntimeException('Rounding adjustment is '.$this->money($rounding->amount).', expected 39.94. Nothing was written.');
        }

        $bankPlug = BankTransaction::query()
            ->where('description', 'like', '%Rounding Adjustment%')
            ->exists();

        if ($bankPlug) {
            throw new RuntimeException('A bank row mentions Rounding Adjustment. Nothing was written.');
        }

        $incomeId = $rounding->id;

        return [
            'row' => [
                'Rounding adjustment',
                'Remove the 39.94 document. It has no bank receipt.',
            ],
            'gap_change' => 39.94,
            'write' => function () use ($incomeId) {
                $income = ExtraIncome::query()->lockForUpdate()->find($incomeId);

                if (! $income || ! $this->sameMoney($income->amount, '39.94')) {
                    throw new RuntimeException('Rounding adjustment changed before it could be removed. Rolled back.');
                }

                $income->delete();
            },
        ];
    }

    /**
     * @param  array{invoice: string, sale: string, bank: string}  $over
     * @return array{row: array{0: string, 1: string}, write: callable, gap_change: float}|null
     */
    private function planOverReceipt(array $over): ?array
    {
        $sale = Sale::query()->where('invoice_no', $over['invoice'])->first();

        if (! $sale) {
            throw new RuntimeException($over['invoice'].' was not found. Nothing was written.');
        }

        if (! $this->sameMoney($sale->total, $over['sale']) || ! $this->sameMoney($sale->paid, $over['sale']) || ! $this->sameMoney($sale->due, '0')) {
            throw new RuntimeException($over['invoice'].' is not total '.$over['sale'].' fully paid. Nothing was written.');
        }

        $receipts = BankTransaction::query()
            ->where('transaction_type', 'in')
            ->where('description', 'like', 'Payment received for invoice '.$over['invoice'].'%')
            ->get();

        if ($receipts->count() !== 1) {
            throw new RuntimeException($over['invoice'].' matched '.$receipts->count().' sale receipts. Nothing was written.');
        }

        $receipt = $receipts->first();
        $title = 'Sale over-receipt '.$over['invoice'];
        $difference = round((float) $over['bank'] - (float) $over['sale'], 2);
        $aligned = ExtraIncome::query()->where('title', $title)->get();

        if ($this->sameMoney($receipt->amount, $over['sale'])) {
            if ($aligned->count() === 1 && $this->sameMoney($aligned->first()->amount, $difference)) {
                return null;
            }

            throw new RuntimeException($over['invoice'].' receipt is already '.$over['sale'].' but the extra income split is missing or duplicated. Nothing was written.');
        }

        if (! $this->sameMoney($receipt->amount, $over['bank'])) {
            throw new RuntimeException($over['invoice'].' receipt is '.$this->money($receipt->amount).', expected '.$over['bank'].' or '.$over['sale'].'. Nothing was written.');
        }

        if ($aligned->isNotEmpty()) {
            throw new RuntimeException($title.' already exists while the sale receipt is still '.$over['bank'].'. Nothing was written.');
        }

        $categoryId = ExtraIncomeCategory::query()->where('name', 'Others Sale')->value('id');
        if (! $categoryId) {
            throw new RuntimeException('Extra income category "Others Sale" was not found. Nothing was written.');
        }

        $receiptId = $receipt->id;
        $invoice = $over['invoice'];
        $saleAmount = $over['sale'];
        $bankAmount = $over['bank'];

        return [
            'row' => [
                $invoice,
                'Sale stays '.$saleAmount.'. Receipt '.$bankAmount.' → '.$saleAmount.', and '.$this->money($difference).' becomes extra income on the same date. Cash total unchanged.',
            ],
            'gap_change' => -$difference,
            'write' => function () use ($receiptId, $invoice, $saleAmount, $bankAmount, $difference, $categoryId, $title) {
                $updated = DB::table('bank_transactions')
                    ->where('id', $receiptId)
                    ->whereNull('deleted_at')
                    ->where('amount', $bankAmount)
                    ->update([
                        'amount' => $saleAmount,
                        'updated_at' => now(),
                    ]);

                if ($updated !== 1) {
                    throw new RuntimeException($invoice.' receipt was not in the expected state. Rolled back.');
                }

                $source = DB::table('bank_transactions')->where('id', $receiptId)->first();

                ExtraIncome::create([
                    'bank_account_id' => $source->bank_account_id,
                    'category_id' => $categoryId,
                    'amount' => $difference,
                    'title' => $title,
                    'description' => 'Balance alignment for '.$invoice.'. Cash stayed in the bank; the amount above the invoice was split out of the sale receipt.',
                    'date' => $source->date,
                    'created_by' => $source->created_by,
                ]);

                DB::table('bank_transactions')->insert([
                    'bank_account_id' => $source->bank_account_id,
                    'transaction_type' => 'in',
                    'amount' => $difference,
                    'running_balance' => $source->running_balance,
                    'description' => 'Extra Income (Others Sale): '.$title,
                    'date' => $source->date,
                    'created_by' => $source->created_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            },
        ];
    }

    private function documentGap(): float
    {
        $saleIn = $this->sumBank("transaction_type = 'in' AND description LIKE 'Payment received for invoice%'");
        $saleAdjustment = $this->sumBank("transaction_type = 'out' AND description LIKE 'Payment reduction%'");
        $salesNet = (float) Sale::query()->sum('total');
        $extraBank = $this->sumBank("transaction_type = 'in' AND description LIKE 'Extra Income%'");
        $extraDocument = (float) ExtraIncome::query()->sum('amount');
        $purchases = (float) DB::table('product_stocks')->whereNull('deleted_at')->where('type', 'purchase')->sum('total_cost');
        $purchaseBank = $this->sumBank("transaction_type = 'out' AND description LIKE 'Stock purchase%'");
        $refunds = $this->sumBank("transaction_type = 'in' AND description LIKE '%refund%'");

        return round(
            ($saleIn - $saleAdjustment - $salesNet)
            + ($extraBank - $extraDocument)
            + ($purchases - $purchaseBank + $refunds),
            2
        );
    }

    private function sumBank(string $filter): float
    {
        return (float) DB::table('bank_transactions')
            ->whereNull('deleted_at')
            ->whereRaw($filter)
            ->sum('amount');
    }

    private function bankNet(): float
    {
        return round((float) DB::table('bank_transactions')
            ->whereNull('deleted_at')
            ->sum(DB::raw("CASE WHEN transaction_type = 'in' THEN amount ELSE -amount END")), 2);
    }

    private function cachedBalance(): float
    {
        return round((float) DB::table('bank_accounts')->whereNull('deleted_at')->sum('current_balance'), 2);
    }

    private function sameMoney(mixed $left, mixed $right): bool
    {
        return $this->money($left) === $this->money($right);
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
