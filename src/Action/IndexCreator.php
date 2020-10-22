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
 */

declare(strict_types=1);

namespace oat\tao\elasticsearch\Action;

use common_report_Report as Report;
use Exception;
use oat\oatbox\extension\script\ScriptAction;
use oat\tao\elasticsearch\ElasticSearch;
use oat\tao\model\search\Search;

class IndexCreator extends ScriptAction
{

    private const INDICES_FILES = 'indicesFiles';

    protected function provideOptions()
    {
        return
            [
                self::INDICES_FILES => [
                    'prefix' => 'f',
                    'longPrefix' => self::INDICES_FILES,
                    'required' => true,
                    'description' => 'Absolute path to indices declaration.',
                ],
            ];
    }

    protected function provideDescription()
    {
        return 'Creates indices at Elastic Search';
    }

    protected function run()
    {
        $elasticService = $this->getServiceLocator()->get(Search::SERVICE_ID);
        if ($elasticService instanceof ElasticSearch) {
            $elasticService->setOption('indicesFiles', $this->getOption(self::INDICES_FILES) ?? []);
            try {
                $elasticService->createIndexes();
            } catch (Exception $exception) {
                return new Report(
                    Report::TYPE_ERROR,
                    sprintf(
                        'Error while indices creation: %s',
                        $exception->getMessage()
                    )
                );
            }
            return new Report(Report::TYPE_SUCCESS, 'Elastic indices created successfully');
        }
        return new Report(Report::TYPE_ERROR, 'No proper service found');
    }
}
