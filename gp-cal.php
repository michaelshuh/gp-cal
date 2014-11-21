<?php
/*
Plugin Name: Gracepoint Calendar Widget
Plugin URI: 
Description: Plugin to pull events from a Google Calendar
Version: 1.0
Author: Mike Shuh
Author URI: 
*/

/* License

    Gracepoint Calendar Widget

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License gpfor more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
*/
include_once dirname( __FILE__ ) . '/gp-cal-widget.php';

function add_gp_cal_settings_page() {
	add_options_page('GP Calendar Widget', 'GP Calendar Widget', 'manage_options', 'gp-cal.php', 'gp_cal_settings_page'); 
}

function gp_cal_settings_page() {
	$gp_cal_widget_settings = get_option( "gp-cal-widget-settings" );
  
  if ( empty($gp_cal_widget_settings) ) {
      $gp_cal_widget_settings = array();
  }

	if (isset($_POST['info_update'])) {
	    $count = count( $gp_cal_widget_settings );
	    $gp_cal_widget_settings = array();

	    for( $i = 0; $i < $count; $i++ ) {
	        $cal_name = $_POST["cal_name_" . $i];
    	    $cal_userid = $_POST["cal_userid_" . $i];
    	    $cal_apikey = $_POST["cal_apikey_" . $i];

    	    $entry_array = array("cal_name" => $cal_name, "cal_userid" => $cal_userid, "cal_apikey" => $cal_apikey);
    	    array_push( $gp_cal_widget_settings, $entry_array );
	    }  
		update_option( 'gp-cal-widget-settings', $gp_cal_widget_settings );
	} 

	if (isset($_POST['add_entry'])) {
        $count = count( $gp_cal_widget_settings );
	    $cal_name = $_POST["cal_name_" . $count];
	    $cal_userid = $_POST["cal_userid_" . $count];
	    $cal_apikey = $_POST["cal_apikey_" . $count];

	    $entry_array = array("cal_name" => $cal_name, "cal_userid" => $cal_userid, "cal_apikey" => $cal_apikey);

	    array_push( $gp_cal_widget_settings, $entry_array );

	    update_option( 'gp-cal-widget-settings', $gp_cal_widget_settings);
	}

	if (isset($_POST['delete_entry'])) {
	    $entry_id = $_POST['delete_entry'];
	    unset($gp_cal_widget_settings[$entry_id]);

	    $gp_cal_widget_settings = array_values($gp_cal_widget_settings);

	    update_option( 'gp-cal-widget-settings', $gp_cal_widget_settings );
	}

?>
		<div class="wrap">
            <h2>GP Calendar Widget</h2>
			<form method="post" action="options-general.php?page=gp-cal.php" id="gp-cal-widget-settings">

				<h3>Gracepoint Calendar Settings</h3>
				<table class="form-table">
				    <tr valign="top">
				        <th>Calendar Name</th>
				        <th>Calendar User ID</th>
				        <th>Calendar API Key</th>
				        <th>Add/Delete</th>
				    </tr>
				    <?php 
				        $count = 0;
				        foreach ( $gp_cal_widget_settings as $entry ) {
				            echo '<tr valign="top">';
				            echo '<td><input name="cal_name_'.$count.'" type="text" value="'.$entry["cal_name"].'" /></td>';
				            echo '<td><input name="cal_userid_'.$count.'" type="text" value="'.$entry["cal_userid"].'" /></td>';
				            echo '<td><input name="cal_apikey_'.$count.'" type="text" value="'.$entry["cal_apikey"].'" /></td>';
				            echo '<td><button name="delete_entry" class="button-secondary" value="'.$count.'" type="submit">Delete</button></td>';
				            echo '</tr>';
				            $count++;
				        }
	                ?>
	                <tr valign="top">
	                    <td><input name="<?php echo "cal_name_" . $count; ?>" type="text" /></td>
	                    <td><input name="<?php echo "cal_userid_" . $count; ?>" type="text" /></td>
	                    <td><input name="<?php echo "cal_apikey" . $count; ?>" type="text" /></td>
	                    <td><input type="submit" name="add_entry" class="button-secondary" value="Add" /></td>
	                </tr>  
				</table>
				<p class="submit">
					<input type="submit" name="info_update" class="button-primary" value="Save" />
				</p>
				</form>
		</div>
<?php

}

function gp_cal_widget_settings_link($links, $file) {
    static $this_plugin;
 
    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }
 
    // check to make sure we are on the correct plugin
    if ($file == $this_plugin) {
        // the anchor tag and href to the URL we want. For a "Settings" link, this needs to be the url of your settings page
        $settings_link = '<a href="options-general.php?page=gp-cal.php">Settings</a>';
        // add the link to the list
        array_unshift($links, $settings_link);
    }
 
    return $links;
}
// Run code and init
add_filter('plugin_action_links', 'gp_cal_widget_settings_link', 10, 2);
add_action('admin_menu', 'add_gp_cal_settings_page');

?>
