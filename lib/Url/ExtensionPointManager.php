<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Url;

class ExtensionPointManager
{
    const MODE_UPDATE_URL_ALL = 1;

    const MODE_UPDATE_URL_DATASET = 2;

    const MODE_UPDATE_URL_COLLECTION = 3;

    /**
     * The rex_extension_point instance.
     *
     * @var \rex_extension_point
     */
    protected $extensionPoint;

    protected $mode;

    protected $dataEditMode;
    protected $dataPrimaryId;
    protected $dataPrimaryColumnName;
    protected $dataTableName;

    protected $structureArticleId;
    protected $structureClangId;

    /**
     * Create a new manager instance.
     *
     * @param  \rex_extension_point $extensionPoint
     */
    public function __construct($extensionPoint)
    {
        $this->extensionPoint = $extensionPoint;
        $this->normalize();
        return $this;
    }

    protected function normalize()
    {
        switch ($this->extensionPoint->getName()) {
            case 'ART_ADDED':
            case 'ART_STATUS':
            case 'ART_UPDATED':
            case 'CAT_ADDED':
            case 'CAT_STATUS':
            case 'CAT_UPDATED':
                $this->setMode(self::MODE_UPDATE_URL_COLLECTION);
                $this->setStructureArticleId($this->extensionPoint->getParam('id'));
                $this->setStructureClangId($this->extensionPoint->getParam('clang'));
                break;

            case 'CLANG_ADDED':
            case 'CLANG_STATUS':
            case 'CLANG_UPDATED':
            // case 'CAT_UPDATED':
                $this->setMode(self::MODE_UPDATE_URL_ALL);
                break;

            case 'REX_FORM_SAVED':
                /* @var $object \rex_form */
                $object = $this->extensionPoint->getParam('form');
                $tableName = $object->getTableName();

                if ($tableName == \rex::getTable(Profile::TABLE_NAME)) {
                    // Profil wurde angelegt/aktualisiert
                    // Nur Urls dieses Profiles bearbeiten
                    $this->setMode(self::MODE_UPDATE_URL_COLLECTION);
                    $this->setStructureArticleId($object->elementPostValue(\rex_i18n::msg('url_generator_article_legend'), 'article_id'));
                    $this->setStructureClangId($object->elementPostValue(\rex_i18n::msg('url_generator_article_legend'), 'clang_id', '0'));
                } else {
                    // Datensatz wurde aktualisiert
                    // Urls neu schreiben
                    $primaryKey = \rex_sql_table::get($tableName)->getPrimaryKey()[0];

                    $this->setMode(self::MODE_UPDATE_URL_DATASET);
                    $this->setDatasetEditMode($object->isEditMode());
                    $this->setDatasetPrimaryId($object->getSql()->getValue($primaryKey));
                    $this->setDatasetPrimaryColumnName($primaryKey);
                    $this->setDatasetTableName($tableName);
                }

                break;

            case 'YFORM_DATA_ADDED':
            case 'YFORM_DATA_UPDATED':
                // dump($this->extensionPoint->getParams());
                /* @var $object \rex_yform_manager_dataset */
                $object = $this->extensionPoint->getParam('data');
                $tableName = $object->getTableName();
                $primaryKey = \rex_sql_table::get($tableName)->getPrimaryKey()[0];

                $this->setMode(self::MODE_UPDATE_URL_DATASET);
                $this->setDatasetEditMode(($this->extensionPoint->getParam('old_data') ? true : false));
                $this->setDatasetPrimaryId($object->getId());
                $this->setDatasetPrimaryColumnName($primaryKey);
                $this->setDatasetTableName($object->getTableName());
                break;
        }
    }

    public function getMode()
    {
        return $this->mode;
    }

    protected function setMode($mode)
    {
        $this->mode = $mode;
    }

    protected function setDatasetEditMode($mode)
    {
        $this->dataEditMode = $mode;
    }

    public function getDatasetPrimaryId()
    {
        return $this->dataPrimaryId;
    }

    protected function setDatasetPrimaryId($id)
    {
        $this->dataPrimaryId = $id;
    }

    public function getDatasetPrimaryColumnName()
    {
        return $this->dataPrimaryColumnName;
    }

    protected function setDatasetPrimaryColumnName($key)
    {
        $this->dataPrimaryColumnName = $key;
    }

    public function getDatasetTableName()
    {
        return $this->dataTableName;
    }

    protected function setDatasetTableName($tableName)
    {
        $this->dataTableName = $tableName;
    }

    public function getStructureArticleId()
    {
        return $this->structureArticleId;
    }

    protected function setStructureArticleId($id)
    {
        $this->structureArticleId = $id;
    }

    public function getStructureClangId()
    {
        return $this->structureClangId;
    }

    protected function setStructureClangId($clang_id)
    {
        $this->structureClangId = $clang_id;
    }

    public function __get($name)
    {
        if (method_exists($this, $name)) {
            return $this->$name();
        } elseif(property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }

}
