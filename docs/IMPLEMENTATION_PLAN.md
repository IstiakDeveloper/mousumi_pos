# Mousumi POS implementation plan

Inspection date: 31 October 2026. No application code and no database rows were changed to produce this plan.

TrustCash (`C:\Code\TrustCash`) was read as the reference for intended shop features. Mousumi POS (`C:\Code\mousumi_pos`) is the system to repair. The live shop books are the MySQL database `mousumi_pos`. The TrustCash landlord database `trustcash` contains two registered tenants (`demo`, `dokandaricombd`) and does not contain these shop books. `tenantmousumi` and `tenantmousumii` exist on the server and are not rows in `trustcash.tenants`.

Shared POS migrations in the two repositories are the same files. Mousumi is the single-shop application and it stays that way. The owner confirmed on 31 October 2026 that this system is only for Mousumi Prokashon. The shop name and address may stay hardcoded. TrustCash’s stancl/tenancy, plans, and landlord admin are out of scope and will not be ported.

TrustCash remains the reference for purchases, suppliers, returns, fixed assets, and a balance sheet that no longer hardcodes 555. Several of those implementations post wrong stock or wrong cash, so they are specified again below instead of being copied.

## 1. What the shop actually does

One business, one cash account (`Mousumi Prokashon`, Prime Bank, id 1, opening balance 0). Catalog, POS sales, stock purchases, expenses, extra income, owner funds, a public storefront that creates pending orders, and reports. There is no tenant key, no branch foreign key, and no permission check. `/admin` requires only a logged-in user.

Live volume on 31 October 2026:

| Record | Live rows | Notes |
|---|---:|---|
| Sales | 1,717 | 2 soft-deleted. Every live sale is `paid`. Due is 0. Header totals match line items. Tax is always 0. 172 sales have a discount. |
| Sale lines | 19,113 | No soft deletes. Lines of deleted sales remain. |
| Sale payments | 5 | POS takings are not stored here. |
| Products | 184 live | 2 soft-deleted. |
| Stock rows | 19,561 live | 1,562 purchase, 17,967 sale, 32 sale reversal, 4 soft-deleted. |
| Customers | 54 | Balance 0. `branch_code` empty on every row. Credit limit from 50,000 to 50,000,000. |
| Bank transactions | 3,509 live | 8 soft-deleted. |
| Expenses | 133 | 10 of them are category `Fixed Asset`, total 346,750. |
| Extra income | 89 live | 2 soft-deleted (1,500 and 75). |
| Funds | 3 | In 1,150,000, out 150,000. |
| Pending sales | 7 | All rejected. |

First cash row, first stock row, and first fund are dated 2024-12-29. First sale is 2025-01-02. Nothing is dated before 2024-12-29.

## 2. Why the reports disagree

The books are a set of documents (sale, expense, extra income, fund, stock purchase) plus a cash list (`bank_transactions`). Reports sometimes add the documents, sometimes add cash rows chosen by the wording of `description`, and sometimes add a stored balance that neither of those supports. Three profit formulas are in use. One balance-sheet line is a constant.

### 2.1 The 555 is a plug for a 555.06 document gap

`BalanceSheetController` sets customer due to 0 and then does `fixed assets − 555`. The comment on that line says the 555 has to be adjusted. No fixed-asset expense is 555. The ten fixed-asset lines sum to 346,750.00.

Recomputing the sheet’s own formulas from the live tables, through 31 October 2026, gives:

| Side | Amount |
|---|---:|
| Cash, from live bank transactions (opening 0) | 245,594.47 |
| Customer due, real and hardcoded | 0.00 |
| Fixed assets, before the plug | 346,750.00 |
| Stock value, purchase cost still unsold at weighted average | 815,197.32 |
| **Assets** | **1,407,541.79** |
| Owner funds, in minus out | 1,000,000.00 |
| Product profit, sales after discount minus sold quantity × average purchase cost | 627,602.12 |
| Extra income, from `extra_incomes` | 303,416.08 |
| Operating expenses, all expenses except Fixed Asset | 524,031.47 |
| **Equity** | **1,406,986.73** |
| Assets minus equity | **555.06** |

Subtracting 555 from fixed assets hides that gap. It does not explain it. The gap is three sets of rows that do not describe the same event.

