<?php

defined('_JEXEC') or die;

class CommentsModelSubscriptions extends FOFModel {

    public function amIaSubscriber($userid, $item, $table) {
        $db    = & $this->getDbo();
        $query = $db->getQuery(true)
                    ->select('user_id')
                    ->from($db->qn('#__comments_subscriptions'))
                    ->where($db->qn('user_id') . ' = ' . $db->q($userid))
                    ->where($db->qn('row') . ' = ' . $db->q($item->id))
                    ->where($db->qn('table') . ' = ' . $db->q($table));
        $db->setQuery($query);
        $result = $db->loadResult();

        if ($result == $userid) {
            return true;
        } else {
            return false;
        }
    }

    public function buildQuery($overrideLimits = false) {
        $db    = $this->getDbo();
        $query = $db->getQuery(true)
                    ->select(array('*'))
                    ->from($db->quoteName('#__comments_subscriptions'));

        $uuid = $this->getState('uuid', null, 'string');
        if ($uuid) {
            $query->where($db->quoteName('uuid') . ' = ' . $db->quote($uuid));
        }

        $userid = $this->getState('user_id', null, 'string');
        if ($userid) {
            $query->where($db->quoteName('user_id') . ' = ' . $db->quote($userid));
        }

        $row = $this->getState('row', null, 'string');
        if ($row) {
            $query->where($db->quoteName('row') . ' LIKE ' . $db->quote($row));
        }

        $email = $this->getState('email', null, 'string');
        if ($email) {
            $email = '%' . $email . '%';
            $query->where($db->quoteName('email') . ' LIKE ' . $db->quote($email));
        }

        if (!$overrideLimits) {
            $order = $this->getState('filter_order', null, 'cmd');
            if (!in_array($order, array_keys($this->getTable()->getData()))) {
                $order = 'comments_subscription_id';
            }
            $dir = $this->getState('filter_order_Dir', 'ASC', 'cmd');
            $query->order($order . ' ' . $dir);
        }

        return $query;
    }

    protected function onBeforeSave(&$data, &$table) {
        $subscriptionModel = FOFModel::getAnInstance('Subscriptions', 'CommentsModel');

        foreach ($data as $key => $value) {
            $subscriptionModel->setState($key, $value);
        }

        if (!$subscriptionModel->getFirstItem()->comments_subscription_id) {
            return parent::onBeforeSave($data, $table);
        } else {
            return false;
        }
    }

}
