# kiboko/temporal-bundle

Bundle Symfony pour **`kiboko/temporal`**.

## État actuel (0.1)

- Enregistre le bundle et charge une configuration DI de base.
- Services : `config/services.php` enregistre le codec JSON (`SymfonyJsonTemporalPayloadCodec`), `TemporalTransportFactory` (tag `messenger.transport_factory`), `ActivityTaskHandler` (autowiré) pour `GenericActivityTaskHandler`, et `TemporalTestSerializerFactory` pour les tests.

## Installation

```bash
composer require kiboko/temporal-bundle
```

Enregistrer dans `config/bundles.php` :

```php
Kiboko\TemporalBundle\TemporalBundle::class => ['all' => true],
```

## Monorepo

Ce package est développé dans le monorepo (`packages/temporal-bundle/`, à côté de `packages/temporal/` et de `temporal/`). Publication vers un repo dédié : voir `temporal/docs/PUBLISH_REPOS.md`.
