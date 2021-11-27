<?php

// serve image
if (!isset($_GET['code'])) {
  http_response_code(400);
  exit;
}

$code = $_GET['code'];
if (strlen($code) > 255)
  $code = substr($code, 0, 255);

// save solution
include("captcha.php");
$cap = new Captcha();
$cap->cleanup();
$solution = $cap->generate($code);

// generation failed
if (!$solution) {
  http_response_code(500);
  exit;
}

// display image
$image = $cap->image($solution);
if (!$image) {
  http_response_code(500);
  exit;
}

header('Content-type: image/png');
imagepng($image);
imagedestroy($image);
exit;

?>
