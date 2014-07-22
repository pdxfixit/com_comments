<?php

defined('_JEXEC') or die;

class CommentsModelWhiteips extends FOFModel {

    public function onAfterBuildQuery($query) {
        $fltIP = $this->getState('ip', null, 'string');
        if ($fltIP) {
            $fltIP = '%' . $fltIP . '%';
            $query->where($this->_db->qn('ip') . ' LIKE ' . $this->_db->q($fltIP));
        }

        $note = $this->getState('note', null, 'string');
        if ($note) {
            $note = '%' . $note . '%';
            $query->where($this->_db->qn('note') . ' LIKE ' . $this->_db->q($note));
        }

        return $query;
    }

}
