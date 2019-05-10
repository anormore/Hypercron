<?php

// RTMS LOGS
// ---------
?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.bundle.js"></script>
<?php

require_once("../../config.php");

// Security.
$context = context_system::instance();
require_login();
require_capability('moodle/site:config', $context);

// Page boilerplate stuff.
$url = new moodle_url('/local/hypercron/logs.php');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$title = "RTMS Logs";
$PAGE->set_title($title);
$PAGE->set_heading($title);




echo $OUTPUT->header();

?>


<canvas id="myChart3" width="400" height="100"></canvas>
<script>
var ctx3 = document.getElementById('myChart3');
var myChart3 = new Chart(ctx3, {
    type: 'line',
    data: {
        labels: [
            <?php 
                $logs = $DB->get_records_sql('SELECT * FROM {hypercron_logs} WHERE type = "batch" AND data="complete" ORDER BY time DESC LIMIT 100 ', array(1));
                foreach($logs as $log){
                  echo $log->runtime-$log->time.",";
                }
            ?>
        ],
        datasets: [{
            label: 'Batch Process duration in seconds (60 seconds = 1 minute)',
            data: [
                <?php 
                    $logs = $DB->get_records_sql('SELECT * FROM {hypercron_logs} WHERE type = "batch" AND data="complete" ORDER BY time DESC LIMIT 100 ', array(1));
                foreach($logs as $log){
                  echo ($log->runtime - $log->time).",";
                }
                ?>
            ],
            backgroundColor: [
                'rgba(0, 90, 0, 0.08)',
            ],
            borderColor: [
                'rgba(0, 90, 0, 0.5)',
            ],
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true
                }
            }]
        }
    }
});
</script>


<canvas id="myChart2" width="400" height="100"></canvas>
<script>
var ctx2 = document.getElementById('myChart2');
var myChart2 = new Chart(ctx2, {
    type: 'line',
    data: {
        labels: [
            <?php 
                $logs = $DB->get_records_sql('SELECT * FROM {hypercron_logs} WHERE type = "course" ORDER BY time DESC LIMIT 100 ', array(1));
                foreach($logs as $log){
                  echo "'".gmdate("h:i:s", $log->time)."',";
                }
            ?>
        ],
        datasets: [{
            label: 'Individual course process time in seconds (60 seconds = 1 minute)  CHANNEL 1',
            data: [
                <?php 
                    $logs = $DB->get_records_sql('SELECT * FROM {hypercron_logs} WHERE type = "time" AND quechannel=1 ORDER BY time DESC LIMIT 100 ', array(1));
                    foreach($logs as $log){
                      echo $log->runtime.",";
                    }
                ?>
            ],
            backgroundColor: [
                'rgba(255, 90, 255, 0)',
            ],
            borderColor: [
                'rgba(255, 90, 255, 0.5)',
            ],
            borderWidth: 1
        },{
            label: 'Individual course process time in seconds (60 seconds = 1 minute)  CHANNEL 2',
            data: [
                <?php 
                    $logs2 = $DB->get_records_sql('SELECT * FROM {hypercron_logs} WHERE type = "time" AND quechannel=2 ORDER BY time DESC LIMIT 100 ', array(1));
                    foreach($logs2 as $log){
                      echo $log->runtime.",";
                    }
                ?>
            ],
            backgroundColor: [
                'rgba(11, 22, 255, 0)',
            ],
            borderColor: [
                'rgba(11, 22, 255, 0.5)',
            ],
            borderWidth: 1
        },{
            label: 'Individual course process time in seconds (60 seconds = 1 minute)  CHANNEL 3',
            data: [
                <?php 
                    $logs3 = $DB->get_records_sql('SELECT * FROM {hypercron_logs} WHERE type = "time" AND quechannel=3 ORDER BY time DESC LIMIT 100 ', array(1));
                    foreach($logs3 as $log){
                      echo $log->runtime.",";
                    }
                ?>
            ],
            backgroundColor: [
                'rgba(255, 90, 33, 0)',
            ],
            borderColor: [
                'rgba(255, 90, 33, 0.5)',
            ],
            borderWidth: 1
        },{
            label: 'Individual course process time in seconds (60 seconds = 1 minute)  CHANNEL 4',
            data: [
                <?php 
                    $logs4 = $DB->get_records_sql('SELECT * FROM {hypercron_logs} WHERE type = "time" AND quechannel=4 ORDER BY time DESC LIMIT 100 ', array(1));
                    foreach($logs4 as $log){
                      echo $log->runtime.",";
                    }
                ?>
            ],
            backgroundColor: [
                'rgba(11, 90, 255, 0)',
            ],
            borderColor: [
                'rgba(11, 90, 255, 0.5)',
            ],
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true
                }
            }]
        }
    }
});
</script>



<?php




echo $OUTPUT->footer();