<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Razor Pay enrolment plugin - support for user self unenrolment.
 *
 * @package    enrol_razorpay
 * @copyright  2021 Suraj maurya surajmaurya450@gmail.com  
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');

global $DB;

$courseid = required_param('id', PARAM_INT);

if (!$course = $DB->get_record('course', array('id'=>$courseid))) { // Check that course exists
    throw new Exception("Invalid course", 1);
    die;
}

$context = context_course::instance($course->id, MUST_EXIST);

if (has_capability('moodle/course:enrolconfig', $context) && has_capability('enrol/razorpay:config', $context)) {
}else{
	throw new Exception("You Don't have capability to view the report ");
	exit();
}

$PAGE->set_context($context);
$PAGE->set_heading(get_string('reportheading', 'enrol_razorpay', format_string($course->fullname)));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'enrol_razorpay'));
$PAGE->set_url( new moodle_url('/enrol/razorpay/subscription-report.php', array('id' => $courseid ) ) );
// Display page header.
echo $OUTPUT->header();

		$sql ="SELECT {enrol}.id, {enrol}.courseid, {enrol}.name, {enrol_razorpay_plans}.item_amount, {enrol_razorpay_plans}.item_currency, {enrol_razorpay_plans}.subscriptioncycle as subcriptioncycle, {enrol_razorpay_plans}.plan_id, {enrol_razorpay_subscription}.subscription_id FROM {enrol}";

		$sql.=" INNER JOIN {enrol_razorpay_plans} ON {enrol}.id = {enrol_razorpay_plans}.instanceid INNER JOIN {enrol_razorpay_subscription} ON {enrol_razorpay_subscription}.plan_id = {enrol_razorpay_plans}.plan_id";
	
		$sql .= " WHERE {enrol}.customchar2 = 'subscription' ";


	if ($course->id) {
		$sql.= "  AND {enrol}.courseid =  ".$course->id;		
	}

	$courses = $DB->get_records('course', array('visible' => 1 , 'summaryformat' => 1 ));
	$crsname = array();
	$crsids = array();
	if (!empty($courses)) {
		foreach ($courses as $key =>  $i) {
			$crsname[$key] = $i->fullname;
		}
	}

	$plugin = enrol_get_plugin('razorpay');
	$plansins = $DB->get_records_sql($sql);

	if (!empty($plansins)) {
		foreach ($plansins as $key => $pl) {
			// print_r($pl);
			$aplan = $plugin->getPlanById($pl->plan_id);
			$pl->createat = date("d M Y H:i:s", $aplan->created_at);
			$pl->interval = $aplan->interval;
			$pl->period = $aplan->period;
			$subs = $plugin->getSubscriptionById($pl->subscription_id);
			$pl->status = $subs->status; 
			$pl->current_start =  date("d M Y H:i:s", $subs->current_start);
			$pl->current_end =  date("d M Y H:i:s", $subs->current_end) ;
			$pl->charge_at =  date("d M Y H:i:s", $subs->charge_at) ;
			$pl->start_at =  date("d M Y H:i:s", $subs->start_at) ;
			$pl->end_at =  date("d M Y H:i:s", $subs->end_at) ;
			$pl->auth_attempts = $subs->auth_attempts;
			$pl->total_count = $subs->total_count;
			$pl->paid_count = $subs->paid_count;
			$pl->short_url = $subs->short_url;
			$pl->payment_method = $subs->payment_method;
		} 
	}

echo "<br />";
echo html_writer::start_tag('div', array('class' => 'row' ));
echo html_writer::start_tag('div', array('class' => 'col-md-6' ));
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', array('class' => 'col-md-6' ));
echo html_writer::start_span('zombie') . 'select course' . html_writer::end_span();
echo html_writer::select($crsname, 'arraytest', array($courseid), false, array("onchange"=>"(function(e){
    var crsid = document.getElementById(\"menuarraytest\").value;
    var url = window.location.href;
if(url.indexOf(\"?\") > 0) {
  url = url.substring(0, url.indexOf(\"?\"));
} 
url += \"?id=\"+crsid;
window.location.replace(url);
    return false;
})();return false;"));
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
echo "<br />";
if (!empty($plansins)) {
	// Get data here .
			$table = new html_table(array('class' => 'table-responsive' ));
			$table->head = ['Instance id', 'course','Item name', 'Amount', 'Currency', 'Biling Cycle', 'Plan Id', 'Susbcription id', 'Plan created at', 'interval', 'period', 'Susbscription status', 'subscription current start', 'subscription current start', 'Charge at', 'Start at', 'End at', 'auth_attempts', 'Total count', 'paid count', 'Short url', 'payment method'];
		    foreach ($plansins as $key => $value) {
		    	$table->data[] = $value;
		    }
echo html_writer::table($table);
	}else{
		\core\notification::info(get_string('noinstance', 'enrol_razorpay'));
	}


// Display page footer.
echo $OUTPUT->footer();