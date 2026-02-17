<?php


namespace Monyxie\Agora\TokenBuilder\AccessControl;


class Privilege
{
    const JOIN_CHANNEL = 1;
    const PUBLISH_VIDEO_STREAM = 3;
    const INVITE_PUBLISH_VIDEO_STREAM = 11;
    const PUBLISH_AUDIO_CDN = 5;
    const ADMINISTRATE_CHANNEL = 101;
    const RTM_LOGIN = 1000;
    const PUBLISH_VIDEO_CDN = 6;
    const PUBLISH_DATA_STREAM = 4;
    const REQUEST_PUBLISH_AUDIO_STREAM = 7;
    const INVITE_PUBLISH_AUDIO_STREAM = 10;
    const INVITE_PUBLISH_DATA_STREAM = 12;
    const REQUEST_PUBLISH_VIDEO_STREAM = 8;
    const PUBLISH_AUDIO_STREAM = 2;
    const REQUEST_PUBLISH_DATA_STREAM = 9;
}
