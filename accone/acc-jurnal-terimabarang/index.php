<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta http-equiv="X-UA-Compatible" content="ie=edge">
   <title>One</title>
   <link rel="stylesheet" href="../../../libs/vendor/css/google-fonts.css">
   <link rel="stylesheet" href="../../../libs/vendor/css/icomoon-fonts.css">
   <link rel="stylesheet" href="../../../libs/vendor/css/vuetify.min.css">
</head>

<body>
   <div v-cloak id="app">
      <v-app id="smartApp">
         <one-navbar></one-navbar>
         <v-content style="background:#F5E8DF!important">
            <v-container fluid fill-height class="pl-1 pr-1 pt-2 pb-2">
               <v-layout row wrap>
                  <one-terimabarang></one-terimabarang>
               </v-layout>
            </v-container>
         </v-content>
         <one-footer> </one-footer>
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
   <script src="../../../libs/vendor/sheetjs-master/xlsx.full.min.js"></script>

   <!-- App Script -->
   <?php
   $ts = "?ts=" . Date("ymdhis");
   ?>
   <script type="module">
      window.calculate_age = function(inp_dob) {
         var now = moment(new Date())
         var dob = moment(new Date(inp_dob))
         var year = now.diff(dob, 'years')
         dob.add(year, 'years')
         var month = now.diff(dob, 'months')
         dob.add(month, 'months')
         var day = now.diff(dob, 'days')
         if (isNaN(year)) return ''
         return `${year} tahun ${month} bulan ${day} hari`
      }

      import {
         store
      } from './store.js<?php echo $ts ?>';
      //for testing
      window.store = store;
      var ts = "?ts=" + Date.now();

      new Vue({
         store,
         el: '#app',
         methods: {
            tab_selected: function(tab) {
               return this.$store.state.tab_selected == tab
            }
         },

         mounted: function() {

            var url_string = window.location.href
            var url = new URL(url_string);
            var code = url.searchParams.get("code");

            if (code != null) {
               this.$store.commit('order/update_setupcode', code)
               this.$store.commit("patient/update_patients", [])
               this.$store.commit("patient/update_total_patients", 0)
               this.$store.commit("patient/update_current_page", 1)
               this.$store.dispatch("patient/generatesetup", {
                  setupcode: code
               })
            }
            if (!one_token())
               location.replace('/one-ui/test/vuex/one-login')
         },
         components: {
            'one-navbar': httpVueLoader('../../../apps/components/oneNavbarComponent.vue'),
            'one-footer': httpVueLoader('../../../apps/components/oneFooter.vue'),
            'one-terimabarang': httpVueLoader('./components/terimaBarangList.vue'),
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