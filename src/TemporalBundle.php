<?php

declare(strict_types=1);

namespace Kiboko\TemporalBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Intégration Symfony pour {@see \Kiboko\Temporal\Transport\TransportInterface} et services associés.
 *
 * La couche applicative complète (Messager, gRPC) est en cours de migration depuis le PoC.
 */
final class TemporalBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');
    }
}
