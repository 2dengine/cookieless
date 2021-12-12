<?php
function sendmsg($email, $message, $code, $solution) {
  // check parameters
  if (!$code || !$solution || !$email || !$message)
    return -1;
  // validate email
  if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    return -2;
  // message body
  if (!trim($message))
    return -3;
  // captcha
  include('./captcha/captcha.php');
  $cap = new Captcha();
  if (!$cap->verify($code, $solution))
    return -4;
  // success
  return 1;
}

$email = isset($_POST['email']) ? $_POST['email'] : '';
$message = isset($_POST['message']) ? $_POST['message'] : '';
$code = isset($_POST['code']) ? $_POST['code'] : '';
$solution = isset($_POST['solution']) ? $_POST['solution'] : '';

$status = 0;
// encrypted connection (ssl)
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off')
  $status = -9;
elseif ($_SERVER['REQUEST_METHOD'] === 'POST')
  $status = sendmsg($email, $message, $code, $solution);

if ($status == 0)
  $code = rand(0, 1000000);

// disable caching
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>

<div>
  <h1>Contact form</h1>
<?php if ($status == -9): ?>
  <h2>Requests are only accepted through secure connections</h2>
<?php elseif ($status == 1): ?>
  <h2>Thank you</b> for contacting us!</h2>
<?php else: ?>

<?php if ($status == -1): ?>
  <h2>Please complete all required fields</h2>
<?php elseif ($status == -2): ?>
  <h2>Please enter a valid email address</h2>
<?php elseif ($status == -3): ?>
  <h2>Please enter a valid message</h2>
<?php elseif ($status == -4): ?>
  <h2>Please check the verification code</h2>
<?php elseif ($status == -8): ?>
  <h2>Please try again later</h2>
<?php endif; ?>
  <form method="POST">
    Email address<br>
    <input type="text" name="email" class="full" value="<?= htmlentities($email) ?>" required></input>
    <br>
    Message<br>
    <textarea name="message" rows="6" class="full" required><?= htmlentities($message) ?></textarea>
    <br>
    Verification code
    <br>
    <input type="hidden" name="code" value="<?= $code ?>" id="code"></input>
    <img src="index.php?code=<?= $code ?>" id="puzzle" onclick="javascript:refresh();"><br>
    <input type="text" name="solution" class="full" autocomplete="off" required></input>
    <br>
    <input type="submit" onclick="javascript:this.disabled=true; this.form.submit();"></input>
  </form>
<?php endif; ?>

</div>
