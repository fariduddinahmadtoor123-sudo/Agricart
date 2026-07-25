<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OnlineSaleOrdersStatusQueuedIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_composite_index_on_order_status_and_queued_at_exists(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND name = 'online_sale_orders_status_queued_idx'",
            );
            $this->assertCount(1, $rows);

            return;
        }

        if ($driver === 'mysql') {
            $rows = DB::select(
                'SHOW INDEX FROM online_sale_orders WHERE Key_name = ?',
                ['online_sale_orders_status_queued_idx'],
            );
            $this->assertNotEmpty($rows);

            return;
        }

        $this->markTestSkipped('Index assertion not implemented for driver: '.$driver);
    }
}
