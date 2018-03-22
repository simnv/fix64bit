<?php

$guest_account = true;

chdir('../..');
include_once("./include/auth.php");
include_once("./include/global.php");
include_once("./lib/utility.php");
include_once("./lib/template.php");
include_once("./lib/functions.php");
include_once("./lib/rrd.php");

global $realtime_refresh, $realtime_window, $realtime_sizes;

/* ================= input validation ================= */
input_validate_input_number(get_request_var_request("local_graph_id"));
/* ==================================================== */

?>
<html>
<head>
  <title>Cacti - Fix 64 counters</title>
  <link href="../../include/main.css" rel="stylesheet"/>
</head>
<body style="text-align: center; padding: 5px 0px 5px 0px; margin: 5px 0px 5px 0px;">
<div style="text-align: left; margin: 8px">
<?php
$errmsg = '';

$host = db_fetch_assoc("SELECT host.id, host.description, host.snmp_version FROM host, graph_local WHERE host.id=graph_local.host_id AND graph_local.id=" . $_REQUEST['local_graph_id']);

if (!sizeof($host)) {
  $errmsg =  "<p><strong>Cannot find host with this graph!</strong></p>\n";
}

if(strlen($errmsg) < 1) {
  $host = $host[0];

  if ($host['snmp_version'] < 2) {
    $errmsg =  "<p><strong>64 bit counters could not be used with SNMP version 1.<br/>
            Set at least SNMP v2 for this host:
            <a href='../../host.php?action=edit&id=". $host['id'] ."'>". $host['description'] ."</a>.</strong></p>\n";
  }
}

if(strlen($errmsg) < 1) {
  $fixed = db_fetch_cell("SELECT COUNT(DISTINCT(data_input_data.data_template_data_id))
                 FROM data_template_rrd, graph_templates_item, data_template_data, data_input_data
                 WHERE data_template_rrd.data_template_id=" . read_config_option("fix64bit_datatemplate") . "
                       AND data_template_rrd.id=graph_templates_item.task_item_id
                       AND data_template_data.local_data_id=data_template_rrd.local_data_id
                       AND data_input_data.data_template_data_id=data_template_data.id
                       AND data_input_data.data_input_field_id=" . read_config_option("fix64bit_data_input") . "
                       AND data_input_data.value=" . read_config_option("fix64bit_snmpquery") . "
                       AND graph_templates_item.local_graph_id=" . $_REQUEST['local_graph_id']);
  if($fixed < 1) {
    $errmsg =  "<p><strong>Already fixed.</strong></p>\n";
  }
}

if(strlen($errmsg) < 1) {
  $data_local = db_fetch_assoc("SELECT DISTINCT data_local.* FROM data_local, data_template_rrd, graph_templates_item
              WHERE data_local.id=data_template_rrd.local_data_id
                    AND data_template_rrd.id=graph_templates_item.task_item_id
                    AND data_template_rrd.data_template_id=" . read_config_option("fix64bit_datatemplate") . "
                    AND graph_templates_item.local_graph_id=" . $_REQUEST['local_graph_id']);

  if (!sizeof($data_local)) {
    $errmsg =  "<p><strong>Data source not found.</strong></p>\n";
  }
}

if(strlen($errmsg) > 0) {
  print $errmsg;
} else {
  print "<p><strong>All prerequisites are met.</strong></p>";
  if (isset($_POST["action"]) && $_POST["action"] == "do") {
    foreach($data_local as $ds) {
      print "<p><strong>Scheduling " . $host['description'] .".".  $ds['id'] . " to fix by poller.</strong></p>";
      db_execute("INSERT INTO plugin_fix64bit(local_data_id, rrd_maximum) VALUES(". $ds['id'] .", '". read_config_option("fix64bit_rrdtool_max")."')");
    }
    print "<p><a href='#' onClick='javascript:window.close()'>Close</a>.</p>";
  } else {
?>
  <form name='confirm' method='post'>
  <input type='hidden' name='action' value='do'>
  <p><strong>Do you really want to convert datasource for this graph?</strong></p>
  <p><a href='#' onClick='javascript:window.close()'><img src='../../images/button_no.gif' alt='No' align='absmiddle' border='0'></a>
  <input type='image' src='../../images/button_yes.gif' alt='Yes' align='absmiddle'></p>
<?php
  print "<p><img class='graphimage' src='../../graph_image.php?action=view&local_graph_id=". $_REQUEST['local_graph_id'] ."' border='0' alt=''> </p>";
?>
  </form>
<?php
  }
}
?>
</div>
</body>
</html>
