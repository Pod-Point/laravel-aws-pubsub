# Changelog

All notable changes to `laravel-aws-pubsub` will be documented in this file.

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
