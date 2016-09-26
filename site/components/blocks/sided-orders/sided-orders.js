angular.module('exchangeApp').directive('sidedOrders', function() {

    return {
        restrict: 'E',
        templateUrl: '/components/blocks/sided-orders/sided-orders.html',
        scope: {
            marketData: '=',
            isSellTable: '=',
            baseCurrency: '@',
            selectedCurrency: '@',
            translation: '='
        }
    }

})

