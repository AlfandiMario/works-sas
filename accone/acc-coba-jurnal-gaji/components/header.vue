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
    <v-dialog v-model="dialogAddHeader" persistent width="500">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          PRESET JOURNAL
        </v-card-title>

        <v-card-text>
          <v-autocomplete
            v-model="selectedPeriodeForm"
            :items="periodeList"
            outline
            label="Periode"
            item-text="name"
            return-object
          >
            <template v-slot:item="data">
              <template>
                <v-list-tile-content>
                  <v-list-tile-title
                    v-html="data.item.name"
                  ></v-list-tile-title>
                  <v-list-tile-sub-title
                    v-html="data.item.periode"
                  ></v-list-tile-sub-title>
                </v-list-tile-content>
              </template>
            </template>
          </v-autocomplete>
          <v-autocomplete
            v-model="selectedBranch"
            :items="branchList"
            outline
            label="Branch"
            item-text="branchName"
            return-object
          >
          </v-autocomplete>
          <v-autocomplete
            v-model="selectedJurnalType"
            :items="jurnalType"
            outline
            label="Tipe"
          >
          </v-autocomplete>
          <v-text-field
            v-model="jurnalName"
            label="Journal Name"
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
            @click="dialogAddHeader = false"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            :loading="loadingSave"
            :disabled="loadingSave"
            @click="addData()"
          >
            Simpan
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-toolbar dark color="primary">
      <v-toolbar-title class="white--text">PRESET JOURNAL</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn @click="openDialogAdd()" flat icon color="white">
        <v-icon>add_box</v-icon>
      </v-btn>
    </v-toolbar>
    <v-card class="pa-2">
      <v-layout row wrap>
        <v-flex xs6>
          <v-autocomplete
            v-model="selectedPeriode"
            :items="periodeList"
            outline
            hide-details
            label="Periode"
            item-text="name"
            return-object
          >
            <template v-slot:item="data">
              <template>
                <v-list-tile-content>
                  <v-list-tile-title
                    v-html="data.item.name"
                  ></v-list-tile-title>
                  <v-list-tile-sub-title
                    v-html="data.item.periode"
                  ></v-list-tile-sub-title>
                </v-list-tile-content>
              </template>
            </template> </v-autocomplete
        ></v-flex>
        <v-flex xs6>
          <v-text-field
            v-model="searchHeader"
            label="Cari"
            class="ml-2"
            outline
            hide-details
          ></v-text-field>
        </v-flex>
      </v-layout>
    </v-card>
    <v-card>
      <div style="height: 65vh; overflow-y: scroll;">
        <v-data-table
          hide-actions
          :headers="headers"
          :items="dataHeader"
          :loading="loading"
          class="elevation-1"
        >
          <template v-slot:items="props">
            <tr
              @click="selectMe(props.item)"
              v-bind:class="{
                'yellow lighten-4': isSelected(props.item),
              }"
            >
              <td class="text-xs-left">{{ props.item.number }}</td>
              <td class="text-xs-left">{{ props.item.branch }}</td>
              <td class="text-xs-left">{{ props.item.name }}</td>
              <td class="text-xs-left">{{ props.item.status }}</td>
              <td class="text-xs-left">
                <v-icon
                  v-if="props.item.status==='NEW'"
                  small
                  class="mr-2"
                  @click="openDialogEdit(props.item)"
                >
                  edit
                </v-icon>
                <!-- <v-icon
                  v-if="props.item.status==='NEW'"
                  small
                  class="mr-2"
                  @click="openPostDialog(props.item)"
                >
                  check
                </v-icon> -->
                <v-icon
                  v-if="props.item.status==='NEW'"
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
      <div class="text-xs-left pa-2">
        <v-pagination
          v-model="pageHeader"
          :length="totalPageHeader"
        ></v-pagination>
      </div>
    </v-card>
    <one-dialog-alert
      :msg="alertMsg"
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
      //   this.formatCurrency(10000);
      this.$store.dispatch("preset/getPeriode");
      this.$store.dispatch("preset/getBranch");
      this.$store.dispatch("preset/searchHeader");
    },
    data: () => ({
      loadingCsv: false,
      jurnalName: "",
      act: "add",
      headers: [
        {
          text: "NO. JURNAL",
          align: "left",
          sortable: false,
          value: "action",
          width: "20%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "BRANCH",
          align: "left",
          sortable: false,
          value: "mr",
          width: "10%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "NAME",
          align: "left",
          sortable: false,
          value: "lab",
          width: "30%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "STATUS",
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
          width: "20%",
          class: "blue lighten-3 white--text",
        },
      ],
    }),
    computed: {
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
      dialogAlert: {
        get() {
          return this.$store.state.preset.dialogAlert;
        },
        set(val) {
          this.$store.commit("preset/update_dialogAlert", val);
        },
      },
      dialogPost: {
        get() {
          return this.$store.state.preset.dialogPost;
        },
        set(val) {
          this.$store.commit("preset/update_dialogPost", val);
          //   this.$store.dispatch("preset/searchHeader");
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
      pageHeader: {
        get() {
          return this.$store.state.preset.pageHeader;
        },
        set(val) {
          this.$store.commit("preset/update_pageHeader", val);
          this.$store.dispatch("preset/searchHeader");
        },
      },
      totalPageHeader: {
        get() {
          return this.$store.state.preset.totalPageHeader;
        },
        set(val) {
          this.$store.commit("preset/update_totalPageHeader", val);
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
      jurnalType: {
        get() {
          return this.$store.state.preset.jurnalType;
        },
        set(val) {
          this.$store.commit("preset/update_jurnalType", val);
        },
      },
      selectedJurnalType: {
        get() {
          return this.$store.state.preset.selectedJurnalType;
        },
        set(val) {
          this.$store.commit("preset/update_selectedJurnalType", val);
        },
      },
      selectedPeriode: {
        get() {
          return this.$store.state.preset.selectedPeriode;
        },
        set(val) {
          this.pageHeader = 1;
          this.$store.commit("preset/update_selectedPeriode", val);
          this.$store.dispatch("preset/searchHeader");
        },
      },
      selectedPeriodeForm: {
        get() {
          return this.$store.state.preset.selectedPeriodeForm;
        },
        set(val) {
          this.$store.commit("preset/update_selectedPeriodeForm", val);
        },
      },
      dialogAddHeader: {
        get() {
          return this.$store.state.preset.dialogAddHeader;
        },
        set(val) {
          this.$store.commit("preset/update_dialogAddHeader", val);
        },
      },
      selectedBranch: {
        get() {
          return this.$store.state.preset.selectedBranch;
        },
        set(val) {
          this.$store.commit("preset/update_selectedBranch", val);
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
      dataDetail: {
        get() {
          return this.$store.state.preset.dataDetail;
        },
        set(val) {
          this.$store.commit("preset/update_dataDetail", val);
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
      searchHeader: {
        get() {
          return this.$store.state.preset.searchHeader;
        },
        set(val) {
          this.pageHeader = 1;
          this.$store.commit("preset/update_searchHeader", val);
          this.$store.dispatch("preset/searchHeader");
        },
      },
      branchList() {
        return this.$store.state.preset.branchList;
      },
      dataHeader() {
        return this.$store.state.preset.dataHeader;
      },
      periodeList() {
        return this.$store.state.preset.periodeList;
      },
      summary() {
        return this.$store.state.balance.summary;
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
        if (this.act === "delete") {
          let prm = {
            id: this.selectedHeader.id,
          };
          this.$store.dispatch("preset/deleteJurnal", prm);
        }
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
        this.selectedHeader = val;
        this.dialogPost = true;
      },

      isSelected(val) {
        return this.selectedHeader.id === val.id;
      },
      selectMe(val) {
        if (this.loading || this.loadingSave) {
          let snackbar = {
            state: true,
            color: "warning",
            msg: "Loading masih berlangsung ....",
          };
          this.snackbar = snackbar;
          return;
        }
        this.dataDetail = [];
        this.selectedHeader = val;
        this.$store.dispatch("preset/searchDetail");
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
          "Apakah anda yakin menghapus data berikut " + val.name + " ?";
        this.selectedHeader = val;
        this.actAlert = "deleteHeader";
        this.dialogAlert = true;
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
        if (this.periodeList.length > 0) {
          this.selectedPeriodeForm = this.periodeList[0];
        }
        if (this.branchList.length > 0) {
          this.selectedBranch = this.branchList[0];
        }
        if (this.jurnalType.length > 0) {
          this.selectedJurnalType = this.jurnalType[0];
        }
        this.jurnalName = "";
        this.dialogAddHeader = true;
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
        this.act = "edit";
        this.selectedHeader = val;
        // debugger;
        if (this.periodeList.length > 0) {
          this.selectedPeriodeForm = this.periodeList.find(
            (e) => e.id === val.periode
          );
        }
        if (this.branchList.length > 0) {
          this.selectedBranch = this.branchList.find(
            (e) => e.branchCode === val.branch
          );
        }
        if (this.jurnalType.length > 0) {
          this.selectedJurnalType = this.jurnalType.find((e) => e === val.type);
        }
        this.jurnalName = val.name;
        this.dialogAddHeader = true;
      },
      editData() {
        let error = [];
        if (Object.keys(this.selectedPeriodeForm).length === 0) {
          error.push("periode tidak boleh kosong");
        }
        if (Object.keys(this.selectedBranch).length === 0) {
          error.push("Branch tidak boleh kosong");
        }
        if (this.jurnalName.trim() === "") {
          error.push("Nama tidak boleh kosong");
        }
        if (error.length > 0) {
          alert(error.join(",\n"));
          return;
        }
        let prm = {
          id: this.selectedHeader.id,
          branch: this.selectedBranch.branchCode,
          periode: this.selectedPeriodeForm.id,
          name: this.jurnalName,
          type: this.selectedJurnalType,
        };
        this.$store.dispatch("preset/editJurnal", prm);
      },
      addData() {
        let error = [];
        if (Object.keys(this.selectedPeriodeForm).length === 0) {
          error.push("periode tidak boleh kosong");
        }
        if (Object.keys(this.selectedBranch).length === 0) {
          error.push("Branch tidak boleh kosong");
        }
        if (this.jurnalName.trim() === "") {
          error.push("Nama tidak boleh kosong");
        }
        if (error.length > 0) {
          alert(error.join(",\n"));
          return;
        }
        let prm = {
          act: this.act,
          id: this.selectedHeader.id,
          branch: this.selectedBranch.branchCode,
          periode: this.selectedPeriodeForm.id,
          name: this.jurnalName,
          type: this.selectedJurnalType,
        };
        if (this.act === "add") {
          this.$store.dispatch("preset/addJurnal", prm);
        }
        if (this.act === "edit") {
          this.$store.dispatch("preset/editJurnal", prm);
        }
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
