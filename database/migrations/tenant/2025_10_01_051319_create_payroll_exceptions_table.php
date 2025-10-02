<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('payroll_exceptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_batch_id');
            $table->uuid('employee_id')->nullable();
            $table->string('rule_id');
            $table->string('rule_set_id');
            $table->string('severity')->default('error');
            $table->string('status')->default('open');
            $table->string('origin')->default('validation');
            $table->string('assigned_to')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('message')->nullable();
            $table->text('context')->nullable();
            $table->text('metadata')->nullable();
            $table->timestamps();

            $table->index('payroll_batch_id');
            $table->index('employee_id');
            $table->index('status');
            $table->index('rule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_exceptions');
    }
};
