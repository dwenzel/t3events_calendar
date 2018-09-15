<?php

namespace DWenzel\T3eventsCalendar\Tests\Unit\Controller;

use DWenzel\T3calendar\Domain\Factory\CalendarFactory;
use DWenzel\T3calendar\Domain\Factory\CalendarFactoryInterface;
use DWenzel\T3calendar\Domain\Model\Dto\CalendarConfiguration;
use DWenzel\T3calendar\Domain\Model\Dto\CalendarConfigurationFactory;
use DWenzel\T3calendar\Domain\Model\Dto\CalendarConfigurationFactoryInterface;
use DWenzel\T3events\Domain\Factory\Dto\PerformanceDemandFactory;
use DWenzel\T3events\Domain\Model\Dto\PerformanceDemand;
use DWenzel\T3events\Domain\Repository\EventTypeRepository;
use DWenzel\T3events\Domain\Repository\GenreRepository;
use DWenzel\T3events\Domain\Repository\PerformanceRepository;
use DWenzel\T3events\Domain\Repository\VenueRepository;
use DWenzel\T3events\Session\SessionInterface;
use DWenzel\T3events\Utility\SettingsUtility;
use DWenzel\T3eventsCalendar\Controller\CalendarController;
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

    /**
     * @var CalendarFactoryInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $calendarFactory;

    /**
     * @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(CalendarController::class,
            ['dummy', 'emitSignal', 'createSearchObject'], [], '', false);
        $mockSession = $this->getMockBuilder(SessionInterface::class)
            ->setMethods(['has', 'get', 'clean', 'set', 'setNamespace'])->getMock();
        $this->performanceDemandFactory = $this->getMockBuilder(PerformanceDemandFactory::class)
            ->setMethods(['createFromSettings'])->getMock();
        $mockDemand = $this->getMockBuilder(PerformanceDemand::class)->getMock();
        $this->performanceDemandFactory->method('createFromSettings')->will($this->returnValue($mockDemand));
        $this->subject->injectPerformanceDemandFactory($this->performanceDemandFactory);

        $mockResult = $this->getMockBuilder(QueryResultInterface::class)->getMock();
        $this->performanceRepository = $this->getMockBuilder(PerformanceRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findDemanded'])
            ->getMock();
        $this->performanceRepository->method('findDemanded')->will($this->returnValue($mockResult));
        $this->subject->injectPerformanceRepository($this->performanceRepository);

        $this->view = $this->getMockBuilder(TemplateView::class)
            ->disableOriginalConstructor()
            ->setMethods(['assign', 'assignMultiple'])
            ->getMock();
        $mockContentObject = $this->getMockBuilder(ContentObjectRenderer::class)->getMock();
        $mockDispatcher = $this->getMockBuilder(Dispatcher::class)->getMock();
        $mockRequest = $this->getMockBuilder(Request::class)->getMock();
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManagerInterface::class)
            ->setMethods(
                ['getContentObject', 'setContentObject', 'getConfiguration', 'setConfiguration', 'isFeatureEnabled']
            )->getMock();
        $mockObjectManager = $this->getMockBuilder(ObjectManager::class)->getMock();

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
        $mockCalendarConfiguration = $this->getMockForAbstractClass(CalendarConfiguration::class);
        $this->calendarConfigurationFactory->method('create')
            ->will($this->returnValue($mockCalendarConfiguration));
        $this->subject->injectCalendarConfigurationFactory($this->calendarConfigurationFactory);
        $this->session = $this->getMockBuilder(SessionInterface::class)
            ->setMethods(['get', 'set', 'has', 'clean', 'setNamespace'])->getMock();
        $this->subject->injectSession($this->session);
        $this->calendarFactory = $this->getMockBuilder(CalendarFactory::class)
            ->setMethods(['create'])->getMock();
        $this->subject->injectCalendarFactory($this->calendarFactory);
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
        $subject = $this->getMockBuilder(CalendarController::class)
            ->disableOriginalConstructor()
            ->setMockClassName('tx_foo_class')
            ->getMock();
        $subject->__construct();
        $this->assertAttributeSame(
            'foo',
            'extensionName',
            $subject
        );
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

    protected function mockSettingsUtility()
    {
        $mockSettingsUtility = $this->getMockBuilder(SettingsUtility::class)
            ->setMethods(['getControllerKey'])->getMock();
        $this->subject->injectSettingsUtility($mockSettingsUtility);
        $mockSettingsUtility->expects($this->any())
            ->method('getControllerKey')
            ->will($this->returnValue('calendar'));
    }

    /**
     * @test
     */
    public function showActionGetsPerformanceDemandFromFactory()
    {
        $mockDemand = $this->getMockBuilder(PerformanceDemand::class)->getMock();
        $this->performanceDemandFactory->expects($this->once())
            ->method('createFromSettings')
            ->with($this->settings)
            ->will($this->returnValue($mockDemand));

        $this->subject->showAction();
    }

    /**
     * @test
     */
    public function showActionGetsConfigurationFromFactory()
    {
        $settings = [];
        $this->subject->_set('settings', $settings);
        $this->mockGetPerformanceDemandFromFactory();
        $this->calendarConfigurationFactory->expects($this->once())
            ->method('create')
            ->with($settings);
        $this->subject->showAction();
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
        $mockPerformanceDemand = $this->getMockBuilder(PerformanceDemand::class)->getMock();
        $this->performanceDemandFactory->expects($this->once())
            ->method('createFromSettings')
            ->will($this->returnValue($mockPerformanceDemand));
        $this->subject->injectPerformanceDemandFactory($this->performanceDemandFactory);
        return $mockPerformanceDemand;
    }

    /**
     * @test
     */
    public function showActionOverwritesDemandObject()
    {
        $this->subject = $this->getAccessibleMock(CalendarController::class,
            ['overwriteDemandObject', 'emitSignal'], [], '', false);
        $this->subject->injectPerformanceDemandFactory($this->performanceDemandFactory);
        $this->subject->_set('settings', $this->settings);
        $this->subject->injectCalendarConfigurationFactory($this->calendarConfigurationFactory);
        $this->subject->injectPerformanceRepository($this->performanceRepository);
        $this->subject->_set('view', $this->view);

        $mockDemand = $this->getMockBuilder(PerformanceDemand::class)->getMock();
        $this->performanceDemandFactory->method('createFromSettings')
            ->will($this->returnValue($mockDemand));
        $this->subject->expects($this->once())
            ->method('overwriteDemandObject')
            ->with($mockDemand);

        $this->subject->showAction();
    }

    /**
     * @test
     */
    public function showActionEmitsSignal()
    {
        $this->subject->expects($this->once())
            ->method('emitSignal')
            ->with(CalendarController::class, CalendarController::CALENDAR_SHOW_ACTION);
        $this->subject->showAction();
    }

    /**
     * @test
     */
    public function showActionAssignsVariablesToView()
    {
        $this->view->expects($this->once())
            ->method('assignMultiple');

        $this->subject->showAction();
    }

    /**
     * @test
     */
    public function controlActionGetsPerformanceDemandFromFactory()
    {
        $mockDemand = $this->getMockBuilder(PerformanceDemand::class)->getMock();
        $this->performanceDemandFactory->expects($this->once())
            ->method('createFromSettings')
            ->with($this->settings)
            ->will($this->returnValue($mockDemand));

        $this->subject->controlAction();
    }

    /**
     * @test
     */
    public function controlActionGetsConfigurationFromFactory()
    {
        $settings = [];
        $this->subject->_set('settings', $settings);
        $this->mockGetPerformanceDemandFromFactory();
        $this->calendarConfigurationFactory->expects($this->once())
            ->method('create')
            ->with($settings);
        $this->subject->controlAction();
    }

    /**
     * @test
     */
    public function controlActionOverwritesDemandObject()
    {
        $overwriteDemandFromSession = '';
        $this->subject = $this->getAccessibleMock(CalendarController::class,
            ['overwriteDemandObject', 'emitSignal'], [], '', false);
        $this->subject->injectSession($this->session);
        $this->subject->injectCalendarFactory($this->calendarFactory);
        
        $this->session->expects($this->once())
            ->method('get')
            ->will($this->returnValue($overwriteDemandFromSession));

        $this->subject->injectPerformanceDemandFactory($this->performanceDemandFactory);
        $this->subject->_set('settings', $this->settings);
        $this->subject->injectCalendarConfigurationFactory($this->calendarConfigurationFactory);
        $this->subject->injectPerformanceRepository($this->performanceRepository);
        $this->subject->_set('view', $this->view);

        $mockDemand = $this->getMockBuilder(PerformanceDemand::class)->getMock();
        $this->performanceDemandFactory->method('createFromSettings')
            ->will($this->returnValue($mockDemand));
        $this->subject->expects($this->once())
            ->method('overwriteDemandObject')
            ->with($mockDemand);

        $this->subject->controlAction();
    }

    /**
     * @test
     */
    public function controlActionEmitsSignal()
    {
        $this->subject->expects($this->once())
            ->method('emitSignal')
            ->with(CalendarController::class, CalendarController::CALENDAR_SHOW_ACTION);
        $this->subject->controlAction();
    }

    /**
     * @test
     */
    public function controlActionAssignsVariablesToView()
    {
        $this->view->expects($this->once())
            ->method('assignMultiple');

        $this->subject->controlAction();
    }

    /**
     * @test
     */
    public function controlActionGetsOverwriteDemandFromSession()
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with('tx_t3events_overwriteDemand');
        $this->subject->expects($this->once())
            ->method('emitSignal')
            ->will($this->returnValue([]));

        $this->subject->controlAction();
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
}

