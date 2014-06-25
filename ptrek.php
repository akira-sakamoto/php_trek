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
$pha	= new T_PHA("PHASER CNTRL");
$tor	= new T_TOR("PHOTON TUBES", $Cosmos);
$dam    = new T_DAM("DAMAGE CNTRL", "DispDamage");
$shi	= new T_SHI("SHIELD CNTRL");
$com	= new T_COM("COMPUTER    ", null, $Galaxy);
$device = array('0'=>$nav, $srs, $lrs, $pha, $tor, $dam, $shi, $com);

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

		case '10':	// direct quadrant move
			DebugDirectQuadrantMove();
			break;

		case '11':	// direct sector move
			DebugDirectSectorMove();
			break;

		case '12':	// debug echo on/off
			DebugEchoSwitch();
			break;

		case '13':	// debug
			DebugDump();
			break;

		case '14':
			DebugObject();
			break;

		case '15':
			$Galaxy->total_klingons = 0;
			break;

		case '99':
			die('exit');

		default:
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

function DispDamage()
{
	global $device;

	println();
	println("DAMAGE CONTROL REPORT:");
	foreach ($device as $d) {
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
function MakeKlingon($i, $x, $y)
{
	global $Klingons;
	$Klingons[$i]->Create($x, $y);
}

function FindKlingonByXY($sx, $sy)
{
	global $Klingons;
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

// called from T_TOR::BlockOut()
function DestroyKlingon($sx, $sy)
{
	global $Enterprise, $Klingons, $Galaxy, $Space;
	if (($i = FindKlingonByXY($sx, $sy)) >= 0) {
		println("*** KLINGON DESTROYED ***");
		$Klingons[$i]->Destroy();
		$Space->SetSpace($sx, $sy);
		$Galaxy->DelKlingon($Enterprise->qx, $Enterprise->qy);
	}
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

function GetTime()
{
	global $Time;
	return $Time->TimeLeft();	
}

// Debug
function DebugDirectQuadrantMove()
{
	global $Enterprise;
	$Enterprise->qx = input("QX = ");
	$Enterprise->qy = input("QY = ");
}
function DebugDirectSectorMove()
{
	global $Enterprise;
	$Enterprise->sx = input("SX = ");
	$Enterprise->sy = input("SY = ");
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
?>
