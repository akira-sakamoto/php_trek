<?php
/* pt_device.php
 */

include_once("pt_lib.php");

class T_DEVICE {
	var $name;
	var $damage;
	var $func;

	function __construct($name, $func = null)
	{
		$this->name = $name;
		$this->damage = 0;
		$this->func = $func;
		debugecho("__construct($name, $func)");
	}

	function action()
	{
		debugecho($this->name . "()");
		if ($this->damage > 0) {
			echo "$this->name DAMAGED" . PHP_EOL;
			return false;
		}
		if ($this->func != null)
			return call_user_func($this->func);
		return true;
	}

	function report()
	{
		echo "$this->name $this->damage" . PHP_EOL;
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
		$c2 = int($c1);
		$vx = $this->VectorX[$c2] + ($this->VectorX[$c2 + 1] - $this->VectorX[$c2]) * ($c1 - $c2);
		$vy = $this->VectorY[$c2] + ($this->VectorY[$c2 + 1] - $this->VectorY[$c2]) * ($c1 - $c2);
		debugecho("SetVector: $vx, $vy, $c1, $c2");
	}

	function GetFactor(&$c1, &$w1) 
	{
		$c1 = $w1 = 0;
		return true;
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

	function action()
	{
		debugecho("T_PHYSICAL::action()");
		// get course and warp factor
		$c1 = $w1 = -1;
		if (!$this->GetFactor($c1, $w1))
			return null;	// array(-1, -1);

		$enterprise = $this->Cosmos['enterprise'];
		$space      = $this->Cosmos['space'];

		$w = $w1;
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

			$this->ShowMessage(2, $sx, $sy);

			$retX = RangeCheck($sx);
			$retY = RangeCheck($sy);
			if (!$retX || !$retY) {
				// warp or stop
				return $this->OutSpace($vx, $vy, $w1);
			}
			else {
				if (!$space->IsSpace($sx, $sy)) {
					// blocked
					$this->BlockOut($sx, $sy);
					$sx = $x0;
					$sy = $y0;
					break;
				}
			}
		} while ($w1 > 0);

		// return sx, sy
		debugecho("action($sx,$sy)");
		return array($sx, $sy);
	}
}



/* T_NAV Navigation Device and Control
 */
class T_NAV extends T_PHYSICAL {
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
			if ($this->damage == 0 || $w1 <= 0.2)
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
		parent::ShowMessage($n, $x, $y);
	}

	// NAV command main
	function action()
	{
		// erace enterprise temporary
		$enterprise = $this->Cosmos['enterprise'];
		$enterprise->WarpIn();

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
		if (parent::action()) {
			$space = $this->Cosmos['space'];
			$space->Show();
			$enterprise = $this->Cosmos['enterprise'];
			debugecho("sx,sy = $enterprise->sx,$enterprise->sy");
			$enterprise->DebugShow();
		}
		else {
			println("SRS DAMAGED");
		}
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
		$xy = parent::action();
		$x = $xy[0];
		$y = $xy[1];
		echo "------ ----- ------" . PHP_EOL;
		for ($v = $y - 1; $v <= $y + 1; $v++) {
			for ($h = $x - 1; $h <= $x + 1; $h++) {
				if ($v < 0 || $v >= 8 || $h < 0 || $h >= 8)
					echo ": --- ";
				else {
					$this->galaxy->Watched($h, $v);
					echo ": " . $this->galaxy->Get($h, $v) . " ";
				}
			}
			echo ":" . PHP_EOL;
		}
		echo "------ ----- ------" . PHP_EOL;
	}

}

class T_TOR extends T_PHYSICAL {

	function GetFactor(&$c1, &$w1)
	{
		if (($c1 = input("TORPEDOE COURSE (1-9): ") * 1.0) == 0)
			return false;
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
			println("BASE DESTROYED");
			DestroyBase($sx, $sy);
		}
		elseif ($space->IsObj($sx, $sy, 'star')) {
			println("STAR DESTROYED");
			DestroyStar($sx, $sy);
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
		if ($enterprise->torpedoes-- <= 0) {
			println("ALL PHOTON TORPEDOES EXPENDED");
			$enterprise->torpedoes = 0;
			return;
		}

		$xy = parent::action();
		if ($xy != null) {
			debugecho("Hit at $xy[0],$xy[1]");
		}
		else {
			debugecho("Cansel input or torpedoe missed");
		}
	}
}

class T_PHA extends T_DEVICE {

}

class T_SHI extends T_DEVICE {

}

class T_DAM extends T_DEVICE {

}

class T_COM extends T_DEVICE {
	var $galaxy;
	var $flag;
	function __construct($name, $func, $galaxy)
	{
		parent::__construct($name, $func);
		$this->galaxy = $galaxy;
		$this->flag   = false;	// Galaxy Map display flag
	}

	function action()
	{
		parent::action();
		$this->galaxy->ShowMap($this->flag);
	}
}


?>

