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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\tao\elasticsearch\Action;

use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use oat\tao\elasticsearch\ElasticSearch;
use common_report_Report as Report;
use oat\tao\elasticsearch\Watcher\IndexDocumentFactory;
use oat\tao\model\search\index\IndexService;
use oat\tao\elasticsearch\IndexUpdater;
use oat\tao\model\search\index\IndexUpdaterInterface;
use oat\tao\model\search\SearchProxy;
use oat\tao\model\search\strategy\GenerisSearch;
use oat\tao\model\search\SyntaxException;
use oat\oatbox\extension\InstallAction;
use oat\tao\model\search\Search;

/**
 * Class InitElasticSearch
 * @package oat\tao\elasticsearch\Action
 */
class InitElasticSearch extends InstallAction
{
    /**
     * @return array
     */
    protected function getDefaultHost(): array
    {
        return [
            'http://localhost:9200'
        ];
    }

    /**
     * @return array
     */
    protected function getDefaultSettings(): array
    {
        return [
            'analysis' => [
                'filter' => [
                    'autocomplete_filter' => [
                        'type' => 'edge_ngram',
                        'min_gram' => 1,
                        'max_gram' => 100,
                    ]
                ],
                'analyzer' => [
                    'autocomplete' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [
                            'lowercase',
                            'autocomplete_filter',
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $params
     * @return Report
     * @throws \common_Exception
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     * @throws \Exception
     */
    public function __invoke($params): Report
    {
        $report = new Report(Report::TYPE_INFO, 'Started switch to elastic search');
        if (!class_exists('oat\\tao\\elasticsearch\\ElasticSearch')) {
            throw new \Exception('Tao ElasticSearch not found');
        }

        $config = [
            'hosts' => $this->getDefaultHost(),
            'settings' => $this->getDefaultSettings(),
            'indexes' => array_pop($params)
        ];

        if (count($params) > 0) {
            $config['hosts'] = [];
            $hosts = $this->parseConnectionParams($params);
            $config['hosts'][] = $hosts;
        }

        if (count($params) > 0) {
            $config['hosts'][0]['port'] = array_shift($params);
        }

        if (count($params) > 0) {
            $config['hosts'][0]['user'] = array_shift($params);
        }

        if (count($params) > 0) {
            $config['hosts'][0]['pass'] = array_shift($params);
        }

        $oldSearchService = $this->getServiceLocator()->get(Search::SERVICE_ID);
        $oldSettings = $oldSearchService->getOptions();

        if (isset($oldSettings['settings'])) {
            $config['settings'] = $oldSettings['settings'];
        }

        $config[GenerisSearch::class] = new GenerisSearch();

        try {
            //FIXME
            //FIXME
            //FIXME
            //@FIXME @TODO Initialize in a proper way using taoAdvanceSearch
            //FIXME
            //FIXME
            //FIXME
            $elasticSearch = new ElasticSearch($config);

            $this->getServiceManager()->register(ElasticSearch::SERVICE_ID, $elasticSearch);

            $elasticSearch->createIndexes();

            /** @var SearchProxy $searchProxy */
            $searchProxy = $this->getServiceManager()->get(Search::SERVICE_ID);
            $searchProxy->setOption('main_search', $elasticSearch); //FIXME
            
            $this->getServiceManager()->register(Search::SERVICE_ID, $searchProxy);

            $report->add(new Report(Report::TYPE_SUCCESS, __('Switched search service implementation to ElasticSearch')));

            $this->getServiceManager()->register(IndexUpdaterInterface::SERVICE_ID, new IndexUpdater($config['hosts']));
        } catch (BadRequest400Exception $e) {
            $report->add(new Report(Report::TYPE_ERROR, 'Unable to create index: ' . $e->getMessage()));
        } catch (\Exception $e) {
            $report->add(new Report(Report::TYPE_ERROR, 'ElasticSearch server could not be found'));
        }

        return $report;
    }

    private function parseConnectionParams(array &$params)
    {
        $parsed = parse_url(array_shift($params));
        if (is_array($params) && !isset($parsed['host'])) {
            $parsed['host'] = $parsed['path'] ?? null;
            unset($parsed['path']);
        }
        return $parsed;
    }
}
