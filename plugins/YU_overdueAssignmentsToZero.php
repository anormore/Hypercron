<?php

$triggerRegrade = false;

debugMessage ("p", 'Processing courseId:'.$course->id);
debugMessage ("p", '<hr />');

$moodleContext = get_context_instance(CONTEXT_COURSE, $course->id);
debugMessage ("p","Moodle context:");
debugMessage ("p",$moodleContext);

debugMessage ("p","building students list");
debugMessage ("p",'SELECT userid,contextid FROM mdl_role_assignments WHERE contextid = '.$moodleContext->id);
$students = [];
$studentEnrolments = $DB->get_records_sql('SELECT userid,contextid FROM mdl_role_assignments WHERE contextid = '.$moodleContext->id .'', array(1));
foreach($studentEnrolments as $studentEnrolment){
    //debugMessage ("p", var_dump($studentEnrolment) );
    array_push($students, $studentEnrolment->userid);
}
//debugMessage ("p", var_dump($students) );

$gradeItems = $DB->get_records_sql('SELECT id,courseid,itemmodule,iteminstance FROM mdl_grade_items WHERE courseid = '.$course->id, array(1));
foreach($gradeItems as $gradeItem){

    if($gradeItem->itemmodule=="assign"){

        $assignments = $DB->get_records_sql('SELECT * FROM mdl_assign WHERE id = '.$gradeItem->iteminstance, array(1));
        foreach($assignments as $assignment){

            echo "<h3>Assignment:</h3>";
            var_dump($assignment);

            var_dump("CHECK DUEDATE: ". $assignment->duedate);

            if( (int)$assignment->duedate > 0 && time() - (int)$assignment->duedate >= 0 ){
                var_dump("OVERDUE ASSIGNMENT DETECTED");
            }else{
                var_dump("Not overdue yet");
            }

            echo "<h4>Checking Student Assignemnt:</h4>";
            foreach($students as $student){
                $studentAssignmentFound = false;

                $grades = $DB->get_records_sql('SELECT * FROM mdl_grade_grades WHERE (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade >= 0) OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade = "0.0000") OR (itemid='.$gradeItem->id.' AND userid='.$student . ' AND finalgrade IS NULL) ', array(1));
                foreach($grades as $grade){

                    echo "<h4>ASSIGNMENT FOUND</h4>";
                    $studentAssignmentFound = true;

                    // Additional check is required for some reason, a grade can be inserted as null?
                    if(is_null($grade->finalgrade)){
                        var_dump("WAS NULL");
                        $DB->execute('DELETE FROM mdl_grade_grades WHERE id='.$grade->id);
                        $studentAssignmentFound = false;
                    }
                }

                if($studentAssignmentFound == false){

                    // Make sure it's actually past due date
                    if( (int)$assignment->duedate > 0 && time() - (int)$assignment->duedate >= 0 ){

                        echo "<h4>OVERDUE ASSIGNMENT, GIVE THEM A ZERO</h4>";

                        $DB->execute('DELETE FROM mdl_grade_grades WHERE itemid='.$gradeItem->id.' AND userid='.$student);

                        $theGrade = new stdClass();
                        $theGrade->itemid = $gradeItem->id;
                        $theGrade->userid = $student;
                        $theGrade->finalgrade = 0;
                        $theGrade->overridden   = time();
                        $theGrade->timemodified   = time();
                        var_dump($theGrade);
                        $lastinsertid = $DB->insert_record('grade_grades', $theGrade, false);

                    }

                }


            }

        }

    }

    // FORCE REGRADING!
    if($triggerRegrade == true){
        $grade_item = grade_item::fetch(array('id'=>$gradeItem->id, 'courseid'=>$course->id));
        $grade_item->force_regrading();
        debugMessage ("p","REGRADE COMPLETE");
    }


}