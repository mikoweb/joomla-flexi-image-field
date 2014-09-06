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
    static $field_types = array('rapidimage');

    /**
     * @param $subject
     * @param $params
     */
    public function __construct(&$subject, $params)
    {
        parent::__construct($subject, $params);
        JPlugin::loadLanguage('plg_flexicontent_fields_rapidimage', JPATH_ADMINISTRATOR);
    }
}
