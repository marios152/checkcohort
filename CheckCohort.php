<?php
//************************************************************************************************
//																								 
// I get all the lecturers enrolled in the selected programme and then I check if				 
// any lecturers are missing from the cohort associated with the programme.						 
//																								 
//************************************************************************************************
require_once('../../config.php');
require_login();

global $DB, $PAGE, $USER;
//error_reporting(0);

$PAGE->set_context(context_system::instance()); 
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Check Cohorts');

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/formslib.php");
require($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->libdir.'/adminlib.php');
echo $OUTPUT->header();
?>
<html <?php echo $OUTPUT->htmlattributes();  ?>>
<?php //echo $OUTPUT->header();?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
	<script type='text/javascript'>
		$(document).ready(function () {
			$('.btn').on('click',function(e){  //since I have multiple buttons I cannot have multiple buttons with the same id. that's why I am using class
			var currentRow=$(this).closest("tr");
				var userId = currentRow.find("td").eq(1).children("span").text();
				var cohortId = $('#cohortId').val();
				$.ajax({
					type:'POST',
					url:'enrol.php',
					data:{
						'userId': userId,
						'cohortId': cohortId
					},
					success:function(data){
						currentRow.find("td").eq(7).children("span").text("Enrolled"); // with "eq(6)" I target the 6th column in order to output the message 'Enrolled'
						currentRow.find("td").eq(6).children("input").hide();  // here I hide the button 'Enroll' after I used it
						//alert('everything is fine. cohortId: '+cohortId+' username: '+username );
					},
					error:function(data){
						alert('something went wrong');
					}
				});			
				 e.preventDefault();
			})
		});
	</script>
<h1>Select what to check</h1>

<?php
	$getMoodleCategories = $DB->get_records("course_categories", null, 'name');
	$getMoodleCohorts = $DB->get_records("cohort", null, 'name');
?>

<form method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>">
<div class="align">
<table>
	<tr>
		<td>
			<?php echo"<h2>Programme: </h2>";?>	
		</td>
		<td>	
			<select name="catId">
				<?php foreach($getMoodleCategories as $category): ?>
					<?php if(@$_GET['catId'] == $category->id):?>
						<option value="<?php echo $category->id;?>" selected><?php echo $category->name;?></option>
					<?php else:?>
						<option value="<?php echo $category->id;?>"><?php echo $category->name;?></option>
					<?php endif;?>
				<?php endforeach;?>
			</select>
		</td>
		<td>
			<?php echo"<h2>Cohort: </h2>";?>
		</td>
		<td>
			<select name="cohortId">
				<?php foreach($getMoodleCohorts as $cohort): ?>
					<?php if(@$_GET['cohortId'] == $cohort->id):?>
						<option value="<?php echo $cohort->id;?>" selected><?php echo $cohort->name;?></option>
					<?php else:?>
						<option value="<?php echo $cohort->id;?>"><?php echo $cohort->name;?></option>
					<?php endif;?>
				<?php endforeach;?>
			</select>
		</td>
		<td>
			<input class="align" type="submit" name="action" value="Check"></input>
		</td>
	</tr>
