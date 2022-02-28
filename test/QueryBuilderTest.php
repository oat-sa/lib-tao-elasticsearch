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
 * Copyright (c) 2020-2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\tao\test\elasticsearch;

use oat\generis\model\data\permission\PermissionInterface;
use oat\generis\model\data\permission\ReverseRightLookupInterface;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\log\LoggerService;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\User;
use oat\tao\elasticsearch\IndexerInterface;
use oat\tao\elasticsearch\QueryBuilder;
use oat\tao\elasticsearch\Specification\UseAclSpecification;
use oat\tao\model\TaoOntology;
use Zend\ServiceManager\ServiceLocatorInterface;

interface PermissionMock extends PermissionInterface, ReverseRightLookupInterface {
}

class QueryBuilderTest extends TestCase
{
    /** @var QueryBuilder */
    private $subject;

    /** @var ServiceLocatorInterface|MockObject */
    private $serviceLocator;

    /** @var SessionService|MockObject */
    private $sessionServiceMock;

    /** @var PermissionInterface|MockObject */
    private $permissionMock;

    /** @var LoggerService|MockObject */
    private $loggerService;

    /** @var LoggerService|MockObject */
    private $useAclSpecification;

    protected function setUp(): void
    {
        $this->sessionServiceMock = $this->createMock(SessionService::class);
        $this->permissionMock = $this->createMock(PermissionMock::class);
        $this->loggerService = $this->createMock(LoggerService::class);
        $this->user = $this->createMock(User::class);
        $this->useAclSpecification = $this->createMock(UseAclSpecification::class);

        $this->subject = new QueryBuilder();
        $this->subject->withUseAclSpecification($this->useAclSpecification);
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    SessionService::SERVICE_ID => $this->sessionServiceMock,
                    PermissionInterface::SERVICE_ID => $this->permissionMock,
                    LoggerService::SERVICE_ID => $this->loggerService
                ]
            )
        );

        $this->sessionServiceMock
            ->expects($this->any())
            ->method('getCurrentUser')
            ->willReturn($this->user);
    }

    /**
     * @dataProvider queryResultsWithAccessControl
     */
    public function testGetSearchParamsWithAccessControl(string $queryString, string $body): void
    {
        $this->createAccessControlMock(true);

        $this->assertSame(
            [
                'index' => 'items',
                'size' => 10,
                'from' => 0,
                'client' => [
                    'ignore' => 404
                ],
                'body' => $body,
            ],
            $this->subject->getSearchParams($queryString, IndexerInterface::ITEMS_INDEX, 0, 10, '_id', 'DESC')
        );
    }

    public function queryResultsWithAccessControl(): array
    {
        return [
            'with user access control and role access control' => [
                'test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(\"test\") AND (read_access:(\"https:\/\/tao.docker.localhost\/ontologies\/tao.rdf#i5f64514f1c36110793759fc28c0105b\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#BackOfficeRole\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#ItemsManagerRole\"))"}},"sort":{"_id":{"order":"DESC"}}}'
            ],
            'Simple query' => [
                'test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(\"test\") AND (read_access:(\"https:\/\/tao.docker.localhost\/ontologies\/tao.rdf#i5f64514f1c36110793759fc28c0105b\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#BackOfficeRole\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#ItemsManagerRole\"))"}},"sort":{"_id":{"order":"DESC"}}}'
            ],
            'Query specific field' => [
                'label:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (read_access:(\"https:\/\/tao.docker.localhost\/ontologies\/tao.rdf#i5f64514f1c36110793759fc28c0105b\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#BackOfficeRole\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#ItemsManagerRole\"))"}},"sort":{"_id":{"order":"DESC"}}}'

            ],
            'Query specific field (variating case)' => [
                'LaBeL:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (read_access:(\"https:\/\/tao.docker.localhost\/ontologies\/tao.rdf#i5f64514f1c36110793759fc28c0105b\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#BackOfficeRole\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#ItemsManagerRole\"))"}},"sort":{"_id":{"order":"DESC"}}}'

            ],
            'Query custom field (using underscore)' => [
                'custom_field:test',
                'body' => '{"query":{"query_string":{"default_operator":"AND","query":"(HTMLArea_custom_field:\"test\" OR TextArea_custom_field:\"test\" OR TextBox_custom_field:\"test\" OR ComboBox_custom_field:\"test\" OR CheckBox_custom_field:\"test\" OR RadioBox_custom_field:\"test\" OR SearchTextBox_custom_field:\"test\" OR SearchDropdown_custom_field:\"test\") AND (read_access:(\"https:\/\/tao.docker.localhost\/ontologies\/tao.rdf#i5f64514f1c36110793759fc28c0105b\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#BackOfficeRole\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#ItemsManagerRole\"))"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query custom field (using dash)' => [
                'custom_field:test',
                'body' => '{"query":{"query_string":{"default_operator":"AND","query":"(HTMLArea_custom_field:\"test\" OR TextArea_custom_field:\"test\" OR TextBox_custom_field:\"test\" OR ComboBox_custom_field:\"test\" OR CheckBox_custom_field:\"test\" OR RadioBox_custom_field:\"test\" OR SearchTextBox_custom_field:\"test\" OR SearchDropdown_custom_field:\"test\") AND (read_access:(\"https:\/\/tao.docker.localhost\/ontologies\/tao.rdf#i5f64514f1c36110793759fc28c0105b\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#BackOfficeRole\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#ItemsManagerRole\"))"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query custom field (using space)' => [
                'custom field:test',
                'body' => '{"query":{"query_string":{"default_operator":"AND","query":"(HTMLArea_custom field:\"test\" OR TextArea_custom field:\"test\" OR TextBox_custom field:\"test\" OR ComboBox_custom field:\"test\" OR CheckBox_custom field:\"test\" OR RadioBox_custom field:\"test\" OR SearchTextBox_custom field:\"test\" OR SearchDropdown_custom field:\"test\") AND (read_access:(\"https:\/\/tao.docker.localhost\/ontologies\/tao.rdf#i5f64514f1c36110793759fc28c0105b\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#BackOfficeRole\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#ItemsManagerRole\"))"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query logic operator (Uppercase)' => [
                'label:test AND custom_field:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (HTMLArea_custom_field:\"test\" OR TextArea_custom_field:\"test\" OR TextBox_custom_field:\"test\" OR ComboBox_custom_field:\"test\" OR CheckBox_custom_field:\"test\" OR RadioBox_custom_field:\"test\" OR SearchTextBox_custom_field:\"test\" OR SearchDropdown_custom_field:\"test\") AND (read_access:(\"https:\/\/tao.docker.localhost\/ontologies\/tao.rdf#i5f64514f1c36110793759fc28c0105b\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#BackOfficeRole\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#ItemsManagerRole\"))"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query logic operator (Lowercase)' => [
                'label:test and custom_field:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (HTMLArea_custom_field:\"test\" OR TextArea_custom_field:\"test\" OR TextBox_custom_field:\"test\" OR ComboBox_custom_field:\"test\" OR CheckBox_custom_field:\"test\" OR RadioBox_custom_field:\"test\" OR SearchTextBox_custom_field:\"test\" OR SearchDropdown_custom_field:\"test\") AND (read_access:(\"https:\/\/tao.docker.localhost\/ontologies\/tao.rdf#i5f64514f1c36110793759fc28c0105b\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#BackOfficeRole\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#ItemsManagerRole\"))"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query logic operator (Mixed)' => [
                'label:test aNd custom_field:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (HTMLArea_custom_field:\"test\" OR TextArea_custom_field:\"test\" OR TextBox_custom_field:\"test\" OR ComboBox_custom_field:\"test\" OR CheckBox_custom_field:\"test\" OR RadioBox_custom_field:\"test\" OR SearchTextBox_custom_field:\"test\" OR SearchDropdown_custom_field:\"test\") AND (read_access:(\"https:\/\/tao.docker.localhost\/ontologies\/tao.rdf#i5f64514f1c36110793759fc28c0105b\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#BackOfficeRole\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#ItemsManagerRole\"))"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query URIs' => [
                'https://test-act.docker.localhost/ontologies/tao.rdf#i5f200ed20e80a8c259ebe410db7f6a',
                '{"query":{"query_string":{"default_operator":"AND","query":"(\"https:\/\/test-act.docker.localhost\/ontologies\/tao.rdf#i5f200ed20e80a8c259ebe410db7f6a\") AND (read_access:(\"https:\/\/tao.docker.localhost\/ontologies\/tao.rdf#i5f64514f1c36110793759fc28c0105b\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#BackOfficeRole\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#ItemsManagerRole\"))"}},"sort":{"_id":{"order":"DESC"}}}'
            ],
            'Query Field with URI' => [
                'delivery: https://test-act.docker.localhost/ontologies/tao.rdf#i5f200ed20e80a8c259ebe410db7f6a',
                '{"query":{"query_string":{"default_operator":"AND","query":"(delivery:\"https:\/\/test-act.docker.localhost\/ontologies\/tao.rdf#i5f200ed20e80a8c259ebe410db7f6a\") AND (read_access:(\"https:\/\/tao.docker.localhost\/ontologies\/tao.rdf#i5f64514f1c36110793759fc28c0105b\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#BackOfficeRole\" OR \"http:\/\/www.tao.lu\/Ontologies\/TAOItem.rdf#ItemsManagerRole\"))"}},"sort":{"_id":{"order":"DESC"}}}'
            ],
        ];
    }

    /**
     * @dataProvider queryResultsWithoutAccessControl
     */
    public function testGetSearchParamsWithoutAccessControl(string $queryString, string $body): void
    {
        $this->createAccessControlMock(false);

        $this->assertSame(
            [
                'index' => 'items',
                'size' => 10,
                'from' => 0,
                'client' => [
                    'ignore' => 404
                ],
                'body' => $body,
            ],
            $this->subject->getSearchParams($queryString, IndexerInterface::ITEMS_INDEX, 0, 10, '_id', 'DESC')
        );
    }

    public function queryResultsWithoutAccessControl(): array
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
                'body' => '{"query":{"query_string":{"default_operator":"AND","query":"(HTMLArea_custom_field:\"test\" OR TextArea_custom_field:\"test\" OR TextBox_custom_field:\"test\" OR ComboBox_custom_field:\"test\" OR CheckBox_custom_field:\"test\" OR RadioBox_custom_field:\"test\" OR SearchTextBox_custom_field:\"test\" OR SearchDropdown_custom_field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query custom field (using dash)' => [
                'custom_field:test',
                'body' => '{"query":{"query_string":{"default_operator":"AND","query":"(HTMLArea_custom_field:\"test\" OR TextArea_custom_field:\"test\" OR TextBox_custom_field:\"test\" OR ComboBox_custom_field:\"test\" OR CheckBox_custom_field:\"test\" OR RadioBox_custom_field:\"test\" OR SearchTextBox_custom_field:\"test\" OR SearchDropdown_custom_field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query custom field (using space)' => [
                'custom field:test',
                'body' => '{"query":{"query_string":{"default_operator":"AND","query":"(HTMLArea_custom field:\"test\" OR TextArea_custom field:\"test\" OR TextBox_custom field:\"test\" OR ComboBox_custom field:\"test\" OR CheckBox_custom field:\"test\" OR RadioBox_custom field:\"test\" OR SearchTextBox_custom field:\"test\" OR SearchDropdown_custom field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query logic operator (Uppercase)' => [
                'label:test AND custom_field:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (HTMLArea_custom_field:\"test\" OR TextArea_custom_field:\"test\" OR TextBox_custom_field:\"test\" OR ComboBox_custom_field:\"test\" OR CheckBox_custom_field:\"test\" OR RadioBox_custom_field:\"test\" OR SearchTextBox_custom_field:\"test\" OR SearchDropdown_custom_field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query logic operator (Lowercase)' => [
                'label:test and custom_field:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (HTMLArea_custom_field:\"test\" OR TextArea_custom_field:\"test\" OR TextBox_custom_field:\"test\" OR ComboBox_custom_field:\"test\" OR CheckBox_custom_field:\"test\" OR RadioBox_custom_field:\"test\" OR SearchTextBox_custom_field:\"test\" OR SearchDropdown_custom_field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
            ],
            'Query logic operator (Mixed)' => [
                'label:test aNd custom_field:test',
                '{"query":{"query_string":{"default_operator":"AND","query":"(label:\"test\") AND (HTMLArea_custom_field:\"test\" OR TextArea_custom_field:\"test\" OR TextBox_custom_field:\"test\" OR ComboBox_custom_field:\"test\" OR CheckBox_custom_field:\"test\" OR RadioBox_custom_field:\"test\" OR SearchTextBox_custom_field:\"test\" OR SearchDropdown_custom_field:\"test\")"}},"sort":{"_id":{"order":"DESC"}}}',
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

    private function createAccessControlMock(bool $includeAccessControl): void
    {
        $this->useAclSpecification
            ->method('isSatisfiedBy')
            ->willReturn($includeAccessControl);

        $this->user
            ->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('https://tao.docker.localhost/ontologies/tao.rdf#i5f64514f1c36110793759fc28c0105b');

        $this->user
            ->expects($this->any())
            ->method('getRoles')
            ->willReturn(
                [
                    'http://www.tao.lu/Ontologies/TAOItem.rdf#BackOfficeRole',
                    'http://www.tao.lu/Ontologies/TAOItem.rdf#ItemsManagerRole'
                ]
            );
    }
}
