google.load("visualization", "1", {packages:["corechart"]});

var orderChartShowing = false;
var orderChartTable;
var orderChartUpdate;

var options = {
    hAxis: {title: 'Price',  titleTextStyle: {color: '#333'}},
    vAxis: {minValue: 0},
    legend: {position: 'none'},
    chartArea: {left: "3%", top: "10%", width: "95%", height: "80%"},
    colors: ['#27ae60', '#c0392b']
};

function drawChart() {

    // Show the loading div
    $('.loading').show();

    // Load the JSON
    $.ajax({
        url: "/action/getOrderDepth/" + market,
        type: 'get',
        dataType: 'json',
        cache: false,
        async: true,
        success: function(rows) {

            // Hide the loading div
            $('.loading').hide();

            orderChartData = new google.visualization.DataTable();
            orderChartData.addColumn('number', 'Price');
            orderChartData.addColumn('number', 'Bid');
            // A column for custom tooltip content
            orderChartData.addColumn({type: 'string', role: 'tooltip'});
            orderChartData.addColumn('number', 'Ask');
            // A column for custom tooltip content
            orderChartData.addColumn({type: 'string', role: 'tooltip'});

            for (var j = rows['buy'].length - 1; j >= 0; j--) {
                orderChartData.addRow([
                    parseFloat(rows['buy'][j]['price']),
                    rows['buy'][j]['total'],
                    rows['buy'][j]['total'] + ' ' + exchange + ' to get to ' + rows['buy'][j]['price'],
                    null,
                    null
                ]);
            }

            for (var i = 0; i < rows['sell'].length; i++) {
                orderChartData.addRow([
                    parseFloat(rows['sell'][i]['price']),
                    null,
                    null,
                    rows['sell'][i]['total'],
                    rows['sell'][i]['total'] + ' ' + exchange + ' to get to ' + rows['sell'][i]['price']
                ]);
            }

            graphLoad();

            orderChartUpdate = setTimeout(function() { drawChart(); }, 60000);

        }

    });

}

function graphLoad() {
    if (orderChartShowing) {
        var orderChart = new google.visualization.AreaChart(document.getElementById('chartdiv_orderdepth'));
        orderChart.draw(orderChartData, options);
    }
}

window.onresize = graphLoad;