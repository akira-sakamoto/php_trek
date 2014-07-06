<?php
/* pt_device.php
 */

include_once("pt_lib.php");

class T_DEVICE {
	var $name;
	var $damage;
	var $func;
	var $ActionTime;

	function __construct($name, $func = null)
	{
		$this->name = $name;
		$this->damage = 0;
		$this->func = $func;
		$this->ActionTime = 0;	// default no time is needed
		debugecho("__construct($name, $func)");
	}

	function GetFactor(&$c1, &$w1) 
	{
		$c1 = $w1 = 0;
		return true;
	}

	function action($msg = null)
	{
		debugecho($this->name . "()");
		if ($msg != null) {
			if ($this->GetDamage() > 0) {
				debugecho("$this->name DAMAGED");
				println($msg);
				return false;
			}
			if ($this->func != null)
				return call_user_func($this->func);
		}
		return true;
	}

	function report()
	{
		echo "$this->name $this->damage" . PHP_EOL;
	}

	function GetDamage()
	{
		return $this->damage;
	}

	function RepairDamage($d = 1)
	{
		if ($d < 0) {
			$this->damage = 0;
		}
		else {
			$this->damage -= $d;
			if ($this->damage < 0)
				$this->damage = 0;
		}
	}

	function BeDamaged($d)
	{
		$this->damage += $d;
	}

	function Spend()
	{
		return $this->ActionTime;
	}
}


/**
* T_PHYSICAL
* Callback['GetFactor', 'OutSpace', 'BlockOut']
*/
class T_PHYSICAL extends T_DEVICE
{
	var $VectorX = array('1' => 1, 1, 0, -1, -1, -1, 0, 1, 1);
	var $VectorY = array('1' => 0, -1, -1, -1, 0, 1, 1, 1, 0);
	var $Cosmos;

	function __construct($name, $cosmos)
	{
		parent::__construct($name);
		$this->Cosmos = $cosmos;
	}

	function SetVector(&$vx, &$vy, $c1)
	{
		$c2 = int($c1) % count($this->VectorX);
		$vx = $this->VectorX[$c2] + ($this->VectorX[$c2 + 1] - $this->VectorX[$c2]) * ($c1 - $c2);
		$vy = $this->VectorY[$c2] + ($this->VectorY[$c2 + 1] - $this->VectorY[$c2]) * ($c1 - $c2);
		debugecho("SetVector: $vx, $vy, $c1, $c2");
	}

	function OutSpace($vx, $vy, $w)
	{
		// do nothing
		return null;	// array(-2, -2);
	}

	function BlockOut($sx, $sy) 
	{
		$space = $this->Cosmos['space'];
		$obj = $space->Get($sx, $sy);
		echo "BlockOut at $sx, $sy by $obj";
		return 0;
	}

	function ShowMessage($n, $sx = 0, $sy = 0)
	{
		switch ($n) {
			case 1:
				print("TORPEDO TRACK: ");
				break;
			case 2:
				print("$sx,$sy ");
				break;
		}
	}

	function action($msg = null)
	{
		debugecho("T_PHYSICAL::action()");

		if (!parent::action($msg)) {
			debugecho("canceled");
			return;
		}

		// get course and warp factor
		$c1 = $w1 = -1;
		if (!$this->GetFactor($c1, $w1))
			return null;	// array(-1, -1);

		$enterprise = $this->Cosmos['enterprise'];
		$space      = $this->Cosmos['space'];

		// Spend Time
		// $this->ActionTime = (($w = $w1) > 1) ? 1 : 0;

		$sx = $enterprise->sx;
		$sy = $enterprise->sy;
		$this->SetVector($vx, $vy, $c1);
		$this->ShowMessage(1);

		do {
			$w1 -= 0.125;
			$x0 = $sx;
			$y0 = $sy;
			$sx += $vx;
			$sy += $vy;

			$sx1 = int($sx);
			$sy1 = int($sy);
			$this->ShowMessage(2, $sx1, $sy1);

			$retX = RangeCheck($sx1);
			$retY = RangeCheck($sy1);
			if (!$retX || !$retY) {
				// warp or stop
				return $this->OutSpace($vx, $vy, $w1);
			}
			else {
				if (!$space->IsSpace($sx1, $sy1)) {
					// blocked
					$this->BlockOut($sx1, $sy1);
					$sx = $x0;
					$sy = $y0;
					break;
				}
			}
		} while ($w1 > 0);


		// return sx, sy
		debugecho("action($sx1,$sy1)");
		return array($sx1, $sy1);	// give last position in sector
	}
}



/* T_NAV Navigation Device and Control
 */
