<template>
  <v-layout class="mb-2 mt-2" column>
    <one-req></one-req>
    <one-comment></one-comment>

    <v-dialog v-model="dialognote" width="40%">
      <v-card>
        <v-card-title class="headline white--text error" primary-title>
        </v-card-title>

        <v-card-text>
          <v-layout v-if="selected_patient.fo_note != ''" mb-2 row>
            <v-flex mb-2 xs3>
              <span style="color: #0e6fbc" class="mono name">Catatan </span>
            </v-flex>
            <v-flex xs9>
              <v-layout row>
                <v-flex mb-1 xs12>
                  <code style="
                      box-shadow: none !important;
                      color: #0e6fbc !important;
                      background-color: #2196f34d !important;
                    ">front office</code>
                  <div class="v-markdown">
                    <p style="margin-top: 2px; margin-bottom: 0">
                      {{ selected_patient.fo_note }}
                    </p>
                  </div>
                </v-flex>
              </v-layout>
            </v-flex>
          </v-layout>
          <v-layout v-if="xnoterequirement.length > 0" mb-2 row>
            <v-flex mb-2 xs3>
              <span style="color: #c0341d" class="mono name">Requirement </span>
            </v-flex>
            <v-flex xs9>
              <v-layout v-for="notereq in xnoterequirement" row>
                <v-flex mb-1 xs12>
                  <code style="
                      box-shadow: none !important;
                      color: #c0341d !important;
                      background-color: #fbe5e1 !important;
                    ">{{ notereq.position }}</code>
                  <div class="v-markdown">
                    <p style="margin-top: 2px; margin-bottom: 0">
                      {{ notereq.requirements }}
                    </p>
                  </div>
                </v-flex>
              </v-layout>
            </v-flex>
          </v-layout>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" dark flat text @click="dialognote = false">
            Tutup
          </v-btn>
        </v-card-actions>
      </v-card>

    </v-dialog>

    <v-dialog v-model="dialogformnote" width="40%">
      <v-card>
        <v-card-title class="headline white--text primary" primary-title>
        </v-card-title>

        <v-card-text>
          <v-layout mb-2 row>
            <v-flex xs12>
              <v-textarea outline label="Catatan" v-model="selected_patient.sampling_note"></v-textarea>
            </v-flex>
          </v-layout>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" dark flat text @click="searchPatientLastSelect">
            Tutup
          </v-btn>
          <v-btn color="primary" dark flat text @click="saveNoteSampling()">
            Simpan
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogrequirement" persistent max-width="45%">
      <v-card>
        <v-card-title color="success" class="headline">Pilih yang tidak terpenuhi</v-card-title>
        <v-card-text>
          <v-layout wrap>
            <v-flex v-for="(req, idx) in requirements" xs6>
              <one-x-check :xdatalabel="req.name" :xdatacbx="req.chex"
                @update-data-cbx="(val) => checkReq(val, idx)"></one-x-check>
            </v-flex>
          </v-layout>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="green darken-1" flat @click="saveRequirement">Tutup</v-btn>
        </v-card-actions>
      </v-card>

    </v-dialog>

    <v-card style="overflow-y: auto; max-height: 550px" v-if="xsampletypes.length > 0">
      <v-layout row>
        <v-flex xs12>
          <v-subheader style="background: #03a9f4; padding: 5px">
            <v-icon dark large left>assignment_ind</v-icon>
            <h3 style="font-size: x-large" dark class="font-weight-bold white--text">
              {{ staff.name.toUpperCase() }}
            </h3>
            <v-flex text-md-right>
              <v-btn v-if="
                selected_patient.fo_note !== '' ||
                selected_patient.fo_ver_note !== '' ||
                selected_patient.fo_requirements_status !== 'Y' ||
                selected_patient.fo_verification_status !== 'X'
              " @click="openDialogFoNoteRequirement()" style="min-width: 20px; margin-left: 1px; margin-right: 1px"
                deppressed small color="error"><v-icon small>info</v-icon></v-btn>
              <v-btn @click="openDialogFormNote()" style="min-width: 20px; margin-left: 1px; margin-right: 1px"
                deppressed small color="warning"><v-icon small>speaker_notes</v-icon></v-btn>
            </v-flex>
          </v-subheader>
          <v-divider></v-divider>
          <v-layout align-center pa-2 mb-1 class="grey lighten-2" row>
            <v-flex xs4> SPECIMEN / PEMERIKSAAN </v-flex>
            <v-flex xs3> BARCODE </v-flex>
            <v-flex xs2> REQUIREMENT </v-flex>
            <v-flex class="text-xs-center" xs3> AKSI </v-flex>
          </v-layout>

          <v-layout pb-1 row v-for="(sampletype, idx) in xsampletypes" :key="sampletype.T_BarcodeLabBarcode">
            <v-flex xs12>
              <v-layout align-center class="pa-2 grey lighten-4" row>
                <v-flex pl-2 xs4>
                  {{ sampletype.sampletype_name }}
                </v-flex>

                <v-flex xs3>
                  <span v-if="sampletype.is_sampling === 'X'" style="text-decoration: line-through" class="red--text">{{
                    sampletype.T_BarcodeLabBarcode }}</span>
                  <span v-if="sampletype.is_sampling !== 'X'">{{
                    sampletype.barcode
                  }}</span>
                </v-flex>

                <v-flex xs2>
                  <div v-if="
                    selected_patient.status === 'Process' ||
                    selected_patient.status === 'Done'
                  ">
                    <!-- TODO: Field: sampletype.requirement_status pakai apa? -->

                    <span @click="openDialogRequirement(sampletype, idx)" v-bind:class="{
                      white: sampletype.requirement_status === 'X',
                      error: sampletype.requirement_status === 'N',
                    }" class="icon-medium-fill-base-small white"><v-icon
                        :dark="sampletype.requirement_status === 'N'">close</v-icon></span>
                    <span @click="confirmRequirement(sampletype, idx)" v-bind:class="{
                      white: sampletype.requirement_status === 'X',
                      success: sampletype.requirement_status === 'Y',
                    }" class="icon-medium-fill-base-small white"><v-icon
                        :dark="sampletype.requirement_status === 'Y'">check</v-icon></span>
                  </div>
                  <div v-if="
                    selected_patient.status === 'New' ||
                    selected_patient.status === 'Call' ||
                    selected_patient.status === 'Skip'
                  ">
                    -
                  </div>
                </v-flex>

                <v-flex class="text-xs-center" xs3>
                  <v-btn v-if="
                    selected_patient.status === 'Process' ||
                    selected_patient.status === 'Done'
                  " style="margin: 3px 2px" small color="warning">{{ sampletype.sampling_date }}
                    {{ sampletype.sample_time }}</v-btn>
                  <v-btn v-if="
                    selected_patient.status === 'Process' &&
                    sampletype.is_sampling === 'Y' &&
                    sampletype.requirement_status !== 'X'
                  " @click="receiveSample(sampletype)" style="margin: 3px 2px" small color="success">{{
                    sampletype.done_date }}
                    {{ sampletype.receive_time }}</v-btn>
                  <v-btn depressed dark v-if="
                    selected_patient.status === 'Process' &&
                    sampletype.is_received === 'N' &&
                    sampletype.requirement_status === 'X'
                  " style="margin: 3px 2px" small color="grey">00-00-0000 00:00</v-btn>
                  <div v-if="
                    selected_patient.status === 'New' ||
                    selected_patient.status === 'Call' ||
                    selected_patient.status === 'Skip'
                  ">
                    -
                  </div>
                </v-flex>

              </v-layout>
            </v-flex>
          </v-layout>
        </v-flex>
      </v-layout>
      <v-divider></v-divider>

      <!-- TODO: Hilangkan  -->
      <!-- <v-layout row>
        <v-flex xs12>
          <v-layout wrap>
            <v-flex v-for="inf in info" :key="inf.id" pb-1 pl-1 pr-1 xs3>
              <v-btn block small color="primary" v-bind:class="{
                success: inf.status_bahan === 'R',
                warning: inf.status_bahan === 'P',
              }" dark>{{ inf.T_BahanName }}</v-btn>
            </v-flex>
          </v-layout>
        </v-flex>
      </v-layout> -->
    </v-card>
  </v-layout>
