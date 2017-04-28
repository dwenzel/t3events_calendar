<?php
namespace DWenzel\T3eventsCalendar\Hooks;

    /**
     * This file is part of the TYPO3 CMS project.
     *
     * It is free software; you can redistribute it and/or modify it under
     * the terms of the GNU General Public License, either version 2
     * of the License, or any later version.
     *
     * For the full copyright and license information, please read the
     * LICENSE.txt file that was distributed with this source code.
     *
     * The TYPO3 project - inspiring people to share!
     */

/**
 * Class ItemsProcFunc
 * @package CPSIT\T3eventsCourse\Hooks
 */
class ItemsProcFunc extends \DWenzel\T3events\Hooks\ItemsProcFunc
{
    /**
     * Key for look up in TYPO3_CONF_VARS and page TS config
     *
     * @const EXTENSION_KEY
     */
    const EXTENSION_KEY = 't3events_calendar';
}
