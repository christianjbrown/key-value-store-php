<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Google\Cloud\Firestore\FirestoreClient;
use Psr\Clock\ClockInterface;

final class FirestoreKeyValueStoreFactory implements FirestoreKeyValueStoreFactoryInterface
{
    private ClockInterface $clock;
    private FirestoreDocumentAdapterFactoryInterface $documentAdapterFactory;

    public function __construct(FirestoreDocumentAdapterFactoryInterface $documentAdapterFactory, ClockInterface $clock)
    {
        $this->documentAdapterFactory = $documentAdapterFactory;
        $this->clock = $clock;
    }

    public function create(FirestoreClient $client, string $collection, string $documentId): FirestoreKeyValueStoreInterface
    {
        return new FirestoreKeyValueStore($this->documentAdapterFactory->create($client, $collection, $documentId), $this->clock);
    }
}
