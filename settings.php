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
 * @package local_hypercron
 * @author Andrew Normore<anormore@yorkvilleu.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2019 onwards Yorkville Education Company
 */

defined('MOODLE_INTERNAL') || die();

if (!$hassiteconfig || !is_siteadmin()){
    return;
}

$settings = new admin_settingpage('local_hypercron', get_string('pluginname', 'local_hypercron'));
$ADMIN->add('localplugins', $settings);

// ---------------------
$HypercronKey = get_config('local_hypercron', 'key');

//var_dump("//".$_SERVER['SERVER_NAME']); die();

$settings->add(
    new admin_setting_configcheckbox('local_hypercron/enabled',
        "Enable RTMS",
        "<hr />",
        0
    )
);


// RTMS Link
$settings->add( new admin_setting_configempty('local_hypercron/local_hypercron',
        "RTMS URL",
        "Test run:<hr /><a target='_blank' href='".$CFG->wwwroot."/local/hypercron/?key=".$HypercronKey."'>".$CFG->wwwroot."/local/hypercron/?KEY=".$HypercronKey."</a><hr /><a target='_blank' href='".$CFG->wwwroot."/local/hypercron/?debugMessages=true&key=".$HypercronKey."'>".$CFG->wwwroot."/local/hypercron/?KEY=".$HypercronKey."&debugMessages=true</a><hr />You must add a 4 new cron jobs, separate from the regular Moodle cron: <hr />*/1 * * * * ( sleep 0 ; wget -O - ".$CFG->wwwroot."/local/hypercron/?key=".$HypercronKey." > /dev/null 2>&1 )<hr />*/1 * * * * ( sleep 15 ; wget -O - ".$CFG->wwwroot."/local/hypercron/?key=".$HypercronKey." > /dev/null 2>&1 )<hr />*/1 * * * * ( sleep 30 ; wget -O - ".$CFG->wwwroot."/local/hypercron/?key=".$HypercronKey." > /dev/null 2>&1 )<hr />*/1 * * * * ( sleep 45 ; wget -O - ".$CFG->wwwroot."/local/hypercron/?key=".$HypercronKey." > /dev/null 2>&1 )<p><a target='_blank' href='".$CFG->wwwroot."/local/hypercron/logs.php'>View RTMS Logs</a></p>"
    )
);


// KEY?
$settings->add(
    new admin_setting_configtext(
        'local_hypercron/key',
        "URL Security Key",
        '',
        "",
        PARAM_TEXT
    )
);

// AMOUNT TO PROCESS
$settings->add(
    new admin_setting_configselect(
        'local_hypercron/amountToProcess',
        "Amount of Courses to Process",
        '<hr />',
        10,
        array(
            1 => '1 (LOW CPU, SLOWER RESULTS)',
            5 => '5',
            10 => '10',
            25 => '25',
            50 => '50 (MID CPU, FAST RESULTS)',
            100 => '100',
            200 => '200 (HIGH CPU, VERY FAST RESULTS)',

        )
    )
);

// PLUGINS
$settings->add(
	new admin_setting_configcheckbox('local_hypercron/plugin_ned_block_teacher_tools',
		"NED_block.php",
    	"",
    	0
    )
);

/*
$settings->add(
	new admin_setting_configcheckbox('local_hypercron/plugin_YU_overdueAssignmentsToZero',
		"YU_overdueAssignmentsToZero.php",
    	"",
    	0
    )
);
*/


