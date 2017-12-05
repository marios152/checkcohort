# checkcohort

<h4>Check if students or teachers that belong to a category also belong to a cohort. If not then enroll.</h4>


<p>CheckCohortStudents.php</p>
<ul>
<li>CheckCohortStudents.php is for the admins who want to do a checkup if all the students enrolled into the courses of a 
specific category are also included into a specific cohort associated with the programme.<li>
</ul>

<p>CheckCohort.php</p>
<ul>
<li>
CheckCohort.php is for the admins who want to do a checkup if all the teachers (including non-editing teachers role) enrolled into the courses of a 
specific category are also included into a specific cohort associated with the programme.
</li>
</ul>


# important

In order for the scripts to work you need to include checkcohort directory into a new directory in moodle eg. pages. 
The url must look like this: 'https://<samplemoodle.com>/moodle/pages/checkcohort/CheckCohortStudents.php'

In case you modified your roles in moodle you need to change the 'get_role_users()' so that you get the 
correct course context of your moodle. My user role values are set to the default.



