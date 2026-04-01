<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AssignCustomerBranchesSeeder extends Seeder
{
    public function run(): void
    {
        $url = 'https://mis.mousumibd.org/api/public/branches';
        $resp = Http::timeout(20)->get($url);

        if (! $resp->ok()) {
            $this->command?->error("Failed to fetch branches from {$url} (HTTP {$resp->status()})");
            return;
        }

        $payload = $resp->json();
        $branches = $payload['data'] ?? [];

        if (! is_array($branches) || count($branches) === 0) {
            $this->command?->error('No branches found in API payload.');
            return;
        }

        // Pre-normalize branch names for matching
        $branchIndex = collect($branches)
            ->filter(fn ($b) => isset($b['name'], $b['code']))
            ->map(fn ($b) => [
                'name' => (string) $b['name'],
                'code' => (string) $b['code'],
                'needle' => Str::lower(trim((string) $b['name'])),
            ])
            // Prefer longer names first to avoid partial collisions
            ->sortByDesc(fn ($b) => strlen($b['needle']))
            ->values();

        // Exact-match map for name-based matching
        $branchByNeedle = $branchIndex
            ->keyBy('needle')
            ->all();

        // Common spelling/label aliases found in customer names -> API branch needle
        $aliases = [
            'atrai' => 'atrai',
            'kirtipur' => 'kritipur',
            'abadpokur' => 'abadpukur',
            'shaharpukur' => 'saharpukur',
            'cheragpur' => 'charagpur',
            'mohadevpur' => 'mohadebpur',
            'chawbaria' => 'chaubaria hat',
            'katkhoir' => 'katkhair',
            'noldanga' => 'naldanga',
            'somoshpara' => 'samaspara',
            'sharpukur' => 'saharpukur',
        ];

        $matched = 0;
        $skipped = 0;
        $already = 0;
        $nameMatched = 0;
        $addressMatched = 0;

        // Safety cleanup: if someone was assigned via a name that does NOT end in "Branch",
        // undo it (prevents cases like "Nachol Branch ECCCP Drought").
        $cleaned = Customer::query()
            ->whereNotNull('branch_code')
            ->whereRaw('LOWER(TRIM(name)) LIKE ?', ['% branch %'])
            ->whereRaw('LOWER(TRIM(name)) NOT LIKE ?', ['% branch'])
            ->where(function ($q) {
                $q->whereNull('address')->orWhere('address', '');
            })
            ->update(['branch_code' => null, 'branch_name' => null]);
        if ($cleaned) {
            $this->command?->warn("Cleaned {$cleaned} invalid branch assignments (name does not end with 'Branch').");
        }

        Customer::query()
            ->orderBy('id')
            ->chunkById(200, function ($customers) use ($branchIndex, $branchByNeedle, $aliases, &$matched, &$skipped, &$already, &$nameMatched, &$addressMatched) {
                foreach ($customers as $c) {
                    if ($c->branch_code) {
                        $already++;
                        continue;
                    }

                    // 1) Prefer name-based match for customers ending with "Branch"
                    $nameNeedle = $this->normalizeBranchNeedleFromCustomerName((string) $c->name);
                    if ($nameNeedle) {
                        if (isset($aliases[$nameNeedle])) {
                            $nameNeedle = $aliases[$nameNeedle];
                        }
                        $hit = $branchByNeedle[$nameNeedle] ?? null;
                        // tolerate minor spelling differences using "contains"
                        if (! $hit) {
                            $hit = $branchIndex->first(fn ($b) => $b['needle'] !== '' && str_contains($nameNeedle, $b['needle']));
                        }
                        // tolerate minor spelling differences using levenshtein distance
                        if (! $hit) {
                            $best = null;
                            $bestDist = 999;
                            foreach ($branchIndex as $b) {
                                $dist = levenshtein($nameNeedle, $b['needle']);
                                if ($dist < $bestDist) {
                                    $bestDist = $dist;
                                    $best = $b;
                                }
                            }
                            // Only accept close matches
                            if ($best && $bestDist <= 2) {
                                $hit = $best;
                            }
                        }
                        if ($hit) {
                            $c->forceFill([
                                'branch_code' => $hit['code'],
                                'branch_name' => $hit['name'],
                            ])->save();

                            $matched++;
                            $nameMatched++;
                            $this->command?->line("Name matched customer #{$c->id} {$c->name} → {$hit['name']} ({$hit['code']})");
                            continue;
                        }
                    }

                    // 2) Fallback: address-based match
                    $hay = Str::lower(trim((string) ($c->address ?? '')));
                    if ($hay === '') {
                        $skipped++;
                        continue;
                    }

                    $hit = $branchIndex->first(function ($b) use ($hay) {
                        // simple contains match; keeps it deterministic
                        return $b['needle'] !== '' && str_contains($hay, $b['needle']);
                    });

                    if (! $hit) {
                        $skipped++;
                        continue;
                    }

                    $c->forceFill([
                        'branch_code' => $hit['code'],
                        'branch_name' => $hit['name'],
                    ])->save();

                    $matched++;
                    $addressMatched++;
                    $this->command?->line("Address matched customer #{$c->id} {$c->name} → {$hit['name']} ({$hit['code']})");
                }
            });

        $this->command?->info("Done. matched={$matched}, already_assigned={$already}, skipped={$skipped}");
        $this->command?->info("Breakdown: name_matched={$nameMatched}, address_matched={$addressMatched}");
        $this->command?->info('Tip: matches try customer.name ("... Branch") first, then fallback to customer.address.');
    }

    private function normalizeBranchNeedleFromCustomerName(string $name): ?string
    {
        $s = Str::lower(trim($name));

        // Only match when the LAST word is "branch"
        if (! str_ends_with($s, ' branch')) {
            return null;
        }

        // remove trailing "branch" and anything like it (exact end)
        $s = trim(substr($s, 0, -strlen(' branch')));

        $s = trim(preg_replace('/\s+/', ' ', $s));

        return $s !== '' ? $s : null;
    }
}

