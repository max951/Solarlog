<?php
require_once('../../PHPMailer/class.phpmailer.php');
require_once('../../PHPMailer/class.smtp.php');


require_once('./config.php');






date_default_timezone_set('Europe/Berlin');
$date = new DateTime("now", new DateTimeZone('Europe/Berlin') );


$cookiesFile = "cookies.txt"; // <--- cookies are stored here


/***** SERIENNUMMER *****/
//echo "***** SERIENNUMMER *****<br>\r\n";
// API URL
$url = $address . '/getjp';
//echo $url . "<br>\r\n";
// Create a new cURL resource
$ch = curl_init($url);
// Setup request to send json via POST
$data = array(
    "706" => null
);
$payload = json_encode($data);
//echo $payload . "<br>\r\n";
// Attach encoded JSON string to the POST fields
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
// Set the content type to application/json
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
// Return response instead of outputting
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Execute the POST request
$result = curl_exec($ch);
// Close cURL resource
curl_close($ch);
//echo $result . "<br>\r\n";
$arr = json_decode($result, true);
$seriennummer = $arr['706'];
//echo $seriennummer . "<br>\r\n";




/***** LOGIN *****/
//echo "***** LOGIN *****<br>\r\n";
$url = $address . '/login';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
$postfields = http_build_query(array('u' => $user, 'p' => $password));
curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiesFile ); // <---
curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookiesFile ); // <---
 
// get headers too with this line
curl_setopt($ch, CURLOPT_HEADER, 1);
$result = curl_exec($ch);
//echo $result . "<br>\r\n";  

$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
//echo $header_size . "<br>\r\n";  

// Close cURL resource
curl_close($ch);



// get cookie, all cos sometime set-cookie could be more then one
preg_match_all('/^Set-Cookie:\s*([^\r\n]*)/mi', $result, $ms);
// print_r($result);
$cookies = array();
foreach ($ms[1] as $m) {
    list($name, $value) = explode('=', $m, 2);
    $cookies[$name] = $value;
}
//print_r($cookies);
$token = $cookies["SolarLog"];
//echo $token . "<br>\r\n";  


$header = substr($result, 0, $header_size);
//echo $header . "<br>\r\n";  
$body = substr($result, $header_size);
//echo $body . "<br>\r\n";  


// Further processing ...
if (substr($body, 0, 7) == "SUCCESS") {
    //echo "Anmeldung erfolgreich.<br>\r\n";
    //echo $token . "<br>\r\n";
} else {
    exit("Fehler: Anmeldung war nicht erfolgreich");
}




//*****************************************************************************
/***** LOGCHECK *****/
//echo "***** LOGCHECK *****<br>\r\n";
date_default_timezone_set("UTC");
$timestamp = microtime(true);
$timestamp = $timestamp * 1000;

$url = $address . '/logcheck?_=' . $timestamp;
// Create a new cURL resource
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiesFile ); // <---
curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookiesFile ); // <---
$result = curl_exec($ch);
// Close cURL resource
curl_close($ch);

 
 


//*****************************************************************************
/***** DATA REQUEST *****/
//echo "***** DATA REQUEST *****<br>\r\n";
$url = $address . '/getjp';

// Create a new cURL resource 
$ch = curl_init($url);

// Setup request to send json via POST
$data = array(
    "860" => array(
        "0" => null
    )
);

$payload = json_encode($data); 

//$payload = '{"800":{"160":null}}';
//$payload = '{"800":{"160":null}}';
//$payload = '{"801":{"170":null}}';
//$payload = '{"141":{"0":{"108":null}}}';
//$payload = 'token='. $token .';preval=none;{"800":{"160":null}}'; 
//$payload = 'token='. $token .';preval=none;{"141":{"32000":{"108":null,"118":null,"119":null,"145":null,"149":null,"158":null}},"152":null,"161":null,"162":null,"480":null,"776":{"1":null},"777":{"1":null},"801":{"100":null}}'; 
$payload = 'token='. $token .';preval=none;{"141":{"32000":{"108":null,"118":null,"119":null,"145":null,"149":null,"158":null}},"152":null,"161":null,"162":null,"480":null,"776":{"1":null},"777":{"1":null},"801":{"100":null}}'; 
//$payload = 'token='. $token .';preval=none;' . $payload; 
//echo $payload . "<br>\r\n";

