<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lead> */
class LeadFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'external_id' => fake()->bothify('LD-######'),
            'created_at' => '2026-01-01 12:00:00',
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->numerify('38067#######'),
            'email' => fake()->safeEmail(),
            'city' => fake()->city(),
            'source' => 'Website',
            'utm_campaign' => null,
            'product' => 'Сайт',
            'budget_uah' => '23700.00',
            'status' => 'new',
            'manager' => null,
            'comment' => null,
            'next_contact_at' => null,
        ];
    }
}
