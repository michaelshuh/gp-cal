<?php

/* Add our function to the widgets_init hook. */
add_action( 'widgets_init', 'gp_calendar_load_widgets' );

/* Function that registers our widget. */
function gp_calendar_load_widgets() {
	register_widget( 'Gracepoint_Calendar_Widget' );
	wp_register_style('gp_cal_widget', WP_PLUGIN_URL . '/gp-cal/gp-cal-widget.css');
	wp_enqueue_style( 'gp_cal_widget' );
}


class Gracepoint_Calendar_Widget extends WP_Widget {
	function Gracepoint_Calendar_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'gp_cal_widget', 'description' => 'Widget to pull events from a google calendar' );

		/* Widget control settings. */
		$control_ops = array( 'width' => 500,  'id_base' => 'gp-cal-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'gp-cal-widget', 'Gracepoint Calendar', $widget_ops, $control_ops );
	}
	
	
	function widget( $args, $instance ) {
		extract( $args );


		// These are our own options
		$gp_cal_widget_settings = get_option( "gp-cal-widget-settings");
		
		$cal_name = $instance['gp_cal_calendar'];
		$this_calendar;
		
		foreach ( $gp_cal_widget_settings as $calendar ) {
			if ($calendar['cal_name'] == $cal_name) {
				$this_calendar = $calendar;
				break;
			}
		}
		
		$title = apply_filters('widget_title', $instance['title'] );
		$debug = $instance['gp_cal_debug'];
		$numdays = $instance['gp_cal_num_days'];
		$show = $instance['gp_cal_show'];
		$tags = $instance['gp_cal_tags'];
		
		$userid = $this_calendar['cal_userid'];
		$password = $this_calendar['cal_pwd'];
		$cal_name = $this_calendar['cal_name'];
		
		$myCalendar = array("calendar_userid" => $userid, "calendar_password" => $password, "calendar_name" => $cal_name);
		
		echo $before_widget;
		echo $before_title . 'Upcoming Events' . $after_title;
		require (dirname(__FILE__) . '/gcal/gpcal.class.php');
		$gpcal = new GPCal( $myCalendar, array(
					'debug' => $debug,
					'shows' => $show,
					'numdays' => $numdays,
					'tags' => $tags
		));
		echo $gpcal->display();
		echo $after_widget;
	}
	
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags (if needed) and update the widget settings. */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['gp_cal_calendar'] = strip_tags( $new_instance['gp_cal_calendar']);
		$instance['gp_cal_debug'] = strip_tags( $new_instance['gp_cal_debug'] );
		$instance['gp_cal_num_days'] = strip_tags( $new_instance['gp_cal_num_days'] );
		$instance['gp_cal_show'] = strip_tags( $new_instance['gp_cal_show'] );
		$instance['gp_cal_tags'] = strip_tags( $new_instance['gp_cal_tags'] );
		return $instance;
	}
	
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'title' => 'Upcoming Events', 'gp_cal_debug' => '0', 'gp_cal_num_days' => '6', 'gp_cal_show' => 'normal', 'gp_cal_calendar' => '0');
		$instance = wp_parse_args( (array) $instance, $defaults ); 
		$gp_cal_widget_settings = get_option( "gp-cal-widget-settings");
	?>

<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'gp_cal_calendar' ); ?>">Calendar:</label>
			<select name="<?php echo $this->get_field_name( 'gp_cal_calendar' ); ?>" id="<?php echo $this->get_field_id( 'gp_cal_calendar' ); ?>" class="widefat">
<?php
	$this_calendar = $instance['gp_cal_calendar'];
	foreach ( $gp_cal_widget_settings as $calendar ) {
		if ($calendar['cal_name'] == $this_calendar) {
			echo '<option value="' . $this_calendar . '" selected >' . $this_calendar . '</option>';
		} else {
				echo '<option value="' . $calendar["cal_name"] . '">' . $calendar["cal_name"] . '</option>';
		}
	}
?>
			</select>
		</p>
<p>
<label for="<?php echo $this->get_field_id( 'gp_cal_num_days' ); ?>">Number of Days to Show:</label>
<input id="<?php echo $this->get_field_id( 'gp_cal_num_days' ); ?>" name="<?php echo $this->get_field_name( 'gp_cal_num_days' ); ?>" value="<?php echo $instance['gp_cal_num_days']; ?>" style="width:100%;" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'gp_cal_show' ); ?>">What to Show:</label>
	<select name="<?php echo $this->get_field_name( 'gp_cal_show' ); ?>" id="<?php echo $this->get_field_id( 'gp_cal_show' ); ?>" class="widefat">
		<option value="normal" <?php if ($instance ['gp_cal_show'] == 'normal') echo 'selected'; ?>>Normal</option>
		<option value="featured" <?php if ($instance ['gp_cal_show'] == 'featured') echo 'selected'; ?>>Featured</option>
	</select>
</p>
<p>
<label for="<?php echo $this->get_field_id( 'gp_cal_tags' ); ?>">Tags to Show:</label>
<input id="<?php echo $this->get_field_id( 'gp_cal_tags' ); ?>" name="<?php echo $this->get_field_name( 'gp_cal_tags' ); ?>" value="<?php echo $instance['gp_cal_tags']; ?>" style="width:100%;" />
</p>
<p>
<label for="<?php echo $this->get_field_id( 'gp_cal_debug' ); ?>">Debug:</label>
	<select name="<?php echo $this->get_field_name( 'gp_cal_debug' ); ?>" id="<?php echo $this->get_field_id( 'gp_cal_debug' ); ?>" class="widefat">
		<option value=1<?php if ($instance ['gp_cal_debug'] == 1) echo 'selected'; ?>>Yes</option>
		<option value=0 <?php if ($instance ['gp_cal_debug'] == 0) echo 'selected'; ?>>No</option>
	</select>
</p>
<?php
	}
}
?>