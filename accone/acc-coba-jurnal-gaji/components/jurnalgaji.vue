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
            v-model="selectedBranchDetail"
            :items="defaultBranch"
            :loading="loadingAutocomplete"
            hide-no-data
            class="mb-3"
            hide-selected
            item-text="branchName"
            label="Cabang"
            hide-details
            outline
            return-object
          ></v-autocomplete>
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
            @click="addDetailJurnalGaji()"
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
          FORM JURNAL GAJI REGIONAL
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
            <!-- <v-flex xs6 class="px-1 mb-2">
              <v-text-field
                label="BRANCH"
                hide-details
                disabled
                outline
              ></v-text-field>
            </v-flex> -->
            <v-flex xs6 class="px-1 mb-2">
              <v-text-field
                label="JUDUL"
                v-model="title"
                :readonly="disableForm()"
                hide-details
                outline
              ></v-text-field>
            </v-flex>
            <v-flex xs6 class="px-1 mb-2">
              <v-autocomplete
                :search-input.sync="searchCoa"
                v-model="selectedCoa"
                :items="coaList"
                :loading="loadingAutocomplete"
                hide-no-data
                :readonly="disableForm()"
                hide-selected
                item-text="display"
                label="ACCOUNT"
                hide-details
                outline
                return-object
              ></v-autocomplete>
            </v-flex>
            <v-flex xs6 class="px-1 mb-2">
              <v-autocomplete
                :search-input.sync="searchCoaKas"
                v-model="selectedCoaKas"
                :items="coaListKas"
                :loading="loadingAutocomplete"
                hide-no-data
                :readonly="disableForm()"
                hide-selected
                item-text="display"
                v-if="dialogType === 'N' || dialogType === 'R'"
                label="ACCOUNT BANK"
                hide-details
                outline
                return-object
              ></v-autocomplete>
            </v-flex>
            <v-flex xs6 class="px-1 mb-2">
              <v-text-field
                v-model="sallary"
                label="JUMLAH GAJI REGIONAL PER PT"
                v-if="dialogType === 'N' || dialogType === 'R'"
                hide-details
                outline
                :readonly="disableForm()"
                type="number"
              ></v-text-field>
            </v-flex>

            <v-flex xs6 class="px-1 mb-2">
              <v-autocomplete
                v-model="selectedBranchPercent"
                :items="branchPercentList"
                hide-no-data
                :readonly="disableForm()"
                hide-selected
                item-text="BranchPercentType"
                label="PRESENTASE CABANG"
                v-if="dialogType === 'N' || dialogType === 'R'"
                hide-details
                outline
                return-object
              ></v-autocomplete>
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
            <v-flex
              xs12
              class="px-1 mb-2 mt-2 text-xs-center"
              v-if="detailJurnalGajiRegional.length > 0"
            >
              <p class="title p-0">JURNAL REGIONAL</p>
            </v-flex>
            <v-flex
              xs12
              class="px-1 mb-2 mt-2"
              v-if="detailJurnalGajiRegional.length > 0"
            >
              <!-- {{ detailJurnalGaji }} -->
              <v-data-table
                hide-actions
                :headers="headers"
                :items="detailJurnalGajiRegional"
                class="elevation-1"
              >
                <!-- :items="desserts" -->
                <template v-slot:items="props">
                  <td class="px-1">{{ props.item.branchName }}</td>
                  <td class="px-1">{{ props.item.coaNo }}</td>
                  <td class="px-1">{{ props.item.description }}</td>
                  <td class="px-1">{{ formatCurrency(props.item.debet) }}</td>
                  <td class="px-1">{{ formatCurrency(props.item.kredit) }}</td>
                  <td class="px-1 text-xs-center">
                    <!-- <div v-if="props.item.type === 'K'">
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
                    </div> -->
                  </td>
                </template>
                <template v-slot:footer>
                  <tr>
                    <td colspan="3" class="font-weight-bold px-1">Total</td>

                    <td class="font-weight-bold px-1">
                      {{ formatCurrency(balanceRegional.debet) }}
                    </td>
                    <td class="font-weight-bold px-1">
                      {{ formatCurrency(balanceRegional.kredit) }}
                    </td>
                    <td class="font-weight-bold px-1"></td>
                  </tr>
                  <tr>
                    <td colspan="4" class="font-weight-bold px-1">
                      Balance
                    </td>
                    <td colspan="2">
                      <div class="d-flex text-right font-weight-bold px-1">
                        <v-spacer></v-spacer>
                        {{ formatCurrency(balanceRegional.balance) }}
                      </div>
                    </td>
                  </tr>
                </template>
              </v-data-table>
            </v-flex>
            <v-flex
              xs12
              class="px-1 mb-2 mt-2 text-xs-center"
              v-if="detailJurnalGaji.length > 0"
            >
              <p class="title p-0">JURNAL CABANG</p>
            </v-flex>
            <v-flex
              xs12
              class="px-1 mb-1 text-xs-right"
              v-if="detailJurnalGaji.length > 0"
            >
              <v-btn
                color="info"
                v-if="dialogType === 'N'"
                :disabled="detailJurnalGaji.length === 0 || disableForm() || balance.balance === 0"
                @click="openDialogAddDetail()"
                >TAMBAH</v-btn
              >
            </v-flex>
            <v-flex
              xs12
              class="px-1 mb-2 mt-2"
              v-if="detailJurnalGaji.length > 0"
            >
              <!-- {{ detailJurnalGaji }} -->
              <v-data-table
                hide-actions
                :headers="headers"
                :items="detailJurnalGaji"
                class="elevation-1"
              >
                <!-- :items="desserts" -->
                <template v-slot:items="props">
                  <td class="px-1">{{ props.item.branchName }}</td>
                  <td class="px-1">{{ props.item.coaNo }}</td>
                  <td class="px-1">{{ props.item.description }}</td>
                  <td class="px-1">{{ formatCurrency(props.item.debet) }}</td>
                  <td class="px-1">{{ formatCurrency(props.item.kredit) }}</td>
                  <td class="px-1 text-xs-center">
                    <div v-if="props.item.type === 'K' && !disableForm()">
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
                    <td colspan="3" class="font-weight-bold px-1">Total</td>

                    <td class="font-weight-bold px-1">
                      {{ formatCurrency(balance.debet) }}
                    </td>
                    <td class="font-weight-bold px-1">
                      {{ formatCurrency(balance.kredit) }}
                    </td>
                    <td class="font-weight-bold px-1"></td>
                  </tr>
                  <tr>
                    <td colspan="4" class="font-weight-bold px-1">
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
            v-if="dialogType === 'N'"
            @click="saveJurnalGaji()"
          >
            SIMPAN
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
          text: "CABANG",
          align: "left",
          sortable: false,
          value: "action",
          width: "15%",
          class: "pa-2 pl-2 blue lighten-3 white--text",
        },
        {
          text: "AKUN",
          align: "left",
          sortable: false,
          value: "mr",
          width: "10%",
          class: "pa-2 blue lighten-3 white--text",
        },
        {
          text: "DESKRIPSI",
          align: "left",
          sortable: false,
          value: "lab",
          width: "20%",
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
          return this.$store.state.jurnalgaji.balanceRegional;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_balanceRegional", val);
        },
      },
      balance: {
        get() {
          return this.$store.state.jurnalgaji.balance;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_balance", val);
        },
      },
      dialogForm: {
        get() {
          return this.$store.state.jurnalgaji.dialogForm;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_dialogForm", val);
        },
      },
      selectedBranchPercent: {
        get() {
          return this.$store.state.jurnalgaji.selectedBranchPercent;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_selectedBranchPercent", val);
          this.generateDetail();
        },
      },
      dialogAddDetail: {
        get() {
          return this.$store.state.jurnalgaji.dialogAddDetail;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_dialogAddDetail", val);
        },
      },
      loading: {
        get() {
          return this.$store.state.jurnalgaji.loading;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_loading", val);
        },
      },
      detailJurnalGaji: {
        get() {
          return this.$store.state.jurnalgaji.detailJurnalGaji;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_detailJurnalGaji", val);
        },
      },
      detailJurnalGajiRegional: {
        get() {
          return this.$store.state.jurnalgaji.detailJurnalGajiRegional;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_detailJurnalGajiRegional", val);
        },
      },
      defaultBranch: {
        get() {
          return this.$store.state.jurnalgaji.defaultBranch;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_defaultBranch", val);
        },
      },
      sallary: {
        get() {
          return this.$store.state.jurnalgaji.sallary;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_sallary", val);
          this.generateDetail();
        },
      },
      description: {
        get() {
          return this.$store.state.jurnalgaji.description;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_description", val);
        },
      },
      title: {
        get() {
          return this.$store.state.jurnalgaji.title;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_title", val);
        },
      },
      selectedDate: {
        get() {
          return this.$store.state.jurnalgaji.selectedDate;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_selectedDate", val);
        },
      },
      selectedJurnalType: {
        get() {
          return this.$store.state.jurnalgaji.selectedJurnalType;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_selectedJurnalType", val);
        },
      },
      loadingAutocomplete: {
        get() {
          return this.$store.state.jurnalgaji.loadingAutocomplete;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_loadingAutocomplete", val);
        },
      },
      selectedPeriode: {
        get() {
          return this.$store.state.jurnalgaji.selectedPeriode;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_selectedPeriode", val);
        },
      },
      searchPeriode: {
        get() {
          return this.$store.state.jurnalgaji.searchPeriode;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_searchPeriode", val);
        },
      },
      selectedCoa: {
        get() {
          return this.$store.state.jurnalgaji.selectedCoa;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_selectedCoa", val);
          this.generateDetail();
        },
      },
      kreditDetail: {
        get() {
          return this.$store.state.jurnalgaji.kreditDetail;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_kreditDetail", val);
        },
      },
      selectedBranchDetail: {
        get() {
          return this.$store.state.jurnalgaji.selectedBranchDetail;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_selectedBranchDetail", val);
          this.selectedCoaDetail = {};
          this.coaListDetail = [];
          this.searchCoaDetail = "";
        },
      },
      searchCoa: {
        get() {
          return this.$store.state.jurnalgaji.searchCoa;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_searchCoa", val);
        },
      },
      selectedCoaDetail: {
        get() {
          return this.$store.state.jurnalgaji.selectedCoaDetail;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_selectedCoaDetail", val);
        },
      },
      searchCoaDetail: {
        get() {
          return this.$store.state.jurnalgaji.searchCoaDetail;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_searchCoaDetail", val);
        },
      },
      coaListDetail: {
        get() {
          return this.$store.state.jurnalgaji.coaListDetail;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_coaListDetail", val);
        },
      },
      selectedCoaKas: {
        get() {
          return this.$store.state.jurnalgaji.selectedCoaKas;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_selectedCoaKas", val);
        },
      },
      searchCoaKas: {
        get() {
          return this.$store.state.jurnalgaji.searchCoaKas;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_searchCoaKas", val);
        },
      },
      coaListKas: {
        get() {
          return this.$store.state.jurnalgaji.coaListKas;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_coaListKas", val);
        },
      },
      snackbar: {
        get() {
          return this.$store.state.jurnalgaji.snackbar;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_snackbar", val);
        },
      },
      branchPercentList() {
        return this.$store.state.jurnalgaji.branchPercentList;
      },
      //   coaListDetail() {
      //     return this.$store.state.jurnalgaji.coaListDetail;
      //   },
      coaList() {
        return this.$store.state.jurnalgaji.coaList;
      },
      periodeList() {
        return this.$store.state.jurnalgaji.periodeList;
      },
      jurnalTypeList() {
        return this.$store.state.jurnalgaji.jurnalTypeList;
      },
      defaultJurnalType() {
        return this.$store.state.jurnalgaji.defaultJurnalType;
      },
      user() {
        return this.$store.state.jurnalgaji.user;
      },
      formatedSelectedDate() {
        return this.formatDate(this.selectedDate);
      },
      idEdit: {
        get() {
          return this.$store.state.jurnalgaji.idEdit;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_idEdit", val);
        },
      },
      isEdit: {
        get() {
          return this.$store.state.jurnalgaji.isEdit;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_isEdit", val);
        },
      },
      dialogType: {
        get() {
          return this.$store.state.jurnalgaji.dialogType;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_dialogType", val);
        },
      },
    },
    methods: {
      disableForm() {
        return this.loading || this.isEdit === "N";
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
        this.selectedBranchDetail = {};
        this.kreditDetail = 0;
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
        this.selectedBranchDetail = {
          branchID: data.branchID,
          branchRegionalID: "",
          branchCode: "",
          branchName: data.branchName,
          branchCompanyID: "",
        };
        this.selectedCoaDetail = {
          display: data.coaNo + " - " + data.coaName,
          id: data.coaID,
          keterangan: data.coaName,
          number: data.coaNo,
        };
        this.searchCoaDetail = data.coaNo + " - " + data.coaName;
        this.kreditDetail = data.kredit;
        this.dialogAddDetail = true;
      },
      deleteDetailJurnalGaji(data) {
        if (data.dataType === "D") {
          return;
        }

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
        tmpList.sort((a, b) => a.branchName.localeCompare(b.branchName));
        let tmp = JSON.stringify(tmpList);
        this.detailJurnalGaji = JSON.parse(tmp);
        this.closeDialogAddDetail();
        this.countSummary();
      },
      editDetailJurnalGaji() {
        if (this.isObjectEmpty(this.selectedCoaDetail)) return;
        if (this.isObjectEmpty(this.selectedBranchDetail)) return;
        if (this.kreditDetail == "" || this.kreditDetail <= 0) return;
        var coa = this.selectedCoaDetail;

        var branchPercent = this.selectedBranchPercent;

        var selectedDetail = this.selectedDetail;
        let data = {};

        branchPercent.detail.forEach((p) => {
          if (
            this.selectedBranchDetail.branchID ===
            p.BranchPercentDetailM_BranchID
          ) {
            data = {
              id: selectedDetail.id,
              branchID: this.selectedBranchDetail.branchID,
              branchName: this.selectedBranchDetail.branchName,
              description: coa.keterangan,
              debet: 0,
              type: "K",
              dataType: selectedDetail.dataType,
              kredit: this.kreditDetail,
              precentage: 0,
              coaID: coa.id,
              coaName: coa.keterangan,
              coaNo: coa.number,
            };
          }
        });

        let debet = 0;
        let kredit = 0;

        this.detailJurnalGaji.forEach((element) => {
          if (this.selectedBranchDetail.branchID === element.branchID) {
            if (element.type === "K" && element.id != data.id) {
              kredit = kredit + parseFloat(element.kredit);
            }
            if (element.type === "D") {
              debet = debet + parseFloat(element.debet);
            }
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
        // tmpList.forEach((e, i) => {
        //   console.log("data");
        //   if (e.id === data.id) {
        //     console.log("DATA FOUND");
        //     tmpList[i] = data;
        //   }
        // });

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
        tmpList.sort((a, b) => a.branchName.localeCompare(b.branchName));
        let tmp = JSON.stringify(tmpList);
        this.detailJurnalGaji = JSON.parse(tmp);
        this.closeDialogAddDetail();
        this.countSummary();
      },
      addDetailJurnalGaji() {
        if (this.isObjectEmpty(this.selectedCoaDetail)) return;
        if (this.isObjectEmpty(this.selectedBranchDetail)) return;
        if (this.kreditDetail == "" || this.kreditDetail <= 0) return;
        var coa = this.selectedCoaDetail;

        var branchPercent = this.selectedBranchPercent;
        let data = {};

        branchPercent.detail.forEach((p) => {
          if (
            this.selectedBranchDetail.branchID ===
            p.BranchPercentDetailM_BranchID
          ) {
            data = {
              id: this.generateRandomId(),
              branchID: this.selectedBranchDetail.branchID,
              branchName: this.selectedBranchDetail.branchName,
              description: coa.keterangan,
              debet: 0,
              type: "K",
              dataType: "N",
              kredit: this.kreditDetail,
              precentage: 0,
              coaID: coa.id,
              coaName: coa.keterangan,
              coaNo: coa.number,
            };
          }
        });

        let debet = 0;
        let kredit = 0;

        this.detailJurnalGaji.forEach((element) => {
          if (this.selectedBranchDetail.branchID === element.branchID) {
            if (element.type === "K") {
              kredit = kredit + parseFloat(element.kredit);
            }
            if (element.type === "D") {
              debet = debet + parseFloat(element.debet);
            }
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
        tmpList.push(data);
        tmpList.sort((a, b) => a.branchName.localeCompare(b.branchName));
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
        console.log("close dialogs");
        this.$emit("update:show", false);
        this.dialogForm = false;
      },
      saveJurnalGaji() {
        let message = [];
        if (this.isObjectEmpty(this.selectedPeriode)) {
          message.push("Pilih periode terlebih dahulu");
        }
        if (this.isObjectEmpty(this.selectedCoa)) {
          message.push("Pilih account terlebih dahulu");
        }
        if (this.isObjectEmpty(this.selectedCoaKas)) {
          message.push("Pilih account bank terlebih dahulu");
        }
        if (this.isObjectEmpty(this.selectedBranchPercent)) {
          message.push("Pilih presentase cabang terlebih dahulu");
        }
        if (this.title === "") {
          message.push("Isi judul terlebih dahulu");
        }

        if (parseFloat(this.sallary) <= 0) {
          message.push("Jumlah gaji regional per pt tidak sesuai");
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
        if (this.detailJurnalGajiRegional.length === 0) return;

        let header = {
          companyID: this.user.M_BranchCompanyID,
          regionalID: this.user.S_RegionalID,
          jurnalTypeID: this.selectedJurnalType.JurnalTypeID,
          date: this.selectedDate,
          periodeID: this.selectedPeriode.periodeID,
          title: this.title,
          coaID: this.selectedCoa.id,
          coaName: this.selectedCoa.keterangan,
          coaNo: this.selectedCoa.number,
          coaKasID: this.selectedCoaKas.id,
          coaKasName: this.selectedCoaKas.keterangan,
          coaKasNo: this.selectedCoaKas.number,
          sallary: this.sallary,
          description: this.description,
          branchPrecenID: this.selectedBranchPercent.BranchPercentID,
          jurnalRegional: this.detailJurnalGajiRegional,
          detail: this.detailJurnalGaji,
        };
        this.$store.dispatch("jurnalgaji/saveJurnalGaji", header);
        console.log(header);
      },
    },
    watch: {
      searchPeriode(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.$store.dispatch("jurnalgaji/getPeriode");
      },
      searchCoaDetail(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.$store.dispatch("jurnalgaji/getCoaDetail");
      },
      searchCoa(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.$store.dispatch("jurnalgaji/getCoa");
      },
      searchCoaKas(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.$store.dispatch("jurnalgaji/getCoaKas");
      },
      async dialogForm(val, old) {
        if (val) {
          await this.$store.dispatch("jurnalgaji/getJurnalType");
          await this.$store.dispatch("jurnalgaji/getBranchpercent");
          await this.$store.dispatch("jurnalgaji/getDefaultBranch");
          if (this.idEdit !== 0) {
            await this.$store.dispatch("jurnalgaji/getDetail", {
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
