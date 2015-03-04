<?php
/**
 * Menu entries. The controller is the key, the label the value.
 */
define('MENU', serialize(array(
    'index' => _('Application'),
    'help' => _('Help'),
    'apps' => _('Top 500 Apps'),
    'contact' => _('Contact'),
    'terms' => _('Terms of Service'),
    'impressum' => _('Legal Info'),
    'privacy' => _('Data Privacy Statement'),
)));

/**
 * A list of common devices to attach to fake accounts.
 */
define('COMMON_DEVICES', serialize(array(
    'en_US' => array('nexus5_en_us', 'nexus-5'),
    'de_DE' => array('nexus5_de_de', 'nexus-5'),
    'es_ES' => array('nexus5_es_es', 'nexus-5'),
    'fr_FR' => array('nexus5_fr_fr', 'nexus-5'),
    'ru_RU' => array('nexus5_ru_ru', 'nexus-5'),
    'zh_CN' => array('nexus5_zh_cn', 'nexus-5'),
)));

/**
 * Lookup table for converting machine readable name to human readable names.
 */
define('COMMON_DEVICES_NAMES', serialize(array(
    'nexus5_en_us' => _('Generic Device (English)'),
    'nexus5_de_de' => _('Generic Device (German)'),
    'nexus5_es_es' => _('Generic Device (Spanish)'),
    'nexus5_fr_fr' => _('Generic Device (French)'),
    'nexus5_ru_ru' => _('Generic Device (Russian)'),
    'nexus5_zh_cn' => _('Generic Device (Mandarin Chinese)'),
)));

/**
 * A list of android apps to offer as direct download.
 */
define('DIRECT_DOWNLOADS', serialize(array(
    'Facebook Messenger' => 'com.facebook.orca',
    'Facebook' => 'com.facebook.katana',
    'Pandora' => 'com.pandora.android',
    'Snapchat' => 'com.snapchat.android',
    'Netflix' => 'com.netflix.mediaclient',

    'Skype' => 'com.skype.raider',
    'Rock Hero' => 'com.grillgames.guitarrockhero',
    'Twitter' => 'com.twitter.android',
    'Whatsapp' => 'com.whatsapp',
    'Kik' => 'kik.android',

    'Spotify' => 'com.spotify.music',
    'LINK' => 'com.igg.android.im',
    'eBay' => 'com.ebay.mobile',
    'Clash of Clans' => 'com.supercell.clashofclans',
    'Tango Messenger' => 'com.sgiggle.production',

    'Subway Surfers' => 'com.kiloo.subwaysurf',
    'Walmart' => 'com.walmart.android',
    'Yahoo Mail' => 'com.yahoo.mobile.client.android.mail',
    'Pinterest' => 'com.pinterest',
    'Temple Run 2' => 'com.imangi.templerun2',
)));

/**
 * A list defining how languages should be written out.
 */
define ('LANG_NAMES', serialize(array(
    'en' => 'English',
    'de' => 'Deutsch',
)));

/**
 * A lookup table for converting languages to country codes.
 */
define ('LANG_COUNTRY', serialize(array(
    'en' => 'gb',
    'de' => 'de',
)));
