<?php

defined('_JEXEC') or die;

// Load FOF
include_once JPATH_LIBRARIES . '/fof/include.php';
if (!defined('FOF_INCLUDED')) {
    throw new Exception('FOF is not installed', 500);
}

FOFDispatcher::getTmpInstance('com_comments')->dispatch();
