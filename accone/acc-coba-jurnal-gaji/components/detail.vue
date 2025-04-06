<template>
  <div style="width: 100%;" class="">
    <v-dialog v-model="dialogPost" persistent width="500">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          Konfirmasi
        </v-card-title>

        <v-card-text class="pa-2">
          Apakah anda yakin post data
          <span class="font-weight-bold"
            >[{{ selectedHeader.number }}] {{ selectedHeader.name }}
          </span>
          <p style="font-style: italic;" class="mt-2">
            *Data yng sudah di post tidak bisa di edit dan di hapus
          </p>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            :loading="loadingSave"
            :disabled="loadingSave"
            flat
            @click="dialogPost = false"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            :loading="loadingSave"
            :disabled="loadingSave"
            @click="postData()"
          >
            Simpan
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialogAddDetail" persistent width="500">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          PRESET JOURNAL
        </v-card-title>

        <v-card-text>
          <v-autocomplete
            :search-input.sync="searchCoa"
            v-model="selectedCoa"
            :items="coaList"
            :loading="loadingAutocomplete"
            hide-no-data
            hide-selected
            item-text="display"
            label="COA"
            placeholder="Start typing desc /number"
            outline
            return-object
          ></v-autocomplete>
          <v-text-field
            type="number"
            v-model="debit"
            label="Debit"
            placeholder="Debit"
            outline
          ></v-text-field>
          <v-text-field
            type="number"
            v-model="credit"
            label="Credit"
            placeholder="Credit"
            outline
          ></v-text-field>
          <v-text-field
            v-model="description"
            label="Description"
            outline
          ></v-text-field>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            :loading="loadingSave"
            :disabled="loadingSave"
            flat
            @click="dialogAddDetail = false"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            :loading="loadingSave"
            :disabled="loadingSave"
            @click="addJurnal()"
          >
            Simpan
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialogImport" persistent width="500">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          UPLOAD FILE JURNAL
        </v-card-title>

        <v-card-text>
          <input
            accept=".xlsx"
            type="file"
            id="csv_file"
            name="csv_file"
            class="form-control"
            :disabled="loadingCsv || loading"
            @input="loadCSV($event)"
          />
          <div v-if="loadingCsv">
            <v-progress-circular
              indeterminate
              v-if="loadingCsv"
              color="primary"
            ></v-progress-circular>
            load file
          </div>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            :loading="loadingSave || loadingCsv "
            :disabled="loadingSave || loadingCsv"
            flat
            @click="resetInputFile()"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            :loading="loadingSave || loadingCsv"
            :disabled="loadingSave || loadingCsv"
            @click="upload()"
          >
            Upload
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-card v-if="isObjectEmpty(selectedHeader)">
      <v-card-title primary-title>
        <div>
          <div class="headline font-weight-bold">
            Pilih salah satu journal
          </div>
        </div>
      </v-card-title>
    </v-card>
    <!-- <div>
      <v-card v-if="loading" class="pa-2">
        <v-progress-linear :indeterminate="true"></v-progress-linear>
      </v-card>
    </div> -->

    <v-card v-if="!isObjectEmpty(selectedHeader)">
      <v-card>
        <v-card-title primary-title>
          <div>
            <div class="headline font-weight-bold">
              [{{ selectedHeader.number }}] {{ selectedHeader.name }}
            </div>
            <span class="grey--text font-weight-bold"
              >{{ selectedHeader.branch }} -
              {{ selectedHeader.branchName }}</span
            >
            <p>
              <kbd>{{ selectedHeader.periodeName }}</kbd>
              <kbd>{{ selectedHeader.periodeDate }}</kbd>
            </p>
          </div>
        </v-card-title>
      </v-card>
      <!-- <v-divider ></v-divider> -->
      <v-layout row wrap class="pa-2">
        <v-flex xs3
          ><v-text-field
            v-model="searchDetail"
            prepend-icon="search"
            label="cari"
            single-line
            hide-details
          ></v-text-field
        ></v-flex>
        <v-flex
          xs9
          v-if="selectedHeader.status==='NEW'"
          class="text-end"
          style="display: flex; justify-content: end;"
        >
          <v-btn
            @click="dialogImport = true"
            :disabled="loading||loadingSave"
            flat
            icon
            color="primary"
          >
            <v-icon>
              file_upload
            </v-icon>
          </v-btn>
          <v-btn
            @click="downloadcsv()"
            :disabled="loading||loadingSave"
            flat
            icon
            color="primary"
          >
            <v-icon>
              file_download
            </v-icon>
          </v-btn>

          <v-btn
            @click="openDialogAdd()"
            :disabled="loading||loadingSave"
            flat
            icon
            color="primary"
          >
            <v-icon>add_box</v-icon>
          </v-btn>
          <v-btn
            @click="openPostDialog()"
            :disabled="loading||loadingSave"
            class="text-white"
            color="warning"
            >POST DATA</v-btn
          >
        </v-flex>
      </v-layout>
      <v-divider></v-divider>
      <div style="height: 48vh; overflow-y: scroll;">
        <v-data-table
          :headers="headers"
          hide-actions
          :loading="loading"
          :items="data"
          class="elevation-1"
        >
          <template v-slot:items="props">
            <tr>
              <td>{{ props.item.number }}</td>
              <td>{{ props.item.keterangan }}</td>
              <td>
                <div v-if="props.item.type ==='DB'">
                  {{ formatCurrency(props.item.value) }}
                </div>
                <div v-else>
                  Rp. 0,00
                </div>
              </td>
              <td>
                <div v-if="props.item.type ==='CR'">
                  {{ formatCurrency(props.item.value) }}
                </div>
                <div v-else>
                  Rp. 0,00
                </div>
              </td>
              <td class="justify-center layout px-0">
                <v-icon
                  v-if="selectedHeader.status==='NEW'"
                  small
                  class="mr-2"
                  @click="openDialogEdit(props.item)"
                >
                  edit
                </v-icon>
                <v-icon
                  v-if="selectedHeader.status==='NEW'"
                  small
                  @click="deleteJurnal(props.item)"
                >
                  delete
                </v-icon>
              </td>
            </tr>
          </template>
        </v-data-table>
      </div>
      <v-divider></v-divider>
      <v-data-table
        :headers="headers"
        hide-actions
        :loading="loading"
        hide-headers
        :items="data"
        class="elevation-1"
      >
        <template v-slot:items="props">
          <tr></tr>
        </template>

        <template v-slot:footer>
          <tr>
            <td width="50%" class="font-weight-bold">Total</td>

            <td width="20%" class="font-weight-bold">
              {{ formatCurrency(summary.debit) }}
            </td>
            <td width="20%" class="font-weight-bold">
              {{ formatCurrency(summary.credit) }}
            </td>
            <td width="10%" class="font-weight-bold"></td>
          </tr>
          <tr>
            <td width="70%" colspan="2" class="font-weight-bold">Balance</td>
            <td width="20%">
              <div class="d-flex text-right font-weight-bold">
                <v-spacer></v-spacer> {{ formatCurrency(summary.balance) }}
              </div>
            </td>
            <td width="10%" class="font-weight-bold"></td>
          </tr>
        </template>
      </v-data-table>
      <div class="text-xs-left pa-2">
        <v-pagination
          v-model="pageDetail"
          :length="totalPageDetail"
        ></v-pagination>
      </div>
    </v-card>
    <one-dialog-alert
      :msg="msg"
      :loading="loadingSave"
      :confirm="handleDialogAlert"
    ></one-dialog-alert>
  </div>
