<?php
namespace Vda\ServiceIntegration\Event;

class ListenerConfig
{
    private $listenerId;
    private $eventChannels;
    private $taskChannels;
    private $isAutoAck;
    private $isDurable;

    /**
     * @param $listenerId
     * @param array $eventChannels
     * @param array $taskChannels
     * @param bool $isAutoAck
     * @param bool $isDurable
     */
    public function __construct(
        $listenerId,
        $eventChannels = [],
        $taskChannels = [],
        $isAutoAck = false,
        $isDurable = false
    ) {
        $this->listenerId = $listenerId;
        $this->eventChannels = $eventChannels;
        $this->taskChannels = $taskChannels;
        $this->isAutoAck = $isAutoAck;
        $this->isDurable = $isDurable;
    }

    /**
     * @return mixed
     */
    public function getListenerId()
    {
        return $this->listenerId;
    }

    /**
     * @return array
     */
    public function getEventChannels()
    {
        return $this->eventChannels;
    }

    /**
     * @return array
     */
    public function getTaskChannels()
    {
        return $this->taskChannels;
    }

    /**
     * @return boolean
     */
    public function getIsAutoAck()
    {
        return $this->isAutoAck;
    }

    /**
     * @return mixed
     */
    public function getIsDurable()
    {
        return $this->isDurable;
    }
}
