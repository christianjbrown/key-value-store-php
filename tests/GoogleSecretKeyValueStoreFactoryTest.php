<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStore;
use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStoreFactory;
use ChristianBrown\KeyValueStore\SecretManagerClientFactoryInterface;
use ChristianBrown\KeyValueStore\SecretManagerClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;

#[CoversClass(GoogleSecretKeyValueStoreFactory::class)]
#[UsesClass(GoogleSecretKeyValueStore::class)]
final class GoogleSecretKeyValueStoreFactoryTest extends TestCase
{
    /**
     * @throws MockObjectException
     */
    public function testCreate(): void
    {
        $clientFactory = self::createMock(SecretManagerClientFactoryInterface::class);
        $clientFactory->expects(self::once())
            ->method('create')
            ->willReturn(self::createStub(SecretManagerClientInterface::class));

        $store = (new GoogleSecretKeyValueStoreFactory($clientFactory))->create('test/secret/path/here');

        self::assertInstanceOf(GoogleSecretKeyValueStore::class, $store);
    }
}
