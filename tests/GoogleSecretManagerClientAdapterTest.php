<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\GoogleSecretManagerClientAdapter;
use ChristianBrown\KeyValueStore\SecretManagerClientException;
use Google\ApiCore\CredentialsWrapper;
use Google\ApiCore\Testing\MockTransport;
use Google\Cloud\SecretManager\V1\AccessSecretVersionResponse;
use Google\Cloud\SecretManager\V1\AddSecretVersionRequest;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\SecretPayload;
use Google\Cloud\SecretManager\V1\SecretVersion;
use Google\Protobuf\Internal\Message;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(GoogleSecretManagerClientAdapter::class)]
final class GoogleSecretManagerClientAdapterTest extends TestCase
{
    /**
     * @throws MockObjectException
     */
    public function testAccessLatest(): void
    {
        $client = $this->createClient(new AccessSecretVersionResponse([
            'payload' => new SecretPayload(['data' => 'test-secret-value']),
        ]));
        $adapter = new GoogleSecretManagerClientAdapter($client);

        self::assertSame('test-secret-value', $adapter->accessLatest('test/secret/path/here/versions/latest'));
    }

    /**
     * @throws MockObjectException
     */
    public function testAccessLatestApiException(): void
    {
        $this->expectException(SecretManagerClientException::class);
        $this->expectExceptionMessage('test-exception-message');

        $adapter = new GoogleSecretManagerClientAdapter($this->createFailingClient());

        $adapter->accessLatest('test/secret/path/here/versions/latest');
    }

    /**
     * @throws MockObjectException
     */
    public function testAccessLatestNoPayload(): void
    {
        $adapter = new GoogleSecretManagerClientAdapter($this->createClient(new AccessSecretVersionResponse()));

        self::assertNull($adapter->accessLatest('test/secret/path/here/versions/latest'));
    }

    /**
     * @throws MockObjectException
     */
    public function testAddVersion(): void
    {
        $transport = new MockTransport();
        $transport->addResponse(new SecretVersion(['name' => 'test-secret-version-name']));
        $adapter = new GoogleSecretManagerClientAdapter($this->createClientWithTransport($transport));

        $adapter->addVersion('test/secret/path/here', 'test-secret-value');

        $request = $transport->popReceivedCalls()[0]->getRequestObject();
        self::assertInstanceOf(AddSecretVersionRequest::class, $request);
        self::assertSame('test/secret/path/here', $request->getParent());
        self::assertSame('test-secret-value', $request->getPayload()?->getData());
    }

    /**
     * @throws MockObjectException
     */
    public function testAddVersionApiException(): void
    {
        $this->expectException(SecretManagerClientException::class);
        $this->expectExceptionMessage('test-exception-message');

        $adapter = new GoogleSecretManagerClientAdapter($this->createFailingClient());

        $adapter->addVersion('test/secret/path/here', 'test-secret-value');
    }

    /**
     * @throws MockObjectException
     */
    private function createClient(Message $response): SecretManagerServiceClient
    {
        $transport = new MockTransport();
        $transport->addResponse($response);

        return $this->createClientWithTransport($transport);
    }

    /**
     * @throws MockObjectException
     */
    private function createClientWithTransport(MockTransport $transport): SecretManagerServiceClient
    {
        return new SecretManagerServiceClient([
            'credentials' => self::createStub(CredentialsWrapper::class),
            'transport' => $transport,
        ]);
    }

    /**
     * @throws MockObjectException
     */
    private function createFailingClient(): SecretManagerServiceClient
    {
        $status = new stdClass();
        $status->code = 5;
        $status->details = 'test-exception-message';

        $transport = new MockTransport();
        $transport->addResponse(new SecretVersion(), $status);

        return $this->createClientWithTransport($transport);
    }
}
