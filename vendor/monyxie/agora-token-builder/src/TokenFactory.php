<?php

namespace Monyxie\Agora\TokenBuilder;

use Monyxie\Agora\TokenBuilder\AccessControl\Privilege;

/**
 * Class TokenFactory
 * @package Monyxie\Agora\TokenBuilder
 */
class TokenFactory
{
    /**
     * @var
     */
    private $appId;
    /**
     * @var
     */
    private $appCertificate;

    /**
     * TokenFactory constructor.
     * @param $appId
     * @param $appCertificate
     */
    public function __construct($appId, $appCertificate)
    {
        $this->appId = $appId;
        $this->appCertificate = $appCertificate;
    }

    /**
     * 创建token
     * @param string|null $channelName
     * @param string|null $uid
     * @param array|null $privileges
     * @param int $expireTimestamp
     * @return Token
     */
    public function create(
        string $channelName = null,
        string $uid = null,
        array $privileges = null,
        int $expireTimestamp = null)
    {
        return new Token($this->appId, $this->appCertificate, $channelName, $uid, $privileges, $expireTimestamp);
    }

    /**
     * 创建 RTM token
     * @param string $uid
     * @return Token
     *
     * @link https://github.com/AgoraIO/Tools/blob/master/DynamicKey/AgoraDynamicKey/php/src/RtmTokenBuilder.php#L12
     * @link https://github.com/AgoraIO/Tools/blob/master/DynamicKey/AgoraDynamicKey/php/sample/RtmTokenBuilderSample.php
     */
    public function createRtmToken(string $uid)
    {
        return new Token($this->appId, $this->appCertificate, $uid, 0, [Privilege::RTM_LOGIN], 0);
    }

    /**
     * 从 token 字符串创建 Token 对象
     * @param $token
     * @param $appCertificate
     * @param $channel
     * @param $uid
     * @return Token
     */
    public function fromString($token, $appCertificate, $channel, $uid)
    {
        return Token::fromString($token)
            ->withAppCertificate($appCertificate)
            ->withChannelName($channel)
            ->withUid($uid);
    }
}
