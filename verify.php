<?php

require("../../config.php");
require 'vendor/autoload.php';
require_once("$CFG->dirroot/enrol/razorpay/lib.php");
global $SESSION, $CFG;

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

// With logged in user not allowed.
require_login();

if (empty($_POST)) {
    throw new Exception("Direct Access not allowed", 1);
    exit();
}

// GET THE ENROLMENT PLUGIN.
$plugin = enrol_get_plugin('razorpay');
$api = $plugin->createApi();

// If some how payment is failed.

// Check if Error code is there.
if ( isset($_POST['errorcode']) && $_POST['errorcode'] != "" && isset( $_POST['errordescription'] ) && $_POST['errordescription'] != "" ) {
    // We have Payment Error here.

    $errorcode          = $_POST['errorcode'];
    $errordescription   = $_POST['errordescription'];
    $source             = $_POST['source'];
    $step               = $_POST['step'];
    $reason             = $_POST['reason'];
    $orderid            = $_POST['orderid'];
    $paymentid          = $_POST['paymentid'];

    // Let's Check for payment status again.

    // We need to add all the details in database also. And Dismiss the popup.
        
    try {
            $payment = $api->payment->fetch($paymentid);
            // We have payment and other information directly from the gateway.

            $paymentid  = $payment->id;
            $orderid    = $payment->order_id;
            $email      = $payment->email;
            $instanceid = $payment->notes->instanceid;
            $userid     = $payment->notes->userid;
            $courseid   = $payment->notes->courseid;
            $amount     = $payment->amount;
            $currency   = $payment->currency;
            $phone      = $payment->contact;
            $paymethod  = $payment->method;
            $item_name  = $payment->description;
            $paystring  = serialize($payment);

            $data                   = new stdClass;
            $data->userid           = $userid;
            $data->courseid         = $courseid;
            $data->instanceid       = $instanceid;
            $data->paymentid        = $paymentid;
            $data->orderid          = $orderid;
            $data->amount           = $amount;
            $data->currency         = $currency;
            $data->item_name        = $item_name;
            $data->user_email       = $email;
            $data->user_phone       = $phone;
            $data->paid_through     = $paymethod;
            $data->paymentrawdata   = $paystring;
            $data->status           = 'failed';
            $data->error_code       = $errorcode;
            $data->error_description= $errordescription;
            $data->error_source     = $source;
            $data->error_step       = $step;
            $data->error_reason     = $reason;
            $data->paymenttime      = time();
            $data->timeupdated      = time();

            // Save Error data as well to database for future use.
            $DB->insert_record("enrol_razorpay", $data);

            if (!empty($SESSION->wantsurl)) {
                $destination = $SESSION->wantsurl;
                unset($SESSION->wantsurl);
            } else {
                $destination = "$CFG->wwwroot/enrol/index.php?id=$course->id";
            }

            echo "error";

    } catch (Exception $e) {   }

}else{
    $success = true;
    $error = "Payment Failed";

    if (empty($_POST['razorpay_payment_id']) === false) {
        try {
            $attributes = array(
                'razorpay_order_id'     => $_SESSION['razorpay_order_id'],
                'razorpay_payment_id'   => $_POST['razorpay_payment_id'],
                'razorpay_signature'    => $_POST['razorpay_signature']
            );
            $api->utility->verifyPaymentSignature($attributes);
        }catch(SignatureVerificationError $e){
            $success = false;
            $error = 'Razorpay Error : ' . $e->getMessage();   
        }
    }
}

// Have Payment status Now.

