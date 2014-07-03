<?php
/* ptrek.php
 */

$DEBUGECHO = 1;
include_once("pt_device.php");
include_once("pt_galaxy.php");
include_once("pt_ship.php");
include_once("pt_time.php");

// start here
println("            STAR TREK");
if (strtoupper(input("DO YOU WANT INSTRUCTIONS (THEY'RE LONG!) ")) == 'YES')
	ShowInstructions();

// initialize
$Galaxy = new T_GALAXY();
$Space  = new T_SPACE("MakeKlingon");

$Enterprise = new T_ENTERPRISE($Galaxy, $Space);
$Enterprise->Create();

$Klingons = array(3);
for ($i = 0; $i < 3; $i++) {
	$Klingons[$i] = new T_KLINGON($Galaxy, $Space);
}

$Base = new T_BASE($Galaxy, $Space);

$Cosmos = array('galaxy'     => $Galaxy,
				'space'      => $Space,
				'enterprise' => $Enterprise,
				'klingon'    => $Klingons,
				'base'       => $Base);

$nav	= new T_NAV("WARP ENGINES", $Cosmos);
$srs    = new T_SRS("S.R. SENSORS", $Cosmos);
$lrs    = new T_LRS("L.R. SENSORS", "GetQuadrant", $Galaxy);
$pha	= new T_PHA("PHASER CNTRL", $Cosmos);
$tor	= new T_TOR("PHOTON TUBES", $Cosmos);
$dam    = new T_DAM("DAMAGE CNTRL", "DispDamage");
$shi	= new T_SHI("SHIELD CNTRL", $Enterprise);
$com	= new T_COM("COMPUTER    ", null, $Cosmos);
$device = array('0'=>$nav, $srs, $lrs, $pha, $tor, $shi, $dam, $com);
$dam->Init($device);		// cannot set un-initialized variable by constructor

$Time = new T_TIME();


CreateGalaxy();
$Enterprise->EnterNewQuadrant();
$Time->Init();

// Show Mission
$t = $Time->TimeLeft();
println("YOU MUST DESTROY $Galaxy->total_klingons KLINGONS" .
        " IN $t STARDATES" .
        " WITH $Galaxy->total_bases STARBASES");


// debug
// $Enterprise->DebugShow();

// Main Loop
while (1) {
	// Enterprise Status
	if ($Enterprise->CheckCondition() == 'DOCKED')
		$Enterprise->ReCharge();

	// docking
	// repair
	// reset timer
	$Enterprise->ResetTime();


	$cmd = input(PHP_EOL . "COMMAND: ");

	switch ($cmd) {
		case '0':
		case '1':
		case '2':
		case '3':
		case '4':
		case '5':
		case '6':
		case '7':
			$device[$cmd]->action();
			break;

		case '10':	// change important parameters
			DebugParameters();
			break;

		case '11':	// debug echo on/off
			DebugEchoSwitch();
			break;

		case '12':	// debug
			DebugDump();
			break;

		case '13':
			DebugObject();
			break;

		case '98':
			ShowCommandList(true);
			break;

		case '99':
			die('exit');

		default:
			ShowCommandList(false);
	}

	// Timer
	if ($Enterprise->spend > 0) {
		if ($Time->Elasped($Enterprise->spend)) {
			die('Time Over');
		}

	}

	// all klingons are destroyed?
	if ($Galaxy->total_klingons <= 0) {
		println();
		println("THE LAST KLINGON BATTLE CRUISER IN THE GALAXY HAS BEEN DESTROYED");
		println("THE FEDERATION HAS BEEN SAVED !!!");
		println();
		println("YOUR EFFICIENCY RATIONG = " .
			(($Galaxy->initial_klingons / $Time->TimeLeft()) * 1000));
		$t1 = time();
		println("YOUR ACTUAL TIME OF MISSION = ".
			int(($t1 - $Time->rt_start) / 60) . " MINUTES");
	}
		

}


