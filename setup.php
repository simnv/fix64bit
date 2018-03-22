<?php

define('FIX64BIT_REALM_ID', '254');

function plugin_init_fix64bit() {
  global $plugin_hooks;

  // This is where you hook into the plugin archetecture
  $plugin_hooks['config_arrays']['fix64bit']            = 'fix64bit_config_arrays';
  $plugin_hooks['poller_bottom']['fix64bit']            = 'fix64bit_poller_bottom';
  $plugin_hooks['graph_buttons']['fix64bit']            = 'fix64bit_graph_buttons';
  $plugin_hooks['graph_buttons_thumbnails']['fix64bit'] = 'fix64bit_graph_buttons';
  $plugin_hooks['config_settings']['fix64bit']          = 'fix64bit_config_settings';
  $plugin_hooks['graphs_action_array']['fix64bit']      = 'fix64bit_graphs_action_array';
  $plugin_hooks['graphs_action_prepare']['fix64bit']    = 'fix64bit_graphs_action_prepare';
  $plugin_hooks['graphs_action_execute']['fix64bit']    = 'fix64bit_graphs_action_execute';

}

function plugin_fix64bit_install() {
  api_plugin_register_hook('fix64bit', 'config_arrays',            'fix64bit_config_arrays',         "setup.php");
  api_plugin_register_hook('fix64bit', 'config_settings',          'fix64bit_config_settings',       "setup.php");
  api_plugin_register_hook('fix64bit', 'poller_bottom',            'fix64bit_poller_bottom',         "setup.php");
  api_plugin_register_hook('fix64bit', 'graph_buttons',            'fix64bit_graph_buttons',         "setup.php");
  api_plugin_register_hook('fix64bit', 'graph_buttons_thumbnails', 'fix64bit_graph_buttons',         "setup.php");
  api_plugin_register_hook('fix64bit', 'graphs_action_array',      'fix64bit_graphs_action_array',   "setup.php");
  api_plugin_register_hook('fix64bit', 'graphs_action_prepare',    'fix64bit_graphs_action_prepare', "setup.php");
  api_plugin_register_hook('fix64bit', 'graphs_action_execute',    'fix64bit_graphs_action_execute', "setup.php");

  fix64bit_setup_table();
}

function plugin_fix64bit_uninstall () {
  /* Do any extra Uninstall stuff here */
}

function plugin_fix64bit_check_config () {
  /* Here we will check to ensure everything is configured */
  fix64bit_check_upgrade();
  return true;
}

function plugin_fix64bit_upgrade () {
  /* Here we will upgrade to the newest version */
  fix64bit_check_upgrade();
  return false;
}

function plugin_fix64bit_version () {
  return fix64bit_version();
}

function fix64bit_check_upgrade () {
  global $config;

  $files = array('index.php', 'plugins.php', 'fix64bit.php');
  if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files)) {
    return;
  }

  $current = plugin_fix64bit_version();
  $current = $current['version'];
  $old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='fix64bit'");
  if (sizeof($old) && $current != $old["version"]) {
    /* if the plugin is installed and/or active */
    if ($old["status"] == 1 || $old["status"] == 4) {
      /* re-register the hooks */
      plugin_fix64bit_install();

      /* perform a database upgrade */
      fix64bit_database_upgrade();
    }

    /* update the plugin information */
    $info = plugin_fix64bit_version();
    $id   = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='fix64bit'");
    db_execute("UPDATE plugin_config
      SET name='" . $info["longname"] . "',
      author='"   . $info["author"]   . "',
      webpage='"  . $info["homepage"] . "',
      version='"  . $info["version"]  . "'
      WHERE id='$id'");
  }
}

function fix64bit_database_upgrade () {
}

function fix64bit_check_dependencies() {
  global $plugins, $config;

  return true;
}

