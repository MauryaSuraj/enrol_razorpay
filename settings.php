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
 * Plugin administration pages are defined here.
 *
 * @package     enrol_razorpay
 * @category    admin
 * @copyright   2021 Suraj Maurya surajmaurya450@gmail.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

    if ($hassiteconfig) {
        
        if ($ADMIN->fulltree) {

        //--- settings ------------------------------------------------------------------------------------------
        $settings->add(new admin_setting_heading('enrol_razorpay_settings', '', get_string('pluginname_desc', 'enrol_razorpay')));

        $settings->add(new admin_setting_configtext('enrol_razorpay/apikey', get_string('apikey', 'enrol_razorpay'), get_string('apikey', 'enrol_razorpay'), '', PARAM_RAW));

        $settings->add(new admin_setting_configtext('enrol_razorpay/apisecret', get_string('apisecret', 'enrol_razorpay'), get_string('apisecret', 'enrol_razorpay'), '', PARAM_RAW));

        $settings->add(new admin_setting_configcheckbox('enrol_razorpay/mailstudents', get_string('mailstudents', 'enrol_razorpay'), '', 0));

        $settings->add(new admin_setting_configcheckbox('enrol_razorpay/mailteachers', get_string('mailteachers', 'enrol_razorpay'), '', 0));

        $settings->add(new admin_setting_configcheckbox('enrol_razorpay/mailadmins', get_string('mailadmins', 'enrol_razorpay'), '', 0));

        // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
        //       it describes what should happen when users are not supposed to be enrolled any more.
        $options = array(
            ENROL_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'enrol'),
            ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'),
            ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
        );
        $settings->add(new admin_setting_configselect('enrol_razorpay/expiredaction', get_string('expiredaction', 'enrol_razorpay'), get_string('expiredaction_help', 'enrol_razorpay'), ENROL_EXT_REMOVED_SUSPENDNOROLES, $options));

        //--- enrol instance defaults ----------------------------------------------------------------------------
        $settings->add(new admin_setting_heading('enrol_razorpay_defaults',
            get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));

        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        $settings->add(new admin_setting_configselect('enrol_razorpay/status',
            get_string('status', 'enrol_razorpay'), get_string('status_desc', 'enrol_razorpay'), ENROL_INSTANCE_DISABLED, $options));

        $settings->add(new admin_setting_configtext('enrol_razorpay/cost', get_string('cost', 'enrol_razorpay'), '', 0, PARAM_FLOAT, 4));

        $razorpaycurrencies = enrol_get_plugin('razorpay')->get_currencies();
        $settings->add(new admin_setting_configselect('enrol_razorpay/currency', get_string('currency', 'enrol_razorpay'), '', 'USD', $razorpaycurrencies));

        if (!during_initial_install()) {
            $options = get_default_enrol_roles(context_system::instance());
            $student = get_archetype_roles('student');
            $student = reset($student);
            $settings->add(new admin_setting_configselect('enrol_razorpay/roleid',
                get_string('defaultrole', 'enrol_razorpay'), get_string('defaultrole_desc', 'enrol_razorpay'), $student->id, $options));
        }

        $settings->add(new admin_setting_configduration('enrol_razorpay/enrolperiod',
            get_string('enrolperiod', 'enrol_razorpay'), get_string('enrolperiod_desc', 'enrol_razorpay'), 0));

        $settings->add(new admin_setting_configcheckbox('enrol_razorpay/unenrolredirect', get_string('unenrolredirect', 'enrol_razorpay'), '', 0));
    }
}