// Show Instructions
function ShowInstructions()
{
	$fp = file('StarTrekHelp.txt');
	foreach ($fp as $str)
		print $str;
}

// Show Command List
// flag: true = show with debug command
function ShowCommandList($flag)
{
	echo <<< CommandHelp
 0 = SET COURSE
 1 = SHORT RANGE SENSOR SCAN
 2 = LONG RANGE SENSOR SCAN
 3 = FIRE PHASERS
 4 = FIRE PHOTON TORPEDOES
 5 = SHIELD CONTROL
 6 = DAMAGE CONTROL REPORT
 7 = CALL ON LIBRARY COMPUTER

CommandHelp;

	if (!$flag)
		return;

	echo <<< DebugCommand
10 = Debug Parameters
11 = Debug Echo ON / OFF
12 = Debug Dump
13 = Debug Object
98 = Show Debug Command List (this)
99 = exit

DebugCommand;
}

function DispDamage($n = 0)
{
	global $device;

	println();
	println("DAMAGE CONTROL REPORT:");
	$i = 0;
	foreach ($device as $d) {
		if ($n > 0)
			print($i++ . " : ");
		println($d->name . "  " . $d->damage . "  " . (($d->damage > 0)?" DAMAGED":""));
	}
	unset($d);
}

function CreateGalaxy()
{
	global $Galaxy;
	$Galaxy->MakeGalaxy();
}


/* callback function */
function GetQuadrant()
{
	global $Enterprise;
	return array($Enterprise->qx, $Enterprise->qy);
}

function InitKlingons()
{
	global $Klingons;
	for ($i = 0; $i < 3; $i++) {
		$Klingons[$i]->Destroy();
	}
}
function MakeKlingon($n, $x, $y)
{
	global $Klingons;
	$Klingons[$n]->Create($x, $y);
}
function GetKlingonPos($n)
{
	global $Klingons;
	$sx = $sy = -1;
	if (IsKlingonAlive($n)) {
		$sx = $Klingons[$n]->sx;
		$sy = $Klingons[$n]->sy;
	}
	return array($sx, $sy);
}

function FindKlingonByXY($sx, $sy)
{
	global $Klingons;
	$sx = int($sx);
	$sy = int($sy);
	for ($i = 0; $i < 3; $i++) {
		if ($Klingons[$i]->sx == $sx && $Klingons[$i]->sy == $sy)
			return $i;
	}
	return -1;
}

function IsKlingonAliveByXY($sx, $sy)
{
	global $Klingons;
	if (($n = FindKlingonByXY($sx, $sy)) >= 0) {
		return IsKlingonAlive($n);	// true: alive
	}
	return false;		// caution: the same result as dead klingon
}
function IsKlingonAlive($n)
{
	global $Klingons;
	return $Klingons[$n]->IsAlive();	// true: alive / false: dead
}

function HitKlingon($n, $p)
{
	global $Klingons;
	$Klingons[$n]->energy -= $p;
}

function GetKlingonPower($n)
{
	global $Klingons;
	return $Klingons[$n]->energy;
}

// called from T_TOR::BlockOut()
/* sx, sy : sector positon
 * if (sx < 0) then sy = klingon number
 */
function DestroyKlingon($sx, $sy)
{
	global $Enterprise, $Klingons, $Galaxy, $Space;
	if ($sx < 0) {
		$i = $sy;
		$sx = $Klingons[$i]->sx;
		$sy = $Klingons[$i]->sy;
	}
	else {
		if (($i = FindKlingonByXY($sx, $sy)) < 0)
		return;
	}

	println("*** KLINGON DESTROYED ***");
	$Klingons[$i]->Destroy();
	$Space->SetSpace($sx, $sy);
	$Galaxy->DelKlingon($Enterprise->qx, $Enterprise->qy);

	debugecho("FIndKlingonByXY($sx,$sy) --> $i");
}