</table>
</div>	
</form>
<?php
if (isset($_POST['action']) && ($_POST['action'] == "Check")){
$cohortId=$_POST['cohortId'];
$categoryId=$_POST['catId'];

	$cohortName=  $DB->get_record("cohort", array('id'=>$cohortId),'id,name');
    $categoryName = $DB->get_record("course_categories", array('id'=>$categoryId),'id,name,path,depth');
	echo "<h1>You have selected the programme: <span style='color:blue'>".$categoryName->name."</span> and the cohort: <span style='color:blue'>".$cohortName->name."</span></h1>";
//********************************************** Get cohort members with Moodle api *************************************************************
	$usersForumArr=[];
	$cohortids=array('id'=>$cohortName->id);
	$params =  array('cohortids' => $cohortids);
    $cohmembers = array();
		foreach ($params['cohortids'] as $cohortid) {
			// Validate params.
			$cohort = $DB->get_record('cohort', array('id' => $cohortid), '*', MUST_EXIST);
			// Now security checks.
			$context = context::instance_by_id($cohort->contextid, MUST_EXIST);
			if ($context->contextlevel != CONTEXT_COURSECAT and $context->contextlevel != CONTEXT_SYSTEM) {
				throw new invalid_parameter_exception('Invalid context');
			}
			// self::validate_context($context);
			if (!has_any_capability(array('moodle/cohort:manage', 'moodle/cohort:view'), $context)) {
				throw new required_capability_exception($context, 'moodle/cohort:view', 'nopermissions', '');
			}
			$cohortmembers = $DB->get_records_sql("SELECT u.id FROM {user} u, {cohort_members} cm
				WHERE u.id = cm.userid AND cm.cohortid = ?
				ORDER BY lastname ASC, firstname ASC", array($cohort->id));
			// $cohmembers[] = array('cohortid' => $cohortid, 'userids' => array_keys($cohortmembers));
			$cohmembers = array('userids' => array_keys($cohortmembers));
		}
//******************************************* Courses Exist in Moodle - Get lecturers of the courses *********************************************
	$resultsCourses = $DB->get_records("course",array('category'=>$categoryId)); // get all the courses of a specific category.
	echo "<h2> Courses exist in moodle </h2>";
	$lecturersArr=[];
	echo "<pre>";
		foreach($resultsCourses as $rc){   // courses of specific category exist in moodle.
			$lec_count = 0;
			$non_ed_teach_count = 0;
            $coursecontext = context_course::instance($rc->id);
			$lecturers = get_role_users(3, $coursecontext);
			$non_editing_teach = get_role_users(4, $coursecontext);		
			$tempLecturerName="";
			/*search for lecturer*/
				foreach($lecturers as $lecturer){
					$lec_count++; 
				}
				foreach($non_editing_teach as $non_ed_t){ 
					$non_ed_teach_count++;
				}
				if ($lec_count>=1){
					foreach($lecturers as $lec){
						echo "Course: <a style='color:blue;' target='_blank' href=".$CFG->wwwroot."/course/view.php?id=".$rc->id.">".$rc->shortname."</a> Lecturer: ".$lec->firstname." ".$lec->lastname."<br/>";
						$tempLecturerName= $lec->firstname." ".$lec->lastname;
						//echo "</br>the temp Lecturer Name is: ".$tempLecturerName."<br/>";
						if(!in_array($lec->id,$lecturersArr)){
							array_push($lecturersArr,$lec->id);
						}	
					}
				}
				if ($non_ed_teach_count>=1){
					foreach($non_editing_teach as $non_ed_t){
						echo "Course: <a style='color:blue;' target='_blank' href=".$CFG->wwwroot."/course/view.php?id=".$rc->id.">".$rc->shortname."</a>  Lecturer: ".$non_ed_t->firstname." ".$non_ed_t->lastname."<br/>";
						$tempLecturerName= $non_ed_t->firstname." ".$non_ed_t->lastname;
						//echo "</br>the temp Lecturer Name is: ".$tempLecturerName."<br/>";
						if(!in_array($non_ed_t->id,$lecturersArr)){
							array_push($lecturersArr,$non_ed_t->id);
						}
					}
				}
				if(($lec_count==0) && ($non_ed_teach_count==0)){
					echo "Course: <a style='color:blue;' target='_blank' href=".$CFG->wwwroot."/course/view.php?id=".$rc->id.">".$rc->shortname."</a> Lecturer: <span style='color:red;'> No lecturer enrolled</span></br>";
				}
		}
	echo "</pre>";		
//**************************************************************** COMPARISON *************************************************************
echo "<h2> Check cohort </h2>";
$problem = 0;
	echo "<pre>";
	echo "<form id='enrolUserForm' method='POST' action='enrol.php'>";
	
				echo "<table id='userInfo' style='width:100%' >";
					 echo "<colgroup>
							<col style='width: 0%' />
							<col style='width: 5%' />
							
							<col style='width: 20%' />
							<col style='width: 15%' />
							<col style='width: 15%' />
							<col style='width: 15%' />
							<col style='width: 15%' />
							<col style='width: 15%' />
						   </colgroup>";
		foreach ($lecturersArr as $lecArr){	
			if (in_array($lecArr, $cohmembers['userids'])){
				// echo "<p>User: ".$lecArr." is in the cohort</p>";	
			}else{
				$problem=1;
				$userarray = array('id'=>$lecArr);//get the usernames
				$userlecturer = $DB->get_record('user', $userarray);		
						echo "<tr>";		
							echo "<td style='color:red'>There is a problem with: </td>";
							$tempUsername = $userlecturer->username;
							echo "<td><span style='display:none;'>".$userlecturer->id."<span></td>";
							echo "<td><span>".$tempUsername."</span></td>"; // username
							echo "<td><span>".$userlecturer->firstname."</span></td>"; // firstname
							echo "<td><span>".$userlecturer->lastname."</span></td>"; // lastname		
							echo "<td><span>".$userlecturer->email."</span></td>"; // email	
							echo "<input type='hidden' id='cohortId' name='cohortId' value='".$cohortId."'></input>";
							echo "<td><input class='btn' id='enrollUser' type='submit' value='Enroll'></input></td>";
							echo "<td><span style='color:blue'></span></td>";
						echo "</tr>";
			}
		}
				echo "</table>";
	echo "</form>";	
		
		if ($problem!=1){
			echo "<p style='color:blue'>Everyone is enrolled </p>";
		}
	echo "</pre>";
//*****************************************************************************************************************************************
}
?>
<?php //error reporting
// ini_set ('display_errors', 'on');
// ini_set ('display_startup_errors', 'on');
// ini_set ('error_reporting', E_ALL);
 echo $OUTPUT->footer();  ?>
</html>