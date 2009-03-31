<?php
/**
 * @package epesi-libs
 * @subpackage QuickForm
 */
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Alexey Borzov <borz_off@cs.msu.su>                          |
// |          Adam Daniel <adaniel1@eesus.jnj.com>                        |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// |          Paul Bukowski <pbukowski@telaxus.com>                       |
// |          Arkadiusz Bisaga <abisaga@telaxus.com>                      |
// +----------------------------------------------------------------------+
//
//
// $Id$

require_once('HTML/QuickForm/Renderer.php');

/**
 * A concrete renderer for HTML_QuickForm,
 * based on QuickForm 2.x built-in one
 * 
 * @access public
 */
class HTML_QuickForm_Renderer_TCMSDefault extends HTML_QuickForm_Renderer
{
   /**
    * The HTML of the form  
    * @var      string
    * @access   private
    */
    var $_html;

   /**
    * Header Template string
    * @var      string
    * @access   private
    */
    var $_headerTemplate = 
        "\n\t<tr>\n\t\t<td class=\"header\" colspan=\"2\">{header}</td>\n\t</tr>";

   /**
    * Element template string
    * @var      string
    * @access   private
    */
    var $_elementTemplate = 
        "\n\t<tr>\n\t\t<td class=\"element\"><!-- BEGIN required --><span style=\"color: #ff0000\">*</span><!-- END required -->{label}</td>\n\t\t<td class=\"{element_style}\"><span class=\"error\" id=\"{error_id}\"></span>\t{element}</td>\n\t</tr>";

   /**
    * Form template string
    * @var      string
    * @access   private
    */
    var $_formTemplate = 
        "\n<form{attributes}>\n<div>\n{hidden}<table border=\"0\" id=\"quickform\">\n{content}\n</table>\n</div>\n</form>";

   /**
    * Required Note template string
    * @var      string
    * @access   private
    */
    var $_requiredNoteTemplate = 
        "\n\t<tr>\n\t\t<td></td>\n\t<td align=\"left\" valign=\"top\">{requiredNote}</td>\n\t</tr>";

   /**
    * Array containing the templates for customised elements
    * @var      array
    * @access   private
    */
    var $_templates = array();

   /**
    * Array containing the templates for group wraps.
    * 
    * These templates are wrapped around group elements and groups' own
    * templates wrap around them. This is set by setGroupTemplate().
    * 
    * @var      array
    * @access   private
    */
    var $_groupWraps = array();

   /**
    * Array containing the templates for elements within groups
    * @var      array
    * @access   private
    */
    var $_groupTemplates = array();

   /**
    * True if we are inside a group 
    * @var      bool
    * @access   private
    */
    var $_inGroup = false;

   /**
    * Array with HTML generated for group elements
    * @var      array
    * @access   private
    */
    var $_groupElements = array();

   /**
    * Template for an element inside a group
    * @var      string
    * @access   private
    */
    var $_groupElementTemplate = '';

   /**
    * HTML that wraps around the group elements
    * @var      string
    * @access   private
    */
    var $_groupWrap = '';

   /**
    * HTML for the current group
    * @var      string
    * @access   private
    */
    var $_groupTemplate = '';
    
   /**
    * Collected HTML of the hidden fields
    * @var      string
    * @access   private
    */
    var $_hiddenHtml = '';

   /**
    * Constructor
    *
    * @access public
    */
    function HTML_QuickForm_Renderer_Default()
    {
        $this->HTML_QuickForm_Renderer();
    } // end constructor

   /**
    * returns the HTML generated for the form
    *
    * @access public
    * @return string
    */
    function toHtml()
    {
        // _hiddenHtml is cleared in finishForm(), so this only matters when
        // finishForm() was not called (e.g. group::toHtml(), bug #3511)
        return $this->_hiddenHtml . $this->_html;
    } // end func toHtml
    
