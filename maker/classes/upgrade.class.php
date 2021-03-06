<?php
class upgrade {

	private function versioncompare($dbversion, $fileversion) {
		// Is the current version older then the newly installed version?
		$dbversion = preg_split("[\\.]", $dbversion);
		$fileversion = preg_split("[\\.]", $fileversion);

		if ($dbversion [0] < $fileversion [0] || ($dbversion [1] < $fileversion [1] || ($dbversion [2] < $fileversion [2]))) return true;
		else return false;
	}

	public function database($dbversion, $fileversion) {
        require "../includes/config.php";
		// If this is not a new version. Do nothing.
		if (! $this->versioncompare($dbversion, $fileversion)) {}

		/* Are we upgrading to 0.1.7?
		This version completely changed how permissions is stored in the database*/
		else if ($this->versioncompare($dbversion, "0.1.7")) {
			// Create the new table
			$sql = "CREATE TABLE `".$mysql_database."`.`permissions` (
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`uid` INT UNSIGNED NOT NULL ,
				`sid` INT UNSIGNED NOT NULL ,
				`permissions` INT UNSIGNED NOT NULL
				) ENGINE = MYISAM ;";
			@mysql_query($sql, $conn) or die("<b>A fatal MySQL error occurred</b>.\n<br />\nError: (" . mysql_errno() . ") " . mysql_error());

			// Convert the old table to the new table.
			$sql = "SELECT * FROM admin_rights RIGHT JOIN maker ON maker.id = admin_rights.userid";
			$query = mysql_query($sql);
			$num_sections = (mysql_num_fields($query) - 7);
			$headadmin = (mysql_num_fields($query) - 3);
			while ($fetch = mysql_fetch_array($query, MYSQL_NUM)) {
				for ($i = 0; $i < $num_sections; $i ++) {
					$sid = $fetch [$i + 1];
					if ($sid) {
						$sql = "INSERT INTO `".$mysql_database."`.`permissions` (`id`, `uid`, `sid`, `permissions`)
								VALUES (NULL , '" . $fetch [0] . "', '" . ($i + 1) . "', '1');";
						//echo $sql."\n";
						mysql_query($sql, $conn) or die("<b>A fatal MySQL error occurred</b>.\n<br />\nError: (" . mysql_errno() . ") " . mysql_error());
					}
				}
				if ($fetch [$headadmin]) {
					$sql = "INSERT INTO `".$mysql_database."`.`permissions` (`id`, `uid`, `sid`, `permissions`)
							VALUES (NULL , '" . $fetch [0] . "', '0', '1');";
					//echo $sql."\n";
					mysql_query($sql, $conn) or die("<b>A fatal MySQL error occurred</b>.\n<br />\nError: (" . mysql_errno() . ") " . mysql_error());
				}
			}
			// Create extra permissions, Headadmin is the first "extra" permission.
			$sql="CREATE TABLE `".$mysql_database."`.`permissions_extra` (
				`id` INT UNSIGNED NOT NULL ,
				`name` TEXT NOT NULL ,
				PRIMARY KEY (  `id` )
				) ENGINE = MYISAM ;";
			//echo $sql . "<br />";
			mysql_query($sql, $conn) or die("<b>A fatal MySQL error occurred</b>.\n<br />\nError: (" . mysql_errno() . ") " . mysql_error());

			$sql="INSERT INTO `".$mysql_database."`.`permissions_extra` (`id`, `name`) VALUES ('1', 'Headadmin');";
			//echo $sql . "<br />";
			mysql_query($sql, $conn) or die("<b>A fatal MySQL error occurred</b>.\n<br />\nError: (" . mysql_errno() . ") " . mysql_error());

			// Remove the old table
			$sql = "DROP TABLE `".$mysql_database."`.`admin_rights`";
			mysql_query($sql, $conn) or die("<b>A fatal MySQL error occurred</b>.\n<br />\nError: (" . mysql_errno() . ") " . mysql_error());

			// Remove unused field
			$sql = "ALTER TABLE `".$mysql_database."`.`maker` DROP `headadmin`";
			mysql_query($sql, $conn) or die("<b>A fatal MySQL error occurred</b>.\n<br />\nError: (" . mysql_errno() . ") " . mysql_error());

			// This is important! Never forget to also change the version number :)
			$sql = "UPDATE `".$mysql_database."`.`global` SET `version` = '0.1.7' WHERE `id` = 1";
			mysql_query($sql, $conn) or die("<b>A fatal MySQL error occurred</b>.\n<br />\nError: (" . mysql_errno() . ") " . mysql_error());
		}
	}
}