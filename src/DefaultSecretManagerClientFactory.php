<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Exception;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use RuntimeException;

final class DefaultSecretManagerClientFactory implements SecretManagerClientFactoryInterface
{
    public function create(): SecretManagerClientInterface
    {
        try {
            return new GoogleSecretManagerClientAdapter(new SecretManagerServiceClient());
        } catch (Exception $exception) {
            throw new RuntimeException(GoogleSecretKeyValueStoreInterface::CLIENT_START_FAILED, 0, $exception);
        }
    }
}
