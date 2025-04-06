<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>One</title>
  <link rel="stylesheet" href="../../../libs/vendor/css/google-fonts.css">
  <link rel="stylesheet" href="../../../libs/vendor/css/vuetify.min.css">
</head>

<body>
  <div v-cloak id="app">
    <v-app id="smartApp" >
      <one-navbar></one-navbar>
      <v-content class="blue lighten-5" >
         <v-container fluid fill-height class="pl-1 pr-1 pt-2 pb-2">
            <v-layout row wrap >
               <v-flex xs4 class="left" fill-height pa-1>
                    <!-- komponen kiri -->
                    <one-md-user-group-list></one-md-user-group-list>
                </v-flex>
                <v-flex xs8 class="right" fill-height pa-1>
                     <!-- komponen kanan -->
                    <one-md-user-list></one-md-user-list>
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
  <!-- App Script -->
<?php
$ts = "?ts=" . Date("ymdhis");
?>
<script type="module">
import { store } from './store.js<?php echo $ts ?>';
//for testing
window.store = store;
new Vue({
store,
   el: '#app',
   components: {
   'one-navbar': httpVueLoader('../../../apps/components/oneNavbarComponent.vue'),
        'one-footer': httpVueLoader('../../../apps/components/oneFooter.vue'),
        'one-md-user-group-list': httpVueLoader('./components/oneMdUserGroupList.vue'),
        'one-md-user-list' : httpVueLoader('./components/oneMdUserList.vue')
      }
    })
    </script>
  <style>
    [v-cloak] {
      display: none
    }
   .left {
   }
   .right {
   }
  </style>
</body>

</html>
