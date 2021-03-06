<?php
/*
*  
*   stagehand.php
*	=======================================================================
*   A simple tool for issuing shell commands to staging servers.
*	- Allows preset shell commands to be run on selected staging servers
*   - Has quick access to basic git repo statuses like current branch.
*
*
*   Configuration
*   ```````````````````````````````````````````````````````````````````````
*   Stagehand is configured by providing the file 'config.json' within
*   the same directory.  Please refer to the README.txt for more
*   information about it's usage. ***
*
*
*	Default Behavior
*   ```````````````````````````````````````````````````````````````````````	
*	- stage (all = default)
*	- cmd (0 = default)
*	- output (0 = default)
*
*
*   API Examples
*   ```````````````````````````````````````````````````````````````````````
*	- View list of available stages and their current git branch.
*		?
*		?stage=
*
*	- View stage "2", run cmd "0" (default), and return HTML (default).
*		?stage=2
*
*	- View stage "1", run cmd "1", and return JSON formatted data.
*		?stage=1&cmd=1&output=json
*
*
*   *** WARNING ***
*   ```````````````````````````````````````````````````````````````````````
*   This tool can EASILY expose direct access to your server to anyone that
*   has access it.  Please choose your list of "cmds" wisely. Make sure to
*	utilize stage-specific cmds for actions that could be especially
*	devistating to run in batch form.
*
*/

require 'utils.php';

// Config Data
$json     = file_get_contents( "config.json") ; 
$config   = json_decode( $json, true );
$options  = $config["options"];
$stages   = $config["stages"];
$cmds     = $config["cmds"];
$alerts   = $config["alerts"];
$outputs  = $config["output"];

// Server Data (maybe this be hardcoded within config.json?)
$baseUrl  = explode( ":" , $_SERVER['HTTP_HOST'] );
$address  = $baseUrl[0];

// User Input
$stage    = $_GET['stage'];
$cmd      = $_GET['cmd'];
$output   = $_GET['output'];

$stgCmds = $stages[$stage]['cmds'];

// Cmd Scope Logic
$cmdSplit = explode( ":", $cmd );
$cmd_scope = "";
$cmd_idx = "";
// Batch-able Cmd
//  If the command does not contain a ":", then assume a Batch Command.
if (isset( $cmd ) && count( $cmdSplit ) == 1 ) {
	$cmd_idx = $cmd;
	$cmd_scope = "batch";
	$cmd_current = $cmds[$cmd];
}
// Scene-Specific Cmd
//  If the command contains a ":", then assume it is Scene-Specfic Command.
elseif (isset( $cmd ) && count( $cmdSplit ) == 2 ) {
	$cmd_idx = $cmdSplit[1];
	$cmd_scope = "stage";
	$cmd_current = $stages[$stage]['cmds'][$cmdSplit[1]];
}

// Build Output Array
$r = array();
foreach ( $stages as $k => $v ) {
	// Skip unneeded stage
	if ( isset( $stage ) && $stage != $k && $stage != "") {
		continue;
	}

	if (!is_dir ( $v['path'])) {
		continue;
	}

	// Gather Data
	chdir ( $v['path'] );
	$data = array();
	$data['stage']  = $v['name'];
	$data['path']   = $v['path'];
	$data['domain'] = $v['domain'];
	$data['port']   = $v['port'];
	$data['cmd']    = $cmds[$cmd];
	$data['branch'] = shell_exec( "git branch");
	$data['status'] = shell_exec( $cmd_current . " 2>&1"); //str_error merged

	//Record Data
	array_push($r, $data);
}

// Output JSON
if ( $outputs[$output] == "json" ) {
	echo json_encode($r);
	exit;
}

// Output XML (TODO: not the best output currently)
if ( $outputs[$output] == "xml" ) {
	$xml = new SimpleXMLElement( '<root/>' );
	array_walk_recursive( $r, array($xml, 'addChild') );
	print $xml->asXML();
	exit;
}

