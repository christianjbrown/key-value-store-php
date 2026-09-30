<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

final class GoogleSecretKeyValueStoreFactory implements GoogleSecretKeyValueStoreFactoryInterface
{
    private SecretManagerClientFactoryInterface $clientFactory;

    public function __construct(SecretManagerClientFactoryInterface $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    public function create(string $secretPath): GoogleSecretKeyValueStoreInterface
    {
        return new GoogleSecretKeyValueStore($this->clientFactory->create(), $secretPath);
    }
}
