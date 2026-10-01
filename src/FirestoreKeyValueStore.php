<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Psr\Clock\ClockInterface;

use function is_int;
use function is_string;

final class FirestoreKeyValueStore implements FirestoreKeyValueStoreInterface
{
    private ClockInterface $clock;
    private FirestoreDocumentAdapterInterface $document;

    public function __construct(FirestoreDocumentAdapterInterface $document, ClockInterface $clock)
    {
        $this->document = $document;
        $this->clock = $clock;
    }

    public function getTtl(): ?int
    {
        $fields = $this->document->getFields();
        if (null === $fields) {
            return null;
        }

        $expiresAt = self::readExpiresAt($fields);
        if (null === $expiresAt) {
            return null;
        }

        return $expiresAt - $this->clock->now()->getTimestamp();
    }

    public function getValue(): ?string
    {
        $fields = $this->document->getFields();
        if (null === $fields) {
            return null;
        }

        $expiresAt = self::readExpiresAt($fields);
        if (null !== $expiresAt) {
            if ($expiresAt < $this->clock->now()->getTimestamp()) {
                return null;
            }
        }

        $value = $fields[self::FIELD_VALUE] ?? null;
        if (!is_string($value)) {
            return null;
        }

        return $value;
    }

    public function setValue(?string $value, ?int $ttl = null): self
    {
        $expiresAt = null;
        if (null !== $ttl) {
            $expiresAt = $this->clock->now()->getTimestamp() + $ttl;
        }

        $this->document->setFields([
            self::FIELD_VALUE => $value,
            self::FIELD_EXPIRES_AT => $expiresAt,
        ]);

        return $this;
    }

    /**
     * @param array<array-key, mixed> $fields
     */
    private static function readExpiresAt(array $fields): ?int
    {
        $expiresAt = $fields[self::FIELD_EXPIRES_AT] ?? null;
        if (!is_int($expiresAt)) {
            return null;
        }

        return $expiresAt;
    }
}
