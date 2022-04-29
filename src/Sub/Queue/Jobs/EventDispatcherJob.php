<?php

namespace PodPoint\AwsPubSub\Sub\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Support\Facades\Log;

class EventDispatcherJob extends SqsJob implements JobContract
{
    /** @var string */
    private $eventName;

    private $eventPayload;

    public function __construct(
        Container $container,
        SqsClient $sqs,
        array $job,
        $connectionName,
        $queue,
        string $eventResolver
    ) {
        parent::__construct($container, $sqs, $job, $connectionName, $queue);

        $eventResolver = new $eventResolver($this);

        $validationResult = $eventResolver->validate();

        if (! $validationResult->result()) {
            if ($this->container->bound('log')) {
                Log::error($validationResult->message(), $this->job);
            }

            return;
        }

        $this->eventName = $eventResolver->resolveName();
        $this->eventPayload = $eventResolver->resolvePayload();
    }

    /**
     * @inheritDoc
     */
    public function fire()
    {
        if ($this->eventName) {
            $this->resolve(Dispatcher::class)->dispatch($this->eventName, [
                'payload' => $this->eventPayload,
                'name' => $this->eventName,
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    protected function failed($e)
    {
        // ...
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->eventName;
    }

    /**
     * @inheritDoc
     */
    public function resolveName()
    {
        return $this->getName();
    }
}
