<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

interface GoogleSecretKeyValueStoreFactoryInterface
{
    public function create(string $secretPath): GoogleSecretKeyValueStoreInterface;
}
