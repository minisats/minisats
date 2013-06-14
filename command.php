<?php
include("common.php");
if (!( isset($_REQUEST['id_address']) && isset($_REQUEST['command']) && isset($_REQUEST['signed']))) {
  die("eNot enough vars defined");
}

$id_address = $_REQUEST['id_address'];
$raw_command = urldecode($_REQUEST['command']);
$signed = urldecode($_REQUEST['signed']);

if (!validate_address($bitcoin,$id_address)) {
  die("eInvalid id_address");
}

$query = "SELECT * FROM `accounts` WHERE id_address = \"".mysql_real_escape_string($id_address)."\"";
$account_result = mysql_query($query)or die("e".mysql_error());
if (!($account_assoc = mysql_fetch_assoc($account_result))) {
  die("eNo account with that id_address found");
}

if (!verify_message($bitcoin,$id_address,$signed,$raw_command)) {
  die("eCommand signature verify failed: ".$signed);
}

$command = explode("&",$raw_command);

$increment_data = $command[0];
$query = "SELECT increment_data FROM `commands` WHERE id_address = '".$id_address."' ORDER BY increment_data DESC LIMIT 1";
$result = mysql_query($query)or die("e".mysql_error());
if ($row = mysql_fetch_row($result)) {//if there are any commands from this address already
  $last_increment_data = $row[0];
  if ($increment_data <= $last_increment_data) {
    die("eHigher increment_data has already been stored: ".$last_increment_data);
  }
}

$query = "INSERT INTO `commands` (id_address, increment_data, command, signed) VALUES (\"".mysql_real_escape_string($id_address)."\", \"".mysql_real_escape_string($increment_data)."\", \"".mysql_real_escape_string($raw_command)."\", \"".mysql_real_escape_string($signed)."\")";
mysql_query($query)or die("e".mysql_error());
$command_id = mysql_insert_id();
echo "s".$command_id."&";//signal that the command has been stored, but not necessarily executed

if ($command[1]=="data") {
  $data = urldecode($command[2]);
  $signed_data = urldecode($command[3]);
  if (!verify_message($bitcoin,$id_address,$signed_data,$data)) {
    die("eData signature verify failed: ".$signed_data);
  }
  
  if (!deduct_funds($id_address,$data_fee)) {
    die("eNot enough funds");//don't forget the tx fee: if you have 2 BTC you can't actually sent 2 BTC, but instead 1.99999....
  }
  
  $query = "INSERT INTO `data` (id_address,command_id,data,signed) values(\"".$id_address."\", \"".$command_id."\", \"".mysql_real_escape_string($data)."\", \"".mysql_real_escape_string($signed_data)."\")";
  mysql_query($query)or die("e".mysql_error());
  
  echo "s".mysql_insert_id();
}
/* else if ($command[1]=="dataurl") {
  $curl_address = urldecode($command[2]);
  $signed = $command[3];
  
  $ch = curl_init();
  curl_setopt($ch,CURLOPT_URL,$command);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
  $output = curl_exec($ch);
  curl_close($ch);
  
  if (!verify_message($bitcoin,$id_address,urldecode($signed),$output)) {
    die("eData signature verify failed: ".urldecode($signed));
  }
  
  $query = "INSERT INTO `data` (data,signed) VALUES ('".$output."', '".$signed."')";
  mysql_query($query)or die("e".mysql_error());
  echo "s".mysql_insert_id();
}*/
elseif ($command[1]=="tip") {
  $to_address = $command[2];
  if (!validate_address($bitcoin,$to_address)) {
    die("eInvalid to_address");
  }
  $nsats = abs((int)$command[3]);
  $data_id = $command[4];
  if (!deduct_funds($id_address,$nsats+$tip_fee)) {
    die("eDeduction failed");//don't forget the tx fee: if you have 2 BTC you can't actually sent 2 BTC, but instead 1.99999....
  }
  $query = "SELECT * FROM `accounts` WHERE id_address = \"".mysql_real_escape_string($to_address)."\"";
  $result = mysql_query($query)or die("eadd_funds:".mysql_error());
  if (mysql_num_rows($result)==0) {
    add_account($bitcoin,$to_address);
  }
  if (!add_funds($to_address,$nsats)) {
    add_funds($id_address,$nsats+$tip_fee);//send back to id_address before dying and refund fee
    die("eDeposit failed; amount returned to id_address");
  }
  $query = "INSERT INTO `tips` (from_id_address,to_id_address,amount,data_id,command_id) VALUES (\"".mysql_real_escape_string($id_address)."\", \"".mysql_real_escape_string($to_address)."\", ".$nsats.", \"".mysql_real_escape_string($data_id)."\", \"".mysql_real_escape_string($command_id)."\")";
  mysql_query($query)or die("e".$query." --- ".mysql_error());
  echo "s".mysql_insert_id();
}
elseif ($command[1]=="withdraw") {
  $amount = $command[2];
  if ($amount=="all") {
    $nsats = bcsub($account_assoc['balance'],$tx_fee_nsats);
  }
  else {
    $nsats = (int)$amount;
  }
  $nsats = nsats_floored_to_satoshi($nsats);//Can't send them everything, but send them everything we can
  if ($nsats <= satoshis_to_nsats(5430)) {//to avoid a nonstandard transaction as defined at https://github.com/bitcoin/bitcoin/pull/2577
    die("eWithdraw too small");
  }
  if (!deduct_funds($id_address,bcadd($nsats,$tx_fee_nsats))) {
    die("eDeduct failed");
  }
  $txid = $bitcoin->sendtoaddress($id_address,nsats_to_btc(bcsub($nsats,$tx_fee_nsats)));
  echo "s".$txid;
}
else {
  echo "eCommand not recognized: ".$command[1];
}
?>