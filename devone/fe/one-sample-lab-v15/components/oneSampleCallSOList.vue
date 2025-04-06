<template>
  <v-layout class="fill-height" column>
    <v-dialog v-model="dialogsuccess" persistent max-width="290">
      <v-card>
        <v-card-title color="success" class="headline">Berhasil !</v-card-title>
        <v-card-text>
          {{ msgsuccess }}
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="green darken-1" flat @click="closeDialogSuccess"
            >OK</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-card>
      <v-layout row>
        <v-flex xs12 pl-2 pr-2 pt-2 pb-2>
          <v-data-table
            :headers="headers"
            :items="patients"
            :loading="isLoading"
            hide-actions
            class="elevation-1 fixed_header"
          >
            <template slot="items" slot-scope="props">
              <td
                style="width: 10%"
                class="text-xs-center pa-2"
                v-bind:class="{
                  'amber lighten-4': isSelected(props.item),
                  'teal white--text': props.item.coming === 'Y',
                  amber: props.item.iscito === 'Y',
                }"
                @click="selectMe(props.item)"
              >
                {{ format_date(props.item.T_OrderHeaderDate) }}
              </td>
              <td
                style="width: 10%"
                class="text-xs-center pa-2"
                v-bind:class="{
                  'amber lighten-4': isSelected(props.item),
                  'teal white--text': props.item.coming === 'Y',
                  amber: props.item.iscito === 'Y',
                }"
                @click="selectMe(props.item)"
              >
                <p class="mb-0 font-weight-black caption">
                  {{ props.item.T_OrderHeaderLabNumber }}
                </p>
                <p style="color: #800000" class="mb-0 font-weight-bold caption">
                  {{ props.item.T_OrderHeaderLabNumberExt }}
                </p>
              </td>
              <td
                style="width: 15%"
                class="text-xs-center pa-2"
                v-bind:class="{
                  'amber lighten-4': isSelected(props.item),
                  'teal white--text': props.item.coming === 'Y',
                  amber: props.item.iscito === 'Y',
                }"
                @click="selectMe(props.item)"
              >
                {{ props.item.M_CompanyName }}
              </td>
              <td
                style="width: 20%"
                class="text-xs-center pa-2"
                v-bind:class="{
                  'amber lighten-4': isSelected(props.item),
                  'teal white--text': props.item.coming === 'Y',
                  amber: props.item.iscito === 'Y',
                }"
                @click="selectMe(props.item)"
              >
                {{ props.item.patient_fullname }}
              </td>
              <td
                style="width: 10%"
                class="text-xs-center pa-2"
                v-bind:class="{
                  'amber lighten-4': isSelected(props.item),
                  'teal white--text': props.item.coming === 'Y',
                  amber: props.item.iscito === 'Y',
                }"
                @click="selectMe(props.item)"
              >
                {{ props.item.status }}
              </td>
            </template>
          </v-data-table>
        </v-flex>
      </v-layout>
    </v-card>
    <one-dialog-info
      :status="opendialoginfo"
      :msg="msginfo"
      @close-dialog-info="closeDialogInfo()"
    ></one-dialog-info>
    <one-dialog-alert
      :status="openalertconfirmation"
      :msg="msgalertconfirmation"
      @forget-dialog-alert="forgetAlertConfirmation()"
      @close-dialog-alert="closeAlertConfirmation()"
    ></one-dialog-alert>
  </v-layout>
</template>

<style scoped>
.searchbox .v-input.v-text-field .v-input__slot {
  min-height: 60px;
}
.searchbox .v-btn {
  min-height: 60px;
}
table,
td,
th {
  border: 0.5px solid rgba(0, 0, 0, 0.12);
  text-align: left;
}

table {
  border-collapse: collapse;
  width: 100%;
}
table.v-table tbody td,
table.v-table tbody th {
  height: 40px;
}

table.v-table thead tr {
  height: 40px;
}

.fixed_header tbody {
  display: block;
  max-height: 630px;
  overflow-y: scroll;
}

.fixed_header thead,
tbody tr {
  display: table;
  width: 100%;
  table-layout: fixed;
}
</style>

