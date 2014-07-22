<?php

defined('_JEXEC') or die();

abstract class JHtmlCommentsGrid extends JHtmlJGrid {

    static function enabled($value = 0, $i) {
        $states = array(0 => array('cancel', 'enabled', '', 'COM_COMMENTS_STATE_FALSE'),
                        1 => array('checkmark', 'disabled', '', 'COM_COMMENTS_STATE_TRUE'),
                        2 => array('warning', 'enabled', '', 'COM_COMMENTS_STATE_MODERATED'),
                        3 => array('locked', 'enabled', '', 'COM_COMMENTS_STATE_HELD'));
        $state  = JArrayHelper::getValue($states, (int)$value, $states[1]);
        $html   = '<i class="icon-' . $state[0] . '"></i>';
        $html   = '<a class="btn btn-micro" href="javascript:void(0);" onclick="return listItemTask(\'cb' . $i . '\',\'' . $state[1] . '\')" title="' . JText::_($state[3]) . '">' . $html . '</a>';

        return $html;
    }
}
