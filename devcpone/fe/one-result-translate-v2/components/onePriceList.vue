<template>
  <div>
    <v-dialog persistent v-model="dialogValidasi" width="500">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          <v-layout align-center justify-space-between row fill-height>
            <div>KONFIRMASI</div>
          </v-layout>
        </v-card-title>
        <v-card-text
          >Apakah anda yakin akan mevalidasi
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
            @click="dialogValidasi = false"
          >
            BATAL
          </v-btn>
          <v-btn
            color="success"
            :disabled="loading"
            flat
            @click="validateHeader()"
          >
            YAKIN
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-layout class="fill-height" column>
      <v-layout align-center column>
        <v-toolbar dark color="primary">
          <v-toolbar-title class="white--text">
            <kbd class="mr-2">{{ this.selectedPriceHeader.headerCode }}</kbd>
            {{ this.selectedPriceHeader.headerName }}</v-toolbar-title
          >
          <v-spacer></v-spacer>
          <!-- v-if="selectedPriceHeader.headerValidate === 'N'" -->
          <v-btn color="success" :disabled="loading" @click="handleSave()"
            >Simpan</v-btn
          >
          <!-- selectedPriceHeader.headerValidate === 'N' && -->
          <v-btn
            :disabled="loading"
            color="warning"
            @click="dialogValidasi = true"
            >Validasi
          </v-btn>
          <!-- <v-btn
            :disabled="loading"
            v-if="
              selectedPriceHeader.headerValidate === 'Y' &&
              selectedPriceHeader.validatePacket === 'Y' &&
              selectedPriceHeader.readyValidate === 'Y'
            "
            color="warning"
            @click="dialogValidasi = true"
            >Validasi
          </v-btn> -->
        </v-toolbar>
        <v-card style="width: 100%" class="mb-2 pa-2 searchbox">
          <v-layout row>
            <v-flex xs4 class="mr-1">
              <v-text-field
                label="Kode/nama"
                hide-details
                :loading="loading"
                v-model="filterName"
                outline
              ></v-text-field>
            </v-flex>
            <v-flex xs4 class="mr-1 ml-1">
              <v-autocomplete
                label="Sub Group"
                v-model="selectedFilterSubGroup"
                :items="filterSubGroup"
                item-text="name"
                outline
                hide-details
                :disabled="loading"
                return-object
                :loading="loading"
                no-data-text="Pilih Sub Group"
              >
                <template slot="item" slot-scope="{ item }">
                  <v-list-tile-content>
                    <v-list-tile-title v-text="item.name"></v-list-tile-title>
                  </v-list-tile-content>
                </template>
              </v-autocomplete>
            </v-flex>
            <v-flex xs4 class="ml-1">
              <v-autocomplete
                label="Status"
                v-model="selectedFilterStatus"
                :items="filterStatus"
                item-text="name"
                outline
                hide-details
                return-object
                :disabled="loading"
                :loading="loading"
                no-data-text="Pilih Status"
              >
                <template slot="item" slot-scope="{ item }">
                  <v-list-tile-content>
                    <v-list-tile-title v-text="item.name"></v-list-tile-title>
                  </v-list-tile-content>
                </template>
              </v-autocomplete>
            </v-flex>
          </v-layout>
        </v-card>
      </v-layout>
      <v-card style="overflow-y: scroll; height: 65vh" class="fill-height">
        <v-data-table
          :loading="loading"
          :items="priceTestList"
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
            <tr>
              <td class="py-2">
                <div style="color: brown" class="mb-0">
                  <v-btn
                    small
                    color="success"
                    class="white--text"
                    v-if="props.item.status === 'Y'"
                  >
                    <v-icon left dark>check</v-icon>
                  </v-btn>
                  <v-btn
                    small
                    color="error"
                    class="white--text"
                    v-if="props.item.status === 'N'"
                  >
                    <v-icon right dark>close</v-icon>
                  </v-btn>
                </div>
              </td>
              <td class="py-2">
                <!-- v-bind:class="{ 'amber lighten-4': isSelected(props.item) }" -->
                <p style="" class="mb-1">
                  <kbd class="mr-2">{{ props.item.testCode }}</kbd>
                  <span class="font-weight-bold">
                    {{ props.item.testName }}
                  </span>
                </p>
              </td>
              <td class="py-2">
                <!-- v-bind:class="{ 'amber lighten-4': isSelected(props.item) }" -->
                <v-text-field
                  label="Amount"
                  type="number"
                  v-model="props.item.priceAmount"
                  hide-details
                  :disabled="
                    (selectedPriceHeader.headerValidate === 'Y' &&
                      props.item.status === 'Y') ||
                    loading
                  "
                  @input="handleChangeAmount(props.item)"
                  outline
                ></v-text-field>
                <!-- v-on:change="handleChangeAmount(props.item.priceAmount)" -->
                <!-- :rules="[rules.min]" -->
              </td>
              <td class="py-2">
                <v-text-field
                  label="Diskon %"
                  type="number"
                  v-model="props.item.priceDisc"
                  @input="handleChangeDisc(props.item)"
                  :disabled="
                    props.item.priceAmount === NaN ||
                    props.item.priceAmount === null ||
                    props.item.priceAmount === undefined ||
                    props.item.priceAmount <= 0 ||
                    props.item.priceAmount === '' ||
                    (selectedPriceHeader.headerValidate === 'Y' &&
                      props.item.status === 'Y') ||
                    loading
                  "
                  hide-details
                  outline
                ></v-text-field>
                <!-- :rules="[rules.min, rules.maxPersen]" -->
              </td>
              <td class="py-2">
                <!-- v-bind:class="{ 'amber lighten-4': isSelected(props.item) }" -->
                <v-text-field
                  label="Diskon Rp"
                  type="number"
                  v-model="props.item.priceDiscRp"
                  @input="handleChangeDiscRp(props.item)"
                  :disabled="
                    props.item.priceAmount === NaN ||
                    props.item.priceAmount === null ||
                    props.item.priceAmount === undefined ||
                    props.item.priceAmount <= 0 ||
                    props.item.priceAmount === '' ||
                    (selectedPriceHeader.headerValidate === 'Y' &&
                      props.item.status === 'Y') ||
                    loading
                  "
                  hide-details
                  outline
                ></v-text-field>
                <!-- :rules="[rules.min]" -->
              </td>
              <td class="py-2">
                <!-- v-bind:class="{ 'amber lighten-4': isSelected(props.item) }" -->
                <v-text-field
                  label="Total"
                  type="number"
                  readonly
                  v-model="props.item.subTotal"
                  hide-details
                  outline
                ></v-text-field>
                <!-- :rules="[rules.min]" -->
              </td>
            </tr>
          </template>
        </v-data-table>
      </v-card>
      <v-card class="pa-2">
        <div class="text-xs-left">
          <v-pagination
            v-model="priceTestPage"
            :length="priceTestPageTotal"
          ></v-pagination>
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
// const { data } = require("./onePriceHeader.vue");

