<?php

namespace Database\Factories;

use App\Enums\LoanStatus;
use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use App\Models\ServiceActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentLoan>
 */
class DocumentLoanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'archive_document_id' => ArchiveDocument::factory(),
            'borrower_id' => User::factory()->employee(),
            'service_activity_id' => ServiceActivity::factory(),
            'approved_by' => null,
            'received_by' => null,
            'loan_date' => now()->toDateString(),
            'return_date' => null,
            'status' => LoanStatus::PENDING,
            'notes' => fake()->sentence(),
            'approved_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LoanStatus::PENDING,
            'return_date' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function borrowed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LoanStatus::BORROWED,
            'approved_by' => User::factory()->archivist(),
            'approved_at' => now(),
            'return_date' => null,
        ]);
    }

    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LoanStatus::RETURNED,
            'approved_by' => User::factory()->archivist(),
            'approved_at' => now()->subDays(2),
            'received_by' => User::factory()->archivist(),
            'return_date' => now()->toDateString(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LoanStatus::CANCELLED,
            'cancelled_at' => now(),
            'return_date' => null,
        ]);
    }
}
