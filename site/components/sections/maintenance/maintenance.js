
angular.module('exchangeApp').directive('maintenance', function() {

    return {
        restrict: 'E',
        templateUrl: '/components/sections/maintenance/maintenance.html',
        scope: {
            underMaintenance: '=',
            translation: '='
        }
    }

});