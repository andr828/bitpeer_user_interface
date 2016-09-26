angular.module('exchangeApp').service('backendCalls', ['$http', function ($http) {

    this.getVolumeUpdate = function ($scope) {
        $http({
            cache: false,
            url: 'https://backend.bitpeerdemo.com/api/2016-09-01/get-all-volumes',
            method: "GET"
        }).then(function (response) {
                $scope.$emit('maintenance_on', false);
                $scope.$emit('volumes_update_event', response.data);
            }, function () {
            }
        );
    };

    this.getCurrencyDescriptions = function ($scope) {
        $http({
            cache: false,
            url: 'https://backend.bitpeerdemo.com/api/2016-09-01/get-currency-descriptions',
            method: "GET"
        }).then(function (response) {
                $scope.$emit('maintenance_on', false);
                $scope.$emit('currency_descriptions_event', response.data);
            }, function () {
            }
        );
    };

    this.logThis = function(data) {
      console.log(data);
    };

    this.getTransactionCount = function ($scope) {
        $http({
            cache: false,
            url: 'https://backend.bitpeerdemo.com/api/2016-09-01/get-transactions-count',
            method: "GET"
        }).then(function (response) {
                $scope.$emit('maintenance_on', false);
                $scope.$emit('transaction_count_event', response.data.Result);
            }, function () {
            }
        );
    };


    this.checkMaintenance = function ($scope) {
        $http({
            cache: false,
            url: 'https://backend.bitpeerdemo.com/api/internal/2016-09-01/is-under-maintenance',
            method: "GET"
        }).then(function (response) {
                $scope.$emit('maintenance_on', response.Result)
            }, function () {
                $scope.$emit('maintenance_on', true)
            }
        );
    }

    this.getDepositAddress = function ($scope, coinCode, eventSuffix) {
        $http({
            cache: false,
            url: 'https://backend.bitpeerdemo.com/api/2016-09-01/get-deposit-address/' + coinCode,
            method: "GET"
        }).then(function (response) {
                $scope.$emit('deposit_address_event_' + eventSuffix, response.data);
                $scope.$emit('maintenance_on', false);
            }, function () {
            }
        );
    };

    this.getPostToken = function($scope, eventName) {
        $http({
            cache: false,
            url: 'https://backend.bitpeerdemo.com/api/2016-09-01/get-post-token',
            method: "GET"
        }).then(function (response) {
                console.log(response);
                $scope.$emit(eventName, response.data);
                $scope.$emit('maintenance_on', false);
            }, function () {
            }
        );
    };

    this.checkFunds = function($scope, coinCode, address, token, amount, minimumConfirmation, eventName) {
        var urlToCall = 'https://backend.bitpeerdemo.com/api/2016-09-01/check-funds/' + coinCode + '/' + address + '/' + encodeURIComponent(token) + '/' + encodeURIComponent(roundOff(amount)) + '/' + minimumConfirmation;
        console.log(urlToCall);
        $http({
            cache: false,
            url: urlToCall,
            method: "GET"
        }).then(function (response) {
                $scope.$emit(eventName, {'Confirmed': response.data.Result, 'Coin': coinCode, 'Address': address, 'Token': token, 'Amount': amount});
                $scope.$emit('maintenance_on', false);
            }, function () {
            }
        );
    }

    this.getAllMarketStats = function ($scope) {
        $http({
            cache: false,
            url: 'https://backend.bitpeerdemo.com/api/2016-09-01/get-current-market-stats/all',
            method: "GET"
        }).then(function (response) {
                $scope.$emit('maintenance_on', false);
                $scope.$emit('global_market_stats_event', response.data);
            }, function () {
            }
        );
    };

    this.sendOrder = function ($scope, event, transactionBill) {
        $http({
            method: "POST",
            url: 'https://backend.bitpeerdemo.com/api/2016-09-01/api/place-order',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            transformRequest: function(obj) {
                var str = [];
                for(var p in obj)
                    str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
                return str.join("&");
            },
            data: transactionBill
        }).then(function (response) {
                $scope.$emit('maintenance_on', false);
                transactionBill.orderid = response.data.Result;
                $scope.$emit(event, transactionBill);
            }, function () {
            }
        );
    };


}]);
