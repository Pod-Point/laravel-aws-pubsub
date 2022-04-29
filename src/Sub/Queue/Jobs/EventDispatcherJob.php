<?php

namespace PodPoint\AwsPubSub\Sub\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Support\Facades\Log;
use PodPoint\AwsPubSub\Sub\Queue\EventResolvers\EventResolver;

class EventDispatcherJob extends SqsJob implements JobContract
{
    /** @var string */
    private $eventName;

    /** @var array */
    private $eventPayload;

    /** @var string */
    private $eventSubject;

    public function __construct(
        Container $container,
        SqsClient $sqs,
        array $job,
        $connectionName,
        $queue,
        EventResolver $eventResolver
    ) {
        parent::__construct($container, $sqs, $job, $connectionName, $queue);

        $validationResult = $eventResolver->validate($this);

        if (! $validationResult->result()) {
            if ($this->container->bound('log')) {
                Log::error($validationResult->message(), $this->job);
            }

            return;
        }

        $this->eventName = $eventResolver->resolveName($this);
        $this->eventPayload = $eventResolver->resolvePayload($this);
        $this->eventSubject = $eventResolver->resolveSubject($this);
    }

    /**
     * @inheritDoc
     */
    public function fire()
    {
        if ($this->eventName) {
            $this->resolve(Dispatcher::class)->dispatch($this->eventName, [
                'payload' => $this->eventPayload,
                'subject' => $this->eventSubject,
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
