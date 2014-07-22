<?php

defined('_JEXEC') or die;

class CommentsModelComments extends FOFModel {

    private function getArticleTitle($id) {
        $query = $this->_db->getQuery(true);
        $query->select($this->_db->qn('title'))
              ->from($this->_db->qn('#__content'))
              ->where($this->_db->qn('id') . ' = ' . $this->_db->q($id));
        $this->_db->setQuery($query);

        return $this->_db->loadResult();
    }

    /**
     * Returns a list of items
     *
     * @param   boolean $overrideLimits Should I override set limits?
     * @param   string  $group          The group by clause
     *
     * @return  array
     */
    public function &getItemList($overrideLimits = false, $group = '') {
        if (empty($this->list)) {
            $query = $this->buildQuery($overrideLimits);

            // this is needed for when the View creates the Timeline
            $task = $this->getState('task', 'edit');
            if ($task !== 'browse') {
                // todo: FUTURE REFACTOR; do a direct SELECT statement, instead of a join.
                $table = $this->getTable();
                $query->select('quality')->leftJoin('#__comments_spam_reports AS s ON ( s.comment_id = ' . $table->getTableName() . '.' . $table->getKeyName() . ' ) ');
            }

            if (!$overrideLimits) {
                $limitstart = $this->getState('limitstart');
                $limit      = $this->getState('limit');
                $this->list = $this->_getList((string)$query, $limitstart, $limit, $group);
            } else {
                $this->list = $this->_getList((string)$query, 0, 0, $group);
            }
        }

        return $this->list;
    }

    protected function onAfterGetItem(&$record) {
        if (JFactory::getApplication()->isAdmin() && isset($record->table) && !empty($record->table) && isset($record->row) && !empty($record->row)) {
            switch ($record->table) {
                case 'content':
                    $record->item = $this->getArticleTitle($record->row);
                    $record->url  = 'index.php?option=com_content&task=article.edit&id=' . $record->row;
                    break;
            }
        }
    }

    protected function onBeforeSave(&$data, &$table) {
        $return = parent::onBeforeSave($data, $table);

        /* Reports */
        if ($reports = $this->input->get('report', null)) { // we're really getting an array back.
            foreach ($reports as $id => $state) {
                $this->updateReportState($id, $state);
            }
        }

        /* Subscriptions */
        if ($subscribe = $this->input->get('subscribe', 0, 'int')) {
            $user              = JFactory::getUser();
            $subscriptionData  = array('user_id' => $user->id, 'row' => $data['row'], 'table' => $data['table'], 'email' => $data['email']);
            $subscriptionModel = FOFModel::getAnInstance('Subscription', 'CommentsModel');

            $subscriptionModel->save($subscriptionData);
        }

        return $return;
    }

    // For each record, get the table and row from the relations, and find the title.
    protected function onProcessList(&$resultArray) {
        foreach ($resultArray as $record) {
            $this->onAfterGetItem($record);
        }
    }

    private function updateReportState($id, $state) { // this is a hack (not using a table) to get-the-job-done.
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true)
                    ->update($db->qn('#__comments_reports'))
                    ->set($db->qn('state') . ' = ' . (int)$state)
                    ->where($db->qn('comments_report_id') . ' = ' . (int)$id);
        $db->setQuery($query);

        return $db->execute();
    }

}
