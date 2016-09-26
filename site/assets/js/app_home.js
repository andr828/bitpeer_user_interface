// Enable HTML5 Mode: http://stackoverflow.com/questions/19211576/enabling-html-5-mode-in-angularjs-1-2

angular.module('exchangeApp', [
    'ngSanitize',
    'ngResource',
    'ngRoute'
]);


function coinOrder(typeOfOrder, sourceCurrency, destinationCurrency, amountToTrade, pricePerUnit, destinationAddress, destinationToken, sourceAddress) {
    this.typeOfOrder = typeOfOrder;
    this.sourceCurrency = sourceCurrency;
    this.destinationCurrency = destinationCurrency;
    this.amountToTrade = amountToTrade;
    this.pricePerUnit = pricePerUnit;
    this.destinationAddress = destinationAddress;
    this.destinationToken = destinationToken;
    this.sourceAddress = sourceAddress;
}

Date.prototype.addDays = function(days)
{
    var dat = new Date(this.valueOf());
    dat.setDate(dat.getDate() + days);
    return dat;
};

roundOff = function(amount)
{
    return parseFloat(Math.round(amount * 10000000000) / 10000000000).toFixed(10)
}

angular.module('exchangeApp').config(['$routeProvider', '$locationProvider', '$httpProvider', function ($routeProvider, $locationProvider, $httpProvider) {

    //$locationProvider.html5Mode(true);

    $routeProvider

        .when('/', {

            templateUrl: 'pages/home.html',
            controller: 'translationController'
        })

        .when('/market', {

            templateUrl: 'pages/market.html',
            controller: 'marketController'
        })

        .when('/about', {

            templateUrl: 'pages/about.html',
            controller: 'translationController'
        })

        .when('/fees', {

            templateUrl: 'pages/fees.html',
            controller: 'translationController'
        })

        .when('/privacy', {

            templateUrl: 'pages/privacy.html',
            controller: 'translationController'
        })

        .when('/terms', {

            templateUrl: 'pages/terms.html',
            controller: 'translationController'
        })

        .when('/security', {

            templateUrl: 'pages/security.html',
            controller: 'translationController'
        })

        .when('/api', {

            templateUrl: 'pages/api.html',
            controller: 'translationController'
        })

    $httpProvider.defaults.useXDomain = true;
    delete $httpProvider.defaults.headers.common['X-Requested-With'];

}]);

angular.module('exchangeApp').run(function($rootScope, $interval, backendCalls) {

    $rootScope.prodName = "BitPeer";
    $rootScope.maintenanceMode = false;
    $rootScope.lastContacted = 0;

    $rootScope.$on('maintenance_on', function (event, data) {
        $rootScope.maintenanceMode = data;
        $rootScope.lastContacted = Date.now();
    });

    $interval(function () {
        if ((Date.now() - $rootScope.lastContacted) > 15000) {
            backendCalls.checkMaintenance($rootScope);
        }
        if ((Date.now() - $rootScope.lastContacted) > 30000) {
            $rootScope.maintenanceMode = true;
        }}, 15 * 1000);

    backendCalls.checkMaintenance($rootScope);

});


angular.module('exchangeApp').constant('prodName', 'Bitpeer');
var countryCode = '';
angular.module('exchangeApp').constant('prodName', 'Bitpeer');
angular.module('exchangeApp').value('language', 'en');
if (countryCode) {
    angular.module('exchangeApp').value('language', countryCode.toLowerCase());
} else {
    angular.module('exchangeApp').value('language', getBrowserLang());

    function getBrowserLang() {
        try {
            var langu = navigator.language || navigator.userLanguage;
            langu = langu.slice(0, 2);
            return langu.toLowerCase();

        } catch (e) {
            return 'en';
        }

    }
}


// From http://stackoverflow.com/questions/11561350/javascript-how-to-get-keys-of-associative-array-to-array-variable
function getKeys(obj, filter) {
    var name,
        result = [];

    for (name in obj) {
        if ((!filter || filter.test(name)) && obj.hasOwnProperty(name)) {
            result[result.length] = name;
        }
    }
    return result;
}

angular.module('exchangeApp').controller('sidebar-controller', ['$rootScope', '$scope', '$http', function ($rootScope, $scope, $http) {

}]);


angular.module('exchangeApp').controller('homePageController', ['$scope', 'language', 'translationService', function ($scope, language, translationService) {

    $scope.btcVolume = '0.000';
    $scope.ltcVolume = '0.000';
    $scope.trades = '0.000';

}]);


function inArray(needle, haystack) {
    var count = haystack.length;
    for (var i = 0; i < count; i++) {
        if (haystack[i] === needle) {
            return true;
        }
    }
    return false;
}

