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
 * The enrol plugin razorpay is defined here.
 *
 * @package     enrol_razorpay
 * @copyright   2021 Suraj Maurya surajmaurya450@gmail.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// The base class 'enrol_plugin' can be found at lib/enrollib.php. Override
// methods as necessary.

/**
 * Class enrol_razorpay_plugin.
 */
require 'vendor/autoload.php';
use Razorpay\Api\Api;
class enrol_razorpay_plugin extends enrol_plugin {

    /**
    * Get All the Currencies from RazorPay Plugin.
    * @return array() of currencies.
    */
    public function get_currencies() {
        $codes = array(
            'AUD', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'ILS', 'INR', 'JPY',
            'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RUB', 'SEK', 'SGD', 'THB', 'TRY', 'TWD', 'USD');
        $currencies = array();
        foreach ($codes as $c) {
            $currencies[$c] = new lang_string($c, 'core_currencies');
        }
        return $currencies;
    }

    /**
     * Does this plugin allow manual enrolments?
     *
     * All plugins allowing this must implement 'enrol/razorpay:enrol' capability.
     *
     * @param stdClass $instance Course enrol instance.
     * @return bool True means user with 'enrol/razorpay:enrol' may enrol others freely, false means nobody may add more enrolments manually.
     */
    public function allow_enrol($instance) {
        return true;
    }

    /**
     * Does this plugin allow manual unenrolment of all users?
     *
     * All plugins allowing this must implement 'enrol/razorpay:unenrol' capability.
     *
     * @param stdClass $instance Course enrol instance.
     * @return bool True means user with 'enrol/razorpay:unenrol' may unenrol others freely, false means nobody may touch user_enrolments.
     */
    public function allow_unenrol($instance) {
        return true;
    }

    /**
     * Does this plugin allow manual changes in user_enrolments table?
     *
     * All plugins allowing this must implement 'enrol/razorpay:manage' capability.
     *
     * @param stdClass $instance Course enrol instance.
     * @return bool True means it is possible to change enrol period and status in user_enrolments table.
     */
    public function allow_manage($instance) {
        return true;
    }

    /**
     * Does this plugin allow manual unenrolment of a specific user?
     *
     * All plugins allowing this must implement 'enrol/razorpay:unenrol' capability.
     *
     * This is useful especially for synchronisation plugins that
     * do suspend instead of full unenrolment.
     *
     * @param stdClass $instance Course enrol instance.
     * @param stdClass $ue Record from user_enrolments table, specifies user.
     * @return bool True means user with 'enrol/razorpay:unenrol' may unenrol this user, false means nobody may touch this user enrolment.
     */
    public function allow_unenrol_user($instance, $ue) {
        return true;
    }

    /**
     * Use the standard interface for adding/editing the form.
     *
     * @since Moodle 3.1.
     * @return bool.
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/paypal:config', $context);
    }

    /**
     * Adds form elements to add/edit instance form.
     *
     * @since Moodle 3.1.
     * @param object $instance Enrol instance or null if does not exist yet.
     * @param MoodleQuickForm $mform.
     * @param context $context.
     * @return void
     */
    /**
     * @since version 2.0
     * Major Version Upgrade -- subscription part.
     * Payment Instance type -- Full payment && daily|weekly|monthly|yearly subscription payment.
     * 
    */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        global $PAGE;
        
        // If Payment type is full then show full instance details.
        // Else Show data for subscriptions.
        // Billing Frequency. 
        // Plan Name, Plan Description, Billing Frequency, Billing Amount.

        $attributes = array();
        $paymentarrays = array();
        $paymentarrays[] = $mform->createElement('radio', 'fullpaymentsubscription', '', get_string('fullpayment', 'enrol_razorpay'), 'fullpayment', $attributes);
        $paymentarrays[] = $mform->createElement('radio', 'fullpaymentsubscription', '', get_string('subscription', 'enrol_razorpay'), 'subscription', $attributes);
        $mform->addGroup($paymentarrays, 'paymenttype', get_string('fullpaymentsubscription', 'enrol_razorpay') , array(' '), false);
        $mform->setDefault('fullpaymentsubscription', 'fullpayment');


