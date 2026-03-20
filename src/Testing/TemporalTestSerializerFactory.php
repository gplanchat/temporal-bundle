<?php

declare(strict_types=1);

namespace Kiboko\TemporalBundle\Testing;

use Kiboko\Temporal\Serialization\TemporalPayloadCodecInterface;
use Kiboko\TemporalBundle\Serialization\SymfonyJsonTemporalPayloadCodec;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Serializer Symfony prêt pour les tests (JSON, DTO, Uid, dates) et codec Temporal associé.
 */
final class TemporalTestSerializerFactory
{
    public static function createSerializer(): Serializer
    {
        $propertyAccessor = new PropertyAccessor();

        $normalizers = [
            new DateTimeNormalizer(),
            new UidNormalizer(),
            new ObjectNormalizer(null, null, $propertyAccessor),
            new ArrayDenormalizer(),
        ];

        return new Serializer($normalizers, [new JsonEncoder()]);
    }

    public static function createTemporalPayloadCodec(): TemporalPayloadCodecInterface
    {
        return new SymfonyJsonTemporalPayloadCodec(self::createSerializer());
    }
}
