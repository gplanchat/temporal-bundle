<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Kiboko\Temporal\Messenger\ActivityTaskHandler;
use Kiboko\Temporal\Serialization\TemporalPayloadCodecInterface;
use Kiboko\TemporalBundle\Messenger\TemporalTransportFactory;
use Kiboko\TemporalBundle\Serialization\SymfonyJsonTemporalPayloadCodec;
use Kiboko\TemporalBundle\Testing\TemporalTestSerializerFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autoconfigure()
        ->autowire();

    $container->services()
        ->set(ActivityTaskHandler::class)
            ->autowire();

    $container->services()
        ->set('temporal_bundle.temporal_payload_codec', SymfonyJsonTemporalPayloadCodec::class)
            ->factory([TemporalTestSerializerFactory::class, 'createTemporalPayloadCodec'])
        ->alias(TemporalPayloadCodecInterface::class, 'temporal_bundle.temporal_payload_codec')

        ->set('temporal_bundle.messenger.transport_factory', TemporalTransportFactory::class)
            ->args([
                service(HttpClientInterface::class)->ignoreOnInvalid(),
                service('temporal_bundle.temporal_payload_codec'),
            ])
            ->tag('messenger.transport_factory');
};
