<?php

namespace Tests\Feature\Support;

use App\Support\FriendlyError;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FriendlyErrorTest extends TestCase
{
    public function test_mac_invalid_becomes_friendly_decrypt_message(): void
    {
        $message = FriendlyError::message(new DecryptException('The MAC is invalid.'));

        $this->assertSame(__('agricart.errors.decrypt'), $message);
        $this->assertStringNotContainsString('MAC', $message);
    }

    public function test_connection_errors_become_friendly(): void
    {
        $this->assertSame(
            __('agricart.errors.connection'),
            FriendlyError::message(new ConnectionException('cURL error 6: Could not resolve host: openrouter.ai')),
        );
    }

    public function test_unknown_errors_fall_back_to_generic(): void
    {
        $this->assertSame(
            __('agricart.errors.generic'),
            FriendlyError::message(new \RuntimeException('Undefined array key 7 in SomeInternal.php')),
        );
    }

    public function test_validation_messages_pass_through_unchanged(): void
    {
        $exception = ValidationException::withMessages(['name' => 'Please enter the category name.']);

        $this->assertSame('Please enter the category name.', FriendlyError::message($exception));
    }
}
