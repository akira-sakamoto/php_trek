<?php
/* pt_galaxy.php
 */

class T_QUADRANT {
	var $q;		// k/b/s/t
	function __construct()
	{
		$this->Clear();
	}
	function Clear()
	{
		$this->q = "0000";
	}
	function GetKlingon()
	{
		return substr($this->q, 0, 1);
	}
	function GetBase()
	{
		return substr($this->q, 1, 1);
	}
	function GetStar()
	{
		return substr($this->q, 2, 1);
	}
	function GetStatus()
	{
		return substr($this->q, 3, 1);
	}
	function SetKlingon($n)
	{
		$this->q = substr_replace($this->q, $n, 0, 1);
	}
	function SetBase($n)
	{
		$this->q = substr_replace($this->q, $n, 1, 1);
	}
	function SetStar($n)
	{
		$this->q = substr_replace($this->q, $n, 2, 1);
	}
	function SetStatus($n)
	{
		$this->q = substr_replace($this->q, $n, 3, 1);
	}
	function Show($flag)
	{
		if ($this->GetStatus() == '0' && !$flag)
			$s = "***";
		else
			$s = substr($this->q, 0, 3);
		return $s;
	}
}


class T_GALAXY {
	var $map = array(array(), array());
	var $initial_klingons;
	var $total_klingons;
	var $total_bases;
	var $total_stars;
	var $base_destroyed;

	function __construct() {
		for ($y = 0; $y < 8; $y++) {
			for ($x = 0; $x < 8; $x++) {
				$this->map[$x][$y] = new T_QUADRANT();
			}
		}
		$this->total_klingons = $this->total_bases = $this->total_stars = 0;
	}
	function Clear()
	{
		for ($y = 0; $y < 8; $y++) {
			for ($x = 0; $x < 8; $x++) {
				$this->map[$x][$y]->Clear();
			}
		}
		$this->initial_klingons = 0;
		$this->total_klingons = $this->total_bases = $this->total_stars = 0;
		$this->base_destroyed = false;
	}
	function AddKlingon($qx, $qy, $n = 1)
	{
		$q = $this->map[$qx][$qy];
		$q->SetKlingon($n);
		$this->total_klingons += $n;
		$this->initial_klingons = $this->total_klingons;
	}
	function AddBase($qx, $qy, $n = 1)
	{
		$q = $this->map[$qx][$qy];
		$q->SetBase($n);
		$this->total_bases += $n;
	}
	function AddStar($qx, $qy, $n = 1)
	{
		$q = $this->map[$qx][$qy];
		$q->SetStar($n);
		$this->total_stars += $n;
	}
	function DelKlingon($qx, $qy, $n = 1)
	{
		debugecho("DelKlingon($qx, $qy)");
		$q = $this->map[$qx][$qy];
		$k = $q->GetKlingon() - $n;
		$q->SetKlingon($k);
		$this->total_klingons -= $n;
	}
	function DelBase($qx, $qy, $n = 1)
	{
		$q = $this->map[$qx][$qy];
		$k = $q->GetBase() - $n;
		$q->SetBase($k);
		$this->total_bases -= $n;
		$this->base_destroyed = true;
	}
	function DelStar($qx, $qy, $n = 1)
	{
		$q = $this->map[$qx][$qy];
		$k = $q->GetStar() - $n;
		$q->SetStar($k);
		$this->total_stars -= $n;
	}

	function Get($qx, $qy)
	{
		$q = $this->map[$qx][$qy];
		return $q->Show(true);
	}
	function GetKlingon($qx, $qy)
	{
		$q = $this->map[$qx][$qy];
		return $q->GetKlingon();
	}
	function GetBase($qx, $qy)
	{
		$q = $this->map[$qx][$qy];
		return $q->GetBase();
	}
	function GetStar($qx, $qy)
	{
		$q = $this->map[$qx][$qy];
		return $q->GetStar();
	}
	function Watched($qx, $qy)
	{
		$q = $this->map[$qx][$qy];
		$q->SetStatus('1');
	}

	function IsCombatArea($qx, $qy)
	{
		return ($this->GetKlingon($qx, $qy) > 0);
	}

	function ShowMap($flag)
	{
		$s = "    ";
		for ($qx = 0; $qx < 8; $qx++)
			$s .= "-$qx- ";
		println($s);
		for ($qy = 0; $qy < 8; $qy++) {
			$s = " $qy: ";
			for ($qx = 0; $qx < 8; $qx++) {
				$s .= $this->map[$qx][$qy]->Show($flag) . " ";
			}
			println($s);
		}
	}

	function MakeGalaxy()
	{
		do {
			$this->Clear();
			for ($qy = 0; $qy < 8; $qy++) {
				for ($qx = 0; $qx < 8; $qx++) {
					// Generate Klingon
					$r = mt_rand(0, 99);
					if ($r > 98)
						$k = 3;
					elseif ($r > 95)
						$k = 2;
					elseif ($r > 80)
						$k = 1;
					else
						$k = 0;
					$this->AddKlingon($qx, $qy, $k);

					// Generate Base
					$b = ((mt_rand(0, 99) > 96) ? 1 : 0);
					$this->AddBase($qx, $qy, $b);

					// Generate Star
					$s = mt_rand(1, 8);
					$this->AddStar($qx, $qy, $s);
				}
			}
		} while ($this->total_klingons == 0 || $this->total_bases == 0);
	}
}


