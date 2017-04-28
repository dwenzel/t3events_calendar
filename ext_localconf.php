<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$emSettings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY]);

$identifier = 't3events_calendar_content';

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$identifier])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$identifier] = [];
}
// register t3events_calendar content cache with pages group
if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations'][$identifier]['groups'])) {
    $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations'][$identifier]['groups'] = ['pages', 'all'];
}

// disable cache
if (
    !(bool)$emSettings['enableContentCache']
    && !isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations'][$identifier]['backend'])
) {
    $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations'][$identifier]['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
}

// configure plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'DWenzel.' . $_EXTKEY,
    'Calendar',
    [
        'Calendar' => 'show, control',
    ],
    // non-cacheable actions
    [
        'Calendar' => 'control'
    ]
);

unset($identifier, $emSettings);
