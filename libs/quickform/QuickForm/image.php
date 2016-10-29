<?php
/**
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @copyright   2001-2011 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 */

/**
 * Base class for <input /> form elements
 */
require_once 'HTML/QuickForm/input.php';

/**
 * HTML class for an <input type="image" /> element
 *
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 */
class HTML_QuickForm_image extends HTML_QuickForm_input
{
    /**
     * Class constructor
     *
     * @param     string    $elementName    (optional)Element name attribute
     * @param     string    $src            (optional)Image source
     * @param     mixed     $attributes     (optional)Either a typical HTML attribute string
     *                                      or an associative array
     */
    function HTML_QuickForm_image($elementName=null, $src='', $attributes=null)
    {
        HTML_QuickForm_input::HTML_QuickForm_input($elementName, null, $attributes);
        $this->setType('image');
        $this->setSource($src);
    }

    /**
     * Sets source for image element
     *
     * @param     string    $src  source for image element
     */
    function setSource($src)
    {
        $this->updateAttributes(array('src' => $src));
    }

    /**
     * Sets border size for image element
     *
     * @param     string    $border  border for image element
     */
    function setBorder($border)
    {
        $this->updateAttributes(array('border' => $border));
    }

    /**
     * Sets alignment for image element
     *
     * @param     string    $align  alignment for image element
     */
    function setAlign($align)
    {
        $this->updateAttributes(array('align' => $align));
    }

    /**
     * Freeze the element so that only its value is returned
     */
    function freeze()
    {
        return false;
    }
}
?>