/* Small Space in Quadrant
 */
class T_SPACE {
	var $map = array(array(), array());
	var $icon = array(
			'enterprise' => "<E>",
			'klingon'    => ">K<",
			'base'       => "+B+",
			'star'       => " * ",
			'space'      => "   ");

	function __construct()
	{
		debugecho("T_SPACE()");
		$this->Clear();
	}
	function Clear()
	{
		for ($y = 0; $y < 8; $y++) {
			for ($x = 0; $x < 8; $x++) {
				$this->SetSpace($x, $y);
			}
		}
	}

	function Set($x, $y, $obj)
	{
		$this->map[$x][$y] = $obj;
	}
	function SetSpace($x, $y)
	{
		$this->Set($x, $y, $this->icon['space']);
	}
	function SetEnterprise($x, $y)
	{
		$this->Set($x, $y, $this->icon['enterprise']);
	}
	function SetKlingon($x, $y)
	{
		$this->Set($x, $y, $this->icon['klingon']);
	}
	function SetBase($x, $y)
	{
		$this->Set($x, $y, $this->icon['base']);
	}
	function SetStar($x, $y)
	{
		$this->Set($x, $y, $this->icon['star']);
	}
	function Get($x, $y)
	{
		return $this->map[$x][$y];
	}
	function IsSpace($x, $y)
	{
		return $this->IsObj($x, $y, 'space');
	}
	function IsObj($x, $y, $obj)
	{
		return ($this->map[$x][$y] == $this->icon[$obj]);
	}
	function FindEmptySlot(&$x, &$y)
	{
		do {
			$x = mt_rand(0, 7);
			$y = mt_rand(0, 7);
		} while ($this->Get($x, $y) != $this->icon['space']);
		debugecho("FindEmptySlot($x,$y)");
	}

	function Create($sx, $sy, $k, $b, $s)
	{
		debugecho("T_SPACE::Create($sx,$sy,$k,$b,$s)");
		$this->Clear();

		// Set Enterprise at sx, sy
		if ($sx < 0 && $sy < 0)
			$this->FindEmptySlot($sx, $sy);
		$this->SetEnterprise($sx, $sy);

		// Set Klingons
		InitKlingons();
		for ($i = 0; $i < $k; $i++) {
			$this->FindEmptySlot($x, $y);
			$this->SetKlingon($x, $y);
			MakeKlingon($i, $x, $y);
		}
		for ($i = 0; $i < $k; $i++) {
		}

		// Set Base
		if ($b != 0) {
			$this->FindEmptySlot($x, $y);
			$this->SetBase($x, $y);
		}

		// Set Stars
		for ($i = 0; $i < $s; $i++) {
			$this->FindEmptySlot($x, $y);
			$this->SetStar($x, $y);
		}
	}

	function SearchNaighbor($x, $y, $obj)
	{
		for ($v = $y - 1; $v <= $y + 1; $v++) {
			if ($v >= 0 && $v < 8) {
				for ($h = $x - 1; $h <= $x + 1; $h++) {
					if ($h >= 0 && $h < 8) {
						if ($this->IsObj($h, $v, $obj))
							return true;	// match
					}
				}
			}
		}
		return false;
	}
	function Show()
	{
		println("--- --- --- --- --- --- --- ---");
		for ($y = 0; $y < 8; $y++) {
			for ($x = 0; $x < 8; $x++) {
				$obj = $this->Get($x, $y);
				if ($obj == $this->icon['klingon']) {
					if (!IsKlingonAliveByXY($x, $y))
						$obj = $this->icon['space']	;
				}
				echo $obj . ' ';
			}
			echo PHP_EOL;
		}
		println("--- --- --- --- --- --- --- ---");
	}
}

/**
$p = new T_SPACE();
$p->Set(5, 3, "<E>");
$p->Set(1, 1, " * ");
$p->Set(6, 2, " * ");
$p->Set(2, 5, ">K<");
$p->Set(3, 4, "+B+");
$p->Show();
echo var_dump($p->SearchNaighbor(5, 3, "+B+"));
echo var_dump($p->SearchNaighbor(2, 5, "+B+"));
echo var_dump($p->SearchNaighbor(7, 6, "+B+"));

$g = new T_GALAXY();
$g->AddKlingon(3,4,2);
$g->ShowMap(true);
echo $g->total_klingons . PHP_EOL;
$g->DelKlingon(3,4,1);
$g->ShowMap(true);
echo $g->total_klingons . PHP_EOL;
$g->Clear();
$g->ShowMap(true);
echo $g->total_klingons . PHP_EOL;
**/
?>
