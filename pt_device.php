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


/* T_NAV Navigation Device and Control
 */
class T_NAV extends T_DEVICE {
	var $VectorX = array('1' => 1, 1, 0, -1, -1, -1, 0, 1, 1);
	var $VectorY = array('1' => 0, -1, -1, -1, 0, 1, 1, 1, 0);
	var $Cosmos;

	function __construct($name, $cosmos)
	{
		parent::__construct($name);
		$this->Cosmos = $cosmos;
	}

	// NAV command main
	function action()
	{
		// parent::action();
		
		// input course and warp factor
		while (1) {
			do {
				do {
					if (($c1 = input("COURSE (1-9): ") * 1.0) == 0)
						return;
				} while ($c1 < 1 || $c1 > 9);

				if (($w1 = input("WARP FACTOR (0-8): ") * 1.0) == 0)
					return;
			} while ($w1 < 0 || $w1 > 8);

			if ($this->damage == 0 || $w1 <= 0.2)
				break;

			println("WARP ENGINES ARE DAMAGED, MAXIMUM SPEED = WARP 0.2");
		}

		if ($w1 < 0.2)
			$w1 = 0.125;

		// erace enterprise temporary
		$enterprise = $this->Cosmos['enterprise'];
		$enterprise->WarpIn();

		$space = $this->Cosmos['space'];

		// calcurate vector
		$w = $w1;
		$x = $enterprise->sx;
		$y = $enterprise->sy;
		$this->SetVector($vx, $vy, $c1);

		do {
			$w -= 0.125;		// sector move power (1/8)
			$x0 = $x; $y0 = $y;
			$x += $vx; $y += $vy;
			debugecho("SectorMove: $x, $y, $w");
			$retX = RangeCheck($x);
			$retY = RangeCheck($y);
			if (!$retX || !$retY) {
				debugecho("Out from sector");
				$n = int($w + 1);	// quadrant power
				$qx = $enterprise->qx + ($vx * $n);
				$qy = $enterprise->qy + ($vy * $n);
				$retX = RangeCheck($qx, true);
				$retY = RangeCheck($qy, true);
				if (!$retX || !$retY) {
					debugecho("Out of Space");
				}

				// Make New Quadrant
				$enterprise->qx = $qx;
				$enterprise->qy = $qy;
				$enterprise->sx = $x = mt_rand(0, 7);
				$enterprise->sy = $y = mt_rand(0, 7);
				$enterprise->EnterNewQuadrant();
				return;
			}
			else {
				if (!$space->IsSpace($x, $y)) {
					println("WARP ENGINES SHUTDOWN AT SECTOR $x, $y DUE TO BAD NAVIGATION");
					$x = $x0;
					$y = $y0;
					break;
				}
			}
		} while ($w > 0);

		$enterprise->sx = $x;
		$enterprise->sy = $y;
		$enterprise->WarpOut();
	}

	function SetVector(&$vx, &$vy, $c1)
	{
		$c2 = int($c1);
		$vx = $this->VectorX[$c2] + ($this->VectorX[$c2 + 1] - $this->VectorX[$c2]) * ($c1 - $c2);
		$vy = $this->VectorY[$c2] + ($this->VectorY[$c2 + 1] - $this->VectorY[$c2]) * ($c1 - $c2);
		debugecho("SetVector: $vx, $vy, $c1, $c2");
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
		parent::action();
		$space = $this->Cosmos['space'];
		$space->Show();
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

class T_TOR extends T_DEVICE {

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

