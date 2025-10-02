<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('payroll_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('reference')->unique();
            $table->timestamp('scheduled_for')->nullable();
            $table->string('status')->default('draft');
            $table->text('metadata')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_batches');
    }
};
