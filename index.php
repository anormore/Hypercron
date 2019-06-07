<?php

// Load RTMS
// ---------

require_once("../../config.php");

//  Install as a Cron Job and run this as fast as possible
//  There are internal braking methods to prevent a CPU overload
//  To acheive "real time" performance, we try to run this file as fast as possible, at all times, forever!
//
//  # Moodle Real Time Scanner, must run every minute
//  */1 * * * * wget -O - https://yourwebsite.com/local/RealTimeMonitoringService/?key=YOUR_WEB_KEY > /dev/null 2>&1

$HypercronEnabled = get_config('local_hypercron', 'enabled');
if($HypercronEnabled == 0){

	?>

	<h1>RTMS is not enabled</h1>

	<?php
	die();
}

$HypercronKey = get_config('local_hypercron', 'key');
$HypercronAmountToProcess = get_config('local_hypercron', 'amountToProcess');

$HypercronPlugin_plugin_NED_block = get_config('local_hypercron', 'plugin_ned_block_teacher_tools');
$HypercronPlugin_plugin_YU_overdueAssignmentsToZero = get_config('local_hypercron', 'plugin_YU_overdueAssignmentsToZero');

echo "hypercron processing: ".$HypercronAmountToProcess ;


if($_GET['key'] != $HypercronKey){
	echo "<h1>invalid key</h1>";
	die();
}

function debugMessage($type, $message){

	if( isset($_GET['debugMessages']) ){

		if($_GET['debugMessages'] == "true"){

			if( is_string($message) ){
				echo "<$type>$message</$type>";
			}

			if( is_object($message) ){
				echo "<pre>";
				var_dump($message);
				echo "</pre>";
			}


		}

	}

	return false;

}








$startTime = microtime(true);


// Start Moodle & Libraries
// ------------------------

//require_once $CFG->libdir.'/gradelib.php';
//require_once $CFG->dirroot.'/grade/lib.php';
//require_once $CFG->dirroot.'/grade/report/lib.php';

//require_once $CFG->dirroot.'/mod/assign/externallib.php'; // New attempt to save external grades -- ONLY works for assignments darnit...
//$externalGrade = new mod_assign_external;

//require_once $CFG->dirroot.'/mod/quiz/lib.php'; // Maybe we can pull directly from this lib?

//global $USER, $DB;


// RTMS Que Check & Build
// ----------------------

$refillQue = "empty";
$ques = $DB->get_records_sql('SELECT id FROM mdl_hypercron_courseque LIMIT 1', array(1));
foreach($ques as $que){
	$refillQue = "full";
}
debugMessage ("h1", "Overdue que status: ".$refillQue);

if($refillQue == "empty"){

	debugMessage ("h2", "Refilling Que with ALL Courses!");

	$channelCount = 1;

	$courses = $DB->get_records_sql('SELECT id FROM mdl_course', array(1));
	foreach($courses as $course){

		$theCourse = new stdClass();
		$theCourse->quechannel = $channelCount;
		$theCourse->courseid = $course->id;
		$theCourse->timeadded = time();

		if($theCourse->courseid != 1){ // 1 is the homepage, skip this
			$lastinsertid = $DB->insert_record('hypercron_courseque', $theCourse, false);
		}

		$channelCount++;
		if($channelCount >= 5){
			$channelCount = 1;
		}

	}

	$batches = $DB->get_records_sql('SELECT id FROM {hypercron_logs} WHERE data="new"', array(1));
	foreach($batches as $batch){
		$DB->execute("UPDATE {hypercron_logs} SET runtime=".time().", data='complete' WHERE data='new'");
	}

	$DB->execute("INSERT INTO {hypercron_logs} (time, runtime, amount, type, data) VALUES (".time().", 0, '".count($courses)."', 'batch', 'new')");

}else{
	debugMessage("h2", "Que has data, process!");
}


// STEP DOWN HIGH CHANNEL COURSES

