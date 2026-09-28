<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\FirestoreClient;

interface FirestoreDocumentReferenceFactoryInterface
{
    public function create(FirestoreClient $client, string $collection, string $documentId): DocumentReference;
}
