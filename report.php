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
$PAGE->set_url( new moodle_url('/enrol/razorpay/report.php', array('id' => $courseid ) ) );
// Display page header.
echo $OUTPUT->header();

$plugininstances = $DB->get_records("enrol", array("enrol" => "razorpay", "status" => 0, "courseid" => $course->id ));

$allinsts = $DB->get_records("enrol", array("enrol" => "razorpay", "status" => 0));

$crsids = array();
if (!empty($allinsts)) {
	foreach ( $allinsts as $value) {
		array_push($crsids, $value->courseid);
	}
}

$crsids = array_unique($crsids);

$crsname = array();

if (!empty($crsids)) {
	foreach ($crsids as $i) {
		$course = $DB->get_record('course', array('id'=>$i));
		$crsname[$i] = $course->fullname;
	}
}

$razorpays = array();

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

if (!empty($plugininstances)) {
	// Get data here .
			$table = new html_table();
			$heads = array();
			$cols=array('instancename','username','useremail','userphone','amount','currency','course','payedat','paymentmode','status','errordescription','errorsource');
			foreach ($cols as $col) {
				array_push($heads, get_string($col, 'enrol_razorpay'));
			}

			$table->head = $heads;
	
	foreach ($plugininstances as $key => $razorinstance) {
		$razorpays = $DB->get_records('enrol_razorpay', array('courseid' => $course->id, 'instanceid' => $razorinstance->id )); 	
		if (!empty($razorpays)) {
			foreach ($razorpays as $records) {
			$fullname = @$DB->get_record('user', array('id' => $records->userid ))->firstname ." ".@$DB->get_record('user', array('id' => $records->userid ))->lastname;
			if ($records->status == 'failed') { 
				$htmlstat = html_writer::start_span('alert  alert-warning') . $records->status . html_writer::end_span();
			}elseif ($records->status == 'success') {
				$htmlstat = html_writer::start_span('alert alert-success') . $records->status . html_writer::end_span();
			}else{
				$htmlstat = html_writer::start_span('alert alert-primary') . $records->status . html_writer::end_span();
			}

		    $table->data[] = array(
		    	$razorinstance->name, 
		    	$fullname,
		    	$records->user_email,
		    	$records->user_phone,
		    	$records->amount,
		    	$records->currency,
		    	$records->item_name,
		    	date("d M Y H:i:s", $records->paymenttime),
		    	$records->paid_through,
		    	$htmlstat,
		    	$records->error_description,
		    	$records->error_source,
		    );
		}
		}else{
			\core\notification::info('Payment Not started');
		}
	}

echo html_writer::table($table);

}else{
 	\core\notification::info(get_string('noinstance', 'enrol_razorpay'));
}

// Display page footer.
echo $OUTPUT->footer();