   /**
    * Called when visiting a form, before processing any form elements
    *
    * @param    object      An HTML_QuickForm object being visited
    * @access   public
    * @return   void
    */
    function startForm(&$form)
    {
        $this->_html = '';
        $this->_hiddenHtml = '';
        $this->_formName = $form->getAttribute('name');
	load_js('modules/Libs/QuickForm/Renderer/TCMSDefault.js');
    } // end func startForm

   /**
    * Called when visiting a form, after processing all form elements
    * Adds required note, form attributes, validation javascript and form content.
    * 
    * @param    object      An HTML_QuickForm object being visited
    * @access   public
    * @return   void
    */
    function finishForm(&$form)
    {
        // add a required note, if one is needed
        if (!empty($form->_required) && !$form->_freezeAll) {
            $this->_html .= str_replace('{requiredNote}', $form->getRequiredNote(), $this->_requiredNoteTemplate);
        }
        // add form attributes and content
        $html = str_replace('{attributes}', $form->getAttributes(true), $this->_formTemplate);
        if (strpos($this->_formTemplate, '{hidden}')) {
            $html = str_replace('{hidden}', $this->_hiddenHtml, $html);
        } else {
            $this->_html .= $this->_hiddenHtml;
        }
        $this->_hiddenHtml = '';
        $this->_html = str_replace('{content}', $this->_html, $html);
        // add a validation script
        if ('' != ($script = $form->getValidationScript())) {
            $this->_html = $script . "\n" . $this->_html;
        }
    } // end func finishForm
      
   /**
    * Called when visiting a header element
    *
    * @param    object     An HTML_QuickForm_header element being visited
    * @access   public
    * @return   void
    */
    function renderHeader(&$header)
    {
        $name = $header->getName();
        if (!empty($name) && isset($this->_templates[$name])) {
            $this->_html .= str_replace('{header}', $header->toHtml(), $this->_templates[$name]);
        } else {
            $this->_html .= str_replace('{header}', $header->toHtml(), $this->_headerTemplate);
        }
    } // end func renderHeader

   /**
    * Helper method for renderElement
    *
    * @param    string      Element name
    * @param    mixed       Element label (if using an array of labels, you should set the appropriate template)
    * @param    bool        Whether an element is required
    * @param    string      Error message associated with the element
    * @access   private
    * @see      renderElement()
    * @return   string      Html for element
    */
    function _prepareTemplate($name, $label, $required, $error)
    {
        if (is_array($label)) {
            $nameLabel = array_shift($label);
        } else {
            $nameLabel = $label;
        }
        if (isset($this->_templates[$name])) {
            $html = str_replace('{label}', $nameLabel, $this->_templates[$name]);
        } else {
            $html = str_replace('{label}', $nameLabel, $this->_elementTemplate);
        }
        if ($required) {
            $html = str_replace('<!-- BEGIN required -->', '', $html);
            $html = str_replace('<!-- END required -->', '', $html);
        } else {
            $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN required -->(\s|\S)*<!-- END required -->([ \t\n\r]*)?/iU", '', $html);
        }
        $html = str_replace('{error_id}', 'error'.$this->_formName.$name, $html);
  		eval_js('seterror(\'error'.$this->_formName.$name.'\',\''.addslashes($error).'\')');
        if (is_array($label)) {
            foreach($label as $key => $text) {
                $key  = is_int($key)? $key + 2: $key;
                $html = str_replace("{label_{$key}}", $text, $html);
                $html = str_replace("<!-- BEGIN label_{$key} -->", '', $html);
                $html = str_replace("<!-- END label_{$key} -->", '', $html);
            }
        }
        if (strpos($html, '{label_')) {
            $html = preg_replace('/\s*<!-- BEGIN label_(\S+) -->.*<!-- END label_\1 -->\s*/i', '', $html);
        }
        return $html;
    } // end func _prepareTemplate
    
