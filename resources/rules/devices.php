<?php

declare(strict_types=1);

/*
| Brands, first match wins: [pattern, brand]. Each pattern is tried against
| the model the User-Agent or Sec-CH-UA-Model names ("SM-S918B",
| "Redmi Note 12", "TECNO KI7"), then against the whole User-Agent.
| Model codes shared by sister brands (BBK: Oppo, OnePlus, realme, vivo)
| are listed under the brand that uses them most.
*/

return [
    // Apple, Google, Microsoft, Amazon --------------------------------------
    ['^(?:iPhone|iPad|iPod|Macintosh|Mac|Apple ?TV|Apple ?Watch|Vision Pro)', 'Apple'],
    ['^(?:Pixel|Nexus|Chromecast|Google)\b|\bCrKey\b', 'Google'],
    ['^(?:Surface|Lumia|Xbox|Microsoft)', 'Microsoft'],
    ['^(?:KF[A-Z]{2,4}|AFT[A-Z0-9]+|Kindle|Echo|Fire)\b|\bAEO[A-Z]{2}\b', 'Amazon'],

    // Android brands --------------------------------------------------------
    ['^(?:SAMSUNG|Galaxy|SM-[A-Z0-9]+|GT-[A-Z0-9]+|SCH-|SGH-|SHV-|SPH-|SC-\d\d[A-Z])', 'Samsung'],
    ['^(?:Redmi|POCO|Mi |MI |MIX|Xiaomi|M\d{4}[A-Z]\d{1,2}[A-Z]{1,3}|2[0-4]\d{2}[0-9A-Z]{3,6}[A-Z]{1,3})\b|\bXiaoMi\b', 'Xiaomi'],
    ['^(?:HONOR|HRY-|BKL-|PCT-|LLD-|JSN-|ANY-|ALI-|CMA-|CRT-|RKY-|VNE-|BRP-|FNE-|LGE-|MAG-|NTN-|RBN-|VER-|ELI-|PTP-|REA-|ALT-)', 'Honor'],
    ['^(?:HUAWEI|[A-Z]{3}-(?:AL|AN|L|LX|NX|TL|W|N)\d{1,2}[A-Z]?|ALN-|NOH-|NOP-|LIO-|ELS-|ANA-|VOG-|MAR-|STK-|JNY-|DVC-|BAH\d?-|AGS\d?-|KOB\d?-)', 'Huawei'],
    ['^(?:ONEPLUS|OnePlus)|^(?:A|GM|HD|IN|KB|LE|NE)\d{4}\b|^PJ[A-Z]\d{3}\b', 'OnePlus'],
    ['^(?:RMX\d{4}|realme)', 'realme'],
    ['^(?:OPPO|CPH\d{4}|P[A-H][A-Z]{2}\d{2})\b', 'OPPO'],
    ['^(?:vivo|V\d{4}[A-Z]{0,2}|I\d{4})\b', 'vivo'],
    ['^(?:TECNO|Tecno)\b|^(?:K[A-Z]\d{1,2}[a-z]?|CK\d|BG\d|LH\d|BE\d|CI\d|KI\d|CL\d)\b', 'Tecno'],
    ['^(?:Infinix|X6\d{2,3}[A-Z]?)\b', 'Infinix'],
    ['^(?:itel|ITEL|W[56]\d{3}|L\d{4}|S6\d{2}[A-Z]?|A\d{3}[A-Z]?L?)\b', 'itel'],
    ['^(?:moto|motorola|XT\d{4})', 'Motorola'],
    ['^(?:Nokia|TA-\d{4})', 'Nokia'],
    ['^(?:Sony|Xperia|SO-\d\d[A-Z]|SOV\d\d|SOG\d\d|XQ-[A-Z]{2}\d{2}|[CDEFGH]\d{4})\b', 'Sony'],
    ['^(?:LG|LM-[A-Z]\d{3}|VS\d{3}|LG-)', 'LG'],
    ['^(?:Nothing|A0(?:15|42|59|63|65|69)|A142P?)\b', 'Nothing'],
    ['^(?:Lenovo|TB-[A-Z0-9]+|TB\d{3}|YT-|L\d{5})', 'Lenovo'],
    ['^(?:ASUS|ASUS_|ZenFone|ROG|I00\dD|Z0\d{2}D)', 'Asus'],
    ['^(?:ZTE|Blade|nubia|NX\d{3}J|Axon)', 'ZTE'],
    ['^(?:TCL|T\d{3}[A-Z]{1,2}|\d{4}[A-Z]\b|Alcatel)', 'TCL'],
    ['^(?:HTC|Desire|HTC_)', 'HTC'],
    ['^(?:Fairphone|FP\d)\b', 'Fairphone'],
    ['^(?:BlackBerry|BB10|BBF\d|BBB\d|BBE\d|STV\d)', 'BlackBerry'],
    ['^(?:CUBOT)\b', 'Cubot'],
    ['^(?:DOOGEE)\b', 'Doogee'],
    ['^(?:Ulefone|Armor)\b', 'Ulefone'],
    ['^(?:BLU|BLU_|Vivo XL|Studio)\b', 'BLU'],
    ['^(?:Meizu|MZ-|M\d{3}[A-Z]?|MEIZU)', 'Meizu'],
    ['^(?:Sharp|SH-\d\d[A-Z]|SHV\d\d|AQUOS)', 'Sharp'],
    ['^(?:Kyocera|KYV\d\d|KYG\d\d|E\d{4})\b', 'Kyocera'],
    ['^(?:Coolpad)', 'Coolpad'],
    ['^(?:Wiko)', 'Wiko'],
    ['^(?:Hisense|HLTE\d)', 'Hisense'],
    ['^(?:Gionee|GN\d{4})', 'Gionee'],
    ['^(?:Lava|LAVA)\b', 'Lava'],
    ['^(?:Micromax)', 'Micromax'],
    ['^(?:Oukitel)', 'Oukitel'],
    ['^(?:Umidigi|UMIDIGI)', 'Umidigi'],
    ['^(?:Blackview|BV\d{4})', 'Blackview'],
    ['^(?:Teclast)', 'Teclast'],
    ['^(?:Archos)', 'Archos'],
    ['^(?:Vodafone|VFD \d{3})', 'Vodafone'],
    ['^(?:Orange)\b', 'Orange'],
    ['^(?:Fire ?Phone|SD4930UR)', 'Amazon'],
    ['^(?:Crosscall|Core-)', 'Crosscall'],
    ['^(?:Energizer)', 'Energizer'],
    ['^(?:CAT|S[46]\d) ', 'Cat'],

    // Wearables, XR, cars, e-readers ---------------------------------------
    ['^(?:Quest|Oculus|Meta Quest)|\bOculusBrowser\b', 'Meta'],
    ['\bPico\b|PicoBrowser', 'Pico'],
    ['\bTesla\b|QtCarBrowser', 'Tesla'],
    ['\bKobo\b', 'Kobo'],
    ['\bPocketBook\b', 'PocketBook'],
    ['\bremarkable\b', 'reMarkable'],
    ['\bBOOX\b|Onyx', 'Onyx Boox'],
    ['\bNook\b|BNTV\d', 'Barnes & Noble'],
    ['\bGarmin\b', 'Garmin'],
    ['\bFitbit\b', 'Fitbit'],

    // TVs, consoles, players (matched against the whole User-Agent) --------
    ['PlayStation|PLAYSTATION', 'Sony'],
    ['\bXbox\b', 'Microsoft'],
    ['\bBB10\b|BlackBerry|RIM Tablet', 'BlackBerry'],
    ['Kindle/\d|\bSilk/', 'Amazon'],
    ['\bCrKey\b|Chromecast', 'Google'],
    ['\bBRAVIA\b', 'Sony'],
    ['Nintendo', 'Nintendo'],
    ['\bRoku\b', 'Roku'],
    ['Web0S|webOS.*TV|NetCast|\bLG Browser\b|\bLGE\b', 'LG'],
    ['SMART-TV.*SAMSUNG|SAMSUNG.*SMART-TV|Tizen.*TV|SmartHub', 'Samsung'],
    ['Hisense|VIDAA', 'Hisense'],
    ['Philips|NETTV', 'Philips'],
    ['Panasonic|Viera', 'Panasonic'],
    ['Vizio|VIZIO', 'Vizio'],
    ['\bTCL\b', 'TCL'],
    ['Xiaomi.*TV|\bMiTV\b|MiBOX', 'Xiaomi'],
    ['NVIDIA ?Shield|SHIELD Android TV', 'NVIDIA'],
    ['Steam ?Deck|Valve', 'Valve'],
];
