<?php
/* ptreklib.php
 *   PHP-TREK Library
 */


// if no input then return null
function input($msg = '')
{
  if ($msg != '')
    print $msg . "? ";
  return trim(fgets(STDIN, 64));
}

function inputs($msg = '')
{
  if (($ret = input($msg)) != null) {
    $ret = explode(",", $ret);
    foreach ($ret as &$r) {
      $r = trim($r);
    }
    unset($r);
  }
  return $ret;
}


/* PHP does not have int() */
function int($n)
{
  return floor($n);
}


/* BASIC互換の乱数
 * 負: 乱数系列の初期化
 *  0: 前回と同じ乱数値
 * 正: 新しい乱数値
 */
function rnd($n)
{
  static $r = -1;

  if ($r < 0)
    srand(1);

  if ($n < 0)
    srand();
  elseif ($n == 0) {
    if ($r >= 0)
      return $r;
  }
  return ($r = mt_rand(0, 65535) / 65536);
}


/* ESC [ 0 m  元に戻す
 * ESC [ 1 m  強調
 * ESC [ 4 m  下線
 * ESC [ 7 m  反転
 * ESC [ 30 m 前景色 (黒 / 赤 / 緑 / 黃 / 青 / マジェンタ / シアン / 白)
 * ESC [ 39 m 標準色に戻す
 * ESC [ 40 m 背景色
 * ESC [ 49 m 標準色に戻す
 */ 
function debugecho($str = "")
{
  global $DEBUGECHO;
  if ($DEBUGECHO)
    println("\x1B[34mDEBUG: $str\x1B[39m");
}


function println($s = "")
{
  print $s . PHP_EOL;
}


/* in / out: range
 * in: modify - if range over then modify the value
 * ret: true = ok, false = modified
 */
function RangeCheck(&$n, $modify = false)
{
  debugecho("RangeCheck $n");
  $ret = true;
  if ($n < 0) {
    if ($modify)
      $n = 0;
    $ret = false;
  }
  if ($n > 7) {
    if ($modify)
      $n = 7;
    $ret = false;
  }
  return $ret;
}

?>