// Output HTML
?>
<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="utf-8">
	    <title>Stagehand<?php if (!empty($options['subtitle'])) { echo " - ".$options['subtitle']; } ?></title>
	    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	    <!-- Bootstrap -->
	    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">

		<!-- For IE -->
		<!--[if IE]><link rel="shortcut icon" href="img/favicon.ico"><![endif]-->
		<!-- For Modern Browsers with PNG Support -->
		<link rel="icon" type="image/png" href="img/favicon.png">
		<!-- Apple 	Favicon without reflective shine -->
		<link rel="apple-touch-icon-precomposed" sizes="114×114" href="img/apple-touch-icon-114×114-precomposed.png">
		<link rel="apple-touch-icon-precomposed" sizes="72×72" href="img/apple-touch-icon-72×72-precomposed.png">
		<link rel="apple-touch-icon-precomposed" href="img/apple-touch-icon-57x57-precomposed">

	    <link rel="stylesheet" href="css/normalize.css">
	    <link rel="stylesheet" href="css/stagehand.css">
	    <link rel="stylesheet" href="css/font-awesome.min.css">
	    <!--[if IE]>
	        <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	    <![endif]-->
	</head>
	<body id="home">
		<div id="root">
			<div id="header">
				<div id="menu">
					<h1 id="title">
						<span>Stagehand</span>
						<i class="icon-sitemap title_icon"></i>
						<span><?php if (!empty($options['subtitle'])) { echo $options['subtitle']; } ?></span>
					</h1>
 <?
                    //////////
					// Menu //
					//////////
 ?>
					<div class="menu_item">
						output: <select onChange="window.location.href = addParameter(window.location.href, 'output', value);">
						<?
						// List Output Formats
						foreach ( $outputs as $k => $v ) {
							echo "<option " . ($k == $output ? "SELECTED" : "") . " value='" . $k . "'>" . $v . "</option>";
						}
						?>
						</select>
					</div>

					<div class="menu_item">
						cmd: <select onChange="window.location.href = addParameter(window.location.href, 'cmd', value);">
						<option value="">clear</option>
						<?
						// List Commands
						echo "<optgroup label='Batch Options'>";
						foreach ( $cmds as $k => $v ) {					
							echo "<option " . ($k == $cmd_idx && $cmd_idx != "" && $cmd_scope == "batch" ? "SELECTED" : "")  . " value='" . $k . "'>" . $v . "</option>";
						}
						echo "</optgroup>";
						// List Stage-Specific Commands, if appropriate
						if ( (isset( $stage ) && $stage != "") ) {
							echo "<optgroup label='Stage-Specific Options'>";
							foreach ( $stgCmds as $k => $v ) {
								echo "<option " . ($k == $cmd_idx && $cmd_idx != "" && $cmd_scope == "stage" ? "SELECTED" : "")  . " value='" . $stage .":". $k . "'>" . $v . "</option>";
							}
							echo "</optgroup>";
						}
						?>
						</select>
					</div>

					<div class="menu_item">
						stage: <select onChange="window.location.href = addParameter(window.location.href, 'stage', value);">
						<option value="">all</option>
						<option disabled>` ` ` ` ` ` ` `</option>
						<?
						// List Stages
						foreach ( $stages as $k => $v ) {
							echo "<option " . ($k == $stage && $stage != "" ? "SELECTED" : "") . " value='" . $k . "'>" . $v['name'] . "</option>";
						}
						?>
						</select>
					</div>
				</div>

				<div id="submenu_container">
					<div id="submenu">
					  <button href="#" class="btn btn-mini"><i class="icon-plus"></i> Add New Stage</button>
					  <button href="#" class="btn btn-mini"><i class="icon-list"></i> AutoDeploy Logs</button>
					  <button href="#" class="btn btn-mini"><i class="icon-briefcase"></i> Server Status</button>
					</div>
				</div>

			</div>
			<div id="content">
				<?
				// List Stage(s) , perform Command and return Output
				foreach ( $stages as $k => $v ) {
					// Skip unneeded stage
					if ( isset( $stage ) && $stage != $k && $stage != "") {
						continue;
					}

					if (!is_dir ( $v['path'])) {
						continue;
					}

					// Set Environment Location (ALL cmds and alerts should run from this path)
					chdir ( $v['path'] );

					// Display stage data
					echo "<h2>";

						// Display Title
						echo "<i class='icon-folder-close stage_icon'></i><span class='stage_title' onClick=\"window.location.href = addParameter(window.location.href, 'stage', " . $k .");\"> " . $v['name'] . "</span>";

						if (!empty($v['domain'])) {
							$web_link = "https://" . $v['domain'];
							$web_link .= (!empty($v['port'])) ? ":" . $v['port'] : "";
							echo " <a class='web_link' href='".$web_link."'><i class='icon-globe quick_icon'></i></a>";						
						}

						echo "<div class='alerts' style='float:right;'>";

	                    ////////////
						// Alerts //
						////////////
						// Git Alerts, only if a branch exists						
						$shell_return = shell_exec( $cmd_current . " 2>&1"); //str_error merged
						$gitExists = shell_exec( "[ -d .git ] && echo 'true'");
						if ( $gitExists != "" ) {
							// Run Cmds and Alerts			
							$describe = shell_exec( "git describe");
							$branch = shell_exec( "git rev-parse --abbrev-ref HEAD");
							$shaAbbrev = shell_exec( "git rev-list -n1 --abbrev-commit HEAD" );
							$attached_head = shell_exec( "git symbolic-ref -q HEAD");
							$local_changes = shell_exec( "git status --p");
							$last_fetch = shell_exec( "stat -c %Y .git/FETCH_HEAD");

							// Display last push/fetch
							//  If nothing is returned, assume that the head is detached
							if ( $last_fetch != "" ) {
								echo "<span class='tag'>Last pull/fetch " . prettyDateEpoch($last_fetch) . "</span>";
							}

							// Alert local changes
							if ( $local_changes != "" ) {
								echo "<span class='localChanges tag'><i class='icon-bell branch_icon'></i>Local Changes</span>";
							}

							if ( $branch != "" && $attached_head != "" ) {
								// Display Branch Data
								echo "<span class='branch tag'><i class='icon-code-fork branch_icon'></i>" . $branch  . "</span>";
							}

							// Detect "detached HEAD"
							//  If nothing is returned, assume that the head is detached
							else if ( $attached_head == "" ) {
								echo "<span class='localChanges tag'><i class='icon-bell branch_icon'></i>HEAD detached at " . $describe . "</span>";
							}

							if ( $shaAbbrev != "" ) {
								// Display Branch Data
								echo "<span class='commit_hash tag'>" . $shaAbbrev  . "</span>";
							}

						} else {
							// Git Not Found
							echo "<span class='detachedHead tag'>.git not found</span>";							
						}

						echo "</div>";

	                    ///////////////////
						// Quick Buttons //
						///////////////////
						// Link to Stage's homepage
						echo "<span style='float:right;'>
							
						</span>";

					echo "</h2>";

                    ////////////
					// Output //
					////////////
					if ($shell_return != "") {
						echo "<div class='cmd_output_light'>";
						echo "<h3>" . $cmd_current . "</h3>";
						echo "<pre>" . htmlspecialchars($shell_return) . "</pre>";
						echo "</div>";
					}
				}
				?>
			</div>
			<div id="footer">
				<span>view <a href="config.json">config.json</a></span>
			</div>
			<div class="system_info">
				<iframe src="serverStatus.php"></iframe>
			</div>
		</div>
    	<script src="vendor/bootstrap/js/bootstrap.min.js"></script>		
		<script type="text/javascript" src="js/stagehand.js"></script>
	</body>
</html>