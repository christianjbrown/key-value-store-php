<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Psr\Clock\ClockInterface;

final class MemoryKeyValueStore implements MemoryKeyValueStoreInterface
{
    private ClockInterface $clock;
    private ?int $expiresAt = null;
    private ?string $value = null;

    public function __construct(ClockInterface $clock)
    {
        $this->clock = $clock;
    }

    public function getTtl(): ?int
    {
        if (null === $this->expiresAt) {
            return null;
        }

        return $this->expiresAt - $this->clock->now()->getTimestamp();
    }

    public function getValue(): ?string
    {
        if (null !== $this->expiresAt) {
            if ($this->expiresAt < $this->clock->now()->getTimestamp()) {
                return null;
            }
        }

        return $this->value;
    }

    public function setValue(?string $value, ?int $ttl = null): self
    {
        $this->expiresAt = null;
        if (null !== $ttl) {
            $this->expiresAt = $this->clock->now()->getTimestamp() + $ttl;
        }
        $this->value = $value;

        return $this;
    }
}
