<?php
/* pt_ship.php
 */

include_once("pt_lib.php");

/**
* T_SHIP
*/
class T_SHIP
{
	var $icon;
	var $energy;
	var $default_energy;
	var $sx, $sy;

	function __construct($icon, $def_engy)
	{
		$this->icon = $icon;
		$this->default_energy = $this->energy = $def_engy;
		$this->sx = 0;
		$this->sy = 0;
	}
}


/**
* T_ENTERPRISE
*/
class T_ENTERPRISE extends T_SHIP
{
	var $galaxy;
	var $space;
	var $shield;
	var $default_shield;
	var $time;
	var $time_left;
	var $qx, $qy;

	function __construct($galaxy, $space)
	{
		parent::__construct("<E>", 4000);
		$this->galaxy = $galaxy;
		$this->space  = $space;
		$this->shield = 0;
		$this->default_shield = 0;
		$this->time = 0;
		$this->time_left = 0;
		$this->qx = 0;
		$this->qy = 0;
	}

	function Create()
	{
		$this->time = (mt_rand(0, 19) + 20) * 100;
		$this->time_left = 30;
		$this->qx = mt_rand(0, 7);
		$this->qy = mt_rand(0, 7);
		$this->sx = mt_rand(0, 7);
		$this->sy = mt_rand(0, 7);
	}

	function EnterNewQuadrant()
	{
		debugecho("EnterNewQuadrant()");
		$k = $this->galaxy->GetKlingon($this->qx, $this->qy);
		$b = $this->galaxy->GetBase($this->qx, $this->qy);
		$s = $this->galaxy->GetStar($this->qx, $this->qy);
		$this->space->Create($this->sx, $this->sy, $k, $b, $s);
	}

	function WarpIn()
	{
		$this->space->SetSpace($this->sx, $this->sy);
	}
	function WarpOut()
	{
		$this->space->SetEnterprise($this->sx, $this->sy);
	}
	function DebugShow()
	{
		println("QX, QY          $this->qx, $this->qy");
		println("SX, SY          $this->sx, $this->sx");
		println("SHIELD          $this->shield");
		println("DEFAULT SHILED  $this->default_shield");
		println("ENERGY          $this->energy");
		println("DEFAULT ENERGY  $this->default_energy");
		println("TIME            $this->time");
		println("TIME LEFT       $this->time_left");
	}
}



/**
* T_BASE
*/
class T_BASE extends T_SHIP
{
	function __construct()
	{
		parent::__construct("+B+", 0);
	}
}


/**
* T_KLINGON
*/
class T_KLINGON extends T_SHIP
{
	function __construct()
	{
		parent::__construct(">K<", 200);
	}
}
?>
