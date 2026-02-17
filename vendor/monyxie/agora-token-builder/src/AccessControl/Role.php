<?php

namespace Monyxie\Agora\TokenBuilder\AccessControl;

class Role
{
    /**
     * 已废弃。通信模式的通话方。享有权限与角色 Role_Publisher 相同
     * @deprecated
     */
    const ATTENDEE = 0;
    /**
     * 直播模式下的主播（BROADCASTER）
     */
    const PUBLISHER = 1;
    /**
     *  (默认) 直播模式下的观众（AUDIENCE）
     */
    const SUBSCRIBER = 2;
    /**
     * 已废弃。享有权限与角色 Role_Publisher 相同
     * @deprecated
     */
    const ADMIN = 101;

    /**
     * @deprecated
     */
    const PRIVILEGES_ATTENDEE = [
        Privilege::JOIN_CHANNEL => 0,
        Privilege::PUBLISH_AUDIO_STREAM => 0,
        Privilege::PUBLISH_VIDEO_STREAM => 0,
        Privilege::PUBLISH_DATA_STREAM => 0
    ];

    const PRIVILEGES_PUBLISHER = [
        Privilege::JOIN_CHANNEL => 0,
        Privilege::PUBLISH_AUDIO_STREAM => 0,
        Privilege::PUBLISH_VIDEO_STREAM => 0,
        Privilege::PUBLISH_DATA_STREAM => 0,
        Privilege::PUBLISH_AUDIO_CDN => 0,
        Privilege::PUBLISH_VIDEO_CDN => 0,
        Privilege::INVITE_PUBLISH_AUDIO_STREAM => 0,
        Privilege::INVITE_PUBLISH_VIDEO_STREAM => 0,
        Privilege::INVITE_PUBLISH_DATA_STREAM => 0
    ];

    /**
     * @deprecated
     */
    const PRIVILEGES_ADMIN = [
        Privilege::JOIN_CHANNEL => 0,
        Privilege::PUBLISH_AUDIO_STREAM => 0,
        Privilege::PUBLISH_VIDEO_STREAM => 0,
        Privilege::PUBLISH_DATA_STREAM => 0,
        Privilege::ADMINISTRATE_CHANNEL => 0
    ];

    const PRIVILEGES_SUBSCRIBER = [
        Privilege::JOIN_CHANNEL => 0,
        Privilege::REQUEST_PUBLISH_AUDIO_STREAM => 0,
        Privilege::REQUEST_PUBLISH_VIDEO_STREAM => 0,
        Privilege::REQUEST_PUBLISH_DATA_STREAM => 0
    ];

    public static function privileges(int $role)
    {
        $mapping = [
            Role::ATTENDEE => self::PRIVILEGES_ATTENDEE,
            Role::PUBLISHER => self::PRIVILEGES_PUBLISHER,
            Role::SUBSCRIBER => self::PRIVILEGES_SUBSCRIBER,
            Role::ADMIN => self::PRIVILEGES_ADMIN,
        ];

        return $mapping[$role] ?? [];
    }
}
