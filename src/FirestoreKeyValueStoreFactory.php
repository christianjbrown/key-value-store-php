<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Google\Cloud\Firestore\FirestoreClient;

final class FirestoreKeyValueStoreFactory implements FirestoreKeyValueStoreFactoryInterface
{
    private FirestoreDocumentAdapterFactoryInterface $documentAdapterFactory;

    public function __construct(FirestoreDocumentAdapterFactoryInterface $documentAdapterFactory)
    {
        $this->documentAdapterFactory = $documentAdapterFactory;
    }

    public function create(FirestoreClient $client, string $collection, string $documentId): FirestoreKeyValueStoreInterface
    {
        return new FirestoreKeyValueStore($this->documentAdapterFactory->create($client, $collection, $documentId));
    }
}