// Channel 1
$channel1Found = false;
$scans1 = $DB->get_records_sql('SELECT id FROM mdl_hypercron_courseque WHERE quechannel = 1 ', array(1));
foreach($scans1 as $scan){
	$channel1Found = true;
}

// Channel 2
$channel2Found = false;
$scans2 = $DB->get_records_sql('SELECT id FROM mdl_hypercron_courseque WHERE quechannel = 2 ', array(1));
foreach($scans2 as $scan){
	$channel2Found = true;
}

// Channel 3
$channel3Found = false;
$scans3 = $DB->get_records_sql('SELECT id FROM mdl_hypercron_courseque WHERE quechannel = 3 ', array(1));
foreach($scans3 as $scan){
	$channel3Found = true;
}

// Channel 4
$channel4Found = false;
$scans4 = $DB->get_records_sql('SELECT id FROM mdl_hypercron_courseque WHERE quechannel = 4 ', array(1));
foreach($scans4 as $scan){
	$channel4Found = true;
}


debugMessage("h2", "Channel 1: ".$channel1Found);
debugMessage("h2", "Channel 2: ".$channel2Found);
debugMessage("h2", "Channel 3: ".$channel3Found);
debugMessage("h2", "Channel 4: ".$channel4Found);


if($channel4Found && !$channel3Found){

	$newQueChannel = 3;
	foreach($scans4 as $scan){
		$DB->execute("UPDATE mdl_hypercron_courseque SET quechannel=".$newQueChannel." WHERE id=".$scan->id);
		$newQueChannel --;
		if($newQueChannel == 0){
			$newQueChannel = 3;
		}
	}

}
if($channel3Found && !$channel2Found){
	
	$newQueChannel = 2;
	foreach($scans3 as $scan){
		$DB->execute("UPDATE mdl_hypercron_courseque SET quechannel=".$newQueChannel." WHERE id=".$scan->id);
		$newQueChannel --;
		if($newQueChannel == 0){
			$newQueChannel = 2;
		}
	}

}
if($channel2Found && !$channel1Found){
	
	$newQueChannel = 1;
	foreach($scans2 as $scan){
		$DB->execute("UPDATE mdl_hypercron_courseque SET quechannel=".$newQueChannel." WHERE id=".$scan->id);
		$newQueChannel --;
		if($newQueChannel == 0){
			$newQueChannel = 1;
		}
	}

}


// DEFINE LOCKS



// CHECK FOR LOCKS, CLEAR OR DIE
// -----------------------------

