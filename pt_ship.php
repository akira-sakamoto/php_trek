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
	var $sx;
	var $sy;

	function __construct($icon, $def_engy)
	{
		$this->icon = $icon;
		$this->default_energy = $this->energy = $def_engy;
		$this->sx = -1;
		$this->sy = -1;
	}

	function Create($sx, $sy)
	{
		$this->SetPosition($sx, $sy);
		$this->ReCharge();
	}

	function ReCharge()
	{
		$this->energy = $this->default_energy;
	}

	function SetPosition($sx, $sy)
	{
		$this->sx = $sx;
		$this->sy = $sy;
	}

	function Destroy()
	{
		$this->energy = -1;
	}

	function Hit($e) {
		$this->energy -= $e;
	}

	function IsAlive()
	{
		return ($this->energy > 0);
	}

	function DebugShow()
	{
		println("icon            $this->icon");
		println("energy          $this->energy");
		println("default_energy  $this->default_energy");
		println("sx,sy           $this->sx, $this->sy");
		println("IsAlive         " . ($this->IsAlive() ? "TRUE":"FALSE"));
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
	var $qx;
	var $qy;
	var $torpedoes;
	var $spend;

	function __construct($galaxy, $space)
	{
		parent::__construct("<E>", 4000);
		$this->galaxy = $galaxy;
		$this->space  = $space;
		$this->shield = $this->default_shield = 0;
		$this->qx = $this->qy = 0;
		$this->spend = 0;
	}

	function Create()
	{
		parent::Create(mt_rand(0, 7), mt_rand(0, 7));
		$this->qx = mt_rand(0, 7);
		$this->qy = mt_rand(0, 7);
		$this->ReCharge();
	}

	function ReCharge()
	{
		parent::ReCharge();
		$this->shield = 0;
		$this->torpedoes = 10;	// default
		RepairAll();
	}

	function SetQuadrant($qx, $qy)
	{
		$this->qx = $qx;
		$this->qy = $qy;
		$this->galaxy->Watched($qx, $qy);
	}
	
	function EnterNewQuadrant()
	{
		debugecho("EnterNewQuadrant($this->qx,$this->qy)");
		$k = $this->galaxy->GetKlingon($this->qx, $this->qy);
		$b = $this->galaxy->GetBase($this->qx, $this->qy);
		$s = $this->galaxy->GetStar($this->qx, $this->qy);
		$this->space->Create($this->sx, $this->sy, $k, $b, $s);
		$this->galaxy->Watched($this->qx, $this->qy);

		if ($k > 0) {
			if ($this->shield < 200) {
				println("COMBAT AREA      CONDITION RED");
				println("   SHIELDS DANGEROUSLY LOW");
			}
		}
	}

	function WarpIn()
	{
		debugecho("WarpIn($this->sx, $this->sy)");
		$this->space->SetSpace($this->sx, $this->sy);
	}
	function WarpOut()
	{
		debugecho("WarpOut($this->sx, $this->sy)");
		$this->space->SetEnterprise($this->sx, $this->sy);
	}
	function CheckCondition()
	{
		if (SearchNaighbor($this->sx, $this->sy))
			return "DOCKED";
		if ($this->galaxy->IsCombatArea($this->qx, $this->qy))
			return "RED";
		if ($this->energy < $this->default_energy * 0.1)
			return "YELLOW";
		return "GREEN";
	}

	function DebugShow()
	{
		println(sprintf("STARDATE            %5d", GetTime()));
		println(sprintf("CONDITION          %6s", $this->CheckCondition()));
		println(sprintf("QUADRANT          %3d,%3d", $this->qx,$this->qy));
		println(sprintf("SECTOR            %3d,%3d", $this->sx,$this->sy));
		println(sprintf("ENERGY             %6s", $this->energy));
		println(sprintf("PHOTON TORPEDOES      %3d", $this->torpedoes));
		println(sprintf("SHIELD             %6d", $this->shield));
	}

	function ResetTime()
	{
		$this->spend = 0;
	}

	function SpendTime($t)
	{
		$this->spend += $t;
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
	
	function Create($sx, $sy)
	{
		debugecho("T_KLINGON::Create($sx, $sy)");
		parent::Create($sx, $sy);
	}

	function Destroy($disp = false)
	{
		parent::Destroy();
		if ($disp && $this->sx >= 0 && $this->sy >= 0)
			println(sprintf("KLINGON AT SECTOR %d,%d DESTROYED ****", $this->sx, $this->sy));
	}

	function action()
	{
		$sx = $this->sx;
		$sy = $this->sy;
		$e  = $this->energy;
		println("Klingon $sx,$sy, $e");
	}
}
?>
