<?php
/**
 *
 * This is where all the various events will be declared and subsequently executed by the system
 * 
 * Use the following naming conventions
 * - Use only lowercase letters, numbers, dots (.) and underscores (_);
 * - Prefix names with a namespace followed by a dot (e.g. order., user.*);
 * - End names with a verb that indicates what action has been taken (e.g. order.placed)
 */

use Jenga\App\Request\Url;
use Jenga\App\Views\Redirect;
use Jenga\App\Views\Notifications;
use Jenga\App\Project\Core\Project;
use Jenga\MyProject\Users\Acl\Gateway;
use Jenga\App\Request\Session;

return [
    'auth.check' =>
        [
            function() {
 
                if (!Gateway::isLogged()) {
                        Redirect::withNotice('Please login to view this section')
                            ->to(Project::getConfigs()->frontend_url);
                } else {
                    return TRUE;
                }
            }, 10
        ]
];

