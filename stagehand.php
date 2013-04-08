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
*   Examples
*   ```````````````````````````````````````````````````````````````````````
*	- View list of available stages and their current git branch.
*		?
*		?stage=
*
*	- View stage "master", run cmd "0" (default), and return HTML (default).
*		?stage=master
*
*	- View stage "sandbox2", run cmd "1", and return JSON formatted data.
*		?stage=sandbox2&cmd=1&output=json
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

	$data = array();
	// Gather Data
	chdir ( $v );
	$data['stage'] = $k;
	$data['path'] = $v;
	$data['cmd'] = $cmds[$cmd];
	$data['branch']= shell_exec( 'git branch' );
	$data['status'] = shell_exec( $cmds[$cmd] );

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
			<div id="menu">
				<div class="menu_item">
					stage: <select onChange="window.location.href = addParameter(window.location.href, 'stage', value);">
					<option value="">all</option>
					<option disabled>` ` ` ` ` ` ` `</option>
					<?
					foreach ( $stages as $k => $v ) {
						echo "<option " . ($k == $stage ? "SELECTED" : "") . " value='" . $k . "'>" . $k . "</option>";
					}
					?>
					</select>
				</div>
				<div class="menu_item">
					cmd: <select onChange="window.location.href = addParameter(window.location.href, 'cmd', value);">
					<option value=""></option>
					<?
					foreach ( $cmds as $k => $v ) {
						echo "<option " . ($k == $cmd && $cmd != "" ? "SELECTED" : "")  . " value='" . $k . "'>" . $v . "</option>";
					}
					?>
					</select>
				</div>
				<div class="menu_item">
					output: <select onChange="window.location.href = addParameter(window.location.href, 'output', value);">
					<?
					foreach ( $outputs as $k => $v ) {
						echo "<option " . ($k == $output ? "SELECTED" : "") . " value='" . $k . "'>" . $v . "</option>";
					}
					?>
					</select>
				</div>
			</div>
			<div id="content">
				<h1><span style="color:#bbb;">rgb</span>Stagehand<i class='icon-sitemap title_icon'></i></h1>
				<p>
					A simple tool for issuing shell commands to staging servers.
				</p>
				<?
				// List Stages and Data
				foreach ( $stages as $k => $v ) {
					// Skip unneeded stage
					if ( isset( $stage ) && $stage != $k && $stage != "") {
						continue;
					}

					// Set Environment Location
					chdir ( $v );

					// Display branch data
					$branch = shell_exec( 'git branch' );
					echo "<h2><i class='icon-hdd stage_icon'></i>\"$k\" ";
					if ( $branch != "" ) {
						echo "<span class='branch'><i class='icon-github-sign branch_icon'></i>" . $branch  . "</span>";
					}
					echo "</h2>";

					// Run Cmd
					if (isset( $cmd )) {
						$status = shell_exec( $cmds[$cmd] );
						// Display Cmd Output
						if ($status != "") {
							echo "<div class='cmd_output'>";
							echo "<h3>" . $cmds[$cmd] . "</h3>";
							echo "<pre class='prettyprint' style='border:none;'>$status</pre>";
							echo "</div>";
						}
					}
				}
				?>
			</div>
			<div id="footer">
				<span>view <a href="/config.json">config.json</a></span>
			</div>
		</div>
		<script src="https://google-code-prettify.googlecode.com/svn/loader/run_prettify.js"></script>
		<link rel="stylesheet" href="css/prettify.skin.light.css">
		<script type="text/javascript" src="/js/stagehand.js"></script>
	</body>
</html>

