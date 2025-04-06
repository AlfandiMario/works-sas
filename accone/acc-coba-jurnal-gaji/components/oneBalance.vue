<template>
  <div style="width: 100%;" class="">
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
    <v-dialog v-model="dialogAdd" persistent width="500">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          ADD BEGINNING BALANCE
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
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            :loading="loading"
            :disabled="loading"
            flat
            @click="dialogAdd = false"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            :loading="loading"
            :disabled="loading"
            flat
            @click="addBegginingBalance()"
          >
            Simpan
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialogImport" persistent width="500">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          UPLOAD FILE BEGINNING BALANCE
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
            :loading="loading || loadingCsv"
            :disabled="loading || loadingCsv"
            flat
            @click="resetInputFile()"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            :loading="loading || loadingCsv"
            :disabled="loading || loadingCsv"
            flat
            @click="upload()"
          >
            Upload
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialogEdit" persistent width="500">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          {{ selectedData.keterangan }}
        </v-card-title>

        <v-card-text>
          <v-text-field
            readonly
            v-model="selectedData.number"
            label="No. Account"
            placeholder="No. Account"
            outline
          ></v-text-field>
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
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            :loading="loading"
            :disabled="loading"
            flat
            @click="dialogEdit = false"
          >
            Tutup
          </v-btn>
          <v-btn
            color="primary"
            :loading="loading"
            :disabled="loading"
            flat
            @click="editData()"
          >
            Simpan
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-toolbar dark color="primary">
      <v-toolbar-title class="white--text">OPENING BALANCE</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn
        v-if="btnUpload && status==='N'"
        @click="dialogImport = true"
        flat
        icon
        color="white"
      >
        <v-icon v-if="btnUpload">
          file_upload
        </v-icon>
      </v-btn>

      <v-btn
        v-if="btnUpload && status==='N'"
        @click="openDialogAdd()"
        flat
        icon
        color="white"
      >
        <v-icon>add_box</v-icon>
      </v-btn>
      <v-btn
        @click="postData()"
        v-if="btnUpload && status==='N' && data.length >0"
        color="orange"
        >POST DATA</v-btn
      >
    </v-toolbar>
    <v-card class="pa-2">
      <v-autocomplete
        v-model="selectedPeriode"
        :items="periodeList"
        outline
        hide-details
        color="blue"
        label="Periode"
        item-text="name"
        return-object
      >
        <template v-slot:item="data">
          <template>
            <v-list-tile-content>
              <v-list-tile-title v-html="data.item.name"></v-list-tile-title>
              <v-list-tile-sub-title
                v-html="data.item.periode"
              ></v-list-tile-sub-title>
            </v-list-tile-content>
          </template>
        </template>
      </v-autocomplete>
    </v-card>
    <v-card class="pa-2 mt-2">
      <v-layout align-center justify-space-between row fill-height class="mb-2">
        <div style="width: 300px;">
          <v-text-field
            v-model="searchTable"
            prepend-icon="search"
            label="cari"
            single-line
            hide-details
          ></v-text-field>
        </div>
        <div class="pa-1">
          Download template
          <v-btn @click="download()" flat icon color="primary">
            <v-icon class="">
              file_download
            </v-icon>
          </v-btn>
          <!-- <v-spacer></v-spacer> -->
        </div>
      </v-layout>

      <div style="height: 54vh; overflow-y: scroll;">
        <v-data-table
          :headers="headers"
          hide-actions
          :loading="loading"
          :items="data"
          class="elevation-1"
          :search="searchTable"
          :custom-filter="filterItemsObj"
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
                  v-if="status==='N'"
                  small
                  class="mr-2"
                  @click="openDialogEdit(props.item)"
                >
                  edit
                </v-icon>
                <v-icon
                  v-if="status==='N'"
                  small
                  @click="deleteData(props.item)"
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
    </v-card>
  </div>
</template>

<style scoped></style>

