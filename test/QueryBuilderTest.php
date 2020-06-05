<?php

namespace oat\tao\test\elasticsearch;

use oat\generis\test\TestCase;
use oat\tao\elasticsearch\IndexerInterface;
use oat\tao\elasticsearch\QueryBuilder;
use oat\tao\model\TaoOntology;

class QueryBuilderTest extends TestCase
{
    /** @var QueryBuilder */
    private $subject;

    protected function setUp(): void
    {
       $this->subject = QueryBuilder::create();
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
            ]
        ];
    }
}
