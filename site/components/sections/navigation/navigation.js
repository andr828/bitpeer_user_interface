angular.module('exchangeApp').controller('navigationController', ['$scope', '$location', 'translationService', 'language', function ($scope, $location, translationService, language) {

    $scope.translate = function () {
        translationService.getTranslation($scope, language);
    };
    $scope.translate();

    /* $scope.market = ($location.absUrl().indexOf('market') != -1) ? 'active' : '';
    $scope.voting = ($location.absUrl().indexOf('voting') != -1) ? 'active' : '';
    $scope.fees = ($location.absUrl().indexOf('fees') != -1) ? 'active' : '';
    $scope.about = ($location.absUrl().indexOf('about') != -1) ? 'active' : '';
    $scope.security = ($location.absUrl().indexOf('security') != -1) ? 'active' : '';
    $scope.apiv1 = ($location.absUrl().indexOf('api') != -1) ? 'active' : '';
    $scope.terms = ($location.absUrl().indexOf('terms') != -1) ? 'active' : '';
    $scope.register = ($location.absUrl().indexOf('register') != -1) ? 'active' : '';
    $scope.login = ($location.absUrl().indexOf('login') != -1) ? 'active' : ''; */

}]);
