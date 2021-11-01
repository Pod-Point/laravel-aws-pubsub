<?php

namespace PodPoint\AwsPubSub\Pub\Broadcasting\Broadcasters;

use Aws\EventBridge\EventBridgeClient;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;

class EventBridgeBroadcaster extends Broadcaster
{
    /**
     * @var string
     */
    protected $source;

    /**
     * @var string
     */
    protected $eventBusName;

    /**
     * @var EventBridgeClient
     */
    protected $eventBridgeClient;

    /**
     * EventBridgeBroadcaster constructor.
     *
     * @param  string  $source
     * @param  string  $eventBusName
     */
    public function __construct(string $source = '', string $eventBusName = '')
    {
        $this->source = $source;
        $this->eventBusName = $eventBusName;
        $this->eventBridgeClient = app(EventBridgeClient::class);
    }

    /**
     * @inheritDoc
     */
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        $events = collect($channels)
            ->map(function ($channel) use ($event, $payload) {
                return [
                    'Detail' => json_encode($payload),
                        'DetailType' => $event,
                        'EventBusName' => $channel,
                        'Source' => $this->source,
                    ];
            })
            ->all();

        $this->eventBridgeClient->putEvents([
            'Entries' => $events
        ]);
    }

    /**
     * @inheritDoc
     */
    public function auth($request)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function validAuthenticationResponse($request, $result)
    {
        return true;
    }
}
