var chartData = [];
var chartData = [];
var chartData2 = [];
var ChartData1= [{"BTC_1CR":[[{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193642,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193581,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193521,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193461,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193402,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193342,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193281,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193221,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193161,"usec":0}}]]}];

generateChartData();

function generateChartData() {
  

  for(var i in ChartData1[0]['BTC_1CR'][0]){
        var iTem = ChartData1[0]['BTC_1CR'][0];

        var theDate = new Date(iTem[i].timestamp.sec * 1000);
        var dateString = theDate.toGMTString();
        

            chartData2.push(
                { 
                  volume:iTem[i].volume,
                  date: dateString,
                  price:iTem[i].price 
                }
              )
    }
  var firstDate = new Date();
  firstDate.setHours( 0, 0, 0, 0 );
  firstDate.setDate( firstDate.getDate() - 2000 );

  for ( var i = 0; i < 10; i++ ) {
    var newDate = new Date( firstDate );

    newDate.setDate( newDate.getDate() + i );

    var open = Math.round( Math.random() * ( 30 ) + 100 );
    var close = open + Math.round( Math.random() * ( 15 ) - Math.random() * 10 );

    var low;
    if ( open < close ) {
      low = open - Math.round( Math.random() * 5 );
    } else {
      low = close - Math.round( Math.random() * 5 );
    }

    var high;
    if ( open < close ) {
      high = close + Math.round( Math.random() * 5 );
    } else {
      high = open + Math.round( Math.random() * 5 );
    }

    var volume = Math.round( Math.random() * ( 1000 + i ) ) + 100 + i;
    var value = Math.round( Math.random() * ( 30 ) + 100 );

    chartData[ i ] = ( {
      "date": newDate,
      "open": open,
      "close": close,
      "high": high,
      "low": low,
      "volume": volume,
      "value": value
    } );
  }
}

var chart = AmCharts.makeChart( "chartdiv_candlestick", {
  "type": "stock",
  "theme": "light",

  "dataSets": [ {
    "fieldMappings": [ {
      "fromField": "volume",
      "toField": "volume"
    },{
      "fromField": "price",
      "toField": "price"
    },{
      "fromField": "date",
      "toField": "date"
    }],
    "color": "#7f8da9",
    "dataProvider": chartData2,
    "categoryField": "date"
  }],


  "panels": [{
      "title": "Price",
      "percentHeight": 60,
      "marginTop": 1,
      "showCategoryAxis": false,
      "stockGraphs": [ {
        "valueField": "price",
        "type": "serial",
        "fillAlphas": 1
      }]
    },{
      "title": "Volume",
      "percentHeight": 40,
      "marginTop": 5,
      "showCategoryAxis": true,
      "stockGraphs": [ {
        "valueField": "volume",
        "type": "column",
        "fillAlphas": 1
      }]
    }
  ],
  "periodSelector": {
    "position": "bottom",
    
    "inputFieldWidth" : 150,
    "inputFieldsEnabled" : false,
    "hideOutOfScopePeriods" : false,
    "periods":  [{
        period: "hh",
        count: 6,
        label: "6 hours"
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
        label: "MAX",
        selected: true
    }]
  },
  "export": {
    "enabled": true
  }
} );
chart.chartScrollbarSettings.enabled = false;
chart.periodSelector.inputFieldsEnabled = false;
