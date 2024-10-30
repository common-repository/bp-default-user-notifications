<?php

/**
 * @author Neumann S Valle
 * class BPdefaultUserNotification
 * License GPLv2
 * @ vcomputadoras@yahoo.com
 * 
 */
class BPdefaultUserNotification
{

    private $count = 0;

    private $countfailed = 0;

    private $getNusers = 1000;

    private $offset = 0;

    private static $class = null;

    public static $ver = "1.0.0";

    // default notifications
    private static $notifications = [
        "notification_activity_new_mention",
        "notification_activity_new_reply",
        "notification_messages_new_message",
        "notification_friends_friendship_request",
        "notification_friends_friendship_accepted",
        "notification_groups_invite",
        "notification_groups_group_updated",
        "notification_groups_admin_promotion",
        "notification_groups_membership_request",
        "notification_membership_request_completed"
    ];

    public static function init()
    {
        if (null === self::$class) {

            self::$class = new self();

            return self::$class;
        }
    }

    public function __construct()
    {
        $this->notificationProcessor();
    }

    public function notificationProcessor()
    {

        // if can activate plugin is admin
        if (current_user_can("activate_plugins")) {

            if (isset($_POST["posted"])) {

                // if Javascript sent offet as post parameter then update
                if (isset($_POST["offset"])) {

                    $this->offset = (int) $_POST["offset"];
                }

                // returns 404 if security is wrong
                check_ajax_referer("bp-default-user-notification", "security");

                $user_query = new WP_User_Query(array(
                    "role__not_in" => "Administrator",
                    "orderby" => "ID",
                    "number" => $this->getNusers,
                    "offset" => $this->offset
                ));

                if (! empty($user_query->get_results())) {

                    if ((isset($_POST["process"]) && count($_POST["process"]) > 0)) {

                        foreach ($user_query->get_results() as $user) {

                            // update
                            $done = $this->setDefaultNotifications($user->ID, $_POST["process"]);

                            if ($done) {

                                $this->count ++;
                            } else {

                                $this->countfailed ++;
                            }
                        }

                        // save new offset, as long count is more than 0
                        $this->offset = ($this->count > 0) ? ($this->offset + $this->count) : 0;

                        // if we offset is same as total users
                        if ($this->offset === $user_query->get_total()) {

                            $this->offset = 0;
                        }

                        echo json_encode([
                            "success" => true,
                            "msg" => "Notification settings updated!, last " . $this->count . " processed users were updated in the database.",
                            "updated" => $this->count,
                            "failed" => $this->countfailed,
                            "users" => $user_query->get_total(),
                            "new_offset" => $this->offset
                        ]);
                    }
                } else {

                    echo json_encode([
                        "success" => false,
                        "err" => "opps, getting users failed to work... check server logs..",
                        "new_offset" => $this->offset
                    ]);
                }
            }
        } else {
            // not an admin
            wp_die("forbidden", "unauthorized", 403);
        }

        wp_die();
    }

    public function setDefaultNotifications($user_id, $notifications = [])
    {
        $bp_notifications = self::getBPnotifications();

        for ($i = 0; $i < count($notifications); $i ++) {

            for ($a = 0; $a < count($bp_notifications); $a ++) {

                // check security , make sure this is true
                if (isset($notifications[$i][$bp_notifications[$a]])) {

                    bp_update_user_meta($user_id, $bp_notifications[$a], $notifications[$i][$bp_notifications[$a]]);
                }
            }
        }

        return true;
    }

    public static function getBPnotifications()
    {
        return self::$notifications;
    }

    public static function createBPcheckboxs()
    {
        echo '<div class="bd-d-u-settings-container">';

        if (function_exists("bp_is_active")) {

            $bp_notifications = self::getBPnotifications();

            $notification_msg = [
                "A member mentions you in an update using \"@user\"",
                "A member replies to an update or comment you've posted",
                "A member sends you a new message",
                "A member sends you a friendship request",
                "A member accepts your friendship request",

                "A member invites you to join a group",
                "Group information is updated",
                "You are promoted to a group administrator or moderator",
                "A member requests to join a private group for which you are an admin",
                "Your request to join a group has been approved or denied"
            ];

            echo '<table class="bd-d-u-settings">
                    <caption class="settings-title">Set default settings to Buddypress user Notifications</caption>';

            for ($i = 0; $i < count($bp_notifications); $i ++) {

                echo '<tr>
                        <td>
                             <span class="desc"><strong>' . $notification_msg[$i] . '</strong></span>
                                <div class="check-markup">
                                 <span> No </span>
                                 <label class="switch">
                                    <input type="checkbox" class="chk" name="' . $bp_notifications[$i] . '" checked>
                                 <span class="slider round"></span>
                                 </label>
                                 <span> Yes </span>
                                </div>
                        </td>
                     </tr>';
            }

            echo '<tr>
                        <td class="bt-d-u-warning">
                            <span>
                                Warning, updating might take some time, also if it stops you will need to increase the execution time in you php.ini. </br>
                                Click reset button to set Buddypress notifications default, affects all users but <strong>Administrator roles</strong>.
                            </span>
                           <td>
                         </tr>
                         <tr>
                          <td>
                             <p><button class="bp-d-u-submit button">Update Notifications</button> <button class="bp-d-u-reset button reset">reset</button></p>
                          <td>
                        </tr>
                        <tr>
                            <td><p class="bp-d-u-version">Buddypress Default user notifications Ver <strong>' . self::$ver . '</strong></p></td>
                        </tr>
                                  
                    </table>';
        } else {

            echo '<p class="fatal-err">"BP default user noifications" plugin requires Buddypress to be installed in order to work.</p>';
        }

        echo '</div>';

        wp_die();
    }
}

