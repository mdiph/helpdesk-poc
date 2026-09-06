<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable()->unique();  // TKT-000123, set after insert
            $table->string('title');
            $table->text('description');

            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->string('priority')->default('medium');       // low | medium | high | urgent
            $table->string('status')->default('open');           // open | in_progress | pending | resolved | closed
            $table->string('support_tier')->default('l1');       // l1 | l2

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->text('resolution')->nullable();

            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('support_tier');
            $table->index('assigned_to');
            $table->index('created_by');
            $table->index('created_at');
            $table->index(['support_tier', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
