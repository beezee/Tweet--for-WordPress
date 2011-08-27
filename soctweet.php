<?php
/*
 Plugin Name: Seaofclouds Tweet!
 Plugin URI: http://www.workinginboxershorts.com/
 Description: Port of the <a href="http://tweet.seaofclouds.com/">seaofclouds jQuery plugin</a> to WordPress. Easily add twitter feeds via widgets and shortcodes. Show tweets that match a search term, tweets from a specific user, or tweets from a twitter list.
 Author: Brian Zeligson
 Version: 1
 Author URI: http://www.workinginboxershorts.com

 ==
 Copyright 2011 - present date  Brian Zeligson 

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

//************************************  create plugin cleanup function and admin page
 
register_uninstall_hook(__FILE__, 'soctweet_delete_plugin_options');
add_action('admin_init', 'soctweet_init' );
add_action('admin_menu', 'soctweet_add_options_page');

function soctweet_delete_plugin_options() {
	delete_option('soctweet_feeds');
}

function soctweet_init(){
	 wp_register_style( 'soctweet_admin_style', plugins_url('/styles/adminstyle.css', __FILE__) );
	 wp_register_style( 'soctweet_jqui_style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.10/themes/flick/jquery-ui.css');
}

function soctweet_add_options_page() {
	$optpage = add_submenu_page( 'options-general.php', 'Tweet! Settings', 'Tweet!', 'manage_options', __FILE__, 'soctweet_render_form' );
	add_action( 'admin_print_styles-' . $optpage, 'soctweet_admin_styles' );
}

function soctweet_admin_styles() {
       
       wp_enqueue_style( 'soctweet_admin_style' );
	   wp_enqueue_style( 'soctweet_jqui_style');
   }
if (isset($_GET['page']) and $_GET['page'] == 'soctweet/soctweet.php') :
add_action('admin_head', 'soctweet_admin_javascript');
add_action('admin_enqueue_scripts', 'soctweet_enqueue_admin_deps');

function soctweet_enqueue_admin_deps() {
wp_register_script('jqfilter', plugin_dir_url(__FILE__).'/js/filter.js' , array('jquery'));
wp_enqueue_script('jqfilter');
wp_enqueue_script('jquery-ui-dialog');
}


function soctweet_admin_javascript() {

?>
<script type="text/javascript" >
jQuery(document).ready(function($) {
	$('form ul li input').filter_input({regex:'[a-zA-Z0-9_]'});
	$('#Howmanytweets, #refreshrate').filter_input({regex:'[0-9]'});
		$('#Feedtype').change(function() {
			$('.typeli').hide();
			if ($(this).val() == 'User') { $('.socuser').show(); }
			if ($(this).val() == 'List') { $('.soclist').show(); }
			if ($(this).val() == 'Search Results') { $('.socsearch').show(); }
		});
		$('#Usecustomtemplate2').click(function() {
			if ($(this).is(':checked')) { $('#customtemplateli').show(); } else
			{ $('#customtemplateli').hide(); }
		});
		
		$('#Addnewtweetfeed').click(function() {
			var valid = 'yes';
			$('form ul li input, form ul li textarea').each(function() {
				if ($(this).is(':visible') && $(this).val() == '') { valid = 'no'; return; }
			});
			if (valid == 'no') {$('#missingfields').fadeIn('slow', function() { setTimeout("jQuery('#missingfields').fadeOut('slow')", 1000); }); ; return; }
				var data = {
					action : 'soctweet_addfeed',
					<?php $soctweet_addfeed_nonce = wp_create_nonce('soctweet_addfeed_nonce'); ?>
					security : '<?php echo $soctweet_addfeed_nonce; ?>',
					feedname : $('#socfeedname').val(),
					count : $('#Howmanytweets').val(),
					exatreplies : $('#Excludereplies').val(),
					newwindow : $('#Openlinksinnewwindow2').val(),
					refresh_interval : $('#refreshrate').val()
					}
				if ($('#Usecustomtemplate2').is(':checked')) {
					data['template'] = $('#tweettemplate').val();
					}
				if ($('#Feedtype').val() == 'User') {
					data['username'] = $('#username').val();
					data['favorites'] = $('#Showfavoritedtweets').val();
					}
				if ($('#Feedtype').val() == 'List') {
					data['username'] = $('#username').val();
					data['list'] = $('#list').val();
					}
				if ($('#Feedtype').val() == 'Search Results') {
					data['query'] = $('#query').val();
					}
		     jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
						if (response == 'dupename') {$('#uniquename').fadeIn('slow', function() { setTimeout("jQuery('#uniquename').fadeOut('slow')", 1000); }); ; return; }
						$('#existingfeeds').html(response);
						$('form ul li input, form ul li textarea').each(function() { if ($(this).attr('type') != 'button') {$(this).val(''); } });
						$('input:checkbox').removeAttr('checked');
						$('form ul li select').val('User');
						$('#customtemplateli').hide();
			 });
		});
		$('.removefeed').live('click', function() {
			$(this).parent().fadeOut('slow');
			var removefeedname = $(this).attr('id').substring(4);
			var data = {
				action : 'soctweet_removefeed',
				<?php $soctweet_removefeed_nonce = wp_create_nonce('soctweet_removefeed_nonce'); ?>
				security : '<?php echo $soctweet_removefeed_nonce; ?>',
				removefeed : removefeedname
					}
			jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) { 
			});
		});
		$('#showtemplateinstructions').click(function() {
			$('#templateinstructions').dialog({
			height: 400,
			width: 600,
			modal: true
		});
		});
});
</script>
<?php
}
endif;

function soctweet_render_form() {
	?>
	<div class="wrap">
		
		<!-- Display Plugin Icon, Header, and Description -->
		<div class="icon32" id="icon-options-general"><br></div>
		<h2>Tweet! Settings</h2>
		<p>Add or remove tweetfeeds, and place them on your site using the Tweet! widget or shortcode [soctweet tweetfeed="feed name"] in your posts and pages.</p>
		<!-- Beginning of the Plugin Options Form -->
		<form method="post" class="iform" action="options.php">
			<?php settings_fields('soctweet_plugin_options'); ?>
			<?php $options = get_option('soctweet_options'); ?>
			<ul>
				<li class="iheader">Existing tweetfeeds</li>
				<li class="fancy" id="existingfeeds" style="margin-bottom:40px;">
					<ul>
					<?php $tweetfeeds = get_option('soctweet_feeds');
					if (is_array($tweetfeeds)) : 
					foreach ($tweetfeeds as $tweetfeedname => $tweetfeed) : ?>
						<li class="schmancy"><?php echo $tweetfeedname.' | '; $feedparams = ''; foreach ($tweetfeed as $key => $value) {  if ($key != 'action' and $key != 'security') { if ($key == 'template') { $feedparams.=$key.' : '.htmlspecialchars($value).' | '; continue;} $feedparams .= $key.' : '.$value.' | '; } } echo substr($feedparams, 0, -2); ?><a title="Remove this feed" class="removefeed" id="del-<?php echo $tweetfeedname; ?>" style="float:right;cursor:pointer;"><img src="<?php echo plugin_dir_url(__FILE__);?>/img/delbtn.png" /></a></li>
					<?php endforeach; else : ?>
						<li class="schmancy">No feeds yet, add one below.</li>
					<?php endif; ?>
					</ul>
				</li>
				<li class="iheader">Add a tweetfeed</li>
				<li><label for="query">New feed name</label><input class="itext" type="text" name="socfeedname" id="socfeedname" /></li>
				<li class="iseparator">&nbsp;</li>
				<li><label for="Feedtype">Feed type</label><select class="iselect" name="Feedtype" id="Feedtype"><option value="User">User</option>
				<option value="List">List</option>
				<option value="Search Results">Search Results</option>
				</select></li>
				<li class="typeli socuser soclist" id="usernameli"><label for="username">Username</label><input class="itext" type="text" name="username" id="username" /></li>
				<li class="typeli socuser" style="display:none;" id="favoriteli" ><label for="Showfavoritedtweets">Only show favorited tweets?</label><ul><li><input class="icheckbox" type="checkbox" name="Showfavoritedtweets" id="Showfavoritedtweets2" value="Yes"></li>
				</ul></li>
				<li id="listli" class="typeli soclist" style="display:none;"><label for="list">List name</label><input class="itext" type="text" name="list" id="list" /></li>
				<li id="searchli" class="typeli socsearch" style="display:none;"><label for="query">Search terms</label><input class="itext" type="text" name="query" id="query" /></li>
				<li class="iseparator">&nbsp;</li>
				<li><label for="Howmanytweets">How many tweets?</label><input class="itext" type="text" name="Howmanytweets" id="Howmanytweets" /></li>
				<li><label for="refreshrate">How often should it refresh? (seconds)</label><input class="itext" type="text" name="refreshrate" id="refreshrate" /></li>
				<li><label for="Includereplies">Exclude @replies?</label><ul><li><input class="icheckbox" type="checkbox" name="Includereplies" id="Excludereplies" value="yes"></li>
				</ul></li>
				<li><label for="Openlinksinnewwindow">Open links in new window?</label><ul><li><input class="icheckbox" type="checkbox" name="Openlinksinnewwindow" id="Openlinksinnewwindow2" value="yes"></li>
				</ul></li>
				<li class="iseparator">&nbsp;</li>
				<li><label for="Usecustomtemplate">Use custom template? - <a id="showtemplateinstructions">(Details)</a></label><ul><li><input class="icheckbox" type="checkbox" name="Usecustomtemplate" id="Usecustomtemplate2" value="yes"></li>
				</ul></li>
				<li id="customtemplateli" style="display:none;"><label for="Customtemplate-Whatisthis">Custom template</label><textarea class="itextarea" name="Customtemplate-Whatisthis" id="tweettemplate"></textarea></li>
				<br clear="all" /> 
				<li style="width:128px;float:right;"><label>&nbsp;</label><input type="button" class="ibutton" name="Addnewtweetfeed" id="Addnewtweetfeed" value="Add new tweet feed" /></li>
				<li id="missingfields" style="padding-top:30px;width:162px;float:right;display:none;color:red;font-weight:bold;">All fields are required.</li>
				<li id="uniquename" style="padding-top:30px;width:184px;float:right;display:none;color:red;font-weight:bold;">Feedname must be unique.</li>
				
				</ul>
				<div style="display:none;" id="templateinstructions">
				<p>Custom templates allow you to tweak the format of the tweets as they display in your feed. You can use HTML and the following custom parameters to define your template:
					<br /> <br /> 
					{screen_name} -> User who tweeted, plain text <br /> 
					{user} -> User who tweeted, linked to their profile <br /> 
					{avatar} -> Users avatar <br /> 
					{time} -> How long ago they tweeted it <br /> 
					{text} -> Contents of the tweet <br /> 
					{reply_action} -> Link to reply to the tweet <br /> 
					{retweet_action} -> Link to retweet the tweet <br /> 
					{favorite_action} -> Link to favorite the tweet <br /> 
					<br /> <br /> 
					So for example, if you wanted to show their picture, followed by a link to their profile, their tweet, and a retweet link on the next line, your template would look like this:
					<br /> <br /> 
					<?php echo htmlspecialchars('{avatar} {user} &raquo; {text} <br /> <br /> {retweet_action}'); ?>
				</p>
				</div>
		</form>
<br clear="all" /> 
			<p style="margin-top:30px;font-style: italic;font-weight: bold;color: #26779a;">If you like this plugin, you might like my other stuff. Keep up with me and my projects on <a href="http://www.workinginboxershorts.com">Working In Boxer Shorts</a>.
		</p>

	</div>
	<?php
	
}

add_filter( 'plugin_action_links', 'soctweet_plugin_action_links', 10, 2 );
// Display a Settings link on the main Plugins page
function soctweet_plugin_action_links( $links, $file ) {

	if ( $file == plugin_basename( __FILE__ ) ) {
		$soctweet_links = '<a href="'.get_admin_url().'options-general.php?page=soctweet/soctweet.php">'.__('Settings').'</a>';
		// make the 'Settings' link appear first
		array_unshift( $links, $soctweet_links );
	}

	return $links;
}
add_action('wp_ajax_soctweet_addfeed', 'soctweet_add_feed');

function soctweet_add_feed() {
check_ajax_referer('soctweet_addfeed_nonce', 'security');
$option = get_option('soctweet_feeds');
if (isset($option[$_POST['feedname']])) { echo 'dupename'; die(); }
$feedname = $_POST['feedname'];
foreach ($_POST as $key => $postvar) : 
if ($key == 'feedname') continue;
$option[$feedname][$key] = $postvar;
endforeach;
update_option('soctweet_feeds', $option); ?>
<ul>
					<?php $tweetfeeds = get_option('soctweet_feeds');
					if (is_array($tweetfeeds)) : 
					foreach ($tweetfeeds as $tweetfeedname => $tweetfeed) : ?>
						<li class="schmancy"><?php echo $tweetfeedname.' | '; $feedparams = ''; foreach ($tweetfeed as $key => $value) {  if ($key != 'action' and $key != 'security') { if ($key == 'template') { $feedparams.=$key.' : '.htmlspecialchars($value).' | '; continue;} $feedparams .= $key.' : '.$value.' | '; } } echo substr($feedparams, 0, -2); ?><a title="Remove this feed" class="removefeed" id="del-<?php echo $tweetfeedname; ?>" style="float:right;cursor:pointer;"><img src="<?php echo plugin_dir_url(__FILE__);?>/img/delbtn.png" /></a></li>
					<?php endforeach; else : ?>
						<li class="schmancy">No feeds yet, add one below.</li>
					<?php endif; ?>
					</ul>
<?php
die();
}

add_action('wp_ajax_soctweet_removefeed', 'soctweet_remove_feed');

function soctweet_remove_feed() {
check_ajax_referer('soctweet_removefeed_nonce', 'security');
$option = get_option('soctweet_feeds');
unset($option[$_POST['removefeed']]);
update_option('soctweet_feeds', $option);
die();
}

//************************************  Create widget

class SoctweetWidget extends WP_Widget {
	/** constructor */
	function SoctweetWidget() {
		$tweetwidgetparams = array('description' => 'Add a chosen tweetfeed from those defined on the Tweet! Settings Page');
		parent::WP_Widget( 'soctweetwidget', $name = 'Tweet! Widget', $tweetwidgetparams);
	}

	/** @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title; ?>
		<li style="list-style:none;" class="soctweet soctweetwidget soctweet<?php echo $instance['feedname']; ?>"></li>
		<?php echo $after_widget;
	}

	/** @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['feedname'] = strip_tags($new_instance['feedname']);
		return $instance;
	}

	/** @see WP_Widget::form */
	function form( $instance ) {
		if ( $instance ) {
			$title = esc_attr( $instance[ 'title' ] );
			$feedname = esc_attr( $instance['feedname']);
		}
		else {
			$title = __( 'New title', 'text_domain' );
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Display Title:'); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		<br clear="all" /> <br clear="all" /> 
		<label for="<?php echo $this->get_field_id('feedname'); ?>"><?php _e('Feed Name:'); ?></label> 
		<select class="widefat" id="<?php echo $this->get_field_id('feedname'); ?>" name="<?php echo $this->get_field_name('feedname'); ?>">
			<?php $option = get_option('soctweet_feeds'); foreach ($option as $deffeedname => $feed) : ?>
				<option value="<?php echo $deffeedname; ?>" <?php if ($deffeedname == $feedname) echo ' SELECTED '; ?>><?php echo $deffeedname; ?></option>
				<?php endforeach; ?>
				</select>
		</p>
		<?php 
	}

} 

