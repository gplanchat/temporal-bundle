# kiboko/temporal-bundle

Bundle Symfony pour **`kiboko/temporal`**.

## État actuel (0.1)

- Enregistre le bundle et charge une configuration DI de base.
- Les services concrets (codec JSON, `TemporalTransportFactory`, transports Messenger gRPC, etc.) restent pour l’instant dans le PoC (`Kiboko\PocTemporal\Bundle\PocTemporalBundle`) ; ils seront **progressivement migrés** ici.

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
