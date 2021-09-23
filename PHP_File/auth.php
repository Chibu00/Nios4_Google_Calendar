<?php
/*
Copyright of Chibuzo Udoji 2021
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE
*/


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
$idCalendarFieldMySql= "*the field name of your id calendar info column on your mySQL*";
$tokenFieldNios4= "*your token name field in Info table on Nios4*";
$refreshTokenFieldNios4= "*your refresh token field name in Info table on Nios4*";
$syncTokenFieldNios4= "*your sync token field name in Info table on Nios4*";
$idChannelFieldNios4= "*your id channel field name in Info table on Nios4*";
$resourceIdFieldNios4= "*your resource id field name in Info table on Nios4*";

$token= $data["tokenNios4"];
$db= $data["db"];
$code= $data["code"];
$gguidInfo= $data["gguid_info"];
$idCalendar= $data["idCalendario"]
$idChannel= $data["idChannel"];
$resourceId= $data["resourceId"];
$tablename= $data["tableName"];


///////////////////////////////////////METHODS////////////////////////////////////////////////////////
//function list of the calendars in Google Calendar
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
//end function
//function save token and refresh token inside nios4
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
//end function
//function save idchannel and resourceid
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
//end function
////////////////////////////////////END METHODS////////////////////////////////////////////////////////





//force syncro
$urlSync= "https://web.nios4.com/ws/?action=sync&db=".$db."&token=".$token;

$chSync= curl_init();
curl_setopt($chSync, CURLOPT_URL, $urlSync);
curl_setopt($chSync, CURLOPT_RETURNTRANSFER, true);

$responseSync= curl_exec($chSync);
curl_close($chSync);

//find token and refresh token
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

if($idCalendar == "") {
    exit("NOCALENDAR");
}

//stop channel if exist
if($idChannel != "" || $resourceId != "") {
    $urlStop= "https://www.googleapis.com/calendar/v3/channels/stop";

    $dataStop= json_encode(array(
        "id" => $idChannel,
        "resourceId" => $resourceId,
    ));
    
    $chStop= curl_init();
    curl_setopt($chStop, CURLOPT_URL, $urlStop);
    curl_setopt($chStop, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chStop, CURLOPT_POST, true);
    curl_setopt($chStop, CURLOPT_POSTFIELDS, $dataStop);
    curl_setopt($chStop, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$tokenCalendar.", Content-Type: application/json"]);
    
    $responseStop= curl_exec($chStop);
    curl_close($chStop);
}

//create a new channel
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

//find the synctoken: It's usefull for the next events change.
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

//write some info in my own db, so i can catch this info for notif.php
$sql= "SELECT * FROM ".$tableNameMySql." WHERE ".$databaseFieldMySql."='$db' AND ".$tokenNios4FieldMySql."='$token'";
$resultset= $conn->query($sql);
$righe= mysqli_fetch_all($resultset, MYSQLI_ASSOC);

if(count($righe) == 0) {
    $sql2= "INSERT INTO ".$tableNameMySql." (".$tokenNios4FieldMySql.", ".$databaseFieldMySql.", ".$refreshTokenFieldMySql.", ".$tableNameFieldMySql.", ".$idCalendarFieldMySql.") "
            . "VALUES ('$token', '$db', '$refresh_token', '$tablename', '$idCalendar')";
    
    $resultset2= $conn->query($sql2);
} else {
    $idRiga= $righe[0][$idFieldMySql];
    
    $sql3= "UPDATE ".$tableNameMySql." "
            . "SET ".$refreshTokenFieldMySql."='".$refresh_token."' "
            . "WHERE ".$idFieldMySql."=$idRiga";
    
    $resultset3= $conn->query($sql3);
}

exit("OK");
