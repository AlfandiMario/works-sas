<template>
  <v-layout row justify-center>
    <v-dialog v-model="dialog_scanner" persistent max-width="290">
      <v-card>
        <v-card-title class="headline">Arahkan kamera ke QR Code</v-card-title>
        <v-card-text>
          <v-layout align-content-center row>
            <v-flex align-self-center xs12 ma-2>
              <div id="qr-reader" style="width: 290"></div>
            </v-flex>
          </v-layout>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn dark color="blue darken-1" @click="startscan">mulai</v-btn>
          <v-btn color="grey darken-1" flat @click="dialog_scanner = false"
            >tutup</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialog_done" width="80%" persistent>
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          Selesai
        </v-card-title>

        <v-card-text>
          <v-layout row>
            <v-flex xs12>
              <v-card color="primary" class="mb-3 white--text">
                <v-layout row>
                  <v-flex xs7>
                    <v-card-title primary-title>
                      <div>
                        <div class="headline">{{ data_patient.labnumber }}</div>
                        <div>
                          {{ data_patient.patient_name }}
                          <span v-if="data_patient.nip"
                            >/ {{ data_patient.nip }}</span
                          >
                        </div>
                        <div class="caption">{{ data_patient.order_date }}</div>
                        <div class="body-2">
                          {{ data_patient.corporate_name }}
                        </div>
                      </div>
                    </v-card-title>
                  </v-flex>
                  <v-flex xs5>
                    <v-img
                      class="mt-2"
                      :src="data_patient.photo"
                      height="115px"
                      contain
                    ></v-img>
                  </v-flex>
                </v-layout>
                <v-divider light></v-divider>
                <v-card-actions class="pa-3">
                  {{ data_patient.gender }}
                  <v-spacer></v-spacer>

                  {{ data_patient.dob }}
                </v-card-actions>
              </v-card>

              <p>
                Pasien sudah melakukan semua pengambilan sample atau pemeriksaan
                di station ini
              </p>
              <v-card flat>
                <v-chip
                  v-for="sample in data_sample_lab_done"
                  color="success"
                  text-color="white"
                >
                  <v-avatar>
                    <v-icon>check_circle</v-icon>
                  </v-avatar>
                  {{ sample.sampletype_name }}
                </v-chip>
              </v-card>
            </v-flex>
          </v-layout>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" flat @click="closeDone()"> Tutup </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <!--<one-dialog-alert :status="alert_status" :msg="alert_msg"></one-dialog-alert>-->

    <v-dialog v-model="alert_status" width="80%">
      <v-card>
        <v-card-title class="headline red lighten-2 white--text" primary-title>
          Peringatan
        </v-card-title>

        <v-card-text>
          <p>{{ alert_msg }}</p>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" flat @click="closeAlert()"> Tutup </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-layout>
</template>

<script>
module.exports = {
  components: {
    //'one-dialog-info':httpVueLoader('../../common/oneDialogInfo.vue'),
    //'one-dialog-alert':httpVueLoader('../../common/oneDialogAlert.vue')
  },
  mounted() {},
  methods: {
    startscan() {
      if (!this.isScanning) {
        this.isScanning = true;
        this.html5QrCode = new Html5Qrcode("qr-reader");
        this.html5QrCode
          .start(
            { facingMode: "environment" },
            {
              fps: 10,
              qrbox: { width: 250, height: 250 },
            },
            this.qrCodeSuccessCallback
          )
          .catch((err) => {
            console.error(`Error scanning QR code: ${err}`);
            this.isScanning = false;
          });
      }
    },
    qrCodeSuccessCallback(decodedText, decodedResult) {
      //alert(`QR Code Content: ${decodedText}`);
      let act = this.$store.state.patient.act_scan;
      var station = this.$store.state.patient.selected_station;
      var location = this.$store.state.patient.selected_location;
      console.log(location);
      console.log(act);
      // return;
      if (act === "qrpatient") {
        this.dialog_scanner = false;
        window.location =
          "/one-ui/"+this.urls.url_sampling+"?labnumber=" +
          decodedText +
          "&stat=" +
          station.id +
          "&loc=" +
          location.locationID;
        this.$store.dispatch("patient/scan_patient", {
          labnumber: decodedText,
        });
      }
      if (act === "scanbarcode") {
        this.dialog_scanner = false;
        this.$store.dispatch("patient/scan_barcode", { barcode: decodedText });
      }
    },
    closeAlert() {
      let act = this.$store.state.patient.act_scan;

      if (act === "scanbarcode") this.$store.dispatch("patient/skipaction");
      else {
        this.alert_status = false;
      }
      //location.replace("/one-ui/test/vuex/cpone-sample-lab-mobile-v3/")
    },
    closeDone() {
      this.dialog_done = false;
      var station = this.$store.state.patient.selected_station;
      var location = this.$store.state.patient.selected_location;
      //location.replace("/one-ui/test/vuex/cpone-sample-lab-mobile-v3/")
      window.location =
        "/one-ui/"+this.urls.url_sampling+"?stat=" +
        station.id +
        "&loc=" +
        location.locationID;
      //location.replace("/one-ui/test/vuex/cpone-sample-lab-mobile-v3/?stat="+station.id+"loc="+location.id)
    },
  },
  computed: {
    urls() {
      return this.$store.state.patient.urls
    },
    dialog_done: {
      get() {
        return this.$store.state.patient.dialog_done
      },
      set(val) {
        this.$store.commit("patient/update_dialog_done", val);
      },
    },
    data_sample_lab_done() {
      return this.$store.state.patient.data_sample_lab_done;
    },
    data_patient() {
      return this.$store.state.patient.data_patient;
    },
    alert_msg() {
      return this.$store.state.patient.alert_msg;
    },
    alert_status: {
      get() {
        return this.$store.state.patient.alert_status;
      },
      set(val) {
        this.$store.commit("patient/update_alert_status", val);
      },
    },
    dialog_scanner: {
      get() {
        return this.$store.state.patient.dialog_scanner;
      },
      set(val) {
        this.$store.commit("patient/update_dialog_scanner", val);
      },
    },
    isScanning: {
      get() {
        return this.$store.state.patient.isScanning;
      },
      set(val) {
        this.$store.commit("patient/update_isScanning", val);
      },
    },
    html5QrCode: {
      get() {
        return this.$store.state.patient.html5QrCode;
      },
      set(val) {
        this.$store.commit("patient/update_html5QrCode", val);
      },
    },
  },
  data() {
    return {
      isDialogVisible: false,
    };
  },
  watch: {
    dialog_scanner(n, o) {
      console.log("blallal");
      if (n == true) {
      } else {
        if (this.isScanning && this.html5QrCode) {
          this.html5QrCode
            .stop()
            .then(() => {
              this.isScanning = false;
            })
            .catch((err) => {
              console.error("Failed to stop QR code scanning.", err);
            });
        }
      }
    },
  },
};
</script>
