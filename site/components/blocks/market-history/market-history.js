
angular.module('exchangeApp').directive('marketHistory', function() {

    return {
        restrict: 'E',
        templateUrl: '/components/blocks/market-history/market-history.html',
        scope: {
            marketData: '=',
            baseCurrency: '@',
            selectedCurrency: '@',
            translation: '='
        }
    }

})

