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

$data= json_decode(file_get_contents('php://input'), true);

if(!isset($data)) {
    exit("ERROR");
}

$client_id= "*your client_id from Google*";
$client_secret= "*your client secret from Google*";
$webhook= "*your_URL_webhook/notif.php*";
$idChannelFieldNios4= "*your id channel field name in Info table on Nios4*";
$resourceIdFieldNios4= "*your resource id field name in Info table on Nios4*";
$tokenFieldNios4= "*your token name field in Info table on Nios4*";
$refreshTokenFieldNios4= "*your refresh token field name in Info table on Nios4*";


$startDate= $data["startDate"];
$oreInizio= $data["oreInizio"];
$minutiInizio= $data["minutiInizio"];
$secondiInizio= $data["secondiInizio"];
$endDate= $data["endDate"];
$oreFine= $data["oreFine"];
$minutiFine= $data["minutiFine"];
$secondiFine= $data["secondiFine"];
$title= $data["title"];
$description= $data["description"];
$idEvent= $data["idEvent"];
$tokenCalendar= $data["tokenCalendar"];
$refresh_token= $data["refreshToken"];
$calendarName= $data["calendarName"];
$token= $data["token_nios4"];
$db= $data["db"];
$gguid= $data["gguid"];
$gguidInfo= $data["gguidInfo"];
$tablename= $data["tableName"];
$idChannel= $data["idChannel"];
$resourceId= $data["resourceId"];



