<?php
namespace Vda\ServiceIntegration\Event;

class EventListenerConfig
{
    private $listenerId;

    /**
     * @var ChannelSettings[]
     */
    private $eventChannels;

    /**
     * @var ChannelSettings[]
     */
    private $taskChannels;

    /**
     * @param $listenerId
     * @param ChannelSettings[] $eventChannels
     * @param ChannelSettings[] $taskChannels
     */
    public function __construct(
        $listenerId,
        $eventChannels = [],
        $taskChannels = []
    ) {
        $this->listenerId = $listenerId;
        $this->eventChannels = (array)$eventChannels;
        $this->taskChannels = (array)$taskChannels;
    }

    /**
     * @return mixed
     */
    public function getListenerId()
    {
        return $this->listenerId;
    }

    /**
     * @return ChannelSettings[]
     */
    public function getEventChannels()
    {
        return $this->eventChannels;
    }

    /**
     * @return ChannelSettings[]
     */
    public function getTaskChannels()
    {
        return $this->taskChannels;
    }
}
