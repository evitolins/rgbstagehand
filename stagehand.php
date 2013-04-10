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

// Config Data
$json = file_get_contents( "config.json") ; 
$config = json_decode( $json, true ); 
$stages = $config["stages"];
$cmds = $config["cmds"];
$outputs = $config["output"];

// Server Data (maybe this be hardcoded within config.json?)
$baseUrl = explode( ":" , $_SERVER['HTTP_HOST'] );
$address = $baseUrl[0];

// User Input
$stage = $_GET['stage'];
$cmd = $_GET['cmd'];
$output = $_GET['output'];

// Build Output Array
$r = array();
foreach ( $stages as $k => $v ) {
	// Skip unneeded stage
	if ( isset( $stage ) && $stage != $k && $stage != "") {
		continue;
	}

	// We add a ":" to differentiate a "stage-specific command" from a "batch command" 
	$cmdSplit = explode( ":", $cmd );
	$shell_return = "";
	$shell_cmd = "";

	// Batch-able Cmd
	//  If the command contains a ":", then assume it is Scene-Specfic and skip.
	if (isset( $cmd ) && count( $cmdSplit ) == 1 ) {
		$shell_cmd = $cmds[$cmd];
	}

	// Scene-Specific Cmd
	//  If the command contains a ":", then assume it is Scene-Specfic and skip.
	elseif (isset( $cmd ) && count( $cmdSplit ) == 2 ) {
		$shell_cmd = $stages[$stage]['cmds'][$cmdSplit[1]];
	}

	// Gather Data
	chdir ( $v['path'] );
	$data = array();
	$data['stage'] = $v['name'];
	$data['path'] = $v['path'];
	$data['port'] = $v['port'];
	$data['cmd'] = $cmds[$cmd];
	$data['branch'] = shell_exec( "git branch");
	$data['status'] = shell_exec( $shell_cmd . " 2>&1"); //str_error merged

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
	    <title>Stagehand</title>
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
						<span style="color:#D16400;">rgb</span>Stagehand
						<i class="icon-sitemap title_icon" style="color: rgb(209, 100, 0); font-size: 32px; padding: 0px; margin-left: 5px;"></i>
					</h1>

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
						<option value=""></option>
						<?
						// List Commands
						echo "<optgroup label='Batch Options'>";
						foreach ( $cmds as $k => $v ) {
							echo "<option " . ($k == $cmd && $cmd != "" ? "SELECTED" : "")  . " value='" . $k . "'>" . $v . "</option>";
						}
						echo "</optgroup>";
						?>
						
						<?
						// List Stage-Specific Commands, if appropriate
						if ( (isset( $stage ) && $stage != "") ) {
							$stgCmds = $stages[$stage]['cmds'];
							echo "<optgroup label='Stage-Specific Options'>";
							foreach ( $stgCmds as $k => $v ) {
								echo "<option value='" . $stage .":". $k . "'>" . $v . "</option>";
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
			</div>
			<div id="content">
				<?
				// List Stage(s) , perform Command and return Output
				foreach ( $stages as $k => $v ) {
					// Skip unneeded stage
					if ( isset( $stage ) && $stage != $k && $stage != "") {
						continue;
					}

					// Set Environment Location
					chdir ( $v['path'] );

					// Display branch data
					$branch = shell_exec( "git branch");
					echo "<h2><i class='icon-hdd stage_icon'></i><span class='stage_title' onClick=\"window.location.href = addParameter(window.location.href, 'stage', " . $k .");\">\"" . $v['name'] . "\"</span> ";
					if ( $branch != "" ) {
						echo "<span class='branch'><i class='icon-github-sign branch_icon'></i>" . $branch  . "</span>";
						// Detect "detached HEAD"
						//  If nothing is returned, assume that the head is detached
						if ( shell_exec( "git symbolic-ref -q HEAD") == "" ) {
							echo "<span class='detachedHead'><i class='icon-exclamation-sign branch_icon'></i>HEAD Detached</span>";
						};					
					}
					echo "<span style='float:right;'>
						<i class='icon-home http_icon' onClick=\"window.open('http://" . $address . ":" . $v['port'] . "');\"></i>
					</span>";
					echo "</h2>";

					// We add a ":" to differentiate a "stage-specific command" from a "batch command" 
					$cmdSplit = explode( ":", $cmd );
					$shell_return = "";
					$shell_cmd = "";

					// Batch-able Cmd
					//  If the command contains a ":", then assume it is Scene-Specfic and skip.
					if (isset( $cmd ) && count( $cmdSplit ) == 1 ) {
						$shell_cmd = $cmds[$cmd];
					}

					// Scene-Specific Cmd
					//  If the command contains a ":", then assume it is Scene-Specfic and skip.
					else if (isset( $cmd ) && count( $cmdSplit ) == 2 ) {
						$shell_cmd = $stages[$stage]['cmds'][$cmdSplit[1]];
					}

					// Run Cmd
					$shell_return = shell_exec( $shell_cmd . " 2>&1"); //str_error merged

					// Display Cmd Output
					if ($shell_return != "") {
						echo "<div class='cmd_output_light'>";
						echo "<h3>" . $shell_cmd . "</h3>";
						echo "<pre style='border:none;'>" . htmlspecialchars($shell_return) . "</pre>";
						echo "</div>";
					}
				}
				?>
			</div>
			<div id="footer">
				<span>view <a href="/config.json">config.json</a></span>
			</div>
		</div>
		<script type="text/javascript" src="/js/stagehand.js"></script>
	</body>
</html>

