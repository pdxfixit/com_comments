<?php

defined('_JEXEC') or die;

class CommentsModelBlackemails extends FOFModel {

    public function onAfterBuildQuery($query) {
        $email = $this->getState('email', null, 'string');
        if ($email) {
            $email = '%' . $email . '%';
            $query->where($this->_db->qn('email') . ' LIKE ' . $this->_db->q($email));
        }

        $note = $this->getState('note', null, 'string');
        if ($note) {
            $note = '%' . $note . '%';
            $query->where($this->_db->qn('note') . ' LIKE ' . $this->_db->q($note));
        }

        return $query;
    }

}
