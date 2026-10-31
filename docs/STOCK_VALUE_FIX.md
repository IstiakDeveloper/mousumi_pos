# Stock value: balance sheet and product analysis

First fix only. Nothing else in `docs/IMPLEMENTATION_PLAN.md` is part of this change.

Checked against the live `mousumi_pos` database on 31 October 2026 by running `Product::getProductAnalysis`. No rows were changed.

## The two numbers

| Screen | End date | Stock value |
|---|---|---:|
| Balance sheet | 31 Oct 2026 (start date on the form is ignored) | 815,197.32 |
| Product analysis, default range 1–31 Oct 2026 | 31 Oct 2026 | 830,691.07 |
| Difference | | 15,493.75 |

Quantity is the same on both: 13,226 units. The pieces match. The taka do not.

## Why

Both screens call `Product::getProductAnalysis`. Closing stock value inside that method is not “pieces left × average cost”. It is a period sum:

```
opening pieces × unit cost of the last stock row before the start date
+ purchase cost inside the selected dates
− pieces sold inside the selected dates × average cost of every purchase up to the end date
```

`Product::calculateProductMetrics` (the block that sets `available_stock_value`). Each product is then clamped with `max(0, …)`.

The balance sheet does not use the dates typed on the balance sheet form. It calls the same method from the fixed start `2024-12-29` through the end date (`BalanceSheetController`, `calculatePropertyAndAssets`). Nothing in this shop is dated before 29 December 2024, so opening stock on that call is zero. The formula then collapses to:

```
all purchase cost − all pieces sold × lifetime average cost
= pieces still in hand × lifetime average cost
= 815,197.32
```

Product analysis opens on the first day of the current month (`ProductAnalysisReportController::index`). For October it values the 1 October stock at the `unit_cost` stored on the last stock row before that morning, then mixes in October purchases and October sales. That path produces 830,691.07.

Same end date. Different start date. Different stock value. The start date should not change what the stock is worth at the end date.

Two rows show the mix:

| Product | In hand 31 Oct | October report | Balance sheet | What happened |
|---|---:|---:|---:|---|
| Pressure Roller | 20 | 25,000.00 | 16,140.00 | No purchase and no sale in October. The month report keeps 20 × 1,250, the unit cost on the last row. The sheet uses 20 × the lifetime average, 807. |
| A4 Size Plastic File | 0 | 1,400.00 | 0.00 | Sold out. The month report still shows 1,400 because opening value was not cancelled by the sales-cost line. The sheet shows zero. |

Across all products the gaps sum to 15,493.75.

## Rule

Stock value on a date is a balance, not a total for the selected range.

For each active product, at end date D:

```
on_hand = purchase quantity with created_at <= D
        − sale line quantity whose sale is not deleted and created_at <= D
value   = on_hand × (purchase cost up to D ÷ purchase quantity up to D)
```

If `on_hand` is 0, value is 0. A sold-out product cannot keep a stock value.

One PHP method owns this. The balance sheet and the product analysis closing column both call it with their end date. The product analysis start date continues to filter purchases, sales, and profit for the month. It does not enter the closing stock value.

On 31 October 2026 both screens then show **815,197.32**.

## What stays untouched

- Today’s on-hand quantities. This change does not write stock or bank rows.
- The 555 fixed-asset line, customer due, bank balance, and net profit on the balance sheet.
- Month profit on product analysis.
- The opening-value column may still show the old snapshot price until a later change. It must not be added into `available_stock_value`.

## Code

1. Add `Product::stockValueAsOf(Carbon $endDate): array` returning per-product `available_quantity`, `available_stock_value`, and the grand total. The arithmetic is the rule above, including `whereNull('deleted_at')` on purchases and sales, active products only, same as `getCurrentStockPositions`.
2. `calculateProductMetrics` sets `available_stock_value` from that method for `$endDate`. Remove the line `beforeStockValue + totalBuyCost - monthlySalesCost` and the `max(0, …)` around it.
3. `BalanceSheetController` sets stock value from `Product::stockValueAsOf($endDate)`. Delete the `2024-12-29` call that exists only to feed this stock figure. Leave the profit calculation as it is for this fix.
4. Test with a product that does not move inside the month and whose last `unit_cost` is not the lifetime average (Pressure Roller shape), and a product that reaches zero on hand inside the month (A4 file shape). Assert the month report’s closing value equals the all-time report’s closing value and equals `on_hand × average`. Assert a zero balance has zero value.

## Done when

Product analysis for 1–31 October 2026 and the balance sheet ended 31 October 2026 both show stock value 815,197.32. Pressure Roller shows 16,140 on both. A4 Size Plastic File shows 0 on both.
