angular.module('exchangeApp').directive('orderForm', ['backendCalls', '$timeout', function (backend, $timeout) {

    return {
        restrict: "E",
        replace: true,
        scope: {
            orderType: '@',
            coinDescriptionArray: '=',
            enableAdvancedTrades: '=',
            fromCurrency: '=fromCurrency',
            toCurrency: '=toCurrency',
            submitOrderFunction: '&',
            translation: '='
        },

        link: function ($scope, element, attrs) {

            $scope.getPostedOrderEvent = function () {
                return 'posted_order_' + $scope.$id;
            };

            $scope.$on($scope.getPostedOrderEvent(), function(event, data) {
                $scope.$emit('orderEvent', data);
            });

            $scope.buildOrder = function (postToken) {

                var order = new Object();
                order.posttoken = postToken;
                order.addresstoken = $scope.receivingToken;
                order.sysaddress = $scope.receivingAddress;

                order.cointosell = $scope.getDepositCurrencyCode();
                order.cointobuy = $scope.getReceptionCurrencyCode();
                order.ismarket = $scope.isMarketOrder;
                if (!order.ismarket) {
                    order.price = roundOff($scope.getTotal());
                }
                order.amount = roundOff($scope.orderAmount);
                order.type = $scope.orderType;
                order.sellreturnaddress = $scope.getDepositAddress();
                order.buyreturnaddress = $scope.getReceptionAddress();

                var date = new Date();
                date.setDate(date.getDate() + $scope.expiry);
                order.expiry = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();

                order.order_type = order.type;
                order.from_currency = order.cointosell;
                order.to_currency = $scope.toCurrency;
                order.from_returnAddress = $scope.fromreturnAddress;
                order.to_returnAddress = $scope.toreturnAddress;
                order.order_amount = $scope.orderAmount;
                order.market_trade = order.ismarket;
                order.order_price = order.price;
                backend.sendOrder($scope, $scope.getPostedOrderEvent(), order);

            };
            $scope.infunding = false;
            $scope.getRealValue = function (value) {
                return $scope.$root.getRealValue(value, $scope);
            };

            $scope.getPostTokenEvent = function () {
                return 'post_token_' + $scope.$id;
            };


            $scope.$on($scope.getPostTokenEvent(), function(event, token) {
                $scope.buildOrder(token);
                $scope.watchForFunding();
            });

            $scope.$on('qrcode_response_event_' + $scope.$id, function (event, data) {
                $scope.addressURI = data;
                backend.getPostToken($scope, $scope.getPostTokenEvent());
            });

            $scope.$on('deposit_address_event_' + $scope.$id, function (event, data) {
                $scope.receivingAddress = data.address;
                $scope.receivingToken = data.token;
                var qrcodeRequest = new Object();
                qrcodeRequest.event = 'qrcode_response_event_' + $scope.$id;
                qrcodeRequest.address = data.address;
                qrcodeRequest.coin = $scope.getDepositCurrencyCode();
                qrcodeRequest.amount = $scope.getTotal();
                $scope.$emit('qrcode_request', qrcodeRequest);
            });

            $scope.requestAddress = function (coinCode) {
                backend.getDepositAddress($scope, coinCode, $scope.$id);
            };

            $scope.getSubTotal = function () {
                return ($scope.orderAmount * 1) * ($scope.orderPrice * 1);
            };
            $scope.getTotal = function () {
                var value= $scope.getSubTotal() + $scope.getCommission();
                return roundOff(value);
            };
            $scope.getCommission = function () {
                return $scope.getSubTotal() * 0.0015;
            };
            $scope.resetAddress = function () {
                $scope.receivingAddress = "";
            };
            $scope.canProceedToFunding = function () {
                return $scope.orderAmount > 0;
            };
            $scope.getDepositCurrencyCode = function () {
                if ($scope.orderType == "buy")
                    return $scope.toCurrency;
                else
                    return $scope.fromCurrency;
            };
            $scope.getDepositAddress = function () {
                if ($scope.orderType == "buy")
                    return $scope.toreturnAddress;
                else
                    return $scope.fromreturnAddress;
            };
            $scope.getReceptionCurrencyCode = function () {
                if ($scope.orderType == "sell")
                    return $scope.toCurrency;
                else
                    return $scope.fromCurrency;
            };
            $scope.getReceptionAddress = function () {
                if ($scope.orderType == "sell")
                    return $scope.toreturnAddress;
                else
                    return $scope.fromreturnAddress;
            };


            $scope.getPaymentConfirmationEvent = function () {
                return 'funding_confirmation_' + $scope.$id;
            };


            $scope.$on($scope.getPaymentConfirmationEvent(), function (event, data) {
                if (data.Confirmed) {
                    $scope.isConfirmed = true;
                    $timeout(function() {
                        $scope.resetForm();
                    }, 4000);
                }
            });

            $scope.watchForFunding = function () {

                backend.checkFunds($scope, $scope.getDepositCurrencyCode(), $scope.receivingAddress, $scope.receivingToken, $scope.getTotal(), 0, $scope.getPaymentConfirmationEvent());
                $timeout(function () {
                    if ((!$scope.isConfirmed) && ($scope.infunding)) {
                        $scope.watchForFunding();
                    }
                }, 250);

            };

            $scope.resetForm = function() {
                $scope.infunding=false;
                $scope.receivingAddress = '';
                $scope.receivingToken = '';
                $scope.addressURI='';
                $scope.isConfirmed=false;
            }

            $scope.addressChange = function (obj) {
                /*var BTCvalidator = WAValidator.validate($scope.toreturnAddress, 'BTC');
                 if (!BTCvalidator) {
                 alert("Please Enter BTC Return Address");
                 return false;
                 }*/
                $scope.resetAddress();
                $scope.infunding = true;
                $scope.requestAddress(obj);
            }
        },
        templateUrl: "/components/wizards/order-form/order-form.html"
    };

}]);
