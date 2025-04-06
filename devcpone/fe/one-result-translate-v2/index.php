<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta http-equiv="X-UA-Compatible" content="ie=edge">
   <title>CPOne</title>
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
                  <v-flex xs3 class="left" fill-height pa-1>
                     <one-resume-left></one-resume-left>
                  </v-flex>
                  <v-flex xs9 class="right" fill-height pa-1>
                     <one-resume-right></one-resume-right>
                  </v-flex>
                  <!--   <v-flex xs6 class="right" fill-height pa-1> -->
                  <!-- komponen kanan -->
                  <!--    <one-fo-cashier-payment></one-fo-cashier-payment>-->
                  <!-- </v-flex> -->
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
   <!-- <script src="../../../libs/one_print_barcode.js"></script>
   <script src="../../../libs/one_print_robo.js"></script> -->
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
      new Vue({
         store,
         el: '#app',
         methods: {
            tab_selected: function(tab) {
               return this.$store.state.tab_selected == tab
            }
         },
         components: {
            'one-navbar': httpVueLoader('../../../apps/components/oneNavbarComponent.vue'),
            'one-footer': httpVueLoader('../../../apps/components/oneFooter.vue'),
            'one-resume-left': httpVueLoader('./components/oneResumeLeft.vue'),
            'one-resume-right': httpVueLoader('./components/oneResumeRight.vue'),
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