<?php
// This file is part of the LFB-BW plugin for Moodle - http://moodle.org/
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

require_once(dirname(__FILE__).'/../../config.php');

$site = get_site();
$pluginname = get_string('pluginname', 'local_exacomp_local');

$PAGE->set_pagelayout('admin');
$PAGE->set_context(context_system::instance());
$PAGE->set_title($site->shortname.': '.$pluginname);
$PAGE->set_heading($site->fullname);

$action = optional_param('action', 'ws', PARAM_TEXT);
require_login();
$PAGE->set_url(new moodle_url('/local/exacomp_local/index.php',array('action'=>$action)));

// Check permissions
if (!has_capability('local/exacomp_local:execute', context_system::instance())) {
	throw new moodle_exception('nopermissions', '', $PAGE->url->out(), get_string('exacomp_local:execute', 'local_exacomp_local'));
}

echo $OUTPUT->header();

if($action == 'ws') {
	echo get_string('status_header', 'local_exacomp_local');

	$brtag = html_writer::empty_tag('br');

    // echo $OUTPUT->heading(get_string('onesystemcontrolling', 'webservice'), 3, 'main');
	$table = new html_table();
	$table->head = array(get_string('step', 'webservice'), get_string('status'),
		get_string('description'));
	$table->colclasses = array('leftalign step', 'leftalign status', 'leftalign description');
	$table->id = 'onesystemcontrol';
	$table->attributes['class'] = 'admintable wsoverview generaltable';
	$table->data = array();

	/// 1. Enable Web Services
	$row = array();
	$url = new moodle_url("/admin/search.php?query=enablewebservices");
	$row[0] = "1. " . html_writer::tag('a', get_string('enablews', 'webservice'),
					array('href' => $url));
	$status = html_writer::tag('span', get_string('no'), array('class' => 'statuscritical'));
	if ($CFG->enablewebservices) {
		$status = html_writer::tag('span', get_string('ok'), array('class' => 'statusok'));
	}
	$row[1] = $status;
	$row[2] = get_string('enablewsdescription', 'webservice');
	$table->data[] = $row;

	/// 2. Enable protocols
	$row = array();
	$url = new moodle_url("/admin/settings.php?section=webserviceprotocols");
	$row[0] = "2. " . html_writer::tag('a', get_string('enableprotocols', 'webservice'),
					array('href' => $url));
	//retrieve activated protocol
	$active_protocols = empty($CFG->webserviceprotocols) ?
			array() : explode(',', $CFG->webserviceprotocols);
	$status = "";
	if (!empty($active_protocols)) {
		foreach ($active_protocols as $protocol) {
			$status .= $protocol . $brtag;
		}
	}
	if (!in_array('rest', $active_protocols)) {
		$status = html_writer::tag('span', 'REST Protocol not enabled', array('class' => 'statuscritical')).$brtag.$status;
	} else {
		$status = html_writer::tag('span', get_string('ok'), array('class' => 'statusok')).$brtag.$status;
	}

	$row[1] = $status;
	$row[2] = get_string('enableprotocolsdescription', 'webservice');
	$table->data[] = $row;

	/// 3. Enable Web Services for Mobile Devices
	$row = array();
	$url = new moodle_url("/admin/search.php?query=enablemobilewebservice");
	$row[0] = "3. " . html_writer::tag('a', get_string('enablemobilewebservice', 'admin'),
					array('href' => $url));
	if ($CFG->enablemobilewebservice) {
		$status = html_writer::tag('span', get_string('ok'), array('class' => 'statusok'));
	} else {
		$status = html_writer::tag('span', get_string('no'), array('class' => 'statuscritical'));
	}

	$row[1] = $status;
	$enablemobiledocurl = new moodle_url(get_docs_url('Enable_mobile_web_services'));
	$enablemobiledoclink = html_writer::link($enablemobiledocurl, new lang_string('documentation'));
	$default = is_https() ? 1 : 0;
	$row[2] = new lang_string('configenablemobilewebservice', 'admin', $enablemobiledoclink);
	$table->data[] = $row;

	/// 4. Webservice Roles
	$row = array();
	$url = new moodle_url("/admin/roles/manage.php");
	$row[0] = "4. " . html_writer::tag('a', 'Roles with webservice access',
					array('href' => $url));
	$wsroles = get_roles_with_capability('moodle/webservice:createtoken');
 	// get rolename in local language
	$wsroles = role_fix_names($wsroles, context_system::instance(), ROLENAME_ORIGINAL);
	if ($wsroles) {
		$status = html_writer::tag('span', get_string('ok'), array('class' => 'statusok'));
		foreach ($wsroles as $role) {
			$status .= $brtag.$role->localname;
		}
	} else {
		$status = html_writer::tag('span', 'Permissions not set', array('class' => 'statuscritical'));
	}

	$row[1] = $status;
	$row[2] = nl2br('Grant additional permission to the role "authenticated user" at: Site administration/Users/Permissions/Define roles
	4.1 Select Authenticated User
	4.2 Click on "Edit"
	4.3 Filter for createtoken
	4.4 Allow moodle/webservice:createtoken');
	$table->data[] = $row;


	/// 5. Checks
	$status = '';
	//set shortname for external service exacompservices
	$exacomp_service = $DB->get_record('external_services', array('name'=>'exacompservices'));
	if($exacomp_service){
		$exacomp_service->shortname = 'exacompservices';
		$DB->update_record('external_services', $exacomp_service);
	}else{
		$status .= html_writer::tag('span', 'Exacompservice not found', array('class' => 'statuscritical'));
	}
    $exaport_service = $DB->get_record('external_services', array('name'=>'exaportservices'));
	if($exaport_service){
		$exaport_service->shortname = 'exaportservices';
		$DB->update_record('external_services', $exaport_service);
	}else{
		$status .= html_writer::tag('span', 'Exaportservice not found', array('class' => 'statuscritical'));
	}

	if (empty($status)) {
		$status = html_writer::tag('span', get_string('ok'), array('class' => 'statusok'));
	}

	$row = array();
	$row[0] = "5. Webservice checks";
	$row[1] = $status;
	$row[2] = '';
	$table->data[] = $row;


	if (get_config('exacomp', 'external_trainer_assign')) {
		$count = $DB->count_records('block_exacompexternaltrainer');
		if ($count) {
			$status = html_writer::tag('span', get_string('ok'), array('class' => 'statusok'));
		} else {
			$status = html_writer::tag('span', 'No external trainers assigned', array('class' => 'statuscritical'));
		}
		// checks for elove app
		$row = array();
		$url = new moodle_url("/blocks/exacomp/externaltrainers.php?courseid=1");
		$row[0] = "6. " . html_writer::tag('a', get_string('block_exacomp_external_trainer_assign', 'block_exacomp'),
						array('href' => $url));

		$row[1] = $status;
		$row[2] = '';
		$table->data[] = $row;
	}

	echo html_writer::table($table);
} else if($action == 'comp') {
	global $CFG;
	
	require_once($CFG->dirroot . '/blocks/exacomp/lib/lib.php');
	if(block_exacomp_perform_auto_test()) {
		
	} else {
		echo get_string('something_went_wrong', 'local_exacomp_local');
	}
}

echo $OUTPUT->footer();

