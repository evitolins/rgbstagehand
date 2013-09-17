<?
$system = array(
    "diskspace" => `df -h`,
    "memory" => `egrep 'Mem|Cache|Swap' /proc/meminfo`
    );

$installed = array(
    "linux" => `uname -a`,
    "git" => `git --version`,
    "apache" => `apache2 -V`,
    "php" => `php --version`,
    "mysql" => `mysql --version`,
    "linux" => `uname -a`,
    "python" => `python -V`
    );

?>

<h2>System</h2>
<pre>
<? print_r($system); ?>
</pre>

<h2>Installed</h2>
<pre>
<? print_r($installed); ?>
</pre>