</template>

<style scoped></style>

<script>
  module.exports = {
    components: {
      "one-dialog-alert": httpVueLoader("./dialogAlert.vue"),
    },
    mounted() {
      // this.dialogAlert = true;
      //   this.formatCurrency(10000);
      //   this.$store.dispatch("balance/getPeriode");
    },
    data: () => ({
      act: "",
      loadingCsv: false,
      dataUpload: [],
      debit: 0,
      credit: 0,
      description: "",
      act: "add",
      selectedDetail: {},
      headers: [
        {
          text: "NO. ACCOUNT",
          align: "left",
          sortable: false,
          value: "action",
          width: "10%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "KETERANGAN",
          align: "left",
          sortable: false,
          value: "mr",
          width: "40%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "DEBIT",
          align: "left",
          sortable: false,
          value: "lab",
          width: "20%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "KREDIT",
          align: "left",
          sortable: false,
          value: "lab",
          width: "20%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "AKSI",
          align: "left",
          sortable: false,
          value: "lab",
          width: "10%",
          class: "blue lighten-3 white--text",
        },
      ],
    }),
    computed: {
      dialogAlert: {
        get() {
          return this.$store.state.preset.dialogAlert;
        },
        set(val) {
          this.$store.commit("preset/update_dialogAlert", val);
        },
      },
      actAlert: {
        get() {
          return this.$store.state.preset.actAlert;
        },
        set(val) {
          this.$store.commit("preset/update_actAlert", val);
        },
      },
      msg: {
        get() {
          return this.$store.state.preset.alertMsg;
        },
        set(val) {
          this.$store.commit("preset/update_alertMsg", val);
        },
      },
      loadingSave: {
        get() {
          return this.$store.state.preset.loadingSave;
        },
        set(val) {
          this.$store.commit("preset/update_loadingSave", val);
        },
      },
      selectedHeader: {
        get() {
          return this.$store.state.preset.selectedHeader;
        },
        set(val) {
          this.$store.commit("preset/update_selectedHeader", val);
          //   this.$store.dispatch("preset/searchHeader");
        },
      },
      pageDetail: {
        get() {
          return this.$store.state.preset.pageDetail;
        },
        set(val) {
          this.$store.commit("preset/update_pageDetail", val);
          this.$store.dispatch("preset/searchDetail");
        },
      },
      dialogAddDetail: {
        get() {
          return this.$store.state.preset.dialogAddDetail;
        },
        set(val) {
          this.$store.commit("preset/update_dialogAddDetail", val);
          //   this.$store.dispatch("preset/searchHeader");
        },
      },
      dialogImport: {
        get() {
          return this.$store.state.preset.dialogImport;
        },
        set(val) {
          this.$store.commit("preset/update_dialogImport", val);
        },
      },
      loadingAutocomplete: {
        get() {
          return this.$store.state.preset.loadingAutocomplete;
        },
        set(val) {
          this.$store.commit("preset/update_loadingAutocomplete", val);
        },
      },
      searchCoa: {
        get() {
          return this.$store.state.preset.searchCoa;
        },
        set(val) {
          this.$store.commit("preset/update_searchCoa", val);
        },
      },
      selectedCoa: {
        get() {
          return this.$store.state.preset.selectedCoa;
        },
        set(val) {
          this.$store.commit("preset/update_selectedCoa", val);
        },
      },
      searchDetail: {
        get() {
          return this.$store.state.preset.searchDetail;
        },
        set(val) {
          this.pageDetail = 1;
          this.$store.commit("preset/update_searchDetail", val);
          this.$store.dispatch("preset/searchDetail");
        },
      },
      loading: {
        get() {
          return this.$store.state.preset.loading;
        },
        set(val) {
          this.$store.commit("preset/update_loading", val);
        },
      },
      dialogPost: {
        get() {
          return this.$store.state.preset.dialogPost;
        },
        set(val) {
          this.$store.commit("preset/update_dialogPost", val);
        },
      },
      snackbar: {
        get() {
          return this.$store.state.preset.snackbar;
        },
        set(val) {
          this.$store.commit("preset/update_snackbar", val);
        },
      },
      summary() {
        return this.$store.state.preset.summaryDetail;
      },
      data() {
        return this.$store.state.preset.dataDetail;
      },
      coaList() {
        return this.$store.state.preset.coaList;
      },
      totalPageDetail() {
        return this.$store.state.preset.totalPageDetail;
      },
    },
    methods: {
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
        if (this.actAlert === "deleteDetail") {
          this.msg =
            "Apakah anda yakin menghapus jurnal " +
            this.selectedDetail.keterangan +
            " ?";
          let prm = {
            id: this.selectedDetail.id,
          };
          this.$store.dispatch("preset/deleteData", prm);
        }
        if (this.actAlert === "deleteHeader") {
          let prm = {
            id: this.selectedHeader.id,
          };
          this.$store.dispatch("preset/deleteJurnal", prm);
        }
      },
      downloadcsv() {
        // window.open("./template_preset_journal.xlsx");
        this.download();
      },
      openPostDialog(val) {
        if (this.loading || this.loadingSave) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Loading masih berlangsung ....",
          };
          this.snackbar = snackbar;
          return;
        }
        this.dialogPost = true;
      },
      postData() {
        if (this.loading || this.loadingSave) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Loading masih berlangsung ....",
          };
          this.snackbar = snackbar;
          return;
        }

        this.$store.dispatch("preset/postData");
      },
      addJurnal() {
        this.loading = true;
        let prm = {
          act: this.act,
          data: [
            {
              Number: this.selectedCoa.number,
              Keterangan: this.description,
              Debit: this.debit,
              Kredit: this.credit,
            },
          ],
        };

        let prmUpdate = {
          Number: this.selectedCoa.number,
          Keterangan: this.description,
          Debit: this.debit,
          Kredit: this.credit,
        };
        if (this.act === "add") this.$store.dispatch("preset/addData", prm);
        if (this.act === "edit") this.editData();
      },
      editData() {
        // debugger;

        let type = "DB";
        let value = 0;
        if (
          this.debit.toString().trim() === "" &&
          this.credit.toString().trim() === ""
        ) {
          alert("Debit dan kredit tidak boleh kosong");
          return;
        }
        if (this.description === "") {
          alert("Keterangan tidak boleh kosong tidak boleh kosong");
          return;
        }
        if (parseFloat(this.debit) > 0 && parseFloat(this.credit) > 0) {
          alert("Debit dan kredit tidak boleh lebih besar dari 0");
          return;
        }

        if (parseFloat(this.debit) < 0 && parseFloat(this.credit) < 0) {
          alert("Debit dan kredit tidak boleh kurang dari 0");
          return;
        }
        if (parseFloat(this.debit) > 0 && parseFloat(this.credit) === 0) {
          type = "DB";
        }
        if (parseFloat(this.debit) === 0 && parseFloat(this.credit) > 0) {
          type = "CR";
        }
        if (type == "DB") {
          value = this.debit;
        }
        if (type == "CR") {
          value = this.credit;
        }
        let prm = {
          data: {
            id: this.selectedDetail.id,
            type: type,
            value: value,
            keterangan: this.description,
          },
        };
        this.$store.dispatch("preset/updateData", prm);

        console.log(prm);
      },
      deleteJurnal(val) {
        if (this.loading || this.loadingSave) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Loading masih berlangsung ....",
          };
          this.snackbar = snackbar;
          return;
        }
        this.msg =
          "Apakah anda yakin menghapus jurnal berikut " + val.keterangan + " ?";
        this.selectedDetail = val;
        this.actAlert = "deleteDetail";
        this.dialogAlert = true;
      },
      upload() {
        // this.loading = true;

        let prm = {
          data: this.dataUpload,
        };
        if (this.data.length > 0) {
          if (
            confirm(
              "Apakah anda yakin import document ? \n *import document akan menghapus data yang sudah ada dan mengganti yang baru"
            )
          ) {
            this.$store.dispatch("preset/save", prm);
          }
        } else {
          this.$store.dispatch("preset/save", prm);
        }
      },
      openDialogAdd() {
        if (this.loading || this.loadingSave) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Loading masih berlangsung ....",
          };
          this.snackbar = snackbar;
          return;
        }
        this.act = "add";
        this.selectedCoa = {};
        this.debit = 0;
        this.credit = 0;
        this.searchCoa = "";
        this.description = "";
        this.dialogAddDetail = true;
      },
      openDialogEdit(val) {
        if (this.loading || this.loadingSave) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Loading masih berlangsung ....",
          };
          this.snackbar = snackbar;
          return;
        }
        //           {
        //     "id": "203",
        //     "number": "1110211001",
        //     "coaid": "9",
        //     "searchCoa": "1110211001-BCA PT PRAMITA (4338)",
        //     "keterangan": "BCA PT PRAMITA (4338)",
        //     "jurnalTxDebit": "10000.00",
        //     "jurnalTxCredit": "0.00",
        //     "value": "10000.00",
        //     "type": "DB"
        //   }
        // {"jurnalTxID":"203","number":"1110211001","keterangan":"BCA PT PRAMITA (4338)","display":"1110211001 BCA PT PRAMITA (4338)"}
        this.act = "edit";
        this.selectedDetail = val;
        this.searchCoa = val.searchCoa;
        this.selectedCoa = {
          coaID: val.coaid,
          jurnalTxID: 0,
          number: val.number,
          keterangan: val.coaDescription,
          display: val.searchCoa,
        };
        // this.selectedCoa = {
        //   coaID: val.coaid,
        //   number: val.number,
        //   keterangan: val.coaDescription,
        //   display: val.searchCoa,
        // };
        this.debit = val.jurnalTxDebit;
        this.credit = val.jurnalTxCredit;
        this.description = val.keterangan;
        this.dialogAddDetail = true;
      },
      resetInputFile() {
        document.getElementById("csv_file").value = null;
        this.dialogImport = false;
      },
      isObjectEmpty(val) {
        let obj = val;
        return Object.keys(obj).length === 0;
      },
      download() {
        // window.open("/one-api/mockup/cpone-nonlab-upload-document/patient/downloadfile/" + name, '_self')
        // window.location = "/one-api/mockup/cpone-nonlab-upload-document/patient/downloadfile/" + name;
        // `/one-api/mockup/cpone-nonlab-upload-document/patient/downloadfile/${name}`
        console.log(window.location.href);
        console.log(window.location);
        let url =
          window.location.protocol +
          "//" +
          window.location.host +
          "/" +
          window.location.pathname +
          "template_preset_journal.xlsx";
        url.replace("#", "");
        console.log(url);
        fetch(url)
          .then((response) => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.blob();
          })
          .then((blob) => {
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.setAttribute("download", "Template_preset_journal");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
          })
          .catch((error) => {
            console.error("Error downloading file:", error);
          });
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
      loadCSV(e) {
        this.loadingCsv = true;
        // debugger;
        var vm = this;
        var files = e.target.files,
          f = files[0];
        var reader = new FileReader();
        let error = [];
        reader.onload = function (e) {
          var data = new Uint8Array(e.target.result);
          var workbook = XLSX.read(data, {
            type: "array",
            cellText: true,
            cellDates: true,
          });
          let sheetName = workbook.SheetNames[0];
          /* DO SOMETHING WITH workbook HERE */
          console.log(workbook);
          let worksheet = workbook.Sheets[sheetName];
          // console.log(XLSX.utils.sheet_to_json(worksheet));
          //var xdata = XLSX.utils.sheet_to_json(worksheet,{ raw:false,  dateNF: 'FMT 22'})
          var data_json = [];
          // console.log(xdata)
          var date_data = XLSX.utils.sheet_to_json(worksheet, {
            raw: false,
            dateNF: "22",
          });
          var ktp_data = XLSX.utils.sheet_to_json(worksheet, {
            cellText: true,
          });
          //console.log(zdata)

          date_data.forEach(function (entry, iidx) {
            if (entry.Number === undefined) {
              error.push("Kolom Number tidak ditemukan ");
            }
            if (entry.Keterangan === undefined) {
              error.push("Kolom Keterangan tidak ditemukan ");
            }
            if (entry.Debit === undefined) {
              error.push("Debit tidak ditemukan / kosong");
            }
            if (entry.Kredit === undefined) {
              error.push("Kredit tidak ditemukan /kosong");
            }
            if (entry.Number === "" || entry.Number === undefined) {
              error.push("No. Account/Number tidak boleh kosong ");
            }
            if (entry.Keterangan === "" || entry.Keterangan === undefined) {
              error.push("Keterangan tidak boleh kosong ");
            }
            if (entry.Debit === "") {
              error.push("Debit tidak boleh kosong ");
            }
            if (entry.Kredit === "") {
              error.push("Kredit tidak boleh kosong ");
            }
            if (parseFloat(entry.Debit) > 0 && parseFloat(entry.Kredit) > 0) {
              error.push(
                entry.Number + " Kredit dan debit jumlahnya lebih besar dari 0 "
              );
            }

            data_json.push(entry);
          });
          //   var prm = {
          //     xid: vm.$store.state.patient.data_setup.McuOfflinePrepareID,
          //     data: data_json,
          //   };
          console.log(data_json);

          console.log(error);
          if (error.length > 0) {
            let msg = error.join(",\n");
            alert(msg);
            vm.loadingCsv = false;
            document.getElementById("csv_file").value = null;
            return;
          }
          console.log(data_json);
          let prm = {
            data: data_json,
          };
          vm.dataUpload = data_json;
          //   setTimeout(() => {
          //     console.log("Bam! 5 seconds have passed.");
          // }, 5000);
          vm.loadingCsv = false;
          //   vm.$store.dispatch("balance/save", prm);

          //   console.log(data_json);
          //XLSX.utils.sheet_to_json(ws, {dateNF:"YYYY-MM-DD"})
          //   vm.$store.dispatch("patient/savecsv", prm);
        };

        reader.readAsArrayBuffer(f);
      },
    },
    watch: {
      searchCoa(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.$store.dispatch("preset/searchCoa");
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