function DestroyBase($sx, $sy)
{
	global $Enterprise, $Galaxy, $Space;
	$Space->SetSpace($sx, $sy);
	$Galaxy->DelBase($Enterprise->qx, $Enterprise->qy);
}

function DestroyStar($sx, $sy)
{
	global $Enterprise, $Galaxy, $Space;
	$Space->SetSpace($sx, $sy);
	$Galaxy->DelStar($Enterprise->qx, $Enterprise->qy);
}

function SearchNaighbor($sx, $sy, $obj = 'base')
{
	global $Space;
	return $Space->SearchNaighbor($sx, $sy, $obj);
}


// Timer Related
function GetTime()
{
	global $Time;
	return $Time->TimeLeft();	
}

function SpendTime($t)
{
	global $Enterprise;
	debugecho("SpendTime()");
	$Enterprise->SpendTime($t);
}


// Debug
function DebugDirectQuadrantMove()
{
	global $Enterprise;
	$xy = inputs("Qx,Qy = ");
	$Enterprise->SetQuadrant($xy[0], $xy[1]);
}
function DebugDirectSectorMove()
{
	global $Enterprise;
	$xy = inputs("Sx,Sy = ");
	$Enterprise->SetPosition($xy[0], $xy[1]);
}

function DebugEchoSwitch()
{
	global $DEBUGECHO;
	$next = !$DEBUGECHO;
	$DEBUGECHO = true;
	debugecho("DEBUGECHO = " . ($next?"ON":"OFF"));
	$DEBUGECHO = $next;

	global $Space;
	var_dump($Space);
}

function DebugObject()
{
	global $Enterprise, $Klingons, $Base, $Galaxy;
	
	$Enterprise->DebugShow();
	$qx = $Enterprise->qx;
	$qy = $Enterprise->qy;

	if ($Galaxy->IsCombatArea($qx, $qy)) {
		$k = $Galaxy->GetKlingon($qx, $qy);
		for ($i = 0; $i < $k; $i++) {
			println();
			println("Klingon[$i]:");
			$Klingons[$i]->DebugShow();
		}
	}
}

function DebugDump()
{
	global $Cosmos;
	var_dump($Cosmos);
}

function DebugParameters()
{
	global $Enterprise, $Galaxy;
	global $device;
	global $Time;

	switch (input("Target: 1=Enterprise / 2=Klingon / 3=Timer / 0=exit ")) {
		case '1':	// enterprise
			switch (input("Object: 1=Quadrant / 2=Sector / 3=Energy / 4=Torpedoes / 5=Damage ")) {
				case '1':
					DebugDirectQuadrantMove();
					break;
				case '2':
					DebugDirectSectorMove();
					break;
				case '3':
					$Enterprise->energy = input("Energy = ");
					break;
				case '4':
					$Enterprise->torpedoes = input("Torpedoes = ");
					break;
				case '5':
					DispDamage(1);	// current status
					while (($dd = inputs("Device, Damage = ")) != null) {
						$device[$dd[0]]->damage = $dd[1];
					}
					DispDamage(1);	// new status
					break;
			}
			break;

		case '2':	// klingon
			if (strtoupper(input("Do you set klingon counter to zero (Y/N) ")) == 'Y')
				$Galaxy->total_klingons = 0;
			break;

		case '3':	// timer
			$t = time();
			$t1 = $t - $Time->rt_start;
			println("0: Start Time        $Time->start_time");
			println("1: Current Time      $Time->current_time");
			println("2: Limit Time        $Time->limit_time");
			println("   Real Start Time   $Time->rt_start");
			println("   Real Current Time $t ($t1 Elasped)");

			if (($dd = inputs("Option, Value = ")) != null) {
				switch ($dd[0]) {
					case '0':
						$Time->start_time = $dd[1];
						break;
					case '1':
						$Time->current_time = $dd[1];
						break;
					case '2':
						$Time->limit_time = $dd[1];
						break;
				}
			}
			break;

		default:
			return;
	}



}
?>
