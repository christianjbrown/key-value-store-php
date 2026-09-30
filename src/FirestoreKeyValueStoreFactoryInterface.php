<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Google\Cloud\Firestore\FirestoreClient;

interface FirestoreKeyValueStoreFactoryInterface
{
    public function create(FirestoreClient $client, string $collection, string $documentId): FirestoreKeyValueStoreInterface;
}