/////////////////////////////////////////METHODS//////////////////////////////////////////////////////
//
function saveChannelAndResource($database, $tokenNios4, $idRigaInfo, $idCanale, $idRisorsa, $IDChannelField, $resourceIDField) {
    $urlSaveCR= "https://web.nios4.com/ws/?action=table_save&db=".$database."&tablename=info&token=".$tokenNios4;

    $dataSaveCR= json_encode(array(
        "rows" => array(
            [
                "gguid" => $idRigaInfo,
                $IDChannelField => $idCanale,
                $resourceIDField => $idRisorsa
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


//function get calendar list
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
//function get a new token with the refresh token
function refreshToken($refreshToken, $IDClient, $ClientSecret) {
    $urlRefreshToken= "https://oauth2.googleapis.com/token";

    $chRefreshToken= curl_init();

    $dataRefreshToken= "client_id=".$IDClient."&"
                        . "client_secret=".$ClientSecret."&"
                        . "grant_type=refresh_token&"
                        . "refresh_token=".$refreshToken;

    curl_setopt($chRefreshToken, CURLOPT_URL, $urlRefreshToken);
    curl_setopt($chRefreshToken, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chRefreshToken, CURLOPT_POST, true);
    curl_setopt($chRefreshToken, CURLOPT_POSTFIELDS, $dataRefreshToken);

    $responseRefreshToken= curl_exec($chRefreshToken);
    $responseRefreshToken= json_decode($responseRefreshToken);
    curl_close($chRefreshToken);
    
    return $responseRefreshToken;
}
//end function
//function save the token and the refresh token inside nios4
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
//function convert date from dd/MM//YY format to YYYY-MM-ddTHH:mm:ss format
function convertData($date, $ora, $minuti, $secondi) {
    $dataArray= explode("/", $date);
    $dataFormat= $dataArray[2]."-".$dataArray[1]."-".$dataArray[0];
    
    if(strlen($ora) == 1) {
        $ora= "0".$ora;
    }
    
    if(strlen($minuti) == 1) {
        $minuti= "0".$minuti;
    }
    
    if(strlen($secondi) == 1) {
        $secondi= "0".$secondi;
    }
    
    $tempo= $ora.":".$minuti.":".$secondi;
    
    return $dataFormat."T".$tempo;
}
//end function
///////////////////////////////////////END METHODS/////////////////////////////////////////////////////

//force syncro
$urlSync= "https://web.nios4.com/ws/?action=sync&db=".$db."&token=".$token;

$chSync= curl_init();
curl_setopt($chSync, CURLOPT_URL, $urlSync);
curl_setopt($chSync, CURLOPT_RETURNTRANSFER, true);

$responseSync= curl_exec($chSync);
curl_close($chSync);

//calendar list
$responseCalendarList= calendarList($tokenCalendar);

//if i get some errors maybe the calendar token or the idchannel is expired
if(array_key_exists("error", $responseCalendarList)) {
    //get a new token with the refresh token
    $responseRefresh= refreshToken($refresh_token, $client_id, $client_secret);
    $tokenCalendar= $responseRefresh->access_token;
    
    //save the new token inside nios4
    saveToken($db, $token, $gguidInfo, $tokenCalendar, $refresh_token, $tokenFieldNios4, $refreshTokenFieldNios4);
    
    //get the calenda list, so i can get the id calendar
    $responseCalendarList= calendarList($tokenCalendar);
    
    $calendarList= $responseCalendarList->items;

    $idCalendar= "";
    foreach ($calendarList as $key => $value) {
        if($value->summary == $calendarName)
            $idCalendar= $value->id;
    }
    
    //new watch.. first i delete the previous one
    $urlStop= "https://www.googleapis.com/calendar/v3/channels/stop";

    $dataStop= json_encode(array(
        "id" => $idChannel,
        "resourceId" => $resourceId
    ));

    $chStop= curl_init();

    curl_setopt($chStop, CURLOPT_URL, $urlStop);
    curl_setopt($chStop, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chStop, CURLOPT_POST, true);
    curl_setopt($chStop, CURLOPT_POSTFIELDS, $dataStop);
    curl_setopt($chStop, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$tokenCalendar.", Content-Type: application/json"]);

    $responseStop= curl_exec($chStop);
    curl_close($chStop);
    
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
    
    saveChannelAndResource($db, $token, $gguidInfo, $idChannel, $resourceId, $idChannelFieldNios4, $resourceIdFieldNios4);
} else {
    
    $calendarList= $responseCalendarList->items;

    $idCalendar= "";
    foreach ($calendarList as $key => $value) {
        if($value->summary == $calendarName)
            $idCalendar= $value->id;
    } 
}



//watch the value of the id event 
if($idEvent == "") {
    //the event doesn't exist. I have to add it on Google Calendar
    $urlNewEvent= "https://www.googleapis.com/calendar/v3/calendars/".$idCalendar."/events";
    
    $dataNewEvent= json_encode(array(
        "summary" => $title,
        "description" => $description,
        "start" => array(
            "dateTime" => convertData($startDate, $oreInizio, $minutiInizio, $secondiInizio),
            "timeZone" => "Europe/Rome"
        ),
        "end" => array(
            "dateTime" => convertData($endDate, $oreFine, $minutiFine, $secondiFine),
            "timeZone" => "Europe/Rome"
        ),
        "extendedProperties" => array(
            "private" => array(
                "Nios4" => $gguid
            )
        )
    ));
    
    $chNewEvent= curl_init();
    curl_setopt($chNewEvent, CURLOPT_URL, $urlNewEvent);
    curl_setopt($chNewEvent, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chNewEvent, CURLOPT_POST, true);
    curl_setopt($chNewEvent, CURLOPT_POSTFIELDS, $dataNewEvent);
    curl_setopt($chNewEvent, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$tokenCalendar]);
    
    $responseNewEvent= json_decode(curl_exec($chNewEvent));
    curl_close($chNewEvent);
    
    $idEvent= $responseNewEvent->id;
    
} else {
    //The event already exist in Google Calendar. Edit the event.
    $urlUpdateEvent= "https://www.googleapis.com/calendar/v3/calendars/".$idCalendar."/events/".$idEvent;
    
    $dataUpdateEvent= json_encode(array(
        "summary" => $title,
        "description" => $description,
        "start" => array(
            "dateTime" => convertData($startDate, $oreInizio, $minutiInizio, $secondiInizio),
            "timeZone" => "Europe/Rome"
        ),
        "end" => array(
            "dateTime" => convertData($endDate, $oreFine, $minutiFine, $secondiFine),
            "timeZone" => "Europe/Rome"
        )
    ));
    
    $chUpdateEvent= curl_init();
    curl_setopt($chUpdateEvent, CURLOPT_URL, $urlUpdateEvent);
    curl_setopt($chUpdateEvent, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chUpdateEvent, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($chUpdateEvent, CURLOPT_POST, true);
    curl_setopt($chUpdateEvent, CURLOPT_POSTFIELDS, $dataUpdateEvent);
    curl_setopt($chUpdateEvent, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$tokenCalendar]);
    
    $responseUpdateEvent= curl_exec($chUpdateEvent);
    curl_close($chUpdateEvent);
    
}
