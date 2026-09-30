<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\FirestoreDocumentAdapterFactoryInterface;
use ChristianBrown\KeyValueStore\FirestoreDocumentAdapterInterface;
use ChristianBrown\KeyValueStore\FirestoreKeyValueStore;
use ChristianBrown\KeyValueStore\FirestoreKeyValueStoreFactory;
use Google\Cloud\Firestore\FirestoreClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;

#[CoversClass(FirestoreKeyValueStoreFactory::class)]
#[UsesClass(FirestoreKeyValueStore::class)]
final class FirestoreKeyValueStoreFactoryTest extends TestCase
{
    /**
     * @throws MockObjectException
     */
    public function testCreate(): void
    {
        $client = self::createStub(FirestoreClient::class);

        $documentAdapterFactory = self::createMock(FirestoreDocumentAdapterFactoryInterface::class);
        $documentAdapterFactory->expects(self::once())
            ->method('create')
            ->with($client, 'kv', 'my-key')
            ->willReturn(self::createStub(FirestoreDocumentAdapterInterface::class));

        $store = (new FirestoreKeyValueStoreFactory($documentAdapterFactory))->create($client, 'kv', 'my-key');

        self::assertInstanceOf(FirestoreKeyValueStore::class, $store);
    }
}
