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

$client_id= "*your client_id from Google*";
$client_secret= "*your client secret from Google*";
$customerTable= "*your customer table name*";
$idChannelFieldNios4= "*your id channel field name in Info table on Nios4*";
$resourceIdFieldNios4= "*your resource id field name in Info table on Nios4*";
$tableNameMySql= "*the name of the table on your mySQL*";
$databaseFieldMySql= "*the field name of your database name column on your mySQL*";
$tokenNios4FieldMySql= "*the field name of your token Nios4 column on your mySQL*";
$refreshTokenFieldMySql= "*the field name of your refresh token column on your mySQL*";
$tableNameFieldMySql= "*the field name of your table name column on your mySQL*";
$refreshTokenFieldNios4= "*your refresh token field name in Info table on Nios4*";
$calendarNameFieldNios4= "*your calendar name field name in Info table on Nios4*";
$tokenFieldNios4= "*your token name field in Info table on Nios4*";
$syncTokenFieldNios4= "*your sync token field name in Info table on Nios4*";
$titleFieldNios4= "*your title field name in Info table on Nios4*";
$descriptionFieldNios4= "*your description field name in Info table on Nios4*";
$webhook= "*your_URL_webhook/notif.php*";
$idCalendarEventFieldNios4= "*your id calendar event field name in your event table on Nios4*";
$dataFieldNios4= "*field name of the date on your events Nios4 Table*";
$startDataFieldNios4= "*field name of the start date event on your events Nios4 table*";
$endDataFieldNios4= "*field name of the start date event on your events Nios4 table*";
        

///////////////////////////METODI/////////////////////////////////////
//function per la generazione di un gguid
function randomGguid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
//end function

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
//funzione per la converasione della data da Google Calendar a Nios4
function dataConversionFromGCtoNios($data) {
    $data_ora= explode("T", $data);
    
    $dataArray= explode("-", $data_ora[0]);
    $dataOfficial= "";
    foreach ($dataArray as $keyD => $valueD) {
        $dataOfficial.= $valueD;
    }
    
    $oraArray= explode("+", $data_ora[1]);
    $HHmmss= explode(":", $oraArray[0]);
    $oraOfficial= "";
    foreach ($HHmmss as $keyH => $valueH) {
        $oraOfficial .= $valueH;
    }
    
    $dataConverted= $dataOfficial."".$oraOfficial;
    
    return $dataConverted;
}
//fine funzione
//funzione che mi restituisce la lista dei campi di una cera tabella
function tableInfoFields($database, $nome_tabella, $token_nios4) {
    $url= "https://web.nios4.com/ws/?action=table_info&db=".$database."&tablename=".$nome_tabella."&token=".$token_nios4;
    
    $ch= curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response= json_decode(curl_exec($ch));
    curl_close($ch);
    
    $infoCampi= $response->fields;
    
    return $infoCampi;
}
//fine funzione
//funzione per trovare un cliente
function findCustomer($database, $nome_tabella_clienti, $token_nios4, $nome_campo_nominativo_cliente_dentro_cliente, $valore_del_campo_nominativo) {
    $urlModel= "https://web.nios4.com/ws/?action=model&db=".$database."&tablename=".$nome_tabella_clienti."&token=".$token_nios4;

    $chModel= curl_init();
    $dataModel= json_encode(array(
        "conditions" => array(
            $nome_campo_nominativo_cliente_dentro_cliente => $valore_del_campo_nominativo
        )
    ));

    curl_setopt($chModel, CURLOPT_URL, $urlModel);
    curl_setopt($chModel, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chModel, CURLOPT_POST, true);
    curl_setopt($chModel, CURLOPT_POSTFIELDS, $dataModel);

    $responseModel= json_decode(curl_exec($chModel));
    curl_close($chModel);

    $cliente= $responseModel->records;

    return $cliente;
}
//fine funzione
//funzione che ti aggiunge un nuovo cliente
function saveCustomer($database, $nome_tabella_clienti, $token_nios4, $gguidCliente, $campo_del_nome_cliente_dentro_tabella_clienti, $valore_nome_cliente) {
    $urlNewCustomer= "https://web.nios4.com/ws/?action=table_save&db=".$database."&tablename=".$nome_tabella_clienti."&token=".$token_nios4;
    $dataNewCustomer= json_encode(array(
        "rows" => array(
            [
                "gguid" => $gguidCliente,
                $campo_del_nome_cliente_dentro_tabella_clienti => $valore_nome_cliente
            ]
        )
    ));

    $chNewCustomer= curl_init();
    curl_setopt($chNewCustomer, CURLOPT_URL, $urlNewCustomer);
    curl_setopt($chNewCustomer, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chNewCustomer, CURLOPT_POST, true);
    curl_setopt($chNewCustomer, CURLOPT_POSTFIELDS, $dataNewCustomer);

    $responseNewCustomer= curl_exec($chNewCustomer);
    curl_close($chNewCustomer);
}
/////////////////////////////FINE METODI////////////////////////////////////




