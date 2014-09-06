<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

class plgFlexicontent_fieldsRapidimageInstallerScript
{
    /**
     * method to run after an install/update/uninstall method
     * @param $type
     * @param $parent
     * @return void
     */
    function postflight($type, $parent)
    {
        if ($type == "install") {
            $row = JTable::getInstance('extension');
            $id = $row->find(array('name' => 'plg_flexicontent_fields_rapidimage'));
            if (!empty($id)) {
                $row->load($id);
                $row->enabled = 1;
                $row->check();
                $row->store();
            }
        }
    }
}