class T_NAV extends T_PHYSICAL {
	function __construct($name, $cosmos)
	{
		parent::__construct($name, $cosmos);
		$this->ActionTime = 1;
	}
	function GetFactor(&$c1, &$w1)
	{
		// input course and warp factor
		while (1) {
			do {
				do {
					if (($c1 = input("COURSE (1-9): ") * 1.0) == 0)
						return false;
				} while ($c1 < 1 || $c1 > 9);

				if (($w1 = input("WARP FACTOR (0-8): ") * 1.0) == 0)
					return false;
			} while ($w1 < 0 || $w1 > 8);
			if ($this->GetDamage() <= 0 || $w1 <= 0.2)
				break;

			println("WARP ENGINES ARE DAMAGED, MAXIMUM SPEED = WARP 0.2");
		}
		if ($w1 < 0.2)
			$w1 = 0.125;

		return true;
	}

	function OutSpace($vx, $vy, $w)
	{
		$enterprise = $this->Cosmos['enterprise'];
		debugecho("Out from sector $enterprise->sx,$enterprise->sy, $w");
		$n = int($w + 0.5);	// quadrant power
		$qx = $enterprise->qx + ($vx * $n);
		$qy = $enterprise->qy + ($vy * $n);
		$retX = RangeCheck($qx, true);
		$retY = RangeCheck($qy, true);
		if (!$retX || !$retY) {
			debugecho("Out of galaxy");
		}

		// Make New Quadrant
		$enterprise->qx = $qx;
		$enterprise->qy = $qy;
		$enterprise->sx = $sx = mt_rand(0, 7);
		$enterprise->sy = $sy = mt_rand(0, 7);
		$enterprise->EnterNewQuadrant();
		debugecho("after EnterNewQuadrant: $enterprise->sx,$enterprise->sy");

		$this->ActionTime = 1;

		return array($sx, $sy);
	}

	function BlockOut($sx, $sy)
	{
		parent::BlockOut($sx, $sy);
		println("WARP ENGINES SHUTDOWN AT SECTOR $sx, $sy DUE TO BAD NAVIGATION");
	}

	function ShowMessage($n, $x = 0, $y = 0)
	{
		// do nothing
		parent::ShowMessage(0, $x, $y);
	}

	// NAV command main
	function action()
	{
		// erace enterprise temporary
		$enterprise = $this->Cosmos['enterprise'];
		$enterprise->WarpIn();

		$this->ActionTime = 0;	// inter quadrant move does not spend time
		$xy = parent::action();

		// show enterprise at new or previous position
		if ($xy != null) {
			debugecho("xy != null");
			$enterprise->sx = $xy[0];
			$enterprise->sy = $xy[1];
		}
		else
			debugecho("xy == null");

		$enterprise->WarpOut();

		// generate random damage
		DamageAndRepair();

		// docking
		if ($enterprise->CheckCondition() == 'DOCKED') {
			$enterprise->ReCharge();
			debugecho("DOCKED");
		}
	}

}


/* Short Range Sensor
 */
class T_SRS extends T_DEVICE {
	var $Cosmos;
	function __construct($name, $cosmos)
	{
		parent::__construct($name);
		$this->Cosmos = $cosmos;
	}

	function action()
	{
		if (parent::action(PHP_EOL . "*** SHORT RANGE SENSORS ARE OUT ***" . PHP_EOL)) {
			$space = $this->Cosmos['space'];
			$space->Show();
		}
		$enterprise = $this->Cosmos['enterprise'];
		$enterprise->DebugShow();
	}

}


/* Long Range Sensor
 */
class T_LRS extends T_DEVICE {
	var $galaxy;
	function __construct($name, $func, $galaxy)
	{
		parent::__construct($name, $func);
		$this->galaxy = $galaxy;
	}

	function action()
	{
		global $com;

		if (!($xy = parent::action(PHP_EOL . "LONG RANGE SENSORS ARE INOPERABLE" . PHP_EOL))) {
			return;
		}

		$qx = int($xy[0]);
		$qy = int($xy[1]);
		println("LONG RANGE SENSOR SCAN FOR QUADRANT $qx,$qy");
		echo "------ ----- ------" . PHP_EOL;
		for ($v = $qy - 1; $v <= $qy + 1; $v++) {
			for ($h = $qx - 1; $h <= $qx + 1; $h++) {
				if ($v < 0 || $v >= 8 || $h < 0 || $h >= 8)
					echo ": --- ";
				else {
					// computer damaged
					if ($com->GetDamage() <= 0)
						$this->galaxy->Watched($h, $v);
					echo ": " . $this->galaxy->Get($h, $v) . " ";
				}
			}
			echo ":" . PHP_EOL;
		}
		echo "------ ----- ------" . PHP_EOL;
	}

}


/* Torpedoes
 */
class T_TOR extends T_PHYSICAL
{
	function __construct($name, $cosmos)
	{
		parent::__construct($name, $cosmos);
		$this->ActionTime = 1;
	}