$sql= "SELECT * FROM ".$tableNameMySql."";
$resultset= $conn->query($sql);
$righe= mysqli_fetch_all($resultset, MYSQLI_ASSOC);

foreach ($righe as $keyR => $valueR) {
    $urlInfo= "https://web.nios4.com/ws/?action=model&db=".$valueR[$databaseFieldMySql]."&tablename=info&token=".$valueR[$tokenNios4FieldMySql];
    
    $chInfo= curl_init();
    curl_setopt($chInfo, CURLOPT_URL, $urlInfo);
    curl_setopt($chInfo, CURLOPT_RETURNTRANSFER, true);
    
    $responseInfo= curl_exec($chInfo);
    $responseInfo= json_decode($responseInfo);
    curl_close($chInfo);
    
    $info= $responseInfo->records[0];
    if(isset($info->$refreshTokenFieldNios4) && $info->$refreshTokenFieldNios4 == $valueR[$refreshTokenFieldMySql]) {
        $refresh_token= $info->$refreshTokenFieldNios4;
        $calendarName= $info->$calendarNameFieldNios4;
        $tokenCalendar= $info->$tokenFieldNios4;
        $resourceId= $info->$resourceIdFieldNios4;
        $idChannel= $info->$idChannelFieldNios4;
        $syncToken= $info->$syncTokenFieldNios4;
        $gguidInfo= $info->gguid;
        $title_field= $info->$titleFieldNios4;
        $description_field= $info->$descriptionFieldNios4;
        $db= $valueR[$databaseFieldMySql];
        $token= $valueR[$tokenNios4FieldMySql];
        $tablename= $valueR["$tableNameFieldMySql"];
        break;
    }
    
}

//prima forza la sincronizzazione
$urlSync= "https://web.nios4.com/ws/?action=sync&db=".$db."&token=".$token;

$chSync= curl_init();
curl_setopt($chSync, CURLOPT_URL, $urlSync);
curl_setopt($chSync, CURLOPT_RETURNTRANSFER, true);

$responseSync= curl_exec($chSync);
curl_close($chSync);

//lista dei calendari
$urlCalendarList= "https://www.googleapis.com/calendar/v3/users/me/calendarList";
$chCalendarList= curl_init();

curl_setopt($chCalendarList, CURLOPT_URL, $urlCalendarList);
curl_setopt($chCalendarList, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chCalendarList, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$tokenCalendar]);

$responseCalendarList= curl_exec($chCalendarList);
$responseCalendarList= json_decode($responseCalendarList);
curl_close($chCalendarList);