        $mform->addElement('text', 'planname', get_string('planname', 'enrol_razorpay'));
        $mform->hideIf('planname', 'fullpaymentsubscription', 'neq' ,'subscription');
        $mform->setType('planname', PARAM_TEXT);

        $attributes=array('size'=>'20');
        $mform->addElement('textarea', 'plandescription', get_string('plandescription', 'enrol_razorpay'), $attributes);
        $mform->hideIf('plandescription', 'fullpaymentsubscription', 'neq' ,'subscription');

        $optionsperiod = array('daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly',  'yearly' => 'Yearly');
        $setselcted = 'daily';

        $availablefromgroup=array();
        $availablefromgroup[] =& $mform->createElement('text', 'interval', get_string('interval', 'enrol_razorpay'));
        $availablefromgroup[] =& $mform->createElement('select', 'period', get_string('period', 'enrol_razorpay'), $optionsperiod);
        $mform->setType('interval', PARAM_TEXT);
        $mform->addGroup($availablefromgroup, 'intervalperiod', get_string('intervalperiod', 'enrol_razorpay'), ' ', false);
        $mform->hideIf('intervalperiod', 'fullpaymentsubscription', 'neq' ,'subscription');
        $mform->addHelpButton('intervalperiod', 'intervalperiod', 'enrol_razorpay');

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        $mform->hideIf('name', 'fullpaymentsubscription', 'eq' ,'subscription');
        $mform->setType('name', PARAM_TEXT);

        $options = $this->get_status_options();
        $mform->addElement('select', 'status', get_string('status', 'enrol_razorpay'), $options);
        $mform->setDefault('status', $this->get_config('status'));

        $mform->addElement('text', 'cost', get_string('cost', 'enrol_razorpay'), array('size' => 4));
        $mform->setType('cost', PARAM_RAW);
        $mform->setDefault('cost', format_float($this->get_config('cost'), 2, true));
        $mform->hideIf('cost', 'fullpaymentsubscription', 'eq' ,'subscription');

        $mform->addElement('text', 'costsub', get_string('cost', 'enrol_razorpay'), array('size' => 4));
        $mform->setType('costsub', PARAM_RAW);
        $mform->setDefault('costsub', format_float($this->get_config('cost'), 2, true));
        $mform->addHelpButton('costsub', 'cost', 'enrol_razorpay');
        $mform->hideIf('costsub', 'fullpaymentsubscription', 'neq' ,'subscription');

        $razorcurrencies = $this->get_currencies();
        $mform->addElement('select', 'currency', get_string('currency', 'enrol_razorpay'), $razorcurrencies);
        $mform->setDefault('currency', $this->get_config('currency'));

        $mform->addElement('text', 'subscriptiontilldate', get_string('subscriptiontilldate', 'enrol_razorpay'));
        $mform->hideIf('subscriptiontilldate', 'fullpaymentsubscription', 'neq' ,'subscription');
        $mform->addHelpButton('subscriptiontilldate', 'subscriptiontilldate', 'enrol_razorpay');
        $mform->setType('subscriptiontilldate', PARAM_TEXT);

        $roles = $this->get_roleid_options($instance, $context);
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_razorpay'), $roles);
        $mform->setDefault('roleid', $this->get_config('roleid'));

        $options = array('optional' => true, 'defaultunit' => 86400);
        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_razorpay'), $options);
        $mform->setDefault('enrolperiod', $this->get_config('enrolperiod'));
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_razorpay');
        $mform->hideIf('enrolperiod', 'fullpaymentsubscription', 'eq' ,'subscription');

        $options = array('optional' => true);
        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_razorpay'), $options);
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_razorpay');