</template>

<style scoped>
.overline {
  font-size: 0.625rem !important;
  font-weight: 400;
  letter-spacing: 0.1666666667em !important;
  line-height: 1rem;
  text-transform: uppercase;
}

.redcode {
  box-shadow: none !important;
  color: #c0341d !important;
  background-color: #fbe5e1 !important;
}
</style>

<script>
let ts = "?ts=" + moment().format("YYYYMMDDHHmmss");
module.exports = {
  components: {
    "one-x-check": httpVueLoader("../../common/onexcheck.vue"),
    "one-req": httpVueLoader("./oneRequirement.vue" + ts),
    "one-comment": httpVueLoader("./oneComment.vue" + ts),
  },
  data: () => ({
    checkbox: false,
    //dialognote:false,
    dialognotecolor: "warning",
    // msgnote:''
  }),
  computed: {
    xnoterequirement() {
      return this.$store.state.samplecall.note_requirement;
    },
    xsampletypes() {
      return this.$store.state.samplecall.sampletypes;
    },
    xstatus() {
      return this.$store.state.samplecall.selected_status;
    },
    selected_patient() {
      return this.$store.state.samplecall.selected_patient;
    },
    dialogrequirement: {
      get() {
        return this.$store.state.samplecall.dialog_requirement;
      },
      set(val) {
        this.$store.commit("samplecall/update_dialog_requirement", val);
      },
    },
    dialognote: {
      get() {
        return this.$store.state.samplecall.dialog_note;
      },
      set(val) {
        this.$store.commit("samplecall/update_dialog_note", val);
      },
    },
    dialogformnote: {
      get() {
        return this.$store.state.samplecall.dialog_form_note;
      },
      set(val) {
        this.$store.commit("samplecall/update_dialog_form_note", val);
      },
    },
    msgnote: {
      get() {
        return this.$store.state.samplecall.msg_note;
      },
      set(val) {
        this.$store.commit("samplecall/update_msg_note", val);
      },
    },
    requirements: {
      get() {
        return this.$store.state.samplecall.requirements;
      },
      set(val) {
        this.$store.commit("samplecall/update_requirements", val);
      },
    },
    info: {
      get() {
        return this.$store.state.samplecall.information_bahan;
      },
      set(val) {
        this.$store.commit("samplecall/update_information_bahan", val);
      },
    },
    staff: {
      get() {
        return this.$store.state.samplecall.staff;
      },
      set(val) {
        this.$store.commit("samplecall/update_staff", val);
      },
    },
  },
  methods: {
    processSample(sampletype) {
      var patient = this.$store.state.samplecall.selected_patient;
      var msg =
        "Anda yakin akan melakukan proses untuk " +
        sampletype.T_SampleTypeName +
        " dari " +
        patient.patient_fullname +
        " ? ";
      this.$store.commit("samplecall/update_msg_action", msg);
      this.$store.commit("samplecall/update_act", "samplingprocess");
      this.$store.commit("samplecall/update_selected_sampletype", sampletype);
      this.$store.commit("samplecall/update_dialog_action", true);
    },
    doneSample(sampletype) {
      var patient = this.$store.state.samplecall.selected_patient;
      var msg =
        "Anda yakin proses untuk " +
        sampletype.T_SampleTypeName +
        " dari " +
        patient.patient_fullname +
        " telah selesai ? ";
      this.$store.commit("samplecall/update_msg_action", msg);
      this.$store.commit("samplecall/update_act", "samplingdone");
      this.$store.commit("samplecall/update_selected_sampletype", sampletype);
      this.$store.commit("samplecall/update_dialog_action", true);
    },
    printBarcodeGroup() {
      var id = this.selected_patient.T_OrderHeaderID;
      one_print_barcode_so_group(id);
    },
    printBarcode(sampletype) {
      var id = sampletype.T_OrderDetailID;
      one_print_barcode_so(id);
    },
    openDialogRequirement(value, idx) {
      if (value.status === "D" && value.requirement_status === "Y") {
      } else {
        this.$store.commit("samplecall/update_selected_sample", value);
        var sampletypes = this.$store.state.samplecall.sampletypes;
        sampletypes[idx].requirement_status = "N";
        this.$store.commit(
          "samplecall/update_requirements",
          sampletypes[idx].requirements
        );
        this.$store.commit("samplecall/update_dialog_requirement", true);
      }
    },
    confirmRequirement(value, idx) {
      if (value.status === "P") {
        var sampletypes = this.$store.state.samplecall.sampletypes;
        sampletypes[idx].requirement_status = "Y";
        this.$store.commit("samplecall/update_sampletypes", sampletypes);

        sampletypes[idx].requirements.forEach((el) => {
          el.chex = "N";
        });
      }
    },
    saveRequirement() {
      //console.log(this.$store.state.samplecall.selected_sample)

      var sampletypes = this.$store.state.samplecall.sampletypes;
      var selected_sample = this.$store.state.samplecall.selected_sample;
      var idx = _.findIndex(sampletypes, function (o) {
        return o.T_BarcodeLabBarcode == selected_sample.T_BarcodeLabBarcode;
      });
      if (sampletypes[idx].status === "P") {
        sampletypes[idx].requirements =
          this.$store.state.samplecall.requirements;
      }
      this.$store.commit("samplecall/update_dialog_requirement", false);
    },
    checkReq(val, idx) {
      var xrequirements = this.requirements;
      console.log(xrequirements[idx]);
      if (xrequirements[idx].T_OrderSampleReceive === "N") {
        xrequirements[idx].chex = val;
        this.$store.commit("samplecall/update_requirements", xrequirements);
      }
    },
    receiveSample(value) {
      var goaction = true;
      if (value.requirement_status === "N") {
        var req_check = _.filter(value.requirements, function (o) {
          return o.chex === "Y";
        });
        if (req_check.length === 0) {
          goaction = false;
        }
      }
      if (
        value.T_OrderSampleReceive === "N" &&
        value.requirement_status !== "X" &&
        goaction
      ) {
        this.$store.commit("samplecall/update_act", "samplingdone");
        var prm = this.selected_patient;
        prm.id = this.selected_patient.T_OrderHeaderID;
        prm.act = "samplingdone";
        prm.sample = value;
        prm.staff = this.$store.state.samplecall.staff;
        prm.search = {
          xdate: this.$store.state.samplecall.start_date,
          name: this.$store.state.samplecall.name,
          nolab: this.$store.state.samplecall.nolab,
          stationid: this.$store.state.samplecall.selected_station.id,
          statusid: this.$store.state.samplecall.selected_status.id,
          companyid: this.$store.state.samplecall.selected_company.id,
          lastid: this.$store.state.samplecall.last_id,
          locationid: this.$store.state.samplecall.selected_location.locationID,
        };
        this.$store.dispatch("samplecall/receivesample", prm);
      } else {
        //console.log('oeey')
        if (value.status === "P") {
          this.$store.commit(
            "samplecall/update_msg_info",
            "Jalan - jalan ke gunung merapi, Requirement-nya tolong dilengkapi"
          );
          this.$store.commit("samplecall/update_open_dialog_info", true);
        }
      }
    },
    addNewLabel(sampletype) {
      this.$store.commit("samplecall/update_selected_sampletype", sampletype);
      var sample = sampletype.T_SampleTypeName;
      var patient = this.$store.state.samplecall.selected_patient;
      var msg =
        "Anda yakin akan melakukan penambahan label spesimen " +
        sample +
        " untuk pasien " +
        patient.patient_fullname +
        " ? ";
      this.$store.commit("samplecall/update_msg_action", msg);
      this.$store.commit("samplecall/update_act", "addnewlabel");
      //this.closeDialogAction()
      this.$store.commit("samplecall/update_dialog_action", true);
    },
    openDialogNote() {
      this.msgnote = '<p><code color="red">catatan dari fo : </code></p>';
      this.msgnote +=
        "<p>" +
        this.$store.state.samplecall.selected_patient.T_OrderHeaderFoNote +
        "</p>";
      this.dialognotecolor = "warning";
      this.dialognote = true;
    },
    openDialogFormNote() {
      //this.dialogformnote = true
      //WIP
      this.$store.commit("comment/update_show", true);
      this.$store.commit(
        "comment/update_patient",
        this.$store.state.samplecall.selected_patient
      );
      this.$store.dispatch("comment/load");
    },
    openDialogFoNoteRequirement() {
      var prm = this.$store.state.samplecall.selected_patient;
      this.$store.commit("req/update_patient", prm);
      this.$store.dispatch("req/load");
      this.$store.commit("req/update_show", true);
      // this.$store.dispatch("samplecall/getdatanoterequirement",prm)
    },
    searchPatientLastSelect() {
      this.dialogformnote = false;
      this.$store.dispatch("samplecall/search", {
        xdate: this.$store.state.samplecall.start_date,
        name: this.$store.state.samplecall.name,
        nolab: this.$store.state.samplecall.nolab,
        stationid: this.$store.state.samplecall.selected_station.id,
        statusid: this.$store.state.samplecall.selected_status.id,
        companyid: this.$store.state.samplecall.selected_company.id,
        lastid: this.$store.state.samplecall.last_id,
        locationid: this.$store.state.samplecall.selected_location.locationID,
      });
    },
    saveNoteSampling() {
      var prm = this.$store.state.samplecall.selected_patient;
      prm.search = {
        xdate: this.$store.state.samplecall.start_date,
        name: this.$store.state.samplecall.name,
        nolab: this.$store.state.samplecall.nolab,
        stationid: this.$store.state.samplecall.selected_station.id,
        statusid: this.$store.state.samplecall.selected_status.id,
        companyid: this.$store.state.samplecall.selected_company.id,
        lastid: this.$store.state.samplecall.last_id,
        locationid: this.$store.state.samplecall.selected_location.locationID,
      };
      this.$store.dispatch("samplecall/savenotesampling", prm);
    },
  },
};
</script>