<script>
  module.exports = {
    mounted() {
      //   this.formatCurrency(10000);
      this.$store.dispatch("balance/getPeriode");
      this.$store.dispatch("balance/search");
    },
    data: () => ({
      loadingCsv: false,
      search_city: "",
      oldlabel: "",
      selectedData: {},
      debit: 0,
      credit: 0,
      btnUpload: false,
      searchTable: "",
      headers: [
        {
          text: "NO. ACCOUNT",
          align: "left",
          sortable: false,
          value: "action",
          width: "10%",
          class: "pa-2 pl-2 blue lighten-3 white--text",
        },
        {
          text: "KETERANGAN",
          align: "left",
          sortable: false,
          value: "mr",
          width: "40%",
          class: "pa-2 blue lighten-3 white--text",
        },
        {
          text: "DEBIT",
          align: "left",
          sortable: false,
          value: "lab",
          width: "20%",
          class: "pa-2 blue lighten-3 white--text",
        },
        {
          text: "KREDIT",
          align: "left",
          sortable: false,
          value: "lab",
          width: "20%",
          class: "pa-2 blue lighten-3 white--text",
        },
        {
          text: "ACTION",
          align: "left",
          sortable: false,
          value: "lab",
          width: "10%",
          class: "pa-2  blue lighten-3 white--text",
        },
      ],
    }),
    computed: {
      selectedPeriode: {
        get() {
          return this.$store.state.balance.selectedPeriode;
        },
        set(val) {
          this.$store.commit("balance/update_selectedPeriode", val);
        },
      },
      snackbar: {
        get() {
          return this.$store.state.balance.snackbar;
        },
        set(val) {
          this.$store.commit("balance/update_snackbar", val);
        },
      },
      status: {
        get() {
          return this.$store.state.balance.status;
        },
        set(val) {
          this.$store.commit("balance/update_status", val);
        },
      },
      data: {
        get() {
          return this.$store.state.balance.data;
        },
        set(val) {
          this.$store.commit("balance/update_data", val);
        },
      },
      dialogEdit: {
        get() {
          return this.$store.state.balance.dialogEdit;
        },
        set(val) {
          this.$store.commit("balance/update_dialogEdit", val);
        },
      },
      dialogImport: {
        get() {
          return this.$store.state.balance.dialogImport;
        },
        set(val) {
          this.$store.commit("balance/update_dialogImport", val);
        },
      },
      dataUpload: {
        get() {
          return this.$store.state.balance.dataUpload;
        },
        set(val) {
          this.$store.commit("balance/update_dataUpload", val);
        },
      },
      loading: {
        get() {
          return this.$store.state.balance.loading;
        },
        set(val) {
          this.$store.commit("balance/update_loading", val);
        },
      },
      loadingAutocomplete: {
        get() {
          return this.$store.state.balance.loadingAutocomplete;
        },
        set(val) {
          this.$store.commit("balance/update_loadingAutocomplete", val);
        },
      },
      selectedCoa: {
        get() {
          return this.$store.state.balance.selectedCoa;
        },
        set(val) {
          this.$store.commit("balance/update_selectedCoa", val);
        },
      },
      searchCoa: {
        get() {
          return this.$store.state.balance.searchCoa;
        },
        set(val) {
          this.$store.commit("balance/update_searchCoa", val);
        },
      },
      dialogAdd: {
        get() {
          return this.$store.state.balance.dialogAdd;
        },
        set(val) {
          this.$store.commit("balance/update_dialogAdd", val);
        },
      },

      periodeList() {
        return this.$store.state.balance.periodeList;
      },
      summary() {
        return this.$store.state.balance.summary;
      },
      coaList() {
        return this.$store.state.balance.coaList;
      },
    },
    methods: {
      postData() {
        if (parseInt(this.summary.balance) !== 0) {
          alert("Balance tidak sama dengan 0, tidak bisa post data !!");
          return;
        }
        let cek = confirm(
          "Apakah anda yakin konfirmasi data beginning balance yang sudah ada ? \n\n *setelah post/konfirmasi data tidak bisa diedit lagi"
        );
        if (cek) {
          this.$store.dispatch("balance/postData");
        }
      },
      filterItems(val, search) {
        return (
          val.keterangan.includes(search.toLowerCase()) ||
          val.number.includes(search.toLowerCase())
        );
      },
      filterItemsObj(items, search, filterBawaan) {
        const result = items.filter(
          (e) =>
            e.keterangan.toLowerCase().includes(search.toLowerCase()) ||
            e.number.toLowerCase().includes(search.toLowerCase())
        );

        return result;
      },
      download() {
        // window.open("/one-api/mockup/cpone-nonlab-upload-document/patient/downloadfile/" + name, '_self')
        // window.location = "/one-api/mockup/cpone-nonlab-upload-document/patient/downloadfile/" + name;
        // `/one-api/mockup/cpone-nonlab-upload-document/patient/downloadfile/${name}`
        fetch(
          "/birt/run?__report=report/one/acc/sp_rpt_acc_002.rptdesign&__format=xlsx&username=admin"
        )
          .then((response) => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.blob();
          })
          .then((blob) => {
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.setAttribute("download", "Template_beginning_balance");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
          })
          .catch((error) => {
            console.error("Error downloading file:", error);
          });
      },
      addBegginingBalance() {
        this.loading = true;
        let prm = {
          data: [
            {
              Number: this.selectedCoa.number,
              Keterangan: this.selectedCoa.keterangan,
              Debit: this.debit,
              Kredit: this.credit,
            },
          ],
        };
        this.$store.dispatch("balance/addData", prm);
      },
      openDialogAdd() {
        this.selectedCoa = {};
        this.debit = 0;
        this.credit = 0;
        this.searchCoa = "";
        this.dialogAdd = true;
      },
      isObjectEmpty(val) {
        let obj = val;
        return Object.keys(obj).length === 0 && obj.constructor === Object;
      },
      isPeriodeEmpty() {
        return Object.keys(this.selectedPeriode).length === 0;
      },
      openDialogEdit(val) {
        if (this.loading) {
          return;
        }
        if (this.status == "P") {
          return;
        }
        this.selectedData = val;
        if (val.type == "DB") {
          this.debit = val.value;
          this.credit = 0;
        }
        if (val.type == "CR") {
          this.credit = val.value;
          this.debit = 0;
        }
        this.dialogEdit = true;
      },
      editData() {
        // debugger;
        if (this.loading) {
          return;
        }
        if (this.status == "P") {
          return;
        }
        let type = "DB";
        let value = 0;
        if (
          this.debit.toString().trim() === "" &&
          this.credit.toString().trim() === ""
        ) {
          alert("Debit dan kredit tidak boleh kosong");
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
            id: this.selectedData.id,
            type: type,
            value: value,
          },
        };
        this.$store.dispatch("balance/updateData", prm);

        console.log(prm);
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
      resetInputFile() {
        document.getElementById("csv_file").value = null;
        this.dialogImport = false;
      },
      upload() {
        // this.loading = true;
        let prm = {
          data: this.dataUpload,
        };
        if (this.data.length > 0) {
          if (
            confirm(
              "Apakah anda yakin import document ? \n *import document akan menghapus data yang sudah ada"
            )
          ) {
            this.$store.dispatch("balance/save", prm);
          }
        } else {
          this.$store.dispatch("balance/save", prm);
        }
      },
      deleteData(val) {
        console.log(val);
        let cek = confirm(
          "Apakah anda yakin untuk menghapus data berikut " +
            val.number +
            " " +
            val.keterangan
        );
        if (cek) {
          let prm = {
            id: val.id,
          };
          this.$store.dispatch("balance/deleteData", prm);
        }
      },
    },
    watch: {
      selectedPeriode(val, old) {
        if (Object.keys(val).length === 0) {
          this.btnUpload = false;
        } else {
          this.btnUpload = true;
        }
      },
      searchCoa(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.$store.dispatch("balance/searchCoa");
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
