<?php

defined('_JEXEC') or die;

class CommentsModelPeople extends FOFModel {

    public function getImage($id) {
        $item = $this->getItem($id);
        $path = '/media/com_comments/images/avatar.png';

        if ($item->avatar) {
            if (JFile::exists(JPATH_ROOT . $item->avatar)) {
                $path = $item->avatar;
            }
        }

        return $path;
    }

}
