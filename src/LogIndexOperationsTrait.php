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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA.
 */

declare(strict_types=1);

namespace oat\tao\elasticsearch;

use oat\tao\elasticsearch\IndexerInterface;
use oat\tao\model\search\index\IndexDocument;
use Psr\Log\LoggerInterface;

/**
 * @internal To be used only by lib-tao-elasticsearch
 */
trait LogIndexOperationsTrait
{
    private function logCompletion(LoggerInterface $logger, int $count, int $visited, int $skipped, int $exceptions): void
    {
        if (($count != $visited) || ($exceptions > 0) || ($skipped > 0)) {
            $logger->warning(
                sprintf(
                    "%d / %d items were processed (%d skipped, %d exceptions)",
                    $count,
                    $visited,
                    $skipped,
                    $exceptions
                )
            );
        } else {
            $logger->debug(
                sprintf(
                    "Processed %d items (no exceptions, no skipped items)",
                    $count,
                    $skipped
                )
            );
        }
    }

    private function logAddingDocumentToQueue(
        LoggerInterface $logger,
        IndexDocument $document,
        string $indexName
    ): void
    {
        $logger->info(
            $this->getDocumentIdPrefix($document) .
            spritnf(
                'Queuing document with types %s into index "%s"',
                $this->getTypesString($document),
                $indexName
            )
        );
    }

    private function logMappings(LoggerInterface $logger, IndexDocument $document): void
    {
        if (!($this instanceof IndexerInterface)) {
            return;
        }

        foreach (self::AVAILABLE_INDEXES as $documentType => $indexName) {
            $logger->warning(
                sprintf(
                    'documentId: "%s" Index mappings: type="%s" index="%s"',
                    $document->getId(),
                    $documentType,
                    $indexName)
            );
        }
    }

    private function debug(LoggerInterface $logger,  ?IndexDocument $document, string $message, ...$args): void
    {
        $logger->debug(
            $this->getDocumentIdPrefix($document) .
            vsprintf($message, $args)
        );
    }

    private function info(LoggerInterface $logger, ?IndexDocument $document, string $message, ...$args): void
    {
        $logger->info(
            $this->getDocumentIdPrefix($document) .
            vsprintf($message, $args)
        );
    }

    private function warn(LoggerInterface $logger, ?IndexDocument $document, string $message, ...$args): void
    {
        $logger->warning(
            $this->getDocumentIdPrefix($document) .
            vsprintf($message, $args)
        );
    }

    private function logErrorsFromResponse(LoggerInterface $logger, ?IndexDocument $document, $clientResponse): void
    {
        if (isset($clientResponse['errors']) && $clientResponse['errors']) {
            $logger->warning(
                $this->getDocumentIdPrefix($document) .
                sprintf(
                    'Unexpected error response from client: %s',
                    json_encode($clientResponse)
                )
            );
        }
    }

    private function logUnclassifiedDocument(
        LoggerInterface $logger,
        ?IndexDocument $document,
        string $method,
        string $index,
        $type = null,
        $parentClasses = null
    ): void
    {
        $this->logSkippedUpdate(
            $logger,
            'unclassified document',
            $document,
            $method,
            $index,
            $type,
            $parentClasses
        );
    }

    private function logSkippedUpdate(
        LoggerInterface $logger,
        string $why,
        ?IndexDocument $document,
        string $method,
        string $index,
        $type = null,
        $parentClasses = null
    ): void
    {
        $logger->info(
            $this->getDocumentIdPrefix($document) .
            sprintf(
                '%s: Skipping document update: %s '.
                '(index=%s type=%s, typesString=%s, parentClasses=%s,)',
                $method,
                $why,
                $index,
                var_export($type, true),
                $document !== null ? $this->getTypesString($document) : '',
                var_export($parentClasses, true)
            )
        );
    }

    private function getDocumentIdPrefix(?IndexDocument $document): string
    {
        return ($document ? sprintf('[documentId: "%s"] ', $document->getId()) : '');
    }

    private function logIndexFailure(
        LoggerInterface $logger,
        Throwable $e,
        string $method,
        string $script='',
        $type = null,
        array $query = []
    ): void {
        $logger->error(
            sprintf(
                '%s: Exception %s: %s (code %s) (script="%s" type="%s" query="%s")',
                $method,
                get_class($e),
                $e->getMessage(),
                $e->getCode(),
                $script,
                $type,
                var_export($query, true)
            )
        );
    }

    private function logBatchFlush(LoggerInterface $logger, string $method, array $params): void
    {
        $logger->debug(
            sprintf(
                '%s: Flushing batch with %d operations',
                $method,
                count($params)
            )
        );
    }

    /**
     * @internal
     */
    private function getTypesString(IndexDocument $document): ?string
    {
        return var_export($document->getBody()['type'] ?? null, true);
    }
}
