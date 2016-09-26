angular.module('exchangeApp').directive('userTrades', function() {

    return {
        restrict: 'E',
        templateUrl: '/components/blocks/user-trades/user-trades.html',
        scope: {
            marketData: '=',
            selectedCurrency: '@',
            translation: '='
        }
    }

});

