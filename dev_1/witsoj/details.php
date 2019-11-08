
<table style="width:100%">
  <tr>
    <th>Firstname</th>
    <th>Lastname</th>
    <th>Age</th>
  </tr>
  <tr>
    <td>Jill</td>
    <td>Smith</td>
    <td>50</td>
  </tr>
  <tr>
    <td>Eve</td>
    <td>Jackson</td>
    <td>94</td>
  </tr>
  <tbody id = "data">
  </tbody>
</table> 

<?php
#error_reporting(E_ALL) ;
#ini_set('display_errors',1) ;
#var_dump($CFG->dirroot);
#require_once($CFG->dirroot . '/mod/assign/feedback/witsoj/locallib.php');
require_once('../../../../config.php');
global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once('/var/www/html/moodle/mod/assign/feedback/witsoj/locallib.php');
$id = $_GET["id"] ;
echo "Running through the hello worlds '$id'";	
?>



<script>
	var id = <?php echo $id ?> ;
	$.ajax({
		url: 'feedback/witsoj/ajax.php',
		type :"POST",
		data:{sendID: id},
		success: function(response){
			console.log("Hello");
			console.log(id);
			if(response != "GRADED"){
				$("#tmp").html(response);
			}else{
				location.reload() ;
			}
		},
		error: function(status){
			console.log(id);
			alert(JSON.stringify(status,null,4)) ;
		}	

	});


</script>

