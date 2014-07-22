<?php

defined('_JEXEC') or die;

// Load FOF
include_once JPATH_LIBRARIES . '/fof/include.php';
if (!defined('FOF_INCLUDED')) {
    throw new Exception('FOF is not installed', 500);
}

function CommentsBuildRoute(&$query) {
    $segments = array();

    // Get any id whatsoever
    if (!isset($query['Itemid'])) {
        $item = JSite::getMenu()->getItems('component', 'com_comments', true);
        if (isset($item->id)) {
            $query['Itemid'] = $item->id;
        }
    }

    // Get the current menu id
    if (isset($query['view'])) {
        $item = JSite::getMenu()->getItems('link', 'index.php?option=com_comments&view=' . FOFInflector::pluralize($query['view']), true);
        $id   = (isset($item->id)) ? $item->id : null;

        $query['Itemid'] = (isset($query['Itemid']) && !$id) ? $query['Itemid'] : $id;
    }

    if (array_key_exists('view', $query)) {
        $segments[0] = $query['view'];

        if (array_key_exists('id', $query)) {
            $segments[1] = $query['id'];
            unset($query['id']);
        }

        unset($query['view']);
    }

    return $segments;
}

function CommentsParseRoute($segments) {
    if (isset($segments[0])) {
        $id           = current(explode(':', $segments[0]));
        $vars['view'] = $segments[0];

        if (isset($segments[1])) {

            $vars['id'] = (int)$segments[1];

            $vars['view'] = FOFInflector::singularize($vars['view']);

            if (isset($segments[2]) && isset($segments[3])) {
                $vars[FOFInflector::singularize($segments[2])] = $segments[3];
            }
        }
    }

    return $vars;
}
