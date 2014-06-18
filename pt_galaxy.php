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
	var $total_klingons;
	var $total_bases;
	var $total_stars;

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
		$this->total_klingons = $this->total_bases = $this->total_stars = 0;
	}
	function AddKlingon($x, $y, $n)
	{
		$q = $this->map[$x][$y];
		$q->SetKlingon($n);
		$this->total_klingons += $n;
	}
	function AddBase($x, $y, $n)
	{
		$q = $this->map[$x][$y];
		$q->SetBase($n);
		$this->total_bases += $n;
	}
	function AddStar($x, $y, $n)
	{
		$q = $this->map[$x][$y];
		$q->SetStar($n);
		$this->total_stars += $n;
	}
	function DelKlingon($x, $y, $n)
	{
		$q = $this->map[$x][$y];
		$k = $q->GetKlingon() - $n;
		$q->SetKlingon($k);
		$this->total_klingons -= $n;
	}
	function DelBase($x, $y, $n)
	{
		$q = $this->map[$x][$y];
		$k = $q->GetBase() - $n;
		$q->SetBase($k);
		$this->total_bases -= $n;
	}
	function DelStar($x, $y, $n)
	{
		$q = $this->map[$x][$y];
		$k = $q->GetStar() - $n;
		$q->SetStar($k);
		$this->total_stars -= $n;
	}

	function Get($x, $y)
	{
		$q = $this->map[$x][$y];
		return $q->Show(true);
	}
	function GetKlingon($x, $y)
	{
		$q = $this->map[$x][$y];
		return $q->GetKlingon();
	}
	function GetBase($x, $y)
	{
		$q = $this->map[$x][$y];
		return $q->GetBase();
	}
	function GetStar($x, $y)
	{
		$q = $this->map[$x][$y];
		return $q->GetStar();
	}
	function Watched($x, $y)
	{
		$q = $this->map[$x][$y];
		$q->SetStatus('1');
	}

	function ShowMap($flag)
	{
		$s = "    ";
		for ($x = 0; $x < 8; $x++)
			$s .= "-$x- ";
		echo $s . PHP_EOL;
		for ($y = 0; $y < 8; $y++) {
			echo " $y: ";
			for ($x = 0; $x < 8; $x++) {
				echo $this->map[$x][$y]->Show($flag) . " ";
			}
			echo PHP_EOL;
		}
	}

	function MakeGalaxy()
	{
		do {
			$this->Clear();
			for ($y = 0; $y < 8; $y++) {
				for ($x = 0; $x < 8; $x++) {
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
					$this->AddKlingon($x, $y, $k);

					// Generate Base
					$b = ((mt_rand(0, 99) > 96) ? 1 : 0);
					$this->AddBase($x, $y, $b);

					// Generate Star
					$s = mt_rand(1, 8);
					$this->AddStar($x, $y, $s);
				}
			}
		} while ($this->total_klingons == 0 || $this->total_bases == 0);
	}
}


/* Small Space in Quadrant
 */
class T_SPACE {
	var $map = array(array(), array());
	var $ent = "<E>";
	var $kgn = ">K<";
	var $bas = "+B+";
	var $str = " * ";
	var $spc = "   ";

	function __construct()
	{
		debugecho("T_SPACE()");
		$this->Clear();
	}
	function Clear()
	{
		for ($y = 0; $y < 8; $y++) {
			for ($x = 0; $x < 8; $x++) {
				$this->map[$x][$y] = $this->spc;
			}
		}
	}

	function Set($x, $y, $obj)
	{
		$this->map[$x][$y] = $obj;
	}
	function SetSpace($x, $y)
	{
		$this->Set($x, $y, $this->spc);
	}
	function SetEnterprise($x, $y)
	{
		$this->Set($x, $y, $this->ent);
	}
	function SetKlingon($x, $y)
	{
		$this->Set($x, $y, $this->kgn);
	}
	function SetBase($x, $y)
	{
		$this->Set($x, $y, $this->bas);
	}
	function SetStar($x, $y)
	{
		$this->Set($x, $y, $this->str);
	}
	function Get($x, $y)
	{
		return $this->map[$x][$y];
	}
	function IsSpace($x, $y)
	{
		return ($this->map[$x][$y] == $this->spc);
	}
	function FindEmptySlot(&$x, &$y)
	{
		debugecho("FindEmptySlot($x,$y)");
		do {
			$x = mt_rand(0, 7);
			$y = mt_rand(0, 7);
		} while ($this->Get($x, $y) != $this->spc);
	}

	function Create($sx, $sy, $k, $b, $s)
	{
		debugecho("T_SPACE::Create()");
		$this->Clear();

		// Set Enterprise at sx, sy
		$this->map[$sx][$sy] = $this->ent;

		// Set Klingons
		for ($i = 0; $i < $k; $i++) {
			$this->FindEmptySlot($x, $y);
			$this->Set($x, $y, $this->kgn);
		}

		// Set Base
		if ($b != 0) {
			$this->FindEmptySlot($x, $y);
			$this->Set($x, $y, $this->bas);
		}

		// Set Stars
		for ($i = 0; $i < $s; $i++) {
			$this->FindEmptySlot($x, $y);
			$this->Set($x, $y, $this->str);
		}
	}

	function SearchNaighbor($x, $y, $obj)
	{
		for ($v = $y - 1; $v <= $y + 1; $v++) {
			if ($v >= 0 && $v < 8) {
				for ($h = $x - 1; $h <= $x + 1; $h++) {
					if ($h >= 0 && $h < 8) {
						echo "$h,$v ";
						if ($this->map[$h][$v] == $obj)
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
				echo $this->map[$x][$y] . " ";
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
