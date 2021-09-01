<?php
include("connessione.php");
$data= json_decode(file_get_contents('php://input'), true);

if(!isset($data)) {
    exit("ERROR");
}

$client_id= "*your client_id from Google*";
$client_secret= "*your client secret from Google*";
$webhook= "*your_URL_webhook/notif.php*";
$tableNameMySql= "*the name of the table on your mySQL*";
$databaseFieldMySql= "*the field name of your database name column on your mySQL*";
$tokenNios4FieldMySql= "*the field name of your token Nios4 column on your mySQL*";
$refreshTokenFieldMySql= "*the field name of your refresh token column on your mySQL*";
$idFieldMySql= "*the field name of your id column on your mySQL*";
$tableNameFieldMySql= "*the field name of your table name column on your mySQL*";
$tokenFieldNios4= "*your token name field in Info table on Nios4*";
$refreshTokenFieldNios4= "*your refresh token field name in Info table on Nios4*";
$syncTokenFieldNios4= "*your sync token field name in Info table on Nios4*";
$idChannelFieldNios4= "*your id channel field name in Info table on Nios4*";
$resourceIdFieldNios4= "*your resource id field name in Info table on Nios4*";

$token= $data["tokenNios4"];
$db= $data["db"];
$code= $data["code"];
$gguidInfo= $data["gguid_info"];
$calendarName= $data["calendarName"];
$idChannel= $data["idChannel"];
$resourceId= $data["resourceId"];
$tablename= $data["tableName"];


///////////////////////////////////////METODI////////////////////////////////////////////////////////
//funzione lista dei calendari su google calendar
function calendarList($token_calendario) {
    $urlCalendarList= "https://www.googleapis.com/calendar/v3/users/me/calendarList";

    $chCalendarList= curl_init();

    curl_setopt($chCalendarList, CURLOPT_URL, $urlCalendarList);
    curl_setopt($chCalendarList, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chCalendarList, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$token_calendario]);

    $responseCalendarList= curl_exec($chCalendarList);
    $responseCalendarList= json_decode($responseCalendarList);
    curl_close($chCalendarList);
    
    return $responseCalendarList;
}
//fine funzione
//funzione di salvataggio del token e del refresh dentro nios4
function saveToken($database, $tokenNios4, $idRigaInfo, $tokenCalendario, $refreshToken, $tokenField, $refreshTokenField) {
    $urlSaveToken= "https://web.nios4.com/ws/?action=table_save&db=".$database."&tablename=info&token=".$tokenNios4;

    $dataSaveToken= json_encode(array(
        "rows" => array(
            [
                "gguid" => $idRigaInfo,
                $tokenField => $tokenCalendario,
                $refreshTokenField => $refreshToken
            ]
        )
    ));

    $chSaveToken= curl_init();
    curl_setopt($chSaveToken, CURLOPT_URL, $urlSaveToken);
    curl_setopt($chSaveToken, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chSaveToken, CURLOPT_POST, true);
    curl_setopt($chSaveToken, CURLOPT_POSTFIELDS, $dataSaveToken);

    $responseSaveToken= curl_exec($chSaveToken);
    curl_close($chSaveToken);
    
    
}
//fine funzione
//funzione di salvataggio del della isChannel e della resourceid
function saveChannelAndResource($database, $tokenNios4, $idRigaInfo, $idCanale, $idRisorsa, $tokenSincro, $IDChannelField, $resourceIDField, $syncTokenField) {
    $urlSaveCR= "https://web.nios4.com/ws/?action=table_save&db=".$database."&tablename=info&token=".$tokenNios4;

    $dataSaveCR= json_encode(array(
        "rows" => array(
            [
                "gguid" => $idRigaInfo,
                $IDChannelField => $idCanale,
                $resourceIDField => $idRisorsa,
                $syncTokenField => $tokenSincro
            ]
        )
    ));

    $chSaveCR= curl_init();
    curl_setopt($chSaveCR, CURLOPT_URL, $urlSaveCR);
    curl_setopt($chSaveCR, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chSaveCR, CURLOPT_POST, true);
    curl_setopt($chSaveCR, CURLOPT_POSTFIELDS, $dataSaveCR);

    $responseCR= curl_exec($chSaveCR);
    $responseCR= json_decode($responseCR);
    curl_close($chSaveCR);
    
}
//fine funzione
////////////////////////////////////FINE METODI////////////////////////////////////////////////////////





//prima forza la sincronizzazione
$urlSync= "https://web.nios4.com/ws/?action=sync&db=".$db."&token=".$token;

