<?php

use App\Enums\DocumentStatus;
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
        Schema::create('archive_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 50);
            $table->string('year', 4)->nullable();
            $table->foreignId('document_type_id')->constrained('document_types')->onDelete('restrict');
            $table->foreignId('land_right_type_id')->constrained('land_right_types')->onDelete('restrict');
            $table->foreignId('storage_rack_id')->constrained('storage_racks')->onDelete('restrict');
            $table->string('range_number', 50)->nullable();
            $table->char('district_id', 7);
            $table->char('village_id', 10);
            $table->string('status', 20)->default(DocumentStatus::AVAILABLE->value);
            $table->string('file_path', 255)->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('district_id')
                ->references('id')
                ->on('districts')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('village_id')
                ->references('id')
                ->on('villages')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->index('document_number');
            $table->index('year');
            $table->index('district_id');
            $table->index('village_id');
            $table->index('status');
            $table->index('document_type_id');
            $table->index('land_right_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_documents');
    }
};
