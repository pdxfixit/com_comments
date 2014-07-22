<?php

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Form Field class for the FOF framework
 * Shows the due date field, either as a calendar input or as a formatted due date field
 * @since       2.0
 */
class FOFFormFieldEnabled extends FOFFormFieldPublished {

    /**
     * Get the rendering of this field type for a repeatable (grid) display,
     * e.g. in a view listing many item (typically a "browse" task)
     * @since 2.0
     */
    public function getRepeatable() {
        if (!($this->item instanceof FOFTable)) {
            throw new Exception(__CLASS__ . ' needs a FOFTable to act upon');
        }

        // Initialise
        $prefix       = '';
        $checkbox     = 'cb';
        $publish_up   = null;
        $publish_down = null;
        $enabled      = true;

        // Get options
        if ($this->element['prefix']) {
            $prefix = (string)$this->element['prefix'];
        }

        if ($this->element['checkbox']) {
            $checkbox = (string)$this->element['checkbox'];
        }

        if ($this->element['publish_up']) {
            $publish_up = (string)$this->element['publish_up'];
        }

        if ($this->element['publish_down']) {
            $publish_down = (string)$this->element['publish_down'];
        }

        // Get the HTML
        #JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
        require_once(JPATH_COMPONENT . '/helpers/grid.php');

        return JHTML::_('commentsGrid.enabled', $this->value, $this->rowid, $prefix, $enabled, $checkbox, $publish_up, $publish_down);
    }

}
