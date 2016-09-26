angular.module('exchangeApp').directive('currencyPairCallout', function() {

    return {
        restrict: 'E',
        templateUrl: '/components/callouts/currency-pair/currency-pair-callout.html',
        scope: {
            selectedCurrency: '@',
            baseCurrency: '@',
            selectedCurrencyName: '@',
            baseCurrencyName: '@',
            translation: '='
        }
    }

})