add_action( 'widgets_init', create_function( '', 'return register_widget("SoctweetWidget");' ) );

//************************************  create shortcode

function make_soctweet_shortcode_work($atts) {
     $output = '<ul style="list-style:none;"><li class="soctweet soctweetshortcode soctweet'.$atts['tweetfeed'].'"></li></ul>';
	 return $output;
}
add_shortcode('soctweet', 'make_soctweet_shortcode_work');

//************************************  enqueue jquery.tweet.css

function enqueue_soctweet_styles() {
wp_register_style('soctweet_styles', plugin_dir_url(__FILE__).'/styles/jquery.tweet.css');
wp_enqueue_style('soctweet_styles');
}

add_action('wp_print_styles', 'enqueue_soctweet_styles');

//************************************  enqueue jquery.tweet.js, and add invocation script to header

function enqueue_soctweet_js() {
wp_register_script('soctweet_js', plugin_dir_url(__FILE__).'/js/jquery.tweet.js' , array('jquery'));
wp_enqueue_script('soctweet_js');
}

add_action('wp_enqueue_scripts', 'enqueue_soctweet_js');

add_action('wp_head', 'invoke_soctweet_feeds');

function invoke_soctweet_feeds() { ?>
<script type="text/javascript"> 
<?php 
$feeds = get_option('soctweet_feeds');
foreach ($feeds as $tweetfeedname => $tweetfeed ) : ?>
jQuery(document).ready(function($) { 
if ($('.soctweet<?php echo $tweetfeedname; ?>')[0]) {
$('.soctweet<?php echo $tweetfeedname; ?>').tweet({
avatar_size:32,
<?php foreach ($tweetfeed as $property => $value ) :
if ($property == 'exatreplies') {echo 'filter: function(t){ return ! /^@\w+/.test(t["tweet_raw_text"]); },'; continue; }
if ($property == 'newwindow') continue;
if ($property == 'count' or $property == 'refresh_interval') { echo $property.' : '.$value.','; continue;}
echo $property.' : "'.$value.'",';
 endforeach; ?>
})<?php
if (isset($tweetfeed['newwindow'])) echo '.bind("loaded",function(){$(this).find("a").attr("target","_blank");})'; ?>
.bind("empty", function() { $(this).append("No matching tweets found"); }); 
} });
<?php endforeach; ?>
</script>
<?php
}