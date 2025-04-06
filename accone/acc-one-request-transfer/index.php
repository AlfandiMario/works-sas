<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="../../../libs/vendor/css/google-fonts.css">
    <link rel="stylesheet" href="../../../libs/vendor/css/icomoon-fonts.css">
    <link rel="stylesheet" href="../../../libs/vendor/css/vuetify.min.css">
    <title>One</title>
</head>

<body>
    <div v-cloak id="app">
        <v-app id="smartApp">
            <one-navbar></one-navbar>
            <v-content style="background: #F5E8DF!important">
                <v-container fluid fill-height class="pl-1 pr-1 pt-2 pb-2">
                    <v-layout row wrap>
                        <v-flex xs12 class="left" fill-height pa-1>
                            <request-listing></request-listing>
                        </v-flex>
                    </v-layout>
                </v-container>
            </v-content>
            <one-footer></one-footer>
        </v-app>
    </div>

    <!-- Vendor -->
    <script src="../../../libs/vendor/moment.min.js"></script>
    <script src="../../../libs/vendor/numeral.min.js"></script>
    <script src="../../../libs/vendor/moment-locale-id.js"></script>
    <script src="../../../libs/vendor/lodash.js"></script>
    <script src="../../../libs/vendor/axios.min.js"></script>
    <script src="../../../libs/vendor/vue.js"></script>
    <script src="../../../libs/vendor/vuex.js"></script>
    <script src="../../../libs/vendor/vuetify.js"></script>
    <script src="../../../libs/vendor/httpVueLoader.js"></script>
    <script src="../../../libs/one_global.js"></script>

    <!-- App Script -->
    <script type="module">
        import {
            store
        } from './store.js';
        window.store = store;
        new Vue({
            store,
            el: "#app",
            methods: {},
            components: {
                'one-navbar': httpVueLoader('../../../apps/components/oneNavbarComponent.vue'),
                'one-footer': httpVueLoader('../../../apps/components/oneFooter.vue'),
                'request-listing': httpVueLoader('./components/requestListing.vue')
            }
        })
    </script>
    <style>
        [v-cloak] {
            display: none
        }
    </style>
</body>

</html>