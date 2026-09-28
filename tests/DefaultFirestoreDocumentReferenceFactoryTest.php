<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\DefaultFirestoreDocumentReferenceFactory;
use Google\Cloud\Firestore\CollectionReference;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\FirestoreClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultFirestoreDocumentReferenceFactory::class)]
final class DefaultFirestoreDocumentReferenceFactoryTest extends TestCase
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

        $factory = new DefaultFirestoreDocumentReferenceFactory();

        self::assertSame($documentReference, $factory->create($client, 'kv', 'my-key'));
    }
}
