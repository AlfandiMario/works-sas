<template>
  <div>
    <!-- :title="printtitle" :width="printwidth" :height="500" :status="openprint"
    :urlprint="urlprint" @close-dialog-print="closePrint" -->
    <v-dialog v-model="openprint" width="80%">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          Cetak Label
        </v-card-title>

        <v-card-text>
          <v-layout wrap align-center>
            <v-flex xs12>
              <v-select
                :items="LabelTypeList"
                v-model="selectedLabelType"
              ></v-select>
            </v-flex>
            <v-flex xs12>
              <object :data="urlprint" width="100%" height="500"></object>
            </v-flex>
          </v-layout>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" flat @click="closePrint"> Tutup </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-snackbar
      color="success"
      v-model="snackbarSuccess"
      right="right"
      :timeout="3000"
      top="top"
    >
      {{ successMsg }}
      <v-btn color="white" flat @click="snackbarSuccess = false"> Close </v-btn>
    </v-snackbar>
    <v-snackbar
      color="warning"
      v-model="snackbarWarning"
      right="right"
      :timeout="3000"
      top="top"
    >
      {{ warningMsg }}
      <v-btn color="white" flat @click="snackbarWarning = false"> Close </v-btn>
    </v-snackbar>
    <v-snackbar
      color="error"
      v-model="snackbarError"
      right="right"
      :timeout="3000"
      top="top"
    >
      {{ errorMsg }}
      <v-btn color="white" flat @click="snackbarError = false"> Close </v-btn>
    </v-snackbar>
    <v-layout class="fill-height" column>
      <v-card style="width: 100%;" class="mb-2 pa-2">
        <v-layout row wrap>
          <v-flex xs12 class="mb-2">
            <v-autocomplete
              label="Pilih Proyek MCU"
              v-model="selectedSetup"
              :items="setupList"
              item-text="Mgm_McuLabel"
              outline
              hide-details
              return-object
              no-data-text="Pilih Proyek MCU"
            >
              <template slot="item" slot-scope="{ item }">
                <v-list-tile-content>
                  <v-list-tile-title
                    v-text="item.Mgm_McuLabel"
                  ></v-list-tile-title>
                </v-list-tile-content>
              </template>
            </v-autocomplete>
          </v-flex>
          <v-flex xs6 class="mb-2">
            <v-menu
              v-model="menuFormDateStart"
              :close-on-content-click="false"
              :nudge-right="40"
              lazy
              transition="scale-transition"
              offset-y
              full-width
              max-width="290px"
              min-width="290px"
            >
              <template v-slot:activator="{ on }">
                <v-text-field
                  class="mr-2"
                  v-model="formatedStartDate"
                  label="Tanggal Awal"
                  outline
                  hide-details
                  readonly
                  v-on="on"
                  @blur="deFormatedDate(formatedStartDate)"
                ></v-text-field>
              </template>
              <v-date-picker
                v-model="startDate"
                no-title
                @input="menuFormDateStart = false"
              ></v-date-picker>
            </v-menu>
          </v-flex>
          <v-flex xs6 class="mb-2">
            <v-menu
              v-model="menuFormDateEnd"
              :close-on-content-click="false"
              :nudge-right="40"
              lazy
              transition="scale-transition"
              offset-y
              full-width
              max-width="290px"
              min-width="290px"
            >
              <template v-slot:activator="{ on }">
                <v-text-field
                  class="ml-2"
                  v-model="formatedEndDate"
                  label="Tanggal Akhir"
                  outline
                  readonly
                  hide-details
                  v-on="on"
                  @blur="deFormatedDate(formatedEndDate)"
                ></v-text-field>
              </template>
              <v-date-picker
                v-model="endDate"
                no-title
                @input="menuFormDateEnd = false"
              ></v-date-picker>
            </v-menu>
          </v-flex>
          <v-flex xs12>
            <v-text-field
              label="Cari..."
              outline
              hide-details
              v-model="search"
            ></v-text-field>
          </v-flex>
        </v-layout>
      </v-card>
    </v-layout>

    <v-card style="overflow-y: scroll; height: 59vh;" class="fill-height">
      <v-data-table
        :loading="loading"
        :items="patientList"
        :headers="headers"
        class="v-table elevation-1"
        hide-actions
      >
        <template v-slot:headers="props">
          <tr>
            <th
              v-for="header in props.headers"
              :width="header.width"
              :class="header.class"
            >
              {{ header.text }}
            </th>
          </tr>
        </template>
        <v-progress-linear
          v-slot:progress="loading"
          color="blue"
          :indeterminate="true"
        ></v-progress-linear>
        <template v-slot:items="props">
          <tr @click="selectMe(props.item)">
            <!-- 'deep-orange darken-1': props.item.status !== 'NEW', -->
            <td
              v-bind:class="{
                'yellow lighten-4': isSelected(props.item),
                'py-2 align-center text-sm-left': true,
              }"
            >
              <p class="font-weight-bold body-2 py-0 mb-0">
                {{ props.item.labNumber }}
              </p>
              <p class="body-2">{{ props.item.orderDate }}</p>
            </td>
            <td
              v-bind:class="{
                'yellow lighten-4': isSelected(props.item),
                'py-2': true,
              }"
            >
              <p class="body-2">{{ props.item.patientFullname }}</p>
            </td>
          </tr>
        </template>
      </v-data-table>
    </v-card>
    <v-card class="pa-2">
      <div class="text-xs-left">
        <v-pagination v-model="page" :length="totalPage"></v-pagination>
      </div>
    </v-card>
    <!-- <one-dialog-print-label
      :title="printtitle"
      :width="printwidth"
      :height="500"
      :status="openprint"
      :urlprint="urlprint"
      @close-dialog-print="closePrint"
    ></one-dialog-print-label> -->
  </div>
