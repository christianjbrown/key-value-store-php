<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

interface SecretManagerClientFactoryInterface
{
    public function create(): SecretManagerClientInterface;
}
