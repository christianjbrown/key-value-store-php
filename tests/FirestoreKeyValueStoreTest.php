<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\FirestoreDocumentAdapterInterface;
use ChristianBrown\KeyValueStore\FirestoreKeyValueStore;
use ChristianBrown\KeyValueStore\FirestoreKeyValueStoreInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

#[CoversClass(FirestoreKeyValueStore::class)]
final class FirestoreKeyValueStoreTest extends TestCase
{
    private const int NOW = 1_700_000_000;

    /**
     * @throws MockObjectException
     */
    public function testGetTtlAbsent(): void
    {
        $store = new FirestoreKeyValueStore($this->documentWithFields([]), new MockClock('@1700000000'));

        self::assertNull($store->getTtl());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetTtlNotExists(): void
    {
        $store = new FirestoreKeyValueStore($this->documentWithFields(null), new MockClock('@1700000000'));

        self::assertNull($store->getTtl());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetTtlPresent(): void
    {
        $expiresAt = self::NOW + 60;

        $store = new FirestoreKeyValueStore($this->documentWithFields([
            FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT => $expiresAt,
        ]), new MockClock('@1700000000'));

        self::assertSame(60, $store->getTtl());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueExpired(): void
    {
        $store = new FirestoreKeyValueStore($this->documentWithFields([
            FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT => self::NOW - 60,
            FirestoreKeyValueStoreInterface::FIELD_VALUE => 'test-value',
        ]), new MockClock('@1700000000'));

        self::assertNull($store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueNotExists(): void
    {
        $store = new FirestoreKeyValueStore($this->documentWithFields(null), new MockClock('@1700000000'));

        self::assertNull($store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueNotExpiredNotStored(): void
    {
        $store = new FirestoreKeyValueStore($this->documentWithFields([
            FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT => self::NOW + 60,
            FirestoreKeyValueStoreInterface::FIELD_VALUE => null,
        ]), new MockClock('@1700000000'));

        self::assertNull($store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueNotStored(): void
    {
        $store = new FirestoreKeyValueStore($this->documentWithFields([]), new MockClock('@1700000000'));

        self::assertNull($store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueNoTtl(): void
    {
        $store = new FirestoreKeyValueStore($this->documentWithFields([
            FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT => null,
            FirestoreKeyValueStoreInterface::FIELD_VALUE => 'test-value',
        ]), new MockClock('@1700000000'));

        self::assertSame('test-value', $store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueValid(): void
    {
        $store = new FirestoreKeyValueStore($this->documentWithFields([
            FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT => self::NOW + 60,
            FirestoreKeyValueStoreInterface::FIELD_VALUE => 'test-value',
        ]), new MockClock('@1700000000'));

        self::assertSame('test-value', $store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testSetValueNoTtl(): void
    {
        $document = self::createMock(FirestoreDocumentAdapterInterface::class);
        $document->expects(self::once())
            ->method('setFields')
            ->with([
                FirestoreKeyValueStoreInterface::FIELD_VALUE => 'test-value',
                FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT => null,
            ]);

        $store = new FirestoreKeyValueStore($document, new MockClock('@1700000000'));

        self::assertSame($store, $store->setValue('test-value'));
    }

    /**
     * @throws MockObjectException
     */
    public function testSetValueTtl(): void
    {
        $document = self::createMock(FirestoreDocumentAdapterInterface::class);
        $document->expects(self::once())
            ->method('setFields')
            ->with(self::callback(
                static function (array $fields): bool {
                    if ('test-value' !== $fields[FirestoreKeyValueStoreInterface::FIELD_VALUE]) {
                        return false;
                    }

                    return self::NOW + 60 === $fields[FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT];
                },
            ));

        $store = new FirestoreKeyValueStore($document, new MockClock('@1700000000'));

        self::assertSame($store, $store->setValue('test-value', 60));
    }

    /**
     * @param null|array<array-key, mixed> $fields
     *
     * @throws MockObjectException
     */
    private function documentWithFields(?array $fields): FirestoreDocumentAdapterInterface
    {
        $document = self::createStub(FirestoreDocumentAdapterInterface::class);
        $document->method('getFields')
            ->willReturn($fields);

        return $document;
    }
}
