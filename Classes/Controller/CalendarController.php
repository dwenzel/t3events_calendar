<?php

namespace DWenzel\T3eventsCalendar\Controller;


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

use DWenzel\T3calendar\Domain\Factory\CalendarFactoryTrait;
use DWenzel\T3calendar\Domain\Model\Dto\CalendarConfigurationFactoryTrait;
use DWenzel\T3events\Controller\CategoryRepositoryTrait;
use DWenzel\T3events\Controller\DemandTrait;
use DWenzel\T3events\Controller\EntityNotFoundHandlerTrait;
use DWenzel\T3events\Controller\FilterableControllerInterface;
use DWenzel\T3events\Controller\FilterableControllerTrait;
use DWenzel\T3events\Controller\PerformanceDemandFactoryTrait;
use DWenzel\T3events\Controller\PerformanceRepositoryTrait;
use DWenzel\T3events\Controller\SearchTrait;
use DWenzel\T3events\Controller\SessionTrait;
use DWenzel\T3events\Controller\SettingsUtilityTrait;
use DWenzel\T3events\Controller\TranslateTrait;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class CalendarController
 */
class CalendarController
    extends ActionController
    implements FilterableControllerInterface
{
    use CategoryRepositoryTrait, CalendarConfigurationFactoryTrait, CalendarFactoryTrait,
        DemandTrait, EntityNotFoundHandlerTrait, FilterableControllerTrait,
        PerformanceDemandFactoryTrait, PerformanceRepositoryTrait,
        SearchTrait, SessionTrait, SettingsUtilityTrait, TranslateTrait;

    const CALENDAR_SHOW_ACTION = 'showAction';
    const CALENDAR_CONTROL_ACTION = 'controlAction';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->namespace = get_class($this);
    }

    /**
     * initializes all actions
     */
    public function initializeAction()
    {
        $this->settings = $this->mergeSettings();
        if ($this->request->hasArgument('overwriteDemand')) {
            $this->session->set(
                'tx_t3events_overwriteDemand',
                serialize($this->request->getArgument('overwriteDemand'))
            );
        }
    }

    /**
     * Show action
     * Show a calendar widget containing performances as items
     *
     * @param array $overwriteDemand
     */
    public function showAction(array $overwriteDemand = null)
    {
        $templateVariables = $this->getTemplateVariables($overwriteDemand);
        $this->emitSignal(__CLASS__, self::CALENDAR_SHOW_ACTION, $templateVariables);
        $this->view->assignMultiple($templateVariables);
    }

    /**
     * Control action
     * displays a calender control which filters a list plugin
     */
    public function controlAction()
    {
        $overwriteDemand = unserialize($this->session->get('tx_t3events_overwriteDemand'));
        $templateVariables = $this->getTemplateVariables($overwriteDemand, true);
        $this->emitSignal(__CLASS__, self::CALENDAR_SHOW_ACTION, $templateVariables);
        $this->view->assignMultiple($templateVariables);
    }

    /**
     * @param $overwriteDemand
     * @param bool $withCalendar
     * @return array
     */
    protected function getTemplateVariables($overwriteDemand, $withCalendar = false)
    {
        $demand = $this->performanceDemandFactory->createFromSettings($this->settings);
        $this->overwriteDemandObject($demand, $overwriteDemand);
        $performances = $this->performanceRepository->findDemanded($demand);
        $calendarConfiguration = $this->calendarConfigurationFactory->create($this->settings);

        $templateVariables = [
            'performances' => $performances,
            'demand' => $demand,
            'calendarConfiguration' => $calendarConfiguration,
            'overwriteDemand' => $overwriteDemand
        ];

        if ($withCalendar) {
            $templateVariables['calendar'] = $this->calendarFactory->create($calendarConfiguration, $performances);
        }

        return $templateVariables;
    }
}