	function GetFactor(&$c1, &$w1)
	{
		if (($c1 = input("TORPEDOE COURSE (1-9): ")) == 0)
			return false;
		if ($c1 > 9)
			$c1 %= 9;
		$c1 *= 1.0;
		$w1 = 1.0;		// torpedoe's power
		return true;
	}

	function OutSpace()
	{
		println("TORPEDO MISSED");
		return null;
	}

	function BlockOut($sx, $sy)
	{
		parent::BlockOut($sx, $sy);

		debugecho("T_TOR::BlockOut($sx,$sy)");
		$space = $this->Cosmos['space'];
		if ($space->IsObj($sx, $sy, 'klingon')) {
			println("KLINGON DESTROYED");
			DestroyKlingon($sx, $sy);
		}
		elseif ($space->IsObj($sx, $sy, 'base')) {
			println("*** STAR BASE DESTROYED ***  .......CONGRATULATIONS");
			DestroyBase($sx, $sy);
		}
		elseif ($space->IsObj($sx, $sy, 'star')) {
			println("YOU CAN'T DESTROY STARS SILLY");
			// DestroyStar($sx, $sy);
			$this->OutSpace();		// torpedoe missed
		}
		else {
			$obj = $space->Get($sx, $sy);
			var_dump($obj);
			die('Unknown destroyed');
		}
	}

	function ShowMessage($n, $sx = -1, $sy = -1)
	{
		parent::ShowMessage($n, $sx, $sy);
	}

	function action()
	{
		$enterprise = $this->Cosmos['enterprise'];
		if ($enterprise->torpedoes <= 0) {
			println("ALL PHOTON TORPEDOES EXPENDED");
			$enterprise->torpedoes = 0;
			return;
		}

		if (($xy = parent::action("PHOTON TUBES ARE NOT OPERATIONAL")) == null) {
			debugecho("Cansel input or torpedoe missed");
			return;
		}
		debugecho("Hit at $xy[0],$xy[1]");
		$enterprise->torpedoes--;
	}
}


/* T_PHA - PHASER CONTROL
 */
class T_PHA extends T_DEVICE {
	var $cosmos;

	function __construct($name, $cosmos)
	{
		parent::__construct($name);
		$this->cosmos = $cosmos;
		$this->ActionTime = 1;
	}

	function action()
	{
		global $com;
	
		if (!parent::action("PHASER CONTROL IS DISABLED"))
			return;

		$enterprise = $this->cosmos['enterprise'];
		$galaxy = $this->cosmos['galaxy'];
		$klingons = $this->cosmos['klingon'];

		do {
			if ($com->GetDamage() > 0)
				println("COMPUTER FAILURE HAMPERS ACCURACY");

			println("PHASERS LOCKED ON TARGET.  ENERGY AVAILABLE = $enterprise->energy");
			if (($egy = input("NUMBER OF UNITS TO FIRE: ")) <= 0)
				return;
		} while ($enterprise->energy < $egy);

		$enterprise->energy -= $egy;

		// klingon's action

		if ($com->GetDamage() > 0)
			$egy *= rnd(1);		// give random power loss
		
		$numklingon = $galaxy->GetKlingon($enterprise->qx, $enterprise->qy);
		for ($i = 0; $i < 3; $i++) {
			if (($xy = GetKlingonPos($i)) != null) {
				$kx = $xy[0];
				$ky = $xy[1];
				$fn = sqrt(($kx - $enterprise->sx) * ($kx - $enterprise->sx) + ($ky - $enterprise->sy) * ($ky - $enterprise->sy));
				$h = 1;
				$h = ($egy / $numklingon / $fn * (2 * $h));
				HitKlingon($i, $h);

				debugecho("kx,ky: $kx,$ky / ex,ey: $enterprise->sx,$enterprise->sy / fn: $fn");
				debugecho("numklingon: $numklingon / egy: $egy / h: $h");

				println(sprintf("%4d UNIT HIT ON KLINGON AT SECTOR %d,%d (%3d LEFT)", $h, $kx, $ky, GetKlingonPower($i)));

				if (!IsKlingonAlive($i)) {
					DestroyKlingon(-1, $i);		// destroy klingon by number
				}
			}
		}
	}
}


class T_SHI extends T_DEVICE {
	var $enterprise;

	function __construct($name, $enterprise)
	{
		parent::__Construct($name);
		$this->enterprise = $enterprise;
	}

	function GetFactor(&$c)
	{
		$p = $this->enterprise->energy + $this->enterprise->shield;
		if (($c = input("ENERGY AVAILABLE = $p   NUMBER OF UNITS TO SHIELD: ")) <= 0)
			return; false;
		return true;
	}

