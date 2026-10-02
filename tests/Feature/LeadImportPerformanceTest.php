<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadImportPerformanceTest extends TestCase
{
    public function test_provided_csv_imports_all_rows_into_disk_database_within_30_seconds(): void
    {
        $path = getenv('LEADS_CSV_PATH');
        if (! $path) {
            $this->markTestSkipped('Set LEADS_CSV_PATH to the supplied CSV to run the benchmark.');
        }

        $database = tempnam(sys_get_temp_dir(), 'lead-import-test-');
        $previousConnection = DB::getDefaultConnection();
        config(['database.connections.import_benchmark' => [
            'driver' => 'sqlite', 'database' => $database, 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('import_benchmark');

        try {
            Artisan::call('migrate', [
                '--database' => 'import_benchmark',
                '--path' => 'database/migrations/2026_10_01_173659_create_leads_table.php',
                '--force' => true, '--no-interaction' => true,
            ]);
            $upload = new UploadedFile($path, 'leads.csv', 'text/csv', null, true);
            $started = hrtime(true);

            $response = $this->post(route('leads.import'), ['file' => $upload]);

            $seconds = (hrtime(true) - $started) / 1e9;
            $response->assertRedirect(route('home'))->assertSessionHasNoErrors()->assertSessionHas('import.rows', 100000);
            $this->assertDatabaseCount('leads', 100000);
            $this->assertSame(99795, DB::table('leads')->distinct()->count('external_id'));
            $this->assertLessThan(30, $seconds);
            fwrite(STDERR, sprintf("\nCSV benchmark: 100000 rows, %.3f seconds, max_execution_time=%s, peak_memory=%.1f MiB\n", $seconds, ini_get('max_execution_time'), memory_get_peak_usage(true) / 1048576));
        } finally {
            DB::setDefaultConnection($previousConnection);
            DB::purge('import_benchmark');
            unlink($database);
        }
    }
}
