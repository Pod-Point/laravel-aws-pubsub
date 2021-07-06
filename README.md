# Laravel SNS Broadcaster

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pod-point/laravel-sns-broadcast-driver.svg?style=flat-square)](https://packagist.org/packages/pod-point/laravel-sns-broadcast-driver)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/pod-point/laravel-sns-broadcast-driver/run-tests?label=tests)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/pod-point/laravel-sns-broadcast-driver.svg?style=flat-square)](https://packagist.org/packages/pod-point/laravel-sns-broadcast-driver)

This package adds support for broadcasting events via SNS (Simple Notification Service).

## Installation

You can install the package via composer:

```bash
composer require pod-point/laravel-sns-broadcaster:^0.1
```

Add the sns driver to `config/broadcasting.php` in the `connections` array:

```php
'sns' => [
    'driver' => 'sns',
    'region' => env('AWS_DEFAULT_REGION'),
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'arn-prefix' => env('BROADCAST_TOPIC_ARN_PREFIX'),
],
```

Update the `.env` to use the driver and add the AWS values:

```dotenv
BROADCAST_DRIVER=sns
AWS_DEFAULT_REGION=eu-west-1
AWS_ACCESS_KEY_ID=YOUR-AWS-KEY
AWS_SECRET_ACCESS_KEY=YOUR-AWS-SECRET
BROADCAST_TOPIC_ARN_PREFIX=arn:aws:sns:eu-east-1:123345666: #note the arn prefix contains colon
```

Finally, to use broadcasting, enable `App\Providers\BroadcastServiceProvider::class` in `config/app.php` in the `providers` array.

## Usage

### Model Events

To broadcast Model Events, first add the `PodPoint\SnsBroadcaster\BroadcastsEvents` trait to your Model.

```injectablephp
use Illuminate\Foundation\Auth\User as Authenticatable;
use PodPoint\SnsBroadcaster\BroadcastsEvents;

class User extends Authenticatable 
{
    use BroadcastsEvents;
}
```

Next, add the topic to the `broadcastOn` method in the Model.

```injectablephp
/**
 * Get the channels that model events should broadcast on.
 *
 * @param string $event
 * @return array
 */
public function broadcastOn($event)
{
    return ['users'];
}
```

#### Customizing the published data

By default, the package will publish the default Laravel payload, but you can transform the data that is published by transforming the data using `broadcastWith`.

Here you, can define exactly what payload gets published.

The `broadcastWith()` method receives an `$event` parameter that specifies the type of action performed, e.g. created.

```injectablephp
/**
 * Get and format the data to broadcast.
 *
 * @return array
 */
public function broadcastWith($event)
{
    return [
        'action' => $event,
        'data' => [
            'user' => $this,
            'foo' => 'bar',
        ],
    ];
}
```

#### Defining which actions are publishable

By default, the following actions performed on a Model will be published: 

`created`, `updated`, `deleted` and if soft delete is enabled, `trashed`, `restored`.

To only publish specific actions from the list above, add a `broadcastEvents` method the model and define an array of the publishable actions:

```injectablephp
/**
 * Get the events to broadcast to.
 *
 * @return array
 */
public function broadcastEvents()
{
    return ['created', 'updated'];
}
```

Now, only the created and updated events for this Model will be published.

### Custom Events

To broadcast any other custom event, add the `ShouldBroadcast` trait to the Event.

```injectablephp
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserRegistered implements ShouldBroadcast {
       
}
```

Next, add the topic to the `broadcastOn` method in the Event.

```injectablephp
/**
 * Get the channels that model events should broadcast on.
 *
 * @param string $event
 * @return array
 */
public function broadcastOn()
{
    return ['users'];
}
```

#### Customizing the published data
By default, all public properties on the Event will be added to the payload that is published.

Unlike a Model Event, you will need to manually set an action as a public property to the Event if you wish to see it in the payload.

```injectablephp
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRetrieved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action = 'registered';
}
```

However, you can transform the data that is published by transforming the data using `broadcastWith`.

```injectablephp

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRetrieved implements ShouldBroadcast {

    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $action = 'registered';
    
    /**
     * @var User
     */
    public $user;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
    
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['users-local'];
    }
    
    /**
     * Get and format the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'action' => $this->action,
            'data' => [
                'user' => $this->user,
                'foo' => 'bar',
            ],
        ];
    }
}
```

Now, when the event is triggered, it will broadcast to the channel defined in the Event with the data defined in the `broadcastWith` method.

An Event Listener will also receive the same payload when the Event has been fired.

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
- [All Contributors](https://github.com/pod-point/laravel-sns-broadcast-driver/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

<img src="https://d3h256n3bzippp.cloudfront.net/pod-point-logo.svg" align="right" />

Travel shouldn't damage the earth 🌍

Made with ❤️ at [Pod Point](https://pod-point.com)
