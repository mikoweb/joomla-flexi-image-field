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

/**
 * Pole typu plik graficzny
 * @author  Rafał Mikołajun <rafal@vision-web.pl>
 * @package Joomla FlexiContent Image Field
 */
class plgFlexicontent_fieldsRapidimage extends plgFlexicontent_fieldsImage
{
    /**
     * @param $subject
     * @param $params
     */
    public function __construct(&$subject, $params)
    {
        plgFlexicontent_fieldsImage::$field_types[] = 'rapidimage';
        parent::__construct($subject, $params);
        JPlugin::loadLanguage('plg_flexicontent_fields_rapidimage', JPATH_ADMINISTRATOR);
    }


    /**
     * Method to create field's HTML display for item form
     * @param $field
     * @param $item
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
}
