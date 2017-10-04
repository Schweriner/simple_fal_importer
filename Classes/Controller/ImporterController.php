<?php
namespace PB\SimpleFalImporter\Controller;

/***
 *
 * This file is part of the "Simple FAL Importer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2017 Paul Beck &lt;hi@toll-paul.de&gt;, Toll Paul!
 *
 ***/

use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * ImporterController
 */
class ImporterController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * action index
     * @param array $configuration
     * @return void
     */
    public function indexAction($configuration = [])
    {

        $this->view->assign('configuration', $configuration);

        if(false === empty($configuration)) {

            if ($configuration['field'] === '') {
                $this->addFlashMessage('You must enter a valid field name', '', AbstractMessage::ERROR);
                return;
            }

            if ($configuration['folder'] === ''
                || false === file_exists(PATH_site . '/uploads/' . PathUtility::getCanonicalPath($configuration['folder']))) {
                $this->addFlashMessage('You must enter a valid foldername / the folder you entered does not exist.', '', AbstractMessage::ERROR);
                return;
            } else {
                $sourceFolderPath = PATH_site . '/uploads/' . PathUtility::getCanonicalPath($configuration['folder']) . '/';
            }

            if ($configuration['storageUid'] === '') {
                $this->addFlashMessage('You must enter a valid Storage UID', '', AbstractMessage::ERROR);
                return;
            }

            if ($configuration['targetFolder'] === '') {
                $this->addFlashMessage('You must enter a valid target folder', '', AbstractMessage::ERROR);
                return;
            }

            /** @var \TYPO3\CMS\Core\Resource\StorageRepository $storageRepository */
            $storageRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\StorageRepository::class);

            /** @var \PB\SimpleFalImporter\Domain\Repository\RecordRepository $recordRepository */
            $recordRepository = GeneralUtility::makeInstance(\PB\SimpleFalImporter\Domain\Repository\RecordRepository::class);

            /** @var \TYPO3\CMS\Core\Resource\ResourceStorage $storage */
            $storage = $storageRepository->findByUid($configuration['storageUid']);

            if($storage === null) {
                $this->addFlashMessage('Storage not found for your UID ', '', AbstractMessage::ERROR);
                return;
            }

            if(false === $storage->hasFolder($configuration['targetFolder'])) {
                $this->addFlashMessage('The target folder does not exist in Storage with UID ' . $configuration['storageUid'], '', AbstractMessage::ERROR);
                return;
            }

            if (false === ($records = $recordRepository->getRecords($configuration['table'],
                    $configuration['field']))) {
                $this->addFlashMessage('The table or the field in it does not exist!', '', AbstractMessage::ERROR);
            } else {

                foreach ($records as &$record) {
                    foreach (explode(',', $record[$configuration['field']]) as $file) {
                        $record['falimporterInfo'][$file] =
                            file_exists($sourceFolderPath . $file);
                    }
                }

                if($configuration['step'] !== 'import') {

                    $this->view->assign('records', $records);

                } else {

                    /** @var \TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory */
                    $resourceFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);

                    $targetFolder = $storage->getFolder($configuration['targetFolder']);

                    $count = 0;

                    foreach ($records as $record) {
                        foreach ($record['falimporterInfo'] as $fileName => $fileState) {
                            if($fileState === false) continue;
                            $movedFile = $resourceFactory->retrieveFileOrFolderObject($sourceFolderPath . $fileName)->copyTo($targetFolder);
                            $recordRepository->insertFileReference($movedFile->getUid(), $record['uid'], $record['pid'], $configuration);
                            $count++;
                        }
                    }
                    $this->view->assign('importCount', $count);
                }
            }
        }
    }
}
