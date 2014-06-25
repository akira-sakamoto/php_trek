<?php
/* pt_time.php
 * time related functions
 */

include_once("pt_lib.php");

/**
* T_TIME
*/
class T_TIME
{
	var $start_time;
	var $current_time;
	var $limit_time;
	var $rt_start;

	function __construct()
	{
		$this->start_time = $this->current_time = $this->limit_time = 0;
		$this->rt_start = 0;
	}

	function Init()
	{
		$this->start_time = $this->current_time = (mt_rand(0, 19) + 20) * 100;
		$this->limit_time = $this->start_time + 30;
		$this->rt_start = time();
	}

	function Elasped($t)
	{
		$this->current_time += $t;
		return $this->IsTimeOver();
	}

	function TimeLeft()
	{
		return $this->limit_time - $this->current_time;
	}

	function IsTimeOver()
	{
		return ($this->limit_time < $this->current_time);
	}

}


?>

