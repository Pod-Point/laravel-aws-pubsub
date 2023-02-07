# AWS PubSub for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pod-point/laravel-aws-pubsub.svg?style=flat-square)](https://packagist.org/packages/pod-point/laravel-aws-pubsub)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/pod-point/laravel-aws-pubsub/run-tests.yml?branch=main&label=0.4.x)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/pod-point/laravel-aws-pubsub.svg?style=flat-square)](https://packagist.org/packages/pod-point/laravel-aws-pubsub)

**The Pub**

Similar to [Pusher](https://laravel.com/docs/broadcasting#pusher-channels), this package provides [Laravel Broadcasting](https://laravel.com/docs/broadcasting) drivers for [AWS SNS](https://aws.amazon.com/sns/) (Simple Notification Service) and [AWS EventBridge](https://aws.amazon.com/eventbridge/) in order to publish server-side events.

We understand [Broadcasting](https://laravel.com/docs/broadcasting) is usually used to "broadcast" your server-side Laravel [Events](https://laravel.com/docs/events) over a WebSocket connection to your client-side JavaScript application. However, we believe this approach of leveraging broadcasting makes sense for a Pub/Sub architecture where an application would like to broadcast a server-side event to the outside world about something that just happened.

In this context, "channels" can be assimilated to "topics" when using the SNS driver and "event buses" when using the EventBridge driver.

**The Sub**

This part is pretty straight forward, we simply have to listen to these messages pushed to an SQS queue and act upon them. The only difference here is that we don't use the default Laravel SQS driver as the messages pushed are not following Laravel's classic JSON payload for queued Jobs/Events pushed from a Laravel application. The messages from SNS are simpler.

## Prerequisites

1. This package installed and configured on both Laravel applications: the publisher and the subscriber
2. At least one SQS Queue - **one queue per Laravel application subscribing**
3. At least one SNS Topic
4. An [SQS subscription](./docs/sqs-subscription.jpg) between your SNS Topic and your SQS Queue with "raw message delivery" [disabled](./docs/raw-message-delivery.jpg)
5. The relevant [Access policies configured](https://docs.aws.amazon.com/sns/latest/dg/sns-access-policy-use-cases.html), especially if you want to be able to publish messages directly from the AWS Console.

## Installation

You can install the package on a Laravel 8+ application via composer:

```bash
composer require pod-point/laravel-aws-pubsub
```

**Note:** For Laravel 5.x, 6.x or 7.x you can use `pod-point/laravel-aws-pubsub:^0.0.1`.

This package needs a separate Service Provider, please install it by running:

```bash
php artisan pubsub:install
```

This will create `App\Providers\PubSubEventServiceProvider` and load it within your `config/app.php` file automatically.

## Publishing / Broadcasting

### Configuration

You will need to add the following connection and configure your SNS credentials in the `config/broadcasting.php` configuration file:

```php
'connections' => [
    // ...
    'sns' => [
        'driver' => 'sns',
        'region' => env('AWS_DEFAULT_REGION'),
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'arn-prefix' => env('BROADCAST_TOPIC_ARN_PREFIX'),
        'arn-suffix' => env('BROADCAST_TOPIC_ARN_SUFFIX'),
    ],

    'eventbridge' => [
        'driver' => 'eventbridge',
        'region' => env('AWS_DEFAULT_REGION'),
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'source' => env('AWS_EVENTBRIDGE_SOURCE'),
    ],
    // ...
],
```

Make sure to define your [environment variables](https://laravel.com/docs/configuration#environment-configuration) accordingly:

```dotenv
# both drivers require:
AWS_DEFAULT_REGION=you-region
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret

# SNS driver only:
BROADCAST_TOPIC_ARN_PREFIX=arn:aws:sns:us-east-1:123456789: # up until your Topic name
BROADCAST_TOPIC_ARN_SUFFIX=-local # optional

# EventBridge driver only:
AWS_EVENTBRIDGE_SOURCE=com.your-app-name
```

The `arn-suffix` can be used to help manage SNS topics for different environments. It will be added to the end when constructing the full SNS Topic ARN.

Next, you will need to make sure you're using the `sns` broadcast driver as your default driver when broadcasting in your `.env` file:

```php
BROADCAST_DRIVER=sns
```
or
```php
BROADCAST_DRIVER=eventbridge
```

**Remember** that you can define the connection at the Event level if you ever need to be able to use [two drivers concurrently](https://github.com/laravel/framework/pull/38086).

### Usage

Simply follow the default way of broadcasting Laravel events, explained in the [official documentation](https://laravel.com/docs/broadcasting#defining-broadcast-events).

In a similar way, you will have to make sure you're implementing the `Illuminate\Contracts\Broadcasting\ShouldBroadcast` interface and define which channel / Topic you'd like to broadcast on.

```php
use App\Models\Order;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderShipped implements ShouldBroadcast
{
    use SerializesModels;

    /**
     * The order that was shipped.
     *
     * @var \App\Models\Order
     */
    public $order;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the topics that model events should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return ['orders']; // This is the Topic name for the ARN 'arn:aws:sns:us-east-1:123456789:orders' for example
    }
}
```

#### Broadcast Data

By default, the package will publish the default Laravel payload which is already used when broadcasting an Event. Once published, its JSON representation could look like this:

```json
{
    "order": {
        "id": 1,
        "name": "Some Goods",
        "total": 123456,
        "created_at": "2021-06-29T13:21:36.000000Z",
        "updated_at": "2021-06-29T13:21:36.000000Z"
    },
    "connection": null,
    "queue": null
}
```

By default, Laravel will automatically add any additional public property to the payload:

```php
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderShipped implements ShouldBroadcast
{
    use SerializesModels;

    public $action = 'parcel_handled';

    // ...
}
```

Which would produce the following payload:

```json
{
    "action": "parcel_handled",
    "order": {
        "id": 1,
        "name": "Some Goods",
        "total": 123456,
        "created_at": "2021-06-29T13:21:36.000000Z",
        "updated_at": "2021-06-29T13:21:36.000000Z"
    },
    "connection": null,
    "queue": null
}
```

However, using the `broadcastWith` method, you will be able to define exactly what kind of payload gets published.

```php
/**
 * Get and format the data to broadcast.
 *
 * @return array
 */
public function broadcastWith()
{
    return [
        'action' => 'parcel_handled',
        'data' => [
            'order-id' => $this->order->id,
            'order-total' => $this->order->total,
        ],
    ];
}
```

Now, when the event is being triggered, it will behave like a standard Laravel event, which means other listeners can listen to it, as usual, but it will also broadcast to the Topic defined by the `broadcastOn` method using the payload defined by the `broadcastWith` method.

#### Broadcast Name / Subject

In a Pub/Sub context, it can be handy to specify a `Subject` on each notification which broadcast to SNS. This can be an easy way to configure a Listeners for each specific kind of subject you can receive and process later on within queues.

By default, the package will use the standard [Laravel broadcast name](https://laravel.com/docs/broadcasting#broadcast-name) in order to define the `Subject` of the notification sent. Feel free to customize it as you wish.

```php
/**
 * The event's broadcast name/subject.
 *
 * @return string
 */
public function broadcastAs()
{
    return "orders.{$this->action}";
}
```

#### Model Broadcasting

If you're familiar with [Model Broadcasting](https://laravel.com/docs/broadcasting#model-broadcasting), you already know that Eloquent models dispatch several events during their lifecycle and broadcast them accordingly.

In the context of model broadcasting, only the following model events can be broadcasted:

- `created`
- `updated`
- `deleted`
- `trashed` _if soft delete is enabled_
- `restored` _if soft delete is enabled_

In order to broadcast the model events, you need to use the `Illuminate\Database\Eloquent\BroadcastsEvents` trait on your Model and follow the official [documentation]((https://laravel.com/docs/broadcasting#model-broadcasting)).

You can use `broadcastOn()`, `broadcastWith()` and `broadcastAs()` methods on your model in order to customize the Topic names, the payload and the Subject respectively.

> **Note:** Model Broadcasting is **only available from Laravel 8.x**.
> If you'd like to do something similar with an older version of Laravel, we recommend to manually dispatch some "broadcastable" Events you'd be creating yourself from the [Model Observer](https://laravel.com/docs/eloquent#observers) functions.

## Subscribing / Listening

### Configuration

Once the package is installed and similar to what you would do for a standard Laravel SQS queue, you will need to add the following connection and configure your credentials in the `config/queue.php` configuration file:

```php
'connections' => [
    // ...
    'sqs-sns' => [
        'driver' => 'sqs-sns',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'prefix' => env('SQS_SNS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
        'queue' => env('SQS_SNS_QUEUE', 'pub-sub'),
        'suffix' => env('SQS_SNS_SUFFIX'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    // ...
],
```

Once your queue is configured properly, you will need to be able to define which listeners you would like to use for which kind of incoming events. In order to do so, you'll need to create Laravel listeners and associate the events through a Service Provider the package can create for you.

### Registering Events & Listeners

You'll need a separate Service Provider in order to define the mapping for each PubSub event and its Listeners. We provide `App\Providers\PubSubEventServiceProvider` which you should have already installed by now when running `php artisan pubsub:install` upon package installation.

The `listen` property contains an array of all events (keys) and their listeners (values). Unlike the standard Laravel `EventServiceProvider`, you can only define one Listeners per event, however you may add as many events to this array as your application requires.

#### Using the Broadcast Name / Subject of an SNS message

You can define a PubSub event by using its [Broadcast Name / Subject](#broadcast-name--subject). For example, let's add an event with `orders.shipped` as its `Subject` (aka. Broadcast Name):

```php
use App\Listeners\PubSub\SendShipmentNotification;

/**
 * The event handler mappings for subscribing to PubSub events.
 *
 * @var array
 */
protected $listen = [
    'orders.shipped' => [
        SendShipmentNotification::class,
    ],
];
```

#### Using the SNS Topic Name

As a fallback, you can also use the ARN of an SNS Topic itself and have a more generic Listener for any event coming from that Topic **which haven't been already mapped** to an existing subject-based Event/Listeners couple.

For example, let's add a generic Listener for any event pushed to a given SNS Topic as a fallback:

```php
use App\Listeners\PubSub\OrdersListener;

/**
 * The event handler mappings for subscribing to PubSub events.
 *
 * @var array
 */
protected $listen = [
    'orders.shipped' => [
        UpdateTrackingNumber::class,
        SendShipmentNotification::class,
    ],
    'arn:aws:sns:us-east-1:123456789:orders' => [
        OrdersListener::class,
    ],
];
```

You may do whatever you want from that generic `OrdersListener`, you could even [dispatch more events](https://laravel.com/docs/events) internally within your application.

**Note:** Topic-based Event/Listeners couples should be registered last so the Subject-based ones take priority.

### Defining Listeners

Here we are simply re-using standard Laravel event listeners. The only difference being the function definition of the main `handle()` method which differs slightly. Instead of expecting an instance of an Event class passed, we simply receive the `payload` and the `subject`, if it's found.

```php
/**
 * Handle the event.
 *
 * @return void
 */
public function handle(array $payload, string $subject = '')
{
    // ...
}
```

Feel free to queue these listeners, just like you would with a standard Laravel Listeners.

#### Generating Listeners

We also provide a convenient command to generate these classes for you:

```bash
artisan pubsub:make:listener SendShipmentNotification
```

**Note:** you will still need to make sure the mapping within the `PubSubEventServiceProvider` is configured.

## Testing

Run the tests with:

```bash
composer test
```

## Changelog

Please see our [releases](https://github.com/Pod-Point/laravel-aws-pubsub/releases) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [laravel-sns-broadcaster](https://github.com/maxgaurav/laravel-sns-broadcaster) for some inspiration
- [laravel-sqs-sns-subscription-queue](https://github.com/joblocal/laravel-sqs-sns-subscription-queue) for more inspiration
- [Laravel Package Development](https://laravelpackage.com) documentation by [John Braun](https://github.com/Jhnbrn90)
- [Pod Point](https://github.com/pod-point)
- [All Contributors](https://github.com/pod-point/laravel-aws-pubsub/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

<img src="https://d3h256n3bzippp.cloudfront.net/pod-point-logo.svg" align="right" />

Travel shouldn't damage the earth 🌍

Made with ❤️&nbsp;&nbsp;at [Pod Point](https://pod-point.com)
