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
 *
 *
 */
namespace oat\tao\elasticsearch\Action;

use oat\tao\elasticsearch\ElasticSearch;
use common_report_Report as Report;
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
    protected function getDefaultHost()
    {
        return [
            'http://localhost:9200'
        ];
    }

    /**
     * @return array
     */
    protected function getDefaultSettings()
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
     * @param $params
     * @return Report
     * @throws \common_Exception
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     * @throws \Exception
     */
    public function __invoke($params)
    {
        if (!class_exists('oat\\tao\\elasticsearch\\ElasticSearch')) {
            throw new \Exception('Tao ElasticSearch not found');
        }

        $config = [
            'hosts' => $this->getDefaultHost(),
            'settings' => $this->getDefaultSettings(),
        ];

        if (count($params) > 0) {
            $config['hosts'] = [];
            $hosts = parse_url(array_shift($params));
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

        $taoVersion = $this->getServiceLocator()->get(\common_ext_ExtensionsManager::SERVICE_ID)->getInstalledVersion('tao');

        if (version_compare($taoVersion, '7.8.0') < 0) {
            return new Report(Report::TYPE_ERROR, 'Requires Tao 7.8.0 or higher');
        }

        $oldSearchService = $this->getServiceLocator()->get(Search::SERVICE_ID);
        $oldSettings = $oldSearchService->getOptions();

        if (isset($oldSettings['settings'])) {
            $config['settings'] = $oldSettings['settings'];
        }

        $search = new ElasticSearch($config);

        $search->createItemsIndex();
        $search->createTestsIndex();
        $search->createGroupsIndex();
        $search->createDeliveriesIndex();
        $search->createResultsIndex();

        try {
            $search->query('', 'sample');
            $this->getServiceManager()->register(Search::SERVICE_ID, $search);
            return new Report(Report::TYPE_SUCCESS, __('Switched to ElasticSearch'));
        } catch (SyntaxException $e) {
            return new Report(Report::TYPE_ERROR, 'ElasticSearch server could not be found');
        }
    }
}
