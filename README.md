# AWS PubSub for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pod-point/laravel-aws-pubsub.svg?style=flat-square)](https://packagist.org/packages/pod-point/laravel-aws-pubsub)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/pod-point/laravel-aws-pubsub/run-tests?label=tests)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/pod-point/laravel-aws-pubsub.svg?style=flat-square)](https://packagist.org/packages/pod-point/laravel-aws-pubsub)

**The Pub**

Similar to [Pusher](https://laravel.com/docs/master/broadcasting#pusher-channels), this package provides a [Laravel Broadcasting](https://laravel.com/docs/master/broadcasting) driver for [AWS SNS](https://aws.amazon.com/sns/) (Simple Notification Service) in order to publish server-side events.

We understand [Broadcasting](https://laravel.com/docs/master/broadcasting) is usually used to "broadcast" your server-side Laravel [Events](https://laravel.com/docs/master/events) over a WebSocket connection to your client-side JavaScript application. However, we believe this approach of leveraging broadcasting makes sense for a Pub/Sub architecture where an application would like to broadcast a server-side event to the outside world about something that just happened.

In this context, "channels" can be assimilated to "topics" on SNS.

**The Sub**

This part is pretty straight forward, we simply have to listen to these messages pushed to an SQS queue and act upon. The only difference here is that we don't use the default Laravel SQS driver as the messages pushed are not exactly following Laravel's standard payload for queued Jobs/Events, as the messages from SNS are a bit simpler.

## Prerequisites

1. This package installed and configured on both Laravel applications: the publisher and the subscriber
2. An SQS queue
3. An SNS topic
4. An [SQS subscription](./docs/sqs-subscription.jpg) between your SNS topic and your SQS queue with "raw message delivery" [disabled](./docs/raw-message-delivery.jpg)
5. The relevant [Access policies configured](https://docs.aws.amazon.com/sns/latest/dg/sns-access-policy-use-cases.html), especially if you want to be able to publish messages directly from the AWS Console.

## Publishing / Broadcasting

### Installation

You can install the package on a Laravel 8+ application via composer:

```bash
composer require pod-point/laravel-aws-pubsub
```

Next, you will need to add the following connection and configure your SNS credentials in the `config/broadcasting.php` configuration file:

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
    // ...
],
```

Make sure to define your [environment variables](https://laravel.com/docs/master/configuration#environment-configuration) accordingly:

```dotenv
AWS_DEFAULT_REGION=you-region
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
BROADCAST_TOPIC_ARN_PREFIX=arn:aws:sns:us-east-1:123456789: # up until your topic name
BROADCAST_TOPIC_ARN_SUFFIX=-local # optional
```

The `arn-suffix` can be used to help manage SNS topics for different environments. It will be added to the end when constructing the full SNS Topic ARN.

Next, you will need to make sure you're using the `sns` broadcast driver when broadcasting in your `.env` file:

```php
BROADCAST_DRIVER=sns
```

Finally, don't forget to enable the [Broadcast Service Provider](https://laravel.com/docs/master/broadcasting#broadcast-service-provider).

### Usage

Here we will simply re-use the power of Laravel Broadcasting, out of the box, with some minor additional functionalities for the Eloquent Model Events.

#### Basic Events

Simply follow the default way of broadcasting Laravel events, explained in the [official documentation](https://laravel.com/docs/master/broadcasting#defining-broadcast-events).

In a similar way, you will have to make sure you're implementing the `Illuminate\Contracts\Broadcasting\ShouldBroadcast` interface and define which channel / topic you'd like to broadcast on.

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

##### Broadcast Data

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

Now, when the event is being triggered, it will behave like a standard Laravel event, which means other Listeners can listen to it, as usual, but it will also broadcast to the topic defined by the `broadcastOn` method using the payload defined by the `broadcastWith` method.

##### Broadcast Name / Subject

In a Pub/Sub context, it can be handy to specify a `Subject` on each notification which broadcast to SNS. This can be an easy way to configure a listener for each specific kind of subject you can receive and process later on within queues.

By default, the package will use the standard [Laravel broadcast name](https://laravel.com/docs/8.x/broadcasting#broadcast-name) in order to define the `Subject` of the notification sent. Feel free to customize it as you wish.

```php
/**
 * The event's broadcast name/subject.
 *
 * @return string
 */
public function broadcastAs()
{
    return "{$this->order->getTable()}.{$this->action}";
}
```

#### Model Broadcasting

If you're familiar with [Model Observers](https://laravel.com/docs/master/eloquent#observers), you already know that Eloquent models dispatch several events during their lifecycle.

> **Note:** [Model Broadcasting](https://laravel.com/docs/8.x/broadcasting#model-broadcasting) is **only available from Laravel 8.x**.
> If you'd like to do something similar with an older version of Laravel, we recommend to manually dispatch some "broadcastable" Events you'd be creating yourself from the [Model Observer](https://laravel.com/docs/master/eloquent#observers) functions.

In order to broadcast the model events, you need to use the `PodPoint\SnsBroadcaster\BroadcastsEvents` trait on your Model.

```php
use Illuminate\Database\Eloquent\Model;
use PodPoint\SnsBroadcaster\BroadcastsEvents;

class Order extends Model
{
    use BroadcastsEvents;

    /**
     * Get the channels that model events should broadcast on.
     *
     * @param string $event
     * @return array
     */
    public function broadcastOn($event)
    {
        return ['orders'];
    }
}
```

##### Events

In the context of broadcasting, only the following model events can be broadcasted:

- `created`
- `updated`
- `deleted`
- `trashed` _if soft delete is enabled_
- `restored` _if soft delete is enabled_

By default, all of these events would broadcast, but you can define which events in particular you'd like to broadcast using the `broadcastOn` method on the model itself, just like Laravel suggest it:

```php
/**
 * Get the channels that model events should broadcast on.
 *
 * @param  string  $event
 * @return \Illuminate\Broadcasting\Channel|array
 */
public function broadcastOn($event)
{
    return match ($event) {
        'deleted' => [], // disable broadcasting of the 'deleted' model event
        default => ['users'],
    };
}
```

Now only the `created` and `updated` events for this Model will broadcast.

##### Broadcast Data

By default, the package will publish the default Laravel payload which is already used when broadcasting an Event. Once published, its JSON representation could look like this:

```json
{
    "model": {
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

However, using the `broadcastWith` method, you will be able to define exactly what kind of payload is used when broadcasting, here is an example:

```php
/**
 * Define and format the payload of data to broadcast.
 *
 * @param string $event
 * @return array
 */
public function broadcastWith($event)
{
    return [
        'action' => $event, // could be 'created', 'updated'...
        'data' => [
            'order-id' => $this->id,
            'order-total' => $this->total,
        ],
    ];
}
```

##### Broadcast Name / Subject

If you wish to customize the `Subject` of your SNS notification here, it's exactly like for basic events, the only difference being that the actual event name (`created`, `updated`...) is given to you within the `broadcastAs()` method so you can decide wether you want to use it or not. Here is an example:

```php
/**
 * The event's broadcast name/subject.
 *
 * @return string
 */
public function broadcastAs($event)
{
    return "orders.{$event}";
}
```

Remember that if you don't use `broadcastAs()` at all, Laravel will default to use the event class name, and here for an Order model for example you could see `OrderCreated` as the `Subject`.

## Subscribing / Listening

### Configuration

Similar to what you would do for a standard Laravel SQS queue, you will need to add the following connection and configure your credentials in the `config/queue.php` configuration file:

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

### Usage

####

## Testing

Run the tests with:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

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
