<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('external_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('salary', 12, 2)->default(0);
            $table->string('currency', 3)->default('AED');
            $table->text('metadata')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
