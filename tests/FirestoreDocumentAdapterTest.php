<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\FirestoreDocumentAdapter;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\DocumentSnapshot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;

#[CoversClass(FirestoreDocumentAdapter::class)]
final class FirestoreDocumentAdapterTest extends TestCase
{
    /**
     * @throws MockObjectException
     */
    public function testGetFields(): void
    {
        $snapshot = self::createStub(DocumentSnapshot::class);
        $snapshot->method('exists')
            ->willReturn(true);
        $snapshot->method('data')
            ->willReturn(['value' => 'test-value']);

        $documentReference = self::createStub(DocumentReference::class);
        $documentReference->method('snapshot')
            ->willReturn($snapshot);

        self::assertSame(['value' => 'test-value'], (new FirestoreDocumentAdapter($documentReference))->getFields());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetFieldsNotExists(): void
    {
        $snapshot = self::createStub(DocumentSnapshot::class);
        $snapshot->method('exists')
            ->willReturn(false);

        $documentReference = self::createStub(DocumentReference::class);
        $documentReference->method('snapshot')
            ->willReturn($snapshot);

        self::assertNull((new FirestoreDocumentAdapter($documentReference))->getFields());
    }

    /**
     * @throws MockObjectException
     */
    public function testSetFields(): void
    {
        $documentReference = self::createMock(DocumentReference::class);
        $documentReference->expects(self::once())
            ->method('set')
            ->with(['value' => 'test-value'])
            ->willReturn([]);

        (new FirestoreDocumentAdapter($documentReference))->setFields(['value' => 'test-value']);
    }
}