    function _prepareValue(&$element) {
		$type = $element->getType();
    	$name = $element->getName();
		$value = '';
		if(!$element->isFrozen()) {
			if($type == 'text' || $type=='textarea' || $type=='hidden') {
				$value = $element->getValue();
	        	    	$element->setValue('');
	        		if($value!==null) {
					eval_js('settextvalue(\''.$this->_formName.'\',\''.$name.'\',"'.str_replace("\n",'\n',addslashes($value)).'")');
	    			}
			} elseif($type == 'select') {
				$value = $element->getValue();
  				$element->setValue(array());
				if($element->getMultiple()) $name .= '[]'; 
				if($value!==null)
					foreach($value as $v) {
						eval_js('setselectvalue(\''.$this->_formName.'\',\''.$name.'\',\''.str_replace("\n",'\n',addslashes(addslashes($v))).'\')');
					}
			} elseif($type == 'checkbox' || $type=='radio') {
		    		$value = $element->getAttribute('checked');
    		        	$element->removeAttribute('checked');
	    			if($value!==null) {
					if($type=='checkbox')
						eval_js('setcheckvalue(\''.$this->_formName.'\',\''.$name.'\',\''.addslashes(addslashes($value)).'\')');
					else
						eval_js('setradiovalue(\''.$this->_formName.'\',\''.$name.'\',\''.str_replace("\n",'\n',addslashes(addslashes($element->getValue()))).'\')');
	    			}
			} else {
				$value = $element->getValue();
        		if ($value!==null && !is_array($value)) eval_js('settextvalue(\''.$this->_formName.'\',\''.$name.'\',"'.str_replace("\n",'\n',addslashes($value)).'")');
			}
		}
    }

   /**
    * Renders an element Html
    * Called when visiting an element
    *
    * @param object     An HTML_QuickForm_element object being visited
    * @param bool       Whether an element is required
    * @param string     An error message associated with an element
    * @access public
    * @return void
    */
    function renderElement(&$element, $required, $error)
    {
		if (!$this->_inGroup) {
	    	$this->_prepareValue($element);
            $html = $this->_prepareTemplate($element->getName(), $element->getLabel(), $required, $error);
            $this->_html .= str_replace(array('{element}','{element_style}'), array($element->toHtml(),'element_'.$element->getType()), $html);

        } elseif (!empty($this->_groupElementTemplate)) {
            $html = str_replace('{label}', $element->getLabel(), $this->_groupElementTemplate);
            if ($required) {
                $html = str_replace('<!-- BEGIN required -->', '', $html);
                $html = str_replace('<!-- END required -->', '', $html);
            } else {
                $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN required -->(\s|\S)*<!-- END required -->([ \t\n\r]*)?/iU", '', $html);
            }

	    	$this->_prepareValue($element);
            $html = $this->_prepareTemplate($element->getName(), $element->getLabel(), $required, $error);
            $this->_groupElements[] = str_replace(array('{element}','{element_style}'), array($element->toHtml(),$element->getType()), $html);
            if (!$this->_groupType) $this->_groupType = $element->getType();
        } else {
            $this->_groupElements[] = $element->toHtml();
            if (!$this->_groupType) $this->_groupType = $element->getType();
        }
    } // end func renderElement
   
   /**
    * Renders an hidden element
    * Called when visiting a hidden element
    * 
    * @param object     An HTML_QuickForm_hidden object being visited
    * @access public
    * @return void
    */
    function renderHidden(&$element)
    {
		$this->_prepareValue($element);
        $this->_hiddenHtml .= $element->toHtml() . "\n";
    } // end func renderHidden

   /**
    * Called when visiting a raw HTML/text pseudo-element
    * 
    * @param  object     An HTML_QuickForm_html element being visited
    * @access public
    * @return void
    */
    function renderHtml(&$data)
    {
        $this->_html .= $data->toHtml();
    } // end func renderHtml

