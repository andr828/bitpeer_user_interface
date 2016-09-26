
angular.module('exchangeApp').directive('sidebar', function() {

    return {
        restrict: 'E',
        templateUrl: '/components/sections/sidebar/sidebar.html',
		scope: {
            baseCurrency: '@',
            coinDescriptionArray: '=',
            productName: '@',
            numberOfTrades: '=',
            setSelectedCurrencyFunction: '&',
            translation: '='
        }
    }

})
