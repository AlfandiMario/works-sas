<template>
  <div>
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
      color="error"
      v-model="snackbarError"
      right="right"
      :timeout="3000"
      top="top"
    >
      {{ errorMsg }}
      <v-btn color="white" flat @click="snackbarError = false"> Close </v-btn>
    </v-snackbar>

    <v-dialog persistent v-model="dialogPriceHeader" width="500">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          <v-layout align-center justify-space-between row fill-height>
            <div>FORM HARGA</div>
            <kbd v-if="dialogAct === 'edit'">{{
              selectedPriceHeader.headerCode
            }}</kbd>
            <kbd v-if="dialogAct === 'add'">CODE</kbd>
          </v-layout>
        </v-card-title>
        <v-card-text>
          <v-text-field
            label="Nama"
            placeholder="masukan nama"
            v-model="nameHeader"
            outline
          ></v-text-field>
          <!-- <v-menu
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
                v-model="formatedStartDateHeader"
                label="Tanggal Awal"
                outline
                readonly
                v-on="on"
                @blur="date = deFormatedDate(formatedStartDateHeader)"
              ></v-text-field>
            </template>
            <v-date-picker
              v-model="startDateHeaderForm"
              no-title
              @input="menuFormDateStart = false"
            ></v-date-picker>
          </v-menu>
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
                class="mr-2"
                v-model="formatedEndDateHeader"
                label="Tanggal Akhir"
                outline
                readonly
                v-on="on"
                @blur="date = deFormatedDate(formatedEndDateHeader)"
              ></v-text-field>
            </template>
            <v-date-picker
              v-model="endDateHeaderForm"
              :min="
                new Date(this.startDateHeaderForm).toISOString().substr(0, 10)
              "
              no-title
              @input="menuFormDateEnd = false"
            ></v-date-picker>
          </v-menu> -->
        </v-card-text>
        <v-divider></v-divider>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            :disabled="loading"
            flat
            @click="dialogPriceHeader = false"
          >
            BATAL
          </v-btn>
          <v-btn
            color="primary"
            :disabled="loading"
            flat
            @click="savePriceHeader"
          >
            SIMPAN
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog persistent v-model="dialogDeleteHeader" width="500">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          <v-layout align-center justify-space-between row fill-height>
            <div>KONFIRMASI</div>
          </v-layout>
        </v-card-title>
        <v-card-text
          >Apakah anda yakin akan menghapus harga
          <kbd class="mx-2">{{ selectedPriceHeader.headerCode }}</kbd>
          <span>{{ selectedPriceHeader.headerName }}</span> ?
        </v-card-text>
        <v-divider></v-divider>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            :disabled="loading"
            flat
            @click="dialogDeleteHeader = false"
          >
            BATAL
          </v-btn>
          <v-btn
            color="warning"
            :disabled="loading"
            flat
            @click="deletePriceHeader()"
          >
            YAKIN
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog persistent v-model="dialogCopyHarga" width="500">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          <v-layout align-center justify-space-between row fill-height>
            <div>FORM COPY HARGA</div>

            <kbd>CODE</kbd>
          </v-layout>
        </v-card-title>
        <v-card-text>
          <v-text-field
            label="Nama"
            placeholder="masukan nama"
            v-model="nameHeader"
            outline
          ></v-text-field>
          <v-autocomplete
            label="Price Header"
            v-model="selectedPriceHeaderCopy"
            :items="priceHeaderCopyList"
            item-text="headerName"
            outline
            return-object
            :search-input.sync="searchPriceHeaderCopy"
            no-data-text="Pilih Price Header"
          >
            <template slot="item" slot-scope="{ item }">
              <v-list-tile-content>
                <v-list-tile-title v-text="item.headerName"></v-list-tile-title>
              </v-list-tile-content>
            </template>
          </v-autocomplete>
          <v-checkbox v-model="copyPacket" label="Copy Packet"></v-checkbox>
          <!-- <v-menu
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
                v-model="formatedStartDateHeader"
                label="Tanggal Awal"
                outline
                readonly
                v-on="on"
                @blur="date = deFormatedDate(formatedStartDateHeader)"
              ></v-text-field>
            </template>
            <v-date-picker
              v-model="startDateHeaderForm"
              no-title
              @input="menuFormDateStart = false"
            ></v-date-picker>
          </v-menu>
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
                class="mr-2"
                v-model="formatedEndDateHeader"
                label="Tanggal Akhir"
                outline
                readonly
                v-on="on"
                @blur="date = deFormatedDate(formatedEndDateHeader)"
              ></v-text-field>
            </template>
            <v-date-picker
              v-model="endDateHeaderForm"
              :min="
                new Date(this.startDateHeaderForm).toISOString().substr(0, 10)
              "
              no-title
              @input="menuFormDateEnd = false"
            ></v-date-picker>
          </v-menu> -->
        </v-card-text>
        <v-divider></v-divider>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            :disabled="loading"
            flat
            @click="dialogCopyHarga = false"
          >
            BATAL
          </v-btn>
          <v-btn color="primary" :disabled="loading" flat @click="copyprice">
            SIMPAN
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-layout column>
      <v-layout align-center column>
        <v-toolbar dark color="primary">
          <v-toolbar-title class="white--text">HARGA</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn @click="openDialogAdd()" icon>
            <v-icon>add_box</v-icon>
          </v-btn>
          <v-btn @click="openDialogCopy()" icon>
            <v-icon>content_copy</v-icon>
          </v-btn>
        </v-toolbar>
        <v-card style="width: 100%;" class="mb-2 pa-2 searchbox">
          <!-- :disabled="loading" -->
          <v-text-field
            v-model="searchPrice"
            label="Cari nama"
            :loading="loading"
            hide-details
            outline
          ></v-text-field>
        </v-card>
      </v-layout>
      <v-card style="overflow-y: scroll; height: 65vh;" class="fill-height">
        <v-data-table
          :loading="loading"
          :items="priceHeaderList"
          class="v-table elevation-1"
          hide-actions
          hide-headers
        >
          <v-progress-linear
            v-slot:progress="loading"
            color="blue"
            :indeterminate="true"
          ></v-progress-linear>
          <template v-slot:items="props">
            <tr
              v-bind:class="{
                'deep-orange lighten-4': isSelected(props.item),
              }"
              @click="selectMe(props.item)"
            >
              <td width="85%" class="py-2">
                <!-- v-bind:class="{ 'amber lighten-4': isSelected(props.item) }" -->
                <v-layout align-start justify-start row fill-height>
                  <v-flex xs2>
                    <!-- <v-tooltip bottom>
                      <template v-slot:activator="{ on }"> -->
                    <!-- v-on="on" -->
                    <v-icon
                      color="success"
                      v-if="props.item.headerValidate === 'Y'"
                      class="ml-1"
                    >
                      check_circle
                    </v-icon>
                    <!-- </template>
                      <span>Sudah di validasi</span>
                    </v-tooltip> -->

                    <!-- <v-tooltip bottom>
                      <template v-slot:activator="{ on }"> -->
                    <!-- v-on="on" -->
                    <v-icon
                      color="blue"
                      v-if="
                        props.item.headerValidate === 'N' &&
                        props.item.readyValidate === 'Y'
                      "
                      class="ml-1"
                    >
                      info
                    </v-icon>
                    <!-- </template>
                      <span>Siap Validasi</span>
                    </v-tooltip> -->
                    <!-- <v-tooltip bottom>
                      <template v-slot:activator="{ on }"> -->
                    <!-- v-on="on" -->
                    <v-icon
                      color="warning"
                      v-if="
                        props.item.headerValidate === 'N' &&
                        props.item.readyValidate === 'N'
                      "
                      class="ml-1"
                    >
                      error
                    </v-icon>
                    <!-- </template>
                      <span>Belum mengisi price</span>
                    </v-tooltip> -->
                  </v-flex>
                  <v-flex xs10>
                    <p style="" class="mb-1">
                      <kbd class="mr-2">{{ props.item.headerCode }}</kbd>
                      <span class="font-weight-bold">
                        {{ props.item.headerName }}
                      </span>
                    </p></v-flex
                  >
                </v-layout>
                <!-- <div style="color: brown" class="mb-0">
                  {{ props.item.headerStartDate }} -
                  {{ props.item.headerEndDate }}
                </div> -->
              </td>
              <td width="15%" class="py-2">
                <!-- @click="selectMe(props.item)"
                  v-bind:class="{ 'amber lighten-4': isSelected(props.item) }" -->
                <v-layout justify-space-between row>
                  <v-icon
                    color="blue"
                    @click="openDialogEdit(props.item)"
                    class="mr-1"
                    v-if="props.item.headerValidate === 'N'"
                    style="cursor: pointer;"
                  >
                    edit
                  </v-icon>
                  <v-icon
                    color="red "
                    v-if="props.item.headerValidate === 'N'"
                    @click="openDialogDelete(props.item)"
                    class="ml-1"
                    style="cursor: pointer;"
                  >
                    delete
                  </v-icon>
                </v-layout>
              </td>
            </tr>
          </template>
        </v-data-table>
      </v-card>
      <v-card class="pa-2">
        <div class="text-xs-left">
          <v-pagination v-model="page" :length="totalPageHeader"></v-pagination>
        </div>
      </v-card>
    </v-layout>
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
  module.exports = {
    //   components: {
    //     "one-dialog-info": httpVueLoader("../../common/oneDialogInfo.vue"),
    //     "one-dialog-alert": httpVueLoader("../../common/oneDialogAlert.vue"),
    //   },
    mounted() {
      this.$store.dispatch("price/searchPriceHeader");
      this.$store.dispatch("price/getpricefilter");
      this.$store.dispatch("price/getpricefilter");
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

      closeDialogQrCode() {
        this.dialogQrCode = false;
      },
      copyprice() {
        this.$store.dispatch("price/copyharga");
      },
      savePriceHeader() {
        console.log(this.dialogAct);
        if (this.dialogAct === "add") {
          this.$store.dispatch("price/insertPriceHeader");
        } else if (this.dialogAct === "edit") {
          this.$store.dispatch("price/editPriceHeader");
        }
      },
      deletePriceHeader() {
        this.$store.dispatch("price/deletePriceHeader");
      },
      selectMe(val) {
        if (this.loading) {
          return;
        }

        this.selectedPriceHeader = val;
      },
      isSelected(val) {
        return this.selectedPriceHeader.headerID === val.headerID;
      },
      openDialogAdd() {
        this.dialogAct = "add";
        this.dialogPriceHeader = true;
      },
      openDialogCopy() {
        this.dialogCopyHarga = true;
        this.selectedPriceHeaderCopy = {};
        this.headerName = "";
      },
      openDialogDelete(val) {
        if (this.loading) {
          return;
        }
        this.selectedPriceHeader = val;
        this.dialogDeleteHeader = true;
      },
      openDialogEdit(val) {
        if (this.loading) {
          return;
        }
        this.dialogAct = "edit";
        // {
        //       "headerID": "0",
        //       "headerName": "",
        //       "headerStartDate": "",
        //       "headerEndDate": "",
        //       "headerCode": "CODE"
        //   }
        this.selectedPriceHeader = val;
        this.nameHeader = this.selectedPriceHeader.headerName;
        this.startDateHeaderForm = this.selectedPriceHeader.headerStartDate;
        this.endDateHeaderForm = this.selectedPriceHeader.headerEndDate;
        this.dialogPriceHeader = true;
      },
      handleChangeFIlter() {
        let submitted = [];
        data = this.priceTestList;
        if (data !== undefined && data !== null) {
          for (let i = 0; i < data.length; i++) {
            const e = data[i];
            if (
              e.priceAmount !== "" &&
              e.priceAmount !== null &&
              e.priceAmount !== undefined
            ) {
              if (
                isNaN(e.priceAmount) ||
                isNaN(e.priceDisc) ||
                isNaN(e.priceDiscRp)
              ) {
                alert("Input test " + e.testName + " salah, mohon ulangi");
                return;
              }
              diskon = parseFloat(e.priceDisc);
              diskonRp = parseFloat(e.priceDiscRp);
              amount = parseFloat(e.priceAmount);
              total = parseFloat(e.subTotal);
              if (diskon > 100) {
                alert(
                  "Diskon % test " +
                    e.testName +
                    " Tidak boleh lebih dari 100, mohon ulangi"
                );
                return;
              }
              if (diskon < 0) {
                alert(
                  "Diskon % test " +
                    e.testName +
                    " Tidak boleh kurang dari 0, mohon ulangi"
                );
                return;
              }
              if (diskonRp > amount) {
                alert(
                  "Diskon Rp test " +
                    e.testName +
                    " Tidak boleh lebih dari price amount, mohon ulangi"
                );
                return;
              }
              if (diskonRp < 0) {
                alert(
                  "Diskon Rp test " +
                    e.testName +
                    " Tidak boleh kurang dari 0, mohon ulangi"
                );
                return;
              }
              if (total < 0) {
                alert(
                  "Total test " +
                    e.testName +
                    " Tidak boleh kurang dari 0, mohon ulangi"
                );
                return;
              }
              if (total > amount) {
                console.log(total);
                console.log(amount);
                alert(
                  "Total test " +
                    e.testName +
                    " Tidak boleh lebih dari price amount, mohon ulangi"
                );
                return;
              }

              if (e.isChange == "Y") {
                submitted.push(e);
              }
            }
          }
        }
        console.log(submitted);
        // priceAmount
        // priceDisc
        // priceDiscRp
        // subTotal
        // testID
        return submitted;
      },

      thrsearchphautocomplete: _.debounce(function () {
        this.$store.dispatch("price/searchPriceHeaderAutocomplete", {
          search: this.searchPriceHeaderCopy,
        });
      }, 800),
    },
    computed: {
      priceTestList: {
        get() {
          return this.$store.state.price.priceTestList;
        },
        set(val) {
          this.$store.commit("price/update_priceTestList", val);
        },
      },
      searchPrice: {
        get() {
          return this.$store.state.price.searchPrice;
        },
        set(val) {
          let dt = this.handleChangeFIlter();
          if (dt.length > 0) {
            if (
              confirm(
                "Data belum belum tersimpan,  Apakah anda yakin untuk melanjutkan ?"
              )
            ) {
              this.$store.commit("price/update_searchPrice", val);
              this.$store.dispatch("price/searchPriceHeader");
              return;
            } else {
              return;
            }
          } else {
            this.$store.commit("price/update_searchPrice", val);
            this.$store.dispatch("price/searchPriceHeader");
          }
        },
      },
      priceHeaderList: {
        get() {
          return this.$store.state.price.priceHeaderList;
        },
        set(val) {
          this.$store.commit("price/update_priceHeaderList", val);
        },
      },
      loading: {
        get() {
          return this.$store.state.price.loading;
        },
        set(val) {
          this.$store.commit("price/update_loading", val);
        },
      },
      page: {
        get() {
          return this.$store.state.price.page;
        },
        set(val) {
          let dt = this.handleChangeFIlter();
          if (dt.length > 0) {
            if (
              confirm(
                "Data belum belum tersimpan,  Apakah anda yakin untuk melanjutkan ?"
              )
            ) {
              this.$store.commit("price/update_page", val);
              this.selectedPriceHeader = {
                headerID: "0",
                headerName: "",
                headerStartDate: "",
                headerEndDate: "",
                headerCode: "CODE",
              };
              this.$store.dispatch("price/searchPriceHeader");
              return;
            } else {
              return;
            }
          } else {
            this.$store.commit("price/update_page", val);
            this.selectedPriceHeader = {
              headerID: "0",
              headerName: "",
              headerStartDate: "",
              headerEndDate: "",
              headerCode: "CODE",
            };
            this.$store.dispatch("price/searchPriceHeader");
          }
        },
      },
      totalPageHeader: {
        get() {
          return this.$store.state.price.totalPageHeader;
        },
        set(val) {
          this.$store.commit("price/update_totalPageHeader", val);
        },
      },
      errorMsg: {
        get() {
          return this.$store.state.price.errorMsg;
        },
        set(val) {
          this.$store.commit("price/update_errorMsg", val);
        },
      },
      snackbarError: {
        get() {
          return this.$store.state.price.snackbarError;
        },
        set(val) {
          this.$store.commit("price/update_snackbarError", val);
        },
      },
      snackbarSuccess: {
        get() {
          return this.$store.state.price.snackbarSuccess;
        },
        set(val) {
          this.$store.commit("price/update_snackbarSuccess", val);
        },
      },
      selectedPriceHeader: {
        get() {
          return this.$store.state.price.selectedPriceHeader;
        },
        set(val) {
          let dt = this.handleChangeFIlter();
          if (dt.length > 0) {
            if (
              confirm(
                "Data belum belum tersimpan,  Apakah anda yakin untuk melanjutkan ?"
              )
            ) {
              this.$store.commit("price/update_selectedPriceHeader", val);
              this.$store.dispatch("price/searchpricetest");
              return;
            } else {
              return;
            }
          } else {
            this.$store.commit("price/update_selectedPriceHeader", val);
            this.$store.dispatch("price/searchpricetest");
          }
        },
      },

      successMsg: {
        get() {
          return this.$store.state.price.successMsg;
        },
        set(val) {
          this.$store.commit("price/update_successMsg", val);
        },
      },
      dialogPriceHeader: {
        get() {
          return this.$store.state.price.dialogPriceHeader;
        },
        set(val) {
          this.$store.commit("price/update_dialogPriceHeader", val);
          if (val === false) {
            this.nameHeader = "";
          }
        },
      },
      startDateHeaderForm: {
        get() {
          return this.$store.state.price.startDateHeader;
        },
        set(val) {
          console.log("sd");
          console.log(val);
          this.$store.commit("price/update_startDateHeader", val);
          let sd = new Date(val);
          let ed = new Date(this.endDateHeaderForm);
          if (sd > ed) {
            this.endDateHeaderForm = val;
          }
        },
      },
      endDateHeaderForm: {
        get() {
          return this.$store.state.price.endDateHeader;
        },
        set(val) {
          this.$store.commit("price/update_endDateHeader", val);
          console.log("ed");
          console.log(val);
        },
      },
      nameHeader: {
        get() {
          return this.$store.state.price.nameHeader;
        },
        set(val) {
          this.$store.commit("price/update_nameHeader", val);
        },
      },
      dialogDeleteHeader: {
        get() {
          return this.$store.state.price.dialogDeleteHeader;
        },
        set(val) {
          this.$store.commit("price/update_dialogDeleteHeader", val);
        },
      },
      dialogCopyHarga: {
        get() {
          return this.$store.state.price.dialogCopyHarga;
        },
        set(val) {
          this.$store.commit("price/update_dialogCopyHarga", val);
          if (val === false) {
            this.nameHeader = "";
          }
        },
      },
      priceHeaderCopyList: {
        get() {
          return this.$store.state.price.priceHeaderCopyList;
        },
        set(val) {
          this.$store.commit("price/update_priceHeaderCopyList", val);
        },
      },
      selectedPriceHeaderCopy: {
        get() {
          return this.$store.state.price.selectedPriceHeaderCopy;
        },
        set(val) {
          this.$store.commit("price/update_selectedPriceHeaderCopy", val);
        },
      },
      copyPacket: {
        get() {
          return this.$store.state.price.copyPacket;
        },
        set(val) {
          this.$store.commit("price/update_copyPacket", val);
        },
      },
      formatedStartDateHeader() {
        return this.formatDate(this.startDateHeaderForm);
      },
      formatedEndDateHeader() {
        return this.formatDate(this.endDateHeaderForm);
      },
    },
    watch: {
      // search_company(val, old) {
      //   if (val == old) return;
      //   if (!val) return;
      //   if (val.length < 1) return;
      //   if (this.$store.state.patient.update_autocomplete_status == 1) return;
      //   this.thr_search_company();
      // },
      // search_doctor(val, old) {
      //   if (val == old) return;
      //   if (!val) return;
      //   if (val.length < 1) return;
      //   if (this.$store.state.patient.update_autocomplete_status == 1) return;
      //   this.thr_search_doctor();
      // },
      // search_test(val, old) {
      //   if (val == old) return;
      //   if (!val) return;
      //   if (val.length < 1) return;
      //   if (this.$store.state.patient.update_autocomplete_status == 1) return;
      //   this.thr_search_test();
      // },
      searchPriceHeaderCopy(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.thrsearchphautocomplete();
      },
    },
    data() {
      return {
        searchPriceHeaderCopy: "",
        selected_delivery: {},
        search_company: "",
        search_test: "",
        menuFormDateStart: false,
        menuFormDateEnd: false,
        date: new Date().toISOString().substr(0, 10),
        dialogAct: "add",
        items: [],
        menustartdate: false,
        menuenddate: false,
        errors: [],
        sheet: false,
        indeterminatex: false,
        checkednotall: false,
        bar_chx_all: false,
        selected_barcode: [],
        dialogtimeline: false,
        search_doctor: "",
        headers: [
          {
            text: "",
            align: "center",
            sortable: false,
            value: "lab",
            width: "2%",
            class: "pa-2 blue darken-2 white--text",
          },
          {
            text: "NO",
            align: "center",
            sortable: false,
            value: "lab",
            width: "5%",
            class: "pa-2 blue darken-2 white--text",
          },
          {
            text: "NO REG",
            align: "center",
            sortable: false,
            value: "lab",
            width: "8%",
            class: "pa-2 blue darken-2 white--text",
          },
          {
            text: "NAMA",
            align: "center",
            sortable: false,
            value: "name",
            width: "15%",
            class: "pa-2 blue darken-2 white--text",
          },
          {
            text: "CORPORATE",
            align: "center",
            sortable: false,
            value: "name",
            width: "15%",
            class: "pa-2 blue darken-2 white--text",
          },
          {
            text: "PEMERIKSAAN",
            align: "center",
            sortable: false,
            value: "status",
            width: "20%",
            class: "pa-2 blue darken-2 white--text",
          },

          {
            text: "TOTAL",
            align: "center",
            sortable: false,
            value: "status",
            width: "5%",
            class: "pa-2 blue darken-2 white--text",
          },
        ],
      };
    },
  };
</script>
