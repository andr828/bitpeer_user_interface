angular.module('exchangeApp').directive('volumeBlock', ['$interpolate', function($interpolate) {

    return {
        restrict: 'E',
        templateUrl: '/components/blocks/volume/volume-block.html',
        scope: {
            coinDescriptionArray: '=',
            numberOfTrades: '=',
            translation: '='
        },
        link: function (scope) {
            scope.getRealValue = function (value, coin) {
                scope.coin = coin;
                return scope.$root.getRealValue(value, scope);
            }
        }
    }

}]);
