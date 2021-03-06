<?php

include_once("./Services/COPage/classes/class.ilPageComponentPlugin.php");

/**
 * Example user interface plugin
 *
 * @author Guido Vollbach <gvollbach@databay.de>
 * @version $Id$
 *
 */
class ilMassMaticsPlugin extends ilPageComponentPlugin
{
    /**
     * Get plugin name
     *
     * @return string
     */
    function getPluginName()
    {
        return "MassMatics";
    }


    /**
     * Get plugin name
     *
     * @return string
     */
    function isValidParentType($a_parent_type)
    {
        if (in_array($a_parent_type, array("cont"))) {
            return true;
        }
        return false;
    }

    /**
     * Get Javascript files
     */
    function getJavascriptFiles($a_mode)
    {
        return array();
    }

    /**
     * Get css files
     */
    function getCssFiles($a_mode)
    {
        return array();
    }
}
