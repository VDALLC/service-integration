<?php
namespace Vda\ServiceIntegration\Event\Impl;

use Vda\Messaging\IMessageConsumer;
use Vda\Messaging\Message;
use Vda\ServiceIntegration\Event\BaseEvent;
use Vda\ServiceIntegration\Event\Exception\MessageAckFailedException;
use Vda\ServiceIntegration\Event\Exception\MessageReceivingFailedException;
use Vda\ServiceIntegration\Event\IEventListener;

class EventListener implements IEventListener
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
     * @var boolean
     */
    private $isAutoAck;


    /**
     * @var array
     */
    private $messagesToAck = [];

    public function __construct(
        IMessageConsumer $consumer,
        EventService $eventService,
        $isAutoAck
    ) {
        $this->consumer = $consumer;
        $this->eventService = $eventService;
        $this->isAutoAck = $isAutoAck;
    }

    /**
     * @param $timeout
     * @throws \Vda\ServiceIntegration\Event\Exception\MessageReceivingFailedException
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

        if (!$this->isAutoAck) {
            $this->messagesToAck[spl_object_hash($baseEvent)] = $message;
        }

        return $baseEvent;
    }

    /**
     * @param BaseEvent $baseEvent
     * @throws \Vda\ServiceIntegration\Event\Exception\MessageAckFailedException
     */
    public function ack(BaseEvent $baseEvent)
    {
        if (!$this->isAutoAck) {
            $messageHash = spl_object_hash($baseEvent);

            if (!array_key_exists($messageHash, $this->messagesToAck)) {
                throw new MessageAckFailedException('Failed to ack message. Message not found by Event');
            }

            try {
                $message = $this->messagesToAck[$messageHash];
                /* @var $message Message*/
                $this->consumer->ack($message->getId());

            } catch(\Exception $e) {
                throw new MessageAckFailedException('Failed to ack message.', 0, $e);
            }
        }
    }
}
