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

<body v-on:key="doSomething">
   <div v-cloak id="app">
      <v-app id="smartApp">
         <one-navbar></one-navbar>
         <v-content style="background:#F5E8DF!important">
            <v-container fluid fill-height class="pl-1 pr-1 pt-2 pb-2">
               <v-layout row wrap>
                  <v-flex xs12>
                     <one-sample-call-so-filter></one-sample-call-so-filter>
                  </v-flex>
                  <v-flex xs6 class="left" fill-height pa-1>
                     <one-sample-call-so-list></one-sample-call-so-list>
                  </v-flex>
                  <v-flex xs6 class="right" fill-height pa-1>
                     <one-sample-call-so-info></one-sample-call-so-info>
                     <one-sample-call-so-detail></one-sample-call-so-detail>
                     <one-order-info></one-order-info>
                  </v-flex>
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
   <script src="../../../libs/one_print_barcode.js"></script>
   <script src="../../../libs/vendor/socket.io.js"></script>
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

      var socketIoUrl = "http://" + window.location.host + ":9099/";
      // var socketIoUrl = "http://" + window.location.host + ":9090/";

      import {
         store
      } from './store.js<?php echo $ts ?>';

      //for testing
      window.store = store;
      let ts = "?ts=" + moment().format("YYYYMMDDHHmmss");
      new Vue({
         data: {
            socket: io.connect(socketIoUrl, {
               forceNew: false
            })
         },
         store,
         el: '#app',
         mounted() {
            document.addEventListener('keypress', logKeyboard)
            window.key_enter = ''
            async function logKeyboard(e) {

               window.key_enter = (window.key_enter + e.key).toUpperCase()
               if (e.key == 'Enter') {
                  if (window.key_enter.includes('STF')) {
                     var prm = {
                        search: window.key_enter.replace('ENTER', '').trim()
                     }
                     await store.dispatch("samplecall/search_staff", prm)
                  } else {
                     var prm = {
                        search: window.key_enter.replace('ENTER', '').trim()
                     }
                     store.dispatch("samplecall/search_patient_enter", prm)
                  }
                  window.key_enter = ''
               }

            }
            let self = this;
            this.socket.on("notification", function(msg) {
               switch (msg.type) {
                  case "specimen-col-process":
                  case "fo-verification-y":
                     self.$store.dispatch("samplecall/search", {
                        xdate: self.$store.state.samplecall.start_date,
                        name: self.$store.state.samplecall.name,
                        nolab: self.$store.state.samplecall.nolab,
                        stationid: self.$store.state.samplecall.selected_station.id,
                        statusid: self.$store.state.samplecall.selected_status.id,
                        companyid: self.$store.state.samplecall.selected_company.id,
                        lastid: self.$store.state.samplecall.last_id
                     });
                     break;
               }
            });

         },
         methods: {
            tab_selected: function(tab) {
               return this.$store.state.tab_selected == tab
            }
         },
         components: {
            'one-navbar': httpVueLoader('../../../apps/components/oneNavbarComponent.vue' + ts),
            'one-footer': httpVueLoader('../../../apps/components/oneFooter.vue' + ts),
            'one-sample-call-so-list': httpVueLoader('./components/oneSampleCallSOList.vue' + ts),
            'one-sample-call-so-filter': httpVueLoader('./components/oneSampleCallSOFilter.vue' + ts),
            'one-sample-call-so-info': httpVueLoader('./components/oneSampleCallSOInfo.vue' + ts),
            'one-sample-call-so-detail': httpVueLoader('./components/oneSampleCallSODetail.vue' + ts),
            'one-order-info': httpVueLoader('./components/oneOrderInfo.vue' + ts)
         }
      })
   </script>
   <style>
      [v-cloak] {
         display: none
      }

      .left {}

      .right {}
   </style>
</body>

</html>