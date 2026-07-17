<?php

namespace Tests\Feature\Catalog;

use App\Services\Catalog\ControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ControlValueLengthTest extends TestCase
{
    use RefreshDatabase;

    public function test_control_values_allow_up_to_one_thousand_characters(): void
    {
        $value = str_repeat('A', 900);

        $control = app(ControlService::class)->create([
            'name_en' => 'Mesh Size',
            'name_ur' => 'میش سائز',
            'value_en' => $value,
            'value_ur' => $value,
        ]);

        $this->assertSame($value, $control->value_en);
        $this->assertSame($value, $control->value_ur);
    }

    public function test_control_values_still_reject_more_than_one_thousand_characters(): void
    {
        $this->expectException(ValidationException::class);

        app(ControlService::class)->create([
            'name_en' => 'Mesh Size',
            'name_ur' => 'میش سائز',
            'value_en' => str_repeat('A', 1001),
            'value_ur' => 'Valid value',
        ]);
    }
}