</template>
<style scoped>
  .searchbox .v-input.v-text-field .v-input__slot {
    min-height: 60px;
  }
  .searchbox .v-btn {
    min-height: 60px;
  }
  table.v-table tbody td,
  table.v-table tbody th {
    height: 40px;
  }

  table.v-table thead tr {
    height: 40px;
  }
  .scroll-container {
    scroll-padding: 50px 0 0 50px;
  }

  ::-webkit-scrollbar {
    width: 7px;
  }

  /* this targets the default scrollbar (compulsory) */

  ::-webkit-scrollbar-track {
    background-color: #73baf3;
  }

  /* the new scrollbar will have a flat appearance with the set background color */

  ::-webkit-scrollbar-thumb {
    background-color: #2196f3;
  }

  /* this will style the thumb, ignoring the track */

  ::-webkit-scrollbar-button {
    background-color: #0079da;
  }

  /* optionally, you can style the top and the bottom buttons (left and right for horizontal bars) */

  ::-webkit-scrollbar-corner {
    background-color: black;
  }
</style>
<script>
  // const { data } = require("./onePriceHeader.vue");

  module.exports = {
    components: {
      "one-dialog-print-label": httpVueLoader(
        "../../common/oneDialogPrintX.vue"
      ),
    },
    mounted() {
      this.$store.dispatch("resume/getsetup");
      // this.$store.dispatch("resume/getFitnessCategory");
    },
    methods: {
      closePrint() {
        this.openprint = false;
      },
      print() {
        let user = one_user();
        var d = new Date();
        var n = d.getTime();
        // https://cpone.aplikasi.web.id/birt/run?__report=report/one/rekap/rpt_mcu_patient_label.rptdesign&__format=pdf&PStartDate=2024-07-15&PEndDate=2024-07-15&PMcuID=61&PType=divisi&username=joko@gmail.com&tm=1721657764454
        let rptname = "rpt_mcu_patient_label";
        this.urlprint =
          "/birt/run?__report=report/one/rekap/" +
          rptname +
          ".rptdesign&__format=pdf" +
          "&PStartDate=" +
          this.startDate +
          "&PEndDate=" +
          this.endDate +
          "&PMcuID=" +
          this.selectedSetup.Mgm_McuID +
          "&PType=" +
          this.selectedLabelType.toLowerCase() +
          "&username=" +
          user.M_StaffName +
          "&tm=" +
          n;
        this.openprint = true;
      },
      formatDate(date) {
        if (!date) return null;

        const [year, month, day] = date.split("-");
        return `${day}-${month}-${year}`;
      },
      deFormatedDate(date) {
        if (!date) return null;

        const [day, month, year] = date.split("-");
        return `${year}-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
      },
      selectMe(val) {
        if (this.loading || this.loadingDetail || this.loadingSave) {
          this.snackbarWarning = true;
          this.warningMsg =
            "Loading sedang berlangsung, silahkan tunggu loading selesai untuk memilih pasien lain";
          return;
        }
        // this.loading = true;
        this.$store.commit("resume/reset_input");
        this.selectedPatient = val;
        let tmpDetail = this.patientDetail;
        tmpDetail.detail = [];
        this.patientDetail = tmpDetail;
        this.$store.dispatch("resume/getdetail");
      },
      isSelected(val) {
        return this.selectedPatient.orderID === val.orderID;
      },
    },
    computed: {
      errorMsg: {
        get() {
          return this.$store.state.resume.errorMsg;
        },
        set(val) {
          this.$store.commit("resume/update_errorMsg", val);
        },
      },
      successMsg: {
        get() {
          return this.$store.state.resume.successMsg;
        },
        set(val) {
          this.$store.commit("resume/update_successMsg", val);
        },
      },
      snackbarError: {
        get() {
          return this.$store.state.resume.snackbarError;
        },
        set(val) {
          this.$store.commit("resume/update_snackbarError", val);
        },
      },
      snackbarSuccess: {
        get() {
          return this.$store.state.resume.snackbarSuccess;
        },
        set(val) {
          this.$store.commit("resume/update_snackbarSuccess", val);
        },
      },
      setupList: {
        get() {
          return this.$store.state.resume.setupList;
        },
        set(val) {
          this.$store.commit("resume/update_setupList", val);
        },
      },
      selectedSetup: {
        get() {
          return this.$store.state.resume.selectedSetup;
        },
        set(val) {
          this.patientDetail = { lab: [], nonlab: [], fisik: [] };
          this.$store.commit("resume/update_selectedSetup", val);
          this.selectedPatient = {};
        },
      },
      startDate: {
        get() {
          return this.$store.state.resume.startDate;
        },
        set(val) {
          this.$store.commit("resume/update_startDate", val);
          this.selectedPatient = {};
          this.patientDetail = { lab: [], nonlab: [], fisik: [] };
          this.page = 1;
          if (!this.changeSetup) this.$store.dispatch("resume/search");
        },
      },
      endDate: {
        get() {
          return this.$store.state.resume.endDate;
        },
        set(val) {
          this.selectedPatient = {};
          this.patientDetail = { lab: [], nonlab: [], fisik: [] };
          this.page = 1;
          this.$store.commit("resume/update_endDate", val);
          if (!this.changeSetup) this.$store.dispatch("resume/search");
        },
      },
      patientDetail: {
        get() {
          return this.$store.state.resume.patientDetail;
        },
        set(val) {
          this.$store.commit("resume/update_patientDetail", val);
        },
      },
      search: {
        get() {
          return this.$store.state.resume.search;
        },
        set(val) {
          this.selectedPatient = {};
          this.patientDetail = { lab: [], nonlab: [], fisik: [] };
          this.page = 1;
          this.$store.commit("resume/update_search", val);
          if (!this.changeSetup) this.$store.dispatch("resume/search");
        },
      },
      patientList: {
        get() {
          return this.$store.state.resume.patientList;
        },
        set(val) {
          this.$store.commit("resume/update_patientList", val);
        },
      },
      selectedPatient: {
        get() {
          return this.$store.state.resume.selectedPatient;
        },
        set(val) {
          this.$store.commit("resume/update_selectedPatient", val);
        },
      },
      totalPage: {
        get() {
          return this.$store.state.resume.totalPage;
        },
        set(val) {
          this.$store.commit("resume/update_totalPage", val);
        },
      },
      page: {
        get() {
          return this.$store.state.resume.page;
        },
        set(val) {
          this.selectedPatient = {};
          this.patientDetail = { lab: [], nonlab: [], fisik: [] };
          this.$store.commit("resume/update_page", val);
          if (!this.changeSetup) this.$store.dispatch("resume/search");
        },
      },
      loading: {
        get() {
          return this.$store.state.resume.loading;
        },
        set(val) {
          this.$store.commit("resume/update_loading", val);
        },
      },
      loadingSave: {
        get() {
          return this.$store.state.resume.loadingSave;
        },
        set(val) {
          this.$store.commit("resume/update_loadingSave", val);
        },
      },
      loadingDetail: {
        get() {
          return this.$store.state.resume.loadingDetail;
        },
        set(val) {
          this.$store.commit("resume/update_loadingDetail", val);
        },
      },
      formatedStartDate() {
        return this.formatDate(this.startDate);
      },
      formatedEndDate() {
        return this.formatDate(this.endDate);
      },
    },
    watch: {
      selectedSetup(val, old) {
        this.changeSetup = true;
        this.page = 1;
        this.endDate = val.Mgm_McuEndDate;
        this.startDate = val.Mgm_McuStartDate;
        this.$store.dispatch("resume/search");
        this.changeSetup = false;
      },
      selectedLabelType(val, old) {
        let user = one_user();
        var d = new Date();
        var n = d.getTime();
        // https://cpone.aplikasi.web.id/birt/run?__report=report/one/rekap/rpt_mcu_patient_label.rptdesign&__format=pdf&PStartDate=2024-07-15&PEndDate=2024-07-15&PMcuID=61&PType=divisi&username=joko@gmail.com&tm=1721657764454
        let rptname = "rpt_mcu_patient_label";
        this.urlprint =
          "/birt/run?__report=report/one/rekap/" +
          rptname +
          ".rptdesign&__format=pdf" +
          "&PStartDate=" +
          this.startDate +
          "&PEndDate=" +
          this.endDate +
          "&PMcuID=" +
          this.selectedSetup.Mgm_McuID +
          "&PType=" +
          val.toLowerCase() +
          "&username=" +
          user.M_StaffName +
          "&tm=" +
          n;
      },
    },
    data() {
      return {
        menuFormDateStart: false,
        menuFormDateEnd: false,
        changeSetup: false,
        printtitle: "",
        printwidth: "80%",
        openprint: false,
        urlprint: "",
        selectedLabelType: "Departement",
        LabelTypeList: ["Departement", "Divisi"],
        snackbarWarning: false,
        warningMsg: "",
        headers: [
          {
            text: "NO REG",
            align: "left",
            sortable: false,
            value: "lab",
            width: "40%",
            class: "pa-2 blue lighten-3 white--text",
          },
          {
            text: "PASIEN",
            align: "left",
            sortable: false,
            value: "lab",
            width: "60%",
            class: "pa-2 blue lighten-3 white--text",
          },
        ],
      };
    },
  };
</script>
