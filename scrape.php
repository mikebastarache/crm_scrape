<?php 
include('simple_html_dom.php');

define("HOST", "qimple-development.cwmomrkawegu.us-east-1.rds.amazonaws.com");
define("USER", "qimple_db");
define("PASSWORD", "49REd8[%f.ji23");
define("DATABASE", "crm_scrape");
 
define("CAN_REGISTER", "any");
define("DEFAULT_ROLE", "member");
 
define("SECURE", FALSE);

$site_id = 5;
$start_target_id = 0;
$contact_count = 0;

// Create connection
$conn = new mysqli(HOST, USER, PASSWORD, DATABASE);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$sql = "SELECT * FROM contacts WHERE site_id = $site_id order by target_id desc limit 0,1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
        $start_target_id = $row["target_id"];
    }
} else {
    $start_target_id = 1000;
}

$insert_sql = "INSERT INTO contacts (site_id, target_id, email, name, title, organization, website, location, city, province, country) VALUES ";

/* HRANB LOGIN INFORMATION */

$username="3275"; 
$password="3275Bou"; 
$url = "http://hranb.org/login.php";

$ch = curl_init();    
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

curl_setopt($ch, CURLOPT_URL, $url); 
$cookie = 'cookies.txt';
$timeout = 30;

curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_TIMEOUT,         10); 
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,  $timeout );
curl_setopt($ch, CURLOPT_COOKIEJAR,       $cookie);
curl_setopt($ch, CURLOPT_COOKIEFILE,      $cookie);

curl_setopt ($ch, CURLOPT_POST, 1); 
curl_setopt ($ch,CURLOPT_POSTFIELDS,"username=".$username."&password=".$password);     

$r = curl_exec($ch);

$start_target_id = $start_target_id + 1;
$end_target_id = $start_target_id + 100;
for ($target_id = $start_target_id; $target_id <= $end_target_id; $target_id++) 
{   $email = "";
    $name = "";
    $title = "";
    $organization = "";
    $website = "";
    $location = "";
    $city = "";
    $province = "";
    $country = "Canada";

    //OPTIONAL - Redirect to another page after login 
    $url = "http://hranb.org/admin/view_member.php?id=" . $target_id;
    curl_setopt ($ch, CURLOPT_POST, 0); 
    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);

    $html = new simple_html_dom();
    $html->load($result);
    # get an element representing the second paragraph
    $table = $html->find('table');

    // initialize empty array to store the data array from each row
    $theData = array();

    // loop over rows
    foreach($table[6]->find('tr') as $row) {
    	$status = false;
        // initialize array to store the cell data from each row
        $rowData = array();
        foreach($row->find('td') as $cell) {
    		$status = true;

            // push the cell's text to the array
            $rowData[] = $cell->plaintext;
        }

        if($status){
     	   // push the row's data array to the 'big' array
        	$theData[] = $rowData;
            if($rowData[0] == "Name:")
            {
                $name = mysql_real_escape_string(trim($rowData[1]));
            }
            if($rowData[0] == "Title:")
            {
                $title = mysql_real_escape_string(trim($rowData[1]));
            }
            if($rowData[0] == "Organization:")
            {
                $organization = mysql_real_escape_string(trim($rowData[1]));
            }
            if($rowData[0] == "Email:")
            {
                $email = mysql_real_escape_string(trim($rowData[1]));
            }
            if($rowData[0] == "Website:")
            {
                $website = mysql_real_escape_string(trim($rowData[1]));
            }
            if($rowData[0] == "Chapter:")
            {
                $location = mysql_real_escape_string(trim($rowData[1]));
            }
        }
    }
    if(!empty(trim($email))) 
    {
        $contact_count += 1;
        $insert_sql .= "(" . $site_id . ", " . $target_id . ", '" . $email . "', '" . $name . "','" . $title . "','" . $organization . "','" . $website . "','" . $location . "','" . $city . "','" . $province . "','" . $country . "'),";    
    }
}
curl_close($ch); 


print "STARTED: " . $start_target_id . "<br>";
print "ENDED: " . $end_target_id . "<br>";
print "FOUND: " . $contact_count . "<br><br>";



// DO ONLY 1 SQL INSERT
if($contact_count > 0)
{
    print $insert_sql;
    $insert_sql = rtrim($insert_sql, ",") . ";";
    if ($conn->query($insert_sql) === TRUE) {
        echo "Record(s) created successfully";
    } else {
        echo "Error: " . $insert_sql . "<br>" . $conn->error;
    }   
}

$sql = "UPDATE sites SET updated = " . now() ." WHERE site_id=" . $site_id;

if ($conn->query($sql) === TRUE) {
    echo "Record updated successfully";
} else {
    echo "Error updating record: " . $conn->error;
}

$conn->close();
?>