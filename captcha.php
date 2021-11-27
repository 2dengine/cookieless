<?php
// Adapted from: www.the-art-of-web.com

// number of characters
define('SOLUTION', 5);
// cool-down period between attempts
define('COOLDOWN', 3);
// code expiration in seconds
define('EXPIRATION', 60*10);

class Captcha {
  protected $code;
  protected $data;
  protected $path;
  
  function __construct($ip = null) {
    if (!$ip)
      $ip = $_SERVER['REMOTE_ADDR'];
    $this->code = null;
    $this->data = [];
    $this->ip = $ip;
    // hash IP addresses
    $hash = hash('sha256', $this->ip, false);
    $this->path = dirname(__FILE__).'/cache/'.$hash;
    // check answer
    $this->load();
  }
  
  function lock($code) {
    if (strlen($code) > 255)
      $code = substr($code, 0, 255);
    $this->code = $code;
    // flood prevention
    $mod = @filemtime($this->path);
    while ($mod and time() - $mod < COOLDOWN)
      sleep(COOLDOWN);
    @touch($this->path);
  }
  
  function cleanup() {
    // scan cache directory
    $scan = scandir(dirname(__FILE__).'/cache/');
    $now = time();
    // remove expired files
    $files = preg_grep('/^([^.])/', $scan);
    foreach ($files as $path) {
      $file = './cache/'.$path;
      $mod = filemtime($file);
      if ($now - $mod > EXPIRATION)
        @unlink($file);
    }
  }
  
  function load() {
    $file = $this->path;
    // load cache file
    $this->data = [];
    $cont = @file_get_contents($file);
    if (!$cont) return;

    // parse code requests
    $lines = explode("\n", trim($cont));
    for ($i = 0; $i < count($lines); $i++) {
      $d = explode(",", $lines[$i]);
      $this->data[] = array(
        'code' => $d[0],
        'solution' => $d[1],
        'generated' => $d[2],
        'tested' => $d[3]
      );
    }
    return $this->data;
  }
  
  function save($code) {
    $this->lock($code);
    $file = $this->path;
    // save cache file
    $now = time();
    // store code requests
    $lines = [];
    for ($i = 0; $i < count($this->data); $i++) {
      $d = $this->data[$i];
      if ($now - $d['generated'] > EXPIRATION)
        continue;
      $line = [ $d['code'], $d['solution'], $d['generated'], $d['tested'] ];
      $lines[] = implode(",", $line);
    }
    $cont = implode("\n", $lines)."\n";
    @file_put_contents($file, $cont);
  }
  
  function generate($code) {
    if (!$code)
      return false;
    //$this->lock($code);
    
    $file = $this->path;

    // code generation
    $solution = '';
    for($i = 1; $i <= SOLUTION; $i ++) {
      $c = chr(rand(65, 90));
      if (rand(0, 1) < 1)
        $c = strtolower($c);
      $solution .= $c;
    }
    // save
    $now = time();
    $hash = password_hash(strtolower($solution), PASSWORD_DEFAULT);
    $new = array(
      'code' => base64_encode($code),
      'solution' => $hash,
      'generated' => $now,
      'tested' => 0
    );
    array_push($this->data, $new);
    $this->save($code);

    return $solution;
  }
  
  function verify($code, $solution) {
    if (!$code)
      return false;
    $this->lock($code);

    $code64 = base64_encode($code);
    $solution = trim(strtolower($solution));
    $now = time();

    for ($i = count($this->data) - 1; $i >= 0; $i--) {
      $d = $this->data[$i];
      // skip
      if ($code64 == $d['code']) {
        // flooded
        if ($now - $d['tested'] <= COOLDOWN)
          break;
        // expired
        if ($now - $d['generated'] > EXPIRATION)
          break;
        // modification
        $d['tested'] = $now;

        // correct
        if (password_verify($solution, $d['solution'])) {
          array_splice($this->data, $i, 1);
          $this->save($code);
          return true;
        }
        
        // incorrect
        sleep(COOLDOWN);
      }
    }
    return false;
  }
  
  function image($solution) {
    // initialise image
    $image = @imagecreatetruecolor(250, 100);
    if (!$image) return;

    // allocate colors
    $bgc = rand(0, 255);
    $fgc = ($bgc + 128)%255;
    $bg = imagecolorallocate($image, $bgc, $bgc, $bgc);
    $fg = imagecolorallocate($image, $fgc, $fgc, $fgc);

    // background
    imagefill($image, 0, 0, $bg);

    $fpath = dirname(__FILE__).'/helvetidoodle.ttf';

    // metrics
    $len = strlen($solution);
    $widths = [];
    $sizes = [];
    for($i = 0; $i < $len; $i ++) {
      $size = rand(40, 60);
      $box = imageftbbox($size, 0, $fpath, $solution[$i]);
      $widths[$i] = ($box[4] - $box[0]);
      $sizes[$i] = $size;
    }
    $sum = array_sum($widths);

    // letters
    $x = 250/2 - $sum/2;
    for($i = 0; $i < $len; $i ++) {
      $chr = $solution[$i];
      $r = rand(-20, 20);
      imagefttext($image, $sizes[$i], $r, $x, 70, $fg, $fpath, $chr);
      $x += $widths[$i];
    }
/*
    // dots
    for($i = 0; $i < 30; $i ++) {
      $x = rand(0, 250);
      $y = rand(0, 100);
      $size = rand(32, 64);
      imagefttext($image, $size, 0, $x, $y, $bg, $fpath, '.');
    }
*/
    return $image;
  }
}