   /**
    * Called when visiting a group, before processing any group elements
    *
    * @param object     An HTML_QuickForm_group object being visited
    * @param bool       Whether a group is required
    * @param string     An error message associated with a group
    * @access public
    * @return void
    */
    function startGroup(&$group, $required, $error)
    {
        $name = $group->getName();
        $this->_groupTemplate        = $this->_prepareTemplate($name, $group->getLabel(), $required, $error);
        $this->_groupElementTemplate = empty($this->_groupTemplates[$name])? '': $this->_groupTemplates[$name];
        $this->_groupWrap            = empty($this->_groupWraps[$name])? '': $this->_groupWraps[$name];
        $this->_groupElements        = array();
        $this->_groupType            = null;
        $this->_inGroup              = true;
    } // end func startGroup

   /**
    * Called when visiting a group, after processing all group elements
    *
    * @param    object      An HTML_QuickForm_group object being visited
    * @access   public
    * @return   void
    */
    function finishGroup(&$group)
    {
        $separator = $group->_separator;
        if (is_array($separator)) {
            $count = count($separator);
            $html  = '';
            for ($i = 0; $i < count($this->_groupElements); $i++) {
                $html .= (0 == $i? '': $separator[($i - 1) % $count]) . $this->_groupElements[$i];
            }
        } else {
            if (is_null($separator)) {
                $separator = '&nbsp;';
            }
            $html = implode((string)$separator, $this->_groupElements);
        }
        if (!empty($this->_groupWrap)) {
            $html = str_replace('{content}', $html, $this->_groupWrap);
        }
        $this->_html   .= str_replace(array('{element}','{element_style}'),array($html,'element_'.$this->_groupType), $this->_groupTemplate);
        $this->_inGroup = false;
    } // end func finishGroup

    /**
     * Sets element template 
     *
     * @param       string      The HTML surrounding an element 
     * @param       string      (optional) Name of the element to apply template for
     * @access      public
     * @return      void
     */
    function setElementTemplate($html, $element = null)
    {
        if (is_null($element)) {
            $this->_elementTemplate = $html;
        } else {
            $this->_templates[$element] = $html;
        }
    } // end func setElementTemplate


    /**
     * Sets template for a group wrapper 
     * 
     * This template is contained within a group-as-element template 
     * set via setTemplate() and contains group's element templates, set
     * via setGroupElementTemplate()
     *
     * @param       string      The HTML surrounding group elements
     * @param       string      Name of the group to apply template for
     * @access      public
     * @return      void
     */
    function setGroupTemplate($html, $group)
    {
        $this->_groupWraps[$group] = $html;
    } // end func setGroupTemplate

    /**
     * Sets element template for elements within a group
     *
     * @param       string      The HTML surrounding an element 
     * @param       string      Name of the group to apply template for
     * @access      public
     * @return      void
     */
    function setGroupElementTemplate($html, $group)
    {
        $this->_groupTemplates[$group] = $html;
    } // end func setGroupElementTemplate

    /**
     * Sets header template
     *
     * @param       string      The HTML surrounding the header 
     * @access      public
     * @return      void
     */
    function setHeaderTemplate($html)
    {
        $this->_headerTemplate = $html;
    } // end func setHeaderTemplate

    /**
     * Sets form template 
     *
     * @param     string    The HTML surrounding the form tags 
     * @access    public
     * @return    void
     */
    function setFormTemplate($html)
    {
        $this->_formTemplate = $html;
    } // end func setFormTemplate

    /**
     * Sets the note indicating required fields template
     *
     * @param       string      The HTML surrounding the required note 
     * @access      public
     * @return      void
     */
    function setRequiredNoteTemplate($html)
    {
        $this->_requiredNoteTemplate = $html;
    } // end func setRequiredNoteTemplate

    /**
     * Clears all the HTML out of the templates that surround notes, elements, etc.
     * Useful when you want to use addData() to create a completely custom form look
     *
     * @access  public
     * @return  void
     */
    function clearAllTemplates()
    {
        $this->setElementTemplate('{element}');
        $this->setFormTemplate("\n\t<form{attributes}>{content}\n\t</form>\n");
        $this->setRequiredNoteTemplate('');
        $this->_templates = array();
    } // end func clearAllTemplates
} // end class HTML_QuickForm_Renderer_Default
?>
