<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fx_learn_economic_events', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->string('external_id', 80)->nullable()->index();
            $table->string('title');
            $table->string('ccy', 8)->index();
            $table->string('country', 16)->nullable();
            $table->string('impact', 8)->default('med')->index();
            $table->unsignedBigInteger('at_ms')->index();
            $table->date('event_date')->index();
            $table->string('previous', 64)->nullable();
            $table->string('forecast', 64)->nullable();
            $table->string('actual', 64)->nullable();
            $table->string('unit', 32)->nullable();
            $table->string('source', 24)->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['ccy', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_learn_economic_events');
    }
};
