<?php
namespace Vda\ServiceIntegration\Event\Impl;

use Vda\Messaging\IMessageProducer;
use Vda\Messaging\IMessengerFactory;
use Vda\Messaging\Message;
use Vda\Messaging\MessagingException;
use Vda\Messaging\Subscription;
use Vda\ServiceIntegration\Event\BaseEvent;
use Vda\ServiceIntegration\Event\Event;
use Vda\ServiceIntegration\Event\IEventListener;
use Vda\ServiceIntegration\Event\IEventService;
use Vda\ServiceIntegration\Event\ListenerConfig;
use Vda\ServiceIntegration\Event\Task;
use Vda\Util\BeanUtil;

class EventService implements IEventService
{
    /**
     * @var IMessengerFactory
     */
    private $messengerFactory;

    /**
     * @var IMessageProducer
     */
    private $publisher;

    private $publisherId;

    /**
     * @var \Log_Logger
     */
    private $log;

    public function __construct(IMessengerFactory $messengerFactory, $publisherId = 'event-publisher')
    {
        $this->messengerFactory = $messengerFactory;
        $this->publisherId = $publisherId;
        $this->log = \Log_LoggerFactory::getLogger('service-event-publishing');
    }

    /**
     * @param BaseEvent $baseEvent
     * @return Message
     */
    private function wrapEventInMessage(BaseEvent $baseEvent)
    {
        $message = new Message(
            BeanUtil::toJson($baseEvent, BaseEvent::getTransientFields())
        );

        $message->setPersistent($baseEvent->isPersistent());
        $message->setPriority($baseEvent->getPriority());

        return $message;
    }

    /**
     * @param Message $message
     * @return BaseEvent
     */
    public function extractEventFromMessage(Message $message)
    {
        $eventData = json_decode($message->getBody(), true);
        $baseEvent = new BaseEvent(
            $eventData['channel'],
            $eventData['type'],
            $eventData['data']
        );
        return $baseEvent;
    }

    /**
     * @param BaseEvent $message
     * @param bool $suppressExceptions
     * @throws \RuntimeException
     * @throws \Exception
     */
    private function send(BaseEvent $message, $suppressExceptions = false)
    {
        try {
            if (empty($this->publisher)) {
                $this->publisher = $this->messengerFactory->createMessenger(
                    $this->publisherId
                );
            }

            if ($message instanceof Event) {
                $this->publisher->send(
                    $this->formatTopicChannel($message->getChannel()),
                    $this->wrapEventInMessage($message)
                );

            } elseif ($message instanceof Task) {
                $this->publisher->send(
                    $this->formatQueueChannel($message->getChannel()),
                    $this->wrapEventInMessage($message)
                );

            } else {
                throw new \Exception('Unsupported message class');
            }

        } catch (MessagingException $e) {
            $this->log->warn("Message broadcast failed", $e);

            if (!$suppressExceptions) {
                throw new \RuntimeException("Failed to broadcast event", 0, $e);
            }
        }
    }

    /**
     * @param Event $event
     * @param bool $suppressExceptions
     * @throws \RuntimeException
     */
    public function publish(Event $event, $suppressExceptions = false)
    {
        $this->send($event, $suppressExceptions);
    }

    /**
     * @param Task $task
     * @param bool $suppressExceptions
     * @throws \RuntimeException
     */
    public function enqueue(Task $task, $suppressExceptions = false)
    {
        $this->send($task, $suppressExceptions);
    }

    /**
     * @param ListenerConfig $listenerConfig
     * @return IEventListener
     */
    public function createListener(ListenerConfig $listenerConfig)
    {
        $consumer = $this->messengerFactory->createMessenger($listenerConfig->getListenerId());

        $ackMode = $listenerConfig->getIsAutoAck()
            ? Subscription::ACK_AUTO
            : Subscription::ACK_INDIVIDUAL;

        $isDurable = $listenerConfig->getIsDurable();

        foreach ((array)$listenerConfig->getEventChannels() as $eventChannel) {
            $channel = $this->formatTopicChannel($eventChannel);

            $consumer->subscribe(
                new Subscription(
                    $channel,
                    'topic_' . $listenerConfig->getListenerId() . '_' . $channel,
                    $isDurable,
                    $ackMode
                )
            );
        }

        foreach ((array)$listenerConfig->getTaskChannels() as $taskChannel) {
            $channel = $this->formatQueueChannel($taskChannel);

            $consumer->subscribe(
                new Subscription(
                    $channel,
                    'queue_'  . $listenerConfig->getListenerId() . '_' . $channel,
                    true, //queue is always durable
                    $ackMode
                )
            );
        }

        return new EventListener($consumer, $this, $listenerConfig->getIsAutoAck());
    }

    private function formatTopicChannel($channel)
    {
        return '/topic/' . trim($channel, '/');
    }

    private function formatQueueChannel($channel)
    {
        return '/queue/' . trim($channel, '/');
    }
}
