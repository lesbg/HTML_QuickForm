<?php
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
// +----------------------------------------------------------------------+
//
// $Id $

require_once('HTML/QuickForm/Renderer.php');

/**
 * A concrete renderer for HTML_QuickForm,
 * based on QuickForm 2.x built-in one
 * 
 * @access public
 */
class HTML_QuickForm_Renderer_Default extends HTML_QuickForm_Renderer
{
   /**
    * The HTML of the form  
    * @var string
    * @access    private
    */
    var $_html;

   /**
    * Header Template string
    * @var       string
    * @access    private
    */
    var $_headerTemplate = 
        "\n\t<tr>\n\t\t<td nowrap=\"nowrap\" align=\"left\" valign=\"top\" colspan=\"2\" bgcolor=\"#CCCCCC\"><b>{header}</b></td>\n\t</tr>";

   /**
    * Element template string
    * @var       string
    * @access    private
    */
    var $_elementTemplate = 
        "\n\t<tr>\n\t\t<td align=\"right\" valign=\"top\"><!-- BEGIN required --><font color=\"red\">*</font><!-- END required --><b>{label}</b></td>\n\t\t<td nowrap=\"nowrap\" valign=\"top\" align=\"left\"><!-- BEGIN error --><font color=\"#FF0000\">{error}</font><br><!-- END error -->\t{element}</td>\n\t</tr>";

   /**
    * Form template string
    * @var       string
    * @access    private
    */
    var $_formTemplate = 
        "\n<table border=\"0\">\n\t<form{attributes}>{content}\n\t</form>\n</table>";

   /**
    * Required Note template string
    * @var       string
    * @access    private
    */
    var $_requiredNoteTemplate = 
        "\n\t<tr>\n\t\t<td></td>\n\t<td align=\"left\" valign=\"top\">{requiredNote}</td>\n\t</tr>";

   /**
    * Array containing the templates for customised elements
    * @var       array
    * @access    private
    */
    var $_templates = array();

   /**
    * True if we are inside a group 
    * @var bool
    * @access private
    */
    var $_inGroup = false;

   /**
    * Array with HTML generated for group elements
    * @var array
    * @access private
    */
    var $_groupElements = array();

   /**
    * Template for an element inside a group
    * @var string
    * @access private
    */
    var $_groupElementTemplate = '';