module.exports = {
  //   components: {
  //     "one-dialog-info": httpVueLoader("../../common/oneDialogInfo.vue"),
  //     "one-dialog-alert": httpVueLoader("../../common/oneDialogAlert.vue"),
  //   },
  mounted() {},
  methods: {
    closeDialogQrCode() {
      this.dialogQrCode = false;
    },
    handleChangeAmount(data) {
      console.log(data);
      // priceAmount
      // priceDisc
      // priceDiscRp
      // subTotal
      // testID
      if (data.priceAmount.trim() === "") {
        return;
      }
      data.isChange = "Y";
      cek = parseFloat(data.priceAmount);
      if (isNaN(cek)) {
        data.subTotal = 0;
        alert("Inputan tes " + data.testName + "harus berupa angka");
        return;
      }
      if (parseFloat(data.priceAmount) < 0) {
        alert(" price amount test " + data.testName + "Kurang dari 0");
        return;
      }
      amount = parseFloat(data.priceAmount);
      diskon = 0;
      if (
        data.priceDisc !== "" &&
        data.priceDisc !== null &&
        data.priceDisc !== "0" &&
        data.priceDisc !== 0
      ) {
        diskon = parseFloat(data.priceDisc);
      }

      diskonRp = 0;
      if (
        data.priceDiscRp !== "" &&
        data.priceDiscRp !== null &&
        data.priceDiscRp !== "0" &&
        data.priceDiscRp !== 0
      ) {
        diskonRp = parseFloat(data.priceDiscRp);
      }
      total = 0;
      if (data.subTotal !== "" && data.subTotal !== null) {
        total = parseFloat(data.subTotal);
      }
      let newTotal = 0;

      diskonAmount = (diskon / 100) * amount;
      newTotal = amount - diskonAmount - diskonRp;

      console.log("new total");
      console.log(newTotal);
      if (newTotal < 0) {
        alert("Total tes " + data.testName + " tidak boleh kurang dari 0");
        return;
      }
      if (newTotal == NaN) {
        newTotal = 0;
      }
      data.subTotal = newTotal;
    },
    handleChangeDisc(data) {
      data.isChange = "Y";

      console.log(data);
      // data.priceDiscRp = 0;
      // priceAmount
      // priceDisc
      // priceDiscRp
      // subTotal
      // testID
      if (data.priceDisc.trim() === "") {
        return;
      }
      cek = parseFloat(data.priceDisc);
      if (isNaN(cek)) {
        alert("Inputan tes " + data.testName + "harus berupa angka");
        return;
      }
      if (parseFloat(data.priceAmount) < 0) {
        alert(" price amount test " + data.testName + "Kurang dari 0");
        return;
      }
      amount = parseFloat(data.priceAmount);
      diskon = 0;
      if (
        data.priceDisc !== "" &&
        data.priceDisc !== null &&
        data.priceDisc !== "0" &&
        data.priceDisc !== 0
      ) {
        diskon = parseFloat(data.priceDisc);
      }
      if (diskon < 0 || diskon > 100) {
        alert(
          "Diskon tes " +
            data.testName +
            " tidak boleh kurang dari 0 dan tidak boleh lebih dari 100"
        );
        return;
      }

      diskonRp = 0;
      if (
        data.priceDiscRp !== "" &&
        data.priceDiscRp !== null &&
        data.priceDiscRp !== "0" &&
        data.priceDiscRp !== 0
      ) {
        diskonRp = parseFloat(data.priceDiscRp);
      }
      total = 0;
      if (data.subTotal !== "" && data.subTotal !== null) {
        total = parseFloat(data.subTotal);
      }
      let newTotal = 0;

      diskonAmount = (diskon / 100) * amount;
      newTotal = amount - diskonAmount - diskonRp;

      if (newTotal < 0) {
        alert("Total tes " + data.testName + " tidak boleh kurang dari 0");
        return;
      }
      console.log("new total");
      console.log(newTotal);
      if (newTotal < 0) {
        alert(
          "Total tes " +
            data.testName +
            " tidak boleh kurang dari  price amount"
        );
        return;
      }
      if (newTotal == NaN) {
        newTotal = 0;
      }
      data.subTotal = newTotal;
    },
    handleChangeDiscRp(data) {
      data.isChange = "Y";
      if (data.priceDisc.trim() === "") {
        return;
      }

      console.log(data);

      // priceAmount
      // priceDisc
      // priceDiscRp
      // subTotal
      // testID
      if (data.priceDiscRp.trim() === "") {
        return;
      }
      cek = parseFloat(data.priceDiscRp);
      if (isNaN(cek) && data.priceDiscRp !== "") {
        alert("Inputan tes " + data.testName + "harus berupa angka");
        return;
      }
      if (parseFloat(data.priceDiscRp) < 0) {
        alert(" price amount test " + data.testName + "Kurang dari 0");
        return;
      }
      amount = parseFloat(data.priceAmount);
      diskon = 0;
      if (
        data.priceDisc !== "" &&
        data.priceDisc !== null &&
        data.priceDisc !== "0" &&
        data.priceDisc !== 0
      ) {
        diskon = parseFloat(data.priceDisc);
      }

      diskonRp = 0;
      if (
        data.priceDiscRp !== "" &&
        data.priceDiscRp !== null &&
        data.priceDiscRp !== "0" &&
        data.priceDiscRp !== 0
      ) {
        diskonRp = parseFloat(data.priceDiscRp);
      }
      if (diskonRp < 0 || diskonRp > amount) {
        alert(
          "Diskon tes " +
            data.testName +
            " tidak boleh kurang dari 0 dan tidak boleh lebih dari price amount"
        );
        return;
      }
      total = 0;
      if (data.subTotal !== "" && data.subTotal !== null) {
        total = parseFloat(data.subTotal);
      }
      let newTotal = 0;
      diskonAmount = (diskon / 100) * amount;
      newTotal = amount - diskonAmount - diskonRp;

      console.log("new total");
      console.log(newTotal);
      if (newTotal < 0) {
        alert("Total tes " + data.testName + " tidak boleh kurang dari 0");
        return;
      }
      if (newTotal == NaN) {
        newTotal = 0;
      }
      data.subTotal = newTotal;
    },
    handleSave() {
      let submitted = [];
      data = this.priceTestList;
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
      console.log(submitted);
      if (submitted.length > 0) {
        console.log("submitted > 0");
        this.$store.dispatch("price/savetest", {
          test: submitted,
        });
      } else {
        alert("Tidak ada yang perlu disimpan");
      }
      // priceAmount
      // priceDisc
      // priceDiscRp
      // subTotal
      // testID
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
    validateHeader() {
      this.$store.dispatch("price/validateheader");
    },
  },
  computed: {
    filterName: {
      get() {
        return this.$store.state.price.filterName;
      },
      set(val) {
        let dt = this.handleChangeFIlter();
        if (dt !== undefined && dt !== null) {
          if (dt.length > 0) {
            if (
              confirm(
                "Data belum belum tersimpan,  Apakah anda yakin untuk melanjutkan ?"
              )
            ) {
              this.$store.commit("price/update_filterName", val);
              this.$store.dispatch("price/searchpricetest");
              return;
            } else {
              return;
            }
          } else {
            this.$store.commit("price/update_filterName", val);
            this.$store.dispatch("price/searchpricetest");
          }
        }
      },
    },
    filterSubGroup: {
      get() {
        return this.$store.state.price.filterSubGroup;
      },
      set(val) {
        this.$store.commit("price/update_filterSubGroup", val);
      },
    },
    filterStatus: {
      get() {
        return this.$store.state.price.filterStatus;
      },
      set(val) {
        this.$store.commit("price/update_filterStatus", val);
      },
    },
    selectedFilterSubGroup: {
      get() {
        return this.$store.state.price.selectedFilterSubGroup;
      },
      set(val) {
        let dt = this.handleChangeFIlter();
        if (dt !== undefined && dt !== null) {
          if (dt.length > 0) {
            if (
              confirm(
                "Data belum belum tersimpan,  Apakah anda yakin untuk melanjutkan ?"
              )
            ) {
              this.$store.commit("price/update_selectedFilterSubGroup", val);
              this.$store.dispatch("price/searchpricetest");
              return;
            } else {
              return;
            }
          } else {
            this.$store.commit("price/update_selectedFilterSubGroup", val);
            this.$store.dispatch("price/searchpricetest");
          }
        }
      },
    },
    selectedFilterStatus: {
      get() {
        return this.$store.state.price.selectedFilterStatus;
      },
      set(val) {
        let dt = this.handleChangeFIlter();
        if (dt !== undefined && dt !== null) {
          if (dt.length > 0) {
            if (
              confirm(
                "Data belum belum tersimpan,  Apakah anda yakin untuk melanjutkan ?"
              )
            ) {
              this.$store.commit("price/update_selectedFilterStatus", val);
              this.$store.dispatch("price/searchpricetest");
              return;
            } else {
              return;
            }
          } else {
            this.$store.commit("price/update_selectedFilterStatus", val);
            this.$store.dispatch("price/searchpricetest");
          }
        }
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
        this.$store.commit("price/update_selectedPriceHeader", val);
        this.$store.dispatch("price/searchpricetest");
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
    priceTestList: {
      get() {
        return this.$store.state.price.priceTestList;
      },
      set(val) {
        this.$store.commit("price/update_priceTestList", val);
      },
    },
    priceTestPageTotal: {
      get() {
        return this.$store.state.price.priceTestPageTotal;
      },
      set(val) {
        this.$store.commit("price/update_priceTestPageTotal", val);
      },
    },
    dialogValidasi: {
      get() {
        return this.$store.state.price.dialogValidasi;
      },
      set(val) {
        this.$store.commit("price/update_dialogValidasi", val);
      },
    },
    priceTestPage: {
      get() {
        return this.$store.state.price.priceTestPage;
      },
      set(val) {
        dt = this.handleChangeFIlter();
        if (dt.length > 0) {
          if (
            confirm(
              "Data belum belum tersimpan,  Apakah anda yakin untuk melanjutkan ?"
            )
          ) {
            this.$store.commit("price/update_priceTestPage", val);
            this.$store.dispatch("price/searchpricetest");
          }
        } else {
          this.$store.commit("price/update_priceTestPage", val);
          this.$store.dispatch("price/searchpricetest");
        }
      },
    },
  },
  watch: {},
  data() {
    return {
      selected_delivery: {},
      search_company: "",
      search_test: "",
      menufilterdatestart: false,
      menufilterdateend: false,
      date: new Date().toISOString().substr(0, 10),
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
      dialogWarning: false,
      dialogWarningMsg: "",
      rules: {
        min: (v) => v > 0 || "Minimum value 1",
        maxPersen: (v) => v <= 100 || "Maximum value 100",
      },
      headers: [
        {
          text: "STATUS",
          align: "center",
          sortable: false,
          value: "lab",
          width: "10%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "TEST",
          align: "center",
          sortable: false,
          value: "lab",
          width: "25%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "AMOUNT",
          align: "center",
          sortable: false,
          value: "lab",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "DISKON",
          align: "center",
          sortable: false,
          value: "name",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "DISKON RP",
          align: "center",
          sortable: false,
          value: "name",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "TOTAL",
          align: "center",
          sortable: false,
          value: "status",
          width: "20%",
          class: "pa-2 blue darken-2 white--text",
        },
      ],
    };
  },
};
</script>
