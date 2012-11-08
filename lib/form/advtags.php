<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Drop down for question categories.
 *
 * Contains HTML class for editing tags, both official and peronal.
 *
 * @package   core_form
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->libdir . '/form/group.php');

/**
 * Form field type for editing tags.
 *
 * HTML class for editing tags, both official and peronal.
 *
 * @package   core_form
 * @category  form
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_advtags extends MoodleQuickForm_group {
    /**
     * Inidcates that the user should be the usual interface, with the official
     * tags listed seprately, and a text box where they can type anything.
     * @var int
     */
    const DEFAULTUI = 'defaultui';

    /**
     * Indicates that the user should only be allowed to select official tags.
     * @var int
     */
    const ONLYOFFICIAL = 'onlyofficial';

    /**
     * Indicates that the user should just be given a text box to type in (they
     * can still type official tags though.
     * @var int
     */
    const NOOFFICIAL = 'noofficial';

    /**
     * Control the fieldnames for form elements display => int, one of the constants above.
     * @var array
     */
    protected $_options = array('display' => MoodleQuickForm_advtags::DEFAULTUI);

    /**
     * voreingestellte Text-Tags
     * @var array
     */
    protected $_officialtexttags = null;
    /**
     * voreingestellte Select-Tags
     * @var array
     */
    protected $_officialselecttags = null;
    
    /**
     * Constructor
     *
     * @param string $elementName Element name
     * @param mixed $elementLabel Label(s) for an element
     * @param array $options Options to control the element's display
     * @param mixed $attributes Either a typical HTML attribute string or an associative array.
     */
    function MoodleQuickForm_advtags($elementName = null, $elementLabel = null, $options = array(), $attributes = null) {
        $this->HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_appendName = true;
        $this->_type = 'advtags';
        // set the options, do not bother setting bogus ones
        if (is_array($options)) {
            foreach ($options as $name => $value) {
                if (isset($this->_options[$name])) {
                    if (is_array($value) && is_array($this->_options[$name])) {
                        $this->_options[$name] = array_merge($this->_options[$name], $value);
                    } else {
                        $this->_options[$name] = $value;
                    }
                }
            }
        }
        global $CFG;
        if (empty($CFG->usetags)) {
            debugging('A tags formslib field has been created even thought $CFG->usetags is false.', DEBUG_DEVELOPER);
        }
    }

    /**
     * Internal function to load official tags
     *
     * @access protected
     */
    protected function _load_official_tags() {
        global $CFG, $DB;
        if (!is_null($this->_officialtexttags) && !is_null($this->_officialselecttags)) {
            return;
        }
        $this->_officialtexttags = $DB->get_records_sql('SELECT id, name, rawname, description FROM {tag} WHERE tagtype = ? ', array('official_text' ));
        $this->_officialselecttags = $DB->get_records_sql('SELECT id, name, rawname, description FROM {tag} WHERE tagtype = ? ', array('official_select' ));
    }

    /**
     * Creates the group's elements.
     */
    function _createElements() {
        global $CFG, $OUTPUT;
        $this->_elements = array();

        // Official tags.
        //$namefield = empty($CFG->keeptagnamecase) ? 'name' : 'rawname';
        
        $this->_load_official_tags();
        if (count($this->_officialselecttags)> 0) {
            foreach ($this->_officialselecttags as $offtag) {
                $options = explode(",",$offtag->description);
                $this->_elements[] = @MoodleQuickForm::createElement('select', 'tag_'.$offtag->name, $offtag->rawname,array_combine($options, $options));
            }
        }
        if (count($this->_officialtexttags)> 0) {
            foreach ($this->_officialtexttags as $offtag) {
                $this->_elements[] = @MoodleQuickForm::createElement('text', 'tag_'.$offtag->name, $offtag->rawname);
            }
        }
        

                //$officialtags = array_combine($this->_officialtags, $this->_officialtags);
        /*$this->_elements[] = @MoodleQuickForm::createElement('text', 'semester', get_string('semester', 'theme_standard'));
        $this->_elements[] = @MoodleQuickForm::createElement('text', 'modulenr', get_string('modulenr', 'theme_standard'));
        $this->_elements[] = @MoodleQuickForm::createElement('text', 'author', get_string('author', 'theme_standard'));
		$this->_elements[] = @MoodleQuickForm::createElement('select',
                                                             'difficulty',
                                                             get_string('difficulty', 'theme_standard') ,
                                                             array(get_string('unknown', 'theme_standard'),
                                                                   get_string('easy', 'theme_standard'),
                                                                   get_string('medium', 'theme_standard'),
                                                                   get_string('hard', 'theme_standard')),
                                                             array('size' => 1));
        $this->_elements[] = @MoodleQuickForm::createElement('select',
                                                             'typeoftask',
                                                             get_string('typeoftask', 'theme_standard') ,
                                                             array(get_string('unknown', 'theme_standard'),
                                                                   get_string('facts', 'theme_standard'),
                                                                   get_string('application', 'theme_standard'),
                                                                   get_string('transfer', 'theme_standard')),
                                                             array('size' => 1));*/
        // Other tags.
        if ($this->_options['display'] != MoodleQuickForm_advtags::ONLYOFFICIAL) {
            $label = 'Weitere Schlagworte (kommagetrennt)';
            // E_STRICT creating elements without forms is nasty because it internally uses $this
            $othertags = @MoodleQuickForm::createElement('textarea', 'othertags', $label, array('cols'=>'40', 'rows'=>'5'));
            $this->_elements[] = $othertags;
        }

        // Paradoxically, the only way to get labels output is to ask for 'hidden'
        // labels, and then override the .accesshide class in the CSS!
        foreach ($this->_elements as $element){
            if (method_exists($element, 'setHiddenLabel')){
                $element->setHiddenLabel(true);
            }
        }
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event Name of event
     * @param mixed $arg event arguments
     * @param object $caller calling object
     */
    function onQuickFormEvent($event, $arg, &$caller) {
        switch ($event) {
            case 'updateValue':
                // Get the value we should be setting.
                $value = $this->_findValue($caller->_constantValues);
                if (null === $value) {
                    // if no boxes were checked, then there is no value in the array
                    // yet we don't want to display default value in this case
                    if ($caller->isSubmitted()) {
                        $value = $this->_findValue($caller->_submitValues);
                    } else {
                        $value = $this->_findValue($caller->_defaultValues);
                    }
                }

                if ((!isset($value['othertags']))) {
                    $this->_load_official_tags();
                    $officialtags = array();
                    foreach ($this->_officialselecttags as $ost) {
                        $officialtags[$ost->name] = "";
                    }
                    foreach ($this->_officialtexttags as $ott) {
                        $officialtags[$ott->name] = "";
                    }
                    $tagscontent = array();
                    $other = array();
                    if (!empty($value)) {
                        foreach ($value as $usedTag) {
                            $set = false;
                            $ispos = strpos($usedTag,"=");
                            if ($ispos > 0) {
                                $offtag = substr($usedTag,0,$ispos);
                                $tagval = substr($usedTag,$ispos+1);
                                if (strlen($tagval) > 0) {
                                    if (isset($officialtags[$offtag])) {
                                        if (!isset($tagscontent['tag_'.$offtag])) {
                                            $tagscontent['tag_'.$offtag] = $tagval;
                                        }
                                        $set = true;
                                    }
                                }
                            }
                            if (!$set) {
                                $other[] = $usedTag;
                            }
                        }
                    }
                    //Standardtags setzen
                    foreach ($this->_officialtexttags as $ott) {
                        if (!isset($tagscontent['tag_'.$ott->name])) {
                            $tagscontent['tag_'.$ott->name] = $ott->description;
                        }
                    }
                    $value = $tagscontent;
                    $value['othertags'] = implode(', ', $other);
                }
                if (!empty($value)) {
                    $this->setValue($value);
                }

                break;
            default:
                return parent::onQuickFormEvent($event, $arg, $caller);
        }
    }

    /**
     * Returns HTML for submitlink form element.
     *
     * @return string
     */
    function toHtml() {
        require_once('HTML/QuickForm/Renderer/Default.php');
        $renderer = new HTML_QuickForm_Renderer_Default();
        $renderer->setElementTemplate('{element}');
        parent::accept($renderer);
        return $renderer->toHtml();
    }

    /**
     * Accepts a renderer
     *
     * @param HTML_QuickForm_Renderer $renderer An HTML_QuickForm_Renderer object
     * @param bool $required Whether a group is required
     * @param string $error An error message associated with a group
     */
    function accept(&$renderer, $required = false, $error = null)
    {
        $renderer->renderElement($this, $required, $error);
    }

    /**
     * Output both official and peronal.
     *
     * @param array $submitValues values submitted.
     * @param bool $assoc specifies if returned array is associative
     * @return array
     */
    function exportValue(&$submitValues, $assoc = false) {
        $valuearray = array();

        // Get the data out of our child elements.
        foreach ($this->_elements as $element){
            $thisexport = $element->exportValue($submitValues[$this->getName()], true);
            if ($thisexport != null){
                $valuearray += $thisexport;
            }
        }

        // Get any manually typed tags.
        $tags = array();
        if ($this->_options['display'] != MoodleQuickForm_advtags::ONLYOFFICIAL &&
                !empty($valuearray['othertags'])) {
            $rawtags = explode(',', clean_param($valuearray['othertags'], PARAM_NOTAGS));
            foreach ($rawtags as $tag) {
                $tags[] = trim($tag);
            }
        }
        $this->_load_official_tags();
        $officialvalues = array();
        foreach ($this->_officialselecttags as $ost) {
            if (!empty($valuearray['tag_'.$ost->name])) {
                $officialvalues[] = $ost->name."=".$valuearray['tag_'.$ost->name];
            }
        }
        foreach ($this->_officialtexttags as $ott) {
            if (!empty($valuearray['tag_'.$ott->name])) {
                $officialvalues[] = $ott->name."=".$valuearray['tag_'.$ott->name];
            }
        }
        $tags = array_unique(array_merge($tags, $officialvalues));
        // Add any official tags that were selected.
        /*if ($this->_options['display'] != MoodleQuickForm_advtags::NOOFFICIAL &&
                !empty($valuearray['officialtags'])) {
            $tags = array_unique(array_merge($tags, $valuearray['officialtags']));
        }*/

        return array($this->getName() => $tags);
    }
}