   /**
    * HTML that wraps around the group elements
    * @var string
    * @access private
    */
    var $_groupWrap = '';
    
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
        return $this->_html;
    } // end func toHtml
    
   /**
    * Called when visiting a form, before processing any form elements
    *
    * @param object    An HTML_QuickForm object being visited
    * @access public
    * @return void
    */
    function startForm(&$form)
    {
        $this->_html = '';
    } // end func startForm

   /**
    * Called when visiting a form, after processing all form elements
    * Adds required note, form attributes, validation javascript and form content.
    * 
    * @param object     An HTML_QuickForm object being visited
    * @access public
    * @return void
    */
    function finishForm(&$form)
    {
        // add a required note, if one is needed
        if (!empty($form->_required) && !$form->_freezeAll) {
            $this->_html .= str_replace('{requiredNote}', $form->getRequiredNote(), $this->_requiredNoteTemplate);
        }
        // add form attributes and content
        $html = str_replace('{attributes}', $form->getAttributesString(), $this->_formTemplate);
        $this->_html = str_replace('{content}', $this->_html, $html);
        // add a validation script
        if ('' != ($script = $form->getValidationScript())) {
            $this->_html = $script . "\n" . $this->_html;
        }
    } // end func finishForm
      
   /**
    * Called when visiting a header
    * Might be changed soon to use static element instead
    *
    * @param string     header text to output
    * @access public
    * @return void
    */
    function renderHeader($header)
    {
        $this->_html .= str_replace('{header}', $header, $this->_headerTemplate);
    } // end func renderHeader

   /**
    * Helper method for renderElement
    *
    * @param string     Element name
    * @param string     Element label
    * @param bool       Whether an element is required
    * @param string     Error message associated with the element
    * @access private
    * @see   renderElement()
    * @return string 	Html for element
    */
    function _prepareTemplate($name, $label, $required, $error)
    {
        if (isset($this->_templates[$name])) {
            $html = str_replace('{label}', $label, $this->_templates[$name]);
        } else {
            $html = str_replace('{label}', $label, $this->_elementTemplate);
        }
        if ($required) {
            $html = str_replace('<!-- BEGIN required -->', '', $html);
            $html = str_replace('<!-- END required -->', '', $html);
        } else {
            $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN required -->(\s|\S)*<!-- END required -->([ \t\n\r]*)?/i", '', $html);
        }
        if (isset($error)) {
            $html = str_replace('{error}', $error, $html);
            $html = str_replace('<!-- BEGIN error -->', '', $html);
            $html = str_replace('<!-- END error -->', '', $html);
        } else {
            $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN error -->(\s|\S)*<!-- END error -->([ \t\n\r]*)?/i", '', $html);
        }
        return $html;
    } // end func _prepareTemplate

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
            $html = $this->_prepareTemplate($element->getName(), $element->getLabel(), $required, $error);
            $this->_html .= str_replace('{element}', $element->toHtml(), $html);

        } elseif (!empty($this->_groupElementTemplate)) {
            $html = str_replace('{label}', $element->getLabel(), $this->_groupElementTemplate);
            if ($required) {
                $html = str_replace('<!-- BEGIN required -->', '', $html);
                $html = str_replace('<!-- END required -->', '', $html);
            } else {
                $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN required -->(\s|\S)*<!-- END required -->([ \t\n\r]*)?/i", '', $html);
            }
            $this->_groupElements[] = str_replace('{element}', $element->toHtml(), $html);

        } else {
            $this->_groupElements[] = $element->toHtml();
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
        $this->_html .= "\n\t". $element->toHtml();
    } // end func renderHidden

   /**
    * Renders a data node
    * Called when visiting a data node
    * Might soon be replaced by a static element
    * 
    * @param object     An HTML_QuickForm_hidden object being visited
    * @access public
    * @return void
    */
    function renderData($data)
    {
        $this->_html .= $data;
    } // end func renderData

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
        $this->_groupWrap = $this->_prepareTemplate($group->getName(), $group->getLabel(), $required, $error);
        $this->_groupElements = array();
        $this->_groupElementTemplate = $group->_elementTemplate;
        $this->_inGroup = true;
    } // end func startGroup

   /**
    * Called when visiting a group, after processing all group elements
    *
    * @param object     An HTML_QuickForm_group object being visited
    * @access public
    * @return void
    */
    function finishGroup(&$group)
    {
        if (!empty($group->_groupTemplate)) {
            $html = str_replace('{content}', implode('', $this->_groupElements), $group->_groupTemplate);
        } else {
            $separator = $group->_seperator;
            if (is_array($separator)) {
                $count = count($separator);
                $html  = '';
                for ($i = 0; $i < count($this->_groupElements); $i++) {
                    $html .= $this->_groupElements[$i] . $separator[$i % $count];
                }
                $html = substr($html, 0, -strlen($separator[($i - 1) % $count]));
            } else {
                if (is_null($separator)) {
                    $separator = '&nbsp;';
                }
                $html = implode((string)$separator, $this->_groupElements);
            }
        }
        $this->_html   .= str_replace('{element}', $html, $this->_groupWrap);
        $this->_inGroup = false;
    } // end func finishGroup

    /**
     * Sets element template 
     *
     * @param     string	The HTML surrounding an element 
     * @param     string	(optional) Name of the element to apply template for
     * @access    public
     * @return    void
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
     * Sets header template
     * Might be changed soon in order to use a static element
     *
     * @param     string   The HTML surrounding the header 
     * @access    public
     * @return    void
     */
    function setHeaderTemplate($html)
    {
        $this->_headerTemplate = $html;
    } // end func setHeaderTemplate

    /**
     * Sets form template 
     *
     * @param     string	The HTML surrounding the form tags 
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
     * @param     string	The HTML surrounding the required note 
     * @access    public
     * @return    void
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
     * @return void
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