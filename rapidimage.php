<?php

/*
 * This file is part of the Joomla FlexiContent Image Field
 *
 * website: www.vision-web.pl
 * (c) Rafał Mikołajun <rafal@vision-web.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

defined('_JEXEC') or die;
defined('RAPID_FRAMEWORK') or die('Joomla! Rapid Framework is not installed.');
jimport ('joomla.plugin.helper');

require_once JPATH_PLUGINS . "/flexicontent_fields/image/image.php";

use Joomla\Rapid\Document\DocumentOptimizer;
use Joomla\Rapid\FlexiContent\Images\FlexiImages;
use Joomla\RapidApp\App;

/**
 * Pole typu plik graficzny
 * @author  Rafał Mikołajun <rafal@vision-web.pl>
 * @package Joomla FlexiContent Image Field
 */
class plgFlexicontent_fieldsRapidimage extends plgFlexicontent_fieldsImage
{
    /**
     * @var DocumentOptimizer
     */
    private $docOptimizer = null;

    /**
     * @var FlexiImages
     */
    private $flexiImages = null;

    /**
     * @param JEventDispatche $subject
     * @param array $params
     */
    public function __construct(&$subject, $params)
    {
        plgFlexicontent_fieldsImage::$field_types[] = 'rapidimage';
        parent::__construct($subject, $params);
        JPlugin::loadLanguage('plg_flexicontent_fields_rapidimage', JPATH_ADMINISTRATOR);

        $this->docOptimizer = new DocumentOptimizer(App::container());
        $this->flexiImages = FlexiImages::create();
    }


