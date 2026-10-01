<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStore;
use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStoreExceptionInterface;
use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStoreInterface;
use ChristianBrown\KeyValueStore\SecretManagerClientException;
use ChristianBrown\KeyValueStore\SecretManagerClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversClass(GoogleSecretKeyValueStore::class)]
final class GoogleSecretKeyValueStoreTest extends TestCase
{
    /**
     * @throws MockObjectException
     */
    public function testGetValue(): void
    {
        $client = self::createMock(SecretManagerClientInterface::class);
        $client->expects(self::once())
            ->method('accessLatest')
            ->with('test/secret/path/here/versions/latest')
            ->willReturn('test-secret-value');

        $store = new GoogleSecretKeyValueStore($client, '/test/secret/path/here/');

        self::assertSame('test-secret-value', $store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueClientException(): void
    {
        $this->expectException(GoogleSecretKeyValueStoreExceptionInterface::class);
        $this->expectExceptionMessage(sprintf(GoogleSecretKeyValueStoreInterface::GET_VALUE_FAILED_SPRINTF, 'here'));

        $client = self::createStub(SecretManagerClientInterface::class);
        $client->method('accessLatest')
            ->willThrowException(new SecretManagerClientException('test-exception-message'));

        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        $store->getValue();
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueNoPayload(): void
    {
        $client = self::createStub(SecretManagerClientInterface::class);
        $client->method('accessLatest')
            ->willReturn(null);

        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        self::assertNull($store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testSetValue(): void
    {
        $client = self::createMock(SecretManagerClientInterface::class);
        $client->expects(self::once())
            ->method('addVersion')
            ->with('test/secret/path/here', 'test-secret-value');

        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        self::assertSame($store, $store->setValue('test-secret-value'));
    }

    /**
     * @throws MockObjectException
     */
    public function testSetValueClientException(): void
    {
        $this->expectException(GoogleSecretKeyValueStoreExceptionInterface::class);
        $this->expectExceptionMessage(sprintf(GoogleSecretKeyValueStoreInterface::SET_VALUE_FAILED_SPRINTF, 'here'));

        $client = self::createStub(SecretManagerClientInterface::class);
        $client->method('addVersion')
            ->willThrowException(new SecretManagerClientException('test-exception-message'));

        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        $store->setValue('test-secret-value');
    }
}
