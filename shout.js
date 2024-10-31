function shoutcheck(s,v)
{
	if ( s.value == '' )
	{
		s.value=v;
	}
}

function shoutblur(s,v)
{
	if ( s.value == v )
	{
		s.value = '';
	}
}

function shout_send() 
{
	//untuk makhluk2 yang tak reti nak write
	if (jQuery("#shout-name").val() == 'Name' || jQuery("#shout-email").val() == 'Your Email')
	{
		alert('Fill in both Name and Email');
		return false;
	}
	
	var myshout = jQuery("#shout-shout").val();
	if (  myshout == '' )
	{
		alert('You must write something first.');
		return false;
	}

	if ( myshout.length < 3 )
	{
		alert('Too short! Must be atleast 3 chars.');
		return false;
	}

	//serialize data
	 var data = jQuery("#myshouts_form").serialize();
	
	 var html = '';
	 var html = jQuery.ajax({
					   type: "POST",
					   url: post_file+"?do=sendshout",
					   data: data,
					   async: false
					 }).responseText;
	
	var check = html.search(/li/);
	
	//tade error
	if (check == -1)
	{
		alert(html);
	}
	else
	{
		/*jQuery.ajax({
				type: "GET",
				url:  post_file+"?do=gettime",
				data: '',
				success: function(ot){
					jQuery("#myshouts_shouts").attr("rel",ot);
			   }
		 });*/

		jQuery("#myshouts_shouts ul").prepend(html);
		jQuery("#myshouts_shouts ul li:first").slideDown('slow');

		jQuery("#shout-shout").val(" ");
	}

	
	//and still return false. kekeke
	return false;
}


function myshouts_check_stream()
{
	var since = jQuery("#myshouts_shouts").attr("rel");
	var ts	  = jQuery.ajax({
				type: "GET",
				url:  post_file+"?do=gettime",
				data: ''
				}).responseText;

	jQuery.ajax({
			type: "POST",
			url: post_file+"?do=newshouts",
			data: 'time='+since,
			success: function(html){
				if (html.length>10)
				{
					jQuery("#myshouts_shouts").attr("rel",ts);

					jQuery("#myshouts_shouts ul").prepend(html);
					jQuery("#myshouts_shouts ul li.new").slideDown('fast');
					jQuery("#myshouts_shouts ul li.new").removeClass("new");					
				}
		   }
	 });

	setTimeout(function(){ myshouts_check_stream(); },5000);
}