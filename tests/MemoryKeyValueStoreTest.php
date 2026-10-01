<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\MemoryKeyValueStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

#[CoversClass(MemoryKeyValueStore::class)]
final class MemoryKeyValueStoreTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $store = new MemoryKeyValueStore(new MockClock());

        self::assertNull($store->getTtl());
        self::assertNull($store->getValue());
    }

    public function testSetValueIsFluentAndPersistsState(): void
    {
        $store = new MemoryKeyValueStore(new MockClock());

        self::assertSame($store, $store->setValue('test-value', 2));
        self::assertSame(2, $store->getTtl());
        self::assertSame('test-value', $store->getValue());
    }

    public function testSetValueWithoutTtlClearsEarlierTtl(): void
    {
        $store = new MemoryKeyValueStore(new MockClock());

        $store->setValue('first', 60);
        $store->setValue('second');

        self::assertNull($store->getTtl());
        self::assertSame('second', $store->getValue());
    }

    public function testSetValueWithoutTtlNeverExpires(): void
    {
        $clock = new MockClock();
        $store = new MemoryKeyValueStore($clock);

        $store->setValue('test-value');
        $clock->sleep(86400);

        self::assertNull($store->getTtl());
        self::assertSame('test-value', $store->getValue());
    }

    public function testTtlCountsDownWithTheClock(): void
    {
        $clock = new MockClock();
        $store = new MemoryKeyValueStore($clock);

        $store->setValue('test-value', 60);
        $clock->sleep(45);

        self::assertSame(15, $store->getTtl());
        self::assertSame('test-value', $store->getValue());
    }

    public function testValueExpiresOnceTheTtlHasPassed(): void
    {
        $clock = new MockClock();
        $store = new MemoryKeyValueStore($clock);

        $store->setValue('test-value', 60);
        $clock->sleep(61);

        self::assertNull($store->getValue());
    }

    public function testValueIsStillReadableAtTheExpiryInstant(): void
    {
        $clock = new MockClock();
        $store = new MemoryKeyValueStore($clock);

        $store->setValue('test-value', 60);
        $clock->sleep(60);

        self::assertSame('test-value', $store->getValue());
    }
}
