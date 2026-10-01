<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

interface SecretManagerClientInterface
{
    /**
     * @throws SecretManagerClientExceptionInterface
     */
    public function accessLatest(string $versionName): ?string;

    /**
     * @throws SecretManagerClientExceptionInterface
     */
    public function addVersion(string $secretName, ?string $value): void;
}
