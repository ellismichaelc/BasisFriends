<!DOCTYPE html>
<html lang="en">
 <head>
  <title>Basis Friends</title>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <script type="text/javascript" language="javascript" src="datatables/media/js/jquery.js"></script>
  <script type="text/javascript" language="javascript" src="datatables/media/js/jquery.dataTables.js"></script>
  <script type="text/javascript" language="javascript" src="js/bootstrap.min.js"></script>
  <script type="text/javascript" language="javascript" src="js/moment.min.js"></script>
  <script type="text/javascript" language="javascript" src="js/transit.js"></script>
  <script type="text/javascript" language="javascript" src="js/swap.js"></script>
  
  <style type="text/css" title="currentStyle">
	@import "datatables/media/css/demo_page.css";
	@import "datatables/media/css/demo_table.css";
	@import "style/font.css";
	@import "css/bootstrap.min.css";
	@import "css/bootstrap-theme.min.css";
  </style>

  <style>
  	body {
	  	font-family: museo-sans;
  	}

	body {
	  padding-top: 40px;
	  padding-bottom: 30px;
	}
  	
  	div#container {
	  padding: 15px;
	  overflow: auto;
  	}
  	
  	#user_list_info, #user_list_paginate {
	  	margin-top: 10px;
  	}
  	
  	#user_list_filter, #user_list_length {
	  	margin-bottom: 10px;
  	}

	/* Large desktops and laptops */
	@media (min-width: 1200px) {

	}
	
	/* Portrait tablets and medium desktops */
	@media (min-width: 992px) and (max-width: 1199px) {

	}
	
	/* Portrait tablets and small desktops */
	@media (min-width: 768px) and (max-width: 991px) {

	}
	
	/* Landscape phones and portrait tablets */
	@media (max-width: 767px) {

	}
	
	/* Landscape phones and smaller */
	@media (max-width: 480px) {

	}
  </style>
		
  <script>
	$(document).ready(function() {
		var alerted = false;
		
		$('#refresh').click(function() {
			getData();
		});

		$('#test_trans').click(function() {
			var div1 = $('#data_user_1');
			var div2 = $('#data_user_2');
			
			swapDivs(div1, div2);
		});
		
		function swapDivs(div1, div2) {
			div1 = $(div1);
			div2 = $(div2);
			both = $.merge(div1, div2);
			
			if(!div1.length > 0 || !div2.length > 0) {
				console.log("One of the two div selectors is empty, cannot swap.");
				return;
			}
			
			height1 = div1.find("#habit_content").height();
			height2 = div2.find("#habit_content").height();
			
			console.log(height1);
			console.log(height2);
			
			//div2.css('height', height1 + 'px');
			//div1.css('height', height2 + 'px');
			
			started = false
			
			$('#data_user_3').hide();
			
			both.find("#habit_content").transition({ height: '0', opacity: 0 }, 1000, function() {
			
				// Duplicate run fix
				if(started) return;
				started = true;
				
				div1.swap({
					target: div2.attr('id'),
					opacity: "0.5", 
					speed: 1000, 
					callback: function() { 

					} 
		        });
		        
		        setTimeout(function() {
					// okayyyy now we need to ACTUALLY swap the divs and then return them to relative positoning
					
					div1.find("#habit_content").transition({ height: height1 + 'px', opacity: 1 }, 1000);
					div2.find("#habit_content").transition({ height: height2 + 'px', opacity: 1 }, 1000);
					
					
					
		        }, 1000);
				
			});
		}
	
	    function getData() {
	    	$('#status').html('Updating..');
	    	
		    $.get("data_v2.php", function(data) {
		    	updated = moment(data.last_update).unix();
		    	now     = moment().unix();
		    	
			    $('#status').html('Data Updated: ' + formatDuration(now - updated, " hours", " minutes", false, " ", true, false, "just now", " ago"));
			    
			    $(data.users).each(function(i, user) {
					updateRow(user);
			    });
		    }, "json");
	    }
	    
	    function createHabit(user, habit) {
			var new_row = $('#data_user_' + user.id + ' #habit_template').clone().appendTo('#data_user_' + user.id + ' #habit_content');
			new_row.attr('id', 'habit_' + habit.template).addClass("habit");
			
			return new_row;
	    }
	    
	    function log(text) {
		    $('#log').append(text + "<br>");
	    }
	    
	    function updateHabit(user, habit) {
			var row = $('#data_user_' + user.id + " #habit_" + habit.template);
			
			if(row.length == 0) row = createHabit(user, habit);

			//if(habit.score_units == habit.goal_units) habit.score_units = '';
			
			var score = habit.score;
			var goal  = habit.goal;
			
			if(habit.units == "timedelta") {
				goal  = formatDuration(goal, "h", "m", "s", " ", true, true, "none");
				score = formatDuration(score, "h", "m", "s", " ", true, true, "none");				
			} else {
				goal = goal + " " + habit.units;
			}
			
			row.data('habit', habit);
			row.attr('data-percent', habit.percent);
			

			var class_name   = "";
			var percent_text = habit.percent + "%";
			
			//console.log(user.id + " - " + habit.title + " - " + habit.state);
			
			if(habit.state == "1") {
				// In progress (time sensitive)
				class_name = "progress-bar-warning";
				
			} else if(habit.state == "4" || ( habit.score >= habit.goal && habit.type == "below" )) {
				// Failed (time sensitive)
				class_name    = "progress-bar-danger";
				percent_text  = "Failed";
				habit.percent = 100;
			
			} else if(habit.state == "13" || habit.state == "11" || habit.state == "9")  {
				// Done
				class_name = "progress-bar-success";
				percent_text = "Complete!"
			
			} else {
				// In progress
				class_name = "progress-bar-info";
			}
			
			row.find("[id='habit_progress']").removeClass().addClass('progress-bar').addClass(class_name);
			

			// bug workaround
			// Had to do it this way. row.find() was working everywhere except on iphones safari,
			// was even working in iphone simulator. just not actual devices.
			
			row.find("[id='habit_title']").html(habit.title);
			row.find("[id='habit_desc']").html(habit.desc);
			row.find("[id='habit_progress_label']").html(score + " of " + goal);
			row.find("[id='habit_progress_preface']").html(habit.preface);
			row.find("[id='habit_progress_percent']").html(percent_text);
			row.find("[id='habit_progress']").css("width", habit.percent + "%");
			row.find("[id='habit_icon']").attr("src", habit.icon);
			

			if(habit.score == 0) row.attr('data-active', 0).fadeOut();
			else			     row.attr('data-active', 1).fadeIn();
			
			// reorder rows!
			var all_rows = $('#data_user_' + user.id + ' .habit');
			
			all_rows.each(function(i,row2) {
				if(habit.percent > $(row2).attr('data-percent')) {
					$(row2).before(row);

					return false;
				}
			});
	    }
	    
	    function updateRow(user) {
			var row = $('#data_user_' + user.id);
			
			if(row.length == 0) row = createRow(user);
			
			row.data("syncd", user.syncd);
			
			row.find("#data_name").html(user.name);
			row.find("#data_pulse").html(user.pulse);
			row.find("#data_cals").html(user.cals);
			row.find("#data_steps").html(user.steps);
			row.find("#data_syncd").html(moment(user.syncd).fromNow());
			
			if(user.active == "1") row.find("#data_active").fadeOut();
			if(user.active == "0") row.find("#data_active").fadeIn();

			$.each(user.habits, function(i, val) {
				updateHabit(user, val);
			});
			
			if(row.find('#habit_content .habit[data-active=1]').length > 0) row.find('#habit_content').fadeIn();
			else															row.find('#habit_content').fadeOut();
			
			
			
			row.fadeIn();
	    }
	    
	    function createRow(user) {
			var new_row = $('#data_template').clone().appendTo('#data_content');
			new_row.attr('id', 'data_user_' + user.id).addClass("user_row").addClass("user_" + user.id);
			
			return new_row;
	    }
	    
	    function updateSyncd() {
			rows = $('[id^="data_user_"]');
			
			rows.each(function(i, row) {
				syncd  = $(row).data("syncd");
				format = moment(syncd).fromNow();
				
				$(row).find("#data_syncd").html(format);
			});
	    }
	    
	    // Will turn num seconds/minutes into h/m/s with sep as separator
	    // use is_mins = true if youre passing minutes
	    // zero_return is the string to return if h/m/s are all empty and exclude_empty is true
	    function formatDuration(num, h_format, m_format, s_format, sep, exclude_empty, is_mins, zero_return, suffix) {
		    duration = is_mins ? minutesFormat(num) : secondsFormat(num);
		    
		    ret_val = "";
		    
			if(!exclude_empty || (exclude_empty && duration.h) && h_format) ret_val = duration.h + h_format;
			if(!exclude_empty || (exclude_empty && duration.m) && m_format) ret_val = (ret_val != "" ? ret_val + sep : "") + duration.m + m_format;
			if(!exclude_empty || (exclude_empty && duration.s) && s_format) ret_val = (ret_val != "" ? ret_val + sep : "") + duration.s + s_format;
			
			if(ret_val == "") return zero_return;
			
			if(!suffix) suffix = '';
			
			return ret_val + suffix;
	    }
	    
	    function minutesFormat(mins) {
		    return secondsFormat(mins * 60);
	    }

		function secondsFormat(secs) {
		    var hours = Math.floor(secs / (60 * 60));
		   
		    var divisor_for_minutes = secs % (60 * 60);
		    var minutes = Math.floor(divisor_for_minutes / 60);
		 
		    var divisor_for_seconds = divisor_for_minutes % 60;
		    var seconds = Math.ceil(divisor_for_seconds);
		   
		    var obj = {
		        "h": hours,
		        "m": minutes,
		        "s": seconds
		    };
		    
		    return obj;
		}
	    
	    setInterval(getData, 60000);
	    getData();
	});
  </script>
 </head>
 <body role="document">
 
 	<div id="log" style="background:#eee"></div>
	<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				
				<a class="navbar-brand" href="#">Basis Friends</a>
				
			</div>
			
			<div class="navbar-collapse collapse">
				<ul class="nav navbar-nav">
					<li class="active"><a href="#">Overview</a></li>
				</ul>
          
				<form class="navbar-form navbar-right" role="form">
					<div class="form-group">
						<input type="text" placeholder="Email" class="form-control">
					</div>
					
					<div class="form-group">
						<input type="password" placeholder="Password" class="form-control">
					</div>
					
					<button type="submit" class="btn btn-success">Sign in</button>
				</form>
			</div><!--/.navbar-collapse -->
		</div>
	</div>
	
	<div class="container" role="main">
		<div class="page-header">
			<h1>Basis Friends <a href="javascript://" id="test_trans">[ test transition ]</a></h1>
			
			<h5 style="color: #666;">
				<span class="glyphicon glyphicon-refresh" style="cursor: pointer; font-size: 13px;" id="refresh"></span>
				<span id="status" style="margin-left: 5px; vertical-align: top;"></span>
			</h5>
		</div>


		<div class="row" id="data_content">
			<div class="col-xs-12 col-sm-6" id="data_template" style="display:none;overflow:hidden;">
				<div class="panel panel-default">
					<div class="panel-heading">
						<span class="glyphicon glyphicon-user"></span>
						<span id="data_name" style="margin-left: 5px"></span>
						<span id="data_active" style="float: right; color: #999; font-size: 12px; margin-top: 2px;">
							<span style="font-size: 10px;" class="glyphicon glyphicon-time"></span>
							<span>hasn't sync'd today</span>
						</span>
					</div>
					 <div class="panel-body">
						<div class="row">
						  <div class="col-xs-4 col-sm-2"><b>Sync'd:</b></div>
						  <div class="col-xs-8 col-sm-4" id="data_syncd">-</div>
						  
						  <div class="col-xs-4 col-sm-2"><b>RHR:</b></div>
						  <div class="col-xs-8 col-sm-4" id="data_pulse">-</div>
						</div>

						<div class="row">
						  <div class="col-xs-4 col-sm-2"><b>Steps:</b></div>
						  <div class="col-xs-8 col-sm-4" id="data_steps">-</div>
						  
						  <div class="col-xs-4 col-sm-2"><b>Calories:</b></div>
						  <div class="col-xs-8 col-sm-4" id="data_cals">-</div>
						</div>
						
						<div class="row" id="habit_content" style="display:none; overflow:hidden;">
						  <div style="margin-top: 15px;">
							  <div id="habit_template" style="display:none;">
								  <div class="col-xs-7 col-sm-4" id="habit_title"></div>
								  <div class="col-xs-5 col-sm-8">
								  	<div style="float:left;">
									  	<img id="habit_icon" style="width:18px;margin-right:3px;" />
								  	</div>
									<div class="progress progress-striped active" style="margin-bottom: 2px;">
									  <span style="position: absolute; margin-left: 8px; font-size:11px; margin-top:3px; color:#444;" id="habit_progress_percent"></span>
								      
										
									  <div class="progress-bar" id="habit_progress" role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 75%"></div>
								    </div>
								  </div>
								  
								  <div class="col-xs-0 col-sm-4"></div>
								  <div class="col-xs-12 col-sm-8" style="margin-bottom:8px; font-size:12px; color:#777;">
									  <span id="habit_desc" style="float:left;"></span>
									  <span style="float:right;">
									  	<span id="habit_progress_preface" class="hidden-xs"></span>
									  	<span id="habit_progress_label"></span>
									  </span>
								  </div>
								</div>
							</div>
						</div>
					 </div>
				</div>
			</div>
		</div>
	</div>
 </body>
</html>