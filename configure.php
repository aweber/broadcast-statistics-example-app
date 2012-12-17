<?php
require_once('aweber_api/aweber_api.php');
require_once('lib.php');

// Handle aweber oauth callback or POSTed actions
$action = array_key_exists('action', $_GET ) ? $_GET['action']  : '';
$action = array_key_exists('action', $_POST) ? $_POST['action'] : $action;

// Cancel
if ($action == "Cancel") {
    header("Location: index.php");
    exit();
}

// Authorize connection to AWeber API
if ($action == "Authorize") {
    $oauth_token    = array_key_exists('oauth_token'    , $_GET) ? $_GET['oauth_token']    : null;
    $oauth_verifier = array_key_exists('oauth_verifier' , $_GET) ? $_GET['oauth_verifier']: null;
    get_access_tokens($oauth_token, $oauth_verifier);
    header("Location: configure.php");
    exit();
}

// Connect App
if ($action == "Continue") {
    $consumer_key    = array_key_exists('consumer_key',    $_POST) ? $_POST['consumer_key']    : null;
    $consumer_secret = array_key_exists('consumer_secret', $_POST) ? $_POST['consumer_secret'] : null;
    get_request_tokens($consumer_key, $consumer_secret);
    exit();
}

// Save List setting.
if ($action == "Save") {
    $list_id = array_key_exists('list_id', $_GET) ? $_GET['list_id'] : 0;
    save_user_settings($list_id);
    header("Location: index.php");
    exit();
}

// Main configure page
$settings = get_settings();
if (!$settings) {
    $settings = array();
}

// load structs
$list_id           = array_key_exists('list_id'            , $settings) ? $settings['list_id']             : 0;
$consumer_key      = array_key_exists('consumer_key'       , $settings) ? $settings['consumer_key']        : '';
$consumer_secret   = array_key_exists('consumer_secret'    , $settings) ? $settings['consumer_secret']     : '';
$accessToken       = array_key_exists('access_token'       , $settings) ? $settings['access_token']        : '';
$accessTokenSecret = array_key_exists('access_token_secret', $settings) ? $settings['access_token_secret'] : '';
$page_mode         = "normal";
$error             = null;

try {
    $aweber     = new AWeberAPI($consumer_key, $consumer_secret);
    $account    = $aweber->getAccount($accessToken, $accessTokenSecret);
    $account_id = $account->id;
    $lists      = $account->loadFromUrl("/accounts/{$account_id}/lists");

} catch (AWeberAPIException $exc) {
    $page_mode = "error";
    $error = $exc->type . " " . $exc->message;
}

if (empty($consumer_key)) { 
    $page_mode = "first_time";
}

?>
<head>
  <meta charset="utf-8" />
  <!-- stylesheets: load jqueries first, then override with ours -->
  <link rel="stylesheet" type="text/css" href="jquery/jquery-ui.css" />
  <link rel="stylesheet" type="text/css" href="index.css" />

  <!-- jquery javascript includes -->
  <script language="javascript" type="text/javascript" src="jquery/jquery-1.8.2.js"></script>
  <script language="javascript" type="text/javascript" src="jquery/jquery-ui.js"></script>

  <title>App Settings</title>
  <meta name = "viewport" content = "initial-scale=1, user-scalable=no">
</head>

<body>
<div class="header">
  <div class="container">
  <img src="images/logo.png" class="logo" height="36" width="142" alt="AWeber Communications"/>
  <?php if ($page_mode == "normal") { ?>
    <a href="index.php" class="refresh">Cancel</a>
  <?php } ?>
  </div>
</div>

<div class="container settings-page">
<?php
if ($page_mode == "first_time") {
?>
      <div class="row">
        <br/>
        <h1>Welcome to AWeber Pocket Stats!</h1>
        <div class="info">
        <img src="images/ipad.jpg" alt="" id="ipad" style="display:none;"/>
        </div>
        <form class="form" method="post" action="configure.php">
          <h2 class="get-started">Let's Get Started</h2>
          <div class="widget-large">
            You will need these three things:
            <ul>
             <li>An <a href="https://labs.aweber.com">AWeber Labs account</a>
             <li>An <a href="https://labs.aweber.com/apps">app created</a> on that AWeber Labs account.</li>
             <li>The <a href="https://labs.aweber.com/getting_started/private#1">consumer key and secret</a> from that app.</li>
            </ul>
            <label>Consumer Key:</label>
            <input class="config" type="text" name="consumer_key" value="<?php echo $consumer_key; ?>" /><br/>
            <label>Consumer Secret:</label>
            <input class="config" type="text" name="consumer_secret" value="<?php echo $consumer_secret; ?>" /><br/>
            <input type="submit" class="continue" name="action" value="Continue" />
          </div>
        </form>
      </div>

<?php
} if ($page_mode == "error") {
?>

      <div class="row">
        <br/>
        <h1>Oops! Something went wrong.</h1>   

        <div class="info">
          <p>Please make sure your consumer key and consumer secret are correct.</p>
          <p>To get back on track you will need:</p>
          <ul>
           <li>An <a href="https://labs.aweber.com">AWeber Labs account</a>
           <li>An <a href="https://labs.aweber.com/apps">app created</a> on that AWeber Labs account.</li>
           <li>The <a href="https://labs.aweber.com/getting_started/private#1">consumer key and secret</a> from that app.</li>
          </ul>
        </div>
        <form class="form" method="post" action="configure.php">
          <div class="widget-large">
            <label>Consumer Key:</label>
            <input class="config" type="text" name="consumer_key" value="<?php echo $consumer_key; ?>" /><br/>
            <label>Consumer Secret:</label>
            <input class="config" type="text" name="consumer_secret" value="<?php echo $consumer_secret; ?>" /><br/>
            <input type="submit" name="action" class="continue" value="Continue" />
          </div>
        </form>
      </div>

<?php
}
?>

  <div class="container">


<?php if ($page_mode == "normal") { ?>

    <div class="row">
      <form method="GET" action="configure.php">
      <div class="settings-block">
      <?php if (!get_cache_data('broadcast.json')) {?><br />List does not have broadcasts, please choose another list!<?php } ?>
        <h2>Current List:</h2>
        <input type="hidden" name="action" value="Save" />
        <select name="list_id" style="font-size:20px;" onChange="submit();">

          <?php if ($list_id == 0) { ?><option value=''>--[ Please Select List ] --</option><?php } ?>
          <?php
            foreach($lists as $list) {
               if ($list->id == $list_id) {  echo "<option id=\"{$list->id}\" name=\"list\" value=\"{$list->id}\" selected>{$list->name}</option>"; }
               else                       {  echo "<option id=\"{$list->id}\" name=\"list\" value=\"{$list->id}\"         >{$list->name}</option>"; }
            }
          ?>
        </select>
        </div>
    </form>
    </div>

<?php } ?>

  </div>
</body>