<script>
module.exports = {
  components: {
    "one-dialog-info": httpVueLoader("../../common/oneDialogInfo.vue"),
    "one-dialog-alert": httpVueLoader("../../common/oneDialogAlert.vue"),
  },
  mounted() {
    //this.$store.dispatch("samplecall/getinitdata")
    this.$store.dispatch("samplecall/getstationstatus", {
      xdate: this.$store.state.samplecall.start_date,
      name: this.name,
      nolab: this.nolab,
      stationid: this.xselectedstation.id,
      statusid: this.xselectedstatus.id,
      companyid: this.selected_company.id,
      // current_page:1,
      lastid: -1,
    });
  },
  methods: {
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
    format_date(d) {
      return moment(d).format("DD.MM.YYYY");
    },
    isSelected(p) {
      if (p.coming === "Y" || p.iscito === "Y") return false;
      else
        return (
          p.T_OrderHeaderID ==
          this.$store.state.samplecall.selected_patient.T_OrderHeaderID
        );
    },
    searchPatient() {
      this.$store.commit("samplecall/update_last_id", -1);
      this.$store.dispatch("samplecall/search", {
        xdate: this.$store.state.samplecall.start_date,
        name: this.name,
        nolab: this.nolab,
        stationid: this.xselectedstation.id,
        statusid: this.xselectedstatus.id,
        companyid: this.selected_company.id,
        locationid: this.selected_location.locationID,
        //current_page:1,
        lastid: -1,
      });
    },
    selectMe(pat) {
      if (this.$store.state.samplecall.no_save == 0) {
        var patients = this.$store.state.samplecall.patients;
        this.$store.commit("samplecall/update_selected_patient", pat);
        var idx = _.findIndex(patients, function (o) {
          return o.T_OrderHeaderID == pat.T_OrderHeaderID;
        });
        this.$store.commit("samplecall/update_last_id", idx);
        this.$store.dispatch("samplecall/getsampletypes", {
          orderid: pat.T_OrderHeaderID,
          stationid: pat.T_SampleStationID,
          statusid: pat.statusid,
        });
      } else {
        this.$store.commit("samplecall/update_open_alert_confirmation", true);
      }
    },
    closeAlertConfirmation() {
      this.$store.commit("samplecall/update_open_alert_confirmation", false);
    },
    forgetAlertConfirmation() {
      this.$store.commit("samplecall/update_no_save", 0);
      this.$store.commit("samplecall/update_open_alert_confirmation", false);
    },
    updateAlert_success(val) {
      this.$store.commit("samplecall/update_alert_success", val);
    },
    setNewPatient() {},
    closeDialogSuccess() {
      let arrPatient = this.$store.state.samplecall.patients;
      var idx = _.findIndex(
        arrPatient,
        (item) => item.M_PatientID === this.$store.state.samplecall.last_id
      );
      console.log(idx);
      var xcur_page = 1;

      this.$store.dispatch("samplecall/search", {
        name: this.name,
        nolab: this.nolab,
        stationid: this.xselectedstation.id,
        statusid: this.xselectedstatus.id,
        locationid: this.selected_location.locationID,
        lastid: idx,
      });

      this.$store.commit("samplecall/update_dialog_success", false);
    },
    closeDialogInfo() {
      this.$store.commit("samplecall/update_open_dialog_info", false);
    },
    thr_search_company: _.debounce(function () {
      this.$store.dispatch("samplecall/searchcompany", this.search_company);
    }, 2000),
  },
  computed: {
    xdatestart: {
      get() {
        return this.$store.state.samplecall.start_date;
      },
      set(val) {
        this.$store.commit("samplecall/update_start_date", val);
        //this.searchTransaction()
      },
    },
    filterComputedDateFormattedStart() {
      return this.formatDate(this.xdatestart);
    },
    dialogsuccess: {
      get() {
        return this.$store.state.samplecall.dialog_success;
      },
      set(val) {
        this.$store.commit("samplecall/update_dialog_success", val);
      },
    },
    msgsuccess() {
      return this.$store.state.samplecall.msg_success;
    },
    snackbar: {
      get() {
        return this.$store.state.samplecall.alert_success;
      },
      set(val) {
        this.$store.commit("samplecall/update_alert_success", val);
      },
    },
    isLoading() {
      return this.$store.state.samplecall.search_status == 1;
    },
    xstations() {
      return this.$store.state.samplecall.stations;
    },
    xselectedstation: {
      get() {
        return this.$store.state.samplecall.selected_station;
      },
      set(val) {
        this.$store.commit("samplecall/update_selected_station", val);
        this.$store.dispatch("samplecall/getLocation", val.id);
        // this.searchPatient()
      },
    },
    xstatuses() {
      return this.$store.state.samplecall.statuses;
    },
    xselectedstatus: {
      get() {
        return this.$store.state.samplecall.selected_status;
      },
      set(val) {
        this.$store.commit("samplecall/update_selected_status", val);
        // this.searchPatient()
      },
    },
    patients() {
      return this.$store.state.samplecall.patients;
    },
    openalertconfirmation: {
      get() {
        return this.$store.state.samplecall.open_alert_confirmation;
      },
      set(val) {
        this.$store.commit("samplecall/update_open_alert_confirmation", val);
      },
    },
    curr_page: {
      get() {
        return this.$store.state.samplecall.current_page;
      },
      set(val) {
        this.$store.commit("samplecall/update_current_page", val);
        this.$store.dispatch("samplecall/search", {
          name: this.name,
          nolab: this.nolab,
          stationid: this.xselectedstation.id,
          statusid: this.xselectedstatus.id,
          current_page: val,
          locationid: this.selected_location.locationID,
          lastid: idx,
        });
      },
    },
    xtotal_page: {
      get() {
        return this.$store.state.samplecall.total_page;
      },
      set(val) {
        this.$store.commit("samplecall/update_total_page", val);
      },
    },
    opendialoginfo: {
      get() {
        return this.$store.state.samplecall.open_dialog_info;
      },
      set(val) {
        this.$store.commit("samplecall/update_open_dialog_info", false);
      },
    },
    msginfo() {
      return this.$store.state.samplecall.msg_info;
    },
    name: {
      get() {
        return this.$store.state.samplecall.name;
      },
      set(val) {
        this.$store.commit("samplecall/update_name", val);
      },
    },
    nolab: {
      get() {
        return this.$store.state.samplecall.nolab;
      },
      set(val) {
        this.$store.commit("samplecall/update_nolab", val);
        //this.searchPatient()
      },
    },
    xcompanies() {
      return this.$store.state.samplecall.companies;
    },
    selected_company: {
      get() {
        return this.$store.state.samplecall.selected_company;
      },
      set(val) {
        this.$store.commit("samplecall/update_selected_company", val);
        // this.searchPatient()
      },
    },
    selected_location: {
      get() {
        return this.$store.state.samplecall.selected_location;
      },
      set(val) {
        this.$store.commit("samplecall/update_selected_location", val);
        // this.searchPatient()
      },
    },
    locations() {
      return this.$store.state.samplecall.locations;
    },
  },
  watch: {
    search_company(val, old) {
      if (val == old) return;
      if (!val) return;
      if (val.length < 1) return;
      if (this.$store.state.samplecall.update_autocomplete_status == 1) return;
      this.thr_search_company();
    },
  },
  data() {
    return {
      msgalertconfirmation:
        "Perubahan yang telah dilakukan belum disimpan dong !",
      items: [],
      search_company: "",
      menufilterdatestart: false,
      //isLoading: false,
      page: 1,
      headers: [
        {
          text: "TANGGAL",
          align: "center",
          sortable: false,
          value: "mr",
          width: "10%",
          class: "pa-2 blue lighten-3 white--text",
        },
        {
          text: "NO REG",
          align: "center",
          sortable: false,
          value: "mr",
          width: "10%",
          class: "pa-2 blue lighten-3 white--text",
        },
        {
          text: "KEL. PELANGGAN",
          align: "center",
          sortable: false,
          value: "lab",
          width: "15%",
          class: "pa-2 blue lighten-3 white--text",
        },
        {
          text: "NAMA",
          align: "center",
          sortable: false,
          value: "name",
          width: "20%",
          class: "pa-2 blue lighten-3 white--text",
        },

        {
          text: "STATUS",
          align: "center",
          sortable: false,
          value: "status",
          width: "10%",
          class: "pa-2 blue lighten-3 white--text",
        },
      ],
    };
  },
};
</script>
