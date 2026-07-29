<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Override;
use RuntimeException;

final class EncryptedStringType extends Type
{
    public const NAME = "encrypted_string";

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        $key = self::getKey();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox((string) $value, $nonce, $key);
        return base64_encode($nonce . $ciphertext);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        $key = self::getKey();
        $decoded = base64_decode((string) $value, true);

        if ($decoded === false || strlen($decoded) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('Malformed encrypted value.');
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

        if ($plaintext === false) {
            throw new RuntimeException('Failed to decrypt value: authentication failed or wrong key.');
        }

        return $plaintext;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    private static function getKey(): string
    {
        $envKey = getenv('ENCRYPTION_KEY');

        if ($envKey === false || $envKey === '') {
            throw new RuntimeException('ENCRYPTION_KEY environment variable is not set.');
        }

        $key = base64_decode(str_replace('base64:', '', $envKey), true);

        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('ENCRYPTION_KEY is invalid or worng length.');
        }

        return $key;
    }
}
