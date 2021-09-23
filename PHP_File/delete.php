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
$webhook= "*your_URL_webhook/notif.php?idc=idCalendar*";
$idChannelFieldNios4= "*your id channel field name in Info table on Nios4*";
$resourceIdFieldNios4= "*your resource id field name in Info table on Nios4*";
$tokenFieldNios4= "*your token name field in Info table on Nios4*";
$refreshTokenFieldNios4= "*your refresh token field name in Info table on Nios4*";

$tokenCalendar= $data["tokenGoogle"];
$refresh_token= $data["refreshToken"];
$db= $data["db"];
$token= $data["tokenNios4"];
$gguidInfo= $data["gguidInfo"];
$idEvent= $data["idCalendarEvent"];
$idCalendar= $data["idCalendar"];
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
//function get a new token with refresh token
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
///////////////////////////////////////END METHODS/////////////////////////////////////////////////////


//calendar list
$responseCalendarList= calendarList($tokenCalendar);

//if i get a error maybe the calendar token is expired
if(array_key_exists("error", $responseCalendarList)) {
    //get a new token with the refresh token    
    $responseRefresh= refreshToken($refresh_token, $client_id, $client_secret);
    $tokenCalendar= $responseRefresh->access_token;
    
    //save the new token inside nios4 
    saveToken($db, $token, $gguidInfo, $tokenCalendar, $refresh_token, $tokenFieldNios4, $refreshTokenFieldNios4);
    
    //get new watch.. first delete the previous one
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
    
}


//delete the event

$urlDelete= "https://www.googleapis.com/calendar/v3/calendars/".$idCalendar."/events/".$idEvent;

$chDelete= curl_init();
curl_setopt($chDelete, CURLOPT_URL, $urlDelete);
curl_setopt($chDelete, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chDelete, CURLOPT_CUSTOMREQUEST, "DELETE");
curl_setopt($chDelete, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$tokenCalendar]);

$responseDelete= curl_exec($chDelete);
curl_close($chDelete);

exit("OK");
