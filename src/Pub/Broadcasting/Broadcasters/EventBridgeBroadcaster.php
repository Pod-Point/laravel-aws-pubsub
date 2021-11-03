<?php

namespace PodPoint\AwsPubSub\Pub\Broadcasting\Broadcasters;

use Aws\EventBridge\EventBridgeClient;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;

class EventBridgeBroadcaster extends Broadcaster
{
    /**
     * @var EventBridgeClient
     */
    protected $eventBridgeClient;

    /**
     * @var string
     */
    protected $source;

    /**
     * EventBridgeBroadcaster constructor.
     *
     * @param EventBridgeClient $eventBridgeClient
     * @param string $source
     */
    public function __construct(EventBridgeClient $eventBridgeClient, string $source = '')
    {
        $this->eventBridgeClient = $eventBridgeClient;
        $this->source = $source;
    }

    /**
     * @inheritDoc
     */
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        $events = $this->mapToEventBridgeEntries($channels, $event, $payload);

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

    /**
     * @param array $channels
     * @param string $event
     * @param array $payload
     * @return array
     */
    protected function mapToEventBridgeEntries(array $channels, string $event, array $payload): array
    {
        return collect($channels)
            ->map(function ($channel) use ($event, $payload) {
                return [
                    'Detail' => json_encode($payload),
                    'DetailType' => $event,
                    'EventBusName' => $channel,
                    'Source' => $this->source,
                ];
            })
            ->all();
    }
}