        $options = array('optional' => true);
        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_razorpay'), $options);
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_razorpay');

        if (enrol_accessing_via_instance($instance)) {
            $warningtext = get_string('instanceeditselfwarningtext', 'core_enrol');
            $mform->addElement('static', 'selfwarn', get_string('instanceeditselfwarning', 'core_enrol'), $warningtext);
        }

    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @since Moodle 3.1.
     * @param array $data Array of ("fieldname"=>value) of submitted data.
     * @param array $files Array of uploaded files "element_name"=>tmp_file_path.
     * @param object $instance The instance data loaded from the DB.
     * @param context $context The context of the instance we are editing.
     * @return array Array of "element_name"=>"error_description" if there are errors, empty otherwise.
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        
        $errors = array();

        if (!empty($data['enrolenddate']) and $data['enrolenddate'] < $data['enrolstartdate']) {
            $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_razorpay');
        }

        $cost = str_replace(get_string('decsep', 'langconfig'), '.', $data['cost']);
        if (!is_numeric($cost)) {
            $errors['cost'] = get_string('costerror', 'enrol_razorpay');
        }

        $validstatus = array_keys($this->get_status_options());
        $validcurrency = array_keys($this->get_currencies());
        $validroles = array_keys($this->get_roleid_options($instance, $context));
        $tovalidate = array(
            'name' => PARAM_TEXT,
            'status' => $validstatus,
            'currency' => $validcurrency,
            'roleid' => $validroles,
            'enrolperiod' => PARAM_INT,
            'enrolstartdate' => PARAM_INT,
            'enrolenddate' => PARAM_INT
        );

        $typeerrors = $this->validate_param_types($data, $tovalidate);
        $errors = array_merge($errors, $typeerrors);

