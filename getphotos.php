<!--this document use Bootstrap framewrok for css styling
*PHP reference: https://www.w3schools.com/php/php_form_url_email.asp	
-->
<!DOCTYPE html>
<html lang = "en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <!--Bootstrap CSS-->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  <meta name="author" content="Dara Chi">
  <meta name="description" content="Get photos from cloud UI">
  <title>Get Photos</title>
  <script src="myScript.js"></script>
</head>
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

use Aws\S3\S3Client; 
use Aws\Common\Exception\S3Exception;

function make_title_search_query($link, $title){
	
	$title = $link-> real_escape_string($title);//clean input to pure string
	return $query_by_title = "SELECT * FROM photo WHERE photo_title = '$title'";
}

function make_keyword_search_query($link, $keyword){
	
	$keyword = $link-> real_escape_string($keyword);//clean input to pure string
	$query_by_keyword = "
		SELECT photo.*
		FROM photo_keyword
		LEFT JOIN photo ON photo.photo_id = photo_keyword.photo_id
		WHERE photo_keyword.keyword_id = '$keyword'
	";
	return $query_by_keyword;
}

function make_date_range_search_query($link, $startdate, $enddate) {

	$query_by_date_range = "SELECT * FROM photo WHERE date_of_photo >= '$startdate' AND date_of_photo <= '$enddate'";
	return $query_by_date_range;
}

$photos = array();
function photo_search($link, $query) {
	global $photos;

	$result = $link->query($query);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$photos[] = $row;
		}
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


function getSignedURL($resource, $timeout){
    // This is the id of the Cloudfront key pair you generated
    $keyPairId = "[pk-APKAJZX45KMU7PW2JZ3A]";

    $expires = time() + 60; // Timeout in seconds
    $json = '{"Statement":[{"Resource":"'.$resource.'","Condition":{"DateLessThan":{"AWS:EpochTime":'.$expires.'}}}]}';

    // Read Cloudfront Private Key Pair, do not place it in the webroot!
    $fp = fopen("/AWS/pk-APKAJZX45KMU7PW2JZ3A.pem", "r");
    $priv_key = fread($fp,8192);
    fclose($fp);

    // Create the private key
    $key = openssl_get_privatekey($priv_key);
    if (!$key) {
        throw new Exception('Loading private key failed');
    }

    // Sign the policy with the private key
    if (!openssl_sign($json, $signed_policy, $key, OPENSSL_ALGO_SHA1)) {
        throw new Exception('Signing policy failed, '.openssl_error_string());
    }

    // Create url safe signed policy
    $base64_signed_policy = base64_encode($signed_policy);
    $signature = str_replace(array('+','=','/'), array('-','_','~'), $base64_signed_policy);

    // Construct the URL
    $url = $resource .  (strpos($resource, '?') === false ? '?' : '&') . 'Expires='.$expires.'&Signature=' . $signature . '&Key-Pair-Id=' . $keyPairId;

    return $url;
}

// function get_presignedURL($file_name){
// 	require  (dirname(__DIR__).'/aws/aws-autoloader.php');
// 	global $url;
// 	$s3Client = new S3Client(['region' => 'ap-southeast-2',
//                                      'version' => 'latest']);

// 	$cmd = $s3Client->getCommand('GetObject', [
// 							    'Bucket' => '20-09-assign1photos',
// 							    'Key' => 'photos/'.$file_name]);

// 	$request = $s3Client->createPresignedRequest($cmd, '+20 minutes');

// 	//Creating a presigned URL
// 	$cmd = $s3Client->getCommand('GetObject', ['Bucket' => '20-09-assign1photos',
// 	     						'Key' => 'photos/'.$file_name]);

// 	$request = $s3Client->createPresignedRequest($cmd, '+20 minutes');
// 	// Get the actual presigned-url
// 	$presignedUrl = (string)$request->getUri();
// 	// Getting the URL to an object
// 	//Getting the URL to an object
// 	$url = $s3Client->getObjectUrl('20-09-assign1photos', 'photos/'.$file_name);
// 	return $url;

// }

// $
// function list_object_from_S3(){
// 	require  (dirname(__DIR__).'/aws/aws-autoloader.php');
// 	$s3_client = new S3Client(['version' => 'latest',
// 								'region' => 'ap-southeast-2']);
	// try {
	// 	$results = $s3->getPaginator('ListObjects', [
	// 	'Bucket' => BUCKET
	// 	]);

	// 	foreach ($results as $result) {
	// 		if($result['Contents'] != null){
	// 			foreach ($result['Contents'] as $object) {
	// 			$file_name = $object['Key'];
				
	// 			return $file_name;
				
	// 			}
	// 		}	
	// 	}
	
	// } catch (S3Exception $e) {
	// 	echo $e->getMessage() . PHP_EOL;
	// }

	
