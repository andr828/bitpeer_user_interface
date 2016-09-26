angular.module('exchangeApp').directive('priceBlock', function () {

    return {
        restrict: 'E',
        templateUrl: '/components/blocks/price/price-block.html',
        scope: {
            baseCurrency: '@',
            coinDescriptionArray: '=',
            setSelectedCurrencyFunction: '&',
            translation: '='
        },
        link: function (scope) {
            scope.setSelectedCurrencyFunction = function (coin) {
                scope.$emit('currencyChangeEvent', coin);
            }

            scope.getRealValue = function (value) {
              return  scope.$root.getRealValue(value);
            }
        }
    }

})

angular.module('exchangeApp').directive('priceBlockLine', function () {

    return {
        restrict: 'E',
        templateUrl: '/components/blocks/price/price-block-line.html',
        scope: {
            coin: "=",
            translation: '='
        }
    }

})

