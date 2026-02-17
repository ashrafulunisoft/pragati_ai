<?php

namespace Monyxie\Agora\TokenBuilder;

class Message
{
    /**
     * @var int|null
     */
    private $salt;
    /**
     * @var float|int|null
     */
    private $ts;
    /**
     * @var array|null
     */
    private $privileges;

    /**
     * Message constructor.
     * @param null $privileges
     * @param null $ts
     * @param null $salt
     */
    public function __construct($privileges = null, $ts = null, $salt = null)
    {
        $this->privileges = $privileges ?? [];
        $this->ts = $ts ?? time() + 24 * 3600;
        $this->salt = $salt ?? rand(0, 100000);
    }

    /**
     * @param $msg
     * @return Message
     */
    public static function fromString($msg)
    {
        $pos = 0;
        $salt = unpack("V", substr($msg, $pos, 4))[1];
        $pos += 4;
        $ts = unpack("V", substr($msg, $pos, 4))[1];
        $pos += 4;
        $size = unpack("v", substr($msg, $pos, 2))[1];
        $pos += 2;

        $privileges = array();
        for ($i = 0; $i < $size; $i++) {
            $key = unpack("v", substr($msg, $pos, 2));
            $pos += 2;
            $value = unpack("V", substr($msg, $pos, 4));
            $pos += 4;
            $privileges[$key[1]] = $value[1];
        }

        $message = new static($privileges, $ts, $salt);
        return $message;
    }

    /**
     * @return mixed
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param mixed $salt
     * @return Message
     */
    public function withSalt(int $salt): Message
    {
        $message = clone($this);
        $message->salt = $salt;
        return $message;
    }

    /**
     * @return mixed
     */
    public function getTs()
    {
        return $this->ts;
    }

    /**
     * @param mixed $ts
     * @return Message
     */
    public function withTs(int $ts): Message
    {
        $message = clone($this);
        $message->ts = $ts;
        return $message;
    }

    /**
     * @return mixed
     */
    public function getPrivileges()
    {
        return $this->privileges;
    }

    /**
     * @param mixed $privileges
     * @return Message
     */
    public function withPrivileges(array $privileges): Message
    {
        $message = clone($this);
        $message->privileges = $privileges;
        return $message;
    }

    /**
     * @return string
     */
    public function toString()
    {
        $buffer = pack("V", $this->salt);
        $buffer .= pack("V", $this->ts);
        $buffer .= pack("v", sizeof($this->privileges));

        foreach ($this->privileges as $key => $value) {
            $buffer .= pack("v", $key);
            $buffer .= pack("V", $value);
        }

        return $buffer;
    }

}

