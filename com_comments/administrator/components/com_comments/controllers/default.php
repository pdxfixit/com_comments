<?php

defined('_JEXEC') or die;

class CommentsControllerDefault extends FOFController {

    public function disabled() {
        return parent::unpublish();
    }

    public function enabled() {
        return parent::publish();
    }

    // blacklist checks
    protected function onBeforeAdd() {
        $return = parent::onBeforeAdd();

        $identifier = FOFInflector::singularize($this->view);
        if (in_array($identifier, array('whiteip', 'blackip', 'blackemail', 'blackhost'))) {
            $input = $this->input;

            // if we have an ip then its an ip list
            $ip = $input->get('ip', '');
            if (!empty($ip)) {
                $ip = preg_replace('#[^a-f0-9:\.]#i', '', $ip);
                // Check the ip is valid
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    // check the ip doesn't already exist in this table
                    $ipTable = FOFTable::getAnInstance($identifier, 'CommentsTable');
                    if (!$ipTable->load(array('ip' => $ip))) {
                        //if the ip exists in the opposite table, then remove it and let them know
                        if ($identifier == 'whiteip') {
                            $note       = 'Manually Whitelisted';
                            $identifier = 'blackip';
                        } else {
                            $note       = 'Manually Blacklisted';
                            $identifier = 'whiteip';
                        }
                        $oppositeIpTable = FOFTable::getAnInstance($identifier, 'CommentsTable');
                        if ($oppositeIpTable->load(array('ip' => $ip))) {
                            $oppositeIpTable->delete();
                            throw new Exception(JText::_('COM_COMMENTS_IP_REMOVED_FROM_' . strtoupper($identifier)), 500);
                        }

                        $input->set('ip', $ip);
                        $input->set('note', $note);

                        return true;
                    } else {
                        throw new Exception(JText::_('COM_COMMENTS_IP_IS_ALREADY_' . strtoupper($identifier)), 500);
                    }
                } else {
                    throw new Exception(JText::_('COM_COMMENTS_IP_PROVIDED_DOES_NOT_APPEAR_TO_BE_VALID'), 500);
                }
            }

            // if we have an email then its an email blacklist
            $email = $input->get('email', '');
            if (!empty($email)) {
                $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    //check that the email address doesnt already exist
                    $emailTable = FOFTable::getAnInstance($identifier, 'CommentsTable');
                    if (!$emailTable->load(array('email' => $email))) {
                        $input->set('email', $email);
                        $input->set('note', 'Manually Blacklisted');

                        return true;
                    } else {
                        throw new Exception(JText::_('COM_COMMENTS_EMAIL_IS_ALREADY_BLACK_LISTED'), 500);
                    }
                } else {
                    throw new Exception(JText::_('COM_COMMENTS_EMAIL_PROVIDED_DOES_NOT_APPEAR_TO_BE_VALID'), 500);
                }
            }

            // if we have a domain then its a domain blacklist
            $domain = $input->get('name', '');
            if (!empty($domain)) {
                // check that the host doesn't already exist in this table
                $hostTable = FOFTable::getAnInstance($identifier, 'CommentsTable');
                if (!$hostTable->load(array('name' => $domain))) {
                    $input->set('name', $domain);
                    $input->set('note', 'Manually Blacklisted');

                    return true;
                } else {
                    throw new Exception(JText::_('COM_COMMENTS_DOMAIN_IS_ALREADY_BLACK_LISTED'), 500);
                }
            }
        }

        return $return;
    }

}
