<?php
/**
 * Virtual Hosts Manager for EasyPHP DevServer [ www.easyphp.org ]
 * @author   Laurent Abbal <laurent@abbal.com>
 * @version  1.2
 * @link     http://www.easyphp.org
 */
?>

<style type="text/css" media="all">
	.add_vhost_warning {font-family:arial;font-size:12px;color:black;margin:10px 0px 0px 0px;padding:0px 0px 0px 0px;}
	.add_vhost_warning a {text-decoration:none;font-size:11px;color:#E4E4E4;background-color:#808080;margin:0px 10px 0px 0px;padding:0px 5px 0px 5px;-moz-border-radius:2px;-khtml-border-radius:2px;-webkit-border-radius:2px;border-radius:2px;}
	.add_vhost_warning a:hover {color:white;background-color:black;}
</style>

<?php
include_once('virtualhostsmanager_functions.php');

$module_i18n = array();
$module_i18n = array(
	"en"	=>	array(
		"add_vhost_warning_1"	=>	"Warning : the name is empty.",
		"add_vhost_warning_2"	=>	"Warning : the path is empty.",
		"add_vhost_warning_3"	=>	"Warning : the directory corresponding to the path you have chosen does not exist.",
		"add_vhost_warning_4"	=>	"Warning : the name can only contain alpha-numeric characters, dots, underscores and hyphens.",
		"add_vhost_warning_5"	=>	"Warning : this name, or a part of this name, is already used by the system.",
		"back"					=>	"back",
	),
	"fr"	=>	array(
		"add_vhost_warning_1"	=>	"Attention : le nom est vide.",
		"add_vhost_warning_2"	=>	"Attention : le chemin est vide.",
		"add_vhost_warning_3"	=>	"Attention : le r&eacute;pertoire correspondant au chemin que vous avez saisi n'existe pas.",
		"add_vhost_warning_4"	=>	"Attention : le nom ne peut contenir que des carat&egrave;res alphanum&eacute;riques, des points, tirets et tirets bas (underscore).",
		"add_vhost_warning_5"	=>	"Attention : ce nom, ou une partie de ce nom, est d&eacute;j&agrave; utilis&eacute; par le syst&egrave;me.",
		"back"					=>	"retour",
	),
);

//== ACTIVATE OR DESACTIVATE HOST NAME =======================================
if ((isset($_GET['to'])) and ($_GET['to'] == "onoff_host")) {

	// HOSTS FILE
		$hostsfile_array = read_hostsfile('file');

		$hash = ($_GET['hash'] == 'on') ? '#':'';
		$new_hostfile_content = '';
		foreach ($hostsfile_array as $line) {
			if((stripos($line,"127.0.0.1") !== false) and (stripos($line,$_GET['servername']) !== false)) {
				$new_hostfile_content .= $hash . "127.0.0.1  " . $_GET['servername'] . "\n";
			} else {
				$new_hostfile_content .= $line . "\n";
			}
		}

		// Backup old hosts file
		copy(get_hostsfile_dir() . "\hosts", get_hostsfile_dir() . "\hosts_" . date("Y-m-d@U"));

		// Save new hosts file
		file_put_contents(get_hostsfile_dir() . '\hosts', trim($new_hostfile_content));


	//INC_VIRTUALHOST.conf
	$vhosts_array = read_vhosts('easyphp_vhosts');
	$new_vhosts_content = '';
	$vhost_rows_array = array();
	$n = 0;
	while ($n < count($vhosts_array)) {
		$vhost_rows_array = explode("\n", $vhosts_array[$n][0]);
		if ($n == $_GET['num_virtualhost']) {
			if (strstr($vhost_rows_array[0], '#') !== FALSE) {
				// vhost is commented > we decomment
				foreach ($vhost_rows_array as $vhost_data) {
					$new_vhosts_content .=  str_replace('#', '', $vhost_data) . "\n";
				}
			} else {
				// vhost is not commented > we comment
				foreach ($vhost_rows_array as $vhost_data) {
					$new_vhosts_content .=  "#" . $vhost_data . "\n";
				}
			}
		} else {
			foreach ($vhost_rows_array as $vhost_data) {
				$new_vhosts_content .=  $vhost_data . "\n";
			}
		}
		$n++;
	}

	// Backup old inc_virtual_hosts.conf
	copy('../../binaries/apache/conf/inc_virtual_hosts.conf', '../../binaries/apache/conf/inc_virtual_hosts_' . date("Y-m-d@U") . '.conf');

	// Save new inc_virtual_hosts.conf
	file_put_contents('../../binaries/apache/conf/inc_virtual_hosts.conf', $new_vhosts_content);

	// trigger server restart
	file_put_contents('../../binaries/conf_files/httpd.conf', file_get_contents('../../binaries/conf_files/httpd.conf'));

	$redirect = "http://" . $_SERVER['HTTP_HOST'] . "/home/index.php";
	sleep(2);
	header("Location: " . $redirect);
	exit;
}
//============================================================================


//== DELETE VIRTUAL HOST AND HOST NAME =======================================
if ((isset($_GET['to'])) and ($_GET['to'] == "del_virtualhost")) {

	$vhosts_array = read_vhosts('easyphp_vhosts');
	$hostsfile_array = read_hostsfile('file');

	// Delete host in hosts file
	$new_hostfile_content = '';
	// array_unique — Removes duplicate values from an array
	foreach ($hostsfile_array as $line) {
		if((stripos($line,"127.0.0.1") === false) or (stripos($line,$vhosts_array[$_GET['num_virtualhost']][2]) === false)) {
			$new_hostfile_content .= trim($line) . "\n";
		}
	}

	// Backup old hosts file
	copy(get_hostsfile_dir() . "\hosts", get_hostsfile_dir() . "\hosts_" . date("Y-m-d@U"));

	// Save new hosts file
	file_put_contents(get_hostsfile_dir() . '\hosts', trim($new_hostfile_content));


	// 	Delete vhost in inc_virtual_hosts.conf file
	unset($vhosts_array[$_GET['num_virtualhost']]);
	$new_vhosts_content = '';
	foreach ($vhosts_array as $vhost_data) {
		$new_vhosts_content .=  $vhost_data[0] . "\n";
	}

	// Backup old inc_virtual_hosts.conf
	copy('../../binaries/apache/conf/inc_virtual_hosts.conf', '../../binaries/apache/conf/inc_virtual_hosts_' . date("Y-m-d@U") . '.conf');

	// Save new inc_virtual_hosts.conf
	file_put_contents('../../binaries/apache/conf/inc_virtual_hosts.conf', $new_vhosts_content);

	// trigger server restart
	file_put_contents('../../binaries/conf_files/httpd.conf', file_get_contents('../../binaries/conf_files/httpd.conf'));

	$redirect = "http://" . $_SERVER['HTTP_HOST'] . "/home/index.php";
	sleep(2);
	header("Location: " . $redirect);
	exit;
}
//============================================================================


//== ADD VITRTUAL HOST AND HOST NAME =========================================
if ((isset($_POST['to'])) and ($_POST['to'] == "add_vhost_2")) {

	$vhosts_content = read_vhosts('file');

	/*  virtualhost name tests  */
	$name_test = true;
	$lang = $_POST['lang'];

	if ($_POST['vhost_name'] == "") {
		echo "<div class='add_vhost_warning'><a href=\"javascript:history.back()\">&laquo; " . $module_i18n[$lang]['back'] . "</a>" . $module_i18n[$lang]['add_vhost_warning_1'] . "</div>";
		$name_test = false;
		exit;
	}
	elseif ($_POST['vhost_link'] == "") {
		echo "<div class='add_vhost_warning'><a href=\"javascript:history.back()\">&laquo; " . $module_i18n[$lang]['back'] . "</a>" . $module_i18n[$lang]['add_vhost_warning_2'] . "</div>";
		$name_test = false;
		exit;
	}
	elseif (($_POST['vhost_link'] != "") && (!is_dir($_POST['vhost_link']))) {
		echo "<div class='add_vhost_warning'><a href=\"javascript:history.back()\">&laquo; " . $module_i18n[$lang]['back'] . "</a>" . $module_i18n[$lang]['add_vhost_warning_3'] . "</div>";
		$name_test = false;
		exit;
	}
	elseif (!preg_match('/^[-a-zA-Z0-9_.]+$/i', trim($_POST['vhost_name']))) {
		echo "<div class='add_vhost_warning'><a href=\"javascript:history.back()\">&laquo; " . $module_i18n[$lang]['back'] . "</a>" . $module_i18n[$lang]['add_vhost_warning_4'] . "</div>";
		$name_test = false;
		exit;
	}
	elseif (in_array(trim($_POST['vhost_name']), read_vhosts('servernames'))) {
		echo "<div class='add_vhost_warning'><a href=\"javascript:history.back()\">&laquo; " . $module_i18n[$lang]['back'] . "</a>" . $module_i18n[$lang]['add_vhost_warning_5'] . "</div>";
		$name_test = false;
		exit;
	}


	if (($_POST['vhost_name'] != "") && ($_POST['vhost_link'] != "") && (is_dir($_POST['vhost_link'])) && ($name_test == true)) {

		// Cleaning
		$vhost_link = str_replace("\\","/", $_POST['vhost_link']);
		$vhost_link = str_replace("//","/", $vhost_link);

		if (substr($vhost_link, -1) == "/"){$vhost_link = substr($vhost_link,0,strlen($vhost_link)-1);}
		$new_vhost = "<VirtualHost 127.0.0.1>\n";
		$new_vhost .= "\tDocumentRoot \"" . $vhost_link . "\"\n";
		$new_vhost .= "\tServerName " . $_POST['vhost_name'] . "\n";
		$new_vhost .= "\t<Directory \"" . $vhost_link . "\">\n";
		$new_vhost .= "\t\tOptions FollowSymLinks Indexes\r\n";
		$new_vhost .= "\t\tAllowOverride All\r\n";
		$new_vhost .= "\t\tOrder deny,allow\r\n";
		$new_vhost .= "\t\tAllow from 127.0.0.1\r\n";
		$new_vhost .= "\t\tDeny from all\r\n";
		$new_vhost .= "\t\tRequire all granted\r\n";
		$new_vhost .= "\t</Directory>\r\n";
		$new_vhost .= "</VirtualHost>\n";

		$new_vhosts_content = $vhosts_content . $new_vhost;

		// Backup old inc_virtual_hosts.conf
		copy('../../binaries/apache/conf/inc_virtual_hosts.conf', '../../binaries/apache/conf/inc_virtual_hosts_' . date("Y-m-d@U") . '.conf');

		// Save new inc_virtual_hosts.conf
		file_put_contents('../../binaries/apache/conf/inc_virtual_hosts.conf', $new_vhosts_content);

		// Write hosts file
		$hostsfile_array = read_hostsfile('file');
		$new_hostfile_content = '';
		$vhost_exists = false;
		// array_unique — Removes duplicate values from an array
		foreach ($hostsfile_array as $line) {
			if (stristr($line,$_POST['vhost_name']) AND (stristr($line,"127.0.0.1"))) {
				$new_hostfile_content = $new_hostfile_content . "\n" . "127.0.0.1  " . $_POST['vhost_name'];
				$vhost_exists = true;
			} else {
				$new_hostfile_content = $new_hostfile_content . "\n" . $line;
			}
		}
		if (!$vhost_exists) {
			$new_hostfile_content = $new_hostfile_content . "\n" . "127.0.0.1  " . $_POST['vhost_name'];
		}

		// Backup old hosts file
		copy(get_hostsfile_dir() . "\hosts", get_hostsfile_dir() . "\hosts_" . date("Y-m-d@U"));

		// Save new hosts file

		// Save new hosts file
		file_put_contents(get_hostsfile_dir() . '\hosts', trim($new_hostfile_content));


		// trigger server restart
		file_put_contents('../../binaries/conf_files/httpd.conf', file_get_contents('../../binaries/conf_files/httpd.conf'));

		$redirect = 'http://' . $_SERVER['HTTP_HOST'] . '/home/index.php';
		sleep(2);
		header("Location: " . $redirect);
	}
}
//============================================================================


//== RETURN STATUS OF VIRTUAL HOST =======================================
if ((isset($_GET['to'])) and ($_GET['to'] == "status_host")) {

    //INC_VIRTUALHOST.conf
    $vhost_data = '!';
    $vhosts_array = read_vhosts('easyphp_vhosts');
    foreach ($vhosts_array as $key => $vhost) {
        $vhost_name=trim($vhost[2]);
        $pos_hash = stripos($vhost[0], '#');
        $switch_hash = ($pos_hash !== false) ? 'off' : 'on';
        $vhost_data .= $key . $vhost_name . $switch_hash . '!';
    }
    echo $vhost_data;
    exit;
}
//============================================================================



?>