function fix64bit_config_settings () {
  global $tabs, $settings;

  /* check for an upgrade */
  plugin_fix64bit_check_config();

  /* populate graph templates list */
  if (!isset($_SESSION['fix64bit_graphtemplates'])) {
    $fix64bit_graphtemplate = array();
    $sql_result = db_fetch_assoc("SELECT DISTINCT id, name
                    FROM graph_templates
                    ORDER BY name");
    if(sizeof($sql_result)) {
      foreach($sql_result as $sq) {
        $fix64bit_graphtemplates[$sq['id']] = $sq['name'];
      }
      $_SESSION['fix64bit_graphtemplates'] = $fix64bit_graphtemplates;
    }
  } else {
    $fix64bit_graphtemplates = $_SESSION['fix64bit_graphtemplates'];
  }
  /* find graph template id, try to get by default id by hash if not present */
  $graph_template_id = read_config_option("fix64bit_graphtemplate");
  if (!isset($graph_template_id) || $graph_template_id == 0) {
    $graph_template_id = db_fetch_cell("SELECT id FROM graph_templates WHERE hash='5deb0d66c81262843dce5f3861be9966'");
    if(strlen($graph_template_id) == 0) {
      $graph_template_id = '0';
    }
  }

  /* populate data templates list */
  if (!isset($_SESSION['fix64bit_datatemplates'])) {
    $fix64bit_graphtemplate = array();
    $sql_result = db_fetch_assoc("SELECT DISTINCT id, name
                    FROM data_template
                    ORDER BY name");
    if(sizeof($sql_result)) {
      foreach($sql_result as $sq) {
        $fix64bit_datatemplates[$sq['id']] = $sq['name'];
      }
      $_SESSION['fix64bit_graphtemplates'] = $fix64bit_graphtemplates;
    }
  } else {
    $fix64bit_datatemplates = $_SESSION['fix64bit_datatemplates'];
  }
  /* find data template id, try to get by default id by hash if not present */
  $data_template_id = read_config_option("fix64bit_datatemplate");
  if (!isset($data_template_id) || $data_template_id == 0) {
    $data_template_id = db_fetch_cell("SELECT id FROM data_template WHERE hash='6632e1e0b58a565c135d7ff90440c335'");
    if(strlen($data_template_id) == 0) {
      $data_template_id = '0';
    }
  }

  /* populate snmp queries list */
  $fix64bit_snmpquery = array();
  $sql_result = db_fetch_assoc("SELECT DISTINCT id, name
                  FROM snmp_query_graph
                  WHERE graph_template_id=" . $graph_template_id
                  . " ORDER BY name ");
  if(sizeof($sql_result)) {
    foreach($sql_result as $sq) {
      $fix64bit_snmpquery[$sq['id']] = $sq['name'];
    }
  }
  /* find snmp query id to search, try to get by default id by hash if not present */
  $fix64bit_snmpquery_search = read_config_option("fix64bit_snmpquery");
  if (!isset($fix64bit_snmpquery_search) || $fix64bit_snmpquery_search == 0) {
    $fix64bit_snmpquery_search = db_fetch_cell("SELECT id FROM snmp_query_graph WHERE hash='ae34f5f385bed8c81a158bf3030f1089'
                               AND graph_template_id=" . $graph_template_id);
    if(strlen($fix64bit_snmpquery_search) == 0) {
      $fix64bit_snmpquery_search = '0';
    }
  }

  /* find desired snmp query id, try to get by default id by hash if not present */
  $fix64bit_snmpquery_desired = read_config_option("fix64bit_snmpquery_desired");
  if (!isset($fix64bit_snmpquery_desired) || $fix64bit_snmpquery_desired == 0) {
    $fix64bit_snmpquery_desired = db_fetch_cell("SELECT id FROM snmp_query_graph WHERE hash='1e16a505ddefb40356221d7a50619d91'
                               AND graph_template_id=" . $graph_template_id);
    if(strlen($fix64bit_snmpquery_desired) == 0) {
      $fix64bit_snmpquery_desired = '0';
    }
  }

  /* populate data input list */
  $fix64bit_data_input = array();
  $sql_result = db_fetch_assoc("SELECT DISTINCT data_input_fields.id, data_input_fields.name
              FROM data_input_fields, data_template_data
              WHERE data_template_data.local_data_id=0
                    AND data_input_fields.data_input_id=data_template_data.data_input_id
                    AND data_template_data.data_template_id=" . $data_template_id
                  . " ORDER BY data_input_fields.name ");
  if(sizeof($sql_result)) {
    foreach($sql_result as $sq) {
      $fix64bit_data_input[$sq['id']] = $sq['name'];
    }
  }

  /* find data input id, try to get by default id by hash if not present */
  $data_input_id = read_config_option("fix64bit_data_input");
  if (!isset($data_input_id) || $data_input_id == 0) {
    $data_input_id = db_fetch_cell("SELECT id FROM data_input_fields WHERE hash='e6deda7be0f391399c5130e7c4a48b28'");
    if(strlen($data_input_id) == 0) {
      $data_input_id = '0';
    }
  }

  $tabs["misc"] = "Misc";

  $temp = array(
    "fix64bit_header" => array(
      "friendly_name" => "Fix 64bit Counters",
      "method" => "spacer",
      ),
    "fix64bit_graphtemplate" => array(
      "friendly_name" => "Graph Template",
      "description" => "Graph template with desired Traffic Graph.
                    <br/>You should select \"Interface - Traffic (bits/sec)\" here.",
      "method" => "drop_array",
      "default" => $graph_template_id,
      "array" => $fix64bit_graphtemplates,
      ),
    "fix64bit_snmpquery" => array(
      "friendly_name" => "SNMP Query to Search",
      "description" => "Search for this type of SNMP query.
                    <br/>(In/Out Bits)",
      "method" => "drop_array",
      "default" => $fix64bit_snmpquery_search,
      "array" => $fix64bit_snmpquery,
      ),
    "fix64bit_snmpquery_desired" => array(
      "friendly_name" => "Desired SNMP Query",
      "description" => "Set this SNMP query.
                    <br/>(In/Out Bits (64-bit Counters))",
      "method" => "drop_array",
      "default" => $fix64bit_snmpquery_desired,
      "array" => $fix64bit_snmpquery,
      ),
    "fix64bit_datatemplate" => array(
      "friendly_name" => "Data Template",
      "description" => "Data template with desired Traffic Graph.
                    <br/>You should select \"Interface - Traffic\" here.",
      "method" => "drop_array",
      "default" => $data_template_id,
      "array" => $fix64bit_datatemplates,
      ),
    "fix64bit_data_input" => array(
      "friendly_name" => "Data Input",
      "description" => "Search for this data input.
                    <br/>(Output Type ID)",
      "method" => "drop_array",
      "default" => $data_input_id,
      "array" => $fix64bit_data_input,
      ),
    "fix64bit_rrdtool_max" => array(
      "friendly_name" => "New RRDTool Maximum",
      "description" => "Set new RRDTool maximum to this value.",
      "method" => "textbox",
      "default" => "1000000000",
      "max_length" => 64,
      "size" => 40,
      ),
  );

  if (isset($settings["misc"])) {
    $settings["misc"] = array_merge($settings["misc"], $temp);
  }else {
    $settings["misc"] = $temp;
  }
}

function fix64bit_config_arrays () {
  global $user_auth_realms, $messages, $user_auth_realm_filenames;

  $realm_id                    = FIX64BIT_REALM_ID;
  $user_auth_realms[$realm_id] = 'Plugin -> fix64bit';
  $user_auth_realm_filenames['fix64bit.php'] = $realm_id;

  if (isset($_SESSION['fix64bit_message']) && $_SESSION['fix64bit_message'] != '') {
    $messages['fix64bit_message'] = array('message' => $_SESSION['fix64bit_message'], 'type' => 'info');
  }
}

function fix64bit_poller_bottom () {
  global $config, $database_default;

  include_once($config["library_path"] . "/database.php");
  include_once($config["library_path"] . "/rrd.php");
  include_once($config["library_path"] . "/utility.php");
  include_once($config["library_path"] . "/template.php");

  $data_local = db_fetch_assoc("SELECT local_data_id, rrd_maximum FROM plugin_fix64bit");
  foreach($data_local as $ds) {
    $host_description = db_fetch_cell("SELECT host.description
          FROM host, data_local
          WHERE host.id=data_local.host_id
                AND data_local.id=". $ds['local_data_id'] . " LIMIT 1");
    $data_template_rrd = db_fetch_assoc("SELECT * FROM data_template_rrd
                       WHERE data_template_rrd.data_template_id=" . read_config_option("fix64bit_datatemplate") . "
                       AND local_data_id=" . $ds['local_data_id']);
    $rrd = get_data_source_path($ds['local_data_id'], true);
    cacti_log("Found file ". $rrd, false, "FIX64BIT");
		$data_template_rrd_count = sizeof($data_template_rrd);
    if($data_template_rrd_count > 0 && strlen($rrd) > 0) {
      $fix64bit_success = FALSE;
      foreach($data_template_rrd as $dtr) {
        cacti_log("Fixing ". $host_description .".".  $ds['local_data_id'] .".". $dtr['data_source_name'], false, "FIX64BIT");
        db_execute("UPDATE data_template_rrd SET rrd_maximum='". $ds['rrd_maximum']
                           ."' WHERE id=" . $dtr['id']);

        cacti_log("Fixing ". $rrd .":". $dtr['data_source_name'], false, "FIX64BIT");
        rrdtool_execute("tune ". addslashes($rrd) ." -a ". $dtr['data_source_name'] .":" . $ds['rrd_maximum'],
                              FALSE, RRDTOOL_OUTPUT_STDOUT);
        db_execute("UPDATE data_input_data, data_template_data SET data_input_data.value='".
                           read_config_option("fix64bit_snmpquery_desired")
                           ."' WHERE data_input_data.data_template_data_id=data_template_data.id AND data_input_data.data_input_field_id=".
                           read_config_option("fix64bit_data_input")
                           ." AND data_template_data.local_data_id=" . $ds['local_data_id']);

        $fix64bit_success = TRUE;
      }
      if($fix64bit_success) {
				cacti_log("Resetting RRD counters in ". $rrd, false, "FIX64BIT");
				cacti_log("update ". addslashes($rrd) . " N" . str_repeat(":U", $data_template_rrd_count), false, "FIX64BIT");
				rrdtool_execute("update ". addslashes($rrd) . " N" . str_repeat(":U", $data_template_rrd_count), FALSE, RRDTOOL_OUTPUT_STDOUT);
        cacti_log("Updating poller cache for ". $host_description .".".  $ds['local_data_id'], false, "FIX64BIT");
        update_poller_cache($ds['local_data_id'], true);
        db_execute("DELETE from plugin_fix64bit WHERE local_data_id=". $ds['local_data_id']);
      }
    }
  }
}

function fix64bit_graph_buttons($args) {
  global $config;

  if (api_plugin_user_realm_auth("fix64bit.php")) {
    $local_graph_id = $args[1]['local_graph_id'];
    #echo "<!-- fix64bit_data_input=" . read_config_option("fix64bit_data_input") . "-->\n";
    #echo "<!-- fix64bit_snmpquery=" . read_config_option("fix64bit_snmpquery") . "-->\n";
    $may_fix = db_fetch_cell("SELECT COUNT(DISTINCT(data_input_data.data_template_data_id))
               FROM data_template_rrd, graph_templates_item, data_template_data, data_input_data
               WHERE data_template_rrd.data_template_id=" . read_config_option("fix64bit_datatemplate") . "
                     AND data_template_rrd.id=graph_templates_item.task_item_id
                     AND data_template_data.local_data_id=data_template_rrd.local_data_id
                     AND data_input_data.data_template_data_id=data_template_data.id
                     AND data_input_data.data_input_field_id=" . read_config_option("fix64bit_data_input") . "
                     AND data_input_data.value=" . read_config_option("fix64bit_snmpquery") . "
                     AND graph_templates_item.local_graph_id=" . $local_graph_id);

    if ($may_fix > 0) {
      echo "<a href='"
        . $config['url_path']."plugins/fix64bit/fix64bit.php?local_graph_id=" . $local_graph_id
        . "' onclick=\"window.open('".$config['url_path']."plugins/fix64bit/fix64bit.php?local_graph_id=". $local_graph_id
        ."', 'fix64popup_".$local_graph_id."', 'toolbar=no,menubar=no,location=no,scrollbars=no,status=no,titlebar=no,width=650,height=360'); return false;\"><img src='"
        . $config['url_path']
        . "plugins/fix64bit/vector.png' border='0' alt='Fix 64 Counters' title='Fix 64 Counters' style='padding: 3px;'></a><br/>";
      fix64bit_setup_table();
    }
  }

}

function fix64bit_setup_table() {
  global $config, $database_default;

  include_once($config["library_path"] . "/database.php");

  /* realm_id */
  $realm_id = FIX64BIT_REALM_ID;

  /* tables for realtime */
  $result = db_fetch_assoc("SHOW TABLES LIKE 'plugin_fix64bit%%'");

  if (count($result) == 0) {
    db_execute('
      CREATE TABLE IF NOT EXISTS plugin_fix64bit (
        local_data_id mediumint(8) unsigned NOT NULL default \'0\',
        rrd_maximum varchar(19) NOT NULL default \'\',
        PRIMARY KEY  (local_data_id,rrd_maximum)
      ) ENGINE=MyISAM;
    ');
  }
}

function fix64bit_graphs_action_array($action) {
    /* Setup new dropdown action for Graph Management */
	$action['plugin_fix64bit'] = 'Fix 64bit Counters';
	return $action;
}

function fix64bit_check_graph($local_graph_id) {
  $host = db_fetch_assoc("SELECT host.id, host.description, host.snmp_version FROM host, graph_local WHERE host.id=graph_local.host_id AND graph_local.id=" . $local_graph_id);

  if (!sizeof($host)) {
    return "Cannot find host with this graph!";
  }

  $host = $host[0];

  if ($host['snmp_version'] < 2) {
    return "64 bit counters could not be used with SNMP version 1.<br/>
            Set at least SNMP v2 for this host:
            <a href='../../host.php?action=edit&id=". $host['id'] ."'>". sql_sanitize($host['description']) ."</a>.";
  }

  $may_fix = db_fetch_cell("SELECT COUNT(DISTINCT(data_input_data.data_template_data_id))
             FROM data_template_rrd, graph_templates_item, data_template_data, data_input_data
             WHERE data_template_rrd.data_template_id=" . read_config_option("fix64bit_datatemplate") . "
                   AND data_template_rrd.id=graph_templates_item.task_item_id
                   AND data_template_data.local_data_id=data_template_rrd.local_data_id
                   AND data_input_data.data_template_data_id=data_template_data.id
                   AND data_input_data.data_input_field_id=" . read_config_option("fix64bit_data_input") . "
                   AND data_input_data.value=" . read_config_option("fix64bit_snmpquery") . "
                   AND graph_templates_item.local_graph_id=" . $local_graph_id);

  if($may_fix < 1) {
    return "Not suitable or graph is already fixed.";
  }

  return false;
}

/**
 * fix64bit_graphs_action_prepare - perform fix64bit_graph prepare action
 * @param array $save - drp_action: selected action from dropdown
 *              graph_array: graphs titles selected from graph management's list
 *              graph_list: graphs selected from graph management's list
 * returns array $save				-
 *  */
function fix64bit_graphs_action_prepare($save) {
  global $colors, $config, $graph_timespans, $alignment;

  if ($save["drp_action"] == "plugin_fix64bit") { /* fix64bit */
    $return_code = false;
    if (isset($save["graph_array"])) {
      html_start_box("", "100%", $colors["header_panel"], "3", "center", "");
      /* list affected graphs */
      print "<tr>";
      print "<td class='textArea' valign='top' bgcolor='#" . $colors["form_alternate1"] . "'>" .
        "<p><strong>Are you sure you want to convert the following graphs to 64 bit counters?</strong></p><ul>";

      $error_graphs = array();
      $convert_count = 0;

      foreach ($save["graph_array"] as $local_graph_id) {
        input_validate_input_number($local_graph_id);
        $check_result = fix64bit_check_graph($local_graph_id);
        $url = $config["url_path"] . "graphs.php?action=graph_edit&id=$local_graph_id";
        $name = get_graph_title($local_graph_id);

        if(!$check_result) {
          print "<li><a href='" . $url . "'>" . $name . "</a></li>\n";
          $convert_count++;
        } else {
          $error_graphs[] = array("url" => $url,"name" => $name, "msg" => $check_result);
        }
      }

      if($convert_count < 1) {
        print "<li><strong>Nothing to convert</strong></li>";
      }

      print "</ul></td>";

      if (sizeof($error_graphs) > 0) {
        print "<td class='textArea' valign='top' bgcolor='#" . $colors["form_alternate1"] . "'>" .
        "<p><strong>These graphs can't be fixed:</strong></p><ul>";
        foreach ($error_graphs as $graph) {
          print "<li><dl><dt><a href='" . $url . "'>" . $graph["name"] . "</a></dt><dd>" . $graph["msg"] . "</dd></li>\n";
        }
        print "</ul></td>";
      }
      print "</tr>";

      html_end_box();

      $return_code = true;
    }

    return $return_code;
  } else {
    return $save;
  }
}

/**
 * fix64bit_graphs_action_execute - perform fix64bit_graph execute action
 * @param string $action - action to be performed
 * return -
 *  */
function fix64bit_graphs_action_execute($action) {
  global $config;

  if ($action == "plugin_fix64bit") { /* fix64bit */
    $message = '';
    $return_code = false;
    $convert_count = 0;

    if (isset($_POST["selected_items"])) {
      $selected_items = unserialize(stripslashes($_POST["selected_items"]));

      if (sizeof($selected_items)) {
        $error_graphs = array();
        $message .= "Scheduling to fix by poller:<br/>";

        foreach($selected_items as $local_graph_id) {
          input_validate_input_number($local_graph_id);

          $check_result = fix64bit_check_graph($local_graph_id);
          $url = $config["url_path"] . "graphs.php?action=graph_edit&id=$local_graph_id";
          $name = get_graph_title($local_graph_id);
  
          if(!$check_result) {
            #print "<li><a href='" . $url . "'>" . sql_sanitize($name) . "</a></li>\n";
            $data_local = db_fetch_assoc("SELECT DISTINCT data_local.* FROM data_local, data_template_rrd, graph_templates_item
                        WHERE data_local.id=data_template_rrd.local_data_id
                              AND data_template_rrd.id=graph_templates_item.task_item_id
                              AND data_template_rrd.data_template_id=" . read_config_option("fix64bit_datatemplate") . "
                              AND graph_templates_item.local_graph_id=" . $local_graph_id);
            
            if (!sizeof($data_local)) {
              $error_graphs[] = array("url" => $url,"name" => $name, "msg" => "<p><strong>Data sources not found.</strong></p>\n");
            } else {
              foreach($data_local as $ds) {
                $data_url = $config["url_path"] . "data_sources.php?action=ds_edit&id=" . $ds['id'];
                $message .= "<font size=-2><a href='" . $url . "'>" . $name ."</a>, datasource id <a href='". $data_url . "'>" . $ds['id'] . "</a></font><br/>";
                db_execute("INSERT INTO plugin_fix64bit(local_data_id, rrd_maximum) VALUES(". $ds['id'] .", '". read_config_option("fix64bit_rrdtool_max")."')");
                $convert_count++;
              }
            }

            $return_code = true;
          } else {
            $error_graphs[] = array("url" => $url,"name" => $name, "msg" => $check_result);
          }
        }

        if (sizeof($error_graphs) > 0) {
          $message .= "<br/>Following graphs could not be fixed:";
          foreach ($error_graphs as $graph) {
            $message .= "<dl><dt><font size=-2><a href='" . $url . "'>" . $graph["name"] . "</a></font></dt><dd><font size=-2>" . $graph["msg"] . "</font></dd>";
          }
        }
      }
    }

    if($convert_count < 1) {
      $message = "<h3>No graphs converted.</h3>" . $message;
    }

    if (strlen($message)) {
      $_SESSION['fix64bit_message'] = $message;
      raise_message('fix64bit_message');
    }

    return $return_code;
  } else {
    return $action;
  }
}

function fix64bit_version () {
  return array(
    'name'     => 'fix64bit',
    'version'  => '0.5',
    'longname' => 'Fix 64bit Counters',
    'author'   => 'Valeriy Simonov',
    'homepage' => 'https://github.com/simnv/fix64bit',
    'email'    => 'simonov@gmail.com',
    'url'      => 'https://github.com/simnv/fix64bit'
  );
}

?>