// Attach encoded JSON string to the POST fields
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiesFile ); // <---
curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookiesFile ); // <---

// Set the content type to application/json
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

// Return response instead of outputting
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the POST request
$result = curl_exec($ch);

// Close cURL resource
curl_close($ch);


//echo $result;

//echo "************************************************************<br>\r\n";
//var_dump(json_decode($result));



$arr = json_decode($result, true);


//echo "141 Wechselrichterinformationen \r\n";
$i=0;
foreach ($arr[141] as &$value) { 
    //echo "<pre>"; var_dump($value[119]); echo "</pre>\r\n";
    $resarr[$i]["Bezeichnung"] = $value[119];
    $resarr[$i]["Generatorleistung"] = $value[118];
    $i++;
}


//echo "777 Tagesdaten der Wechselrichter des aktuellen Monates \r\n"; 
$aktuellerTag= end($arr[777][1]);
$datum = $aktuellerTag[0];
$i=0;
foreach ($aktuellerTag[1] as &$value) { 
    //echo "<pre>"; var_dump($value); echo "</pre>\r\n";
    $resarr[$i]["Ertrag"] = $value;
    $resarr[$i]["spezifischerErtrag"] = $resarr[$i]["Ertrag"] / $resarr[$i]["Generatorleistung"];
    $i++;
}

/*
echo "*********************************************************************************\r\n"; 
echo "<pre>"; 
echo "RESULT 1 \r\n";
echo "Datum: ". $datum ." \r\n";
var_dump($resarr); 
echo "</pre><br>\r\n";
echo "*********************************************************************************\r\n"; 
*/

$resTable = []; 
$resTable[] = "Solar-Log - [SN:". $seriennummer ."]<br>\r\n";
$resTable[] = "Ertragsübersicht vom ". $date->format('d.m.Y - H:i:s') ."<br>\r\n";
$resTable[] = "abgefragt um ". $date->format('d.m.Y - H:i:s') ."<br>\r\n";
$resTable[] = " <br>\r\n";
$resTable[] = '<table border="1">'."\r\n";
$resTable[] = "<tr><td>lfd.Nr</td><td>Bezeichnung</td><td>Ertrag [kWh]</td><td>spez. Ertrag [kWh/kWp]</td></tr>\r\n";
$i = 0;
foreach ($resarr as &$value) {
    $i++;
    $resTable[] = "<tr><td>". $i ."</td><td>". $value["Bezeichnung"] ."</td><td>". number_format(($value["Ertrag"]/1000), 2, ',', '.') ."</td><td>". number_format($value["spezifischerErtrag"], 2, ',', '.') ."</td></tr>\r\n";
}
$resTable[] = "</table>\r\n";
//$resTable[] = "Test\r\n";

$resString = implode($resTable); 

echo $resString;







/***** E-MAIL VERSAND *****/
//echo "***** E-MAIL VERSAND *****<br>\r\n";
$mail = new PHPMailer(); 

$mail->IsSMTP();
$mail->Host       = $mailhost;
$mail->SMTPDebug  = $SMTPDebug; // Kann man zu debug Zwecken aktivieren
$mail->SMTPAuth   = $SMTPAuth;
$mail->Username   = $mailusername;
$mail->Password   = $mailpassword;
// To load the French version
$mail->setLanguage('de');

$mail->SetFrom($frommail, $fromname);

$mail->AddAddress($recvaddress, $recvadrname);

$mail->Subject = '=?UTF-8?B?' . base64_encode("Solar-Log - [SN:". $seriennummer ."] - Ertragsübersicht vom ". $date->format('d.m.Y - H:i:s')) . '?=';
$mail->Body    = $resString;
$mail->AltBody = 'You need a HTML E-Mail Client';
/*
if(!$mail->Send()) {
  echo "Mailer Error: " . $mail->ErrorInfo;
} else {
  echo "Message sent!";
}
*/

//echo "Ende". $result ."<br>\r\n";
//exit("Test 13");