	function action()
	{
		if (!parent::action("SHIELD CONTROL IS NON-OPERATIONAL"))
			return;

		do {
			if (!$this->GetFactor($shi))
				return;
		} while ($this->enterprise->energy + $this->enterprise->shield < $shi);

		$this->enterprise->energy += ($this->enterprise->shield - $shi);
		$this->enterprise->shield = $shi;
	}
}


/* T_DAM: Damage Control
 * this logic is negative to the original
 */
class T_DAM extends T_DEVICE {
	var $device;

	function Init($device)
	{
		$this->device = $device;
	}

	function Repair()
	{
		foreach ($this->device as $d) {
			if ($d->damage > 0)
				$d->damege--;
		}
		unset($d);
	}

	function action()
	{
		parent::action("DAMAGE CONTROL REPORT IS NOT AVAILABLE");
	}
}


/* T_COM: Library Computer
 */
class T_COM extends T_DEVICE {
	var $cosmos;
	var $flag;
	function __construct($name, $func, $cosmos)
	{
		parent::__construct($name, $func);
		$this->cosmos = $cosmos;
		$this->flag   = false;	// Galaxy Map display flag
	}

	function GetFactor(&$c)
	{
		if (($c = input("COMPUTER ACTIVE AND AWAITING COMMAND ")) == "")
			return false;
		return true;
	}

	function action()
	{
		if (!parent::action("COMPUTER DISABLED"))
			return;

		if (!$this->GetFactor($c))
			return;

		switch ($c) {
			case '0':
				$galaxy = $this->cosmos['galaxy'];
				$galaxy->ShowMap($this->flag);
				break;

			case '1':
				$this->StatusReport();
				break;

			case '2':
				$this->PhotonTorpedoData();
				break;

			default:
echo <<< CommandHelp
FUNCTIONS AVAILABLE FROM COMPUTER
   0 = COMULATIVE GALACTIC RECORD
   1 = STATUS REPORT
   2 = PHOTON TORPEDO DATA			

CommandHelp;
		}
	}

	function StatusReport()
	{
		$galaxy = $this->cosmos['galaxy'];
		$k = $galaxy->total_klingons;
		$b = $galaxy->total_bases;

		println();
		println("   STATUS REPORT");
		println("NUMBER OF KLINGONS LEFT  = $k");
		println("NUMBER OF STARDATES LEFT = " . GetTime());
		println("NUMBER OF STARBASES LEFT = $b");
	}

	function PhotonTorpedoData()
	{
		debugecho("PhotonTorpedoData");
		$galaxy = $this->cosmos['galaxy'];
		$e = $this->cosmos['enterprise'];
		$k = $galaxy->GetKlingon($e->qx, $e->qy);
		for ($i = 0; $i < $k; $i++) {
			if (($xy = GetKlingonPos($i)) != null) {
				$dirdest = $this->CalculateCourse($e->sx, $e->sy, $xy[0], $xy[1]);
				println("DIRECTION = $dirdest[0]");
				println("DISTANCE  = $dirdest[1]");
			}
		}

		if (strtoupper(input("DO YOU WANT TO USE THE CALCULATOR ")) == 'YES') {
			do {
				println("YOU ARE AT QUADRANT ($e->qx, $e->qy)  SECTOR ($e->sx, $e->sy)");
				println("SHIP 'SHIELD & TARGET' CORDINATES ARE");
				$arg = explode(",", input());
			} while (count($arg) != 4);
			$dirdest = $this->CalculateCourse($arg[0], $arg[1], $arg[2], $arg[3]);
			println("DIRECTION = $dirdest[0]");
			println("DISTANCE  = $dirdest[1]");
		}
	}

	function CalculateCourse($ex, $ey, $kx, $ky)
	{
		$dx = $kx - $ex;
		$dy = $ey - $ky;

		if ($dx < 0) {
			if ($dy > 0)
				$c1 = 3;
			else
				$c1 = 5;
		}
		elseif ($dy < 0)
			$c1 = 7;
		elseif ($dx > 0)
			$c1 = 1;
		elseif ($dy > 0)
			$c1 = 3;
		else {
			if ($dx == 0)
				return array(0, 0);
			else
				$c1 = 5;
		}

		if ($c1 == 1 || $c1 == 5) {
			if (abs($dy) <= abs($dx)) {
				$direction = $c1 + (abs($dy) / abs($dx));
			}
			else {
				$direction = $c1 + ((abs($dy) - abs($dx) + abs($dy)) / abs($dy));
			}
		}
		else {
			if (abs($dy) >= abs($dx)) {
				$direction = $c1 + (abs($dx) / abs($dy));
			}
			else {
				$direction = $c1 + (((abs($dx) - abs($dy)) + abs($dx)) / abs($dx));
			}
		}

		$distance = sqrt($dx * $dx + $dy * $dy);
		return array($direction, $distance);
	}


}


?>

