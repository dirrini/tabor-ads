<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return ['name' => fake()->words(3, true), 'kind' => 'standard', 'status' => 'active', 'public_id' => Str::uuid()];
    }
}
