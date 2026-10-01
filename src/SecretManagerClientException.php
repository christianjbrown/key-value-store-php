<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use RuntimeException;

final class SecretManagerClientException extends RuntimeException implements SecretManagerClientExceptionInterface
{
}