**Extra income, net 490.06.** `ExtraIncomeController::update` saves the document and then looks up the bank row with `getOriginal()`. After `update()`, that returns the new amount, so the old bank row is not found and is left unchanged. The 25 February 2025 entries were edited on the document only:

| Title | Document | Bank row still posted |
|---|---:|---:|
| Jan interest | 6.00 | 96.00 |
| Gp Feb | 19.00 | 109.00 |
| Photocopy Feb | 26.00 | 376.00 |

Bank minus document on those three is 530.00. A document-only row, `Rounding Adjustment` 39.94 on 30 November 2025, has no bank row. 530.00 − 39.94 = 490.06.

**Sales receipts, net 65.00.** Four live invoices have a `Payment received` bank row that does not equal `sales.paid`:

| Invoice | Paid on the invoice | Bank receipt | Difference |
|---|---:|---:|---:|
| INV-20250917-0036 | 2,400.00 | 2,440.00 | 40.00 |
| INV-20250106-0049 | 55.00 | 75.00 | 20.00 |
| INV-20250703-0007 | 4,640.00 | 4,660.00 | 20.00 |
| INV-20250601-0011 | 1,960.00 | 1,965.00 | 5.00 |

The 20.00 on `INV-20250703-0007` is reversed by bank transaction 1169, an `out` of 20.00 described `Payment reduction for updated invoice INV-20250703-0007`. That pair cancels in a full cash sum. The other three do not. 40 + 20 + 5 = 65.

490.06 + 65.00 = 555.06.

`sales.bank_account_id` is read and written in `SaleController` and is not a column. `Sale::$fillable` does not include it. Editing or deleting a sale cannot find the original tender. The payment-reduction row was typed in by hand and is not linked to the sale.

### 2.2 Stored bank balance is a different, larger error

`bank_accounts.current_balance` is **1,098,520.49**. The sum of live transactions is **245,594.47**. The latest `running_balance` is **245,526.80**. Nine rows break the running-balance chain, with 736,137.67 of absolute jumps, because `BankTransactionObserver::creating` copies the latest balance by date and does not rewrite later rows when a backdated transaction is inserted.

The balance sheet and the dashboard recompute cash from the transaction rows, so they show about 245,594. The bank-account screen shows 1,098,520.49. Any page that compares a computed running balance with `current_balance` will look wrong even when the transaction list is complete.

The stored balance was doubled by a second write after the observer. `BankTransactionObserver::created` already adds the amount. These controllers then add or subtract it again: `FundManagementController`, `ExpenseController`, `ExtraIncomeController`, and `SaleCreator`. POS sale posting does not do that second add. Fund, expense, and extra-income updates also adjust the balance by hand and then miss the original bank row, so the cache and the row diverge permanently. TrustCash removed the second write. Mousumi still has it.

`current_balance` is not a source for any repaired report. The transaction list is the cash source, after the rows in section 2.1 are tied back to their documents.

### 2.3 Stock quantity on the screen matches sales. The `quantity` column does not.

For all 184 live products, the latest `product_stocks.available_quantity` equals purchase `quantity` minus live `sale_items.quantity`. On-hand stock used by POS is consistent with the invoices.

The `quantity` column is not a reliable delta. On 90 products, sale rows sum to 3,181 fewer units than the sale lines (sale rows store a negative quantity; for example product 87, `5 Takar Item`, sale rows −6,637 against 6,847 sold, while on-hand 706 still matches purchases minus sales). `SaleController::destroy` does not append a reversal. It runs `ProductStock::firstOrCreate(['product_id'])` and `increment('quantity')` on that old row, and it does not change `available_quantity`. A 7-day guard returns the JSON key `massage`. The reversal type it would post is `withdrawal`, which is not in the `in`/`out` enum, and it only runs when `bank_account_id` is set, which never is.

`Product::getCurrentStockPositions` comments that `available_quantity` must not be trusted, then rebuilds quantity from purchase rows minus sale lines and clamps at 0. That happens to match this database today. `stocks:recalculate` does not. For a non-purchase row it subtracts `quantity`, and sale rows are already negative, so it adds the sale back, then subtracts sale lines again, then clamps at 0. Running it would corrupt on-hand stock that is currently right.

`stock_movements` is a second log. `ManagesStock` writes movements and never updates `product_stocks`. Nothing in the sale flow calls it. `reference_type` is `sale`, `purchase`, or a class name, depending on which command last rebuilt the table. `stock:populate-movements` truncates the table.

