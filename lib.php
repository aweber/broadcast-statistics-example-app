<?php
/* Misc. utility methods
 *
 */

// function to get settings from session
function get_settings() {

    $settings_data = array_key_exists('app_settings', $_COOKIE) ? $_COOKIE['app_settings'] : "";
    $settings_data = base64_decode($settings_data);
    $settings = json_decode($settings_data, true);
    return $settings;
}

// function to set settings in session
function set_settings($settings) {

    $settings_data = json_encode($settings);
    $settings_data = base64_encode($settings_data);
    $thirty_days = time() + 60 * 60 * 24 * 30;
    setcookie('app_settings', $settings_data, $thirty_days);
    return;
}

// Read the saved data from a file.
function get_cache_data($filename) {
    $data = @file($filename);
    if (count($data) == 0) {
        // file not there!
        return False;
    }
    return json_decode($data[0], true);
}

// Save the data to a file to a file so we dont have to make api calls later.
function set_cache_data($filename, $data) {
    $json_data = json_encode($data);
    $file = fopen($filename, "w");
    fwrite($file, $json_data);
    fclose($file);
    return;
}

// Reformat timestamps into readable formats based on the type of stat (hourly or daily) and
// arrange the data in a format suitable for use in JQPlot.
function format_graph_data($broadcast_data, $stat_type, $stat_name, $series_labels) {

    // find api statistic values
    $data = $broadcast_data['stats']["{$stat_type}_{$stat_name}"]['value'];

    // initialize 'series' array as an array of N arrays (where N is the number of series labels)
    $series = array();
    foreach($series_labels as $series_name) {
        array_push($series, array());
    }

    // iterate thru each 'over time' statistic value
    foreach($data as $row) {

        // extract and format the timestamp
        if ($stat_type == 'hourly') { $timestamp = date("ga",  strtotime($row['timestamp'])); }
        if ($stat_type == 'daily')  { $timestamp = date("M j", strtotime($row['timestamp'])); }

        // populate all series elements with each value (formatted for what jqplot requires)
        $ctr = 0;
        foreach($series_labels as $series_name) {
            $name = "{$series_name}_{$stat_name}";
            $value = array_key_exists($name, $row) ? $row[$name] : 0;
            array_push($series[$ctr],  array($timestamp, $value));
            $ctr++;
        }
    }

    return $series;
}

// Return the percentage formatted to 2 decimal places or zero if undefined
function format_percentage($value1, $value2, $decimal_places) {
    if ($value2 == 0) {
        return "0.00";
    } else {
        return number_format(($value1 / $value2) * 100, $decimal_places);
    }
}

// Query the API for the last 4 sent broadcasts and store their stats data.
function refresh_api_data($settings) {
    // aweber-api stuff
    //
    // Concept:  Read in the last 4 sent broadcasts.
    //           Store broadcast data as a simple associative array.
    //           Store each broadcast's Statistics Collection data as a simple associative array
    //           Serialize the data (json_encode) and write it to a file.

    // Retrieve the authentication keys and account settings
    $list_id           = $settings['list_id'];
    $consumerKey       = $settings['consumer_key'];
    $consumerSecret    = $settings['consumer_secret'];
    $accessToken       = $settings['access_token'];
    $accessTokenSecret = $settings['access_token_secret'];

    // connect to the AWeber API.
    $aweber         = new AWeberAPI($consumerKey, $consumerSecret);

    $account        = $aweber->getAccount($accessToken, $accessTokenSecret);
    $account_id     = $account->id;
    $broadcast_data = array();

    /* It is more efficient to go directly to the resource you want if you know its URL.
     * You can also use this approach for named operations like 'find' which is what we're
     * doing in the line below.
     */
    $campaigns = $account->loadFromUrl("/accounts/{$account_id}/lists/{$list_id}/campaigns?ws.op=find&campaign_type=b&ws.size=4");

    // We want to cache the broadcast and stats data, so we're reaching directly into the returned API data
    // and storing that instead of the AWeberCollection and Entry objects themselves.

    $ctr = 0;
    foreach($campaigns->data['entries'] as $broadcast) {

        $id = "b" . $broadcast['id'];
        $stats = $account->loadFromUrl("/accounts/{$account_id}/lists/{$list_id}/campaigns/{$id}/stats");

        // parse campaigns stats into more efficient structure
        $formatted_stats = array();
        foreach($stats->data['entries'] as $stat) {
            // use the StatEntry id (name of stat) stat instead of numerical index of the entries list as the key
            $formatted_stats[$stat['id']] = $stat;
        }

        $broadcast['stats'] = $formatted_stats;
        $broadcast_data[$ctr] = $broadcast;
        $ctr++;
    }
    set_cache_data('broadcast.json', $broadcast_data);
}

// Authorize the connection to the aweber api
function get_access_tokens($oauth_token, $oauth_verifier) {

    $settings            = get_settings();
    $request_token       = array_key_exists('request_token'  , $settings) ? $settings['request_token']   : '';
    $request_secret      = array_key_exists('request_secret' , $settings) ? $settings['request_secret']  : '';
    $consumerKey         = array_key_exists('consumer_key'   , $settings) ? $settings['consumer_key']    : '';
    $consumerSecret      = array_key_exists('consumer_secret', $settings) ? $settings['consumer_secret'] : '';
    $verifier            = array_key_exists('oauth_verifier' ,     $_GET) ? $_GET['oauth_verifier']      : null;

    if ($request_token != $oauth_token) {
        // basic security check! ...
        return;
    }

    $aweber = new AWeberAPI($consumerKey, $consumerSecret);

    // Pull the request token key and verifier code from the URL
    $aweber->user->requestToken = $request_token;
    $aweber->user->verifier = $oauth_verifier;
    $aweber->user->tokenSecret = $request_secret;

    // Exchange a request token with a verifier code for an access token.
    try {
        list($access_token, $access_secret) = $aweber->getAccessToken();

    } catch (AWeberAPIException $exc) {
        # something is not correct!
        return;
    }

    // store tokens
    $settings['access_token'] = $access_token;
    $settings['access_token_secret'] = $access_secret;

    set_settings($settings);
}

// Save API Settings
function save_user_settings($list_id) {

    // store the list_id
    if ($list_id > 0) {

        $settings = get_settings();
        $settings['list_id'] = $list_id;

        // save settings
        set_settings($settings);

         // refresh broadcast data
        refresh_api_data($settings);
        return;

    }
    return;
}

// function to get request tokens from the API and prompt the user to connect their AWeber account
function get_request_tokens($consumer_key, $consumer_secret) {

    $settings = array();
    $settings['consumer_key'] = trim($consumer_key);
    $settings['consumer_secret'] = trim($consumer_secret);
    set_cache_data('broadcast.json', array());

    try {
        $aweber = new AWeberAPI($consumer_key, $consumer_secret);

        // get a request token
        $callbackURL = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "?action=Authorize";
        list($key, $secret) = $aweber->getRequestToken($callbackURL);
        $settings['request_token'] = $key;
        $settings['request_secret'] = $secret;

        // store settings and redirect 
        set_settings($settings);

        // redirect to AWeber's authorization service
        header("Location: {$aweber->getAuthorizeUrl()}");
        exit();

    } catch (AWeberAPIException $exc) {
        // store settings and redirect
        set_settings($settings);

        // redirect to configure page
        header("Location: configure.php");
        exit();
    }
}
