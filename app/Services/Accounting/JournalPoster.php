<?php

namespace App\Services\Accounting;

use App\Models\AccAccount;
use App\Models\AccJournal;
use Illuminate\Support\Facades\DB;

/**
 * Centralized double-entry journal posting for accounting transactions.
 * Every source document (invoice, bill, expense, payment, depreciation)
 * routes through here to guarantee balanced entries.
 */
class JournalPoster
{
    public function nextNumber(string $prefix): string
    {
        $prefix = $prefix.'-'.now()->format('Ym').'-';
        $last = AccJournal::where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');

        $seq = 1;
        if ($last) {
            $seq = (int) substr($last, strlen($prefix)) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a journal with the given lines.
     *
     * @param  array  $lines  each: ['account_id' => int, 'debit' => float, 'credit' => float, 'memo' => ?string]
     */
    public function post(array $meta, array $lines): AccJournal
    {
        return DB::transaction(function () use ($meta, $lines) {
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($lines as $line) {
                $totalDebit += (float) ($line['debit'] ?? 0);
                $totalCredit += (float) ($line['credit'] ?? 0);
            }

            if (abs($totalDebit - $totalCredit) > 0.005) {
                throw new \RuntimeException('Jurnal tidak seimbang: debit '.$totalDebit.' vs kredit '.$totalCredit);
            }

            $journal = AccJournal::create([
                'number' => $meta['number'] ?? $this->nextNumber('JV'),
                'date' => $meta['date'],
                'source' => $meta['source'] ?? 'manual',
                'source_id' => $meta['source_id'] ?? null,
                'contact_id' => $meta['contact_id'] ?? null,
                'reference' => $meta['reference'] ?? null,
                'description' => $meta['description'] ?? null,
                'total' => $totalDebit,
                'is_posted' => true,
                'created_by' => $meta['created_by'] ?? auth()->id(),
            ]);

            foreach ($lines as $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);
                if ($debit == 0 && $credit == 0) {
                    continue;
                }
                $journal->lines()->create([
                    'account_id' => $line['account_id'],
                    'memo' => $line['memo'] ?? null,
                    'debit' => $debit,
                    'credit' => $credit,
                ]);
            }

            return $journal;
        });
    }

    /**
     * Remove a journal previously posted for a source document.
     */
    public function reverseForSource(string $source, int $sourceId): void
    {
        AccJournal::where('source', $source)->where('source_id', $sourceId)->each(function ($journal) {
            $journal->delete();
        });
    }

    public function accountId(string $code): ?int
    {
        return AccAccount::where('code', $code)->value('id');
    }
}
