<?php
include("common.php");
?>
<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript">
var id_address,deposit_address;
var balance;
function unformat_balance(amount,denomination) {//returns the amount of nsats represented by the argument pair
  if (denomination == "BTC") return amount*100000000000000000;
  else if (denomination == "MSats") return amount*1000000000000000;
  else if (denomination == "KSats") return amount*1000000000000;
  else if (denomination == "Satoshis") return amount*1000000000;
  else if (denomination == "mSats") return amount*1000000;
  else if (denomination == "uSats") return amount*1000;
  else return amount;
}
function format_balance(nsats) {//formats the amount of nsats to be a bit easier to read
  var value;
  var denomination;
  if (nsats >= 100000000000000000) {
    value = nsats / 100000000000000000;
    denomination = "BTC";
  }
  else if (nsats >= 1000000000000000) {
    value = nsats / 1000000000000000;
    denomination = "MSats";
  }
  else if (nsats >= 1000000000000) {
    value = nsats / 1000000000000;
    denomination = "KSats";
  }
  else if (nsats >= 1000000000) {
    value = nsats / 1000000000;
    denomination = "Satoshis";
  }
  else if (nsats >= 1000000) {
    value = nsats / 1000000;
    denomination = "mSats";
  }
  else if (nsats >= 1000) {
    value = nsats / 1000;
    denomination = "uSats";
  }
  else {
    value = nsats;
    denomination = "nSats";
  }
  return "<a style='font-size:20px'>"+value+"</a> "+denomination;
}
function refresh_info() {//doesn't fetch anything, but just displays the stored values appropriately
  if (deposit_address != undefined && deposit_address != null) {
    $('#deposit_address_div').html("Deposit Address: "+deposit_address);
    $('#balance_div').html(format_balance(balance));
  }
  else {
    $('#deposit_address_div').html("");
    $('#balance_div').html("");
  }
}
function load_account(address) {//given an ID address, get account info, load appropriate vars, and call refresh_info()
  id_address = address;
  $('#balance_div').html("<a style='color:blue'>Querying...</a>");
  $.post("get_account.php",{id_address : id_address})
  .done(function(data) {
    if (data[0]=="e") {
      alert("Error: "+data.substring(1));
      deposit_address = null;
      balance = null;
    }
    else if (data[0]=="s") {
      data = data.substring(1).split(",");
      deposit_address = data[0];
      balance = parseFloat(data[1]);
    }
    refresh_info();
  });
}
$(document).ready(function() {
  $('#id_address_input').keypress(function(e) {
    var code = (e.keyCode ? e.keyCode : e.which);
    if(code == 13) { //Enter keycode
      if ($('#id_address_input').val()=="") {
        alert("No ID address specified");
      }
      else load_account($('#id_address_input').val());
    }
  });
  $('#generate_tip_button').click(function() {
    var to_address = $('#tip_to_address_input').val();
    var amount = $('#tip_amount_input').val();
    var denomination = $('#tip_denomination_input').val();
    var memo = $('#memo_input').val();
    if (to_address == "") {
      alert("No to-address specified");
      return;
    }
    if (amount == "") {
      alert("No amount specified");
      return;
    }
    var command = "tip," + to_address + "," + unformat_balance(amount,denomination).toString() + "," + encodeURIComponent(memo);
    $('#command_input').val(encodeURIComponent(command));
  });
  $('#generate_withdraw_button').click(function() {
    var amount = $('#withdraw_amount_input').val();
    var denomination = $('#withdraw_denomination_input').val();
    if (amount == "") {
      alert("No amount specified");
      return;
    }
    var command = "withdraw,";
    if (amount=="all") {
      command = command + "all";
    }
    else if (!isNaN(amount)) {
      command = command + unformat_balance(amount,denomination).toString();
    }
    else {
      alert("Invalid entry for amount");
      return;
    }
    $('#command_input').val(encodeURIComponent(command));
  });
  $('#send_command_button').click(function() {
    id_address = $('#id_address_input').val();
    if (id_address == "") {
      alert("No id address specified");
      return;
    }
    var command = $('#command_input').val();
    if (command == "") {
      alert("No command specified");
      return;
    }
    var signed = encodeURIComponent($('#signed_command_input').val());
    $.post("command.php",{id_address : id_address , command : command , signed : signed})
    .done(function(data) {
      if (data[0]=="e") {
        alert("Error: "+data.substring(1));
      }
      else if (data[0]=="s") {
        $('#command_input').val("");
        $('#signed_command_input').val("");
        var returned = data.substring(1);
        alert("Command successful! Returned "+returned);
        load_account(id_address);//to refresh balance
      }
    });
  });
  var disclaimer = [
  "This is a very early version of the minisats project!",
  "",
  "For example, the maximum an account can hold is 18.xx BTC.",
  "Obviously that's not the intended cap, but it's something that works for now.",
  "Any feedback is welcome at reddit.com/r/minisatsproject/"
  ].join("\n");
  alert(disclaimer);
});
</script>
<html>
<head>
</head>
<body>
<center>
<br><br>
Your ID Address (input and press enter):<br>
<input type='text' id='id_address_input' size=45><br>
<br>
<div id='balance_div'></div>
<div id='deposit_address_div'></div>
<table id='actions_table' border=1 width='100%'><tr width='100%'>
<td width='50%' style='text-align:center'>
<a style='font-size:24px'>Send Tip</a> (Fee: <?php echo $tip_fee; ?> nSats)<br>
<br>
Receiver's ID Address: <input id='tip_to_address_input' type='text' size=45><br>
Amount: <input id='tip_amount_input' type='text'>
<select id='tip_denomination_input'>
<option value="BTC">BTC</option>
<option value="MSats">MSats</option>
<option value="KSats">KSats</option>
<option value="Satoshis">Satoshis</option>
<option value="mSats">mSats</option>
<option value="uSats">uSats</option>
<option value="nSats">nSats</option>
</select>
<br>
Memo: <textarea id='memo_input' rows='4' cols='40'></textarea><br>
<input id='generate_tip_button' type='button' value='Generate Tip Command'>
</td>
<td width='50%' style='text-align:center;vertical-align:top'>
<a style='font-size:24px'>Withdraw</a><br>
<br>
Amount (or enter "all"): <input id='withdraw_amount_input' type='text'>
<select id='withdraw_denomination_input'>
<option value="BTC">BTC</option>
<option value="MSats">MSats</option>
<option value="KSats">KSats</option>
<option value="Satoshis">Satoshis</option>
<option value="mSats">mSats</option>
<option value="uSats">uSats</option>
<option value="nSats">nSats</option>
</select>
<br>
<input id='generate_withdraw_button' type='button' value='Generate Withdraw Command'>
</td>
</tr></table>
<div id ='command_area' width=100% style='text-align:center'>
Command:<br><textarea id='command_input' rows="4" cols="40"></textarea><br>
Signed Command:<br><textarea id='signed_command_input' rows="4" cols="40"></textarea><br>
<input id='send_command_button' type='button' value='Send Command'>
</div>
</center>
</body>
</html>