<template>
  <div>
    <v-snackbar
      :color="snackbar.color"
      v-model="snackbar.state"
      :timeout="5000"
      top
    >
      {{ snackbar.msg }}
      <v-btn flat @click="snackbar.state = false">
        Close
      </v-btn>
    </v-snackbar>
    <v-dialog v-model="dialogAddDetail" persistent width="500">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          FORM DETAIL
        </v-card-title>

        <v-card-text>
          <v-autocomplete
            :search-input.sync="searchCoaDetail"
            v-model="selectedCoaDetail"
            :items="coaListDetail"
            :loading="loadingAutocomplete"
            hide-no-data
            hide-selected
            class="mb-3"
            item-text="display"
            label="ACCOUNT"
            hide-details
            outline
            return-object
          ></v-autocomplete>
          <v-text-field
            v-model="debetDetail"
            label="DEBET"
            hide-details
            outline
            class="mb-3"
            type="number"
          ></v-text-field>
          <v-text-field
            v-model="kreditDetail"
            label="KREDIT"
            hide-details
            outline
            class="mb-3"
            type="number"
          ></v-text-field>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" flat @click="closeDialogAddDetail()">
            TUTUP
          </v-btn>
          <v-btn
            color="primary"
            v-if="dialogDetailEditAct==='add'"
            flat
            @click="addDetailJurnalSendBarang()"
          >
            TAMBAHKAN
          </v-btn>
          <v-btn
            color="primary"
            v-if="dialogDetailEditAct==='edit'"
            flat
            @click="editDetailJurnalGaji()"
          >
            EDIT
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialogForm" persistent width="70vw">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          FORM JURNAL PENGIRIMAN BARANG REGIONAL <v-spacer></v-spacer>
          <kbd>{{ jurnalNumber }}</kbd>
        </v-card-title>

        <v-card-text>
          <v-layout row wrap>
            <v-flex xs6 class="px-1 mb-2">
              <v-text-field
                label="COMPANY"
                v-model="user.M_BranchCompanyName"
                readonly
                hide-details
                outline
              ></v-text-field>
            </v-flex>
            <v-flex xs6 class="px-1 mb-2">
              <v-autocomplete
                v-model="selectedJurnalType"
                :items="jurnalTypeList"
                hide-no-data
                hide-selected
                item-text="JurnalTypeName"
                label="JURNAL TYPE"
                hide-details
                readonly
                outline
                return-object
              ></v-autocomplete>
            </v-flex>
            <v-flex xs6 class="px-1 mb-2">
              <v-text-field
                label="REGIONAL"
                v-model="user.S_RegionalName"
                readonly
                hide-details
                outline
              ></v-text-field>
            </v-flex>
            <v-flex xs3 class="px-1 mb-2">
              <v-menu
                v-model="menuFormSelectedDate"
                :close-on-content-click="false"
                :nudge-right="40"
                lazy
                :disabled="disableForm()"
                transition="scale-transition"
                offset-y
                full-width
                max-width="290px"
                min-width="290px"
              >
                <template v-slot:activator="{ on }">
                  <v-text-field
                    class="mr-2"
                    v-model="formatedSelectedDate"
                    label="TANGGAL"
                    outline
                    hide-details
                    readonly
                    v-on="on"
                    @blur="deFormatedDate(formatedSelectedDate)"
                  ></v-text-field>
                </template>
                <!-- :allowed-dates="disablePastDates" -->
                <v-date-picker
                  v-model="selectedDate"
                  no-title
                  @input="menuFormSelectedDate = false"
                ></v-date-picker>
              </v-menu>
            </v-flex>
            <v-flex xs3 class="px-1 mb-2">
              <v-autocomplete
                :search-input.sync="searchPeriode"
                v-model="selectedPeriode"
                :items="periodeList"
                :loading="loadingAutocomplete"
                hide-no-data
                hide-selected
                item-text="display"
                label="PERIODE"
                hide-details
                :readonly="disableForm()"
                outline
                return-object
              ></v-autocomplete>
            </v-flex>
            <v-flex xs6 class="px-1 mb-2">
              <v-autocomplete
                v-model="selectedBranchDetail"
                :items="defaultBranch"
                :loading="loadingAutocomplete"
                hide-no-data
                :readonly="disableForm()"
                class="mb-3"
                hide-selected
                item-text="branchName"
                label="Cabang"
                hide-details
                outline
                return-object
              ></v-autocomplete>
            </v-flex>
            <v-flex xs6 class="px-1 mb-2">
              <v-text-field
                label="JUDUL"
                v-model="title"
                :readonly="disableForm()"
                hide-details
                outline
              ></v-text-field>
            </v-flex>
            <v-flex xs12 class="px-1 mb-2">
              <v-text-field
                v-model="description"
                label="DESKRIPSI"
                :readonly="disableForm()"
                hide-details
                outline
              ></v-text-field>
            </v-flex>
            <v-flex xs12 class="px-1 mb-1 text-xs-right">
              <v-btn
                color="info"
                :disabled="disableBtnAddDetail()"
                @click="openDialogAddDetail()"
                >TAMBAH</v-btn
              >
            </v-flex>
            <v-flex xs12 class="px-1 mb-2 mt-2">
              <!-- {{ detailJurnalGaji }} -->
              <v-data-table
                hide-actions
                :headers="headers"
                :items="detailJurnalGaji"
                class="elevation-1"
              >
                <!-- :items="desserts" -->
                <template v-slot:items="props">
                  <td class="px-1">{{ props.item.coaNo }}</td>
                  <td class="px-1">{{ props.item.description }}</td>
                  <td class="px-1">{{ formatCurrency(props.item.debet) }}</td>
                  <td class="px-1">{{ formatCurrency(props.item.kredit) }}</td>
                  <td class="px-1 text-xs-center">
                    <div v-if="!disableForm()">
                      <v-btn
                        @click="openDialogEditDetail(props.item)"
                        small
                        flat
                        icon
                        color="info"
                        class="mr-2"
                      >
                        <v-icon small>edit</v-icon>
                      </v-btn>
                      <v-btn
                        @click="deleteDetailJurnalGaji(props.item)"
                        small
                        flat
                        icon
                        color="error"
                      >
                        <v-icon small>delete</v-icon>
                      </v-btn>
                    </div>
                  </td>
                </template>
                <template v-slot:footer>
                  <tr>
                    <td colspan="2" class="font-weight-bold px-1">Total</td>

                    <td class="font-weight-bold px-1">
                      {{ formatCurrency(balance.debet) }}
                    </td>
                    <td class="font-weight-bold px-1">
                      {{ formatCurrency(balance.kredit) }}
                    </td>
                    <td class="font-weight-bold px-1"></td>
                  </tr>
                  <tr>
                    <td colspan="3" class="font-weight-bold px-1">
                      Balance
                    </td>
                    <td colspan="2">
                      <div class="d-flex text-right font-weight-bold px-1">
                        <v-spacer></v-spacer>
                        {{ formatCurrency(balance.balance) }}
                      </div>
                    </td>
                  </tr>
                </template>
              </v-data-table>
            </v-flex>
          </v-layout>
          <v-progress-linear
            v-if="loading"
            class="p-0"
            :indeterminate="loading"
          ></v-progress-linear>
          <v-divider></v-divider>
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" :disabled="loading" flat @click="closeDialog()">
            TUTUP
          </v-btn>
          <v-btn
            :loading="loading"
            color="success"
            :disabled="loading"
            flat
            v-if="idEdit === 0 "
            @click="saveJurnalGaji()"
          >
            SIMPAN
          </v-btn>
          <v-btn
            :loading="loading"
            color="success"
            :disabled="loading"
            flat
            v-if="idEdit !== 0 && isEdit === 'Y'"
            @click="editJurnal()"
          >
            SIMPAN PERUBAHAN
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<style scoped></style>

