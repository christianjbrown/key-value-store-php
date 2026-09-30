<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Google\Cloud\Firestore\DocumentReference;

final class FirestoreDocumentAdapter implements FirestoreDocumentAdapterInterface
{
    private DocumentReference $documentReference;

    public function __construct(DocumentReference $documentReference)
    {
        $this->documentReference = $documentReference;
    }

    /**
     * @return null|array<array-key, mixed>
     */
    public function getFields(): ?array
    {
        $snapshot = $this->documentReference->snapshot();
        if (!$snapshot->exists()) {
            return null;
        }

        return $snapshot->data();
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function setFields(array $fields): void
    {
        $this->documentReference->set($fields);
    }
}
