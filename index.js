var page_data = Array();

// function to toggle between numbers and percentages
function toggle_field() {
    $(".percent").toggle();
    $(".number").toggle();
    return false;
}

// function to toggle the spinner vs refresh icon
function spinner_state(state) {
    if(state) {
        // show spinner, hide refresh
        $(".spinner-icon").show();
        $(".refresh-icon").hide();

    } else {
        // hide spinner, show refresh
        $(".spinner-icon").hide();
        $(".refresh-icon").show();
    }
}

// function to refresh page
function refresh_page() {

    // we're already refreshing something, cancel
    if ($(".spinner-icon").is(":visible")) {
        return false;
    }

    // show spinner icon
    spinner_state(true);

    // make a blocking call to refresh the data
    jQuery.ajax({
         url:    'data.php?action=refresh-data',
         success: function(result) {
             spinner_state(false);
             switch_campaign(0);
         },
         error: function(jqXHR, textStatus, errorThrown) {
             alert('Unable to communicate with the AWeber API.  Please try your request again.');
         },
    });
    return false;
}

// function to update the currently selected broadcase based on what was clicked on.
function switch_campaign(campaign_id) {
    // change campaign id
    spinner_state(true);
    $("#campaign-id").val(campaign_id);

    // make a blocking call to refresh the data
    page_data = Array();
    jQuery.ajax({
         url:    'data.php?action=current-broadcast&campaign_id=' + campaign_id,
         async:   false,
         dataType: 'json',
         success: function(result) { page_data = result },
         error: function(jqXHR, textStatus, errorThrown) { alert('Ajax Query Error'); },
    });

    // update stats on page
    update_stats();
    spinner_state(false);
    return false;
}

// function to update the stats via ajax
function update_stats() {
    spinner_state(true);

    // load form variables
    var campaign_id = parseInt($("#campaign-id").val());
    var graph_range = $("input:radio[name=range]:checked").val();

    // update html content for graphs when you change the range buttons
    $.jqplot('opens-graph'       , [], graph_config('opens-graph'       , campaign_id, graph_range, 'Opens'       , true )).replot();
    $.jqplot('clicks-graph'      , [], graph_config('clicks-graph'      , campaign_id, graph_range, 'Clicks'      , true )).replot();
    $.jqplot('sales-graph'       , [], graph_config('sales-graph'       , campaign_id, graph_range, 'Sales'       , false)).replot();
    $.jqplot('webhits-graph'     , [], graph_config('webhits-graph'     , campaign_id, graph_range, 'Webhits'     , false)).replot();
    $.jqplot('unsubscribed-graph', [], graph_config('unsubscribed-graph', campaign_id, graph_range, 'Unsubscribed', false)).replot();

    // highlight which bc is active ....
    if (campaign_id == 0) { $("#bc1").addClass("highlight");    }
    else                  { $("#bc1").removeClass("highlight"); }
    if (campaign_id == 1) { $("#bc2").addClass("highlight");    }
    else                  { $("#bc2").removeClass("highlight"); }
    if (campaign_id == 2) { $("#bc3").addClass("highlight");    }
    else                  { $("#bc3").removeClass("highlight"); }
    if (campaign_id == 3) { $("#bc4").addClass("highlight");    }
    else                  { $("#bc4").removeClass("highlight"); }

    // update html content for non-graph html elements.
    var updatable_elements = [
        'bc1-sent-date', 'bc2-sent-date', 'bc3-sent-date', 'bc4-sent-date',
        'info_box', 'broadcast-subject', 'broadcast-sent-time', 'message-type',
        'num-sent', 'num-delivered', 'num-delivered-percent', 'num-engaged',
        'num-opens-unique', 'num-opens-unique-percent',
        'num-opens-total', 'num-opens-total-percent',
        'num-clicks-unique', 'num-clicks-unique-percent', 'num-clicks-total', 'num-clicks-total-percent',
        'num-sales', 'num-sales-dollars',
        'num-unsubs', 'num-unsubs-percent',
        'num-undeliv', 'num-undeliv-percent',
        'num-complaints', 'num-complaints-percent',
        'top-clicks', 'top-opens', 'top-sales', 'top-webhits',
    ];

    // update element values
    for(i=0; i<updatable_elements.length; i++) {
        var widget_name = updatable_elements[i];
        $('#' + widget_name).html(page_data[widget_name]);
    }

    // turn spinner off
    spinner_state(false);
}

/* jqplot graph config rendering function
 * This function defines how the graphs work, look, and how they query data.php 
 */
