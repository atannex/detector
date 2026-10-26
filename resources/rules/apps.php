<?php

declare(strict_types=1);

/*
| Apps that open links in their own browser, or call your API natively:
| [pattern, name]. A match sets Device::$app; the browser is still parsed
| (Instagram on iOS is still WebKit, TikTok on Android is still Chrome).
*/

return [
    ['\bInstagram\b', 'Instagram'],
    ['\bFBAN/Messenger|\bMessengerForiOS|\bFB_IAB/Orca', 'Messenger'],
    ['\bFBAN/|\bFBAV/|\bFB_IAB/|\[FB[^\]]*;', 'Facebook'],
    ['musical_ly|BytedanceWebview|\bTikTok\b|trill_\d|\bJsSdk/\d.*NetType', 'TikTok'],
    ['LinkedInApp', 'LinkedIn'],
    ['Twitter for (?:iPhone|iPad|Android)|TwitterAndroid|\bTwitter/\d', 'X'],
    ['Snapchat', 'Snapchat'],
    ['\[Pinterest/|Pinterest for (?:iOS|Android)', 'Pinterest'],
    ['Reddit/|RedditAndroid', 'Reddit'],
    ['\bLine/\d', 'LINE'],
    ['MicroMessenger', 'WeChat'],
    ['\bQQ/\d', 'QQ'],
    ['Weibo', 'Weibo'],
    ['DingTalk', 'DingTalk'],
    ['AlipayClient', 'Alipay'],
    ['KAKAOTALK', 'KakaoTalk'],
    ['NAVER\(inapp', 'Naver'],
    ['Telegram-Android|TelegramBot-?App|\bTelegram/\d', 'Telegram'],
    ['Discord/|discord/\d', 'Discord'],
    ['Slack/\d|Slack_SSB', 'Slack'],
    ['Teams/\d|MicrosoftTeams', 'Microsoft Teams'],
    ['Zoom ?Mobile|ZoomWebKit', 'Zoom'],
    ['Outlook-(?:iOS|Android)|ms-office|Microsoft Outlook', 'Outlook'],
    ['GoogleApp/|\bGSA/\d', 'Google'],
    ['\bYouTube/|com\.google\.ios\.youtube', 'YouTube'],
    ['Gmail', 'Gmail'],
    ['Spotify/\d', 'Spotify'],
    ['Viber/\d', 'Viber'],
    ['imo(?:Android|iOS)', 'imo'],
    ['Opera(?:Mini)?News', 'Opera News'],
    ['Flipboard', 'Flipboard'],
    ['Feedly/', 'Feedly'],
    ['Steam(?: |-)?(?:Client|Mobile)|Valve Steam', 'Steam'],
    ['Electron/', 'Electron app'],
    ['\bDart/\d|\(dart:io\)', 'Flutter app'],
    ['okhttp/\d', 'Android app'],
    ['\bDalvik/\d', 'Android app'],
    ['CFNetwork/\d|Alamofire/\d', 'iOS app'],
];
