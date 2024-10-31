<?php
// (9262013)
/*
Plugin Name: My Shouts
Plugin URI: http://www.munzir.net/entry/myshouts-wordpress-shoutbox-plugin
Description: A simple ajax shoutbox with customizable options
Version: 0.9
Author: Munzir
Author URI: http://www.munzir.net

// usage instruction here
echo myshouts_show() 

or use the widget

Note: this shout is ported from my website, so I didn't have much time to work on porting it 'properly'
*/ 

//tables
define('SHOUTDB',$wpdb->prefix.'myshouts');
define('SHOUTDIR',dirname(__FILE__));

//$shoutpath = get_bloginfo('wpurl').'/'.PLUGINDIR.'/myshouts/'; //apparently causing some problems
$shoutpath = get_bloginfo('wpurl').'/'.PLUGINDIR.'/'.basename(dirname(__FILE__)).'/';

//inits
add_action('init', 'myshouts_init');
add_action('admin_menu', 'myshouts_adminmenu'); // create menu
add_action('wp_head', 'myshouts_headstuff',12);

$shoutStyles = array(
						'bgcolor'			=>	'Background color',
						'datetextcolor'		=>	'Date Text Color',
						'prevnextcolor'		=>	'Prev/Next BG Color',
						'submitform'		=>  'Submit Form BG Color',	
						'inputcolor'		=>  'Input Fields Color',
						'inputtextcolor'	=>	'Input Fields Text Color',
						'titletextcolor'	=>  'Shout Title Color',
						'shoutrowcolor'		=>	'Shout Row BG Color',
						'shoutrownametextcolor' =>	'Shout Row Name Text Color',
						'shoutrowtextcolor'	=>	'Shout Row Text Color',
						'submitbgcolor'		=>	'Submit Button BG Color',
						'submittextcolor'	=>	'Submit Button Text Color',
						'submitbordercolor'	=>	'Submit Button Border Color'
					);