### 2.4 One profit figure does not exist

| Screen | Formula |
|---|---|
| Dashboard | `sales.total` in the range, minus sold quantity × average purchase cost of **all** purchases with no end date, plus extra income, minus expenses. Mousumi drops category `Fixed Asset`. |
| Income and expenditure | Product-analysis profit for the range (average cost of purchases up to the range end, so a purchase inside the range restates earlier sales) plus extra income. Mousumi drops `Fixed Asset` from the lines. The Vue “Grand Total” prints the expense total again, on both period and cumulative columns (`IncomeExpenditure.vue`). |
| Balance sheet | Product-analysis profit from the constant `2024-12-29` through `end_date`, plus extra income, minus operating expenses. `start_date` is accepted and ignored. `calculateCostOfGoodsSold()` walks cost at each sale and is never added. |
| Product analysis | The income-statement engine. `available_stock_value` and quantity are clamped with `max(0, …)`. Inactive products are omitted even if they sold. |

For this shop the constant `2024-12-29` does not drop rows, because that is the first day in the database. Reports should still use the earliest business date in the data. A hardcoded start date drops any opening row entered before that day.

Customer due on the Mousumi sheet is the constant 0. It matches today only because every live invoice is fully paid and every `customers.balance` is 0. A credit sale would disappear from assets.

### 2.5 Cash reports classify sentences

`BankBalanceCalculator`, the bank transaction report, and the receipt-and-payment report decide what a row is by `description LIKE 'Fund in%'`, `'Payment received%'`, `'Stock purchase%'`, `'Expense%'`, and similar. `ReceiptPaymentController` also hardcodes `$bankAccountId = 1`.

Consequences already visible:

- Five real sale receipts say `Payment received for invoice … from customer …`. An exact description match misses them. The amounts are the invoice totals.
- The 20.00 payment reduction matches none of the buckets, so the receipt-and-payment closing, which replays only the buckets, can still balance while omitting it.
- Extra income is added from `extra_incomes` in some reports and from bank rows in others. Those totals differ by 490.06 (303,906.14 on the bank, 303,416.08 on the table).
- Bank report SQL excludes `%Rounding Adjustment%`. The balance sheet does not. The only such document row is the 39.94 above, and it has no bank row, so this filter is currently a no-op on cash and a trap for the next rounding row.
- `BankReportController` returns the balance **after** the period as `previous_balance`, because the running loop overwrites the opening variable.

TrustCash’s daily cash report adds `other_in` and `other_out`, which is the right idea, and then takes `max(bank extra income, extra_incomes table)` so a mismatch is hidden. A description that contains both `invoice` and `Refund` can land in two columns. That `max()` is not a repair to copy.

### 2.6 Sale posting trusts the browser and loses the tender

`PosController` and `SaleCreator` store `subtotal`, `discount`, and `total` from the request. They do not recompute them from the lines. On this database the stored headers happen to match. That is not a guarantee.

The paid amount becomes one bank row, `Payment received for invoice {invoice_no}`, and no `sale_payments` row. The account id is not stored on the sale. Walk-in sales in Mousumi may finish with a due amount and no customer, so that due is nobody’s receivable. TrustCash rejects that, and it treats credit limit 0 as “no limit”. Mousumi compares `balance + due` with `credit_limit` even when the limit is 0, which blocks every credit sale. The live customers all have a large limit, so the bug is latent.

`SaleCreator` (pending-order approval) inserts the bank row and then `increment('current_balance')`, so the observer’s add runs twice. Invoice numbers use a count of non-deleted sales and give up after 10 tries, so a soft-deleted invoice number can collide.

`customers.balance` is incremented by `sales.due` and is not the sum of open invoices. Both are 0 today. They will drift as soon as a receipt is posted on a later day, because `CustomerSalesReportController` uses the current `sales.due` for “previous balance”, not the due as of the first of the month.

### 2.7 Commands that rewrite history

