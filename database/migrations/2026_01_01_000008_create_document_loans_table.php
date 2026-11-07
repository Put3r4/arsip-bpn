<?php

use App\Enums\LoanStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_document_id')->constrained('archive_documents')->onDelete('cascade');
            $table->foreignId('borrower_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('service_activity_id')->constrained('service_activities')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->date('loan_date');
            $table->date('return_date')->nullable();
            $table->string('status', 20)->default(LoanStatus::PENDING->value);
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('borrower_id');
            $table->index('archive_document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_loans');
    }
};
