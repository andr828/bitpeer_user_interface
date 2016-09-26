var chartData = [];
var ChartData1= [{"BTC_1CR":[[{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193642,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193581,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193521,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193461,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193402,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193342,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193281,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193221,"usec":0}},{"price":"0.00032884","volume":"0.08070334","timestamp":{"sec":1465193161,"usec":0}}]]}];
var current_Pricevolume = "BTC_1CR";
    generateChartData1();

 function generateChartData(param) {
    var str_param = param;
    chartData = [];
    //createStockChart();

    // Show the loading div
    $('.loading').show();
    $('#chartdiv_candlestick').hide();

    // Load the JSON
    $.ajax({
        url: "http://cryptorates.info/?from=2016-03-02&currency=" + str_param,
        type: 'get',
        dataType: 'json',
        cache: false,
        async: true,
        success: function(rows) {

            // Hide the loading div
            $('.loading').hide();
            var iTem = rows[0][str_param][0];
            for (var i = 0; i < iTem.length; i++) {
                //var iTem = rows[0]['BTC_1CR'][0];
                var theDate = new Date(iTem[i].timestamp.sec * 1000);
                var dateString = theDate.toGMTString();


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
                  "volume":iTem[i].volume,
                  "date": dateString,
                  "price":iTem[i].price,
                  "open": open,
                  "close": close,
                  "high": high,
                  "low": low
                } );
              }

              var chart = AmCharts.makeChart( "chartdiv_candlestick", {
                "type": "stock",
                "theme": "light",
                "chartScrollbarSettings":{
                  "enabled":false
                },
                "dataSets": [ {
                  "fieldMappings": [ {
                    "fromField": "open",
                    "toField": "open"
                  }, {
                    "fromField": "close",
                    "toField": "close"
                  }, {
                    "fromField": "high",
                    "toField": "high"
                  }, {
                    "fromField": "low",
                    "toField": "low"
                  }, {
                    "fromField": "volume",
                    "toField": "volume"
                  }, {
                    "fromField": "price",
                    "toField": "price"
                  }],
                  "color": "#7f8da9",
                  "dataProvider": chartData,
                  "categoryField": "date"
                },{
                  "fieldMappings": [{
                    "fromField": "volume",
                    "toField": "volume"
                  }],
                  "color": "#fac314",
                  "dataProvider": chartData,
                  "categoryField": "date"
                }],
                "panels": [{
                    "title": "Price",
                    "showCategoryAxis": true,
                    "percentHeight": 70,
                    "valueAxes": [{
                      "id": "v1",
                      "dashLength": 5
                    }],
                    

                    "stockGraphs": [{
                      "type": "candlestick",
                      "id": "g2",
                      "openField": "price",
                      "closeField": "price",
                      "highField": "price",
                      "lowField": "price",
                      "valueField": "price",
                      "lineColor": "#7f8da9",
                      "fillColors": "#7f8da9",
                      "negativeLineColor": "#db4c3c",
                      "negativeFillColors": "#db4c3c",
                      "fillAlphas": 1,
                      "useDataSetColors": false,
                      "balloonText": "Open:<b>[[open]]</b><br>Low:<b>[[low]]</b><br>High:<b>[[high]]</b><br>Close:<b>[[close]]</b><br>",
                      "proCandlesticks": true
                    } ],

                    "stockLegend": {
                      "valueTextRegular": undefined
                    }
                  },
                  {
                    "title": "volume",
                    "percentHeight": 30,
                    "marginTop": 1,
                    "showCategoryAxis": true,
                    "valueAxes": [{
                      "dashLength": 5
                    }],
                    "stockGraphs": [{
                      "valueField": "volume",
                      "type": "column",
                      "showBalloon": false,
                      "fillAlphas": 1
                    }],

                    "stockLegend": {
                      "markerType": "none",
                      "markerSize": 0
                    }
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
              });

            //chart.chartScrollbarSettings.enabled = false;
            //chart.periodSelector.inputFieldsEnabled = false;
           }
   })
 };

function generateChartData1(param) {
  $('.loading').show();
  var str_param = "BTC_1CR";//param;
  for(var i in ChartData1[0][str_param][0]){
        var iTem = ChartData1[0][str_param][0];
        var theDate = new Date(iTem[i].timestamp.sec * 1000);
        var dateString = theDate.toGMTString();

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

        chartData[ i ] = ({
          "volume":iTem[i].volume,
          "date": dateString,
          "price":iTem[i].price,
          "open": open,
          "close": close,
          "high": high,
          "low": low
        });
  }


  var chart = AmCharts.makeChart( "chartdiv_candlestick", {
    "type": "stock",
    "theme": "light",
    "chartScrollbarSettings":{
      "enabled":false
    },
    "dataSets": [ {
      "fieldMappings": [ {
        "fromField": "open",
        "toField": "open"
      }, {
        "fromField": "close",
        "toField": "close"
      }, {
        "fromField": "high",
        "toField": "high"
      }, {
        "fromField": "low",
        "toField": "low"
      }, {
        "fromField": "volume",
        "toField": "volume"
      }, {
        "fromField": "price",
        "toField": "price"
      }],
      "color": "#7f8da9",
      "dataProvider": chartData,
      "categoryField": "date"
    },{
      "fieldMappings": [{
        "fromField": "volume",
        "toField": "volume"
      }],
      "color": "#fac314",
      "dataProvider": chartData,
      "categoryField": "date"
    }],
    "panels": [{
        "title": "Price",
        "showCategoryAxis": true,
        "percentHeight": 70,
        "valueAxes": [{
          "id": "v1",
          "dashLength": 5
        }],
        

        "stockGraphs": [{
          "type": "candlestick",
          "id": "g2",
          "openField": "price",
          "closeField": "price",
          "highField": "price",
          "lowField": "price",
          "valueField": "price",
          "lineColor": "#7f8da9",
          "fillColors": "#7f8da9",
          "negativeLineColor": "#db4c3c",
          "negativeFillColors": "#db4c3c",
          "fillAlphas": 1,
          "useDataSetColors": false,
          "balloonText": "Open:<b>[[open]]</b><br>Low:<b>[[low]]</b><br>High:<b>[[high]]</b><br>Close:<b>[[close]]</b><br>",
          "proCandlesticks": true
        } ],

        "stockLegend": {
          "valueTextRegular": undefined
        }
      },
      {
        "title": "volume",
        "percentHeight": 30,
        "marginTop": 1,
        "showCategoryAxis": true,
        "valueAxes": [{
          "dashLength": 5
        }],
        "stockGraphs": [{
          "valueField": "volume",
          "type": "column",
          "showBalloon": false,
          "fillAlphas": 1
        }],

        "stockLegend": {
          "markerType": "none",
          "markerSize": 0
        }
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
  });

  $('.loading').hide();
  $('#chartdiv_candlestick').show();
}


