<?php

namespace App\Services\Communication\Support;

use InvalidArgumentException;

final readonly class ActorRef
{
    public function __construct(
        public string $type,
        public ?int $id,
    ) {
        if ($type === '') {
            throw new InvalidArgumentException('Actor type is required.');
        }

        if ($type !== 'system' && ($id === null || $id < 1)) {
            throw new InvalidArgumentException("Actor [{$type}] requires a positive id.");
        }
    }

    public static function user(int $id): self
    {
        return new self('user', $id);
    }

    public static function customer(int $id): self
    {
        return new self('customer', $id);
    }

    public static function supplier(int $id): self
    {
        return new self('supplier', $id);
    }

    public static function system(): self
    {
        return new self('system', null);
    }

    /**
     * @return array{type: string, id: int|null}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
        ];
    }
}
