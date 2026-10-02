<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use SplFileObject;

class ImportLeads
{
    public const array COLUMNS = [
        'external_id', 'created_at', 'first_name', 'last_name', 'phone', 'email',
        'city', 'source', 'utm_campaign', 'product', 'budget_uah', 'status',
        'manager', 'comment', 'next_contact_at',
    ];

    private const array NULLABLE = [
        'first_name', 'email', 'utm_campaign', 'budget_uah', 'manager', 'comment', 'next_contact_at',
    ];

    /** @return array{rows: int, seconds: float} */
    public function handle(string $path): array
    {
        $started = hrtime(true);
        $file = new SplFileObject($path, 'r');
        $file->setCsvControl(',', '"', '');
        $header = $file->fgetcsv();
        if (! is_array($header)) {
            $this->fail('Не вдалося прочитати заголовок CSV.');
        }
        $header[0] = str_starts_with($header[0] ?? '', "\xEF\xBB\xBF")
            ? substr($header[0], 3)
            : ($header[0] ?? '');

        if ($header !== self::COLUMNS) {
            $this->fail('Заголовок CSV має містити 15 колонок у порядку що відповідає файлу імпорту наданому в ТЗ');
        }

        $rows = DB::transaction(function () use ($file): int {
            $batch = [];
            $count = 0;
            $record = 1;
            $updatedAt = now()->format('Y-m-d H:i:s');

            while (! $file->eof()) {
                $values = $file->fgetcsv();
                $record++;

                if ($values === [null] && $file->eof()) {
                    break;
                }

                if (! is_array($values) || count($values) !== count(self::COLUMNS)) {
                    $this->fail("Рядок {$record}: очікується 15 колонок.");
                }

                $batch[] = [
                    ...$this->normalizeRow(array_combine(self::COLUMNS, $values)),
                    'updated_at' => $updatedAt,
                ];
                $count++;

                if (count($batch) === 50) {
                    DB::table('leads')->insert($batch);
                    $batch = [];
                }
            }

            if ($count === 0) {
                $this->fail('Файл не містить заявок для імпорту.');
            }

            if ($batch !== []) {
                DB::table('leads')->insert($batch);
            }

            return $count;
        });

        return ['rows' => $rows, 'seconds' => round((hrtime(true) - $started) / 1e9, 3)];
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array<string, string|null>
     */
    private function normalizeRow(array $row): array
    {
        foreach (self::NULLABLE as $column) {
            if ($row[$column] === '') {
                $row[$column] = null;
            }
        }

        if ($row['budget_uah'] !== null) {
            $row['budget_uah'] = str_replace(
                ["\u{00A0}", "\u{202F}", ' ', ','],
                ['', '', '', '.'],
                $row['budget_uah'],
            );
        }

        return $row;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