    /**
     * Method to create field's HTML display for item form
     * @param stdClass $field
     * @param stdClass $item
     */
    public function onDisplayField(&$field, &$item)
    {
        parent::onDisplayField($field, $item);

        /*
         * Naprawiono opcje usuwania ilustracji
         */
        \JFactory::getDocument()->addScriptDeclaration('jQuery(document).ready(function($) {
            $(".container_fcfield").on("change", "input.imgremove[type=\"checkbox\"]", function() {
                var $this = $(this),
                    container = $this.closest(".container_fcfield"),
                    existingname = container.find("input.existingname").eq(0);

                if ($this.is(":checked")) {
                    existingname.data("old_value", existingname.val());
                    existingname.val("");
                } else {
                    existingname.val(existingname.data("old_value"));
                    existingname.data("old_value", "");
                }
            });
        });');
    }

    /**
     * Method to create field's HTML display for frontend views
     * @param stdClass $field
     * @param object $item
     * @param array|null $values
     * @param string $prop
     * @return array|void
     */
    function onDisplayFieldValue(&$field, $item, $values=null, $prop='display')
    {
        parent::onDisplayFieldValue($field, $item, $values, $prop);

        /*
         * Usuwanie zbędnych zasobów
         */
        $this->docOptimizer->removeResource('/components/com_flexicontent/librairies/multibox/Styles/multiBox.css', 'css');
        $this->docOptimizer->removeResource('/components/com_flexicontent/librairies/multibox/Styles/multiBoxIE6.css', 'css');
        $this->docOptimizer->removeResource('/components/com_flexicontent/librairies/multibox/multibox.css', 'css');
        $this->docOptimizer->removeResource('/components/com_flexicontent/librairies/multibox/Scripts/overlay.js', 'js');
        $this->docOptimizer->removeResource('/components/com_flexicontent/librairies/multibox/Scripts/multiBox.js', 'js');
        $this->docOptimizer->removeResource('/components/com_flexicontent/librairies/multibox/js/overlay.js', 'js');
        $this->docOptimizer->removeResource('/components/com_flexicontent/librairies/multibox/js/multibox.js', 'js');

        // generowanie danych ilustracji
        $field->image_data = array();
        $field->image_data_length = count($field->value);
        for ($i = 0; $i < $field->image_data_length;  $i++) {
            $field->image_data[$i] = $this->getImageData($field, $item, $i);
            $this->flexiImages->generate($field, $field->image_data[$i], $item);
        }
    }

    /**
     * Method to take any actions/cleanups
     * needed after field's values are saved into the DB
     * @param stdClass $field
     * @param array $post
     * @param array $file
     * @param flexicontent_items $item
     */
    function onAfterSaveField(&$field, &$post, &$file, &$item)
    {
        /*
         * Dane przed aktualizacją ilustracji
         */
        $beforeField = clone $field;
        $beforeItem = clone $item;
        $this->onDisplayFieldValue($beforeField, $beforeItem);

        $beforeData = array();
        for ($i = 0, $len = count($beforeField->value); $i < $len;  $i++) {
            $beforeData[$i] = $this->getImageData($beforeField, $beforeItem, $i);
        }

        // zrób co trzeba
        parent::onAfterSaveField($field, $post, $file, $item);

        /*
         * Dane po aktualizacji ilustracji
         */
        $afterField = clone $field;
        $afterItem = clone $item;

        // podmieniamy wartości tablicy 'value'
        $afterField->value = array();
        foreach($post as $i => $value) {;
            $afterField->value[$i] = $post[$i];
        }
        $this->onDisplayFieldValue($afterField, $afterItem);

        $afterData = array();
        for ($i = 0, $len = count($afterField->value); $i < $len;  $i++) {
            $afterData[$i] = $this->getImageData($afterField, $afterItem, $i);
        }

        // log
        //file_put_contents(JPATH_ROOT . '/field_log.txt', serialize($beforeData) . "\n\n\n" . serialize($afterData));

        $defaultData = array(
            'filename' => null,
            'filesize' => null,
            'filemtime' => null
        );

        /*
         * Tablice $originalOld i $originalNew będą porównywane
         * w celu ustalenia czy nastąpiły jakieś zmiany
         */
        $originalOld = array();
        $originalNew = array();

        /**
         * Funkcja generująca dane porównawcze
         * @param array $original
         * @param array $imageData
         * @param bool $sizeTimeFromCache
         */
        $setOriginal = function (array &$original, array &$imageData, $sizeTimeFromCache = false) use($defaultData) {
            foreach ($imageData as $i => &$data) {
                $original[$i] = $defaultData;
                if (isset($data['thumbs_path'])
                    && is_array($data['thumbs_path'])
                    && isset($data['thumbs_path']['original'])
                    && is_string($data['thumbs_path']['original'])
                ) {
                    $original[$i]['filename'] = $data['thumbs_path']['original'];
                    if ($sizeTimeFromCache) {
                        $cacheData = @unserialize(file_get_contents($original[$i]['filename'] . '.cache'));
                        if (is_array($cacheData)) {
                            if (isset($cacheData['filesize'])) {
                                $original[$i]['filesize'] = intval($cacheData['filesize']);
                            }
                            if (isset($cacheData['filemtime'])) {
                                $original[$i]['filemtime'] = intval($cacheData['filemtime']);
                            }
                        }
                    } else {
                        $original[$i]['filesize'] = @filesize($data['thumbs_path']['original']);
                        $original[$i]['filemtime'] = @filemtime($data['thumbs_path']['original']);
                    }
                }
            }
        };

        // wypełnij danymi
        $setOriginal($originalOld, $beforeData, true);
        $setOriginal($originalNew, $afterData);

        /*
         * Zapisywanie plików graficznych bądź też usuwanie
         * w zależności od $originalOld i $originalNew
         */

        $forceUpdate = false;
        $params = (isset($field->parameters) && $field->parameters instanceof JRegistry)
            ? $field->parameters : null;

        if (!is_null($params)) {
            $forceUpdate = $params->get('image_loader_autoregenerate', '0') == '1';
        }

        // usuwanie starych
        foreach ($beforeData as $i => &$data) {
            if (isset($originalOld[$i]) && (
                    $forceUpdate || (
                        !isset($originalNew[$i])
                        || $originalNew[$i]['filename'] != $originalOld[$i]['filename']
                        || $originalNew[$i]['filesize'] != $originalOld[$i]['filesize']
                        || $originalNew[$i]['filemtime'] != $originalOld[$i]['filemtime']
                    )
                )
            ) {
                $this->flexiImages->generate($beforeField, $data, $beforeItem, array("cleanup_mode" => true));
                // usuwanie pliku cache
                @unlink($originalOld[$i]['filename'] . '.cache');
                // log
                //file_put_contents(JPATH_ROOT . '/field_log.txt', file_get_contents(JPATH_ROOT . '/field_log.txt') . '[' . date('l jS F Y h:i:s A') . '] delete from: ' . $originalOld[$i]['filename'] . "\n");
            }
        }

        // zapisywanie nowych
        foreach ($afterData as $i => &$data) {
            if (isset($originalNew[$i]) && (
                    $forceUpdate || (
                        !isset($originalOld[$i])
                        || $originalNew[$i]['filename'] != $originalOld[$i]['filename']
                        || $originalNew[$i]['filesize'] != $originalOld[$i]['filesize']
                        || $originalNew[$i]['filemtime'] != $originalOld[$i]['filemtime']
                    )
                )
            ) {
                $this->flexiImages->generate($afterField, $data, $afterItem, array("forcesave" => true));
                // tworzenie pliku cache dla oryginalnej ilustracji
                $copy = $originalNew[$i];
                unset($copy['filename']);
                file_put_contents($originalNew[$i]['filename'] . '.cache', serialize($copy));
                // log
                //file_put_contents(JPATH_ROOT . '/field_log.txt', file_get_contents(JPATH_ROOT . '/field_log.txt') . '[' . date('l jS F Y h:i:s A') . '] saved from: ' . $originalNew[$i]['filename'] . "\n");
            }
        }
    }

    /**
     * Method called just before the item is deleted
     * to remove custom item data related to the field
     * @param stdClass $field
     * @param flexicontent_items $item
     * @return bool|void
     */
    function onBeforeDeleteField(&$field, &$item)
    {
        parent::onBeforeDeleteField($field, $item);
        $outputDir = FlexiImages::getImagesOutputDir($item, $field->name);
        if (is_string($outputDir) && !empty($outputDir)) {
            // usuwanie wszystkich plików graficznych z katalogu
            foreach(glob(JPATH_ROOT . $outputDir . '/{*.jpg,*.jpeg,*.gif,*.png,*.bmp}', GLOB_BRACE) as $file) {
                unlink($file);
            }

            // usuwanie katalogu
            rmdir(JPATH_ROOT . $outputDir);
        }
    }

    /**
     * Generowanie danych ilustracji na baze obiektu pola i wpisu
     * @param stdClass $field
     * @param object $item
     * @param integer $imageIndex
     * @param array options
     * @return array
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    private function &getImageData(stdClass $field, $item, $imageIndex = 0, array $options = array())
    {
        $data = array();

        if (is_integer($imageIndex) && is_array($field->value)
            && is_array($field->thumbs_src)
            && is_array($field->thumbs_path)
        ) {
            $values = array();
            foreach ($field->value as $val) {
                $values[] = unserialize($val);
            }

            if (isset($values[$imageIndex]) && is_array($values[$imageIndex])) {
                $data = array(
                    'alt' => $values[$imageIndex]['alt'],
                    'title' => $values[$imageIndex]['title'],
                    'description' => $values[$imageIndex]['desc'],
                    'thumbs_src' => array(
                        'backend' => $field->thumbs_src['backend'][$imageIndex],
                        'small' => $field->thumbs_src['small'][$imageIndex],
                        'medium' => $field->thumbs_src['medium'][$imageIndex],
                        'large' => $field->thumbs_src['large'][$imageIndex],
                        'original' => $field->thumbs_src['original'][$imageIndex]
                    ),
                    'thumbs_path' => array(
                        'backend' => $field->thumbs_path['backend'][$imageIndex],
                        'small' => $field->thumbs_path['small'][$imageIndex],
                        'medium' => $field->thumbs_path['medium'][$imageIndex],
                        'large' => $field->thumbs_path['large'][$imageIndex],
                        'original' => $field->thumbs_path['original'][$imageIndex]
                    ),
                    'picture' => array()
                );
            }
        }

        return $data;
    }
}
