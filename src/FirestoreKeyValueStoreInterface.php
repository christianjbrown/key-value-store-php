<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

interface FirestoreKeyValueStoreInterface extends TtlAwareKeyValueStoreInterface
{
    public const string FIELD_EXPIRES_AT = 'expiresAt';
    public const string FIELD_VALUE = 'value';
}
