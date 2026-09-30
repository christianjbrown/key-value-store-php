<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

interface FirestoreDocumentAdapterInterface
{
    /**
     * The document's fields, or null when the document does not exist.
     *
     * @return null|array<array-key, mixed>
     */
    public function getFields(): ?array;

    /**
     * Replaces the document's fields.
     *
     * @param array<string, mixed> $fields
     */
    public function setFields(array $fields): void;
}