//se mi da un errore può darsi che il token del calendario sia scaduto oppure il watch sia scaduto
if(array_key_exists("error", $responseCalendarList)) {
    //faccio il refresh token in modo tale da avere il nuovo token
    $urlRefresh= "https://oauth2.googleapis.com/token";

    $chRefresh= curl_init();

    $dataRefresh= "client_id=".$client_id."&"
            . "client_secret=".$client_secret."&"
            . "grant_type=refresh_token&"
            . "refresh_token=".$refresh_token;

    curl_setopt($chRefresh, CURLOPT_URL, $urlRefresh);
    curl_setopt($chRefresh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chRefresh, CURLOPT_POST, true);
    curl_setopt($chRefresh, CURLOPT_POSTFIELDS, $dataRefresh);

    $responseRefresh= curl_exec($chRefresh);
    $responseRefresh= json_decode($responseRefresh);
    curl_close($chRefresh);
    
    $tokenCalendar= $responseRefresh->access_token;
    
    //salvo il nuovo token dentro Nios4
    $urlSaveToken= "https://web.nios4.com/ws/?action=table_save&db=".$db."&tablename=info&token=".$token;

    $dataSaveToken= json_encode(array(
        "rows" => array(
            [
                "gguid" => $gguidInfo,
                $tokenFieldNios4 => $tokenCalendar,
                $refreshTokenFieldNios4 => $refresh_token
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
    
    //faccio di nuovo la chiamata per avere la lista dei calendari in modo tale da prendermi id interessato
    $urlCalendarList= "https://www.googleapis.com/calendar/v3/users/me/calendarList";
    $chCalendarList= curl_init();

    curl_setopt($chCalendarList, CURLOPT_URL, $urlCalendarList);
    curl_setopt($chCalendarList, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chCalendarList, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$tokenCalendar]);

    $responseCalendarList= curl_exec($chCalendarList);
    $responseCalendarList= json_decode($responseCalendarList);
    curl_close($chCalendarList);
    
    $calendarList= $responseCalendarList->items;

    $idCalendar= "";
    foreach ($calendarList as $key => $value) {
        if($value->summary == $calendarName)
            $idCalendar= $value->id;
    }
    
    //nuovo watch..cancello prima quello precedente
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

//eventi che hanno subito una variazione/nuovo
$urlListEventsSync= "https://www.googleapis.com/calendar/v3/calendars/".$idCalendar."/events?syncToken=".$syncToken;

$chListEventSync= curl_init();
curl_setopt($chListEventSync, CURLOPT_URL, $urlListEventsSync);
curl_setopt($chListEventSync, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chListEventSync, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$tokenCalendar]);

$responseListEventSync= curl_exec($chListEventSync);
$responseListEventSync= json_decode($responseListEventSync);
curl_close($chListEventSync);

$eventsItems= $responseListEventSync->items;

//aggiorno il nuovo synctoken mettendo anche su Nios4
$syncToken= $responseListEventSync->nextSyncToken;
$urlSaveSyncToken= "https://web.nios4.com/ws/?action=table_save&db=".$db."&tablename=info&token=".$token;

$dataSaveSyncToken= json_encode(array(
    "rows" => array(
        [
            "gguid" => $gguidInfo,
            $syncTokenFieldNios4 => $syncToken,
        ]
    )
));

$chSaveSyncToken= curl_init();
curl_setopt($chSaveSyncToken, CURLOPT_URL, $urlSaveSyncToken);
curl_setopt($chSaveSyncToken, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chSaveSyncToken, CURLOPT_POST, true);
curl_setopt($chSaveSyncToken, CURLOPT_POSTFIELDS, $dataSaveSyncToken);

$responseSaveSyncToken= curl_exec($chSaveSyncToken);
curl_close($chSaveSyncToken);


foreach ($eventsItems as $keyItems => $valueItems) {
    if($valueItems->status == "cancelled") {
        //recupero il gguid dell'evento attraverso l'id del calendario
        $urlIdCalendar= "https://web.nios4.com/ws/?action=model&db=".$db."&tablename=".$tablename."&token=".$token;
        
        $dataIdCalendar= json_encode(array(
            "conditions" => array(
                $idCalendarEventFieldNios4 => $valueItems->id,
            )
        ));
        
        $chIdCalendar= curl_init();
        curl_setopt($chIdCalendar, CURLOPT_URL, $urlIdCalendar);
        curl_setopt($chIdCalendar, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chIdCalendar, CURLOPT_POST, true);
        curl_setopt($chIdCalendar, CURLOPT_POSTFIELDS, $dataIdCalendar);
        
        $responseIdCalendar= json_decode(curl_exec($chIdCalendar));
        curl_close($chIdCalendar);
        
        $gguidEvento= $responseIdCalendar->records[0]->gguid;
        
        //cancello l'evento su Nios4 facendo riferimento all'id del calendario
        $urlDeleteEvent= "https://web.nios4.com/ws/?action=table_save&db=".$db."&tablename=".$tablename."&token=".$token;
        
        $dataDeleteEvent= json_encode(array(
            "delete" => [
                $gguidEvento
            ]
        ));
        
        $chDeleteEvent= curl_init();
        curl_setopt($chDeleteEvent, CURLOPT_URL, $urlDeleteEvent);
        curl_setopt($chDeleteEvent, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chDeleteEvent, CURLOPT_POST, true);
        curl_setopt($chDeleteEvent, CURLOPT_POSTFIELDS, $dataDeleteEvent);
        
        $responseDeleteEvent= curl_exec($chDeleteEvent);
        curl_close($chDeleteEvent);
        
    } else {
        //ricavo tutte le righe della tabella da nios4 facendo la ricerca in base all'id dell'evento.
        $urlInterventiList= "https://web.nios4.com/ws/?action=model&db=".$db."&tablename=".$tablename."&token=".$token;
        
        $dataInterventiList= json_encode(array(
            "conditions" => array(
                $idCalendarEventFieldNios4 => $valueItems->id,
            )
        ));
        
        $chInterventiList= curl_init();
        curl_setopt($chInterventiList, CURLOPT_URL, $urlInterventiList);
        curl_setopt($chInterventiList, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chInterventiList, CURLOPT_POST, true);
        curl_setopt($chInterventiList, CURLOPT_POSTFIELDS, $dataInterventiList);
        
        $responseInterventiList= json_decode(curl_exec($chInterventiList));
        curl_close($chInterventiList);
        
        $interventiList= $responseInterventiList->records;
        
        if(count($interventiList) == 0) {
            //se la lista è vuota allora vuol dire che ho un nuovo evento
            //nuovo evento
            //serve per gestire la seconda chiamata a nios4 quando aggiungo un evento da nios4. In questo modo riesco a modificare il campo id_calendar_event senza far aggiunger un altra riga.
            if(isset($valueItems->extendedProperties->private->Nios4)) {
                
                $cliente= findCustomer($db, $customerTable, $token, $title_field, $valueItems->summary);
                
                if(count($cliente) != 0) {
                    $gguid_cliente= $cliente[0]->gguid;
                } else {
                    $gguid_cliente= randomGguid();
                    saveCustomer($db, $customerTable, $token, $gguid_cliente, $title_field, $valueItems->summary);
                }
                
                $urlUpdate= "https://web.nios4.com/ws/?action=table_save&db=".$db."&tablename=".$tablename."&token=".$token;

                $dataUpdate= json_encode(array(
                    "rows" => array(
                        [
                            "gguid" => $valueItems->extendedProperties->private->Nios4,
                            $dataFieldNios4 => dataConversionFromGCtoNios($valueItems->start->dateTime),
                            $startDataFieldNios4 => dataConversionFromGCtoNios($valueItems->start->dateTime),
                            $endDataFieldNios4 => dataConversionFromGCtoNios($valueItems->end->dateTime),
                            $idCalendarEventFieldNios4 => $valueItems->id,
                            "gguid_".$title_field => $gguid_cliente,
                            $title_field => $valueItems->summary,
                            $description_field => $valueItems->description
                        ]
                    )
                ));

                $chUpdate= curl_init();
                curl_setopt($chUpdate, CURLOPT_URL, $urlUpdate);
                curl_setopt($chUpdate, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chUpdate, CURLOPT_POST, true);
                curl_setopt($chUpdate, CURLOPT_POSTFIELDS, $dataUpdate);

                $responseUpdate= curl_exec($chUpdate);
                curl_close($chUpdate);
                
            } else {
                $cliente= findCustomer($db, $customerTable, $token, $title_field, $valueItems->summary);
                
                if(count($cliente) != 0) {
                    $gguid_cliente= $cliente[0]->gguid;
                } else {
                    $gguid_cliente= randomGguid();
                    saveCustomer($db, $customerTable, $token, $gguid_cliente, $title_field, $valueItems->summary);
                }
                
                $urlNewEvent= "https://web.nios4.com/ws/?action=table_save&db=".$db."&tablename=".$tablename."&token=".$token;

                $dataNewEvent= json_encode(array(
                    "rows" => array(
                        [
                            "gguid" => randomGguid(),
                            $dataFieldNios4 => dataConversionFromGCtoNios($valueItems->start->dateTime),
                            $startDataFieldNios4 => dataConversionFromGCtoNios($valueItems->start->dateTime),
                            $endDataFieldNios4 => dataConversionFromGCtoNios($valueItems->end->dateTime),
                            $idCalendarEventFieldNios4 => $valueItems->id,
                            "gguid_".$title_field => $gguid_cliente,
                            $title_field => $valueItems->summary,
                            $description_field => $valueItems->description
                        ]
                    )
                ));

                $chNewEvent= curl_init();
                curl_setopt($chNewEvent, CURLOPT_URL, $urlNewEvent);
                curl_setopt($chNewEvent, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chNewEvent, CURLOPT_POST, true);
                curl_setopt($chNewEvent, CURLOPT_POSTFIELDS, $dataNewEvent);

                $responseNewEvent= curl_exec($chNewEvent);
                curl_close($chNewEvent);
            }
            
        } else {
            //se la lista non è vuota vuol dire che me ne ha trovato UNO su Nios4 e quindi vuol dire che devo modificarlo
            $cliente= findCustomer($db, $customerTable, $token, $title_field, $valueItems->summary);
                
            if(count($cliente) != 0) {
                $gguid_cliente= $cliente[0]->gguid;
            } else {
                $gguid_cliente= randomGguid();
                saveCustomer($db, $customerTable, $token, $gguid_cliente, $title_field, $valueItems->summary);
            }
            
            
            $gguid_riga= $interventiList[0]->gguid;
            
            $urlUpdateEvent= "https://web.nios4.com/ws/?action=table_save&db=".$db."&tablename=".$tablename."&token=".$token;
            $dataUpdateEvent= json_encode(array(
                "rows" => array(
                    [
                        "gguid" => $gguid_riga,
                        "$dataFieldNios4" => dataConversionFromGCtoNios($valueItems->start->dateTime),
                        "$startDataFieldNios4" => dataConversionFromGCtoNios($valueItems->start->dateTime),
                        "$endDataFieldNios4" => dataConversionFromGCtoNios($valueItems->end->dateTime),
                        "gguid_".$title_field => $gguid_cliente,
                        $title_field => $valueItems->summary,
                        $description_field => $valueItems->description,
                        $idCalendarEventFieldNios4 => $valueItems->id
                    ]
                )
            ));

            $chUpdateEvent= curl_init();
            curl_setopt($chUpdateEvent, CURLOPT_URL, $urlUpdateEvent);
            curl_setopt($chUpdateEvent, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chUpdateEvent, CURLOPT_POST, true);
            curl_setopt($chUpdateEvent, CURLOPT_POSTFIELDS, $dataUpdateEvent);

            $responseUpdateEvent= curl_exec($chUpdateEvent);
            curl_close($chUpdateEvent);
        }

        //forzo la sincronizzazione
        $urlSync= "https://web.nios4.com/ws/?action=sync&db=".$db."&token=".$token;

        $chSync= curl_init();
        curl_setopt($chSync, CURLOPT_URL, $urlSync);
        curl_setopt($chSync, CURLOPT_RETURNTRANSFER, true);

        $responseSync= curl_exec($chSync);
        curl_close($chSync);
    }
}
