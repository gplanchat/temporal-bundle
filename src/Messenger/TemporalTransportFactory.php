<?php

declare(strict_types=1);

namespace Kiboko\TemporalBundle\Messenger;

use Kiboko\Temporal\Grpc\TemporalPayloadMapper;
use Kiboko\Temporal\Serialization\TemporalPayloadCodecInterface;
use Kiboko\Temporal\Transport\GrpcTransport;
use Kiboko\Temporal\Transport\HttpTransport;
use Kiboko\Temporal\Transport\TransportInterface;
use Kiboko\Temporal\Transport\WorkflowPollTransportInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface as MessengerTransportInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Factory pour créer le transport Messenger Temporal.
 *
 * DSN supportés :
 * - temporal://default — en mémoire (injectTask)
 * - temporal+http://host:7243/ns — HTTP (Nexus / API HTTP)
 * - temporal+grpc://host:7233/ns — activités via gRPC (extension php grpc)
 * - temporal+grpc-workflow://host:7233/ns — workflows via gRPC (poll + respond)
 */
final class TemporalTransportFactory implements TransportFactoryInterface
{
    public function __construct(
        private readonly ?HttpClientInterface $httpClient = null,
        private readonly ?TemporalPayloadCodecInterface $payloadCodec = null,
    ) {
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): MessengerTransportInterface
    {
        $config = $this->parseDsn($dsn);

        return match ($config['scheme']) {
            'temporal+grpc-workflow' => $this->createWorkflowMessengerTransport($config),
            default => new TemporalActivityTransport(
                taskQueue: $config['task_queue'] ?? 'default',
                transport: $this->createActivityBackend($config),
                pollTimeoutSeconds: $config['poll_timeout'],
            ),
        };
    }

    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'temporal://')
            || str_starts_with($dsn, 'temporal+http://')
            || str_starts_with($dsn, 'temporal+grpc://')
            || str_starts_with($dsn, 'temporal+grpc-workflow://');
    }

    /**
     * @param array{scheme: string, host?: string, port?: int, namespace: string, task_queue: string, poll_timeout?: float|null} $config
     */
    private function createWorkflowMessengerTransport(array $config): TemporalWorkflowTransport
    {
        $grpc = $this->createGrpcTransport($config);
        if (!$grpc instanceof WorkflowPollTransportInterface) {
            throw new \LogicException('GrpcTransport doit implémenter WorkflowPollTransportInterface.');
        }

        return new TemporalWorkflowTransport(
            $config['task_queue'] ?? 'default',
            $grpc,
            $config['poll_timeout'] ?? null,
        );
    }

    /**
     * @param array{scheme: string, host?: string, port?: int, namespace: string, task_queue: string, poll_timeout?: float|null} $config
     */
    private function createActivityBackend(array $config): ?TransportInterface
    {
        return match ($config['scheme']) {
            'temporal' => null,
            'temporal+http' => $this->createHttpTransport($config),
            'temporal+grpc' => $this->createGrpcTransport($config),
            default => null,
        };
    }

    /**
     * @return array{scheme: string, host?: string, port?: int, namespace: string, task_queue: string, poll_timeout: float|null}
     */
    private function parseDsn(string $dsn): array
    {
        $scheme = 'temporal';
        if (str_starts_with($dsn, 'temporal+grpc-workflow://')) {
            $scheme = 'temporal+grpc-workflow';
            $dsnParse = 'http://' . substr($dsn, \strlen('temporal+grpc-workflow://'));
        } elseif (str_starts_with($dsn, 'temporal+grpc://')) {
            $scheme = 'temporal+grpc';
            $dsnParse = 'http://' . substr($dsn, \strlen('temporal+grpc://'));
        } elseif (str_starts_with($dsn, 'temporal+http://')) {
            $scheme = 'temporal+http';
            $dsnParse = 'http://' . substr($dsn, \strlen('temporal+http://'));
        } elseif (str_starts_with($dsn, 'temporal://')) {
            $scheme = 'temporal';
            $dsnParse = 'http://' . substr($dsn, \strlen('temporal://'));
        } else {
            throw new \InvalidArgumentException(sprintf('DSN Temporal non reconnu : %s', $dsn));
        }

        $parsed = parse_url($dsnParse);
        if ($parsed === false) {
            throw new \InvalidArgumentException(sprintf('DSN invalide : %s', $dsn));
        }

        $path = trim($parsed['path'] ?? '/default', '/');
        $namespace = $path !== '' ? $path : 'default';

        parse_str($parsed['query'] ?? '', $query);
        $taskQueue = $query['task_queue'] ?? $query['taskQueue'] ?? 'default';
        $pollTimeout = null;
        if (isset($query['poll_timeout'])) {
            $pollTimeout = (float) $query['poll_timeout'];
        }

        $defaultPort = match ($scheme) {
            'temporal+http' => 7243,
            'temporal+grpc', 'temporal+grpc-workflow' => 7233,
            default => 7233,
        };

        return [
            'scheme' => $scheme,
            'host' => $parsed['host'] ?? '127.0.0.1',
            'port' => isset($parsed['port']) ? (int) $parsed['port'] : $defaultPort,
            'namespace' => $namespace,
            'task_queue' => $taskQueue,
            'poll_timeout' => $pollTimeout,
        ];
    }

    /**
     * @param array{scheme: string, host?: string, port?: int, namespace: string, task_queue: string, poll_timeout?: float|null} $config
     */
    private function createHttpTransport(array $config): TransportInterface
    {
        $client = $this->httpClient;
        if ($client === null) {
            throw new \LogicException('HttpClient requis pour temporal+http.');
        }

        $baseUri = sprintf(
            'http://%s:%d',
            $config['host'] ?? 'localhost',
            $config['port'] ?? 7243,
        );

        return new HttpTransport(
            $client,
            $baseUri,
            $config['namespace'],
            $this->payloadCodec,
            HttpTransport::httpUnaryRetryPolicyFromEnvironment(),
        );
    }

    /**
     * @param array{scheme: string, host?: string, port?: int, namespace: string, task_queue: string, poll_timeout?: float|null} $config
     */
    private function createGrpcTransport(array $config): GrpcTransport
    {
        $codec = $this->payloadCodec;
        if ($codec === null) {
            throw new \LogicException(
                'TemporalPayloadCodecInterface requis pour temporal+grpc / temporal+grpc-workflow (service temporal_bundle.temporal_payload_codec ou argument factory).'
            );
        }
        $mapper = new TemporalPayloadMapper($codec);
        $target = sprintf('%s:%d', $config['host'] ?? '127.0.0.1', $config['port'] ?? 7233);

        return GrpcTransport::createDefault($target, $mapper, $config['namespace']);
    }
}
