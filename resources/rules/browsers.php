<?php

declare(strict_types=1);

/*
| Browsers, first match wins: [pattern, name, family]. The (?<v>…) group is
| the version. Browsers built on Chromium also say "Chrome/…" and Safari's
| token appears in nearly every User-Agent, so the generic ones come last.
| The engine is worked out separately (Blink, WebKit, Gecko…).
*/

return [
    // Chromium-based, each with its own token --------------------------------
    ['OculusBrowser/(?<v>[\d.]+)', 'Meta Quest Browser', 'Chrome'],
    ['PicoBrowser/(?<v>[\d.]+)', 'Pico Browser', 'Chrome'],
    ['Wolvic/(?<v>[\d.]+)', 'Wolvic', 'Firefox'],
    ['Edg(?:A|iOS)?/(?<v>[\d.]+)', 'Microsoft Edge', 'Chrome'],
    ['Edge/(?<v>[\d.]+)', 'Microsoft Edge', 'Edge'],
    ['OPR/(?<v>[\d.]+).*\(Edition GX|OPRGX/(?<v2>[\d.]+)', 'Opera GX', 'Chrome'],
    ['Opera Mini/(?<v>[\d.]+)|OPiOS/(?<v2>[\d.]+).*Mini', 'Opera Mini', 'Opera'],
    ['OPR/(?<v>[\d.]+)|OPiOS/(?<v2>[\d.]+)|OPT/(?<v3>[\d.]+)|OPX/(?<v4>[\d.]+)', 'Opera', 'Chrome'],
    ['Opera.*Version/(?<v>[\d.]+)|Opera[ /](?<v2>[\d.]+)', 'Opera', 'Opera'],
    ['SamsungBrowser/(?<v>[\d.]+)', 'Samsung Internet', 'Chrome'],
    ['UCBrowser/(?<v>[\d.]+)|UCWEB/?(?<v2>[\d.]+)?|UBrowser/(?<v3>[\d.]+)|UCTurbo/(?<v4>[\d.]+)', 'UC Browser', 'Chrome'],
    ['YaBrowser/(?<v>[\d.]+)|YaSearchBrowser/(?<v2>[\d.]+)|Yowser/(?<v3>[\d.]+)', 'Yandex Browser', 'Chrome'],
    ['Vivaldi/(?<v>[\d.]+)', 'Vivaldi', 'Chrome'],
    ['Brave(?: Chrome)?/(?<v>[\d.]+)|\bBrave\b', 'Brave', 'Chrome'],
    ['DuckDuckGo/(?<v>[\d.]+)|Ddg/(?<v2>[\d.]+)', 'DuckDuckGo', 'Chrome'],
    ['Whale/(?<v>[\d.]+)', 'Naver Whale', 'Chrome'],
    ['(?:XiaoMi/)?MiuiBrowser/(?<v>[\d.]+)', 'MIUI Browser', 'Chrome'],
    ['HuaweiBrowser/(?<v>[\d.]+)|HBPC/(?<v2>[\d.]+)', 'Huawei Browser', 'Chrome'],
    ['HeyTapBrowser/(?<v>[\d.]+)', 'HeyTap Browser', 'Chrome'],
    ['VivoBrowser/(?<v>[\d.]+)|VivoBrowserLite', 'Vivo Browser', 'Chrome'],
    ['OppoBrowser/(?<v>[\d.]+)', 'Oppo Browser', 'Chrome'],
    ['PHX/(?<v>[\d.]+)', 'Phoenix Browser', 'Chrome'],
    ['MQQBrowser/(?<v>[\d.]+)|QQBrowser/(?<v2>[\d.]+)', 'QQ Browser', 'Chrome'],
    ['baiduboxapp/(?<v>[\d.]+)|baidubrowser/(?<v2>[\d.]+)|BIDUBrowser[ /](?<v3>[\d.]+)', 'Baidu Browser', 'Chrome'],
    ['360SE|360EE|QihooBrowser/(?<v>[\d.]+)', '360 Browser', 'Chrome'],
    ['Maxthon[ /](?<v>[\d.]+)|MxBrowser/(?<v2>[\d.]+)', 'Maxthon', 'Chrome'],
    ['Puffin/(?<v>[\d.]+)', 'Puffin', 'Chrome'],
    ['Silk/(?<v>[\d.]+)', 'Amazon Silk', 'Chrome'],
    ['coc_coc_browser/(?<v>[\d.]+)', 'Cốc Cốc', 'Chrome'],
    ['Iron(?: Safari)?/(?<v>[\d.]+)', 'SRWare Iron', 'Chrome'],
    ['Avast/(?<v>[\d.]+)', 'Avast Secure Browser', 'Chrome'],
    ['AVG/(?<v>[\d.]+)', 'AVG Secure Browser', 'Chrome'],
    ['CCleaner/(?<v>[\d.]+)', 'CCleaner Browser', 'Chrome'],
    ['Ecosia (?:android|ios)@(?<v>[\d.]+)', 'Ecosia', 'Chrome'],
    ['Kiwi(?: Browser)?/?(?<v>[\d.]+)?', 'Kiwi Browser', 'Chrome'],
    ['SznProhlizec/(?<v>[\d.]+)', 'Seznam Browser', 'Chrome'],
    ['Electron/(?<v>[\d.]+)', 'Electron', 'Chrome'],
    ['HeadlessChrome/(?<v>[\d.]+)', 'Headless Chrome', 'Chrome'],

    // Gecko -----------------------------------------------------------------
    ['Firefox/(?<v>[\d.]+).*KAIOS|KAIOS/.*Firefox/(?<v2>[\d.]+)', 'KaiOS Browser', 'Firefox'],
    ['PaleMoon/(?<v>[\d.]+)', 'Pale Moon', 'Firefox'],
    ['Basilisk/(?<v>[\d.]+)', 'Basilisk', 'Firefox'],
    ['Waterfox/(?<v>[\d.]+)', 'Waterfox', 'Firefox'],
    ['LibreWolf/(?<v>[\d.]+)', 'LibreWolf', 'Firefox'],
    ['SeaMonkey/(?<v>[\d.]+)', 'SeaMonkey', 'Firefox'],
    ['IceCat/(?<v>[\d.]+)', 'IceCat', 'Firefox'],
    ['Floorp/(?<v>[\d.]+)', 'Floorp', 'Firefox'],
    ['Zen/(?<v>[\d.]+)', 'Zen Browser', 'Firefox'],
    ['Mypal/(?<v>[\d.]+)', 'Mypal', 'Firefox'],
    ['Focus/(?<v>[\d.]+)|Klar/(?<v2>[\d.]+)', 'Firefox Focus', 'Firefox'],
    ['FxiOS/(?<v>[\d.]+)', 'Firefox', 'Firefox'],
    ['Firefox/(?<v>[\d.]+)', 'Firefox', 'Firefox'],

    // Others ----------------------------------------------------------------
    ['Konqueror/(?<v>[\d.]+)', 'Konqueror', 'Konqueror'],
    ['Epiphany/(?<v>[\d.]+)|GNOME Web', 'GNOME Web', 'Safari'],
    ['Falkon/(?<v>[\d.]+)|QupZilla/(?<v2>[\d.]+)', 'Falkon', 'Chrome'],
    ['Midori/(?<v>[\d.]+)', 'Midori', 'Safari'],
    ['QtWebEngine/(?<v>[\d.]+)', 'Qt WebEngine', 'Chrome'],
    ['NetSurf/(?<v>[\d.]+)', 'NetSurf', 'NetSurf'],
    ['Dillo/(?<v>[\d.]+)', 'Dillo', 'Dillo'],
    ['Lynx/(?<v>[\d.a-z]+)', 'Lynx', 'Lynx'],
    ['Links \((?<v>[\d.]+)', 'Links', 'Links'],
    ['w3m/(?<v>[\d.]+)', 'w3m', 'w3m'],
    ['ELinks/(?<v>[\d.]+)', 'ELinks', 'Links'],
    ['Ladybird/(?<v>[\d.]+)', 'Ladybird', 'Ladybird'],
    ['NokiaBrowser/(?<v>[\d.]+)|BrowserNG/(?<v2>[\d.]+)', 'Nokia Browser', 'Safari'],
    ['BlackBerry.*Version/(?<v>[\d.]+)|BB10.*Version/(?<v2>[\d.]+)', 'BlackBerry Browser', 'Safari'],
    ['PlayStation \d.*Version/(?<v>[\d.]+)|PLAYSTATION \d', 'PlayStation Browser', 'Safari'],
    ['NintendoBrowser/(?<v>[\d.]+)', 'Nintendo Browser', 'Safari'],
    ['(?:SMART-TV|SmartTV).*Tizen|Tizen.*\bTV\b', 'Samsung TV Browser', 'Chrome'],

    // Internet Explorer -----------------------------------------------------
    ['IEMobile[ /](?<v>[\d.]+)', 'IE Mobile', 'Internet Explorer'],
    ['MSIE (?<v>[\d.]+)', 'Internet Explorer', 'Internet Explorer'],
    ['Trident/.*rv:(?<v>[\d.]+)', 'Internet Explorer', 'Internet Explorer'],

    // The generic three -----------------------------------------------------
    ['CriOS/(?<v>[\d.]+)', 'Chrome', 'Chrome'],
    ['Chromium/(?<v>[\d.]+)', 'Chromium', 'Chrome'],
    ['Chrome/(?<v>[\d.]+)', 'Chrome', 'Chrome'],
    ['Version/(?<v>[\d.]+).*Safari/|Safari/.*Version/(?<v2>[\d.]+)', 'Safari', 'Safari'],
];
