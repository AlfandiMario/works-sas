<template>
  <div style="width: 100%;">
    <!-- SNACKBAR -->
    <v-snackbar
      v-model="snackbar"
      :timeout="5000"
      :multi-line="false"
      :vertical="false"
      :top="true"
    >
      {{ msgSnackbar }}
      <v-btn flat @click="updateAlertSuccess(false)"> Tutup </v-btn>
    </v-snackbar>

    <!-- ERROR DIALOG -->
    <v-dialog v-model="dialog_error" max-width="500px">
      <v-card>
        <v-card-title>
          <span>ERROR !</span>
          <v-spacer></v-spacer>
        </v-card-title>
        <v-divider></v-divider>
        <div class="ma-3 red--text">{{ msgError }}</div>
        <v-divider></v-divider>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" flat @click="dialog_error = false"
            >Tutup</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>
    <!-- END ERROR DIALOG -->

    <v-toolbar color="primary" dark>
      <v-toolbar-title class="white--text">MANUAL JURNAL</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn icon @click="openDialogAdd()">
        <v-icon>add_box</v-icon>
      </v-btn>
    </v-toolbar>
    <v-card class="pa-2">
      <v-layout row>
        <v-flex xs2 pl-2>
          <v-menu
            v-model="menufilterdatestart"
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
                v-model="filterComputedDateFormattedStart"
                class="mr-2"
                label="Tanggal Awal"
                outline
                hide-details
                readonly
                v-on="on"
                @blur="date = deFormatedDate(filterComputedDateFormattedStart)"
              ></v-text-field>
            </template>
            <v-date-picker
              no-title
              v-model="xdatestart"
              @input="menufilterdatestart = false"
            ></v-date-picker>
          </v-menu>
        </v-flex>
        <v-flex xs2 pl-2>
          <v-menu
            v-model="menufilterdateend"
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
                v-model="filterComputedDateFormattedEnd"
                class="mr-2"
                label="Tanggal Akhir"
                outline
                hide-details
                readonly
                v-on="on"
                @blur="date = deFormatedDate(filterComputedDateFormattedEnd)"
              ></v-text-field>
            </template>
            <v-date-picker
              no-title
              v-model="xdateend"
              @input="menufilterdateend = false"
            ></v-date-picker>
          </v-menu>
        </v-flex>
        <v-flex xs2 pl-2>
          <v-text-field
            v-model="xsearch"
            label="Cari"
            outline
            hide-details
          ></v-text-field>
        </v-flex>
        <v-flex pl-2>
          <v-btn
            class="ml-2"
            dark
            color="primary"
            style="min-width: 50px; height: 50px; margin: 0;"
            @click="searchTransaction()"
          >
            <v-icon>search</v-icon>
          </v-btn>
        </v-flex>
      </v-layout>
    </v-card>
    <v-card class="pa-2 mt-2">
      <v-layout
        row
        wrap
        class="scroll-container"
        style="max-height: 600px; overflow: auto;"
      >
        <v-flex xs12 pl-2 pr-2 pt-2 pb-2>
          <v-data-table
            :headers="headers"
            :items="xjurnals"
            :loading="isLoading"
            hide-actions
            class="elevation-1"
          >
            <template slot="items" slot-scope="props">
              <td
                class="text-xs-center pa-2"
                v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                @click="selectMe(props.item)"
              >
                {{ props.item.jurnalDate }}
              </td>
              <td
                class="text-xs-left pa-2"
                v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                @click="selectMe(props.item)"
              >
                <span class="font-weight-bold">
                  {{ props.item.jurnalNo }}
                </span>
                <br />
                {{ props.item.jurnalTitle }}
              </td>
              <td
                class="text-xs-left pa-2"
                v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                @click="selectMe(props.item)"
              >
                {{ props.item.S_RegionalName }}
              </td>
              <td
                class="text-xs-left pa-2"
                v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                @click="selectMe(props.item)"
              >
                {{ props.item.JurnalTypeName }}
              </td>
              <td
                class="text-xs-right pa-2"
                v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                @click="selectMe(props.item)"
              >
                <v-tooltip bottom>
                  <template v-slot:activator="{ on, attrs }">
                    <v-icon
                      v-bind="attrs"
                      v-on="on"
                      small
                      v-if="hideDelete(props.item)"
                      @click="openDeleteJurnal(props.item)"
                      >delete</v-icon
                    >
                  </template>
                  <span>Hapus</span>
                </v-tooltip>
                <v-tooltip bottom>
                  <template v-slot:activator="{ on, attrs }">
                    <v-icon
                      v-bind="attrs"
                      v-on="on"
                      small
                      @click="openEditFormJurnal(props.item)"
                      >edit</v-icon
                    >
                  </template>
                  <span>Edit</span>
                </v-tooltip>
                <v-tooltip bottom>
                  <template v-slot:activator="{ on, attrs }">
                    <v-icon
                      v-bind="attrs"
                      v-on="on"
                      color="red"
                      small
                      @click="doPrintVoucher(props.item)"
                      >picture_as_pdf</v-icon
                    >
                  </template>
                  <span>Voucher</span>
                </v-tooltip>
              </td>
            </template>
          </v-data-table>
        </v-flex>
      </v-layout>
      <v-divider></v-divider>
      <v-pagination
        style="margin-top: 10px; margin-bottom: 10px;"
        v-model="curr_page"
        :length="xtotal_page"
      ></v-pagination>
    </v-card>

    <template>
      <v-dialog v-model="dialogdeletealejurnal" max-width="30%">
        <v-card>
          <v-card-title class="headline grey lighten-2 pt-2 pb-2" primary-title>
            Peringatan !
          </v-card-title>
          <v-card-text class="pt-2 pb-2">
            <v-layout row>
              <v-flex xs12 d-flex>
                <v-layout row>
                  <v-flex pb-1 xs12>
                    <v-layout row>
                      <v-flex pt-2 pr-2 xs12>
                        {{ msgalertjurnal }}
                      </v-flex>
                    </v-layout>
                  </v-flex>
                </v-layout>
              </v-flex>
            </v-layout>
          </v-card-text>
          <v-divider></v-divider>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="error" flat @click="dialogdeletealejurnal = false">
              Tutup
            </v-btn>
            <v-btn
              :disabled="xis_posted === 'Y'"
              color="primary"
              @click="closeDeleteAlertJurnal()"
            >
              Ya
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </template>

    <template>
      <v-layout row justify-center>
        <v-dialog v-model="dialogjurnaltype" persistent max-width="550px">
          <v-toolbar dark class="blue lighten-3 white--text">
            <v-toolbar-title class="white--text"
              >PILIH JURNAL TYPE</v-toolbar-title
            >
            <v-spacer></v-spacer>
          </v-toolbar>
          <v-card>
            <v-card-text class="pt-4 pb-2">
              <v-layout wrap>
                <v-flex xs12>
                  <v-autocomplete
                    v-model="x_drop_tipe_jurnal"
                    :items="drop_tipe_jurnal"
                    hide-no-data
                    hide-selected
                    item-text="JurnalTypeName"
                    label="JURNAL TYPE"
                    hide-details
                    outline
                    return-object
                  ></v-autocomplete>
                </v-flex>
              </v-layout>
            </v-card-text>
            <v-card-actions class="pr-3">
              <v-spacer></v-spacer>
              <v-btn color="error" flat @click="closeDialogJurnaltype()"
                >TUTUP</v-btn
              >
              <v-btn color="primary" @click="chooseJurnaltype()">PILIH</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>
      </v-layout>
    </template>

    <!-- <template>
        <v-layout row justify-center>
          <v-dialog v-model="dialogItemForm" persistent max-width="750px">
            <v-card>
              <v-card-title class="headline grey lighten-2" primary-title>
                FORM ACCOUNT SALES
              </v-card-title>
              <v-card-text class="pt-0 pb-0">
                <v-form
                  ref="formAccountSales"
                  v-model="validationAccountSales"
                  lazy-validation
                >
                  <v-layout wrap>
                    <v-flex xs12>
                      <v-autocomplete
                        v-model="xCoaId"
                        :filter="accountFilter"
                        label="Account No"
                        menu-icon="mdi-chevron-down"
                        :items="coa"
                        item-text="coaDescription"
                        item-value="coaID"
                        clearable
                      ></v-autocomplete>
                    </v-flex>
                    <v-flex xs12>
                      <v-text-field
                        v-model="xSubGroupCode"
                        label="Kode Sub Group*"
                        counter
                        maxlength="2"
                        :rules="subGroupCodeRules"
                        required
                      ></v-text-field>
                    </v-flex>
                    <v-flex xs12>
                      <v-text-field
                        v-model="xSubGroupName"
                        label="Nama Sub Group*"
                        :rules="subGroupNameRules"
                        required
                      ></v-text-field>
                    </v-flex>
                    <v-flex xs12>
                      <v-autocomplete
                        v-model="xOmzetId"
                        label="Nama Tipe Omzet*"
                        menu-icon="mdi-chevron-down"
                        :items="omzet"
                        item-text="M_OmzetTypeName"
                        item-value="M_OmzetTypeID"
                        :rules="omzetIdRules"
                        required
                      ></v-autocomplete>
                    </v-flex>
                    <v-flex>
                      <p
                        v-for="(xError, idx) in xErrors"
                        class="error pl-2 pr-2"
                        style="color: #fff"
                      >
                        {{ xError.msg }}
                      </p>
                    </v-flex>
                  </v-layout>
                </v-form>
              </v-card-text>
              <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="error" flat @click="onClosedialogItemForm()"
                  >Tutup</v-btn
                >
                <v-btn
                  v-if="xAct === 'new'"
                  color="primary"
                  dark
                  @click="saveFormAccountSales()"
                  >Simpan</v-btn
                >
                <v-btn
                  v-if="xAct === 'edit'"
                  color="primary"
                  dark
                  @click="updateFormAccountSales()"
                  >Simpan Perubahan</v-btn
                >
              </v-card-actions>
            </v-card>
          </v-dialog>
        </v-layout>
      </template> -->

    <!-- <one-dialog-alert
        :status="dialogDeleteAlertAccount"
        :msg="msgAlertAccount"
        @forget-dialog-alert="dialogDeleteAlertAccount = false"
        @close-dialog-alert="closeDeleteAlertAccount()"
      ></one-dialog-alert> -->

    <!-- <one-dialog-error :msg="msgError"></one-dialog-error> -->
    <one-dialog-jurnal-umum></one-dialog-jurnal-umum>
    <dialog-jurnal-terimabarang></dialog-jurnal-terimabarang>
    <dialog-jurnal-gaji></dialog-jurnal-gaji>
    <dialog-pengeluaran-barang></dialog-pengeluaran-barang>
    <dialog-jurnal-kirim-barang-regional></dialog-jurnal-kirim-barang-regional>

    <template>
      <one-dialog-info
        :status="opendialoginfo"
        :msg="msginfo"
        @close-dialog-info="opendialoginfo = false"
      ></one-dialog-info>
    </template>

    <one-dialog-print
      :title="printtitle"
      :width="printwidth"
      :height="700"
      :status="openprint"
      :urlprint="urlprint"
      @close-dialog-print="openprint = false"
    ></one-dialog-print>
  </div>