        return $errors;
    }

    /**
     * Return whether or not, given the current state, it is possible to add a new instance
     * of this enrolment plugin to the course.
     *
     * @param int $courseid.
     * @return bool.
     */
    /**
     * @since version 2.0
     * Major Version Upgrade -- subscription part.
     * Payment Instance type -- Full payment && daily|weekly|monthly|yearly subscription payment.
     * 
    */
    public function can_add_instance($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);
        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/razorpay:config', $context)) {
            return false;
        }
        return true;
    }

    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    protected function get_status_options() {
        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        return $options;
    }
    /**
     * Return an array of valid options for the roleid.
     *
     * @param stdClass $instance
     * @param context $context
     * @return array
     */
    protected function get_roleid_options($instance, $context) {
        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $this->get_config('roleid'));
        }
        return $roles;
    }

    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param array $fields instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = null) {

        $instanceid = null;

        if (isset($fields['costsub']) && $fields['costsub'] != "" ) {
            $fields['cost'] = $fields['costsub'];
        }
        if(isset($fields['fullpaymentsubscription']) && $fields['fullpaymentsubscription'] == 'subscription' ){
             $plan = $this->add_RazorPay_Plan($course, $fields);
             $fields['name'] = $fields['planname'];
             unset($fields['planname']);
        }


        if(isset($fields['fullpaymentsubscription'])){
            $fields['customchar2'] = $fields['fullpaymentsubscription'];
            unset($fields['fullpaymentsubscription']); 
        }


        if ($fields && !empty($fields['cost'])) {
            $fields['cost'] = unformat_float($fields['cost']);
        }


        if(isset($fields['customchar2']) && $fields['customchar2'] == 'subscription' ){
            
            if (isset($plan->id) && $plan->id != "" ) {
                $instanceid = parent::add_instance($course, $fields);
            }

            $planname        = isset($fields['name']) ? $fields['name'] : '';
            $plandescription = isset($fields['plandescription']) ? $fields['plandescription'] : '';  
            $interval        = isset($fields['interval']) ? ( (int) $fields['interval'] ) : 0;    
            $period          = isset($fields['period']) ? $fields['period'] : '';  
            $cost            = isset($fields['cost']) ? ($fields['cost']) : '';  
            $currency        = isset($fields['currency']) ? $fields['currency'] : '';  
            $period          = isset($fields['period']) ? $fields['period'] : '';
            $courses         = serialize( array('id' => $course->id, 'name' => $course->fullname ));
            $subcysle        = isset($fields['subscriptiontilldate']) ? $fields['subscriptiontilldate'] : 0;

            
            if (isset($instanceid) && !is_null($instanceid) ) {
                $this->savePlantoTable( $course->id, $instanceid, $planname, $cost, $currency, $plandescription, $plan->id, $plan, $subcysle);    
            }

        }else{
            $instanceid = parent::add_instance($course, $fields);
        }

        return $instanceid;
    }

    public function razorPayCheckPaymentType($instance){

    }

    private function add_RazorPay_Plan($course, array $fields = null){

        $planname        = isset($fields['planname']) ? $fields['planname'] : '';
        $plandescription = isset($fields['plandescription']) ? $fields['plandescription'] : '';  
        $interval        = isset($fields['interval']) ? ( (int) $fields['interval'] ) : 0;    
        $period          = isset($fields['period']) ? $fields['period'] : '';  
        $cost            = isset($fields['cost']) ? ($fields['cost']) : '';  
        $currency        = isset($fields['currency']) ? $fields['currency'] : '';  
        $period          = isset($fields['period']) ? $fields['period'] : '';
        $courses         = serialize(array('id' => $course->id, 'name' => $course->fullname ));

        if (!$planname && !$plandescription && !$interval && !$period && !$cost && !$currency && !$period && !$course) {
            return false;
        }      

        $api             = $this->createApi();
        $plans           = new stdClass;
        $plans->period   = $period;
        $plans->interval = $interval;
        
        $plans->item     = (object) array(
                                'name'          => $planname, 
                                'amount'        => intval((string)($fields['cost']).'00'), 
                                'currency'      => $currency, 
                                "description"   => $plandescription
                            );

        $plans->notes    = (object) array(
                                'coursedetails' =>  $courses,
                            );

            return $razorpayOrder = $api->plan->create((array)$plans);
    }

    private function savePlantoTable( $courseid, $instanceid, $itemname,$itemamount,$itemcurrency,$itemdescription,$planid,$apidata, $subcysle){
        global $DB, $USER;
        try {

            $table                        = 'enrol_razorpay_plans';
            $dataobject                   = new stdClass;
            $dataobject->courseid         = $courseid;
            $dataobject->createdby        = $USER->id;
            $dataobject->instanceid       = $instanceid;
            $dataobject->item_name        = $itemname;
            $dataobject->item_amount      = $itemamount;
            $dataobject->item_currency    = $itemcurrency;
            $dataobject->item_description = $itemdescription;
            $dataobject->plan_id          = $planid;
            $dataobject->api_data         = serialize($apidata);
            $dataobject->subscriptioncycle= $subcysle;
            $dataobject->createdat        = time();
            $insertplan                   = $DB->insert_record($table, $dataobject, true, false);
            return $insertplan;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getDBPlanById($planid){
        global $DB;
        $table = 'enrol_razorpay_plans';
        if ($DB->record_exists($table, array('plan_id' => $planid ))) {
            return $DB->get_record($table, array('plan_id' => $planid ));
        }
        return false;
    }

    private function getDBPlanByInstanceid($instanceid){
        global $DB;
        $table = 'enrol_razorpay_plans';
        if ($DB->record_exists($table, array('instanceid' => $instanceid ))) {
            return $DB->get_record($table, array('instanceid' => $instanceid ));
        }
        return false;
    }
    
    private function getDBSubscriptionByInstanceidandUser($instanceid){
        global $DB , $USER;
        $table = 'enrol_razorpay_subscription';
        if ($DB->record_exists($table, array('instanceid' => $instanceid, 'userid' => $USER->id ))) {
            return $DB->get_record($table, array('instanceid' => $instanceid, 'userid' => $USER->id ));
        }
        return false;
    }

    public function getDBSubscriptionBysubid($instanceid){
        global $DB , $USER;
        $table = 'enrol_razorpay_subscription';
        if ($DB->record_exists($table, array('subscription_id' => $instanceid ))) {
            return $DB->get_record($table, array('subscription_id' => $instanceid ));
        }
        return false;
    }

    private function saveSubscriptionTotable($planid, $subscriptionid, $subscriptioncycle, $startat,$apidata){
        global $DB, $USER;
        try {

            $this->getPlanById($planid);
            $table = 'enrol_razorpay_subscription';

            if (is_object($this->getDBPlanById($planid))) {
                
                $plan              = $this->getDBPlanById($planid);
                $courseid          = $plan->courseid; 
                $instanceid        = $plan->instanceid;
                $item_name         = $plan->item_name;
                $item_amount       = $plan->item_amount;
                $item_currency     = $plan->item_currency;
                $item_description  = $plan->item_description;
                $subscriptioncycle = $plan->subscriptioncycle;

            }
            
            $dataobject                         = new stdClass;
            $dataobject->courseid               = $courseid;  
            $dataobject->userid                 = $USER->id;
            $dataobject->instanceid             = $instanceid;
            $dataobject->item_name              = $item_name;  
            $dataobject->item_amount            = $item_amount;  
            $dataobject->item_currency          = $item_currency;  
            $dataobject->item_description       = $item_description;
            $dataobject->plan_id                = $planid;  
            $dataobject->subscription_id        = $subscriptionid;
            $dataobject->subscription_cycle     = $subscriptioncycle;
            $dataobject->subscription_start_at  = $startat;
            $dataobject->plan_status            = 1; 
            $dataobject->api_data               = serialize($apidata);
            $dataobject->createdat              = time();     
            $subscrplan                         = $DB->insert_record($table, $dataobject, true, false);
            return $subscrplan;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function getAllPlans(){
        $api   = $this->createApi();
        $plans = $api->plan->all();
        return $plans;
    }

    public function getPlanById($plan_id = null){
        if (is_null($plan_id)) {
            return null;
        }
        $api = $this->createApi();
        $plan = $api->plan->fetch($plan_id);
        return $plan;
    }

    public function createSubscription($planid, $customernotify = 1 , $total_count = 1, $startat = null ){

        $api = $this->createApi();
        $subscription  = $api->subscription->create(
            array(  
                'plan_id' => $planid,  
                'customer_notify' => $customernotify,  
                'total_count' => $total_count,  
                'start_at' => strtotime('+5 hours', time())  
            )
        );

        return $subscription;
    }


    private function getAllSubscription () {
        $api = $this->createApi();
        return $subscriptions = $api->subscription->all();
    }

    public function getSubscriptionById($subscription_id = null) {
        if (is_null($subscription_id)) {
            return null;
        }
        $api = $this->createApi();
        $subscription = $api->subscription->fetch($subscription_id);
        return $subscription;
    }

    private function cancelSubscription($subscription_id = null) {
        $api = $this->createApi();
        $options = ['cancel_at_cycle_end' => 1];
        $subscription = $api->subscription->fetch($subscription_id)->cancel($options);
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    function enrol_page_hook(stdClass $instance) {
        global $CFG, $USER, $OUTPUT, $PAGE, $DB;

        ob_start();

        if ($DB->record_exists('user_enrolments', array('userid'=>$USER->id, 'enrolid'=>$instance->id))) {
            return ob_get_clean();
        }

        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            return ob_get_clean();
        }

        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            return ob_get_clean();
        }

        $course = $DB->get_record('course', array('id'=>$instance->courseid));
        $context = context_course::instance($course->id);

        $shortname = format_string($course->shortname, true, array('context' => $context));
        $strloginto = get_string("loginto", "", $shortname);
        $strcourses = get_string("courses");

        // Pass $view=true to filter hidden caps if the user cannot see them
        if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                             '', '', '', '', false, true)) {
            $users = sort_by_roleassignment_authority($users, $context);
            $teacher = array_shift($users);
        } else {
            $teacher = false;
        }

        if ( (float) $instance->cost <= 0 ) {
            $cost = (float) $this->get_config('cost');
        } else {
            $cost = (float) $instance->cost;
        }

        if (abs($cost) < 0.01) { // no cost, other enrolment methods (instances) should be used
            echo '<p>'.get_string('nocost', 'enrol_razorpay').'</p>';
        } else {

            // Calculate localised and "." cost, make sure we send Razorpay the same value,
            // please note Razorpay expects amount with 2 decimal places and "." separator.
            $localisedcost = format_float($cost, 2, true);
            $cost = format_float($cost, 2, false);

            if (isguestuser()) { // force login only for guest user, not real users with guest role
                $wwwroot = $CFG->wwwroot;
                echo '<div class="mdl-align"><p>'.get_string('paymentrequired').'</p>';
                echo '<p><b>'.get_string('cost').": $instance->currency $localisedcost".'</b></p>';
                echo '<p><a href="'.$wwwroot.'/login/">'.get_string('loginsite').'</a></p>';
                echo '</div>';
            } else {

                // Check here for subscriptions model.

                $subscription = $this->getDBSubscriptionByInstanceidandUser($instance->id);

                $subscriptioninst = null; 

                if ($subscription) {
                    // Subcription from db.

                    if (is_null($subscriptioninst)) {
                        $subscriptioninst = $subscription;
                    }

                }else{

                    if (isset($instance->customchar2) && $instance->customchar2 == 'subscription' ) {
                        // get the instance Details.
                        $plan = $this->getDBPlanByInstanceid($instance->id);
                        $subscription = $this->createSubscription($plan->plan_id, 1 , $plan->subscriptioncycle);
                        if (isset($subscription) && is_object($subscription) && $subscription->id != "") {
                            $this->saveSubscriptionTotable($plan->plan_id, $subscription->id , $subscription->total_count,$subscription->start_at,$subscription);
                        }
                        $subscriptioninst = $this->getDBSubscriptionByInstanceidandUser($instance->id);
                    }else{
                            $verficationurl  = $this->verifyRazorPayRequest();
                            $razorpayOrderId = $this->createRazorPayOrder($instance);
                    }
                }

                //Sanitise some fields before building the Razorpay form
                $coursefullname  = format_string($course->fullname, true, array('context'=>$context));
                $courseshortname = $shortname;
                $userfullname    = fullname($USER);
                $userfirstname   = $USER->firstname;
                $userlastname    = $USER->lastname;
                $useraddress     = $USER->address;
                $usercity        = $USER->city;
                $instancename    = $this->get_instance_name($instance);
                $razorpayicon    = $this->razorpaylogo();
                $razorscripturl  = $this->razorpayScriptUrl();


                if (isset($instance->customchar2) && $instance->customchar2 == 'subscription' ) {

                    // $contactnumber = "+918375980653";
                    $data = new stdClass;
                    $data->key = $this->get_config('apikey');
                    $data->subscription_id = $subscriptioninst->subscription_id;
                    $data->name = $subscriptioninst->item_name;
                    $data->description = $subscriptioninst->item_description;
                    $data->callback_url = $this->callbackSusbcription();
                    $data->prefill = (object) array('name' => $userfullname, 'email' => $USER->email );
                    $data->notes = (object) array( 'courseid' => $course->id, 'instanceid' => $instance->id  );
                    $data->theme = (object) array('color' => "#F37254" );
                    
                }else{
                    $data = [
                        "key"               => $this->get_config('apikey'),
                        "amount"            => $instance->cost,
                        "name"              => $coursefullname,
                        "description"       => $coursefullname,
                        "image"             => '',
                        "prefill"           => [
                        "name"              => $userfullname,
                        "email"             => $USER->email,
                        ],
                        "notes"             => [
                        "instanceid"        => $instance->id,
                        "userid"            => $USER->id,
                        "courseid"          => $course->id,
                        ],
                        "theme"             => [
                        "color"             => "#F37254"
                        ],
                        "display_currency"  => $instance->currency,
                        "display_amount"    => $instance->cost,
                    ];

                    if (isset($razorpayOrderId) && $razorpayOrderId) {
                        $data["order_id"]  = $razorpayOrderId;
                    }

                }


                if (isset($razorpayOrderId) && $razorpayOrderId) {
                    $json = json_encode($data);
                    include($CFG->dirroot.'/enrol/razorpay/enrol.html');
                }elseif ($subscription) {
                    $json = json_encode($data);
                    include($CFG->dirroot.'/enrol/razorpay/subscription.html');
                } else{
                    throw new Exception("Error Processing Request");
                }
            }
        }
        return $OUTPUT->box(ob_get_clean());
    }
    /**
     * Returns optional enrolment information icons.
     *
     * This is used in course list for quick overview of enrolment options.
     *
     * We are not using single instance parameter because sometimes
     * we might want to prevent icon repetition when multiple instances
     * of one type exist. One instance may also produce several icons.
     *
     * @param array $instances all enrol instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances) {
        $found = false;
        foreach ($instances as $instance) {
            if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
                continue;
            }
            if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
                continue;
            }
            $found = true;
            break;
        }
        if ($found) {
            return array(new pix_icon('icon', get_string('pluginname', 'enrol_razorpay'), 'enrol_razorpay'));
        }
        return array();
    }

    /**
    * @method 
    * @return razor pay logo
    */
    private function razorpaylogo(){
        global $OUTPUT;
        return $OUTPUT->image_url('icon', 'enrol_razorpay'); 
    }

    private function razorpayScriptUrl(){
        return 'https://checkout.razorpay.com/v1/checkout.js';
    }

    /**
    * @method used to create Api based on the Razor Pay Key and Secrect.
    * @return object of RazorPay Api.
    * @param apikey, apiSecret.
    */
    public function createApi(){
        $keyId = $this->get_config('apikey');
        $keySecret = $this->get_config('apisecret'); 
        return new Api($keyId, $keySecret);
    }

    public function getsecret(){
        return $this->get_config('apisecret'); 
    }

    /**
     * @method used to start the order based on the key.
     * @return order object.
     * @param key, product and users data.
     * */
    public function createRazorPayOrder($instance){
        $api = $this->createApi();

        $orderData = [
            'receipt'         => $instance->id,
            'amount'          => $instance->cost,
            'currency'        => $instance->currency,
        ];
        
        $razorpayOrder = $api->order->create($orderData);
        
        // Save It To DataBase.

        $razorpayOrderId = $razorpayOrder['id'];
        $_SESSION['razorpay_order_id'] = $razorpayOrderId;
        return $razorpayOrderId;
    }

    /**
     * @method verifyRazorPayRequest is used to return path of success and failure page url.
     * @return URL
     * @param null
     * */
    public function verifyRazorPayRequest(){
        global $CFG;
        return $CFG->wwwroot.'/enrol/razorpay/verify.php';
    }

    private function callbackSusbcription(){
        global $CFG;
        return $CFG->wwwroot.'/enrol/razorpay/subscription-verify.php';
    }

}

function enrol_razorpay_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('moodle/course:enrolconfig', $context) && has_capability('enrol/razorpay:config', $context)) {
        $url = new moodle_url('/enrol/razorpay/report.php', array('id'=>$course->id));
        $navigation->add(get_string('purchasereport', 'enrol_razorpay'), $url, navigation_node::NODETYPE_LEAF, null, null, new pix_icon('i/report', ''));
    }
}

?>