| Command | What it does to this database |
|---|---|
| `sales:fix-payments` | Clamps `paid` to `total` and recomputes `due` from the header. Does not touch the bank row or `customers.balance`. |
| `bank:recalculate-balances` | Replays live transactions onto `running_balance` and `current_balance`. This is the correct repair for the 1,098,520.49 cache **after** the document gaps in section 2.1 are decided. Running it first only makes the cache match a list that still disagrees with income by 555.06. |
| `stocks:recalculate` | Double-counts negative sale quantities and clamps at 0. Do not run. |
| `stock:recalculate-historical` | Default window ends 2025-01-29. Matches a sale row by product, day, and quantity, so two sales of the same quantity on the same day collide. Writes `unit_cost = 0` when it inserts a missing row. |
| `stock:populate-movements` | Truncates `stock_movements`. |
| `stock:soft-delete-for-deleted-sales` | Soft-deletes every `type = sale` row on that calendar day for the product, including a different invoice. |

TrustCash adds `stocks:fix-deleted-sales` for Mousumi’s old delete path. It is a patch for the `increment('quantity')` bug, not a stock model.

## 3. TrustCash features, and the behavior to leave behind

TrustCash is the feature reference. The following behavior is specified again in section 5 so it is not ported.

**Tenancy.** stancl/tenancy, database per shop, name `tenant{id}`. Shop admin runs on the central host. The tenant database is selected from session `active_tenant_id`. Central `users` and tenant `users` are different tables and both start at id 1. `/super-admin` deliberately does not initialize tenancy, then loads that numeric id from the central `users` table. Password reset updates `users.id = 1` or `role_id = 1`. Login walks every tenant database and keeps the first username match; a failed database is an empty `catch`. `database/migrations` and `database/migrations/tenant` both contain the POS schema. `artisan migrate` can see the tenant folder. `branches` exists, and `branch_id` is a column only on `fixed_assets`. Plan limits (`max_users`, `max_products`, `max_branches`) are never checked. `roles.permissions` is stored and never enforced. `FixedAssetController` filters `branches.status = true` while the column is the string `active`.

**Purchases and returns.** `PurchaseController` sets `available_quantity` to the line quantity, not previous on-hand plus the line. The movement’s `before_quantity` is `sum(available_quantity)` of every other row, which sums snapshots. `SaleReturnController` does the same with the returned quantity, and it stores `unit_cost` as the selling price. The next POS sale then treats that snapshot as the whole on-hand quantity. Supplier payments often leave `purchase_id` null and do not reduce `purchases.due_amount`. `ProductStockController` writes a weighted average into `cost_price` and then overwrites it with the last unit cost. New bank descriptions (`Purchase Payment`, `Supplier Payment`, `Fixed Asset`, `Customer Sale Refund`) do not match the old cash-report buckets. Sale-return refunds are `out` rows, and the refund bucket counts `in` rows.

**Balance sheet in TrustCash.** The 555 and the zeroed receivable are gone. Opening capital is `sum(bank_accounts.opening_balance)`, customer due is today’s `customers.balance` with no rollback to `end_date`, supplier due is historical, fixed assets are `fixed_asset_items.current_value`, and profit still starts at `2024-12-29`. Sale returns never enter that profit. For this shop, opening balance is already 0 and the 1,000,000 is a fund, so adding opening capital on top of funds would be correct only because opening balance is 0. Copying the formula into a shop that recorded the same cash both ways would double capital.

**Customer “branch”.** `customers.branch_code` and `branch_name` are free text. The seeder reads `https://mis.mousumibd.org/api/public/branches`. In `mousumi_pos` every code is empty. `bank_accounts.branch_name` is the bank’s branch. Neither is a stock location.

## 4. Target behavior

Mousumi stays Laravel 11, Inertia, and Vue 3, one database, one shop. Financial documents are posted by services. Reports read those documents. The invoice and storefront may show “Mousumi Prokashon” and its address as fixed text. A balance sheet that does not balance is shown as a difference. It is not given a constant.

### 4.1 Accounting identity

For any date D, in the shop’s business timezone (Asia/Dhaka):

```
cash(D) + receivables(D) + inventory(D) + fixed_assets(D)
  = owner_capital(D) + retained_earnings(D) + supplier_payables(D)
```

Definitions:

