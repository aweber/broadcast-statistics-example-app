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
$action = array_key_exists('action', $_GET) ? $_GET['action'] : 'current-broadcast';

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
$graph_range = array_key_exists('graph_range', $_GET) ? $_GET['graph_range'] : 'daily';

// Read api stat data and populate some structures for use below
$broadcasts = get_cache_data('broadcast.json');

// broadcast does not exist, skip it ...
if(count($broadcasts) <= $campaign_id) {
    exit();
}

$broadcast_data  = $broadcasts[$campaign_id];
$total_sent      = $broadcast_data['total_sent'];
$total_delivered = $total_sent - $broadcast_data['total_undelivered'];
$broadcast_stats = $broadcast_data['stats'];
$page_data       = null;

// gather broadcast data
switch ($action) {

    case "current-broadcast":
        // aggregate broadcast stats
        $page_data = array();
        $page_data["num-clicks-total"]  = number_format($broadcast_stats['total_clicks']['value']);
        $page_data["num-clicks-unique"] = number_format($broadcast_stats['unique_clicks']['value']);
        $page_data["num-opens-total"]   = number_format($broadcast_stats['total_opens']['value']); 
        $page_data["num-opens-unique"]  = number_format($broadcast_stats['unique_opens']['value']);
        $page_data["num-sales"]         = number_format($broadcast_stats['total_sales']['value']);
        $page_data["num-sales-dollars"] = number_format($broadcast_stats['total_sales_dollars']['value'], 2);
        $page_data["num-complaints"]    = number_format($broadcast_data['total_spam_complaints']);
        $page_data["num-undeliv"]       = number_format($broadcast_data['total_undelivered']);
        $page_data["num-undeliv"]       = number_format($broadcast_data['total_undelivered']);
        $page_data["num-unsubs"]        = number_format($broadcast_data['total_unsubscribes']);
        $page_data["num-delivered"]     = number_format($total_delivered);
        $page_data["num-sent"]          = number_format($total_sent);
        $page_data["message-type"]      = $broadcast_data['content_type'];
        $page_data["broadcast-subject"] = $broadcast_data['subject'];

        // percentages
        $page_data["num-complaints-percent"]    = format_percentage($broadcast_data['total_spam_complaints'],   $total_sent, 2);
        $page_data["num-opens-total-percent"]   = format_percentage($broadcast_stats['total_opens']['value'],   $total_sent, 1);
        $page_data["num-opens-unique-percent"]  = format_percentage($broadcast_stats['unique_opens']['value'],  $total_sent, 1);
        $page_data["num-clicks-total-percent"]  = format_percentage($broadcast_stats['total_clicks']['value'],  $total_sent, 1);
        $page_data["num-clicks-unique-percent"] = format_percentage($broadcast_stats['unique_clicks']['value'], $total_sent, 1);
        $page_data["num-unsubs-percent"]        = format_percentage($broadcast_data['total_unsubscribes'],      $total_sent, 2);
        $page_data["num-undeliv-percent"]       = format_percentage($broadcast_data['total_undelivered'],       $total_sent, 2);
        $page_data["num-delivered-percent"]     = format_percentage($total_delivered,                           $total_sent, 1);
        $page_data["num-engaged"]               = format_percentage($broadcast_stats['unique_clicks']['value'], $total_delivered, 1);

        // dates and times
        $page_data["bc1-sent-date"] = count($broadcasts) > 0 ? date("m/d",  strtotime($broadcasts[0]['sent_at'])) : "";
        $page_data["bc2-sent-date"] = count($broadcasts) > 1 ? date("m/d",  strtotime($broadcasts[1]['sent_at'])) : "";
        $page_data["bc3-sent-date"] = count($broadcasts) > 2 ? date("m/d",  strtotime($broadcasts[2]['sent_at'])) : "";
        $page_data["bc4-sent-date"] = count($broadcasts) > 3 ? date("m/d",  strtotime($broadcasts[3]['sent_at'])) : "";
        $page_data["broadcast-sent-time"] = date("g:ia", strtotime($broadcast_data['sent_at']));

        break;

    case "opens-graph":
        $page_data = format_graph_data($broadcast_data, $graph_range, 'opens', array('total', 'unique'));
        break;

    case "clicks-graph":
        $page_data = format_graph_data($broadcast_data, $graph_range, 'clicks', array('total', 'unique'));
        break;

    case "sales-graph":
        $page_data = format_graph_data($broadcast_data, $graph_range, 'sales', array('total'));
        break;

    case "unsubscribed-graph":
        $page_data = format_graph_data($broadcast_data, $graph_range, 'unsubscribed', array('total'));
        break;

    case "webhits-graph":
        $page_data = format_graph_data($broadcast_data, $graph_range, 'webhits', array('total'));
        break;
}

// Render json output
header('Content-Type: application/json; charset=utf-8');
echo json_encode($page_data);
exit();
