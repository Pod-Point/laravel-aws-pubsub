# Changelog

All notable changes to `laravel-aws-pubsub` will be documented in this file.

## 0.4.0 - 2022-04-29

**Breaking Change** - Adding the ability to support multiple event handlers defined in your `PubSubEventServiceProvider`.

```php
protected $listen = [
    // from
    'orders.shipped' => SendShipmentNotification::class,
    'arn:aws:sns:us-east-1:123456789:orders' => OrdersListener::class,
    // to
    'orders.shipped' => [
        UpdateTrackingNumber::class,
        SendShipmentNotification::class,
    ],
    'arn:aws:sns:us-east-1:123456789:orders' => [
        OrdersListener::class,
    ],
];
```
### What's Changed

* Add support to multiple event handlers in `PubSubEventServiceProvider` by @clemblanco in https://github.com/Pod-Point/laravel-aws-pubsub/pull/38
* Refactor event processing mechanism to piggy-back on the default Laravel Event Dispatcher by @clemblanco in https://github.com/Pod-Point/laravel-aws-pubsub/pull/38

**Full Changelog**: https://github.com/Pod-Point/laravel-aws-pubsub/compare/0.3.1...0.4.0

## 0.3.1 - 2022-04-25

Adds support for Laravel 9 thanks to [cappuc](https://github.com/cappuc), see [#40](https://github.com/Pod-Point/laravel-aws-pubsub/pull/40).

## 0.3.0 - 2021-11-09

Adds support for publishing pub/sub events to AWS EventBridge in addition to AWS SNS [#11](https://github.com/Pod-Point/laravel-aws-pubsub/pull/11)

- Add EventBridge driver
- Cover it with some tests
- Update the `README.md`

## 0.0.1 - 2021-11-09

Backward compatibility with Laravel 5, 6 and 7

Adds backward compatibility with Laravel 5.x, 6.x and 7.x by removing the following functionalities:

- [Model Broadcasting](https://github.com/Pod-Point/laravel-aws-pubsub#model-broadcasting) unsupported
- Queue suffix unsupported

All the functionalities to add EventBridge support from the [release 0.3.0](https://github.com/Pod-Point/laravel-aws-pubsub/releases/tag/0.3.0) have also been back ported here.

See PR [#10](https://github.com/Pod-Point/laravel-aws-pubsub/pull/10).

## 0.2.2 - 2021-11-03

Bug fixing

- Fix registration lifecycle within the IoC container [#9](https://github.com/Pod-Point/laravel-aws-pubsub/pull/9)

## 0.2.1 - 2021-07-30

Bug fixing + update README.md

- Hotfix: fallback to Topic ARN listener when subject is not found
- Add output to `pubsub:install` command
- Update `README.md`

## 0.2.0 - 2021-07-29

Refactored the package into a PubSub package offering a complete solution for both the publication (via Laravel [Broadcasting](https://laravel.com/docs/master/broadcasting)) and the subscription (using Laravel queue [Listeners](https://laravel.com/docs/master/queues)) of server-side events.

- Implemented the "Sub part" with the ability to subscribe to events pushed from SNS and queued on SQS via the `SnsSqs` Queue Connector
- Queue suffix & prefix are supported
- Only rich notification messages are supported, not raw ones
- Subject-based and Topic-based Listeners are supported
- Ability to define the Listeners within a `PubSubEventServiceProvider.php` and resolve the `ListenerName` dynamically
- Covering the "Sub part" with tests
- Add a command `pubsub:install` to create the `PubSubEventServiceProvider.php` automatically upon package installation
- Add a command `pubsub:make:listener` to create any Listener for a PubSub event coming from SNS
- Cover the new commands with some tests
- Remove our custom `broadastEvents()` on the "Pub part" support as now this is already [part of the Laravel core](https://github.com/laravel/framework/pull/38137)
- Update the documentation within the `README.nd` accordingly
- Rename package from `laravel-sns-broadcaster` to `laravel-aws-pubsub`

## 0.1.2 - 2021-07-15

- Fix some dependencies, using `aws/aws-sdk-php` instead of `aws/laravel-aws-sdk-php`
- Waiting on [a PR upstream](https://github.com/aws/aws-sdk-php/pull/2264) to fix some dependency issues upon installation on an existing Laravel application

## 0.1.1 - 2021-07-14

- Update `README.md` file

## 0.1.0 - 2021-07-13

- First release supporting PHP7.3+ and Laravel 8+
- Only support publishing events through the SNS Broadcaster
