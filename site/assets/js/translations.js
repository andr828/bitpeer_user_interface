
//***Translation lang*************************************//

angular.module('exchangeApp').service('translationService', ['$http', function ($http) {

    this.getTranslation = function(language, eventname, $scope)
    {
        $http({
            cache: true,
            url: '/translations/supported_languages.json',
            method: "GET",
            headers: {
                'Content-type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            var supportedLanguages = response.data;
            if (!inArray(language, supportedLanguages['supported'])) {
                language = supportedLanguages['default']
            }
            var languageFilePath = '/translations/translation_' + language + '.json';
            $http({
                cache: true,
                url: languageFilePath,
                method: "GET",
                headers: {
                    'Content-type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (response) {
                $scope.$emit(eventname, response.data);
            }, function () {
            });
        });

    }

}]);

