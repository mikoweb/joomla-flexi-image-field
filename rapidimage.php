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
     * @param JEventDispatche $subject
     * @param array $params
     */
    public function __construct(&$subject, $params)
    {
        plgFlexicontent_fieldsImage::$field_types[] = 'rapidimage';
        parent::__construct($subject, $params);
        JPlugin::loadLanguage('plg_flexicontent_fields_rapidimage', JPATH_ADMINISTRATOR);

        $this->docOptimizer = new DocumentOptimizer(App::container());
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
     * @param stdClass $item
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
            $field->image_data[] = $this->getImageData($field, $item, $i);
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
        parent::onAfterSaveField($field, $post, $file, $item);

        /*
         * Potrzebne są dane ilustracji: ścieżki, url etc.
         */
        $displayField = clone $field;
        $displayItem = clone $item;
        $this->onDisplayFieldValue($displayField, $displayItem);

        // generowanie danych ilustracji
        $imageData = array();
        for ($i = 0, $len = count($displayField->value); $i < $len;  $i++) {
            $imageData[] = $this->getImageData($displayField, $displayItem, $i);
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
        if (!is_array($field->value) || !is_array($field->thumbs_src)
            || !is_array($field->thumbs_path)
        ) {
            throw new \UnexpectedValueException("Invalid field object");
        }

        if (!is_integer($imageIndex)) {
            throw new \InvalidArgumentException("imageIndex is not integer");
        }

        $values = array();
        foreach ($field->value as $val) {
            $values[] = unserialize($val);
        }

        if (!isset($values[$imageIndex]) || !is_array($values[$imageIndex])) {
            throw new \RuntimeException('Not exists image marked imageIndex: ' . $imageIndex);
        }

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

        return $data;
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
    }
}
