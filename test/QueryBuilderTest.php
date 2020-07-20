<?php

namespace oat\tao\test\elasticsearch;

use oat\generis\model\data\permission\PermissionInterface;
use oat\generis\model\data\permission\ReverseRightLookupInterface;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\tao\elasticsearch\IndexerInterface;
use oat\tao\elasticsearch\QueryBuilder;
use oat\tao\model\TaoOntology;
use Zend\ServiceManager\ServiceLocatorInterface;

class QueryBuilderTest extends TestCase
{
    /** @var QueryBuilder */
    private $subject;

    /** @var ServiceLocatorInterface|MockObject  */
    private $serviceLocator;

    protected function setUp(): void
    {
        $this->serviceLocator = $this->createMock(ServiceLocatorInterface::class);

        $this->subject = QueryBuilder::create();
        $this->subject->setServiceLocator($this->serviceLocator);
    }

    /**
     * @dataProvider queryResults
     */
    public function testGetSearchParams(string $queryString, string $body): void {
        $expected = [
            'index' => 'items',
            'size' => 10,
            'from' => 0,
            'client' => [
                'ignore' => 404
            ],
            'body' => $body,
        ];

        $result = $this->subject->getSearchParams($queryString, TaoOntology::CLASS_URI_ITEM, 0, 10, '_id', 'DESC');

        $this->assertSame($expected, $result);
    }

    public function queryResults() {
        return [
            [
                'test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(\"test\")"}},"sort":{"_id":{"order":"DESC"}}}'

            ],
            [
                'label:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}'

            ],
            [
                'LaBeL:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}'

            ],
            [
                'custom_field:test',
                'body' => '{"query":{"query_string":{"default_operator":"AND","query":"(HTMLArea_custom-field:\"test\" OR TextArea_custom-field:\"test\" OR TextBox_custom-field:\"test\" OR ComboBox_custom-field:\"test\" OR CheckBox_custom-field:\"test\" OR RadioBox_custom-field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            [
                'custom-field:test',
                'body' => '{"query":{"query_string":{"default_operator":"AND","query":"(HTMLArea_custom-field:\"test\" OR TextArea_custom-field:\"test\" OR TextBox_custom-field:\"test\" OR ComboBox_custom-field:\"test\" OR CheckBox_custom-field:\"test\" OR RadioBox_custom-field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            [
                'custom field:test',
                'body' => '{"query":{"query_string":{"default_operator":"AND","query":"(HTMLArea_custom-field:\"test\" OR TextArea_custom-field:\"test\" OR TextBox_custom-field:\"test\" OR ComboBox_custom-field:\"test\" OR CheckBox_custom-field:\"test\" OR RadioBox_custom-field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            [
                'label:test AND custom_field:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (HTMLArea_custom-field:\"test\" OR TextArea_custom-field:\"test\" OR TextBox_custom-field:\"test\" OR ComboBox_custom-field:\"test\" OR CheckBox_custom-field:\"test\" OR RadioBox_custom-field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            [
                'label:test and custom_field:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (HTMLArea_custom-field:\"test\" OR TextArea_custom-field:\"test\" OR TextBox_custom-field:\"test\" OR ComboBox_custom-field:\"test\" OR CheckBox_custom-field:\"test\" OR RadioBox_custom-field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            [
                'label:test aNd custom_field:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (HTMLArea_custom-field:\"test\" OR TextArea_custom-field:\"test\" OR TextBox_custom-field:\"test\" OR ComboBox_custom-field:\"test\" OR CheckBox_custom-field:\"test\" OR RadioBox_custom-field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ]
        ];
    }

    /**
     * @dataProvider queryResultsWithAccessControl
     */
    public function testGetSearchParamsWithAccessControl(string $queryString, string $body): void {
        $this->createAccessControlMock();

        $expected = [
            'index' => 'items',
            'size' => 10,
            'from' => 0,
            'client' => [
                'ignore' => 404
            ],
            'body' => $body,
        ];

        $result = $this->subject->getSearchParams($queryString, TaoOntology::CLASS_URI_ITEM, 0, 10, '_id', 'DESC');

        $this->assertSame($expected, $result);
    }

    public function queryResultsWithAccessControl() {
        return [
            [
                'test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(\"test\") AND (read_access:\"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#BackOfficeRole\" OR read_access:\"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#ItemsManagerRole\")"}},"sort":{"_id":{"order":"DESC"}}}'

            ],
        ];
    }

    private function createAccessControlMock(): void
    {
        $permissionProvider = $this->createMock(ReverseRightLookupInterface::class);
        $sessionService = $this->createMock(SessionService::class);
        $user = $this->createMock(User::class);

        $this->serviceLocator->expects($this->at(0))
            ->method('get')
            ->with(PermissionInterface::SERVICE_ID)
            ->willReturn($permissionProvider);

        $this->serviceLocator->expects($this->at(1))
            ->method('get')
            ->with(SessionService::SERVICE_ID)
            ->willReturn($sessionService);

        $sessionService->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn($user);

        $user->expects($this->once())
            ->method('getRoles')
            ->willReturn([
                'http://www.tao.lu/Ontologies/TAOItem.rdf#BackOfficeRole',
                'http://www.tao.lu/Ontologies/TAOItem.rdf#ItemsManagerRole'
            ]);
    }
}
