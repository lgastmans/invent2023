<?php
/**
 * FTP Class
 *
 * @package		FTPClass
 * @author		Luk Gastmans
 * @copyright	Copyright (c) 2006, Cynergy software
 * @link		http://www.cynergy-software.com
 * @since		Version 1.0
 */
 
// ------------------------------------------------------------------------

class FTPClass {
	var $destination_folder;
	var $client_folder;
	var $host;
	var $username;
	var $password;
	var $ftpstream;
	var $debug;
	
	/**
	 * Constructor
	 *
	 * The constructor opens a connection to the FTP server
	 * and logs in to the server using the specified details.
	 * @param string $destination_folder The document root of the web server
	 * @param string $client_folder The client folder
	 * @param string $host The domain name of the FTP server
	 * @param string $username FTP server login ID
	 * @param string $password FTP server password
	 */
	 
	function FTPClass($destination_folder, $client_folder, $host, $username, $password) {
		$this->destination_folder = $destination_folder;
		$this->client_folder = $client_folder;
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->debug = false;
		
		$ftpstream = @ftp_connect($host);
		
		if($ftpstream) {
			$login = @ftp_login($ftpstream, $username, $password);
			if($login) {
				$this->ftpstream = $ftpstream;
				return true;
			}
			else {
				if ($this->debug)
					echo "Failed to login to FTP server<br />";
				@ftp_close($ftpstream);
				return false;
			}
		}
		else {
			if($this->debug)
				echo "Failed to connect to FTP server<br />";
			return false;
		}
	}
	
	/**
	 * Send a file
	 *
	 * @access public
	 * @param string $filename Name of the file
	 * @return bool
	 */
	function FTP_send($filename, $mode=FTP_ASCII) {
		if($this->ftpstream) {
			if($filename != '') {
				// turn passive mode on
				@ftp_pasv($this->ftpstream, true);
				
				// open some file for reading
				$fp = @fopen($this->client_folder.$filename, 'r');
				
				if (!$fp)
					if ($this->debug) {
						echo "Failed to open ".$this->client_folder.$filename."<br>";
					}
				
				// upload $file
				if (@ftp_fput($this->ftpstream, $this->destination_folder.$filename, $fp, $mode)) {
					// close the file handler
					fclose($fp);
					
					if (ftp_chmod($this->ftpstream, 0777, $this->destination_folder.$filename) === false) {
						if ($this->debug)
							echo "could not chmod $filename\n";
					}
					return true;
				} else {
					if ($this->debug)
						echo "Failed to send file ".$this->destination_folder.$filename."<br>";
					
					// close the file handler
					@fclose($fp);
					return false;
				}
			}
			else {
				return false;
			}
		}
		else {
			if($this->debug)
				echo "Not connected to the FTP server<br />";
			return false;
		}
	}
	
	/**
	 * Send a file
	 *
	 * @access public
	 * @param string $filename Name of the file
	 * @return bool
	 */
	function FTP_receive() {
		if($this->ftpstream) {
			// turn passive mode on
			@ftp_pasv($this->ftpstream, true);
			
			$arr_files = ftp_nlist($this->ftpstream, $this->destination_folder."*.xml");

			foreach($arr_files as $value) {
				if(ftp_get($this->ftpstream, $this->client_folder.basename($value), $value, FTP_ASCII)) {
					echo "Received file ".basename($value)."<br>";
					/*
						rename / remove the file from the server
					*/
					$arr_filename = explode(".", basename($value));
					$str_source = basename($value);
					$str_destination = $arr_filename[0].".bak";
					
					/*
					if (@ftp_rename($this->ftpstream, $str_source, $str_destination))
						echo "renamed successfully<br>";
					else
						echo "could not rename ".$str_source."<br>";
					*/
					echo "going to delete ".$this->destination_folder.basename($value)."<br>";

					if(ftp_delete($this->ftpstream, $this->destination_folder.basename($value)))
						echo "deleted successfully<br>";
					else
						echo "could not delete<br>";
				}
				else {
					echo "Error receiving file ".$value;
				}
			}
		}
		else {
			if($this->debug)
				echo "Not connected to the FTP server<br />";
			return false;
		}
	}
	
	/**
	 * Close the connection
	 *
	 * @access public
	 * @return bool
	 */
	function FTP_close() {
		if($this->ftpstream) {
			ftp_close($this->ftpstream);
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Set debug flag
	 *
	 * @access public
	 * @param bool $debug
	 * @return void
	 */
	function setDebug($debug) {
		$this->debug = $debug;
	}
}
?>