- **Cash.** `opening_balance` plus every live `bank_transactions` row with `business_date <= D`. No description filter. No rounding exclusion. Inactive accounts stay in the historical sum.
- **Receivables.** For each sale dated on or before D, `total − payments dated on or before D − returns dated on or before D`, summed. Not `customers.balance`. Not a hardcoded 0.
- **Inventory.** On-hand quantity at D × cost, using the cost stored on each outbound movement (section 4.3). Negative quantity is shown, not clamped.
- **Fixed assets.** Sum of asset item cost for items purchased on or before D. No depreciation until a depreciation schedule exists. `current_value` is not a second accounting method.
- **Owner capital.** Fund in minus fund out through D. `opening_balance` is capital only for cash that has no fund document. This shop’s opening balance is 0 and the 1,000,000 is already a fund on 2024-12-29, so capital is the fund net and opening balance adds nothing.
- **Retained earnings.** Sales after discount, minus cost of goods sold, minus sale returns (revenue and cost both reverse), plus extra income, minus operating expenses, from the first business date through D. Purchases, supplier payments, customer receipts, and fixed-asset purchases are not expenses.
- **Supplier payables.** Purchase totals through D minus supplier payments allocated through D, including opening supplier balances.

Dashboard, income statement, and balance sheet call one statement service. The income statement is retained earnings for a range. The dashboard’s profit for that range is the same number. The balance sheet’s retained earnings is that number from inception through `end_date`.

`start_date` on the balance sheet is display only. The statement is a position at `end_date`.

### 4.2 Business date

`created_at` is the audit timestamp. Reports and stock sequence use `business_date` (a `date` column) on sales, payments, purchases, returns, stock movements, expenses, extra income, funds, and bank transactions. The application timezone for “today” is Asia/Dhaka. The current app timezone is UTC, and sales are filtered on `created_at`, so a late evening sale in Bangladesh can fall on the next report day.

### 4.3 Cost of goods sold

Weighted average at the moment the stock leaves, stored on the sale line and on the stock movement:

```
average = on_hand_cost / on_hand_qty    before this line
line_cogs = quantity × average
```

A purchase posted later does not change `sale_items.cogs_amount`. A return restores quantity at the original sale line’s unit cost, not at the selling price. Product analysis for a past month sums the stored `cogs_amount` of lines in that month.

`products.cost_price` may cache the current average for the product form. Reports do not read it.

### 4.4 Posting rules

One service writes cash. One service writes stock. Controllers do not update `current_balance`, `running_balance`, `customers.balance`, `suppliers.current_balance`, or `available_quantity` themselves.

**Cash service.** Locks the `bank_accounts` row. Inserts one `bank_transactions` row with `source_type` and `source_id`. Sets `running_balance` from the previous row in `(business_date, id)` order under that lock. Updates `current_balance` once. If a backdated row is inserted, rebuilds `running_balance` for later rows of that account inside the same transaction. Update and delete of a source document update or reverse that one linked row. Lookup by amount plus date is removed.

**Stock service.** Locks the product. Inserts one movement with a signed quantity and a unit cost. On-hand is the sum of movements. A cached `products.on_hand` is updated in that same lock for the POS screen. There is one quantity column on the movement. `available_quantity`, `total_quantity`, and a separately edited `quantity` are not kept as competing truths.

**Sale.** The server computes line `subtotal`, invoice `subtotal`, `discount`, `tax`, and `total`. Each tender is a `sale_payments` row and, when it hits a bank account, one cash row whose source is that payment. `paid` and `due` are rewritten from the payment rows. A walk-in (no customer) must be paid in full. A named customer may take credit up to `credit_limit`; a null or zero limit means no credit. The customer row is locked for the credit check. `customers.balance` is then set from open dues, not incremented in isolation.

**Sale edit and void.** Void posts reversing stock movements and reversing cash for the payments, in the same transaction. It does not increment an old purchase row. Edit is a void of the old lines plus a new posting, or an explicit adjustment movement. The 7-day rule can stay as a business rule, enforced on `business_date`, with the error key `message`.

**Stock purchase, until purchase invoices exist.** The existing stock-receipt screen posts an inbound movement at the entered unit cost and one cash `out` linked to that receipt. It does not write `current_balance` itself.

**Expense, extra income, fund.** The document and its cash row are created, updated, and voided together through the cash service. Changing the amount changes the linked row. Fixed-asset purchases are not expenses (section 5, phase 4).

**Customer receipt against an old invoice.** A `sale_payments` row dated on the receipt date, linked cash row, and recalculated `paid`, `due`, and customer balance. The sale’s own `business_date` does not change. Reports of “cash collected this month” use the payment date.

## 5. Schema

Money columns that are `decimal(10,2)` move to `decimal(14,2)`. Quantities that are integers move to `decimal(12,3)`. This shop’s quantities are whole numbers, so the migration is exact. Purchase lines in the reference app already use decimals while sale lines use integers; one scale removes that split.

