<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\DefaultSecretManagerClientFactory;
use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStoreInterface;
use ChristianBrown\KeyValueStore\GoogleSecretManagerClientAdapter;
use ChristianBrown\KeyValueStore\SecretManagerClientInterface;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function putenv;

#[CoversClass(DefaultSecretManagerClientFactory::class)]
#[UsesClass(GoogleSecretManagerClientAdapter::class)]
final class DefaultSecretManagerClientFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=./tests/test-credentials.json');

        $client = (new DefaultSecretManagerClientFactory())->create();

        self::assertInstanceOf(SecretManagerClientInterface::class, $client);
    }

    public function testCreateException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(GoogleSecretKeyValueStoreInterface::CLIENT_START_FAILED);

        putenv('GOOGLE_APPLICATION_CREDENTIALS=./tests/file-does-not-exist.json');
        (new DefaultSecretManagerClientFactory())->create();
    }
}
