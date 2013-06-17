<?php
include("secret_values.php");//$database_username, $rpcuser, etc.

if ($use_select_user) {
  $link=mysql_connect("localhost",$database_select_username,$database_select_pass)or trigger_error(mysql_error());
}
else {
  $link=mysql_connect("localhost",$database_username,$database_pass)or trigger_error(mysql_error());
}
mysql_select_db($database_name)or die(msyql_error());

try {
  require_once 'jsonRPCClient.php';
  $bitcoin = new jsonRPCClient('http://'.$rpcuser.':'.$rpcpassword.'@'.$rpcip.':'.$rpcport);
  $bitcoin->getinfo();//I haven't taken the time to figure out the error-handling system of bitcoind
  //easier to just try a call and catch the exception to detect failure, for now
  //before long it might be best to switch to another bitcoin transaction handler--bitcoind seems pretty clumsy in some ways
}
catch (Exception $e) {
  echo "can't connect to bitcoind.";
  die();
}

include("common_functions.php");

//constants
$tip_fee = satoshis_to_nsats(0.01);//fee in nsats to send a tip. 1 is just a placeholder for now, and will change to roughly pay back server processing costs
$data_fee = satoshis_to_nsats(0.02);
$withdraw_fee = satoshis_to_nsats(0.001);
$deposit_min_conf = 0;//will be changed to a higher number before production release
$tx_fee_nsats = btc_to_nsats(0.0005);
?>