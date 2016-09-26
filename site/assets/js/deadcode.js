$scope.setCoinContextXXXX = function(obj) {
        //$('#chartdiv_candlestick').hide();
        $('.loading').show();
        var BTCMarketItem = "BTC_"; + obj.Code;
        $rootScope.selectedCurrency = obj.Code;
        current_Pricevolume = BTCMarketItem;
        generateChartData1(BTCMarketItem);
        //$('#chartdiv_candlestick').show();
    };

