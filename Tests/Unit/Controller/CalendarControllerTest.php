<?php

namespace DWenzel\T3events\Tests\Unit\Controller;

use DWenzel\T3calendar\Domain\Model\Dto\CalendarConfigurationFactory;
use DWenzel\T3calendar\Domain\Model\Dto\CalendarConfigurationFactoryInterface;
use DWenzel\T3eventsCalendar\Controller\CalendarController;
use DWenzel\T3events\Domain\Factory\Dto\PerformanceDemandFactory;
use DWenzel\T3events\Domain\Model\Dto\PerformanceDemand;
use DWenzel\T3events\Domain\Repository\EventTypeRepository;
use DWenzel\T3events\Domain\Repository\GenreRepository;
use DWenzel\T3events\Domain\Repository\PerformanceRepository;
use DWenzel\T3events\Domain\Repository\VenueRepository;
use DWenzel\T3events\Session\SessionInterface;
use DWenzel\T3events\Utility\SettingsUtility;
use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Test case for class \DWenzel\T3eventsCalendar\Controller\CalendarController.
 *
 */
class CalendarControllerTest extends UnitTestCase
{

    /**
     * @var CalendarController|\PHPUnit_Framework_MockObject_MockObject|AccessibleMockObjectInterface
     */
    protected $subject;