for($selectedQueChannel = 1; $selectedQueChannel <= 4; $selectedQueChannel++){

	debugMessage ("h1", "<div style='color:darkgreen;'>SCANNING CHANNEL: ".$selectedQueChannel."</div>");

	$processThisQue = true;
	
	// If there is a match detected, 1/2/3/4 it will die, the previous job hasn't finished.
	$queLocks = $DB->get_records_sql('SELECT * FROM mdl_hypercron_coursequelocks WHERE quechannel = '.$selectedQueChannel.'', array(1));
	foreach($queLocks as $queLock){

		debugMessage ("h1", "Previous Que Still Running, EXIT -> Channel: ".$selectedQueChannel);


		debugMessage ("p", var_dump($queLock) );
		debugMessage("h2", "Lock Duration: ". (time() - $queLock->time));

		if((time() - $queLock->time) >= 10){ //Five Minutes = 300s
			debugMessage("h2", "Que has been running for some time and is perhaps stuck. Removing lock");
			$DB->execute('DELETE FROM mdl_hypercron_coursequelocks WHERE id='.$queLock->id);
		}

		$processThisQue = false;

		debugMessage ("h1", "Next Channel: ".$selectedQueChannel);
		break;

	}


	if($processThisQue){
		// CREATE NEW LOCK
		// ---------------
		$theLock = new stdClass();
		$theLock->quechannel = $selectedQueChannel;
		$theLock->status = "running";
		$theLock->time = time();
		$theLockId = $DB->insert_record('hypercron_coursequelocks', $theLock, true);
		debugMessage ("p","LOCKING CHANNEL: ".$selectedQueChannel);
		debugMessage ("p",$theLockId);





		// BEGIN COURSE SCANNING
		$quedCourses = $DB->get_records_sql('SELECT * FROM mdl_hypercron_courseque WHERE quechannel = '.$selectedQueChannel.' LIMIT '.$HypercronAmountToProcess, array(1));
		debugMessage ("p","Processing Channel: " .$selectedQueChannel );
		debugMessage ("p", count($quedCourses) );
		debugMessage ("p","===========================");





		foreach($quedCourses as $theCourse){

			debugMessage ("p","===========================");
			debugMessage ("p","=== COURSE ID: ".$theCourse->courseid." ============");
			debugMessage ("p","===========================");

			$courses = $DB->get_records_sql('SELECT id FROM {course} WHERE id='.$theCourse->courseid, array(1));
			if($courses){
				debugMessage ("p","COURSE FOUND, YOU MAY PROCEED");
			}else{
				debugMessage ("p","NO COURSE WAS FOUND -- THE COURSE WAS LIKELY DELETED BETWEEN REAL TIME CYCLE -- REMOVING FROM QUE");
				$DB->execute('DELETE FROM mdl_hypercron_courseque WHERE courseid='.$theCourse->courseid);
			}


			$startTimeCourseProcess = microtime(true);
			foreach($courses as $course){

				// no longer using the /plugins/enabled structure, let us run from a database
				/*
				foreach (glob("plugins/enabled/*.php") as $filename){
				    include $filename;
				}
				*/

				if($HypercronPlugin_plugin_NED_block == "1"){
					debugMessage ("p","RUNNING NED BLOCK");
					require($CFG->dirroot."/blocks/ned_teacher_tools/hypercron/refresh_all_data.php");
				}
				if($HypercronPlugin_plugin_YU_overdueAssignmentsToZero == "1"){
					debugMessage ("p","YU Overdue Assignments");
					require("plugins/YU_overdueAssignmentsToZero.php");
				}

				

				debugMessage ("p","clearing qued course");
				$DB->execute('DELETE FROM {hypercron_courseque} WHERE courseid='.$course->id);

				$runtimeCourse = "".round( (microtime(true) - $startTimeCourseProcess), 5);
				$DB->execute("INSERT INTO {hypercron_logs} (time, runtime, amount, type, data) VALUES (".time().", $runtimeCourse, '1', 'course', $course->id)");

			}

		}


		debugMessage ("p","clearing qued course:".$theLockId);
		$DB->execute('DELETE FROM {hypercron_coursequelocks} WHERE id='.$theLockId);

		debugMessage ("p", '<hr />');
		debugMessage ("p", '<h1 style="color:green;">COMPLETED '.$HypercronAmountToProcess.' JOBS SUCCESSFULLY: '.(microtime(true) - $startTime).' seconds</h1>');

		$runtime = "".round( (microtime(true) - $startTime), 5);

		echo "completed $HypercronAmountToProcess jobs in: ".$runtime." seconds";


		// ADD LOG
		// ---------------
		$hypercronLog = new stdClass();
		$hypercronLog->time = time();
		$hypercronLog->runtime = $runtime;
		$hypercronLog->amount = $HypercronAmountToProcess;

		var_dump($hypercronLog);
		//$lastinsertid = $DB->insert_record('hypercron_logs', $hypercronLog, false);
		$DB->execute("INSERT INTO {hypercron_logs} (time, runtime, amount, type, data, quechannel) VALUES ($hypercronLog->time, $hypercronLog->runtime, $hypercronLog->amount, 'time', '', $selectedQueChannel)");


		// DIE
		// DIE
		die();
		// DIE
		// DIE

		// We do not want to re-run any logic here, other Hypercron jobs should be running already

	}

}




?>
