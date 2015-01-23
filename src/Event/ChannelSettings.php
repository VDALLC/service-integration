<?php
namespace Vda\ServiceIntegration\Event;

class ChannelSettings
{
    private $channel;
    private $durable;
    private $isAutoAck;

    public function __construct(
        $channel,
        $isDurable = false,
        $isAutoAck = true
    ) {
        $this->channel = $channel;
        $this->durable = $isDurable;
        $this->isAutoAck = $isAutoAck;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function isDurable()
    {
        return $this->durable;
    }

    public function isAutoAck()
    {
        return $this->isAutoAck;
    }
}
