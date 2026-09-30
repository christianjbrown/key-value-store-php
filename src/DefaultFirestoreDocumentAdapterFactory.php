<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Google\Cloud\Firestore\FirestoreClient;

final class DefaultFirestoreDocumentAdapterFactory implements FirestoreDocumentAdapterFactoryInterface
{
    public function create(FirestoreClient $client, string $collection, string $documentId): FirestoreDocumentAdapterInterface
    {
        return new FirestoreDocumentAdapter($client->collection($collection)->document($documentId));
    }
}
