<template>
  <div style="width: 100%;" class="pa-2">
    <v-snackbar
      :color="snackbar.color"
      v-model="snackbar.state"
      :timeout="5000"
      multi-line
      top
    >
      {{ snackbar.msg }}
      <v-btn flat @click="snackbar.state = false">
        Close
      </v-btn>
    </v-snackbar>
    <v-dialog persistent v-model="dialogForm" width="700">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          Form Recrusive Journal Template
        </v-card-title>

        <v-card-text>
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
                label="Tanggal Mulai"
                outline
                hide-details
                readonly
                v-on="on"
                @blur="deFormatedDate(formatedStartDate)"
              ></v-text-field>
            </template>
            <!-- :allowed-dates="disablePastDates" -->
            <v-date-picker
              v-model="startDate"
              no-title
              @input="menuFormDateStart = false"
            ></v-date-picker>
          </v-menu>
          <v-layout row wrap class="mt-2">
            <v-flex xs9
              ><v-menu
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
                    class="mr-2"
                    v-model="formatedEndDate"
                    label="Tanggal Selesai"
                    outline
                    hide-details
                    readonly
                    v-on="on"
                    @blur="deFormatedDate(formatedEndDate)"
                  ></v-text-field>
                </template>
                <v-date-picker
                  v-model="endDate"
                  :allowed-dates="disablePastDates"
                  no-title
                  @input="menuFormDateEnd = false"
                ></v-date-picker> </v-menu
            ></v-flex>
            <v-flex xs3>
              <v-btn color="warning" @click="countTenor()">Hitung Tenor</v-btn>
            </v-flex>
          </v-layout>
          <v-text-field
            label="Tenor"
            @change="countBulanan()"
            v-model="tenor"
            type="number"
            hide-details
            class="mt-2"
            outline
          ></v-text-field>
          <v-text-field
            label="Jumlah Pre-paid"
            v-model="prePaid"
            type="number"
            @blur="countBulanan()"
            hide-details
            class="mt-2"
            outline
          ></v-text-field>
          <v-autocomplete
            :search-input.sync="searchKredit"
            v-model="selectedKredit"
            :items="kreditList"
            :loading="loadingAutocomplete"
            hide-no-data
            hide-selected
            class="mt-2"
            item-text="display"
            label="Kredit"
            hide-details
            outline
            return-object
          ></v-autocomplete>
          <v-text-field
            label="Biaya Bulanan"
            type="number"
            v-model="bulanan"
            hide-details
            class="mt-2"
            outline
          ></v-text-field>
          <v-autocomplete
            :search-input.sync="searchDebet"
            v-model="selectedDebet"
            :items="debetList"
            :loading="loadingAutocomplete"
            hide-no-data
            hide-selected
            class="mt-2"
            item-text="display"
            label="Debet"
            outline
            hide-details
            return-object
          ></v-autocomplete>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            :loading="loadingSave"
            :disabled="loadingSave"
            flat
            @click="dialogForm=false"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            :loading="loadingSave"
            :disabled="loadingSave"
            flat
            @click="handleSavetemplate()"
          >
            Simpan
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-toolbar dark color="primary">
      <v-toolbar-title class="white--text"
        >RECRUSIVE JOURNAL TEMPLATE</v-toolbar-title
      >
      <v-spacer></v-spacer>

      <v-btn @click="openDialogJurnalGaji()" color="orange">New template</v-btn>
    </v-toolbar>
    <v-card class="pa-2">
      <v-text-field
        class="mr-2"
        outline
        v-model="searchTemplate"
        hide-details
        label="cari "
      ></v-text-field>
    </v-card>
    <v-card class="pa-2 mt-2">
      <div style="height: 62vh;">
        <v-data-table
          :headers="headers"
          :items="templateList"
          hide-actions
          :loading="loading"
          class="elevation-1"
        >
          <v-progress-linear
            v-slot:progress
            color="primary"
            indeterminate
          ></v-progress-linear>
          <template v-slot:items="props">
            <td class="pa-1">{{ props.item.number }}</td>
            <td class="pa-1 text-xs-left">{{ props.item.startDate }}</td>
            <td class="pa-1 text-xs-left">{{ props.item.endDate }}</td>
            <td class="pa-1 pr-4 text-xs-right">
              {{ formatCurrency(props.item.prePaid) }}
            </td>
            <td class="pa-1 text-xs-left">{{ props.item.tenor }}</td>
            <td class="pa-1 pr-4 text-xs-right">
              {{ formatCurrency(props.item.bulanan) }}
            </td>
            <td class="pa-1 text-xs-center">
              <v-icon small class="mr-2" @click="handleOpenEdit(props.item)">
                edit
              </v-icon>
              <v-icon small @click="openDelete(props.item)">
                delete
              </v-icon>
            </td>
          </template>
        </v-data-table>
      </div>
      <v-divider></v-divider>
      <div class="text-xs-left pa-2">
        <v-pagination v-model="page" :length="total"></v-pagination>
      </div>
    </v-card>
    <one-dialog-alert
      :msg="alertMsg"
      :loading="loadingSave"
      :confirm="handleDialogAlert"
    ></one-dialog-alert>
    <dialog-jurnal-kirim-barang-regional></dialog-jurnal-kirim-barang-regional>
  </div>
