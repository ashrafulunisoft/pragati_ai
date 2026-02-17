<?php

namespace Monyxie\Agora\TokenBuilder;

use Monyxie\Agora\TokenBuilder\Exceptions\RuntimeException;

/**
 * Class Token
 * @package Monyxie\Agora\TokenBuilder
 */
class Token
{
    /**
     *
     */
    const VERSION = "006";
    /**
     * @var string
     */
    private $appCertificate;
    /**
     * @var string
     */
    private $channelName;
    /**
     * @var string
     */
    private $uid;
    /**
     * @var string
     */
    private $appId;
    /**
     * @var int
     */
    private $ts;
    /**
     * @var int
     */
    private $salt;
    /**
     * @var array
     */
    private $privileges;

    /**
     * Token constructor.
     * @param string $appId
     * @param string $appCertificate
     * @param string $channelName
     * @param string $uid
     * @param array $privileges
     * @param int $ts
     * @param int $salt
     */
    public function __construct(
        string $appId = null,
        string $appCertificate = null,
        string $channelName = null,
        string $uid = null,
        array $privileges = null,
        int $ts = null,
        int $salt = null)
    {
        $this->appId = $appId;
        $this->appCertificate = $appCertificate;
        $this->channelName = $channelName;
        $this->uid = $uid === '0' ? '' : $uid;
        $this->privileges = $privileges ?: [];
        $this->ts = $ts;
        $this->salt = $salt;
    }

    /**
     * @param string $token
     * @return Token
     */
    public static function fromString(string $token): Token
    {
        $versionLen = 3;
        $appIdLen = 32;
        $version = substr($token, 0, $versionLen);
        if ($version !== self::VERSION) {
            throw new RuntimeException("Invalid version: $version.");
        }

        $appId = substr($token, $versionLen, $appIdLen);
        $content = (base64_decode(substr($token, $versionLen + $appIdLen, strlen($token) - ($versionLen + $appIdLen))));

        $pos = 0;
        $len = unpack("v", $content . substr($pos, 2))[1];
        $pos += 2;
//        $sig = substr($content, $pos, $len);
        $pos += $len;
//        $crc_channel = unpack("V", substr($content, $pos, 4))[1];
        $pos += 4;
//        $crc_uid = unpack("V", substr($content, $pos, 4))[1];
        $pos += 4;
        $msgLen = unpack("v", substr($content, $pos, 2))[1];
        $pos += 2;
        $msg = substr($content, $pos, $msgLen);

        $message = Message::fromString($msg);

        $accessToken = new static();
        $accessToken->appId = $appId;
        $accessToken->privileges = $message->getPrivileges();
        $accessToken->salt = $message->getSalt();
        $accessToken->ts = $message->getTs();

        return $accessToken;
    }

    /**
     * @return string
     * @throws RuntimeException
     */
    public function toString()
    {
        foreach (['appId', 'appCertificate', 'channelName'] as $field) {
            if (!is_string($this->$field) || $this->$field === "") {
                throw new RuntimeException("$field should be a non-empty string.");
            }
        }

        $message = new Message($this->privileges, $this->ts, $this->salt);
        $msg = $message->toString();

        $signStr = $this->appId . $this->channelName . $this->uid . $msg;
        $sig = hash_hmac('sha256', $signStr, $this->appCertificate, true);

        $crcChannelName = crc32($this->channelName) & 0xffffffff;
        $crcUid = crc32($this->uid) & 0xffffffff;

        $content =
            pack("v", strlen($sig))
            . $sig
            . pack("V", $crcChannelName)
            . pack("V", $crcUid)
            . pack("v", strlen($msg))
            . $msg;

        $ret = self::VERSION . $this->appId . base64_encode($content);
        return $ret;
    }

    /**
     * @return string
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    /**
     * @param string $appId
     * @return Token
     */
    public function withAppId(string $appId): Token
    {
        $token = clone($this);
        $token->appId = $appId;
        return $token;
    }

    /**
     * @return string
     */
    public function getAppCertificate(): string
    {
        return $this->appCertificate;
    }

    /**
     * @param string $appCertificate
     * @return Token
     */
    public function withAppCertificate(string $appCertificate): Token
    {
        $token = clone($this);
        $token->appCertificate = $appCertificate;
        return $token;
    }

    /**
     * @return string
     */
    public function getChannelName(): string
    {
        return $this->channelName;
    }

    /**
     * @param string $channelName
     * @return Token
     */
    public function withChannelName(string $channelName): Token
    {
        $token = clone($this);
        $token->channelName = $channelName;
        return $token;
    }

    /**
     * @return int
     */
    public function getTs(): int
    {
        return $this->ts;
    }

    /**
     * @param int $ts
     * @return Token
     */
    public function withTs(int $ts): Token
    {
        $token = clone($this);
        $token->ts = $ts;
        return $token;
    }

    /**
     * @return int
     */
    public function getSalt(): int
    {
        return $this->salt;
    }

    /**
     * @param int $salt
     * @return Token
     */
    public function withSalt(int $salt): Token
    {
        $token = clone($this);
        $token->salt = $salt;
        return $token;
    }

    /**
     * @return array
     */
    public function getPrivileges(): array
    {
        return $this->privileges;
    }

    /**
     * @param array $privileges
     * @return Token
     */
    public function withPrivileges(array $privileges): Token
    {
        $token = clone($this);
        $token->privileges = $privileges;
        return $token;
    }

    /**
     * @param int $privilege
     * @param int $expireTimestamp
     * @return Token
     */
    public function withAddedPrivilege(int $privilege, int $expireTimestamp): Token
    {
        $token = clone($this);
        $token->privileges [$privilege] = $expireTimestamp;
        return $token;
    }

    /**
     * @param int $privilege
     * @return Token
     */
    public function withRemovedPrivilege(int $privilege): Token
    {
        $token = clone($this);
        unset($token->privileges[$privilege]);
        return $token;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     * @return Token
     */
    public function withUid(string $uid): Token
    {
        $token = clone($this);
        if ($uid === '0') {
            $token->uid = "";
        } else {
            $token->uid = $uid;
        }

        return $token;
    }
}
