<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     enrol_razorpay
 * @category    string
 * @copyright   2021 Suraj Maurya surajmaurya450@gmail.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Razor Pay Enrollment';
$string['pluginname_desc'] = 'The RazorPay module allows you to set up paid courses.  If the cost for any course is zero, then students are not asked to pay for entry.  There is a site-wide cost that you set here as a default for the whole site and then a course setting that you can set for each course individually. The course cost overrides the site cost.';
$string['apikey'] = 'Razor Pay Api Key';
$string['apisecret'] = 'Razor pay Api Secret';
$string['mailadmins'] = 'Notify admin';
$string['mailstudents'] = 'Notify students';
$string['mailteachers'] = 'Notify teachers';
$string['expiredaction'] = 'Enrolment expiry action';
$string['expiredaction_help'] = 'Select action to carry out when user enrolment expires. Please note that some user data and settings are purged from course during course unenrolment.';
$string['status'] = 'Allow RazorPay enrolments';
$string['status_desc'] = 'Allow users to use RazorPay to enrol into a course by default.';
$string['cost'] = 'Enrol cost';
$string['currency'] = 'Currency';
$string['defaultrole_desc'] = 'Select role which should be assigned to users during RazorPay enrolments';
$string['enrolperiod_desc'] = 'Default length of time that the enrolment is valid. If set to zero, the enrolment duration will be unlimited by default.';
$string['defaultrole'] = 'Default role assignment';
$string['enrolperiod'] = 'Enrolment duration';
$string['assignrole'] = 'Assign role';
$string['enrolstartdate'] = 'Start date';
$string['enrolenddate'] = 'End date';
$string['enrolenddaterror'] = 'Enrolment end date cannot be earlier than start date';
$string['costerror'] = 'The enrolment cost is not numeric';
$string['nocost'] = 'There is no cost associated with enrolling in this course!';
$string['razorparaccepted'] = "Razor Pay Accepted";
$string['sendpaymentbutton'] = "Pay Using Razor Pay";
$string['paymentfailed'] = 'Payment Failed, Please try again';
$string['logo'] = 'Razor pay Logo';
$string['logodesc'] = 'Logo shown at Razor Pay Pop Up, It will be your site logo.';
$string['razorpay:config'] = 'Configure RazorPay enrol instances';
$string['razorpay:manage'] = 'Manage enrolled users';
$string['razorpay:unenrol'] = 'Unenrol users from course';
$string['razorpay:unenrolself'] = 'Unenrol self from the course';
$string['unenrolselfconfirm'] = 'Do you really want to unenrol yourself from course "{$a}"?';
$string['unenrolredirect'] = 'After Self Unenrol Redirect users to course page';
$string['purchasereport'] = 'Transaction report through razorpay';
$string['noinstance'] = 'No RazorPay instance created for the course';
$string['reportheading'] = 'Transaction report through Razor pay in course "{$a}"';
$string['instancename'] = 'Instance name';
$string['username'] = 'User name';
$string['useremail'] = 'User email';
$string['userphone'] = 'User Phone';
$string['amount'] = 'Amount';
$string['currency'] = 'Currency';
$string['course'] = 'Course';
$string['payedat'] = 'Payed at';
$string['paymentmode'] = 'Payment Mode';
$string['status'] = 'Status';
$string['errordescription'] = 'Error description';
$string['errorsource'] = 'Error source';
$string['fullpayment'] = 'Full Payment';
$string['subscription'] = 'Subscriptions Based';
$string['fullpaymentsubscription'] = 'Payment Type';
$string['planname'] = 'Plan Name';
$string['plandescription'] = 'Plan Description';
$string['intervalperiod'] = 'Biling Cycle';
$string['enrolstartdate_help'] = 'If enabled, users can be enrolled from this date onward only.';
$string['enrolenddate_help'] = 'If enabled, users can be enrolled until this date only.';
$string['subscriptiontilldate'] = 'Subscriptions rounds';
$string['intervalperiod_help'] = 'Used together with period to define how often the customer should be charged. For example, if you want to create a monthly subscription, pass period monthly and interval 1, <b> For daily plans, the minimum interval is 7. </b>';
$string['cost_help'] = 'Cost here will be Per Payment <b> Per EMI Payment </b>';
$string['subscriptiontilldate_help'] = '<b>No. of billing cycles to be charged</b>';
$string['interval'] = 'interval';
$string['period'] = 'period';
$string['enrolperiod_help'] = 'Help';
$string['paymentauthrizationonsub'] = "You are authorized for the Recurring Payment, Deducted about will be retured to your respective account";
$string['refundedamount'] = 'Your Deducted amount is reduned on you respective card. "{$a}"';