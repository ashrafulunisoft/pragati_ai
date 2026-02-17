<?php


use Monyxie\Agora\TokenBuilder\AccessControl\Privilege;
use Monyxie\Agora\TokenBuilder\Token;

class TokenTest extends \PHPUnit\Framework\TestCase
{

    public function testNormalCase()
    {
        $appID = "970CA35de60c44645bbae8a215061b33";
        $appCertificate = "5CFd2fd1755d40ecb72977518be15d3b";
        $channelName = "7d72365eb983485397e3e3f9d460bdda";
        $ts = 1111111;
        $salt = 1;
        $uid = "2882341273";
        $expiredTs = 1446455471;


        $expected = "006970CA35de60c44645bbae8a215061b33IACV0fZUBw+72cVoL9eyGGh3Q6Poi8bgjwVLnyKSJyOXR7dIfRBXoFHlEAABAAAAR/QQAAEAAQCvKDdW";
        $result = (new Token($appID, $appCertificate, $channelName, $uid))
            ->withSalt($salt)
            ->withTs($ts)
            ->withAddedPrivilege(Privilege::JOIN_CHANNEL, $expiredTs)
            ->toString();

        $this->assertEquals($expected, $result);

        $result2 = Token::fromString($expected)
            ->withAppCertificate($appCertificate)
            ->withChannelName($channelName)
            ->withUid($uid)
            ->toString();

        $this->assertEquals($expected, $result2);
    }

    public function testUidIsZero()
    {
        $appID = "970CA35de60c44645bbae8a215061b33";
        $appCertificate = "5CFd2fd1755d40ecb72977518be15d3b";
        $channelName = "7d72365eb983485397e3e3f9d460bdda";
        $ts = 1111111;
        $salt = 1;
        $uid = 0;
        $expiredTs = 1446455471;

        $expected = "006970CA35de60c44645bbae8a215061b33IACw1o7htY6ISdNRtku3p9tjTPi0jCKf9t49UHJhzCmL6bdIfRAAAAAAEAABAAAAR/QQAAEAAQCvKDdW";
        $result = (new Token($appID, $appCertificate, $channelName, $uid))
            ->withSalt($salt)
            ->withTs($ts)
            ->withAddedPrivilege(Privilege::JOIN_CHANNEL, $expiredTs)
            ->toString();

        $this->assertEquals($expected, $result);

        $result2 = Token::fromString($expected)
            ->withAppCertificate($appCertificate)
            ->withChannelName($channelName)
            ->withUid($uid)
            ->toString();

        $this->assertEquals($expected, $result2);
    }

    public function testInvalidAppId()
    {
        $this->expectException(\Monyxie\Agora\TokenBuilder\Exceptions\RuntimeException::class);

        (new Token())->toString();
    }
}
