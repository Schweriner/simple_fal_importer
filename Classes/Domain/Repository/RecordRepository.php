<?php
namespace PB\SimpleFalImporter\Domain\Repository;
/***************************************************************
 *  Copyright notice
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 */
class RecordRepository {
    /**
     * @param string $tablename
     * @return array|boolean
     */
	public function getRecords($tablename, $fieldname) {

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tablename);

        if(false === $queryBuilder->getConnection()->getSchemaManager()->tablesExist($tablename)) {
            return false;
        }

        if(false === array_key_exists($fieldname,$queryBuilder->getConnection()->getSchemaManager()->listTableColumns($tablename))) {
            return false;
        }

        return $rows = $queryBuilder
            ->select('*')
            ->from($tablename)
            ->where(
                $queryBuilder->expr()->neq($fieldname,"''")
            )
            ->execute()
            ->fetchAll();
    }

    /**
     * @param integer $fileUid
     * @param integer $recordUid
     * @param integer $recordPid
     * @param array $configuration
     */
    public function insertFileReference($fileUid, $recordUid, $recordPid, $configuration) {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder
            ->insert('sys_file_reference')
            ->values([
                'pid' => $recordPid,
                'uid_local' => $fileUid,
                'uid_foreign' => $recordUid,
                'table_local' => 'sys_file',
                'tablenames' => $configuration['table'],
                'fieldname' => $configuration['field']
            ])
            ->execute();
    }
}