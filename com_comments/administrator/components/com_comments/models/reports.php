<?php

defined('_JEXEC') or die;

class CommentsModelReports extends FOFModel {

    public function buildQuery($overrideLimits = false) {
        $db    = $this->getDbo();
        $query = $db->getQuery(true)
                    ->select(array('*'))
                    ->from($db->qn('#__comments_reports'));

        $this->_buildWhereClause($db, $query);

        return $query;
    }

    public function hasReports() {
        $db    = $this->getDbo();
        $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->qn('#__comments_reports'));

        $this->_buildWhereClause($db, $query);

        $db->setQuery($query);

        if ($db->loadResult() > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function _buildWhereClause(&$db, &$query) {
        $commentId = $this->getState('comment_id', null, 'int');
        if (isset($commentId)) {
            $query->where($db->qn('comment_id') . ' = ' . $db->q($commentId));
        }

        $ip = $this->getState('ip', null, 'string');
        if ($ip) {
            $ip = '%' . $ip . '%';
            $query->where($db->qn('ip') . ' LIKE ' . $db->q($ip));
        }

        $state = $this->getState('state', null, 'int');
        if (isset($state)) {
            $query->where($db->qn('state') . ' = ' . $db->q($state));
        }

        $createdBy = $this->getState('created_by', null, 'int');
        if (isset($createdBy)) {
            $query->where($db->qn('created_by') . ' = ' . $db->q($createdBy));
        }
    }

}