function graph_config(css_tag, campaign_id, graph_range, stat_name, show_unique_stats) {

    // configure labels and legends based on statistic name and type
    var legend_labels = [];
    var series_labels = [];

    // add legend and series label for total (clicks, opens, etc...) stats
    legend_labels.push('<span class="Total">Total ' + stat_name + '</span>');
    series_labels.push({label: 'Total', color: '#2984c2'});

    // add legend and series label for unique (clicks, opens, etc...) stats
    if (show_unique_stats) {
        legend_labels.push('<span class="Unique">Unique ' + stat_name + '</span>');
        series_labels.push({label: 'Unique', color: '#549c14'});
    }

    // function to make an ajax call to load the json data from the data controller
    // this will automatically json_decode the data for us
    var ajaxDataRenderer = function(url, plot) {
        var ret = null;
        var the_url = 'data.php?action=' + css_tag + '&campaign_id=' + campaign_id + '&graph_range=' + graph_range
        $.ajax({
            async: false,
            url: the_url,
            dataType:'json',
            success: function(data) {
               ret = data;
            }
        });
        return ret;
    };

    /* Configure the JQPlot graphs.
     *
     * For more information on what this is, please check out the JQPlot docs
     * http://www.jqplot.com/tests/line-charts.php
     */

    var config = {
        // Set height and width (required when using tabs)
        height: 330,
        width: 570,

        // Dont show a legend.
        legend: {
            show: false,
        },

        // Use the axis tick renderer.
        axesDefaults: {
            tickRenderer: $.jqplot.CanvasAxisTickRenderer
        },

        // Use the series label HTML that we generated above.
        series: series_labels,

        // Render each series with a label, and a 5pt line width
        seriesDefaults: {
            markerOptions: { size: 12 },
            showLabel: true,
            lineWidth: 5
        },

        // Choose our colors for the graph grid
        grid: {gridLineColor: '#ededed', background: '#f9f9f9'},

        // Use the ajax renderer (retrive the data from an ajax request)
        dataRenderer: ajaxDataRenderer,

        // Configure the axes
        axes: {

         // X-Axis: put text at -45 degree angle
          xaxis: {
             renderer: $.jqplot.CategoryAxisRenderer,
             tickRenderer: $.jqplot.CanvasAxisTickRenderer,
             tickOptions: {
                angle: -45,
             },
          },

          // Y-Axis: format as numbers with a min value of zero
          yaxis: {
             tickRenderer:$.jqplot.CanvasAxisTickRenderer,
             tickOptions:{
                formatString:'%\'7d',
             },
             min: 0,
          }
        },

        // Dont use highlighter
        highlighter: {
            show: false,
        },

        // Make cursor turn into crosshairs and show tool tips with series values when the
        //   user hovers over the graph
        cursor: {
            style: 'crosshair',
            show: true,
            showVerticalLine: true,
            showTooltip: true,
            followMouse: true,
            tooltipFormatString: '<span class="tooltipValue %1$s">%3$s</span> <span class="tooltipLabel %1$s">%1$s</span>',
            intersectionThreshold: 20,
            showTooltipDataPosition: true
        }
    };
    return config;
}

// javascript to run when document.ready() event fires off
var page_startup = function() {

    // configure buttons
    $("#range").buttonset();

    // start loading the stats
    switch_campaign(0);

    // configure tabs
    $("#graph-tabs").tabs();

    // update html content for graphs when you change the tabs
    $('#graph-tabs').bind('tabsshow', function(event, ui) {
       spinner_state(true);
       var campaign_id = parseInt($("#campaign-id").val());
       var graph_range = $("input:radio[name=range]:checked").val();

       if(ui.index == 0) {
           $.jqplot('opens-graph'       , [], graph_config('opens-graph'       , campaign_id, graph_range, 'Opens'       , true )).replot();
       }
       if(ui.index == 1) {
           $.jqplot('clicks-graph'      , [], graph_config('clicks-graph'      , campaign_id, graph_range, 'Clicks'      , true )).replot();
       }
       if(ui.index == 2) {
           $.jqplot('sales-graph'       , [], graph_config('sales-graph'       , campaign_id, graph_range, 'Sales'       , false)).replot();
       }
       if(ui.index == 3) {
           $.jqplot('webhits-graph'     , [], graph_config('webhits-graph'     , campaign_id, graph_range, 'Webhits'     , false)).replot();
       }
       if(ui.index == 4) {
           $.jqplot('unsubscribed-graph', [], graph_config('unsubscribed-graph', campaign_id, graph_range, 'Unsubscribed', false)).replot();
       }
       spinner_state(false);
    });
};

$(document).ready(page_startup());
