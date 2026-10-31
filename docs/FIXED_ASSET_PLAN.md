# Fixed assets, separate from expenses

Only this change. Stock valuation, the bank-balance cache, and tenancy stay as they are.

Mousumi records assets as expenses in the category named `Fixed Asset`. TrustCash keeps a head (`fixed_assets`) and the things bought under it (`fixed_asset_items`), and the balance sheet reads those items. This plan takes that shape. It does not copy TrustCash’s branch column, its description-text cash sorting, or the `− 555` plug.

## What is in the books today

Category id 1, name `Fixed Asset`. Ten live expenses, total **346,750.00**:

| Date | Amount | Description |
|---|---:|---|
| 2024-12-30 | 150,000.00 | Dokan Security |
| 2025-01-01 | 120,500.00 | Interior Design |
| 2025-01-01 | 17,150.00 | CPU |
| 2025-01-01 | 14,000.00 | Pos Printer |
| 2025-01-01 | 3,750.00 | Barcode Scanner |
| 2025-01-01 | 3,000.00 | Fan |
| 2025-02-02 | 30,000.00 | Pos Software Customizable |
| 2025-02-02 | 4,600.00 | Chair Purchase |
| 2025-05-21 | 1,250.00 | Intercom Telephone |
| 2025-06-30 | 2,500.00 | RFL Scale 30kg |

Each row already has a bank withdrawal described `Expense: {description}`. The balance sheet shows fixed assets as that sum **minus 555**, so the line is **346,195.00**. The 555 is not one of these purchases. Net profit already skips this category (`BalanceSheetController`, `IncomeExpenditureController`, `DashboardController`).

## Tables

No `branch_id`. This shop has no branches table.

`fixed_assets`: `asset_code` unique, `name`, `status` (`active` or `disposed`), `description`, `created_by`, timestamps, soft deletes.

`fixed_asset_items`: `fixed_asset_id`, `item_name`, `purchase_date`, `purchase_price` decimal(15,2), `current_value` decimal(15,2), `bank_account_id`, `bank_transaction_id`, `expense_id` nullable (the old expense this item came from), `status` (`active`, `disposed`, `damaged`), `description`, `created_by`, timestamps, soft deletes.

`current_value` starts equal to `purchase_price`. There is no depreciation in this change. The balance sheet sums `current_value` for items with `status = active` and `purchase_date` on or before the end date.

## Screen

Admin menu entry **স্থায়ী সম্পদ**, route `/admin/fixed-assets`.

- A head (কম্পিউটার, ডেকোরেশন, আসবাব) and items under it.
- Adding an item with a price and a bank account creates one `bank_transactions` row, type `out`, description `Fixed Asset: {item} ({head})`, and stores that row’s id on the item.
- The controller does not also subtract `current_balance`. The existing bank observer does that once. TrustCash’s screen is the model for this part. Mousumi’s expense screen subtracts a second time; the new screen must not.
- Editing the price edits that same bank row’s amount once.
- Deleting an item soft-deletes that same bank row once.
- Code `FA-0001` upward, same as TrustCash.
- Creating or renaming an expense category to “Fixed Asset” or “স্থায়ী সম্পদ” is rejected, as in TrustCash’s `ExpenseCategoryController`.

## Move the ten expenses

Do this in one transaction, after a database backup. Do not insert a new bank withdrawal for these ten. The money has already left the account. A second `out` would change today’s bank balance.

For each of the ten rows:

1. Find the live bank row with the same account, date, amount, and description `Expense: {that description}`.
2. If the row is missing or two rows match, stop and leave that expense in place.
3. Create one asset head named from the description, and one item. `purchase_price` and `current_value` are the expense amount. `expense_id` and `bank_transaction_id` are stored. The bank description is left as `Expense: …` so the old cash grid, which still sorts by that sentence, keeps the same running total.
4. Soft-delete the expense.

After that, the asset register totals **346,750.00**. `bank_accounts.current_balance` is unchanged. No stock row is written.

The old category stays in the table so the history is visible, and it is removed from the expense form’s category list. New asset purchases go only through `/admin/fixed-assets`.

## Reports

| Place | After this change |
|---|---|
| Balance sheet fixed-asset line | Sum of active items’ `current_value`. The `− 555` in `BalanceSheetController` is removed. With these ten items the line becomes **346,750.00**, not 346,195.00. Bank balance and stock value on that sheet are not recalculated by this work. |
| Balance sheet breakdown | One row per asset head, as TrustCash already shows. |
| Net profit, income and expenditure, dashboard | Unchanged. Those expenses were already left out of profit. Soft-deleting them does not add them back. |
| Expense list | The “Fixed Asset Expenses” card goes away. The ten rows no longer appear. |
| Receipt and payment | Today the payment side adds every expense, including these 346,750. After the expenses are soft-deleted, that report must add the asset items’ `purchase_price` for the same dates as its own fixed-asset section. Payment total for a full history stays the same number, split into operating expenses and asset purchases. |

The daily bank grid will still show the old ten under “Expense”, because their bank sentences are not rewritten. New purchases, worded `Fixed Asset:`, will not appear on that grid until that grid is taught the new wording. That grid change is not part of this work. The balance sheet and the asset screen do not depend on the wording. They use `bank_transaction_id`.

## Done when

- `/admin/fixed-assets` lists the ten items and totals 346,750.00.
- The expense list no longer includes them, and a new expense cannot be saved in that category.
- A new test purchase reduces the bank screen balance once, and deleting it puts that amount back once.
- The balance sheet fixed-asset line equals the register (346,750.00 for an end date on or after 30 June 2025).
- Today’s stock quantities are the same as before the migration.
