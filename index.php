<?php
/* Broadcast Stats Example App:
 *
 * The basic idea is that we tag certain HTML elements (index.php) with an ID.
 * We then use JQuery (index.js) to dynamically load the content (data.php) for
 * those elements or render a graph using JQPlot.
 *
 * Files:
 *
 * index.php     : main page for app
 * configure.php : configuration page for app
 * index.css     : style sheet for app
 * index.js      : javascript functions for dynamically updating HTML
 *                 content and rendering JQPlot graphs
 * data.php      : the php script that returns each statistic.
 */

require_once('lib.php');

// If the app is not configured or no broadcast data exists:
//   redirect to the configure page
if (!get_settings() || !get_cache_data('broadcast.json')) {
    header("Location: configure.php");
    exit();
}

?>
<head>
  <meta charset="utf-8" />
  <!-- stylesheets: load jquery css first, then override with ours -->
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

  <title>AWeber Stats App</title>
  <meta name = "viewport" content = "initial-scale=1, user-scalable=no">

</head>

<body>
<div class="header">
  <div class="container">
    <img src="images/logo.png" class="logo" height="36" width="142" alt="AWeber Communications"/>
    <a href="#" onClick="return refresh_page();" class="refresh">
      <img src="images/refresh.png" class="refresh-icon" alt="" align="absmiddle"/>
      <img src="images/loading.gif" class="spinner-icon" alt="" align="absmiddle"/>
    </a>
  </div>
</div>
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

      <div class="row">

        <!-- Number Opened -->
        <div class="widget">
          <div class="block-stat">
            <div class="number">
              <a href="#" class="toggle" onclick="return toggle_field();">
                <span id="num-opens-unique"></span>
              </a>
            </div>
            <div class="percent">
              <a href="#" class="toggle" onclick="return toggle_field();">
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
              <a href="#" class="toggle" onclick="return toggle_field();">
                <span id="num-clicks-total" class="integer"></span>
              </a>
            </div>
            <div class="percent">
              <a href="#" class="toggle" onclick="return toggle_field();">
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
              <a href="#" class="toggle" onclick="return toggle_field();">
                <span id="num-complaints" class="integer"></span>
              </a>
            </div>
            <div class="percent">
              <a href="#" class="toggle" onclick="return toggle_field();">
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
              <a href="#" class="toggle" onclick="return toggle_field();">
                <span id="num-unsubs" class="integer"></span>
              </a>
            </div>
            <div class="percent">
              <a href="#" class="toggle" onclick="return toggle_field();">
                <span id="num-unsubs-percent" class="percent"></span><small> %</small>
              </a>
            </div>
          </div>
          Unsubscribes
        </div>
      </div>

      <!--Number Delivered / Bounced-->
      <div class="row">
        <div class="widget-large">
          <div class="number">
            <a href="#" class="toggle" onclick="return toggle_field();">
              <span id="num-delivered" class="large-number"></span>
              <span class="delivered-label"> delivered</span>
              <span class="delivered-divider">/</span>
              <span id="num-undeliv" class="large-number"></span>
              <span class="bounced-label"> bounced</span>
           </a>
          </div>
          <div class="percent">
            <a href="#" class="toggle" onclick="return toggle_field();">
              <span id="num-delivered-percent" class="large-number"></span>
              <span class="delivered-label"> % delivered</span>
              <span class="delivered-divider">/</span>
              <span id="num-undeliv-percent" class="large-number"></span>
              <span class="bounced-label"> % bounced</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Number of unique clicks -->
      <div class="row">
        <div class="widget-large">
          <h2><span id="num-clicks-unique" class="value"></span> people clicked a link in your message.</h2>
        </div>
      </div>

     <!-- graphs -->
     <div class="charts">
       <div class="row" id="range">

         <!-- graphs -->
         <div class="ot-pane" id="graph-tabs">
           <ul>
             <li><a href="#opens-graph">Opens</a></li>
             <li><a href="#clicks-graph">Clicks</a></li>
             <li><a href="#sales-graph">Sales</a></li>
             <li><a href="#webhits-graph">Webhits</a></li>
             <li><a href="#unsubscribed-graph">Unsubscribed</a></li>
           </ul>
           <div class="ot-graph" id="opens-graph"></div>
           <div class="ot-graph" id="clicks-graph"></div>
           <div class="ot-graph" id="sales-graph"></div>
           <div class="ot-graph" id="webhits-graph"></div>
           <div class="ot-graph" id="unsubscribed-graph"></div>
         </div>

         <!-- range -->
         <div id="range">
           <input id="range1" type="radio" name="range" value="hourly" onClick="update_stats();" checked />
           <label for="range1">1 Day</label>
           <input id="range2" type="radio" name="range" value="daily"  onClick="update_stats();"/>
           <label for="range2">14 Days</label> 
         </div>
       </div>
     </div>
     <div class="row">
      <a href="configure.php" class="app-settings">App Settings</a>
     </div>
   </div>
  </body>

<!-- UI/UX javascript code -->
<script language="javascript" type="text/javascript" src="index.js"></script>
