var chart;
var defaultLoad = false;
var chartData = [];

// Function to load the JSON
AmCharts.loadJSON = function(timeSpan, buttonClick) {

    // Empty graph and delete current data
    chartData = [];
    createStockChart();

    // Show the loading div
    $('.loading').show();

    // Load the JSON
    $.ajax({
        url: "http://cryptorates.info/?from=2016-03-02&currency=BTC_1CR",
        type: 'get',
        dataType: 'json',
        cache: false,
        async: true,
        success: function(rows) {

            // Hide the loading div
            $('.loading').hide();
            var iTem = rows[0]['BTC_1CR'][0];
            for (var i = 0; i < iTem.length; i++) {
                chartData.push({
                    date: rows[i]['date'],
                    open: rows[i]['open'],
                    close: rows[i]['close'],
                    high: rows[i]['high'],
                    low: rows[i]['low'],
                    exchange_volume: rows[i]['exchange_volume']
                });
            }

            // Hack: Adding extra row to make it show the latest point
            // Convert last date point and add 10 minutes
            date = rows[rows.length-1]['date'];
            date = new Date(date.replace(" ","T") + 'Z');
            var localOffset = date.getTimezoneOffset() * 60000;
            date.setTime(date.getTime() + 600000 + localOffset);

            // Push to chart
            chartData.push({
                date: date,
                open: rows[rows.length-1]['close'],
                close: rows[rows.length-1]['close'],
                high: rows[rows.length-1]['close'],
                low: rows[rows.length-1]['close'],
                exchange_volume: 0
            });

            // Push it to the chart
            chart.dataProvider = chartData;
            chart.validateNow();

            if (buttonClick === false) {
                // Handle the default load
                $('input[value="6 hours"]').click();
            } else {
                // Change the selected button
                $('input[value="MAX"]').removeClass('amChartsButtonSelected').addClass('amChartsButton');
                if (timeSpan == '6hh') {
                    $('input[value="6 hours"]').removeClass('amChartsButton').addClass('amChartsButtonSelected');
                } else if (timeSpan == '1DD') {
                    $('input[value="24 hours"]').removeClass('amChartsButton').addClass('amChartsButtonSelected');
                } else if (timeSpan == '3DD') {
                    $('input[value="3 days"]').removeClass('amChartsButton').addClass('amChartsButtonSelected');
                } else if (timeSpan == '7DD') {
                    $('input[value="1 week"]').removeClass('amChartsButton').addClass('amChartsButtonSelected');
                } else {
                    $('input[value="MAX"]').removeClass('amChartsButton').addClass('amChartsButtonSelected');
                }
            }
        }
    });

};

// Handle clicking the buttons
function buttonClickHandler(data) {

    // Don't double load the file the first time we force a default
    if (defaultLoad === true) {
        if (typeof data.count !== 'undefined') {
            AmCharts.loadJSON(data.count + data.predefinedPeriod, true);
        } else {
            AmCharts.loadJSON(data.predefinedPeriod, true);
        }
    } else {
        defaultLoad = true;
    }
}

// Function to load the data
AmCharts.ready(function () {

    // load the data
    AmCharts.loadJSON('6hh', false);

    // Create the cart
    createStockChart();
});

