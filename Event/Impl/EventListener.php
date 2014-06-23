<?php
namespace Vda\ServiceIntegration\Event\Impl;

use Vda\Messaging\IMessageConsumer;
use Vda\Messaging\Message;
use Vda\Messaging\MessagingException;
use Vda\ServiceIntegration\Event\AbstractEvent;
use Vda\ServiceIntegration\Event\ChannelSettings;
use Vda\ServiceIntegration\Event\EventListenerConfig;
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
     * @var \Vda\ServiceIntegration\Event\EventListenerConfig
     */
    private $listenerConfig;

    /**
     * @var ChannelSettings[]
     */
    private $eventChannelsSettings = [];

    /**
     * @var ChannelSettings[]
     */
    private $taskChannelsSettings = [];

    /**
     * @var array
     */
    private $messagesToAck = [];

    public function __construct(
        IMessageConsumer $consumer,
        EventService $eventService,
        EventListenerConfig $listenerConfig
    ) {
        $this->consumer = $consumer;
        $this->eventService = $eventService;
        $this->listenerConfig = $listenerConfig;

        foreach ((array)$listenerConfig->getEventChannels() as $eventChannel) {
            /* @var $eventChannel ChannelSettings */
            $this->eventChannelsSettings[$eventChannel->getChannel()] = $eventChannel;
        }

        foreach ((array)$listenerConfig->getTaskChannels() as $taskChannel) {
            /* @var $taskChannel ChannelSettings */
            $this->taskChannelsSettings[$taskChannel->getChannel()] = $taskChannel;
        }
    }

    /**
     * Method return BaseEvent
     * If there are no message after timeout or empty message received, then method return null
     *
     * @param $timeout
     * @throws \Vda\ServiceIntegration\Event\Exception\MessageReceivingFailedException
     * @return AbstractEvent|null
     */
    public function receive($timeout = -1)
    {
        $startTime = time();

        while (true) {
            $message = null;

            try {
                $message = $this->consumer->receive($timeout);

            } catch (MessagingException $e) {
                if ($e->getMessage() != 'Unable to read message') {
                    throw new MessageReceivingFailedException('Failed to receive a message.', 0, $e);
                }

                if ($timeout >= 0) {
                    $timeout -= time() - $startTime;
                    if ($timeout < 0)  {
                        return null;
                    }
                } else {
                    $sleep = rand(1, 5);
                    sleep($sleep);

                    continue;
                }
            } catch(\Exception $e)  {
                throw new MessageReceivingFailedException('Failed to receive a message.', 0, $e);
            }

            if (is_null($message)) {
                return null;
            }

            $baseEvent = $this->eventService->extractEventFromMessage($message);

            if (!$this->isEventAutoAck($baseEvent)) {
                $this->messagesToAck[spl_object_hash($baseEvent)] = $message;
            }

            return $baseEvent;
        }
    }

    private function isEventAutoAck(AbstractEvent $baseEvent)
    {
        switch ($baseEvent->getChannelType()) {
            case AbstractEvent::CHANNEL_TYPE_TASK:
                $channelSettings = $this->taskChannelsSettings[$baseEvent->getChannel()];

                return $channelSettings->isAutoAck();

            default:
            case AbstractEvent::CHANNEL_TYPE_EVENT:
                $channelSettings = $this->eventChannelsSettings[$baseEvent->getChannel()];

                return $channelSettings->isAutoAck();
        }
    }

    /**
     * @param AbstractEvent $baseEvent
     * @throws \Vda\ServiceIntegration\Event\Exception\MessageAckFailedException
     */
    public function ack(AbstractEvent $baseEvent)
    {
        if (!$this->isEventAutoAck($baseEvent)) {
            $messageHash = spl_object_hash($baseEvent);

            if (!array_key_exists($messageHash, $this->messagesToAck)) {
                throw new MessageAckFailedException('Failed to ack message. Message not found by Event');
            }

            try {
                $message = $this->messagesToAck[$messageHash];
                /* @var $message Message*/
                $this->consumer->ack($message->getId());

                unset($this->messagesToAck[$messageHash]);

            } catch(\Exception $e) {
                throw new MessageAckFailedException('Failed to ack message.', 0, $e);
            }
        }
    }
}
