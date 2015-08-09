#!/usr/bin/php
<?php
//error_reporting(E_ALL & E_STRICT);

$options = array(
	"sipgate" => array(
		"user" => "sipgateuser",
		"pass" => "sipgatepass"
	),
	"xmpp" => array(
		"host" => "localhost",
		"port" => 5222,
		"user" => "phonewatch",
		"domain" => "jabber.example.com",
		"pass" => "password"
	),
	"xmpprecipient" => "me@jabber.example.com",
	"clicktelapikey" => "abcdefg123456"
);

/*******************************************************/

include dirname(__FILE__).'/xmlrpc-2.2.2/lib/xmlrpc.inc';
include dirname(__FILE__).'/XMPPHP/XMPP.php';

$conn = new XMPPHP_XMPP($options['xmpp']['host'], $options['xmpp']['port'], $options['xmpp']['user'], $options['xmpp']['pass'], 'PhoneWatch', $options['xmpp']['domain'], $printlog=false, $loglevel=XMPPHP_Log::LEVEL_INFO);

################

$periodStart = time() - 300;
$periodEnd   = time();


//create XMLPRC client
$rpcurl = "https://".$options['sipgate']['user'].":".$options['sipgate']['pass']."@samurai.sipgate.net:443/RPC2";
$xmlrpc_client = new xmlrpc_client($rpcurl);
$xmlrpc_client->setSSLVerifyPeer(FALSE);


//define parameters for request
$params = new xmlrpcval();
$params->addStruct(
	array(
		//start date and time for demanded listing
		"PeriodStart" => new xmlrpcval(
			iso8601_encode($periodStart),
			"dateTime.iso8601"
		),

		//end date and time for demanded listing
		"PeriodEnd" => new xmlrpcval(
			iso8601_encode($periodEnd),
			"dateTime.iso8601"
		),

		//list of status that the history listing is to be filtered by
		"StatusList" => new xmlrpcval(
			array(
				new xmlrpcval("missed", "string")
			),
			"array"
		),
	)
);


$msg = new xmlrpcmsg("samurai.HistoryGetByDate", array($params));
$resp = $xmlrpc_client->send($msg);

$hist = $resp->value()->me['struct']['History']->me['array'];

//print_r($hist);

if(count($hist)){
    $conn->connect();
    $conn->processUntil('session_start');
    $conn->presence();

    foreach($hist AS $obj){
        $call = $obj->me['struct'];

        unset($temp);

        $temp['id']    = $call['EntryID']->me['string'];
        $temp['timestamp'] = strtotime($call['Timestamp']->me['string']);
        $temp['status']    = $call['Status']->me['string'];

        $num = $call['RemoteUri']->me['string'];
        preg_match("/sip:([0-9]+)@/s", $num, $matches);
        $temp['num'] = $matches[1];

        $jabmsg = "Verpasster Anruf\nUhrzeit: ".date("d.m.Y - H:i:s", $temp['timestamp'])." Uhr\nNummer: ".$temp['num']."\n";

	$teljson = file_get_contents("http://openapi.klicktel.de/searchapi/invers?key=".$options['clicktelapikey']."&number=0".substr($temp['num'],2));
	$telarray = json_decode($teljson);

	$telres = $telarray->response->results[0]->entries[0];

	if($telres){
		$telstring = "Telefonbuch: ".$telres->displayname.", ".$telres->location->street." ".$telres->location->streetnumber.", ".$telres->location->zipcode." ".$telres->location->city;
		$jabmsg .= $telstring;
	}

        $conn->message($options['xmpprecipient'], $jabmsg);
    }

    $conn->disconnect();
}