angular.module('exchangeApp').controller('market-page-controller', ['$scope', function ($scope) {

    var feesArray = '{"buyer_fee":12,"seller_fee":34}'; // TODO: replace with real data
    $scope.fees = (feesArray == {} ? null : JSON.parse(feesArray));

    /*    $scope.IsNull = function(value) {
     if (value == null || value == undefined || value == "")
     return true;
     else
     return false;
     }*/


    $scope.Click_CancelOrder = function (obj) {
        var orderId = obj.order_id;
        var type = (obj.type > 0 ? "SELL" : "BUY");
        new Messi('Are you sure you would like to cancel this order?<br /><br />WARNING: Your order is currently live so there is a possibility that it may be matched before it can be cancelled.', {
            title: 'Please confirm',
            titleClass: 'info',
            buttons: [{
                id: 0,
                label: 'Yes',
                val: 'Y',
                btnClass: 'btn-success'
            }, {
                id: 1,
                label: 'No',
                val: 'N',
                btnClass: 'btn-danger'
            }],
            modal: true,
            callback: function (val) {
                if (val == 'Y') {
                    var cust = 'orderId=' + orderId + '&market=' + $scope.market + '&csrf_token_market' + $scope.market + '=' + $scope.token;
                    Get._CancelOrder('/action/cancelOrder', cust).then(function (response) {
                        var data = response.data;
                        if (data.response == 'success') {

                            // Handle the success box
                            $('#spanSuccessBox').html(data.reason);
                            $('#sucessBox').show();
                            $('#failBox').hide();
                            clearTimeout(timeout);
                            timeout = setTimeout(function () {
                                $('#sucessBox').hide()
                            }, 10000);

                        } else {

                            // Show a fail box
                            $('#spanFailBox').html(data.reason);
                            $('#failBox').show();
                            $('#sucessBox').hide();
                            $('html, body').animate({
                                scrollTop: $("#failBox").offset().top - 95
                            }, 1000);
                            clearTimeout(timeout);
                            timeout = setTimeout(function () {
                                $('#failBox').hide()
                            }, 10000);
                        }

                    });
                }
            }
        });
    };

    $scope.buyer_fee = 0.15;
    $scope.seller_fee = 0.15;

    $scope.Fn_loadPriceVolum = function () {
        generateChartData1(current_Pricevolume);
    };
    $scope.Fn_loadorderdepth = function () {
        //generateChartData1();
    };


    //.............Buy Model

    $scope.BuyModel = {
        Amount: "0.00",
        Priceperbc: '0.00000001',
        total: '0.00000000',
        fee: 0.15,
        NetTotal: 0
    };

    $scope.SellModel = {
        Amount: "0.00",
        Priceperbc: '0.00000001',
        total: '0.00000000',
        fee: 0.15,
        NetTotal: 0
    };

    $scope.loadControl = function () {
        $("#buyForm").validate({
            ignore: [],
            rules: {
                amount: {
                    min: 0,
                    required: true
                },
                price: {
                    required: true,
                    min: 0.00000001
                },
                buyNetTotal: {
                    required: true,
                    min: 0.00010000,
                    max: function () {
                        return parseFloat($('.exchangeBalance').text());
                    }
                }
            },
            messages: {
                amount: {
                    min: "Amount must be greater than zero.",
                    required: "Please enter an amount."
                },
                price: {
                    min: "Price must be greater than zero.",
                    required: "Please enter a price."
                },
                buyNetTotal: {
                    min: "Total must be equal to or greater than 0.00010000.",
                    max: "Net total must be equal to or less than your available balance."
                }
            }
        });

        $("#sellForm").validate({
            rules: {
                amount: {
                    required: true,
                    min: 0,
                    max: function () {
                        return parseFloat($('.coinBalance').text());
                    }
                },
                price: {
                    required: true,
                    min: 0.00000001
                },
                sellNetTotal: {
                    required: true,
                    min: 0.00010000
                }
            },
            messages: {
                amount: {
                    min: "Amount must be greater than zero.",
                    max: "Amount must be equal to or less than your available balance.",
                    required: "Please enter an amount."
                },
                price: {
                    min: "Price must be greater than zero.",
                    required: "Please enter a price."
                },
                sellNetTotal: {
                    min: "Total must be equal to or greater than 0.00010000."
                }
            }
        });
    };

    $scope.loadControl();

    $scope.SubmitBuy = function (ObjClass) {
        var myClass = ObjClass;
        if (!$("#" + myClass).valid()) {
            new Messi('Are you sure you would like to place this order?<br /><br /><strong>Market:</strong> ' + coin + '/' + exchange + '<br /><strong>Amount:</strong> ' + $('#' + myClass).find('input[name="amount"]').val() + ' ' + coin + '<br /><strong>Price:</strong> ' + $('#' + myClass).find('input[name="price"]').val() + ' ' + exchange + '<br /><strong>Net Total:</strong> ' + $('#' + myClass).find('.netTotal').val() + ' ' + exchange + '<br /><br />Please ensure the order numbers are correct before confirming. We will be unable to refund/cancel any live order that has been matched.', {
                title: 'Please confirm',
                titleClass: 'info',
                buttons: [{
                    id: 0,
                    label: 'Yes',
                    val: 'Y',
                    btnClass: 'btn-success'
                }, {
                    id: 1,
                    label: 'No',
                    val: 'N',
                    btnClass: 'btn-danger'
                }],
                modal: true,
                callback: function (val) {
                    // Yes we are a go
                    if (val == 'Y') {
                        var url = "/action/addOrder";
                        var cust = $("#" + myClass).serialize();
                        Get._post_withdraw_Submit(url, cust)
                            .then(function (data) {
                                data = data.data;
                                if (data.response == 'success') {
                                    // Handle the success box
                                    $('#spanSuccessBox').html(data.reason);
                                    $('#sucessBox').show();
                                    $('#failBox').hide();
                                    clearTimeout(timeout);
                                    timeout = setTimeout(function () {
                                        $('#sucessBox').hide()
                                    }, 10000);

                                    // Update the balance
                                    if (myClass == "buyForm") {
                                        $('.exchangeBalance').text(($('.exchangeBalance').text() - data.removeFromBalance).toFixed(8));
                                    } else {
                                        $('.coinBalance').text(($('.coinBalance').text() - data.removeFromBalance).toFixed(8));
                                    }

                                    // Start worker that checks for user trades
                                    clearTimeout(orderWorkerRunning);
                                    userOrderWorker();

                                } else {

                                    // Show a fail box!
                                    $('#spanFailBox').html(data.reason);
                                    $('#failBox').show();
                                    $('#sucessBox').hide();
                                    // Scroll to it
                                    $('html, body').animate({
                                        scrollTop: $("#failBox").offset().top - 95
                                    }, 1000);
                                    clearTimeout(timeout);
                                    timeout = setTimeout(function () {
                                        $('#failBox').hide()
                                    }, 10000);
                                }
                            });


                        // Do the AJAX order subission
                        $.ajax({
                            type: 'POST',
                            url: '/action/addOrder',
                            data: $('.buyForm').serialize(),
                            success: function (data) {

                                // Check if we got a successful add order
                                if (data.response == 'success') {

                                    // Handle the success box
                                    $('#spanSuccessBox').html(data.reason);
                                    $('#sucessBox').show();
                                    $('#failBox').hide();
                                    clearTimeout(timeout);
                                    timeout = setTimeout(function () {
                                        $('#sucessBox').hide()
                                    }, 10000);

                                    // Update the balance
                                    if (myClass == "buyForm") {
                                        $('.exchangeBalance').text(($('.exchangeBalance').text() - data.removeFromBalance).toFixed(8));
                                    } else {
                                        $('.coinBalance').text(($('.coinBalance').text() - data.removeFromBalance).toFixed(8));
                                    }

                                    // Start worker that checks for user trades
                                    clearTimeout(orderWorkerRunning);
                                    userOrderWorker();

                                } else {

                                    // Show a fail box!
                                    $('#spanFailBox').html(data.reason);
                                    $('#failBox').show();
                                    $('#sucessBox').hide();
                                    // Scroll to it
                                    $('html, body').animate({
                                        scrollTop: $("#failBox").offset().top - 95
                                    }, 1000);
                                    clearTimeout(timeout);
                                    timeout = setTimeout(function () {
                                        $('#failBox').hide()
                                    }, 10000);
                                }
                            }
                        });
                    }

                }

            });
        }

    };

}]);


angular.module('exchangeApp').controller('translationController', ['$scope', 'language', 'translationService',
    function ($scope, language, translationService) {
        $scope.openMbActive=false;
        translationService.getTranslation(language, 'language_change', $scope);
        $scope.$on('language_change', function (event, data) {
            $scope.translation = data;
        });
        $scope.addClass = function (){
            $scope.openMbActive=!$scope.openMbActive;
        }
    }]);

angular.module('exchangeApp').controller('order-maker', ['$scope', function ($scope) {

    $scope.submitOrder = function () {
    }

    $scope.isMarketOrder = false;
    $scope.showAdvanced = false;
    $scope.infunding = false;

}]);

