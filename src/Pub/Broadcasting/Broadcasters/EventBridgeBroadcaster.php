<?php

namespace PodPoint\AwsPubSub\Pub\Broadcasting\Broadcasters;

use Aws\EventBridge\EventBridgeClient;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

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
     * @param  EventBridgeClient  $eventBridgeClient
     * @param  string  $source
     */
    public function __construct(EventBridgeClient $eventBridgeClient, string $source = '')
    {
        $this->eventBridgeClient = $eventBridgeClient;
        $this->source = $source;
    }

    /**
     * @inheritDoc
     */
    public function auth($request)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function validAuthenticationResponse($request, $result)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $events = $this->mapToEventBridgeEntries($channels, $event, $payload);

        $result = $this->eventBridgeClient->putEvents([
            'Entries' => $events,
        ]);

        if ($this->failedToBroadcast($result)) {
            Log::error('Failed to send events to EventBridge', [
                'errors' => collect($result->get('Entries'))->filter(function (array $entry) {
                    return Arr::hasAny($entry, ['ErrorCode', 'ErrorMessage']);
                })->toArray(),
            ]);
        }
    }

    /**
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
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

    protected function failedToBroadcast(?\Aws\Result $result): bool
    {
        return $result
            && $result->hasKey('FailedEntryCount')
            && $result->get('FailedEntryCount') > 0;
    }
}