    /**
     * @var CalendarConfigurationFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $calendarConfigurationFactory;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var ViewInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $view;

    /**
     * @var PerformanceDemandFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $performanceDemandFactory;

    /**
     * @var PerformanceRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $performanceRepository;

    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(CalendarController::class,
            ['dummy', 'emitSignal', 'createSearchObject'], [], '', false);
        $mockSession = $this->getMock(
            SessionInterface::class, ['has', 'get', 'clean', 'set', 'setNamespace']
        );
        $this->performanceDemandFactory = $this->getMock(PerformanceDemandFactory::class, ['createFromSettings']);
        $mockDemand = $this->getMock(PerformanceDemand::class);
        $this->performanceDemandFactory->method('createFromSettings')->will($this->returnValue($mockDemand));
        $this->subject->injectPerformanceDemandFactory($this->performanceDemandFactory);

        $mockResult = $this->getMock(QueryResultInterface::class);
        $this->performanceRepository = $this->getMock(
            PerformanceRepository::class,
            ['findDemanded'], [], '', false);
        $this->performanceRepository->method('findDemanded')->will($this->returnValue($mockResult));
        $this->subject->injectPerformanceRepository($this->performanceRepository);

        $this->view = $this->getMock(TemplateView::class, ['assign', 'assignMultiple'], [], '', false);
        $mockContentObject = $this->getMock(ContentObjectRenderer::class);
        $mockDispatcher = $this->getMock(Dispatcher::class);
        $mockRequest = $this->getMock(Request::class);
        $mockConfigurationManager = $this->getMock(
            ConfigurationManagerInterface::class,
            ['getContentObject', 'setContentObject', 'getConfiguration',
                'setConfiguration', 'isFeatureEnabled']
        );
        $mockObjectManager = $this->getMock(ObjectManager::class);

        $this->subject->_set('view', $this->view);
        $this->subject->_set('session', $mockSession);
        $this->subject->_set('contentObject', $mockContentObject);
        $this->subject->_set('signalSlotDispatcher', $mockDispatcher);
        $this->subject->_set('request', $mockRequest);
        $this->subject->_set('configurationManager', $mockConfigurationManager);
        $this->subject->_set('objectManager', $mockObjectManager);
        $this->subject->_set('settings', $this->settings);

        $this->calendarConfigurationFactory = $this->getMockBuilder(CalendarConfigurationFactory::class)
            ->setMethods(['create'])->getMock();
        $mockCalendarConfiguration = $this->getMockForAbstractClass(CalendarConfigurationFactoryInterface::class);
        $this->calendarConfigurationFactory->method('create')
            ->will($this->returnValue($mockCalendarConfiguration));
        $this->subject->injectCalendarConfigurationFactory($this->calendarConfigurationFactory);
    }

    /**
     * @test
     */
    public function constructorSetsNameSpace()
    {
        $this->subject->__construct();
        $this->assertAttributeSame(
            get_class($this->subject),
            'namespace',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function constructorSetsExtensionName()
    {
        $subject = $this->getMock(CalendarController::class, [], [], 'tx_t3events_foo_class', false);
        $subject->__construct();
        $this->assertAttributeSame(
            't3events_calendar',
            'extensionName',
            $subject
        );
    }

    protected function mockSettingsUtility()
    {
        $mockSettingsUtility = $this->getMock(
            SettingsUtility::class, ['getControllerKey']
        );
        $this->subject->injectSettingsUtility($mockSettingsUtility);
        $mockSettingsUtility->expects($this->any())
            ->method('getControllerKey')
            ->will($this->returnValue('calendar'));
    }

    /**
     * @test
     */
    public function initializeActionSetsOverwriteDemandInSession()
    {
        $this->subject->_set('settings', []);
        $this->mockSettingsUtility();
        $overwriteDemand = ['foo'];
        $mockSession = $this->subject->_get('session');
        $mockRequest = $this->subject->_get('request');
        $mockRequest->expects($this->once())
            ->method('hasArgument')
            ->will($this->returnValue(true));
        $mockRequest->expects($this->once())
            ->method('getArgument')
            ->will($this->returnValue($overwriteDemand));

        $mockSession->expects($this->once())
            ->method('set')
            ->with('tx_t3events_overwriteDemand', serialize($overwriteDemand));

        $this->subject->initializeAction();
    }

    /**
     * @test
     */
    public function initializeQuickMenuActionResetsOverwriteDemandInSession()
    {
        $mockSession = $this->subject->_get('session');
        $mockRequest = $this->subject->_get('request');
        $mockRequest->expects($this->once())
            ->method('hasArgument')
            ->will($this->returnValue(false));
        $mockSession->expects($this->once())
            ->method('clean');
        $this->subject->initializeQuickMenuAction();
    }

    /**
     * @param array $methodsToStub
     */
    protected function injectMockRepositories(array $methodsToStub)
    {
        $repositoryClasses = [
            'genreRepository' => GenreRepository::class,
            'venueRepository' => VenueRepository::class,
            'eventTypeRepository' => EventTypeRepository::class,
        ];
        foreach ($repositoryClasses as $propertyName => $className) {
            $mock = $this->getAccessibleMock($className, $methodsToStub, [], '', false, true, false);
            $this->inject($this->subject, $propertyName, $mock);
        }
    }
    

    /**
     * @test
     */
    public function calendarActionGetsPerformanceDemandFromFactory()
    {
        $mockDemand = $this->getMock(PerformanceDemand::class);
        $this->performanceDemandFactory->expects($this->once())
            ->method('createFromSettings')
            ->with($this->settings)
            ->will($this->returnValue($mockDemand));

        $this->subject->calendarAction();
    }

    /**
     * @test
     */
    public function calendarActionGetsConfigurationFromFactory()
    {
        $settings = [];
        $this->subject->_set('settings', $settings);
        $this->mockGetPerformanceDemandFromFactory();
        $this->calendarConfigurationFactory->expects($this->once())
            ->method('create')
            ->with($settings);
        $this->subject->calendarAction();
    }

    /**
     * mocks getting an PerformanceDemandObject from ObjectManager
     * @return \PHPUnit_Framework_MockObject_MockObject|PerformanceDemand
     */
    public function mockGetPerformanceDemandFromFactory()
    {
        $this->performanceDemandFactory = $this->getMockForAbstractClass(
            PerformanceDemandFactory::class, [], '', false, true, true, ['createFromSettings']
        );
        $mockPerformanceDemand = $this->getMock(PerformanceDemand::class);
        $this->performanceDemandFactory->expects($this->once())
            ->method('createFromSettings')
            ->will($this->returnValue($mockPerformanceDemand));
        $this->subject->injectPerformanceDemandFactory($this->performanceDemandFactory);
        return $mockPerformanceDemand;
    }

    /**
     * @test
     */
    public function calendarActionOverwritesDemandObject()
    {
        $this->subject = $this->getAccessibleMock(CalendarController::class,
            ['overwriteDemandObject', 'emitSignal'], [], '', false);
        $this->subject->injectPerformanceDemandFactory($this->performanceDemandFactory);
        $this->subject->_set('settings', $this->settings);
        $this->subject->injectCalendarConfigurationFactory($this->calendarConfigurationFactory);
        $this->subject->injectPerformanceRepository($this->performanceRepository);
        $this->subject->_set('view', $this->view);

        $mockDemand = $this->getMock(PerformanceDemand::class);
        $this->performanceDemandFactory->method('createFromSettings')
            ->will($this->returnValue($mockDemand));
        $this->subject->expects($this->once())
            ->method('overwriteDemandObject')
            ->with($mockDemand);

        $this->subject->calendarAction();
    }

    /**
     * @test
     */
    public function calendarActionEmitsSignal()
    {
        $this->subject->expects($this->once())
            ->method('emitSignal')
            ->with(CalendarController::class, CalendarController::PERFORMANCE_CALENDAR_ACTION);
        $this->subject->calendarAction();
    }

    /**
     * @test
     */
    public function calendarActionAssignsVariablesToView()
    {
        $this->view->expects($this->once())
            ->method('assignMultiple');

        $this->subject->calendarAction();
    }
}