</template>

<style scoped>
  table.v-table tbody td,
  table.v-table tbody th {
    height: 40px;
  }

  table.v-table thead tr {
    height: 40px;
  }

  .textinput {
    -webkit-transition: width 0.4s ease-in-out;
    transition: width 0.4s ease-in-out;
    background-color: white;
    background-position: 10px 10px;
    background-repeat: no-repeat;
    padding-left: 40px;
    width: 100%;
    padding: 8px 10px;
    margin-bottom: 5px;
    box-sizing: border-box;
    border: 1px solid #607d8b;
  }

  .textinput:focus {
    width: 100%;
  }

  .textinput:focus::-webkit-input-placeholder {
    color: transparent;
  }

  .textinput:focus::-moz-placeholder {
    color: transparent;
  }

  .textinput:-moz-placeholder {
    color: transparent;
  }

  .scroll-container {
    scroll-padding: 50px 0 0 50px;
  }
</style>

<script>
  module.exports = {
    components: {
      "one-dialog-info": httpVueLoader("../../common/oneDialogInfo.vue"),
      "one-dialog-alert": httpVueLoader("../../common/oneDialogAlert.vue"),
      "one-dialog-error": httpVueLoader("../../common/oneDialogError.vue"),
      "one-dialog-print": httpVueLoader("../../common/oneDialogPrintX.vue"),
      "one-dialog-jurnal-umum": httpVueLoader("./accountDialogJurnalUmum.vue"),
      "dialog-jurnal-terimabarang": httpVueLoader("./terimaBarangForm.vue"),
      "dialog-jurnal-gaji": httpVueLoader("./jurnalgaji.vue"),
      "dialog-pengeluaran-barang": httpVueLoader(
        "./pengeluaranBarangDialog.vue"
      ),
      "dialog-jurnal-kirim-barang-regional": httpVueLoader(
        "./JurnalPengirimanBarangRegional.vue"
      ),
    },
    data() {
      return {
        menufilterdatestart: false,
        menufilterdateend: false,
        userLogin: one_user(),
        date: new Date().toISOString().substr(0, 10),
        page: 1,
        xid: 0,
        xCoaId: null,
        xSubGroupCode: "",
        xSubGroupName: "",
        xOmzetId: null,
        subGroupCodeRules: [(v) => !!v || "Kode Sub Group harus diisi"],
        subGroupNameRules: [(v) => !!v || "Nama Sub Group harus diisi"],
        omzetIdRules: [(v) => !!v || "Nama Tipe Omzet harus diisi"],
        headers: [
          {
            text: "TANGGAL",
            align: "center",
            sortable: false,
            value: "date",
            width: "15%",
            class: "blue lighten-3 white--text",
          },
          {
            text: "JURNAL",
            align: "center",
            sortable: false,
            value: "jurnal",
            width: "30%",
            class: "blue lighten-3 white--text",
          },
          {
            text: "LOKASI",
            align: "center",
            sortable: false,
            value: "location",
            width: "20%",
            class: "blue lighten-3 white--text",
          },
          {
            text: "INFO",
            align: "center",
            sortable: false,
            value: "info",
            width: "30%",
            class: "blue lighten-3 white--text",
          },
          {
            text: "AKSI",
            align: "center",
            sortable: false,
            value: "status",
            width: "5%",
            class: "blue lighten-3 white--text",
          },
        ],
        validationAccountSales: false,
        msgAlertAccount: "",
        dialogDeleteAlertAccount: false,
        dialogdeletealejurnal: false,
        msgalertjurnal: "",
        xtitle: "",
        xdetail: [],
        xis_posted: "",
        formatreport: "pdf",
        urlprint: "",
        printtitle: "",
        printwidth: "100%",
      };
    },
    mounted() {
      this.$store.dispatch("jurnalumum/search", {
        regionalid: this.$store.state.jurnalumum.user.S_RegionalID,
        branchid: this.$store.state.jurnalumum.user.M_BranchID,
        current_page: this.$store.state.jurnalumum.current_page,
        search: this.$store.state.jurnalumum.x_search,
        startdate: this.$store.state.jurnalumum.start_date,
        enddate: this.$store.state.jurnalumum.end_date,
        last_id: -1,
      });

      this.$store.dispatch("jurnalumum/openjurnaltype");
    },
    computed: {
      xjurnals() {
        return this.$store.state.jurnalumum.jurnalumums;
      },
      isLoading() {
        return this.$store.state.jurnalumum.search_status === 1;
      },
      xsearch: {
        get() {
          return this.$store.state.jurnalumum.x_search;
        },
        set(val) {
          this.$store.commit("jurnalumum/update_x_search", val);
        },
      },
      xdatestart: {
        get() {
          return this.$store.state.jurnalumum.start_date;
        },
        set(val) {
          this.$store.commit("jurnalumum/update_start_date", val);
          this.$store.commit("jurnalumum/update_last_id", -1);
        },
      },
      xdateend: {
        get() {
          return this.$store.state.jurnalumum.end_date;
        },
        set(val) {
          this.$store.commit("jurnalumum/update_end_date", val);
          this.$store.commit("jurnalumum/update_last_id", -1);
        },
      },
      curr_page: {
        get() {
          return this.$store.state.jurnalumum.current_page;
        },
        set(val) {
          this.$store.commit("jurnalumum/update_current_page", val);
          this.$store.commit("jurnalumum/update_last_id", -1);
          this.searchTransaction();
        },
      },
      xtotal_page: {
        get() {
          return this.$store.state.jurnalumum.total_jurnalumum;
        },
        set(val) {
          this.$store.commit("jurnalumum/update_total_jurnalumum", val);
        },
      },
      filterComputedDateFormattedStart() {
        return this.formatDate(this.xdatestart);
      },
      filterComputedDateFormattedEnd() {
        return this.formatDate(this.xdateend);
      },
      opendialoginfo: {
        get() {
          return this.$store.state.jurnalumum.open_dialog_info;
        },
        set(val) {
          this.$store.commit("jurnalumum/update_open_dialog_info", false);
        },
      },
      msginfo: {
        get() {
          return this.$store.state.jurnalumum.msg_info;
        },
        set(val) {
          this.$store.commit("jurnalumum/update_msg_info", false);
        },
      },
      snackbar: {
        get() {
          return this.$store.state.jurnalumum.alert_success;
        },
        set(val) {
          this.$store.commit("jurnalumum/update_alert_success", val);
        },
      },
      msgSnackbar() {
        return this.$store.state.jurnalumum.msg_success;
      },
      dialog_error: {
        get() {
          return this.$store.state.jurnalumum.alert_error;
        },
        set(val) {
          this.$store.commit("jurnalumum/update_alert_error", val);
        },
      },
      msgError() {
        return this.$store.state.jurnalumum.save_error_message;
      },
      dialogjurnaltype: {
        get() {
          return this.$store.state.jurnalumum.dialogjurnaltype;
        },
        set(val) {
          this.$store.commit("jurnalumum/update_dialogjurnaltype", val);
        },
      },
      drop_tipe_jurnal() {
        return this.$store.state.jurnalumum.drop_tipe_jurnal;
      },
      x_drop_tipe_jurnal: {
        get() {
          return this.$store.state.jurnalumum.x_drop_tipe_jurnal;
        },
        set(val) {
          this.$store.commit("jurnalumum/update_x_drop_tipe_jurnal", val);
        },
      },
      idEditJurnalGaji: {
        get() {
          return this.$store.state.jurnalgaji.idEdit;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_idEdit", val);
        },
      },
      dialogTypeJurnalGaji: {
        get() {
          return this.$store.state.jurnalgaji.dialogType;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_dialogType", val);
        },
      },
      dialogFormJurnalGaji: {
        get() {
          return this.$store.state.jurnalgaji.dialogForm;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_dialogForm", val);
        },
      },
      dialogFormJpbreg: {
        get() {
          return this.$store.state.jpbreg.dialogForm;
        },
        set(val) {
          this.$store.commit("jpbreg/update_dialogForm", val);
        },
      },
      idEditJpbreg: {
        get() {
          return this.$store.state.jpbreg.idEdit;
        },
        set(val) {
          this.$store.commit("jpbreg/update_idEdit", val);
        },
      },
      openprint: {
        get() {
          return this.$store.state.jurnalumum.open_print;
        },
        set(val) {
          this.$store.commit("jurnalumum/update_open_print", val);
        },
      },
    },
    methods: {
      doPrintVoucher(val) {
        // console.log(val)
        this.printwidth = 1028;
        this.printtitle = "";
        let user = one_user();
        tm = Date.now();
        var rptname = "rpt_jurnal_001";
        var formatrpt = this.formatreport;

        // https://devone.aplikasi.web.id/birt/run?__report=report/one/accounting/rpt_jurnal_001.rptdesign&__format=pdf&PID=6&username=RIYAN%20AMINULAH%20PRAKOSO
        this.urlprint =
          "/birt/run?__report=report/one/accounting/" +
          rptname +
          ".rptdesign&__format=" +
          formatrpt +
          "&PID=" +
          val.id +
          "&username=" +
          user.M_StaffName +
          "&tm=" +
          tm;
        // console.log(this.urlprint)

        this.$store.commit("jurnalumum/update_open_print", true);
      },
      hideDelete(data) {
        if (data.JurnalTypeCode !== "SALARYREG") {
          return true;
        } else if (data.JurnalTypeCode === "SALARYREG") {
          if (this.userLogin.M_UserLocationFlag !== "R") {
            return false;
          } else if (
            this.userLogin.M_UserLocationFlag === "R" &&
            (data.M_BranchID === null || data.M_BranchID === 0)
          ) {
            return true;
          }
        } else {
          return true;
        }
      },
      openDialogJurnalGaji() {
        this.idEditJurnalGaji = 0;
        this.$store.commit("jurnalumum/update_dialogjurnaltype", false);
        this.dialogFormJurnalGaji = true;
      },
      openEditDialogJurnalGaji(id) {
        this.idEditJurnalGaji = id;
        this.$store.commit("jurnalumum/update_dialogjurnaltype", false);
        this.dialogFormJurnalGaji = true;
      },
      openDialogJurnalPengirimanRegional() {
        this.idEditJpbreg = 0;
        this.$store.commit("jurnalumum/update_dialogjurnaltype", false);
        this.dialogFormJpbreg = true;
      },
      openEditDialogJurnalPengirimanRegional(id) {
        this.idEditJpbreg = id;
        this.$store.commit("jurnalumum/update_dialogjurnaltype", false);
        this.dialogFormJpbreg = true;
      },
      isSelected(p) {
        return p.id == this.$store.state.jurnalumum.selected_jurnalumum.id;
      },
      selectMe(sc) {
        this.$store.commit("jurnalumum/update_selected_jurnalumum", sc);
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
      thr_search: _.debounce(function () {
        this.searchTransaction();
      }, 100),
      searchTransaction() {
        this.$store.dispatch("jurnalumum/search", {
          regionalid: this.$store.state.jurnalumum.user.S_RegionalID,
          branchid: this.$store.state.jurnalumum.user.M_BranchID,
          current_page: this.$store.state.jurnalumum.current_page,
          search: this.$store.state.jurnalumum.x_search,
          startdate: this.$store.state.jurnalumum.start_date,
          enddate: this.$store.state.jurnalumum.end_date,
          last_id: -1,
        });
      },
      openDialogAdd() {
        this.$store.commit("jurnalumum/update_dialogjurnaltype", true);
      },
      updateAlertSuccess(val) {
        this.$store.commit("jurnalumum/update_alert_success", val);
      },
      openEditFormJurnal(val) {
        // Check tipe jurnal
        if (val.JurnalTypeCode == "GOODRECEIVE") {
          this.$store.dispatch("terimaBarang/loadEditDialog", {
            jurnalID: val.id,
          });
          return;
        } else if (val.JurnalTypeCode == "GENERAL") {
          this.$store.commit("jurnalumum/update_dialog_is_active", true);
          this.$store.commit("jurnalumum/update_act", "edit");
          this.xid = val.id;
          this.$store.commit("jurnalumum/update_jurnaltypes", [
            {
              JurnalTypeID: val.JurnalTypeID,
              JurnalTypeName: val.JurnalTypeName,
            },
          ]);
          this.$store.commit("jurnalumum/update_selected_jurnaltype", {
            JurnalTypeID: val.JurnalTypeID,
            JurnalTypeName: val.JurnalTypeName,
          });
          this.$store.commit(
            "jurnalumum/update_xdate",
            this.deFormatedDate(val.jurnalDate)
          );
          this.$store.commit("jurnalumum/update_periodes", [
            {
              periodeID: val.periodeID,
              periodeName: val.periodeName,
            },
          ]);
          this.$store.commit("jurnalumum/update_selected_periode", {
            periodeID: val.periodeID,
            periodeName: val.periodeName,
            periodeStartDate: val.periodeStartDate,
            periodeEndDate: val.periodeEndDate,
          });
          this.$store.commit(
            "jurnalumum/update_periodeStartDate",
            val.periodeStartDate
          );
          this.$store.commit(
            "jurnalumum/update_periodeEndDate",
            val.periodeEndDate
          );
          this.$store.commit("jurnalumum/update_title", val.jurnalTitle);
          this.$store.commit(
            "jurnalumum/update_deskripsi",
            val.jurnalDescription
          );
          this.$store.commit("jurnalumum/update_jurnaldetails", val.detail);

          let totalDebit = 0;
          let totalKredit = 0;
          for (let item = 0; item < val.detail.length; item++) {
            const element = val.detail[item];
            totalDebit += parseFloat(element.debit);
            totalKredit += parseFloat(element.credit);
          }

          let totalBalance = totalDebit - totalKredit;
          let summary = {
            debit: totalDebit,
            credit: totalKredit,
            balance: totalBalance,
          };
          this.$store.commit("jurnalumum/update_xsummary", summary);
          this.$store.commit("jurnalumum/update_is_posted", val.jurnalIsPosted);
        } else if (val.JurnalTypeCode == "SALARYREG") {
          this.openEditDialogJurnalGaji(val.id);
        } else if (val.JurnalTypeCode == "SENDGOODR") {
          this.openEditDialogJurnalPengirimanRegional(val.id);
        } else if (val.JurnalTypeCode == "OUTGOODB") {
          this.$store.dispatch("pengeluaranbarang/edit_jurnal", {jurnalid: val.id})
        }
      },
      openDeleteJurnal(val) {
        console.log(val);
        this.xid = val.id;
        this.xtitle = val.jurnalTitle;
        this.xdetail = val.detail;
        this.xis_posted = val.jurnalIsPosted;

        this.msgalertjurnal =
          "Yakin, mau hapus jurnal [" +
          val.jurnalNo +
          " - " +
          val.jurnalTitle +
          "] ?";
        this.dialogdeletealejurnal = true;
      },
      closeDeleteAlertJurnal() {
        let prm = {
          id: this.xid,
          title: this.xtitle,
          detailjurnal: this.xdetail,
          current_page: this.$store.state.jurnalumum.current_page,
          search: this.$store.state.jurnalumum.x_search,
          startdate: this.$store.state.jurnalumum.start_date,
          enddate: this.$store.state.jurnalumum.end_date,
          last_id: -1,
        };
        this.dialogdeletealejurnal = false;

        if (
          this.$store.state.jurnalumum.selected_jurnalumum.JurnalTypeCode ===
          "SALARYREG"
        ) {
          this.$store.dispatch("jurnalgaji/deleteJurnalgaji", prm);
        } else if (
          this.$store.state.jurnalumum.selected_jurnalumum.JurnalTypeCode ===
          "SENDGOODR"
        ) {
          this.$store.dispatch("jpbreg/deleteJurnal", prm);
        } else {
          this.$store.dispatch("jurnalumum/deletejurnalumum", prm);
        }
      },
      closeDialogJurnaltype() {
        this.$store.commit("jurnalumum/update_dialogjurnaltype", false);
        this.$store.commit("jurnalumum/update_x_drop_tipe_jurnal", {});
      },
      chooseJurnaltype() {
        if (
          this.$store.state.jurnalumum.x_drop_tipe_jurnal.JurnalTypeCode ===
          "GENERAL"
        ) {
          this.$store.commit("jurnalumum/update_dialog_is_active", true);
          this.$store.commit("jurnalumum/update_act", "add");
          this.$store.commit("jurnalumum/update_periodeStartDate", null);
          this.$store.commit("jurnalumum/update_periodeEndDate", null);
          this.$store.commit("jurnalumum/update_dialogjurnaltype", false);
        } else if (
          this.$store.state.jurnalumum.x_drop_tipe_jurnal.JurnalTypeCode ===
          "GOODRECEIVE"
        ) {
          this.$store.commit(
            "terimaBarang/update_dialogJurnalTerimaBarang",
            true
          );
          this.$store.commit("terimaBarang/update_actForm", "add");
          this.$store.commit("jurnalumum/update_dialogjurnaltype", false);
        } else if (
          this.$store.state.jurnalumum.x_drop_tipe_jurnal.JurnalTypeCode ===
            "SALARYREG" &&
          this.$store.state.jurnalumum.user.M_UserLocationFlag === "R"
        ) {
          this.openDialogJurnalGaji();
        } else if (
          this.$store.state.jurnalumum.x_drop_tipe_jurnal.JurnalTypeCode ===
            "SALARYREG" &&
          this.$store.state.jurnalumum.user.M_UserLocationFlag !== "R"
        ) {
          let msg =
            "Form jurnal type " +
            this.$store.state.jurnalumum.x_drop_tipe_jurnal.JurnalTypeName +
            " Hanya dapat diakses akun regional";
          this.$store.commit("jurnalumum/update_msg_info", msg);
          this.$store.commit("jurnalumum/update_open_dialog_info", true);
        } else if (
          this.$store.state.jurnalumum.x_drop_tipe_jurnal.JurnalTypeCode ===
          "OUTGOODB"
        ) {
          this.$store.dispatch("pengeluaranbarang/add_jurnal")
          this.$store.commit("jurnalumum/update_dialogjurnaltype", false);
        } else if (
          this.$store.state.jurnalumum.x_drop_tipe_jurnal.JurnalTypeCode ===
            "SENDGOODR" &&
          this.$store.state.jurnalumum.user.M_UserLocationFlag === "R"
        ) {
          this.openDialogJurnalPengirimanRegional();
        } else if (
          this.$store.state.jurnalumum.x_drop_tipe_jurnal.JurnalTypeCode ===
            "SENDGOODR" &&
          this.$store.state.jurnalumum.user.M_UserLocationFlag !== "R"
        ) {
          let msg =
            "Form jurnal type " +
            this.$store.state.jurnalumum.x_drop_tipe_jurnal.JurnalTypeName +
            " Hanya dapat diakses akun regional";
          this.$store.commit("jurnalumum/update_msg_info", msg);
          this.$store.commit("jurnalumum/update_open_dialog_info", true);
        } else if (
          Object.keys(this.$store.state.jurnalumum.x_drop_tipe_jurnal)
            .length === 0
        ) {
          let msg =
            "Anda belum memilih jurnal type. Silahkan pilih jurnal type dulu!!!";
          this.$store.commit("jurnalumum/update_msg_info", msg);
          this.$store.commit("jurnalumum/update_open_dialog_info", true);
        } else {
          let msg =
            "Form jurnal type " +
            this.$store.state.jurnalumum.x_drop_tipe_jurnal.JurnalTypeName +
            " belum ada";
          this.$store.commit("jurnalumum/update_msg_info", msg);
          this.$store.commit("jurnalumum/update_open_dialog_info", true);
        }
      },
    },
    watch: {
      xsearch(val, old) {
        this.xsearch = val;
        this.thr_search();
      },
      xdatestart(val, old) {
        this.xdatestart = val;
        this.thr_search();
      },
      xdateend(val, old) {
        this.xdateend = val;
        this.thr_search();
      },
    },
  };
</script>
