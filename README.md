# AWS SNS driver for Laravel Broadcasting

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pod-point/laravel-sns-broadcaster.svg?style=flat-square)](https://packagist.org/packages/pod-point/laravel-sns-broadcaster)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/pod-point/laravel-sns-broadcaster/run-tests?label=tests)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/pod-point/laravel-sns-broadcaster.svg?style=flat-square)](https://packagist.org/packages/pod-point/laravel-sns-broadcaster)

Similar to [Pusher](https://laravel.com/docs/master/broadcasting#pusher-channels), this package provides a [Laravel Broadcasting](https://laravel.com/docs/master/broadcasting) driver for [AWS SNS](https://aws.amazon.com/sns/) (Simple Notification Service).

In this context, "channels" can be assimilated to "topics" on SNS.

We believe this approach of leveraging broadcasting makes sense for a Pub/Sub architecture where an application would like to broadcast an event to the outside world about something that just happened.

## Installation

You can install the package on a Laravel 8+ application via composer:

```bash
composer require pod-point/laravel-sns-broadcaster:^0.1
```

Next, you should add the following connection and configure your SNS credentials in the `config/broadcasting.php` configuration file:

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
```

The `arn-suffix` can be used to help manage SNS topics for different environments. It will be added to the end when constructing the full TopicARN. 

```dotenv
BROADCAST_TOPIC_ARN_SUFFIX=-local # optional
```

Next, you will need to change your broadcast driver to SNS in your `.env` file:

```dotenv
BROADCAST_DRIVER=sns
```

Finally, don't forget to enable the [Broadcast Service Provider](https://laravel.com/docs/master/broadcasting#broadcast-service-provider).

## Usage

Here we will simply re-use the power of Laravel Broadcasting, out of the box, with some minor additional functionalities for the Eloquent Model Events.

### Broadcast Events

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
        return ['orders']; // for the ARN 'arn:aws:sns:us-east-1:123456789:orders'
    }
}
```

#### The published payload

By default, the package will publish the default Laravel payload which is already used when broadcasting an Event. Once published, its JSON representation looks like this:

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

    public $action = 'parcel_collected';

    // ...
}
```

Which would produce the following payload:

```json
{
    "action": "parcel_collected",
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
        'action' => 'parcel_collected',
        'data' => [
            'order-id' => $this->order->id,
            'order-total' => $this->order->total,
        ],
    ];
}
```

Now, when the event is triggered, it will behave like a standard Laravel event, which means other Listeners can listen to it, as usual, but it will also be broadcasted to the topic defined by the `broadcastOn` method using the payload defined by the `broadcastWith` method.

#### Setting the Subject

By default, the package will set the `Subject` in the following format: `{channel}.{event_class}` = `orders.order_shipped`

You can override this by adding a public property named `$action`.

```php
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderShipped implements ShouldBroadcast
{
    use SerializesModels;

    public $action = 'parcel_collected';

    /**
     * Get the topics that model events should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return ['orders']; // for the ARN 'arn:aws:sns:us-east-1:123456789:orders'
    }
}
```

This will set the Subject to `orders.order_shipped`

If you are using `broadcastWith`, make sure to add the `action` to the output.

```php
/**
 * Get and format the data to broadcast.
 *
 * @return array
 */
public function broadcastWith()
{
    return [
        'action' => 'parcel_collected',
        'data' => [
            'order-id' => $this->order->id,
            'order-total' => $this->order->total,
        ],
    ];
}
```

The Subject for the above will be `orders.parcel_collected`. 

### Broadcast Eloquent Model Events

If you're familiar with [Model Observers](https://laravel.com/docs/master/eloquent#observers), you already know that Eloquent models dispatch several events during their lifecycle.

In order to broadcast the model events, you need to use the `PodPoint\SnsBroadcaster\BroadcastsEvents` trait on your Model.

```php
use Illuminate\Database\Eloquent\Model;
use PodPoint\SnsBroadcaster\BroadcastsEvents;

class Order extends Model
{
    use BroadcastsEvents;
}
```

#### The Events

In the context of broadcasting, only the following model events can be broadcasted:

- `created`
- `updated`
- `deleted`
- `trashed` __if soft delete is enabled__
- `restored` __if soft delete is enabled__

By default, all of these events are broadcasted, but you can define which events in particular you'd like to broadcast using the `broadcastEvents` method on the model itself:

```php
/**
 * Define the model events to broadcast.
 *
 * @return array
 */
public function broadcastEvents()
{
    return ['created', 'updated'];
}
```

Now only the `created` and `updated` events for this Model will be published.

#### The Channels / Topics

Next, in a similar fashion to what you're used to with Laravel, specify the channel/topic you'd like to use within the `broadcastOn` method on the model.

The `broadcastOn()` method receives an `$event` argument that holds the event name as a string, e.g. `created`.

```php
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
```

#### The published payload

By default, the package will publish the default Laravel payload which is already used when broadcasting an Event. Once published, its JSON representation looks like this:

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

However, using the `broadcastWith` method, you will be able to define exactly what kind of payload gets published.

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

#### Setting the Subject

By default, the package will set the `Subject` in the following format: `{channel}.{event_class}` = `orders.order_shipped`

You can override this by using `broadcastWith` and adding `action` to the output.

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
}
```

The Subject for the above will be `orders.created`.

### Multiple Channels

You can publish to multiple SNS topics by adding them to the `broadcastOn` array.

```php
/**
 * Get the channels that model events should broadcast on.
 *
 * @param string $event
 * @return array
 */
public function broadcastOn($event)
{
    return ['orders', 'installations'];
}
```

The Subject for each publication will begin with the name of the channel e.g. `orders.order_created` and `installations.order_created`

*Note: the `arn-prefix` and `arn-suffix` will be added to all channels*

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
- [Laravel Package Development](https://laravelpackage.com) documentation by [John Braun](https://github.com/Jhnbrn90)
- [Pod Point](https://github.com/pod-point)
- [All Contributors](https://github.com/pod-point/laravel-sns-broadcaster/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

<img src="https://d3h256n3bzippp.cloudfront.net/pod-point-logo.svg" align="right" />

Travel shouldn't damage the earth 🌍

Made with ❤️&nbsp;&nbsp;at [Pod Point](https://pod-point.com)
