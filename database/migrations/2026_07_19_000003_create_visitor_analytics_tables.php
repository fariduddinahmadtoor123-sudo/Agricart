<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_visitors', function (Blueprint $table): void {
            $table->id();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('first_seen_at')->index();
            $table->timestamp('last_seen_at')->index();
            $table->char('first_country_code', 2)->nullable();
            $table->string('first_country_name')->nullable();
            $table->string('first_city')->nullable();
            $table->string('first_device_type', 24)->nullable();
            $table->boolean('opted_out')->default(false);
            $table->timestamps();
        });

        Schema::create('analytics_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('visitor_id')->nullable()->index();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('started_at')->index();
            $table->timestamp('last_seen_at')->index();
            $table->string('landing_route', 120)->nullable();
            $table->char('country_code', 2)->nullable()->index();
            $table->string('country_name')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('masked_ip', 80)->nullable();
            $table->char('ip_hash', 64)->nullable()->index();
            $table->string('device_type', 24)->default('unknown')->index();
            $table->string('browser', 80)->nullable();
            $table->string('operating_system', 80)->nullable();
            $table->string('referrer_host')->nullable();
            $table->string('traffic_source', 50)->default('direct')->index();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->unsignedInteger('page_views')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->timestamps();
        });

        Schema::create('analytics_page_views', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('visitor_id')->nullable()->index();
            $table->unsignedBigInteger('session_id')->nullable()->index();
            $table->timestamp('occurred_at')->index();
            $table->string('route_name', 120)->index();
            $table->string('page_type', 32)->default('other');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_slug')->nullable();
            $table->unsignedSmallInteger('response_status')->default(200);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('referrer_host')->nullable();
            $table->string('traffic_source', 50)->default('direct');
            $table->timestamps();

            $table->index(['session_id', 'occurred_at'], 'analytics_views_session_time');
            $table->index(['page_type', 'entity_id', 'occurred_at'], 'analytics_views_entity_time');
            $table->index(['traffic_source', 'occurred_at'], 'analytics_views_source_time');
        });

        Schema::create('analytics_rollups', function (Blueprint $table): void {
            $table->id();
            $table->string('grain', 12);
            $table->date('period_start');
            $table->string('dimension_type', 32)->default('all');
            $table->string('dimension_key', 191)->default('_all');
            $table->string('dimension_label')->nullable();
            $table->unsignedBigInteger('page_views')->default(0);
            $table->unsignedBigInteger('visitors')->default(0);
            $table->unsignedBigInteger('sessions')->default(0);
            $table->unsignedBigInteger('new_visitors')->default(0);
            $table->timestamps();

            $table->unique(
                ['grain', 'period_start', 'dimension_type', 'dimension_key'],
                'analytics_rollups_dimension_unique',
            );
            $table->index(['dimension_type', 'period_start'], 'analytics_rollups_dimension_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_rollups');
        Schema::dropIfExists('analytics_page_views');
        Schema::dropIfExists('analytics_sessions');
        Schema::dropIfExists('analytics_visitors');
    }
};
