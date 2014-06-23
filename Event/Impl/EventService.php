<?php
namespace Vda\ServiceIntegration\Event\Impl;

use Vda\Messaging\IMessageProducer;
use Vda\Messaging\IMessengerFactory;
use Vda\Messaging\Message;
use Vda\Messaging\MessagingException;
use Vda\Messaging\Subscription;
use Vda\ServiceIntegration\Event\AbstractEvent;
use Vda\ServiceIntegration\Event\ChannelSettings;
use Vda\ServiceIntegration\Event\Event;
use Vda\ServiceIntegration\Event\Task;
use Vda\ServiceIntegration\Event\IEventListener;
use Vda\ServiceIntegration\Event\IEventService;
use Vda\ServiceIntegration\Event\EventListenerConfig;
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
     * @param AbstractEvent $baseEvent
     * @return Message
     */
    private function wrapEventInMessage(AbstractEvent $baseEvent)
    {
        $message = new Message(
            BeanUtil::toJson($baseEvent, AbstractEvent::getTransientFields())
        );

        $message->setPersistent($baseEvent->isPersistent());
        $message->setPriority($baseEvent->getPriority());

        return $message;
    }

    /**
     * @param Message $message
     * @throws \Exception
     * @return AbstractEvent
     */
    public function extractEventFromMessage(Message $message)
    {
        $eventData = json_decode($message->getBody(), true);

        switch ($eventData['channelType']) {
            case AbstractEvent::CHANNEL_TYPE_TASK:
                $baseEvent = new Task(
                    $eventData['channel'],
                    $eventData['type'],
                    $eventData['data']
                );
                break;

            default:
            case AbstractEvent::CHANNEL_TYPE_EVENT:
                $baseEvent = new Event(
                    $eventData['channel'],
                    $eventData['type'],
                    $eventData['data']
                );
                break;
        }
        return $baseEvent;
    }

    /**
     * @param AbstractEvent $message
     * @param bool $suppressExceptions
     * @throws \RuntimeException
     * @throws \Exception
     */
    private function send(AbstractEvent $message, $suppressExceptions = false)
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
     * @param EventListenerConfig $listenerConfig
     * @return IEventListener
     */
    public function createListener(EventListenerConfig $listenerConfig)
    {
        $consumer = $this->messengerFactory->createMessenger($listenerConfig->getListenerId());


        foreach ((array)$listenerConfig->getEventChannels() as $eventChannel) {
            /* @var $eventChannel ChannelSettings */

            $channel = $this->formatTopicChannel($eventChannel->getChannel());

            $consumer->subscribe(
                new Subscription(
                    $channel,
                    'topic_' . $listenerConfig->getListenerId() . '_' . $channel,
                    $eventChannel->isDurable(),
                    $eventChannel->isAutoAck() ? Subscription::ACK_AUTO : Subscription::ACK_INDIVIDUAL
                )
            );
        }

        foreach ((array)$listenerConfig->getTaskChannels() as $taskChannel) {
            /* @var $taskChannel ChannelSettings */

            $channel = $this->formatQueueChannel($taskChannel->getChannel());

            $consumer->subscribe(
                new Subscription(
                    $channel,
                    'queue_'  . $listenerConfig->getListenerId() . '_' . $channel,
                    true, //queue is always durable
                    $taskChannel->isAutoAck() ? Subscription::ACK_AUTO : Subscription::ACK_INDIVIDUAL
                )
            );
        }

        return new EventListener($consumer, $this, $listenerConfig);
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
