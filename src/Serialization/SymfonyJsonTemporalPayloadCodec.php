<?php

declare(strict_types=1);

namespace Kiboko\TemporalBundle\Serialization;

use Kiboko\Temporal\Serialization\TemporalPayloadCodecInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Codec Temporal basé sur le Serializer Symfony (JSON).
 * Même représentation JSON pour les appels HTTP et pour les octets embarqués en gRPC.
 */
final class SymfonyJsonTemporalPayloadCodec implements TemporalPayloadCodecInterface
{
    private const ENCODING_JSON_PLAIN = 'json/plain';

    public function __construct(
        private readonly SerializerInterface $serializer,
        /** @var array<string, mixed> */
        private readonly array $context = [],
    ) {
    }

    public function encodeJson(mixed $value): string
    {
        return $this->serializer->serialize($value, 'json', $this->context + [
            JsonEncode::OPTIONS => \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE,
        ]);
    }

    public function decodeJson(string $json, ?string $type = null): mixed
    {
        if ($type !== null) {
            return $this->serializer->deserialize($json, $type, 'json', $this->context);
        }

        $decoded = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        if (!\is_array($decoded)) {
            throw new \InvalidArgumentException('JSON racine attendu : objet ou tableau associatif.');
        }

        return $decoded;
    }

    public function toTemporalWireArray(mixed $value): array
    {
        $json = $this->encodeJson($value);

        return [
            'metadata' => [
                'encoding' => base64_encode(self::ENCODING_JSON_PLAIN),
            ],
            'data' => base64_encode($json),
        ];
    }

    public function fromTemporalWireArray(array $wire): mixed
    {
        $data = $wire['data'] ?? null;
        if (!\is_string($data)) {
            throw new \InvalidArgumentException('Temporal payload wire array must contain a string "data" (base64 JSON).');
        }

        $json = base64_decode($data, true);
        if ($json === false) {
            throw new \InvalidArgumentException('Invalid base64 in Temporal payload "data".');
        }

        return $this->decodeJson($json);
    }

    public function toGrpcPayloadBytes(mixed $value): string
    {
        return $this->encodeJson($value);
    }
}
