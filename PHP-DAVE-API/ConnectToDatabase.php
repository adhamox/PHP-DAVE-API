<?php

/***********************************************
DAVE PHP API
https://github.com/evantahler/PHP-DAVE-API
Evan Tahler | 2011

I am the mySQL connection class

Here's an example:

	$DBObj = new DBConnection();
	$Status = $DBObj->GetStatus();
	if ($Status === true)
	{
		$DBObj->Query($SQL);
		$Status = $DBObj->GetStatus();
		if ($Status === true){ $Results = $DBObj->GetResults();}
		// Do stuff with the $Results array
		else{ $ERROR = $Status; }
	}
	else { $ERROR = $Status; } 
	$DBObj->close();

use the GetLastInsert() function to get the deatils of an entry you just added.

***********************************************/

class DBConnection
{
	protected $Connection, $Status, $OUT, $DataBase;
	
	public function __construct($OtherDB = "")
	{
		global $dbhost, $dbuser, $dbpass, $DB, $MySQLLogFile;
		$this->Status = true;
		
		if ($OtherDB != "") { $this->DataBase = $OtherDB ; } 
		else { $this->DataBase = $DB; }
				
		$this->Connection = @mysql_connect($dbhost, $dbuser, $dbpass);
		if(!empty($this->Connection))
		{
			$DatabaseSelected=mysql_select_db($this->DataBase);
			if (!empty($DatabaseSelected))
			{
				return true;
			}
			else
			{
				$this->Status = "Database Selection Error (mySQL) | ".mysql_errno($this->Connection) . ": " . mysql_error($this->Connection);
				return false;
			}
		}
		else
		{
			$this->Status = "Connection Error (mySQL) | Connection or Access permission error";
			return false;
		}		
	}
	
	private function mysql_log($line)
	{
		global $IP, $MySQLLogFile;
		
		$host = $IP;
		if ($host == ""){$host = "local_system";}
		
		$line = date("Y-m-d H:i:s")." | ".$host." | ".$line;
		if (strlen($MySQLLogFile) > 0)
		{
			$LogFileHandle = fopen($MySQLLogFile, 'a');
			if($LogFileHandle)
			{
				fwrite($LogFileHandle, ($line."\r\n"));
			}
			fclose($LogFileHandle);
		}
	}
	
	private function CheckForSpecialStrings($string)
	{	
		global $SpecialStrings;
		foreach ($SpecialStrings as $term)
		{
			$string = str_replace($term[0],$term[1],$string);
		}
		$string = str_replace("  "," ",$string);
		return $string;
	}
	
	public function Query($SQL)
	{
		if($this->Status != true)
		{
			return false;
		}
		elseif(strlen($SQL) < 1)
		{
			return false;
		}
		else
		{
			$SQL = $this->CheckForSpecialStrings($SQL);
			$LogLine = $SQL;
			$Query=mysql_query($SQL);
			if (empty($Query))
			{
				$this->Status = "MYSQL Query Error: ".mysql_errno($this->Connection) . ": " . mysql_error($this->Connection);
				$LogLine .= " | Error->".$this->Status;
				$this->mysql_log($LogLine);
				return false;
			}
			else
			{
				$this->OUT = array();
				$tmp = array();
				$NumRows=@mysql_num_rows($Query);
				
				if ($NumRows > 0){ $LogLine .= " | RowsFond -> ".$NumRows; }
				elseif($this->NumRowsEffected > 0){ $LogLine .= " | RowsEffected -> ".$this->NumRowsEffected; } 
				if ($this->GetLastInsert > 0){ $LogLine .= " | InsertID -> ".$this->GetLastInsert; }
				
				$this->mysql_log($LogLine);
				if ($NumRows > 0)
				{
					while($row = mysql_fetch_assoc($Query))
					{
						$tmp[] = $row;
					}
					$this->OUT = $tmp;
					unset($tmp);
					return true;
				}
				else
				{
					return true; // it worked, but there is no data retruned.  Perhaps this wasn't a SELECT
				}
			}
		}
	}
	
	public function GetLastInsert()
	{
		return mysql_insert_id($this->Connection);
	}
	
	public function NumRowsEffected()
	{
		return mysql_affected_rows($this->Connection);
	}
	
	public function GetConnection()
	{
		return $this->Connection;
	}
	
	public function GetStatus()
	{
		return $this->Status;
	}
	
	public function GetResults()
	{
		return $this->OUT;
	}
	
	public function close()
	{
		@mysql_close($this->Connection);
		$this->Status = "Disconnected. (mySQL)";
	}
}

?>