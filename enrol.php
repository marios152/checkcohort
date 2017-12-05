<?php
require_once('../../config.php');
require($CFG->dirroot.'/cohort/lib.php');
$cohortId = $_POST['cohortId'];
$userId = $_POST['userId'];
 echo "cohortid: ".$cohortId."<br/>";
 echo "userid: ".$userId;
cohort_add_member($cohortId, $userId);		
