<?php
namespace Vda\ServiceIntegration\Event;

class BaseEvent
{
    private static $transientFields = [
        'persistent',
        'priority',
        'message'
    ];

    private $channel;
    private $type;
    private $data;
    private $persistent;
    private $priority;

    /**
     * Link to the message which contains this event
     * @var \Vda\Messaging\Message
     */
    private $message;

    /**
     * @param string $channel Event destination
     * @param string $type Type of this event
     * @param mixed $data Either array or object containing the event data
     * @param bool $persistent
     * @param null $priority
     * @throws \InvalidArgumentException
     */
    public function __construct($channel, $type, $data, $persistent = false, $priority = null)
    {
        if (!is_array($data) && !is_object($data)) {
            throw new \InvalidArgumentException('$data must be either array or object');
        }

        $this->channel = $channel;
        $this->type = $type;
        $this->data = $data;
        $this->persistent = $persistent;
        $this->priority = $priority;
        $this->message = null;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getData()
    {
        return $this->data;
    }

    public function isPersistent()
    {
        return $this->persistent;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public static function getTransientFields()
    {
        return self::$transientFields;
    }

    /**
     * @param \Vda\Messaging\Message $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return \Vda\Messaging\Message|null
     */
    public function getMessage()
    {
        return $this->message;
    }
}