if ($success === true){
	// Get all payment informations.
	$payment = $api->payment->fetch($_POST['razorpay_payment_id']);
    
    $paymentid  = $payment->id;
    $orderid    = $payment->order_id;
    $email	    = $payment->email;
    $instanceid = $payment->notes->instanceid;
    $userid		= $payment->notes->userid;
    $courseid 	= $payment->notes->courseid;
    $amount 	= $payment->amount;
    $currency	= $payment->currency;
    $phone      = $payment->contact;
    $paymethod  = $payment->method;

    // Verify all the data with Payment Gateway.

    if (!$user = $DB->get_record('user', array('id'=>$userid))) {   // Check that user exists
        \enrol_razorpay\util::message_razorpay_error_to_admin("User $data->userid doesn't exist", $data);
        die;
    }

    if (!$course = $DB->get_record('course', array('id'=>$courseid))) { // Check that course exists
        \enrol_razorpay\util::message_razorpay_error_to_admin("Course $data->courseid doesn't exist", $data);
        die;
    }

    // Check that amount paid is the correct amount
    
    if ( (float) $plugin_instance->cost <= 0 ) {
        $cost = (float) $plugin->get_config('cost');
    } else {
        $cost = (float) $plugin_instance->cost;
    }

    // Use the same rounding of floats as on the enrol form.
    $cost = format_float($cost, 2, false);

    if ($amount < $cost) {
        \enrol_razorpay\util::message_razorpay_error_to_admin("Amount paid is not enough ($data->payment_gross < $cost))", $data);
        die;
    }

    $user = $DB->get_record("user", array("id" => $userid), "*", MUST_EXIST);
	$course = $DB->get_record("course", array("id" => $courseid), "*", MUST_EXIST);
	$coursecontext = $context = context_course::instance($course->id, MUST_EXIST);
	$plugin_instance = $DB->get_record("enrol", array("id" => $instanceid, "enrol" => "razorpay", "status" => 0), "*", MUST_EXIST);

    // Create Object Of all payment and enrollment related data.

    $data = new stdClass;
    $data->userid           = $userid;
    $data->courseid         = $courseid;
    $data->instanceid       = $instanceid;
    $data->paymentid        = $paymentid;
    $data->orderid          = $orderid;
    $data->amount           = $amount;
    $data->currency         = $currency;
    $data->item_name        = format_string($course->fullname, true, array('context' => $coursecontext));
    $data->user_email       = $email;
    $data->user_phone       = $phone;
    $data->status           = 'success';
    $data->paid_through     = $paymethod;
    $data->paymentrawdata   = serialize($payment);
    $data->paymenttime      = time();
    $data->timeupdated      = time();

    //Save All data to database.

    $DB->insert_record("enrol_razorpay", $data);

        if ($plugin_instance->enrolperiod) {
            $timestart = time();
            $timeend   = $timestart + $plugin_instance->enrolperiod;
        } else {
            $timestart = 0;
            $timeend   = 0;
        }

        // Enrol user
        $plugin->enrol_user($plugin_instance, $user->id, $plugin_instance->roleid, $timestart, $timeend);

        // Pass $view=true to filter hidden caps if the user cannot see them
        if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                             '', '', '', '', false, true)) {
            $users = sort_by_roleassignment_authority($users, $context);
            $teacher = array_shift($users);
        } else {
            $teacher = false;
        }

        $mailstudents = $plugin->get_config('mailstudents');
        $mailteachers = $plugin->get_config('mailteachers');
        $mailadmins   = $plugin->get_config('mailadmins');
        $shortname = format_string($course->shortname, true, array('context' => $context));
        
        if (!empty($mailstudents)) {
            $a = new stdClass();
            $a->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
            $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";

            $eventdata = new \core\message\message();
            $eventdata->courseid          = $course->id;
            $eventdata->modulename        = 'moodle';
            $eventdata->component         = 'enrol_razorpay';
            $eventdata->name              = 'razorpay_enrolment';
            $eventdata->userfrom          = empty($teacher) ? core_user::get_noreply_user() : $teacher;
            $eventdata->userto            = $user;
            $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
            $eventdata->fullmessage       = get_string('welcometocoursetext', '', $a);
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml   = '';
            $eventdata->smallmessage      = '';
            message_send($eventdata);

        }

        if (!empty($mailteachers) && !empty($teacher)) {
            $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
            $a->user = fullname($user);

            $eventdata = new \core\message\message();
            $eventdata->courseid          = $course->id;
            $eventdata->modulename        = 'moodle';
            $eventdata->component         = 'enrol_razorpay';
            $eventdata->name              = 'razorpay_enrolment';
            $eventdata->userfrom          = $user;
            $eventdata->userto            = $teacher;
            $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
            $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml   = '';
            $eventdata->smallmessage      = '';
            message_send($eventdata);
        }

        if (!empty($mailadmins)) {
            $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
            $a->user = fullname($user);
            $admins = get_admins();
            foreach ($admins as $admin) {
                $eventdata = new \core\message\message();
                $eventdata->courseid          = $course->id;
                $eventdata->modulename        = 'moodle';
                $eventdata->component         = 'enrol_razorpay';
                $eventdata->name              = 'razorpay_enrolment';
                $eventdata->userfrom          = $user;
                $eventdata->userto            = $admin;
                $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
                $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml   = '';
                $eventdata->smallmessage      = '';
                message_send($eventdata);
            }
        }

        if (!empty($SESSION->wantsurl)) {
            $destination = $SESSION->wantsurl;
            unset($SESSION->wantsurl);
        } else {
            $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
        }

        $fullname = format_string($course->fullname, true, array('context' => $context));

        if (is_enrolled($context, NULL, '', true)) { // TODO: use real Razorpay check
            redirect($destination, get_string('paymentthanks', '', $fullname));

        } else {   /// Somehow they aren't enrolled yet!  :-(
            $PAGE->set_url($destination);
            echo $OUTPUT->header();
            $a = new stdClass();
            $a->teacher = get_string('defaultcourseteacher');
            $a->fullname = $fullname;
            notice(get_string('paymentsorry', '', $a), $destination);
        }
}