<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exception_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_exception_id');
            $table->string('type');
            $table->string('locale', 5);
            $table->string('title');
            $table->text('body');
            $table->json('payload')->nullable();
            $table->timestamp('queued_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('payroll_exception_id')
                ->references('id')
                ->on('payroll_exceptions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exception_notifications');
    }
};