function myshouts_add()
{
	global $wpdb;

	$key = sha1(date('dmy'));
	
	if ( $key != $_POST['shout_key'] )
	{
		die('Invalid Post Key');
	}

	//name,email, spam
	//check `comment` field
	if ( $_POST['name'] == '' )
	{
		die('Please provide a name.');
	}

	if ( $_POST['email'] == '' )
	{
		die('Enter your email first.');
	}

	if ( $_POST['shout'] == '' )
	{
		die('You must write something first.');
	}

	if ( strlen($_POST['shout']) < 3 )
	{
		echo 'Your shout is too short! It must be more than 3 characters';
		exit;
	}

	$date	= time();
	
	$_POST['shout']	= trim(strip_tags($_POST['shout']));
	
	if ( myshouts_getcookie('shout_addspam') == 1 )
	{
		die('Flood Control: You\'ve just submitted a comment less than a minute ago.');
	}

	$save = $_POST;
	

	$wpdb->query("INSERT INTO 
								`".SHOUTDB."` 
							SET 				
								name		= '".strip_tags($save['name'])."',
								email		= '".strip_tags($save['email'])."',
								website		= '".strip_tags($save['website'])."',
								message		= '".$save['shout']."',
								date		= '".$date."'
				");
	
	$comment_id = $wpdb->insert_id;	

	//makecookie (its coz im too lazy to find out how wp handle cookies)
	myshouts_setcookie('shout_addspam',1,60);
	myshouts_setcookie('shout_name',$_POST['name']);
	myshouts_setcookie('shout_email',$_POST['email']);
	myshouts_setcookie('shout_website',$_POST['website']);

	//all done
	
	//echo latest comment
	$row = $wpdb->get_row("SELECT * FROM `".SHOUTDB."` WHERE id='".$comment_id."'");

	echo myshouts_shoutrow($row,1);
	exit;
}

//start functions
function myshouts_headstuff()
{
	global $shoutpath;
	
	?>
	<script type="text/javascript">
		var post_file = '<?php echo $_SERVER['PHP_SELF'] ?>';
		//jQuery(document).ready(function(){		
			  
			//setTimeout(function(){ myshouts_check_stream(); },1500);

		//});
	</script>
	<?php

	if  ( get_option('myshouts_useaccordion') == 1 )
	{
		?>
		<script type="text/javascript">
		jQuery(function() {
			jQuery("#myshouts_shouts").jCarouselLite({
				btnNext: ".next",
				btnPrev: ".prev",
				vertical: true,
				circular: false,
				visible: <?php echo get_option('myshouts_accordionshow') > 0 ? get_option('myshouts_accordionshow') : 3?>
			});
		});	
		</script>
		<?php
	}

	echo '<script type="text/javascript" src="'.$shoutpath.'shout.js"></script>'."\n";
	if ( get_option('myshouts_ignorestyles') != 1 )
	{
		echo '<link rel="stylesheet" type="text/css" href="'.$shoutpath.'myshouts.css" />'."\n";
	}
}

function myshouts_customstyle($what='',$additional='')
{
	global $Styles,$stylesPos;
	
	if ( get_option('myshouts_ignorestyles') == 1 )
	{
		return false;
	}

	$type = preg_match("/text/",$what) ? 'color':'background-color';
	
	if ( $Styles[$what] != '' )
	{
		echo 'style="'.$type.':'.$Styles[$what].';'.$additional.'"';
	}
	else
	{
		echo 'style="'.$additional.'"';
	}
}

function myshouts_customstyles_show()
{
	global $Styles;
	
	if ( get_option('myshouts_ignorestyles') == 1 )
	{
		return false;
	}

	?>
	<style type="text/css">
		#myshouts_form .inputtext { <?php echo $Styles['inputcolor'] == '' ? '':'background-color:'.$Styles['inputcolor'].';' ?><?php echo $Styles['inputtextcolor'] == '' ? '':'color:'.$Styles['inputtextcolor'] ?> }
		#myshouts_form .submit input { <?php echo $Styles['submitbordercolor'] == '' ? '':'border-color:'.$Styles['submitbordercolor'].';' ?><?php echo $Styles['submitbgcolor'] == '' ? '':'background-color:'.$Styles['submitbgcolor'].';' ?><?php echo $Styles['submittextcolor'] == '' ? '':'color:'.$Styles['submittextcolor'].';' ?> }
		#myshouts_shouts ul li { <?php echo $Styles['shoutrowcolor'] == '' ? '':'background-color:'.$Styles['shoutrowcolor'].';' ?><?php echo $Styles['shoutrowtextcolor'] == '' ? '':'color:'.$Styles['shoutrowtextcolor'].';' ?> }
		#myshouts_shouts ul li dt, #myshouts_shouts ul li dt a { <?php echo $Styles['shoutrownametextcolor'] == '' ? '':'color:'.$Styles['shoutrownametextcolor'].' !important;' ?> }
	</style>
	<?php
}

function myshouts_show()
{	
	global $Styles;
	$Styles = unserialize(get_option('myshouts_styles'));
	$stylesReset = array(
							'myshouts_theshouts'		=>	'padding:0;margin:10px 0 10px 0; float:none; border:none; background:none; position:static;',
							'myshouts_theshouts_ul'		=>	'list-style:none; margin:0; padding:0; overflow:hidden;line-height:1em; position: static; background:none; border:none',
							'myshouts_theshouts_ul_li'	=>	'padding:4px; margin:0 0 5px 0; background:#fff; line-height:1em; font-weight:normal; float:none; border:none'
						);
	
	myshouts_customstyles_show();
	?>
	<div id="myshouts_wrapper" <?php myshouts_customstyle('bgcolor')?>>
	<!-- /wrapper -->
	<?php if  ( get_option('myshouts_useaccordion') == 1 ) { ?>
	<a href="javascript:void(0)" class="prev" title="Previous Shouts" <?php myshouts_customstyle('prevnextcolor')?>><em>prev</em></a>
	<?php } ?>

	<div id="myshouts_shouts" class="theshouts" rel="<?php echo time()?>">
	<ul>
		<?php
			myshouts_showshouts() 
		?>
	</ul>
	</div>
	<?php if  ( get_option('myshouts_useaccordion') == 1 ) { ?>
	<a href="javascript:void(0)" class="next" title="More Shouts" <?php myshouts_customstyle('prevnextcolor')?>><em>next</em></a>
	<?php } ?>

	<div id="myshouts_formwrap">
	<form name="myshouts_form" id="myshouts_form" onsubmit="return false" method="post" <?php myshouts_customstyle('submitform')?>>
	<?php
		$key = sha1(date('dmy'));
	?>
	<input type="hidden" value="<?php echo $key?>" name="shout_key" />
	<div class="myshouts_title" <?php myshouts_customstyle('titletextcolor')?>>Your Shout</div>
	<?php
		$shout_name		= myshouts_getcookie('shout_name')	!= '' ? myshouts_getcookie('shout_name') : 'Name';
		$shout_email	= myshouts_getcookie('shout_email')	!= '' ? myshouts_getcookie('shout_email') : 'Email';
		$shout_website	= myshouts_getcookie('shout_website') != '' ? myshouts_getcookie('shout_website') : 'http://';
	?>
		<div class="name"><input type="text" size="30" name="name" id="shout-name" class="inputtext" value="<?php echo $shout_name?>" onfocus="shoutblur(this,'Name')" onblur="shoutcheck(this,'Name')"/></div>
		<div class="email"><input type="text" size="30" name="email" id="shout-email" class="inputtext" value="<?php echo $shout_email?>" onfocus="shoutblur(this,'Email')" onblur="shoutcheck(this,'Email')" /></div>
		<div class="website"><input type="text" size="30" name="website" id="shout-website" class="inputtext" value="<?php echo $shout_website?>" onfocus="shoutblur(this,'http://')" onblur="shoutcheck(this,'http://')" /></div>
		<div class="shout"><input type="text" size="30" name="shout" id="shout-shout" class="inputtext" /></div>
		
		<div class="submit">
			<input type="submit" name="shout_submit" onclick="return shout_send()" onmouseover="this.className='shoutsubmit_over';" onmouseout="this.className='shoutsubmit';" class="shoutsubmit"  value="submit"/>
		</div>

	</form>
	</div>
	
	<!-- /wrapper -->
	</div>
	<?php
}

function myshouts_get_gravatar( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {
    $url = 'http://www.gravatar.com/avatar/';
    $url .= md5( strtolower( trim( $email ) ) );
    $url .= "?s=$s&d=$d&r=$r";
    if ( $img ) {
        $url = '<img src="' . $url . '"';
        foreach ( $atts as $key => $val )
            $url .= ' ' . $key . '="' . $val . '"';
        $url .= ' />';
    }
    return $url;
}

function myshouts_newshouts()
{
	global $wpdb;
		
	$filter = 'WHERE date>\''.$wpdb->escape($_POST['time']).'\'';

	$shouts = $wpdb->get_results("SELECT * FROM `".SHOUTDB."` ".$filter." ORDER BY date DESC");
	
	foreach ( $shouts as $shout )
	{
		myshouts_shoutrow($shout,1,1);
	}

	exit;
}

function myshouts_showshouts()
{
	global $wpdb;
	
	$total = get_option('myshouts_total') > 0 ? get_option('myshouts_total') : 10;

	$shouts = $wpdb->get_results("SELECT * FROM `".SHOUTDB."` ORDER BY date DESC LIMIT ".$total);
	foreach ( $shouts as $shout )
	{
		myshouts_shoutrow($shout);
	}
}

function myshouts_hidemail($email='')
{
	$mail = explode('@',$email);
	return str_repeat('x',strlen($mail[0])).'@'.str_repeat('x',strlen($mail[1]));
}

function myshouts_shoutrow($row,$hide=0,$new=0)
{
	global $shoutpath;
	
	$row->name = $row->website == 'http://' || $row->website == '' ? $row->name : '<a href="'.$row->website.'">'.$row->name.'</a>';
	
	if ( get_option('myshouts_gravatar') == 1 )
	{
		if ( $row->email != '' )
		{
			$get_gv = myshouts_get_gravatar($row->email, 32);
			$gravatar = '<dd><span class="gravatar"><img src="'.$get_gv.'" alt="" /></span></dd>';
		}
	}
	?>
	<li<?php echo $hide==1?' style="display:none"':''?><?php echo $new==1 ? 'class="new"':''?>>
		<?php echo $gravatar?>
		<div class="outershout">
		<dl>			
			<dt><?php echo $row->name?></dt>
			<!-- <dd class="icons"><a href="mailto:<?php echo __(myshouts_hidemail($row->email)) ?>" rel="nofollow"><img src="<?php echo $shoutpath ?>email.png" alt="email" /></a></dd> -->
			<dd class="shout">
			<div class="bottom">
				<?php echo __($row->message) ?>
			</div>
			</dd>
			<dd class="date" <?php myshouts_customstyle('datetextcolor')?>><?php echo date('j F Y',$row->date)?></dd>
		</dl>
		</div>
	</li>
	<?php
}


function myshouts_setcookie($name='',$val='',$time=30000000)
{
	$expire = time() + $time;

	setcookie( $name . COOKIEHASH, $val, $expire, COOKIEPATH );
}

function myshouts_getcookie($name='') 
{
	return $_COOKIE[$name. COOKIEHASH];
}

function myshouts_init()
{
	global $shoutpath;

	// load jquery
	wp_enqueue_script('jquery');

	if  ( get_option('myshouts_useaccordion') == 1 )
	{
		wp_enqueue_script('wp_wall_script',$shoutpath.'jcarousellite_1.0.1.min.js');
	}

	if ( isset($_GET['activate']) || isset($_GET['activate-multi']) )
    {
        myshouts_install();
    }

	if ( $_GET['do'] == 'sendshout' )
	{
		myshouts_add();
	}

	if ( $_GET['do'] == 'gettime' )
	{
		die( time() );
	}

	if ( $_GET['do'] == 'newshouts' )
	{
		myshouts_newshouts();
	}

	if ( is_admin() )
	{
		myshouts_admin_submits();
	}

}

add_action("widgets_init", array('MyShouts', 'register'));
class MyShouts 
{
	function control()
	{
	  $title = get_option('myshouts_title');
	  ?>
	  <p><label for="myshouts_title">Widget Title</label> <input id="myshouts_title" name="myshouts_title" type="text" style="width:150px" value="<?php echo $title; ?>" /></p>
	  <?php
		if (isset($_POST['myshouts_title']))
		{
			update_option('myshouts_title', $_POST['myshouts_title']);
		}
	}

	function widget($args)
	{
		extract($args);
		$shout_title = get_option('myshouts_title');
		$shout_title = $shout_title == '' ? 'Shoutbox' : $shout_title;

		echo $before_widget; 
		echo $before_title .$shout_title. $after_title;

		echo myshouts_show();

		echo $after_widget; 
	}
	
	function register()
	{
		register_sidebar_widget('My Shouts', array('MyShouts', 'widget'));
		register_widget_control('My Shouts', array('MyShouts', 'control'));
	}
}

function myshouts_install()
{
	global $wpdb;

	//install baby
	if ($wpdb->get_var("show tables like '".SHOUTDB."'") != SHOUTDB)
	{
		$wpdb->query("CREATE TABLE IF NOT EXISTS `".SHOUTDB."` (
					  `id` int(10) NOT NULL auto_increment,
					  `name` varchar(100) NOT NULL,
					  `email` varchar(150) NOT NULL,
					  `website` varchar(150) NOT NULL,
					  `message` text NOT NULL,
					  `date` int(11) NOT NULL,
					  PRIMARY KEY  (`id`)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
		
		update_option('myshouts_total',10);
		update_option('myshouts_useaccordion',0);
		update_option('myshouts_accordionshow',3);
	}

	myshouts_maybe_add_column(SHOUTDB,'website','ALTER TABLE `'.SHOUTDB.'` ADD `website` VARCHAR( 150 ) NOT NULL AFTER `email` ;');
}

function myshouts_maybe_add_column($table_name, $column_name, $create_ddl) {
	global $wpdb, $debug;
	foreach ($wpdb->get_col("DESC $table_name", 0) as $column ) {
		if ($debug) echo("checking $column == $column_name<br />");
		if ($column == $column_name) {
			return true;
		}
	}
	//didn't find it try to create it.
	$q = $wpdb->query($create_ddl);
	// we cannot directly tell that whether this succeeded!
	foreach ($wpdb->get_col("DESC $table_name", 0) as $column ) {
		if ($column == $column_name) {
			return true;
		}
	}
	return false;
}

function myshouts_adminmenu()
{
	add_options_page('My Shouts', 'My Shouts', 8, basename(__FILE__), 'myshouts_options');
}

function myshouts_options()
{
	global $wpdb, $shoutpath,$shoutStyles;
	
	
	?>
	<script type="text/javascript" src="<?php echo $shoutpath ?>/colorpick2/iColorPicker-noLink.js"></script>
	<script type="text/javascript">
		jQuery(document).ready(function()
		{
			jQuery('.iColorPicker').each(function()
			{
				jQuery(this).css("background-color",jQuery(this).val());
				if ( jQuery(this).val != '')
				{
					jQuery(this).change();
				}
			});
		});
	</script>
	<style type="text/css">
		.iColorPicker { width:100px; font-size:11px }
	</style>
	<div class="wrap">
	<h2><?php _e('Manage Shouts')?></h2>
	<h3>Options</h3>
	<div style="padding:10px; background:#fff; border:1px solid #ccc;">
	<form method="post">
		
		<p><strong>Total Shouts on site</strong> <input type="text" size="5" maxlength="2" value="<?php echo get_option('myshouts_total')?>" name="myshouts_total"/></p>
		
		<p><strong>Use Gravatar?</strong> <input type="checkbox" value="1" name="myshouts_gravatar" <?php if ( get_option('myshouts_gravatar') == 1 ) echo 'checked="checked"' ?>/></p>
		<p><strong>Use Accordion?</strong> <input type="checkbox" value="1" name="myshouts_useaccordion" <?php if ( get_option('myshouts_useaccordion') == 1 ) echo 'checked="checked"' ?>/></p>
		<p><strong>Shouts in Accordion</strong> <input type="text" size="5" maxlength="2" value="<?php echo get_option('myshouts_accordionshow')?>" name="myshouts_accordionshow"/><br /><small>Must be lower than total shouts on site</small></p>
		<p><strong>Ignore Styles, I will change myshouts.css myself.</strong> <input type="checkbox" name="myshouts_ignorestyles" value="1" <?php if ( get_option('myshouts_ignorestyles') ) echo 'checked="checked"';?> /> <small>If you don't like the style changer and would like to change the css file yourself, check this</small></p>
		<p class="submit">
			<input type="hidden" name="savetype" value="saveoptions" />
			<input name="save" type="submit" value="Save" />
			<input type="hidden" name="action" value="save" />
		</p>
	</form>
	</div>

	<br />
	<h3>Style</h3>
	<?php
	
	?>
	<style type="text/css">
		#myshouts_styleadmin { padding:10px; background:#fff; border:1px solid #ccc; margin-bottom:10px;overflow:hidden; width:100%; }
		#myshouts_demo { float:left; margin-right:10px; width:220px; border:1px solid #ccc; }
		#styleform { float:left; width:500px; }

		/* shouts */
		#myshouts_shouts { margin:10px 0;  }
		#myshouts_shouts ul { list-style:none; margin:0; padding:0; overflow:hidden; }
		#myshouts_shouts ul li { padding:4px; margin-bottom:5px; background:#fff; }
		#myshouts_shouts ul li dl { position:relative; }
		#myshouts_shouts ul li dt, #myshouts_shouts ul li dt a { color:#333; font:bold 11px Arial; } /* name */
		#myshouts_shouts ul li dl .icons { position:absolute; top:5px; right:5px; }
		#myshouts_shouts ul li dl .shout { padding:5px; }
		#myshouts_shouts ul li dl .date { color:#a4a09a; font-size:0.9em;}

		/* form */
		#myshouts_form { background:#eee; padding:5px; }
		#myshouts_form .inputtext { background:#fff; border:none; font:normal 11px Arial; color:#666; padding:4px; width: 90%; -moz-border-radius:0; }
		#myshouts_form .name, #myshouts_form .email, #myshouts_form .shout, #myshouts_form .website { padding:4px; }
		#myshouts_form .myshouts_title { font:bold 14px Arial; letter-spacing:-1px; color:#444; }

		#myshouts_form .submit { margin-top:10px; }
		#myshouts_form .shoutsubmit { border:1px solid #999; background:#ccc; color:#666; font:bold 11px Arial; padding:5px; -moz-border-radius:0; text-shadow:none }
		#myshouts_form .shoutsubmit_over { border:1px solid red; background:orange; color:#fff; font:bold 11px Arial; padding:5px; -moz-border-radius:0; text-shadow:none  }

		/* accordion */
		#myshouts_wrapper a.prev { background:#ccc url('<?php echo $shoutpath?>icon_prev.png') center 2px no-repeat; display:block; height:10px; margin-bottom:5px; }
		#myshouts_wrapper a.next { background:#ccc url('<?php echo $shoutpath?>icon_next.png') center 2px no-repeat; display:block; height:10px; margin-top:5px; margin-bottom:5px; }
		#myshouts_wrapper .prev em, #myshouts_wrapper .next em { display:none }

	</style>
	<script type="text/javascript">
		function changedemo(name,what)
		{
			if (name == 'submitbgcolor')
			{
				jQuery("#shout_submit").css("background-color",what.value);
			}
			else if ( name == 'submittextcolor' )
			{
				jQuery("#shout_submit").css("color",what.value);
			}
			else if ( name == 'submitbordercolor' )
			{
				jQuery("#shout_submit").css("border-color",what.value);
			}
			else if ( name.match(/text/) )
			{
				jQuery("."+name).css("color",what.value);
			}
			else
			{
				jQuery("."+name).css("background-color",what.value);
			}
		}
	</script>
	
	<div id="myshouts_styleadmin">
	<div id="myshouts_demo">
		<!-- wrapper -->
		<div id="myshouts_wrapper" class="bgcolor">
		<a href="javascript:void(0)" class="prev prevnextcolor" title="Previous Shouts"><em>prev</em></a>
			<div id="myshouts_shouts" class="theshouts">
			<ul>
				<li class="shoutrow shoutrowcolor shoutrowtextcolor">
					<dl>
						<dt class="shoutrownametextcolor">John</dt>
						<dd class="icons"><a href="#" rel="nofollow"><img src="<?php echo $shoutpath?>/email.png" alt="email" /></a></dd>
						<dd class="shout">
						<div class="bottom">
							Hi! This is my shout
						</div>
						</dd>
						<dd class="date datetextcolor">3 October, 2009</dd>
					</dl>				
				</li>
			</ul>
			</div>
			
			<a href="javascript:void(0)" class="next prevnextcolor" title="More Shouts"><em>next</em></a>		

			<div id="myshouts_formwrap">
			<div name="myshouts_form" id="myshouts_form" onsubmit="return false" method="post"  class="submitform">

			<div class="myshouts_title titletextcolor">Your Shout</div>
				<div class="name"><input type="text" size="30" name="name" id="shout-name" class="inputcolor inputtext inputtextcolor" value="Name"/></div>
				<div class="email"><input type="text" size="30" name="email" id="shout-email" class="inputcolor inputtext inputtextcolor" value="Email" /></div>
				<div class="website"><input type="text" size="30" name="website" id="shout-website" class="inputcolor inputtext inputtextcolor" value="Website" /></div>
				<div class="shout"><input type="text" size="30" name="shout" id="shout-shout" class="inputcolor inputtext inputtextcolor" /></div>
				
				<div class="submit">
					<input type="button" id="shout_submit" name="shout_submit" class="shoutsubmit" onclick="return shout_send()" onmouseover="this.className='shoutsubmit_over';" onmouseout="this.className='shoutsubmit';" value="submit"/>
				</div>
			</div>
			</div>
		</div>
		<!-- wrapper -->
	</div>

	<form method="post" id="styleform">
	<?php
		$Styles = unserialize(get_option('myshouts_styles'));
		//echo get_option('myshouts_styles');

		foreach( $shoutStyles as $sname => $stitle )
		{
		?>
		<div class="shout_style" style="overflow:hidden">
			<span style="float:left; margin-right:5px;"><input type="text" id="shout_style_<?php echo $sname?>" value="<?php echo($Styles[$sname]!=''?$Styles[$sname]:'')?>" class="iColorPicker" name="shout_style[<?php echo $sname?>]" onchange="changedemo('<?php echo $sname?>',this)" /></span> <strong><?php echo __($stitle) ?></strong>
		</div>
		<?php
		}
	?>
		<p class="submit">
			<input type="hidden" name="savetype" value="savestyles" />
			<input name="save" type="submit" value="Save" />
			<input type="hidden" name="action" value="save" />
		</p>
	</form>
	</div>
	<br />
	
	<h3>Shouts</h3>

	<?php
	$_page = isset($_GET['page']) && intval($_GET['page']) != '' ? $_GET['page'] : 1;	
	
	myshouts_manage_paged($_page, 10);

	echo '</div>'."\n";
}

function myshouts_manage_paged($_page, $perpage)
{
	global $wpdb, $shoutpath;
	
	$gettotal	= $wpdb->get_row("SELECT COUNT(*) FROM `".SHOUTDB."`",ARRAY_N);

	$totalpage	= ceil( $gettotal[0] / (float) $perpage);
	$limit		= ( ($_page-1) * $perpage).','.$perpage;
	
	if ( $gettotal[0] == 0 )
	{
		echo '<p class="message">'.__('No shouts yet').'</p>'."\n";
		return;
	}
			
	if ( $_page > $totalpage )
	{
		echo '<p class="message error">Invalid Page</p>';
		return;
	}

	$nav = myshouts_make_paging($_SERVER['PHP_SELF'].'?page=myshouts.php&page=', 10, $totalpage, $_page);

	$shouts = $wpdb->get_results("SELECT 
										
										*
								FROM
										`".SHOUTDB."`
							
								ORDER BY id DESC
																
								LIMIT ".$limit);

	?>
	<script type="text/javascript" src="<?php echo $shoutpath?>jquery.inplace.js"></script>
	<script type="text/javascript">
		function sure()
		{
			var ask = confirm('Are you sure?');
			if (ask == true)
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		jQuery(document).ready(function()
		{			
			jQuery(".captions").editInPlace({
				url: "<?php echo $_SERVER['PHP_SELF']?>?page=myshouts.php&do=editshout",
				saving_image: "<?php echo $shoutpath?>ajax_loading_small.gif"
			  });
		});
		
	</script>
	<style style="text/css">
		.inplace_field { width: 80%; display:block; }
	</style>
	<table class="widefat comments fixed" cellspacing="0">
	<thead>
		<tr>
			<th>Shout</th>
			<th>By</th>
			<th>When</th>
			<th>Option</th>
		</tr>
	</thead>

	<tbody id="the-shout-list">
	<?php
		foreach ($shouts as $shout) 
		{
			myshouts_manage_row($shout);
		}

		if ( empty($shouts) )
		{
			echo '<tr><td colspan="4">No shouts yet</td></tr>'."\n";
		}
	?>
	</tbody>
	</table>

	<?php

	echo '<br />'.$nav;
}

function myshouts_make_paging($link,$showpages='4',$totalpage,$curpage)
{
	//global $func;

	$nav = '';
	
	$prev_page = $curpage != 1 ? ($curpage - 1) : '';
	$next_page = $curpage != $totalpage ? ($curpage + 1) : '';

	if ( $totalpage > 1 && $curpage != 1 )
	{
		$nav .= ' <a href="'.$link.'1/" title="Go back to first page">&laquo;</a>';
	}
	
	if ($curpage > 1)
	{
		$nav .= '<a href="'.$link.''.$prev_page.'/" title="Previous Page">< Prev</a> ';			
	}

	if ($totalpage > 1)
	{
		if ( $totalpage > $showpages )
		{
			$showed = ($totalpage+1) - $showpages;
		}
		else
		{
			$showed = 1;
		}
		
		$last_half	= ceil($showpages/2);
		$first_half = $showpages - $last_half;
		
		for ($i = 1; $i<=$totalpage; $i++)
		{	
			if ($i+$first_half >= $curpage && $i <= $curpage+$last_half)
			{
				if ( $i == $curpage )
				{
					$nav .= '<strong>'.$i.'</strong>';
				}
				else
				{
					$nav .= '<a href="'.$link.''.$i.'/">'.$i.'</a>';
				}
			}
		}
	}

	if ($curpage < $totalpage)
	{
		$nav .= ' <a href="'.$link.''.$next_page.'/" title="Next Page">Next ></a>';
	}

	if ($totalpage > 1 && $curpage != $totalpage)
	{
		$nav .= '<a href="'.$link.''.$totalpage.'" title="Last Page">&raquo;</a>';
	}
	
	if ( $totalpage == 1 )
	{
		$nav = '<b>1</b>';

	}

	$pagenav = '<div class="pagination">'.$nav.'</div>';
	
	return $pagenav;
}

function myshouts_manage_row($row)
{
	?>
	<tr>
		<td><span class="captions"  id="caption-<?php echo $row->id?>"><?php echo $row->message?></span></td>
		<td><strong><?php echo $row->name?></strong><br /><?php echo $row->email?></td>
		<td><?php if ( $row->date > 0 ) echo time_ago($row->date)?></td>
		<td><a href="<?php echo $_SERVER['PHP_SELF'].'?page=myshouts.php&amp;do=deleteshout&amp;id='.$row->id?>" onclick="return sure()">Delete</a></td>
	<?php
}

function myshouts_admin_submits()
{
	global $wpdb;
	
	if ( $_POST['savetype'] == 'savestyles' )
	{
		$myshouts_styles = serialize($_POST['shout_style']);
		update_option('myshouts_styles', $myshouts_styles);

		wp_redirect($_SERVER['PHP_SELF'].'?page=myshouts.php&updated=1');
	}

	if ( $_POST['savetype'] == 'saveoptions' )
	{
		$_POST['myshouts_total'] = (int) $_POST['myshouts_total'];
		$_POST['myshouts_useaccordion'] = (int) $_POST['myshouts_useaccordion'];
		$_POST['myshouts_accordionshow'] = (int) $_POST['myshouts_accordionshow'];
		$_POST['myshouts_ignorestyles'] = (int) $_POST['myshouts_ignorestyles'];
		$_POST['myshouts_gravatar'] = (int) $_POST['myshouts_gravatar'];

		update_option('myshouts_ignorestyles',$_POST['myshouts_ignorestyles']);
		update_option('myshouts_total', $_POST['myshouts_total']);
		update_option('myshouts_useaccordion',$_POST['myshouts_useaccordion']);
		update_option('myshouts_accordionshow',$_POST['myshouts_accordionshow']);
		update_option('myshouts_gravatar',$_POST['myshouts_gravatar']);

		wp_redirect($_SERVER['PHP_SELF'].'?page=myshouts.php&updated=1');
	}

	if ( $_GET['do'] == 'deleteshout' )
	{
		$id = (int) $_GET['id'];

		$wpdb->query("DELETE FROM `".SHOUTDB."` WHERE id='".$id."'");

		wp_redirect($_SERVER['PHP_SELF'].'?page=myshouts.php');
	}

	if ( $_GET['do'] == 'editshout' )
	{
		$getid = str_replace("caption-","",$_POST['element_id']);
		$id = (int) $getid;

		$shout = $wpdb->get_row("SELECT * FROM `".SHOUTDB."` WHERE id='".$id."'",ARRAY_N);

		if ( !is_array($shout) )
		{
			die( 'Invalid Shout ID' );
		}

		$caption = trim($_POST['update_value']);

		$wpdb->query("UPDATE `".SHOUTDB."` SET message='".$caption."' WHERE id='".$id."'");
		die( $caption );
	}
}

function time_ago($timestamp)
{
	$difference = time() - $timestamp;
	$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	$lengths = array("60","60","24","7","4.35","12","10");
	for($j = 0; $difference >= $lengths[$j]; $j++)
	 $difference /= $lengths[$j];
	$difference = round($difference);
	if($difference != 1) $periods[$j].= "s";
	$text = "$difference $periods[$j] ago";
	return $text;
}
?>