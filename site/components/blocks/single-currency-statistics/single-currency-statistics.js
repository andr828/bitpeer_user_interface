
angular.module('exchangeApp').directive('fastCurrencyStatistics', function() {

    return {
        restrict: 'E',
        templateUrl: '/components/blocks/single-currency-statistics/single-currency-statistics.html',
        scope: {
            lastSelectedCurrencyPrice: "@",
            selectedCurrencyDayHighPrice: "@",
            selectedCurrencyDayLowPrice: "@",
            selectedCurrencyDayVolume: "@",
            baseCurrency: '@',
            translation: '='
        }
    }
})