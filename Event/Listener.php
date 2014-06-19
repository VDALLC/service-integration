<?php
namespace Vda\ServiceIntegration\Event;

use Vda\Messaging\IMessageConsumer;
use Vda\Messaging\Subscription;
use Vda\ServiceIntegration\Event\Exception\MessageReceivingFailedException;

class Listener
{
    /**
     * @var IMessageConsumer
     */
    private $consumer;

    /**
     * @var EventService
     */
    private $eventService;

    /**
     * Contains info about subscription ack type.
     * Value must be on of \Vda\Messaging\Subscription::ACK_XXX consts
     * @var number
     */
    private $ackMode;

    public function __construct(
        IMessageConsumer $consumer,
        EventService $eventService,
        $ackMode
    ) {
        $this->consumer = $consumer;
        $this->eventService = $eventService;
        $this->ackMode = $ackMode;
    }

    /**
     * @param $timeout
     * @throws Exception\MessageReceivingFailedException
     * @return BaseEvent
     */
    public function receive($timeout = -1)
    {
        try {
            $message = $this->consumer->receive($timeout);
        } catch(\Exception $e)  {
            throw new MessageReceivingFailedException('Failed to receive a message.', 0, $e);
        }

        if (empty($message)) {
            throw new MessageReceivingFailedException('Failed to receive a message. The message is empty.');
        }

        $baseEvent = $this->eventService->extractEventFromMessage($message);
        $baseEvent->setMessage($message);

        return $baseEvent;
    }

    /**
     * @param BaseEvent $baseEvent
     */
    public function ack(BaseEvent $baseEvent)
    {
        if ($this->ackMode != Subscription::ACK_AUTO) {
            $this->consumer->ack($baseEvent->getMessage()->getId());
        }
    }
}
