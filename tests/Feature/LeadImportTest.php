<?php

namespace Tests\Feature;

use App\Actions\ImportLeads;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeadImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_page_is_public_and_uses_blade(): void
    {
        $this->get(route('home'))->assertViewIs('lead-import')->assertSee('Імпорт заявок');
    }

    public function test_import_saves_every_batch_and_normalizes_csv_data(): void
    {
        Queue::fake();
        $existing = Lead::factory()->create();
        $row = $this->row([
            'budget_uah' => "23\u{00A0}700",
            'comment' => "Коментар, із комою\nта новим рядком",
            'next_contact_at' => '2026-01-02 14:30:00',
        ]);
        $rows = array_fill(0, 49, $row);
        $rows[] = $this->row(['budget_uah' => "23\u{202F}700,50"]);
        $rows[] = $this->row(['first_name' => '', 'email' => '', 'budget_uah' => '']);

        $response = $this->post(route('leads.import'), ['file' => $this->csv($rows)]);

        $response->assertRedirect(route('home'))->assertSessionHas('import.rows', 51);
        $this->assertDatabaseCount('leads', 52);
        $this->assertModelExists($existing);
        $this->assertDatabaseHas('leads', [
            'external_id' => 'LD-000001', 'budget_uah' => 23700,
            'phone' => '038067123456', 'comment' => $row['comment'],
            'created_at' => '2026-01-01 12:00:00',
            'next_contact_at' => '2026-01-02 14:30:00',
        ]);
        $this->assertDatabaseHas('leads', ['external_id' => 'LD-000001', 'budget_uah' => 23700.50]);
        $this->assertDatabaseHas('leads', ['external_id' => 'LD-000001', 'first_name' => null, 'email' => null, 'budget_uah' => null]);
        $lead = Lead::query()->where('comment', $row['comment'])->firstOrFail();
        $this->assertSame('23700.00', $lead->getAttribute('budget_uah'));
        $this->assertSame('2026-01-02 14:30:00', $lead->getAttribute('next_contact_at')->format('Y-m-d H:i:s'));
        Queue::assertNothingPushed();
        $this->get(route('home'))->assertSee('Успішно')->assertSee('Записано рядків:');
    }

    public function test_invalid_row_rolls_back_the_entire_import(): void
    {
        $existing = Lead::factory()->create();
        $rows = array_fill(0, 50, $this->row());
        $invalid = $this->row();
        unset($invalid['next_contact_at']);
        $rows[] = $invalid;

        $this->from(route('home'))->post(route('leads.import'), ['file' => $this->csv($rows)])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['file' => 'Рядок 52: очікується 15 колонок.']);

        $this->assertDatabaseCount('leads', 1);
        $this->assertModelExists($existing);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function row(array $overrides = []): array
    {
        return array_replace([
            'external_id' => 'LD-000001', 'created_at' => '2026-01-01 12:00:00',
            'first_name' => 'Олександр', 'last_name' => 'Поліщук', 'phone' => '038067123456',
            'email' => 'test@example.com', 'city' => 'Київ', 'source' => 'Website',
            'utm_campaign' => '', 'product' => 'Сайт', 'budget_uah' => '23700',
            'status' => 'new', 'manager' => '', 'comment' => '', 'next_contact_at' => '',
        ], $overrides);
    }

    /** @param list<array<string, string>> $rows */
    private function csv(array $rows): UploadedFile
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ImportLeads::COLUMNS, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($stream, array_values($row), ',', '"', '');
        }
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return UploadedFile::fake()->createWithContent('leads.csv', $contents);
    }
}
