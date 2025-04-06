<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta http-equiv="X-UA-Compatible" content="ie=edge">
   <title>CPONE</title>
   <link rel="stylesheet" href="../../../libs/vendor/css/google-fonts.css">
   <link rel="stylesheet" href="../../../libs/vendor/css/icomoon-fonts.css">
   <link rel="stylesheet" href="../../../libs/vendor/css/vuetify.min.css">
</head>

<body>
   <div v-cloak id="app">
      <v-app id="smartApp">
         <one-navbar></one-navbar>
         <v-content style="background:#F5E8DF!important">
            <v-container fluid>
               <template>
                  <div id="e3" style="max-width: 620px; margin:auto">
                     <v-dialog v-model="dialogError" width="500">
                        <v-card>
                           <v-card-title class="headline red darken-4 text-white" primary-title>
                              ERROR !
                           </v-card-title>

                           <v-card-text>
                              {{ errorMsg }}
                           </v-card-text>

                           <v-divider></v-divider>

                           <v-card-actions>
                              <v-spacer></v-spacer>
                              <v-btn color="primary" flat @click="dialogError = false">
                                 Tutup
                              </v-btn>
                           </v-card-actions>
                        </v-card>
                     </v-dialog>
                     <v-toolbar class="primary" dark>
                        <v-btn icon>
                           <v-icon>colorize</v-icon>
                        </v-btn>
                        <v-toolbar-title>Sampling</v-toolbar-title>
                        <v-spacer></v-spacer>
                        <!--<v-btn @click="logout()" icon>
                           <v-icon color="black">remove_circle</v-icon>
                        </v-btn>-->
                     </v-toolbar>

                     <v-card>
                        <v-card-text>
                           <v-container fluid grid-list-lg>
                              <v-layout row>
                                 <v-flex xs12>
                                    <v-select class="mini-select ml-1" :items="stations" item-text="name" return-object style="font-size: 14px" v-model="selected_station" label="Station" outline hide-details></v-select>
                                 </v-flex>
                              </v-layout>
                              <v-layout row>
                                 <v-flex xs12>
                                    <v-select class="mini-select ml-1" :items="locations" item-text="locationName" return-object style="font-size: 14px" v-model="selected_location" label="Lokasi" outline hide-details></v-select>
                                 </v-flex>
                              </v-layout>
                              <v-layout row>
                                 <v-flex xs12>
                                    <v-divider></v-divider>
                                 </v-flex>
                              </v-layout>
                              <v-layout v-if="_.isEmpty(data_patient)" row wrap>
                                 <v-flex xs12>
                                    <v-card color="primary" class="white--text">
                                       <v-card-text>
                                          <v-img :src="imageSrc"></v-img>
                                       </v-card-text>
                                       <v-card-actions>
                                          <v-btn block @click="openscanner()">
                                             Scan QR Code Pasien (Check In)
                                          </v-btn>
                                       </v-card-actions>
                                    </v-card>
                                 </v-flex>
                              </v-layout>
                              <v-layout v-if="!_.isEmpty(data_patient) && !status_done" row>
                                 <v-flex xs12>
                                    <v-card color="primary" class="white--text">
                                       <v-layout row>
                                          <v-flex xs7>
                                             <v-card-title primary-title>
                                                <div>
                                                   <div class="headline mb-1">{{data_patient.labnumber}}</div>
                                                   <p class="mb-0">{{data_patient.patient_name}} <span v-if="data_patient.nip">/ {{data_patient.nip}}</span></p>
                                                   <!--<div class="caption">{{data_patient.order_date}}</div>-->
                                                   <p class="mb-0 caption font-weight-bold">{{data_patient.corporate_name}}</p>
                                                </div>
                                             </v-card-title>
                                          </v-flex>
                                          <v-flex class="align-self-end" xs5>
                                             <v-img class="mt-2 mr-2 text-xs-right" position="center right" :src="data_patient.photo" height="115px" contain></v-img>
                                          </v-flex>
                                       </v-layout>
                                       <v-divider light></v-divider>
                                       <v-card-actions class="pa-3">

                                          {{data_patient.gender}}
                                          <v-spacer></v-spacer>

                                          {{data_patient.dob}}
                                       </v-card-actions>
                                    </v-card>
                                    <v-subheader class="mt-4" v-if="data_requirement.length > 0">Requirement</v-subheader>
                                    <v-layout v-if="data_requirement.length > 0" class="px-3" row wrap>
                                       <div v-for="(req ,idx) in data_requirement">
                                          <kbd class="ml-1 mr-1">{{ req.reqName }}</kbd>
                                       </div>
                                    </v-layout>
                                    <v-list v-if="data_patient" class="mt-4" subheader>
                                       <v-subheader>Pemeriksaan</v-subheader>

                                       <v-list-group v-if="data_packet.length > 0" v-for="(item,k_item) in data_packet" :key="k_item" v-model="item.active" :prepend-icon="item.action" no-action>
                                          <template v-slot:activator>
                                             <v-list-tile>
                                                <v-list-tile-content>
                                                   <v-list-tile-title>{{ item.packet_name }}</v-list-tile-title>
                                                </v-list-tile-content>
                                             </v-list-tile>
                                          </template>

                                          <v-list-tile v-for="subItem in item.details" :key="subItem.test_name" @click="">
                                             <v-list-tile-content>
                                                <v-list-tile-title>{{ subItem.test_name }}</v-list-tile-title>
                                             </v-list-tile-content>

                                             <v-list-tile-action>
                                                <v-icon>{{ subItem.action }}</v-icon>
                                             </v-list-tile-action>
                                          </v-list-tile>
                                       </v-list-group>

                                       <v-card v-if="data_tests.length > 0" flat>

                                          <v-form>
                                             <v-text-field v-for="(test,k_test) in data_tests" :key="k_test" label="" prepend-inner-icon="label_important" :value="test.test_name" single-line full-width hide-details></v-text-field>
                                             <!--<v-divider v-if="k_test <= data.tests.length - 1"></v-divider>-->
                                             <!--<v-text-field
                                       prepend-inner-icon="label_important"
                                       label="Subject"
                                       value="Nebeng boy"
                                       single-line
                                       full-width
                                       hide-details
                                       ></v-text-field>-->

                                          </v-form>
                                       </v-card>


                                    </v-list>


                                    <v-subheader>Pengambilan Sample / Pemeriksaan Fisik</v-subheader>
                                    <!-- <v-subheader v-if="data_sample_lab.length > 0" inset>Laboratorium</v-subheader>-->

                                    <v-list-tile v-if="data_sample_lab.length > 0" v-for="sample in data_sample_lab" :key="sample.barcode" avatar @click="goToResult(sample)">
                                       <v-list-tile-avatar>
                                          <v-icon v-if="selected_station.isnonlab === ''" class="blue white--text">colorize</v-icon>
                                          <v-icon v-if="selected_station.isnonlab === 'RADIODIAGNOSTIC'" class="amber white--text">assignment_ind</v-icon>
                                          <v-icon v-if="selected_station.isnonlab === 'ELEKTROMEDIS'" class="teal white--text">contacts</v-icon>
                                          <v-icon v-if="selected_station.isnonlab === 'OTHERS'" class="orange darken-2 white--text">accessibility</v-icon>
                                       </v-list-tile-avatar>

                                       <v-list-tile-content>
                                          <v-list-tile-title>{{ sample.sampletype_name }}</v-list-tile-title>
                                          <v-list-tile-sub-title>{{ sample.receive_date }} {{ sample.receive_time }}</v-list-tile-sub-title>
                                       </v-list-tile-content>

                                       <v-list-tile-action>
                                          <v-btn icon ripple>
                                             <v-icon v-if="sample.is_sampling == 'N' || sample.is_received == 'N'" color="grey lighten-1">info</v-icon>
                                             <v-icon v-if="sample.is_sampling == 'Y' && sample.is_received == 'Y'" color="success lighten-1">check_circle</v-icon>
                                          </v-btn>
                                       </v-list-tile-action>
                                    </v-list-tile>

                                    <v-layout v-if="parseInt(selected_station.id) === 17" class="mt-2" row>
                                       <v-flex xs12>
                                          <v-text-field v-model="value_tb" label="Tinggi Badan" hide-details></v-text-field>
                                       </v-flex>
                                       <v-flex xs12>
                                          <v-text-field v-model="value_bb" label="Berat Badan" hide-details></v-text-field>
                                       </v-flex>
                                    </v-layout>
                                    <v-layout v-if="parseInt(selected_station.id) === 35" class="mt-2" row>
                                       <v-flex xs12>
                                          <v-text-field v-model="value_bf" label="Body Fat" hide-details></v-text-field>
                                       </v-flex>
                                    </v-layout>

                                    <div class="mt-3" v-if="glucoses.length > 0">
                                       <v-divider></v-divider>
                                       <p class="mb-0 mt-2">Isi hasil gula di bawah ini : </p>
                                       <v-layout  class="mt-1" row>
                                       <v-flex xs12 v-for="gluc in glucoses">
                                          <v-text-field v-model="gluc.result" :label="gluc.test_name" hide-details></v-text-field>
                                       </v-flex>

                                    </v-layout>
                                    </div>
                                    

                                    <v-layout v-if="parseInt(selected_station.id) === 11 || parseInt(selected_station.id) === 35" class="mt-2" row>
                                       <v-flex xs12 v-if="data_patient.isTBBB === 'Y'">
                                          <p>Umur Pasien {{ data_patient.patient_age }}</p>
                                          <p>Tinggi badan {{ data_patient.patientTB }}</p>
                                          <p>Berat badan {{ data_patient.patientBB }}</p>
                                       </v-flex>
                                       <v-flex xs12 v-if="data_patient.isTBBB === 'N'">
                                          <p>Umur Pasien {{ data_patient.patient_age }}</p>
                                          <p>Belum melakukan pengukuran tinggi dan berat badan</p>
                                       </v-flex>

                                    </v-layout>

                                    <p v-if="parseInt(selected_station.id) === 33" class="mt-4 mb-1">Tanpa Kacamata</p>
                                    <v-layout v-if="parseInt(selected_station.id) === 33" row>
                                       <v-flex xs12>
                                          <v-text-field v-model="tkod" label="OD" hide-details></v-text-field>
                                       </v-flex>
                                       <v-flex xs12>
                                          <v-text-field v-model="tkos" label="OS" hide-details></v-text-field>
                                       </v-flex>
                                    </v-layout>
                                    <p v-if="parseInt(selected_station.id) === 33" class="mt-2 mb-1">Dengan Kacamata</p>
                                    <v-layout v-if="parseInt(selected_station.id) === 33" row>
                                       <v-flex xs12>
                                          <v-text-field v-model="dkod" label="OD" hide-details></v-text-field>
                                       </v-flex>
                                       <v-flex xs12>
                                          <v-text-field v-model="dkos" label="OS" hide-details></v-text-field>
                                       </v-flex>
                                    </v-layout>
                                    <p v-if="parseInt(selected_station.id) === 33 && visusDenganKoreksi()" class="mt-4 mb-1">Dengan Koreksi</p>
                                    <v-layout v-if="parseInt(selected_station.id) === 33 && visusDenganKoreksi()" row>
                                       <v-flex xs12>
                                          <v-text-field label="OD Sph" v-model="odSph"></v-text-field>
                                       </v-flex>
                                       <v-flex xs12>
                                          <v-text-field label="OD Cyl" v-model="odCyl"></v-text-field>
                                       </v-flex>
                                       <v-flex xs12>
                                          <v-text-field label="OD X" v-model="odX"></v-text-field>
                                       </v-flex>
                                       <v-flex xs12>
                                          <v-text-field label="ADD" v-model="visusAdd"></v-text-field>
                                       </v-flex>
                                    </v-layout>
                                    <v-layout v-if="parseInt(selected_station.id) === 33 && visusDenganKoreksi()" row>
                                       <v-flex xs12>
                                          <v-text-field label="OS Sph" v-model="osSph"></v-text-field>
                                       </v-flex>
                                       <v-flex xs12>
                                          <v-text-field label="OS Cyl" v-model="osCyl"></v-text-field>
                                       </v-flex>
                                       <v-flex xs12>
                                          <v-text-field label="OS X" v-model="osX"></v-text-field>
                                       </v-flex>
                                    </v-layout>
                                    <p v-if="parseInt(selected_station.id) === 33" class="mt-2 mb-1">Buta Warna</p>
                                    <v-radio-group v-if="parseInt(selected_station.id) === 33" v-model="btwrn">
                                       <v-radio label="Normal" value="N"></v-radio>
                                       <v-radio label="Buta Warna" value="BW"></v-radio>
                                       <!-- <v-radio label="Buta Warna Parsial" value="BP"></v-radio>
                                       <v-radio label="Buta Warna Total" value="BT"></v-radio> -->
                                    </v-radio-group>
                                    <v-text-field v-if="btwrn === 'BW'" label="Buta Warna" v-model="colorBlindNumber"></v-text-field>
                                 </v-flex>
                              </v-layout>
                           </v-container>
                        </v-card-text>
                        <v-divider v-if="!_.isEmpty(data_patient)"></v-divider>
                        <v-card-actions v-if="!_.isEmpty(data_patient) && isdone != 'Y'">
                           <v-btn @click="skipAction()" dark color="black ligthen-2"><v-icon left>fast_rewind</v-icon> SKIP</v-btn>
                           <v-spacer></v-spacer>
                           <v-btn @click="scanSample()" dark color="blue"> SCAN QR CODE (CHECKOUT) <v-icon right>camera</v-icon></v-btn>
                        </v-card-actions>
                     </v-card>


                     <qr-scanner></qr-scanner>

                  </div>
               </template>

            </v-container>
         </v-content>
         <one-footer> </one-footer>

      </v-app>
   </div>

   <!-- Vendor -->
   <script src="../../../libs/vendor/html5-qrcode.min.js"></script>
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
   <script src="../../../libs/one_print_robo.js"></script>



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
            'one-patient-list': httpVueLoader('./components/onePatientList.vue'),
            'qr-scanner': httpVueLoader('./components/oneQRscanner.vue')
         },
         mounted: function() {
            let url_string = window.location.href
            let url = new URL(url_string)
            let id = url.searchParams.get("nolab")
            let labnumber = url.searchParams.get("labnumber")
            let station_id = url.searchParams.get("stat")
            let location_id = url.searchParams.get("loc")
            console.log("xasdasd")

            //this.$store.commit('patient/update_order_id', id)
            //this.$store.commit('patient/update_noreg', noreg)
            if (labnumber) {
               console.log(labnumber)
               this.$store.commit('patient/update_url_labnumber', labnumber)
               this.$store.commit('patient/update_station_id', station_id)
               this.$store.commit('patient/update_location_id', location_id)
               this.$store.dispatch("patient/scan_patient", {
                  'labnumber': labnumber,
                  'station_id': station_id,
                  'location_id': location_id
               })
               //this.$store.dispatch("patient/getstations",{labnumber:labnumber,station_id:station_id,location_id:location_id})
            } else if (station_id && location_id) {
               console.log('stationid ' + station_id)
               this.$store.commit('patient/update_station_id', station_id)
               this.$store.commit('patient/update_location_id', location_id)
               //this.$store.dispatch("patient/scan_patient",{'station_id':station_id,'location_id':location_id})
            }

            this.$store.dispatch("patient/getstations")



         },
         methods: {
            visusDenganKoreksi() {
               let ret = false;

               if ((this.tkod.trim() != '20/20' && this.tkod.trim() != '20/25' && this.tkod.trim() != '') ||
                  (this.tkos.trim() != '20/20' && this.tkos.trim() != '20/25' && this.tkos.trim() != '') ||
                  (this.dkod.trim() != '20/20' && this.dkod.trim() != '20/25' && this.dkod.trim() != '') ||
                  (this.dkos.trim() != '20/20' && this.dkos.trim() != '20/25' && this.dkos.trim() != '')
               ) {
                  ret = true;
               }
               // console.log("Visus dengan koreksi");
               // console.log(this.tkod);
               // console.log(this.tkos);
               // console.log(this.dkod);
               // console.log(this.dkos);
               // console.log(ret);
               this.withCorection = ret;
               return ret;
            },
            goToResult(sample) {
               let stat_id = this.$store.state.patient.selected_station.id
               let loc_id = this.$store.state.patient.selected_location.locationID
               let urls = this.$store.state.patient.urls
               if (sample.sampletype_name === "Pemeriksaan Fisik") {
                  window.location = "/one-ui/"+urls.url_fisik+"?id=" + this.url_labnumber + "&type=fisik&stat=" + stat_id + "&loc=" + loc_id
               }
               if (sample.groupresult_name === "Elektromedik" || sample.groupresult_name === "Audiometri" || sample.groupresult_name === "Spirometri") {
                  // https: //devcpone.aplikasi.web.id/one-ui/test/vuex/one-resultentry-so-electromedis-v7-cpone/?id=T2406260012&test=1675
                  window.location = "/one-ui/"+urls.url_electromedis+"?id=" + this.url_labnumber + "&test=" + sample.test_id + "&stat=" + stat_id + "&loc=" + loc_id
               }
               if (sample.groupresult_name === "USG" || sample.groupresult_name === "Rontgen") {
                  // https: //devcpone.aplikasi.web.id/one-ui/test/vuex/one-resultentry-so-xray-v7-cpone/?id=T2406260009&stat=7&loc=3
                  window.location = "/one-ui/"+urls.url_xray+"?id=" + this.url_labnumber + "&stat=" + stat_id + "&loc=" + loc_id
               }
            },
            logout() {
               window.one_logout('/one-ui/test/vuex/one-login')
            },
            openscanner() {
               this.$store.commit("patient/update_act_scan", 'qrpatient')
               this.dialog_scanner = true
            },
            skipAction() {
               this.$store.dispatch("patient/skipaction")
            },
            scanSample() {
               console.log("check input")
               if (parseInt(this.selected_station.id) === 17) {
                  if (this.value_tb === 0 || this.value_bb === 0) {
                     this.$store.commit("patient/update_alert_msg", "Silahkan diisi, tinggi badan dan berat badan")
                     this.$store.commit("patient/update_alert_status", true)
                  } else {
                     this.$store.commit("patient/update_act_scan", 'scanbarcode')
                     this.dialog_scanner = true
                  }
               } else if (parseInt(this.selected_station.id) === 35) {
                  console.log("body fat")
                  if (parseFloat(this.value_bf) === 0 || this.value_bf === "") {
                     this.$store.commit("patient/update_alert_msg", "Silahkan diisi, body fat")
                     this.$store.commit("patient/update_alert_status", true)
                  } else {
                     this.$store.commit("patient/update_act_scan", 'scanbarcode')
                     this.dialog_scanner = true
                  }
               } else if (parseInt(this.selected_station.id) === 33) {
                  if ((this.tkod !== '' && this.tkos !== '') || (this.dkod !== '' && this.dkos !== '')) {
                     this.$store.commit("patient/update_act_scan", 'scanbarcode')
                     this.dialog_scanner = true
                  } else {
                     this.$store.commit("patient/update_alert_msg", "Anda lupa mengisi visus")
                     this.$store.commit("patient/update_alert_status", true)
                  }
               } else {
                  this.$store.commit("patient/update_act_scan", 'scanbarcode')
                  this.dialog_scanner = true
               }

            }
         },
         computed: {
            
            glucoses: {
               get() {
                  return this.$store.state.patient.glucoses
               },
               set(val) {
                  this.$store.commit("patient/update_glucoses", val)
               }
            },
            loc_id() {
               return this.$store.state.patient.loc_id
            },
            stat_id() {
               return this.$store.state.patient.station_id
            },
            url_labnumber() {
               return this.$store.state.patient.url_labnumber
            },
            btwrn: {
               get() {
                  return this.$store.state.patient.btwrn
               },
               set(val) {
                  this.$store.commit("patient/update_btwrn", val)
               }
            },
            dkos: {
               get() {
                  return this.$store.state.patient.dkos
               },
               set(val) {
                  this.$store.commit("patient/update_dkos", val)
               }
            },
            dkod: {
               get() {
                  return this.$store.state.patient.dkod
               },
               set(val) {
                  this.$store.commit("patient/update_dkod", val)
               }
            },
            tkos: {
               get() {
                  return this.$store.state.patient.tkos
               },
               set(val) {
                  this.$store.commit("patient/update_tkos", val)
               }
            },
            tkod: {
               get() {
                  return this.$store.state.patient.tkod
               },
               set(val) {
                  this.$store.commit("patient/update_tkod", val)
               }
            },
            visusAdd: {
               get() {
                  return this.$store.state.patient.visusAdd
               },
               set(val) {
                  this.$store.commit("patient/update_visusAdd", val)
               }
            },
            value_tb: {
               get() {
                  return this.$store.state.patient.value_tb
               },
               set(val) {
                  this.$store.commit("patient/update_value_tb", val)
               }
            },
            value_bb: {
               get() {
                  return this.$store.state.patient.value_bb
               },
               set(val) {
                  this.$store.commit("patient/update_value_bb", val)
               }
            },
            value_bf: {
               get() {
                  return this.$store.state.patient.value_bf
               },
               set(val) {
                  this.$store.commit("patient/update_value_bf", val)
               }
            },
            status_done() {
               return this.$store.state.patient.status_done
            },
            isdone() {
               return this.$store.state.patient.isdone
            },
            stations() {
               return this.$store.state.patient.stations
            },
            selected_station: {
               get() {
                  return this.$store.state.patient.selected_station
               },
               set(val) {
                  this.$store.commit("patient/update_selected_station", val)
                  this.$store.dispatch("patient/getLocation", val.id)
               }
            },
            locations() {
               return this.$store.state.patient.locations
            },
            selected_location: {
               get() {
                  return this.$store.state.patient.selected_location
               },
               set(val) {
                  this.$store.commit("patient/update_selected_location", val)
               }
            },
            data_patient() {
               return this.$store.state.patient.data_patient
            },
            dialog_scanner: {
               get() {
                  return this.$store.state.patient.dialog_scanner
               },
               set(val) {
                  this.$store.commit("patient/update_dialog_scanner", val)
               }
            },

            withCorection: {
               get() {
                  return this.$store.state.patient.withCorection
               },
               set(val) {
                  this.$store.commit("patient/update_withCorection", val)
               }
            },
            odSph: {
               get() {
                  return this.$store.state.patient.odSph
               },
               set(val) {
                  this.$store.commit("patient/update_odSph", val)
               }
            },
            odCyl: {
               get() {
                  return this.$store.state.patient.odCyl
               },
               set(val) {
                  this.$store.commit("patient/update_odCyl", val)
               }
            },
            odX: {
               get() {
                  return this.$store.state.patient.odX
               },
               set(val) {
                  this.$store.commit("patient/update_odX", val)
               }
            },
            osSph: {
               get() {
                  return this.$store.state.patient.osSph
               },
               set(val) {
                  this.$store.commit("patient/update_osSph", val)
               }
            },
            osCyl: {
               get() {
                  return this.$store.state.patient.osCyl
               },
               set(val) {
                  this.$store.commit("patient/update_osCyl", val)
               }
            },
            osX: {
               get() {
                  return this.$store.state.patient.osX
               },
               set(val) {
                  this.$store.commit("patient/update_osX", val)
               }
            },
            colorBlindNumber: {
               get() {
                  return this.$store.state.patient.colorBlindNumber
               },
               set(val) {
                  this.$store.commit("patient/update_colorBlindNumber", val)
               }
            },
            dialogError: {
               get() {
                  return this.$store.state.patient.dialogError
               },
               set(val) {
                  this.$store.commit("patient/update_dialogError", val)
               }
            },
            errorMsg: {
               get() {
                  return this.$store.state.patient.errorMsg
               },
               set(val) {
                  this.$store.commit("patient/update_errorMsg", val)
               }
            },
            data_patient() {
               return this.$store.state.patient.data_patient
            },
            data_requirement() {
               return this.$store.state.patient.data_requirement
            },
            data_packet() {
               return this.$store.state.patient.data_packet
            },
            data_tests() {
               return this.$store.state.patient.data_tests
            },
            data_sample_lab() {
               return this.$store.state.patient.data_sample_lab
            },
            data_sample_radiodiagnostic() {
               return this.$store.state.patient.data_sample_radiodiagnostic
            },
            data_sample_electromedic() {
               return this.$store.state.patient.data_sample_electromedic
            },
            data_sample_other() {
               return this.$store.state.patient.data_sample_other
            },
         },
         data() {
            return {
               imageSrc: './images/undraw_the_search_s0xf.png',
               items2: [{
                     icon: 'assignment',
                     iconClass: 'blue white--text',
                     title: 'Vacation itinerary',
                     subtitle: 'Jan 20, 2014'
                  },
                  {
                     icon: 'call_to_action',
                     iconClass: 'amber white--text',
                     title: 'Kitchen remodel',
                     subtitle: 'Jan 10, 2014'
                  }
               ],
               items: [{
                  action: 'healing',
                  title: 'Paket Pria 2024',
                  items: [{
                        title: 'List Item'
                     },
                     {
                        title: 'List Item'
                     },
                     {
                        title: 'List Item'
                     }
                  ]
               }]
            }
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