Foreign keys that are missing and will be added:

| Column | Target |
|---|---|
| `bank_transactions.source_type` + `source_id` | The payment or document that caused the row. Unique together. Indexed. |
| `sale_items.unit_cost`, `sale_items.cogs_amount` | Stored at posting. Not a foreign key. |
| `sales.business_date` | Replaces `created_at` as the report date. |
| `stock_movements` source | Already polymorphic. One vocabulary: `sale_item`, `purchase_item`, `sale_return_item`, `adjustment`. Not class names and short strings mixed. |
| `supplier_payments.purchase_id` | Real foreign key, required when the payment settles an invoice. |

Columns and relations to drop from application code as soon as the replacement is written:

- Writes to `sales.bank_account_id` and `updated_by` until those columns exist. The tender lives on `sale_payments`, so `sales.bank_account_id` is not added.
- `product_stocks.bank_account_id` in `$fillable`. The cash link lives on the purchase or the stock receipt.
- `roles.status` in `$fillable`. The `roles` table has no `status` column. `permissions` is `text` while the cast is `array`.
- The self-relation `BankAccount::bankAccount()`.

`product_stocks` stays only as a migration source. After movements are rebuilt and checked, new purchases and sales do not insert snapshot rows. A later migration drops `available_quantity` as an edited column once every reader uses the movement sum. Doing that drop before the readers are switched will break POS.

New document tables, when those features are built (phase 4), follow the posting rules above. They are not created by copying the TrustCash migrations unchanged. In particular, a purchase line’s stock movement uses previous on-hand plus the line quantity, `supplier_payments.purchase_id` is a foreign key, and a return line stores the sale line’s unit cost.

Indexes to add with the ledger: `(bank_account_id, business_date, id)` on bank transactions, `(product_id, business_date, id)` on stock movements, `(source_type, source_id)` unique on bank transactions, and `(customer_id, business_date)` on sales.

Soft delete remains for catalog rows (product, customer, category). Financial documents are voided by a reversing posting and a `voided_at` timestamp so the original lines stay visible to the audit. A soft-deleted sale with live lines and no reversal is how the current delete path loses cash and corrupts `quantity`.

## 6. One shop

No tenancy. No `tenant_id`. No second database. No plans, domains, or landlord admin. TrustCash’s tenancy bugs are irrelevant here.

The legal name and address on invoices, PDFs, and the storefront stay hardcoded for Mousumi Prokashon. That does not affect the 555.06 gap. The gap is unmatched money rows, not the shop title.

`GET /migrate`, `GET /storage-link`, and `GET /run-backup` are still removed. They run Artisan with no login.

Staff roles stay inside this one database. `/admin` should require a role, not only a logged-in user. There is no separate super-admin application.

While there is one bank account, reports that assume account id 1 happen to see all cash. Payments are still stored on `sale_payments` with that account id, so a sale edit can find the tender. A later bKash or cash-drawer account then works without another hardcoded id.

## 7. Branches

Not part of the ledger repair. The live shop is one cash account and one stock pool. `customers.branch_code` is an external label, and it is empty.

When a second physical shop is required, `branch_id` is required on sales, sale payments, purchases, stock movements, expenses, extra income, and funds. Cash accounts are either owned by one branch or marked shared. Users have a branch assignment. Reports take a branch filter. That work starts from an empty `branches` table and a single default branch backfilled onto existing rows. It does not start from `customers.branch_code`.

## 8. Implementation phases

Each phase is done in Mousumi POS, with tests, before the next phase starts. TrustCash is not modified.

### Phase 0 — Safety and a failing identity test

- Remove the unauthenticated `/migrate`, `/storage-link`, and `/run-backup` routes.
- Add a read-only audit command that prints the section 2 figures: cash versus `current_balance`, assets versus equity, the extra-income pairs, the four invoices, and on-hand versus purchase minus sales. It writes nothing.
- Add a PHPUnit fixture, not pointed at the live database, where a sale, a purchase, an expense, an extra income, and a fund are posted and the identity in section 4.1 holds to the paisa. This test fails on the current controllers. That failure is the starting line.
- Keep `tests/Feature/ProductAnalysisTest.php` running until phase 3 replaces the formula. Do not change production math in this phase.

