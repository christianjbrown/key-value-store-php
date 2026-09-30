<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Google\Cloud\Firestore\FirestoreClient;

interface FirestoreDocumentAdapterFactoryInterface
{
    public function create(FirestoreClient $client, string $collection, string $documentId): FirestoreDocumentAdapterInterface;
}
