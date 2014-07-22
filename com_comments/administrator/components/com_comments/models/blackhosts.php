<?php

defined('_JEXEC') or die;

class CommentsModelBlackhosts extends FOFModel {

    public function onAfterBuildQuery($query) {
        $name = $this->getState('name', null, 'string');
        if ($name) {
            $name = '%' . $name . '%';
            $query->where($this->_db->qn('name') . ' LIKE ' . $this->_db->q($name));
        }

        $note = $this->getState('note', null, 'string');
        if ($note) {
            $note = '%' . $note . '%';
            $query->where($this->_db->qn('note') . ' LIKE ' . $this->_db->q($note));
        }

        return $query;
    }

}
