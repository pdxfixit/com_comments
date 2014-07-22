<?php

defined('_JEXEC') or die;

class CommentsTableReport extends FOFTable {

    protected function onBeforeStore($updateNulls) {
        $this->ip = $this->getIp();

        return parent::onBeforeStore($updateNulls);
    }

    /**
     * Set the ip address of the user before saving the comment
     */
    public function getIp() {
        $ip = '';
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $matches = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
            if (array_key_exists(0, $matches)) {
                $ip = (filter_var(end($matches), FILTER_VALIDATE_IP));
            }
        } else if (array_key_exists("REMOTE_ADDR", $_SERVER)) {
            $ip = $_SERVER["REMOTE_ADDR"];
        }

        return $ip;
    }

}
