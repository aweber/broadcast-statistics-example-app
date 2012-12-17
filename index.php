<?php
require_once('lib.php');

// the app is not configured, redirect them to configure page
if (!get_cache_data('settings.json')) {
    header("Location: configure.php");
    exit();
}

// no broadcast data exists, load it and try again
if (!get_cache_data('broadcast.json')) {
    header("Location: configure.php");
    exit();
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

  <!-- jqplot javascript includes -->
  <script language="javascript" type="text/javascript" src="jqplot/jquery.jqplot.js"></script>
  <script language="javascript" type="text/javascript" src="jqplot/plugins/jqplot.barRenderer.js"></script>
  <script language="javascript" type="text/javascript" src="jqplot/plugins/jqplot.canvasAxisLabelRenderer.js"></script>
  <script language="javascript" type="text/javascript" src="jqplot/plugins/jqplot.canvasAxisTickRenderer.js"></script>
  <script language="javascript" type="text/javascript" src="jqplot/plugins/jqplot.canvasTextRenderer.js"></script>
  <script language="javascript" type="text/javascript" src="jqplot/plugins/jqplot.categoryAxisRenderer.js"></script>
  <script language="javascript" type="text/javascript" src="jqplot/plugins/jqplot.ciParser.js"></script>
  <script language="javascript" type="text/javascript" src="jqplot/plugins/jqplot.dateAxisRenderer.js"></script>
  <script language="javascript" type="text/javascript" src="jqplot/plugins/jqplot.pointLabels.js"></script>
  <script language="javascript" type="text/javascript" src="jqplot/plugins/jqplot.cursor.js"></script>
  <script language="javascript" type="text/javascript" src="jqplot/plugins/jqplot.highlighter.js"></script>
  <script language="javascript" type="text/javascript" src="jqplot/plugins/jqplot.enhancedLegendRenderer.js"></script>
  <title>Last 4 Sent Broadcasts ...</title>
  <meta name = "viewport" content = "initial-scale = 1, user-scalable = no">
</head>

<body>
  <div class="container">
    <div class="ui-widget row nav-bar" id="broadcasts">
      <input id="campaign-id" type="hidden" name="campaign-id" value="0" />

      <a href="#" class="broadcast" id="bc1" onclick="switch_campaign(0);">
        <span class="envelope-icon"></span>
        <small id="bc1-sent-date"></small>
      </a>
      <div class="full-screen-clear"></div>
      <a href="#" class="broadcast" id="bc2" onclick="switch_campaign(1);">
        <span class="envelope-icon"></span>
        <small id="bc2-sent-date"></small>
      </a>
      <div class="full-screen-clear"></div>
      <a href="#" class="broadcast" id="bc3" onclick="switch_campaign(2);">
        <span class="envelope-icon"></span>
        <small id="bc3-sent-date"></small>
      </a>
      <div class="full-screen-clear"></div>
      <a href="#" class="broadcast" id="bc4" onclick="switch_campaign(3);">
        <span class="envelope-icon"></span>
        <small id="bc4-sent-date"></small>
      </a>
    </div>
    <div class="broadcast-content">
      <div class="row">
        <h1 id="broadcast-subject"></h1>
      </div>

      <div class="time">
       Sent at <span id="broadcast-sent-time"></span> <span class="message-type" id="message-type"></span>
      </div>

      <!--Number Delivered / Bounced-->
      <div class="row">
        <div class="widget-large">
          <div class="number">
            <a href="#" class="toggle" onclick="toggle_field('percent');">
              <span id="num-delivered" class="large-number"></span>
              <span class="delivered-label"> delivered</span>
              <span class="delivered-divider">/</span>
              <span id="num-undeliv" class="large-number"></span>
              <span class="bounced-label"> bounced</span>
           </a>
          </div>
          <div class="percent">
            <a href="#" class="toggle" onclick="toggle_field('percent');">
              <span id="num-delivered-percent" class="large-number"></span>
              <span class="delivered-label"> % delivered</span>
              <span class="delivered-divider">/</span>
              <span id="num-undeliv-percent" class="large-number"></span>
              <span class="bounced-label"> % bounced</span>
            </a>
          </div>
        </div>
      </div>

      <div class="row">

        <!-- Number Opened -->
        <div class="widget">
          <div class="block-stat">
            <div class="number">
              <a href="#" class="toggle" onclick="toggle_field('percent');">
                <span id="num-opens-unique"></span>
              </a>
            </div>
            <div class="percent">
              <a href="#" class="toggle" onclick="toggle_field('total');">
                <span id="num-opens-unique-percent"></span><small> %</small>
              </a>
            </div>
          </div>
          Opened
        </div>

        <!-- Number Clicks -->
        <div class="widget">
          <div class="block-stat">
            <div class="number">
              <a href="#" class="toggle" onclick="toggle_field('percent');">
                <span id="num-clicks-total" class="integer"></span>
              </a>
            </div>
            <div class="percent">
              <a href="#" class="toggle" onclick="toggle_field('total');">
                <span id="num-clicks-total-percent" class="percent"></span><small> %</small>
              </a>
            </div>
          </div>
          Clicks
        </div>

        <div class="mobile-clear"></div>

        <!-- Number Complaints -->
        <div class="widget">
          <div class="block-stat">
            <div class="number">
              <a href="#" class="toggle" onclick="toggle_field('percent');">
                <span id="num-complaints" class="integer"></span>
              </a>
            </div>
            <div class="percent">
              <a href="#" class="toggle" onclick="toggle_field('total');">
                <span id="num-complaints-percent" class="percent"></span><small> %</small>
              </a>
            </div>
          </div>
          Complaints
        </div>

        <!-- Number Unsubscribes -->
        <div class="widget">
          <div class="block-stat">
            <div class="number">
              <a href="#" class="toggle" onclick="toggle_field('percent');">
                <span id="num-unsubs" class="integer"></span>
              </a>
            </div>
            <div class="percent">
              <a href="#" class="toggle" onclick="toggle_field('total');">
                <span id="num-unsubs-percent" class="percent"></span><small> %</small>
              </a>
            </div>
          </div>
          Unsubscribes
        </div>
      </div>

      <!-- Number of unique clicksribes -->
      <div class="row">
        <div class="widget-large">
          <h2><span id="num-clicks-unique" class="value"></span> people clicked a link in your message.</h2>
        </div>
      </div>

     <!-- graphs -->
     <div class="row" id="range">

       <!-- range -->
       <div id="range">
         <input id="range1" type="radio" name="range" value="hourly" onClick="update_stats('graphs');" checked />
         <label for="range1">1 Day</label>
         <input id="range2" type="radio" name="range" value="daily"  onClick="update_stats('graphs');"/>
         <label for="range2">14 Days</label> 
       </div>

       <!-- graphs -->
       <div class="ot-pane" id="graph-tabs">
         <ul>
           <li><a href="#opens-ot">Opens</a></li>
           <li><a href="#clicks-ot">Clicks</a></li>
           <li><a href="#sales-ot">Sales</a></li>
           <li><a href="#webhits-ot">Webhits</a></li>
           <li><a href="#unsubscribed-ot">Unsubscribed</a></li>
         </ul>
         <div class="ot-graph" id="opens-ot"></div>
         <div class="ot-graph" id="clicks-ot"></div>
         <div class="ot-graph" id="sales-ot"></div>
         <div class="ot-graph" id="webhits-ot"></div>
         <div class="ot-graph" id="unsubscribed-ot"></div>
       </div>
     </div>
     <a href="configure.php">App Settings</a>
   </div>
   
  </body>

<!-- page javascript -->
<script language="javascript" type="text/javascript" src="index.js"></script>
