<?php
/* AJAX Data script
 *
 * The main application requests data from the AWeber API thru this script.
 *
 * This script decodes the type of statistic, does some processing, and returns it
 * to the caller (either a JQPlot graph, or a JQuery ajax request.
 */

require_once('aweber_api/aweber_api.php');
require_once('lib.php');

// determine the action (aka what stat to retrieve)
$action      = array_key_exists('action'     , $_GET) ? $_GET['action']              : '';

// handle refreshes first
if ($action == "refresh-data") {
    $settings = get_settings();
    refresh_api_data($settings);
    exit();
}

// determine the campaign we're interested in (a integer ranging between 0 and 3)
//  corresponding to the last 4 sent broadcasts, with index 0 being the most recently sent bc
$campaign_id = array_key_exists('campaign_id', $_GET) ? intval($_GET['campaign_id']) : 0;

// determine range of jqplot graph (hourly or daily)
$graph_range = array_key_exists('graph_range', $_GET) ? $_GET['graph_range']         : 'daily';

// Read api stat data and populate some structures for use below
$broadcasts = get_cache_data('broadcast.json');

$broadcast_data = $broadcasts[$campaign_id];
$total_sent = $broadcast_data['total_sent'];

// return the appropriate data based on the action
switch ($action) {

    case "broadcast-subject":
        echo $broadcast_data['subject'];
        break;

    case "broadcast-sent-time":
        echo date("g:ia", strtotime($broadcast_data['sent_at']));
        break;

    case "num-sent":
        echo number_format($total_sent);
        break;

    case "num-undeliv":
        echo $broadcast_data['total_undelivered'];
        break;

    case "num-delivered":
        echo number_format($total_sent - $broadcast_data['total_undelivered']);
        break;

    case "num-delivered-percent":
        echo format_percentage($total_sent - $broadcast_data['total_undelivered'], $total_sent, 1);
        break;

    case "num-engaged":
        echo format_percentage($broadcast_data['stats']['unique_clicks']['value'],
                               $total_sent - $broadcast_data['total_undelivered'], 1);
        break;

    case "num-opens-unique":
        echo number_format($broadcast_data['stats']['unique_opens']['value']);
        break;

    case "num-opens-unique-percent":
        echo format_percentage($broadcast_data['stats']['unique_opens']['value'], $total_sent, 1);
        break;

    case "num-opens-total":
        echo number_format($broadcast_data['stats']['total_opens']['value']);
        break;

    case "num-opens-total-percent":
        echo format_percentage($broadcast_data['stats']['total_opens']['value'], $total_sent, 1);
        break;

    case "num-clicks-unique":
        echo number_format($broadcast_data['stats']['unique_clicks']['value']);
        break;

    case "num-clicks-unique-percent":
        echo format_percentage($broadcast_data['stats']['unique_clicks']['value'], $total_sent, 1);
        break;

    case "num-clicks-total":
        echo number_format($broadcast_data['stats']['total_clicks']['value']);
        break;

    case "num-clicks-total-percent":
        echo format_percentage($broadcast_data['stats']['total_clicks']['value'], $total_sent, 1);
        break;

    case "num-sales":
        echo number_format($broadcast_data['stats']['total_sales']['value']);
        break;

    case "num-sales-dollars":
        echo "$" . number_format($broadcast_data['stats']['total_sales_dollars']['value'], 2) . "";
        break;

    case "num-unsubs":
        echo number_format($broadcast_data['total_unsubscribes']);
        break;

    case "num-unsubs-percent":
        echo format_percentage($broadcast_data['total_unsubscribes'], $total_sent, 2);
        break;

    case "num-undeliv":
        echo number_format($broadcast_data['total_undelivered']);
        break;

    case "num-undeliv-percent":
        echo format_percentage($broadcast_data['total_undelivered'], $total_sent, 2);
        break;

    case "num-complaints":
        echo number_format($broadcast_data['total_spam_complaints']);
        break;

    case "num-complaints-percent":
        echo format_percentage($broadcast_data['total_spam_complaints'], $total_sent, 2);
        break;

    case "info-box":
        // spam assassin score
        echo "Spam Assassin Score: " . $broadcast_data['spam_assassin_score'] . "<br />";

        // is click tracking on or off
        if ($broadcast_data['click_tracking_enabled'] == true) { echo "Uses click tracking<br />"; }
        else                                                   { echo "Does not use click tracking <br />"; }

        // is this bc featured in the rss feed?
        if ($broadcast_data['is_archived'] == true)            { echo "Published to RSS feed <br />"; }
        else                                                   { echo "Not published to RSS feed <br />"; }
        break;

    case "message-type":
        echo $broadcast_data['content_type'];
        break;

    case "bc1-sent-date":
        echo date("m/d", strtotime($broadcasts[0]['sent_at']));
        break;

    case "bc2-sent-date":
        echo date("m/d", strtotime($broadcasts[1]['sent_at']));
        break;

    case "bc3-sent-date":
        echo date("m/d", strtotime($broadcasts[2]['sent_at']));
        break;

    case "bc4-sent-date":
        echo date("m/d", strtotime($broadcasts[3]['sent_at']));
        break;

    case "opens-ot":
        echo format_graph_data($broadcast_data, $graph_range, 'opens', array('total', 'unique'));
        break;

    case "clicks-ot":
        echo format_graph_data($broadcast_data, $graph_range, 'clicks', array('total', 'unique'));
        break;

    case "sales-ot":
        echo format_graph_data($broadcast_data, $graph_range, 'sales', array('total'));
        break;

    case "unsubscribed-ot":
        echo format_graph_data($broadcast_data, $graph_range, 'unsubscribed', array('total'));
        break;

    case "webhits-ot":
        echo format_graph_data($broadcast_data, $graph_range, 'webhits', array('total'));
        break;

    default:
        echo "unknown ($action)";
        break;
}
