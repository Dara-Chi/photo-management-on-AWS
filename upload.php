<!-- assignment 2  by Dara Chi; student ID : 101662320 
*this document use Bootstrap framewrok for css styling
*PHP reference: https://www.w3schools.com/php/php_form_url_email.asp	
-->
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<!--Bootstrap CSS-->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<meta name="author" content="Dara Chi">
	<meta name="description" content="Upload photos to cloud UI">
	<title>Upload Photos</title>
	<script src="myScript.js"></script>
</head>
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

use Aws\S3\S3Client; 
use Aws\Common\Exception\S3Exception;
use Aws\S3\MultipartUploader;

$keyword;
$title; 

function uploadFromEC2ToS3(){
	$url;
	require  (dirname(__DIR__).'/aws/aws-autoloader.php');
	$S3_client = new S3Client(['version' => 'latest','region' => 'ap-southeast-2']);

	if((isset($_POST['title']))&&(isset($_POST['description']))&&(isset($_POST['keyword']))&&(isset($_FILES['image']))){
		$file_name = basename($_FILES['image']['name']);
		$bucket_name = '20-09-assign1photos';
		$temp_file = dirname(__FILE__).'/uploads/'.$file_name;
		$keyname = basename($_FILES['image']['name']);
		
		if(move_uploaded_file($_FILES['image']['tmp_name'], $temp_file)){
			$uploader = new MultipartUploader($S3_client,$temp_file,['bucket' => $bucket_name, 'key' => 'photos/'.$keyname]);
			try {
				$result = $uploader -> upload();
				$url = $result['ObjectURL'];
			} catch (S3Exception $s3ex){
				echo $s3ex;
			}
		}
	}
	return $url;
}

function insert_query_table_photo($url){
	$title = $_POST['title'];
	$description = $_POST['description'];
	$date_of_photo = date('Y-m-d');
	$insert_query = "INSERT INTO photo (photo_title, description, date_of_photo,reference)
					VALUES ('$title', '$description', '$date_of_photo', '$url')";
	return $insert_query;
}

function insert_query_table_keyword(){

	$keyword = $_POST['keyword'];
	$insert_query = "INSERT INTO keyword(keyword)
					VALUES('$keyword')";
	return $insert_query;
}



function insert_query_table_photo_keyword($photo_id, $keyword_id){
	$insert_query = "INSERT INTO photo_keyword(photo_id,keyword_id)
						VALUES('$photo_id','$keyword_id')";
	return $insert_query;

}


function is_keyword_exist($link){
	$keyword = $_POST['keyword'];
	$keyword_id_query = "SELECT keyword_id FROM keyword WHERE keyword = '$keyword'";
	if($result = $link->query($keyword_id_query)){
		printf($result ->num_rows);
		return TRUE;
	} else return FALSE;

}

function get_keyword_id($link){
	$keyword = $_POST['keyword'];
	$keyword_id_query = "SELECT keyword_id FROM keyword WHERE keyword = '$keyword'";
	if($result = $link->query($keyword_id_query)){
		if ($result ->num_rows > 0);
		$row = $result-> fetch_assoc();
		return $row['keyword_id'];
	} 
}


function get_photo_id($link){
	
	$title = $_POST['title'];
	$get_photo_id_query = "SELECT photo_id FROM photo WHERE photo_title='$title'";
	
	if ($result = $link->query($get_photo_id_query) ){
		if ($result ->num_rows > 0);
		$row = $result-> fetch_assoc();
		return $row['photo_id'];

	}

}


function insert_metadata_to_table($link, $query){
	if($link->query($query) === TRUE){
		echo "insert successfully";
		return $link->insert_id;
	} else {
		echo "Error: " . $query . $link->error;
	}
}

function connectDB(){
	require 'dbconnection.php';

	$link = new mysqli($_hostname, $_username, $_password, $_database);
	if ($link->connect_error) {
		die("connection failed: " . $link->connect_error);
	} 
	return $link;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
	if((isset($_POST['title']))&&(isset($_POST['description']))&&(isset($_POST['keyword']))&&(isset($_FILES['image']))){
		$link = connectDB();
		$url = uploadFromEC2ToS3();
		$query = insert_query_table_photo($url);
		$photo_id = insert_metadata_to_table($link, $query);
		$query = insert_query_table_keyword();
		$keyword_id = insert_metadata_to_table($link, $query);
		$query = insert_query_table_photo_keyword($photo_id,$keyword_id);
		if (is_keyword_exist($link)){
			insert_metadata_to_table($link,$query);
		}
		$link->close();
	}
}

?>
<body>
	<div class="card text-center">
		<div class="card-header">
		    <ul class="nav nav-tabs card-header-tabs">
		      	<li class="nav-item">
		        <a class="nav-link " href="index.php">Home</a>
		      	</li>
		      	<li class="nav-item">
		        <a class="nav-link " href="getphotos.php">Search Photos</a>
		      	</li>
		      	<li class="nav-item">
		        <a class="nav-link active" href="upload.php" >Upload Photos</a>
		     	</li>
		    </ul>
		</div>
		<div class="card-body">
		<h5 class="card-title">Please enter your photo details</h5>
		<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" enctype="multipart/form-data" method="POST" accept-charset="utf-8">
			<div>
				<div class = "form-group row">
					<label for=" title" class="col-sm-2 col-form-label">Photo Title: </label>
					<input type="text" id="title" name="title">
				</div>
				<div class = "form-group row">
					<label for="description" class="col-sm-2 col-form-label">Description: </label>
					<input type="text" id="description" name="description">
				</div>
				<div class = "form-group row">
					<label for="keyword" class="col-sm-2 col-form-label" >Keywords: </label>
					<input type="textfield" id="keyword" name="keyword" >
				</div>
				<div class = "form-group row">
					
					<label for = "image" class="col-sm-2 col-form-label">Choose File: </label>
					<input type="file" name="image" id ="image">
				</div>
				<input type="submit" name="submit" value="Upload" class=" btn btn-primary btn-lg btn-block">
			</div>
		</form>
		</div>
</body>
</html>