// Function to create the chart
function createStockChart() {

    // Create a new chart
    chart = new AmCharts.AmStockChart();
    chart.pathToImages = "/assets/js/amcharts/images/";

    // As we have minutely data, we should set minPeriod to "mm"
    var categoryAxesSettings = new AmCharts.CategoryAxesSettings();
    categoryAxesSettings.minPeriod = "10mm";
    categoryAxesSettings.groupToPeriods = ["10mm", "30mm", "hh", "3hh", "6hh", "12hh", "DD"];
    chart.categoryAxesSettings = categoryAxesSettings;
    chart.dataDateFormat = "YYYY-MM-DD JJ:NN";

    // Set the dataset
    var dataSet = new AmCharts.DataSet();
    dataSet.color = "#7f8da9";
    dataSet.fieldMappings = [{
            fromField: "open",
            toField: "open"
    }, {
            fromField: "close",
            toField: "close"
    }, {
            fromField: "high",
            toField: "high"
    }, {
            fromField: "low",
            toField: "low"
    }, {
            fromField: "exchange_volume",
            toField: "exchange_volume"
    }];
    dataSet.dataProvider = chartData;
    dataSet.categoryField = "date";
    chart.dataSets = [dataSet]; // Set the cart data

    // Setup the first panel
    var stockPanel1 = new AmCharts.StockPanel();
    stockPanel1.showCategoryAxis = false;
    stockPanel1.title = "Price";
    stockPanel1.percentHeight = 70;
    stockPanel1.numberFormatter = {precision:8, decimalSeparator:'.', thousandsSeparator:','};

    // graph of first stock panel
    var graph1 = new AmCharts.StockGraph();
    graph1.valueField = "value";
    graph1.type = "candlestick";
    graph1.openField = "open";
    graph1.closeField = "close";
    graph1.highField = "high";
    graph1.lowField = "low";
    graph1.valueField = "close";
    graph1.lineColor = "#6bbf46";
    graph1.fillColors = "#6bbf46";
    graph1.negativeLineColor = "#db4c3c";
    graph1.negativeFillColors = "#db4c3c";
    graph1.fillAlphas = 1;
    graph1.balloonText = "open:<b>[[open]]</b><br>close:<b>[[close]]</b><br>low:<b>[[low]]</b><br>high:<b>[[high]]</b>";
    graph1.useDataSetColors = false;
    stockPanel1.addStockGraph(graph1);

    // create stock legend
    var stockLegend1 = new AmCharts.StockLegend();
    stockLegend1.valueTextRegular = " ";
    stockLegend1.markerType = "none";
    stockPanel1.stockLegend = stockLegend1;

    // second stock panel
    var stockPanel2 = new AmCharts.StockPanel();
    stockPanel2.title = "Volume";
    stockPanel2.percentHeight = 30;
    stockPanel2.numberFormatter = {precision:3, decimalSeparator:'.', thousandsSeparator:','};
    var graph2 = new AmCharts.StockGraph();
    graph2.valueField = "exchange_volume";
    graph2.type = "column";
    graph2.cornerRadiusTop = 2;
    graph2.fillAlphas = 1;
    graph2.periodValue = "Sum";
    stockPanel2.addStockGraph(graph2);

    // create stock legend
    var stockLegend2 = new AmCharts.StockLegend();
    stockLegend2.valueTextRegular = " ";
    stockLegend2.markerType = "none";
    stockPanel2.stockLegend = stockLegend2;

    // set panels to the chart
    chart.panels = [stockPanel1, stockPanel2];

    // Set up some additional settings
    var cursorSettings = new AmCharts.ChartCursorSettings();
    cursorSettings.valueBalloonsEnabled = true;
    cursorSettings.fullWidth = true;
    cursorSettings.cursorAlpha = 0.1;
    chart.chartCursorSettings = cursorSettings;

    // Set the period selector
    var periodSelector = new AmCharts.PeriodSelector();
    periodSelector.position = "top";
    periodSelector.dateFormat = "YYYY-MM-DD JJ:NN";
    periodSelector.inputFieldWidth = 150;
    periodSelector.inputFieldsEnabled = false;
    periodSelector.hideOutOfScopePeriods = false;
    periodSelector.periods = [{
        period: "hh",
        count: 6,
        label: "6 hours",
        selected: true
    }, {
        period: "DD",
        count: 1,
        label: "24 hours"
    }, {
        period: "DD",
        count: 3,
        label: "3 days"
    }, {
        period: "DD",
        count: 7,
        label: "1 week"
    }, {
        period: "MAX",
        label: "MAX"
    }];
    periodSelector.addListener('changed', function(period) { buttonClickHandler(period); });
    chart.periodSelector = periodSelector;

    var panelsSettings = new AmCharts.PanelsSettings();
    panelsSettings.usePrefixes = false;
    chart.panelsSettings = panelsSettings;

    var valueAxis = new AmCharts.ValueAxis();
    valueAxis.precision = 8;
    chart.valueAxis = valueAxis;

    // Hide the scrollbar
    chart.chartScrollbarSettings.enabled = false;

    // Write the chart
    chart.write('chartdiv_candlestick');
}