Acceptance: the audit command matches the amounts in section 2 against `mousumi_pos`. The new identity test fails for the current double write and passes once phase 1 posts through the services.

### Phase 1 — Ledger services, still one database

- Add `business_date` and backfill it from the existing `date` columns, and from `DATE(created_at)` for sales. Do this before switching filters.
- Add `source_type` and `source_id` on `bank_transactions`, nullable at first.
- Implement the cash service and the stock service. Route new POS sales, `SaleCreator`, stock receipts, funds, expenses, and extra income through them. Delete the second `current_balance` write in those controllers. The observer either becomes the cash service or is removed so there is only one writer.
- Recompute sale totals on the server. Write a `sale_payments` row for every tender and point the cash row at it.
- Store `unit_cost` and `cogs_amount` on new sale lines.
- Fix sale update and void to post reversals through the two services. Persist nothing to `bank_account_id` on `sales`.
- Align the credit-limit rule with section 4.4. Lock the customer row.
- Stop matching cash rows by amount and date.

Acceptance: the fixture identity test passes. A new fund, expense, or extra income changes `current_balance` by the amount once. Editing an extra-income amount changes the linked bank row. A new sale’s on-hand equals the movement sum. Two concurrent sales of the last unit cannot both succeed.

### Phase 2 — Repair this shop’s history

Dry-run first. Apply only with a backup of `mousumi_pos` taken outside this plan’s phase 0 command.

Do not run `stocks:recalculate`, `stock:recalculate-historical`, `stock:populate-movements`, or `sales:fix-payments`.

1. **Extra income on 25 February 2025 and the 39.94 rounding row.** The document and the bank row must carry the same amount. Which amount is true is a business confirmation (bank statement versus what was typed into the form). The code bug explains the split; it does not choose the winner. After the choice, void or amend through the cash service so both sides match. Expected effect on the 555.06 gap: it moves by 490.06 once these agree.
2. **Invoices `INV-20250917-0036` (40), `INV-20250106-0049` (20), and `INV-20250601-0011` (5).** Link each receipt to a `sale_payments` row. Set `sales.paid` from the payment, or correct the bank row, after the same confirmation. `INV-20250703-0007` already has a 20.00 reduction row; link that `out` to the sale as a payment adjustment so reports stop classifying it by its sentence.
3. **Cash anchor.** Today’s on-screen `current_balance` is 1,098,520.49. The live transaction list sums to 245,594.47. The difference is 852,926.02. Both cannot be the Prime Bank passbook. Do not overwrite `current_balance` until the passbook is compared.
   - If the passbook matches the screen, leave 1,098,520.49 in place. Insert one dated cash adjustment (and the equity side of that adjustment) so every report ends on that screen balance. The adjustment is the record of double postings and of deletes that put money back on the screen but left the bank row in place. It is not typed in as a fake sales receipt.
   - If the passbook matches the transaction sum, change the screen to that sum and post no 852,926 adjustment.
   - `running_balance` is then rebuilt so the last row equals the chosen balance.
4. **Stock anchor.** Do not change today’s on-hand. For every live product the latest `available_quantity` already equals purchases minus sale lines, and that quantity stays. Rebuild `stock_movements` so their signed sum equals that same on-hand. Set historical `cogs_amount` from the weighted average as of each sale’s business date. If a product’s old rows cannot be made to add up to today’s quantity, post one stock adjustment on the earliest broken day and leave today’s quantity untouched. Do not run `stocks:recalculate`. The 3,181-unit gap in the old `quantity` column is not applied to on-hand.
5. **Deleted sales 93 (600) and 286 (25).** The 600 bank receipt is already soft-deleted (4 February 2025). Confirm the 25 has no live receipt. Leave the soft-deleted headers in place. Do not increment `quantity` again.

Acceptance: latest on-hand per product is unchanged. `current_balance` is unchanged unless the passbook showed it was the wrong one. After the agreed cash adjustment, the transaction chain ends on that same balance and the balance sheet difference is 0.00. The 555 constant is gone. Any remaining plug is a named adjustment row, not a number buried in PHP.

### Phase 3 — Reports

Replace the report controllers’ private formulas with the statement service.

