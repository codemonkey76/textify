<?php

namespace Database\Factories;

use App\Enums\MessageStatus;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SmsMessage>
 */
class SmsMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone_number' => $this->faker->phoneNumber,
            'message' => $this->faker->text(160),
            'message_id' => strtoupper(Str::random(20)),
            'status' => MessageStatus::Pending,
            'account_id' => Account::factory(),
            'message_price' => $this->faker->randomFloat(2, 0, 10),
            'message_parts' => $this->faker->numberBetween(1, 5),
        ];
    }
}