$chSync= curl_init();
curl_setopt($chSync, CURLOPT_URL, $urlSync);
curl_setopt($chSync, CURLOPT_RETURNTRANSFER, true);

$responseSync= curl_exec($chSync);
curl_close($chSync);

//ricavo il token e il refresh token
$url= "https://oauth2.googleapis.com/token";

$data= "client_id=".$client_id."&"
        . "client_secret=".$client_secret."&"
        . "code=".$code."&"
        . "grant_type=authorization_code&"
        . "redirect_uri=urn:ietf:wg:oauth:2.0:oob";

$ch= curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

$response= curl_exec($ch);
$response= json_decode($response);
curl_close($ch);

if(array_key_exists("error", $response)) {
    exit("NOCODE");
}

$tokenCalendar= $response->access_token;
$refresh_token= $response->refresh_token;

saveToken($db, $token, $gguidInfo, $tokenCalendar, $refresh_token, $tokenFieldNios4, $refreshTokenFieldNios4);

$responseCalendarList= calendarList($tokenCalendar);

//trovo id del calendario
$calendarList= $responseCalendarList->items;

$idCalendar= "";
foreach ($calendarList as $key => $value) {
    if($value->summary == $calendarName)
        $idCalendar= $value->id;
}

if($idCalendar == "") {
    exit("NOCALENDAR");
}

//prima stop del canale nel caso in cui esistesse
if($idChannel != "" || $resourceId != "") {
    $urlStop= "https://www.googleapis.com/calendar/v3/channels/stop";

    $dataStop= json_encode(array(
        "id" => $idChannel,
        "resourceId" => $resourceId,
    ));
}

//creo il channel
$urlWatch= "https://www.googleapis.com/calendar/v3/calendars/".$idCalendar."/events/watch";

$chWatch= curl_init();

$dataWatch= array(
    "id" => "ea7eccef-b1a7-4fc8-84e3-779ce0a66164",
    "type" => "web_hook",
    "address" => $webhook
);

$dataWatch= json_encode($dataWatch);

curl_setopt($chWatch, CURLOPT_URL, $urlWatch);
curl_setopt($chWatch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chWatch, CURLOPT_POST, true);
curl_setopt($chWatch, CURLOPT_POSTFIELDS, $dataWatch);
curl_setopt($chWatch, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$tokenCalendar]);

$responseWatch= curl_exec($chWatch);
$responseWatch= json_decode($responseWatch);
curl_close($chWatch);

$idChannel= $responseWatch->id;
$resourceId= $responseWatch->resourceId;

//mi ricavo il synctoken che mi serve per sapere che tipo di variazione è stata fatta al calendario
$urlListEvents= "https://www.googleapis.com/calendar/v3/calendars/".$idCalendar."/events";

$chListEvents= curl_init();
curl_setopt($chListEvents, CURLOPT_URL, $urlListEvents);
curl_setopt($chListEvents, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chListEvents, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$tokenCalendar]);

$responseListEvents= curl_exec($chListEvents);
$responseListEvents= json_decode($responseListEvents);
curl_close($chListEvents);

$syncToken= $responseListEvents->nextSyncToken;

saveChannelAndResource($db, $token, $gguidInfo, $idChannel, $resourceId, $syncToken, $idChannelFieldNios4, $resourceIdFieldNios4, $syncTokenFieldNios4);

//scrivo alcuni parametri in un mio db in modo tale da riprendere queste informazioni in notif.php
$sql= "SELECT * FROM ".$tableNameMySql." WHERE ".$databaseFieldMySql."='$db' AND ".$tokenNios4FieldMySql."='$token'";
$resultset= $conn->query($sql);
$righe= mysqli_fetch_all($resultset, MYSQLI_ASSOC);

if(count($righe) == 0) {
    $sql2= "INSERT INTO ".$tableNameMySql." (".$tokenNios4FieldMySql.", ".$databaseFieldMySql.", ".$refreshTokenFieldMySql.", ".$tableNameFieldMySql.") "
            . "VALUES ('$token', '$db', '$refresh_token', '$tablename')";
    
    $resultset2= $conn->query($sql2);
} else {
    $idRiga= $righe[0][$idFieldMySql];
    
    $sql3= "UPDATE ".$tableNameMySql." "
            . "SET ".$refreshTokenFieldMySql."='".$refresh_token."' "
            . "WHERE ".$idFieldMySql."=$idRiga";
    
    $resultset3= $conn->query($sql3);
}

exit("OK");