- Balance sheet: section 4.1. Delete the 555, the hardcoded customer due, and the `2024-12-29` start. Show a difference line when the sides differ. Do not absorb it into fixed assets.
- Income and expenditure: same profit. Grand total is income, or it is removed. It is not the expense total painted twice. Fixed-asset category lines stay out of operating expenses once those rows have been moved (phase 4). Until they move, exclude category name `Fixed Asset` in one place inside the statement service, not in three controllers.
- Dashboard: same profit and the same cash sum as the balance sheet, including inactive accounts when they have historical rows.
- Product analysis: quantity and value from movements; profit from stored `cogs_amount`; no `max(0, …)` on the accounting export. The screen may still flag negatives.
- Bank report and receipt-and-payment: group by `source_type`. Opening balance is the sum before `from_date`. The account filter applies to every total on the page. All accounts are available, not only id 1.
- Sales report and customer sales: collections by payment `business_date`. Invoice totals by sale `business_date`. Previous due is reconstructed, not the current `sales.due`.
- Stock report: on-hand at `to_date` from movements. The sales footer sums a field the server actually sends.

Rewrite `ProductAnalysisTest` to the stored-cost examples. Add a test that the balance sheet balances on the fixture, including a credit sale, a later receipt, a backdated expense, and a return.

Acceptance: for one fixture month, dashboard profit, income-statement profit, and the change in balance-sheet retained earnings are the same number. The live audit still balances after phase 2.

### Phase 4 — Purchases, suppliers, returns, fixed assets

Add these as new postings through the phase 1 services.

- Supplier, purchase, purchase line, supplier payment. Stock increases by previous on-hand plus the received quantity. A credit purchase increases supplier payable and does not post cash. A payment requires `purchase_id` when it settles an invoice and reduces `due_amount` in the same transaction. `suppliers.current_balance` is recomputed from documents.
- Sale return against a sale line, quantity limited to quantity sold minus quantity already returned. Stock comes back at `sale_items.unit_cost`. Refund is either cash `out` linked to the return, or a reduction of the customer’s open due. The original sale’s line quantities stay; the return is a separate document. Profit for the period includes the reversal.
- Fixed assets and asset items. Migrate the 10 `Fixed Asset` expense rows (346,750.00, from the security deposit on 30 December 2024 through the scale on 30 June 2025) into asset items, and point each at the existing expense bank row. After that, operating expenses no longer exclude a category by name, because those rows are no longer expenses. Do not subtract 555 during the migration.
- The old stock-receipt screen becomes “receive against a purchase” or posts a purchase document itself, so stock cost and cash still share one source id.

Acceptance: fixture with a credit purchase, a partial supplier payment, a sale, and a partial return balances. Returned units add back the original unit cost. A fixed-asset purchase reduces cash, increases fixed assets, and does not change retained earnings.

### Phase 5 — Users and the storefront

Tenancy is not this phase. Section 6 stands: one shop, hardcoded name and address.

- Shop user management with enforced roles. The live account named Super Admin is stored with `role_id` 1 (`admin`), not role 3 (`super-admin`). Both are users of this same shop.
- The layout link to `/settings` currently has no route. Either add a small settings screen or remove the link. Name and address do not have to become editable.
- Pending public orders stay out of stock until approval, then post through `SaleCreator` (one cash add, one payment row, one stock movement per line).
- `customers.branch_code` and `branch_name` stay optional text for the external MIS list, labeled as customer office so they are not read as a second shop. Do not build stock by those strings.

Excel export can be added once the PDF figures come from the statement service. Export is not a separate accounting path.

Branch-scoped stock and cash are out of scope. This shop has one stock pool and one cash account.

## 9. Order of work and what this plan refuses

1. Phase 0 safety and tests.
2. Phase 1 posting rules for the modules that exist today.
3. Phase 2 repair of `mousumi_pos`, with the three February 2025 income amounts and the three open invoice differences confirmed before they are rewritten.
4. Phase 3 reports, deleting the plugs.
5. Phase 4 documents TrustCash added, posted correctly.
6. Phase 5 users and storefront, still one database.

The existing repair commands are not a phase. The TrustCash purchase and return stock snapshots are not a phase. A `− 555` in a new file is not a phase.

The only business confirmations this plan cannot compute are the true amounts for the 25 February 2025 extra-income lines (document versus bank), whether the 39.94 rounding adjustment was real cash, and whether the 40, 20, and 5 invoice differences should be corrected on the invoice or on the bank row. Everything else in the 555.06 gap and the 852,926.02 balance-cache gap is accounted for by the code paths above.
