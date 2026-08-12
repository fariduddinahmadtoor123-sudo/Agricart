<?php

namespace Tests\Unit;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class AgricartDateTimeTest extends TestCase
{
    public function test_formats_utc_instant_as_pakistan_time_with_pkt_label(): void
    {
        $utc = Carbon::parse('2026-08-12 01:13:00', 'UTC');

        $this->assertSame('Asia/Karachi', agricart_display_timezone());
        $this->assertSame('2026-08-12 06:13 PKT', agricart_datetime($utc));
        $this->assertSame('06:13', agricart_datetime($utc, 'H:i', false));
        $this->assertSame('—', agricart_datetime(null));
    }
}