// }



$link = "";
$keyword = $title = $startdate = $enddate = " ";
$keyword_err = $title_err = $startdate_err = $enddate_err = " ";
$photo_title ="";
$date_of_photo = "";
$description = "";
$url="";





if ($_SERVER['REQUEST_METHOD'] === 'POST'){
	global $url;
	$link = connectDB();	
	if(isset($_POST['keyword'])){
		$keyword = $_POST['keyword'];
		$sql_query = make_keyword_search_query($link, $keyword);
		photo_search($link, $sql_query);
		// $file_name = list_object_from_S3();
		

		
	} 

	else if(isset($_POST['title'])){
		$title = $_POST['title'];
		$sql_query = make_title_search_query($link, $title);
		photo_search($link, $sql_query);
		// $url = get_presignedURL($_POST['title']);
		
	}

	else if (isset($_POST['startdate']) && isset($_POST['enddate'])) {
		$startdate = $_POST['startdate'];
		$enddate = $_POST['enddate'];
		$sql_query = make_date_range_search_query($link,$startdate, $enddate);
		photo_search($link, $sql_query);
		// $url = get_presignedURL($_POST['title']);

		
	}

	$link->close();
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
	        <a class="nav-link active" href="getphotos.php">Search Photos</a>
	      </li>
	      <li class="nav-item">
	        <a class="nav-link " href="upload.php">Upload Photos</a>
	      </li>
	    </ul>
	  	</div>
  	<div class="card-body">
    	<h5 class="card-title">Choose the filter system </h5>
  	</div>
	  	<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="POST">
	  		<label>
	  			<input type="radio" id ="search_title" name ="search_method" onclick="showTitle();">Search By Title
	  		</label>
	  		<label>
	  			<input type="radio" id ="search_keyword" name ="search_method" onclick="showKeyword();">Search By Keyword
	  		</label>
	  		<label>
	  			<input type="radio" id ="search_date" name ="search_method" onclick="showDate();">Search By Date
	  		</label>
	  		
	  		<div class="form-group" id="title_div" style="display:block;">
			    <label for="exampleFormControlSelect1">Search By Title</label>
				    <select class="form-control" name="title" id="title" >
				    	<option disabled selected value> -- select an option -- </option>
		         		<option value="temple">Temple</option>
		          		<option value="waterfall">Waterfall</option>
		         		<option value="lookout">Lookout</option>
		          		<option value="handsome">Handsome</option>

				    </select>
				    <input type="submit" name="submit_title" class="btn btn-primary">
			    	
			</div>

			<div class="form-group row" id="keyword_div" style="display:none;">
				<label for="exampleFormControlSelect1">Search By Keyword</label>
			        <select id="keyword" name= "keyword" class="form-control">
			        <option disabled selected value> -- select an option -- </option>
				        <option value="1">husband</option>
				        <option value="2">cute</option>
				        <option value="5">pilgrim</option>
				        <option value="3">zen</option>
				        <option value="4">mindfulness</option>
				        <option value="7">fresh</option>
				        <option value="8">beautiful</option>
				        <option value="9">cooling</option>
				        <option value="10">view</option>
				        <option value="11">scenery</option>
				        <option value="7">fresh</option>
			        </select>
				    <input type="submit" name="submit_keyword" class="btn btn-primary"> 

			        
	  		</div>

	  		<div class="form-group row" id="date_div" style="display:none;">
	  			<label>Search By Date</label>
	  			<p>
		  			Start Date <input type="date" name="startdate" id="startdate" value="php echo $_startdate;>">
		        	End Date<input type="date" name="enddate" id="enddate" value="<?php echo $_enddate;?>">
			        <input type="submit" name="submit__date_range" class="btn btn-primary" class="col-sm-10">
	  			</p>
	      	</div>
	      	<?php 
				foreach ($photos as $photo) {
			?>
	      	<div class="card mb-3" style="max-width: 100%;">
		  		<div class="row no-gutters">
		    		<div class="col-md-4">
		     			<img src= "<?php echo $url;?>"
		     			class="card-img" >
		     			<a href= "<?php $url; ?>"
		     				class="btn btn-primary stretched-link" target="_blank" ><?php echo $url; ?></a>
		    		</div>
			    	<div class="col-md-8">
			      		<div class="card-body">
			        		<h5 class="card-title">Here is your photo:</h5>
			        		<p ><?php echo "photo name: ".$photo['photo_title']; ?></p>
					        <p ><?php echo "date of photo: ".$photo['date_of_photo']; ?></p>
					        <p ><?php echo "description: ".$photo['description']; ?></p>
			      		</div>
			    	</div>
		  		</div>
	  		</div>
  			<?php 
      		}
			?>
	  		
	  	</form>
	</div>
</body>
</html>