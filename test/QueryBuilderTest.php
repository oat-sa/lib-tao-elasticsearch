<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

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
    public function testGetSearchParams(string $queryString, string $body): void
    {
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

    public function queryResults(): array
    {
        return [
            'Simple query' => [
                'test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(\"test\")"}},"sort":{"_id":{"order":"DESC"}}}'
            ],
            'Query specific field' => [
                'label:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}'

            ],
            'Query specific field (variating case)' => [
                'LaBeL:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}'

            ],
            'Query custom field (using underscore)' => [
                'custom_field:test',
                'body' => '{"query":{"query_string":{"default_operator":"AND","query":"(HTMLArea_custom-field:\"test\" OR TextArea_custom-field:\"test\" OR TextBox_custom-field:\"test\" OR ComboBox_custom-field:\"test\" OR CheckBox_custom-field:\"test\" OR RadioBox_custom-field:\"test\" OR SearchTextBox_custom-field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query custom field (using dash)' => [
                'custom-field:test',
                'body' => '{"query":{"query_string":{"default_operator":"AND","query":"(HTMLArea_custom-field:\"test\" OR TextArea_custom-field:\"test\" OR TextBox_custom-field:\"test\" OR ComboBox_custom-field:\"test\" OR CheckBox_custom-field:\"test\" OR RadioBox_custom-field:\"test\" OR SearchTextBox_custom-field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query custom field (using space)' => [
                'custom field:test',
                'body' => '{"query":{"query_string":{"default_operator":"AND","query":"(HTMLArea_custom-field:\"test\" OR TextArea_custom-field:\"test\" OR TextBox_custom-field:\"test\" OR ComboBox_custom-field:\"test\" OR CheckBox_custom-field:\"test\" OR RadioBox_custom-field:\"test\" OR SearchTextBox_custom-field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query logic operator (Uppercase)' => [
                'label:test AND custom_field:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (HTMLArea_custom-field:\"test\" OR TextArea_custom-field:\"test\" OR TextBox_custom-field:\"test\" OR ComboBox_custom-field:\"test\" OR CheckBox_custom-field:\"test\" OR RadioBox_custom-field:\"test\" OR SearchTextBox_custom-field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query logic operator (Lowercase)' => [
                'label:test and custom_field:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (HTMLArea_custom-field:\"test\" OR TextArea_custom-field:\"test\" OR TextBox_custom-field:\"test\" OR ComboBox_custom-field:\"test\" OR CheckBox_custom-field:\"test\" OR RadioBox_custom-field:\"test\" OR SearchTextBox_custom-field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query logic operator (Mixed)' => [
                'label:test aNd custom_field:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (HTMLArea_custom-field:\"test\" OR TextArea_custom-field:\"test\" OR TextBox_custom-field:\"test\" OR ComboBox_custom-field:\"test\" OR CheckBox_custom-field:\"test\" OR RadioBox_custom-field:\"test\" OR SearchTextBox_custom-field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query URIs' => [
                'https://test-act.docker.localhost/ontologies/tao.rdf#i5f200ed20e80a8c259ebe410db7f6a',
                '{"query":{"query_string":{"default_operator":"AND","query":"(\"https:\/\/test-act.docker.localhost\/ontologies\/tao.rdf#i5f200ed20e80a8c259ebe410db7f6a\")"}},"sort":{"_id":{"order":"DESC"}}}'
            ],
            'Query Field with URI' => [
                'delivery: https://test-act.docker.localhost/ontologies/tao.rdf#i5f200ed20e80a8c259ebe410db7f6a',
                '{"query":{"query_string":{"default_operator":"AND","query":"(delivery:\"https:\/\/test-act.docker.localhost\/ontologies\/tao.rdf#i5f200ed20e80a8c259ebe410db7f6a\")"}},"sort":{"_id":{"order":"DESC"}}}'
            ],
        ];
    }

    /**
     * @dataProvider queryResultsWithAccessControl
     */
    public function testGetSearchParamsWithAccessControl(string $queryString, string $body): void
    {
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

    public function queryResultsWithAccessControl() : array
    {
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