</template>

<style scoped></style>

<script>
  module.exports = {
    components: {
      "one-dialog-alert": httpVueLoader("./dialogAlert.vue"),
      "dialog-jurnal-gaji": httpVueLoader("./jurnalgaji.vue"),
      "dialog-jurnal-kirim-barang-regional": httpVueLoader(
        "./JurnalPengirimanBarangRegional.vue"
      ),
    },
    mounted() {
      //   this.formatCurrency(10000);
      //   this.$store.dispatch("balance/getPeriode");

      this.$store.dispatch("recrusive/search");
    },
    data: () => ({
      dialogJurnalGaji: false,
      loadingCsv: false,
      menuFormDateStart: false,
      menuFormDateEnd: false,
      act: "",
      alertMsg: "",
      headers: [
        {
          text: "NO. TEMPLATE",
          align: "left",
          sortable: false,
          value: "action",
          width: "10%",
          class: "pa-2 pl-2 blue lighten-3 white--text",
        },
        {
          text: "TGL. MULAI",
          align: "left",
          sortable: false,
          value: "mr",
          width: "15%",
          class: "pa-2 blue lighten-3 white--text",
        },
        {
          text: "TGL. SELESAI",
          align: "left",
          sortable: false,
          value: "lab",
          width: "15%",
          class: "pa-2 blue lighten-3 white--text",
        },
        {
          text: "PRE-PAID",
          align: "left",
          sortable: false,
          value: "lab",
          width: "20%",
          class: "pa-2 blue lighten-3 white--text",
        },
        {
          text: "TENOR",
          align: "left",
          sortable: false,
          value: "lab",
          width: "10%",
          class: "pa-2  blue lighten-3 white--text",
        },
        {
          text: "PER BULAN",
          align: "left",
          sortable: false,
          value: "lab",
          width: "20%",
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
      dialogAlert: {
        get() {
          return this.$store.state.recrusive.dialogAlert;
        },
        set(val) {
          this.$store.commit("recrusive/update_dialogAlert", val);
        },
      },
      dialogForm: {
        get() {
          return this.$store.state.recrusive.dialogForm;
        },
        set(val) {
          this.$store.commit("recrusive/update_dialogForm", val);
        },
      },
      startDate: {
        get() {
          return this.$store.state.recrusive.startDate;
        },
        set(val) {
          this.$store.commit("recrusive/update_startDate", val);
        },
      },
      endDate: {
        get() {
          return this.$store.state.recrusive.endDate;
        },
        set(val) {
          this.$store.commit("recrusive/update_endDate", val);
        },
      },
      debetList: {
        get() {
          return this.$store.state.recrusive.debetList;
        },
        set(val) {
          this.$store.commit("recrusive/update_debetList", val);
        },
      },
      searchTemplate: {
        get() {
          return this.$store.state.recrusive.searchTemplate;
        },
        set(val) {
          this.$store.commit("recrusive/update_searchTemplate", val);
          this.page = 1;
          this.$store.dispatch("recrusive/search");
        },
      },
      selectedDebet: {
        get() {
          return this.$store.state.recrusive.selectedDebet;
        },
        set(val) {
          this.$store.commit("recrusive/update_selectedDebet", val);
        },
      },
      searchDebet: {
        get() {
          return this.$store.state.recrusive.searchDebet;
        },
        set(val) {
          this.$store.commit("recrusive/update_searchDebet", val);
        },
      },
      kreditList: {
        get() {
          return this.$store.state.recrusive.kreditList;
        },
        set(val) {
          this.$store.commit("recrusive/update_kreditList", val);
        },
      },
      selectedKredit: {
        get() {
          return this.$store.state.recrusive.selectedKredit;
        },
        set(val) {
          this.$store.commit("recrusive/update_selectedKredit", val);
        },
      },
      searchKredit: {
        get() {
          return this.$store.state.recrusive.searchKredit;
        },
        set(val) {
          this.$store.commit("recrusive/update_searchKredit", val);
        },
      },
      tenor: {
        get() {
          return this.$store.state.recrusive.tenor;
        },
        set(val) {
          this.$store.commit("recrusive/update_tenor", val);
        },
      },
      prePaid: {
        get() {
          return this.$store.state.recrusive.prePaid;
        },
        set(val) {
          this.$store.commit("recrusive/update_prePaid", val);
        },
      },
      bulanan: {
        get() {
          return this.$store.state.recrusive.bulanan;
        },
        set(val) {
          this.$store.commit("recrusive/update_bulanan", val);
        },
      },
      loading: {
        get() {
          return this.$store.state.recrusive.loading;
        },
        set(val) {
          this.$store.commit("recrusive/update_loading", val);
        },
      },
      loadingSave: {
        get() {
          return this.$store.state.recrusive.loadingSave;
        },
        set(val) {
          this.$store.commit("recrusive/update_loadingSave", val);
        },
      },
      snackbar: {
        get() {
          return this.$store.state.recrusive.snackbar;
        },
        set(val) {
          this.$store.commit("recrusive/update_snackbar", val);
        },
      },
      selectedTemplate: {
        get() {
          return this.$store.state.recrusive.selectedTemplate;
        },
        set(val) {
          this.$store.commit("recrusive/update_selectedTemplate", val);
        },
      },
      page: {
        get() {
          return this.$store.state.recrusive.page;
        },
        set(val) {
          this.$store.commit("recrusive/update_page", val);
          this.$store.dispatch("recrusive/search");
        },
      },

      loadingAutocomplete() {
        return this.$store.state.recrusive.loadingAutocomplete;
      },
      templateList() {
        return this.$store.state.recrusive.templateList;
      },
      total() {
        return this.$store.state.recrusive.total;
      },
      formatedStartDate() {
        return this.formatDate(this.startDate);
      },
      formatedEndDate() {
        return this.formatDate(this.endDate);
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
          return this.$store.state.jurnalgaji.dialogType;
        },
        set(val) {
          this.$store.commit("jurnalgaji/update_dialogType", val);
        },
      },
    },
    methods: {
      openDialogJurnalGaji() {
        let a = [0, 152, 153, 155, 158];
        const randomIndex = Math.floor(Math.random() * a.length);
        const randomValue = a[randomIndex];
        console.log(randomValue); // Menampilkan nilai acak dari array a

        this.idEdit = randomValue;
        this.dialogFormJpbreg = true;
      },
      openDelete(val) {
        if (this.loading || this.loadingSave) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Loading masih berlangsung ....",
          };
          this.snackbar = snackbar;
          return;
        }
        this.alertMsg =
          "Apakah anda yakin menghapus template  " + val.number + " ?";
        console.log("opendelete");
        this.selectedTemplate = val;
        this.dialogAlert = true;
      },
      handleDialogAlert() {
        if (this.loading || this.loadingSave) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Loading masih berlangsung ....",
          };
          this.snackbar = snackbar;
          return;
        }
        let prm = {
          id: this.selectedTemplate.id,
        };

        this.$store.dispatch("recrusive/deleteTemplate", prm);
      },
      formatCurrency(val) {
        // Format the price above to USD using the locale, style, and currency.
        let price = parseFloat(val);
        let USDollar = new Intl.NumberFormat("id-ID", {
          style: "currency",
          currency: "IDR",
        });

        // console.log(
        //   `The formated version of ${price} is ${USDollar.format(price)}`
        // );
        return `${USDollar.format(price)}`;
      },
      isObjectEmpty(val) {
        let obj = val;
        return Object.keys(obj).length === 0;
      },

      disablePastDates(val) {
        return !moment(val).isBefore(this.startDate);
      },
      countTenor() {
        // var sd = moment([2019, 1, 1]);
        // var ed = moment([2019, 10, 11]);
        var sd = moment(this.startDate);
        var ed = moment(this.endDate);
        let dfc = ed.diff(sd, "months");
        this.tenor = dfc;
        console.log(dfc);
        this.countBulanan();
      },
      countBulanan() {
        let cnt = parseFloat(this.prePaid) / parseFloat(this.tenor);
        this.bulanan = cnt;
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
      handleOpenAdd() {
        if (this.loading || this.loadingSave) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Loading Masih berlangsung ....",
          };
        }
        this.act = "add";
        this.tenor = 0;
        this.prePaid = 0;
        this.selectedKredit = {};
        this.selectedDebet = {};
        this.bulanan = 0;
        this.searchDebet = "";
        this.searchDebet = "";
        this.dialogForm = true;
      },
      handleOpenEdit(val) {
        if (this.loading || this.loadingSave) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Loading Masih berlangsung ....",
          };
        }
        this.selectedTemplate = val;
        this.act = "edit";
        this.tenor = val.tenor;
        this.prePaid = val.prePaid;
        this.selectedKredit = {
          id: val.creditCoaID,
          display: val.creditName,
        };
        this.selectedDebet = {
          id: val.debitCoaID,
          display: val.debitName,
        };
        // this.startDate = moment(val.startDate, "MM-DD-YYYY").format(
        //   "YYYY-MM-DD"
        // );

        // this.endDate = moment(val.endDate, "MM-DD-YYYY").format("YYYY-MM-DD");
        this.startDate = val.startDateVal;
        this.endDate = val.endDateVal;
        this.bulanan = val.bulanan;
        this.searchDebet = val.debitName;
        this.searchKredit = val.creditName;
        this.dialogForm = true;
      },
      handleSavetemplate() {
        let err = [];
        if (this.tenor === "" || parseFloat(this.tenor) < 0) {
          err.push("Tenor tidak boleh kosong atau kurang dari 0");
        }
        if (this.prePaid === "" || parseFloat(this.prePaid) < 0) {
          err.push("Pre-paid tidak boleh kosong atau kurang dari 0");
        }
        if (this.bulanan === "" || parseFloat(this.bulanan) < 0) {
          err.push("Biaya bulanan tidak boleh kosong atau kurang dari 0");
        }
        if (this.isObjectEmpty(this.selectedDebet)) {
          err.push("Pilih salah satu debet !");
        }
        if (this.isObjectEmpty(this.selectedKredit)) {
          err.push("Pilih salah satu kredit !");
        }
        if (err.length > 0) {
          let msg = err.join("\n, ");
          let snackbar = {
            state: true,
            color: "warning",
            msg: msg,
          };
          this.snackbar = snackbar;
          return;
        }
        let prm = {
          tenor: this.tenor,
          startDate: this.startDate,
          endDate: this.endDate,
          prePaid: this.prePaid,
          kredit: this.selectedKredit.id,
          debet: this.selectedDebet.id,
          bulanan: this.bulanan,
        };
        console.log(this.act);
        if (this.act === "add") {
          this.$store.dispatch("recrusive/insertTemplate", prm);
        }
        if (this.act === "edit") {
          prm.id = this.selectedTemplate.id;
          this.$store.dispatch("recrusive/updateTemplate", prm);
        }
      },
      handleDialogClosed() {
        console.log("Dialog telah ditutup");
      },
    },
    watch: {
      searchDebet(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.$store.dispatch("recrusive/searchDebet");
      },
      searchKredit(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.$store.dispatch("recrusive/searchKredit");
      },

      search_city(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        if (this.$store.state.patient.update_autocomplete_status == 1) return;
        this.thr_search_city();
      },
    },
  };
</script>
