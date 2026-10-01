<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use function basename;
use function mb_trim;
use function sprintf;

final class GoogleSecretKeyValueStore implements GoogleSecretKeyValueStoreInterface
{
    private SecretManagerClientInterface $client;
    private string $secretPath;

    public function __construct(SecretManagerClientInterface $client, string $secretPath)
    {
        $this->client = $client;
        $this->secretPath = mb_trim($secretPath, '/');
    }

    public function getValue(): ?string
    {
        try {
            return $this->client->accessLatest($this->secretPath.self::VERSION_LATEST);
        } catch (SecretManagerClientExceptionInterface) {
            $message = sprintf(self::GET_VALUE_FAILED_SPRINTF, basename($this->secretPath));

            throw new GoogleSecretKeyValueStoreException($message);
        }
    }

    public function setValue(?string $value): self
    {
        try {
            $this->client->addVersion($this->secretPath, $value);
        } catch (SecretManagerClientExceptionInterface) {
            $message = sprintf(self::SET_VALUE_FAILED_SPRINTF, basename($this->secretPath));

            throw new GoogleSecretKeyValueStoreException($message);
        }

        return $this;
    }
}
