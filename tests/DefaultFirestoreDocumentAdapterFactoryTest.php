<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\DefaultFirestoreDocumentAdapterFactory;
use ChristianBrown\KeyValueStore\FirestoreDocumentAdapter;
use ChristianBrown\KeyValueStore\FirestoreDocumentAdapterInterface;
use Google\Cloud\Firestore\CollectionReference;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\FirestoreClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultFirestoreDocumentAdapterFactory::class)]
#[UsesClass(FirestoreDocumentAdapter::class)]
final class DefaultFirestoreDocumentAdapterFactoryTest extends TestCase
{
    /**
     * @throws MockObjectException
     */
    public function testCreate(): void
    {
        $documentReference = self::createStub(DocumentReference::class);

        $collection = self::createMock(CollectionReference::class);
        $collection->expects(self::once())
            ->method('document')
            ->with('my-key')
            ->willReturn($documentReference);

        $client = self::createMock(FirestoreClient::class);
        $client->expects(self::once())
            ->method('collection')
            ->with('kv')
            ->willReturn($collection);

        $adapter = (new DefaultFirestoreDocumentAdapterFactory())->create($client, 'kv', 'my-key');

        self::assertInstanceOf(FirestoreDocumentAdapterInterface::class, $adapter);
    }
}
