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

use oat\oatbox\action\Action;
use oat\tao\elasticsearch\ElasticSearch;
use oat\tao\model\search\SearchService;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use common_report_Report as Report;
use oat\tao\model\search\SyntaxException;

class InitElasticSearch implements Action, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;
    
    protected function getDefaultHost()
    {
        return [
            'host' => 'localhost',
            'port' => '9200',
            'scheme' => 'http',
            'user' => 'username',
            'pass' => 'password!#$?*abc'
        ];
    }

    protected function getDefaultSettings()
    {
        return [
            'analysis' =>
                array (
                    'filter' =>
                        array (
                            'autocomplete_filter' =>
                                array (
                                    'type' => 'edge_ngram',
                                    'min_gram' => 1,
                                    'max_gram' => 20,
                                ),
                        ),
                    'analyzer' =>
                        array (
                            'autocomplete' =>
                                array (
                                    'type' => 'custom',
                                    'tokenizer' => 'standard',
                                    'filter' =>
                                        array (
                                            'lowercase',
                                            'autocomplete_filter',
                                        ),
                                ),
                        ),
                ),
        ];
    }

    public function __invoke($params) {
        
        if (!class_exists('oat\\tao\\elasticsearch\\ElasticSearch')) {
            throw new \Exception('Tao ElasticSearch not found');
        }
        
        $config = [
            'hosts' => $this->getDefaultHost(),
            'settings' => $this->getDefaultSettings()
        ];
        
        $p = $params;
        // host
        if (count($p) > 0) {
            $config['hosts']['host'] = array_shift($p);
        }
        
        // port
        if (count($p) > 0) {
            $config['hosts']['port'] = array_shift($p);
        }
        
        // scheme
        if (count($p) > 0) {
            $config['hosts']['scheme'] = array_shift($p);
        }

        // user
        if (count($p) > 0) {
            $config['hosts']['user'] = array_shift($p);
        }

        // pass
        if (count($p) > 0) {
            $config['hosts']['pass'] = array_shift($p);
        }
        
        $taoVersion = $this->getServiceLocator()->get(\common_ext_ExtensionsManager::SERVICE_ID)->getInstalledVersion('tao');
        if (version_compare($taoVersion, '7.8.0') < 0) {
            return new Report(Report::TYPE_ERROR, 'Requires Tao 7.8.0 or higher');
        }
        
        $search = new ElasticSearch($config);
        try {
            $result = $search->query('');
            $success = SearchService::setSearchImplementation($search);
            return new Report(Report::TYPE_SUCCESS, __('Switched to ElasticSearch'));
        } catch (SyntaxException $e) {
            return new Report(Report::TYPE_ERROR, 'ElasticSearch server could not be found');
        }
        
    }
}
