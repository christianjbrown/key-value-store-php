<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Google\ApiCore\ApiException;
use Google\Cloud\SecretManager\V1\AccessSecretVersionRequest;
use Google\Cloud\SecretManager\V1\AddSecretVersionRequest;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\SecretPayload;

final class GoogleSecretManagerClientAdapter implements SecretManagerClientInterface
{
    private SecretManagerServiceClient $client;

    public function __construct(SecretManagerServiceClient $client)
    {
        $this->client = $client;
    }

    public function accessLatest(string $versionName): ?string
    {
        try {
            $request = (new AccessSecretVersionRequest())->setName($versionName);
            $response = $this->client->accessSecretVersion($request);
        } catch (ApiException $exception) {
            throw new SecretManagerClientException($exception->getMessage(), 0, $exception);
        }

        return $response->getPayload()?->getData();
    }

    public function addVersion(string $secretName, ?string $value): void
    {
        try {
            $request = (new AddSecretVersionRequest())
                ->setParent($secretName)
                ->setPayload(new SecretPayload(['data' => $value]));
            $this->client->addSecretVersion($request);
        } catch (ApiException $exception) {
            throw new SecretManagerClientException($exception->getMessage(), 0, $exception);
        }
    }
}