<script>
  module.exports = {
    name: "JurnalGaji",
    props: {
      show: {
        type: Boolean,
        default: false,
      },
    },
    components: {},
    mounted() {
      console.log("Ini mounted dialog");
    },
    data: () => ({
      loadingCsv: false,
      menuFormSelectedDate: false,
      menuFormDateEnd: false,
      act: "",
      alertMsg: "",
      dialogDetailEditAct: "add",
      selectedDetail: {},
      headers: [
        {
          text: "AKUN",
          align: "left",
          sortable: false,
          value: "mr",
          width: "15%",
          class: "pa-2 blue lighten-3 white--text",
        },
        {
          text: "DESKRIPSI",
          align: "left",
          sortable: false,
          value: "lab",
          width: "25%",
          class: "pa-2 blue lighten-3 white--text",
        },
        {
          text: "DEBET",
          align: "left",
          sortable: false,
          value: "lab",
          width: "15%",
          class: "pa-2 blue lighten-3 white--text",
        },
        {
          text: "KREDIT",
          align: "left",
          sortable: false,
          value: "lab",
          width: "15%",
          class: "pa-2  blue lighten-3 white--text",
        },
        {
          text: "AKSI",
          align: "left",
          sortable: false,
          value: "lab",
          width: "10%",
          class: "pa-2  blue lighten-3 white--text",
        },
      ],
    }),
    computed: {
      dialogLocal: {
        get() {
          return this.show;
        },
        set(value) {
          this.$emit("update:show", value);
        },
      },
      balanceRegional: {
        get() {
          return this.$store.state.jpbreg.balanceRegional;
        },
        set(val) {
          this.$store.commit("jpbreg/update_balanceRegional", val);
        },
      },
      balance: {
        get() {
          return this.$store.state.jpbreg.balance;
        },
        set(val) {
          this.$store.commit("jpbreg/update_balance", val);
        },
      },
      dialogForm: {
        get() {
          return this.$store.state.jpbreg.dialogForm;
        },
        set(val) {
          this.$store.commit("jpbreg/update_dialogForm", val);
        },
      },
      selectedBranchPercent: {
        get() {
          return this.$store.state.jpbreg.selectedBranchPercent;
        },
        set(val) {
          this.$store.commit("jpbreg/update_selectedBranchPercent", val);
          //   this.generateDetail();
        },
      },
      dialogAddDetail: {
        get() {
          return this.$store.state.jpbreg.dialogAddDetail;
        },
        set(val) {
          this.$store.commit("jpbreg/update_dialogAddDetail", val);
        },
      },
      loading: {
        get() {
          return this.$store.state.jpbreg.loading;
        },
        set(val) {
          this.$store.commit("jpbreg/update_loading", val);
        },
      },
      detailJurnalGaji: {
        get() {
          return this.$store.state.jpbreg.detailJurnalGaji;
        },
        set(val) {
          this.$store.commit("jpbreg/update_detailJurnalGaji", val);
        },
      },
      detailJurnalGajiRegional: {
        get() {
          return this.$store.state.jpbreg.detailJurnalGajiRegional;
        },
        set(val) {
          this.$store.commit("jpbreg/update_detailJurnalGajiRegional", val);
        },
      },
      defaultBranch: {
        get() {
          return this.$store.state.jpbreg.defaultBranch;
        },
        set(val) {
          this.$store.commit("jpbreg/update_defaultBranch", val);
        },
      },
      sallary: {
        get() {
          return this.$store.state.jpbreg.sallary;
        },
        set(val) {
          this.$store.commit("jpbreg/update_sallary", val);
          //   this.generateDetail();
        },
      },
      description: {
        get() {
          return this.$store.state.jpbreg.description;
        },
        set(val) {
          this.$store.commit("jpbreg/update_description", val);
        },
      },
      title: {
        get() {
          return this.$store.state.jpbreg.title;
        },
        set(val) {
          this.$store.commit("jpbreg/update_title", val);
        },
      },
      selectedDate: {
        get() {
          return this.$store.state.jpbreg.selectedDate;
        },
        set(val) {
          this.$store.commit("jpbreg/update_selectedDate", val);
        },
      },
      selectedJurnalType: {
        get() {
          return this.$store.state.jpbreg.selectedJurnalType;
        },
        set(val) {
          this.$store.commit("jpbreg/update_selectedJurnalType", val);
        },
      },
      loadingAutocomplete: {
        get() {
          return this.$store.state.jpbreg.loadingAutocomplete;
        },
        set(val) {
          this.$store.commit("jpbreg/update_loadingAutocomplete", val);
        },
      },
      selectedPeriode: {
        get() {
          return this.$store.state.jpbreg.selectedPeriode;
        },
        set(val) {
          this.$store.commit("jpbreg/update_selectedPeriode", val);
        },
      },
      searchPeriode: {
        get() {
          return this.$store.state.jpbreg.searchPeriode;
        },
        set(val) {
          this.$store.commit("jpbreg/update_searchPeriode", val);
        },
      },
      selectedCoa: {
        get() {
          return this.$store.state.jpbreg.selectedCoa;
        },
        set(val) {
          this.$store.commit("jpbreg/update_selectedCoa", val);
          //   this.generateDetail();
        },
      },
      debetDetail: {
        get() {
          return this.$store.state.jpbreg.debetDetail;
        },
        set(val) {
          this.$store.commit("jpbreg/update_debetDetail", val);
        },
      },
      kreditDetail: {
        get() {
          return this.$store.state.jpbreg.kreditDetail;
        },
        set(val) {
          this.$store.commit("jpbreg/update_kreditDetail", val);
        },
      },
      selectedBranchDetail: {
        get() {
          return this.$store.state.jpbreg.selectedBranchDetail;
        },
        set(val) {
          this.$store.commit("jpbreg/update_selectedBranchDetail", val);
          this.selectedCoaDetail = {};
          this.coaListDetail = [];
          this.searchCoaDetail = "";
        },
      },
      searchCoa: {
        get() {
          return this.$store.state.jpbreg.searchCoa;
        },
        set(val) {
          this.$store.commit("jpbreg/update_searchCoa", val);
        },
      },
      selectedCoaDetail: {
        get() {
          return this.$store.state.jpbreg.selectedCoaDetail;
        },
        set(val) {
          this.$store.commit("jpbreg/update_selectedCoaDetail", val);
        },
      },
      searchCoaDetail: {
        get() {
          return this.$store.state.jpbreg.searchCoaDetail;
        },
        set(val) {
          this.$store.commit("jpbreg/update_searchCoaDetail", val);
        },
      },
      coaListDetail: {
        get() {
          return this.$store.state.jpbreg.coaListDetail;
        },
        set(val) {
          this.$store.commit("jpbreg/update_coaListDetail", val);
        },
      },
      selectedCoaKas: {
        get() {
          return this.$store.state.jpbreg.selectedCoaKas;
        },
        set(val) {
          this.$store.commit("jpbreg/update_selectedCoaKas", val);
        },
      },
      searchCoaKas: {
        get() {
          return this.$store.state.jpbreg.searchCoaKas;
        },
        set(val) {
          this.$store.commit("jpbreg/update_searchCoaKas", val);
        },
      },
      coaListKas: {
        get() {
          return this.$store.state.jpbreg.coaListKas;
        },
        set(val) {
          this.$store.commit("jpbreg/update_coaListKas", val);
        },
      },
      snackbar: {
        get() {
          return this.$store.state.jpbreg.snackbar;
        },
        set(val) {
          this.$store.commit("jpbreg/update_snackbar", val);
        },
      },
      branchPercentList() {
        return this.$store.state.jpbreg.branchPercentList;
      },
      //   coaListDetail() {
      //     return this.$store.state.jpbreg.coaListDetail;
      //   },
      coaList() {
        return this.$store.state.jpbreg.coaList;
      },
      periodeList() {
        return this.$store.state.jpbreg.periodeList;
      },
      jurnalTypeList() {
        return this.$store.state.jpbreg.jurnalTypeList;
      },
      defaultJurnalType() {
        return this.$store.state.jpbreg.defaultJurnalType;
      },
      user() {
        return this.$store.state.jpbreg.user;
      },
      formatedSelectedDate() {
        return this.formatDate(this.selectedDate);
      },
      idEdit: {
        get() {
          return this.$store.state.jpbreg.idEdit;
        },
        set(val) {
          this.$store.commit("jpbreg/update_idEdit", val);
        },
      },
      dialogType: {
        get() {
          return this.$store.state.jpbreg.dialogType;
        },
        set(val) {
          this.$store.commit("jpbreg/update_dialogType", val);
        },
      },
      isEdit: {
        get() {
          return this.$store.state.jpbreg.isEdit;
        },
        set(val) {
          this.$store.commit("jpbreg/update_isEdit", val);
        },
      },
      jurnalNumber: {
        get() {
          return this.$store.state.jpbreg.jurnalNumber;
        },
        set(val) {
          this.$store.commit("jpbreg/update_jurnalNumber", val);
        },
      },
    },
    methods: {
      disableForm() {
        return this.loading || this.isEdit === "N";
      },
      disableBtnAddDetail() {
        return (
          this.loading ||
          this.isEdit === "N" ||
          (this.detailJurnalGaji.length > 0 && this.balance.balance == 0)
        );
      },
      countSummary() {
        let kredit = 0;
        let debet = 0;
        this.detailJurnalGaji.forEach((e) => {
          if (e.type === "D") {
            debet = parseFloat(e.debet) + debet;
          }
          if (e.type === "K") {
            kredit = parseFloat(e.kredit) + kredit;
          }
        });
        let total = debet - kredit;

        let summary = {
          debet: debet,
          kredit: kredit,
          balance: total,
        };
        this.balance = summary;
      },
      countSummaryRegional() {
        let kredit = 0;
        let debet = 0;
        this.detailJurnalGajiRegional.forEach((e) => {
          if (e.type === "D") {
            debet = parseFloat(e.debet) + debet;
          }
          if (e.type === "K") {
            kredit = parseFloat(e.kredit) + kredit;
          }
        });
        let total = debet - kredit;

        let summary = {
          debet: debet,
          kredit: kredit,
          balance: total,
        };
        this.balanceRegional = summary;
      },
      formatCurrency(value) {
        // console.log(value);
        if (
          value === null ||
          value === undefined ||
          value === "" ||
          value == NaN
        )
          return "";
        return (
          "Rp " +
          parseFloat(value).toLocaleString("id-ID", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          })
        );
      },
      generateRandomId() {
        return (
          "id-" +
          Math.random().toString(36).substr(2, 9) +
          "-" +
          Date.now().toString(36)
        );
      },
      isObjectEmpty(obj) {
        return Object.keys(obj).length === 0 && obj.constructor === Object;
      },
      openDialogAddDetail() {
        this.dialogDetailEditAct = "add";
        this.dialogAddDetail = true;
      },
      closeDialogAddDetail() {
        this.coaListDetail = [];
        this.selectedCoaDetail = {};
        this.kreditDetail = 0;

        this.debetDetail = 0;
        this.dialogAddDetail = false;
      },
      openDialogEditDetail(data) {
        // data = {
        //       tmpID: this.generateRandomId(),
        //       id: "0",
        //       branchID: this.selectedBranchDetail.branchID,
        //       branchName: this.selectedBranchDetail.branchName,
        //       description: coa.keterangan,
        //       debet: 0,
        //       type: "K",
        //       dataType: "N",
        //       kredit: this.kreditDetail,
        //       precentage: 0,
        //       coaID: coa.id,
        //       coaName: coa.keterangan,
        //       coaNo: coa.number,
        //     }
        this.dialogDetailEditAct = "edit";
        this.selectedDetail = data;

        this.selectedCoaDetail = {
          display: data.coaNo + " - " + data.coaName,
          id: data.coaID,
          keterangan: data.coaName,
          number: data.coaNo,
        };
        this.searchCoaDetail = data.coaNo + " - " + data.coaName;
        this.kreditDetail = data.kredit;
        this.debetDetail = data.debet;
        this.dialogAddDetail = true;
      },
      deleteDetailJurnalGaji(data) {
        // for (let i = 0; i < tmpList.length; i++) {
        //   const e = tmpList[i];
        //   if (e.id === data.id) {
        //     console.log("DATA FOUND");
        //     tmpList[i] = data;
        //   }
        // }
        let tmpList = this.detailJurnalGaji;

        tmpList = tmpList.filter((e) => {
          return e.id !== data.id;
        });

        // tmpList.push(data);
        // tmpList.sort((a, b) => a.branchName.localeCompare(b.branchName));
        let tmp = JSON.stringify(tmpList);
        this.detailJurnalGaji = JSON.parse(tmp);
        this.closeDialogAddDetail();
        this.countSummary();
      },
      editDetailJurnalGaji() {
        if (this.isObjectEmpty(this.selectedCoaDetail)) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Pilih account terlebih dahulu",
          };
          this.snackbar = snackbar;
          return;
        }

        if (this.debetDetail > 0 && this.kreditDetail > 0) {
          let snackbar = {
            state: true,
            color: "warning",
            msg:
              "Debet dan kredit tidak boleh lebih besar dari 0 (pilih salah satu antara debet atau kredit)",
          };
          this.snackbar = snackbar;
          return;
        }
        if (this.debetDetail <= 0 && this.kreditDetail <= 0) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Debet dan kredit masih 0, isi salah satu",
          };
          this.snackbar = snackbar;
          return;
        }
        var coa = this.selectedCoaDetail;

        var selectedDetail = this.selectedDetail;
        let data = {};

        let type = "D";
        if (this.debetDetail > 0) {
          type = "D";
        } else {
          type = "K";
        }

        data = {
          id: selectedDetail.id,
          branchID: this.selectedBranchDetail.branchID,
          branchName: this.selectedBranchDetail.branchName,
          description: coa.keterangan,
          debet: this.debetDetail,
          type: type,
          dataType: selectedDetail.dataType,
          kredit: this.kreditDetail,
          precentage: 0,
          coaID: coa.id,
          coaName: coa.keterangan,
          coaNo: coa.number,
        };

        let debet = 0;
        let kredit = 0;

        this.detailJurnalGaji.forEach((element) => {
          if (element.type === "K" && element.id != data.id) {
            kredit = kredit + parseFloat(element.kredit);
          }
          if (element.type === "D") {
            debet = debet + parseFloat(element.debet);
          }
        });
        kredit = kredit + parseFloat(data.kredit);
        console.log("debet :" + debet);
        console.log("kredit :" + kredit);

        if (parseFloat(kredit) > parseFloat(debet)) {
          alert(
            "total kredit tidak boleh lebih besar dari total depet di cabang " +
              data.branchName
          );
          return;
        }

        let tmpList = this.detailJurnalGaji;
        // debugger;
        console.log("BEFORE");
        console.log(tmpList);

        for (let i = 0; i < tmpList.length; i++) {
          const e = tmpList[i];
          if (e.id === data.id) {
            console.log("DATA FOUND");
            tmpList[i] = data;
          }
        }
        console.log("AFTER");
        console.log(tmpList);
        // tmpList.push(data);
        // tmpList.sort((a, b) => a.branchName.localeCompare(b.branchName));
        let tmp = JSON.stringify(tmpList);
        this.detailJurnalGaji = JSON.parse(tmp);
        this.closeDialogAddDetail();
        this.countSummary();
      },
      addDetailJurnalSendBarang() {
        if (this.isObjectEmpty(this.selectedCoaDetail)) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Pilih account terlebih dahulu",
          };
          this.snackbar = snackbar;
          return;
        }

        if (this.debetDetail > 0 && this.kreditDetail > 0) {
          let snackbar = {
            state: true,
            color: "warning",
            msg:
              "Debet dan kredit tidak boleh lebih besar dari 0 (pilih salah satu antara debet atau kredit)",
          };
          this.snackbar = snackbar;
          return;
        }
        if (this.debetDetail <= 0 && this.kreditDetail <= 0) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Debet dan kredit masih 0, isi salah satu",
          };
          this.snackbar = snackbar;
          return;
        }
        var coa = this.selectedCoaDetail;

        let type = "D";
        if (this.debetDetail > 0) {
          type = "D";
        } else {
          type = "K";
        }

        let data = {};

        data = {
          id: this.generateRandomId(),
          branchID: this.selectedBranchDetail.branchID,
          branchName: this.selectedBranchDetail.branchName,
          description: coa.keterangan,
          debet: this.debetDetail,
          type: type,
          dataType: "N",
          kredit: this.kreditDetail,
          precentage: 0,
          coaID: coa.id,
          coaName: coa.keterangan,
          coaNo: coa.number,
        };

        let debet = 0;
        let kredit = 0;

        this.detailJurnalGaji.forEach((element) => {
          if (element.type === "K") {
            kredit = kredit + parseFloat(element.kredit);
          }
          if (element.type === "D") {
            debet = debet + parseFloat(element.debet);
          }
        });
        kredit = kredit + parseFloat(data.kredit);
        console.log("debet :" + debet);
        console.log("kredit :" + kredit);

        if (parseFloat(kredit) > parseFloat(debet)) {
          //   alert("total kredit tidak boleh lebih besar dari total debet ");
          let snackbar = {
            state: true,
            color: "warning",
            msg: "total kredit tidak boleh lebih besar dari total debet ",
          };
          this.snackbar = snackbar;
          return;
        }

        let tmpList = this.detailJurnalGaji;
        tmpList.push(data);
        // tmpList.sort((a, b) => a.branchName.localeCompare(b.branchName));
        this.detailJurnalGaji = tmpList;
        this.closeDialogAddDetail();
        this.countSummary();
      },
      generateDetail() {
        if (this.isObjectEmpty(this.selectedCoa)) return;
        if (this.isObjectEmpty(this.selectedCoaKas)) return;
        if (this.isObjectEmpty(this.selectedBranchPercent)) return;
        if (this.sallary <= 0) return;

        // let data = {
        //   branchID: "",
        //   branchName: "",
        //   description: "",
        //   debet: "",
        //   kredit: "",
        //   precentage: 0,
        //   coaID: "",
        //   coaName: "",
        //   coaNo: "",
        // };

        var sallaryReg = parseFloat(this.sallary);
        var coa = this.selectedCoa;
        var branch = this.defaultBranch;
        var branchPercent = this.selectedBranchPercent;
        let detail = [];
        branch.forEach((e, index) => {
          branchPercent.detail.forEach((p) => {
            if (e.branchID === p.BranchPercentDetailM_BranchID) {
              let dbt =
                (parseInt(p.BranchPercentDetailValue) / 100) * sallaryReg;

              detail.push({
                id: this.generateRandomId(),
                branchID: e.branchID,
                branchName: e.branchName,
                description: coa.keterangan,
                debet: dbt,
                kredit: 0,
                precentage: 0,
                type: "D",
                dataType: "N",
                coaID: coa.id,
                coaName: coa.keterangan,
                coaNo: coa.number,
              });
            }
          });
        });
        detail.sort((a, b) => a.branchName.localeCompare(b.branchName));
        let detailRegional = [];
        branch.forEach((e, index) => {
          branchPercent.detail.forEach((p) => {
            if (e.branchID === p.BranchPercentDetailM_BranchID) {
              let dbt =
                (parseInt(p.BranchPercentDetailValue) / 100) * sallaryReg;
              detailRegional.push({
                id: this.generateRandomId(),
                branchID: e.branchID,
                branchName: e.branchName,
                description: p.coaDescription,
                debet: dbt,
                kredit: 0,
                precentage: 0,
                type: "D",
                dataType: "N",
                coaID: p.coaID,
                coaName: p.coaDescription,
                coaNo: p.coaAccountNo,
              });
            }
          });
        });
        detailRegional.sort((a, b) => a.branchName.localeCompare(b.branchName));
        this.detailJurnalGajiRegional = [
          {
            id: this.generateRandomId(),
            branchID: "",
            branchName: "",
            description: this.selectedCoaKas.keterangan,
            debet: 0,
            kredit: this.sallary,
            precentage: 0,
            type: "K",
            dataType: "N",
            coaID: this.selectedCoaKas.id,
            coaName: this.selectedCoaKas.keterangan,
            coaNo: this.selectedCoaKas.number,
          },
          ...detailRegional,
        ];

        this.detailJurnalGaji = detail;
        this.countSummary();
        this.countSummaryRegional();
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
      handleDialogOpen() {
        console.log("Dialog telah dibuka");
      },
      closeDialog() {
        this.selectedPeriode = {};
        this.selectedCoa = {};
        this.selectedCoaKas = {};
        this.selectedBranchPercent = {};
        this.selectedBranchDetail = {};
        this.jurnalNumber = "";

        this.detailJurnalGaji = [];
        this.detailJurnalGajiRegional = [];
        this.balance = {
          debet: 0,
          kredit: 0,
          balance: 0,
        };
        this.sallary = 0;
        this.searchCoa = "";
        this.searchCoaKas = "";
        this.title = "";
        this.description = "";
        this.isEdit = "Y";
        console.log("close dialogs");
        this.$emit("update:show", false);
        this.dialogForm = false;
      },
      saveJurnalGaji() {
        let message = [];
        if (this.isObjectEmpty(this.selectedPeriode)) {
          message.push("Pilih periode terlebih dahulu");
        }

        if (this.isObjectEmpty(this.selectedBranchDetail)) {
          message.push("Pilih cabang terlebih dahulu");
        }

        if (this.title === "") {
          message.push("Isi judul terlebih dahulu");
        }

        if (this.balance.balance > 0) {
          message.push("jumlah tidak balance");
        }
        if (message.length > 0) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: message.join(", "),
          };
          this.snackbar = snackbar;
          return;
        }
        if (this.detailJurnalGaji.length === 0) return;

        let header = {
          companyID: this.user.M_BranchCompanyID,
          regionalID: this.user.S_RegionalID,
          jurnalTypeID: this.selectedJurnalType.JurnalTypeID,
          date: this.selectedDate,
          periodeID: this.selectedPeriode.periodeID,
          title: this.title,
          branchName: this.selectedBranchDetail.branchName,
          branchCode: this.selectedBranchDetail.branchCode,
          branchID: this.selectedBranchDetail.branchID,

          description: this.description,

          detail: this.detailJurnalGaji,
        };
        this.$store.dispatch("jpbreg/saveJurnal", header);
        console.log(header);
      },
      editJurnal() {
        let message = [];
        if (this.isObjectEmpty(this.selectedPeriode)) {
          message.push("Pilih periode terlebih dahulu");
        }

        if (this.isObjectEmpty(this.selectedBranchDetail)) {
          message.push("Pilih cabang terlebih dahulu");
        }

        if (this.title === "") {
          message.push("Isi judul terlebih dahulu");
        }

        if (this.balance.balance > 0) {
          message.push("jumlah tidak balance");
        }
        if (message.length > 0) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: message.join(", "),
          };
          this.snackbar = snackbar;
          return;
        }
        if (this.detailJurnalGaji.length === 0) return;

        let header = {
          id: this.idEdit,
          companyID: this.user.M_BranchCompanyID,
          regionalID: this.user.S_RegionalID,
          jurnalTypeID: this.selectedJurnalType.JurnalTypeID,
          date: this.selectedDate,
          periodeID: this.selectedPeriode.periodeID,
          title: this.title,
          branchName: this.selectedBranchDetail.branchName,
          branchCode: this.selectedBranchDetail.branchCode,
          branchID: this.selectedBranchDetail.branchID,

          description: this.description,

          detail: this.detailJurnalGaji,
        };
        this.$store.dispatch("jpbreg/editJurnal", header);
        console.log(header);
      },
    },
    watch: {
      searchPeriode(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.$store.dispatch("jpbreg/getPeriode");
      },
      searchCoaDetail(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.$store.dispatch("jpbreg/getCoaDetail");
      },
      searchCoa(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.$store.dispatch("jpbreg/getCoa");
      },
      searchCoaKas(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.$store.dispatch("jpbreg/getCoaKas");
      },
      async dialogForm(val, old) {
        if (val) {
          await this.$store.dispatch("jpbreg/getJurnalType");
          await this.$store.dispatch("jpbreg/getDefaultBranch");
          if (this.idEdit !== 0) {
            await this.$store.dispatch("jpbreg/getDetail", {
              id: this.idEdit,
            });
          } else {
            this.dialogType = "N";
          }
        } else {
          console.log("Dialog jurnal gaji  close ");
        }
      },
      show(newVal) {
        if (newVal) {
          this.handleDialogOpen();
        }
      },
    },
  };
</script>
