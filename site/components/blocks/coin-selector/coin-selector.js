angular.module('exchangeApp').directive('coinSelector', function () {

    return {
        restrict: 'E',
        templateUrl: '/components/blocks/coin-selector/coin-selector.html',
        scope: {
            coinDescriptionArray: '=',
            selectedCurrency: '@',
            baseCurrency: '@',
            translation: '='
        },
        link: function (scope) {
            scope.setSelectedCurrencyFunction = function (coin) {
                scope.$emit('currencyChangeEvent', coin);
            };
        }
    }

});