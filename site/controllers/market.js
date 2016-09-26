angular.module('exchangeApp').controller('marketController', ['$scope', 'language', 'translationService', '$http', '$rootScope', '$interpolate', 'backendCalls', '$interval',
    function ($scope, language, translationService, $http, $rootScope, $interpolate, backend, $interval) {

        translationService.getTranslation(language, 'language_change', $scope);
        $scope.$on('language_change', function (event, data) {
            $scope.translation = data;
        });


        $scope.coinDescriptions = [];
        $scope.coinDescriptionArray = [];
        $scope.coinDescriptionIndices = [];

        $scope.enableAdvancedTrades = false;

        $scope.marketHistory = [];
        $scope.userCompletedTrades = [];
        $scope.userBuyOrders = [];
        $scope.userSellOrders = [];

        $scope.numberOfTrades = 0;
        $scope.intervalsSet = false;

        $scope.baseCurrency = "BTC";
        $scope.baseCurrencyName = "Bitcoin";
        $scope.selectedCurrency = "";
        $scope.selectedCurrencyName = "Litecoin";

        $scope.lastUpdatedTransactionCount = 0;
        $scope.lastUpdatedMarketStats = 0;
        $scope.lastUpdatedCurrencyDescriptions = 0;

        $scope.updateTransactionCount = function (trades) {
            $scope.numberOfTrades = trades;
            $scope.lastUpdatedTransactionCount = Date.now();
        };

        $scope.getCurrencyByCode = function (coinCode) {

            var coin = $scope.coinDescriptions[coinCode];
            return (coin != null ? coin : null);

        };

        $scope.getCurrencyName = function (coinCode) {
            var coin = $scope.getCurrencyByCode(coinCode);
            return (coin != null ? coin.Name : "");
        }

        function translate() {
            console.log('market');
            translationService.getTranslation(language).then(function (response) {
                $scope.translation = response.data;
            });
        };

        $rootScope.getRealValue = function (value, scope) {
            if (scope)
                return $interpolate(value)(scope);

            return $interpolate(value)($scope);
        }

        $scope.getDailyStats = function (coinCode) {
            var returnObject = new Object();
            var coin = $scope.coinDescriptions[coinCode];
            returnObject.statistics_day_high = (coin != null ? coin.statistics_day_high : 0);
            returnObject.statistics_day_low = (coin != null ? coin.statistics_day_low : 0);
            returnObject.statistics_day_volume = (coin != null ? coin.volume : 0);
            returnObject.latest_price = (coin != null ? coin.price : 0);
            return returnObject;
        }

        $scope.setSelectedCurrency = function (coinToSelect) {
            $scope.selectedCurrency = coinToSelect;
            $scope.selectedCurrencyName = $scope.getCurrencyName(coinToSelect);
            $scope.selectedCurrencyDailyStats = $scope.getDailyStats(coinToSelect);
        }

        $scope.$on('currencyChangeEvent', function (event, data) {
            $scope.setSelectedCurrency(data);
        });

        $scope.$on('orderEvent', function (event, data) {
            $scope.submitOrder(data);
        });

        $scope.$on('qrcode_request', function(event, requestData) {
            var uri = $scope.getQRCodeURI(requestData.coin, requestData.address, requestData.amount);
            $scope.$broadcast(requestData.event, uri);
        });

        $scope.selectedCurrencyDailyStats = $scope.getDailyStats($scope.selectedCurrency);

        $scope.getQRCodeURI = function (coinCode, address, amount) {
            var currency = $scope.getCurrencyByCode(coinCode);
            if ((currency) && (currency.QRCode.Supported)) {
                return currency.QRCode.URIScheme + ':' + address + '?' + currency.QRCode.AmountParameter + '=' + parseFloat(Math.round(amount * 10000000) / 10000000).toFixed(7);
            }
            return null;
        };

        $scope.submitOrder = function (data) {


            var history = new Object();
            history.price = data.order_price * 1;
            history.amount = data.order_amount * 1;
            history.total = history.price * history.amount;
            if (data.order_type == 'buy') {
                $scope.userBuyOrders.push(history);
            }
            if (data.order_type == 'sell') {
                $scope.userSellOrders.push(history);
            }

            console.log(data);
        }

        $scope.updateVolume = function (coinCode, volume) {
            var index = $scope.coinDescriptionIndices.indexOf(coinCode);
            if (index != -1) $scope.coinDescriptionArray[index].volume = volume;
        };

        $scope.updateCurrencyVolumes = function (update_currency_volumes) {
            for (var coin_code in update_currency_volumes) {
                $scope.updateVolume(coin_code, update_currency_volumes[coin_code])
            }
        };


        $scope.updateGlobalMarketStats = function (received_market_data) {
            $scope.lastUpdatedMarketStats = Date.now();
            for (var coin_code in received_market_data) {
                var index = $scope.coinDescriptionIndices.indexOf(coin_code);
                if (index != -1) {
                    $scope.coinDescriptionArray[index].volume = received_market_data[coin_code]["Volume"];
                    var price_pair = coin_code + '/' + $scope.baseCurrency;
                    if (received_market_data[coin_code]["Prices"][price_pair]) {
                        var cur_price = received_market_data[coin_code]["Prices"][price_pair]["Now"];
                        var old_price = received_market_data[coin_code]["Prices"][price_pair]["DayAgo"];
                        $scope.coinDescriptionArray[index].price = cur_price;
                        $scope.coinDescriptionArray[index].price_delta_24h = old_price;
                        $scope.coinDescriptionArray[index].statistics_day_low = received_market_data[coin_code]["Prices"][price_pair]["Lowest"];
                        $scope.coinDescriptionArray[index].statistics_day_high = received_market_data[coin_code]["Prices"][price_pair]["Highest"];
                        var change = ((old_price == cur_price) || (old_price == 0)) ? 0 : (cur_price / old_price) - 1;
                        $scope.coinDescriptionArray[index].change = change;
                    }
                }
            }
        }


        $scope.updateCurrencyDescriptions = function (received_coin_descriptions) {

            $scope.lastUpdatedCurrencyDescriptions = Date.now();

            var descriptions = [];

            for (var coin_index in received_coin_descriptions) {
                var coin = received_coin_descriptions[coin_index];

                // TODO: Remove this temp code
                coin.volume = null;
                coin.price = null;
                coin.price_delta_24h = null;
                coin.change = null;

                coin.statistics_day_high = null;
                coin.statistics_day_low = null;


                // We add volume data here
                // We add price data here

                descriptions[coin.Code] = coin;

            }

            $scope.coinDescriptions = descriptions;
            $scope.coinDescriptionIndices = Object.keys($scope.coinDescriptions).sort();
            $scope.coinDescriptionArray = [];
            for (var index in $scope.coinDescriptionIndices) {
                $scope.coinDescriptionArray.push($scope.coinDescriptions[$scope.coinDescriptionIndices[index]]);
            }
        };

        $scope.$on('volumes_update_event', function (event, data) {
            $scope.updateCurrencyVolumes(data);
        });

        $scope.$on('global_market_stats_event', function (event, data) {
            $scope.updateGlobalMarketStats(data);
        });

        $scope.$on('transaction_count_event', function (event, data) {
            $scope.updateTransactionCount(data);
        });

        $scope.$on('currency_descriptions_event', function (event, data) {
            $scope.updateCurrencyDescriptions(data);
            if ($scope.selectedCurrency.length == 0) $scope.setSelectedCurrency("LTC");
            backend.getAllMarketStats($scope);
            if (!$scope.intervalsSet) {
                $scope.intervalsSet = true;
                $interval(function () {

                    if ((Date.now() - $scope.lastUpdatedTransactionCount) > 15000) {
                        backend.getTransactionCount($scope);
                    }
                    if ((Date.now() - $scope.lastUpdatedMarketStats) > 15000) {
                        backend.getAllMarketStats($scope);
                    }
                    if ((Date.now() - $scope.lastUpdatedCurrencyDescriptions) > 60 * 60 * 1000) {
                        backend.getCurrencyDescriptions($scope);
                    }
                }, 15 * 1000);
                $interval(function () {
                    $scope.selectedCurrencyDailyStats = $scope.getDailyStats($scope.selectedCurrency);
                }, 1000)
            }
        });

        $rootScope.$on('maintenance_on', function (event, data) {
           if ((!data) && ($scope.lastUpdatedCurrencyDescriptions == 0))
           {
                $scope.updateAll();
           }
        });

        $scope.updateAll = function() {
            if (!$rootScope.maintenanceMode) {
                backend.getCurrencyDescriptions($scope);
                backend.getTransactionCount($scope);
            }
        }

        $scope.updateAll();

    }]);
