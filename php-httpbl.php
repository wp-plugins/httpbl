<?php
/*
Plugin Name: Project Honey Pot Http:BL
Plugin URI: http://omninoggin.com
Description: Project Honey Pot http:BL allows you to verify IP addresses of clients connecting to your blog against the <a href="http://www.projecthoneypot.org/?rf=45626">Project Honey Pot</a> database.
Author: Thaya Kareeson
Version: 1.0
Author URI: http://omninoggin.com
*/

/*
Copyright 2008 Thaya Kareeson (email : thaya.kareeson@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
  $php_httpbl_home = 'http://omninoggin.com/2008/07/03/project-honey-pot-httpbl-wordpress-plugin/';

  // ---------- Activation ---------- //
  function activate() {
    global $wpdb;
    // set default options on fresh install
    if ( get_option( 'php_httpbl_key' ) == NULL )
      update_option( 'php_httpbl_key' , 'abcdefghijkl' );
    if ( get_option( 'php_httpbl_age_thres' ) == NULL )
      update_option( 'php_httpbl_age_thres' , '14' );
    if ( get_option( 'php_httpbl_threat_thres' ) == NULL )
      update_option( 'php_httpbl_threat_thres' , '30' );
    if ( get_option( 'php_httpbl_hp' ) == NULL )
      update_option( 'php_httpbl_hp' , 'http://omninoggin.com/suspicious' );
    if ( get_option( 'php_httpbl_log' ) == NULL )
      update_option( 'php_httpbl_log' , true );
    for ($i = 0; pow(2, $i) <= 4; $i++) {
      $value = pow(2, $i);
      if ( get_option( 'php_httpbl_deny'.$value ) == NULL ) {
        $denied[$value] = update_option('php_httpbl_deny_'.$value, true);
      }
    }
    if ( get_option( 'php_httpbl_stats_link' ) == NULL )
      update_option( 'php_httpbl_stats_link' , 1 );
    if ( get_option( 'php_httpbl_not_logged_ips' ) == NULL )
      update_option( 'php_httpbl_not_logged_ips' , '127.0.0.1' );
    // create database table
    php_httpbl_create_log_table();
  }

  // ---------- Statistics ---------- //
  // Add a line to the log table
  function php_httpbl_add_log($ip, $user_agent, $response, $blocked)
  {
    global $wpdb;
    $time = gmdate('Y-m-d H:i:s',
      time() + get_option('gmt_offset') * 60 * 60 );
    $blocked = ($blocked ? 1 : 0);
    $user_agent = mysql_real_escape_string($user_agent);
    $query = "INSERT INTO ".$wpdb->prefix."php_httpbl_log ".
      "(ip, time, user_agent, php_httpbl_response, blocked)".
      " VALUES ( '$ip', '$time', '$user_agent',".
      "'$response', $blocked);";
    $results = $wpdb->query($query);
  }

  // Get latest 50 entries from the log table
  function php_httpbl_get_log()
  {
    global $wpdb;
    $query = "SELECT * FROM ".$wpdb->prefix.
      "php_httpbl_log ORDER BY id DESC LIMIT 50";
    return $wpdb->get_results($query);
  }

  // Get numbers of blocked and passed visitors from the log table
  // and place them in $php_httpbl_stats_data[]
  function php_httpbl_get_stats()
  {
    global $wpdb, $php_httpbl_stats_data;
    $query = "SELECT blocked,count(*) FROM ".$wpdb->prefix.
      "php_httpbl_log GROUP BY blocked";
    $results = $wpdb->get_results($query,ARRAY_N);
    if ($results) {
      foreach ($results as $row) {
        if ($row[0] == 1) {
          $php_httpbl_stats_data['blocked'] = $row[1];
        } else {
          $php_httpbl_stats_data['passed'] = $row[1];
        }
      }
    }
    else {
      $php_httpbl_stats_data['blocked'] = 0;
      $php_httpbl_stats_data['passed'] = 0;
    }
    $results = NULL;
  }

  // Display stats. Output may be configured at the plugin's config page.
  function php_httpbl_stats()
  {
    global $php_httpbl_stats_data, $php_httpbl_home;
    if ( get_option('php_httpbl_stats') ) {
      $pattern = get_option('php_httpbl_stats_pattern');
      $link = get_option('php_httpbl_stats_link');
      $search = array(
        '$block',
        '$pass',
        '$total'
        );
      $replace = array(
        $php_httpbl_stats_data['blocked'],
        $php_httpbl_stats_data['passed'],
        $php_httpbl_stats_data['blocked']+$php_httpbl_stats_data['passed']
        );
      $link_prefix = array(
        '',
        "<a href='$php_httpbl_home'>"
        );
      $link_suffix = array(
        '',
        '</a>'
        );
      echo $link_prefix[$link].
        str_replace($search, $replace, $pattern).
        $link_suffix[$link];
    }
  }

  // Check whether the table exists
  function php_httpbl_check_log_table()
  {
    global $wpdb;
    $result = $wpdb->get_results('SHOW TABLES');
    if ($result) {
      foreach ($result as $stdobject) {
        foreach ($stdobject as $table) {
          if ($wpdb->prefix.'php_httpbl_log' == $table) {
            return true;
          }
        }
      }
    }
    return false;
  }

  // Truncate the log table
  function php_httpbl_truncate_log_table()
  {
    global $wpdb;
    $sql = 'TRUNCATE '.$wpdb->prefix.'php_httpbl_log;';
    $wpdb->query($sql);
  }

  // Drop the log table
  function php_httpbl_drop_log_table()
  {
    global $wpdb;
    update_option('php_httpbl_log', false);
    update_option('php_httpbl_stats', false);
    $sql = 'DROP TABLE '.$wpdb->prefix.'php_httpbl_log;';
    $wpdb->query($sql);
  }

  // Create a new log table
  function php_httpbl_create_log_table()
  {
    global $wpdb;
    $sql = ''
      . 'CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.'php_httpbl_log ('
      . '  `id` INT( 6 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,'
      . '  `ip` VARCHAR( 16 ) NOT NULL DEFAULT \'unknown\' ,'
      . '  `time` DATETIME NOT NULL ,'
      . '  `user_agent` VARCHAR( 255 ) NOT NULL DEFAULT \'unknown\' ,'
      . '  `php_httpbl_response` VARCHAR( 16 ) NOT NULL ,'
      . '  `blocked` BOOL NOT NULL'
      . ')';
    $wpdb->query($sql);
  }

  // ---------- Visitor Verification ---------- //
  function php_httpbl_check_visitor()
  {
    $key = get_option( 'php_httpbl_key' );

    // The http:BL query
    $result = explode( '.', gethostbyname( $key . '.' .
      implode ( '.', array_reverse( explode( '.',
      $_SERVER['REMOTE_ADDR'] ) ) ) .
      '.dnsbl.httpbl.org' ) );

    // If the response is positive,
    if ( $result[0] == 127 ) {

      // Get thresholds
      $age_thres = get_option('php_httpbl_age_thres');
      $threat_thres = get_option('php_httpbl_threat_thres');

      for ($i = 0; pow(2, $i) <= 4; $i++) {
        $value = pow(2, $i);
        $denied[$value] = get_option('php_httpbl_deny_'
          . $value);
      }

      $hp = get_option('php_httpbl_hp');

      // Assume that visitor's OK
      $age = false;
      $threat = false;
      $deny = false;
      $blocked = false;

      if ( $result[1] < $age_thres )
        $age = true;
      if ( $result[2] > $threat_thres )
        $threat = true;
      foreach ( $denied as $key => $value ) {
        if ( ($result[3] - $result[3] % $key) > 0
          and $value)
          $deny = true;
      }

      // If he's not OK
      if ( $deny && $age && $threat ) {
        $blocked = true;

        // If we've got a Honey Pot link
        if ( $hp ) {
          header( 'HTTP/1.1 301 Moved Permanently ');
          header( "Location: $hp" );
        }

      }

      // Are we logging?
      if (get_option('php_httpbl_log') == true) {

        // At first we assume that the visitor
        // should be logged
        $log = true;

        // Checking if he's not one of those, who
        // are not logged
        $ips = explode(' ',
          get_option('php_httpbl_not_logged_ips'));
        foreach ($ips as $ip) {
          if ($ip == $_SERVER['REMOTE_ADDR'])
            $log = false;
        }

        // Don't log search engine bots
        if ($result[3] == 0) $log = false;

        // If we log only blocked ones
        if (get_option('php_httpbl_log_blocked_only')
          and !$blocked) {
          $log = false;
        }

        // If he can be logged, we log him
        if ($log)
          php_httpbl_add_log($_SERVER['REMOTE_ADDR'],
          $_SERVER['HTTP_USER_AGENT'],
          implode($result, '.'), $blocked);
      }
      if ($blocked) die();  // My favourite line.
    }
  }


  // ---------- Configuration Page ---------- //
  function php_httpbl_config_page()
  {
    add_options_page( 'Project Honey Pot Http:BL', 'PHP Http:BL',
      'manage_options', 'php-httpbl-config', 'php_httpbl_configuration' );
  }

  function php_httpbl_configuration()
  {
    global $php_httpbl_home;
    // If the save button was clicked...
    if (isset($_POST['php_httpbl_save'])) {
      // ...the options are updated.
      update_option('php_httpbl_key', $_POST['key'] );
      update_option('php_httpbl_age_thres', $_POST['age_thres'] );
      update_option('php_httpbl_threat_thres',
        $_POST['threat_thres'] );
      for ($i = 0; pow(2, $i) <= 4; $i++) {
        $value = pow(2, $i);
        $denied[$value] = update_option('php_httpbl_deny_'.
          $value, ($_POST['deny_'.$value] == 1 ?
          true : false));
      }
      update_option('php_httpbl_hp', $_POST['hp'] );
      update_option('php_httpbl_log',
        ( $_POST['enable_log'] == 1 ? true : false ));
      update_option('php_httpbl_log_blocked_only',
        ( $_POST['log_blocked_only'] == 1 ?
        true : false ));
      update_option('php_httpbl_not_logged_ips',
        $_POST['not_logged_ips'] );
      update_option('php_httpbl_stats',
        ( $_POST['enable_stats'] == 1 ? true : false ));
      update_option('php_httpbl_stats_pattern',
        $_POST['stats_pattern'] );
      update_option('php_httpbl_stats_link',
        $_POST['stats_link'] );
    }

    // Should we purge the log table?
    if (isset($_POST['php_httpbl_truncate']))
      php_httpbl_truncate_log_table();

    // Should we delete the log table?
    if (isset($_POST['php_httpbl_drop']))
      php_httpbl_drop_log_table();

    // Should we create a new log table?
    if (isset($_POST['php_httpbl_create']))
      php_httpbl_create_log_table();

    // If we log, but there's no table.
    if (get_option('php_httpbl_log') and !php_httpbl_check_log_table()) {
      php_httpbl_create_log_table();
    }

    // If it seems like the first launch,
    // few options should be set as defaults.
    if ( get_option( 'php_httpbl_key' ) == '' )
      update_option( 'php_httpbl_key' , 'abcdefghijkl' );
    if ( get_option( 'php_httpbl_age_thres' ) == 0 )
      update_option( 'php_httpbl_age_thres' , '14' );
    if ( get_option( 'php_httpbl_threat_thres' ) == 0 )
      update_option( 'php_httpbl_threat_thres' , '30' );
    if ( get_option( 'php_httpbl_stats_pattern' ) == '' )
      update_option( 'php_httpbl_stats_pattern' , '<strong>Project Honey Pot Statistics</strong><br/>\n$block of $total suspicious connections blocked' );

    // Get data to be displayed in the form.
    $key = get_option('php_httpbl_key');
    $threat_thres = get_option('php_httpbl_threat_thres');
    $age_thres = get_option('php_httpbl_age_thres');
    for ($i = 0; pow(2, $i) <= 4; $i++) {
      $value = pow(2, $i);
      $denied[$value] = get_option('php_httpbl_deny_' . $value);
      $deny_checkbox[$value] = ($denied[$value] ?
        'checked="true"' : '');
    }
    $hp = get_option('php_httpbl_hp');
    $not_logged_ips = get_option('php_httpbl_not_logged_ips');
    $log_checkbox = ( get_option('php_httpbl_log') ?
      'checked="true"' : '');
    $log_blocked_only_checkbox = (
      get_option('php_httpbl_log_blocked_only') ?
      'checked="true"' : '');
    $stats_checkbox = ( get_option('php_httpbl_stats') ?
      'checked="true"' : '');
    $stats_pattern = get_option('php_httpbl_stats_pattern');
    $stats_link = get_option('php_httpbl_stats_link');
    $stats_link_radio = array();
    for ($i = 0; $i < 3; $i++) {
      if ($stats_link == $i) {
        $stats_link_radio[$i] = 'checked="true"';
        break;
      }
    }

    // The page contents.
?>
<div class='wrap'>
  <h2>Project Honey Pot Http:BL</h2>
<?php
  // No need to link to the log section, if we're not logging
  if (get_option('php_httpbl_log')) {
?>
  <p><a href="#conf">Configuration</a> | <a href="#log">Log</a></p>
<?php
  }
?>
  <p>Project Honey Pot http:BL allows you to verify IP addresses of clients connecting to your blog against the <a href="http://www.projecthoneypot.org/?rf=45626">Project Honey Pot</a> database.</p>
  <a name="conf"></a>
  <h3>Configuration</h3>
  <form action='' method='post' id='php_httpbl_conf'>
  <h4>Main options</h4>
    <fieldset>
    <p>Project Honey Pot Http:BL Access Key <input type='text' name='key' size='16' value='<?php echo $key ?>' /> (example: abcdefghijkl)</p>
    <p><small>An access key is required to perform a query against the Project Honey Pot database.  You can get your key at <a href="http://www.projecthoneypot.org/httpbl_configure.php">http:BL Access Management page</a>. You need to register for a free account at the Project Honey Pot website to get one.</small></p>
    </fieldset>
    <fieldset>
    <p>Age threshold <input type='text' name='age_thres' size='3' value='<?php echo $age_thres ?>'/> day(s)</p>
    <p><small>Project Honey Pot's Http:BL service provides you information about the date of the last activity of a checked IP.  Because some information in the Project Honey Pot database may be obsolete, you may set an age threshold for the data you use. If the verified IP hasn't been active within the threshold time frame, it will be regarded as harmless.</small></p>
    </fieldset>
    <fieldset>
    <p>Threat score threshold <input type='text' name='threat_thres' size='4' value='<?php echo $threat_thres ?>'/> (0-255)</p>
    <p><small>Project Honey Pot assigns a threat score to each suspicious IP address based on the IP's activity and the damage done during the visits. The score is a number between 0 and 255, where 0 is no threat at all and 255 is extremely harmful. IP addresses with a score greater than the given threat score threshold will be regarded as harmful.</small></p>
    </fieldset>
    <fieldset>
    <label>Types of visitors to be treated as malicious</label>
    <p><input type='checkbox' name='deny_1' value='1' <?php echo $deny_checkbox[1] ?>/> Suspicious</p>
    <p><input type='checkbox' name='deny_2' value='1' <?php echo $deny_checkbox[2] ?>/> Harvesters</p>
    <p><input type='checkbox' name='deny_4' value='1' <?php echo $deny_checkbox[4] ?>/> Comment spammers</p>
    <p><small>The fields above allow you to specify which types of visitors should be regarded as harmful. It is recommended to check all of them.</small></p>
    </fieldset>
    <fieldset>
    <p>Personal Honey Pot Link <input type='text' name='hp' size='60' value='<?php echo $hp ?>'/> (example: http://example.com/my-honey-pot.php)</p>
    <p><small>If you've got a Honey Pot (Bot Trap) you may redirect all unwelcomed visitors to it by specifying its url above. If you leave the following field empty, all harmful visitors will be given a blank page instead of your blog.  For more information on installing your own Bot Trap, see <a href="http://omninoggin.com/2008/05/30/list-poisoning-email-harvesters/">List Poisoning Email Harvesters</a>.  You can also specify other people's Bot Trip into this field; for example, this field comes pre-filled with "http://omninoggin.com/suspicious" (which is my own personal Bot Trap).</small></p>
    </fieldset>
    <p><small>More details on Project Honey Pot Http:BL are available at the <a href="http://www.projecthoneypot.org/httpbl_api.php">http:BL API Specification page</a>.</small></p>
  <h4>Logging options</h4>
    <fieldset>
    <p>Enable logging <input type='checkbox' name='enable_log' value='1' <?php echo $log_checkbox ?>/></p>
    <p><small>If you enable logging all visitors which are recorded in the Project Honey Pot's database will be logged in the database and listed in the table below.</small></p>
    </fieldset>
    <fieldset>
    <p>Log only blocked visitors <input type='checkbox' name='log_blocked_only' value='1' <?php echo $log_blocked_only_checkbox ?>/></p>
    <p><small>Enabling this option will result in logging only blocked visitors. The rest shall be forgotten.</small></p>
    </fieldset>
    <fieldset>
    <p>Not logged IP addresses<br/>
      <textarea name='not_logged_ips' rows='4' cols='60'><?php echo $not_logged_ips ?></textarea>
    </p>
    <p><small>Enter a space-separated list of IP addresses which will not be recorded in the log.</small></p>
    </fieldset>
  <h4>Statistics options</h4>
    <fieldset>
    <p>Enable stats <input type='checkbox' name='enable_stats' value='1' <?php echo $stats_checkbox ?>/></p>
    <p><small>If stats are enabled the plugin will get information about its performance from the database, allowing it to be displayed using <code>php_httpbl_stats()</code> function.</small></p>
    </fieldset>
    <fieldset>
    <p>Output pattern<br/>
      <textarea name='stats_pattern' rows='4' cols='60'><?php echo $stats_pattern ?></textarea>
    </p>
    <p><small>This input field allows you to specify the output format of the statistics. You can use following variables: <code>$block</code> will be replaced with the number of blocked visitors, <code>$pass</code> with the number of logged but not blocked visitors, and <code>$total</code> with the total number of entries in the log table. HTML is welcome. PHP won't be compiled.</small></p>
    </fieldset>
    <fieldset>
    <label>Output Link</label>
    <p><input type="radio" name="stats_link" value="0" <?php echo $stats_link_radio[0]; ?>/> No Thanks!  I'll write a post about this plugin!</p>
    <p><input type="radio" name="stats_link" value="1" <?php echo $stats_link_radio[1]; ?>/> <a href="<?php echo $php_httpbl_home; ?>">Project Honey Pot Http:BL WordPress Plugin</a></p>
    </fieldset>
    <p><input type='submit' name='php_httpbl_save' value='Save Settings' /></p>
  </form>
<?php
  if (get_option('php_httpbl_log')) {
?>
  <hr/>
  <a name="log"></a>
  <h3>Log</h3>
  <form action='' method='post' name='php_httpbl_log'><p>
<?php
  // Does a log table exist?
  $php_httpbl_table_exists = php_httpbl_check_log_table();
  // If it exists display a log purging form and output log
  // in a nice XHTML table.
  if ($php_httpbl_table_exists === true) {
?>
  <script language="JavaScript"><!--
  var response;
  // Delete or purge confirmation.
  function httpblConfirm(action) {
    response = confirm("Do you really want to "+action+
      " the log table ?");
    return response;
  }
  //--></script>
  <input type='submit' name='php_httpbl_truncate' value='Purge the log table' onClick='return httpblConfirm("purge")'/>
  <input type='submit' name='php_httpbl_drop' value='Delete the log table' style="margin:0 0 0 30px" onClick='return httpblConfirm("delete")'/>
  </p></form>
  <p>A list of 50 most recent visitors listed in the Project Honey Pot's database.</p>
  <table cellpadding="5px" cellspacing="3px">
  <tr>
    <th>ID</th>
    <th>IP</th>
    <th>Date</th>
    <th>User agent</th>
    <th>Last seen<sup>1</sup></th>
    <th>Threat</th>
    <th>Type<sup>2</sup></th>
    <th>Blocked</th>
  </tr>
<?php
  // Table with logs.
  // Get data from the database.
  $results = php_httpbl_get_log();
  $i = 0;
  $threat_type = array( '', 'S', 'H', 'S/H', 'C', 'S/C', 'H/C', 'S/H/C');
  foreach ($results as $row) {
    // Odd and even rows look differently.
    $style = ($i++ % 2 ? ' class="alternate"' : '' );
    echo "\n\t<tr$style>";
    foreach ($row as $key => $val) {
      if ($key == 'user_agent')
        // In case the user agent string contains
        // unwelcome characters.
        $val = htmlentities($val, ENT_QUOTES);
      if ($key == 'blocked')
        $val = ($val ? '<strong>YES</strong>' : 'No');
      if ($key == 'php_httpbl_response') {
        // Make the http:BL response human-readible.
        $octets = explode( '.', $val);
        $plural = ( $octets[1] == 1 ? '' : 's');
        $lastseen = $octets[1]." day$plural";
        $td = "\n\t\t<td><small>$lastseen</small></td>".
          "\n\t\t<td><small>".$octets[2].
          '</small></td>\n\t\t<td><small>'.
          $threat_type[$octets[3]].
          '</small></td>';
      } else {
        // If it's not an http:BL response it's
        // displayed in one column.
        $td = "\n\t\t<td><small>$val</small></td>";
      }
      echo $td;
    }
    echo "\n\t</tr>";
  }
?>
  </table>
  <p><small><sup>1</sup> Counting from the day of visit.</small></p>
  <p><small><sup>2</sup> S - suspicious, H - harvester, C - comment spammer.</small></p>
<?php
  } else if ($php_httpbl_table_exists === false) {
?>
  It seems that you haven't got a log table yet. Maybe you'd like to <input type='submit' name='php_httpbl_create' value='create it' /> ?
  </p></form>
<?php
  }

  // End of if (get_option('php_httpbl_log'))
  }
?>
</div>
<?php
  }

  // ---------- hooks ---------- //
  if ( function_exists('register_activation_hook') ) {
    register_activation_hook(__FILE__, 'activate');
  }

  if ( function_exists('add_action') ) {
    add_action('init', 'php_httpbl_check_visitor',1);
    if ( get_option('php_httpbl_stats') )
      add_action('init', 'php_httpbl_get_stats',10);
    add_action('admin_menu', 'php_httpbl_config_page');
  }
?>
