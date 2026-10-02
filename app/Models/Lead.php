<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'external_id', 'created_at', 'first_name', 'last_name', 'phone', 'email',
    'city', 'source', 'utm_campaign', 'product', 'budget_uah', 'status',
    'manager', 'comment', 'next_contact_at',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['budget_uah' => 'decimal:2', 'next_contact_at' => 'datetime'];
    }
}
