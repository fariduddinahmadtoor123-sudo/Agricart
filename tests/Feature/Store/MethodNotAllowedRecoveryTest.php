<?php

namespace Tests\Feature\Store;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MethodNotAllowedRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_does_not_expose_the_livewire_update_endpoint(): void
    {
        $this->get(route('store.home'))
            ->assertOk()
            ->assertDontSee('data-update-uri="/livewire/update"', false);
    }

    public function test_staff_register_still_loads_livewire(): void
    {
        $this->get(route('auth.staff.register'))
            ->assertOk()
            ->assertSee('data-update-uri="/livewire/update"', false);
    }

    public function test_get_on_livewire_update_does_not_show_a_405_page(): void
    {
        $this->get('/livewire/update')
            ->assertNoContent();
    }

    public function test_get_on_cart_add_returns_to_the_cart(): void
    {
        $this->get('/cart/add')
            ->assertRedirect('/cart');
    }

    public function test_post_on_a_get_only_store_page_becomes_a_get(): void
    {
        $this->post('/')
            ->assertRedirect('/')
            ->assertStatus(303);
    }
}
